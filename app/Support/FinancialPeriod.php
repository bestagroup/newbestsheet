<?php

namespace App\Support;

use Morilog\Jalali\CalendarUtils;

/** Strict, non-inferential normalization of Solar Hijri reporting dates. */
final class FinancialPeriod
{
    public static function normalize(mixed $value): ?string
    {
        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }
        $value = str_replace(['-', '.'], '/', LocalizedInputNormalizer::digits(trim((string) $value)));
        if (! preg_match('~^(\d{4})/(\d{1,2})/(\d{1,2})$~', $value, $parts)) {
            return null;
        }
        [$year, $month, $day] = array_map('intval', array_slice($parts, 1));
        if ($year < 1300 || $year > 1600 || ! CalendarUtils::checkDate($year, $month, $day)) {
            return null;
        }

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

    public static function errors(array $data): array
    {
        $errors = [];
        $basis = $data['income_basis'] ?? 'unconfirmed';
        $start = self::normalize($data['period_start'] ?? null);
        $end = self::normalize($data['period_end'] ?? null);
        $hasDates = ! empty($data['period_start']) || ! empty($data['period_end']);
        if ($basis === 'unconfirmed' && ! $hasDates) {
            return [];
        }
        foreach (['period_start' => $start, 'period_end' => $end] as $field => $date) {
            if ($date === null) {
                $errors[$field] = 'تاریخ شمسی معتبر با قالب ۱۴۰۴/۰۱/۰۱ وارد کنید؛ هر دو تاریخ الزامی است.';
            }
        }
        if ($basis === 'unconfirmed') {
            $errors['income_basis'] = 'برای دوره دارای تاریخ، مبنای مستقل یا تجمعی را مشخص کنید.';
        }
        if ($start && $end) {
            if ($start > $end) {
                $errors['period_end'] = 'پایان دوره نباید قبل از شروع آن باشد.';
            }
            if ((int) substr($end, 0, 4) !== (int) ($data['year'] ?? 0) || (int) substr($end, 5, 2) !== (int) ($data['month'] ?? 0)) {
                $errors['period_end'] = 'سال و ماه انتخاب‌شده باید با تاریخ پایان دوره یکسان باشد.';
            }
            $days = CalendarUtils::toGregorian(...array_map('intval', explode('/', $start)));
            $endDays = CalendarUtils::toGregorian(...array_map('intval', explode('/', $end)));
            $duration = (new \DateTimeImmutable(implode('-', $days)))->diff(new \DateTimeImmutable(implode('-', $endDays)))->days + 1;
            $maximum = ($data['period_type'] ?? '') === 'quarterly' && $basis === 'standalone' ? 93 : 366;
            if ($duration > $maximum) {
                $errors['period_start'] = 'طول دوره با نوع و مبنای انتخاب‌شده سازگار نیست.';
            }
        }
        if (($data['period_type'] ?? '') === 'annual' && $basis === 'ytd') {
            $errors['income_basis'] = 'صورت مالی سالانه را با مبنای مستقل ثبت کنید؛ تجمعی برای گزارش میان‌دوره‌ای است.';
        }

        return $errors;
    }
}
