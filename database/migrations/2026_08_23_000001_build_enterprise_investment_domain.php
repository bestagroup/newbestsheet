<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendFixedStepDefinitions();
        $this->createProjectStageInstances();
        $this->linkWorkflowHistory();
        $this->hardenDocumentMetadata();
        $this->extendAuditMetadata();
        $this->createContractAndPerformanceDomain();
        $this->createConfigurableFormsAndReports();
        $this->seedSeniorInvestmentExpertRole();
        $this->backfillProjectStageInstances();
    }

    private function extendFixedStepDefinitions(): void
    {
        Schema::table('investsteps', function (Blueprint $table): void {
            $table->string('code', 80)->nullable()->after('id');
            $table->unsignedSmallInteger('sequence')->nullable()->after('code');
            $table->boolean('is_system_locked')->default(true)->after('status');
            $table->boolean('assignment_required')->default(true)->after('is_system_locked');
            $table->unsignedInteger('sla_hours')->nullable()->after('assignment_required');
            $table->index(['status', 'sequence'], 'investsteps_status_sequence_idx');
        });

        DB::table('investsteps')->orderBy('id')->get(['id'])->each(function (object $step): void {
            DB::table('investsteps')->where('id', $step->id)->update([
                'code' => sprintf('STAGE_%02d', (int) $step->id),
                'sequence' => (int) $step->id,
            ]);
        });

        Schema::table('investsteps', function (Blueprint $table): void {
            $table->unique('code', 'investsteps_code_uq');
            $table->unique('sequence', 'investsteps_sequence_uq');
        });
    }

    private function createProjectStageInstances(): void
    {
        Schema::create('project_stage_instances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('invest_step_id')->constrained('investsteps')->restrictOnDelete();
            $table->string('stage_code', 80);
            $table->unsignedSmallInteger('sequence');
            $table->string('title_snapshot');
            $table->decimal('weight_snapshot', 8, 3)->default(0);
            $table->string('status', 40)->default('locked');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'invest_step_id'], 'psi_project_step_uq');
            $table->unique(['project_id', 'sequence'], 'psi_project_sequence_uq');
            $table->index(['status', 'due_at'], 'psi_status_due_idx');
            $table->index(['project_id', 'status'], 'psi_project_status_idx');
        });
    }

    private function linkWorkflowHistory(): void
    {
        Schema::table('project_assignments', function (Blueprint $table): void {
            $table->foreignId('project_stage_instance_id')
                ->nullable()
                ->after('invest_step_id')
                ->constrained('project_stage_instances')
                ->cascadeOnDelete();
            $table->string('assignment_type', 30)->default('primary')->after('role_id');
            $table->timestamp('accepted_at')->nullable()->after('assigned_at');
            $table->text('ended_reason')->nullable()->after('ended_at');
            $table->index(
                ['project_stage_instance_id', 'is_active', 'assignment_type'],
                'pa_stage_active_type_idx'
            );
        });

        Schema::table('project_steps', function (Blueprint $table): void {
            $table->foreignId('project_stage_instance_id')
                ->nullable()
                ->after('project_id')
                ->constrained('project_stage_instances')
                ->cascadeOnDelete();
            $table->timestamp('decided_at')->nullable()->after('status');
            $table->json('decision_metadata')->nullable()->after('description');
            $table->index(['project_stage_instance_id', 'status'], 'ps_stage_status_idx');
        });

        Schema::create('project_stage_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_stage_instance_id')->constrained('project_stage_instances')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_assignment_id')->nullable()->constrained('project_assignments')->nullOnDelete();
            $table->string('type', 30)->default('review_note');
            $table->string('visibility', 30)->default('internal');
            $table->text('body');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_stage_instance_id', 'visibility', 'created_at'], 'psc_stage_visibility_idx');
        });
    }

    private function hardenDocumentMetadata(): void
    {
        Schema::table('media_files', function (Blueprint $table): void {
            $table->foreignId('project_stage_instance_id')
                ->nullable()
                ->after('project_id')
                ->constrained('project_stage_instances')
                ->nullOnDelete();
            $table->foreignId('document_requirement_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('invest_step_document_requirements')
                ->nullOnDelete();
            $table->string('disk', 60)->default('public')->after('file_path');
            $table->char('sha256', 64)->nullable()->after('size');
            $table->string('scan_status', 30)->default('legacy')->after('sha256');
            $table->timestamp('scanned_at')->nullable()->after('scan_status');
            $table->unsignedInteger('version')->default(1)->after('scanned_at');
            $table->foreignId('supersedes_id')->nullable()->after('version')->constrained('media_files')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable()->after('supersedes_id');
            $table->softDeletes();

            $table->index(['project_stage_instance_id', 'document_requirement_id'], 'media_stage_requirement_idx');
            $table->index(['project_id', 'scan_status', 'status'], 'media_project_scan_status_idx');
            $table->index('sha256', 'media_sha256_idx');
        });

        DB::table('media_files')->whereNull('uploaded_at')->update(['uploaded_at' => DB::raw('created_at')]);

        Schema::table('message_attachments', function (Blueprint $table): void {
            $table->string('disk', 60)->default('public')->after('path');
            $table->char('sha256', 64)->nullable()->after('size');
            $table->string('scan_status', 30)->default('legacy')->after('sha256');
            $table->timestamp('scanned_at')->nullable()->after('scan_status');
            $table->index('sha256', 'message_attachments_sha256_idx');
        });

        Schema::table('minutes', function (Blueprint $table): void {
            $table->foreignId('media_file_id')->nullable()->after('file_path')->constrained('media_files')->nullOnDelete();
        });
    }

    private function extendAuditMetadata(): void
    {
        Schema::table('user_logs', function (Blueprint $table): void {
            $table->uuid('request_id')->nullable()->after('user_agent');
            $table->string('subject_type')->nullable()->after('request_id');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->json('old_values')->nullable()->after('description');
            $table->json('new_values')->nullable()->after('old_values');
            $table->json('metadata')->nullable()->after('new_values');
            $table->index(['subject_type', 'subject_id', 'created_at'], 'user_logs_subject_created_idx');
            $table->index('request_id', 'user_logs_request_id_idx');
        });
    }

    private function createContractAndPerformanceDomain(): void
    {
        Schema::create('project_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('contract_number')->nullable();
            $table->string('title');
            $table->string('status', 30)->default('draft');
            $table->date('signed_at')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->decimal('amount', 20, 0)->nullable();
            $table->decimal('equity_percentage', 8, 4)->nullable();
            $table->string('currency', 10)->default('IRR');
            $table->foreignId('contract_media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('terms')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'contract_number'], 'contracts_project_number_uq');
            $table->index(['project_id', 'status', 'starts_at'], 'contracts_project_status_start_idx');
        });

        Schema::table('kpis', function (Blueprint $table): void {
            $table->foreignId('project_contract_id')->nullable()->after('project_id')->constrained('project_contracts')->nullOnDelete();
            $table->string('code', 80)->nullable()->after('project_contract_id');
            $table->text('description')->nullable()->after('title');
            $table->string('direction', 20)->default('increase')->after('type');
            $table->decimal('baseline_value', 24, 6)->nullable()->after('type_value');
            $table->decimal('target_value', 24, 6)->nullable()->after('baseline_value');
            $table->decimal('weight', 8, 3)->default(1)->after('target_value');
            $table->decimal('tolerance', 12, 6)->nullable()->after('weight');
            $table->string('measurement_frequency', 30)->default('quarterly')->after('period_time');
            $table->date('starts_at')->nullable()->after('deadline_at');
            $table->date('ends_at')->nullable()->after('starts_at');
            $table->foreignId('owner_user_id')->nullable()->after('ends_at')->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('active')->after('owner_user_id');
            $table->softDeletes();
            $table->index(['project_id', 'status', 'measurement_frequency'], 'kpis_project_status_frequency_idx');
            $table->index(['project_contract_id', 'status'], 'kpis_contract_status_idx');
        });

        DB::table('kpis')->orderBy('id')->get(['id', 'project_id', 'kpi_number', 'value'])->each(function (object $kpi): void {
            DB::table('kpis')->where('id', $kpi->id)->update([
                'code' => sprintf('P%d-KPI-%d', (int) ($kpi->project_id ?? 0), (int) $kpi->kpi_number),
                'target_value' => is_numeric($kpi->value) ? $kpi->value : null,
            ]);
        });

        Schema::table('kpis', function (Blueprint $table): void {
            $table->unique(['project_id', 'code'], 'kpis_project_code_uq');
        });

        Schema::create('quarterly_performance_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_contract_id')->nullable()->constrained('project_contracts')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->unsignedSmallInteger('revision')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('status', 30)->default('draft');
            $table->date('period_starts_at');
            $table->date('period_ends_at');
            $table->text('executive_summary')->nullable();
            $table->json('achievements')->nullable();
            $table->json('challenges')->nullable();
            $table->json('risks')->nullable();
            $table->json('financial_snapshot')->nullable();
            $table->json('operational_snapshot')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'year', 'quarter', 'revision'], 'qpr_project_period_revision_uq');
            $table->index(['project_id', 'is_current', 'status'], 'qpr_project_current_status_idx');
            $table->index(['status', 'period_ends_at'], 'qpr_status_period_end_idx');
        });

        Schema::create('kpi_measurements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kpi_id')->constrained('kpis')->cascadeOnDelete();
            $table->foreignId('quarterly_performance_report_id')->nullable()->constrained('quarterly_performance_reports')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->decimal('measured_value', 24, 6)->nullable();
            $table->decimal('target_snapshot', 24, 6)->nullable();
            $table->decimal('achievement_percentage', 8, 3)->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('evidence_media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();

            $table->unique(
                ['quarterly_performance_report_id', 'kpi_id'],
                'kpi_measurement_report_kpi_uq'
            );
            $table->index(['kpi_id', 'year', 'quarter'], 'kpi_measurement_kpi_period_idx');
            $table->index(['status', 'year', 'quarter'], 'kpi_measurement_status_period_idx');
        });
    }

    private function createConfigurableFormsAndReports(): void
    {
        Schema::create('stage_form_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invest_step_id')->constrained('investsteps')->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('title');
            $table->unsignedSmallInteger('version')->default(1);
            $table->json('json_schema');
            $table->json('ui_schema')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['invest_step_id', 'code', 'version'], 'sfd_step_code_version_uq');
            $table->index(['invest_step_id', 'is_active'], 'sfd_step_active_idx');
        });

        Schema::create('project_stage_form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_stage_instance_id')->constrained('project_stage_instances')->cascadeOnDelete();
            $table->foreignId('stage_form_definition_id')->constrained('stage_form_definitions')->restrictOnDelete();
            $table->unsignedSmallInteger('revision')->default(1);
            $table->string('status', 30)->default('draft');
            $table->json('payload');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();

            $table->unique(
                ['project_stage_instance_id', 'stage_form_definition_id', 'revision'],
                'psfs_stage_form_revision_uq'
            );
            $table->index(['project_stage_instance_id', 'status'], 'psfs_stage_status_idx');
        });

        Schema::create('report_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('title');
            $table->string('data_source', 80);
            $table->json('columns');
            $table->json('filter_schema')->nullable();
            $table->json('allowed_role_slugs')->nullable();
            $table->unsignedInteger('cache_ttl_seconds')->default(300);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('report_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_definition_id')->constrained('report_definitions')->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('filters')->nullable();
            $table->string('format', 20)->default('screen');
            $table->string('status', 30)->default('queued');
            $table->string('output_disk', 60)->nullable();
            $table->text('output_path')->nullable();
            $table->unsignedBigInteger('row_count')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['requested_by', 'created_at'], 'report_runs_requester_created_idx');
            $table->index(['status', 'created_at'], 'report_runs_status_created_idx');
        });

        $now = now();
        foreach ([
            [
                'code' => 'portfolio_overview',
                'title' => 'نمای کلی پورتفو',
                'data_source' => 'portfolio',
                'columns' => ['company', 'project', 'stage', 'progress', 'contract_amount', 'paid_amount', 'risk'],
            ],
            [
                'code' => 'quarterly_performance',
                'title' => 'عملکرد فصلی و تحقق KPI',
                'data_source' => 'quarterly_performance',
                'columns' => ['company', 'project', 'year', 'quarter', 'status', 'achievement_score', 'below_target'],
            ],
            [
                'code' => 'workflow_sla',
                'title' => 'SLA مراحل و تخصیص‌ها',
                'data_source' => 'workflow_sla',
                'columns' => ['project', 'stage', 'assignee', 'status', 'due_at', 'delay_hours'],
            ],
        ] as $definition) {
            DB::table('report_definitions')->insert([
                ...$definition,
                'columns' => json_encode($definition['columns'], JSON_UNESCAPED_UNICODE),
                'filter_schema' => json_encode(['project_id', 'year', 'quarter', 'status'], JSON_UNESCAPED_UNICODE),
                'allowed_role_slugs' => json_encode([
                    'superadmin', 'manager', 'senior_investment_expert',
                ], JSON_UNESCAPED_UNICODE),
                'cache_ttl_seconds' => 300,
                'is_active' => true,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedSeniorInvestmentExpertRole(): void
    {
        $now = now();
        $roleId = DB::table('roles')->where('title', 'senior_investment_expert')->value('id');

        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'title_fa' => 'کارشناس ارشد سرمایه‌گذاری',
                'title' => 'senior_investment_expert',
                'status' => 4,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionId = DB::table('permissions')->where('slug', 'flow')->value('id');
        if ($permissionId && ! DB::table('permission_role')->where([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ])->exists()) {
            DB::table('permission_role')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'can_view' => true,
                'can_insert' => true,
                'can_edit' => true,
                'can_delete' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillProjectStageInstances(): void
    {
        $steps = DB::table('investsteps')
            ->where('status', 4)
            ->orderByRaw('CASE WHEN sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        if ($steps->isEmpty()) {
            return;
        }

        DB::table('projects')->orderBy('id')->chunkById(100, function ($projects) use ($steps): void {
            foreach ($projects as $project) {
                $decisions = DB::table('project_steps')
                    ->where('project_id', $project->id)
                    ->orderByDesc('id')
                    ->get()
                    ->keyBy('step_number');

                foreach ($steps as $offset => $step) {
                    $decision = $decisions->get($step->id);
                    $isCurrent = (int) $project->invest_step === (int) $step->id;
                    $isBeforeCurrent = (int) ($step->sequence ?? $step->id) < $this->projectCurrentSequence($project, $steps);
                    $status = 'locked';

                    if ($decision) {
                        $status = $decision->status === 'rejected' ? 'rejected' : 'approved';
                    } elseif ($isCurrent) {
                        $status = (bool) $project->is_rejected ? 'rejected' : 'awaiting_assignment';
                    } elseif ($isBeforeCurrent) {
                        $status = 'approved';
                    }

                    $instanceId = DB::table('project_stage_instances')->insertGetId([
                        'project_id' => $project->id,
                        'invest_step_id' => $step->id,
                        'stage_code' => $step->code ?: sprintf('STAGE_%02d', (int) $step->id),
                        'sequence' => $step->sequence ?? ($offset + 1),
                        'title_snapshot' => $step->title,
                        'weight_snapshot' => $step->weight ?? 0,
                        'status' => $status,
                        'opened_at' => $isCurrent || $isBeforeCurrent ? ($project->created_at ?? now()) : null,
                        'decided_at' => $decision?->created_at,
                        'decided_by' => $decision?->user_id,
                        'lock_version' => 1,
                        'metadata' => json_encode(['backfilled' => true], JSON_UNESCAPED_UNICODE),
                        'created_at' => $project->created_at ?? now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('project_steps')
                        ->where('project_id', $project->id)
                        ->where('step_number', $step->id)
                        ->update([
                            'project_stage_instance_id' => $instanceId,
                            'decided_at' => DB::raw('COALESCE(decided_at, created_at)'),
                        ]);

                    DB::table('project_assignments')
                        ->where('project_id', $project->id)
                        ->where('invest_step_id', $step->id)
                        ->update(['project_stage_instance_id' => $instanceId]);
                }
            }
        });
    }

    private function projectCurrentSequence(object $project, $steps): int
    {
        $current = $steps->firstWhere('id', (int) $project->invest_step);

        return (int) ($current?->sequence ?? $current?->id ?? PHP_INT_MAX);
    }

    public function down(): void
    {
        // This migration establishes auditable production domains. Rollback is intentionally
        // non-destructive because dropping workflow decisions, files, contracts, or reports
        // would destroy business records. Use a separately reviewed archival migration instead.
    }
};
