<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Finance;
use App\Models\Financial_statement;
use App\Models\Investstep;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class oldReportController extends Controller
{
    public function index(Request $request)
    {
        $thispage = [
            'title' => 'گزارشات جامع سرمایه‌گذاری',
            'list' => 'داشبورد گزارشات مدیریتی',
            'add' => '',
            'create' => '',
            'enter' => '',
            'edit' => '',
            'delete' => '',
        ];

        $companyId = $request->company_id;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        $monthsFa = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

        $companies = Project::select('id', 'title')->where('invest_step', '>', 13)->get();

        $investSteps = Investstep::where('id', '>=', 1)->orderBy('id')->get();

        // ================================
        // Base Query (Financial_statement + Project + filter)
        // ================================
        $fsQuery = Financial_statement::query()
            ->filter($request)
            ->when($companyId, fn ($q) => $q->where('financial_statements.project_id', $companyId))
            ->join('projects', 'projects.id', '=', 'financial_statements.project_id')
            ->orderBy('financial_statements.year')
            ->orderBy('financial_statements.month');

        // ================================
        // 1️⃣ Net Sales رشد فروش
        // ================================
        $netSales = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            financial_statements.net_sales AS value
        ")->get();

        // ================================
        // 2️⃣ COGS Ratio نسبت بهای تمام‌شده به فروش
        // ================================
        $cogsRatio = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            (financial_statements.cogs_goods + financial_statements.cogs_services)
                / NULLIF(financial_statements.net_sales,0) AS value
        ")->get();

        // ================================
        // 3️⃣ Gross Margin حاشیه سود ناخالص
        // ================================
        $grossMargin = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            financial_statements.gross_profit / NULLIF(financial_statements.net_sales,0) AS value
        ")->get();

        // ================================
        // 4️⃣ SG&A Ratio نسبت هزینه اداری و فروش
        // ================================
        $sgaRatio = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            financial_statements.selling_general_admin_expense
                / NULLIF(financial_statements.net_sales,0) AS value
        ")->get();

        // ================================
        // 5️⃣ Current Asset Ratio ترکیب دارایی‌ها
        // ================================
        $currentAssetRatio = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            financial_statements.total_current_assets
                / NULLIF(financial_statements.total_assets,0) AS value
        ")->get();

        // ================================
        // 6️⃣ Current Ratio نقدینگی
        // ================================
        $currentRatio = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            financial_statements.total_current_assets
                / NULLIF(financial_statements.total_current_liabilities,0) AS value
        ")->get();

        // ================================
        // 7️⃣ Debt to Equity ریسک مالی
        // ================================
        $debtToEquity = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            financial_statements.total_liabilities
                / NULLIF(financial_statements.total_equity,0) AS value
        ")->get();

        // ================================
        // 8️⃣ ROA بازده دارایی
        // ================================
        $roa = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            financial_statements.net_profit
                / NULLIF(financial_statements.total_assets,0) AS value
        ")->get();

        // ================================
        // 9️⃣ Profit Quality کیفیت سود
        // ================================
        $profitQuality = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            (financial_statements.net_profit - financial_statements.non_operating_net)
                / NULLIF(financial_statements.net_profit,0) AS value
        ")->get();

        // ================================
        // 🔟 Balance Check کنترل ترازنامه
        // ================================
        $balanceCheck = (clone $fsQuery)->selectRaw("
            CONCAT(financial_statements.year,'/',LPAD(financial_statements.month,2,'0')) AS label,
            financial_statements.total_assets
                - financial_statements.total_equity_and_liabilities AS value
        ")->get();

        // ================================
        // 1️⃣ Deal Funnel
        // ================================
        $dealFunnel = $investSteps->map(function ($step) use ($companyId) {

            $query = Project::where('is_rejected', 1)
                ->where('reject_step', $step->id);

            if ($companyId) {
                $query->where('id', $companyId);
            }

            return [
                'title' => $step->title,
                'count' => $query->count(),
            ];
        })->filter(fn ($i) => $i['count'] > 0)->values();

        $dealFunnel = [
            'labels' => $dealFunnel->pluck('title'),
            'data' => $dealFunnel->pluck('count'),
        ];

        // ================================
        // 2️⃣ Strategic Fit
        // ================================
        $strategicFit = [
            'labels' => $investSteps->pluck('title'),
            'data' => $investSteps->map(function ($step) use ($companyId) {

                $query = Project::where('invest_step', $step->id)->where('is_rejected', 0)->get();

                if ($companyId) {
                    $query->where('id', $companyId);
                }

                return $query->count();
            }),
        ];

        // ================================
        // 3️⃣ Sector Allocation (با scope)
        // ================================
        $payments = Finance::query()
            ->filter($request)
            ->from('finances as f')
            ->leftJoin('projects as p', 'f.project_id', '=', 'p.id')
            ->where('f.amount', '>', 0)
            ->select(
                'p.id as project_id',
                'p.title',
                'p.logo',
                DB::raw('SUM(f.amount) as total_paid')
            )
            ->groupBy('p.id', 'p.title', 'p.logo')
            ->orderByDesc('total_paid')
            ->get();

        $total = $payments->sum('total_paid');

        $payments = $payments->map(function ($item) use ($total) {
            $item->percent_of_total = $total > 0
                ? round(($item->total_paid / $total) * 100, 2)
                : 0;

            return $item;
        });

        $sectorAllocation = [
            'labels' => $payments->pluck('title'),
            'data' => $payments->pluck('percent_of_total'),
        ];

        // ================================
        // 4️⃣ Financial Statements (با scope)
        // ================================
        $payments = Finance::select(
            DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->filter($request)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        $labels = $payments->pluck('month');
        $data = $payments->pluck('total_amount');
        $stageAllocation = [
            'labels' => $labels,
            'data' => $data,
        ];

        $records = Financial_statement::query()
            ->filter($request)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $labels = $records->map(fn ($r) => $r->year.'/'.str_pad($r->month, 2, '0', STR_PAD_LEFT)
        );

        // ================================
        // KPI Trend (static فعلاً)
        // ================================
        $portfolioKpi = [
            'months' => $monthsFa,
            'mrr' => [120, 138, 155, 172, 190, 210, 235, 255, 280, 305, 330, 360],
            'burn' => [92, 95, 98, 101, 105, 110, 112, 114, 116, 118, 120, 122],
            'runway' => [15, 15, 14, 13, 12, 12, 11, 10, 10, 9, 9, 8],
        ];

        // ================================
        // سایر داده‌های استاتیک
        // ================================
        $portfolioHealth = [
            'labels' => ['پایدار', 'در حال رشد', 'ریسکی', 'بحرانی', 'آماده خروج'],
            'data' => [14, 9, 6, 2, 3],
        ];

        $exitTimeline = [
            'labels' => ['۱۳۹۹', '۱۴۰۰', '۱۴۰۱', '۱۴۰۲', '۱۴۰۳'],
            'count' => [1, 2, 4, 5, 3],
            'value' => [8, 14, 26, 33, 21],
        ];

        $fundMetrics = [
            'labels' => ['۱۳۹۹', '۱۴۰۰', '۱۴۰۱', '۱۴۰۲', '۱۴۰۳'],
            'tvpi' => [1.05, 1.22, 1.38, 1.61, 1.84],
            'dpi' => [0.12, 0.28, 0.39, 0.52, 0.63],
            'rvpi' => [0.93, 0.94, 0.99, 1.09, 1.21],
        ];

        $companyPerformance = [
            'labels' => $labels,
            'irr' => $records->pluck('net_sales'),
            'mom' => $records->pluck('gross_profit'),
        ];

        $runwayByCompany = [
            'labels' => $companies->pluck('title'),
            'data' => [11, 9, 14, 10, 8, 12, 9, 13],
        ];

        $cashAndEquivalents = $records->pluck('cash_and_equivalents')
            ->map(fn ($v) => (int) str_replace(',', '', $v));

        $totalCurrentAssets = $records->pluck('total_current_assets')
            ->map(fn ($v) => (int) str_replace(',', '', $v));

        $totalCurrentLiabilities = $records->pluck('total_current_liabilities')
            ->map(fn ($v) => (int) str_replace(',', '', $v));

        return view('panel.report')->with(compact('thispage', 'companies', 'dealFunnel', 'strategicFit', 'labels', 'sectorAllocation', 'stageAllocation', 'portfolioKpi', 'portfolioHealth', 'exitTimeline', 'fundMetrics', 'companyPerformance', 'runwayByCompany', 'cashAndEquivalents', 'totalCurrentAssets', 'totalCurrentLiabilities'
        ));
    }

    public function show(Request $request)
    {
        // همان منطق index ولی بدون view

        $payments = Finance::query()
            ->filter($request)
            ->from('finances as f')
            ->leftJoin('projects as p', 'f.project_id', '=', 'p.id')
            ->where('f.amount', '>', 0)
            ->select(
                'p.title',
                DB::raw('SUM(f.amount) as total_paid')
            )
            ->groupBy('p.title')
            ->orderByDesc('total_paid')
            ->get();

        $total = $payments->sum('total_paid');

        $sectorAllocation = [
            'labels' => $payments->pluck('title'),
            'data' => $payments->map(fn ($i) => $total > 0 ? round(($i->total_paid / $total) * 100, 2) : 0
            ),
        ];

        $records = FinancialStatement::query()
            ->filter($request)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $labels = $records->map(fn ($r) => $r->year.'/'.str_pad($r->month, 2, '0', STR_PAD_LEFT)
        );

        return response()->json([
            'sectorAllocation' => $sectorAllocation,
            'labels' => $labels,
            'cashAndEquivalents' => $records->pluck('cash_and_equivalents')
                ->map(fn ($v) => (int) str_replace(',', '', $v)),
            'totalCurrentAssets' => $records->pluck('total_current_assets')
                ->map(fn ($v) => (int) str_replace(',','',$v)),
            'totalCurrentLiabilities' => $records->pluck('total_current_liabilities')
                ->map(fn ($v) => (int) str_replace(',','',$v)),
        ]);
    }
}
