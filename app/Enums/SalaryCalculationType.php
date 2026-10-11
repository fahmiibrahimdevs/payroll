<?php

namespace App\Enums;

enum SalaryCalculationType: string
{
    case NOMINAL = 'nominal';
    case PERCENTAGE_OF_BASIC_SALARY = 'persen_gaji_pokok';

    public function label(): string
    {
        return match ($this) {
            self::NOMINAL => 'Nominal (Rp)',
            self::PERCENTAGE_OF_BASIC_SALARY => 'Persen Gaji Pokok (%)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::NOMINAL => 'badge badge-light border',
            self::PERCENTAGE_OF_BASIC_SALARY => 'badge badge-warning',
        };
    }
}
