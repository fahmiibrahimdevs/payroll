<?php

namespace App\Enums;

enum CashAdvancePaymentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu ACC',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'badge badge-warning',
            self::APPROVED => 'badge badge-info',
            self::REJECTED => 'badge badge-danger',
        };
    }
}
