<?php

namespace App\Enums;

enum SalaryAdjustmentType: string
{
    case INITIAL = 'initial';
    case ANNUAL_ADJUSTMENT = 'annual_adjustment';
    case PERFORMANCE_REVIEW = 'performance_review';
    case PROMOTION = 'promotion';
    case PROBATION_PASSED = 'probation_passed';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL => 'Gaji Awal',
            self::ANNUAL_ADJUSTMENT => 'Penyesuaian Tahunan',
            self::PERFORMANCE_REVIEW => 'Kenaikan Kinerja',
            self::PROMOTION => 'Promosi Jabatan',
            self::PROBATION_PASSED => 'Lolos Masa Percobaan',
            self::OTHER => 'Penyesuaian Lainnya',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::INITIAL => 'badge badge-info',
            self::ANNUAL_ADJUSTMENT => 'badge badge-primary',
            self::PERFORMANCE_REVIEW => 'badge badge-success',
            self::PROMOTION => 'badge badge-warning',
            self::PROBATION_PASSED => 'badge badge-secondary',
            self::OTHER => 'badge badge-light border text-dark',
        };
    }
}
