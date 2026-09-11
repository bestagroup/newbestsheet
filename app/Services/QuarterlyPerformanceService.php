<?php

namespace App\Services;

use App\Models\KPI;
use App\Models\KpiMeasurement;
use App\Models\MediaFile;
use App\Models\Project;
use App\Models\ProjectContract;
use App\Models\QuarterlyPerformanceReport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuarterlyPerformanceService
{
    public function __construct(
        private readonly InvestmentWorkflowAccessService $access,
        private readonly ActivityLogService $activity,
    ) {}

    public function createRevision(Project $project, array $data, User $actor): QuarterlyPerformanceReport
    {
        return DB::transaction(function () use ($project, $data, $actor): QuarterlyPerformanceReport {
            $lockedProject = Project::query()->whereKey($project->getKey())->lockForUpdate()->firstOrFail();
            $this->authorizeSubmission($lockedProject, $actor);

            if ((bool) $lockedProject->is_rejected) {
                throw ValidationException::withMessages([
                    'project' => 'برای پرونده ردشده امکان ثبت گزارش عملکرد جدید وجود ندارد.',
                ]);
            }

            $this->assertPayloadBelongsToProject($lockedProject, $data);
            $year = (int) $data['year'];
            $quarter = (int) $data['quarter'];
            $existing = QuarterlyPerformanceReport::query()
                ->where('project_id', $lockedProject->getKey())
                ->where('year', $year)
                ->where('quarter', $quarter)
                ->lockForUpdate()
                ->get();

            $existing->each->update(['is_current' => false]);
            $revision = ((int) $existing->max('revision')) + 1;
            $isSubmitted = (bool) ($data['submit'] ?? false);

            $report = QuarterlyPerformanceReport::query()->create([
                'project_id' => $lockedProject->getKey(),
                'project_contract_id' => $data['project_contract_id'] ?? null,
                'year' => $year,
                'quarter' => $quarter,
                'revision' => $revision,
                'is_current' => true,
                'status' => $isSubmitted ? 'submitted' : 'draft',
                'period_starts_at' => $data['period_starts_at'],
                'period_ends_at' => $data['period_ends_at'],
                'executive_summary' => $data['executive_summary'] ?? null,
                'achievements' => $data['achievements'] ?? null,
                'challenges' => $data['challenges'] ?? null,
                'risks' => $data['risks'] ?? null,
                'financial_snapshot' => $data['financial_snapshot'] ?? null,
                'operational_snapshot' => $data['operational_snapshot'] ?? null,
                'submitted_by' => $isSubmitted ? $actor->getKey() : null,
                'submitted_at' => $isSubmitted ? now() : null,
            ]);

            $kpis = KPI::query()
                ->current()
                ->where('project_id', $lockedProject->getKey())
                ->whereIn('id', collect($data['measurements'] ?? [])->pluck('kpi_id'))
                ->get()
                ->keyBy('id');

            foreach ($data['measurements'] ?? [] as $item) {
                $kpi = $kpis->get((int) $item['kpi_id']);
                if (! $kpi) {
                    continue;
                }

                $measured = isset($item['measured_value']) ? (float) $item['measured_value'] : null;
                $target = is_numeric($kpi->target_value) ? (float) $kpi->target_value : null;

                KpiMeasurement::query()->create([
                    'kpi_id' => $kpi->getKey(),
                    'quarterly_performance_report_id' => $report->getKey(),
                    'year' => $year,
                    'quarter' => $quarter,
                    'measured_value' => $measured,
                    'target_snapshot' => $target,
                    'achievement_percentage' => $this->achievement($kpi, $measured, $target),
                    'status' => $isSubmitted ? 'submitted' : 'draft',
                    'notes' => $item['notes'] ?? null,
                    'evidence_media_file_id' => $item['evidence_media_file_id'] ?? null,
                    'submitted_by' => $isSubmitted ? $actor->getKey() : null,
                    'submitted_at' => $isSubmitted ? now() : null,
                ]);
            }

            $this->activity->record(
                'quarterly_performance.revision_created',
                "گزارش عملکرد فصل {$quarter} سال {$year} برای پروژه #{$lockedProject->getKey()} ثبت شد.",
                (int) $actor->getKey(),
                true,
                null,
                QuarterlyPerformanceReport::class,
                (int) $report->getKey(),
                null,
                $report->toArray(),
                ['revision' => $revision]
            );

            return $report->load('measurements.kpi');
        }, 3);
    }

    public function review(
        Project $project,
        QuarterlyPerformanceReport $report,
        string $decision,
        string $comment,
        User $actor
    ): QuarterlyPerformanceReport {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'نتیجه بررسی گزارش معتبر نیست.']);
        }

        if (! $this->access->canManagePortfolioDepartment($actor)
            || (int) $project->invest_step < Project::PORTFOLIO_MINIMUM_STEP) {
            throw new AuthorizationException('فقط کارشناس ارشد سرمایه‌گذاری یا مدیر مجاز به بررسی گزارش فصلی است.');
        }

        return DB::transaction(function () use ($project, $report, $decision, $comment, $actor): QuarterlyPerformanceReport {
            $locked = QuarterlyPerformanceReport::query()->whereKey($report->getKey())->lockForUpdate()->firstOrFail();

            if ((int) $locked->project_id !== (int) $project->getKey() || ! $locked->is_current) {
                throw ValidationException::withMessages(['report' => 'نسخه جاری گزارش انتخاب نشده است.']);
            }

            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['report' => 'فقط گزارش ارسال‌شده قابل بررسی است.']);
            }

            $locked->forceFill([
                'status' => $decision,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
                'review_comment' => $comment,
            ])->save();
            $locked->measurements()->update([
                'status' => $decision,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
                'review_comment' => $comment,
            ]);

            $this->activity->record(
                'quarterly_performance.reviewed',
                "گزارش عملکرد #{$locked->getKey()} با وضعیت {$decision} بررسی شد.",
                (int) $actor->getKey(),
                true,
                null,
                QuarterlyPerformanceReport::class,
                (int) $locked->getKey(),
                ['status' => 'submitted'],
                ['status' => $decision, 'review_comment' => $comment]
            );

            return $locked->fresh(['measurements.kpi', 'reviewedBy']);
        }, 3);
    }

    private function authorizeSubmission(Project $project, User $actor): void
    {
        $isOwner = (int) $project->user_id === (int) $actor->getKey();
        if (! $isOwner && (! $this->access->canManagePortfolioDepartment($actor)
            || (int) $project->invest_step < Project::PORTFOLIO_MINIMUM_STEP)) {
            throw new AuthorizationException('اجازه ثبت گزارش عملکرد این طرح را ندارید.');
        }
    }

    private function assertPayloadBelongsToProject(Project $project, array $data): void
    {
        $contractId = (int) ($data['project_contract_id'] ?? 0);
        if ($contractId > 0 && ! ProjectContract::query()
            ->whereKey($contractId)
            ->where('project_id', $project->getKey())
            ->exists()) {
            throw ValidationException::withMessages([
                'project_contract_id' => 'قرارداد انتخاب‌شده متعلق به این طرح نیست.',
            ]);
        }

        $measurements = collect($data['measurements'] ?? []);
        $kpiIds = $measurements->pluck('kpi_id')->map(static fn ($id): int => (int) $id)->filter()->values();
        if ($kpiIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['measurements' => 'هر KPI در یک گزارش فقط یک‌بار قابل ثبت است.']);
        }

        if ($kpiIds->isNotEmpty() && KPI::query()
            ->current()
            ->where('project_id', $project->getKey())
            ->whereIn('id', $kpiIds)
            ->count() !== $kpiIds->count()) {
            throw ValidationException::withMessages(['measurements' => 'یک یا چند KPI متعلق به این طرح نیست.']);
        }

        $mediaIds = $measurements->pluck('evidence_media_file_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();
        if ($mediaIds->isNotEmpty() && MediaFile::query()
            ->where('project_id', $project->getKey())
            ->whereIn('id', $mediaIds)
            ->count() !== $mediaIds->count()) {
            throw ValidationException::withMessages(['measurements' => 'یک یا چند مستند متعلق به این طرح نیست.']);
        }
    }

    private function achievement(KPI $kpi, ?float $measured, ?float $target): ?float
    {
        if ($measured === null || $target === null) {
            return null;
        }

        if ($kpi->direction === 'decrease') {
            return $measured <= 0 ? 200.0 : round(min(200, ($target / $measured) * 100), 3);
        }

        if ($kpi->direction === 'maintain') {
            if ($target == 0.0) {
                return $measured == 0.0 ? 100.0 : 0.0;
            }

            return round(max(0, 100 - (abs($measured - $target) / abs($target) * 100)), 3);
        }

        return $target == 0.0 ? null : round(min(200, ($measured / $target) * 100), 3);
    }
}
