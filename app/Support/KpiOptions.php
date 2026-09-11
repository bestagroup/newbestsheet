<?php

namespace App\Support;

final class KpiOptions
{
    public const TYPES = [
        'کمی',
        'کیفی',
    ];

    public const BASES = [
        'وزن',
        'تعداد',
        'حجم',
        'مبلغ',
        'درصد',
        'زمان',
        'مسافت',
        'امتیاز',
    ];

    public const UNITS = [
        'عدد',
        'درصد',
        'ریال',
        'تومان',
        'متر',
        'متر مربع',
        'متر مکعب',
        'سانتی‌متر',
        'کیلومتر',
        'گرم',
        'کیلوگرم',
        'تن',
        'میلی‌لیتر',
        'لیتر',
        'ساعت',
        'روز',
        'نفر',
        'امتیاز',
        'بدون واحد',
    ];

    public const PERIODS = [
        'روزانه',
        'ماهانه',
        'فصلی',
        'سالانه',
    ];

    private const FREQUENCIES = [
        'روزانه' => 'daily',
        'ماهانه' => 'monthly',
        'فصلی' => 'quarterly',
        'سالانه' => 'annual',
    ];

    public static function frequencyForPeriod(?string $period): ?string
    {
        return self::FREQUENCIES[$period ?? ''] ?? null;
    }
}
