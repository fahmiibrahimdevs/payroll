<?php

namespace App\Enums;

enum TaxMethod: string
{
    case GROSS = 'gross';
    case GROSS_UP = 'gross_up';
    case NET = 'net';

    public function label(): string
    {
        return match ($this) {
            self::GROSS => 'Gross (Dipotong dari Gaji)',
            self::GROSS_UP => 'Gross-Up (Ditanggung Perusahaan via Tunjangan Pajak)',
            self::NET => 'Nett (Ditanggung Perusahaan Langsung)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::GROSS => 'Gross',
            self::GROSS_UP => 'Gross-Up',
            self::NET => 'Nett',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::GROSS => 'badge badge-primary',
            self::GROSS_UP => 'badge badge-info',
            self::NET => 'badge badge-secondary',
        };
    }
}
