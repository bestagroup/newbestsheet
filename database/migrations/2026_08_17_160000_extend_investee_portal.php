<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addedProjectId = false;

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) use (&$addedProjectId): void {
                if (! Schema::hasColumn('sales', 'project_id')) {
                    $table->unsignedBigInteger('project_id')->nullable()->after('id');
                    $table->index('project_id', 'sales_project_idx');
                    $addedProjectId = true;
                }

                if (! Schema::hasColumn('sales', 'production_count')) {
                    $table->unsignedInteger('production_count')->nullable()->after('count_sales');
                }
            });

            if ($addedProjectId && DB::getDriverName() === 'mysql') {
                Schema::table('sales', function (Blueprint $table): void {
                    $table->foreign('project_id', 'sales_project_fk')
                        ->references('id')->on('projects')->cascadeOnDelete();
                });
            }
        }

        // Reconcile only applicant-owned projects that do not have a company yet.
        if (! Schema::hasTable('projects') || ! Schema::hasTable('companies') || ! Schema::hasTable('users')) {
            return;
        }

        DB::table('projects')
            ->join('users', 'users.id', '=', 'projects.user_id')
            ->where('users.level', 'applicant')
            ->whereNull('projects.company_id')
            ->select('projects.*')
            ->orderBy('projects.id')
            ->chunkById(100, function ($projects): void {
                foreach ($projects as $project) {
                    $companyId = DB::table('companies')
                        ->where('user_id', $project->user_id)
                        ->value('id');

                    if (! $companyId) {
                        $registrationDate = is_string($project->registration_date ?? null)
                            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $project->registration_date) === 1
                                ? $project->registration_date
                                : null;

                        $companyId = DB::table('companies')->insertGetId([
                            'user_id' => $project->user_id,
                            'title' => $project->title,
                            'company_name' => $project->company_name ?: $project->title,
                            'registration_number' => $project->registration_number,
                            'registration_date' => $registrationDate,
                            'national_id' => $project->national_id,
                            'economic_code' => $project->economic_code,
                            'legal_type' => $project->legal_type,
                            'phone' => $project->tel,
                            'email' => $project->email,
                            'website' => $project->website,
                            'province' => $project->state ? (string) $project->state : null,
                            'city' => $project->city ? (string) $project->city : null,
                            'address' => $project->address,
                            'postal_code' => $project->postal_code,
                            'ceo_name' => $project->CEO,
                            'ceo_national_code' => $project->ceo_national_code,
                            'is_verified' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('projects')->where('id', $project->id)->update([
                        'company_id' => $companyId,
                        'updated_at' => now(),
                    ]);
                }
            }, 'projects.id', 'id');
    }

    public function down(): void
    {
        // Intentionally non-destructive: the historical fresh migration owns the new columns,
        // and reconciled Project -> Company links must not be discarded on rollback.
    }
};
