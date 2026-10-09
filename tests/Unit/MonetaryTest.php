<?php
namespace Tests\Unit;
use App\Support\Monetary;
use PHPUnit\Framework\TestCase;
final class MonetaryTest extends TestCase
{
    public function test_large_rial_amounts_never_pass_through_float(): void
    {
        $this->assertSame('9007199254740995', Monetary::sum(['9007199254740993', '2']));
        $this->assertSame('9,007,199,254,740,993', Monetary::format('9007199254740993'));
        $this->assertSame('2', Monetary::difference('9007199254740995', '9007199254740993'));
        $this->assertSame('0', Monetary::difference('1', '2', true));
        $this->assertSame('-1,250', Monetary::format('-1250'));
    }
}
