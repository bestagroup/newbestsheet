<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_members')) {
            return;
        }

        if (Schema::hasColumn('company_members', 'project_id')) {
            return;
        }

        Schema::table('company_members', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('company_id')
                ->constrained('projects')
                ->cascadeOnDelete();
            $table->index(['project_id', 'is_active'], 'cm_project_active_idx');
        });
    }

    public function down(): void
    {
        // Compatibility bridge: intentionally non-destructive on rollback because
        // fresh installations own project_id in the historical create migration.
    }
};
