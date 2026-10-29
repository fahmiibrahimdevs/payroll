<?php

namespace App\Enums;

enum OvertimeSubmissionSource: string
{
    case KARYAWAN = 'karyawan';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::KARYAWAN => 'Mandiri (Karyawan)',
            self::ADMIN => 'Diinput Admin',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::KARYAWAN => 'badge badge-light border',
            self::ADMIN => 'badge badge-primary',
        };
    }

    public function badgeStyle(): string
    {
        return match ($this) {
            self::KARYAWAN => 'background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;',
            self::ADMIN => 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;',
        };
    }
}
