<?php

namespace App\Support;

use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Throwable;

final class LocalizedInputNormalizer
{
    private const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private const LATIN_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    public static function digits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_replace(
            [...self::PERSIAN_DIGITS, ...self::ARABIC_DIGITS],
            [...self::LATIN_DIGITS, ...self::LATIN_DIGITS],
            trim($value)
        );
    }

    public static function unsignedInteger(?string $value): ?string
    {
        $value = self::digits($value);

        if ($value === null || $value === '') {
            return null;
        }

        return str_replace([',', '٬', '،', ' '], '', $value);
    }

    public static function decimal(?string $value): ?string
    {
        $value = self::digits($value);

        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace([',', '٬', '،', ' '], '', $value);

        return str_replace('٫', '.', $value);
    }

    public static function jalaliDate(?string $value): ?string
    {
        $value = self::digits($value);

        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(['.', '-'], '/', $value);

        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $normalized, $compact)) {
            $normalized = sprintf('%04d/%02d/%02d', (int) $compact[1], (int) $compact[2], (int) $compact[3]);
        }

        if (! preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $normalized, $matches)) {
            return $value;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if ($year >= 1700) {
            try {
                return Jalalian::fromCarbon(
                    Carbon::create($year, $month, $day)->startOfDay()
                )->format('Y/m/d');
            } catch (Throwable) {
                return $value;
            }
        }

        $canonical = sprintf('%04d/%02d/%02d', $year, $month, $day);

        try {
            Jalalian::fromFormat('Y/m/d', $canonical)->toCarbon();

            return $canonical;
        } catch (Throwable) {
            return $value;
        }
    }

    public static function date(?string $value): ?string
    {
        $value = self::digits($value);

        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(['.', '-'], '/', $value);

        if (! preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $normalized, $matches)) {
            return $value;
        }

        $normalized = sprintf('%04d/%02d/%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3]);

        if ((int) $matches[1] < 1700) {
            try {
                return Jalalian::fromFormat('Y/m/d', $normalized)
                    ->toCarbon()
                    ->format('Y-m-d');
            } catch (Throwable) {
                return $normalized;
            }
        }

        return str_replace('/', '-', $normalized);
    }
}
