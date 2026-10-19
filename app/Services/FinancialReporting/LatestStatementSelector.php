<?php

namespace App\Services\FinancialReporting;

use App\Models\Financial_statement;
use Illuminate\Support\Collection;

/** Select a company's latest observation without summing incompatible periods. */
final class LatestStatementSelector
{
    public const TYPES = ['annual', 'quarterly', 'legacy'];

    /**
     * Newest period first; at the same date prefer annual, quarterly, then legacy.
     * Duplicate company/period/type records are ambiguous, never silently deduplicated.
     *
     * @param  Collection<int, Financial_statement>  $statements
     */
    public function select(Collection $statements): array
    {
        $ordered = $statements->filter(fn ($row) => $row->year !== null
            && $row->month >= 1 && $row->month <= 12
            && in_array($row->period_type, self::TYPES, true))
            ->sortBy(fn ($row) => sprintf('%04d%02d%d', $row->year, $row->month,
                3 - array_search($row->period_type, self::TYPES, true)), SORT_STRING, true);
        $latest = $ordered->first();
        if ($latest === null) {
            return ['statement' => null, 'conflict' => false];
        }

        $duplicates = $ordered->filter(fn ($row) => $row->year === $latest->year
            && $row->month === $latest->month && $row->period_type === $latest->period_type)->count();

        return ['statement' => $duplicates === 1 ? $latest : null, 'conflict' => $duplicates > 1];
    }
}
