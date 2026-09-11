<?php

namespace App\Services;

use App\Models\User;
use Google\Client;
use Google\Service\Calendar;

class GoogleService
{
    public function getClient(User $user): Calendar
    {
        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setApplicationName('Bestsheet Web App');
        $client->setScopes([Calendar::CALENDAR]);
        $client->setAccessType('offline');

        $client->setAccessToken([
            'access_token' => $user->google_token,
            'expires_in' => $user->google_expires_in,
            'refresh_token' => $user->google_refresh_token,
            'created' => 0,
        ]);

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $user->google_token = $newToken['access_token'];
            $user->google_expires_in = $newToken['expires_in'];
            if (isset($newToken['refresh_token'])) {
                $user->google_refresh_token = $newToken['refresh_token'];
            }
            $user->save();
        }

        return new Calendar($client);
    }
}
