<?php

namespace App\Livewire\Payrolls;

use App\Enums\CashAdvanceStatus;
use App\Enums\EmploymentStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PayrollLineCategory;
use App\Enums\PayrollLineType;
use App\Enums\PayrollPeriodStatus;
use App\Enums\PtkpStatus;
use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentCategory;
use App\Enums\SalaryComponentType;
use App\Enums\TaxMethod;
use App\Enums\TaxRecipientType;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\PayrollService;
use App\Services\TaxCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Kelola Komponen Gaji - Larashiz HRM')]
class ManageItem extends Component
{
    public PayrollPeriod $period;

    public PayrollItem $item;

    #[Computed]
    public function isEditable(): bool
    {
        if (! auth()->user()?->hasRole('admin') || $this->period->status === PayrollPeriodStatus::PAID || $this->item->isApproved()) {
            return false;
        }

        return $this->period->status->isEditable() || $this->item->isRejected();
    }

    #[Computed]
    public function masterBasicSalary(): ?float
    {
        if (! $this->item->employee) {
            return null;
        }

        $info = app(PayrollService::class)->calculateProratedSalary($this->item->employee, $this->period);

        return (float) $info['basic_salary'];
    }

    /**
     * Synchronize basic salary from master employee data.
     */
    public function syncBasicSalaryFromMaster(PayrollService $service): void
    {
        if (! $this->isEditable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Dapat Diubah',
                'message' => 'Gaji pokok hanya dapat disinkronkan pada draf atau revisi.',
            ]);

            return;
        }

        try {
            $this->item = $service->syncItemWithMasterEmployee($this->item, auth()->user());
            $this->period->refresh();
            $this->syncAutoBpjsAndTax();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Gaji Pokok Disinkronkan',
                'message' => "Gaji pokok berhasil diperbarui menjadi {$this->item->formatted_basic_salary} dari Master Karyawan.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Sinkronisasi',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public array $allowanceRows = [];

    public array $deductionRows = [];

    public ?array $calculationDetail = null;

    public string $modalType = 'allowance';

    public ?int $modalIndex = null;

    public string $modalName = '';

    public mixed $modalAmount = '';

    public string $modalCalcMode = 'nominal';

    public mixed $modalPercentage = '';

    public ?int $modalComponentId = null;

    public array $companyBpjsStatus = [
        'bpjs_kes' => true,
        'bpjs_jht' => true,
        'bpjs_jp' => true,
        'bpjs_jkk' => true,
        'bpjs_jkm' => true,
    ];

    public string $itemRejectionReason = '';

    public ?string $lastSavedAt = null;

    public string $taxConfigurationWarning = '';

    public function mount(PayrollPeriod $period, PayrollItem $item): void
    {
        if ($item->payroll_period_id !== $period->id) {
            abort(404);
        }

        $this->period = $period->load(['creator', 'updater', 'submitter', 'approver']);
        $this->item = $item->load([
            'employee.department',
            'employee.position',
            'employee.detail',
            'lines.salaryComponent',
            'lines.cashAdvanceInstallment.cashAdvance',
            'updater',
            'approver',
            'verifier',
        ]);
        $this->taxConfigurationWarning = $this->getTaxConfigurationWarning();

        $company = CompanyProfile::getProfile();
        $defaultBpjsState = (bool) $company->include_bpjs_in_tax;
        foreach (array_keys($this->companyBpjsStatus) as $k) {
            $this->companyBpjsStatus[$k] = $defaultBpjsState;
        }

        if (! empty($this->item->notes) && str_starts_with($this->item->notes, '{')) {
            $meta = json_decode($this->item->notes, true);
            if (isset($meta['disabled_company_bpjs']) && is_array($meta['disabled_company_bpjs'])) {
                foreach ($meta['disabled_company_bpjs'] as $k) {
                    if (isset($this->companyBpjsStatus[$k])) {
                        $this->companyBpjsStatus[$k] = false;
                    }
                }
            }
            if (isset($meta['enabled_company_bpjs']) && is_array($meta['enabled_company_bpjs'])) {
                foreach ($meta['enabled_company_bpjs'] as $k) {
                    if (isset($this->companyBpjsStatus[$k])) {
                        $this->companyBpjsStatus[$k] = true;
                    }
                }
            }
        }

        $existingCompLines = $this->item->lines->where('type', PayrollLineType::COMPANY_CONTRIBUTION);
        if ($existingCompLines->isNotEmpty()) {
            foreach ($existingCompLines as $compLine) {
                if ((float) $compLine->amount == 0 || $compLine->notes === 'Nonaktif') {
                    if (str_contains($compLine->name, 'Kesehatan')) {
                        $this->companyBpjsStatus['bpjs_kes'] = false;
                    } elseif (str_contains($compLine->name, 'JHT')) {
                        $this->companyBpjsStatus['bpjs_jht'] = false;
                    } elseif (str_contains($compLine->name, 'JP') || str_contains($compLine->name, 'Pensiun')) {
                        $this->companyBpjsStatus['bpjs_jp'] = false;
                    } elseif (str_contains($compLine->name, 'JKK')) {
                        $this->companyBpjsStatus['bpjs_jkk'] = false;
                    } elseif (str_contains($compLine->name, 'JKM')) {
                        $this->companyBpjsStatus['bpjs_jkm'] = false;
                    }
                } elseif ((float) $compLine->amount > 0 && $compLine->notes !== 'Nonaktif') {
                    if (str_contains($compLine->name, 'Kesehatan')) {
                        $this->companyBpjsStatus['bpjs_kes'] = true;
                    } elseif (str_contains($compLine->name, 'JHT')) {
                        $this->companyBpjsStatus['bpjs_jht'] = true;
                    } elseif (str_contains($compLine->name, 'JP') || str_contains($compLine->name, 'Pensiun')) {
                        $this->companyBpjsStatus['bpjs_jp'] = true;
                    } elseif (str_contains($compLine->name, 'JKK')) {
                        $this->companyBpjsStatus['bpjs_jkk'] = true;
                    } elseif (str_contains($compLine->name, 'JKM')) {
                        $this->companyBpjsStatus['bpjs_jkm'] = true;
                    }
                }
            }
        }

        if ($this->isEditable && $this->item->employee) {
            $service = app(PayrollService::class);
            $salaryInfo = $service->calculateProratedSalary($this->item->employee, $this->period);
            $expectedBasic = (float) $salaryInfo['basic_salary'];
            $empDetail = $this->item->employee->detail;

            $needsSalarySync = (float) $this->item->basic_salary !== $expectedBasic;
            $needsTaxSync = $empDetail && $empDetail->ptkp_status && (
                $this->item->ptkp_status !== $empDetail->ptkp_status ||
                $this->item->tax_method !== $empDetail->tax_method
            );

            if ($needsSalarySync || $needsTaxSync) {
                $changedFields = [];
                if ($needsSalarySync) {
                    $this->item->update([
                        'basic_salary' => $expectedBasic,
                        'bank_name' => $empDetail?->bank_name,
                        'bank_account_number' => $empDetail?->bank_account_number,
                        'bank_account_name' => $empDetail?->bank_account_name,
                    ]);
                    $changedFields = ['basic_salary', 'bank_details'];
                }
                if ($needsTaxSync) {
                    $changedFields = array_merge($changedFields, ['ptkp_status', 'tax_method']);
                }
                if ($this->taxConfigurationWarning === '') {
                    $service->recalculateItemTax($this->item);
                }
                $service->recalculateItem($this->item);
                $service->recalculatePeriod($this->period);
                app(ActivityLogService::class)->info(
                    action: 'PAYSLIP_MASTER_DATA_SYNCED',
                    module: 'payrolls',
                    message: 'Payslip synchronized with employee data on open',
                    context: [
                        'payroll_period_id' => $this->period->id,
                        'payroll_item_id' => $this->item->id,
                        'employee_id' => $this->item->employee_id,
                        'changed_fields' => array_values(array_unique($changedFields)),
                    ],
                    userId: auth()->id()
                );
                $this->item->refresh();
                $this->period->refresh();
            }
        }

        $this->loadComponentRows();
        $this->lastSavedAt = $this->item->updated_at?->format('H:i:s') ?? null;
    }

    public function loadComponentRows(): void
    {
        $this->allowanceRows = [];
        $this->deductionRows = [];
        $basic = (float) ($this->item->basic_salary ?? 0);

        foreach ($this->item->lines as $line) {
            if ($line->type === PayrollLineType::COMPANY_CONTRIBUTION) {
                continue;
            }
            $isSystem = ($line->category === PayrollLineCategory::TAX
                || $line->category === PayrollLineCategory::BPJS
                || ! empty($line->cash_advance_installment_id)
                || str_starts_with($line->name, 'PPh 21'));

            $amount = (float) $line->amount;

            $inst = null;
            // Keep the payroll deduction amount user-editable. The reset button
            // explicitly restores the unpaid scheduled installment amount.
            if (! empty($line->cash_advance_installment_id)) {
                $inst = $line->relationLoaded('cashAdvanceInstallment')
                    ? $line->cashAdvanceInstallment
                    : CashAdvanceInstallment::with('cashAdvance')->find($line->cash_advance_installment_id);

                if ($inst && ! $inst->relationLoaded('cashAdvance')) {
                    $inst->load('cashAdvance');
                }

                if ($inst) {
                    $unpaidInstallment = max(0.0, round((float) $inst->amount - (float) $inst->paid_amount, 2));
                    $totalRemainingAdvance = max(0.0, round((float) ($inst->cashAdvance?->remaining_amount ?? $unpaidInstallment), 2));

                    if ($this->isEditable && $amount > $totalRemainingAdvance + 0.001) {
                        $amount = $totalRemainingAdvance;
                        $line->update(['amount' => $amount]);
                        app(PayrollService::class)->recalculateItem($this->item);
                        $this->item->refresh();
                    } elseif ($this->isEditable && abs($amount - (float) $inst->amount) < 0.001 && $inst->paid_amount > 0) {
                        $amount = min($unpaidInstallment, $totalRemainingAdvance);
                        $line->update(['amount' => $amount]);
                        app(PayrollService::class)->recalculateItem($this->item);
                        $this->item->refresh();
                    }
                }
            }

            $calcMode = 'nominal';
            $pct = '';

            $salaryComp = $line->relationLoaded('salaryComponent')
                ? $line->salaryComponent
                : ($line->salary_component_id ? SalaryComponent::find($line->salary_component_id) : null);

            if ($salaryComp && ($salaryComp->calculation_type === SalaryCalculationType::PERCENTAGE_OF_BASIC_SALARY || (is_string($salaryComp->calculation_type) && $salaryComp->calculation_type === 'persen_gaji_pokok'))) {
                $calcMode = 'percentage';
                $pct = $salaryComp->default_value !== null ? (float) $salaryComp->default_value : ($basic > 0 ? round(($amount / $basic) * 100, 2) : '');
            }

            $isEnabled = true;
            if ($isSystem && ($line->category === PayrollLineCategory::TAX || $line->category === PayrollLineCategory::BPJS || str_starts_with($line->name, 'PPh 21'))) {
                if ($line->notes === 'Nonaktif' || ($amount == 0 && ! empty($line->notes) && str_contains($line->notes, 'Nonaktif'))) {
                    $isEnabled = false;
                }
            }

            $advance = $inst?->cashAdvance;

            $rowData = [
                'id' => $line->id,
                'name' => $line->name,
                'amount' => ($amount != 0.0 || ! empty($line->cash_advance_installment_id)) ? $amount : '',
                'calc_mode' => $calcMode,
                'percentage' => $pct,
                'salary_component_id' => $line->salary_component_id,
                'cash_advance_installment_id' => $line->cash_advance_installment_id,
                'installment_paid_amount' => (float) ($inst?->paid_amount ?? 0),
                'installment_total_amount' => (float) ($inst?->amount ?? 0),
                'installment_number' => $inst?->installment_number ?? null,
                'advance_reference_number' => $advance?->reference_number ?? null,
                'advance_total_amount' => (float) ($advance?->amount ?? 0),
                'advance_remaining_amount' => (float) ($advance?->remaining_amount ?? 0),
                'advance_installment_count' => $advance?->installment_count ?? null,
                'category' => $line->category?->value ?? ($line->type === PayrollLineType::ALLOWANCE ? 'allowance' : 'deduction'),
                'is_system' => $isSystem,
                'is_enabled' => $isEnabled,
                'notes' => $line->notes ?? '',
            ];

            if ($line->type === PayrollLineType::ALLOWANCE) {
                $this->allowanceRows[] = $rowData;
            } else {
                $this->deductionRows[] = $rowData;
            }
        }

        if ($this->isEditable) {
            // Auto-inject BPJS Karyawan rows if employee has detail and basic > 0, but lines not yet in DB
            $company = CompanyProfile::getProfile();
            if ($this->item->employee?->detail && $basic > 0) {
                $hasBpjs = collect($this->deductionRows)->contains(fn ($r) => ($r['category'] ?? '') === 'bpjs' || str_starts_with(strtolower($r['name'] ?? ''), 'bpjs'));
                if (! $hasBpjs) {
                    $bpjs = $this->bpjsBreakdown;
                    $compBpjsKes = SalaryComponent::where('code', 'BPJS_KES')->first();
                    $compBpjsJht = SalaryComponent::where('code', 'BPJS_JHT')->first();
                    $compBpjsJp = SalaryComponent::where('code', 'BPJS_JP')->first();
                    $bpjsDefault = (bool) $company->include_bpjs_in_tax;

                    $this->deductionRows[] = [
                        'id' => null,
                        'name' => 'BPJS Kesehatan (1%)',
                        'amount' => $bpjsDefault ? (float) ($bpjs['employee']['bpjs_kes'] ?? 0) : 0.0,
                        'calc_mode' => 'percentage',
                        'percentage' => 1.0,
                        'salary_component_id' => $compBpjsKes?->id,
                        'cash_advance_installment_id' => null,
                        'category' => 'bpjs',
                        'is_system' => true,
                        'is_enabled' => $bpjsDefault,
                        'notes' => $bpjsDefault ? 'Iuran BPJS Kesehatan 1% dibayar karyawan' : 'Nonaktif',
                    ];
                    $this->deductionRows[] = [
                        'id' => null,
                        'name' => 'BPJS JHT (2%)',
                        'amount' => $bpjsDefault ? (float) ($bpjs['employee']['bpjs_jht'] ?? 0) : 0.0,
                        'calc_mode' => 'percentage',
                        'percentage' => 2.0,
                        'salary_component_id' => $compBpjsJht?->id,
                        'cash_advance_installment_id' => null,
                        'category' => 'bpjs',
                        'is_system' => true,
                        'is_enabled' => $bpjsDefault,
                        'notes' => $bpjsDefault ? 'Iuran BPJS Ketenagakerjaan JHT 2% dibayar karyawan' : 'Nonaktif',
                    ];
                    $this->deductionRows[] = [
                        'id' => null,
                        'name' => 'BPJS Pensiun (1%)',
                        'amount' => $bpjsDefault ? (float) ($bpjs['employee']['bpjs_jp'] ?? 0) : 0.0,
                        'calc_mode' => 'percentage',
                        'percentage' => 1.0,
                        'salary_component_id' => $compBpjsJp?->id,
                        'cash_advance_installment_id' => null,
                        'category' => 'bpjs',
                        'is_system' => true,
                        'is_enabled' => $bpjsDefault,
                        'notes' => $bpjsDefault ? 'Iuran BPJS Ketenagakerjaan JP 1% dibayar karyawan' : 'Nonaktif',
                    ];
                }
            }

            // Auto-inject PPh 21 rows if employee has detail and basic > 0, but lines not yet in DB
            if ($this->item->employee?->detail && $basic > 0) {
                $hasPph = collect($this->deductionRows)->contains(fn ($r) => ($r['category'] ?? '') === 'tax' || str_starts_with(strtolower(trim($r['name'] ?? '')), 'pph 21'));
                if (! $hasPph) {
                    $compPph = SalaryComponent::where('code', 'PPH21')->first();
                    $pphDefault = (bool) $company->enable_pph21;
                    $pphAmount = (float) ($this->item->pph21_amount ?? 0);
                    $this->deductionRows[] = [
                        'id' => null,
                        'name' => $pphAmount < 0 ? 'PPh 21 (Lebih Bayar)' : 'PPh 21',
                        'amount' => $pphDefault ? $pphAmount : 0.0,
                        'calc_mode' => 'nominal',
                        'percentage' => '',
                        'salary_component_id' => $compPph?->id,
                        'cash_advance_installment_id' => null,
                        'category' => 'tax',
                        'is_system' => true,
                        'is_enabled' => $pphDefault,
                        'notes' => $pphDefault ? ($pphAmount < 0 ? 'Kelebihan potong PPh 21 dikembalikan ke karyawan' : 'Potongan PPh 21 otomatis') : 'Nonaktif',
                    ];
                }
            }

            // Auto-inject Cash Advance installment if employee has active advance due in this period, but line not yet in DB
            if ($this->item->employee) {
                $existingInstallmentIds = collect($this->deductionRows)
                    ->pluck('cash_advance_installment_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $endDate = $this->period->end_date;
                $activeAdvances = $this->item->employee->cashAdvances()
                    ->whereIn('status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value])
                    ->with(['installments' => function ($q) use ($endDate) {
                        $q->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                            ->where('due_date', '<=', $endDate->toDateString())
                            ->orderBy('installment_number');
                    }])
                    ->get();

                foreach ($activeAdvances as $advance) {
                    $installment = $advance->installments->first();
                    if ($installment && ! in_array((int) $installment->id, $existingInstallmentIds, true)) {
                        $deductionAmount = max(0.0, round((float) $installment->amount - (float) $installment->paid_amount, 2));
                        if ($deductionAmount > 0) {
                            $this->deductionRows[] = [
                                'id' => null,
                                'name' => "Potongan Kasbon ({$advance->reference_number} Cicilan #{$installment->installment_number})",
                                'amount' => $deductionAmount,
                                'calc_mode' => 'nominal',
                                'percentage' => '',
                                'salary_component_id' => null,
                                'cash_advance_installment_id' => $installment->id,
                                'installment_paid_amount' => (float) $installment->paid_amount,
                                'installment_total_amount' => (float) $installment->amount,
                                'installment_number' => $installment->installment_number,
                                'advance_reference_number' => $advance->reference_number,
                                'advance_total_amount' => (float) $advance->amount,
                                'advance_remaining_amount' => (float) $advance->remaining_amount,
                                'advance_installment_count' => $advance->installment_count,
                                'category' => 'cash_advance',
                                'is_system' => true,
                                'is_enabled' => true,
                                'notes' => 'Potongan otomatis dari kasbon aktif',
                            ];
                        }
                    }
                }
            }

            $this->ensureTrailingEmptyRow('allowance');
            $this->ensureTrailingEmptyRow('deduction');
            $this->syncAutoBpjsAndTax();
        }
    }

    /**
     * Master Allowance Components list excluding already used ones.
     */
    public function getMasterAllowancesProperty(): Collection
    {
        $usedIds = collect($this->allowanceRows)
            ->filter(fn ($row, $idx) => ($this->modalIndex === null || $idx !== $this->modalIndex) && ! empty($row['salary_component_id']))
            ->pluck('salary_component_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $usedNames = collect($this->allowanceRows)
            ->filter(fn ($row, $idx) => ($this->modalIndex === null || $idx !== $this->modalIndex) && ! empty(trim($row['name'] ?? '')))
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim($n)))
            ->all();

        return SalaryComponent::query()
            ->where('is_active', true)
            ->where('type', SalaryComponentType::ALLOWANCE)
            ->where('category', '!=', SalaryComponentCategory::DEDUCTION)
            ->whereNotIn('code', ['GAJI_POKOK', 'TUNJ_PPH21', 'BPJS_KES', 'BPJS_JHT', 'BPJS_JP', 'PPH21', 'KASBON', 'POT-KASBON'])
            ->when(! empty($usedIds), fn ($q) => $q->whereNotIn('id', $usedIds))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn ($comp) => ! in_array(strtolower(trim($comp->name)), $usedNames))
            ->values();
    }

    /**
     * Master Deduction Components list excluding already used ones.
     */
    public function getMasterDeductionsProperty(): Collection
    {
        $usedIds = collect($this->deductionRows)
            ->filter(fn ($row, $idx) => ($this->modalIndex === null || $idx !== $this->modalIndex) && ! empty($row['salary_component_id']))
            ->pluck('salary_component_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $usedNames = collect($this->deductionRows)
            ->filter(fn ($row, $idx) => ($this->modalIndex === null || $idx !== $this->modalIndex) && ! empty(trim($row['name'] ?? '')))
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim($n)))
            ->all();

        return SalaryComponent::query()
            ->where('is_active', true)
            ->where('type', SalaryComponentType::DEDUCTION)
            ->where('category', SalaryComponentCategory::DEDUCTION)
            ->whereNotIn('code', ['PPH21', 'KASBON', 'POT-KASBON', 'BPJS_KES', 'BPJS_JHT', 'BPJS_JP'])
            ->where('is_system', false)
            ->when(! empty($usedIds), fn ($q) => $q->whereNotIn('id', $usedIds))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn ($comp) => ! in_array(strtolower(trim($comp->name)), $usedNames))
            ->values();
    }

    /**
     * Get available master components formatted for Select2 dropdown.
     *
     * @return array<int, array{id: int, text: string}>
     */
    public function getAvailableMasterComponents(string $type, ?int $ignoreIndex = null): array
    {
        $rows = $type === 'allowance' ? $this->allowanceRows : $this->deductionRows;

        $usedIds = collect($rows)
            ->filter(fn ($row, $idx) => ($ignoreIndex === null || $idx !== $ignoreIndex) && ! empty($row['salary_component_id']))
            ->pluck('salary_component_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $usedNames = collect($rows)
            ->filter(fn ($row, $idx) => ($ignoreIndex === null || $idx !== $ignoreIndex) && ! empty(trim($row['name'] ?? '')))
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim($n)))
            ->all();

        $enumType = $type === 'allowance' ? SalaryComponentType::ALLOWANCE : SalaryComponentType::DEDUCTION;

        return SalaryComponent::query()
            ->where('is_active', true)
            ->where('type', $enumType)
            ->when($type === 'allowance', fn ($q) => $q->where('category', '!=', SalaryComponentCategory::DEDUCTION)->whereNotIn('code', ['GAJI_POKOK', 'TUNJ_PPH21', 'BPJS_KES', 'BPJS_JHT', 'BPJS_JP', 'PPH21', 'KASBON', 'POT-KASBON']))
            ->when($type === 'deduction', fn ($q) => $q->where('category', SalaryComponentCategory::DEDUCTION)->whereNotIn('code', ['PPH21', 'KASBON', 'POT-KASBON', 'BPJS_KES', 'BPJS_JHT', 'BPJS_JP'])->where('is_system', false))
            ->when(! empty($usedIds), fn ($q) => $q->whereNotIn('id', $usedIds))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn ($comp) => ! in_array(strtolower(trim($comp->name)), $usedNames))
            ->map(fn ($comp) => [
                'id' => $comp->id,
                'text' => $comp->name.($comp->code ? ' ('.$comp->code.')' : ''),
            ])
            ->values()
            ->all();
    }

    public function applyMasterAllowance(int $index, mixed $componentId): void
    {
        if (! isset($this->allowanceRows[$index]) || empty($componentId)) {
            return;
        }

        $component = SalaryComponent::find($componentId);
        if ($component) {
            $this->allowanceRows[$index]['salary_component_id'] = $component->id;
            $this->allowanceRows[$index]['name'] = $component->name;
            $this->syncAutoBpjsAndTax();
        }
    }

    public function applyMasterDeduction(int $index, mixed $componentId): void
    {
        if (! isset($this->deductionRows[$index]) || empty($componentId)) {
            return;
        }

        $component = SalaryComponent::find($componentId);
        if ($component) {
            $this->deductionRows[$index]['salary_component_id'] = $component->id;
            $this->deductionRows[$index]['name'] = $component->name;
            $this->syncAutoBpjsAndTax();
        }
    }

    public function addAllowanceRow(): void
    {
        if (! $this->isEditable) {
            return;
        }

        $this->allowanceRows[] = [
            'id' => null,
            'name' => '',
            'amount' => '',
            'calc_mode' => 'nominal',
            'percentage' => '',
            'salary_component_id' => null,
            'cash_advance_installment_id' => null,
            'category' => 'allowance',
            'is_system' => false,
            'notes' => '',
        ];
    }

    public function removeAllowanceRow(int $index): void
    {
        if (! $this->isEditable) {
            return;
        }

        if (isset($this->allowanceRows[$index])) {
            unset($this->allowanceRows[$index]);
            $this->allowanceRows = array_values($this->allowanceRows);
            $this->ensureTrailingEmptyRow('allowance');
            $this->syncAutoBpjsAndTax();
        }
    }

    public function addDeductionRow(): void
    {
        if (! $this->isEditable) {
            return;
        }

        $this->deductionRows[] = [
            'id' => null,
            'name' => '',
            'amount' => '',
            'calc_mode' => 'nominal',
            'percentage' => '',
            'salary_component_id' => null,
            'cash_advance_installment_id' => null,
            'category' => 'deduction',
            'is_system' => false,
            'notes' => '',
        ];
    }

    public function removeDeductionRow(int $index): void
    {
        if (! $this->isEditable) {
            return;
        }

        if (isset($this->deductionRows[$index])) {
            unset($this->deductionRows[$index]);
            $this->deductionRows = array_values($this->deductionRows);
            $this->ensureTrailingEmptyRow('deduction');
            $this->syncAutoBpjsAndTax();
        }
    }

    public function ensureTrailingEmptyRow(string $type): void
    {
        if ($type === 'allowance') {
            while (count($this->allowanceRows) > 1) {
                $last = end($this->allowanceRows);
                $prev = prev($this->allowanceRows);
                if ($this->isRowEmpty($last) && $this->isRowEmpty($prev)) {
                    array_pop($this->allowanceRows);
                } else {
                    break;
                }
            }

            $last = end($this->allowanceRows);
            if ($last === false || ! $this->isRowEmpty($last)) {
                $this->addAllowanceRow();
            }
        } else {
            while (count($this->deductionRows) > 1) {
                $last = end($this->deductionRows);
                $prev = prev($this->deductionRows);
                if ($this->isRowEmpty($last) && $this->isRowEmpty($prev)) {
                    array_pop($this->deductionRows);
                } else {
                    break;
                }
            }

            $last = end($this->deductionRows);
            if ($last === false || ! $this->isRowEmpty($last)) {
                $this->addDeductionRow();
            }
        }
    }

    public function isRowEmpty(array $row): bool
    {
        if (! empty($row['is_system'])) {
            return false;
        }

        $nameEmpty = empty(trim((string) ($row['name'] ?? '')));
        $amountEmpty = ! isset($row['amount']) || $row['amount'] === '' || $row['amount'] === null || (float) $row['amount'] == 0;
        $pctEmpty = ! isset($row['percentage']) || $row['percentage'] === '' || $row['percentage'] === null || (float) $row['percentage'] == 0;

        return $nameEmpty && $amountEmpty && $pctEmpty;
    }

    public function updatedAllowanceRows(mixed $value, ?string $key = null): void
    {
        if (! $this->isEditable || empty($key)) {
            return;
        }

        $parts = explode('.', $key);
        $index = (int) ($parts[0] ?? 0);
        $field = $parts[1] ?? '';

        if (! isset($this->allowanceRows[$index])) {
            return;
        }

        $basic = (float) ($this->item->basic_salary ?? 0);

        if ($field === 'name') {
            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                $comp = SalaryComponent::query()
                    ->where('is_active', true)
                    ->where('type', SalaryComponentType::ALLOWANCE)
                    ->whereNotIn('code', ['GAJI_POKOK', 'TUNJ_PPH21'])
                    ->where(function ($q) use ($trimmed) {
                        $q->where('name', $trimmed)
                            ->orWhere('code', $trimmed);
                    })
                    ->first();

                if ($comp) {
                    $this->allowanceRows[$index]['salary_component_id'] = $comp->id;
                    $this->allowanceRows[$index]['name'] = $comp->name;

                    $isPercentage = $comp->calculation_type === SalaryCalculationType::PERCENTAGE_OF_BASIC_SALARY
                        || ($comp->calculation_type instanceof \BackedEnum && $comp->calculation_type->value === 'persen_gaji_pokok')
                        || (is_string($comp->calculation_type) && $comp->calculation_type === 'persen_gaji_pokok');

                    if ($isPercentage) {
                        $this->allowanceRows[$index]['calc_mode'] = 'percentage';
                        $pct = $comp->default_value !== null ? (float) $comp->default_value : 0;
                        $this->allowanceRows[$index]['percentage'] = $pct > 0 ? $pct : '';
                        $this->allowanceRows[$index]['amount'] = $pct > 0 ? round(($pct / 100) * $basic) : '';
                    } else {
                        $this->allowanceRows[$index]['calc_mode'] = 'nominal';
                        $this->allowanceRows[$index]['percentage'] = '';
                        if ($comp->default_value !== null && (float) $comp->default_value > 0) {
                            $defaultVal = (float) $comp->default_value;
                            $ratio = $this->getEmployeeProrateRatio();
                            $isFixedAllowance = $comp->category === SalaryComponentCategory::FIXED_ALLOWANCE
                                || (is_string($comp->category) && $comp->category === 'pendapatan_tetap');
                            if ($isFixedAllowance && $ratio < 1.0) {
                                $this->allowanceRows[$index]['amount'] = round($defaultVal * $ratio);
                            } else {
                                $this->allowanceRows[$index]['amount'] = $defaultVal;
                            }
                        }
                    }
                } else {
                    $this->allowanceRows[$index]['salary_component_id'] = null;
                }
            }
        } elseif ($field === 'percentage') {
            if ($value === '' || $value === null) {
                $this->allowanceRows[$index]['amount'] = '';
            } else {
                $cleanPct = (float) str_replace(',', '.', (string) $value);
                $this->allowanceRows[$index]['percentage'] = $cleanPct;
                $this->allowanceRows[$index]['amount'] = round(($cleanPct / 100) * $basic);
            }
        } elseif ($field === 'amount') {
            $clean = preg_replace('/[^0-9]/', '', (string) $value);
            $this->allowanceRows[$index]['amount'] = $clean !== '' ? (float) $clean : '';
            if (($this->allowanceRows[$index]['calc_mode'] ?? 'nominal') === 'percentage' && $basic > 0 && ! empty($this->allowanceRows[$index]['amount'])) {
                $this->allowanceRows[$index]['percentage'] = round(((float) $this->allowanceRows[$index]['amount'] / $basic) * 100, 2);
            }
        }

        $this->ensureTrailingEmptyRow('allowance');
        $this->syncAutoBpjsAndTax();
    }

    public function updatedDeductionRows(mixed $value, ?string $key = null): void
    {
        if (! $this->isEditable || empty($key)) {
            return;
        }

        $parts = explode('.', $key);
        $index = (int) ($parts[0] ?? 0);
        $field = $parts[1] ?? '';

        if (! isset($this->deductionRows[$index])) {
            return;
        }

        $basic = (float) ($this->item->basic_salary ?? 0);

        if ($field === 'name') {
            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                $comp = SalaryComponent::query()
                    ->where('is_active', true)
                    ->where('type', SalaryComponentType::DEDUCTION)
                    ->whereNotIn('code', ['PPH21', 'KASBON', 'POT-KASBON', 'BPJS_KES', 'BPJS_JHT', 'BPJS_JP'])
                    ->where('is_system', false)
                    ->where(function ($q) use ($trimmed) {
                        $q->where('name', $trimmed)
                            ->orWhere('code', $trimmed);
                    })
                    ->first();

                if ($comp) {
                    $this->deductionRows[$index]['salary_component_id'] = $comp->id;
                    $this->deductionRows[$index]['name'] = $comp->name;

                    $isPercentage = $comp->calculation_type === SalaryCalculationType::PERCENTAGE_OF_BASIC_SALARY
                        || ($comp->calculation_type instanceof \BackedEnum && $comp->calculation_type->value === 'persen_gaji_pokok')
                        || (is_string($comp->calculation_type) && $comp->calculation_type === 'persen_gaji_pokok');

                    if ($isPercentage) {
                        $this->deductionRows[$index]['calc_mode'] = 'percentage';
                        $pct = $comp->default_value !== null ? (float) $comp->default_value : 0;
                        $this->deductionRows[$index]['percentage'] = $pct > 0 ? $pct : '';
                        $this->deductionRows[$index]['amount'] = $pct > 0 ? round(($pct / 100) * $basic) : '';
                    } else {
                        $this->deductionRows[$index]['calc_mode'] = 'nominal';
                        $this->deductionRows[$index]['percentage'] = '';
                        if ($comp->default_value !== null && (float) $comp->default_value > 0) {
                            $this->deductionRows[$index]['amount'] = (float) $comp->default_value;
                        }
                    }
                } else {
                    $this->deductionRows[$index]['salary_component_id'] = null;
                }
            }
        } elseif ($field === 'percentage') {
            if ($value === '' || $value === null) {
                $this->deductionRows[$index]['amount'] = '';
            } else {
                $cleanPct = (float) str_replace(',', '.', (string) $value);
                $this->deductionRows[$index]['percentage'] = $cleanPct;
                $this->deductionRows[$index]['amount'] = round(($cleanPct / 100) * $basic);
            }
        } elseif ($field === 'amount') {
            if (! empty($this->deductionRows[$index]['cash_advance_installment_id'])
                && is_numeric($value)
                && (float) $value < 0) {
                $this->deductionRows[$index]['amount'] = $value;
                $this->validateCashAdvanceDeductionRows($index);

                return;
            }

            $clean = preg_replace('/[^0-9]/', '', (string) $value);
            $this->deductionRows[$index]['amount'] = $clean !== '' ? (float) $clean : '';

            if (! empty($this->deductionRows[$index]['cash_advance_installment_id'])
                && ! $this->validateCashAdvanceDeductionRows($index)) {
                return;
            }

            if (($this->deductionRows[$index]['calc_mode'] ?? 'nominal') === 'percentage' && $basic > 0 && ! empty($this->deductionRows[$index]['amount'])) {
                $this->deductionRows[$index]['percentage'] = round(((float) $this->deductionRows[$index]['amount'] / $basic) * 100, 2);
            }
        }

        $this->ensureTrailingEmptyRow('deduction');
        $this->syncAutoBpjsAndTax();
    }

    public function toggleCalcMode(string $type, int $index): void
    {
        if (! $this->isEditable) {
            return;
        }

        $rows = $type === 'allowance' ? $this->allowanceRows : $this->deductionRows;
        if (! isset($rows[$index])) {
            return;
        }

        $currentMode = $rows[$index]['calc_mode'] ?? 'nominal';
        $newMode = $currentMode === 'percentage' ? 'nominal' : 'percentage';
        $basic = (float) ($this->item->basic_salary ?? 0);

        if ($type === 'allowance') {
            $this->allowanceRows[$index]['calc_mode'] = $newMode;
            if ($newMode === 'percentage') {
                $amount = (float) ($this->allowanceRows[$index]['amount'] ?? 0);
                if ($basic > 0 && $amount > 0 && empty($this->allowanceRows[$index]['percentage'])) {
                    $this->allowanceRows[$index]['percentage'] = round(($amount / $basic) * 100, 2);
                } elseif (! empty($this->allowanceRows[$index]['percentage'])) {
                    $pct = (float) $this->allowanceRows[$index]['percentage'];
                    $this->allowanceRows[$index]['amount'] = round(($pct / 100) * $basic);
                }
            }
        } else {
            $this->deductionRows[$index]['calc_mode'] = $newMode;
            if ($newMode === 'percentage') {
                $amount = (float) ($this->deductionRows[$index]['amount'] ?? 0);
                if ($basic > 0 && $amount > 0 && empty($this->deductionRows[$index]['percentage'])) {
                    $this->deductionRows[$index]['percentage'] = round(($amount / $basic) * 100, 2);
                } elseif (! empty($this->deductionRows[$index]['percentage'])) {
                    $pct = (float) $this->deductionRows[$index]['percentage'];
                    $this->deductionRows[$index]['amount'] = round(($pct / 100) * $basic);
                }
            }
        }

        $this->syncAutoBpjsAndTax();
    }

    public function getTotalAllowancesProperty(): float
    {
        $sum = 0;
        foreach ($this->allowanceRows as $row) {
            $sum += (float) ($row['amount'] ?? 0);
        }

        return $sum;
    }

    public function toggleDeductionEnabled(int $index): void
    {
        if (! $this->isEditable) {
            return;
        }

        if (isset($this->deductionRows[$index])) {
            $this->deductionRows[$index]['is_enabled'] = ! ($this->deductionRows[$index]['is_enabled'] ?? true);
            $this->syncAutoBpjsAndTax();
            $this->clearComputedProperties();
        }
    }

    /**
     * Validate editable payroll deductions linked to cash advances.
     * A payroll deduction can cover multiple installments, but cannot exceed the advance's remaining balance.
     */
    protected function validateCashAdvanceDeductionRows(?int $onlyIndex = null): bool
    {
        $indices = $onlyIndex === null ? array_keys($this->deductionRows) : [$onlyIndex];
        $rows = collect($indices)
            ->filter(fn (int $index) => ! empty($this->deductionRows[$index]['cash_advance_installment_id']))
            ->mapWithKeys(fn (int $index) => [$index => $this->deductionRows[$index]]);

        if ($rows->isEmpty()) {
            return true;
        }

        $installmentIds = $rows
            ->pluck('cash_advance_installment_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $installments = CashAdvanceInstallment::query()
            ->with('cashAdvance:id,employee_id,remaining_amount')
            ->whereKey($installmentIds)
            ->get()
            ->keyBy('id');
        $isValid = true;

        foreach ($rows as $index => $row) {
            $errorKey = "deductionRows.{$index}.amount";

            if (($row['is_enabled'] ?? true) === false) {
                $this->resetValidation($errorKey);

                continue;
            }

            $amountValue = $row['amount'] ?? 0;
            $amountValue = $amountValue === '' || $amountValue === null ? 0 : $amountValue;

            if (! is_numeric($amountValue) || ! is_finite((float) $amountValue) || (float) $amountValue < 0) {
                $this->addError($errorKey, 'Nominal potongan kasbon harus berupa angka nol atau lebih.');
                $isValid = false;

                continue;
            }

            $installment = $installments->get((int) $row['cash_advance_installment_id']);
            if (! $installment || (int) $installment->cashAdvance?->employee_id !== (int) $this->item->employee_id) {
                $this->addError($errorKey, 'Jadwal cicilan kasbon tidak valid untuk karyawan ini. Muat ulang halaman.');
                $isValid = false;

                continue;
            }

            $maxAmount = max(0, round((float) $installment->cashAdvance->remaining_amount, 2));
            if ((float) $amountValue > $maxAmount + 0.009) {
                $this->addError(
                    $errorKey,
                    'Nominal potongan tidak boleh melebihi sisa kasbon Rp '.number_format($maxAmount, 0, ',', '.').'.'
                );
                $isValid = false;

                continue;
            }

            $this->resetValidation($errorKey);
        }

        return $isValid;
    }

    protected function getEmployeeProrateRatio(): float
    {
        if (! $this->item->employee) {
            return 1.0;
        }

        $info = app(PayrollService::class)->calculateProratedSalary($this->item->employee, $this->period);

        return $info['is_prorated'] ? (float) ($info['ratio'] ?? 1.0) : 1.0;
    }

    public function resetKasbonDeduction(int $index): void
    {
        if (! $this->isEditable || ! isset($this->deductionRows[$index])) {
            return;
        }

        $total = (float) ($this->deductionRows[$index]['installment_total_amount'] ?? 0);
        $paid = (float) ($this->deductionRows[$index]['installment_paid_amount'] ?? 0);

        if ($total <= 0 && ! empty($this->deductionRows[$index]['cash_advance_installment_id'])) {
            $inst = CashAdvanceInstallment::find($this->deductionRows[$index]['cash_advance_installment_id']);
            if ($inst) {
                $total = (float) $inst->amount;
                $paid = (float) $inst->paid_amount;
                $this->deductionRows[$index]['installment_total_amount'] = $total;
                $this->deductionRows[$index]['installment_paid_amount'] = $paid;
            }
        }

        $normalAmount = max(0.0, round($total - $paid, 2));
        $this->deductionRows[$index]['amount'] = $normalAmount;
        $this->deductionRows[$index]['is_enabled'] = true;
        $this->resetValidation("deductionRows.{$index}.amount");
        $this->syncAutoBpjsAndTax();
        $this->clearComputedProperties();

        $this->dispatch('deduction-reset-'.$index, amount: $normalAmount);
    }

    public function settleRemainingKasbon(int $index): void
    {
        if (! $this->isEditable || ! isset($this->deductionRows[$index])) {
            return;
        }

        $rem = (float) ($this->deductionRows[$index]['advance_remaining_amount'] ?? 0);
        if ($rem <= 0 && ! empty($this->deductionRows[$index]['cash_advance_installment_id'])) {
            $inst = CashAdvanceInstallment::with('cashAdvance')->find($this->deductionRows[$index]['cash_advance_installment_id']);
            if ($inst?->cashAdvance) {
                $rem = (float) $inst->cashAdvance->remaining_amount;
                $this->deductionRows[$index]['advance_remaining_amount'] = $rem;
            }
        }

        if ($rem > 0) {
            $this->deductionRows[$index]['amount'] = $rem;
            $this->deductionRows[$index]['is_enabled'] = true;
            $this->validateCashAdvanceDeductionRows($index);
            $this->syncAutoBpjsAndTax();
            $this->clearComputedProperties();

            $this->dispatch('deduction-reset-'.$index, amount: $rem);
            $this->dispatch('swal:toast', [
                'type' => 'info',
                'message' => 'Nominal potongan diset ke pelunasan sisa kasbon Rp '.number_format($rem, 0, ',', '.'),
            ]);
        }
    }

    public function clearComputedProperties(): void
    {
        unset(
            $this->hasTaxOrBpjsRows,
            $this->hasAnyTaxOrBpjsEnabled,
            $this->allTaxAndBpjsEnabled,
            $this->hasAnyCompanyBpjsEnabled,
            $this->allCompanyBpjsEnabled,
            $this->totalDeductions,
            $this->totalAllowances,
            $this->grossSalary,
            $this->totalCompanyContributions,
            $this->costToCompany,
            $this->estimatedNetSalary,
            $this->employeeTotalRemainingCashAdvance,
        );
    }

    public function getEmployeeTotalRemainingCashAdvanceProperty(): float
    {
        if (! $this->item->employee_id) {
            return 0.0;
        }

        return (float) CashAdvance::query()
            ->where('employee_id', $this->item->employee_id)
            ->whereIn('status', [CashAdvanceStatus::APPROVED->value, CashAdvanceStatus::ACTIVE->value])
            ->sum('remaining_amount');
    }

    public function getGrossSalaryProperty(): float
    {
        $basic = (float) ($this->item->basic_salary ?? 0);

        return $basic + $this->totalAllowances;
    }

    public function getCostToCompanyProperty(): float
    {
        return $this->grossSalary + $this->totalCompanyContributions;
    }

    public function getHasTaxOrBpjsRowsProperty(): bool
    {
        foreach ($this->deductionRows as $row) {
            $cat = $row['category'] ?? '';
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            $isTaxOrBpjs = ($cat === 'tax' || $cat === 'bpjs' || str_starts_with($name, 'bpjs') || strcasecmp(trim((string) ($row['name'] ?? '')), 'pph 21') === 0);
            if ($isTaxOrBpjs && empty($row['cash_advance_installment_id'])) {
                return true;
            }
        }

        return false;
    }

    public function getHasAnyTaxOrBpjsEnabledProperty(): bool
    {
        foreach ($this->deductionRows as $row) {
            $cat = $row['category'] ?? '';
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            $isTaxOrBpjs = ($cat === 'tax' || $cat === 'bpjs' || str_starts_with($name, 'bpjs') || strcasecmp(trim((string) ($row['name'] ?? '')), 'pph 21') === 0);
            if ($isTaxOrBpjs && empty($row['cash_advance_installment_id'])) {
                if ($row['is_enabled'] ?? true) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAllTaxAndBpjsEnabledProperty(): bool
    {
        $hasAny = false;
        foreach ($this->deductionRows as $row) {
            $cat = $row['category'] ?? '';
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            $isTaxOrBpjs = ($cat === 'tax' || $cat === 'bpjs' || str_starts_with($name, 'bpjs') || strcasecmp(trim((string) ($row['name'] ?? '')), 'pph 21') === 0);
            if ($isTaxOrBpjs && empty($row['cash_advance_installment_id'])) {
                $hasAny = true;
                if (! ($row['is_enabled'] ?? true)) {
                    return false;
                }
            }
        }

        return $hasAny;
    }

    public function toggleAllTaxAndBpjs(): void
    {
        if (! $this->isEditable) {
            return;
        }

        $targetState = ! $this->getHasAnyTaxOrBpjsEnabledProperty();

        foreach ($this->deductionRows as $idx => $row) {
            $cat = $row['category'] ?? '';
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            $isTaxOrBpjs = ($cat === 'tax' || $cat === 'bpjs' || str_starts_with($name, 'bpjs') || strcasecmp(trim((string) ($row['name'] ?? '')), 'pph 21') === 0);
            if ($isTaxOrBpjs && empty($row['cash_advance_installment_id'])) {
                $this->deductionRows[$idx]['is_enabled'] = $targetState;
            }
        }

        $this->syncAutoBpjsAndTax();
        $this->clearComputedProperties();
    }

    public function toggleCompanyBpjs(string $key): void
    {
        if (! $this->isEditable) {
            return;
        }

        if (array_key_exists($key, $this->companyBpjsStatus)) {
            $this->companyBpjsStatus[$key] = ! $this->companyBpjsStatus[$key];
            $this->syncAutoBpjsAndTax();
            $this->clearComputedProperties();
        }
    }

    public function getHasAnyCompanyBpjsEnabledProperty(): bool
    {
        foreach ($this->companyBpjsStatus as $isActive) {
            if ($isActive) {
                return true;
            }
        }

        return false;
    }

    public function getAllCompanyBpjsEnabledProperty(): bool
    {
        foreach ($this->companyBpjsStatus as $isActive) {
            if (! $isActive) {
                return false;
            }
        }

        return true;
    }

    public function toggleAllCompanyBpjs(): void
    {
        if (! $this->isEditable) {
            return;
        }

        $targetState = ! $this->getHasAnyCompanyBpjsEnabledProperty();
        foreach ($this->companyBpjsStatus as $key => $val) {
            $this->companyBpjsStatus[$key] = $targetState;
        }
        $this->syncAutoBpjsAndTax();
        $this->clearComputedProperties();
    }

    public function getTotalCompanyContributionsProperty(): float
    {
        $breakdown = $this->bpjsBreakdown['company'] ?? [];
        $total = 0.0;
        foreach ($this->companyBpjsStatus as $key => $isActive) {
            if ($isActive && isset($breakdown[$key])) {
                $total += (float) $breakdown[$key];
            }
        }

        return $total;
    }

    public function getTotalDeductionsProperty(): float
    {
        $sum = 0;
        foreach ($this->deductionRows as $row) {
            if (($row['is_enabled'] ?? true) === false) {
                continue;
            }
            $sum += (float) ($row['amount'] ?? 0);
        }

        return $sum;
    }

    public function getEstimatedNetSalaryProperty(): float
    {
        $basic = (float) ($this->item->basic_salary ?? 0);

        return max(0, $basic + $this->totalAllowances - $this->totalDeductions);
    }

    public function getOtherItemsProperty(): Collection
    {
        return $this->period->items()
            ->select(['id', 'payroll_period_id', 'employee_id'])
            ->with(['employee:id,full_name', 'employee.detail:id,employee_id,employee_code'])
            ->orderBy('id')
            ->get();
    }

    public function openAddAllowanceModal(): void
    {
        $this->resetErrorBag();
        $this->modalType = 'allowance';
        $this->modalIndex = null;
        $this->modalName = '';
        $this->modalAmount = '';
        $this->modalCalcMode = 'nominal';
        $this->modalPercentage = '';
        $this->modalComponentId = null;
        $this->dispatch('open-modal', id: 'componentModal', type: 'allowance', componentId: null, options: $this->getAvailableMasterComponents('allowance', null));
    }

    public function openEditAllowanceModal(int $index): void
    {
        if (! isset($this->allowanceRows[$index])) {
            return;
        }

        $this->resetErrorBag();
        $this->modalType = 'allowance';
        $this->modalIndex = $index;
        $this->modalName = $this->allowanceRows[$index]['name'];
        $this->modalAmount = $this->allowanceRows[$index]['amount'];
        $this->modalCalcMode = 'nominal';

        $basic = (float) ($this->item->basic_salary ?? 0);
        $amount = (float) $this->modalAmount;
        $this->modalPercentage = ($basic > 0 && $amount > 0) ? round(($amount / $basic) * 100, 2) : '';

        $this->modalComponentId = $this->allowanceRows[$index]['salary_component_id'] ?? null;
        $this->dispatch('open-modal', id: 'componentModal', type: 'allowance', componentId: $this->modalComponentId, options: $this->getAvailableMasterComponents('allowance', $index));
    }

    public function openAddDeductionModal(): void
    {
        $this->resetErrorBag();
        $this->modalType = 'deduction';
        $this->modalIndex = null;
        $this->modalName = '';
        $this->modalAmount = '';
        $this->modalCalcMode = 'nominal';
        $this->modalPercentage = '';
        $this->modalComponentId = null;
        $this->dispatch('open-modal', id: 'componentModal', type: 'deduction', componentId: null, options: $this->getAvailableMasterComponents('deduction', null));
    }

    public function openEditDeductionModal(int $index): void
    {
        if (! isset($this->deductionRows[$index])) {
            return;
        }

        $this->resetErrorBag();
        $this->modalType = 'deduction';
        $this->modalIndex = $index;
        $this->modalName = $this->deductionRows[$index]['name'];
        $this->modalAmount = $this->deductionRows[$index]['amount'];
        $this->modalCalcMode = 'nominal';

        $basic = (float) ($this->item->basic_salary ?? 0);
        $amount = (float) $this->modalAmount;
        $this->modalPercentage = ($basic > 0 && $amount > 0) ? round(($amount / $basic) * 100, 2) : '';

        $this->modalComponentId = $this->deductionRows[$index]['salary_component_id'] ?? null;
        $this->dispatch('open-modal', id: 'componentModal', type: 'deduction', componentId: $this->modalComponentId, options: $this->getAvailableMasterComponents('deduction', $index));
    }

    public function updatedModalPercentage(mixed $value): void
    {
        if ($this->modalCalcMode !== 'percentage') {
            return;
        }

        if ($value === '' || $value === null) {
            $this->modalAmount = '';

            return;
        }

        $clean = (float) str_replace(',', '.', (string) $value);
        $basic = (float) ($this->item->basic_salary ?? 0);
        $this->modalAmount = round(($clean / 100) * $basic);
    }

    public function updatedModalCalcMode(string $value): void
    {
        if ($value === 'percentage') {
            $basic = (float) ($this->item->basic_salary ?? 0);
            $amount = (float) $this->modalAmount;
            if ($basic > 0 && $amount > 0 && ($this->modalPercentage === '' || $this->modalPercentage === null)) {
                $this->modalPercentage = round(($amount / $basic) * 100, 2);
            } elseif ($this->modalPercentage !== '' && $this->modalPercentage !== null) {
                $pct = (float) str_replace(',', '.', (string) $this->modalPercentage);
                $this->modalAmount = round(($pct / 100) * $basic);
            }
        }
    }

    public function applyModalMaster(mixed $componentId): void
    {
        if (empty($componentId)) {
            $this->modalComponentId = null;

            return;
        }

        $comp = SalaryComponent::find($componentId);
        if ($comp) {
            $this->modalComponentId = $comp->id;
            $this->modalName = $comp->name;

            $basic = (float) ($this->item->basic_salary ?? 0);

            $isPercentage = $comp->calculation_type === SalaryCalculationType::PERCENTAGE_OF_BASIC_SALARY
                || ($comp->calculation_type instanceof \BackedEnum && $comp->calculation_type->value === 'persen_gaji_pokok')
                || (is_string($comp->calculation_type) && $comp->calculation_type === 'persen_gaji_pokok');

            if ($isPercentage) {
                $this->modalCalcMode = 'percentage';
                $pct = $comp->default_value !== null ? (float) $comp->default_value : 0;
                $this->modalPercentage = $pct > 0 ? $pct : '';
                $this->modalAmount = $pct > 0 ? round(($pct / 100) * $basic) : '';
            } else {
                $this->modalCalcMode = 'nominal';
                $this->modalPercentage = '';
                if ($comp->default_value !== null && (float) $comp->default_value > 0) {
                    $defaultVal = (float) $comp->default_value;
                    $ratio = $this->getEmployeeProrateRatio();
                    $isFixedAllowance = $comp->category === SalaryComponentCategory::FIXED_ALLOWANCE
                        || (is_string($comp->category) && $comp->category === 'pendapatan_tetap');
                    if ($isFixedAllowance && $ratio < 1.0) {
                        $this->modalAmount = round($defaultVal * $ratio);
                    } else {
                        $this->modalAmount = $defaultVal;
                    }
                }
            }
        }
    }

    public function saveModalComponent(): void
    {
        if ($this->modalCalcMode === 'percentage') {
            $basic = (float) ($this->item->basic_salary ?? 0);
            if ($this->modalPercentage !== '' && $this->modalPercentage !== null) {
                $pct = (float) str_replace(',', '.', (string) $this->modalPercentage);
                $this->modalAmount = round(($pct / 100) * $basic);
            }
        } elseif ($this->modalAmount !== '' && $this->modalAmount !== null) {
            $cleaned = preg_replace('/[^0-9]/', '', (string) $this->modalAmount);
            $this->modalAmount = $cleaned !== '' ? (float) $cleaned : '';
        }

        $rules = [
            'modalName' => ['required', 'string', 'max:255'],
            'modalAmount' => ['required', 'numeric', 'min:0'],
        ];

        $messages = [
            'modalName.required' => 'Nama komponen wajib diisi.',
            'modalAmount.required' => 'Nominal komponen wajib diisi.',
            'modalAmount.numeric' => 'Nominal harus berupa angka.',
            'modalAmount.min' => 'Nominal komponen minimal 0.',
        ];

        if ($this->modalCalcMode === 'percentage') {
            $rules['modalPercentage'] = ['required', 'numeric', 'min:0'];
            $messages['modalPercentage.required'] = 'Persentase komponen wajib diisi.';
            $messages['modalPercentage.numeric'] = 'Persentase harus berupa angka.';
            $messages['modalPercentage.min'] = 'Persentase komponen minimal 0.';
        }

        $this->validate($rules, $messages);

        $rows = $this->modalType === 'allowance' ? $this->allowanceRows : $this->deductionRows;
        if ($this->modalComponentId) {
            $duplicateComponent = collect($rows)->contains(function ($row, $idx) {
                if ($this->modalIndex !== null && $idx === $this->modalIndex) {
                    return false;
                }

                return ! empty($row['salary_component_id']) && (int) $row['salary_component_id'] === (int) $this->modalComponentId;
            });

            if ($duplicateComponent) {
                $this->addError('modalComponentId', 'Komponen ini sudah ditambahkan sebelumnya.');

                return;
            }
        }

        if (! empty($this->modalName)) {
            $duplicateName = collect($rows)->contains(function ($row, $idx) {
                if ($this->modalIndex !== null && $idx === $this->modalIndex) {
                    return false;
                }

                return ! empty($row['name']) && strcasecmp(trim($row['name']), trim($this->modalName)) === 0;
            });

            if ($duplicateName) {
                $this->addError('modalName', 'Komponen dengan nama ini sudah ditambahkan.');

                return;
            }
        }

        $rowData = [
            'id' => null,
            'name' => trim($this->modalName),
            'amount' => (float) $this->modalAmount,
            'calc_mode' => $this->modalCalcMode,
            'percentage' => $this->modalPercentage,
            'salary_component_id' => $this->modalComponentId,
            'cash_advance_installment_id' => null,
            'notes' => '',
        ];

        if ($this->modalType === 'allowance') {
            if ($this->modalIndex !== null && isset($this->allowanceRows[$this->modalIndex])) {
                $rowData['id'] = $this->allowanceRows[$this->modalIndex]['id'] ?? null;
                $this->allowanceRows[$this->modalIndex] = $rowData;
            } elseif (count($this->allowanceRows) === 1 && empty($this->allowanceRows[0]['name']) && empty($this->allowanceRows[0]['amount'])) {
                $this->allowanceRows[0] = $rowData;
            } else {
                $this->allowanceRows[] = $rowData;
            }
        } else {
            if ($this->modalIndex !== null && isset($this->deductionRows[$this->modalIndex])) {
                $rowData['id'] = $this->deductionRows[$this->modalIndex]['id'] ?? null;
                $rowData['cash_advance_installment_id'] = $this->deductionRows[$this->modalIndex]['cash_advance_installment_id'] ?? null;
                $this->deductionRows[$this->modalIndex] = $rowData;
            } elseif (count($this->deductionRows) === 1 && empty($this->deductionRows[0]['name']) && empty($this->deductionRows[0]['amount'])) {
                $this->deductionRows[0] = $rowData;
            } else {
                $this->deductionRows[] = $rowData;
            }
        }

        $this->ensureTrailingEmptyRow($this->modalType);
        $this->syncAutoBpjsAndTax();

        $this->dispatch('close-modal', id: 'componentModal');
    }

    public function switchEmployee(int $itemId): mixed
    {
        return redirect()->route('payrolls.items.edit', [$this->period, $itemId]);
    }

    public function getBpjsBreakdownProperty(): array
    {
        $company = CompanyProfile::getProfile();
        $basicSalary = (float) ($this->item->basic_salary ?? 0);
        $grossSalary = $basicSalary + $this->totalAllowances;
        $taxService = app(TaxCalculationService::class);

        return $taxService->calculateBpjsBreakdown($grossSalary, $company);
    }

    public function syncAutoBpjsAndTax(): void
    {
        $company = CompanyProfile::getProfile();
        $basicSalary = (float) ($this->item->basic_salary ?? 0);
        $grossSalary = $basicSalary + $this->totalAllowances;
        $taxService = app(TaxCalculationService::class);

        // 1. Sync BPJS deductions based on Gaji Kotor
        if ($grossSalary > 0) {
            $bpjs = $taxService->calculateBpjsBreakdown($grossSalary, $company);

            foreach ($this->deductionRows as $idx => $row) {
                if (empty($row['is_system']) && ($row['category'] ?? '') !== 'bpjs') {
                    continue;
                }

                $name = strtolower(trim($row['name'] ?? ''));
                $isEnabled = $row['is_enabled'] ?? true;
                if (str_starts_with($name, 'bpjs kesehatan')) {
                    $this->deductionRows[$idx]['amount'] = $isEnabled ? (float) $bpjs['employee']['bpjs_kes'] : 0.0;
                    if (! $isEnabled) {
                        $this->deductionRows[$idx]['notes'] = 'Nonaktif';
                    }
                } elseif (str_starts_with($name, 'bpjs jht')) {
                    $this->deductionRows[$idx]['amount'] = $isEnabled ? (float) $bpjs['employee']['bpjs_jht'] : 0.0;
                    if (! $isEnabled) {
                        $this->deductionRows[$idx]['notes'] = 'Nonaktif';
                    }
                } elseif (str_starts_with($name, 'bpjs pensiun') || str_starts_with($name, 'bpjs jp')) {
                    $this->deductionRows[$idx]['amount'] = $isEnabled ? (float) $bpjs['employee']['bpjs_jp'] : 0.0;
                    if (! $isEnabled) {
                        $this->deductionRows[$idx]['notes'] = 'Nonaktif';
                    }
                }
            }
        }

        // 2. Sync PPh 21 based on Gaji Kotor
        if ($this->taxConfigurationWarning === '' && $this->item->employee?->detail && $basicSalary > 0) {
            $pphRowIndex = null;
            $isPphEnabled = (bool) $company->enable_pph21;
            foreach ($this->deductionRows as $idx => $row) {
                if (($row['category'] ?? '') === 'tax' || str_starts_with(strtolower(trim($row['name'] ?? '')), 'pph 21')) {
                    $pphRowIndex = $idx;
                    $isPphEnabled = $row['is_enabled'] ?? true;
                    break;
                }
            }

            $month = (int) $this->period->month;
            $year = (int) $this->period->year;

            $allowanceList = collect($this->allowanceRows)
                ->filter(fn ($r) => ! empty(trim($r['name'] ?? '')) && (float) ($r['amount'] ?? 0) > 0 && ($r['category'] ?? '') !== 'tax')
                ->values()
                ->all();

            $taxResult = $taxService->calculateMonthlyTax(
                $this->item->employee,
                $basicSalary,
                $allowanceList,
                $company,
                $month,
                $year,
                $isPphEnabled,
                (bool) $this->item->is_final_tax_period,
                $this->currentPensionDeductionFromRows()
            );

            if ($pphRowIndex !== null) {
                $taxAmt = $isPphEnabled ? (float) $taxResult['pph21_amount'] : 0.0;
                $this->deductionRows[$pphRowIndex]['amount'] = $taxAmt;
                if ($taxAmt < 0 && $isPphEnabled) {
                    $this->deductionRows[$pphRowIndex]['name'] = 'PPh 21 (Lebih Bayar)';
                    $this->deductionRows[$pphRowIndex]['notes'] = 'Kelebihan potong PPh 21 masa sebelumnya dikembalikan ke karyawan';
                } elseif ($taxAmt >= 0 && $isPphEnabled) {
                    $this->deductionRows[$pphRowIndex]['name'] = 'PPh 21';
                    $this->deductionRows[$pphRowIndex]['notes'] = $taxResult['notes'] ?? 'Potongan PPh 21 otomatis';
                } elseif (! $isPphEnabled) {
                    $this->deductionRows[$pphRowIndex]['notes'] = 'Nonaktif';
                }
            }

            $this->item->ptkp_status = $taxResult['ptkp_status'];
            $this->item->tax_method = $taxResult['tax_method'];
            $this->item->taxable_gross = $isPphEnabled ? (float) $taxResult['taxable_gross'] : 0.0;
            $this->item->ter_rate = (float) $taxResult['ter_rate'];
            $this->item->ter_category = $taxResult['ter_category'];
            $this->item->pph21_amount = $isPphEnabled ? (float) $taxResult['pph21_amount'] : 0.0;

            if ($taxResult['tax_method'] === TaxMethod::GROSS_UP) {
                $this->item->tax_allowance = $isPphEnabled ? (float) $taxResult['tax_allowance'] : 0.0;
            }
        }
    }

    protected function currentPensionDeductionFromRows(): float
    {
        $total = 0.0;

        foreach ($this->deductionRows as $row) {
            if (($row['category'] ?? '') !== 'bpjs' || ! ($row['is_enabled'] ?? true)) {
                continue;
            }

            $name = strtolower(trim((string) ($row['name'] ?? '')));
            if (str_starts_with($name, 'bpjs jht')
                || str_starts_with($name, 'bpjs pensiun')
                || str_starts_with($name, 'bpjs jp')) {
                $total += (float) ($row['amount'] ?? 0);
            }
        }

        return $total;
    }

    protected function getTaxConfigurationWarning(): string
    {
        $employee = $this->item->employee;
        if (! $employee) {
            return '';
        }

        $recipientType = $employee->detail?->tax_recipient_type;
        if ($employee->employment_status === EmploymentStatus::MAGANG) {
            return 'PPh 21 otomatis belum mendukung pegawai berstatus Magang. Komponen pajak pada slip ini tidak dihitung ulang otomatis.';
        }

        if ($recipientType === TaxRecipientType::OutsideScope) {
            return 'Jenis penerima penghasilan pegawai ini berada di luar cakupan perhitungan PPh 21 saat ini. Komponen pajak pada slip ini tidak dihitung ulang otomatis.';
        }

        if ($employee->employment_status !== EmploymentStatus::TETAP && $recipientType !== TaxRecipientType::MonthlyEmployee) {
            return 'Jenis penerima penghasilan pajak pegawai ini belum ditetapkan. Atur klasifikasi pada Data Karyawan sebelum menghitung PPh 21; pegawai kontrak penuh waktu dapat memakai skema pegawai bulanan.';
        }

        return '';
    }

    public function recalculateTax(PayrollService $service): void
    {
        if (! $this->isEditable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Dapat Diubah',
                'message' => $this->item->isApproved()
                    ? 'Slip gaji karyawan ini telah disetujui (ACC) oleh Direktur sehingga tidak dapat diubah kembali.'
                    : 'Status penggajian tidak dalam tahap draf atau revisi.',
            ]);

            return;
        }

        try {
            if (! $this->persistItemLines($service)) {
                return;
            }

            $this->loadComponentRows();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Pajak Diperbarui',
                'message' => 'Kalkulasi PPh 21 dan BPJS berhasil dihitung ulang.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghitung Pajak',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Set an earlier payroll month as the last PPh 21 period for this employee.
     */
    public function toggleFinalTaxPeriod(PayrollService $service): void
    {
        if (! $this->isEditable) {
            return;
        }

        try {
            $this->item = $service->setFinalTaxPeriod($this->item, ! (bool) $this->item->is_final_tax_period, auth()->user());
            $this->period->refresh();
            $this->loadComponentRows();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Masa Pajak Diperbarui',
                'message' => 'Perhitungan PPh 21 berhasil diperbarui.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui Masa Pajak',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the progressive reconciliation detail for the employee's final tax period.
     */
    protected function buildFinalTaxCalculationDetail(
        array $taxResult,
        float $grossSalary,
        string $ptkpStatus,
        PtkpStatus $ptkpEnum,
        Employee $employee,
        string $mode = 'ter_scheme'
    ): array {
        $recon = $taxResult['annual_reconciliation'] ?? null;
        $monthsList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $periodName = $monthsList[(int) $this->period->month] ?? ($this->period->end_date?->locale('id')->translatedFormat('F') ?? 'Terakhir');
        if (! $recon) {
            return [
                'title' => $mode === 'pph21' ? 'Rincian Perhitungan PPh 21 (Masa Terakhir)' : 'Dasar Skema Tarif PPh 21 (Masa Terakhir)',
                'rows' => [
                    [
                        'component' => 'Skema Masa Pajak Terakhir',
                        'calc' => 'Bulan '.$periodName,
                        'amount' => 'Pasal 17 UU HPP',
                        'is_bold' => true,
                    ],
                ],
                'summary_text' => 'Data rekonsiliasi masa terakhir belum tersedia.',
            ];
        }

        $isNonNpwp = ! empty($taxResult['is_non_npwp_penalized']);
        $finalPeriodTax = (float) ($recon['final_period_tax'] ?? 0);
        $previousTaxPaid = (float) ($recon['tax_paid_before_final_period'] ?? 0);
        $totalAnnualTax = (float) ($recon['total_annual_tax'] ?? 0);
        $monthsCount = (int) ($recon['months_count_before_final_period'] ?? 0);

        $rows = [
            [
                'component' => 'Skema Pajak Masa Terakhir',
                'calc' => 'Masa Pajak '.$periodName,
                'amount' => 'Pasal 17 UU HPP',
                'is_bold' => true,
                'icon' => 'fas fa-balance-scale',
            ],
            [
                'component' => 'Status PTKP Karyawan',
                'calc' => 'PTKP '.$ptkpStatus.' ('.$ptkpEnum->description().')',
                'amount' => 'Rp'.number_format($recon['ptkp'], 0, ',', '.'),
                'is_bold' => false,
                'icon' => 'fas fa-user-shield',
            ],
            [
                'component' => 'Penghasilan Bruto Masa Sebelumnya',
                'calc' => $monthsCount.' Bulan masa pajak sebelumnya',
                'amount' => 'Rp'.number_format($recon['gross_before_final_period'], 0, ',', '.'),
                'is_bold' => false,
                'icon' => 'fas fa-history',
            ],
            [
                'component' => 'Penghasilan Bruto Masa '.$periodName,
                'calc' => 'Gaji Pokok + Tunjangan + Premi Asuransi Kantor',
                'amount' => 'Rp'.number_format($recon['final_period_gross'], 0, ',', '.'),
                'is_bold' => false,
                'icon' => 'fas fa-calendar-day',
            ],
            [
                'component' => 'Total Penghasilan Bruto Setahun',
                'calc' => 'Akumulasi masa sebelumnya + masa '.$periodName,
                'amount' => 'Rp'.number_format($recon['annual_gross'], 0, ',', '.'),
                'is_bold' => true,
                'icon' => 'fas fa-money-bill-wave',
            ],
            [
                'component' => 'Pengurang: Biaya Jabatan Setahun',
                'calc' => '5% dari bruto, batas '.$recon['months_worked'].' bulan × Rp500.000',
                'amount' => '-Rp'.number_format($recon['biaya_jabatan'], 0, ',', '.'),
                'is_bold' => false,
                'icon' => 'fas fa-briefcase',
            ],
        ];

        if (($recon['pension_deduction'] ?? 0) > 0) {
            $rows[] = [
                'component' => 'Pengurang: Iuran JHT & Pensiun Setahun',
                'calc' => 'Iuran BPJS JHT & JP dibayar karyawan',
                'amount' => '-Rp'.number_format($recon['pension_deduction'], 0, ',', '.'),
                'is_bold' => false,
                'icon' => 'fas fa-shield-alt',
            ];
        }

        $rows[] = [
            'component' => 'Penghasilan Neto Setahun',
            'calc' => 'Bruto Setahun – Biaya Jabatan – Iuran BPJS',
            'amount' => 'Rp'.number_format($recon['net_income'], 0, ',', '.'),
            'is_bold' => true,
            'icon' => 'fas fa-calculator',
        ];

        $rows[] = [
            'component' => 'PTKP (Batas Tidak Kena Pajak)',
            'calc' => 'Pengurang bebas pajak untuk '.$ptkpStatus,
            'amount' => '-Rp'.number_format($recon['ptkp'], 0, ',', '.'),
            'is_bold' => false,
            'icon' => 'fas fa-id-card',
        ];

        $rows[] = [
            'component' => 'Penghasilan Kena Pajak (PKP)',
            'calc' => 'Neto Setahun – PTKP (dibulatkan ribuan ke bawah)',
            'amount' => 'Rp'.number_format($recon['pkp'], 0, ',', '.'),
            'is_bold' => true,
            'icon' => 'fas fa-chart-line',
        ];

        if (! empty($recon['brackets'])) {
            foreach ($recon['brackets'] as $b) {
                $rows[] = [
                    'component' => 'Lapisan '.$b['layer'].' Tarif Progresif ('.$b['rate_percent'].')',
                    'calc' => 'Lapisan '.$b['range_label'].': '.$b['rate_percent'].' × Rp'.number_format($b['taxable_amount'], 0, ',', '.'),
                    'amount' => 'Rp'.number_format($b['tax_amount'], 0, ',', '.'),
                    'is_bold' => false,
                    'icon' => 'fas fa-percentage',
                ];
            }
        } else {
            $rows[] = [
                'component' => 'Tarif Progresif Pasal 17 UU HPP',
                'calc' => 'Penghasilan neto di bawah batas PTKP (PKP = Rp0)',
                'amount' => 'Rp0',
                'is_bold' => false,
                'icon' => 'fas fa-percentage',
            ];
        }

        $rows[] = [
            'component' => 'Total PPh 21 Terutang Setahun',
            'calc' => 'Akumulasi seluruh lapisan tarif Pasal 17',
            'amount' => 'Rp'.number_format($recon['total_annual_tax_base'], 0, ',', '.'),
            'is_bold' => true,
            'icon' => 'fas fa-file-invoice-dollar',
        ];

        if ($isNonNpwp && ($recon['non_npwp_penalty'] ?? 0) > 0) {
            $rows[] = [
                'component' => 'Penalti Non-NPWP (+20%)',
                'calc' => '20% × Rp'.number_format($recon['total_annual_tax_base'], 0, ',', '.'),
                'amount' => '+Rp'.number_format($recon['non_npwp_penalty'], 0, ',', '.'),
                'is_bold' => true,
                'icon' => 'fas fa-exclamation-triangle',
            ];
            $rows[] = [
                'component' => 'Total PPh 21 Setahun (+ Penalti)',
                'calc' => 'Pajak Terutang Setahun setelah penalti non-NPWP',
                'amount' => 'Rp'.number_format($totalAnnualTax, 0, ',', '.'),
                'is_bold' => true,
                'icon' => 'fas fa-file-invoice-dollar',
            ];
        }

        $rows[] = [
            'component' => 'Kredit Pajak Masa Sebelumnya',
            'calc' => 'PPh 21 yang telah dipotong pada masa sebelumnya',
            'amount' => '-Rp'.number_format($previousTaxPaid, 0, ',', '.'),
            'is_bold' => false,
            'icon' => 'fas fa-history',
        ];

        if ($finalPeriodTax > 0) {
            $rows[] = [
                'component' => 'PPh 21 Masa '.$periodName.' (Kurang Bayar)',
                'calc' => 'Pajak Setahun – Kredit Pajak Masa Sebelumnya',
                'amount' => 'Rp'.number_format($finalPeriodTax, 0, ',', '.'),
                'is_bold' => true,
                'icon' => 'fas fa-receipt',
            ];
        } elseif ($finalPeriodTax < 0) {
            $rows[] = [
                'component' => 'PPh 21 Masa '.$periodName.' (Lebih Bayar)',
                'calc' => 'Pajak masa sebelumnya melebihi pajak setahun (Dikembalikan ke gaji)',
                'amount' => '+Rp'.number_format(abs($finalPeriodTax), 0, ',', '.'),
                'is_bold' => true,
                'icon' => 'fas fa-hand-holding-usd',
            ];
        } else {
            $rows[] = [
                'component' => 'PPh 21 Masa '.$periodName.' (Nihil)',
                'calc' => 'Pajak setahun telah lunas sesuai pemotongan masa sebelumnya',
                'amount' => 'Rp0',
                'is_bold' => true,
                'icon' => 'fas fa-check-circle',
            ];
        }

        $statusLabel = $finalPeriodTax > 0
            ? '<span class="text-danger font-weight-600">Kurang Bayar sebesar Rp'.number_format($finalPeriodTax, 0, ',', '.').'</span> (dipotong dari slip gaji '.$periodName.')'
            : ($finalPeriodTax < 0
                ? '<span class="text-success font-weight-600">Lebih Bayar sebesar Rp'.number_format(abs($finalPeriodTax), 0, ',', '.').'</span> (dikembalikan ke karyawan pada bulan '.$periodName.')'
                : '<span class="text-primary font-weight-600">Nihil (Rp0)</span> (seluruh pajak setahun telah terlunasi secara pas)');

        $summary = 'Sesuai <strong>PP No. 58/2023</strong> dan <strong>PMK No. 168/2023 Pasal 13</strong>, pada masa pajak terakhir ('.$periodName.') PPh 21 dihitung dengan <strong>Tarif Progresif Pasal 17</strong> atas penghasilan selama bekerja pada tahun ini:<br><br>'
            .'1. <strong>Bruto:</strong> Rp'.number_format($recon['annual_gross'], 0, ',', '.').' (Masa sebelumnya Rp'.number_format($recon['gross_before_final_period'], 0, ',', '.').' + Masa '.$periodName.' Rp'.number_format($recon['final_period_gross'], 0, ',', '.').')<br>'
            .'2. <strong>Penghasilan Neto Setahun:</strong> Rp'.number_format($recon['net_income'], 0, ',', '.').' (setelah Biaya Jabatan Rp'.number_format($recon['biaya_jabatan'], 0, ',', '.').(($recon['pension_deduction'] ?? 0) > 0 ? ' dan Iuran BPJS Rp'.number_format($recon['pension_deduction'], 0, ',', '.') : '').')<br>'
            .'3. <strong>Penghasilan Kena Pajak (PKP):</strong> Rp'.number_format($recon['pkp'], 0, ',', '.').' (Neto dikurangi PTKP '.$ptkpStatus.' Rp'.number_format($recon['ptkp'], 0, ',', '.').')<br>'
            .'4. <strong>Total PPh 21 Setahun:</strong> Rp'.number_format($totalAnnualTax, 0, ',', '.').'<br>'
            .'5. <strong>PPh 21 Telah Dipotong:</strong> Rp'.number_format($previousTaxPaid, 0, ',', '.').'<br>'
            .'&rarr; <strong>PPh 21 Masa '.$periodName.':</strong> '.$statusLabel.'.';

        if ($isNonNpwp) {
            $summary .= '<div class="mt-2 text-warning" style="font-size: 11.5px; border-top: 1px dashed #f59e0b; padding-top: 6px;"><i class="fas fa-exclamation-triangle mr-1"></i> <strong>Catatan Non-NPWP:</strong> Karyawan belum memiliki NPWP/NIK valid sehingga dikenakan tarif 20% lebih tinggi pada perhitungan pajak progresif tahunan.</div>';
        }

        $title = $mode === 'pph21'
            ? 'Rincian Perhitungan PPh 21 (Masa '.$periodName.')'
            : 'Dasar Skema Tarif PPh 21 (Masa '.$periodName.' - UU HPP)';

        return [
            'title' => $title,
            'rows' => $rows,
            'summary_text' => $summary,
        ];
    }

    public function showCalculationDetail(string $type): void
    {
        if (in_array($type, ['pph21', 'ter_scheme'], true) && $this->taxConfigurationWarning !== '') {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Perhitungan PPh 21 Belum Tersedia',
                'message' => $this->taxConfigurationWarning,
            ]);

            return;
        }

        $company = CompanyProfile::getProfile();
        $employee = $this->item->employee;
        $basicSalary = (float) ($this->item->basic_salary ?? 0);
        $totalAllowances = (float) $this->totalAllowances;
        $grossSalary = $basicSalary + $totalAllowances;
        $taxService = app(TaxCalculationService::class);

        $kesCap = (float) ($company->bpjs_kes_cap ?? 12_000_000.0);
        $jpCap = (float) ($company->bpjs_jp_cap ?? 10_042_300.0);
        $kesBaseWage = min($grossSalary, $kesCap);
        $jpBaseWage = min($grossSalary, $jpCap);

        $empKesRate = (float) ($company->bpjs_kes_emp_rate ?? 0.0100);
        $empJhtRate = (float) ($company->bpjs_jht_emp_rate ?? 0.0200);
        $empJpRate = (float) ($company->bpjs_jp_emp_rate ?? 0.0100);

        $compKesRate = (float) ($company->bpjs_kes_comp_rate ?? 0.0400);
        $compJhtRate = (float) ($company->bpjs_jht_comp_rate ?? 0.0370);
        $compJpRate = (float) ($company->bpjs_jp_comp_rate ?? 0.0200);
        $compJkkRate = (float) ($company->bpjs_jkk_risk_rate ?? 0.0024);
        $compJkmRate = (float) ($company->bpjs_jkm_rate ?? 0.0030);

        $bpjsBreakdown = $taxService->calculateBpjsBreakdown($grossSalary, $company);
        $officialPdfUrl = 'https://pajak.go.id/sites/default/files/2024-02/PMK%20168%20Tahun%202023%20Tentang%20PPh%20Pasal%2021%20TER.pdf';

        switch ($type) {
            case 'ter_scheme':
                $taxResult = $taxService->calculateMonthlyTax(
                    $employee,
                    $basicSalary,
                    $this->allowanceRows,
                    $company,
                    $this->period->month ?? 1,
                    $this->period->year ?? 2026,
                    true,
                    (bool) $this->item->is_final_tax_period,
                    $this->currentPensionDeductionFromRows()
                );

                $isFinalTaxPeriod = ! empty($taxResult['is_final_tax_period']);
                $ptkpStatus = $taxResult['ptkp_status'] ?? ($employee?->detail?->ptkp_status instanceof PtkpStatus ? $employee->detail->ptkp_status->value : ($employee?->detail?->ptkp_status ?? 'TK/0'));
                if ($ptkpStatus instanceof PtkpStatus) {
                    $ptkpStatus = $ptkpStatus->value;
                }
                $ptkpEnum = PtkpStatus::tryFrom((string) $ptkpStatus) ?? PtkpStatus::TK_0;
                $terCategory = $taxResult['ter_category'] ?? $ptkpEnum->terCategory();

                $taxableGross = (float) ($taxResult['taxable_gross'] ?? $grossSalary);
                $taxableInsurance = (float) ($taxResult['bpjs_taxable_insurance'] ?? 0);
                $taxAllowance = (float) ($taxResult['tax_allowance'] ?? 0);
                $baseTerRate = (float) ($taxResult['base_ter_rate'] ?? 0);
                $isNonNpwp = ! empty($taxResult['is_non_npwp_penalized']);
                $finalRate = $isNonNpwp ? round($baseTerRate * 1.2, 6) : $baseTerRate;

                if ($isFinalTaxPeriod) {
                    $this->calculationDetail = $this->buildFinalTaxCalculationDetail(
                        $taxResult,
                        $grossSalary,
                        $ptkpStatus,
                        $ptkpEnum,
                        $employee,
                        'ter_scheme'
                    );
                    break;
                }

                $tierInfo = $taxService->getTerTierInfo($terCategory, $taxableGross);
                $tierInfoBase = $taxService->getTerTierInfo($terCategory, $grossSalary);

                $rows = [
                    [
                        'component' => 'Status PTKP & Kategori TER',
                        'calc' => 'PTKP '.$ptkpStatus.' ('.$ptkpEnum->description().')',
                        'amount' => 'TER '.$terCategory,
                        'is_bold' => false,
                    ],
                    [
                        'component' => 'Gaji kotor (penerimaan)',
                        'calc' => 'Gaji Pokok Rp'.number_format($basicSalary, 0, ',', '.').($totalAllowances > 0 ? ' + Tunj. Rp'.number_format($totalAllowances, 0, ',', '.') : ''),
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                ];

                if ($taxAllowance > 0) {
                    $rows[] = [
                        'component' => 'Tunjangan PPh 21 (Gross-Up)',
                        'calc' => 'Ditanggung kantor',
                        'amount' => '+Rp'.number_format($taxAllowance, 0, ',', '.'),
                        'is_bold' => false,
                    ];
                }

                if ($taxableInsurance > 0) {
                    $compKesVal = (float) ($bpjsBreakdown['company']['bpjs_kes'] ?? 0);
                    $compJkkVal = (float) ($bpjsBreakdown['company']['bpjs_jkk'] ?? 0);
                    $compJkmVal = (float) ($bpjsBreakdown['company']['bpjs_jkm'] ?? 0);
                    $rows[] = [
                        'component' => 'Premi asuransi kantor (BPJS)',
                        'calc' => 'Kes Rp'.number_format($compKesVal, 0, ',', '.').' + JKK Rp'.number_format($compJkkVal, 0, ',', '.').' + JKM Rp'.number_format($compJkmVal, 0, ',', '.'),
                        'amount' => '+Rp'.number_format($taxableInsurance, 0, ',', '.'),
                        'is_bold' => false,
                    ];
                }

                $rows[] = [
                    'component' => 'Penghasilan bruto objek TER',
                    'calc' => $taxableInsurance > 0 ? 'Gaji Kotor + Asuransi Kantor' : 'Gaji Kotor',
                    'amount' => 'Rp'.number_format($taxableGross, 0, ',', '.'),
                    'is_bold' => true,
                ];

                $rows[] = [
                    'component' => 'Lapisan TER '.$terCategory.' (Tier '.$tierInfo['tier_number'].' dari '.$tierInfo['total_tiers'].')',
                    'calc' => $tierInfo['range_label'],
                    'amount' => number_format($tierInfo['rate'] * 100, 2, ',', '.').'%',
                    'is_bold' => true,
                ];

                if ($isNonNpwp) {
                    $rows[] = [
                        'component' => 'Penyesuaian Non-NPWP (+20%)',
                        'calc' => number_format($tierInfo['rate'] * 100, 2, ',', '.').'% × 120%',
                        'amount' => number_format($finalRate * 100, 2, ',', '.').'%',
                        'is_bold' => true,
                    ];
                }

                $pmkPdfUrl = 'https://pajak.go.id/sites/default/files/2024-02/PMK%20168%20Tahun%202023%20Tentang%20PPh%20Pasal%2021%20TER.pdf';
                $pmkLink = '<a href="'.$pmkPdfUrl.'" target="_blank" rel="noopener noreferrer" class="text-primary font-weight-600" style="text-decoration: underline;">PMK No. 168/2023</a>';

                if ($tierInfo['tier_number'] !== $tierInfoBase['tier_number'] && $taxableInsurance > 0) {
                    $summary = 'Sesuai <strong>PP No. 58/2023</strong> dan '.$pmkLink.', tarif TER bulanan ditentukan berdasarkan kategori status PTKP (<strong>'.$ptkpStatus.' &rarr; TER '.$terCategory.'</strong>) dan penghasilan bruto objek pajak.<br><br>'
                        .'<strong>Mengapa tarif menjadi '.number_format($tierInfo['rate'] * 100, 2, ',', '.').'% (Tier '.$tierInfo['tier_number'].')?</strong><br>'
                        .'Jika hanya melihat gaji kotor karyawan (Rp'.number_format($grossSalary, 0, ',', '.').'), lapisan tarif berada di <strong>Tier '.$tierInfoBase['tier_number'].'</strong> ('.$tierInfoBase['range_label'].') dengan tarif '.number_format($tierInfoBase['rate'] * 100, 2, ',', '.').'%.<br>'
                        .'Namun peraturan perpajakan mewajibkan <strong>premi asuransi yang dibayar pemberi kerja</strong> (BPJS Kesehatan 4%, JKK, JKM senilai total <strong>+Rp'.number_format($taxableInsurance, 0, ',', '.').'</strong>) ditambahkan ke penghasilan bruto objek pajak menjadi <strong>Rp'.number_format($taxableGross, 0, ',', '.').'</strong>.<br>'
                        .'Karena nominal tersebut melewati batas atas Tier '.$tierInfoBase['tier_number'].' (Rp'.number_format($tierInfoBase['max'], 0, ',', '.').'), maka tarif berpindah ke <strong>Tier '.$tierInfo['tier_number'].' ('.$tierInfo['range_label'].')</strong> dengan tarif <strong>'.number_format($tierInfo['rate'] * 100, 2, ',', '.').'%</strong>.';
                } else {
                    $summary = 'Sesuai <strong>PP No. 58/2023</strong> dan '.$pmkLink.', karyawan dengan status PTKP <strong>'.$ptkpStatus.' ('.$ptkpEnum->description().')</strong> dikelompokkan ke dalam <strong>TER Kategori '.$terCategory.'</strong>.<br><br>'
                        .'Dengan penghasilan bruto objek TER sebesar <strong>Rp'.number_format($taxableGross, 0, ',', '.').'</strong>, karyawan berada pada lapisan <strong>Tier '.$tierInfo['tier_number'].'</strong> ('.$tierInfo['range_label'].') dengan tarif <strong>'.number_format($tierInfo['rate'] * 100, 2, ',', '.').'%</strong>.';
                }

                if ($isNonNpwp) {
                    $summary .= '<div class="mt-2 text-warning" style="font-size: 11.5px; border-top: 1px dashed #f59e0b; padding-top: 6px;"><i class="fas fa-exclamation-triangle mr-1"></i> <strong>Catatan Non-NPWP:</strong> Karyawan belum memiliki NPWP yang valid sehingga dikenakan tarif 20% lebih tinggi menjadi <strong>'.number_format($finalRate * 100, 2, ',', '.').'%</strong>.</div>';
                }

                $this->calculationDetail = [
                    'title' => 'Dasar Penentuan Skema Tarif (PMK 168/2023)',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            case 'pph21':
                $taxResult = $taxService->calculateMonthlyTax(
                    $employee,
                    $basicSalary,
                    $this->allowanceRows,
                    $company,
                    $this->period->month ?? 1,
                    $this->period->year ?? 2026,
                    true,
                    (bool) $this->item->is_final_tax_period,
                    $this->currentPensionDeductionFromRows()
                );

                $isFinalTaxPeriod = ! empty($taxResult['is_final_tax_period']);
                if ($isFinalTaxPeriod) {
                    $ptkpStatus = $taxResult['ptkp_status'] ?? ($employee?->detail?->ptkp_status instanceof PtkpStatus ? $employee->detail->ptkp_status->value : ($employee?->detail?->ptkp_status ?? 'TK/0'));
                    if ($ptkpStatus instanceof PtkpStatus) {
                        $ptkpStatus = $ptkpStatus->value;
                    }
                    $ptkpEnum = PtkpStatus::tryFrom((string) $ptkpStatus) ?? PtkpStatus::TK_0;

                    $this->calculationDetail = $this->buildFinalTaxCalculationDetail(
                        $taxResult,
                        $grossSalary,
                        $ptkpStatus,
                        $ptkpEnum,
                        $employee,
                        'pph21'
                    );
                    break;
                }

                $taxableGross = (float) ($taxResult['taxable_gross'] ?? $grossSalary);
                $terCategory = $taxResult['ter_category'] ?? 'A';
                $baseTerRate = (float) ($taxResult['base_ter_rate'] ?? 0);
                $isNonNpwp = ! empty($taxResult['is_non_npwp_penalized']);
                $taxNormal = round($taxableGross * $baseTerRate);
                $taxFinal = (float) ($taxResult['pph21_amount'] ?? 0);
                $netAfterTax = max(0, $grossSalary - $taxFinal);
                $taxableInsurance = (float) ($taxResult['bpjs_taxable_insurance'] ?? 0);
                $taxAllowance = (float) ($taxResult['tax_allowance'] ?? 0);

                $rows = [];
                if ($taxableInsurance > 0 || $taxAllowance > 0) {
                    $rows[] = [
                        'component' => 'Gaji kotor (penerimaan)',
                        'calc' => 'Gaji Pokok + Tunjangan',
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ];

                    if ($taxAllowance > 0) {
                        $rows[] = [
                            'component' => 'Tunjangan PPh 21 (Gross-Up)',
                            'calc' => 'Ditanggung kantor',
                            'amount' => '+Rp'.number_format($taxAllowance, 0, ',', '.'),
                            'is_bold' => false,
                        ];
                    }

                    if ($taxableInsurance > 0) {
                        $compKesVal = (float) ($bpjsBreakdown['company']['bpjs_kes'] ?? 0);
                        $compJkkVal = (float) ($bpjsBreakdown['company']['bpjs_jkk'] ?? 0);
                        $compJkmVal = (float) ($bpjsBreakdown['company']['bpjs_jkm'] ?? 0);
                        $rows[] = [
                            'component' => 'Premi asuransi kantor (BPJS)',
                            'calc' => 'Kes Rp'.number_format($compKesVal, 0, ',', '.').' + JKK Rp'.number_format($compJkkVal, 0, ',', '.').' + JKM Rp'.number_format($compJkmVal, 0, ',', '.'),
                            'amount' => '+Rp'.number_format($taxableInsurance, 0, ',', '.'),
                            'is_bold' => false,
                        ];
                    }

                    $rows[] = [
                        'component' => 'Penghasilan bruto kena PPh 21',
                        'calc' => 'Dasar tarif TER',
                        'amount' => 'Rp'.number_format($taxableGross, 0, ',', '.'),
                        'is_bold' => true,
                    ];
                } else {
                    $rows[] = [
                        'component' => 'Penghasilan bruto kena PPh 21',
                        'calc' => '—',
                        'amount' => 'Rp'.number_format($taxableGross, 0, ',', '.'),
                        'is_bold' => false,
                    ];
                }

                if (! empty($taxResult['is_final_tax_period'])) {
                    $rows[] = [
                        'component' => 'Skema Pajak Akhir Tahun',
                        'calc' => 'Pasal 17 UU HPP',
                        'amount' => 'Tarif Progresif',
                        'is_bold' => false,
                    ];
                } else {
                    $tierInfo = $taxService->getTerTierInfo($terCategory, $taxableGross);
                    $rows[] = [
                        'component' => 'TER Kategori '.$terCategory.' (Tier '.$tierInfo['tier_number'].')',
                        'calc' => $tierInfo['range_label'],
                        'amount' => number_format($baseTerRate * 100, 2, ',', '.').'%',
                        'is_bold' => false,
                    ];
                }

                $rows[] = [
                    'component' => $isNonNpwp ? 'PPh 21 normal' : 'PPh 21 bulan ini',
                    'calc' => 'Rp'.number_format($taxableGross, 0, ',', '.').' × '.number_format($baseTerRate * 100, 2, ',', '.').'%',
                    'amount' => 'Rp'.number_format($taxNormal, 0, ',', '.'),
                    'is_bold' => ! $isNonNpwp,
                ];

                if ($isNonNpwp) {
                    $rows[] = [
                        'component' => 'Penyesuaian non-NPWP (+20%)',
                        'calc' => 'Rp'.number_format($taxNormal, 0, ',', '.').' × 120%',
                        'amount' => 'Rp'.number_format($taxFinal, 0, ',', '.'),
                        'is_bold' => true,
                    ];
                }

                $rows[] = [
                    'component' => 'Gaji setelah PPh 21',
                    'calc' => 'Rp'.number_format($grossSalary, 0, ',', '.').' – Rp'.number_format($taxFinal, 0, ',', '.'),
                    'amount' => 'Rp'.number_format($netAfterTax, 0, ',', '.'),
                    'is_bold' => false,
                ];

                $summary = 'Jadi, PPh 21 bulan ini adalah <strong>Rp'.number_format($taxFinal, 0, ',', '.').'</strong> dan perkiraan gaji diterima sebesar <strong>Rp'.number_format($netAfterTax, 0, ',', '.').'</strong>, sebelum potongan BPJS atau potongan lainnya.';
                if ($taxableInsurance > 0) {
                    $summary .= '<div style="margin-top: 8px; font-size: 11.5px; border-top: 1px dashed #cbd5e1; padding-top: 6px; color: #475569;"><i class="fas fa-info-circle text-primary mr-1"></i> <strong>Mengapa Gaji Bruto Pajak Rp'.number_format($taxableGross, 0, ',', '.').'?</strong> Sesuai ketentuan perpajakan (<a href="'.$officialPdfUrl.'" target="_blank" rel="noopener noreferrer" class="text-primary font-weight-600" style="text-decoration: underline;">PMK 168/2023</a>), premi asuransi yang dibayarkan perusahaan (BPJS Kesehatan 4%, JKK 0,24%, JKM 0,3% senilai total <strong>+Rp'.number_format($taxableInsurance, 0, ',', '.').'</strong>) diperhitungkan sebagai penambah penghasilan bruto objek PPh 21.</div>';
                }

                $this->calculationDetail = [
                    'title' => 'Rincian Perhitungan PPh 21',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            case 'bpjs_kes_emp':
                $empKes = (float) ($bpjsBreakdown['employee']['bpjs_kes'] ?? 0);
                $netAfter = max(0, $grossSalary - $empKes);

                $rows = [
                    [
                        'component' => 'Gaji kotor (bruto)',
                        'calc' => '—',
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                ];

                if ($grossSalary > $kesCap) {
                    $rows[] = [
                        'component' => 'Batas maksimal upah (cap)',
                        'calc' => 'Maks. Rp'.number_format($kesCap, 0, ',', '.'),
                        'amount' => '—',
                        'is_bold' => false,
                    ];
                    $rows[] = [
                        'component' => 'Dasar perhitungan upah',
                        'calc' => 'Dipatok pada batas atas',
                        'amount' => 'Rp'.number_format($kesBaseWage, 0, ',', '.'),
                        'is_bold' => false,
                    ];
                }

                $rows[] = [
                    'component' => 'Tarif iuran karyawan',
                    'calc' => number_format($empKesRate * 100, 1, ',', '.').'%',
                    'amount' => '—',
                    'is_bold' => false,
                ];
                $rows[] = [
                    'component' => 'Potongan BPJS Kesehatan',
                    'calc' => 'Rp'.number_format($kesBaseWage, 0, ',', '.').' × '.number_format($empKesRate * 100, 1, ',', '.').'%',
                    'amount' => 'Rp'.number_format($empKes, 0, ',', '.'),
                    'is_bold' => true,
                ];
                $rows[] = [
                    'component' => 'Gaji setelah BPJS Kes',
                    'calc' => 'Rp'.number_format($grossSalary, 0, ',', '.').' – Rp'.number_format($empKes, 0, ',', '.'),
                    'amount' => 'Rp'.number_format($netAfter, 0, ',', '.'),
                    'is_bold' => false,
                ];

                $summary = 'Jadi, potongan BPJS Kesehatan bulan ini adalah <strong>Rp'.number_format($empKes, 0, ',', '.').'</strong> dan perkiraan gaji diterima sebesar <strong>Rp'.number_format($netAfter, 0, ',', '.').'</strong>, sebelum potongan lainnya.';

                $this->calculationDetail = [
                    'title' => 'Rincian Perhitungan BPJS Kesehatan',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            case 'bpjs_jht_emp':
                $empJht = (float) ($bpjsBreakdown['employee']['bpjs_jht'] ?? 0);
                $netAfter = max(0, $grossSalary - $empJht);

                $rows = [
                    [
                        'component' => 'Gaji kotor (bruto)',
                        'calc' => '—',
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                    [
                        'component' => 'Tarif iuran JHT',
                        'calc' => number_format($empJhtRate * 100, 1, ',', '.').'%',
                        'amount' => '—',
                        'is_bold' => false,
                    ],
                    [
                        'component' => 'Potongan BPJS JHT',
                        'calc' => 'Rp'.number_format($grossSalary, 0, ',', '.').' × '.number_format($empJhtRate * 100, 1, ',', '.').'%',
                        'amount' => 'Rp'.number_format($empJht, 0, ',', '.'),
                        'is_bold' => true,
                    ],
                    [
                        'component' => 'Gaji setelah BPJS JHT',
                        'calc' => 'Rp'.number_format($grossSalary, 0, ',', '.').' – Rp'.number_format($empJht, 0, ',', '.'),
                        'amount' => 'Rp'.number_format($netAfter, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                ];

                $summary = 'Jadi, potongan BPJS JHT bulan ini adalah <strong>Rp'.number_format($empJht, 0, ',', '.').'</strong> dan perkiraan gaji diterima sebesar <strong>Rp'.number_format($netAfter, 0, ',', '.').'</strong>, sebelum potongan lainnya.';

                $this->calculationDetail = [
                    'title' => 'Rincian Perhitungan BPJS JHT',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            case 'bpjs_jp_emp':
                $empJp = (float) ($bpjsBreakdown['employee']['bpjs_jp'] ?? 0);
                $netAfter = max(0, $grossSalary - $empJp);

                $rows = [
                    [
                        'component' => 'Gaji kotor (bruto)',
                        'calc' => '—',
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                ];

                if ($grossSalary > $jpCap) {
                    $rows[] = [
                        'component' => 'Batas maksimal upah (cap)',
                        'calc' => 'Maks. Rp'.number_format($jpCap, 0, ',', '.'),
                        'amount' => '—',
                        'is_bold' => false,
                    ];
                    $rows[] = [
                        'component' => 'Dasar perhitungan upah',
                        'calc' => 'Dipatok pada batas atas',
                        'amount' => 'Rp'.number_format($jpBaseWage, 0, ',', '.'),
                        'is_bold' => false,
                    ];
                }

                $rows[] = [
                    'component' => 'Tarif iuran pensiun',
                    'calc' => number_format($empJpRate * 100, 1, ',', '.').'%',
                    'amount' => '—',
                    'is_bold' => false,
                ];
                $rows[] = [
                    'component' => 'Potongan BPJS Pensiun',
                    'calc' => 'Rp'.number_format($jpBaseWage, 0, ',', '.').' × '.number_format($empJpRate * 100, 1, ',', '.').'%',
                    'amount' => 'Rp'.number_format($empJp, 0, ',', '.'),
                    'is_bold' => true,
                ];
                $rows[] = [
                    'component' => 'Gaji setelah BPJS JP',
                    'calc' => 'Rp'.number_format($grossSalary, 0, ',', '.').' – Rp'.number_format($empJp, 0, ',', '.'),
                    'amount' => 'Rp'.number_format($netAfter, 0, ',', '.'),
                    'is_bold' => false,
                ];

                $summary = 'Jadi, potongan BPJS Pensiun bulan ini adalah <strong>Rp'.number_format($empJp, 0, ',', '.').'</strong> dan perkiraan gaji diterima sebesar <strong>Rp'.number_format($netAfter, 0, ',', '.').'</strong>, sebelum potongan lainnya.';

                $this->calculationDetail = [
                    'title' => 'Rincian Perhitungan BPJS Pensiun',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            case 'company_bpjs_kes':
                $compKes = (float) ($bpjsBreakdown['company']['bpjs_kes'] ?? 0);

                $rows = [
                    [
                        'component' => 'Gaji kotor (bruto)',
                        'calc' => '—',
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                ];

                if ($grossSalary > $kesCap) {
                    $rows[] = [
                        'component' => 'Batas maksimal upah (cap)',
                        'calc' => 'Maks. Rp'.number_format($kesCap, 0, ',', '.'),
                        'amount' => '—',
                        'is_bold' => false,
                    ];
                    $rows[] = [
                        'component' => 'Dasar perhitungan upah',
                        'calc' => 'Dipatok pada batas atas',
                        'amount' => 'Rp'.number_format($kesBaseWage, 0, ',', '.'),
                        'is_bold' => false,
                    ];
                }

                $rows[] = [
                    'component' => 'Tarif tanggungan kantor',
                    'calc' => number_format($compKesRate * 100, 1, ',', '.').'%',
                    'amount' => '—',
                    'is_bold' => false,
                ];
                $rows[] = [
                    'component' => 'Tanggungan BPJS Kesehatan',
                    'calc' => 'Rp'.number_format($kesBaseWage, 0, ',', '.').' × '.number_format($compKesRate * 100, 1, ',', '.').'%',
                    'amount' => 'Rp'.number_format($compKes, 0, ',', '.'),
                    'is_bold' => true,
                ];

                $summary = 'Jadi, BPJS Kesehatan tanggungan perusahaan sebesar <strong>Rp'.number_format($compKes, 0, ',', '.').'</strong> (ditanggung oleh kantor dan tidak memotong gaji karyawan).';

                $this->calculationDetail = [
                    'title' => 'Rincian BPJS Kesehatan (Perusahaan)',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            case 'company_bpjs_jht':
                $compJht = (float) ($bpjsBreakdown['company']['bpjs_jht'] ?? 0);

                $rows = [
                    [
                        'component' => 'Gaji kotor (bruto)',
                        'calc' => '—',
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                    [
                        'component' => 'Tarif tanggungan kantor',
                        'calc' => number_format($compJhtRate * 100, 1, ',', '.').'%',
                        'amount' => '—',
                        'is_bold' => false,
                    ],
                    [
                        'component' => 'Tanggungan BPJS JHT',
                        'calc' => 'Rp'.number_format($grossSalary, 0, ',', '.').' × '.number_format($compJhtRate * 100, 1, ',', '.').'%',
                        'amount' => 'Rp'.number_format($compJht, 0, ',', '.'),
                        'is_bold' => true,
                    ],
                ];

                $summary = 'Jadi, BPJS JHT tanggungan perusahaan sebesar <strong>Rp'.number_format($compJht, 0, ',', '.').'</strong> (ditanggung oleh kantor dan tidak memotong gaji karyawan).';

                $this->calculationDetail = [
                    'title' => 'Rincian BPJS JHT (Perusahaan)',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            case 'company_bpjs_jp':
                $compJp = (float) ($bpjsBreakdown['company']['bpjs_jp'] ?? 0);

                $rows = [
                    [
                        'component' => 'Gaji kotor (bruto)',
                        'calc' => '—',
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                ];

                if ($grossSalary > $jpCap) {
                    $rows[] = [
                        'component' => 'Batas maksimal upah (cap)',
                        'calc' => 'Maks. Rp'.number_format($jpCap, 0, ',', '.'),
                        'amount' => '—',
                        'is_bold' => false,
                    ];
                    $rows[] = [
                        'component' => 'Dasar perhitungan upah',
                        'calc' => 'Dipatok pada batas atas',
                        'amount' => 'Rp'.number_format($jpBaseWage, 0, ',', '.'),
                        'is_bold' => false,
                    ];
                }

                $rows[] = [
                    'component' => 'Tarif tanggungan kantor',
                    'calc' => number_format($compJpRate * 100, 1, ',', '.').'%',
                    'amount' => '—',
                    'is_bold' => false,
                ];
                $rows[] = [
                    'component' => 'Tanggungan BPJS JP',
                    'calc' => 'Rp'.number_format($jpBaseWage, 0, ',', '.').' × '.number_format($compJpRate * 100, 1, ',', '.').'%',
                    'amount' => 'Rp'.number_format($compJp, 0, ',', '.'),
                    'is_bold' => true,
                ];

                $summary = 'Jadi, BPJS JP tanggungan perusahaan sebesar <strong>Rp'.number_format($compJp, 0, ',', '.').'</strong> (ditanggung oleh kantor dan tidak memotong gaji karyawan).';

                $this->calculationDetail = [
                    'title' => 'Rincian BPJS JP (Perusahaan)',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            case 'company_bpjs_jkk':
                $compJkk = (float) ($bpjsBreakdown['company']['bpjs_jkk'] ?? 0);
                $ratePct = round($compJkkRate * 100, 3);

                $rows = [
                    [
                        'component' => 'Gaji kotor (bruto)',
                        'calc' => '—',
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                    [
                        'component' => 'Tarif risiko JKK kantor',
                        'calc' => number_format($ratePct, 2, ',', '.').'%',
                        'amount' => '—',
                        'is_bold' => false,
                    ],
                    [
                        'component' => 'Tanggungan BPJS JKK',
                        'calc' => 'Rp'.number_format($grossSalary, 0, ',', '.').' × '.number_format($ratePct, 2, ',', '.').'%',
                        'amount' => 'Rp'.number_format($compJkk, 0, ',', '.'),
                        'is_bold' => true,
                    ],
                ];

                $summary = 'Jadi, BPJS JKK tanggungan perusahaan sebesar <strong>Rp'.number_format($compJkk, 0, ',', '.').'</strong> (ditanggung oleh kantor dan tidak memotong gaji karyawan).';

                $this->calculationDetail = [
                    'title' => 'Rincian BPJS JKK (Perusahaan)',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            case 'company_bpjs_jkm':
                $compJkm = (float) ($bpjsBreakdown['company']['bpjs_jkm'] ?? 0);
                $ratePct = number_format($compJkmRate * 100, 2, ',', '.');

                $rows = [
                    [
                        'component' => 'Gaji kotor (bruto)',
                        'calc' => '—',
                        'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                        'is_bold' => false,
                    ],
                    [
                        'component' => 'Tarif tanggungan JKM',
                        'calc' => $ratePct.'%',
                        'amount' => '—',
                        'is_bold' => false,
                    ],
                    [
                        'component' => 'Tanggungan BPJS JKM',
                        'calc' => 'Rp'.number_format($grossSalary, 0, ',', '.').' × '.$ratePct.'%',
                        'amount' => 'Rp'.number_format($compJkm, 0, ',', '.'),
                        'is_bold' => true,
                    ],
                ];

                $summary = 'Jadi, BPJS JKM tanggungan perusahaan sebesar <strong>Rp'.number_format($compJkm, 0, ',', '.').'</strong> (ditanggung oleh kantor dan tidak memotong gaji karyawan).';

                $this->calculationDetail = [
                    'title' => 'Rincian BPJS JKM (Perusahaan)',
                    'rows' => $rows,
                    'summary_text' => $summary,
                ];
                break;

            default:
                $this->calculationDetail = null;

                return;
        }

        if (! empty($this->calculationDetail['rows'])) {
            foreach ($this->calculationDetail['rows'] as &$r) {
                if (empty($r['icon'])) {
                    $c = strtolower((string) ($r['component'] ?? ''));
                    if (str_contains($c, 'ptkp') || str_contains($c, 'kategori') || str_contains($c, 'ter') || str_contains($c, 'lapisan')) {
                        $r['icon'] = 'fas fa-percentage';
                    } elseif (str_contains($c, 'premi') || str_contains($c, 'asuransi') || str_contains($c, 'kantor') || str_contains($c, 'tanggungan') || str_contains($c, 'jht') || str_contains($c, 'jkk') || str_contains($c, 'jkm')) {
                        $r['icon'] = 'fas fa-shield-alt';
                    } elseif (str_contains($c, 'pajak') || str_contains($c, 'pph') || str_contains($c, 'npwp') || str_contains($c, 'penyesuaian')) {
                        $r['icon'] = 'fas fa-calculator';
                    } elseif (str_contains($c, 'bpjs') || str_contains($c, 'iuran') || str_contains($c, 'kes')) {
                        $r['icon'] = 'fas fa-heartbeat';
                    } elseif (str_contains($c, 'gaji') || str_contains($c, 'bruto') || str_contains($c, 'pokok') || str_contains($c, 'upah')) {
                        $r['icon'] = 'fas fa-wallet';
                    } else {
                        $r['icon'] = 'fas fa-receipt';
                    }
                }
            }
            unset($r);
        }

        $this->dispatch('open-modal', ['id' => 'calcDetailModal']);
    }

    /**
     * Persist payroll item allowances and deductions, skipping incomplete rows.
     */
    public function persistItemLines(?PayrollService $service = null): bool
    {
        if (! $this->isEditable || ! $this->validateCashAdvanceDeductionRows()) {
            return false;
        }

        try {
            $this->persistCompanyBpjsStatus();

            // Strictly filter out incomplete/trailing empty allowance rows
            $validAllowances = collect($this->allowanceRows)
                ->filter(function ($row) {
                    $name = trim((string) ($row['name'] ?? ''));
                    $amount = (float) ($row['amount'] ?? 0);

                    return $name !== '' && $amount > 0;
                })
                ->values()
                ->all();

            // Strictly filter deductions: keep system rows (Tax, BPJS, Cash Advance), and only keep custom rows if both name and amount > 0
            $validDeductions = collect($this->deductionRows)
                ->map(function ($row) {
                    if (! empty($row['cash_advance_installment_id']) && isset($row['is_enabled']) && $row['is_enabled'] === false) {
                        $row['amount'] = 0;
                    }

                    return $row;
                })
                ->filter(function ($row) {
                    $name = trim((string) ($row['name'] ?? ''));
                    $cat = $row['category'] ?? '';

                    if (! empty($row['is_system'])
                        || $cat === 'tax'
                        || $cat === 'bpjs'
                        || $cat === 'cash_advance'
                        || ! empty($row['cash_advance_installment_id'])
                        || str_starts_with(strtolower($name), 'pph 21')
                        || str_starts_with(strtolower($name), 'bpjs')) {
                        return true;
                    }

                    $amount = (float) ($row['amount'] ?? 0);

                    return $name !== '' && $amount > 0;
                })
                ->values()
                ->all();

            $service = $service ?? app(PayrollService::class);
            $user = auth()->user() ?? $this->period->creator ?? User::first();
            if (! $user) {
                return false;
            }

            $this->item = $service->syncItemLines(
                $this->item,
                $validAllowances,
                $validDeductions,
                $user
            );

            $this->period->refresh();
            $this->reloadItem();
            $this->loadComponentRows();
            $this->clearComputedProperties();
            $this->lastSavedAt = now()->format('H:i:s');

            return true;
        } catch (Throwable $e) {
            Log::warning('Payroll persistItemLines error: '.$e->getMessage());
            throw $e;
        }
    }

    public function autoSaveItem(): void
    {
        $this->persistItemLines();
    }

    public function save(?PayrollService $service = null): void
    {
        if (! $this->isEditable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Dapat Diubah',
                'message' => $this->item->isApproved()
                    ? 'Slip gaji karyawan ini telah disetujui (ACC) oleh Direktur sehingga tidak dapat diubah kembali.'
                    : 'Status penggajian tidak dalam tahap draf atau revisi.',
            ]);

            return;
        }

        try {
            if (! $this->persistItemLines($service)) {
                $errors = $this->getErrorBag();
                $this->dispatch('swal:alert', [
                    'type' => 'error',
                    'title' => $errors->isNotEmpty() ? 'Periksa Data Komponen Gaji' : 'Gagal Menyimpan',
                    'message' => $errors->first() ?: 'Komponen gaji belum tersimpan. Silakan periksa kembali data dan coba lagi.',
                ]);

                return;
            }

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil Disimpan!',
                'message' => 'Komponen tunjangan dan potongan karyawan berhasil diperbarui.',
            ]);
        } catch (Throwable $e) {
            Log::error('Payroll save error: '.$e->getMessage());
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan',
                'message' => 'Terjadi kesalahan saat menyimpan komponen gaji: '.$e->getMessage(),
            ]);
        }
    }

    public function verifyItem(PayrollService $service): void
    {
        try {
            $service->verifyItem($this->item, auth()->user());
            $this->reloadItem();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Verifikasi Berhasil',
                'message' => "Slip gaji {$this->item->employee->full_name} berhasil ditandai sebagai Terverifikasi HRD.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memverifikasi',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function unverifyItem(PayrollService $service): void
    {
        try {
            $service->unverifyItem($this->item, auth()->user());
            $this->reloadItem();

            $this->dispatch('swal:alert', [
                'type' => 'info',
                'title' => 'Verifikasi Dibatalkan',
                'message' => "Status verifikasi slip gaji {$this->item->employee->full_name} dikembalikan ke Draf.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Membatalkan Verifikasi',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function approveItem(PayrollService $service): void
    {
        try {
            $service->approveItem($this->item, auth()->user());
            $this->reloadItem();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Slip Gaji Berhasil Disetujui!',
                'message' => "Slip gaji {$this->item->employee->full_name} berhasil disetujui (ACC).",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyetujui!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function unapproveItem(PayrollService $service): void
    {
        try {
            $service->unapproveItem($this->item, auth()->user());
            $this->reloadItem();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Persetujuan Dibatalkan',
                'message' => "Persetujuan (ACC) slip gaji {$this->item->employee->full_name} berhasil dibatalkan. Status dikembalikan ke Menunggu ACC.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Membatalkan Persetujuan!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function openRejectModal(): void
    {
        $this->itemRejectionReason = '';
        $this->dispatch('prompt-reject-item');
    }

    public function confirmRejectItem(?string $reason = null, ?PayrollService $service = null): void
    {
        $service = $service ?? app(PayrollService::class);

        if ($reason !== null) {
            $this->itemRejectionReason = trim($reason);
        }

        $this->validate([
            'itemRejectionReason' => 'required|string|min:3|max:500',
        ], [
            'itemRejectionReason.required' => 'Catatan revisi wajib diisi.',
            'itemRejectionReason.min' => 'Catatan revisi minimal 3 karakter.',
        ]);

        try {
            $service->rejectItem($this->item, $this->itemRejectionReason, auth()->user());
            $this->reloadItem();

            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Slip Gaji Dikembalikan untuk Revisi',
                'message' => "Slip gaji {$this->item->employee->full_name} berhasil ditolak untuk revisi dengan catatan: \"{$this->itemRejectionReason}\"",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function reloadItem(): void
    {
        $this->item->refresh()->load([
            'employee.department',
            'employee.position',
            'employee.detail',
            'lines.salaryComponent',
            'lines.cashAdvanceInstallment.cashAdvance',
            'updater',
            'approver',
        ]);
        $this->period->refresh()->load(['creator', 'updater', 'submitter', 'approver']);
    }

    protected function persistCompanyBpjsStatus(): void
    {
        $disabledCompanyKeys = array_keys(array_filter($this->companyBpjsStatus, fn ($v) => ! $v));
        $enabledCompanyKeys = array_keys(array_filter($this->companyBpjsStatus, fn ($v) => (bool) $v));
        $notesData = [];
        $textNote = null;
        if (! empty($this->item->notes)) {
            $trimmed = trim((string) $this->item->notes);
            if (str_starts_with($trimmed, '{')) {
                $notesData = json_decode($trimmed, true) ?: [];
                $textNote = $notesData['text_note'] ?? null;
            } else {
                $textNote = $trimmed;
            }
        }
        if ($textNote) {
            $notesData['text_note'] = $textNote;
        }
        $notesData['disabled_company_bpjs'] = $disabledCompanyKeys;
        $notesData['enabled_company_bpjs'] = $enabledCompanyKeys;
        $this->item->notes = json_encode($notesData);
        $this->item->saveQuietly();
    }

    public function render(): View
    {
        $this->item->loadMissing([
            'employee.department',
            'employee.position',
            'employee.detail',
            'lines.salaryComponent',
            'lines.cashAdvanceInstallment.cashAdvance',
            'updater',
            'approver',
            'verifier',
        ]);
        $this->period->loadMissing(['creator', 'updater', 'submitter', 'approver']);

        $company = CompanyProfile::getProfile();

        return view('livewire.payrolls.manage-item', [
            'company' => $company,
        ]);
    }
}
