<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case TETAP = 'tetap';
    case KONTRAK = 'kontrak';
    case MAGANG = 'magang';

    public function label(): string
    {
        return match ($this) {
            self::TETAP => 'Karyawan Tetap',
            self::KONTRAK => 'Kontrak',
            self::MAGANG => 'Magang',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::TETAP => 'badge badge-primary',
            self::KONTRAK => 'badge badge-info',
            self::MAGANG => 'badge badge-warning',
        };
    }

    public function textColor(): string
    {
        return match ($this) {
            self::TETAP => '#0b52aa',
            self::KONTRAK => '#0284c7',
            self::MAGANG => '#d97706',
        };
    }
}
