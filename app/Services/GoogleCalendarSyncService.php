<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\User;
use Google\Service\Calendar\Event;
use Illuminate\Support\Collection;
use Morilog\Jalali\Jalalian;
use Throwable;

class GoogleCalendarSyncService
{
    public function __construct(
        private readonly GoogleService $google,
    ) {}

    public function canSync(User $user): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled($user->google_token)
            && filled($user->google_refresh_token);
    }

    public function sync(Calendar $calendar, User $user, array $attendeeEmails): void
    {
        if (! $this->canSync($user)) {
            $calendar->forceFill([
                'google_sync_status' => 'not_connected',
                'google_sync_error' => null,
            ])->save();

            return;
        }

        try {
            $service = $this->google->getClient($user);
            $event = new Event([
                'summary' => $calendar->title,
                'description' => $calendar->description,
                'location' => $calendar->location,
                'start' => ['dateTime' => $this->toGoogleDateTime($calendar->start)],
                'end' => ['dateTime' => $this->toGoogleDateTime($calendar->end ?: $calendar->start)],
                'attendees' => $this->attendeeEmails($user, $attendeeEmails)
                    ->map(fn (string $email) => ['email' => $email])
                    ->all(),
            ]);

            if ($calendar->google_event_id) {
                $remote = $service->events->update(
                    'primary',
                    $calendar->google_event_id,
                    $event,
                    ['sendUpdates' => 'all']
                );
            } else {
                $remote = $service->events->insert('primary', $event, ['sendUpdates' => 'all']);
            }

            $calendar->forceFill([
                'google_event_id' => $remote->getId(),
                'google_sync_status' => 'synced',
                'google_sync_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            report($exception);
            $calendar->forceFill([
                'google_sync_status' => 'failed',
                'google_sync_error' => mb_substr($exception->getMessage(), 0, 2000),
            ])->save();
        }
    }

    public function delete(Calendar $calendar, User $user): void
    {
        if (! $calendar->google_event_id || ! $this->canSync($user)) {
            return;
        }

        try {
            $this->google->getClient($user)->events->delete(
                'primary',
                $calendar->google_event_id,
                ['sendUpdates' => 'all']
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @return Collection<int, string> */
    private function attendeeEmails(User $organizer, array $emails): Collection
    {
        $organizerEmail = mb_strtolower(trim((string) $organizer->email));

        return collect($emails)
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->reject(fn (string $email) => $email === $organizerEmail)
            ->unique()
            ->values();
    }

    private function toGoogleDateTime(string $jalali): string
    {
        return Jalalian::fromFormat('Y-m-d H:i:s', $jalali)
            ->toCarbon()
            ->setTimezone(config('app.timezone', 'Asia/Tehran'))
            ->format('Y-m-d\TH:i:sP');
    }
}
