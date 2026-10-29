<?php

namespace App\Models;

use App\Enums\OvertimeStatus;
use App\Enums\OvertimeSubmissionSource;
use App\Enums\OvertimeType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimeSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'overtime_period_id',
        'employee_id',
        'overtime_type',
        'date',
        'start_time',
        'end_time',
        'duration_hours',
        'hourly_rate',
        'meal_allowance',
        'total_amount',
        'reason',
        'submission_source',
        'status',
        'admin_notes',
        'admin_adjusted_at',
        'rejection_reason',
        'submitted_by',
        'verified_by_admin_id',
        'approved_by_direktur_id',
        'rejected_by_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'duration_hours' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'meal_allowance' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'admin_adjusted_at' => 'datetime',
            'approved_at' => 'datetime',
            'status' => OvertimeStatus::class,
            'submission_source' => OvertimeSubmissionSource::class,
            'overtime_type' => OvertimeType::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtimePeriod(): BelongsTo
    {
        return $this->belongsTo(OvertimePeriod::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_admin_id');
    }

    public function approvedByDirektur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_direktur_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    protected function formattedHourlyRate(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->hourly_rate ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedMealAllowance(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->meal_allowance ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedTotalAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->total_amount ?? 0), 0, ',', '.'),
        );
    }

    public function isDaily(): bool
    {
        return $this->overtime_type === OvertimeType::DAILY || $this->overtime_type === 'daily';
    }

    public function isHourly(): bool
    {
        return ! $this->isDaily();
    }

    protected function subtotalOvertime(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->isDaily()
                ? (float) $this->hourly_rate
                : (float) $this->duration_hours * (float) $this->hourly_rate,
        );
    }

    protected function formattedSubtotalOvertime(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format($this->subtotal_overtime, 0, ',', '.'),
        );
    }

    protected function formattedWorkHours(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->isDaily()) {
                    return 'Lembur Harian';
                }

                if (empty($this->start_time) || empty($this->end_time)) {
                    return '-';
                }

                try {
                    $start = Carbon::parse($this->start_time)->format('H:i');
                    $end = Carbon::parse($this->end_time)->format('H:i');
                    return "{$start} - {$end}";
                } catch (\Throwable) {
                    return "{$this->start_time} - {$this->end_time}";
                }
            },
        );
    }

    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->isDaily()) {
                    return '1 Hari';
                }

                $hours = (float) $this->duration_hours;
                return ($hours == (int) $hours ? (int) $hours : number_format($hours, 1)).' Jam';
            },
        );
    }

    protected function formattedRateLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatted_hourly_rate.($this->isDaily() ? ' / hari' : ' / jam'),
        );
    }

    public function isPendingAdmin(): bool
    {
        return $this->status === OvertimeStatus::PENDING_ADMIN;
    }

    public function isPendingDirektur(): bool
    {
        return $this->status === OvertimeStatus::PENDING_DIREKTUR;
    }

    public function isApproved(): bool
    {
        return $this->status === OvertimeStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return in_array($this->status, [OvertimeStatus::REJECTED_ADMIN, OvertimeStatus::REJECTED_DIREKTUR], true);
    }
}
