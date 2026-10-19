<?php

namespace App\Services;

use App\Models\Finance;
use App\Models\Financial_statement;
use App\Models\Project;
use App\Models\User;
use App\Services\FinancialReporting\LatestStatementSelector;
use App\Support\Monetary;
use Illuminate\Validation\ValidationException;

class InvestmentReportSummaryService
{
    public function __construct(
        private readonly OperationalAnalyticsService $access,
        private readonly LatestStatementSelector $selector,
    ) {}

    public function build(?User $viewer, ?string $periodType = null): array
    {
        if ($periodType !== null && ! in_array($periodType, LatestStatementSelector::TYPES, true)) {
            throw ValidationException::withMessages(['period_type' => 'نوع دوره مالی معتبر نیست.']);
        }

        // Counts describe all registered applications; financial detail remains access-scoped.
        $counts = [
            'projects' => Project::query()->count(),
            'active' => Project::query()->currentApplications()->count(),
            'rejected' => Project::query()->where('is_rejected', 1)->count(),
            'portfolio' => Project::query()->activePortfolio()->count(),
        ];
        $projects = Project::query()->whereIn('id', $this->access->visibleProjectIds($viewer))
            ->activePortfolio()->get(['id', 'company_id', 'amount_request_accept']);
        $contract = Monetary::sum($projects->pluck('amount_request_accept'));
        $paid = Monetary::sum(Finance::query()->whereIn('project_id', $projects->pluck('id'))->pluck('amount'));
        $companyKeys = $projects->mapWithKeys(fn ($p) => [$p->id => $p->company_id ? 'company:'.$p->company_id : 'project:'.$p->id]);
        $statements = Financial_statement::query()->whereIn('project_id', $projects->pluck('id'))
            ->when($periodType !== null, fn ($query) => $query->where('period_type', $periodType))
            ->get(['id', 'project_id', 'year', 'month', 'period_type', 'net_profit'])
            ->groupBy(fn ($row) => $companyKeys[$row->project_id]);
        $selections = $statements->map(fn ($rows) => $this->selector->select($rows));
        $latest = $selections->pluck('statement')->filter()->values();
        $known = $latest->filter(fn ($row) => $row->net_profit !== null && trim((string) $row->net_profit) !== '');

        return [
            'counts' => $counts,
            'contract' => $contract,
            'paid' => $paid,
            'remaining' => Monetary::difference($contract, $paid),
            'profit' => $known->isEmpty() ? null : Monetary::sum($known->pluck('net_profit')),
            'reported_companies' => $known->count(),
            'missing_companies' => $companyKeys->unique()->count() - $known->count(),
            'conflicting_companies' => $selections->where('conflict', true)->count(),
            'mixed_periods' => $known->map(fn ($row) => $row->year.'/'.$row->month.'/'.$row->period_type)->unique()->count() > 1,
            'period_type' => $periodType,
        ];
    }
}
