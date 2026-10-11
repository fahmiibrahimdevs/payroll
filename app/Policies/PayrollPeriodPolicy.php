<?php

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAccess($user, 'payrolls-read', 'admin', 'direktur');
    }

    public function view(User $user, PayrollPeriod $period): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasAccess($user, 'payrolls-create', 'admin');
    }

    public function update(User $user, PayrollPeriod $period): bool
    {
        $canUpdateStatus = $period->status->isEditable() || $period->items()->where('approval_status', 'rejected')->exists();

        return $this->hasAccess($user, 'payrolls-update', 'admin', 'direktur') && $canUpdateStatus;
    }

    public function delete(User $user, PayrollPeriod $period): bool
    {
        return $this->hasAccess($user, 'payrolls-delete', 'admin') && $period->status->isEditable();
    }

    public function submit(User $user, PayrollPeriod $period): bool
    {
        return $this->hasAccess($user, 'payrolls-update', 'admin') && $period->status->isEditable();
    }

    public function approve(User $user, PayrollPeriod $period): bool
    {
        return $user->hasRole('direktur');
    }

    public function reject(User $user, PayrollPeriod $period): bool
    {
        return $user->hasRole('direktur');
    }

    public function recordPayment(User $user, PayrollPeriod $period): bool
    {
        return $this->hasAccess($user, 'payrolls-update', 'admin');
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
