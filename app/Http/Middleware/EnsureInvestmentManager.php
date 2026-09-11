<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Services\InvestmentWorkflowAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInvestmentManager
{
    public function handle(Request $request, Closure $next, string $capability = 'legacy'): Response
    {
        $user = $request->user();
        $access = app(InvestmentWorkflowAccessService::class);

        if (! $user || ! $this->allowed($request, $access, $capability)) {
            abort(403);
        }

        return $next($request);
    }

    private function allowed(
        Request $request,
        InvestmentWorkflowAccessService $access,
        string $capability
    ): bool {
        $user = $request->user();

        if ($capability === 'legacy') {
            return $access->canManageAssignments($user);
        }

        if ($capability === 'workflow') {
            return $access->canManageWorkflow($user);
        }

        if ($capability === 'investment') {
            if (! $access->canManageInvestmentDepartment($user)) {
                return false;
            }

            $project = $this->routeProject($request);

            return ! $project instanceof Project
                || $access->canManageAssignments($user)
                || (int) $project->invest_step < Project::PORTFOLIO_MINIMUM_STEP;
        }

        if ($capability === 'portfolio') {
            if (! $access->canManagePortfolioDepartment($user)) {
                return false;
            }

            $project = $this->routeProject($request);

            return ! $project instanceof Project
                || $access->canManageAssignments($user)
                || (int) $project->invest_step >= Project::PORTFOLIO_MINIMUM_STEP;
        }

        if ($capability === 'stage') {
            $stepId = $request->integer('invest_step_id');
            $assignment = $request->route('assignment');
            $project = $this->routeProject($request);

            if (! $stepId && $assignment instanceof ProjectAssignment) {
                $stepId = (int) $assignment->invest_step_id;
            }
            if (! $stepId && $project instanceof Project) {
                $stepId = (int) $project->invest_step;
            }

            return $stepId > 0 && $access->canManageStage($user, $stepId);
        }

        return false;
    }

    private function routeProject(Request $request): ?Project
    {
        $project = $request->route('project');

        if ($project instanceof Project) {
            return $project;
        }

        if (is_numeric($project)) {
            return Project::query()->select('id', 'invest_step')->find((int) $project);
        }

        return null;
    }
}
