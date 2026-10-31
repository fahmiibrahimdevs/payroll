<?php

namespace App\Support;

/**
 * Format tampilan Rupiah yang konsisten di seluruh dokumen cetak dan UI.
 */
class Rupiah
{
    /** Angka bulat berformat ribuan: 1234567 => "1.234.567". */
    public static function format(float|int|string $amount): string
    {
        return number_format((float) $amount, 0, ',', '.');
    }

    /** Sel tabel slip: "-" untuk nol, tanda minus di depan untuk nilai negatif. */
    public static function cell(float|int|string $amount): string
    {
        $amount = (float) $amount;
        if ($amount == 0) {
            return '-';
        }
        if ($amount < 0) {
            return '-'.self::format(abs($amount));
        }

        return self::format($amount);
    }

    /** "Rp 1.234.567". */
    public static function display(float|int|string $amount): string
    {
        return 'Rp '.self::format($amount);
    }
}
