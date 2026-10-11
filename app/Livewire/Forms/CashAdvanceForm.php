<?php

namespace App\Livewire\Forms;

use App\Enums\RepaymentScheme;
use App\Models\CashAdvance;
use App\Models\User;
use App\Services\CashAdvanceService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CashAdvanceForm extends Form
{
    public ?CashAdvance $cashAdvance = null;

    public int|string|null $employee_id = '';

    public int|float|string|null $amount = '';

    public string $repayment_scheme = 'salary_installment';

    public int|string|null $installment_count = 1;

    public ?string $disbursement_date = '';

    public ?string $reason = '';

    public ?string $notes = '';

    /**
     * Define the validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:10000',
                'max:1000000000',
            ],
            'repayment_scheme' => [
                'required',
                Rule::enum(RepaymentScheme::class),
            ],
            'installment_count' => [
                'required',
                'integer',
                'min:1',
                'max:60',
            ],
            'disbursement_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:1000',
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
            'employee_id' => 'Karyawan',
            'amount' => 'Nominal Kasbon',
            'repayment_scheme' => 'Skema Pelunasan',
            'installment_count' => 'Jumlah Tenor / Bulan',
            'disbursement_date' => 'Tanggal Pencairan',
            'reason' => 'Alasan / Keperluan',
            'notes' => 'Catatan Tambahan',
        ];
    }

    /**
     * Populate form fields from an existing CashAdvance model for editing.
     */
    public function setCashAdvance(CashAdvance $cashAdvance): void
    {
        $this->cashAdvance = $cashAdvance;
        $this->employee_id = $cashAdvance->employee_id;
        $this->amount = (float) $cashAdvance->amount;
        $this->repayment_scheme = $cashAdvance->repayment_scheme instanceof RepaymentScheme
            ? $cashAdvance->repayment_scheme->value
            : (string) $cashAdvance->repayment_scheme;
        $this->installment_count = $cashAdvance->installment_count;
        $this->disbursement_date = $cashAdvance->disbursement_date ? $cashAdvance->disbursement_date->format('Y-m-d') : '';
        $this->reason = (string) $cashAdvance->reason;
        $this->notes = (string) ($cashAdvance->notes ?? '');
    }

    /**
     * Reset form fields to initial default values.
     */
    public function resetForm(): void
    {
        $this->cashAdvance = null;
        $this->employee_id = '';
        $this->amount = '';
        $this->repayment_scheme = 'salary_installment';
        $this->installment_count = 1;
        $this->disbursement_date = Carbon::today()->format('Y-m-d');
        $this->reason = '';
        $this->notes = '';
        $this->resetValidation();
    }

    /**
     * Store a newly created cash advance request.
     */
    public function store(CashAdvanceService $service, User $user): CashAdvance
    {
        $validated = $this->validate();

        $cashAdvance = $service->createCashAdvance($validated, $user);
        $this->resetForm();

        return $cashAdvance;
    }

    /**
     * Update an existing pending cash advance.
     */
    public function update(CashAdvanceService $service, User $user): CashAdvance
    {
        $validated = $this->validate();

        $updated = $service->updateCashAdvance($this->cashAdvance, $validated, $user);
        $this->resetForm();

        return $updated;
    }
}
