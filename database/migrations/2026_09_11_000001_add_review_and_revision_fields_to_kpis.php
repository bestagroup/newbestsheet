<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpis', function (Blueprint $table): void {
            $table->foreignId('previous_kpi_id')
                ->nullable()
                ->after('project_id')
                ->constrained('kpis')
                ->nullOnDelete();
            $table->foreignId('root_kpi_id')
                ->nullable()
                ->after('previous_kpi_id')
                ->constrained('kpis')
                ->nullOnDelete();
            $table->unsignedSmallInteger('revision_number')->default(1)->after('root_kpi_id');
            $table->boolean('is_current')->default(true)->after('revision_number');
            $table->string('review_status', 20)->default('pending')->after('is_current');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('review_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_comment')->nullable()->after('reviewed_at');

            $table->index(['project_id', 'is_current', 'review_status'], 'kpis_project_current_review_idx');
            $table->index(['root_kpi_id', 'revision_number'], 'kpis_root_revision_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table): void {
            $table->dropIndex('kpis_project_current_review_idx');
            $table->dropIndex('kpis_root_revision_idx');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('root_kpi_id');
            $table->dropConstrainedForeignId('previous_kpi_id');
            $table->dropColumn([
                'revision_number',
                'is_current',
                'review_status',
                'reviewed_at',
                'review_comment',
            ]);
        });
    }
};
