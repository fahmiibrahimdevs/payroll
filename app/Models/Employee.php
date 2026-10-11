<?php

namespace App\Models;

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
        'department_id',
        'position_id',
        'full_name',
        'join_date',
        'contract_end_date',
        'employment_status',
        'status',
        'basic_salary',
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
            'total_cash_receipt' => 'decimal:2',
        ];
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
     * Get formatted Indonesian rupiah currency for total cash receipt.
     */
    protected function formattedTotalCashReceipt(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->total_cash_receipt ?? 0), 0, ',', '.'),
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
                    return 'Karyawan Tetap';
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
                        return "{$diffInMonths} bln {$remainingDays} hari";
                    }

                    return "{$diffInMonths} bulan";
                }

                return "{$diffInDays} hari";
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
