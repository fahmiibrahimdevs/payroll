<?php

namespace App\Support;

use App\Models\CompanyProfile;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

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
     * Hari kerja aktual untuk seorang karyawan dalam rentang tanggal:
     * - Jika mode Roster: memeriksa EmployeeSchedule untuk karyawan tersebut.
     *   Jika status 'work', maka tanggal tersebut adalah hari kerja karyawan (termasuk Minggu jika dijadwalkan kerja).
     *   Jika status 'off', maka tanggal tersebut adalah libur sah karyawan.
     *   Jika belum dijadwalkan, fallback ke default workDays.
     * - Jika mode Fixed: mengikuti workDays standar.
     *
     * @return array<string, string> tanggal (Y-m-d) => key hari ('mon'..'sun')
     */
    public static function employeeWorkDays(int $employeeId, string $startDate, string $endDate, ?CompanyProfile $company = null, ?Collection $employeeSchedules = null): array
    {
        $company ??= CompanyProfile::getProfile();
        $workDaysPerWeek = (int) ($company->work_days_per_week ?: 6);

        if (! $company->isRosterMode()) {
            return self::workDays($startDate, $endDate, $workDaysPerWeek);
        }

        $holidays = Holiday::datesBetween($startDate, $endDate);

        $hasSchedulesArg = func_num_args() >= 5;
        if ($hasSchedulesArg) {
            $schedules = $employeeSchedules ?? collect();
        } else {
            $schedules = \App\Models\EmployeeSchedule::query()
                ->where('employee_id', $employeeId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();
        }

        if ($schedules->isNotEmpty() && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $schedules->keys()->first())) {
            $schedules = $schedules->keyBy(function ($s) {
                if ($s instanceof \App\Models\EmployeeSchedule) {
                    return $s->date instanceof CarbonInterface ? $s->date->toDateString() : Carbon::parse($s->date)->toDateString();
                }

                return is_array($s) ? ($s['date'] ?? '') : (string) ($s->date ?? '');
            });
        }

        $workDays = [];
        $curr = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        while ($curr->lte($end)) {
            $dateString = $curr->toDateString();
            $sch = $schedules->get($dateString);

            if ($sch) {
                if ($sch->isWork()) {
                    $workDays[$dateString] = self::dayKey($curr);
                }
            } else {
                // Fallback jika belum dijadwalkan secara spesifik:
                if (! self::isWeekend($curr, $workDaysPerWeek) && ! $holidays->has($dateString)) {
                    $workDays[$dateString] = self::dayKey($curr);
                }
            }
            $curr->addDay();
        }

        return $workDays;
    }

    /**
     * Key hari hadir dari log absensi yang hanya menghitung tanggal di dalam
     * himpunan hari kerja valid (hasil workDays()): log di akhir pekan sesuai
     * jadwal kerja maupun di hari libur nasional tidak dihitung sebagai hari
     * presensi mingguan (data absensinya tetap tersimpan). Dedup per hari dan
     * terurut mon..sun agar deterministik.
     *
     * @param  iterable  $attendances  kumpulan model/row Attendance (properti `date`)
     * @param  array<string, string>  $validWorkDays  tanggal (Y-m-d) => key hari dari workDays()
     * @return string[] daftar key hari
     */
    public static function attendedDayKeysWithin(iterable $attendances, array $validWorkDays): array
    {
        $keys = [];
        foreach ($attendances as $attendance) {
            $dateString = Carbon::parse($attendance->date)->toDateString();
            if (! isset($validWorkDays[$dateString])) {
                continue;
            }
            $key = $validWorkDays[$dateString];
            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        usort($keys, fn (string $a, string $b) => array_search($a, self::DAY_KEYS, true) <=> array_search($b, self::DAY_KEYS, true));

        return $keys;
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
