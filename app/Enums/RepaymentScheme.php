<?php

namespace App\Enums;

enum RepaymentScheme: string
{
    case SALARY_SINGLE = 'salary_single';
    case SALARY_INSTALLMENT = 'salary_installment';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::SALARY_SINGLE => 'Potong Gaji Sekaligus',
            self::SALARY_INSTALLMENT => 'Cicilan Potong Gaji',
            self::MANUAL => 'Bayar Manual di Luar Gaji',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::SALARY_SINGLE => 'Potong Gaji 1x',
            self::SALARY_INSTALLMENT => 'Cicilan Gaji',
            self::MANUAL => 'Bayar Manual',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SALARY_SINGLE => 'badge badge-primary',
            self::SALARY_INSTALLMENT => 'badge badge-info',
            self::MANUAL => 'badge badge-warning',
        };
    }
}
