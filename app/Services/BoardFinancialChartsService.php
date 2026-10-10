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
        $request->validate(['chart_companies'=>['nullable','array'], 'chart_companies.*'=>['required','string','max:50']]);
        $projects = Project::query()->whereIn('id', $this->access->visibleProjectIds($request->user()))
            ->whereBetween('invest_step', [14,19])->where(fn ($q) => $q->whereNull('is_rejected')->orWhere('is_rejected',0))
            ->with('company:id,company_name')->orderBy('company_name')->orderBy('id')->get();
        $groups = $projects->groupBy(fn ($p) => $p->company_id ? 'company:'.$p->company_id : 'project:'.$p->id);
        $companies = $groups->map(fn ($rows,$key) => ['id'=>$key,'name'=>$rows->first()->company?->company_name ?: ($rows->first()->company_name ?: $rows->first()->title)])->values();
        $selectedCompanies = array_values(array_unique($request->input('chart_companies', [])));
        if (! $selectedCompanies && $companies->isNotEmpty()) { $selectedCompanies = [$companies->first()['id']]; }
        foreach ($selectedCompanies as $key) { abort_unless($groups->has($key),404); }
        $projectKeys = $projects->mapWithKeys(fn ($p) => [$p->id => $p->company_id ? 'company:'.$p->company_id : 'project:'.$p->id]);
        $ids = $groups->toBase()->only($selectedCompanies)->flatten(1)->pluck('id');
        $statements = Financial_statement::query()->whereIn('project_id',$ids)->whereNotNull('year')->whereBetween('month',[1,12])
            ->orderBy('year')->orderBy('month')->orderBy('id')->get();
        $charts=[]; $notes=[];
        $palette = ['#183b56','#16866b','#b56d17','#8259ac','#2488b0','#c14d69','#587a30','#687b94'];
        $styles = [];
        // Stable identity across requests: colors depend on the authorized company list, not selection order.
        foreach ($companies as $index => $company) {
            $styles[$company['id']] = ['color'=>$index < count($palette) ? $palette[$index] : 'hsl('.fmod($index * 137.508,360).', 55%, 42%)', 'dash'=>intdiv($index,count($palette)) % 2 ? [6,3] : [], 'point'=>['circle','rect','triangle','rectRot'][$index % 4]];
        }
        $types = ['annual'=>'سالانه','quarterly'=>'فصلی','legacy'=>'قدیمی / طبقه‌بندی‌نشده'];
        $definitions = [
            ['sales','فروش خالص','ریال','net_sales',null,'ارقام فروش ثبت‌شده هر شرکت در هر دوره.'],
            ['profit','سود و زیان خالص','ریال','net_profit',null,'زیان زیر خط صفر نمایش داده می‌شود؛ رنگ شرکت ثابت می‌ماند.'],
            ['gross-margin','حاشیه سود ناخالص','درصد','gross_profit','net_sales','سود ناخالص ÷ فروش × ۱۰۰؛ فروش صفر یا منفی قابل محاسبه نیست.'],
            ['net-margin','حاشیه سود خالص','درصد','net_profit','net_sales','سود خالص ÷ فروش × ۱۰۰؛ برای مقایسه سودآوری شرکت‌های با اندازه متفاوت.'],
            ['current','پوشش بدهی جاری','برابر','total_current_assets','total_current_liabilities','دارایی جاری ÷ بدهی جاری؛ نسبت صفر جایگزین مخرج نامعتبر نمی‌شود.'],
            ['cash','پوشش نقدی بدهی جاری','برابر','cash_and_equivalents','total_current_liabilities','وجه نقد و معادل آن ÷ بدهی جاری؛ این شاخص جریان نقد عملیاتی نیست.'],
            ['leverage','بدهی به دارایی','درصد','total_liabilities','total_assets','کل بدهی ÷ کل دارایی × ۱۰۰؛ مقادیر بیش از ۱۰۰٪ محدود نمی‌شوند.'],
            ['equity','حقوق مالکانه','ریال','total_equity',null,'مقادیر منفی بدون تغییر علامت نمایش داده می‌شوند.'],
            ['working-capital','سرمایه در گردش خالص','ریال','working_capital',null,'دارایی جاری − بدهی جاری؛ برای مقایسه کسری یا مازاد منابع کوتاه‌مدت.'],
        ];
        foreach ($types as $type=>$typeLabel) {
            $typed = $statements->where('period_type',$type);
            if ($typed->isEmpty()) { continue; }
            $periods = $typed->map(fn ($s) => sprintf('%04d/%02d',$s->year,$s->month))->unique()->sort()->values()->all();
            $matrix = $typed->groupBy(fn ($s) => $projectKeys[$s->project_id])->map(fn ($rows) => $rows->groupBy(fn ($s) => sprintf('%04d/%02d',$s->year,$s->month)));
            $aligned = [];
            foreach ($selectedCompanies as $key) {
                $aligned[$key] = [];
                foreach ($periods as $period) {
                    $matches = $matrix->get($key,collect())->get($period,collect());
                    $aligned[$key][] = $matches->count() === 1 ? $matches->first() : null;
                    if ($matches->count()>1) { $notes[] = $companies->firstWhere('id',$key)['name'].' / '.$typeLabel.' '.$period.': صورت مالی تکراری؛ این نقطه تا اصلاح اسناد محاسبه نمی‌شود.'; }
                }
            }
            foreach ($definitions as [$id,$title,$unit,$field,$denominator,$note]) {
                $series=[];
                foreach ($selectedCompanies as $key) {
                    $values = array_map(function ($row) use ($field,$denominator,$unit) {
                        if ($field==='working_capital') { return $this->difference($this->value($row,'total_current_assets'),$this->value($row,'total_current_liabilities')); }
                        return $denominator ? $this->ratio($this->value($row,$field),$this->value($row,$denominator),$unit==='درصد'?100:1) : $this->value($row,$field);
                    }, $aligned[$key]);
                    $style=$styles[$key];
                    $series[]=['label'=>$companies->firstWhere('id',$key)['name'],'values'=>$values,'color'=>$style['color'],'dash'=>$style['dash'],'point'=>$style['point']];
                }
                $charts[] = ['id'=>$type.'-'.$id,'title'=>$title.' — '.$typeLabel,'type'=>'line','unit'=>$unit,'labels'=>$periods,'series'=>$series,'note'=>$note,'periodType'=>$typeLabel];
            }
        }
        return compact('companies','selectedCompanies','charts','notes');
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
}
