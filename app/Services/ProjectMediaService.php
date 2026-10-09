<?php

namespace App\Services;

use App\Models\InvestmentStepDocumentRequirement;
use App\Models\MediaFile;
use App\Models\Project;
use App\Models\ProjectStageInstance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectMediaService
{
    public function store(
        UploadedFile $file,
        ?Project $project,
        ?int $subjectId,
        int $userId,
        ?InvestmentStepDocumentRequirement $requirement = null,
        ?ProjectStageInstance $stage = null,
    ): MediaFile {
        $originalName = $file->getClientOriginalName();
        $originalName = preg_replace('/[\x00-\x1F\x7F]/u', '', basename($originalName)) ?: 'document';
        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        $extension = preg_match('/^[a-z0-9]{1,10}$/', $extension) === 1 ? $extension : 'bin';
        $size = (int) $file->getSize();
        $mime = (string) $file->getMimeType();
        $typeDirectory = $this->typeDirectory($mime);
        $fileName = (string) Str::uuid().($extension !== '' ? '.'.$extension : '');
        $disk = (string) config('investment.documents.disk', 'investment_documents');
        if ($project) {
            if ($requirement) {
                $requirement->loadMissing('investStep');
                $step = $requirement->investStep;

                if (! $step || (int) $requirement->subject_file_id !== (int) $subjectId) {
                    throw new \InvalidArgumentException('Document requirement does not match the uploaded subject.');
                }

                $stage ??= app(ProjectStageProvisioningService::class)->stageFor($project, $step);
                if ((int) $stage->project_id !== (int) $project->getKey()
                    || (int) $stage->invest_step_id !== (int) $step->getKey()) {
                    throw new \InvalidArgumentException('Document stage does not belong to the selected project requirement.');
                }
            } else {
                $step = $project->currentStep()->first();
                if ($step) {
                    $stage = app(ProjectStageProvisioningService::class)->stageFor($project, $step);
                    $requirement = InvestmentStepDocumentRequirement::query()
                        ->where('invest_step_id', $step->getKey())
                        ->where('subject_file_id', $subjectId)
                        ->first();
                }
            }
        }

        $directory = $project
            ? $project->getKey().'/'.($stage?->getKey() ?? 'unassigned').'/'.$typeDirectory
            : 'unassigned/'.$typeDirectory;
        $path = $file->storeAs($directory, $fileName, $disk);
        $sha256 = hash_file('sha256', $file->getRealPath()) ?: null;
        $scanStatus = config('investment.documents.antivirus_enabled') ? 'pending' : 'clean';

        try {
            return MediaFile::query()->create([
                'subject_id' => $subjectId,
                'document_requirement_id' => $requirement?->getKey(),
                'project_stage_instance_id' => $stage?->getKey(),
                'name' => $fileName,
                'original_name' => $originalName,
                'type' => rtrim($typeDirectory, 's'),
                'file_path' => $path,
                'disk' => $disk,
                'size' => $size,
                'sha256' => $sha256,
                'scan_status' => $scanStatus,
                'scanned_at' => $scanStatus === 'clean' ? now() : null,
                'uploaded_at' => now(),
                'project_id' => $project?->getKey(),
                'company_id' => $project?->company_id,
                'mime' => $mime,
                'user_id' => $userId,
                'status' => 0,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }

    public function delete(MediaFile $media): void
    {
        $this->assertNotReferenced($media);
        $media->delete();
    }

    public function assertNotReferenced(MediaFile $media): void
    {
        if (\App\Models\PortfolioMeeting::query()->where('media_file_id', $media->id)->exists()
            || \App\Models\ExternalLetter::query()->where('media_file_id', $media->id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'file' => 'این فایل پیوست یک نامه یا صورتجلسه است و حذف یا انتقال آن مجاز نیست.',
            ]);
        }
    }

    private function typeDirectory(string $mime): string
    {
        return match (true) {
            Str::contains($mime, 'image') => 'images',
            Str::contains($mime, 'video') => 'videos',
            Str::contains($mime, 'audio') => 'audios',
            $mime === 'application/pdf' => 'documents',
            Str::contains($mime, 'msword'), Str::contains($mime, 'officedocument.wordprocessingml') => 'documents',
            Str::contains($mime, 'ms-excel'), Str::contains($mime, 'officedocument.spreadsheetml') => 'spreadsheets',
            Str::contains($mime, 'ms-powerpoint'), Str::contains($mime, 'officedocument.presentationml') => 'presentations',
            Str::contains($mime, 'zip'), $mime === 'application/zip', Str::contains($mime, 'rar') => 'archives',
            default => 'others',
        };
    }
}
