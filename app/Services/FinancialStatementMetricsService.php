<?php

namespace App\Services;

use Illuminate\Support\Collection;

class FinancialStatementMetricsService
{
    public function build(Collection $records): array
    {
        $records = $records
            ->sortBy(fn ($record) => sprintf('%04d%02d', (int) $record->year, (int) $record->month))
            ->values();

        $labels = $records->map(
            static fn ($record): string => sprintf('%04d/%02d', (int) $record->year, (int) $record->month)
        )->values();

        $series = [
            'netSales' => $this->series($records, fn ($row) => $this->number($row->net_sales)),
            'cogsRatio' => $this->series($records, fn ($row) => $this->percent(
                $this->number($row->cogs_goods) + $this->number($row->cogs_services),
                $this->number($row->net_sales)
            )),
            'grossMargin' => $this->series($records, fn ($row) => $this->percent(
                $this->number($row->gross_profit),
                $this->number($row->net_sales)
            )),
            'sgaRatio' => $this->series($records, fn ($row) => $this->percent(
                $this->number($row->selling_general_admin_expense),
                $this->number($row->net_sales)
            )),
            'currentAssetRatio' => $this->series($records, fn ($row) => $this->percent(
                $this->number($row->total_current_assets),
                $this->number($row->total_assets)
            )),
            'currentRatio' => $this->series($records, fn ($row) => $this->ratio(
                $this->number($row->total_current_assets),
                $this->number($row->total_current_liabilities)
            )),
            'debtToEquity' => $this->series($records, fn ($row) => $this->ratio(
                $this->number($row->total_liabilities),
                $this->number($row->total_equity)
            )),
            'roa' => $this->series($records, fn ($row) => $this->percent(
                $this->number($row->net_profit),
                $this->number($row->total_assets)
            )),
            'profitQuality' => $this->series($records, fn ($row) => $this->percent(
                $this->number($row->net_profit) - $this->number($row->non_operating_net),
                $this->number($row->net_profit)
            )),
            'balanceCheck' => $this->series($records, fn ($row) => round(
                $this->number($row->total_assets) - $this->number($row->total_equity_and_liabilities),
                2
            )),
        ];

        foreach ($series as $key => $data) {
            $series[$key] = ['labels' => $labels, 'data' => $data];
        }

        return [
            'series' => $series,
            'summary' => $this->summary($records),
        ];
    }

    public function number(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $value = str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            (string) $value
        );
        $value = str_replace([',', '٬', '،', ' '], '', $value);
        $value = str_replace('٫', '.', $value);

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function series(Collection $records, callable $resolver): Collection
    {
        return $records->map(fn ($record) => $resolver($record))->values();
    }

    private function ratio(float $numerator, float $denominator): float
    {
        return $denominator == 0.0 ? 0.0 : round($numerator / $denominator, 4);
    }

    private function percent(float $numerator, float $denominator): float
    {
        return $denominator == 0.0 ? 0.0 : round(($numerator / $denominator) * 100, 2);
    }

    private function summary(Collection $records): array
    {
        $latest = $records->last();
        $previous = $records->count() > 1 ? $records->slice(-2, 1)->first() : null;

        if (! $latest) {
            return [
                'period' => null,
                'net_sales' => 0,
                'net_profit' => 0,
                'total_assets' => 0,
                'total_equity' => 0,
                'total_liabilities' => 0,
                'current_ratio' => 0,
                'debt_to_equity' => 0,
                'roa' => 0,
                'sales_growth' => null,
                'profit_growth' => null,
                'balance_difference' => 0,
            ];
        }

        $netSales = $this->number($latest->net_sales);
        $netProfit = $this->number($latest->net_profit);
        $totalAssets = $this->number($latest->total_assets);
        $totalEquity = $this->number($latest->total_equity);
        $totalLiabilities = $this->number($latest->total_liabilities);

        return [
            'period' => sprintf('%04d/%02d', (int) $latest->year, (int) $latest->month),
            'net_sales' => $netSales,
            'net_profit' => $netProfit,
            'total_assets' => $totalAssets,
            'total_equity' => $totalEquity,
            'total_liabilities' => $totalLiabilities,
            'current_ratio' => $this->ratio(
                $this->number($latest->total_current_assets),
                $this->number($latest->total_current_liabilities)
            ),
            'debt_to_equity' => $this->ratio($totalLiabilities, $totalEquity),
            'roa' => $this->percent($netProfit, $totalAssets),
            'sales_growth' => $previous ? $this->growth($netSales, $this->number($previous->net_sales)) : null,
            'profit_growth' => $previous ? $this->growth($netProfit, $this->number($previous->net_profit)) : null,
            'balance_difference' => round(
                $totalAssets - $this->number($latest->total_equity_and_liabilities),
                2
            ),
        ];
    }

    private function growth(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 2);
    }
}
