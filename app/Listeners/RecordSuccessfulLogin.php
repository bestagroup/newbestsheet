<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function __construct(private readonly ActivityLogService $activityLog) {}

    public function handle(Login $event): void
    {
        $this->activityLog->record(
            'login',
            'ورود موفق کاربر به سامانه.',
            (int) $event->user->getAuthIdentifier()
        );
    }
}
