<?php

namespace App\Services;

use App\Enums\InvestmentRole;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class InvestmentWorkflowAccessService
{
    public function canManageAssignments(User $user): bool
    {
        return $user->hasRole([
            InvestmentRole::SuperAdmin->value,
            InvestmentRole::Manager->value,
            InvestmentRole::SeniorInvestmentExpert->value,
        ]);
    }

    public function canManageWorkflow(User $user): bool
    {
        return $this->canManageAssignments($user) || $user->hasRole([
            InvestmentRole::InvestmentManagement->value,
            InvestmentRole::PortfolioAffairsManagement->value,
        ]);
    }

    public function canManageInvestmentDepartment(User $user): bool
    {
        return $this->canManageAssignments($user)
            || $user->hasRole(InvestmentRole::InvestmentManagement->value);
    }

    public function canManagePortfolioDepartment(User $user): bool
    {
        return $this->canManageAssignments($user)
            || $user->hasRole(InvestmentRole::PortfolioAffairsManagement->value);
    }

    public function canManageStage(User $user, int $stepId): bool
    {
        if ($this->canManageAssignments($user)) {
            return true;
        }

        if ($stepId < Project::PORTFOLIO_MINIMUM_STEP) {
            return $user->hasRole(InvestmentRole::InvestmentManagement->value);
        }

        return $user->hasRole(InvestmentRole::PortfolioAffairsManagement->value);
    }

    /**
     * @return array{assignment: ProjectAssignment|null, role: Role}|null
     */
    public function decisionContext(User $user, int $projectId, int $stepId): ?array
    {
        $user->loadMissing('roles');

        $activeRoles = $user->roles->filter(
            static fn (Role $role): bool => is_null($role->status) || (int) $role->status === 4
        );

        foreach ([InvestmentRole::SuperAdmin, InvestmentRole::Manager] as $globalRole) {
            /** @var Role|null $role */
            $role = $activeRoles->firstWhere('title', $globalRole->value);
            if ($role) {
                return ['assignment' => null, 'role' => $role];
            }
        }

        foreach ([
            InvestmentRole::SeniorInvestmentExpert,
            InvestmentRole::InvestmentManagement,
            InvestmentRole::PortfolioAffairsManagement,
        ] as $managementRole) {
            /** @var Role|null $role */
            $role = $activeRoles->firstWhere('title', $managementRole->value);
            if ($role && $this->canManageStage($user, $stepId)) {
                return ['assignment' => null, 'role' => $role];
            }
        }

        $decisionRoles = $activeRoles
            ->filter(static fn (Role $role): bool => in_array($role->title, InvestmentRole::decisionRoleSlugs(), true))
            ->keyBy('id');

        if ($decisionRoles->isEmpty()) {
            return null;
        }

        /** @var ProjectAssignment|null $assignment */
        $assignment = ProjectAssignment::query()
            ->with('role')
            ->where('project_id', $projectId)
            ->where('user_id', $user->getKey())
            ->whereIn('role_id', $decisionRoles->keys())
            ->where('is_active', true)
            ->where('invest_step_id', $stepId)
            ->first();

        if (! $assignment || ! $assignment->role) {
            return null;
        }

        return ['assignment' => $assignment, 'role' => $assignment->role];
    }

    public function canViewProject(User $user, int $projectId): bool
    {
        if ($this->canManageAssignments($user)) {
            return true;
        }

        $projectStep = Project::query()->whereKey($projectId)->value('invest_step');
        if ($projectStep !== null && $this->canManageStage($user, (int) $projectStep)) {
            return true;
        }

        $user->loadMissing('roles');
        $reviewRoleIds = $user->roles
            ->filter(static fn (Role $role): bool => (is_null($role->status) || (int) $role->status === 4)
                && in_array($role->title, InvestmentRole::assignableReviewRoleSlugs(), true)
            )
            ->pluck('id');

        if ($reviewRoleIds->isEmpty()) {
            return false;
        }

        return ProjectAssignment::query()
            ->where('project_id', $projectId)
            ->where('user_id', $user->getKey())
            ->whereIn('role_id', $reviewRoleIds)
            ->where('is_active', true)
            ->exists();
    }

    public function canTransition(User $user, int $projectId, int $stepId): bool
    {
        return $this->decisionContext($user, $projectId, $stepId) !== null;
    }

    public function canReviewKpis(User $user, int $projectId): bool
    {
        if ($this->canManagePortfolioDepartment($user)) {
            return true;
        }

        $user->loadMissing('roles');
        $reviewRoleIds = $user->roles
            ->filter(static fn (Role $role): bool => (is_null($role->status) || (int) $role->status === 4)
                && in_array($role->title, [
                    InvestmentRole::Expert->value,
                    InvestmentRole::Evaluator->value,
                ], true)
            )
            ->pluck('id');

        if ($reviewRoleIds->isEmpty()) {
            return false;
        }

        return ProjectAssignment::query()
            ->where('project_id', $projectId)
            ->where('user_id', $user->getKey())
            ->whereIn('role_id', $reviewRoleIds)
            ->where('is_active', true)
            ->exists();
    }

    public function scopeWorkflowProjects(
        Builder|EloquentBuilder $query,
        User $user,
        string $projectAlias = 'projects'
    ): Builder|EloquentBuilder {
        if ($this->canManageAssignments($user)) {
            return $query;
        }

        $hasInvestment = $user->hasRole(InvestmentRole::InvestmentManagement->value);
        $hasPortfolio = $user->hasRole(InvestmentRole::PortfolioAffairsManagement->value);

        if ($hasInvestment && $hasPortfolio) {
            return $query;
        }

        if ($hasInvestment) {
            return $query->where(
                $projectAlias.'.invest_step',
                '<',
                Project::PORTFOLIO_MINIMUM_STEP
            );
        }

        if ($hasPortfolio) {
            return $query->where(
                $projectAlias.'.invest_step',
                '>=',
                Project::PORTFOLIO_MINIMUM_STEP
            );
        }

        return $query->whereExists(function ($sub) use ($user, $projectAlias): void {
            $sub->selectRaw('1')
                ->from('project_assignments as access_pa')
                ->join('roles as access_role', 'access_role.id', '=', 'access_pa.role_id')
                ->whereColumn('access_pa.project_id', $projectAlias.'.id')
                ->where('access_pa.user_id', $user->getKey())
                ->where('access_pa.is_active', true)
                ->whereIn('access_role.title', InvestmentRole::assignableReviewRoleSlugs())
                ->where(function ($roleStatus): void {
                    $roleStatus->whereNull('access_role.status')->orWhere('access_role.status', 4);
                });
        });
    }
}
