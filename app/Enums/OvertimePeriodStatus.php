<?php

namespace App\Enums;

enum OvertimePeriodStatus: string
{
    case DRAFT = 'draft';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Belum Dibayar',
            self::PAID => 'Sudah Dibayar',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'badge badge-warning',
            self::PAID => 'badge badge-success',
            self::CANCELLED => 'badge badge-secondary',
        };
    }

    public function badgeStyle(): string
    {
        return match ($this) {
            self::DRAFT => 'background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;',
            self::PAID => 'background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;',
            self::CANCELLED => 'background-color: #f8fafc; color: #64748b; border: 1px solid #cbd5e1;',
        };
    }
}
