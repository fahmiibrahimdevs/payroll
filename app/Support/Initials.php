<?php

namespace App\Support;

/**
 * Inisial nama untuk avatar teks: dua huruf pertama dari dua kata pertama.
 */
class Initials
{
    /**
     * Ambil inisial dari nama: split per spasi, ambil huruf pertama dari
     * maksimal 2 kata pertama yang memuat huruf/angka, lalu uppercase.
     * Fallback "K" (Karyawan) untuk nama kosong.
     */
    public static function fromName(?string $name): string
    {
        $cleanedWords = array_values(array_filter(
            preg_split('/\s+/', trim($name ?? '')),
            fn ($w) => preg_match('/[a-zA-Z0-9]/', $w)
        ));

        $initials = '';
        if (! empty($cleanedWords)) {
            $initials .= strtoupper(substr($cleanedWords[0], 0, 1));
            if (isset($cleanedWords[1])) {
                $initials .= strtoupper(substr($cleanedWords[1], 0, 1));
            }
        }

        return $initials ?: 'K';
    }
}
