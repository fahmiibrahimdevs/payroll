<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;

class DashboardService
{
    /**
     * @return array{total_users: int, active_users: int, inactive_users: int, total_roles: int}
     */
    public function getStatistics(): array
    {
        $userCounts = User::query()
            ->selectRaw('COUNT(*) as total_users')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users')
            ->first();

        return [
            'total_users' => (int) ($userCounts?->total_users ?? 0),
            'active_users' => (int) ($userCounts?->active_users ?? 0),
            'inactive_users' => (int) ($userCounts?->inactive_users ?? 0),
            'total_roles' => Role::query()->count(),
        ];
    }
}
