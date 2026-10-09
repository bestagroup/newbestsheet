<?php

namespace App\Services;

use App\Models\Finance;
use App\Models\Financial_statement;
use App\Models\Project;
use App\Models\User;
use App\Support\Monetary;

class InvestmentReportSummaryService
{
    public function build(?User $viewer, string $periodType): array
    {
        // Global aggregate counts intentionally describe all registered applications.
        $all = Project::query();
        $notRejected = fn ($q) => $q->whereNull('is_rejected')->orWhere('is_rejected', 0);
        $counts = [
            'projects' => (clone $all)->count(),
            'active' => (clone $all)->where($notRejected)->where('invest_step', '<', 6)->count(),
            'rejected' => (clone $all)->where('is_rejected', 1)->count(),
            'portfolio' => (clone $all)->where($notRejected)->whereBetween('invest_step', [14, 19])->count(),
        ];

        // Financial details retain the viewer's existing access boundaries.
        $ids = app(OperationalAnalyticsService::class)->visibleProjectIds($viewer);
        $projects = Project::query()->whereIn('id', $ids)->where($notRejected)
            ->whereBetween('invest_step', [14, 19])
            ->get(['id', 'company_id', 'amount_request_accept']);
        $contract = Monetary::sum($projects->pluck('amount_request_accept'));
        $paid = Monetary::sum(Finance::query()->whereIn('project_id', $projects->pluck('id'))->pluck('amount'));
        $companyKeys = $projects->mapWithKeys(fn ($p) => [$p->id => $p->company_id ? 'company:'.$p->company_id : 'project:'.$p->id]);
        $periodType = in_array($periodType, ['legacy', 'annual', 'quarterly'], true) ? $periodType : 'legacy';
        $latest = Financial_statement::query()->whereIn('project_id', $projects->pluck('id'))
            ->where('period_type', $periodType)->orderByDesc('year')->orderByDesc('month')->orderByDesc('id')
            ->get(['id', 'project_id', 'year', 'month', 'net_profit'])
            ->unique(fn ($row) => $companyKeys[$row->project_id])->values();

        return [
            'counts' => $counts,
            'contract' => $contract,
            'paid' => $paid,
            'remaining' => Monetary::difference($contract, $paid),
            'profit' => $latest->isEmpty() ? null : Monetary::sum($latest->pluck('net_profit')),
            'reported_companies' => $latest->count(),
            'missing_companies' => $companyKeys->unique()->count() - $latest->count(),
            'mixed_periods' => $latest->map(fn ($row) => $row->year.'/'.$row->month)->unique()->count() > 1,
        ];
    }
}
