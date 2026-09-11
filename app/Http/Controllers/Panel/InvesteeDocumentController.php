<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\InvesteeDocumentRequest;
use App\Models\InvestmentStepDocumentRequirement;
use App\Models\MediaFile;
use App\Models\ProjectStageInstance;
use App\Services\ActivityLogService;
use App\Services\InvesteePortalService;
use App\Services\ProjectMediaService;
use App\Services\ProjectStageProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvesteeDocumentController extends Controller
{
    public function __construct(
        private readonly InvesteePortalService $portal,
        private readonly ProjectMediaService $mediaService,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function store(InvesteeDocumentRequest $request): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        app(ProjectStageProvisioningService::class)->ensureForProject($project);
        $requirementId = $request->validated('document_requirement_id');
        $requestedSubjectId = $request->validated('subject_id');

        if ($requirementId) {
            $requirement = InvestmentStepDocumentRequirement::query()
                ->with('investStep')
                ->find($requirementId);
            $step = $requirement?->investStep;
            $subjectId = (int) $requirement?->subject_file_id;
            $stage = $step
                ? ProjectStageInstance::query()
                    ->where('project_id', $project->getKey())
                    ->where('invest_step_id', $step->getKey())
                    ->first()
                : null;
            $requestMatchesRequirement = $requestedSubjectId === null
                || (int) $requestedSubjectId === $subjectId;
            $stageCanAcceptDocuments = $stage
                && ! $project->is_rejected
                && $stage->status->value !== 'rejected';
        } else {
            // Older clients send only a subject and remain limited to the current editable stage.
            $subjectId = (int) $requestedSubjectId;
            $step = $project->currentStep()->first();
            $stage = $step
                ? ProjectStageInstance::query()
                    ->where('project_id', $project->getKey())
                    ->where('invest_step_id', $step->getKey())
                    ->first()
                : null;
            $requirement = $step
                ? InvestmentStepDocumentRequirement::query()
                    ->where('invest_step_id', $step->getKey())
                    ->where('subject_file_id', $subjectId)
                    ->first()
                : null;
            $requestMatchesRequirement = true;
            $stageCanAcceptDocuments = ! $project->is_rejected
                && $stage
                && in_array($stage->status->value, [
                    'awaiting_assignment',
                    'awaiting_documents',
                    'under_review',
                ], true);
        }

        if (! $requirement || ! $stageCanAcceptDocuments || ! $requestMatchesRequirement) {
            return response()->json([
                'message' => 'این مدرک برای یکی از مراحل قابل‌دسترسی پرونده تعریف نشده است.',
                'errors' => [
                    'document_requirement_id' => ['این مدرک برای یکی از مراحل قابل‌دسترسی پرونده تعریف نشده است.'],
                ],
            ], 422);
        }

        $uploadedFiles = $request->file('files', []);
        $uploadedFiles = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
        if ($request->hasFile('file')) {
            array_unshift($uploadedFiles, $request->file('file'));
        }

        if (count($uploadedFiles) > 20) {
            return response()->json([
                'message' => 'در هر بار حداکثر ۲۰ فایل قابل بارگذاری است.',
                'errors' => ['files' => ['در هر بار حداکثر ۲۰ فایل قابل بارگذاری است.']],
            ], 422);
        }

        $mediaFiles = collect($uploadedFiles)
            ->map(fn ($file) => $this->mediaService->store(
                $file,
                $project,
                $subjectId,
                (int) $request->user()->getKey(),
                $requirement,
                $stage
            ));

        $this->activityLog->record(
            'investee.document_uploaded',
            sprintf(
                '%d فایل برای پروژه #%d و مدرک مرحله #%d بارگذاری شد.',
                $mediaFiles->count(),
                $project->getKey(),
                $requirement->getKey()
            ),
            (int) $request->user()->getKey()
        );

        return response()->json([
            'success' => true,
            'message' => $mediaFiles->count() > 1
                ? $mediaFiles->count().' فایل با موفقیت بارگذاری شد.'
                : 'فایل با موفقیت بارگذاری شد.',
            'uploaded_count' => $mediaFiles->count(),
            'file_ids' => $mediaFiles->pluck('id')->all(),
            // Legacy response keys are retained for consumers that upload one file.
            'file_id' => $mediaFiles->first()?->getKey(),
            'file_path' => $mediaFiles->first()?->url,
            'download_url' => $mediaFiles->first()?->url,
        ]);
    }

    public function destroy(Request $request, int $media): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        $record = MediaFile::query()
            ->where('project_id', $project->getKey())
            ->where('user_id', $request->user()->getKey())
            ->findOrFail($media);

        if ((int) $record->status === 4) {
            return response()->json([
                'message' => 'سند تأییدشده قابل حذف نیست.',
                'errors' => [
                    'file' => ['سند تأییدشده قابل حذف نیست.'],
                ],
            ], 422);
        }

        $recordId = (int) $record->getKey();
        $this->mediaService->delete($record);

        $this->activityLog->record(
            'investee.document_deleted',
            sprintf('فایل #%d پروژه #%d توسط نماینده سرمایه‌پذیر حذف شد.', $recordId, $project->getKey()),
            (int) $request->user()->getKey()
        );

        return response()->json(['success' => true, 'message' => 'فایل حذف شد.']);
    }
}
