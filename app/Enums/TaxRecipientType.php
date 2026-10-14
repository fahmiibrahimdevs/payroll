<?php

namespace App\Enums;

enum TaxRecipientType: string
{
    case MonthlyEmployee = 'monthly_employee';
    case OutsideScope = 'outside_scope';

    public function label(): string
    {
        return match ($this) {
            self::MonthlyEmployee => 'Pegawai Tetap PPh 21 (Gaji Bulanan)',
            self::OutsideScope => 'Di Luar Cakupan Perhitungan Saat Ini',
        };
    }
}
