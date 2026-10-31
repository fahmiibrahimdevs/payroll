<?php

namespace App\Support;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Sumber tunggal definisi hari kerja aplikasi — dipakai payroll (prorata),
 * periode lembur mingguan, dan sinkronisasi presensi.
 *
 * Jadwal mengikuti CompanyProfile::work_days_per_week: 5 hari berarti
 * Sabtu+Minggu libur, 6 hari berarti hanya Minggu. Hari libur nasional dari
 * kalender admin selalu dikecualikan dari hari kerja.
 */
class WorkingDays
{
    /** Peta dayOfWeek Carbon => key hari yang dipakai kolom attendance_days. */
    public const DAY_KEY_MAP = [
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat',
        0 => 'sun',
    ];

    public const DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public static function dayKey(CarbonInterface $date): string
    {
        return self::DAY_KEY_MAP[$date->dayOfWeek];
    }

    /** Akhir pekan menurut jadwal kerja: Sabtu+Minggu (5 hari) atau Minggu saja (6 hari). */
    public static function isWeekend(CarbonInterface $date, int $workDaysPerWeek = 6): bool
    {
        return $workDaysPerWeek === 5 ? $date->isWeekend() : $date->isSunday();
    }

    /**
     * Hari kerja aktual dalam rentang tanggal: kecualikan akhir pekan sesuai
     * jadwal dan hari libur nasional.
     *
     * @return array<string, string> tanggal (Y-m-d) => key hari ('mon'..'sun')
     */
    public static function workDays(string $startDate, string $endDate, int $workDaysPerWeek = 6): array
    {
        $holidays = Holiday::datesBetween($startDate, $endDate);
        $workDays = [];

        $curr = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        while ($curr->lte($end)) {
            $dateString = $curr->toDateString();
            if (! self::isWeekend($curr, $workDaysPerWeek) && ! $holidays->has($dateString)) {
                $workDays[$dateString] = self::dayKey($curr);
            }
            $curr->addDay();
        }

        return $workDays;
    }

    /**
     * Key hari hadir dari log absensi: dedup per hari dan terurut mon..sun
     * agar deterministik.
     *
     * @param  iterable  $attendances  kumpulan model/row Attendance (properti `date`)
     * @return string[] daftar key hari
     */
    public static function attendedDayKeys(iterable $attendances): array
    {
        $keys = [];
        foreach ($attendances as $attendance) {
            $key = self::dayKey(Carbon::parse($attendance->date));
            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        usort($keys, fn (string $a, string $b) => array_search($a, self::DAY_KEYS, true) <=> array_search($b, self::DAY_KEYS, true));

        return $keys;
    }
}
