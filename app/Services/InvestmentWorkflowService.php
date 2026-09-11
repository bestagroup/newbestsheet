<?php

namespace App\Services;

use App\Enums\ProjectStageStatus;
use App\Events\InvestmentStepTransitioned;
use App\Models\Investstep;
use App\Models\Project;
use App\Models\Project_step;
use App\Models\ProjectStageComment;
use App\Models\ProjectStageInstance;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentWorkflowService
{
    public function __construct(
        private readonly InvestmentWorkflowAccessService $accessService,
        private readonly InvestmentStepDocumentService $documentService,
        private readonly ActivityLogService $activityLog,
        private readonly InvestmentProgressService $progressService,
        private readonly ProjectStageProvisioningService $stageProvisioning,
        private readonly StageFormService $stageFormService,
    ) {}

    public function transition(
        int $projectId,
        int $stepId,
        string $status,
        ?string $description,
        User $actor
    ): Project_step {
        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => 'وضعیت مرحله معتبر نیست.',
            ]);
        }

        if ($status === 'rejected' && blank($description)) {
            throw ValidationException::withMessages([
                'description' => 'ثبت دلیل رد مرحله الزامی است.',
            ]);
        }

        $projectStep = DB::transaction(function () use ($projectId, $stepId, $status, $description, $actor) {
            /** @var Project $project */
            $project = Project::query()
                ->lockForUpdate()
                ->findOrFail($projectId);

            $this->stageProvisioning->ensureForProject($project);
            $routeStages = ProjectStageInstance::query()
                ->with('definition:id,title,weight,sla_hours')
                ->where('project_id', $project->getKey())
                ->orderBy('sequence')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $currentIndex = $routeStages->search(
                static fn (ProjectStageInstance $stage): bool => (int) $stage->invest_step_id === $stepId
            );

            if ($currentIndex === false) {
                throw ValidationException::withMessages([
                    'step_id' => 'مرحله انتخاب‌شده در مسیر ثابت این پروژه وجود ندارد.',
                ]);
            }

            /** @var ProjectStageInstance $stageInstance */
            $stageInstance = $routeStages->get($currentIndex);
            /** @var Investstep|null $step */
            $step = $stageInstance->definition;
            if (! $step) {
                throw ValidationException::withMessages([
                    'step_id' => 'تعریف مرحله انتخاب‌شده در دسترس نیست.',
                ]);
            }

            if ((int) $project->invest_step !== (int) $step->getKey()) {
                throw ValidationException::withMessages([
                    'step_id' => 'فقط مرحله جاری پروژه قابل ثبت است.',
                ]);
            }

            $decisionContext = $this->accessService
                ->decisionContext($actor, (int) $project->getKey(), (int) $step->getKey());

            if ($decisionContext === null) {
                throw new AuthorizationException('برای ثبت تصمیم این پروژه/مرحله تخصیص یا نقش مجاز ندارید.');
            }

            if ((bool) $project->is_rejected) {
                throw ValidationException::withMessages([
                    'project_id' => 'این پروژه در مرحله جاری رد شده و امکان ثبت اقدام مجدد ندارد.',
                ]);
            }

            if (! in_array($stageInstance->status, [
                ProjectStageStatus::AwaitingAssignment,
                ProjectStageStatus::AwaitingDocuments,
                ProjectStageStatus::UnderReview,
            ], true)) {
                throw ValidationException::withMessages([
                    'step_id' => 'مرحله در وضعیت جاری قابل تصمیم‌گیری نیست.',
                ]);
            }

            $alreadyRecorded = Project_step::query()
                ->where(function ($query) use ($project, $step, $stageInstance): void {
                    $query->where('project_stage_instance_id', $stageInstance->getKey())
                        ->orWhere(function ($legacy) use ($project, $step): void {
                            $legacy->where('project_id', $project->getKey())
                                ->where('step_number', $step->getKey());
                        });
                })
                ->exists();

            if ($alreadyRecorded) {
                throw ValidationException::withMessages([
                    'step_id' => 'نتیجه این مرحله قبلاً ثبت شده است.',
                ]);
            }

            if ($status === 'approved') {
                $this->documentService->assertRequiredDocumentsSatisfied($project, $step);
                $this->stageFormService->assertRequiredFormsSubmitted($stageInstance);
            }

            $projectStep = Project_step::query()->create([
                'project_id' => $project->getKey(),
                'project_stage_instance_id' => $stageInstance->getKey(),
                'title' => $step->title,
                'step_number' => $step->getKey(),
                'status' => $status,
                'decided_at' => now(),
                'description' => $description ?? '',
                'user_id' => $actor->getKey(),
                'project_assignment_id' => $decisionContext['assignment']?->getKey(),
                'actor_role_id' => $decisionContext['role']->getKey(),
                'decision_metadata' => [
                    'request_id' => request()?->attributes->get('request_id'),
                    'source' => 'workflow_service',
                ],
            ]);

            $stageInstance->forceFill([
                'status' => $status === 'approved'
                    ? ProjectStageStatus::Approved
                    : ProjectStageStatus::Rejected,
                'decided_at' => now(),
                'decided_by' => $actor->getKey(),
                'lock_version' => $stageInstance->lock_version + 1,
            ])->save();

            if (filled($description)) {
                ProjectStageComment::query()->create([
                    'project_stage_instance_id' => $stageInstance->getKey(),
                    'author_id' => $actor->getKey(),
                    'project_assignment_id' => $decisionContext['assignment']?->getKey(),
                    'type' => 'decision_reason',
                    'visibility' => 'investee',
                    'body' => $description,
                    'metadata' => ['decision' => $status],
                ]);
            }

            if ($status === 'approved') {
                /** @var ProjectStageInstance|null $nextStage */
                $nextStage = $routeStages->get($currentIndex + 1);
                /** @var Investstep|null $nextStep */
                $nextStep = $nextStage?->definition;

                $project->invest_step = $nextStep?->getKey() ?? $step->getKey();
                $project->flow_level = $nextStep?->title ?? $step->title;
                $project->is_rejected = false;
                $project->reject_step = null;

                if ($nextStep && $nextStage) {
                    $hasActiveAssignment = $nextStage->activeAssignments()->exists();
                    $nextStage->forceFill([
                        'status' => $hasActiveAssignment
                            ? ProjectStageStatus::UnderReview
                            : ProjectStageStatus::AwaitingAssignment,
                        'opened_at' => $nextStage->opened_at ?? now(),
                        'due_at' => $nextStep->sla_hours ? now()->addHours($nextStep->sla_hours) : null,
                        'review_started_at' => $hasActiveAssignment
                            ? ($nextStage->review_started_at ?? now())
                            : $nextStage->review_started_at,
                        'lock_version' => $nextStage->lock_version + 1,
                    ])->save();
                }
            } else {
                $project->invest_step = $step->getKey();
                $project->flow_level = $step->title;
                $project->is_rejected = true;
                $project->reject_step = $step->getKey();
            }

            // Weighted progress is the single source of truth. A rejected step never contributes
            // weight; an approved current/final step is detected from project_steps history.
            $project->unsetRelation('stageInstances');
            $project->progress_percentage = $this->progressService->percentageForProject($project);
            $project->save();

            $this->activityLog->record(
                'workflow.transition',
                sprintf('مرحله «%s» پروژه #%d با وضعیت %s ثبت شد.', $step->title, $project->getKey(), $status),
                (int) $actor->getKey()
            );

            return $projectStep;
        }, 3);

        InvestmentStepTransitioned::dispatch($projectStep->fresh(['project.user', 'project.company']));

        return $projectStep;
    }
}
