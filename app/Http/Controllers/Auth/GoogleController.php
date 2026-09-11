<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/calendar'])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        // This is a cookie/session based web login. Keep Socialite's OAuth
        // state verification enabled to protect the callback against CSRF.
        $googleUser = Socialite::driver('google')->user();
        $email = trim((string) $googleUser->getEmail());

        if ($email === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'حساب گوگل انتخاب‌شده ایمیل قابل استفاده‌ای ارائه نکرد.',
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        // Google OAuth is a sign-in / calendar-connect mechanism, not an
        // alternative registration workflow. New investee accounts must pass
        // through FullRegister so User + Company + Project are created atomically.
        if (! $user) {
            return redirect()->route('register')->withErrors([
                'email' => 'ابتدا ثبت‌نام و ایجاد پرونده سرمایه‌گذاری را تکمیل کنید، سپس می‌توانید با حساب گوگل وارد شوید.',
            ])->withInput(['email' => $email]);
        }

        if (! is_null($user->status) && (int) $user->status !== 4) {
            return redirect()->route('login')->withErrors([
                'email' => 'حساب کاربری شما غیرفعال است.',
            ]);
        }

        $tokenData = [
            'google_token' => $googleUser->token,
        ];

        // Google may omit a refresh token on subsequent consent flows. Never
        // overwrite a previously valid refresh token with null.
        if (filled($googleUser->refreshToken)) {
            $tokenData['google_refresh_token'] = $googleUser->refreshToken;
        }

        $user->forceFill($tokenData)->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
