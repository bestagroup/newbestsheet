<?php

namespace App\Services;

use App\Enums\ProjectStageStatus;
use App\Models\Investstep;
use App\Models\Project;
use App\Models\ProjectStageInstance;
use Illuminate\Support\Facades\DB;

class ProjectStageProvisioningService
{
    public function ensureForProject(Project $project): void
    {
        DB::transaction(function () use ($project): void {
            $lockedProject = Project::query()->whereKey($project->getKey())->lockForUpdate()->firstOrFail();

            // Stage instances are the immutable route snapshot for a dossier.
            // Activating a new global definition must not insert it halfway
            // through projects that already started their route.
            if (ProjectStageInstance::query()->where('project_id', $lockedProject->getKey())->exists()) {
                return;
            }

            $steps = Investstep::query()
                ->where('status', 4)
                ->orderByRaw('CASE WHEN sequence IS NULL THEN 1 ELSE 0 END')
                ->orderBy('sequence')
                ->orderBy('id')
                ->get();

            if ($steps->isEmpty()) {
                return;
            }

            $currentIndex = $steps->search(
                static fn (Investstep $step): bool => (int) $step->getKey() === (int) $lockedProject->invest_step
            );
            if ($currentIndex === false) {
                $currentIndex = 0;
                $current = $steps->first();
                $lockedProject->forceFill([
                    'invest_step' => $current->getKey(),
                    'flow_level' => $current->title,
                ])->save();
            } else {
                $current = $steps->get($currentIndex);
            }

            foreach ($steps as $offset => $step) {
                $sequence = (int) ($step->sequence ?? ($offset + 1));
                $isCurrent = (int) $step->getKey() === (int) $current->getKey();
                $isBefore = $offset < $currentIndex;
                $status = match (true) {
                    $isBefore => ProjectStageStatus::Approved,
                    $isCurrent && (bool) $lockedProject->is_rejected => ProjectStageStatus::Rejected,
                    $isCurrent => ProjectStageStatus::AwaitingAssignment,
                    default => ProjectStageStatus::Locked,
                };

                ProjectStageInstance::query()->firstOrCreate(
                    [
                        'project_id' => $lockedProject->getKey(),
                        'invest_step_id' => $step->getKey(),
                    ],
                    [
                        'stage_code' => $step->code ?: sprintf('STAGE_%02d', (int) $step->getKey()),
                        'sequence' => $sequence,
                        'title_snapshot' => $step->title,
                        'weight_snapshot' => $step->weight,
                        'status' => $status,
                        'opened_at' => $isCurrent || $isBefore ? now() : null,
                        'due_at' => $isCurrent && $step->sla_hours ? now()->addHours($step->sla_hours) : null,
                        'metadata' => ['definition_version' => 1],
                    ]
                );
            }
        }, 3);
    }

    public function stageFor(Project $project, Investstep $step, bool $lock = false): ProjectStageInstance
    {
        $this->ensureForProject($project);

        $query = ProjectStageInstance::query()
            ->where('project_id', $project->getKey())
            ->where('invest_step_id', $step->getKey());

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }
}
