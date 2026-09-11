<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'user_id') && DB::getDriverName() === 'mysql') {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            DB::statement('ALTER TABLE roles MODIFY user_id BIGINT UNSIGNED NULL');

            Schema::table('roles', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        $now = now();

        $representative = DB::table('roles')
            ->whereIn('title', ['investee_representative', 'CapitalCapable'])
            ->orderByRaw("CASE WHEN title = 'investee_representative' THEN 0 ELSE 1 END")
            ->first();

        if ($representative) {
            DB::table('roles')->where('id', $representative->id)->update([
                'title_fa' => 'نماینده سرمایه‌پذیر',
                'title' => 'investee_representative',
                'status' => 4,
                'updated_at' => $now,
            ]);
        }

        $roles = [
            ['title_fa' => 'ادمین', 'title' => 'superadmin'],
            ['title_fa' => 'مدیر', 'title' => 'manager'],
            ['title_fa' => 'کارشناس', 'title' => 'expert'],
            ['title_fa' => 'ناظر', 'title' => 'observer'],
            ['title_fa' => 'نماینده سرمایه‌پذیر', 'title' => 'investee_representative'],
            ['title_fa' => 'ارزیاب', 'title' => 'evaluator'],
        ];

        foreach ($roles as $role) {
            $existing = DB::table('roles')->where('title', $role['title'])->first();

            if ($existing) {
                DB::table('roles')->where('id', $existing->id)->update([
                    'title_fa' => $role['title_fa'],
                    'status' => 4,
                    'updated_at' => $now,
                ]);

                continue;
            }

            DB::table('roles')->insert([
                'title_fa' => $role['title_fa'],
                'title' => $role['title'],
                'status' => 4,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $flowPermission = DB::table('permissions')->where('slug', 'flow')->first();
        if (! $flowPermission) {
            $permissionId = DB::table('permissions')->insertGetId([
                'title' => 'flow',
                'label' => 'مدیریت فرایند سرمایه گذاری',
                'slug' => 'flow',
                'menu_panel_id' => null,
                'submenu_panel_id' => null,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $permissionId = (int) $flowPermission->id;
        }

        foreach (['manager', 'expert', 'observer', 'evaluator'] as $roleSlug) {
            $roleId = DB::table('roles')->where('title', $roleSlug)->value('id');
            if (! $roleId) {
                continue;
            }

            $pivot = DB::table('permission_role')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->first();

            if ($pivot) {
                DB::table('permission_role')->where('id', $pivot->id)->update([
                    'can_view' => true,
                    'can_edit' => true,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('permission_role')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'can_view' => true,
                    'can_insert' => false,
                    'can_edit' => true,
                    'can_delete' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Reference roles are intentionally preserved. Removing them could orphan role_user history.
    }
};
