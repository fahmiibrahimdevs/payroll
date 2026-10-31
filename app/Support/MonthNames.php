<?php

namespace App\Support;

/**
 * Nama bulan Indonesia untuk dropdown, chart, dan label periode.
 */
class MonthNames
{
    /** Nama bulan lengkap: 1 => 'Januari' ... 12 => 'Desember'. */
    public static function full(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }

    /** Nama bulan singkat: 1 => 'Jan' ... 12 => 'Des'. */
    public static function short(): array
    {
        return [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
    }
}
