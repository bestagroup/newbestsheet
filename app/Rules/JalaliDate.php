<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Morilog\Jalali\CalendarUtils;

final class JalaliDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(1[34][0-9]{2})\/(\d{2})\/(\d{2})$/', $value, $m)
            || ! CalendarUtils::checkDate((int) $m[1], (int) $m[2], (int) $m[3])) {
            $fail('تاریخ شمسی معتبر با قالب ۱۴۰۵/۰۷/۱۶ وارد کنید.');
        }
    }
}
