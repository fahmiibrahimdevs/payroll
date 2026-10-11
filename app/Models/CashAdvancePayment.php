<?php

namespace App\Models;

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
        'proof_document',
        'notes',
        'recorded_by',
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
}
