<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Logout;

class RecordLogout
{
    public function __construct(private readonly ActivityLogService $activityLog) {}

    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        $this->activityLog->record(
            'logout',
            'خروج کاربر از سامانه.',
            (int) $event->user->getAuthIdentifier()
        );
    }
}
