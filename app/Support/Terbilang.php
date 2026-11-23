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

        return self::convert($angka);
    }

    private static function convert(float $angka): string
    {
        if ($angka <= 0) {
            return '';
        }

        $bilangan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($angka < 12) {
            $hasil = $bilangan[(int) $angka];
        } elseif ($angka < 20) {
            $hasil = self::convert($angka - 10).' Belas';
        } elseif ($angka < 100) {
            $hasil = self::convert(floor($angka / 10)).' Puluh '.self::convert(fmod($angka, 10));
        } elseif ($angka < 200) {
            $hasil = 'Seratus '.self::convert($angka - 100);
        } elseif ($angka < 1000) {
            $hasil = self::convert(floor($angka / 100)).' Ratus '.self::convert(fmod($angka, 100));
        } elseif ($angka < 2000) {
            $hasil = 'Seribu '.self::convert($angka - 1000);
        } elseif ($angka < 1000000) {
            $hasil = self::convert(floor($angka / 1000)).' Ribu '.self::convert(fmod($angka, 1000));
        } elseif ($angka < 1000000000) {
            $hasil = self::convert(floor($angka / 1000000)).' Juta '.self::convert(fmod($angka, 1000000));
        } elseif ($angka < 1000000000000) {
            $hasil = self::convert(floor($angka / 1000000000)).' Miliar '.self::convert(fmod($angka, 1000000000));
        } else {
            $hasil = self::convert(floor($angka / 1000000000000)).' Triliun '.self::convert(fmod($angka, 1000000000000));
        }

        return trim(preg_replace('/\s+/', ' ', $hasil));
    }
}
