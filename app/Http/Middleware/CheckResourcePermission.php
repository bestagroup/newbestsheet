<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckResourcePermission
{
    /**
     * Enforce CRUD permissions for resource controllers.
     *
     * The optional third middleware argument overrides the permission used for
     * the resource store action. This is used by the investment flow where
     * POST /flow records a state transition and therefore requires edit rather
     * than insert permission.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $resourceSlug,
        ?string $storePermission = null
    ): Response {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $controllerMethod = $request->route()?->getActionMethod();

        $permissionType = match ($controllerMethod) {
            'index', 'show' => 'view',
            'create' => 'insert',
            'store' => $storePermission ?: 'insert',
            'edit', 'update' => 'edit',
            'destroy' => 'delete',
            default => null,
        };

        if (! $permissionType) {
            abort(403, 'نوع عملیات برای کنترل دسترسی معتبر نیست.');
        }

        if (! Gate::forUser($user)->allows('can-access', [$resourceSlug, $permissionType])) {
            abort(403, 'شما دسترسی لازم را ندارید.');
        }

        return $next($request);
    }
}
