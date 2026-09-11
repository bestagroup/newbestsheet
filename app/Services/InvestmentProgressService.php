<?php

namespace App\Services;

use App\Models\Investstep;
use App\Models\Project;
use App\Models\ProjectStageInstance;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class InvestmentProgressService
{
    /**
     * @return EloquentCollection<int, Investstep>
     */
    public function activeSteps(): EloquentCollection
    {
        return Investstep::query()
            ->where('status', 4)
            ->orderBy('id')
            ->get(['id', 'title', 'weight', 'status']);
    }

    public function percentageForProject(Project $project, ?Collection $activeSteps = null): int
    {
        $stages = $project->relationLoaded('stageInstances')
            ? $project->stageInstances
            : $project->stageInstances()
                ->get(['id', 'project_id', 'invest_step_id', 'weight_snapshot', 'status', 'sequence']);

        if ($stages->isNotEmpty()) {
            $weights = $stages->mapWithKeys(static fn (ProjectStageInstance $stage): array => [
                $stage->getKey() => max(0.0, (float) $stage->weight_snapshot),
            ]);
            if ((float) $weights->sum() <= 0) {
                $weights = $stages->mapWithKeys(static fn (ProjectStageInstance $stage): array => [
                    $stage->getKey() => 1.0,
                ]);
            }

            $totalWeight = (float) $weights->sum();
            $completedWeight = (float) $stages
                ->filter(static fn (ProjectStageInstance $stage): bool => $stage->status->value === 'approved')
                ->sum(static fn (ProjectStageInstance $stage): float => (float) $weights->get($stage->getKey(), 0));

            return (int) max(0, min(100, round(($completedWeight / $totalWeight) * 100)));
        }

        $steps = $activeSteps ?? $this->activeSteps();

        if ($steps->isEmpty() || ! $project->invest_step) {
            return 0;
        }

        $weights = $this->effectiveWeights($steps);
        $totalWeight = (float) $weights->sum();

        if ($totalWeight <= 0) {
            return 0;
        }

        $currentIndex = $steps->search(
            static fn (Investstep $step): bool => (int) $step->getKey() === (int) $project->invest_step
        );

        $approvedStepIds = $project->relationLoaded('projectSteps')
            ? $project->projectSteps
                ->where('status', 'approved')
                ->pluck('step_number')
                ->map(static fn ($id): int => (int) $id)
                ->flip()
            : $project->projectSteps()
                ->where('status', 'approved')
                ->pluck('step_number')
                ->map(static fn ($id): int => (int) $id)
                ->flip();

        $completedWeight = 0.0;

        foreach ($steps->values() as $index => $step) {
            $completedByPosition = $currentIndex !== false
                ? $index < $currentIndex
                : (int) $step->getKey() < (int) $project->invest_step;
            $completedByHistory = $approvedStepIds->has((int) $step->getKey());

            if ($completedByPosition || $completedByHistory) {
                $completedWeight += (float) ($weights->get($step->getKey()) ?? 0.0);
            }
        }

        return (int) max(0, min(100, round(($completedWeight / $totalWeight) * 100)));
    }

    public function normalizedShare(Investstep $step, ?Collection $activeSteps = null): float
    {
        $steps = $activeSteps ?? $this->activeSteps();
        $weights = $this->effectiveWeights($steps);
        $totalWeight = (float) $weights->sum();

        if ($totalWeight <= 0) {
            return 0.0;
        }

        return round(((float) ($weights->get($step->getKey()) ?? 0.0) / $totalWeight) * 100, 2);
    }

    public function recalculateAllProjects(): int
    {
        $steps = $this->activeSteps();
        $steps->each(static function (Investstep $step): void {
            ProjectStageInstance::query()
                ->where('invest_step_id', $step->getKey())
                ->update(['weight_snapshot' => max(0.0, (float) $step->weight)]);
        });
        $updated = 0;

        Project::query()
            ->with(['stageInstances:id,project_id,invest_step_id,weight_snapshot,status,sequence'])
            ->select(['id', 'invest_step', 'progress_percentage', 'is_rejected'])
            ->orderBy('id')
            ->chunkById(100, function ($projects) use ($steps, &$updated): void {
                foreach ($projects as $project) {
                    $percentage = $this->percentageForProject($project, $steps);

                    if ((int) $project->progress_percentage !== $percentage) {
                        Project::query()
                            ->whereKey($project->getKey())
                            ->update(['progress_percentage' => $percentage]);
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    private function effectiveWeights(Collection $steps): Collection
    {
        $weights = $steps->mapWithKeys(static function (Investstep $step): array {
            return [$step->getKey() => max(0.0, (float) ($step->weight ?? 0))];
        });

        if ((float) $weights->sum() > 0) {
            return $weights;
        }

        // Defensive fallback for a legacy/misconfigured database: keep progress calculable.
        return $steps->mapWithKeys(static fn (Investstep $step): array => [$step->getKey() => 1.0]);
    }
}
