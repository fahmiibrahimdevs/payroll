<?php

namespace App\Enums;

enum CashAdvanceStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ACTIVE = 'active';
    case PAID = 'paid';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Persetujuan',
            self::APPROVED => 'Disetujui',
            self::ACTIVE => 'Sedang Berjalan',
            self::PAID => 'Lunas',
            self::REJECTED => 'Ditolak',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'badge badge-warning',
            self::APPROVED => 'badge badge-info',
            self::ACTIVE => 'badge badge-primary',
            self::PAID => 'badge badge-success',
            self::REJECTED => 'badge badge-danger',
            self::CANCELLED => 'badge badge-secondary',
        };
    }
}
