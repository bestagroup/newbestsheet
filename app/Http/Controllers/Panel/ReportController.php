<?php

namespace App\Http\Controllers\Panel;

use App\Enums\InvestmentRole;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\GovernanceReportService;
use App\Services\OperationalAnalyticsService;
use App\Services\PortfolioFinancialReportService;
use App\Services\PortfolioPerformanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function __construct(
        private readonly PortfolioFinancialReportService $reportService,
        private readonly OperationalAnalyticsService $operationalAnalytics,
        private readonly PortfolioPerformanceReportService $performanceReport,
    ) {}

    public function index(Request $request)
    {
        $thispage = [
            'title' => 'گزارشات جامع سرمایه‌گذاری',
            'list' => 'داشبورد مالی، SLA و صورت‌وضعیت شرکت‌های پورتفو',
        ];

        $visibleProjectIds = $this->operationalAnalytics->visibleProjectIds($request->user());
        $projects = Project::query()
            ->whereIn('id', $visibleProjectIds)
            ->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)
            ->where('invest_step', '<', 20)
            ->where(fn ($query) => $query->whereNull('is_rejected')->orWhere('is_rejected', 0))
            ->orderBy('title')
            ->get(['id', 'title', 'company_name']);

        $report = $this->reportService->build($request);
        $series = $report['series'];
        $operational = $this->operationalAnalytics->build(
            $request->user(),
            (int) ($request->input('project_id') ?: 0) ?: null
        );
        $performanceRows = $this->performanceReport->rows($request->user(), $request->only([
            'project_id', 'year', 'quarter', 'status',
        ]));

        return view('panel.report', [
            'governance' => app(GovernanceReportService::class)->build($request->user(), $request->integer('project_id') ?: null),
            'thispage' => $thispage,
            'companies' => $projects,
            'netSales' => $series['netSales'],
            'cogsRatio' => $series['cogsRatio'],
            'grossMargin' => $series['grossMargin'],
            'sgaRatio' => $series['sgaRatio'],
            'currentAssetRatio' => $series['currentAssetRatio'],
            'currentRatio' => $series['currentRatio'],
            'debtToEquity' => $series['debtToEquity'],
            'roa' => $series['roa'],
            'profitQuality' => $series['profitQuality'],
            'balanceCheck' => $series['balanceCheck'],
            'financialSummary' => $report['summary'],
            'portfolioRows' => $report['portfolioRows'],
            'sectorAllocation' => $report['sectorAllocation'],
            'totalPaid' => $report['totalPaid'],
            'totalContract' => $report['totalContract'],
            'remainingCommitment' => $report['remainingCommitment'],
            'reportCounts' => $report['reportCounts'],
            'investmentSummary' => app(\App\Services\InvestmentReportSummaryService::class)->build($request->user(), (string) $request->input('period_type', 'legacy')),
            'operationalAnalytics' => $operational,
            'performanceRows' => $performanceRows,
        ]);
    }

    public function expertsData(Request $request): JsonResponse
    {
        $projectId = (int) ($request->input('project_id') ?: 0) ?: null;
        $query = $this->operationalAnalytics->expertPerformanceQuery($request->user(), $projectId);

        return DataTables::of($query)
            ->editColumn('role_slugs', fn ($row): string => $this->roleLabels((string) $row->role_slugs))
            ->addColumn('risk_status', static function ($row): string {
                $count = (int) $row->risk_projects_count;
                if ($count <= 0) {
                    return '<span class="badge bg-label-success">بدون معوقه</span>';
                }

                return '<span class="badge bg-label-danger">'.number_format($count).' پروژه پرریسک</span>';
            })
            ->rawColumns(['risk_status'])
            ->toJson();
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        $projectId = (int) ($request->input('project_id') ?: 0) ?: null;

        return match ($type) {
            'portfolio' => $this->csvResponse(
                'portfolio-report.csv',
                ['شرکت', 'طرح', 'پیشرفت', 'مرحله جاری', 'ارزش قرارداد', 'پرداخت‌شده', 'مانده تعهد', 'KPI معوق', 'تعهد معوق', 'آخرین دوره', 'فروش خالص', 'سود خالص'],
                $this->reportService->build($request)['portfolioRows']->map(static fn (array $row): array => [
                    $row['company_name'],
                    $row['project_title'],
                    $row['progress_percentage'].'%',
                    $row['current_step'] ?: '—',
                    $row['contract_amount'],
                    $row['paid_amount'],
                    $row['remaining_amount'],
                    $row['overdue_kpis'],
                    $row['overdue_commitments'],
                    $row['latest_period'] ?: '—',
                    $row['net_sales'],
                    $row['net_profit'],
                ])
            ),
            'sla' => $this->csvResponse(
                'sla-deadlines.csv',
                ['شرکت', 'طرح', 'نوع', 'عنوان', 'سررسید', 'روز تا/از سررسید', 'وضعیت'],
                $this->operationalAnalytics->deadlineRows($request->user(), $projectId)->map(static fn (array $row): array => [
                    $row['company'],
                    $row['project'],
                    $row['type'],
                    $row['title'],
                    $row['due_date'],
                    $row['days_remaining'],
                    $row['status'],
                ])
            ),
            'experts' => $this->csvResponse(
                'expert-performance.csv',
                ['نام', 'ایمیل', 'نقش', 'کل تخصیص', 'تخصیص فعال', 'پروژه‌ها', 'تصمیم‌ها', 'تأیید', 'رد', 'پروژه پرریسک'],
                $this->operationalAnalytics->expertPerformanceQuery($request->user(), $projectId)
                    ->get()
                    ->map(fn ($row): array => [
                        $row->name,
                        $row->email,
                        $this->roleLabels((string) $row->role_slugs),
                        $row->assignments_count,
                        $row->active_assignments_count,
                        $row->projects_count,
                        $row->decisions_count,
                        $row->approved_count,
                        $row->rejected_count,
                        $row->risk_projects_count,
                    ])
            ),
            'performance' => $this->csvResponse(
                'quarterly-performance.csv',
                ['شرکت', 'طرح', 'سال', 'فصل', 'نسخه', 'وضعیت', 'تعداد KPI', 'امتیاز تحقق', 'زیر هدف', 'تعداد ریسک'],
                $this->performanceReport->rows($request->user(), $request->only([
                    'project_id', 'year', 'quarter', 'status',
                ]))->map(static fn (array $row): array => [
                    $row['company_name'],
                    $row['project_title'],
                    $row['year'],
                    $row['quarter'],
                    $row['revision'],
                    $row['status'],
                    $row['measurements_count'],
                    $row['achievement_score'],
                    $row['below_target_count'],
                    $row['risks_count'],
                ])
            ),
            default => abort(404),
        };
    }

    public function show(Request $request, string $report): JsonResponse
    {
        return response()->json($this->reportService->build($request));
    }

    private function roleLabels(string $slugs): string
    {
        return collect(explode(',', $slugs))
            ->filter()
            ->unique()
            ->map(static function (string $slug): string {
                $role = InvestmentRole::tryFrom(trim($slug));

                return $role?->label() ?? trim($slug);
            })
            ->implode('، ');
    }

    private function csvResponse(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers, ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($stream, array_map(static function ($value) {
                    return is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value)
                        ? "'".$value : $value;
                }, is_array($row) ? $row : (array) $row), ',', '"', '');
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
