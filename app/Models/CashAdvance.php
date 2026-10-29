<?php

namespace App\Models;

use App\Enums\CashAdvanceStatus;
use App\Enums\InstallmentStatus;
use App\Enums\RepaymentScheme;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAdvance extends Model
{
    use HasFactory, HasObfuscatedId;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_number',
        'employee_id',
        'amount',
        'remaining_amount',
        'repayment_scheme',
        'installment_count',
        'monthly_installment',
        'disbursement_date',
        'reason',
        'status',
        'submitted_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'monthly_installment' => 'decimal:2',
            'disbursement_date' => 'date',
            'approved_at' => 'datetime',
            'installment_count' => 'integer',
            'status' => CashAdvanceStatus::class,
            'repayment_scheme' => RepaymentScheme::class,
        ];
    }

    /**
     * Get the employee associated with the cash advance.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * Get the user who submitted the cash advance request.
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the user who approved or rejected the cash advance.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the installment schedules for this cash advance.
     */
    public function installments(): HasMany
    {
        return $this->hasMany(CashAdvanceInstallment::class)->orderBy('installment_number', 'asc');
    }

    /**
     * Get unpaid or partially paid installment schedules.
     */
    public function unpaidInstallments(): HasMany
    {
        return $this->hasMany(CashAdvanceInstallment::class)
            ->whereIn('status', [InstallmentStatus::PENDING, InstallmentStatus::PARTIALLY_PAID])
            ->orderBy('installment_number', 'asc');
    }

    /**
     * Get the payment transactions recorded for this cash advance.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CashAdvancePayment::class)->orderBy('payment_date', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Badge CSS class for status.
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status instanceof CashAdvanceStatus
                ? $this->status->badgeClass()
                : (CashAdvanceStatus::tryFrom((string) $this->status)?->badgeClass() ?? 'badge badge-secondary')
        );
    }

    /**
     * Human-readable label for status.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status instanceof CashAdvanceStatus
                ? $this->status->label()
                : (CashAdvanceStatus::tryFrom((string) $this->status)?->label() ?? (string) $this->status)
        );
    }

    /**
     * Badge CSS class for repayment scheme.
     */
    protected function schemeBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->repayment_scheme instanceof RepaymentScheme
                ? $this->repayment_scheme->badgeClass()
                : (RepaymentScheme::tryFrom((string) $this->repayment_scheme)?->badgeClass() ?? 'badge badge-secondary')
        );
    }

    /**
     * Human-readable label for repayment scheme.
     */
    protected function schemeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->repayment_scheme instanceof RepaymentScheme
                ? $this->repayment_scheme->label()
                : (RepaymentScheme::tryFrom((string) $this->repayment_scheme)?->label() ?? (string) $this->repayment_scheme)
        );
    }

    /**
     * Short label for repayment scheme.
     */
    protected function schemeShortLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->repayment_scheme instanceof RepaymentScheme
                ? $this->repayment_scheme->shortLabel()
                : (RepaymentScheme::tryFrom((string) $this->repayment_scheme)?->shortLabel() ?? (string) $this->repayment_scheme)
        );
    }

    /**
     * Formatted amount in Rupiah string.
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->amount, 0, ',', '.')
        );
    }

    /**
     * Formatted remaining amount in Rupiah string.
     */
    protected function formattedRemainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->remaining_amount, 0, ',', '.')
        );
    }

    /**
     * Total paid amount.
     */
    protected function paidAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, (float) $this->amount - (float) $this->remaining_amount)
        );
    }

    /**
     * Formatted paid amount in Rupiah string.
     */
    protected function formattedPaidAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format($this->paid_amount, 0, ',', '.')
        );
    }

    /**
     * Progress percentage of repayment (0 - 100%).
     */
    protected function progressPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $total = (float) $this->amount;
                if ($total <= 0) {
                    return 100.0;
                }
                $paid = max(0, $total - (float) $this->remaining_amount);

                return round(($paid / $total) * 100, 1);
            }
        );
    }

    /**
     * Scope query to search by reference number, reason, notes, or employee name.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('reference_number', 'like', "%{$term}%")
                ->orWhere('reason', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%")
                ->orWhereHas('employee', function (Builder $eq) use ($term) {
                    $eq->where('full_name', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Scope query to active / in-progress cash advances.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            CashAdvanceStatus::ACTIVE->value,
            CashAdvanceStatus::APPROVED->value,
        ]);
    }

    /**
     * Scope query to pending cash advances.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CashAdvanceStatus::PENDING->value);
    }

    /**
     * Scope query to specific employee.
     */
    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    protected static function booted(): void
    {
        static::saved(fn () => \App\Services\CashAdvanceService::clearRecapChartCache());
        static::deleted(fn () => \App\Services\CashAdvanceService::clearRecapChartCache());
    }
}
