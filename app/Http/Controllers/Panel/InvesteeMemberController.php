<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ProjectMemberRequest;
use App\Models\CompanyMembers;
use App\Services\ActivityLogService;
use App\Services\InvesteePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvesteeMemberController extends Controller
{
    public function __construct(
        private readonly InvesteePortalService $portal,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function store(ProjectMemberRequest $request): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        $company = $this->portal->ensureCompany($project, $request->user());
        $data = $request->validated();
        $data['project_id'] = $project->getKey();
        $data['company_id'] = $company->getKey();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $member = CompanyMembers::query()->create($data);

        $this->activityLog->record('investee.member_created', sprintf('عضو #%d به پروژه #%d افزوده شد.', $member->getKey(), $project->getKey()), (int) $request->user()->getKey());

        return response()->json(['success' => true, 'message' => 'عضو تیم با موفقیت ثبت شد.', 'data' => $member]);
    }

    public function edit(Request $request, int $member): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        $record = $project->members()->whereKey($member)->firstOrFail();

        return response()->json(['data' => $record]);
    }

    public function update(ProjectMemberRequest $request, int $member): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        $company = $this->portal->ensureCompany($project, $request->user());
        $record = $project->members()->whereKey($member)->firstOrFail();
        $data = $request->validated();
        $data['company_id'] = $company->getKey();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;
        $record->fill($data)->save();

        $this->activityLog->record('investee.member_updated', sprintf('عضو #%d پروژه #%d ویرایش شد.', $record->getKey(), $project->getKey()), (int) $request->user()->getKey());

        return response()->json(['success' => true, 'message' => 'اطلاعات عضو تیم به‌روزرسانی شد.', 'data' => $record->fresh()]);
    }

    public function destroy(Request $request, int $member): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        $record = $project->members()->whereKey($member)->firstOrFail();
        $id = (int) $record->getKey();
        $record->delete();
        $this->activityLog->record('investee.member_deleted', sprintf('عضو #%d پروژه #%d حذف شد.', $id, $project->getKey()), (int) $request->user()->getKey());

        return response()->json(['success' => true, 'message' => 'عضو تیم حذف شد.']);
    }
}
