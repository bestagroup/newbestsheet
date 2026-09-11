<?php

namespace App\Services;

use App\Enums\InvestmentRole;
use App\Models\KPI;
use App\Models\Project;
use App\Models\ProjectCommitment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperationalAnalyticsService
{
    /**
     * Build operational metrics for the projects visible to the current viewer.
     * Managers/superadmins see the whole portfolio; reviewers see their active assignments.
     */
    public function build(?User $viewer = null, ?int $projectId = null): array
    {
        $projectIds = $this->visibleProjectIds($viewer, $projectId);
        $today = now()->startOfDay();
        $dueSoon = $today->copy()->addDays(7);

        if ($projectIds->isEmpty()) {
            return $this->emptyAnalytics();
        }

        $projects = Project::query()
            ->whereIn('id', $projectIds)
            ->get(['id', 'title', 'company_name', 'invest_step', 'progress_percentage', 'is_rejected']);

        $kpi = $this->deadlineSummary(
            KPI::query()->current()->whereIn('project_id', $projectIds)->whereNotNull('deadline_at'),
            'deadline_at',
            'completed_at',
            $today,
            $dueSoon
        );

        $commitments = $this->commitmentSummary($projectIds, $today, $dueSoon);

        $overdueProjectIds = KPI::query()
            ->current()
            ->whereIn('project_id', $projectIds)
            ->whereNull('completed_at')
            ->whereDate('deadline_at', '<', $today->toDateString())
            ->pluck('project_id')
            ->merge(
                ProjectCommitment::query()
                    ->whereIn('project_id', $projectIds)
                    ->where('status', 'pending')
                    ->whereNull('completed_at')
                    ->whereDate('due_at', '<', $today->toDateString())
                    ->pluck('project_id')
            )
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $progressDistribution = [
            'labels' => ['۰–۲۴٪', '۲۵–۴۹٪', '۵۰–۷۴٪', '۷۵–۹۹٪', '۱۰۰٪', 'ردشده'],
            'data' => [
                $projects->filter(fn (Project $p): bool => ! $p->is_rejected && (int) $p->progress_percentage < 25)->count(),
                $projects->filter(fn (Project $p): bool => ! $p->is_rejected && (int) $p->progress_percentage >= 25 && (int) $p->progress_percentage < 50)->count(),
                $projects->filter(fn (Project $p): bool => ! $p->is_rejected && (int) $p->progress_percentage >= 50 && (int) $p->progress_percentage < 75)->count(),
                $projects->filter(fn (Project $p): bool => ! $p->is_rejected && (int) $p->progress_percentage >= 75 && (int) $p->progress_percentage < 100)->count(),
                $projects->filter(fn (Project $p): bool => ! $p->is_rejected && (int) $p->progress_percentage >= 100)->count(),
                $projects->where('is_rejected', 1)->count(),
            ],
        ];

        $stepCounts = Project::query()
            ->whereIn('projects.id', $projectIds)
            ->where(function ($query): void {
                $query->whereNull('projects.is_rejected')->orWhere('projects.is_rejected', 0);
            })
            ->where('projects.progress_percentage', '<', 100)
            ->join('investsteps', 'investsteps.id', '=', 'projects.invest_step')
            ->select('investsteps.id', 'investsteps.title', DB::raw('COUNT(projects.id) as aggregate'))
            ->groupBy('investsteps.id', 'investsteps.title')
            ->orderBy('investsteps.id')
            ->get();

        $activeProjectIds = $projects
            ->filter(fn (Project $p): bool => ! $p->is_rejected && (int) $p->progress_percentage < 100)
            ->pluck('id');

        $assignedActiveProjects = DB::table('project_assignments as pa')
            ->join('roles as r', 'r.id', '=', 'pa.role_id')
            ->whereIn('pa.project_id', $activeProjectIds)
            ->where('pa.is_active', true)
            ->whereIn('r.title', InvestmentRole::decisionRoleSlugs())
            ->distinct()
            ->count('pa.project_id');

        $slaEligible = $kpi['on_time'] + $kpi['late'] + $kpi['overdue']
            + $commitments['on_time'] + $commitments['late'] + $commitments['overdue'];
        $slaOnTime = $kpi['on_time'] + $commitments['on_time'];

        return [
            'summary' => [
                'projects' => $projects->count(),
                'average_progress' => round((float) $projects->where('is_rejected', '!=', 1)->avg('progress_percentage'), 1),
                'at_risk_projects' => $overdueProjectIds->count(),
                'active_assignments' => DB::table('project_assignments')->whereIn('project_id', $projectIds)->where('is_active', true)->count(),
                'unassigned_active_projects' => max(0, $activeProjectIds->count() - $assignedActiveProjects),
                'sla_compliance' => $slaEligible > 0 ? round(($slaOnTime / $slaEligible) * 100, 1) : null,
                'sla_eligible' => $slaEligible,
            ],
            'kpi' => $kpi,
            'commitments' => $commitments,
            'progress_distribution' => $progressDistribution,
            'step_workload' => [
                'labels' => $stepCounts->pluck('title')->values(),
                'data' => $stepCounts->pluck('aggregate')->map(static fn ($v): int => (int) $v)->values(),
            ],
            'risk_projects' => $this->riskProjects($overdueProjectIds),
        ];
    }

    public function expertPerformanceQuery(?User $viewer = null, ?int $projectId = null): Builder
    {
        $projectIds = $this->visibleProjectIds($viewer, $projectId);
        $today = now()->toDateString();

        $query = DB::table('project_assignments as pa')
            ->join('users as u', 'u.id', '=', 'pa.user_id')
            ->join('roles as r', 'r.id', '=', 'pa.role_id')
            ->whereIn('r.title', InvestmentRole::decisionRoleSlugs())
            ->whereIn('pa.project_id', $projectIds)
            ->select(
                'u.id',
                'u.name',
                'u.email',
                DB::raw('GROUP_CONCAT(DISTINCT r.title) as role_slugs'),
                DB::raw('COUNT(DISTINCT pa.id) as assignments_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN pa.is_active = 1 THEN pa.id END) as active_assignments_count'),
                DB::raw('COUNT(DISTINCT pa.project_id) as projects_count')
            )
            ->groupBy('u.id', 'u.name', 'u.email');

        $decisionBase = static fn (string $status = '') => DB::table('project_steps as ps')
            ->selectRaw('COUNT(*)')
            ->whereColumn('ps.user_id', 'u.id')
            ->whereIn('ps.project_id', $projectIds)
            ->when($status !== '', fn ($sub) => $sub->where('ps.status', $status));

        $riskSub = DB::table('project_assignments as risk_pa')
            ->selectRaw('COUNT(DISTINCT risk_pa.project_id)')
            ->whereColumn('risk_pa.user_id', 'u.id')
            ->where('risk_pa.is_active', true)
            ->whereIn('risk_pa.project_id', $projectIds)
            ->where(function ($risk) use ($today): void {
                $risk->whereExists(function ($q) use ($today): void {
                    $q->selectRaw('1')
                        ->from('kpis as rk')
                        ->whereColumn('rk.project_id', 'risk_pa.project_id')
                        ->whereNull('rk.completed_at')
                        ->whereDate('rk.deadline_at', '<', $today);
                })->orWhereExists(function ($q) use ($today): void {
                    $q->selectRaw('1')
                        ->from('project_commitments as rc')
                        ->whereColumn('rc.project_id', 'risk_pa.project_id')
                        ->where('rc.status', 'pending')
                        ->whereNull('rc.completed_at')
                        ->whereDate('rc.due_at', '<', $today);
                });
            });

        return $query
            ->selectSub($decisionBase(), 'decisions_count')
            ->selectSub($decisionBase('approved'), 'approved_count')
            ->selectSub($decisionBase('rejected'), 'rejected_count')
            ->selectSub($riskSub, 'risk_projects_count');
    }

    /** @return Collection<int, array<string, mixed>> */
    public function deadlineRows(?User $viewer = null, ?int $projectId = null): Collection
    {
        $projectIds = $this->visibleProjectIds($viewer, $projectId);
        $today = now()->startOfDay();
        $dueSoon = $today->copy()->addDays(7);

        if ($projectIds->isEmpty()) {
            return collect();
        }

        $kpis = KPI::query()
            ->current()
            ->with('project:id,title,company_name')
            ->whereIn('project_id', $projectIds)
            ->whereNull('completed_at')
            ->whereNotNull('deadline_at')
            ->whereDate('deadline_at', '<=', $dueSoon->toDateString())
            ->get()
            ->map(function (KPI $kpi) use ($today): array {
                $days = $today->diffInDays($kpi->deadline_at->copy()->startOfDay(), false);

                return [
                    'project' => $kpi->project?->title,
                    'company' => $kpi->project?->company_name,
                    'type' => 'KPI',
                    'title' => $kpi->title,
                    'due_date' => $kpi->deadline ?: $kpi->deadline_at?->format('Y-m-d'),
                    'days_remaining' => $days,
                    'status' => $days < 0 ? 'overdue' : ($days === 0 ? 'due_today' : 'due_soon'),
                ];
            });

        $commitments = ProjectCommitment::query()
            ->with(['project:id,title,company_name', 'commitment:id,title'])
            ->whereIn('project_id', $projectIds)
            ->where('status', 'pending')
            ->whereNull('completed_at')
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<=', $dueSoon->toDateString())
            ->get()
            ->map(function (ProjectCommitment $item) use ($today): array {
                $days = $today->diffInDays($item->due_at->copy()->startOfDay(), false);

                return [
                    'project' => $item->project?->title,
                    'company' => $item->project?->company_name,
                    'type' => 'تعهد',
                    'title' => $item->commitment?->title ?: 'تعهد پروژه',
                    'due_date' => $item->due_date ?: $item->due_at?->format('Y-m-d'),
                    'days_remaining' => $days,
                    'status' => $days < 0 ? 'overdue' : ($days === 0 ? 'due_today' : 'due_soon'),
                ];
            });

        return $kpis->concat($commitments)
            ->sortBy('days_remaining')
            ->values();
    }

    /** @return array<int, array<string, mixed>> */
    public function riskByProjectIds(Collection $projectIds): array
    {
        if ($projectIds->isEmpty()) {
            return [];
        }

        $today = now()->toDateString();
        $kpi = KPI::query()
            ->current()
            ->whereIn('project_id', $projectIds)
            ->whereNull('completed_at')
            ->whereDate('deadline_at', '<', $today)
            ->select('project_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('project_id')
            ->pluck('aggregate', 'project_id');
        $commitments = ProjectCommitment::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'pending')
            ->whereNull('completed_at')
            ->whereDate('due_at', '<', $today)
            ->select('project_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('project_id')
            ->pluck('aggregate', 'project_id');

        return $projectIds->mapWithKeys(function ($id) use ($kpi, $commitments): array {
            $kpiCount = (int) ($kpi[$id] ?? 0);
            $commitmentCount = (int) ($commitments[$id] ?? 0);

            return [(int) $id => [
                'overdue_kpis' => $kpiCount,
                'overdue_commitments' => $commitmentCount,
                'level' => ($kpiCount + $commitmentCount) > 0 ? 'high' : 'normal',
            ]];
        })->all();
    }

    public function visibleProjectIds(?User $viewer, ?int $projectId = null): Collection
    {
        $query = Project::query()->select('projects.id');

        if ($projectId) {
            $query->whereKey($projectId);
        }

        if ($viewer) {
            $workflowAccess = app(InvestmentWorkflowAccessService::class);

            if ($viewer->hasRole(InvestmentRole::ExecutiveBoard->value)) {
                // The board receives a read-only, organization-wide reporting view.
            } elseif ($viewer->hasRole(InvestmentRole::FinanceManagement->value)) {
                $query->where('projects.invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP);
            } else {
                $workflowAccess->scopeWorkflowProjects($query, $viewer);
            }
        }

        return $query->pluck('projects.id')->map(static fn ($id): int => (int) $id)->values();
    }

    private function deadlineSummary($query, string $deadlineColumn, string $completedColumn, Carbon $today, Carbon $dueSoon): array
    {
        $base = clone $query;
        $open = (clone $base)->whereNull($completedColumn)->count();
        $dueSoonCount = (clone $base)
            ->whereNull($completedColumn)
            ->whereDate($deadlineColumn, '>=', $today->toDateString())
            ->whereDate($deadlineColumn, '<=', $dueSoon->toDateString())
            ->count();
        $overdue = (clone $base)
            ->whereNull($completedColumn)
            ->whereDate($deadlineColumn, '<', $today->toDateString())
            ->count();
        $onTime = (clone $base)
            ->whereNotNull($completedColumn)
            ->whereRaw("DATE({$completedColumn}) <= DATE({$deadlineColumn})")
            ->count();
        $late = (clone $base)
            ->whereNotNull($completedColumn)
            ->whereRaw("DATE({$completedColumn}) > DATE({$deadlineColumn})")
            ->count();

        return [
            'open' => $open,
            'due_soon' => $dueSoonCount,
            'overdue' => $overdue,
            'on_time' => $onTime,
            'late' => $late,
        ];
    }

    private function commitmentSummary(Collection $projectIds, Carbon $today, Carbon $dueSoon): array
    {
        $base = ProjectCommitment::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', '!=', 'waived')
            ->whereNotNull('due_at');

        $open = (clone $base)->where('status', 'pending')->whereNull('completed_at')->count();
        $dueSoonCount = (clone $base)
            ->where('status', 'pending')
            ->whereNull('completed_at')
            ->whereDate('due_at', '>=', $today->toDateString())
            ->whereDate('due_at', '<=', $dueSoon->toDateString())
            ->count();
        $overdue = (clone $base)
            ->where('status', 'pending')
            ->whereNull('completed_at')
            ->whereDate('due_at', '<', $today->toDateString())
            ->count();
        $onTime = (clone $base)
            ->whereNotNull('completed_at')
            ->whereRaw('DATE(completed_at) <= DATE(due_at)')
            ->count();
        $late = (clone $base)
            ->whereNotNull('completed_at')
            ->whereRaw('DATE(completed_at) > DATE(due_at)')
            ->count();

        return [
            'open' => $open,
            'due_soon' => $dueSoonCount,
            'overdue' => $overdue,
            'on_time' => $onTime,
            'late' => $late,
        ];
    }

    private function riskProjects(Collection $projectIds): Collection
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        $risk = $this->riskByProjectIds($projectIds);

        return Project::query()
            ->whereIn('projects.id', $projectIds)
            ->leftJoin('investsteps as current_step', 'current_step.id', '=', 'projects.invest_step')
            ->select('projects.id', 'projects.title', 'projects.company_name', 'projects.progress_percentage', 'current_step.title as current_step')
            ->get()
            ->map(function ($project) use ($risk): array {
                $row = $risk[(int) $project->id] ?? ['overdue_kpis' => 0, 'overdue_commitments' => 0, 'level' => 'normal'];

                return [
                    'id' => (int) $project->id,
                    'title' => $project->title,
                    'company_name' => $project->company_name,
                    'progress_percentage' => (int) $project->progress_percentage,
                    'current_step' => $project->current_step,
                    ...$row,
                ];
            })
            ->sortByDesc(fn (array $row): int => $row['overdue_kpis'] + $row['overdue_commitments'])
            ->values();
    }

    private function emptyAnalytics(): array
    {
        return [
            'summary' => [
                'projects' => 0,
                'average_progress' => 0.0,
                'at_risk_projects' => 0,
                'active_assignments' => 0,
                'unassigned_active_projects' => 0,
                'sla_compliance' => null,
                'sla_eligible' => 0,
            ],
            'kpi' => ['open' => 0, 'due_soon' => 0, 'overdue' => 0, 'on_time' => 0, 'late' => 0],
            'commitments' => ['open' => 0, 'due_soon' => 0, 'overdue' => 0, 'on_time' => 0, 'late' => 0],
            'progress_distribution' => [
                'labels' => ['۰–۲۴٪', '۲۵–۴۹٪', '۵۰–۷۴٪', '۷۵–۹۹٪', '۱۰۰٪', 'ردشده'],
                'data' => [0, 0, 0, 0, 0, 0],
            ],
            'step_workload' => ['labels' => [], 'data' => []],
            'risk_projects' => collect(),
        ];
    }
}
