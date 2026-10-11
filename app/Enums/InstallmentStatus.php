<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case PENDING = 'pending';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case WAIVED = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Belum Dibayar',
            self::PARTIALLY_PAID => 'Dibayar Sebagian',
            self::PAID => 'Lunas',
            self::WAIVED => 'Dihapuskan / Dialihkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'badge badge-warning',
            self::PARTIALLY_PAID => 'badge badge-info',
            self::PAID => 'badge badge-success',
            self::WAIVED => 'badge badge-secondary',
        };
    }
}
