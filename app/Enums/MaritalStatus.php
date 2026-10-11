<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case TK = 'tk';
    case K = 'k';
    case HB = 'hb';

    public function label(): string
    {
        return match ($this) {
            self::TK => 'Tidak Kawin (TK)',
            self::K => 'Kawin (K)',
            self::HB => 'Hidup Berpisah (HB)',
        };
    }
}
