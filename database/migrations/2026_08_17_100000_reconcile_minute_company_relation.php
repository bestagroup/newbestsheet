<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('minutes') || ! Schema::hasTable('projects') || ! Schema::hasTable('companies')) {
            return;
        }

        if (! Schema::hasColumn('minutes', 'company_id') || ! Schema::hasColumn('minutes', 'project_id')) {
            return;
        }

        // Historical application code stored project_id in minutes.company_id.
        // Rebuild the relation from the canonical projects.company_id value.
        DB::table('minutes')
            ->whereNotNull('project_id')
            ->orderBy('id')
            ->chunkById(250, function ($minutes): void {
                $projectCompanies = DB::table('projects')
                    ->whereIn('id', $minutes->pluck('project_id')->filter()->unique()->values())
                    ->pluck('company_id', 'id');

                foreach ($minutes as $minute) {
                    $companyId = $projectCompanies->get($minute->project_id);

                    DB::table('minutes')
                        ->where('id', $minute->id)
                        ->update(['company_id' => $companyId]);
                }
            });

        // Remove orphan legacy values before enforcing referential integrity.
        DB::table('minutes')
            ->whereNotNull('company_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('companies')
                    ->whereColumn('companies.id', 'minutes.company_id');
            })
            ->update(['company_id' => null]);

        if ($this->minuteCompanyForeignKeyExists()) {
            return;
        }

        // SQLite cannot reliably ALTER an existing table to add a foreign key.
        // Fresh SQLite databases already receive the FK from the create migration above.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('minutes', function (Blueprint $table): void {
            $table->foreign('company_id', 'minutes_company_id_foreign')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive. This migration repairs historical relation data
        // and referential integrity; rolling it back must not restore corrupted company IDs.
    }

    private function minuteCompanyForeignKeyExists(): bool
    {
        return match (DB::getDriverName()) {
            'mysql' => (bool) DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'minutes')
                ->where('COLUMN_NAME', 'company_id')
                ->where('REFERENCED_TABLE_NAME', 'companies')
                ->exists(),
            'sqlite' => collect(DB::select("PRAGMA foreign_key_list('minutes')"))
                ->contains(static fn (object $foreignKey): bool => ($foreignKey->from ?? null) === 'company_id'
                    && ($foreignKey->table ?? null) === 'companies'
                ),
            default => false,
        };
    }
};
