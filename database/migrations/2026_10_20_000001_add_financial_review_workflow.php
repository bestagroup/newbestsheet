<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing rows are explicitly unreviewed, never retroactively approved.
        foreach (['workflow_status', 'revision', 'lock_version', 'preparer_ids', 'submitted_by', 'submitted_at', 'review_note'] as $column) {
            if (! Schema::hasColumn('financial_statements', $column)) {
                Schema::table('financial_statements', function (Blueprint $table) use ($column): void {
                    match ($column) {
                        'workflow_status' => $table->string($column, 20)->default('legacy'),
                        'revision', 'lock_version' => $table->unsignedInteger($column)->default(1),
                        'preparer_ids' => $table->json($column)->nullable(),
                        'submitted_by' => $table->unsignedBigInteger($column)->nullable(),
                        'submitted_at' => $table->timestamp($column)->nullable(),
                        'review_note' => $table->text($column)->nullable(),
                    };
                });
            }
        }
        Schema::table('financial_statements', fn (Blueprint $table) => $table->string('workflow_status', 20)->default('draft')->change());
        DB::table('financial_statements')->whereNull('preparer_ids')->orderBy('id')->chunkById(500, function ($rows): void {
            $actors = DB::table('business_audits')->where('subject_type', 'App\\Models\\Financial_statement')
                ->whereIn('subject_id', $rows->pluck('id'))->whereIn('action', ['created', 'updated'])->whereNotNull('actor_id')
                ->get(['subject_id', 'actor_id'])->groupBy('subject_id');
            foreach ($rows as $row) {
                DB::table('financial_statements')->where('id', $row->id)->whereNull('preparer_ids')
                    ->update(['preparer_ids' => json_encode($actors->get($row->id, collect())->pluck('actor_id')->map(fn ($id) => (int) $id)->unique()->values()->all(), JSON_THROW_ON_ERROR)]);
            }
        });
        if (! Schema::hasTable('financial_statement_revisions')) {
            Schema::create('financial_statement_revisions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('financial_statement_id')->constrained('financial_statements')->restrictOnDelete();
                $table->unsignedInteger('revision');
                $table->json('payload');
                // Actor IDs are retained even if the account is subsequently removed.
                $table->unsignedBigInteger('approved_by');
                $table->timestamp('approved_at');
                $table->text('review_note')->nullable();
                $table->unique(['financial_statement_id', 'revision'], 'fs_revision_unique');
            });
        }
        DB::table('permissions')->insertOrIgnore([
            'title' => 'financialstatement-review', 'slug' => 'financialstatement-review',
            'label' => 'بررسی و تأیید صورت‌های مالی', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        throw new RuntimeException('Approved financial history must be preserved; restore a verified backup.');
    }
};
