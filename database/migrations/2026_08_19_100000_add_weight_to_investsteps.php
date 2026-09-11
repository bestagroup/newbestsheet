<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('investsteps') || Schema::hasColumn('investsteps', 'weight')) {
            return;
        }

        Schema::table('investsteps', function (Blueprint $table): void {
            $table->decimal('weight', 8, 3)->default(5)->after('status')->comment('وزن نسبی مرحله در محاسبه پیشرفت');
        });
    }

    public function down(): void
    {
        // Non-destructive compatibility migration. Fresh schema owns this column.
    }
};
