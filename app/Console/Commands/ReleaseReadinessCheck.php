<?php

namespace App\Console\Commands;

use App\Enums\InvestmentRole;
use App\Models\Investstep;
use App\Models\Project;
use App\Models\Role;
use App\Services\InvestmentProgressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReleaseReadinessCheck extends Command
{
    protected $signature = 'system:release-readiness {--strict : Fail when operational warnings are present}';

    protected $description = 'Validate business-critical release candidate invariants before deployment.';

    public function handle(InvestmentProgressService $progressService): int
    {
        $failures = 0;
        $warnings = 0;

        $this->components->info('BestSheet VC release readiness');

        foreach ([
            'roles', 'projects', 'investsteps', 'project_steps', 'project_assignments',
            'project_stage_instances', 'project_stage_comments',
            'invest_step_document_requirements', 'stage_form_definitions',
            'project_stage_form_submissions', 'project_commitments', 'project_contracts',
            'quarterly_performance_reports', 'kpi_measurements', 'report_definitions',
            'report_runs', 'operational_notification_deliveries', 'notifications',
            'employees', 'employee_documents', 'administrative_assets',
        ] as $table) {
            $this->check(Schema::hasTable($table), "{$table} exists.", "{$table} is missing.", $failures);
        }

        if ($failures > 0) {
            $this->newLine();
            $this->line(sprintf('Failures: %d | Warnings: %d', $failures, $warnings));

            return self::FAILURE;
        }

        $coreRoles = InvestmentRole::cases();
        foreach ($coreRoles as $role) {
            $this->check(
                Role::query()->where('title', $role->value)->exists(),
                "Role {$role->value} exists.",
                "Core role {$role->value} is missing.",
                $failures
            );
        }

        if (Schema::hasTable('operational_notification_deliveries')) {
            $maxAttempts = max(1, (int) config('operations.notifications.delivery_max_attempts', 3));
            $claimTimeout = max(1, (int) config('operations.notifications.delivery_claim_timeout_minutes', 15));
            $staleBefore = now()->subMinutes($claimTimeout);

            $staleProcessing = DB::table('operational_notification_deliveries')
                ->where('status', 'processing')
                ->where('updated_at', '<=', $staleBefore)
                ->count();
            $exhaustedFailures = DB::table('operational_notification_deliveries')
                ->where('status', 'failed')
                ->where('attempts', '>=', $maxAttempts)
                ->count();

            if ($staleProcessing > 0) {
                $this->components->warn("{$staleProcessing} stale notification delivery claim(s) detected; they are recoverable by the next matching reminder/event run.");
                $warnings++;
            }
            if ($exhaustedFailures > 0) {
                $this->components->warn("{$exhaustedFailures} notification delivery item(s) exhausted their retry budget and require operational review.");
                $warnings++;
            }
        }

        $activeSteps = $progressService->activeSteps();
        $this->check($activeSteps->isNotEmpty(), 'Active investment steps exist.', 'No active investment step exists.', $failures);
        $this->check(
            (float) $activeSteps->sum(fn (Investstep $step): float => max(0.0, (float) $step->weight)) > 0,
            'Investment step weights are configured.',
            'Total active investment step weight must be greater than zero.',
            $failures
        );

        $staleProgress = 0;
        Project::query()
            ->with('stageInstances:id,project_id,invest_step_id,weight_snapshot,status,sequence')
            ->select('id', 'invest_step', 'progress_percentage', 'is_rejected')
            ->orderBy('id')
            ->chunkById(100, function ($projects) use ($progressService, $activeSteps, &$staleProgress): void {
                foreach ($projects as $project) {
                    if ((int) $project->progress_percentage !== $progressService->percentageForProject($project, $activeSteps)) {
                        $staleProgress++;
                    }
                }
            });
        $this->check(
            $staleProgress === 0,
            'Stored weighted progress is consistent.',
            "{$staleProgress} project(s) have stale progress. Re-save weights or run the progress recalculation path.",
            $failures
        );

        $activeProjectIds = Project::query()
            ->where(function ($query): void {
                $query->whereNull('is_rejected')->orWhere('is_rejected', 0);
            })
            ->where('progress_percentage', '<', 100)
            ->pluck('id');
        $assignedProjectCount = DB::table('project_assignments as pa')
            ->join('roles as r', 'r.id', '=', 'pa.role_id')
            ->join('projects as assigned_projects', 'assigned_projects.id', '=', 'pa.project_id')
            ->whereIn('pa.project_id', $activeProjectIds)
            ->where('pa.is_active', true)
            ->whereColumn('pa.invest_step_id', 'assigned_projects.invest_step')
            ->whereIn('r.title', InvestmentRole::decisionRoleSlugs())
            ->distinct()
            ->count('pa.project_id');
        $unassigned = max(0, $activeProjectIds->count() - $assignedProjectCount);
        if ($unassigned > 0) {
            $this->components->warn("{$unassigned} active project(s) have no active expert/evaluator assignment for their current stage.");
            $warnings++;
        } else {
            $this->components->task('All active projects have operational assignments', static fn (): bool => true);
        }

        $this->newLine();
        $this->line(sprintf('Failures: %d | Warnings: %d', $failures, $warnings));

        if ($failures > 0) {
            return self::FAILURE;
        }

        return $this->option('strict') && $warnings > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function check(bool $condition, string $success, string $failure, int &$failures): void
    {
        if ($condition) {
            $this->components->task($success, static fn (): bool => true);

            return;
        }

        $this->components->error($failure);
        $failures++;
    }
}
