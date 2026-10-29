<?php

namespace App\Models;

use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fingerprint_id',
        'user_id',
        'department_id',
        'position_id',
        'full_name',
        'join_date',
        'contract_end_date',
        'employment_status',
        'status',
        'basic_salary',
        'daily_rate',
        'overtime_rate_per_hour',
        'overtime_rate_per_day',
        'meal_allowance_type',
        'meal_allowance_per_day',
        'photo_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'contract_end_date' => 'date',
            'employment_status' => EmploymentStatus::class,
            'status' => EmployeeStatus::class,
            'basic_salary' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'overtime_rate_per_hour' => 'decimal:2',
            'overtime_rate_per_day' => 'decimal:2',
            'meal_allowance_per_day' => 'decimal:2',
            'total_cash_receipt' => 'decimal:2',
        ];
    }

    /**
     * Limit employees to those whose employment overlaps a payroll period.
     */
    public function scopeEmployedDuring(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query
            ->where(function (Builder $query) use ($endDate): void {
                $query->whereNull('join_date')
                    ->orWhereDate('join_date', '<=', $endDate);
            })
            ->where(function (Builder $query) use ($startDate): void {
                $query->whereNull('contract_end_date')
                    ->orWhereDate('contract_end_date', '>=', $startDate);
            });
    }

    /**
     * Get the user account linked to the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department that the employee belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position that the employee belongs to.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the personal detail record associated with the employee.
     */
    public function detail(): HasOne
    {
        return $this->hasOne(EmployeeDetail::class);
    }

    /**
     * Get the cash advances associated with the employee.
     */
    public function cashAdvances(): HasMany
    {
        return $this->hasMany(CashAdvance::class);
    }

    /**
     * Get the payroll items associated with the employee.
     */
    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function overtimeSubmissions(): HasMany
    {
        return $this->hasMany(OvertimeSubmission::class);
    }

    public function periodAttendances(): HasMany
    {
        return $this->hasMany(OvertimePeriodAttendance::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the most recent payroll item for the employee.
     */
    public function latestPayrollItem(): HasOne
    {
        return $this->hasOne(PayrollItem::class)->latestOfMany();
    }

    /**
     * Get the salary change history for the employee.
     */
    public function salaryHistories(): HasMany
    {
        return $this->hasMany(EmployeeSalaryHistory::class)
            ->orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Get the most recent salary history.
     */
    public function latestSalaryHistory(): HasOne
    {
        return $this->hasOne(EmployeeSalaryHistory::class)
            ->ofMany(['effective_date' => 'max', 'id' => 'max']);
    }

    /**
     * Get the date of the last salary change / increase.
     */
    protected function lastSalaryChangeDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $latest = $this->relationLoaded('salaryHistories')
                    ? $this->salaryHistories->first()
                    : $this->salaryHistories()->first();

                return $latest?->effective_date ?? $this->join_date;
            }
        );
    }

    /**
     * Get progression status alert for salary increases.
     *
     * @return array{label: string, badge_class: string, months: int, note: string}
     */
    protected function salaryProgressionStatus(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $refDate = $this->last_salary_change_date ?? $this->join_date ?? now();
                $diffMonths = (int) $refDate->diffInMonths(now());

                if ($diffMonths <= 3) {
                    return [
                        'status' => 'recently_raised',
                        'label' => 'Baru Naik Gaji',
                        'badge_class' => 'badge badge-success',
                        'months' => $diffMonths,
                        'note' => $diffMonths === 0 ? 'Bulan ini baru disesuaikan' : "{$diffMonths} bulan lalu disesuaikan",
                    ];
                }

                if ($diffMonths >= 12) {
                    $years = floor($diffMonths / 12);
                    $rem = $diffMonths % 12;
                    $timeStr = $years > 0 ? "{$years} thn ".($rem > 0 ? "{$rem} bln" : '') : "{$diffMonths} bln";

                    return [
                        'status' => 'stagnant',
                        'label' => 'Belum Naik Gaji > 1 Thn',
                        'badge_class' => 'badge badge-warning',
                        'months' => $diffMonths,
                        'note' => "Sudah {$timeStr} belum ada penyesuaian gaji",
                    ];
                }

                return [
                    'status' => 'normal',
                    'label' => 'Terakhir Naik: '.$diffMonths.' bln lalu',
                    'badge_class' => 'badge badge-light border text-dark',
                    'months' => $diffMonths,
                    'note' => "{$diffMonths} bulan sejak penyesuaian terakhir",
                ];
            }
        );
    }

    /**
     * Get the badge class for the employee status.
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->status instanceof EmployeeStatus
                ? $this->status->badgeClass()
                : 'badge badge-secondary',
        );
    }

    /**
     * Get the display label for the employee status.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->status instanceof EmployeeStatus
                ? $this->status->label()
                : (string) $this->status,
        );
    }

    /**
     * Get the badge class for the employment status.
     */
    protected function employmentStatusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->employment_status instanceof EmploymentStatus
                ? $this->employment_status->badgeClass()
                : 'badge badge-secondary',
        );
    }

    /**
     * Get the text color for the employment status.
     */
    protected function employmentStatusTextColor(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->employment_status instanceof EmploymentStatus
                ? $this->employment_status->textColor()
                : '#475569',
        );
    }

    /**
     * Get the display label for the employment status.
     */
    protected function employmentStatusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->employment_status instanceof EmploymentStatus
                ? $this->employment_status->label()
                : (string) $this->employment_status,
        );
    }

    /**
     * Get formatted Indonesian rupiah currency for basic salary.
     */
    protected function formattedBasicSalary(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->basic_salary ?? 0), 0, ',', '.'),
        );
    }

    /**
     * Get formatted Indonesian rupiah currency for daily wage / rate.
     */
    protected function formattedDailyRate(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->daily_rate ?? 0), 0, ',', '.'),
        );
    }

    /**
     * Get formatted Indonesian rupiah currency for overtime rate per hour.
     */
    protected function formattedOvertimeRatePerHour(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->overtime_rate_per_hour ?? 0), 0, ',', '.'),
        );
    }

    /**
     * Get formatted Indonesian rupiah currency for daily overtime rate.
     */
    protected function formattedOvertimeRatePerDay(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->overtime_rate_per_day ?? 0), 0, ',', '.'),
        );
    }

    /**
     * Get effective meal allowance per day (respecting company uniform policy vs individual custom rate).
     */
    public function getEffectiveMealAllowancePerDay(): float
    {
        $company = CompanyProfile::getProfile();

        if ($company->is_uniform_meal_allowance ?? true) {
            return (float) ($company->default_meal_allowance_per_day ?? 25000);
        }

        if ($this->meal_allowance_type === 'khusus' && (float) $this->meal_allowance_per_day > 0) {
            return (float) $this->meal_allowance_per_day;
        }

        if ((float) $this->meal_allowance_per_day > 0) {
            return (float) $this->meal_allowance_per_day;
        }

        return (float) ($company->default_meal_allowance_per_day ?? 25000);
    }

    /**
     * Check if employee uses special/custom meal allowance rate.
     */
    public function isKhususMealAllowance(): bool
    {
        $company = CompanyProfile::getProfile();

        if ($company->is_uniform_meal_allowance ?? true) {
            return false;
        }

        return $this->meal_allowance_type === 'khusus';
    }

    /**
     * Get formatted Indonesian rupiah currency for meal allowance per day.
     */
    protected function formattedMealAllowancePerDay(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format($this->getEffectiveMealAllowancePerDay(), 0, ',', '.'),
        );
    }

    /**
     * Get the total accumulated cash advance amount (approved/active/paid).
     */
    protected function totalCashAdvanceAmount(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if ($this->relationLoaded('cashAdvances')) {
                    $total = (float) $this->cashAdvances
                        ->filter(fn ($ca) => in_array($ca->status, [
                            CashAdvanceStatus::APPROVED,
                            CashAdvanceStatus::ACTIVE,
                            CashAdvanceStatus::PAID,
                        ], true) || in_array($ca->status?->value ?? (string) $ca->status, [
                            CashAdvanceStatus::APPROVED->value,
                            CashAdvanceStatus::ACTIVE->value,
                            CashAdvanceStatus::PAID->value,
                        ], true))
                        ->sum('amount');

                    if ($total > 0 || $this->cashAdvances->isNotEmpty()) {
                        return $total;
                    }
                } elseif ($this->exists && $this->cashAdvances()->exists()) {
                    return (float) $this->cashAdvances()
                        ->whereIn('status', [
                            CashAdvanceStatus::APPROVED->value,
                            CashAdvanceStatus::ACTIVE->value,
                            CashAdvanceStatus::PAID->value,
                        ])
                        ->sum('amount');
                }

                return (float) ($this->total_cash_receipt ?? 0);
            }
        );
    }

    /**
     * Get formatted Indonesian rupiah currency for total cash receipt.
     */
    protected function formattedTotalCashReceipt(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->total_cash_advance_amount ?? 0), 0, ',', '.'),
        );
    }

    /**
     * Get remaining cash advance balance for this employee.
     */
    protected function remainingCashAdvance(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if ($this->relationLoaded('cashAdvances')) {
                    return (float) $this->cashAdvances
                        ->filter(fn ($ca) => in_array($ca->status, [
                            CashAdvanceStatus::APPROVED,
                            CashAdvanceStatus::ACTIVE,
                        ], true) || in_array($ca->status?->value ?? (string) $ca->status, [
                            CashAdvanceStatus::APPROVED->value,
                            CashAdvanceStatus::ACTIVE->value,
                        ], true))
                        ->sum('remaining_amount');
                }

                if ($this->exists) {
                    return (float) $this->cashAdvances()
                        ->whereIn('status', [
                            CashAdvanceStatus::APPROVED->value,
                            CashAdvanceStatus::ACTIVE->value,
                        ])
                        ->sum('remaining_amount');
                }

                return 0.0;
            }
        );
    }

    /**
     * Get formatted Indonesian rupiah currency for remaining cash advance.
     */
    protected function formattedRemainingCashAdvance(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->remaining_cash_advance ?? 0), 0, ',', '.'),
        );
    }

    /**
     * Get the remaining contract duration display label.
     */
    protected function remainingContractLabel(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->employment_status === EmploymentStatus::TETAP || ! $this->contract_end_date) {
                    return '-';
                }

                $today = now()->startOfDay();
                $endDate = $this->contract_end_date->copy()->startOfDay();

                if ($today->gt($endDate)) {
                    return 'Kontrak Berakhir';
                }

                $diffInDays = $today->diffInDays($endDate);

                if ($diffInDays === 0) {
                    return 'Hari Ini Berakhir';
                }

                $diffInMonths = (int) $today->diffInMonths($endDate);

                if ($diffInMonths >= 1) {
                    $remainingDays = $today->copy()->addMonths($diffInMonths)->diffInDays($endDate);
                    if ($remainingDays > 0) {
                        return "{$diffInMonths} bln {$remainingDays} hari lagi";
                    }

                    return "{$diffInMonths} bulan lagi";
                }

                return "{$diffInDays} hari lagi";
            },
        );
    }

    /**
     * Get the tenure / work duration display label.
     */
    protected function tenureLabel(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! $this->join_date) {
                    return '-';
                }

                $today = now()->startOfDay();
                $joinDate = $this->join_date->copy()->startOfDay();

                if ($joinDate->gt($today)) {
                    return 'Belum Mulai';
                }

                $years = (int) $joinDate->diffInYears($today);
                $months = (int) $joinDate->copy()->addYears($years)->diffInMonths($today);

                return "{$years} thn {$months} bln";
            },
        );
    }

    /**
     * Get the employee avatar photo URL.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->photo_path
                ? asset('storage/'.$this->photo_path)
                : asset('assets/img/avatar/avatar-1.png'),
        );
    }

    /**
     * Scope a query to search employees by name, code, email, or phone.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
                ->orWhereHas('detail', function (Builder $detailQuery) use ($term) {
                    $detailQuery->where('employee_code', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Scope a query to filter by department.
     */
    public function scopeDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope a query to filter by position.
     */
    public function scopePosition(Builder $query, int $positionId): Builder
    {
        return $query->where('position_id', $positionId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus(Builder $query, EmployeeStatus|string $status): Builder
    {
        $value = $status instanceof EmployeeStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    /**
     * Scope a query to filter by employment status.
     */
    public function scopeEmploymentStatus(Builder $query, EmploymentStatus|string $employmentStatus): Builder
    {
        $value = $employmentStatus instanceof EmploymentStatus ? $employmentStatus->value : $employmentStatus;

        return $query->where('employment_status', $value);
    }
}
