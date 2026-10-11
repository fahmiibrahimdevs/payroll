<?php

namespace App\Policies;

use App\Models\CashAdvance;
use App\Models\User;

class CashAdvancePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAccess($user, 'cash_advances-read', 'admin', 'direktur');
    }

    public function view(User $user, CashAdvance $cashAdvance): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('direktur')) {
            return false;
        }

        return $this->hasAccess($user, 'cash_advances-create', 'admin');
    }

    public function update(User $user, CashAdvance $cashAdvance): bool
    {
        if ($user->hasRole('direktur')) {
            return false;
        }

        return $this->hasAccess($user, 'cash_advances-update', 'admin');
    }

    public function delete(User $user, CashAdvance $cashAdvance): bool
    {
        if ($user->hasRole('direktur')) {
            return false;
        }

        return $this->hasAccess($user, 'cash_advances-delete', 'admin');
    }

    public function approve(User $user, CashAdvance $cashAdvance): bool
    {
        return $user->hasRole('direktur');
    }

    public function reject(User $user, CashAdvance $cashAdvance): bool
    {
        return $user->hasRole('direktur');
    }

    public function recordPayment(User $user, CashAdvance $cashAdvance): bool
    {
        return $this->hasAccess($user, 'cash_advances-create', 'admin');
    }

    public function recordEarlySettlement(User $user): bool
    {
        return $this->hasAccess($user, 'cash_advances-create', 'admin');
    }

    private function hasAccess(User $user, string $permission, string ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return $user->hasPermission($permission);
    }
}
