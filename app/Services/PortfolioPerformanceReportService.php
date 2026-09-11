<?php

namespace App\Services;

use App\Models\QuarterlyPerformanceReport;
use App\Models\User;
use Illuminate\Support\Collection;

class PortfolioPerformanceReportService
{
    public function __construct(private readonly OperationalAnalyticsService $access) {}

    public function rows(?User $viewer, array $filters = []): Collection
    {
        $projectId = (int) ($filters['project_id'] ?? 0) ?: null;
        $projectIds = $this->access->visibleProjectIds($viewer, $projectId);

        return QuarterlyPerformanceReport::query()
            ->whereIn('project_id', $projectIds)
            ->where('is_current', true)
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->where('year', (int) $year))
            ->when($filters['quarter'] ?? null, fn ($query, $quarter) => $query->where('quarter', (int) $quarter))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->with([
                'project:id,title,company_name',
                'measurements.kpi:id,title,code,weight,direction,target_value',
            ])
            ->orderByDesc('year')
            ->orderByDesc('quarter')
            ->get()
            ->map(function (QuarterlyPerformanceReport $report): array {
                $eligible = $report->measurements->filter(
                    static fn ($measurement): bool => $measurement->achievement_percentage !== null
                );
                $weight = $eligible->sum(static fn ($measurement): float => max(0, (float) ($measurement->kpi?->weight ?? 1)));
                $weightedScore = $weight > 0
                    ? $eligible->sum(static fn ($measurement): float => (float) $measurement->achievement_percentage * max(0, (float) ($measurement->kpi?->weight ?? 1))
                    ) / $weight
                    : null;

                return [
                    'project_id' => (int) $report->project_id,
                    'company_name' => $report->project?->company_name,
                    'project_title' => $report->project?->title,
                    'year' => (int) $report->year,
                    'quarter' => (int) $report->quarter,
                    'revision' => (int) $report->revision,
                    'status' => $report->status,
                    'measurements_count' => $report->measurements->count(),
                    'achievement_score' => $weightedScore === null ? null : round($weightedScore, 2),
                    'below_target_count' => $eligible->filter(
                        static fn ($measurement): bool => (float) $measurement->achievement_percentage < 100
                    )->count(),
                    'risks_count' => count((array) $report->risks),
                    'submitted_at' => $report->submitted_at?->toDateTimeString(),
                    'reviewed_at' => $report->reviewed_at?->toDateTimeString(),
                ];
            })
            ->values();
    }
}
