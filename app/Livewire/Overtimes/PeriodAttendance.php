<?php

namespace App\Livewire\Overtimes;

use App\Enums\EmploymentStatus;
use App\Enums\OvertimeStatus;
use App\Models\Attendance;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\OvertimePeriod;
use App\Models\OvertimePeriodAttendance;
use App\Models\OvertimeSubmission;
use App\Services\AttendanceService;
use App\Services\OvertimeService;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Kehadiran & Uang Makan Mingguan - Larashiz HRM')]
class PeriodAttendance extends Component
{
    use WithFileUploads;

    /** Nama hari pendek (Indonesia) per key hari kerja mon..sun. */
    private const DAY_SHORT_NAMES = [
        'mon' => 'Sen',
        'tue' => 'Sel',
        'wed' => 'Rab',
        'thu' => 'Kam',
        'fri' => 'Jum',
        'sat' => 'Sab',
        'sun' => 'Min',
    ];

    public OvertimePeriod $period;

    public string $search = '';

    public ?int $departmentFilter = null;

    public string $viewMode = 'matrix';

    public array $periodAttendances = [];

    // Properties for Employee Payment
    public ?int $selectedEmployeeId = null;

    public ?OvertimePeriodAttendance $selectedAttendance = null;

    public ?array $selectedEmployeeData = null;

    public bool $isEditingPayment = false;

    public string $itemPaymentDate = '';

    public string $itemPaymentMethod = 'bank_transfer';

    public string $itemBankName = '';

    public string $itemBankAccountNumber = '';

    public string $itemBankAccountName = '';

    public mixed $itemPaymentProof = null;

    public string $itemPaymentNotes = '';

    public function mount(OvertimePeriod $period): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya Admin yang dapat mengatur kehadiran & uang makan mingguan.');

        $this->period = $period;
        app(OvertimeService::class)->syncUnlinkedApprovedSubmissions($this->period);
        $this->syncActiveEmployees();
        $this->loadAttendances();
    }

    /**
     * Ensure all active employees (and any linked in overtime) have attendance records in this period.
     */
    protected function syncActiveEmployees(): void
    {
        app(OvertimeService::class)->syncUnlinkedApprovedSubmissions($this->period);

        $linkedEmpIds = $this->period->submissions()->pluck('employee_id')->unique()->all();

        $startDate = Carbon::parse($this->period->start_date)->toDateString();
        $endDate = Carbon::parse($this->period->end_date)->toDateString();
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['hadir', 'terlambat', 'pulang_cepat'])
            ->get()
            ->groupBy('employee_id');

        $hasAttendanceLog = $attendances->isNotEmpty();
        $attendedEmpIds = $attendances->keys()->all();

        // Target karyawan mingguan: Pegawai Harian Lepas aktif + karyawan yang memiliki kehadiran atau lembur
        $targetEmployees = Employee::where('status', 'active')
            ->where(function ($q) use ($linkedEmpIds, $attendedEmpIds, $hasAttendanceLog) {
                $q->where('employment_status', EmploymentStatus::HARIAN)
                    ->orWhereIn('id', $linkedEmpIds);
                if ($hasAttendanceLog) {
                    $q->orWhereIn('id', $attendedEmpIds);
                }
            })
            ->orderBy('full_name')
            ->get();

        $existingEmpIds = $this->period->attendances()->pluck('employee_id')->all();
        // Fallback tanpa log absensi: hari kerja aktual dalam rentang
        // (kecualikan akhir pekan sesuai jadwal kerja dan hari libur nasional).
        $company = CompanyProfile::getProfile();
        $workDaysPerWeek = (int) ($company->work_days_per_week ?? 6);
        $isRoster = $company->isRosterMode();
        $validWorkDays = WorkingDays::workDays($startDate, $endDate, $workDaysPerWeek);
        $defaultDays = array_values($validWorkDays);
        $defaultDaysCount = max(1, count($defaultDays));
        $hasNew = false;

        $rosterSchedules = $isRoster
            ? EmployeeSchedule::schedulesForEmployees($targetEmployees->pluck('id')->all(), $startDate, $endDate)
            : collect();

        foreach ($targetEmployees as $emp) {
            if (! in_array($emp->id, $existingEmpIds, true)) {
                $rate = $emp->getEffectiveMealAllowancePerDay();
                $isHarian = $emp->employment_status === EmploymentStatus::HARIAN;
                $dailyRate = $isHarian ? (float) ($emp->daily_rate ?? 0) : 0.0;
                $hasOvertime = in_array($emp->id, $linkedEmpIds, true);

                $empValidWorkDays = $isRoster
                    ? WorkingDays::employeeWorkDays($emp->id, $startDate, $endDate, $company, $rosterSchedules->get($emp->id))
                    : $validWorkDays;

                if ($hasAttendanceLog) {
                    // Presensi mingguan hanya menghitung hari kerja valid
                    // (Minggu/Sabtu sesuai jadwal dan libur nasional tidak
                    // dihitung); log absensinya tetap tersimpan.
                    $attendedDaysKeys = WorkingDays::attendedDayKeysWithin($attendances->get($emp->id, collect()), $empValidWorkDays);
                    $daysCount = count($attendedDaysKeys);
                    $daysList = $attendedDaysKeys;

                    // Lewati karyawan tanpa kehadiran dan tanpa lembur
                    if ($daysCount <= 0 && ! $hasOvertime) {
                        continue;
                    }
                } else {
                    $empDefaultDays = $isRoster ? array_values($empValidWorkDays) : $defaultDays;
                    $daysCount = max(1, count($empDefaultDays));
                    $daysList = $empDefaultDays;
                }

                $totalDailyWage = $daysCount * $dailyRate;

                OvertimePeriodAttendance::create([
                    'overtime_period_id' => $this->period->id,
                    'employee_id' => $emp->id,
                    'meal_allowance_type' => $emp->meal_allowance_type ?: 'standar',
                    'meal_allowance_per_day' => $rate,
                    'daily_rate' => $dailyRate,
                    'attended_days' => $daysCount,
                    'attendance_days' => $daysList,
                    'total_meal_allowance' => $daysCount * $rate,
                    'total_daily_wage' => $totalDailyWage,
                ]);
                $hasNew = true;
            }
        }

        if ($hasNew) {
            $this->period->recalculateTotals();
        }
    }

    /**
     * Load attendance records into component state array.
     */
    protected function loadAttendances(): void
    {
        $attendances = OvertimePeriodAttendance::with([
            'employee.department',
            'employee.position',
            'employee.detail',
            'payer',
        ])
            ->where('overtime_period_id', $this->period->id)
            ->get()
            ->sortBy(fn ($att) => $att->employee?->full_name ?? '');

        $overtimeSubmissions = $this->period->submissions()
            ->get()
            ->groupBy('employee_id');

        $this->periodAttendances = [];
        foreach ($attendances as $att) {
            $isHarian = $att->employee?->employment_status === EmploymentStatus::HARIAN;
            $dailyRate = $isHarian ? (float) ($att->daily_rate ?: ($att->employee?->daily_rate ?? 0)) : 0.0;
            $totalDailyWage = (float) ($att->total_daily_wage ?? round($att->attended_days * $dailyRate, 2));
            $totalMealAllowance = (float) $att->total_meal_allowance;

            $empSubs = $overtimeSubmissions->get($att->employee_id, collect());
            $overtimeHours = (float) $empSubs->sum('duration_hours');
            $overtimePay = (float) $empSubs->sum(fn ($s) => (float) $s->subtotal_overtime);
            $overtimeMeal = (float) $empSubs->sum('meal_allowance');
            $grandTotal = round($totalDailyWage + $totalMealAllowance + $overtimePay + $overtimeMeal, 2);

            $this->periodAttendances[$att->employee_id] = [
                'attendance_id' => (int) $att->id,
                'employee_id' => (int) $att->employee_id,
                'employee_hashid' => $att->employee?->hashid ?? '',
                'employee_name' => $att->employee?->full_name ?? 'Karyawan',
                'employment_status' => $att->employee?->employment_status?->value ?? 'tetap',
                'is_harian' => $isHarian,
                'department_id' => $att->employee?->department_id,
                'department_name' => $att->employee?->department?->name ?? 'Tanpa Departemen',
                'position_name' => $att->employee?->position?->name ?? 'Karyawan',
                'photo_url' => $att->employee?->photo_url,
                'attended_days' => (int) $att->attended_days,
                'attendance_days' => is_array($att->attendance_days) ? $att->attendance_days : [],
                'meal_allowance_per_day' => (float) $att->meal_allowance_per_day,
                'meal_allowance_type' => $att->meal_allowance_type,
                'total_meal_allowance' => $totalMealAllowance,
                'daily_rate' => $dailyRate,
                'employee_overtime_rate' => (float) ($att->employee?->overtime_rate_per_hour ?: 15000),
                'total_daily_wage' => $totalDailyWage,
                'overtime_hours' => $overtimeHours,
                'overtime_pay' => $overtimePay,
                'overtime_meal' => $overtimeMeal,
                'grand_total' => $grandTotal,
                'payment_status' => $att->payment_status ?: 'unpaid',
                'paid_at' => $att->paid_at ? $att->paid_at->format('d M Y, H:i') : null,
                'payment_method' => $att->payment_method,
                'bank_name' => $att->bank_name ?: ($att->employee?->detail?->bank_name ?? null),
                'bank_account_number' => $att->bank_account_number ?: ($att->employee?->detail?->bank_account_number ?? null),
                'bank_account_name' => $att->bank_account_name ?: ($att->employee?->detail?->bank_account_name ?? $att->employee?->full_name ?? null),
                'payment_proof_path' => $att->payment_proof_path,
                'payment_notes' => $att->payment_notes,
                'paid_by' => $att->paid_by,
                'payer_name' => $att->payer?->name ?? 'Admin',
            ];
        }
    }

    /**
     * Toggle a day in employee's weekly attendance (Sen, Sel, Rab, Kam, Jum, Sab).
     */
    public function toggleAttendanceDay(int $employeeId, string $day): void
    {
        if (! isset($this->periodAttendances[$employeeId])) {
            return;
        }

        // STRICT LOCK: Karyawan yang sudah dibayar tidak boleh diubah
        if (($this->periodAttendances[$employeeId]['payment_status'] ?? 'unpaid') === 'paid') {
            return;
        }

        $days = $this->periodAttendances[$employeeId]['attendance_days'];
        $day = strtolower($day);

        if (in_array($day, $days, true)) {
            $days = array_values(array_diff($days, [$day]));
        } else {
            $days[] = $day;
        }

        $this->periodAttendances[$employeeId]['attendance_days'] = $days;
        $this->periodAttendances[$employeeId]['attended_days'] = count($days);
        $this->recalcEmployeeMealTotal($employeeId);
    }

    /**
     * Directly update numeric attended days count (Opsi A) syncing with checklist (Opsi B).
     */
    public function updateAttendedDaysCount(int $employeeId, mixed $count): void
    {
        if (! isset($this->periodAttendances[$employeeId])) {
            return;
        }

        // STRICT LOCK: Karyawan yang sudah dibayar tidak boleh diubah
        if (($this->periodAttendances[$employeeId]['payment_status'] ?? 'unpaid') === 'paid') {
            return;
        }

        $allDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $existingDays = $this->periodAttendances[$employeeId]['attendance_days'] ?? [];
        $hasSunday = is_array($existingDays) && in_array('sun', $existingDays, true);

        $company = CompanyProfile::getProfile();
        $isRoster = $company->isRosterMode();

        $rosterAllowsSunday = false;
        if ($isRoster) {
            $startDate = Carbon::parse($this->period->start_date)->toDateString();
            $endDate = Carbon::parse($this->period->end_date)->toDateString();

            $schedules = EmployeeSchedule::query()
                ->where('employee_id', $employeeId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            if ($schedules->isNotEmpty()) {
                $rosterAllowsSunday = $schedules->contains(fn ($s) => $s->isWork() && $s->date->isSunday());
            } else {
                $rosterAllowsSunday = true;
            }
        }

        $allowSunday = $hasSunday || $rosterAllowsSunday;

        // Hari Minggu yang tercatat/diizinkan roster harus ikut dalam daftar (upah/uang makan hari
        // Minggu), kuota hari naik menjadi 7 saat Minggu tercatat atau diizinkan roster.
        $count = max(0, min($allowSunday ? 7 : 6, (int) $count));

        if ($allowSunday && $count > 0) {
            $selectedDays = array_slice($allDays, 0, $count - 1);
            $selectedDays[] = 'sun';
        } else {
            $selectedDays = array_slice($allDays, 0, $count);
        }

        $this->periodAttendances[$employeeId]['attended_days'] = $count;
        $this->periodAttendances[$employeeId]['attendance_days'] = $selectedDays;
        $this->recalcEmployeeMealTotal($employeeId);
    }

    /**
     * 1-click shortcut: set all 6 days present for an employee.
     */
    public function setAllDaysPresent(int $employeeId): void
    {
        if (($this->periodAttendances[$employeeId]['payment_status'] ?? 'unpaid') === 'paid') {
            return;
        }

        $this->updateAttendedDaysCount($employeeId, 6);
    }

    /**
     * Bulk 1-click shortcut: set ALL employees to full 6 days.
     */
    public function setAllEmployeesFullWeek(): void
    {
        $allDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        foreach ($this->periodAttendances as $empId => $data) {
            if (($data['payment_status'] ?? 'unpaid') === 'paid') {
                continue;
            }

            $this->periodAttendances[$empId]['attended_days'] = 6;
            $this->periodAttendances[$empId]['attendance_days'] = $allDays;
            $this->recalcEmployeeMealTotal($empId);
        }

        $this->dispatch('swal:alert', [
            'type' => 'info',
            'title' => 'Semua Hadir 6 Hari',
            'message' => 'Seluruh karyawan telah diatur hadir penuh 6 hari kerja (Senin - Sabtu). Jangan lupa klik "Simpan Perubahan".',
        ]);
    }

    /**
     * Pull attendance logs recorded from fingerprint machine for this period's date range.
     */
    public function syncFromAttendance(AttendanceService $attendanceService): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $syncedCount = $attendanceService->syncToWeeklyOvertimePeriod($this->period, auth()->user());
        $this->syncActiveEmployees();
        $this->loadAttendances();

        $this->dispatch('swal:alert', [
            'type' => 'success',
            'title' => 'Sinkronisasi Absensi Selesai',
            'message' => "Data kehadiran {$syncedCount} karyawan telah berhasil disinkronisasi dari log mesin fingerprint.",
        ]);
    }

    /**
     * Count unlinked approved overtime submissions in this period that belong to unpaid employees.
     */
    #[Computed]
    public function unlinkedOvertimeSubmissionsCount(): int
    {
        $startDate = Carbon::parse($this->period->start_date)->toDateString();
        $endDate = Carbon::parse($this->period->end_date)->toDateString();

        $paidEmployeeIds = OvertimePeriodAttendance::query()
            ->where('overtime_period_id', $this->period->id)
            ->where('payment_status', 'paid')
            ->pluck('employee_id')
            ->all();

        return OvertimeSubmission::query()
            ->where('status', OvertimeStatus::APPROVED)
            ->whereNull('overtime_period_id')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('employee_id', $paidEmployeeIds)
            ->count();
    }

    /**
     * Explicit 1-click sync for newly approved overtimes in this period.
     */
    public function syncUnlinkedOvertimes(OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $syncedCount = $service->syncUnlinkedApprovedSubmissions($this->period);
        $this->syncActiveEmployees();
        $this->loadAttendances();

        $this->dispatch('swal:alert', [
            'type' => 'success',
            'title' => 'Lembur Disinkronkan',
            'message' => "Sebanyak {$syncedCount} pengajuan lembur yang telah disetujui berhasil disinkronkan ke rincian periode mingguan ini.",
        ]);
    }

    /**
     * Update employee's meal allowance per day rate.
     */
    public function updateMealAllowanceRate(int $employeeId, mixed $rate): void
    {
        if (! isset($this->periodAttendances[$employeeId])) {
            return;
        }

        // STRICT LOCK: Karyawan yang sudah dibayar tidak boleh diubah
        if (($this->periodAttendances[$employeeId]['payment_status'] ?? 'unpaid') === 'paid') {
            return;
        }

        $this->periodAttendances[$employeeId]['meal_allowance_per_day'] = max(0, (float) $rate);
        $this->recalcEmployeeMealTotal($employeeId);
    }

    protected function recalcEmployeeMealTotal(int $employeeId): void
    {
        $days = (int) $this->periodAttendances[$employeeId]['attended_days'];
        $rate = (float) $this->periodAttendances[$employeeId]['meal_allowance_per_day'];
        $dailyRate = (float) ($this->periodAttendances[$employeeId]['daily_rate'] ?? 0);
        $overtimePay = (float) ($this->periodAttendances[$employeeId]['overtime_pay'] ?? 0);

        $totalMeal = round($days * $rate, 2);
        $totalDaily = round($days * $dailyRate, 2);

        $this->periodAttendances[$employeeId]['total_meal_allowance'] = $totalMeal;
        $this->periodAttendances[$employeeId]['total_daily_wage'] = $totalDaily;
        $this->periodAttendances[$employeeId]['grand_total'] = round($totalDaily + $totalMeal + $overtimePay, 2);
    }

    /**
     * Save all weekly attendances and recalculate period totals.
     */
    public function save(OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        DB::transaction(function () use ($service) {
            foreach ($this->periodAttendances as $employeeId => $data) {
                // STRICT LOCK: Karyawan yang sudah dibayar tidak boleh ditimpa
                if (($data['payment_status'] ?? 'unpaid') === 'paid') {
                    continue;
                }

                $service->updatePeriodAttendance($this->period, (int) $employeeId, [
                    'attended_days' => $data['attended_days'],
                    'attendance_days' => $data['attendance_days'],
                    'meal_allowance_per_day' => $data['meal_allowance_per_day'],
                    'meal_allowance_type' => $data['meal_allowance_type'],
                    'daily_rate' => $data['daily_rate'] ?? 0,
                ], auth()->user(), recalculateTotals: false);
            }

            $this->period->recalculateTotals();
        });

        $this->period->refresh();
        $this->loadAttendances();

        $this->dispatch('swal:alert', [
            'type' => 'success',
            'title' => 'Kehadiran Disimpan',
            'message' => 'Rincian hari masuk kerja dan total uang makan mingguan seluruh karyawan berhasil disimpan.',
        ]);
    }

    /**
     * Open payment form modal for an employee.
     */
    public function openPayEmployeeModal(int $employeeId): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $att = OvertimePeriodAttendance::with(['employee.department', 'employee.position', 'employee.detail'])
            ->where('overtime_period_id', $this->period->id)
            ->where('employee_id', $employeeId)
            ->firstOrFail();

        $this->selectedAttendance = $att;
        $this->selectedEmployeeId = $employeeId;
        $this->selectedEmployeeData = $this->periodAttendances[$employeeId] ?? null;
        $this->isEditingPayment = false;

        $this->itemPaymentDate = Carbon::now()->toDateString();
        $this->itemPaymentMethod = 'bank_transfer';

        $detail = $att->employee?->detail;
        $this->itemBankName = $att->bank_name ?: ($detail?->bank_name ?? '');
        $this->itemBankAccountNumber = $att->bank_account_number ?: ($detail?->bank_account_number ?? $detail?->phone ?? '');
        $this->itemBankAccountName = $att->bank_account_name ?: ($detail?->bank_account_name ?? $att->employee?->full_name ?? '');
        $this->itemPaymentProof = null;
        $this->itemPaymentNotes = '';

        $this->dispatch('open-modal', id: 'payEmployeeModal');
    }

    /**
     * Open payment detail receipt modal for an already paid employee.
     */
    public function openPaymentDetailModal(int $employeeId): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $att = OvertimePeriodAttendance::with(['employee.department', 'employee.position', 'payer'])
            ->where('overtime_period_id', $this->period->id)
            ->where('employee_id', $employeeId)
            ->firstOrFail();

        $this->selectedAttendance = $att;
        $this->selectedEmployeeId = $employeeId;
        $this->selectedEmployeeData = $this->periodAttendances[$employeeId] ?? null;

        $this->dispatch('open-modal', id: 'paymentDetailModal');
    }

    /**
     * Open payment edit form modal for an employee.
     */
    public function openEditPaymentModal(int $employeeId): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $att = OvertimePeriodAttendance::with(['employee.department', 'employee.position', 'employee.detail'])
            ->where('overtime_period_id', $this->period->id)
            ->where('employee_id', $employeeId)
            ->firstOrFail();

        $this->selectedAttendance = $att;
        $this->selectedEmployeeId = $employeeId;
        $this->selectedEmployeeData = $this->periodAttendances[$employeeId] ?? null;
        $this->isEditingPayment = true;

        $this->itemPaymentDate = $att->paid_at ? $att->paid_at->format('Y-m-d') : Carbon::now()->toDateString();
        $this->itemPaymentMethod = in_array($att->payment_method, ['bank_transfer', 'cash'], true) ? $att->payment_method : 'bank_transfer';
        $this->itemBankName = $att->bank_name ?? ($att->employee?->detail?->bank_name ?? '');
        $this->itemBankAccountNumber = $att->bank_account_number ?? ($att->employee?->detail?->bank_account_number ?? '');
        $this->itemBankAccountName = $att->bank_account_name ?? ($att->employee?->detail?->bank_account_name ?? $att->employee?->full_name ?? '');
        $this->itemPaymentProof = null;
        $this->itemPaymentNotes = $att->payment_notes ?? '';

        $this->dispatch('close-modal', id: 'paymentDetailModal');
        $this->dispatch('open-modal', id: 'payEmployeeModal');
    }

    /**
     * Process employee payment confirmation or update.
     */
    public function processEmployeePayment(OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        if (! $this->selectedAttendance && $this->selectedEmployeeId) {
            $this->selectedAttendance = OvertimePeriodAttendance::with(['employee.department', 'employee.position', 'employee.detail'])
                ->where('overtime_period_id', $this->period->id)
                ->where('employee_id', $this->selectedEmployeeId)
                ->first();
        }

        if (! $this->selectedAttendance) {
            return;
        }

        $hasExistingProof = $this->isEditingPayment && ! empty($this->selectedAttendance->payment_proof_path);

        $rules = [
            'itemPaymentDate' => 'required|date',
            'itemPaymentMethod' => 'required|in:bank_transfer,cash',
            'itemPaymentProof' => $hasExistingProof
                ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:3072'
                : 'required|file|mimes:jpg,jpeg,png,pdf|max:3072',
            'itemPaymentNotes' => 'nullable|string|max:500',
        ];

        if ($this->itemPaymentMethod === 'bank_transfer') {
            $rules['itemBankName'] = 'required|string|max:100';
            $rules['itemBankAccountNumber'] = 'required|string|max:100';
            $rules['itemBankAccountName'] = 'nullable|string|max:150';
        }

        $this->validate($rules, [
            'itemPaymentDate.required' => 'Tanggal pembayaran wajib diisi.',
            'itemPaymentMethod.required' => 'Metode pembayaran wajib dipilih.',
            'itemBankName.required' => 'Nama bank atau e-wallet wajib diisi.',
            'itemBankAccountNumber.required' => 'Nomor rekening atau nomor HP wajib diisi.',
            'itemPaymentProof.required' => 'Foto bukti transfer atau kwitansi fisik wajib diunggah (baik tunai maupun transfer).',
            'itemPaymentProof.max' => 'Ukuran file bukti pembayaran maksimal 3MB.',
            'itemPaymentProof.mimes' => 'Format bukti pembayaran harus JPG, PNG, atau PDF.',
        ]);

        try {
            $proofPath = null;
            if ($this->itemPaymentProof) {
                $proofPath = $this->itemPaymentProof->store('payroll-proofs', 'local');
            }

            $bankName = $this->itemPaymentMethod === 'bank_transfer' ? $this->itemBankName : null;
            $bankAccNumber = $this->itemPaymentMethod === 'bank_transfer' ? $this->itemBankAccountNumber : null;
            $bankAccName = $this->itemPaymentMethod === 'bank_transfer' ? $this->itemBankAccountName : null;

            if ($this->isEditingPayment && $this->selectedAttendance->isPaid()) {
                $service->updateEmployeeWeeklyPayment($this->selectedAttendance, [
                    'payment_date' => $this->itemPaymentDate,
                    'payment_method' => $this->itemPaymentMethod,
                    'payment_proof_path' => $proofPath,
                    'payment_notes' => $this->itemPaymentNotes,
                    'bank_name' => $bankName,
                    'bank_account_number' => $bankAccNumber,
                    'bank_account_name' => $bankAccName,
                ], auth()->user());

                $message = 'Informasi pembayaran gaji mingguan berhasil diperbarui.';
                $title = 'Pembayaran Diperbarui!';
            } else {
                $service->payEmployeeWeekly($this->selectedAttendance, [
                    'payment_date' => $this->itemPaymentDate,
                    'payment_method' => $this->itemPaymentMethod,
                    'payment_proof_path' => $proofPath,
                    'payment_notes' => $this->itemPaymentNotes,
                    'bank_name' => $bankName,
                    'bank_account_number' => $bankAccNumber,
                    'bank_account_name' => $bankAccName,
                ], auth()->user());

                $message = 'Status gaji mingguan karyawan berhasil ditandai sebagai Dibayar.';
                $title = 'Pembayaran Berhasil Dicatat!';
            }

            $this->dispatch('close-modal', id: 'payEmployeeModal');
            $this->loadAttendances();
            $this->selectedAttendance = null;
            $this->selectedEmployeeId = null;
            $this->selectedEmployeeData = null;
            $this->itemPaymentProof = null;
            $this->isEditingPayment = false;

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => $title,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memproses Pembayaran!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Computed Properties (Siklus Hari & Grup Status)
    |--------------------------------------------------------------------------
    */

    /**
     * Daftar hari siklus periode secara dinamis untuk kolom kehadiran dan matrix:
     * key hari, nama lengkap hari, nama pendek, dan tanggal terformat.
     *
     * @return array<int, array{key: string, date: string, day_name: string, day_short: string, date_formatted: string, is_sunday: bool, is_saturday: bool}>
     */
    #[Computed]
    public function cycleDays(): array
    {
        $cycleDays = [];
        $currDate = Carbon::parse($this->period->start_date)->copy();
        $endDate = Carbon::parse($this->period->end_date)->copy();

        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        while ($currDate->lte($endDate)) {
            $dKey = WorkingDays::DAY_KEY_MAP[$currDate->dayOfWeek] ?? 'fri';
            $cycleDays[] = [
                'key' => $dKey,
                'date' => $currDate->toDateString(),
                'day_name' => $dayNames[$currDate->dayOfWeek] ?? 'Hari',
                'day_short' => self::DAY_SHORT_NAMES[$dKey] ?? 'Hari',
                'date_formatted' => $currDate->locale('id')->translatedFormat('d M'),
                'is_sunday' => $currDate->dayOfWeek === 0,
                'is_saturday' => $currDate->dayOfWeek === 6,
            ];
            $currDate->addDay();
        }

        return $cycleDays;
    }

    /**
     * Data breakdown harian per karyawan (Format Matrix Excel Sintesa Digital Printing).
     */
    #[Computed]
    public function dailyMatrix(): array
    {
        $startDate = Carbon::parse($this->period->start_date)->toDateString();
        $endDate = Carbon::parse($this->period->end_date)->toDateString();

        // 1. Ambil semua pengajuan lembur yang terhubung dalam periode ini
        $submissions = $this->period->submissions()
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('status', [OvertimeStatus::REJECTED_ADMIN, OvertimeStatus::REJECTED_DIREKTUR])
            ->get()
            ->groupBy(['employee_id', fn ($s) => Carbon::parse($s->date)->toDateString()]);

        // 2. Ambil absensi dalam periode ini
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['hadir', 'terlambat', 'pulang_cepat'])
            ->get()
            ->groupBy(['employee_id', fn ($a) => Carbon::parse($a->date)->toDateString()]);

        // 3. Ambil data libur nasional
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->pluck('name', 'date')
            ->all();

        $company = CompanyProfile::getProfile();
        $defaultMealRate = (float) ($company->default_meal_allowance_per_day ?: 15000.0);
        $otMealThreshold = (float) ($company->overtime_meal_threshold_hours ?? 3.0);
        $otMealAmount = (float) ($company->overtime_meal_allowance ?? 15000.0);
        $workDaysPerWeek = (int) ($company->work_days_per_week ?: 6);

        // 4. Ambil data jadwal kerja (Roster / EmployeeSchedule) karyawan
        $employeeIds = array_keys($this->periodAttendances);
        $schedules = EmployeeSchedule::schedulesForEmployees($employeeIds, $startDate, $endDate);

        $matrix = [];
        $cycleDays = $this->cycleDays;

        foreach ($this->periodAttendances as $empId => $attData) {
            $daysData = [];
            $totalOtHours = 0;
            $totalHolidayOtPay = 0;
            $totalLunch = 0;
            $totalDinner = 0;
            $totalDailyWage = 0;

            $unitPrice = (float) ($attData['employee_overtime_rate'] ?? 15000);
            if ($unitPrice <= 0) {
                $unitPrice = 15000;
            }

            $mealRate = (float) (($attData['meal_allowance_per_day'] ?? 0) > 0 ? $attData['meal_allowance_per_day'] : $defaultMealRate);
            $dailyRate = (float) ($attData['daily_rate'] ?? 0);
            $isHarian = (bool) ($attData['is_harian'] ?? false);

            $empSubsByDate = $submissions->get($empId, collect());
            $empAttsByDate = $attendances->get($empId, collect());
            $empSchedules = $schedules->get($empId, collect());

            foreach ($cycleDays as $cDay) {
                $dateStr = $cDay['date'];
                $dKey = $cDay['key'];

                $daySubs = $empSubsByDate->get($dateStr, collect());

                // Pisahkan pengajuan lembur: Lembur Per Jam (Hourly) vs Lembur Seharian (Daily)
                $hourlySubs = $daySubs->filter(fn ($s) => $s->isHourly());
                $dailySubs = $daySubs->filter(fn ($s) => $s->isDaily());

                $otHours = (float) $hourlySubs->sum('duration_hours');
                $holidayOtPay = (float) $dailySubs->sum(fn ($s) => (float) $s->subtotal_overtime);
                $otMeal = (float) $daySubs->sum('meal_allowance');

                // Hari Libur (Warna Merah) disesuaikan dengan Jadwal Kerja (Roster) masing-masing karyawan
                $sch = $empSchedules->get($dateStr);
                if ($sch) {
                    $isHoliday = $sch->isOff() || isset($holidays[$dateStr]);
                } else {
                    $isHoliday = isset($holidays[$dateStr]);
                    if (! $company->isRosterMode()) {
                        $isHoliday = $isHoliday || WorkingDays::isWeekend(Carbon::parse($dateStr), $workDaysPerWeek);
                    }
                }

                // Hadir jika ada log absensi atau tercentang di checklist
                $hasAttLog = $empAttsByDate->has($dateStr);
                $isInChecklist = in_array($dKey, $attData['attendance_days'] ?? [], true)
                    || in_array($dateStr, $attData['attendance_days'] ?? [], true);
                $isAttended = $hasAttLog || $isInChecklist;

                // Uang Makan Siang: Diberikan jika hadir kerja pada hari itu
                $lunch = $isAttended ? $mealRate : 0.0;

                // Uang Makan Malam: Diberikan jika lembur per jam >= threshold durasi perusahaan (default 3 jam) atau ada uang makan lembur
                $dinner = ($otHours >= $otMealThreshold || $otMeal > 0) ? ($otMeal > 0 ? $otMeal : $otMealAmount) : 0.0;

                // Upah harian untuk pegawai harian
                $wage = ($isHarian && $isAttended) ? $dailyRate : 0.0;

                $dailyHourlyOtPay = $otHours * $unitPrice;
                $dailyTotalOtPay = $dailyHourlyOtPay + $holidayOtPay;
                $dailyTotal = $dailyTotalOtPay + $lunch + $dinner + $wage;

                $totalOtHours += $otHours;
                $totalHolidayOtPay += $holidayOtPay;
                $totalLunch += $lunch;
                $totalDinner += $dinner;
                $totalDailyWage += $wage;

                $daysData[] = [
                    'date' => $dateStr,
                    'key' => $dKey,
                    'day_name' => $cDay['day_name'],
                    'date_formatted' => $cDay['date_formatted'],
                    'is_holiday' => $isHoliday,
                    'ot_hours' => $otHours,
                    'ot_hours_formatted' => $otHours > 0 ? (intval($otHours) == $otHours ? (string) intval($otHours) : number_format($otHours, 1)) : '',
                    'holiday_ot' => $holidayOtPay,
                    'holiday_ot_formatted' => $holidayOtPay > 0 ? 'Rp ' . number_format($holidayOtPay, 0, ',', '.') : '-',
                    'daily_ot_pay' => $dailyTotalOtPay,
                    'daily_ot_pay_formatted' => $dailyTotalOtPay > 0 ? 'Rp ' . number_format($dailyTotalOtPay, 0, ',', '.') : '-',
                    'lunch' => $lunch,
                    'lunch_formatted' => $lunch > 0 ? 'Rp ' . number_format($lunch, 0, ',', '.') : '-',
                    'dinner' => $dinner,
                    'dinner_formatted' => $dinner > 0 ? 'Rp ' . number_format($dinner, 0, ',', '.') : '',
                    'daily_total' => $dailyTotal,
                    'daily_total_formatted' => $dailyTotal > 0 ? 'Rp ' . number_format($dailyTotal, 0, ',', '.') : '',
                    'is_attended' => $isAttended,
                ];
            }

            $hourlyOtPay = $totalOtHours * $unitPrice;
            // Total Upah Lembur = (Total Jam Lembur * Tarif/Jam) + Total Upah Lembur Seharian (Hari Libur)
            $totalOtPay = $hourlyOtPay + $totalHolidayOtPay;
            $grandTotal = $totalOtPay + $totalLunch + $totalDinner + $totalDailyWage;

            $matrix[$empId] = [
                'employee_id' => $empId,
                'employee_hashid' => $attData['employee_hashid'] ?? '',
                'employee_name' => $attData['employee_name'],
                'photo_url' => $attData['photo_url'] ?? null,
                'department_name' => $attData['department_name'],
                'position_name' => $attData['position_name'],
                'payment_status' => $attData['payment_status'],
                'is_harian' => $isHarian,
                'unit_price' => $unitPrice,
                'unit_price_formatted' => 'Rp ' . number_format($unitPrice, 0, ',', '.'),
                'days' => $daysData,
                'totals' => [
                    'total_ot_hours' => $totalOtHours,
                    'total_ot_hours_formatted' => intval($totalOtHours) == $totalOtHours ? (string) intval($totalOtHours) : number_format($totalOtHours, 1),
                    'total_holiday_ot' => $totalHolidayOtPay,
                    'total_holiday_ot_formatted' => $totalHolidayOtPay > 0 ? 'Rp ' . number_format($totalHolidayOtPay, 0, ',', '.') : '-',
                    'hourly_ot_pay' => $hourlyOtPay,
                    'hourly_ot_pay_formatted' => $hourlyOtPay > 0 ? 'Rp ' . number_format($hourlyOtPay, 0, ',', '.') : '-',
                    'total_ot_pay' => $totalOtPay,
                    'total_ot_pay_formatted' => 'Rp ' . number_format($totalOtPay, 0, ',', '.'),
                    'total_lunch' => $totalLunch,
                    'total_lunch_formatted' => 'Rp ' . number_format($totalLunch, 0, ',', '.'),
                    'total_dinner' => $totalDinner,
                    'total_dinner_formatted' => $totalDinner > 0 ? 'Rp ' . number_format($totalDinner, 0, ',', '.') : '-',
                    'grand_total' => $grandTotal,
                    'grand_total_formatted' => 'Rp ' . number_format($grandTotal, 0, ',', '.'),
                ],
            ];
        }

        return $matrix;
    }

    /**
     * Peta label & ikon status kepegawaian untuk baris sub-header grup.
     */
    #[Computed]
    public function employmentStatusMeta(): array
    {
        $meta = [];
        foreach (EmploymentStatus::cases() as $status) {
            $meta[$status->value] = [
                'label' => $status->label(),
                'icon' => $status->icon(),
            ];
        }

        return $meta;
    }

    public function render(): View
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);

        // Filter displayed employees based on search & department
        $filteredAttendances = collect($this->periodAttendances)->filter(function ($data) {
            // Tampilkan jika memiliki hari kerja (> 0 hari) ATAU memiliki lembur (> 0 jam) ATAU hak gaji mingguan > 0
            $hasWork = (int) ($data['attended_days'] ?? 0) > 0;
            $hasOt = (float) ($data['overtime_hours'] ?? 0) > 0 || (float) ($data['overtime_pay'] ?? 0) > 0;
            $hasWage = (float) ($data['grand_total'] ?? 0) > 0;

            if (! $hasWork && ! $hasOt && ! $hasWage) {
                return false;
            }

            if ($this->departmentFilter && (int) $data['department_id'] !== (int) $this->departmentFilter) {
                return false;
            }

            if (! empty($this->search)) {
                $term = strtolower($this->search);
                $nameMatch = str_contains(strtolower($data['employee_name']), $term);
                $deptMatch = str_contains(strtolower($data['department_name']), $term);
                $posMatch = str_contains(strtolower($data['position_name']), $term);

                return $nameMatch || $deptMatch || $posMatch;
            }

            return true;
        })->toArray();

        // Filter daily matrix matching search & department
        $filteredMatrix = array_intersect_key($this->dailyMatrix, $filteredAttendances);

        // Matrix totals across all displayed employees
        $matrixTotalOtHours = array_sum(array_map(fn ($m) => (float) $m['totals']['total_ot_hours'], $filteredMatrix));
        $matrixTotalHolidayOt = array_sum(array_map(fn ($m) => (float) ($m['totals']['total_holiday_ot'] ?? 0), $filteredMatrix));
        $matrixTotalHourlyOtPay = array_sum(array_map(fn ($m) => (float) ($m['totals']['hourly_ot_pay'] ?? 0), $filteredMatrix));
        $matrixTotalOtPay = array_sum(array_map(fn ($m) => (float) ($m['totals']['total_ot_pay'] ?? 0), $filteredMatrix));
        $matrixTotalLunch = array_sum(array_map(fn ($m) => (float) $m['totals']['total_lunch'], $filteredMatrix));
        $matrixTotalDinner = array_sum(array_map(fn ($m) => (float) $m['totals']['total_dinner'], $filteredMatrix));
        $matrixGrandTotal = array_sum(array_map(fn ($m) => (float) $m['totals']['grand_total'], $filteredMatrix));

        // Calculate live summary stats from all active paid employees in this period
        $activeEmployeesCount = count($filteredAttendances);
        $totalDailyWage = array_sum(array_column($filteredAttendances, 'total_daily_wage'));
        $totalMealAllowance = array_sum(array_column($filteredAttendances, 'total_meal_allowance'));
        $totalOvertimePay = array_sum(array_column($filteredAttendances, 'overtime_pay'));
        $totalOvertimeMeal = array_sum(array_column($filteredAttendances, 'overtime_meal'));
        $totalOvertimeHours = array_sum(array_column($filteredAttendances, 'overtime_hours'));
        $grandTotal = $totalDailyWage + $totalMealAllowance + $totalOvertimePay + $totalOvertimeMeal;
        $totalDays = array_sum(array_column($filteredAttendances, 'attended_days'));
        $avgDays = $activeEmployeesCount > 0 ? round($totalDays / $activeEmployeesCount, 1) : 0;

        $statusOrder = [
            'tetap' => 1,
            'kontrak' => 2,
            'harian' => 3,
            'magang' => 4,
        ];

        $groupedAttendances = collect($filteredAttendances)
            ->groupBy(fn ($item) => $item['employment_status'] ?? 'tetap', preserveKeys: true)
            ->sortBy(fn ($items, $status) => $statusOrder[$status] ?? 99);

        return view('livewire.overtimes.period-attendance', [
            'filteredAttendances' => $filteredAttendances,
            'groupedAttendances' => $groupedAttendances,
            'filteredMatrix' => $filteredMatrix,
            'matrixTotalOtHours' => $matrixTotalOtHours,
            'matrixTotalHolidayOt' => $matrixTotalHolidayOt,
            'matrixTotalHourlyOtPay' => $matrixTotalHourlyOtPay,
            'matrixTotalOtPay' => $matrixTotalOtPay,
            'matrixTotalLunch' => $matrixTotalLunch,
            'matrixTotalDinner' => $matrixTotalDinner,
            'matrixGrandTotal' => $matrixGrandTotal,
            'cycleDays' => $this->cycleDays,
            'employmentStatusMeta' => $this->employmentStatusMeta,
            'departments' => $departments,
            'totalEmployees' => $activeEmployeesCount,
            'totalDailyWage' => $totalDailyWage,
            'totalMealAllowance' => $totalMealAllowance,
            'totalOvertimeMeal' => $totalOvertimeMeal,
            'totalOvertimePay' => $totalOvertimePay,
            'totalOvertimeHours' => $totalOvertimeHours,
            'grandTotal' => $grandTotal,
            'avgDays' => $avgDays,
        ]);
    }
}
