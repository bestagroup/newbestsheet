<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'fs_project_period_unique';

    public function up(): void
    {
        if (! Schema::hasTable('financial_statements')) {
            return;
        }

        Schema::table('financial_statements', function (Blueprint $table): void {
            $table->unique(['project_id', 'year', 'month'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('financial_statements')) {
            return;
        }

        Schema::table('financial_statements', function (Blueprint $table): void {
            $table->dropUnique(self::INDEX_NAME);
        });
    }
};
