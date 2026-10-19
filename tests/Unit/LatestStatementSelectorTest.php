<?php

namespace Tests\Unit;

use App\Models\Financial_statement;
use App\Services\FinancialReporting\LatestStatementSelector;
use PHPUnit\Framework\TestCase;

class LatestStatementSelectorTest extends TestCase
{
    public function test_newest_date_wins_and_annual_breaks_same_date_ties(): void
    {
        $annual = $this->statement(1405, 12, 'annual');
        $rows = collect([$this->statement(1404, 12, 'annual'), $this->statement(1405, 12, 'quarterly'), $annual]);
        $selector = new LatestStatementSelector;
        $this->assertSame($annual, $selector->select($rows)['statement']);
        $newer = $this->statement(1406, 3, 'quarterly');
        $this->assertSame($newer, $selector->select($rows->push($newer))['statement']);
    }

    public function test_duplicate_latest_period_does_not_fall_back_to_an_older_record(): void
    {
        $rows = collect([$this->statement(1404, 12, 'annual'), $this->statement(1405, 12, 'annual'), $this->statement(1405, 12, 'annual')]);
        $this->assertSame(['statement' => null, 'conflict' => true], (new LatestStatementSelector)->select($rows));
    }

    public function test_missing_and_invalid_periods_have_no_selected_record(): void
    {
        $selector = new LatestStatementSelector;
        $this->assertSame(['statement' => null, 'conflict' => false], $selector->select(collect()));
        $this->assertNull($selector->select(collect([$this->statement(1405, 13, 'annual')]))['statement']);
    }

    private function statement(int $year, int $month, string $type): Financial_statement
    {
        return new Financial_statement(['year' => $year, 'month' => $month, 'period_type' => $type]);
    }
}
