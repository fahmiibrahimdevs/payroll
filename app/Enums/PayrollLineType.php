<?php

namespace App\Enums;

enum PayrollLineType: string
{
    case ALLOWANCE = 'allowance';
    case DEDUCTION = 'deduction';
    case COMPANY_CONTRIBUTION = 'company_contribution';

    public function label(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'Tunjangan (+)',
            self::DEDUCTION => 'Potongan (-)',
            self::COMPANY_CONTRIBUTION => 'Tanggungan Perusahaan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'badge badge-success',
            self::DEDUCTION => 'badge badge-danger',
            self::COMPANY_CONTRIBUTION => 'badge badge-info',
        };
    }
}
