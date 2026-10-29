<?php

namespace App\Enums;

enum OvertimeStatus: string
{
    case PENDING_ADMIN = 'pending_admin';
    case PENDING_DIREKTUR = 'pending_direktur';
    case APPROVED = 'approved';
    case REJECTED_ADMIN = 'rejected_admin';
    case REJECTED_DIREKTUR = 'rejected_direktur';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_ADMIN => 'Verif Admin',
            self::PENDING_DIREKTUR => 'Approval Direktur',
            self::APPROVED => 'Disetujui',
            self::REJECTED_ADMIN => 'Ditolak Admin',
            self::REJECTED_DIREKTUR => 'Ditolak Direktur',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::PENDING_ADMIN => 'Menunggu Verifikasi Admin',
            self::PENDING_DIREKTUR => 'Menunggu Approval Direktur',
            self::APPROVED => 'Disetujui',
            self::REJECTED_ADMIN => 'Ditolak Admin',
            self::REJECTED_DIREKTUR => 'Ditolak Direktur',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING_ADMIN => 'badge badge-warning',
            self::PENDING_DIREKTUR => 'badge badge-info',
            self::APPROVED => 'badge badge-success',
            self::REJECTED_ADMIN => 'badge badge-danger',
            self::REJECTED_DIREKTUR => 'badge badge-danger',
        };
    }

    public function badgeStyle(): string
    {
        return match ($this) {
            self::PENDING_ADMIN => 'background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;',
            self::PENDING_DIREKTUR => 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;',
            self::APPROVED => 'background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;',
            self::REJECTED_ADMIN, self::REJECTED_DIREKTUR => 'background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING_ADMIN => 'fas fa-clock',
            self::PENDING_DIREKTUR => 'fas fa-hourglass-half',
            self::APPROVED => 'fas fa-check-circle',
            self::REJECTED_ADMIN, self::REJECTED_DIREKTUR => 'fas fa-times-circle',
        };
    }
}
