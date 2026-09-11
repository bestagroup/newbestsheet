<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        $representative = DB::table('roles')->where('title', 'investee_representative')->first();
        $legacyRepresentative = DB::table('roles')->where('title', 'CapitalCapable')->first();

        if (! $representative && $legacyRepresentative) {
            DB::table('roles')->where('id', $legacyRepresentative->id)->update([
                'title' => 'investee_representative',
                'title_fa' => 'نماینده سرمایه‌پذیر',
                'status' => 4,
                'updated_at' => $now,
            ]);
        }

        foreach ([
            'superadmin' => 'ادمین',
            'manager' => 'مدیر',
            'senior_investment_expert' => 'کارشناس ارشد سرمایه‌گذاری',
            'expert' => 'کارشناس',
            'observer' => 'ناظر',
            'investee_representative' => 'نماینده سرمایه‌پذیر',
            'evaluator' => 'ارزیاب',
        ] as $slug => $label) {
            $existing = DB::table('roles')->where('title', $slug)->first();

            if ($existing) {
                DB::table('roles')->where('id', $existing->id)->update([
                    'title_fa' => $label,
                    'status' => 4,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('roles')->insert([
                    'title_fa' => $label,
                    'title' => $slug,
                    'status' => 4,
                    'user_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'flow')->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'title' => 'flow',
                'label' => 'مدیریت فرایند سرمایه‌گذاری',
                'slug' => 'flow',
                'menu_panel_id' => null,
                'submenu_panel_id' => null,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (['manager', 'senior_investment_expert', 'expert', 'observer', 'evaluator'] as $roleSlug) {
            $roleId = DB::table('roles')->where('title', $roleSlug)->value('id');
            if (! $roleId) {
                continue;
            }

            $pivot = DB::table('permission_role')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->first();
            $values = [
                'can_view' => true,
                'can_insert' => $roleSlug === 'manager' || $roleSlug === 'senior_investment_expert',
                'can_edit' => true,
                'can_delete' => false,
                'updated_at' => $now,
            ];

            if ($pivot) {
                DB::table('permission_role')->where('id', $pivot->id)->update($values);
            } else {
                DB::table('permission_role')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    ...$values,
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Core roles and permission history are operational reference data and
        // are intentionally retained on rollback.
    }
};
