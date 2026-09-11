<?php

namespace App\Services;

use App\Models\Investstep;
use App\Models\MediaFile;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InvestmentStepDocumentService
{
    /**
     * @return Collection<int, array{requirement_id:int, subject_file_id:int, title:string, minimum_files:int, uploaded_files:int}>
     */
    public function missingRequiredDocuments(Project $project, Investstep $step): Collection
    {
        $requirements = $step->documentRequirements()
            ->with('subject:id,title')
            ->where('is_required', true)
            ->get();

        if ($requirements->isEmpty()) {
            return collect();
        }

        $stageId = $project->stageInstances()
            ->where('invest_step_id', $step->getKey())
            ->value('id');
        $requirementIds = $requirements->pluck('id')->map(static fn ($id): int => (int) $id);
        $subjectIds = $requirements->pluck('subject_file_id')->map(static fn ($id): int => (int) $id);

        $files = MediaFile::query()
            ->where('project_id', $project->getKey())
            ->where(function ($query) use ($requirementIds, $subjectIds, $stageId): void {
                $query->whereIn('document_requirement_id', $requirementIds)
                    ->orWhere(function ($legacy) use ($subjectIds, $stageId): void {
                        $legacy->whereNull('document_requirement_id')
                            ->whereIn('subject_id', $subjectIds)
                            ->where(function ($stageQuery) use ($stageId): void {
                                $stageQuery->whereNull('project_stage_instance_id');
                                if ($stageId) {
                                    $stageQuery->orWhere('project_stage_instance_id', $stageId);
                                }
                            });
                    });
            })
            ->whereIn('scan_status', ['clean', 'legacy'])
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 5);
            })
            ->get(['id', 'subject_id', 'document_requirement_id', 'project_stage_instance_id']);

        return $requirements
            ->map(function ($requirement) use ($files, $stageId) {
                $uploaded = $files->filter(static function (MediaFile $file) use ($requirement, $stageId): bool {
                    if ($file->document_requirement_id !== null) {
                        return (int) $file->document_requirement_id === (int) $requirement->getKey();
                    }

                    return (int) $file->subject_id === (int) $requirement->subject_file_id
                        && ($file->project_stage_instance_id === null
                            || (int) $file->project_stage_instance_id === (int) $stageId);
                })->count();
                $minimum = max(1, (int) $requirement->minimum_files);

                if ($uploaded >= $minimum) {
                    return null;
                }

                return [
                    'requirement_id' => (int) $requirement->getKey(),
                    'subject_file_id' => (int) $requirement->subject_file_id,
                    'title' => (string) ($requirement->subject?->title ?? 'سند بدون عنوان'),
                    'minimum_files' => $minimum,
                    'uploaded_files' => $uploaded,
                ];
            })
            ->filter()
            ->values();
    }

    public function assertRequiredDocumentsSatisfied(Project $project, Investstep $step): void
    {
        $missing = $this->missingRequiredDocuments($project, $step);

        if ($missing->isEmpty()) {
            return;
        }

        $labels = $missing->map(static function (array $item): string {
            return $item['minimum_files'] > 1
                ? $item['title'].' (حداقل '.$item['minimum_files'].' فایل)'
                : $item['title'];
        })->implode('، ');

        throw ValidationException::withMessages([
            'documents' => 'پیش از تأیید این مرحله، مدارک الزامی تکمیل شوند: '.$labels,
        ]);
    }
}
