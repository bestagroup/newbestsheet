<?php

namespace App\Services;

use App\Enums\InvestmentRole;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ActivityLogVisibilityService
{
    public function scopeForViewer(Builder $query, User $viewer, ?Collection $visibleProjectIds = null): Builder
    {
        if ($this->hasGlobalActivityAccess($viewer)) {
            return $query;
        }

        if (! $this->showsTeamActivity($viewer)) {
            return $query->where('user_id', $viewer->getKey());
        }

        return $query->whereIn('user_id', $this->teamMemberIds($viewer, $visibleProjectIds));
    }

    public function showsTeamActivity(User $viewer): bool
    {
        return $this->hasGlobalActivityAccess($viewer) || $viewer->hasRole([
            InvestmentRole::SeniorInvestmentExpert->value,
            InvestmentRole::InvestmentManagement->value,
            InvestmentRole::PortfolioAffairsManagement->value,
        ]);
    }

    private function hasGlobalActivityAccess(User $viewer): bool
    {
        return $viewer->hasRole([
            InvestmentRole::SuperAdmin->value,
            InvestmentRole::Manager->value,
            InvestmentRole::ExecutiveBoard->value,
        ]);
    }

    private function teamMemberIds(User $viewer, ?Collection $visibleProjectIds): Collection
    {
        $projectIds = $visibleProjectIds ?? app(OperationalAnalyticsService::class)->visibleProjectIds($viewer);

        $assignedUserIds = ProjectAssignment::query()
            ->where('is_active', true)
            ->whereIn('project_id', $projectIds)
            ->whereHas('role', function (Builder $query): void {
                $query->whereIn('title', InvestmentRole::assignableReviewRoleSlugs())
                    ->where(fn (Builder $status): Builder => $status->whereNull('status')->orWhere('status', 4));
            })
            ->pluck('user_id');

        $managementRole = $viewer->hasRole(InvestmentRole::InvestmentManagement->value)
            ? InvestmentRole::InvestmentManagement->value
            : ($viewer->hasRole(InvestmentRole::PortfolioAffairsManagement->value)
                ? InvestmentRole::PortfolioAffairsManagement->value
                : InvestmentRole::SeniorInvestmentExpert->value);

        $managementUserIds = User::query()
            ->whereHas('roles', function (Builder $query) use ($managementRole): void {
                $query->where('title', $managementRole)
                    ->where(fn (Builder $status): Builder => $status->whereNull('status')->orWhere('status', 4));
            })
            ->pluck('id');

        return $assignedUserIds
            ->merge($managementUserIds)
            ->push($viewer->getKey())
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }
}
