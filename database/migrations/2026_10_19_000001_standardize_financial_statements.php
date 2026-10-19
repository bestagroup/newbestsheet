<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Independent guards allow resuming after MySQL's implicit DDL commits.
        foreach (['company_id', 'period_start', 'period_end', 'income_basis'] as $column) {
            if (! Schema::hasColumn('financial_statements', $column)) {
                Schema::table('financial_statements', function (Blueprint $table) use ($column): void {
                    if ($column === 'company_id') {
                        $table->unsignedBigInteger($column)->nullable();
                    } elseif ($column === 'income_basis') {
                        $table->string($column, 20)->default('unconfirmed');
                    } else {
                        // Explicit normalized Solar Hijri dates, NOT SQL/Gregorian DATEs.
                        $table->string($column, 10)->nullable();
                    }
                });
            }
        }
        if (! Schema::hasIndex('financial_statements', 'fs_company_period_index')) {
            Schema::table('financial_statements', fn (Blueprint $table) => $table->index(['company_id', 'year', 'month'], 'fs_company_period_index'));
        }
        // Copy only an exact registered relationship. Never match company names or infer dates.
        DB::table('financial_statements')->whereNull('company_id')->orderBy('id')->chunkById(500, function ($rows): void {
            $owners = DB::table('projects')->join('companies', 'companies.id', '=', 'projects.company_id')
                ->whereIn('projects.id', $rows->pluck('project_id'))->select('companies.id as owner_id', 'projects.id as source_id')->get()->pluck('owner_id', 'source_id');
            foreach ($rows as $row) {
                if (isset($owners[$row->project_id])) {
                    DB::table('financial_statements')->where('id', $row->id)->whereNull('company_id')->update(['company_id' => $owners[$row->project_id]]);
                }
            }
        });
        $foreignKeys = Schema::getForeignKeys('financial_statements');
        if (! collect($foreignKeys)->contains(fn ($key) => $key['columns'] === ['company_id'])) {
            Schema::table('financial_statements', fn (Blueprint $table) => $table->foreign('company_id', 'fs_company_fk')->references('id')->on('companies')->restrictOnDelete());
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Restore a verified backup; financial ownership and period metadata must not be dropped.');
    }
};
