<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectStageComment;
use App\Models\ProjectStageInstance;
use App\Services\InvestmentWorkflowAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectStageCommentController extends Controller
{
    public function store(
        Request $request,
        Project $project,
        ProjectStageInstance $stage,
        InvestmentWorkflowAccessService $access
    ): JsonResponse {
        abort_unless((int) $stage->project_id === (int) $project->getKey(), 404);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'visibility' => ['nullable', 'in:internal,investee'],
            'type' => ['nullable', 'in:review_note,information_request,investee_response'],
        ]);

        $assignment = $stage->activeAssignments()
            ->where('user_id', $request->user()->getKey())
            ->first();
        abort_unless($assignment || $access->canManageStage($request->user(), (int) $stage->invest_step_id), 403);

        $comment = ProjectStageComment::query()->create([
            'project_stage_instance_id' => $stage->getKey(),
            'author_id' => $request->user()->getKey(),
            'project_assignment_id' => $assignment?->getKey(),
            'type' => $validated['type'] ?? 'review_note',
            'visibility' => $validated['visibility'] ?? 'internal',
            'body' => $validated['body'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'نظر مرحله ثبت شد.',
            'data' => $comment->load('author:id,name'),
        ], 201);
    }
}
