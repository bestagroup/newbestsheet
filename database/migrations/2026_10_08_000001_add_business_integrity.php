<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_audits')) {
            Schema::create('business_audits', function (Blueprint $table): void {
                $table->id();
                $table->string('subject_type', 120);
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action', 40);
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->timestamp('created_at');
            });
        }

        if (! Schema::hasIndex(
            'business_audits',
            ['subject_type', 'subject_id']
        )) {
            Schema::table('business_audits', function (Blueprint $table): void {
                $table->index(
                    ['subject_type', 'subject_id'],
                    'business_audits_subject_type_subject_id_index'
                );
            });
        }

        if (! Schema::hasColumn('finances', 'idempotency_key')) {
            Schema::table('finances', function (Blueprint $table): void {
                $table->uuid('idempotency_key')->nullable();
            });
        }

        if (! Schema::hasIndex('finances', ['idempotency_key'], 'unique')) {
            Schema::table('finances', function (Blueprint $table): void {
                $table->unique(
                    'idempotency_key',
                    'finances_idempotency_key_unique'
                );
            });
        }

        if (! Schema::hasColumn('financial_statements', 'period_type')) {
            Schema::table('financial_statements', function (Blueprint $table): void {
                $table->string('period_type', 20)->default('legacy');
            });
        }

        // Establish the new constraint before removing the previous one.
        if (! Schema::hasIndex(
            'financial_statements',
            ['project_id', 'year', 'month', 'period_type'],
            'unique'
        )) {
            Schema::table('financial_statements', function (Blueprint $table): void {
                $table->unique(
                    ['project_id', 'year', 'month', 'period_type'],
                    'fs_project_period_type_unique'
                );
            });
        }

        if (Schema::hasIndex(
            'financial_statements',
            'fs_project_period_unique',
            'unique'
        )) {
            Schema::table('financial_statements', function (Blueprint $table): void {
                $table->dropUnique('fs_project_period_unique');
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Restore a verified backup instead of rolling back business history.'
        );
    }
};
