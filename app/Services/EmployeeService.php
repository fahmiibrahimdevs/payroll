<?php

namespace App\Services;

use App\Enums\PayrollPeriodStatus;
use App\Enums\SalaryAdjustmentType;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalaryHistory;
use App\Models\PayrollItem;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

class EmployeeService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Retrieve paginated employees with eager loading and optional filtering.
     */
    public function getPaginatedEmployees(
        string $search = '',
        ?int $departmentId = null,
        ?int $positionId = null,
        ?string $status = null,
        ?string $employmentStatus = null,
        int $perPage = 10,
        bool $includeSensitive = true
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 100));
        $detailColumns = $includeSensitive
            ? ['employee_id', 'employee_code', 'gender', 'birth_place', 'birth_date', 'address', 'phone', 'email', 'bank_name', 'bank_account_number', 'bank_account_name', 'curriculum_vitae']
            : ['employee_id', 'employee_code', 'gender', 'birth_place', 'birth_date', 'phone', 'email', 'address'];

        return Employee::query()
            ->with([
                'department:id,name',
                'position:id,name',
                'detail:'.implode(',', $detailColumns),
            ])
            ->when($search, fn ($q) => $q->search($search))
            ->when($departmentId, fn ($q) => $q->department($departmentId))
            ->when($positionId, fn ($q) => $q->position($positionId))
            ->when($status, fn ($q) => $q->status($status))
            ->when($employmentStatus, fn ($q) => $q->employmentStatus($employmentStatus))
            ->orderBy(
                Department::select('name')->whereColumn('departments.id', 'employees.department_id')
            )
            ->orderBy(
                Position::selectRaw("
                    CASE
                        WHEN LOWER(name) LIKE '%direktur%' OR LOWER(name) LIKE '%director%' THEN 1
                        WHEN LOWER(name) LIKE '%manager%' OR LOWER(name) LIKE '%kepala%' OR LOWER(name) LIKE '%head%' THEN 2
                        WHEN LOWER(name) LIKE '%supervisor%' OR LOWER(name) LIKE '%koordinator%' OR LOWER(name) LIKE '%lead%' THEN 3
                        WHEN LOWER(name) LIKE '%magang%' OR LOWER(name) LIKE '%pkl%' OR LOWER(name) LIKE '%intern%' THEN 5
                        ELSE 4
                    END
                ")->whereColumn('positions.id', 'employees.position_id')
            )
            ->orderByRaw("
                CASE employees.employment_status
                    WHEN 'tetap' THEN 1
                    WHEN 'kontrak' THEN 2
                    WHEN 'magang' THEN 3
                    ELSE 4
                END ASC
            ")
            ->orderByRaw('
                CASE
                    WHEN employees.contract_end_date IS NULL THEN 1
                    ELSE 0
                END ASC,
                employees.contract_end_date ASC
            ')
            ->latest('employees.id')
            ->paginate($perPage);
    }

    /**
     * Generate a unique sequential employee code (e.g. EMP-2026-0001).
     */
    public function generateEmployeeCode(): string
    {
        $year = date('Y');
        $sequenceKey = "employee-{$year}";
        $callback = function () use ($year, $sequenceKey): string {
            $latest = DB::table('employee_details')
                ->where('employee_code', 'like', "EMP-{$year}-%")
                ->orderByDesc('employee_code')
                ->value('employee_code');
            $initialNumber = $latest && preg_match('/(\d{4,})$/', (string) $latest, $matches)
                ? (int) $matches[1]
                : 0;

            DB::table('employee_sequences')->insertOrIgnore([
                'sequence_key' => $sequenceKey,
                'last_number' => $initialNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('employee_sequences')
                ->where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->first();
            $nextNumber = max((int) $sequence->last_number, $initialNumber) + 1;

            DB::table('employee_sequences')
                ->where('sequence_key', $sequenceKey)
                ->update(['last_number' => $nextNumber, 'updated_at' => now()]);

            return sprintf('EMP-%s-%04d', $year, $nextNumber);
        };

        return DB::transactionLevel() > 0 ? $callback() : DB::transaction($callback);
    }

    /**
     * Create a new employee and associated detail record within a transaction.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createEmployee(array $data, ?UploadedFile $photo = null, ?UploadedFile $cv = null): Employee
    {
        $photoPath = null;
        $cvPath = null;

        try {
            if ($photo instanceof UploadedFile) {
                $photoPath = $photo->store('employees/photos', 'public');
            }

            if ($cv instanceof UploadedFile) {
                $cvPath = $cv->store('employees/cvs', 'local');
            }

            return DB::transaction(function () use ($data, $photoPath, $cvPath) {
                $employeeData = [
                    'department_id' => ! empty($data['department_id']) ? (int) $data['department_id'] : null,
                    'position_id' => ! empty($data['position_id']) ? (int) $data['position_id'] : null,
                    'full_name' => $data['full_name'],
                    'join_date' => $data['join_date'],
                    'contract_end_date' => ! empty($data['contract_end_date']) ? $data['contract_end_date'] : null,
                    'employment_status' => $data['employment_status'] ?? 'tetap',
                    'status' => $data['status'] ?? 'active',
                    'basic_salary' => ! empty($data['basic_salary']) ? (float) $data['basic_salary'] : 0,
                    'photo_path' => $photoPath,
                ];

                $employee = Employee::create($employeeData);

                $employeeCode = ! empty($data['employee_code']) ? $data['employee_code'] : $this->generateEmployeeCode();

                $detailData = [
                    'employee_id' => $employee->id,
                    'employee_code' => $employeeCode,
                    'gender' => $data['gender'] ?? 'male',
                    'birth_place' => $data['birth_place'] ?? null,
                    'birth_date' => ! empty($data['birth_date']) ? $data['birth_date'] : null,
                    'address' => $data['address'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => ! empty($data['email']) ? $data['email'] : (! empty($data['full_name']) ? CompanyProfile::generateEmployeeEmail($data['full_name']) : null),
                    'bank_name' => $data['bank_name'] ?? null,
                    'bank_account_number' => $data['bank_account_number'] ?? null,
                    'bank_account_name' => $data['bank_account_name'] ?? null,
                    'curriculum_vitae' => $cvPath,
                    'marital_status' => $data['marital_status'] ?? 'tk',
                    'dependents_count' => isset($data['dependents_count']) ? (int) $data['dependents_count'] : 0,
                    'tax_method' => $data['tax_method'] ?? 'gross',
                    'has_npwp' => ! empty($data['npwp_number']),
                    'npwp_number' => ! empty($data['npwp_number']) ? trim((string) $data['npwp_number']) : null,
                    'nik' => ! empty($data['nik']) ? trim((string) $data['nik']) : null,
                    'bpjs_kesehatan_number' => ! empty($data['bpjs_kesehatan_number']) ? trim((string) $data['bpjs_kesehatan_number']) : null,
                    'bpjs_ketenagakerjaan_number' => ! empty($data['bpjs_ketenagakerjaan_number']) ? trim((string) $data['bpjs_ketenagakerjaan_number']) : null,
                ];

                $employee->detail()->create($detailData);

                if ((float) $employee->basic_salary > 0) {
                    EmployeeSalaryHistory::create([
                        'employee_id' => $employee->id,
                        'previous_salary' => 0,
                        'new_salary' => (float) $employee->basic_salary,
                        'change_amount' => (float) $employee->basic_salary,
                        'percentage_change' => 0,
                        'effective_date' => $employee->join_date ? Carbon::parse($employee->join_date) : Carbon::today(),
                        'type' => SalaryAdjustmentType::INITIAL,
                        'reason' => 'Gaji awal saat karyawan bergabung',
                        'changed_by' => auth()->id(),
                    ]);
                }

                $this->logger->info(
                    action: 'CREATE',
                    module: 'employees',
                    message: 'Employee created',
                    context: [
                        'employee_id' => $employee->id,
                        'full_name' => $employee->full_name,
                        'employee_code' => $employeeCode,
                        'department_id' => $employee->department_id,
                        'position_id' => $employee->position_id,
                    ]
                );

                return $employee->load(['department', 'position', 'detail']);
            });
        } catch (Throwable $e) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            if ($cvPath && Storage::disk('local')->exists($cvPath)) {
                Storage::disk('local')->delete($cvPath);
            }

            $this->logger->error(
                action: 'CREATE_FAILED',
                module: 'employees',
                message: 'Employee creation failed',
                context: [
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Update an existing employee and detail record within a transaction.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateEmployee(Employee $employee, array $data, ?UploadedFile $photo = null, ?UploadedFile $cv = null): Employee
    {
        $employee->loadMissing('detail');
        $newPhotoPath = null;
        $newCvPath = null;
        $oldPhotoPath = $employee->photo_path;
        $oldCvPath = $employee->detail?->curriculum_vitae;

        try {
            if ($photo instanceof UploadedFile) {
                $newPhotoPath = $photo->store('employees/photos', 'public');
            }

            if ($cv instanceof UploadedFile) {
                $newCvPath = $cv->store('employees/cvs', 'local');
            }

            return DB::transaction(function () use ($employee, $data, $newPhotoPath, $newCvPath, $oldPhotoPath, $oldCvPath) {
                $photoPath = $employee->photo_path;
                if ($newPhotoPath) {
                    $photoPath = $newPhotoPath;
                }

                $cvPath = $employee->detail?->curriculum_vitae;
                if ($newCvPath) {
                    $cvPath = $newCvPath;
                }

                $oldBasicSalary = (float) $employee->basic_salary;
                $newBasicSalary = isset($data['basic_salary']) ? (float) $data['basic_salary'] : $oldBasicSalary;

                $employeeData = [
                    'department_id' => ! empty($data['department_id']) ? (int) $data['department_id'] : null,
                    'position_id' => ! empty($data['position_id']) ? (int) $data['position_id'] : null,
                    'full_name' => $data['full_name'],
                    'join_date' => $data['join_date'],
                    'contract_end_date' => ! empty($data['contract_end_date']) ? $data['contract_end_date'] : null,
                    'employment_status' => $data['employment_status'] ?? $employee->employment_status,
                    'status' => $data['status'] ?? $employee->status,
                    'basic_salary' => $newBasicSalary,
                    'photo_path' => $photoPath,
                ];

                $oldValues = $employee->only(['full_name', 'department_id', 'position_id', 'status', 'employment_status', 'basic_salary']);

                $employee->update($employeeData);

                if (abs($newBasicSalary - $oldBasicSalary) >= 0.01) {
                    $changeAmount = $newBasicSalary - $oldBasicSalary;
                    $percentageChange = $oldBasicSalary > 0 ? round(($changeAmount / $oldBasicSalary) * 100, 2) : 0;
                    $adjustmentType = isset($data['salary_adjustment_type'])
                        ? ($data['salary_adjustment_type'] instanceof SalaryAdjustmentType
                            ? $data['salary_adjustment_type']
                            : SalaryAdjustmentType::tryFrom((string) $data['salary_adjustment_type']) ?? SalaryAdjustmentType::OTHER)
                        : SalaryAdjustmentType::OTHER;
                    $adjustmentReason = $data['salary_adjustment_reason'] ?? 'Penyesuaian gaji dari perubahan data profil karyawan';
                    $effectiveDate = ! empty($data['salary_effective_date']) ? Carbon::parse($data['salary_effective_date']) : Carbon::today();

                    EmployeeSalaryHistory::create([
                        'employee_id' => $employee->id,
                        'previous_salary' => $oldBasicSalary,
                        'new_salary' => $newBasicSalary,
                        'change_amount' => $changeAmount,
                        'percentage_change' => $percentageChange,
                        'effective_date' => $effectiveDate,
                        'type' => $adjustmentType,
                        'reason' => $adjustmentReason,
                        'changed_by' => auth()->id(),
                    ]);
                }

                $detailData = [
                    'employee_code' => ! empty($data['employee_code']) ? $data['employee_code'] : ($employee->detail?->employee_code ?? $this->generateEmployeeCode()),
                    'gender' => $data['gender'] ?? ($employee->detail?->gender ?? 'male'),
                    'birth_place' => $data['birth_place'] ?? null,
                    'birth_date' => ! empty($data['birth_date']) ? $data['birth_date'] : null,
                    'address' => $data['address'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'bank_account_number' => $data['bank_account_number'] ?? null,
                    'bank_account_name' => $data['bank_account_name'] ?? null,
                    'curriculum_vitae' => $cvPath,
                    'marital_status' => $data['marital_status'] ?? ($employee->detail?->marital_status ?? 'tk'),
                    'dependents_count' => isset($data['dependents_count']) ? (int) $data['dependents_count'] : ($employee->detail?->dependents_count ?? 0),
                    'tax_method' => $data['tax_method'] ?? ($employee->detail?->tax_method ?? 'gross'),
                    'has_npwp' => ! empty($data['npwp_number']),
                    'npwp_number' => ! empty($data['npwp_number']) ? trim((string) $data['npwp_number']) : null,
                    'nik' => ! empty($data['nik']) ? trim((string) $data['nik']) : null,
                    'bpjs_kesehatan_number' => ! empty($data['bpjs_kesehatan_number']) ? trim((string) $data['bpjs_kesehatan_number']) : null,
                    'bpjs_ketenagakerjaan_number' => ! empty($data['bpjs_ketenagakerjaan_number']) ? trim((string) $data['bpjs_ketenagakerjaan_number']) : null,
                ];

                $employee->detail()->updateOrCreate(
                    ['employee_id' => $employee->id],
                    $detailData
                );

                // Sinkronisasi otomatis slip gaji yang masih draf / revisi jika data karyawan (gaji pokok, ptkp, pajak, bank) berubah
                $editablePayrollItems = PayrollItem::where('employee_id', $employee->id)
                    ->whereNull('approved_at')
                    ->whereHas('payrollPeriod', function ($q) {
                        $q->whereIn('status', [
                            PayrollPeriodStatus::DRAFT->value,
                            PayrollPeriodStatus::REJECTED->value,
                        ]);
                    })
                    ->with('payrollPeriod')
                    ->get();

                if ($editablePayrollItems->isNotEmpty()) {
                    $employee->refresh();
                    $payrollService = app(PayrollService::class);
                    $company = CompanyProfile::getProfile();
                    $touchedPeriods = [];
                    foreach ($editablePayrollItems as $pItem) {
                        $salaryInfo = $payrollService->calculateProratedSalary($employee, $pItem->payrollPeriod);
                        $pItem->update([
                            'basic_salary' => (float) $salaryInfo['basic_salary'],
                            'bank_name' => $employee->detail?->bank_name,
                            'bank_account_number' => $employee->detail?->bank_account_number,
                            'bank_account_name' => $employee->detail?->bank_account_name,
                        ]);

                        $payrollService->recalculateItemTax($pItem, $company);
                        $payrollService->recalculateItem($pItem);
                        $touchedPeriods[$pItem->payroll_period_id] = $pItem->payrollPeriod;
                    }
                    foreach ($touchedPeriods as $period) {
                        if ($period) {
                            $payrollService->recalculatePeriod($period);
                        }
                    }
                }

                $this->logger->info(
                    action: 'UPDATE',
                    module: 'employees',
                    message: 'Employee updated',
                    context: [
                        'employee_id' => $employee->id,
                        'old_values' => $oldValues,
                        'new_values' => $employee->only(['full_name', 'department_id', 'position_id', 'status', 'employment_status']),
                    ]
                );

                DB::afterCommit(function () use ($oldPhotoPath, $oldCvPath, $photoPath, $cvPath): void {
                    if ($photoPath !== $oldPhotoPath && $oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
                        Storage::disk('public')->delete($oldPhotoPath);
                    }

                    if ($cvPath !== $oldCvPath && $oldCvPath && Storage::disk('local')->exists($oldCvPath)) {
                        Storage::disk('local')->delete($oldCvPath);
                    }

                    if ($cvPath !== $oldCvPath && $oldCvPath && Storage::disk('public')->exists($oldCvPath)) {
                        Storage::disk('public')->delete($oldCvPath);
                    }
                });

                return $employee->fresh(['department', 'position', 'detail']);
            });
        } catch (Throwable $e) {
            if ($newPhotoPath && Storage::disk('public')->exists($newPhotoPath)) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            if ($newCvPath && Storage::disk('local')->exists($newCvPath)) {
                Storage::disk('local')->delete($newCvPath);
            }

            $this->logger->error(
                action: 'UPDATE_FAILED',
                module: 'employees',
                message: 'Employee update failed',
                context: [
                    'employee_id' => $employee->id,
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Archive an employee while retaining details and financial history.
     *
     * @throws Throwable
     */
    public function deleteEmployee(Employee $employee): bool
    {
        $employee->loadMissing('detail');

        try {
            return DB::transaction(function () use ($employee) {
                $deletedInfo = [
                    'employee_id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'employee_code' => $employee->detail?->employee_code,
                ];

                // Remove employee from any editable draft/rejected payroll periods
                $editablePayrollItems = $employee->payrollItems()
                    ->whereHas('payrollPeriod', function ($q) {
                        $q->whereIn('status', [
                            PayrollPeriodStatus::DRAFT->value,
                            PayrollPeriodStatus::REJECTED->value,
                        ]);
                    })
                    ->get();

                if ($editablePayrollItems->isNotEmpty()) {
                    $payrollService = app(PayrollService::class);
                    $touchedPeriods = [];
                    foreach ($editablePayrollItems as $pItem) {
                        $touchedPeriods[$pItem->payroll_period_id] = $pItem->payrollPeriod;
                        $pItem->lines()->delete();
                        $pItem->delete();
                    }
                    foreach ($touchedPeriods as $period) {
                        if ($period) {
                            $payrollService->recalculatePeriod($period);
                        }
                    }
                }

                $deleted = $employee->delete();

                $this->logger->info(
                    action: 'DELETE',
                    module: 'employees',
                    message: 'Employee deleted',
                    context: $deletedInfo
                );

                return $deleted;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'DELETE_FAILED',
                module: 'employees',
                message: 'Employee deletion failed',
                context: [
                    'employee_id' => $employee->id,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Record a dedicated salary adjustment for an employee.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException|Throwable
     */
    public function recordSalaryAdjustment(Employee $employee, array $data, ?User $user = null): EmployeeSalaryHistory
    {
        $previousSalary = (float) $employee->basic_salary;
        $newSalary = (float) ($data['new_salary'] ?? 0);

        if ($newSalary < 0) {
            throw new InvalidArgumentException('Gaji baru tidak boleh negatif.');
        }

        $changeAmount = $newSalary - $previousSalary;
        $percentageChange = $previousSalary > 0
            ? round(($changeAmount / $previousSalary) * 100, 2)
            : 0;

        $type = isset($data['type'])
            ? ($data['type'] instanceof SalaryAdjustmentType
                ? $data['type']
                : SalaryAdjustmentType::tryFrom((string) $data['type']) ?? SalaryAdjustmentType::OTHER)
            : SalaryAdjustmentType::OTHER;

        $effectiveDate = ! empty($data['effective_date'])
            ? Carbon::parse($data['effective_date'])
            : Carbon::today();

        $changedBy = $user?->id ?? auth()->id();

        return DB::transaction(function () use ($employee, $previousSalary, $newSalary, $changeAmount, $percentageChange, $effectiveDate, $type, $data, $changedBy) {
            $history = EmployeeSalaryHistory::create([
                'employee_id' => $employee->id,
                'previous_salary' => $previousSalary,
                'new_salary' => $newSalary,
                'change_amount' => $changeAmount,
                'percentage_change' => $percentageChange,
                'effective_date' => $effectiveDate,
                'type' => $type,
                'reason' => $data['reason'] ?? null,
                'changed_by' => $changedBy,
            ]);

            // Update employee's basic salary
            $employee->update(['basic_salary' => $newSalary]);

            // Sinkronisasi otomatis slip gaji yang masih draf / revisi jika ada
            $editablePayrollItems = PayrollItem::where('employee_id', $employee->id)
                ->whereNull('approved_at')
                ->whereHas('payrollPeriod', function ($q) {
                    $q->whereIn('status', [
                        PayrollPeriodStatus::DRAFT->value,
                        PayrollPeriodStatus::REJECTED->value,
                    ]);
                })
                ->with('payrollPeriod')
                ->get();

            if ($editablePayrollItems->isNotEmpty()) {
                $employee->refresh();
                $payrollService = app(PayrollService::class);
                $company = CompanyProfile::getProfile();
                $touchedPeriods = [];
                foreach ($editablePayrollItems as $pItem) {
                    $salaryInfo = $payrollService->calculateProratedSalary($employee, $pItem->payrollPeriod);
                    $pItem->update([
                        'basic_salary' => (float) $salaryInfo['basic_salary'],
                    ]);
                    $payrollService->recalculateItemTax($pItem, $company);
                    $payrollService->recalculateItem($pItem);
                    $touchedPeriods[$pItem->payroll_period_id] = $pItem->payrollPeriod;
                }
                foreach ($touchedPeriods as $period) {
                    if ($period) {
                        $payrollService->recalculatePeriod($period);
                    }
                }
            }

            $this->logger->info(
                action: 'SALARY_ADJUSTMENT',
                module: 'employees',
                message: "Salary adjusted for employee #{$employee->id} ({$employee->full_name})",
                context: [
                    'employee_id' => $employee->id,
                    'previous_salary' => $previousSalary,
                    'new_salary' => $newSalary,
                    'change_amount' => $changeAmount,
                    'percentage_change' => $percentageChange,
                    'effective_date' => $effectiveDate->toDateString(),
                    'type' => $type->value,
                    'reason' => $data['reason'] ?? null,
                    'changed_by' => $changedBy,
                ]
            );

            return $history;
        });
    }
}
