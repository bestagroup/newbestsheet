<?php

namespace App\Services;

use App\Models\ExternalLetter;
use App\Models\MediaFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class EnterpriseRecordAccess
{
    public function projects(User $user): Builder
    {
        return app(InvestmentWorkflowAccessService::class)->scopeWorkflowProjects(Project::query(), $user);
    }

    public function assertProject(User $user, int $id): void
    {
        abort_unless($this->projects($user)->whereKey($id)->exists(), 403);
    }

    public function letters(User $user): Builder
    {
        $query = ExternalLetter::query();
        if (! $user->hasRole(['superadmin', 'manager'])) {
            $query->where(function ($q) use ($user): void {
                $q->where('created_by', $user->id)->orWhere('assigned_to', $user->id);
            });
        }

        return $query;
    }

    public function attachments(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return app(ArchiveAccessService::class)->scope(MediaFile::query(), $user)
            ->where('scan_status', 'clean')->with('project:id,title')->latest('id')
            ->get(['id', 'project_id', 'original_name', 'name']);
    }

    public function assertAttachment(?int $mediaId, ?int $projectId, User $user): void
    {
        if (! $mediaId) {
            return;
        }
        $media = MediaFile::query()->findOrFail($mediaId);
        abort_unless(app(ArchiveAccessService::class)->allows($media, $user), 403);
        if ((int) $media->project_id !== (int) $projectId) {
            throw ValidationException::withMessages(['media_file_id' => 'پیوست باید متعلق به همین پرونده باشد.']);
        }
        if ($projectId) {
            $this->assertProject($user, $projectId);
        } else {
            abort_unless($user->can('can-access', ['filemanager', 'view']), 403);
        }
        if ($media->scan_status !== 'clean') {
            throw ValidationException::withMessages(['media_file_id' => 'پیوست هنوز مجوز استفاده ندارد.']);
        }
    }
}
