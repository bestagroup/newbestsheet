<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckSubmenuPermission
{
    public function handle(Request $request, Closure $next, string $permissionType, string $submenuSlug): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $action = $this->normalizeAction($permissionType);

        if (! Gate::forUser($user)->allows('can-access', [$submenuSlug, $action])) {
            abort(403, 'شما دسترسی لازم را ندارید.');
        }

        return $next($request);
    }

    private function normalizeAction(string $permissionType): string
    {
        return match ($permissionType) {
            'view', 'can_view' => 'view',
            'insert', 'create', 'can_create', 'can_insert' => 'insert',
            'edit', 'update', 'can_edit' => 'edit',
            'delete', 'destroy', 'can_delete' => 'delete',
            default => abort(403, 'نوع دسترسی معتبر نیست.'),
        };
    }
}
