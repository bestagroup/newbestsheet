<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use App\Support\IranianMobileNormalizer;
use Ghasedak\GhasedakApi;
use RuntimeException;

class GhasedakSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        $apiKey = trim((string) config('services.ghasedak.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('Ghasedak API key is not configured.');
        }

        $phone = IranianMobileNormalizer::normalize($phone);
        if ($phone === null) {
            throw new RuntimeException('SMS recipient phone is empty or invalid.');
        }

        $lineNumber = trim((string) config('services.ghasedak.line_number')) ?: null;
        $api = new GhasedakApi($apiKey);
        $api->SendSimple($phone, $message, $lineNumber);
    }
}
