<?php

namespace App\Http\Controllers\Panel;

use App\Enums\InvestmentRole;
use App\Http\Controllers\Controller;
use App\Models\Investstep;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\ProjectAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectAssignmentController extends Controller
{
    public function store(Request $request, Project $project, ProjectAssignmentService $service): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'invest_step_id' => ['required', 'integer', 'exists:investsteps,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $role = Role::query()->findOrFail($validated['role_id']);
        abort_unless(in_array($role->title, InvestmentRole::assignableReviewRoleSlugs(), true), 422, 'نقش انتخاب‌شده برای تخصیص فرایند معتبر نیست.');

        $assignment = $service->assign(
            $project,
            User::query()->findOrFail($validated['user_id']),
            $role,
            Investstep::query()->findOrFail($validated['invest_step_id']),
            $request->user(),
            $validated['notes'] ?? null,
        );

        return response()->json([
            'success' => true,
            'flag' => 'success',
            'subject' => 'عملیات موفق',
            'message' => 'تخصیص فرایند با موفقیت ثبت شد.',
            'data' => ['id' => $assignment->getKey()],
        ]);
    }

    public function destroy(Request $request, Project $project, ProjectAssignment $assignment, ProjectAssignmentService $service): JsonResponse
    {
        abort_unless((int) $assignment->project_id === (int) $project->getKey(), 404);

        $service->end($assignment, $request->user());

        return response()->json([
            'success' => true,
            'flag' => 'success',
            'subject' => 'عملیات موفق',
            'message' => 'تخصیص فعال با موفقیت پایان یافت.',
        ]);
    }
}
