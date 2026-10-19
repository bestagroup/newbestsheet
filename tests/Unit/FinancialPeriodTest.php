<?php

namespace Tests\Unit;

use App\Support\FinancialPeriod;
use PHPUnit\Framework\TestCase;

class FinancialPeriodTest extends TestCase
{
    public function test_dates_are_strictly_jalali_with_localized_digits_and_no_rollover(): void
    {
        $this->assertSame('1404/01/02', FinancialPeriod::normalize('۱۴۰۴-۱-۲'));
        $this->assertNull(FinancialPeriod::normalize('1404/12/30'));
        $this->assertSame('1403/12/30', FinancialPeriod::normalize('1403/12/30'));
        foreach (['2025/01/01', '1404/13/01', '1404/01/32', '1404/00/01', [], 'x'] as $date) {
            $this->assertNull(FinancialPeriod::normalize($date));
        }
    }

    public function test_incomplete_and_annual_cumulative_periods_are_rejected(): void
    {
        $this->assertSame([], FinancialPeriod::errors(['income_basis' => 'unconfirmed']));
        $this->assertArrayHasKey('period_start', FinancialPeriod::errors(['income_basis' => 'standalone']));
        $this->assertArrayHasKey('income_basis', FinancialPeriod::errors(['year' => 1404, 'month' => 12, 'period_type' => 'annual', 'income_basis' => 'ytd', 'period_start' => '1404/01/01', 'period_end' => '1404/12/29']));
    }
}
