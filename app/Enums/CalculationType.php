<?php

namespace App\Enums;

enum CalculationType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Nominal Tetap',
            self::PERCENTAGE => 'Persentase Gaji Pokok',
            self::MANUAL => 'Input Manual',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::FIXED => 'badge badge-primary',
            self::PERCENTAGE => 'badge badge-info',
            self::MANUAL => 'badge badge-warning',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FIXED => 'Komponen bernilai nominal rupiah tetap setiap bulannya.',
            self::PERCENTAGE => 'Nilai dihitung otomatis berdasarkan persentase dari gaji pokok.',
            self::MANUAL => 'Besaran nominal diinput manual saat pemrosesan payroll.',
        };
    }
}
