<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActiveCode as ActiveCodeModel;
use App\Models\User;
use App\Notifications\ActiveCode as ActiveCodeNotification;
use App\Support\IranianMobileNormalizer;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function otplogin(): View
    {
        return view('auth.otplogin');
    }

    public function gettoken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = IranianMobileNormalizer::normalize($validated['phone']);
        if ($phone === null) {
            return back()
                ->withErrors(['phone' => 'شماره تلفن همراه معتبر نیست.'])
                ->withInput();
        }

        $rateLimitKey = 'otp-request:'.hash('sha256', $phone.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()
                ->withErrors(['phone' => "تعداد درخواست‌ها بیش از حد مجاز است. {$seconds} ثانیه دیگر تلاش کنید."])
                ->withInput();
        }

        RateLimiter::hit($rateLimitKey, 60);

        $nationalNumber = substr($phone, 1);
        $user = User::query()
            ->whereIn('phone', [
                $phone,
                '+98'.$nationalNumber,
                '0098'.$nationalNumber,
                '98'.$nationalNumber,
            ])
            ->first();

        if (! $user || (! is_null($user->status) && (int) $user->status !== 4)) {
            return back()
                ->withErrors(['phone' => 'امکان ارسال کد ورود برای این شماره وجود ندارد.'])
                ->withInput();
        }

        $code = ActiveCodeModel::query()->generateCode($user);
        $user->notify(new ActiveCodeNotification($code, $phone));

        $request->session()->put('otp_user_id', $user->getKey());

        return redirect()
            ->route('sendtoken')
            ->with('success', 'کد ورود ارسال شد.');
    }

    public function sendtoken(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp_user_id')) {
            return redirect()->route('otplogin');
        }

        return view('auth.token');
    }

    public function checktoken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $userId = $request->session()->get('otp_user_id');

        if (! $userId) {
            return redirect()
                ->route('otplogin')
                ->withErrors(['phone' => 'درخواست ورود منقضی شده است.']);
        }

        $verifyRateLimitKey = 'otp-verify:'.$userId.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($verifyRateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($verifyRateLimitKey);

            return back()->withErrors([
                'code' => "تعداد تلاش‌ها بیش از حد مجاز است. {$seconds} ثانیه دیگر تلاش کنید.",
            ]);
        }

        RateLimiter::hit($verifyRateLimitKey, 180);

        $user = User::query()->find($userId);

        if (! $user
            || (! is_null($user->status) && (int) $user->status !== 4)
            || ! ActiveCodeModel::query()->verifyCode($validated['code'], $user)) {
            $request->session()->forget('otp_user_id');

            return back()->withErrors(['code' => 'کد وارد شده معتبر نیست یا منقضی شده است.']);
        }

        $user->activeCodes()->delete();
        RateLimiter::clear($verifyRateLimitKey);

        Auth::login($user);
        $request->session()->forget('otp_user_id');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
