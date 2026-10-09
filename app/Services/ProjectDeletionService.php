<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

final class ProjectDeletionService
{
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $project = Project::query()->lockForUpdate()->findOrFail($id);
            foreach (['finances', 'minutes', 'mediaFiles', 'projectSteps', 'contracts', 'financialStatements', 'quarterlyPerformanceReports', 'commitments', 'kpis'] as $relation) {
                abort_if($project->{$relation}()->exists(), 409, 'پرونده دارای سوابق است و قابل حذف نیست.');
            }
            // New registers also have restrictive foreign keys.
            app(BusinessAudit::class)->record($project, 'deleted', $project->getAttributes());
            $project->delete();
        }, 3);
    }
}
