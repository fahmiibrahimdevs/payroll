<?php

namespace App\Models;

use App\Enums\OvertimePeriodStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimePeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'name',
        'start_date',
        'end_date',
        'total_hours',
        'total_overtime_pay',
        'total_meal_allowance',
        'total_daily_wage',
        'grand_total',
        'employee_count',
        'status',
        'created_by',
        'paid_by',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'paid_at' => 'datetime',
            'total_hours' => 'decimal:2',
            'total_overtime_pay' => 'decimal:2',
            'total_meal_allowance' => 'decimal:2',
            'total_daily_wage' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'status' => OvertimePeriodStatus::class,
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(OvertimeSubmission::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(OvertimePeriodAttendance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    protected function formattedGrandTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->grand_total ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedTotalOvertimePay(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->total_overtime_pay ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedTotalMealAllowance(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->total_meal_allowance ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedTotalDailyWage(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->total_daily_wage ?? 0), 0, ',', '.'),
        );
    }

    /**
     * Recalculate totals for this period from its linked submissions and attendances.
     */
    public function recalculateTotals(): void
    {
        $submissions = $this->submissions()->get();
        $attendances = $this->attendances()->get();

        $totalHours = (float) $submissions->sum('duration_hours');
        $totalOvertimePay = (float) $submissions->sum(fn ($sub) => (float) $sub->subtotal_overtime);
        $totalMealAllowance = (float) $attendances->sum('total_meal_allowance');
        $totalDailyWage = (float) $attendances->sum('total_daily_wage');

        $this->total_hours = $totalHours;
        $this->total_overtime_pay = $totalOvertimePay;
        $this->total_meal_allowance = $totalMealAllowance;
        $this->total_daily_wage = $totalDailyWage;
        $this->grand_total = $totalOvertimePay + $totalMealAllowance + $totalDailyWage;

        // Opsi A & C: Hanya hitung karyawan yang menerima hak gajian mingguan (> 0 hari masuk / upah / lembur)
        $activeAttendanceEmpIds = $attendances->filter(fn ($att) => (
            (float) ($att->total_daily_wage ?? 0) > 0 ||
            (float) ($att->total_meal_allowance ?? 0) > 0 ||
            (int) ($att->attended_days ?? 0) > 0
        ))->pluck('employee_id');

        $activeSubmissionEmpIds = $submissions->filter(fn ($sub) => (
            (float) ($sub->duration_hours ?? 0) > 0 ||
            (float) ($sub->subtotal_overtime ?? 0) > 0
        ))->pluck('employee_id');

        $uniqueEmployeeIds = $activeAttendanceEmpIds
            ->concat($activeSubmissionEmpIds)
            ->unique()
            ->filter();

        $this->employee_count = $uniqueEmployeeIds->count();
        $this->save();
    }
}
