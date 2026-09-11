<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ProjectContractRequest;
use App\Models\Project;
use App\Models\ProjectContract;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;

class ProjectContractController extends Controller
{
    public function store(
        ProjectContractRequest $request,
        Project $project,
        ActivityLogService $activity
    ): JsonResponse {
        $contract = $project->contracts()->create([
            ...$request->validated(),
            'currency' => $request->validated('currency', 'IRR'),
            'created_by' => $request->user()->getKey(),
        ]);
        $activity->record(
            'contract.created',
            "قرارداد #{$contract->getKey()} پروژه #{$project->getKey()} ثبت شد.",
            null,
            true,
            null,
            ProjectContract::class,
            (int) $contract->getKey(),
            null,
            $contract->toArray()
        );

        return response()->json(['success' => true, 'data' => $contract], 201);
    }

    public function update(
        ProjectContractRequest $request,
        Project $project,
        ProjectContract $contract,
        ActivityLogService $activity
    ): JsonResponse {
        abort_unless((int) $contract->project_id === (int) $project->getKey(), 404);
        $old = $contract->toArray();
        $contract->fill($request->validated())->save();
        $activity->record(
            'contract.updated',
            "قرارداد #{$contract->getKey()} به‌روزرسانی شد.",
            null,
            true,
            null,
            ProjectContract::class,
            (int) $contract->getKey(),
            $old,
            $contract->fresh()->toArray()
        );

        return response()->json(['success' => true, 'data' => $contract->fresh()]);
    }

    public function destroy(Project $project, ProjectContract $contract, ActivityLogService $activity): JsonResponse
    {
        abort_unless((int) $contract->project_id === (int) $project->getKey(), 404);
        abort_if($contract->kpis()->current()->where('status', 'active')->exists(), 422, 'قرارداد دارای KPI فعال قابل حذف نیست.');
        $contract->delete();
        $activity->record(
            'contract.archived',
            "قرارداد #{$contract->getKey()} بایگانی شد.",
            null,
            true,
            null,
            ProjectContract::class,
            (int) $contract->getKey()
        );

        return response()->json(['success' => true, 'message' => 'قرارداد بایگانی شد.']);
    }
}
