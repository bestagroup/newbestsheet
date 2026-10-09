<?php

namespace App\Services;

use App\Models\MediaFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ArchiveAccessService
{
    public function scope(Builder $query, User $user): Builder
    {
        $projects = app(InvestmentWorkflowAccessService::class)->scopeWorkflowProjects(Project::query(), $user)->select('projects.id');

        return $query->where(function ($q) use ($projects, $user): void {
            $q->whereIn('media_files.project_id', $projects)->orWhere(function ($q) use ($user): void {
                $q->whereNull('media_files.project_id');
                if (! $user->hasRole(['superadmin', 'manager', 'administrative_support_management'])) {
                    $q->where('media_files.user_id', $user->id);
                }
            });
        });
    }

    public function allows(MediaFile $media, User $user): bool
    {
        return $this->scope(MediaFile::withTrashed(), $user)->whereKey($media->id)->exists();
    }
}
