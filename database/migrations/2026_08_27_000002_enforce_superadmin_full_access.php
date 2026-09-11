<?php

use App\Enums\InvestmentRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('permission_role')) {
            return;
        }

        $superAdminId = DB::table('roles')
            ->where('title', InvestmentRole::SuperAdmin->value)
            ->value('id');

        if (! $superAdminId) {
            return;
        }

        $now = now();
        foreach (DB::table('permissions')->pluck('id') as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $superAdminId, 'permission_id' => $permissionId],
                [
                    'can_view' => true,
                    'can_insert' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        // Superadmin access is an invariant and must not be revoked on rollback.
    }
};
