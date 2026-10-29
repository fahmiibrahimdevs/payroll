<?php

namespace App\Livewire\Overtimes;

use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OvertimePeriod;
use App\Models\OvertimePeriodAttendance;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Kehadiran & Uang Makan Mingguan - Larashiz HRM')]
class PeriodAttendance extends Component
{
    use WithFileUploads;

    public OvertimePeriod $period;

    public string $search = '';

    public ?int $departmentFilter = null;

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
        $this->syncActiveEmployees();
        $this->loadAttendances();
    }

    /**
     * Ensure all active employees (and any linked in overtime) have attendance records in this period.
     */
    protected function syncActiveEmployees(): void
    {
        $linkedEmpIds = $this->period->submissions()->pluck('employee_id')->unique()->all();

        $startDate = \Carbon\Carbon::parse($this->period->start_date)->toDateString();
        $endDate = \Carbon\Carbon::parse($this->period->end_date)->toDateString();
        $attendances = \App\Models\Attendance::whereBetween('date', [$startDate, $endDate])
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
        $defaultDaysCount = 6;
        $defaultDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $hasNew = false;

        $dayKeyMap = [
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            0 => 'sun',
        ];

        foreach ($targetEmployees as $emp) {
            if (! in_array($emp->id, $existingEmpIds, true)) {
                $rate = $emp->getEffectiveMealAllowancePerDay();
                $isHarian = $emp->employment_status === EmploymentStatus::HARIAN;
                $dailyRate = $isHarian ? (float) ($emp->daily_rate ?? 0) : 0.0;
                $hasOvertime = in_array($emp->id, $linkedEmpIds, true);

                if ($hasAttendanceLog) {
                    $empAtts = $attendances->get($emp->id);
                    $attendedDaysKeys = [];
                    if ($empAtts && $empAtts->isNotEmpty()) {
                        foreach ($empAtts as $att) {
                            $dow = \Carbon\Carbon::parse($att->date)->dayOfWeek;
                            $k = $dayKeyMap[$dow] ?? null;
                            if ($k && ! in_array($k, $attendedDaysKeys, true)) {
                                $attendedDaysKeys[] = $k;
                            }
                        }
                    }
                    $daysCount = count($attendedDaysKeys);
                    $daysList = $attendedDaysKeys;

                    // Lewati karyawan tanpa kehadiran dan tanpa lembur
                    if ($daysCount <= 0 && ! $hasOvertime) {
                        continue;
                    }
                } else {
                    $daysCount = $defaultDaysCount;
                    $daysList = $defaultDays;
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
            $grandTotal = round($totalDailyWage + $totalMealAllowance + $overtimePay, 2);

            $this->periodAttendances[$att->employee_id] = [
                'attendance_id' => (int) $att->id,
                'employee_id' => (int) $att->employee_id,
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
                'total_daily_wage' => $totalDailyWage,
                'overtime_hours' => $overtimeHours,
                'overtime_pay' => $overtimePay,
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
        if (! isset($this->periodAttendances[$employeeId])) return;

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
        if (! isset($this->periodAttendances[$employeeId])) return;

        $count = max(0, min(6, (int) $count));
        $allDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $selectedDays = array_slice($allDays, 0, $count);

        $this->periodAttendances[$employeeId]['attended_days'] = $count;
        $this->periodAttendances[$employeeId]['attendance_days'] = $selectedDays;
        $this->recalcEmployeeMealTotal($employeeId);
    }

    /**
     * 1-click shortcut: set all 6 days present for an employee.
     */
    public function setAllDaysPresent(int $employeeId): void
    {
        $this->updateAttendedDaysCount($employeeId, 6);
    }

    /**
     * Bulk 1-click shortcut: set ALL employees to full 6 days.
     */
    public function setAllEmployeesFullWeek(): void
    {
        $allDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        foreach ($this->periodAttendances as $empId => $data) {
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
    public function syncFromAttendance(\App\Services\AttendanceService $attendanceService): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $syncedCount = $attendanceService->syncToWeeklyOvertimePeriod($this->period, auth()->user());
        $this->loadAttendances();

        $this->dispatch('swal:alert', [
            'type' => 'success',
            'title' => 'Sinkronisasi Absensi Selesai',
            'message' => "Data kehadiran {$syncedCount} karyawan telah berhasil disinkronisasi dari log mesin fingerprint.",
        ]);
    }

    /**
     * Update employee's meal allowance per day rate.
     */
    public function updateMealAllowanceRate(int $employeeId, mixed $rate): void
    {
        if (! isset($this->periodAttendances[$employeeId])) return;

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

        \Illuminate\Support\Facades\DB::transaction(function () use ($service) {
            foreach ($this->periodAttendances as $employeeId => $data) {
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
                $proofPath = $this->itemPaymentProof->store('payroll-proofs', 'public');
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

        // Calculate live summary stats from all active paid employees in this period
        $activeEmployeesCount = count($filteredAttendances);
        $totalDailyWage = array_sum(array_column($filteredAttendances, 'total_daily_wage'));
        $totalMealAllowance = array_sum(array_column($filteredAttendances, 'total_meal_allowance'));
        $totalOvertimePay = array_sum(array_column($filteredAttendances, 'overtime_pay'));
        $totalOvertimeHours = array_sum(array_column($filteredAttendances, 'overtime_hours'));
        $grandTotal = $totalDailyWage + $totalMealAllowance + $totalOvertimePay;
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
            'departments' => $departments,
            'totalEmployees' => $activeEmployeesCount,
            'totalDailyWage' => $totalDailyWage,
            'totalMealAllowance' => $totalMealAllowance,
            'totalOvertimePay' => $totalOvertimePay,
            'totalOvertimeHours' => $totalOvertimeHours,
            'grandTotal' => $grandTotal,
            'avgDays' => $avgDays,
        ]);
    }
}
