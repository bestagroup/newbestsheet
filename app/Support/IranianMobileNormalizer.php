<?php

namespace App\Support;

final class IranianMobileNormalizer
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = str_replace([' ', '-', '(', ')'], '', trim($phone));
        $phone = strtr($phone, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if (str_starts_with($phone, '+98')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '0098')) {
            $phone = '0'.substr($phone, 4);
        } elseif (str_starts_with($phone, '98') && strlen($phone) === 12) {
            $phone = '0'.substr($phone, 2);
        }

        return preg_match('/^09\d{9}$/', $phone) === 1 ? $phone : null;
    }
}
