<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\QuarterlyPerformanceRequest;
use App\Http\Requests\Panel\QuarterlyPerformanceReviewRequest;
use App\Models\Project;
use App\Models\QuarterlyPerformanceReport;
use App\Services\InvestmentWorkflowAccessService;
use App\Services\QuarterlyPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuarterlyPerformanceController extends Controller
{
    public function index(
        Request $request,
        Project $project,
        InvestmentWorkflowAccessService $access
    ): JsonResponse {
        $this->authorizeProject($request, $project, $access);

        $reports = $project->quarterlyPerformanceReports()
            ->where('is_current', true)
            ->with(['measurements.kpi:id,title,code,unit,target_value,direction', 'submittedBy:id,name', 'reviewedBy:id,name'])
            ->orderByDesc('year')
            ->orderByDesc('quarter')
            ->paginate(min(100, max(10, $request->integer('per_page', 25))));

        return response()->json($reports);
    }

    public function store(
        QuarterlyPerformanceRequest $request,
        Project $project,
        QuarterlyPerformanceService $service
    ): JsonResponse {
        $report = $service->createRevision($project, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => $report->status === 'submitted'
                ? 'گزارش عملکرد فصلی برای بررسی ارسال شد.'
                : 'پیش‌نویس گزارش عملکرد فصلی ذخیره شد.',
            'data' => $report,
        ], 201);
    }

    public function review(
        QuarterlyPerformanceReviewRequest $request,
        Project $project,
        QuarterlyPerformanceReport $report,
        QuarterlyPerformanceService $service
    ): JsonResponse {
        abort_unless((int) $report->project_id === (int) $project->getKey(), 404);
        $reviewed = $service->review(
            $project,
            $report,
            $request->validated('decision'),
            $request->validated('review_comment'),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'نتیجه بررسی گزارش عملکرد ثبت شد.',
            'data' => $reviewed,
        ]);
    }

    private function authorizeProject(
        Request $request,
        Project $project,
        InvestmentWorkflowAccessService $access
    ): void {
        $isOwner = (int) $project->user_id === (int) $request->user()->getKey();
        abort_unless($isOwner || $access->canViewProject($request->user(), (int) $project->getKey()), 403);
    }
}
