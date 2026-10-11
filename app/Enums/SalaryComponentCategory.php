<?php

namespace App\Enums;

enum SalaryComponentCategory: string
{
    case FIXED_ALLOWANCE = 'pendapatan_tetap';
    case VARIABLE_ALLOWANCE = 'tunjangan_tidak_tetap';
    case DEDUCTION = 'potongan';

    public function label(): string
    {
        return match ($this) {
            self::FIXED_ALLOWANCE => 'Pendapatan Tetap',
            self::VARIABLE_ALLOWANCE => 'Tunjangan Tidak Tetap',
            self::DEDUCTION => 'Potongan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::FIXED_ALLOWANCE => 'badge badge-primary',
            self::VARIABLE_ALLOWANCE => 'badge badge-info',
            self::DEDUCTION => 'badge badge-danger',
        };
    }

    public function isAllowance(): bool
    {
        return $this === self::FIXED_ALLOWANCE || $this === self::VARIABLE_ALLOWANCE;
    }

    public function isDeduction(): bool
    {
        return $this === self::DEDUCTION;
    }

    public function type(): SalaryComponentType
    {
        return $this->isAllowance() ? SalaryComponentType::ALLOWANCE : SalaryComponentType::DEDUCTION;
    }
}
