<?php

namespace App\Enums;

enum SalaryComponentType: string
{
    case ALLOWANCE = 'allowance';
    case DEDUCTION = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'Tunjangan',
            self::DEDUCTION => 'Potongan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'badge badge-success',
            self::DEDUCTION => 'badge badge-danger',
        };
    }

    public function headerBgClass(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'bg-green-50',
            self::DEDUCTION => 'bg-red-50',
        };
    }

    public function headerTextClass(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'text-green-700',
            self::DEDUCTION => 'text-red-700',
        };
    }
}
