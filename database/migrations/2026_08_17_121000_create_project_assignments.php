<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_assignments')) {
            Schema::create('project_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
                $table->foreignId('invest_step_id')->nullable()->constrained('investsteps')->nullOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'is_active'], 'pa_project_active_idx');
                $table->index(['user_id', 'role_id', 'is_active'], 'pa_user_role_active_idx');
                $table->index(['project_id', 'invest_step_id', 'is_active'], 'pa_project_step_active_idx');
            });
        }

        Schema::table('project_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('project_steps', 'project_assignment_id')) {
                $table->foreignId('project_assignment_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('project_assignments')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('project_steps', 'actor_role_id')) {
                $table->foreignId('actor_role_id')
                    ->nullable()
                    ->after('project_assignment_id')
                    ->constrained('roles')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_steps', function (Blueprint $table) {
            if (Schema::hasColumn('project_steps', 'actor_role_id')) {
                $table->dropConstrainedForeignId('actor_role_id');
            }
            if (Schema::hasColumn('project_steps', 'project_assignment_id')) {
                $table->dropConstrainedForeignId('project_assignment_id');
            }
        });

        Schema::dropIfExists('project_assignments');
    }
};
