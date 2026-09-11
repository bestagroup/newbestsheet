<?php

$intList = static function (string $key, string $default): array {
    return collect(explode(',', (string) env($key, $default)))
        ->map(static fn ($value): int => (int) trim($value))
        ->filter(static fn (int $value): bool => $value >= 0)
        ->unique()
        ->values()
        ->all();
};

return [
    'failed_jobs_retention_hours' => (int) env('FAILED_JOBS_RETENTION_HOURS', 720),

    'notifications' => [
        'sms_enabled' => (bool) env('OPERATIONAL_SMS_ENABLED', true),
        'delivery_max_attempts' => max(1, (int) env('OPERATIONAL_DELIVERY_MAX_ATTEMPTS', 3)),
        'delivery_claim_timeout_minutes' => max(1, (int) env('OPERATIONAL_DELIVERY_CLAIM_TIMEOUT_MINUTES', 15)),
    ],

    'reminders' => [
        'enabled' => (bool) env('OPERATIONAL_REMINDERS_ENABLED', true),
        'kpi_days_before' => $intList('KPI_REMINDER_DAYS', '7,3,1,0'),
        'kpi_overdue_days' => $intList('KPI_OVERDUE_REMINDER_DAYS', '1,3,7'),
        'commitment_days_before' => $intList('COMMITMENT_REMINDER_DAYS', '14,7,3,1,0'),
        'commitment_overdue_days' => $intList('COMMITMENT_OVERDUE_REMINDER_DAYS', '1,3,7'),
        'calendar_minutes_before' => $intList('CALENDAR_REMINDER_MINUTES', '1440,60'),
    ],
];
