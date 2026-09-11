<?php

namespace App\Services;

use App\Models\Finance;
use App\Models\Financial_statement;
use App\Models\Project;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PortfolioFinancialReportService
{
    public function __construct(
        private readonly FinancialStatementMetricsService $metrics,
        private readonly OperationalAnalyticsService $operationalAnalytics,
    ) {}

    public function build(Request $request): array
    {
        $projectId = (int) ($request->input('project_id', $request->input('company_id')) ?: 0);
        $visibleProjectIds = $this->operationalAnalytics->visibleProjectIds(
            $request->user(),
            $projectId ?: null
        );
        $financialProjectIds = Project::query()
            ->whereIn('id', $visibleProjectIds)
            ->whereHas('financialStatements')
            ->pluck('id');

        $records = Financial_statement::query()
            ->whereIn('project_id', $financialProjectIds)
            ->filter($request)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        if (! $projectId) {
            $records = $records
                ->groupBy(fn (Financial_statement $row): string => sprintf('%04d-%02d', $row->year, $row->month))
                ->map(function (Collection $periodRows): object {
                    $first = $periodRows->first();
                    $aggregate = (object) [
                        'year' => (int) $first->year,
                        'month' => (int) $first->month,
                    ];

                    foreach (Financial_statement::monetaryFields() as $field) {
                        $aggregate->{$field} = $periodRows->sum(
                            fn (Financial_statement $row): float => $this->metrics->number($row->{$field})
                        );
                    }

                    return $aggregate;
                })
                ->values();
        }

        $metrics = $this->metrics->build($records);

        $financeQuery = Finance::query()
            ->with('project:id,title,company_name,amount_request_accept')
            ->whereIn('project_id', $financialProjectIds)
            ->where('amount', '>', 0)
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId));

        $fromDate = LocalizedInputNormalizer::jalaliDate($request->input('from_date'));
        $toDate = LocalizedInputNormalizer::jalaliDate($request->input('to_date'));

        $financeQuery
            ->when($fromDate, fn ($query) => $query->where('date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('date', '<=', $toDate));

        $payments = $financeQuery->get();
        $totalPaid = (float) $payments->sum(fn (Finance $finance) => $this->metrics->number($finance->amount));

        $allocation = $payments
            ->groupBy('project_id')
            ->map(function (Collection $items) use ($totalPaid): array {
                /** @var Finance $first */
                $first = $items->first();
                $paid = (float) $items->sum(fn (Finance $finance) => $this->metrics->number($finance->amount));

                return [
                    'label' => $first->project?->title ?? 'بدون پروژه',
                    'value' => $totalPaid > 0 ? round(($paid / $totalPaid) * 100, 2) : 0,
                ];
            })
            ->values();

        $projects = Project::query()
            ->whereIn('id', $financialProjectIds)
            ->with([
                'company:id,company_name',
                'financialStatements' => fn ($query) => $query->orderBy('year')->orderBy('month'),
                'finances:id,project_id,amount',
                'currentStep:id,title',
            ])
            ->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)
            ->when($projectId, fn ($query) => $query->whereKey($projectId))
            ->orderBy('title')
            ->get();

        $riskMap = $this->operationalAnalytics->riskByProjectIds($projects->pluck('id'));

        $portfolioRows = $projects->map(function (Project $project) use ($riskMap): array {
            $paid = (float) $project->finances->sum(
                fn (Finance $finance) => $this->metrics->number($finance->amount)
            );
            $contract = $this->metrics->number($project->amount_request_accept);
            $statement = $this->metrics->build($project->financialStatements)['summary'];

            $risk = $riskMap[(int) $project->id] ?? ['overdue_kpis' => 0, 'overdue_commitments' => 0, 'level' => 'normal'];

            return [
                'project_id' => $project->id,
                'project_title' => $project->title,
                'company_name' => $project->company?->company_name ?: $project->company_name,
                'progress_percentage' => (int) $project->progress_percentage,
                'current_step' => $project->currentStep?->title,
                'is_rejected' => (bool) $project->is_rejected,
                'overdue_kpis' => $risk['overdue_kpis'],
                'overdue_commitments' => $risk['overdue_commitments'],
                'risk_level' => $risk['level'],
                'contract_amount' => $contract,
                'paid_amount' => $paid,
                'remaining_amount' => max(0, $contract - $paid),
                'funding_percent' => $contract > 0 ? round(($paid / $contract) * 100, 2) : 0,
                'latest_period' => $statement['period'],
                'net_sales' => $statement['net_sales'],
                'net_profit' => $statement['net_profit'],
                'current_ratio' => $statement['current_ratio'],
                'debt_to_equity' => $statement['debt_to_equity'],
                'roa' => $statement['roa'],
            ];
        });

        $totalContract = (float) $portfolioRows->sum('contract_amount');

        $reportProjectQuery = Project::query()
            ->whereIn('id', $financialProjectIds);
        $reportCounts = [
            'projects' => (clone $reportProjectQuery)->count(),
            'rejected' => (clone $reportProjectQuery)->where('is_rejected', 1)->count(),
            'active' => (clone $reportProjectQuery)
                ->where(function ($query): void {
                    $query->whereNull('is_rejected')->orWhere('is_rejected', 0);
                })
                ->where('progress_percentage', '<', 100)
                ->count(),
            'completed' => (clone $reportProjectQuery)
                ->where(function ($query): void {
                    $query->whereNull('is_rejected')->orWhere('is_rejected', 0);
                })
                ->where('progress_percentage', '>=', 100)
                ->count(),
        ];

        return [
            'series' => $metrics['series'],
            'summary' => $metrics['summary'],
            'sectorAllocation' => [
                'labels' => $allocation->pluck('label')->values(),
                'data' => $allocation->pluck('value')->values(),
            ],
            'portfolioRows' => $portfolioRows,
            'totalPaid' => $totalPaid,
            'totalContract' => $totalContract,
            'remainingCommitment' => max(0, $totalContract - $totalPaid),
            'reportCounts' => $reportCounts,
        ];
    }
}
