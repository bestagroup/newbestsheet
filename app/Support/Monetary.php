<?php
namespace App\Support;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
final class Monetary
{
    public static function value(mixed $value): BigDecimal
    {
        $normalized = LocalizedInputNormalizer::decimal($value === null ? null : (string) $value);
        return BigDecimal::of($normalized === null || $normalized === '' ? '0' : $normalized);
    }
    public static function sum(iterable $values): string
    {
        $sum = BigDecimal::zero();
        foreach ($values as $value) { $sum = $sum->plus(self::value($value)); }
        return (string) $sum;
    }
    public static function difference(mixed $left, mixed $right, bool $floorZero = false): string
    {
        $value = self::value($left)->minus(self::value($right));
        return (string) ($floorZero && $value->isNegative() ? BigDecimal::zero() : $value);
    }
    public static function format(mixed $value): string
    {
        $value = (string) self::value($value)->toScale(0, RoundingMode::HALF_UP);
        return preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $value);
    }
}
