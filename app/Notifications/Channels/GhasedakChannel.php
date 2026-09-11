<?php

namespace App\Notifications\Channels;

use App\Support\IranianMobileNormalizer;
use Ghasedak\GhasedakApi;
use Illuminate\Notifications\Notification;
use RuntimeException;

class GhasedakChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toGhasedakSms')) {
            throw new RuntimeException('Notification must implement toGhasedakSms().');
        }

        $data = $notification->toGhasedakSms($notifiable);
        $apiKey = config('services.ghasedak.api_key');
        $template = config('services.ghasedak.otp_template');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Ghasedak API key is not configured.');
        }

        if (! is_string($template) || $template === '') {
            throw new RuntimeException('Ghasedak OTP template is not configured.');
        }

        $phone = IranianMobileNormalizer::normalize(
            is_scalar($data['phone'] ?? null) ? (string) $data['phone'] : null
        );
        if ($phone === null) {
            throw new RuntimeException('Ghasedak OTP recipient phone is invalid.');
        }

        $api = new GhasedakApi($apiKey);
        $api->Verify($phone, 1, $template, $data['code']);
    }
}
