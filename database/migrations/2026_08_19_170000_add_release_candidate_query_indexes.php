<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->index(['is_rejected', 'progress_percentage', 'invest_step'], 'projects_rc_status_progress_step_idx');
        });

        Schema::table('project_steps', function (Blueprint $table): void {
            $table->index(['user_id', 'project_id', 'status'], 'project_steps_rc_user_project_status_idx');
            $table->index(['project_id', 'step_number', 'status'], 'project_steps_rc_project_step_status_idx');
        });

        Schema::table('finances', function (Blueprint $table): void {
            $table->index(['project_id', 'date'], 'finances_rc_project_date_idx');
        });

        Schema::table('user_logs', function (Blueprint $table): void {
            $table->index(['user_id', 'action', 'status', 'created_at'], 'user_logs_rc_user_action_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_logs', fn (Blueprint $table) => $table->dropIndex('user_logs_rc_user_action_status_idx'));
        Schema::table('finances', fn (Blueprint $table) => $table->dropIndex('finances_rc_project_date_idx'));
        Schema::table('project_steps', function (Blueprint $table): void {
            $table->dropIndex('project_steps_rc_user_project_status_idx');
            $table->dropIndex('project_steps_rc_project_step_status_idx');
        });
        Schema::table('projects', fn (Blueprint $table) => $table->dropIndex('projects_rc_status_progress_step_idx'));
    }
};
