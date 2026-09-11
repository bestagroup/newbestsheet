<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\KPI;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectCommitment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Morilog\Jalali\Jalalian;
use Throwable;

class InvestmentReminderService
{
    public function __construct(
        private readonly OperationalRecipientResolver $recipients,
        private readonly OperationalNotificationService $notifications,
    ) {}

    /** @return array{kpis:int,commitments:int,calendar:int} */
    public function process(?Carbon $now = null): array
    {
        $now ??= now();

        return [
            'kpis' => $this->processKpis($now),
            'commitments' => $this->processCommitments($now),
            'calendar' => $this->processCalendar($now),
        ];
    }

    public function alertsForProject(Project $project, ?Carbon $now = null, int $windowDays = 14): Collection
    {
        $now ??= now();
        $alerts = collect();
        $project->loadMissing(['kpis', 'commitments.commitment']);

        foreach ($project->kpis as $kpi) {
            if ($kpi->completed_at) {
                continue;
            }
            $due = $this->kpiDueDate($kpi);
            if (! $due) {
                continue;
            }
            $remaining = (int) $now->copy()->startOfDay()->diffInDays($due->copy()->startOfDay(), false);
            if ($remaining > $windowDays) {
                continue;
            }
            $alerts->push([
                'type' => 'kpi',
                'title' => $kpi->title,
                'project_id' => $project->id,
                'project_title' => $project->title,
                'due_date' => $kpi->deadline ?: $due->format('Y-m-d'),
                'days_remaining' => $remaining,
                'severity' => $remaining < 0 ? 'overdue' : ($remaining <= 1 ? 'critical' : 'warning'),
            ]);
        }

        foreach ($project->commitments as $item) {
            if ($item->isCompleted() || ! $item->due_at) {
                continue;
            }
            $remaining = (int) $now->copy()->startOfDay()->diffInDays($item->due_at->copy()->startOfDay(), false);
            if ($remaining > $windowDays) {
                continue;
            }
            $alerts->push([
                'type' => 'commitment',
                'title' => $item->commitment?->title ?: 'تعهد پروژه',
                'project_id' => $project->id,
                'project_title' => $project->title,
                'due_date' => $item->due_date ?: $item->due_at->format('Y-m-d'),
                'days_remaining' => $remaining,
                'severity' => $remaining < 0 ? 'overdue' : ($remaining <= 1 ? 'critical' : 'warning'),
            ]);
        }

        return $alerts->sortBy(fn (array $item) => $item['days_remaining'])->values();
    }

    public function alertsForUser(User $user, ?Carbon $now = null, int $limit = 12): Collection
    {
        $user->loadMissing('roles');
        $isGlobal = $user->hasRole(['superadmin', 'manager']);

        $projectQuery = Project::query()->with(['kpis', 'commitments.commitment']);
        if (! $isGlobal) {
            $assignedProjectIds = ProjectAssignment::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->pluck('project_id');

            $ownedProjectIds = Project::query()
                ->where(function ($query) use ($user): void {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('user_id', $user->id));
                })
                ->pluck('id');

            $projectIds = $assignedProjectIds
                ->merge($ownedProjectIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $projectQuery->whereIn('id', $projectIds);
        }

        return $projectQuery->get()
            ->flatMap(fn (Project $project) => $this->alertsForProject($project, $now))
            ->sortBy(fn (array $item) => $item['days_remaining'])
            ->take($limit)
            ->values();
    }

    private function processKpis(Carbon $now): int
    {
        $processed = 0;
        $beforeDays = collect(config('operations.reminders.kpi_days_before', [7, 3, 1, 0]))->map(fn ($v) => (int) $v);
        $overdueDays = collect(config('operations.reminders.kpi_overdue_days', [1, 3, 7]))->map(fn ($v) => (int) $v);

        KPI::query()
            ->current()
            ->whereNull('completed_at')
            ->where(function ($query): void {
                $query->whereNotNull('deadline_at')->orWhereNotNull('deadline');
            })
            ->with(['project.user', 'project.company', 'project.assignments.role', 'project.assignments.user'])
            ->orderBy('id')
            ->chunkById(100, function ($kpis) use ($now, $beforeDays, $overdueDays, &$processed): void {
                foreach ($kpis as $kpi) {
                    $due = $this->kpiDueDate($kpi);
                    if (! $due || ! $kpi->project) {
                        continue;
                    }

                    $remaining = (int) $now->copy()->startOfDay()->diffInDays($due->copy()->startOfDay(), false);
                    if ($beforeDays->contains($remaining)) {
                        $this->notifyProjectDeadline(
                            $kpi->project,
                            $kpi,
                            'kpi.due',
                            'd-'.$remaining,
                            $remaining === 0 ? 'سررسید KPI امروز است' : "{$remaining} روز تا سررسید KPI",
                            sprintf('KPI «%s» پروژه «%s» در تاریخ %s سررسید می‌شود.', $kpi->title, $kpi->project->title, $kpi->deadline ?: $due->format('Y-m-d')),
                            $remaining <= 1 ? 'critical' : 'warning',
                            false
                        );
                        $processed++;
                    } elseif ($remaining < 0 && $overdueDays->contains(abs($remaining))) {
                        $this->notifyProjectDeadline(
                            $kpi->project,
                            $kpi,
                            'kpi.overdue',
                            'od-'.abs($remaining),
                            'KPI معوق شده است',
                            sprintf('KPI «%s» پروژه «%s» %d روز از سررسید گذشته است.', $kpi->title, $kpi->project->title, abs($remaining)),
                            'overdue',
                            true
                        );
                        $processed++;
                    }
                }
            });

        return $processed;
    }

    private function processCommitments(Carbon $now): int
    {
        $processed = 0;
        $beforeDays = collect(config('operations.reminders.commitment_days_before', [14, 7, 3, 1, 0]))->map(fn ($v) => (int) $v);
        $overdueDays = collect(config('operations.reminders.commitment_overdue_days', [1, 3, 7]))->map(fn ($v) => (int) $v);

        ProjectCommitment::query()
            ->where('status', 'pending')
            ->whereNull('completed_at')
            ->whereNotNull('due_at')
            ->with(['commitment', 'project.user', 'project.company', 'project.assignments.role', 'project.assignments.user'])
            ->orderBy('id')
            ->chunkById(100, function ($items) use ($now, $beforeDays, $overdueDays, &$processed): void {
                foreach ($items as $item) {
                    if (! $item->project || ! $item->due_at) {
                        continue;
                    }

                    $remaining = (int) $now->copy()->startOfDay()->diffInDays($item->due_at->copy()->startOfDay(), false);
                    $title = $item->commitment?->title ?: 'تعهد پروژه';
                    if ($beforeDays->contains($remaining)) {
                        $this->notifyProjectDeadline(
                            $item->project,
                            $item,
                            'commitment.due',
                            'd-'.$remaining,
                            $remaining === 0 ? 'سررسید تعهد امروز است' : "{$remaining} روز تا سررسید تعهد",
                            sprintf('تعهد «%s» پروژه «%s» در تاریخ %s سررسید می‌شود.', $title, $item->project->title, $item->due_date ?: $item->due_at->format('Y-m-d')),
                            $remaining <= 1 ? 'critical' : 'warning',
                            false
                        );
                        $processed++;
                    } elseif ($remaining < 0 && $overdueDays->contains(abs($remaining))) {
                        $this->notifyProjectDeadline(
                            $item->project,
                            $item,
                            'commitment.overdue',
                            'od-'.abs($remaining),
                            'تعهد معوق شده است',
                            sprintf('تعهد «%s» پروژه «%s» %d روز از سررسید گذشته است.', $title, $item->project->title, abs($remaining)),
                            'overdue',
                            true
                        );
                        $processed++;
                    }
                }
            });

        return $processed;
    }

    private function processCalendar(Carbon $now): int
    {
        $processed = 0;
        $thresholds = collect(config('operations.reminders.calendar_minutes_before', [1440, 60]))->map(fn ($v) => (int) $v)->sortDesc()->values();
        if ($thresholds->isEmpty()) {
            return 0;
        }

        $nowJalali = Jalalian::fromCarbon($now)->format('Y-m-d H:i:s');
        Calendar::query()
            ->with('creator:id,name')
            ->where('start', '>=', $nowJalali)
            ->orderBy('start')
            ->limit(500)
            ->get()
            ->each(function (Calendar $calendar) use ($now, $thresholds, &$processed): void {
                $start = $this->calendarStart($calendar);
                if (! $start || $start->lte($now)) {
                    return;
                }

                $remainingMinutes = (int) floor($now->diffInMinutes($start, false));
                foreach ($thresholds as $threshold) {
                    $window = min(30, max(15, $threshold));
                    if ($remainingMinutes <= $threshold && $remainingMinutes > max(0, $threshold - $window)) {
                        $guestIds = collect($calendar->guests ?? [])
                            ->map(fn ($id) => (int) $id)
                            ->reject(fn ($id) => $id === (int) $calendar->created_by)
                            ->unique();
                        $guests = User::query()->whereIn('id', $guestIds)->get();
                        if ($guests->isEmpty()) {
                            break;
                        }

                        $message = sprintf(
                            'یادآوری جلسه «%s» در %s. دعوت‌کننده: %s%s%s',
                            $calendar->title,
                            $calendar->start,
                            $calendar->creator?->name ?? 'سامانه',
                            $calendar->location ? '، محل: '.$calendar->location : '',
                            $calendar->description ? '، شرح: '.mb_substr($calendar->description, 0, 180) : ''
                        );

                        $this->notifications->deliver(
                            'calendar.reminder',
                            'm-'.$threshold,
                            $calendar,
                            $guests,
                            [
                                'title' => 'یادآوری جلسه',
                                'message' => $message,
                                'url' => route('calendar.index'),
                                'icon' => 'mdi-calendar-clock-outline',
                                'category' => 'calendar',
                                'severity' => $threshold <= 60 ? 'critical' : 'warning',
                                'actor_id' => $calendar->created_by,
                            ],
                            $message
                        );
                        $processed++;
                        break;
                    }
                }
            });

        return $processed;
    }

    private function notifyProjectDeadline(
        Project $project,
        Model $related,
        string $eventKey,
        string $triggerKey,
        string $title,
        string $message,
        string $severity,
        bool $escalate
    ): void {
        $experts = $this->recipients->investorExperts($project);
        if ($experts->isEmpty()) {
            $experts = $this->recipients->escalationManagers();
        }
        if ($escalate) {
            $experts = $experts->merge($this->recipients->escalationManagers())->unique('id')->values();
        }

        $this->notifications->deliver(
            $eventKey,
            $triggerKey.':investor',
            $related,
            $experts,
            [
                'title' => $title,
                'message' => $message,
                'url' => route('flow.show', $project->id),
                'icon' => $severity === 'overdue' ? 'mdi-alert-octagon-outline' : 'mdi-clock-alert-outline',
                'category' => 'sla',
                'severity' => $severity,
            ],
            $message
        );

        $investeeUsers = $this->recipients->investeeUsers($project);
        $this->notifications->deliver(
            $eventKey,
            $triggerKey.':investee',
            $related,
            $investeeUsers,
            [
                'title' => $title,
                'message' => $message,
                'url' => route('profile'),
                'icon' => $severity === 'overdue' ? 'mdi-alert-octagon-outline' : 'mdi-clock-alert-outline',
                'category' => 'sla',
                'severity' => $severity,
            ],
            $message,
            $this->recipients->investeePhones($project)
        );
    }

    private function kpiDueDate(KPI $kpi): ?Carbon
    {
        if ($kpi->deadline_at) {
            return $kpi->deadline_at->copy()->startOfDay();
        }

        if (blank($kpi->deadline)) {
            return null;
        }

        try {
            $normalized = str_replace('-', '/', trim((string) $kpi->deadline));

            return Jalalian::fromFormat('Y/m/d', $normalized)->toCarbon()->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function calendarStart(Calendar $calendar): ?Carbon
    {
        try {
            return Jalalian::fromFormat('Y-m-d H:i:s', (string) $calendar->start)->toCarbon();
        } catch (Throwable) {
            return null;
        }
    }
}
