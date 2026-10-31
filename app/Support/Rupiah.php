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

    /**
     * Format ringkas untuk chart/kartu ringkasan: "Rp 1,2 M", "Rp 3,4 jt",
     * "Rp 500 rb", atau angka penuh di bawah seribu.
     */
    public static function compact(float|int|string $amount, int $decimals = 1): string
    {
        $amount = (float) $amount;
        if ($amount >= 1000000000) {
            return 'Rp '.number_format($amount / 1000000000, $decimals, ',', '.').' M';
        }
        if ($amount >= 1000000) {
            return 'Rp '.number_format($amount / 1000000, $decimals, ',', '.').' jt';
        }
        if ($amount >= 1000) {
            return 'Rp '.number_format($amount / 1000, 0, ',', '.').' rb';
        }

        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Tarif lembur per jam siap-tampil untuk dropdown: "1,5rb/jam" di atas
     * seribu, atau "Rp 12.500/jam" untuk nominal kecil.
     */
    public static function hourly(float|int|string $rate): string
    {
        $rate = (float) $rate;

        if ($rate >= 1000) {
            return rtrim(rtrim(number_format($rate / 1000, 1, ',', '.'), '0'), ',').'rb/jam';
        }

        return 'Rp '.number_format($rate, 0, ',', '.').'/jam';
    }

    /**
     * Tarif lembur harian siap-tampil untuk dropdown: "150rb/hari" di atas
     * seribu, atau "Rp 90.000/hari" untuk nominal kecil.
     */
    public static function daily(float|int|string $rate): string
    {
        $rate = (float) $rate;

        if ($rate >= 1000) {
            return rtrim(rtrim(number_format($rate / 1000, 1, ',', '.'), '0'), ',').'rb/hari';
        }

        return 'Rp '.number_format($rate, 0, ',', '.').'/hari';
    }
}
