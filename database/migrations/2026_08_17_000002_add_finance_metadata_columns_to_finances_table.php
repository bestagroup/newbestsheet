<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('finances', 'docserial')) {
            Schema::table('finances', function (Blueprint $table) {
                $table->string('docserial')->nullable();
            });
        }

        if (! Schema::hasColumn('finances', 'finance_type')) {
            Schema::table('finances', function (Blueprint $table) {
                $table->string('finance_type')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: legacy deployments may already have these columns.
    }
};
