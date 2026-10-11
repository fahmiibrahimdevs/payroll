<?php

namespace App\Enums;

enum PayrollLineCategory: string
{
    case BASIC = 'basic';
    case ALLOWANCE = 'allowance';
    case DEDUCTION = 'deduction';
    case CASH_ADVANCE = 'cash_advance';
    case BONUS = 'bonus';
    case PENALTY = 'penalty';
    case TAX = 'tax';
    case BPJS = 'bpjs';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BASIC => 'Gaji Pokok',
            self::ALLOWANCE => 'Tunjangan',
            self::DEDUCTION => 'Potongan',
            self::CASH_ADVANCE => 'Potongan Kasbon',
            self::BONUS => 'Bonus / Lembur',
            self::PENALTY => 'Denda / Absensi',
            self::TAX => 'Pajak PPh 21',
            self::BPJS => 'Iuran BPJS',
            self::OTHER => 'Lain-lain',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::BASIC => 'badge badge-primary',
            self::ALLOWANCE => 'badge badge-success',
            self::DEDUCTION => 'badge badge-danger',
            self::CASH_ADVANCE => 'badge badge-warning',
            self::BONUS => 'badge badge-info',
            self::PENALTY => 'badge badge-danger',
            self::TAX => 'badge badge-danger',
            self::BPJS => 'badge badge-secondary',
            self::OTHER => 'badge badge-secondary',
        };
    }
}
