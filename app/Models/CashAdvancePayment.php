<?php

namespace App\Models;

use App\Enums\CashAdvancePaymentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAdvancePayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_number',
        'cash_advance_id',
        'cash_advance_installment_id',
        'payment_date',
        'amount',
        'payment_method',
        'status',
        'proof_document',
        'notes',
        'recorded_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
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
            'payment_date' => 'date',
            'payment_method' => PaymentMethod::class,
            'status' => CashAdvancePaymentStatus::class,
            'approved_at' => 'datetime',
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
     * Get the installment linked to this payment (if applicable).
     */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(CashAdvanceInstallment::class, 'cash_advance_installment_id');
    }

    /**
     * Get the user who recorded this payment.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the user who approved this payment.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if payment is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === CashAdvancePaymentStatus::PENDING;
    }

    /**
     * Check if payment is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === CashAdvancePaymentStatus::APPROVED;
    }

    /**
     * Check if payment is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === CashAdvancePaymentStatus::REJECTED;
    }

    /**
     * Badge CSS class for payment status.
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status instanceof CashAdvancePaymentStatus
                ? $this->status->badgeClass()
                : (CashAdvancePaymentStatus::tryFrom((string) $this->status)?->badgeClass() ?? 'badge badge-warning')
        );
    }

    /**
     * Human-readable label for payment status.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status instanceof CashAdvancePaymentStatus
                ? $this->status->label()
                : (CashAdvancePaymentStatus::tryFrom((string) $this->status)?->label() ?? (string) $this->status)
        );
    }

    /**
     * Badge CSS class for payment method.
     */
    protected function methodBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payment_method instanceof PaymentMethod
                ? $this->payment_method->badgeClass()
                : (PaymentMethod::tryFrom((string) $this->payment_method)?->badgeClass() ?? 'badge badge-secondary')
        );
    }

    /**
     * Human-readable label for payment method.
     */
    protected function methodLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payment_method instanceof PaymentMethod
                ? $this->payment_method->label()
                : (PaymentMethod::tryFrom((string) $this->payment_method)?->label() ?? (string) $this->payment_method)
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
     * URL for proof document if uploaded.
     */
    protected function proofDocumentUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->proof_document
                ? route('cash-advances.documents.proof', ['payment' => $this->id])
                : null
        );
    }

    protected static function booted(): void
    {
        static::saved(fn () => \App\Services\CashAdvanceService::clearRecapChartCache());
        static::deleted(fn () => \App\Services\CashAdvanceService::clearRecapChartCache());
    }
}
