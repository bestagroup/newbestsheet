<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ProjectKpiRequest;
use App\Http\Requests\Panel\ProjectKpiReviewRequest;
use App\Models\KPI;
use App\Models\Project;
use App\Services\ActivityLogService;
use App\Services\InvestmentWorkflowAccessService;
use App\Support\KpiOptions;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectKpiController extends Controller
{
    public function store(ProjectKpiRequest $request, int $project, ActivityLogService $activity): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $data = $request->validated();
        $completed = (bool) ($data['completed'] ?? false);
        unset($data['completed']);
        $data['project_id'] = $projectModel->id;
        $data['code'] = $data['code'] ?? sprintf('P%d-KPI-%d', $projectModel->id, $data['kpi_number']);
        $data['target_value'] = $data['target_value'] ?? (is_numeric($data['value'] ?? null) ? $data['value'] : null);
        $data['deadline_at'] = LocalizedInputNormalizer::date($data['deadline'] ?? null);
        $data['completed_at'] = $completed ? now() : null;
        $data['revision_number'] = 1;
        $data['is_current'] = true;
        $data['review_status'] = 'pending';

        if (array_key_exists('period_time', $data)) {
            $data['time_step'] = $data['period_time'];
            $data['measurement_frequency'] = KpiOptions::frequencyForPeriod($data['period_time']);
        }

        $kpi = KPI::query()->create($data);
        $activity->record('kpi.created', "KPI #{$kpi->id} برای پروژه #{$projectModel->id} ثبت شد.");

        return response()->json([
            'success' => true,
            'message' => 'شاخص کلیدی پروژه با موفقیت ثبت شد.',
            'data' => $kpi,
        ]);
    }

    public function update(ProjectKpiRequest $request, int $project, int $kpi, ActivityLogService $activity): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $this->authorizeEditing($request->user(), $projectModel);

        $revision = DB::transaction(function () use ($request, $projectModel, $kpi): KPI {
            $kpiModel = $projectModel->kpis()
                ->whereKey($kpi)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $kpiModel->is_current) {
                throw ValidationException::withMessages([
                    'kpi' => 'فقط آخرین نسخه KPI قابل ویرایش است.',
                ]);
            }

            $data = $request->validated();
            $completed = array_key_exists('completed', $data)
                ? (bool) $data['completed']
                : $kpiModel->completed_at !== null;
            unset($data['completed'], $data['code']);

            $data['deadline_at'] = LocalizedInputNormalizer::date($data['deadline'] ?? null);
            $data['completed_at'] = $completed ? ($kpiModel->completed_at ?? now()) : null;
            $data['target_value'] = $data['target_value']
                ?? (is_numeric($data['value'] ?? null) ? $data['value'] : null);

            if (array_key_exists('period_time', $data)) {
                $data['time_step'] = $data['period_time'];
                $data['measurement_frequency'] = KpiOptions::frequencyForPeriod($data['period_time']);
            }

            $rootId = (int) ($kpiModel->root_kpi_id ?: $kpiModel->getKey());
            $nextRevision = (int) KPI::query()
                ->whereKey($rootId)
                ->orWhere('root_kpi_id', $rootId)
                ->max('revision_number') + 1;

            $revision = $kpiModel->replicate([
                'code',
                'previous_kpi_id',
                'root_kpi_id',
                'revision_number',
                'is_current',
                'review_status',
                'reviewed_by',
                'reviewed_at',
                'review_comment',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);
            $revision->fill($data);
            $revision->forceFill([
                'project_id' => $projectModel->getKey(),
                'previous_kpi_id' => $kpiModel->getKey(),
                'root_kpi_id' => $rootId,
                'revision_number' => $nextRevision,
                'is_current' => true,
                'review_status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_comment' => null,
                'code' => $this->revisionCode($kpiModel, $nextRevision),
            ])->save();

            $kpiModel->forceFill([
                'is_current' => false,
                'review_status' => 'superseded',
            ])->save();

            return $revision;
        }, 3);

        $activity->record(
            'kpi.revised',
            "نسخه {$revision->revision_number} KPI #{$revision->root_kpi_id} برای پروژه #{$projectModel->id} ثبت شد."
        );

        return response()->json([
            'success' => true,
            'message' => 'نسخه جدید KPI ثبت شد و نسخه قبلی در تاریخچه باقی ماند.',
            'data' => $revision->fresh(['previousVersion']),
        ], 201);
    }

    public function review(
        ProjectKpiReviewRequest $request,
        int $project,
        int $kpi,
        ActivityLogService $activity,
        InvestmentWorkflowAccessService $access
    ): JsonResponse {
        $projectModel = Project::query()->findOrFail($project);
        abort_unless($access->canReviewKpis($request->user(), $projectModel->getKey()), 403);

        $kpiModel = DB::transaction(function () use ($request, $projectModel, $kpi): KPI {
            $lockedKpi = $projectModel->kpis()
                ->whereKey($kpi)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedKpi->is_current) {
                throw ValidationException::withMessages([
                    'kpi' => 'فقط آخرین نسخه KPI قابل بررسی است.',
                ]);
            }

            $lockedKpi->forceFill([
                'review_status' => $request->validated('decision'),
                'reviewed_by' => $request->user()->getKey(),
                'reviewed_at' => now(),
                'review_comment' => $request->validated('review_comment'),
            ])->save();

            return $lockedKpi;
        }, 3);

        $activity->record(
            'kpi.reviewed',
            "KPI #{$kpiModel->id} پروژه #{$projectModel->id} با وضعیت {$kpiModel->review_status} بررسی شد."
        );

        return response()->json([
            'success' => true,
            'message' => $kpiModel->review_status === 'approved'
                ? 'KPI با موفقیت تأیید شد.'
                : 'KPI با موفقیت رد شد.',
            'data' => $kpiModel->fresh('reviewedBy'),
        ]);
    }

    public function destroy(int $project, int $kpi, ActivityLogService $activity): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $kpiModel = $this->kpiForProject($projectModel, $kpi);

        if (! $kpiModel->is_current || $kpiModel->root_kpi_id || $kpiModel->revisions()->exists()) {
            throw ValidationException::withMessages([
                'kpi' => 'KPI دارای سابقه نسخه قابل حذف نیست و باید برای حفظ تاریخچه باقی بماند.',
            ]);
        }

        $kpiId = $kpiModel->id;
        $kpiModel->delete();
        $activity->record('kpi.deleted', "KPI #{$kpiId} پروژه #{$projectModel->id} حذف شد.");

        return response()->json([
            'success' => true,
            'message' => 'شاخص کلیدی پروژه با موفقیت حذف شد.',
        ]);
    }

    private function kpiForProject(Project $project, int $kpiId): KPI
    {
        return $project->kpis()->whereKey($kpiId)->firstOrFail();
    }

    private function authorizeEditing($user, Project $project): void
    {
        $access = app(InvestmentWorkflowAccessService::class);
        abort_unless(
            $access->canManagePortfolioDepartment($user)
                || $access->canReviewKpis($user, $project->getKey()),
            403
        );
    }

    private function revisionCode(KPI $kpi, int $revision): string
    {
        $base = preg_replace('/-R\d+$/', '', (string) $kpi->code)
            ?: sprintf('P%d-KPI-%d', (int) $kpi->project_id, (int) $kpi->kpi_number);
        $suffix = '-R'.$revision;

        return substr($base, 0, 80 - strlen($suffix)).$suffix;
    }
}
