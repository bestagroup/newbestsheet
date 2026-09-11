<?php

namespace App\Http\ViewComposers;

use App\Enums\InvestmentRole;
use App\Models\MenuPanel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MenuComposer
{
    public function compose(View $view)
    {
        if (! Schema::hasTable('menu_panels')) {
            $view->with('menupanels', collect());

            return;
        }

        $user = auth()->user();
        $departmentMenus = [
            InvestmentRole::ExecutiveBoard->value => 'directors',
            InvestmentRole::FinanceManagement->value => 'finance-department',
            InvestmentRole::InvestmentManagement->value => 'investment-department',
            InvestmentRole::PortfolioAffairsManagement->value => 'portfolio-affairs',
            InvestmentRole::AdministrativeSupportManagement->value => 'administrative-support',
        ];
        $preferredDepartmentMenus = $user
            ? collect($departmentMenus)->filter(fn (string $menu, string $role): bool => $user->hasRole($role))->values()
            : collect();
        $isSuperAdmin = $user?->hasRole(InvestmentRole::SuperAdmin->value) ?? false;

        $menupanels = MenuPanel::with(['submenus' => function ($query) {
            $query->where('status', 4);
        }])
            ->where('status', 4)
            ->orderBy('priority')
            ->get()
            ->values();

        $representedPermissionSlugs = $menupanels
            ->filter(fn (MenuPanel $menu): bool => $preferredDepartmentMenus->contains($menu->slug))
            ->flatMap(fn (MenuPanel $menu) => $menu->submenus
                ->filter(fn ($sub): bool => Gate::allows('can-access', [$sub->slug, 'view']))
                ->pluck('slug'))
            ->unique()
            ->values();

        $menupanels = $menupanels->map(function (MenuPanel $menu) use (
            $isSuperAdmin,
            $preferredDepartmentMenus,
            &$representedPermissionSlugs
        ) {
            $accessibleSubs = $menu->submenus->filter(
                fn ($sub): bool => Gate::allows('can-access', [$sub->slug, 'view'])
            );

            if (! $isSuperAdmin && ! $preferredDepartmentMenus->contains($menu->slug)) {
                $accessibleSubs = $accessibleSubs->reject(
                    fn ($sub): bool => $representedPermissionSlugs->contains($sub->slug)
                );
                $representedPermissionSlugs = $representedPermissionSlugs
                    ->merge($accessibleSubs->pluck('slug'))
                    ->unique()
                    ->values();
            }

            $isActive = $accessibleSubs->contains(function ($sub) {
                return request()->segment(2) === $sub->slug;
            });

            $menu->accessible_submenus = $accessibleSubs;
            $menu->has_access = $accessibleSubs->isNotEmpty();
            $menu->is_active = $isActive;

            return $menu;
        });

        $view->with('menupanels', $menupanels);
    }
}
