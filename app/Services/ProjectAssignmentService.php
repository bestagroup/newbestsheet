<?php

namespace App\Services;

use App\Enums\InvestmentRole;
use App\Enums\ProjectStageStatus;
use App\Models\Investstep;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectAssignmentService
{
    public function assign(
        Project $project,
        User $assignee,
        Role $role,
        ?Investstep $step,
        User $actor,
        ?string $notes = null
    ): ProjectAssignment {
        $targetStepId = (int) ($step?->getKey() ?: $project->invest_step);
        if (! app(InvestmentWorkflowAccessService::class)->canManageStage($actor, $targetStepId)) {
            throw ValidationException::withMessages([
                'assignment' => 'فقط مدیر یا ادمین مجاز به تخصیص عوامل فرایند سرمایه‌گذاری است.',
            ]);
        }

        if (! in_array($role->title, InvestmentRole::assignableReviewRoleSlugs(), true)) {
            throw ValidationException::withMessages([
                'role_id' => 'این نقش برای تخصیص عملیاتی فرایند سرمایه‌گذاری مجاز نیست.',
            ]);
        }

        if ((int) $assignee->status !== 4) {
            throw ValidationException::withMessages([
                'user_id' => 'کاربر انتخاب‌شده فعال نیست.',
            ]);
        }

        if (! $assignee->hasRole($role->title)) {
            throw ValidationException::withMessages([
                'user_id' => 'کاربر انتخاب‌شده دارای نقش انتخاب‌شده نیست.',
            ]);
        }

        if ($step !== null && (int) $step->status !== 4) {
            throw ValidationException::withMessages([
                'invest_step_id' => 'مرحله انتخاب‌شده فعال نیست.',
            ]);
        }

        return DB::transaction(function () use ($project, $assignee, $role, $step, $actor, $notes) {
            Project::query()->whereKey($project->getKey())->lockForUpdate()->firstOrFail();

            $step ??= Investstep::query()->findOrFail($project->invest_step);
            $stage = app(ProjectStageProvisioningService::class)->stageFor($project, $step, true);

            if ($stage->status->isTerminal()) {
                throw ValidationException::withMessages([
                    'invest_step_id' => 'مرحله نهایی‌شده قابل تخصیص مجدد نیست.',
                ]);
            }

            $existing = ProjectAssignment::query()
                ->where('project_id', $project->getKey())
                ->where('user_id', $assignee->getKey())
                ->where('role_id', $role->getKey())
                ->where('is_active', true)
                ->when(
                    $step,
                    static fn ($query) => $query->where('invest_step_id', $step->getKey()),
                    static fn ($query) => $query->whereNull('invest_step_id')
                )
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'user_id' => 'این کاربر با همین نقش و محدوده قبلاً به پروژه تخصیص داده شده است.',
                ]);
            }

            $assignment = ProjectAssignment::query()->create([
                'project_id' => $project->getKey(),
                'user_id' => $assignee->getKey(),
                'role_id' => $role->getKey(),
                'invest_step_id' => $step?->getKey(),
                'project_stage_instance_id' => $stage->getKey(),
                'assignment_type' => 'primary',
                'assigned_by' => $actor->getKey(),
                'is_active' => true,
                'assigned_at' => now(),
                'notes' => $notes,
            ]);

            if ($stage->status !== ProjectStageStatus::Locked) {
                $stage->forceFill([
                    'status' => ProjectStageStatus::UnderReview,
                    'review_started_at' => $stage->review_started_at ?? now(),
                    'lock_version' => $stage->lock_version + 1,
                ])->save();
            }

            app(ActivityLogService::class)->record(
                'workflow.assignment_created',
                sprintf('کاربر #%d با نقش %s به پروژه #%d تخصیص یافت.', $assignee->getKey(), $role->title, $project->getKey()),
                (int) $actor->getKey()
            );

            return $assignment;
        }, 3);
    }

    public function end(ProjectAssignment $assignment, User $actor): ProjectAssignment
    {
        if (! app(InvestmentWorkflowAccessService::class)->canManageStage(
            $actor,
            (int) ($assignment->invest_step_id ?: $assignment->project?->invest_step)
        )) {
            throw ValidationException::withMessages([
                'assignment' => 'فقط مدیر یا ادمین مجاز به پایان تخصیص است.',
            ]);
        }

        return DB::transaction(function () use ($assignment, $actor) {
            /** @var ProjectAssignment $locked */
            $locked = ProjectAssignment::query()->whereKey($assignment->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->is_active) {
                return $locked;
            }

            $locked->forceFill([
                'is_active' => false,
                'ended_at' => now(),
            ])->save();

            app(ActivityLogService::class)->record(
                'workflow.assignment_ended',
                sprintf('تخصیص #%d پروژه #%d پایان یافت.', $locked->getKey(), $locked->project_id),
                (int) $actor->getKey()
            );

            return $locked->fresh();
        }, 3);
    }
}
