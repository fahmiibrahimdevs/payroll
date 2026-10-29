<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimePeriodAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'overtime_period_id',
        'employee_id',
        'meal_allowance_type',
        'meal_allowance_per_day',
        'daily_rate',
        'attended_days',
        'attendance_days',
        'total_meal_allowance',
        'total_daily_wage',
        'notes',
        'payment_status',
        'paid_at',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'payment_proof_path',
        'payment_notes',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'meal_allowance_per_day' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'attended_days' => 'integer',
            'attendance_days' => 'array',
            'total_meal_allowance' => 'decimal:2',
            'total_daily_wage' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function overtimePeriod(): BelongsTo
    {
        return $this->belongsTo(OvertimePeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    protected function formattedMealAllowancePerDay(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->meal_allowance_per_day ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedTotalMealAllowance(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->total_meal_allowance ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedDailyRate(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->daily_rate ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedTotalDailyWage(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->total_daily_wage ?? 0), 0, ',', '.'),
        );
    }

    public function isKhusus(): bool
    {
        return $this->meal_allowance_type === 'khusus';
    }

    public function isStandar(): bool
    {
        return $this->meal_allowance_type === 'standar';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'bank_transfer' => 'Transfer Bank / E-Wallet',
            'cash' => 'Tunai (Cash)',
            default => $this->payment_method ? ucfirst(str_replace('_', ' ', $this->payment_method)) : '-',
        };
    }

    public function formattedPaymentProofUrl(): ?string
    {
        return $this->payment_proof_path ? asset('storage/'.$this->payment_proof_path) : null;
    }
}
