<?php

namespace App\Livewire\Payrolls;

use App\Enums\CashAdvanceStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PayrollLineCategory;
use App\Enums\PayrollLineType;
use App\Enums\PayrollPeriodStatus;
use App\Enums\PtkpStatus;
use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentCategory;
use App\Enums\SalaryComponentType;
use App\Enums\TaxMethod;
use App\Models\CashAdvanceInstallment;
use App\Models\CompanyProfile;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Models\User;
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
        if ($this->period->status === PayrollPeriodStatus::PAID || $this->item->isApproved()) {
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
            'lines.cashAdvanceInstallment',
            'updater',
            'approver',
        ]);

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
                if ($needsSalarySync) {
                    $this->item->update([
                        'basic_salary' => $expectedBasic,
                        'bank_name' => $empDetail?->bank_name,
                        'bank_account_number' => $empDetail?->bank_account_number,
                        'bank_account_name' => $empDetail?->bank_account_name,
                    ]);
                }
                $service->recalculateItemTax($this->item);
                $service->recalculateItem($this->item);
                $service->recalculatePeriod($this->period);
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
                || $line->name === 'PPh 21');

            $amount = (float) $line->amount;

            $inst = null;
            // Synchronize cash advance installment with actual unpaid remaining balance
            if (! empty($line->cash_advance_installment_id)) {
                $inst = $line->relationLoaded('cashAdvanceInstallment')
                    ? $line->cashAdvanceInstallment
                    : CashAdvanceInstallment::find($line->cash_advance_installment_id);

                if ($inst) {
                    $unpaidInstallment = max(0.0, round((float) $inst->amount - (float) $inst->paid_amount, 2));
                    if ($this->isEditable && abs($amount - $unpaidInstallment) > 0.001) {
                        $amount = $unpaidInstallment;
                        $line->update(['amount' => $amount]);
                        app(PayrollService::class)->recalculateItem($this->item);
                        $this->item->refresh();
                    } else {
                        $amount = $unpaidInstallment;
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
            if ($isSystem && ($line->category === PayrollLineCategory::TAX || $line->category === PayrollLineCategory::BPJS || $line->name === 'PPh 21')) {
                if ($line->notes === 'Nonaktif' || ($amount == 0 && ! empty($line->notes) && str_contains($line->notes, 'Nonaktif'))) {
                    $isEnabled = false;
                }
            }

            $rowData = [
                'id' => $line->id,
                'name' => $line->name,
                'amount' => ($amount > 0 || ! empty($line->cash_advance_installment_id)) ? $amount : '',
                'calc_mode' => $calcMode,
                'percentage' => $pct,
                'salary_component_id' => $line->salary_component_id,
                'cash_advance_installment_id' => $line->cash_advance_installment_id,
                'installment_paid_amount' => (float) ($inst?->paid_amount ?? 0),
                'installment_total_amount' => (float) ($inst?->amount ?? 0),
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
                $hasPph = collect($this->deductionRows)->contains(fn ($r) => ($r['category'] ?? '') === 'tax' || strcasecmp(trim($r['name'] ?? ''), 'pph 21') === 0);
                if (! $hasPph) {
                    $compPph = SalaryComponent::where('code', 'PPH21')->first();
                    $pphDefault = (bool) $company->enable_pph21;
                    $this->deductionRows[] = [
                        'id' => null,
                        'name' => 'PPh 21',
                        'amount' => $pphDefault ? (float) ($this->item->pph21_amount ?? 0) : 0.0,
                        'calc_mode' => 'nominal',
                        'percentage' => '',
                        'salary_component_id' => $compPph?->id,
                        'cash_advance_installment_id' => null,
                        'category' => 'tax',
                        'is_system' => true,
                        'is_enabled' => $pphDefault,
                        'notes' => $pphDefault ? 'Potongan PPh 21 otomatis' : 'Nonaktif',
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

    public function updatedAllowanceRows(mixed $value, string $key): void
    {
        if (! $this->isEditable) {
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
                            $this->allowanceRows[$index]['amount'] = (float) $comp->default_value;
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

    public function updatedDeductionRows(mixed $value, string $key): void
    {
        if (! $this->isEditable) {
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
            $clean = preg_replace('/[^0-9]/', '', (string) $value);
            $this->deductionRows[$index]['amount'] = $clean !== '' ? (float) $clean : '';
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
        );
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
            ->with(['employee.position', 'employee.department', 'employee.detail'])
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
                    $this->modalAmount = (float) $comp->default_value;
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
        if ($this->item->employee?->detail && $basicSalary > 0) {
            $pphRowIndex = null;
            $isPphEnabled = (bool) $company->enable_pph21;
            foreach ($this->deductionRows as $idx => $row) {
                if (($row['category'] ?? '') === 'tax' || strcasecmp(trim($row['name'] ?? ''), 'pph 21') === 0) {
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
                $isPphEnabled
            );

            if ($pphRowIndex !== null) {
                $this->deductionRows[$pphRowIndex]['amount'] = $isPphEnabled ? (float) $taxResult['pph21_amount'] : 0.0;
                if (! empty($taxResult['notes']) && $isPphEnabled) {
                    $this->deductionRows[$pphRowIndex]['notes'] = $taxResult['notes'];
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
            $this->persistItemLines($payrollService);
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

    public function showCalculationDetail(string $type): void
    {
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
                    true
                );

                $isDecember = ((int) ($this->period->month ?? 1)) === 12;
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

                if ($isDecember) {
                    $rows = [
                        [
                            'component' => 'Skema Masa Pajak Terakhir',
                            'calc' => 'Bulan Desember',
                            'amount' => 'Pasal 17 UU HPP',
                            'is_bold' => true,
                        ],
                        [
                            'component' => 'Status PTKP Karyawan',
                            'calc' => $ptkpEnum->description(),
                            'amount' => $ptkpStatus,
                            'is_bold' => false,
                        ],
                        [
                            'component' => 'Gaji Kotor Bulan Ini',
                            'calc' => 'Gaji Pokok + Tunjangan',
                            'amount' => 'Rp'.number_format($grossSalary, 0, ',', '.'),
                            'is_bold' => false,
                        ],
                    ];

                    $summary = 'Pada bulan Desember (masa pajak terakhir), pemotongan PPh 21 tidak menggunakan skema TER bulanan melainkan menggunakan <strong>Tarif Progresif Pasal 17 ayat (1) huruf a UU HPP</strong> yang dihitung dari total penghasilan neto setahun dikurangi PTKP, kemudian dikurangi kredit PPh 21 yang telah dipotong pada masa Januari hingga November.';

                    $this->calculationDetail = [
                        'title' => 'Dasar Skema Tarif PPh 21 (Masa Desember)',
                        'rows' => $rows,
                        'summary_text' => $summary,
                    ];
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
                    true
                );

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

                if (! empty($taxResult['is_december'])) {
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
    public function persistItemLines(?PayrollService $service = null): void
    {
        if (! $this->isEditable) {
            return;
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
                ->filter(function ($row) {
                    $name = trim((string) ($row['name'] ?? ''));
                    $cat = $row['category'] ?? '';

                    if (! empty($row['is_system'])
                        || $cat === 'tax'
                        || $cat === 'bpjs'
                        || $cat === 'cash_advance'
                        || ! empty($row['cash_advance_installment_id'])
                        || strcasecmp($name, 'pph 21') === 0
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
                return;
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
            $this->persistItemLines($service);

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
            'lines.cashAdvanceInstallment',
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
        if (! empty($this->item->notes) && str_starts_with($this->item->notes, '{')) {
            $notesData = json_decode($this->item->notes, true) ?: [];
        }
        $notesData['disabled_company_bpjs'] = $disabledCompanyKeys;
        $notesData['enabled_company_bpjs'] = $enabledCompanyKeys;
        $this->item->notes = json_encode($notesData);
        $this->item->saveQuietly();
    }

    public function render(): View
    {
        $company = CompanyProfile::getProfile();

        return view('livewire.payrolls.manage-item', [
            'company' => $company,
        ]);
    }
}
