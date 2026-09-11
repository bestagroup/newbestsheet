<?php

namespace App\Services;

use App\Enums\InvestmentRole;
use App\Models\Role;

class InvestmentRoleService
{
    public function resolve(InvestmentRole $role): Role
    {
        $query = Role::query()->where('title', $role->value);

        if ($role === InvestmentRole::InvesteeRepresentative) {
            $query->orWhere('title', 'CapitalCapable');
        }

        return $query->orderByRaw(
            'CASE WHEN title = ? THEN 0 ELSE 1 END',
            [$role->value]
        )->firstOrFail();
    }

    public function id(InvestmentRole $role): int
    {
        return (int) $this->resolve($role)->getKey();
    }

    public function isCoreRole(Role $role): bool
    {
        return in_array($role->title, array_map(
            static fn (InvestmentRole $item): string => $item->value,
            InvestmentRole::cases()
        ), true);
    }
}
