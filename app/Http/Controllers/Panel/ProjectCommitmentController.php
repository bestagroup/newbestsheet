<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ProjectCommitmentRequest;
use App\Models\Project;
use App\Models\ProjectCommitment;
use App\Services\ActivityLogService;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Http\JsonResponse;

class ProjectCommitmentController extends Controller
{
    public function store(
        ProjectCommitmentRequest $request,
        int $project,
        ActivityLogService $activity
    ): JsonResponse {
        $projectModel = Project::query()->findOrFail($project);
        $data = $this->payload($request->validated());
        $data['project_id'] = $projectModel->id;
        $data['created_by'] = $request->user()->id;

        $item = ProjectCommitment::query()->create($data);
        $activity->record('commitment.scheduled', "تعهد #{$item->commitment_id} برای پروژه #{$projectModel->id} زمان‌بندی شد.");

        return response()->json([
            'success' => true,
            'message' => 'زمان‌بندی تعهد با موفقیت ثبت شد.',
            'data' => $item->load('commitment'),
        ]);
    }

    public function update(
        ProjectCommitmentRequest $request,
        int $project,
        int $projectCommitment,
        ActivityLogService $activity
    ): JsonResponse {
        $projectModel = Project::query()->findOrFail($project);
        $item = $this->itemForProject($projectModel, $projectCommitment);
        $data = $this->payload($request->validated(), $item);

        $item->fill($data)->save();
        $activity->record('commitment.updated', "تعهد #{$item->commitment_id} پروژه #{$projectModel->id} به‌روزرسانی شد.");

        return response()->json([
            'success' => true,
            'message' => 'تعهد پروژه با موفقیت به‌روزرسانی شد.',
            'data' => $item->fresh('commitment'),
        ]);
    }

    public function destroy(int $project, int $projectCommitment, ActivityLogService $activity): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $item = $this->itemForProject($projectModel, $projectCommitment);
        $commitmentId = $item->commitment_id;
        $item->delete();
        $activity->record('commitment.unscheduled', "زمان‌بندی تعهد #{$commitmentId} از پروژه #{$projectModel->id} حذف شد.");

        return response()->json(['success' => true, 'message' => 'زمان‌بندی تعهد حذف شد.']);
    }

    private function itemForProject(Project $project, int $id): ProjectCommitment
    {
        return $project->commitments()->whereKey($id)->firstOrFail();
    }

    private function payload(array $data, ?ProjectCommitment $existing = null): array
    {
        $data['due_at'] = LocalizedInputNormalizer::date($data['due_date'] ?? null);
        $status = $data['status'] ?? $existing?->status ?? 'pending';
        $data['status'] = $status;

        if ($status === 'completed') {
            $data['completed_at'] = $existing?->completed_at ?? now();
        } else {
            $data['completed_at'] = null;
        }

        return $data;
    }
}
