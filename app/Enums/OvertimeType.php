<?php

namespace App\Enums;

enum OvertimeType: string
{
    case HOURLY = 'hourly';
    case DAILY = 'daily';

    public function label(): string
    {
        return match ($this) {
            self::HOURLY => 'Per Jam',
            self::DAILY => 'Harian (Seharian)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::HOURLY => 'Per Jam',
            self::DAILY => 'Harian',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::HOURLY => 'far fa-clock',
            self::DAILY => 'fas fa-calendar-day',
        };
    }

    public function badgeStyle(): string
    {
        return match ($this) {
            self::HOURLY => 'background-color: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd;',
            self::DAILY => 'background-color: #fefce8; color: #a16207; border: 1px solid #fef08a;',
        };
    }

    public static function options(): array
    {
        return [
            self::HOURLY->value => self::HOURLY->label(),
            self::DAILY->value => self::DAILY->label(),
        ];
    }
}
