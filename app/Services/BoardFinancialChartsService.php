<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Financial_statement;
use App\Support\Monetary;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;

final class BoardFinancialChartsService
{
    public function __construct(private readonly OperationalAnalyticsService $access) {}

    public function build(Request $request): array
    {
        $request->validate(['chart_company'=>['nullable','string','max:50'], 'chart_period'=>['nullable','string','max:40']]);
        $projects = Project::query()->whereIn('id', $this->access->visibleProjectIds($request->user()))
            ->whereBetween('invest_step', [14, 19])
            ->where(fn ($q) => $q->whereNull('is_rejected')->orWhere('is_rejected', 0))
            ->with('company:id,company_name')->orderBy('company_name')->orderBy('id')->get();
        $groups = $projects->groupBy(fn ($p) => $p->company_id ? 'company:'.$p->company_id : 'project:'.$p->id);
        $companies = $groups->map(fn ($rows, $key) => ['id'=>$key, 'name'=>$rows->first()->company?->company_name ?: ($rows->first()->company_name ?: $rows->first()->title)])->values();
        $selectedCompany = $request->input('chart_company') ?: ($companies->first()['id'] ?? null);
        abort_if($selectedCompany !== null && ! $groups->has($selectedCompany), 404);
        $statements = Financial_statement::query()->whereIn('project_id', $groups->get($selectedCompany, collect())->pluck('id'))
            ->whereBetween('month', [1, 12])->whereNotNull('year')->orderByDesc('year')->orderByDesc('month')->orderByDesc('id')->get();
        $byPeriod = $statements->groupBy(fn ($s) => $s->period_type.':'.$s->year.':'.$s->month);
        $periods = $byPeriod->map(function ($rows, $id): array {
            $s = $rows->first();
            return ['id'=>$id,'label'=>$this->periodLabel($s->period_type, $s->year, $s->month)];
        })->values();
        $selectedPeriod = $request->input('chart_period') ?: ($periods->first()['id'] ?? null);
        // On a company change, never reuse another company's unavailable period.
        $periodReset = $selectedPeriod !== null && ! $byPeriod->has($selectedPeriod);
        if ($periodReset) { $selectedPeriod = $periods->first()['id'] ?? null; }
        $charts = []; $notes = []; $indicators = []; $companyName = $companies->firstWhere('id', $selectedCompany)['name'] ?? '';
        if (! $selectedPeriod) { return compact('companies','periods','selectedCompany','selectedPeriod','companyName','charts','notes','indicators','periodReset'); }
        [$type, $year, $month] = explode(':', $selectedPeriod);
        $year = (int) $year; $month = (int) $month;
        $earliest = $statements->where('period_type', $type)->where('month', $month)->where('year','<=',$year)->min('year');
        $years = range(max((int) $earliest, $year - 5), $year);
        $rows = []; $labels = [];
        foreach ($years as $y) {
            $matches = $byPeriod->get($type.':'.$y.':'.$month, collect());
            $rows[] = $matches->count() === 1 ? $matches->first() : null;
            $labels[] = sprintf('%04d/%02d', $y, $month);
            if ($matches->count() > 1) { $notes[] = 'دوره '.end($labels).' بیش از یک صورت مالی برای این شرکت دارد؛ تا رفع تکرار، در نمودار محاسبه نمی‌شود.'; }
        }
        $current = end($rows) ?: null;
        $previousSet = $byPeriod->get($type.':'.($year - 1).':'.$month, collect());
        $previous = $previousSet->count() === 1 ? $previousSet->first() : null;
        $values = fn ($field) => array_map(fn ($row) => $this->value($row, $field), $rows);
        $ratioValues = fn ($a, $b, $factor=1) => array_map(fn ($row) => $this->ratio($this->value($row,$a), $this->value($row,$b), $factor), $rows);
        $charts[] = $this->chart('income','روند فروش و سودآوری','bar','ریال',$labels,[
            $this->series('فروش خالص',$values('net_sales'),'#183b56'),
            $this->series('سود ناخالص',$values('gross_profit'),'#2596be'),
            $this->series('سود / زیان خالص',$values('net_profit'),'#16866b')], 'آیا رشد فروش به سود تبدیل شده است؟ ارقام دوره‌های مشابه، بدون جمع‌زدن دوره‌ها نمایش داده می‌شوند.');
        $charts[] = $this->chart('margins','روند حاشیه سود','line','درصد',$labels,[
            $this->series('حاشیه سود ناخالص',$ratioValues('gross_profit','net_sales',100),'#2596be'),
            $this->series('حاشیه سود خالص',$ratioValues('net_profit','net_sales',100),'#16866b')], 'سود تقسیم بر فروش × ۱۰۰؛ افت حاشیه سود با وجود رشد فروش نیازمند بررسی هزینه‌هاست. فروش صفر یا منفی: نسبت محاسبه نمی‌شود.');
        $charts[] = $this->chart('liquidity','توان پوشش بدهی‌های کوتاه‌مدت','line','برابر',$labels,[
            $this->series('دارایی جاری / بدهی جاری',$ratioValues('total_current_assets','total_current_liabilities'),'#183b56'),
            $this->series('وجه نقد / بدهی جاری',$ratioValues('cash_and_equivalents','total_current_liabilities'),'#16866b')], 'دارایی جاری و وجه نقد در برابر بدهی جاری؛ موجودی نقد، جریان نقد عملیاتی نیست. بدهی جاری صفر یا منفی به‌صورت نسبت صفر نمایش داده نمی‌شود.');
        $charts[] = $this->chart('balance','روند دارایی، بدهی و حقوق مالکانه','bar','ریال',$labels,[
            $this->series('دارایی‌ها',$values('total_assets'),'#183b56'),
            $this->series('بدهی‌ها',$values('total_liabilities'),'#d49a32'),
            $this->series('حقوق مالکانه',$values('total_equity'),'#16866b')], 'آیا توسعه شرکت با افزایش بدهی همراه شده است؟ حقوق مالکانه منفی با علامت واقعی نمایش داده می‌شود.');
        $expenseFields = ['cogs','selling_general_admin_expense','financial_expense','income_tax_expense'];
        $expenses = fn ($row) => array_map(fn ($field) => $field === 'cogs' ? $this->sum($this->value($row,'cogs_goods'), $this->value($row,'cogs_services')) : $this->value($row,$field), $expenseFields);
        $charts[] = $this->chart('expenses','مقایسه هزینه‌ها با دوره مشابه سال قبل','horizontal','ریال',['بهای تمام‌شده کالا و خدمات','هزینه اداری و فروش','هزینه مالی','مالیات بر درآمد'],[
            $this->series('دوره منتخب '.$year,$expenses($current),'#183b56'),
            $this->series('دوره مشابه '.($year-1),$expenses($previous),'#94a3b8')], 'اقلام ثبت‌شده عیناً مقایسه می‌شوند؛ بهای تمام‌شده فقط با ثبت هر دو جزء کالا و خدمات محاسبه می‌شود. جزء فاقد مصداق باید صفر ثبت شده باشد.');
        $charts[] = $this->chart('working-capital','روند سرمایه در گردش','bar','ریال',$labels,[
            $this->series('دارایی جاری',$values('total_current_assets'),'#2596be'),
            $this->series('بدهی جاری',$values('total_current_liabilities'),'#d49a32'),
            $this->series('سرمایه در گردش خالص',array_map(fn ($row) => $this->difference($this->value($row,'total_current_assets'),$this->value($row,'total_current_liabilities')),$rows),'#16866b')], 'سرمایه در گردش = دارایی جاری − بدهی جاری؛ کسری منابع کوتاه‌مدت به‌صورت مقدار منفی مشخص می‌شود.');
        $salesChange = $this->difference($this->value($current,'net_sales'), $this->value($previous,'net_sales'));
        $indicators = [
            ['label'=>'تغییر فروش نسبت به دوره مشابه سال قبل','value'=>$this->ratio($salesChange,$this->value($previous,'net_sales'),100),'unit'=>'درصد'],
            ['label'=>'تغییر سود خالص نسبت به دوره مشابه سال قبل','value'=>$this->difference($this->value($current,'net_profit'),$this->value($previous,'net_profit')),'unit'=>'ریال'],
            ['label'=>'بدهی به دارایی در دوره منتخب','value'=>$this->ratio($this->value($current,'total_liabilities'),$this->value($current,'total_assets'),100),'unit'=>'درصد'],
        ];
        if ($type !== 'annual') { $notes[] = 'ارقام '.($type === 'quarterly' ? 'فصلی' : 'قدیمی').' همان مقادیر ثبت‌شده هستند؛ مبنای مستقل یا تجمعی در اسناد سامانه ثبت نشده است. مقایسه فقط با همان نوع و ماه پایان دوره در سال‌های قبل انجام می‌شود و تبدیل خودکار انجام نمی‌گیرد.'; }
        if (count($rows) === 1) { $notes[] = 'فقط یک دوره مشابه ثبت شده است؛ برای مشاهده روند، اطلاعات دوره‌های مشابه سال‌های قبل لازم است.'; }
        if (! $previous) { $notes[] = 'صورت مالی یکتای دوره مشابه سال قبل موجود نیست؛ تغییر سالانه محاسبه نشده است.'; }
        $balanceDifference = $this->difference($this->value($current,'total_assets'),$this->sum($this->value($current,'total_liabilities'),$this->value($current,'total_equity')));
        if ($balanceDifference !== null && ! BigDecimal::of($balanceDifference)->isZero()) { $notes[] = 'اختلاف دارایی با مجموع بدهی و حقوق مالکانه در دوره منتخب: '.Monetary::format($balanceDifference).' ریال؛ اقلام صورت مالی نیازمند تطبیق‌اند.'; }
        $equity = $this->value($current, 'total_equity');
        if ($equity !== null && BigDecimal::of($equity)->isNegative()) { $notes[] = 'حقوق مالکانه دوره منتخب منفی است.'; }
        $periodLabel = $this->periodLabel($type,$year,$month);
        return compact('companies','periods','selectedCompany','selectedPeriod','companyName','charts','notes','indicators','periodReset','periodLabel');
    }

    private function periodLabel(string $type, int $year, int $month): string
    {
        return (['annual'=>'سالانه','quarterly'=>'فصلی','legacy'=>'قدیمی'][$type] ?? 'نامشخص').' منتهی به '.sprintf('%04d/%02d',$year,$month);
    }
    private function value(?object $row,string $field): ?string
    {
        $value = $row?->{$field};
        if ($value === null || trim((string)$value) === '') { return null; }
        try { return (string) Monetary::value($value); } catch (\Brick\Math\Exception\MathException) { return null; }
    }
    private function ratio(?string $a,?string $b,int $factor=1): ?string
    {
        return $a === null || $b === null || BigDecimal::of($b)->compareTo(0) <= 0 ? null : (string) BigDecimal::of($a)->multipliedBy($factor)->dividedBy($b,2,RoundingMode::HALF_UP);
    }
    private function difference(?string $a,?string $b): ?string
    {
        return $a === null || $b === null ? null : Monetary::difference($a,$b);
    }
    private function sum(?string $a,?string $b): ?string
    {
        return $a === null || $b === null ? null : Monetary::sum([$a,$b]);
    }
    private function series(string $label,array $values,string $color): array { return compact('label','values','color'); }
    private function chart(string $id,string $title,string $type,string $unit,array $labels,array $series,string $note): array { return compact('id','title','type','unit','labels','series','note'); }
}
