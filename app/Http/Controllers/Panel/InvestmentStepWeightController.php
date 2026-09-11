<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\InvestmentStepWeightRequest;
use App\Models\Investstep;
use App\Services\ActivityLogService;
use App\Services\InvestmentProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InvestmentStepWeightController extends Controller
{
    public function __construct(
        private readonly InvestmentProgressService $progress,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function update(InvestmentStepWeightRequest $request): JsonResponse
    {
        $weights = collect($request->validated('weights'))
            ->mapWithKeys(static fn ($weight, $id): array => [(int) $id => round((float) $weight, 3)]);

        $updatedProjects = DB::transaction(function () use ($weights, $request): int {
            Investstep::query()
                ->where('status', 4)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->each(function (Investstep $step) use ($weights): void {
                    $step->weight = $weights->get((int) $step->getKey());
                    $step->save();
                });

            $updatedProjects = $this->progress->recalculateAllProjects();

            $this->activityLog->record(
                'workflow.weights_updated',
                sprintf('وزن مراحل سرمایه‌گذاری به‌روزرسانی شد؛ درصد پیشرفت %d پروژه بازمحاسبه شد.', $updatedProjects),
                (int) $request->user()->getKey()
            );

            return $updatedProjects;
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'وزن مراحل و درصد پیشرفت پروژه‌ها با موفقیت بازمحاسبه شد.',
            'updated_projects' => $updatedProjects,
        ]);
    }
}
