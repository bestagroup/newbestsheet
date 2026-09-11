<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use App\Models\OperationalNotificationDelivery;
use App\Models\User;
use App\Notifications\InternalNotification;
use App\Support\IranianMobileNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OperationalNotificationService
{
    public function __construct(private readonly SmsGateway $smsGateway) {}

    /**
     * @param  iterable<User>  $users
     * @param  array<int, string>  $extraPhones
     */
    public function deliver(
        string $eventKey,
        string $triggerKey,
        ?Model $related,
        iterable $users,
        array $notificationPayload,
        ?string $smsMessage = null,
        array $extraPhones = []
    ): void {
        $users = collect($users)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();

        foreach ($users as $user) {
            $this->deliverInternal($eventKey, $triggerKey, $related, $user, $notificationPayload);
        }

        if (! config('operations.notifications.sms_enabled', true) || blank($smsMessage)) {
            return;
        }

        $phones = $users->pluck('phone')
            ->merge($extraPhones)
            ->map(static fn ($phone): ?string => IranianMobileNormalizer::normalize(is_scalar($phone) ? (string) $phone : null))
            ->filter()
            ->unique()
            ->values();

        foreach ($phones as $phone) {
            $user = $users->first(
                static fn (User $candidate): bool => IranianMobileNormalizer::normalize((string) $candidate->phone) === $phone
            );

            $this->deliverSms($eventKey, $triggerKey, $related, $user, $phone, (string) $smsMessage);
        }
    }

    private function deliverInternal(
        string $eventKey,
        string $triggerKey,
        ?Model $related,
        User $user,
        array $payload
    ): void {
        $delivery = $this->delivery($eventKey, $triggerKey, 'database', $related, $user->id, null, $payload);
        if (! $this->claim($delivery)) {
            return;
        }

        try {
            $user->notify(new InternalNotification($payload));
            $this->markSent($delivery);
        } catch (Throwable $exception) {
            $this->markFailed($delivery, $exception);
        }
    }

    private function deliverSms(
        string $eventKey,
        string $triggerKey,
        ?Model $related,
        ?User $user,
        string $phone,
        string $message
    ): void {
        $delivery = $this->delivery($eventKey, $triggerKey, 'sms', $related, $user?->id, $phone, [
            'message' => $message,
        ]);
        if (! $this->claim($delivery)) {
            return;
        }

        try {
            $this->smsGateway->send($phone, $message);
            $this->markSent($delivery);
        } catch (Throwable $exception) {
            $this->markFailed($delivery, $exception);
        }
    }

    private function delivery(
        string $eventKey,
        string $triggerKey,
        string $channel,
        ?Model $related,
        ?int $recipientUserId,
        ?string $recipientPhone,
        array $metadata
    ): OperationalNotificationDelivery {
        $relatedType = $related ? $related::class : null;
        $relatedId = $related?->getKey();
        $recipientPhone = IranianMobileNormalizer::normalize($recipientPhone);
        $recipient = $recipientUserId ? 'u:'.$recipientUserId : 'p:'.($recipientPhone ?? '-');
        $fingerprint = hash('sha256', implode('|', [
            $eventKey,
            $triggerKey,
            $channel,
            $recipient,
            $relatedType ?? '-',
            $relatedId ?? '-',
        ]));

        try {
            return OperationalNotificationDelivery::query()->firstOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'event_key' => $eventKey,
                    'trigger_key' => $triggerKey,
                    'channel' => $channel,
                    'recipient_user_id' => $recipientUserId,
                    'recipient_phone' => $recipientPhone,
                    'related_type' => $relatedType,
                    'related_id' => $relatedId,
                    'status' => 'pending',
                    'metadata' => $metadata,
                ]
            );
        } catch (QueryException $exception) {
            // Another request may have created the same fingerprint concurrently.
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }

            return OperationalNotificationDelivery::query()->where('fingerprint', $fingerprint)->firstOrFail();
        }
    }

    /**
     * Atomically claim a delivery before touching an external channel.
     * This closes the race where two scheduler/request processes read the same
     * pending row and both send it. A crashed worker can be reclaimed after
     * the configured timeout.
     */
    private function claim(OperationalNotificationDelivery $delivery): bool
    {
        $maxAttempts = max(1, (int) config('operations.notifications.delivery_max_attempts', 3));
        $claimTimeout = max(1, (int) config('operations.notifications.delivery_claim_timeout_minutes', 15));
        $staleBefore = now()->subMinutes($claimTimeout);

        $claimed = OperationalNotificationDelivery::query()
            ->whereKey($delivery->getKey())
            ->where('attempts', '<', $maxAttempts)
            ->where(function ($query) use ($staleBefore): void {
                $query->whereIn('status', ['pending', 'failed'])
                    ->orWhere(function ($stale) use ($staleBefore): void {
                        $stale->where('status', 'processing')
                            ->where('updated_at', '<=', $staleBefore);
                    });
            })
            ->update([
                'status' => 'processing',
                'attempts' => DB::raw('attempts + 1'),
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            return false;
        }

        $delivery->refresh();

        return true;
    }

    private function markSent(OperationalNotificationDelivery $delivery): void
    {
        $delivery->forceFill([
            'status' => 'sent',
            'sent_at' => now(),
            'last_error' => null,
        ])->save();
    }

    private function markFailed(OperationalNotificationDelivery $delivery, Throwable $exception): void
    {
        $delivery->forceFill([
            'status' => 'failed',
            'last_error' => mb_substr($exception->getMessage(), 0, 4000),
        ])->save();

        Log::warning('Operational notification delivery failed.', [
            'event_key' => $delivery->event_key,
            'channel' => $delivery->channel,
            'recipient_user_id' => $delivery->recipient_user_id,
            'recipient_phone' => $delivery->recipient_phone,
            'attempts' => $delivery->attempts,
            'error' => $exception->getMessage(),
        ]);
    }
}
