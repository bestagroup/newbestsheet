<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Intentionally empty.
        // This project uses its own roles / permissions / permission_role / role_user schema.
        // The original file was a fully commented Spatie migration and returned no Migration instance.
    }

    public function down(): void
    {
        // Intentionally empty: this migration owns no tables.
    }
};
