<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case SALARY_DEDUCTION = 'salary_deduction';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case EWALLET = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::SALARY_DEDUCTION => 'Potong Gaji',
            self::BANK_TRANSFER => 'Transfer Bank / E-Wallet',
            self::CASH => 'Tunai / Cash',
            self::EWALLET => 'Transfer Bank / E-Wallet',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SALARY_DEDUCTION => 'badge badge-primary',
            self::BANK_TRANSFER => 'badge badge-info',
            self::CASH => 'badge badge-success',
            self::EWALLET => 'badge badge-warning text-dark',
        };
    }
}
