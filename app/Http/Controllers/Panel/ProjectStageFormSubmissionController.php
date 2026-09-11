<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectStageInstance;
use App\Models\StageFormDefinition;
use App\Services\StageFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectStageFormSubmissionController extends Controller
{
    public function store(
        Request $request,
        Project $project,
        ProjectStageInstance $stage,
        StageFormDefinition $definition,
        StageFormService $service
    ): JsonResponse {
        abort_unless((int) $stage->project_id === (int) $project->getKey(), 404);
        $validated = $request->validate([
            'payload' => ['required', 'array'],
            'submit' => ['nullable', 'boolean'],
        ]);
        $submission = $service->submit(
            $stage,
            $definition,
            $validated['payload'],
            (bool) ($validated['submit'] ?? false),
            $request->user()
        );

        return response()->json(['success' => true, 'data' => $submission], 201);
    }
}
