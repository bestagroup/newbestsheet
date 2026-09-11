<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ProjectMemberRequest;
use App\Models\CompanyMembers;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class ProjectMemberController extends Controller
{
    public function store(ProjectMemberRequest $request, int $project): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $data = $request->validated();
        $data['project_id'] = $projectModel->id;
        $data['company_id'] = $projectModel->company_id;
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $member = CompanyMembers::query()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات عضو پروژه با موفقیت ثبت شد.',
            'data' => $member,
        ]);
    }

    public function update(ProjectMemberRequest $request, int $project, int $member): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $memberModel = $this->memberForProject($projectModel, $member);

        $data = $request->validated();
        $data['company_id'] = $projectModel->company_id;
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;

        $memberModel->fill($data)->save();

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات عضو پروژه با موفقیت به‌روزرسانی شد.',
            'data' => $memberModel->fresh(),
        ]);
    }

    public function destroy(int $project, int $member): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $memberModel = $this->memberForProject($projectModel, $member);
        $memberModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'عضو پروژه با موفقیت حذف شد.',
        ]);
    }

    private function memberForProject(Project $project, int $memberId): CompanyMembers
    {
        return $project->members()->whereKey($memberId)->firstOrFail();
    }
}
