<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class EnterpriseModuleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        foreach (['meetings' => ['امور مجامع و مصوبات', 'portfolio-affairs', 'PortfolioMeetingController'], 'letters' => ['دبیرخانه مکاتبات خارجی', 'administrative-support', 'ExternalLetterController']] as $slug => [$label, $menu, $controller]) {
            $menuId = DB::table('menu_panels')->where('slug', $menu)->value('id');
            if (! $menuId) {
                continue;
            }
            DB::table('submenu_panels')->updateOrInsert(['menu_id' => $menuId, 'slug' => $slug], ['priority' => 20, 'title' => $slug, 'label' => $label, 'class' => 'panel', 'controller' => $controller, 'status' => 4, 'user_id' => null, 'created_at' => $now, 'updated_at' => $now]);
            $subId = DB::table('submenu_panels')->where('menu_id', $menuId)->where('slug', $slug)->value('id');
            DB::table('permissions')->updateOrInsert(['slug' => $slug], ['title' => $slug, 'label' => $label, 'menu_panel_id' => $menuId, 'submenu_panel_id' => $subId, 'user_id' => null, 'created_at' => $now, 'updated_at' => $now]);
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
            $roleSlugs = $slug === 'meetings' ? ['superadmin', 'manager', 'portfolio_affairs_management'] : ['superadmin', 'manager', 'administrative_support_management'];
            foreach (DB::table('roles')->whereIn('title', $roleSlugs)->pluck('id') as $roleId) {
                // Do not overwrite an administrator's existing permission decisions.
                DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId, 'can_view' => true, 'can_insert' => true, 'can_edit' => true, 'can_delete' => false, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }
}
