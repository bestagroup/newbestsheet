<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->reconcileUsers();
        $this->reconcileMenuPanels();
        $this->reconcileKpis();
        $this->reconcileCommitments();
    }

    public function down(): void
    {
        // Compatibility data is intentionally preserved on rollback.
    }

    private function reconcileUsers(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->text('google_id')->nullable();
            });
        }
    }

    private function reconcileMenuPanels(): void
    {
        if (Schema::hasTable('menu_panels') && ! Schema::hasColumn('menu_panels', 'is_public')) {
            Schema::table('menu_panels', function (Blueprint $table): void {
                $table->boolean('is_public')->default(false);
            });
        }
    }

    private function reconcileKpis(): void
    {
        if (! Schema::hasTable('kpis')) {
            return;
        }

        foreach ([
            'unit' => static fn (Blueprint $table) => $table->string('unit')->nullable(),
            'deadline' => static fn (Blueprint $table) => $table->string('deadline')->nullable(),
            'period_time' => static fn (Blueprint $table) => $table->string('period_time')->nullable(),
        ] as $column => $definition) {
            if (! Schema::hasColumn('kpis', $column)) {
                Schema::table('kpis', $definition);
            }
        }

        // Keep the normalized field populated for old rows without destroying the original period label.
        if (Schema::hasColumn('kpis', 'time_step') && Schema::hasColumn('kpis', 'period_time')) {
            DB::table('kpis')
                ->whereNull('time_step')
                ->whereNotNull('period_time')
                ->update(['time_step' => DB::raw('period_time')]);
        }
    }

    private function reconcileCommitments(): void
    {
        if (! Schema::hasTable('commitments') || ! Schema::hasColumn('commitments', 'title')) {
            return;
        }

        // Fresh SQLite databases already create this column as TEXT in the historical migration.
        // Existing MySQL/MariaDB databases need the legacy INT definition corrected in-place.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `commitments` MODIFY COLUMN `title` TEXT NOT NULL COMMENT 'عنوان'");
        }
    }
};
