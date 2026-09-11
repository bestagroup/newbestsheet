<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $guard = null, $permissionType = null, $submenuSlug = null): Response
    {

        $guard = 'panel';
        // 1. بررسی ورود

        if (! Auth::guard()->check()) {
            return redirect()->route('login');
        }

        $user = Auth::guard()->user();

        // کاربر غیرفعال نباید حتی با Session معتبر به پنل دسترسی داشته باشد.
        // مقدار null برای سازگاری با داده‌های Legacy فعلاً مسدود نمی‌شود.
        if (! is_null($user->status) && (int) $user->status !== 4) {
            Auth::guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'حساب کاربری شما غیرفعال است.',
            ]);
        }

        // 2. بررسی اجبار به تغییر رمز
        if (is_null($user->change_password)) {
            // اگر مسیر جاری مربوط به فرم یا ارسال تغییر رمز نیست
            if (
                ! $request->routeIs('password.change.form') &&
                ! $request->routeIs('password.change.submit')
            ) {
                return redirect()->route('password.change.form');
            }
        }

        // 3. اگر پارامترهای دسترسی وارد نشده باشن، مرحله بعدی اجرا بشه
        if (! $permissionType || ! $submenuSlug) {
            return $next($request);
        }

        // 4. بررسی دسترسی از مسیر canonical permission_role / Gate
        $action = match ($permissionType) {
            'view', 'can_view' => 'view',
            'insert', 'create', 'can_create', 'can_insert' => 'insert',
            'edit', 'update', 'can_edit' => 'edit',
            'delete', 'destroy', 'can_delete' => 'delete',
            default => abort(403, 'نوع دسترسی معتبر نیست.'),
        };

        if (! Gate::forUser($user)->allows('can-access', [$submenuSlug, $action])) {
            abort(403, 'شما دسترسی لازم را ندارید.');
        }

        return $next($request);
    }
}
