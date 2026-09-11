<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'change_password')) {
                $table->boolean('change_password')->nullable();
            }
            if (! Schema::hasColumn('users', 'google_token')) {
                $table->text('google_token')->nullable();
            }
            if (! Schema::hasColumn('users', 'google_refresh_token')) {
                $table->text('google_refresh_token')->nullable();
            }
            if (! Schema::hasColumn('users', 'google_expires_in')) {
                $table->unsignedInteger('google_expires_in')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive for legacy databases.
    }
};
