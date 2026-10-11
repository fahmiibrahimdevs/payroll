<?php

namespace App\Livewire\Forms;

use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\RepaymentScheme;
use App\Models\CashAdvance;
use App\Models\CashAdvancePayment;
use App\Models\User;
use App\Services\CashAdvanceService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CashAdvancePaymentForm extends Form
{
    public ?CashAdvance $cashAdvance = null;

    public int|float|string|null $amount = '';

    public ?string $payment_date = '';

    public string $payment_method = 'cash';

    public ?int $cash_advance_installment_id = null;

    public string $payment_target = 'full';

    public $proof_document = null;

    public ?string $notes = '';

    /**
     * Define the validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $maxAmount = $this->cashAdvance ? (float) $this->cashAdvance->remaining_amount : 1000000000;

        return [
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:'.$maxAmount,
            ],
            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
                ...($this->cashAdvance?->disbursement_date ? ['after_or_equal:'.$this->cashAdvance->disbursement_date->format('Y-m-d')] : []),
            ],
            'payment_method' => [
                'required',
                Rule::enum(PaymentMethod::class),
            ],
            'cash_advance_installment_id' => [
                'nullable',
                'integer',
                Rule::exists('cash_advance_installments', 'id')
                    ->where(fn ($query) => $query->where('cash_advance_id', $this->cashAdvance?->id ?? 0)),
            ],
            'payment_target' => [
                'nullable',
                'string',
                Rule::in(['full', 'installment', 'custom']),
            ],
            'proof_document' => [
                'nullable',
                'file',
                'mimes:jpeg,png,jpg,pdf',
                'max:5120',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Custom validation attribute names.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'amount' => 'Nominal Pembayaran',
            'payment_date' => 'Tanggal Pembayaran',
            'payment_method' => 'Metode Pembayaran',
            'cash_advance_installment_id' => 'Target Cicilan',
            'proof_document' => 'Bukti Pembayaran',
            'notes' => 'Catatan / Nomor Referensi',
        ];
    }

    /**
     * Set target cash advance.
     */
    public function setCashAdvance(CashAdvance $cashAdvance): void
    {
        $this->cashAdvance = $cashAdvance;
        $this->payment_date = Carbon::today()->format('Y-m-d');
        $this->payment_method = 'cash';
        $this->proof_document = null;
        $this->resetValidation();

        $unpaidCount = $cashAdvance->installments()
            ->whereIn('status', [
                InstallmentStatus::PENDING->value,
                InstallmentStatus::PARTIALLY_PAID->value,
            ])
            ->count();

        if ($unpaidCount > 1) {
            $firstUnpaid = $cashAdvance->installments()
                ->whereIn('status', [
                    InstallmentStatus::PENDING->value,
                    InstallmentStatus::PARTIALLY_PAID->value,
                ])
                ->orderBy('installment_number', 'asc')
                ->first();

            if ($firstUnpaid) {
                $this->applyPaymentTarget('installment', $firstUnpaid->id);

                return;
            }
        }

        $this->applyPaymentTarget('full');
    }

    /**
     * Apply target payment selection (specific installment, full settlement, or custom).
     */
    public function applyPaymentTarget(string $targetType, ?int $installmentId = null): void
    {
        if (! $this->cashAdvance) {
            return;
        }

        $this->payment_target = $targetType;

        if ($targetType === 'installment' && $installmentId) {
            $inst = $this->cashAdvance->installments()->find($installmentId);
            if ($inst) {
                $this->cash_advance_installment_id = $inst->id;
                $remainingInst = max(0, (float) $inst->amount - (float) $inst->paid_amount);
                $this->amount = $remainingInst;
                $dueDate = $inst->due_date ? $inst->due_date->format('d M Y') : '';
                $this->notes = "Pembayaran Cicilan Ke-{$inst->installment_number}".($dueDate ? " (Jatuh Tempo: {$dueDate})" : '');

                return;
            }
        }

        if ($targetType === 'full') {
            $this->cash_advance_installment_id = null;
            $this->amount = (float) $this->cashAdvance->remaining_amount;
            if ($this->cashAdvance->repayment_scheme === RepaymentScheme::SALARY_SINGLE) {
                $this->notes = 'Pelunasan penuh lebih awal (Sebelum jadwal potong gaji)';
            } else {
                $this->notes = 'Pelunasan dipercepat seluruh sisa kasbon';
            }

            return;
        }

        if ($targetType === 'custom') {
            $this->cash_advance_installment_id = null;
            $this->notes = 'Pembayaran kasbon sebagian';
        }
    }

    /**
     * Reset payment form.
     */
    public function resetForm(): void
    {
        $this->cashAdvance = null;
        $this->amount = '';
        $this->payment_date = Carbon::today()->format('Y-m-d');
        $this->payment_method = 'cash';
        $this->cash_advance_installment_id = null;
        $this->payment_target = 'full';
        $this->proof_document = null;
        $this->notes = '';
        $this->resetValidation();
    }

    /**
     * Store payment transaction.
     */
    public function store(CashAdvanceService $service, User $user): CashAdvancePayment
    {
        $validated = $this->validate();

        $payment = $service->recordPayment($this->cashAdvance, $validated, $user);
        $this->resetForm();

        return $payment;
    }
}
