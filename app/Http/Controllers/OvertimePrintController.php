<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\OvertimePeriod;
use App\Models\OvertimeSubmission;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;

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
            $isPaid = ($period->status === \App\Enums\OvertimePeriodStatus::PAID) || ($attCheck && $attCheck->isPaid());
            abort_unless($isPaid, 403, 'Slip gaji mingguan belum dapat dicetak karena pembayaran belum dikonfirmasi oleh Admin.');
        } else {
            abort_unless($user && $user->hasRole(['admin', 'direktur']), 403);
        }

        $company = CompanyProfile::getProfile();

        if ($employee) {
            $submissions = $period->submissions()
                ->where('employee_id', $employee->id)
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            $attendance = $period->attendances()
                ->where('employee_id', $employee->id)
                ->first();

            $totalHours = (float) $submissions->sum('duration_hours');
            $totalOvertimePay = (float) $submissions->sum(fn ($s) => (float) $s->subtotal_overtime);
            $totalMealAllowance = $attendance ? (float) $attendance->total_meal_allowance : (float) $submissions->sum('meal_allowance');
            $totalDailyWage = $attendance ? (float) ($attendance->total_daily_wage ?? 0) : 0;
            $grandTotal = $totalOvertimePay + $totalMealAllowance + $totalDailyWage;

            $attendedDays = (int) ($attendance?->attended_days ?? 0);
            $slips = [];

            // Hanya buat slip jika karyawan memiliki kehadiran kerja (> 0 hari) atau memiliki lembur/upah
            if ($attendedDays > 0 || $submissions->isNotEmpty() || $grandTotal > 0) {
                $slips[] = [
                    'employee' => $employee,
                    'submissions' => $submissions,
                    'attendance' => $attendance,
                    'total_hours' => $totalHours,
                    'total_overtime_pay' => $totalOvertimePay,
                    'total_meal_allowance' => $totalMealAllowance,
                    'total_daily_wage' => $totalDailyWage,
                    'grand_total' => $grandTotal,
                ];
            }

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
                'slips' => [
                    [
                        'employee' => $employee,
                        'submissions' => $submissions,
                        'attendance' => $attendance,
                        'total_hours' => $totalHours,
                        'total_overtime_pay' => $totalOvertimePay,
                        'total_meal_allowance' => $totalMealAllowance,
                        'total_daily_wage' => $totalDailyWage,
                        'grand_total' => $grandTotal,
                    ],
                ],
                'isBatch' => false,
            ]);
        }

        // Batch print all employees in period
        $submissionsByEmployee = $period->submissions()
            ->with(['employee.department', 'employee.position', 'employee.detail'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('employee_id');

        $attendancesByEmployee = $period->attendances()
            ->with(['employee.department', 'employee.position', 'employee.detail'])
            ->get()
            ->keyBy('employee_id');

        $allEmployeeIds = $submissionsByEmployee->keys()->concat($attendancesByEmployee->keys())->unique();

        $slips = [];
        foreach ($allEmployeeIds as $empId) {
            $empSubmissions = $submissionsByEmployee->get($empId, collect());
            $attendance = $attendancesByEmployee->get($empId);
            $emp = $empSubmissions->first()?->employee ?? $attendance?->employee;
            if (! $emp) continue;

            $totalHours = (float) $empSubmissions->sum('duration_hours');
            $totalOvertimePay = (float) $empSubmissions->sum(fn ($s) => (float) $s->subtotal_overtime);
            $totalMealAllowance = $attendance ? (float) $attendance->total_meal_allowance : (float) $empSubmissions->sum('meal_allowance');
            $totalDailyWage = $attendance ? (float) ($attendance->total_daily_wage ?? 0) : 0;
            $grandTotal = $totalOvertimePay + $totalMealAllowance + $totalDailyWage;

            // Hanya cetak karyawan yang memiliki kehadiran kerja (> 0 hari) atau memiliki lembur/upah
            $attendedDays = (int) ($attendance?->attended_days ?? 0);
            if ($attendedDays <= 0 && $empSubmissions->isEmpty() && $grandTotal <= 0) {
                continue;
            }

            $slips[] = [
                'employee' => $emp,
                'submissions' => $empSubmissions,
                'attendance' => $attendance,
                'total_hours' => $totalHours,
                'total_overtime_pay' => $totalOvertimePay,
                'total_meal_allowance' => $totalMealAllowance,
                'total_daily_wage' => $totalDailyWage,
                'grand_total' => $grandTotal,
            ];
        }

        // Urutkan slips berdasarkan nama karyawan
        usort($slips, fn ($a, $b) => strcmp($a['employee']->full_name ?? '', $b['employee']->full_name ?? ''));

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
}
