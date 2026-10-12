<?php

namespace App\Policies;

use App\Models\CashAdvancePayment;
use App\Models\User;

class CashAdvancePaymentPolicy
{
    public function view(User $user, CashAdvancePayment $payment): bool
    {
        return $user->hasRole('admin') || $user->hasRole('direktur') || $user->hasPermission('cash_advances-read');
    }

    public function approve(User $user, CashAdvancePayment $payment): bool
    {
        return $user->hasRole('direktur');
    }

    public function reject(User $user, CashAdvancePayment $payment): bool
    {
        return $user->hasRole('direktur');
    }
}
