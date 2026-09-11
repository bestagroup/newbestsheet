<?php

namespace App\Services;

use App\Enums\InvestmentRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class OperationalRecipientResolver
{
    /** @return Collection<int, User> */
    public function investorExperts(Project $project): Collection
    {
        return $project->assignments()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('title', InvestmentRole::Expert->value))
            ->with('user:id,name,phone,email,status')
            ->get()
            ->pluck('user')
            ->filter(fn ($user) => $user instanceof User && (is_null($user->status) || (int) $user->status === 4))
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, User> */
    public function investeeUsers(Project $project): Collection
    {
        $users = collect();

        if ($project->relationLoaded('user') ? $project->user : $project->user()->first()) {
            $users->push($project->user);
        }

        $company = $project->relationLoaded('company') ? $project->company : $project->company()->first();
        if ($company?->user_id) {
            $companyUser = User::query()->find($company->user_id);
            if ($companyUser) {
                $users->push($companyUser);
            }
        }

        return $users
            ->filter(fn ($user) => $user instanceof User && (is_null($user->status) || (int) $user->status === 4))
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, User> */
    public function escalationManagers(): Collection
    {
        return User::query()
            ->where('status', 4)
            ->whereHas('roles', fn ($query) => $query->whereIn('title', [
                InvestmentRole::SuperAdmin->value,
                InvestmentRole::Manager->value,
            ]))
            ->get()
            ->unique('id')
            ->values();
    }

    /** @return array<int, string> */
    public function investeePhones(Project $project): array
    {
        return $this->investeeUsers($project)
            ->pluck('phone')
            ->filter()
            ->push($project->ceo_phone)
            ->filter()
            ->map(fn ($phone) => (string) $phone)
            ->unique()
            ->values()
            ->all();
    }
}
