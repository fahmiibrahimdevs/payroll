<?php

namespace App\Enums;

enum PtkpStatus: string
{
    case TK_0 = 'TK/0';
    case TK_1 = 'TK/1';
    case TK_2 = 'TK/2';
    case TK_3 = 'TK/3';
    case K_0 = 'K/0';
    case K_1 = 'K/1';
    case K_2 = 'K/2';
    case K_3 = 'K/3';

    public function label(): string
    {
        return match ($this) {
            self::TK_0 => 'TK/0 (Tidak Kawin, 0 Tanggungan)',
            self::TK_1 => 'TK/1 (Tidak Kawin, 1 Tanggungan)',
            self::TK_2 => 'TK/2 (Tidak Kawin, 2 Tanggungan)',
            self::TK_3 => 'TK/3 (Tidak Kawin, 3 Tanggungan)',
            self::K_0 => 'K/0 (Kawin, 0 Tanggungan)',
            self::K_1 => 'K/1 (Kawin, 1 Tanggungan)',
            self::K_2 => 'K/2 (Kawin, 2 Tanggungan)',
            self::K_3 => 'K/3 (Kawin, 3 Tanggungan)',
        };
    }

    /**
     * Get clean text description without the status code prefix.
     */
    public function description(): string
    {
        return match ($this) {
            self::TK_0 => 'Tidak Kawin, 0 Tanggungan',
            self::TK_1 => 'Tidak Kawin, 1 Tanggungan',
            self::TK_2 => 'Tidak Kawin, 2 Tanggungan',
            self::TK_3 => 'Tidak Kawin, 3 Tanggungan',
            self::K_0 => 'Kawin, 0 Tanggungan',
            self::K_1 => 'Kawin, 1 Tanggungan',
            self::K_2 => 'Kawin, 2 Tanggungan',
            self::K_3 => 'Kawin, 3 Tanggungan',
        };
    }

    /**
     * Get TER Category (A, B, or C) according to PP 58/2023.
     */
    public function terCategory(): string
    {
        return match ($this) {
            self::TK_0, self::TK_1, self::K_0 => 'A',
            self::TK_2, self::TK_3, self::K_1, self::K_2 => 'B',
            self::K_3 => 'C',
        };
    }

    /**
     * Annual PTKP nominal amount in Rupiah.
     */
    public function ptkpAmount(): float
    {
        return match ($this) {
            self::TK_0 => 54_000_000.0,
            self::TK_1 => 58_500_000.0,
            self::TK_2 => 63_000_000.0,
            self::TK_3 => 67_500_000.0,
            self::K_0 => 58_500_000.0,
            self::K_1 => 63_000_000.0,
            self::K_2 => 67_500_000.0,
            self::K_3 => 72_000_000.0,
        };
    }

    /**
     * Resolve PtkpStatus from marital status and number of dependents.
     */
    public static function fromMaritalAndDependents(MaritalStatus|string $status, int $dependents): self
    {
        $statusValue = $status instanceof MaritalStatus ? $status->value : strtolower(trim((string) $status));
        $clampedDependents = min(3, max(0, $dependents));

        if ($statusValue === 'k') {
            return match ($clampedDependents) {
                0 => self::K_0,
                1 => self::K_1,
                2 => self::K_2,
                default => self::K_3,
            };
        }

        // For 'tk' and 'hb' (hidup berpisah):
        return match ($clampedDependents) {
            0 => self::TK_0,
            1 => self::TK_1,
            2 => self::TK_2,
            default => self::TK_3,
        };
    }
}
