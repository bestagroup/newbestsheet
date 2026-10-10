<?php

namespace App\Services;

use App\Support\Monetary;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Reporting calculations: null is unknown, amounts remain decimal strings. */
final class InvestmentFinancialChartsService
{
    private const FIELDS = ['net_sales', 'net_profit', 'gross_profit', 'total_current_assets',
        'total_current_liabilities', 'cash_and_equivalents', 'total_assets', 'total_liabilities', 'total_equity'];

    public function build(Request $request, Collection $projects, Collection $statements, Collection $payments): array
    {
        $periodType = in_array($request->input('period_type'), ['annual', 'quarterly', 'legacy'], true) ? $request->input('period_type') : 'legacy';
        $basis = in_array($request->input('income_basis'), ['standalone', 'ytd'], true) ? $request->input('income_basis') : 'unconfirmed';
        $incomeAllowed = $periodType === 'annual' || ($periodType === 'quarterly' && $basis !== 'unconfirmed');
        $keys = $projects->mapWithKeys(fn ($p) => [$p->id => $p->company_id ? 'company:'.$p->company_id : 'project:'.$p->id]);
        $companies = $projects->groupBy(fn ($p) => $keys[$p->id]);
        $expected = $companies->count();
        $validStatements = $statements->filter(fn ($s) => isset($keys[$s->project_id]) && $s->year > 0 && $s->month >= 1 && $s->month <= 12 && ($periodType !== 'quarterly' || in_array((int) $s->month, [3, 6, 9, 12], true)));
        $invalidPeriods = $statements->count() - $validStatements->count();
        $periodRows = $validStatements
            ->groupBy(fn ($s) => sprintf('%04d/%02d', $s->year, $s->month))->sortKeys();
        $periodKeys = $periodRows->keys()->all();
        // Include gaps for known quarterly dates, never connect across an absent quarter.
        if ($periodType === 'quarterly' && count($periodKeys) > 1) {
            [$fy, $fm] = array_map('intval', explode('/', $periodKeys[0]));
            [$ly, $lm] = array_map('intval', explode('/', end($periodKeys)));
            if (($ly - $fy) <= 50) {
                $periodKeys = [];
                for ($serial = $fy * 12 + $fm; $serial <= $ly * 12 + $lm; $serial += 3) {
                    $periodKeys[] = sprintf('%04d/%02d', intdiv($serial - 1, 12), ($serial - 1) % 12 + 1);
                }
            }
        }
        if ($periodType === 'annual' && count($periodKeys) > 1) {
            $firstYear = (int) substr($periodKeys[0], 0, 4);
            $lastYear = (int) substr(end($periodKeys), 0, 4);
            $months = array_unique(array_map(fn ($p) => (int) substr($p, 5, 2), $periodKeys));
            if (count($months) === 1 && $lastYear - $firstYear <= 50) {
                $periodKeys = array_map(fn ($year) => sprintf('%04d/%02d', $year, $months[0]), range($firstYear, $lastYear));
            }
        }
        $periods = []; $quality = []; $snapshots = [];
        foreach ($periodKeys as $period) {
            $grouped = $periodRows->get($period, collect())->groupBy(fn ($s) => $keys[$s->project_id]);
            $usable = collect(); $conflicts = 0;
            foreach ($grouped as $companyKey => $rows) {
                // Multiple dossiers must not silently duplicate one company's accounts.
                if ($rows->count() !== 1) { $conflicts++; continue; }
                $usable->put($companyKey, $rows->first());
            }
            $coverage = []; $totals = [];
            foreach (self::FIELDS as $field) {
                $known = $usable->filter(fn ($row) => $this->decimal($row->{$field}) !== null);
                $coverage[$field] = $known->count();
                $totals[$field] = $expected > 0 && $known->count() === $expected
                    ? Monetary::sum($known->pluck($field)) : null;
            }
            $periods[] = [
                'period' => $period, 'reported' => $grouped->count(), 'usable' => $usable->count(),
                'expected' => $expected, 'conflicts' => $conflicts, 'coverage' => $coverage,
                'sales' => $incomeAllowed ? $totals['net_sales'] : null,
                'profit' => $incomeAllowed ? $totals['net_profit'] : null,
                'gross_margin' => $incomeAllowed ? $this->ratio($totals['gross_profit'], $totals['net_sales'], 100) : null,
                'net_margin' => $incomeAllowed ? $this->ratio($totals['net_profit'], $totals['net_sales'], 100) : null,
                'current_ratio' => $this->ratio($totals['total_current_assets'], $totals['total_current_liabilities']),
                'cash_ratio' => $this->ratio($totals['cash_and_equivalents'], $totals['total_current_liabilities']),
                'debt_assets' => $this->ratio($totals['total_liabilities'], $totals['total_assets'], 100),
                'working_capital' => $this->difference($totals['total_current_assets'], $totals['total_current_liabilities']),
            ];
            $snapshots[$period] = $usable;
        }
        $latestPeriod = $periodKeys ? end($periodKeys) : null;
        $comparison = [];
        foreach ($companies as $key => $dossiers) {
            $name = $dossiers->first()->company?->company_name ?: ($dossiers->first()->company_name ?: $dossiers->first()->title);
            $row = $latestPeriod ? ($snapshots[$latestPeriod][$key] ?? null) : null;
            $difference = $row ? $this->difference($this->value($row, 'total_assets'), $this->sumKnown($row, ['total_liabilities', 'total_equity'])) : null;
            $equity = $row ? $this->value($row, 'total_equity') : null;
            $quality[] = ['company' => $name, 'period' => $latestPeriod, 'available' => $row !== null,
                'balance_difference' => $difference, 'negative_equity' => $equity === null ? null : BigDecimal::of($equity)->isNegative(),
                'missing_fields' => $row ? count(array_filter(self::FIELDS, fn ($f) => $this->value($row, $f) === null)) : count(self::FIELDS)];
            $comparison[] = ['label' => $name, 'value' => $incomeAllowed && $row ? $this->value($row, 'net_profit') : null];
        }
        $paidGroups = $payments->filter(fn ($p) => isset($keys[$p->project_id]))->groupBy(fn ($p) => $keys[$p->project_id]);
        $concentration = []; $excludedPayments = 0;
        foreach ($companies as $key => $dossiers) {
            $group = $paidGroups->get($key, collect());
            $valid = $group->filter(fn ($p) => $this->decimal($p->amount) !== null);
            $excludedPayments += $group->count() - $valid->count();
            $amount = Monetary::sum($valid->pluck('amount'));
            $concentration[] = ['label' => $dossiers->first()->company?->company_name ?: ($dossiers->first()->company_name ?: $dossiers->first()->title), 'value' => $amount];
        }
        $concentration = collect($concentration)->sort(fn ($a, $b) => BigDecimal::of($b['value'])->compareTo($a['value']))->values();
        if ($concentration->count() > 10) {
            $other = Monetary::sum($concentration->slice(10)->pluck('value'));
            $concentration = $concentration->take(10)->push(['label' => 'سایر شرکت‌ها', 'value' => $other]);
        }
        $labels = array_column($periods, 'period');
        $charts = [
            $this->chart('revenue-profit', 'فروش و سود خالص دوره', 'bar', 'ریال', $labels, [
                $this->series('فروش خالص', array_column($periods, 'sales'), '#183b56'),
                $this->series('سود / زیان خالص', array_column($periods, 'profit'), '#16866b')], 'جمع شرکت‌های منتخب در هر دوره؛ بدون جمع‌زدن دوره‌ها.'),
            $this->chart('margins', 'حاشیه سود ناخالص و خالص', 'line', 'درصد', $labels, [
                $this->series('حاشیه سود ناخالص', array_column($periods, 'gross_margin'), '#2596be'),
                $this->series('حاشیه سود خالص', array_column($periods, 'net_margin'), '#16866b')], 'سود تقسیم بر فروش × ۱۰۰؛ نسبت مجموع‌ها، نه میانگین درصد شرکت‌ها. فروش صفر یا منفی: محاسبه نمی‌شود.'),
            $this->chart('liquidity', 'پوشش بدهی‌های جاری', 'line', 'برابر', $labels, [
                $this->series('نسبت جاری', array_column($periods, 'current_ratio'), '#183b56'),
                $this->series('نسبت وجه نقد', array_column($periods, 'cash_ratio'), '#2596be')], 'دارایی جاری و وجه نقد، هرکدام تقسیم بر بدهی جاری؛ مخرج صفر یا منفی قابل محاسبه نیست. این نسبت‌ها جریان نقد عملیاتی نیستند.'),
            $this->chart('leverage', 'سهم بدهی از دارایی‌ها', 'bar', 'درصد', $labels, [
                $this->series('بدهی / دارایی', array_column($periods, 'debt_assets'), '#d49a32')], 'کل بدهی تقسیم بر کل دارایی × ۱۰۰؛ عدد بالاتر از ۱۰۰٪ محدود نمی‌شود. نسبت کل، وضعیت تک‌تک شرکت‌ها را تضمین نمی‌کند.'),
            $this->chart('working-capital', 'سرمایه در گردش خالص', 'bar', 'ریال', $labels, [
                $this->series('سرمایه در گردش', array_column($periods, 'working_capital'), '#16866b')], 'دارایی جاری منهای بدهی جاری؛ مقدار منفی با علامت واقعی نمایش داده می‌شود.'),
            $this->chart('concentration', 'توزیع مبلغ پرداخت‌شده بین شرکت‌ها', 'horizontal', 'ریال', $concentration->pluck('label')->all(), [
                $this->series('پرداخت تجمعی', $concentration->pluck('value')->all(), '#183b56')], 'کل عمر سرمایه‌گذاری در پورتفوی فعال فعلی؛ مستقل از فیلتر تاریخ. ۱۰ شرکت نخست و مجموع سایرین؛ مبنای ارزش روز یا بازده سرمایه‌گذاری نیست.'),
            $this->chart('company-profit', 'سود و زیان شرکت‌ها در آخرین دوره منتخب', 'horizontal', 'ریال', array_column($comparison, 'label'), [
                $this->series('سود / زیان خالص', array_column($comparison, 'value'), '#16866b')], 'دوره مشترک: '.($latestPeriod ?: '—').'؛ از آخرین دوره متفاوت هر شرکت برای مقایسه استفاده نمی‌شود.'),
            $this->chart('coverage', 'پوشش صورت‌های مالی هر دوره', 'bar', 'شرکت', $labels, [
                $this->series('صورت مالی یکتا', array_column($periods, 'usable'), '#16866b'),
                $this->series('شرکت‌های منتخب', array_column($periods, 'expected'), '#183b56'),
                $this->series('پرونده‌های تکراری شرکت', array_column($periods, 'conflicts'), '#d45b67')], 'وجود یک صورت مالی به معنی کامل‌بودن تمام اقلام آن نیست؛ جزئیات پوشش هر قلم در جدول کنترل داده آمده است.'),
        ];
        return compact('charts', 'periods', 'quality', 'expected', 'periodType', 'basis', 'incomeAllowed', 'latestPeriod', 'excludedPayments', 'invalidPeriods');
    }

    private function decimal(mixed $value): ?BigDecimal
    {
        if ($value === null || trim((string) $value) === '') { return null; }
        try { return Monetary::value($value); } catch (\Brick\Math\Exception\MathException) { return null; }
    }
    private function value(object $row, string $field): ?string
    {
        $number = $this->decimal($row->{$field});
        return $number === null ? null : (string) $number;
    }
    private function sumKnown(object $row, array $fields): ?string
    {
        $values = array_map(fn ($f) => $this->value($row, $f), $fields);
        return in_array(null, $values, true) ? null : Monetary::sum($values);
    }
    private function difference(?string $a, ?string $b): ?string
    {
        return $a === null || $b === null ? null : Monetary::difference($a, $b);
    }
    private function ratio(?string $a, ?string $b, int $factor = 1): ?string
    {
        if ($a === null || $b === null || BigDecimal::of($b)->compareTo(0) <= 0) { return null; }
        return (string) BigDecimal::of($a)->multipliedBy($factor)->dividedBy($b, 4, RoundingMode::HALF_UP);
    }
    private function series(string $label, array $values, string $color): array
    {
        return compact('label', 'values', 'color');
    }
    private function chart(string $id, string $title, string $type, string $unit, array $labels, array $series, string $note): array
    {
        return compact('id', 'title', 'type', 'unit', 'labels', 'series', 'note');
    }
}
