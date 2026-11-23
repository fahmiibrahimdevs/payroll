<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentStatus;
use App\Enums\OvertimePeriodStatus;
use App\Enums\OvertimeStatus;
use App\Models\Attendance;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\OvertimePeriod;
use App\Models\OvertimePeriodAttendance;
use App\Services\ActivityLogService;
use App\Services\OvertimeService;
use App\Support\Terbilang;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class OvertimePrintController extends Controller
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Display printable weekly overtime slip for a single employee or all employees in a period.
     */
    public function printWeekly(OvertimePeriod $period, ?Employee $employee = null): View
    {
        $user = auth()->user();

        if ($user && $user->hasRole('karyawan')) {
            $myEmployee = $user->getEmployeeRecord();
            abort_unless($myEmployee, 403, 'Profil karyawan tidak ditemukan.');

            if ($employee && $employee->id !== $myEmployee->id) {
                abort(403, 'Anda tidak memiliki hak akses untuk mencetak slip lembur karyawan lain.');
            }

            $employee = $myEmployee;

            $attCheck = $period->attendances()->where('employee_id', $employee->id)->first();
            $isPaid = ($period->status === OvertimePeriodStatus::PAID) || ($attCheck && $attCheck->isPaid());
            abort_unless($isPaid, 403, 'Slip gaji mingguan belum dapat dicetak karena pembayaran belum dikonfirmasi oleh Admin.');
        } else {
            abort_unless($user && $user->hasRole(['admin', 'direktur']), 403);
        }

        $startDate = Carbon::parse($period->start_date)->toDateString();
        $endDate = Carbon::parse($period->end_date)->toDateString();

        // 1. Bangun daftar hari siklus periode secara dinamis
        $cycleDays = [];
        $currDate = Carbon::parse($startDate)->copy();
        $endDateObj = Carbon::parse($endDate)->copy();
        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        while ($currDate->lte($endDateObj)) {
            $dKey = WorkingDays::DAY_KEY_MAP[$currDate->dayOfWeek] ?? 'fri';
            $cycleDays[] = [
                'key' => $dKey,
                'date' => $currDate->toDateString(),
                'day_name' => $dayNames[$currDate->dayOfWeek] ?? 'Hari',
                'date_formatted' => $currDate->locale('id')->translatedFormat('d M'),
                'is_sunday' => $currDate->dayOfWeek === 0,
                'is_saturday' => $currDate->dayOfWeek === 6,
            ];
            $currDate->addDay();
        }

        // 2. Ambil data libur nasional dalam periode
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])->pluck('name', 'date')->all();

        // 3. Profil perusahaan
        $company = CompanyProfile::getProfile();

        // 4. Tentukan target karyawan
        if ($employee) {
            $targetEmployees = collect([$employee]);
        } else {
            $attendances = $period->attendances()
                ->with(['employee.department', 'employee.position', 'employee.detail'])
                ->get()
                ->keyBy('employee_id');

            $submissions = $period->submissions()
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotIn('status', [OvertimeStatus::REJECTED_ADMIN, OvertimeStatus::REJECTED_DIREKTUR])
                ->get()
                ->groupBy('employee_id');

            $allEmpIds = $submissions->keys()->concat($attendances->keys())->unique()->filter()->all();
            $targetEmployees = Employee::withTrashed()->with(['department', 'position', 'detail'])
                ->whereIn('id', $allEmpIds)
                ->get()
                ->sortBy(fn ($e) => $e->full_name);
        }

        $empIds = $targetEmployees->pluck('id')->all();

        // 5. Eager load log absensi mesin, jadwal roster, dan pengajuan lembur
        $attendanceLogs = Attendance::whereIn('employee_id', $empIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['hadir', 'terlambat', 'pulang_cepat'])
            ->where(fn ($query) => $query->whereNull('review_status')->orWhere('review_status', 'approved'))
            ->get()
            ->groupBy(['employee_id', fn ($a) => Carbon::parse($a->date)->toDateString()]);

        $schedules = EmployeeSchedule::schedulesForEmployees($empIds, $startDate, $endDate);

        $submissionsByEmp = $period->submissions()
            ->whereIn('employee_id', $empIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('status', [OvertimeStatus::REJECTED_ADMIN, OvertimeStatus::REJECTED_DIREKTUR])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('employee_id');

        $attendancesByEmp = $period->attendances()
            ->whereIn('employee_id', $empIds)
            ->get()
            ->keyBy('employee_id');

        $slips = [];
        foreach ($targetEmployees as $emp) {
            $empSubs = $submissionsByEmp->get($emp->id, collect());
            $empAtt = $attendancesByEmp->get($emp->id);
            $empLogs = $attendanceLogs->get($emp->id, collect());
            $empSched = $schedules->get($emp->id, collect());

            $slip = $this->buildSlip(
                $period,
                $emp,
                $empSubs,
                $empAtt,
                $cycleDays,
                $holidays,
                $empLogs,
                $empSched,
                $company
            );

            // Filter: hanya cetak slip yang memiliki kehadiran (> 0 hari) atau jam lembur atau hak upah > 0
            if ($slip['days_count'] > 0 || $slip['total_hours'] > 0 || $slip['grand_total'] > 0) {
                $slips[] = $slip;
            }
        }

        if ($employee) {
            $this->logger->info(
                action: 'OVERTIME_WEEKLY_SLIP_PRINTED',
                module: 'overtimes',
                message: "Slip lembur mingguan {$period->reference_number} karyawan {$employee->full_name} dicetak",
                context: [
                    'period_id' => $period->id,
                    'employee_id' => $employee->id,
                ]
            );

            return view('overtimes.print-weekly', [
                'period' => $period,
                'company' => $company,
                'slips' => $slips,
                'isBatch' => false,
            ]);
        }

        $this->logger->info(
            action: 'OVERTIME_WEEKLY_BATCH_SLIPS_PRINTED',
            module: 'overtimes',
            message: "Seluruh slip lembur mingguan periode {$period->reference_number} dicetak massal",
            context: [
                'period_id' => $period->id,
                'total_employees' => count($slips),
            ]
        );

        return view('overtimes.print-weekly', [
            'period' => $period,
            'company' => $company,
            'slips' => $slips,
            'isBatch' => true,
        ]);
    }

    /**
     * Bangun data satu slip mingguan lengkap format matriks sinkron tabel presensi.
     */
    private function buildSlip(
        OvertimePeriod $period,
        Employee $emp,
        Collection $submissions,
        ?OvertimePeriodAttendance $attendance,
        array $cycleDays,
        array $holidays,
        Collection $attendanceLogs,
        Collection $schedules,
        CompanyProfile $company
    ): array {
        $defaultMealRate = (float) ($company->default_meal_allowance_per_day ?: 15000.0);
        $otMealThreshold = (float) ($company->overtime_meal_threshold_hours ?? 3.0);
        $otMealAmount = (float) ($company->overtime_meal_allowance ?? 15000.0);
        $workDaysPerWeek = (int) ($company->work_days_per_week ?: 6);

        $hourlySubmissions = $submissions->filter(fn ($submission) => $submission->isHourly());
        $unitPrice = (float) ($hourlySubmissions->first()?->hourly_rate ?? 0);
        $hasMultipleHourlyRates = $hourlySubmissions->pluck('hourly_rate')->unique()->count() > 1;

        $mealRate = (float) (($attendance?->meal_allowance_per_day ?? 0) > 0 ? $attendance->meal_allowance_per_day : $emp->getEffectiveMealAllowancePerDay());
        $isHarian = $emp->employment_status === EmploymentStatus::HARIAN;
        $dailyRate = (float) ($attendance && (float) $attendance->daily_rate > 0 ? $attendance->daily_rate : ($emp->daily_rate ?? 0));

        $empSubsByDate = $submissions->groupBy(fn ($s) => Carbon::parse($s->date)->toDateString());
        $attendanceDays = is_array($attendance?->attendance_days) ? $attendance->attendance_days : [];

        $daysData = [];
        $totalOtHours = 0;
        $hourlyOtPay = 0;
        $totalHolidayOtPay = 0;
        $totalLunch = 0;
        $totalDinner = 0;
        $totalDailyWage = 0;
        $totalHourlyWorkPay = 0.0;

        foreach ($cycleDays as $cDay) {
            $dateStr = $cDay['date'];
            $dKey = $cDay['key'];

            $daySubs = $empSubsByDate->get($dateStr, collect());
            $hourlySubs = $daySubs->filter(fn ($s) => $s->isHourly());
            $dailySubs = $daySubs->filter(fn ($s) => $s->isDaily());

            $otHours = (float) $hourlySubs->sum('duration_hours');
            $holidayOtPay = (float) $dailySubs->sum(fn ($s) => (float) $s->subtotal_overtime);
            $otMeal = (float) $daySubs->sum('meal_allowance');

            $sch = $schedules->get($dateStr);
            if ($sch) {
                $isHoliday = $sch->isOff() || isset($holidays[$dateStr]);
            } else {
                $isHoliday = isset($holidays[$dateStr]);
                if (! $company->isRosterMode()) {
                    $isHoliday = $isHoliday || WorkingDays::isWeekend(Carbon::parse($dateStr), $workDaysPerWeek);
                }
            }

            $hasAttLog = $attendanceLogs->has($dateStr);
            $dayAttendance = $attendanceLogs->get($dateStr, collect())->first();
            $isInChecklist = in_array($dKey, $attendanceDays, true)
                || in_array($dateStr, $attendanceDays, true);
            $isAttended = $hasAttLog || $isInChecklist;

            $lunch = $isAttended ? $mealRate : 0.0;
            $dinner = ($otHours >= $otMealThreshold || $otMeal > 0) ? ($otMeal > 0 ? $otMeal : $otMealAmount) : 0.0;
            $workType = $dayAttendance?->casual_work_type_confirmed ?? $dayAttendance?->casual_work_type ?? 'daily';
            $payOverride = $dayAttendance?->casual_pay_override;
            $wage = ($isHarian && $isAttended && $workType === 'daily')
                ? (float) ($payOverride ?? $dailyRate)
                : 0.0;
            $roundedWorkHours = $dayAttendance?->clock_in && $dayAttendance?->clock_out
                ? app(OvertimeService::class)->calculateOvertimeDuration($dayAttendance->clock_in, $dayAttendance->clock_out)
                : (int) ($dayAttendance?->duration_minutes ?? 0) / 60;
            $hourlyWorkPay = ($isHarian && $isAttended && $workType === 'hourly')
                ? round($roundedWorkHours * (float) ($emp->overtime_rate_per_hour ?? 0), 2)
                : 0.0;

            $dailyHourlyOtPay = (float) $hourlySubs->sum(fn ($submission) => $submission->subtotal_overtime);
            $hourlyOtPay += $dailyHourlyOtPay;
            $dailyTotalOtPay = $dailyHourlyOtPay + $holidayOtPay;
            $dailyTotal = $dailyTotalOtPay + $lunch + $dinner + $wage + $hourlyWorkPay;

            $totalOtHours += $otHours;
            $totalHolidayOtPay += $holidayOtPay;
            $totalLunch += $lunch;
            $totalDinner += $dinner;
            $totalDailyWage += $wage;
            $totalHourlyWorkPay += $hourlyWorkPay;

            $daysData[] = [
                'date' => $dateStr,
                'key' => $dKey,
                'day_name' => $cDay['day_name'],
                'date_formatted' => $cDay['date_formatted'],
                'is_holiday' => $isHoliday,
                'ot_hours' => $otHours,
                'ot_hours_formatted' => $otHours > 0 ? (intval($otHours) == $otHours ? (string) intval($otHours) : number_format($otHours, 1)) : '',
                'holiday_ot' => $holidayOtPay,
                'holiday_ot_formatted' => $holidayOtPay > 0 ? 'Rp '.number_format($holidayOtPay, 0, ',', '.') : '-',
                'daily_ot_pay' => $dailyTotalOtPay,
                'daily_ot_pay_formatted' => $dailyTotalOtPay > 0 ? 'Rp '.number_format($dailyTotalOtPay, 0, ',', '.') : '-',
                'lunch' => $lunch,
                'lunch_formatted' => $lunch > 0 ? 'Rp '.number_format($lunch, 0, ',', '.') : '-',
                'dinner' => $dinner,
                'dinner_formatted' => $dinner > 0 ? 'Rp '.number_format($dinner, 0, ',', '.') : '-',
                'daily_total' => $dailyTotal,
                'hourly_work_pay' => $hourlyWorkPay,
                'daily_total_formatted' => $dailyTotal > 0 ? 'Rp '.number_format($dailyTotal, 0, ',', '.') : '',
                'is_attended' => $isAttended,
            ];
        }

        $totalOtPay = $hourlyOtPay + $totalHolidayOtPay;
        $grandTotal = $totalOtPay + $totalLunch + $totalDinner + $totalDailyWage + $totalHourlyWorkPay;

        $matrixTotals = [
            'total_ot_hours' => $totalOtHours,
            'total_ot_hours_formatted' => intval($totalOtHours) == $totalOtHours ? (string) intval($totalOtHours) : number_format($totalOtHours, 1),
            'total_holiday_ot' => $totalHolidayOtPay,
            'total_holiday_ot_formatted' => $totalHolidayOtPay > 0 ? 'Rp '.number_format($totalHolidayOtPay, 0, ',', '.') : '-',
            'hourly_ot_pay' => $hourlyOtPay,
            'hourly_ot_pay_formatted' => $hourlyOtPay > 0 ? 'Rp '.number_format($hourlyOtPay, 0, ',', '.') : '-',
            'total_ot_pay' => $totalOtPay,
            'total_ot_pay_formatted' => 'Rp '.number_format($totalOtPay, 0, ',', '.'),
            'total_lunch' => $totalLunch,
            'total_lunch_formatted' => $totalLunch > 0 ? 'Rp '.number_format($totalLunch, 0, ',', '.') : '-',
            'total_dinner' => $totalDinner,
            'total_dinner_formatted' => $totalDinner > 0 ? 'Rp '.number_format($totalDinner, 0, ',', '.') : '-',
            'total_daily_wage' => $totalDailyWage,
            'total_hourly_work_pay' => $totalHourlyWorkPay,
            'total_daily_wage_formatted' => $totalDailyWage > 0 ? 'Rp '.number_format($totalDailyWage, 0, ',', '.') : '-',
            'grand_total' => $grandTotal,
            'grand_total_formatted' => 'Rp '.number_format($grandTotal, 0, ',', '.'),
        ];

        return [
            'employee' => $emp,
            'submissions' => $submissions,
            'attendance' => $attendance,
            'days' => $daysData,
            'totals' => $matrixTotals,
            'unit_price' => $unitPrice,
            'unit_price_formatted' => $hasMultipleHourlyRates
                ? 'Sesuai pengajuan'
                : ($hourlySubmissions->isEmpty() ? '-' : 'Rp '.number_format($unitPrice, 0, ',', '.')),
            'total_hours' => $totalOtHours,
            'total_overtime_pay' => $totalOtPay,
            'overtime_meal_allowance' => $totalDinner,
            'total_meal_allowance' => $totalLunch + $totalDinner,
            'total_daily_wage' => $totalDailyWage,
            'grand_total' => $grandTotal,
            'grand_total_in_words' => Terbilang::format($grandTotal),
            'is_harian' => $isHarian,
            'days_count' => $attendance ? (int) $attendance->attended_days : 0,
            'meal_rate' => $mealRate,
            'daily_rate' => $dailyRate,
            'total_meal' => $totalLunch,
            'is_paid' => ($period->status === OvertimePeriodStatus::PAID) || ($attendance && $attendance->isPaid()),
        ];
    }
}
