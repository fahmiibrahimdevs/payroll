<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAdvanceInstallment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cash_advance_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'paid_at',
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
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'installment_number' => 'integer',
            'status' => InstallmentStatus::class,
        ];
    }

    /**
     * Get the parent cash advance.
     */
    public function cashAdvance(): BelongsTo
    {
        return $this->belongsTo(CashAdvance::class);
    }

    /**
     * Get payments allocated to this installment.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CashAdvancePayment::class, 'cash_advance_installment_id');
    }

    /**
     * Badge CSS class for status.
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status instanceof InstallmentStatus
                ? $this->status->badgeClass()
                : (InstallmentStatus::tryFrom((string) $this->status)?->badgeClass() ?? 'badge badge-secondary')
        );
    }

    /**
     * Human-readable label for status.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status instanceof InstallmentStatus
                ? $this->status->label()
                : (InstallmentStatus::tryFrom((string) $this->status)?->label() ?? (string) $this->status)
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
     * Formatted paid amount in Rupiah string.
     */
    protected function formattedPaidAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->paid_amount, 0, ',', '.')
        );
    }

    /**
     * Remaining installment amount.
     */
    protected function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, (float) $this->amount - (float) $this->paid_amount)
        );
    }

    /**
     * Formatted remaining installment amount.
     */
    protected function formattedRemainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format($this->remaining_amount, 0, ',', '.')
        );
    }
}
