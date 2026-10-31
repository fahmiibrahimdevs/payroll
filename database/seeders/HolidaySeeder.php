<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Hari libur nasional tanggal tetap untuk tahun berjalan.
     * Tanggal bergeser (Cuti Bersama, Idul Fitri, Nyepi, dll.) dapat
     * ditambahkan admin lewat menu Hari Libur.
     */
    public function run(): void
    {
        $year = (int) now()->year;

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => 'Tahun Baru Masehi'],
            ['date' => "{$year}-05-01", 'name' => 'Hari Buruh Internasional'],
            ['date' => "{$year}-06-01", 'name' => 'Hari Lahir Pancasila'],
            ['date' => "{$year}-08-17", 'name' => 'Hari Kemerdekaan Republik Indonesia'],
            ['date' => "{$year}-12-25", 'name' => 'Kelahiran Yesus Kristus (Natal)'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['date' => $holiday['date']],
                ['name' => $holiday['name']]
            );
        }
    }
}
