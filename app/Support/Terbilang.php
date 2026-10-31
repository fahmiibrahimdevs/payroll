<?php

namespace App\Support;

/**
 * Konversi angka ke kata dalam Bahasa Indonesia (terbilang) untuk slip gaji
 * dan dokumen cetak.
 */
class Terbilang
{
    public static function format(float|int|string $angka): string
    {
        $angka = abs((float) $angka);
        if ($angka == 0) {
            return 'Nol';
        }

        $bilangan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($angka < 12) {
            $hasil = $bilangan[(int) $angka];
        } elseif ($angka < 20) {
            $hasil = self::format($angka - 10).' Belas';
        } elseif ($angka < 100) {
            $hasil = self::format(floor($angka / 10)).' Puluh '.self::format(fmod($angka, 10));
        } elseif ($angka < 200) {
            $hasil = 'Seratus '.self::format($angka - 100);
        } elseif ($angka < 1000) {
            $hasil = self::format(floor($angka / 100)).' Ratus '.self::format(fmod($angka, 100));
        } elseif ($angka < 2000) {
            $hasil = 'Seribu '.self::format($angka - 1000);
        } elseif ($angka < 1000000) {
            $hasil = self::format(floor($angka / 1000)).' Ribu '.self::format(fmod($angka, 1000));
        } elseif ($angka < 1000000000) {
            $hasil = self::format(floor($angka / 1000000)).' Juta '.self::format(fmod($angka, 1000000));
        } elseif ($angka < 1000000000000) {
            $hasil = self::format(floor($angka / 1000000000)).' Miliar '.self::format(fmod($angka, 1000000000));
        } else {
            $hasil = self::format(floor($angka / 1000000000000)).' Triliun '.self::format(fmod($angka, 1000000000000));
        }

        return trim(preg_replace('/\s+/', ' ', $hasil));
    }
}
