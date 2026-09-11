<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\InternalNotification;
use Throwable;

class InternalNotificationService
{
    /**
     * @param  iterable<User>  $users
     */
    public function send(iterable $users, array $payload, ?int $excludeUserId = null): void
    {
        collect($users)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->reject(fn (User $user) => $excludeUserId !== null && (int) $user->id === $excludeUserId)
            ->each(function (User $user) use ($payload): void {
                try {
                    $user->notify(new InternalNotification($payload));
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
    }
}
