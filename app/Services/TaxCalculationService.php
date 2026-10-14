<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\PayrollLineCategory;
use App\Enums\PayrollLineType;
use App\Enums\PayrollPeriodStatus;
use App\Enums\PtkpStatus;
use App\Enums\TaxMethod;
use App\Enums\TaxRecipientType;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\SalaryComponent;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class TaxCalculationService
{
    /**
     * Pasal 17 UU HPP Annual Tax Brackets.
     */
    public const PASAL_17_BRACKETS = [
        ['limit' => 60_000_000.0, 'rate' => 0.05],
        ['limit' => 250_000_000.0, 'rate' => 0.15],
        ['limit' => 500_000_000.0, 'rate' => 0.25],
        ['limit' => 5_000_000_000.0, 'rate' => 0.30],
        ['limit' => INF, 'rate' => 0.35],
    ];

    /**
     * Biaya Jabatan maximum annual and monthly caps (5%).
     */
    public const BIAYA_JABATAN_RATE = 0.05;

    public const MAX_BIAYA_JABATAN_MONTHLY = 500_000.0;

    public const MAX_BIAYA_JABATAN_ANNUAL = 6_000_000.0;

    public function supportsMonthlyEmployeeTax(Employee $employee): bool
    {
        if ($employee->employment_status === EmploymentStatus::MAGANG) {
            return false;
        }

        $recipientType = $employee->detail?->tax_recipient_type;

        return $recipientType === TaxRecipientType::MonthlyEmployee
            || ($recipientType === null && $employee->employment_status === EmploymentStatus::TETAP);
    }

    /**
     * Calculate monthly PPh 21 for an employee based on PP 58/2023 & PMK 168/2023.
     *
     * @param  Employee  $employee  Employee model with details relation
     * @param  float  $basicSalary  Basic salary
     * @param  array|Collection  $allowanceRows  Array of allowances or PayrollItemLine collection
     * @param  CompanyProfile|null  $company  Company profile with tax settings
     * @param  int  $month  Payroll month (1-12)
     * @param  int  $year  Payroll year
     * @return array Calculated tax details
     */
    public function calculateMonthlyTax(
        Employee $employee,
        float $basicSalary,
        array|Collection $allowanceRows = [],
        ?CompanyProfile $company = null,
        int $month = 1,
        int $year = 2026,
        bool $forceEnable = false,
        bool $isFinalTaxPeriod = false,
        ?float $currentPensionDeduction = null
    ): array {
        $company = $company ?? CompanyProfile::getProfile();

        // 1. Check Global PPh 21 toggle (unless force enabled per employee)
        if (! $company->enable_pph21 && ! $forceEnable) {
            return [
                'is_enabled' => false,
                'tax_method' => TaxMethod::GROSS,
                'ptkp_status' => PtkpStatus::TK_0,
                'ter_category' => 'A',
                'ter_rate' => 0.0,
                'taxable_gross' => 0.0,
                'tax_allowance' => 0.0,
                'pph21_amount' => 0.0,
                'bpjs_taxable_insurance' => 0.0,
                'is_december' => $month === 12,
                'is_final_tax_period' => $month === 12 || $isFinalTaxPeriod,
                'notes' => 'PPh 21 dinonaktifkan secara global di pengaturan perusahaan.',
            ];
        }

        if (! $this->supportsMonthlyEmployeeTax($employee)) {
            throw new InvalidArgumentException('Jenis penerima penghasilan untuk pajak belum didukung atau belum diklasifikasikan. Atur dahulu pada data karyawan.');
        }

        $detail = $employee->detail;
        $taxMethod = $detail?->tax_method instanceof TaxMethod
            ? $detail->tax_method
            : (TaxMethod::tryFrom((string) ($detail?->tax_method ?? '')) ?? TaxMethod::GROSS);

        $ptkpStatus = $detail?->ptkp_status instanceof PtkpStatus
            ? $detail->ptkp_status
            : ($detail ? PtkpStatus::fromMaritalAndDependents($detail->marital_status ?? MaritalStatus::TK, (int) ($detail->dependents_count ?? 0)) : PtkpStatus::TK_0);

        $terCategory = $ptkpStatus->terCategory();

        // Calculate total cash allowances to determine gross salary (Gaji Kotor = Gaji Pokok + Tunjangan, Bonus, dll)
        $totalAllowances = 0.0;
        foreach ($allowanceRows as $row) {
            $amount = 0.0;
            if (is_array($row)) {
                if (($row['category'] ?? '') === 'tax' || strcasecmp(trim($row['name'] ?? ''), 'tunjangan pph 21 (gross-up)') === 0) {
                    continue;
                }
                $amount = (float) ($row['amount'] ?? 0);
            } elseif (is_object($row)) {
                if (($row->category?->value ?? $row->category ?? '') === 'tax' || strcasecmp(trim($row->name ?? ''), 'tunjangan pph 21 (gross-up)') === 0) {
                    continue;
                }
                $amount = (float) ($row->amount ?? 0);
            }
            $totalAllowances += max(0, $amount);
        }
        $grossSalary = $basicSalary + $totalAllowances;

        // 2. Calculate BPJS breakdown based on Gaji Kotor
        $bpjs = $this->calculateBpjsBreakdown($grossSalary, $company);
        $taxableInsurance = $company->include_bpjs_in_tax ? $bpjs['taxable_insurance'] : 0.0;

        // 3. Calculate taxable cash allowances from allowances list
        $taxableCashAllowances = $this->calculateTaxableAllowancesTotal($allowanceRows);

        // 4. Base monthly gross for tax
        $baseMonthlyCash = $basicSalary + $taxableCashAllowances + $taxableInsurance;

        // The last tax period may occur before December when employment ends.
        if ($month === 12 || $isFinalTaxPeriod) {
            return $this->calculateFinalPeriodReconciliation(
                $employee,
                $baseMonthlyCash,
                $taxMethod,
                $ptkpStatus,
                $terCategory,
                $bpjs,
                $currentPensionDeduction,
                $month,
                $year,
                $company
            );
        }

        // 6. Regular Monthly (Jan - Nov): Apply TER Rate (PP 58/2023)
        $isNonNpwpPenalized = $this->isSubjectToNonNpwpPenalty($employee);
        $nonNpwpMultiplier = $isNonNpwpPenalized ? 1.20 : 1.0;

        $taxAllowance = 0.0;
        $finalTaxableGross = $baseMonthlyCash;
        $terRate = 0.0;
        $baseTerRate = 0.0;
        $tax = 0.0;

        if ($taxMethod === TaxMethod::GROSS || $taxMethod === TaxMethod::NET) {
            // Gross: Employee pays tax / Net: Company absorbs directly without allowance
            $baseTerRate = $this->getTerRate($terCategory, $baseMonthlyCash);
            $terRate = round($baseTerRate * $nonNpwpMultiplier, 6);
            $tax = round($baseMonthlyCash * $baseTerRate * $nonNpwpMultiplier);
            $finalTaxableGross = $baseMonthlyCash;
            $taxAllowance = 0.0;
        } else {
            // Gross-Up: Iterative convergence to find Tax Allowance
            $baseRate = $this->getTerRate($terCategory, $baseMonthlyCash);
            $effectiveRate = $baseRate * $nonNpwpMultiplier;

            if ($baseRate <= 0.0) {
                $baseTerRate = 0.0;
                $terRate = 0.0;
                $taxAllowance = 0.0;
                $finalTaxableGross = $baseMonthlyCash;
                $tax = 0.0;
            } else {
                $iterations = 0;
                while ($iterations < 50) {
                    $calculatedAllowance = ($baseMonthlyCash * $effectiveRate) / (1.0 - $effectiveRate);
                    $testGross = $baseMonthlyCash + $calculatedAllowance;
                    $newBaseRate = $this->getTerRate($terCategory, $testGross);
                    $newEffectiveRate = $newBaseRate * $nonNpwpMultiplier;

                    if ($newBaseRate === $baseRate) {
                        $taxAllowance = round($calculatedAllowance);
                        break;
                    }

                    $baseRate = $newBaseRate;
                    $effectiveRate = $newEffectiveRate;
                    $taxAllowance = round($calculatedAllowance);
                    $iterations++;
                }

                $baseTerRate = $baseRate;
                $terRate = round($effectiveRate, 6);
                $finalTaxableGross = $baseMonthlyCash + $taxAllowance;
                $tax = $taxAllowance;
            }
        }

        $note = $isNonNpwpPenalized
            ? "PPh 21 TER Kategori {$terCategory} (".number_format($baseTerRate * 100, 2).'% + 20% Non-NPWP = '.number_format($terRate * 100, 2).'%)'
            : "PPh 21 TER Kategori {$terCategory} (".number_format($terRate * 100, 2).'%)';

        return [
            'is_enabled' => true,
            'tax_method' => $taxMethod,
            'ptkp_status' => $ptkpStatus,
            'ter_category' => $terCategory,
            'base_ter_rate' => $baseTerRate,
            'ter_rate' => $terRate,
            'taxable_gross' => $finalTaxableGross,
            'tax_allowance' => $taxAllowance,
            'pph21_amount' => $tax,
            'bpjs_taxable_insurance' => $taxableInsurance,
            'is_december' => false,
            'is_final_tax_period' => false,
            'is_non_npwp_penalized' => $isNonNpwpPenalized,
            'notes' => $note,
        ];
    }

    /**
     * Determine if an employee is subject to the +20% non-NPWP tax penalty (UU PPh Pasal 21 ayat 5a).
     *
     * In accordance with PMK 136/2023 and DJP Announcement PENG-6/PJ.09/2024 (e-Bupot 21/26),
     * an employee who has a valid NIK (16-digit or non-empty NIK string) is exempt from the +20%
     * penalty because NIK functions as NPWP.
     * The +20% penalty strictly applies if BOTH NPWP and NIK are absent/empty.
     */
    public function isSubjectToNonNpwpPenalty(Employee $employee): bool
    {
        $detail = $employee->detail;
        if (! $detail) {
            return false;
        }

        $hasNpwp = ! empty(trim((string) ($detail->npwp_number ?? '')));
        if ($hasNpwp) {
            return false;
        }

        $hasNik = ! empty(trim((string) ($detail->nik ?? '')));
        if ($hasNik) {
            return false;
        }

        return true;
    }

    /**
     * Reconcile PPh 21 in December or the employee's earlier final tax period.
     */
    protected function calculateFinalPeriodReconciliation(
        Employee $employee,
        float $finalPeriodGross,
        TaxMethod $taxMethod,
        PtkpStatus $ptkpStatus,
        string $terCategory,
        array $bpjs,
        ?float $currentPensionDeduction,
        int $month,
        int $year,
        CompanyProfile $company
    ): array {
        $previousItems = PayrollItem::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollPeriod', function ($q) use ($year, $month) {
                $q->where('year', $year)
                    ->where('month', '<', $month);
            })
            ->with(['payrollPeriod', 'lines.salaryComponent'])
            ->get(['id', 'payroll_period_id', 'taxable_gross', 'pph21_amount']);

        if ($previousItems->contains(fn (PayrollItem $item) => $item->payrollPeriod?->status !== PayrollPeriodStatus::PAID)) {
            throw new InvalidArgumentException('Payroll masa sebelumnya harus berstatus dibayar sebelum rekonsiliasi PPh 21 masa terakhir.');
        }

        $previousTaxPaid = (float) $previousItems->sum('pph21_amount');
        $previousGross = (float) $previousItems->sum('taxable_gross');
        $monthsWorked = $previousItems->count() + 1;
        $jobExpenseCap = min(self::MAX_BIAYA_JABATAN_ANNUAL, $monthsWorked * self::MAX_BIAYA_JABATAN_MONTHLY);
        $annualPensionDeduction = $this->sumPreviousPensionContributions($previousItems)
            + ($currentPensionDeduction ?? ((float) $bpjs['employee']['bpjs_jht'] + (float) $bpjs['employee']['bpjs_jp']));
        $isNonNpwpPenalized = $this->isSubjectToNonNpwpPenalty($employee);

        $taxAllowance = 0.0;
        $reconciliation = [];
        $finalPeriodTax = 0.0;

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $annualGross = $previousGross + $finalPeriodGross + $taxAllowance;
            $reconciliation = $this->calculateAnnualTax(
                $annualGross,
                $jobExpenseCap,
                $annualPensionDeduction,
                $ptkpStatus,
                $isNonNpwpPenalized
            );
            $finalPeriodTax = round($reconciliation['total_annual_tax'] - $previousTaxPaid);
            $requiredAllowance = $taxMethod === TaxMethod::GROSS_UP ? max(0.0, $finalPeriodTax) : 0.0;

            if ($requiredAllowance === $taxAllowance) {
                break;
            }

            $taxAllowance = $requiredAllowance;
        }

        if ($taxMethod === TaxMethod::GROSS_UP && $taxAllowance !== max(0.0, $finalPeriodTax)) {
            throw new InvalidArgumentException('Perhitungan tunjangan PPh 21 masa terakhir belum konvergen.');
        }

        return [
            'is_enabled' => true,
            'tax_method' => $taxMethod,
            'ptkp_status' => $ptkpStatus,
            'ter_category' => $terCategory,
            'ter_rate' => 0.0,
            'taxable_gross' => $finalPeriodGross + $taxAllowance,
            'tax_allowance' => $taxAllowance,
            'pph21_amount' => $finalPeriodTax,
            'bpjs_taxable_insurance' => $company->include_bpjs_in_tax ? $bpjs['taxable_insurance'] : 0.0,
            'is_december' => $month === 12,
            'is_final_tax_period' => true,
            'is_non_npwp_penalized' => $isNonNpwpPenalized,
            'annual_reconciliation' => [
                ...$reconciliation,
                'annual_gross' => $previousGross + $finalPeriodGross + $taxAllowance,
                'gross_jan_to_nov' => $previousGross,
                'december_gross' => $finalPeriodGross + $taxAllowance,
                'months_count_jan_to_nov' => $previousItems->count(),
                'gross_before_final_period' => $previousGross,
                'final_period_gross' => $finalPeriodGross + $taxAllowance,
                'months_count_before_final_period' => $previousItems->count(),
                'months_worked' => $monthsWorked,
                'job_expense_cap' => $jobExpenseCap,
                'pension_deduction' => $annualPensionDeduction,
                'tax_paid_jan_to_nov' => $previousTaxPaid,
                'december_tax' => $finalPeriodTax,
                'tax_paid_before_final_period' => $previousTaxPaid,
                'final_period_tax' => $finalPeriodTax,
                'final_period_month' => $month,
                'has_npwp' => ! $isNonNpwpPenalized,
                'is_non_npwp_penalized' => $isNonNpwpPenalized,
            ],
            'notes' => 'PPh 21 Rekonsiliasi Masa Pajak Terakhir (Pasal 17 UU HPP)',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function calculateAnnualTax(
        float $annualGross,
        float $jobExpenseCap,
        float $annualPensionDeduction,
        PtkpStatus $ptkpStatus,
        bool $isNonNpwpPenalized
    ): array {
        $biayaJabatan = min($annualGross * self::BIAYA_JABATAN_RATE, $jobExpenseCap);
        $annualNetIncome = max(0, $annualGross - $biayaJabatan - $annualPensionDeduction);
        $ptkp = $ptkpStatus->ptkpAmount();
        $pkp = max(0, floor(($annualNetIncome - $ptkp) / 1000) * 1000);
        $remainingPkp = $pkp;
        $totalAnnualTax = 0.0;
        $previousLimit = 0.0;
        $bracketBreakdown = [];

        foreach (self::PASAL_17_BRACKETS as $index => $bracket) {
            if ($remainingPkp <= 0) {
                break;
            }

            $range = $bracket['limit'] - $previousLimit;
            $taxableAmount = min($remainingPkp, $range);
            $taxForLayer = $taxableAmount * $bracket['rate'];

            $rangeLabel = $previousLimit == 0
                ? 's.d. Rp'.number_format($bracket['limit'], 0, ',', '.')
                : ($bracket['limit'] === INF
                    ? '> Rp'.number_format($previousLimit, 0, ',', '.')
                    : 'Rp'.number_format($previousLimit, 0, ',', '.').' s.d. Rp'.number_format($bracket['limit'], 0, ',', '.'));

            $bracketBreakdown[] = [
                'layer' => $index + 1,
                'rate' => $bracket['rate'],
                'rate_percent' => ($bracket['rate'] * 100).'%',
                'taxable_amount' => $taxableAmount,
                'tax_amount' => $taxForLayer,
                'range_label' => $rangeLabel,
            ];

            $totalAnnualTax += $taxForLayer;
            $remainingPkp -= $taxableAmount;
            $previousLimit = $bracket['limit'];
        }

        $baseAnnualTax = $totalAnnualTax;
        $penaltyAmount = 0.0;

        if ($isNonNpwpPenalized && $totalAnnualTax > 0) {
            $penaltyAmount = $totalAnnualTax * 0.20;
            $totalAnnualTax *= 1.20;
        }

        return [
            'biaya_jabatan' => $biayaJabatan,
            'net_income' => $annualNetIncome,
            'ptkp' => $ptkp,
            'pkp' => $pkp,
            'brackets' => $bracketBreakdown,
            'total_annual_tax_base' => $baseAnnualTax,
            'non_npwp_penalty' => $penaltyAmount,
            'total_annual_tax' => $totalAnnualTax,
        ];
    }

    /**
     * @param  Collection<int, PayrollItem>  $previousItems
     */
    protected function sumPreviousPensionContributions(Collection $previousItems): float
    {
        $total = 0.0;

        foreach ($previousItems as $item) {
            foreach ($item->lines as $line) {
                if ($line->type !== PayrollLineType::DEDUCTION || $line->category !== PayrollLineCategory::BPJS) {
                    continue;
                }

                $code = $line->salaryComponent?->code;
                $name = strtolower(trim($line->name));
                if (in_array($code, ['BPJS_JHT', 'BPJS_JP'], true)
                    || str_starts_with($name, 'bpjs jht')
                    || str_starts_with($name, 'bpjs pensiun')
                    || str_starts_with($name, 'bpjs jp')) {
                    $total += (float) $line->amount;
                }
            }
        }

        return $total;
    }

    /**
     * Calculate BPJS breakdown for both employee and company.
     *
     * @param  float  $basicSalary  Basic salary
     * @param  CompanyProfile|null  $company  Company profile settings
     * @return array BPJS components
     */
    public function calculateBpjsBreakdown(float $grossSalary, ?CompanyProfile $company = null): array
    {
        $company = $company ?? CompanyProfile::getProfile();

        $kesCap = (float) ($company->bpjs_kes_cap ?? 12_000_000.0);
        $jpCap = (float) ($company->bpjs_jp_cap ?? 10_042_300.0);

        // Wage base for BPJS calculations (calculated from Gaji Kotor / Gross Salary)
        $kesBaseWage = min($grossSalary, $kesCap);
        $jpBaseWage = min($grossSalary, $jpCap);

        // 1. Employee deductions (Deducted from take-home pay)
        $empKesRate = (float) ($company->bpjs_kes_emp_rate ?? 0.0100); // 1%
        $empJhtRate = (float) ($company->bpjs_jht_emp_rate ?? 0.0200); // 2%
        $empJpRate = (float) ($company->bpjs_jp_emp_rate ?? 0.0100);   // 1%

        $empKes = round($kesBaseWage * $empKesRate);
        $empJht = round($grossSalary * $empJhtRate);
        $empJp = round($jpBaseWage * $empJpRate);

        // 2. Company contributions (Employer benefit)
        $compKesRate = (float) ($company->bpjs_kes_comp_rate ?? 0.0400); // 4%
        $compJhtRate = (float) ($company->bpjs_jht_comp_rate ?? 0.0370); // 3.7%
        $compJpRate = (float) ($company->bpjs_jp_comp_rate ?? 0.0200);   // 2%
        $compJkkRate = (float) ($company->bpjs_jkk_risk_rate ?? 0.0024); // e.g. 0.24%
        $compJkmRate = (float) ($company->bpjs_jkm_rate ?? 0.0030);      // 0.3%

        $compKes = round($kesBaseWage * $compKesRate);
        $compJht = round($grossSalary * $compJhtRate);
        $compJp = round($jpBaseWage * $compJpRate);
        $compJkk = round($grossSalary * $compJkkRate);
        $compJkm = round($grossSalary * $compJkmRate);

        // Insurance that adds to employee's taxable gross: JKK + JKM + BPJS Kesehatan (Company Share)
        $taxableInsurance = $compJkk + $compJkm + $compKes;

        return [
            'employee' => [
                'bpjs_kes' => $empKes,
                'bpjs_jht' => $empJht,
                'bpjs_jp' => $empJp,
                'total' => $empKes + $empJht + $empJp,
            ],
            'company' => [
                'bpjs_kes' => $compKes,
                'bpjs_jht' => $compJht,
                'bpjs_jp' => $compJp,
                'bpjs_jkk' => $compJkk,
                'bpjs_jkm' => $compJkm,
                'total' => $compKes + $compJht + $compJp + $compJkk + $compJkm,
            ],
            'taxable_insurance' => $taxableInsurance,
        ];
    }

    /**
     * Calculate total taxable allowances from allowance rows.
     */
    protected function calculateTaxableAllowancesTotal(array|Collection $allowanceRows): float
    {
        $total = 0.0;
        $componentIds = collect($allowanceRows)
            ->map(fn ($row) => is_array($row) ? ($row['salary_component_id'] ?? null) : ($row->salary_component_id ?? null))
            ->filter()
            ->unique()
            ->values();
        $components = $componentIds->isEmpty()
            ? collect()
            : SalaryComponent::query()->whereIn('id', $componentIds)->get(['id', 'code', 'is_taxable'])->keyBy('id');

        foreach ($allowanceRows as $row) {
            $amount = 0.0;
            $componentId = null;
            $code = null;

            $category = null;
            $name = '';

            if (is_array($row)) {
                $amount = (float) ($row['amount'] ?? 0);
                $componentId = $row['salary_component_id'] ?? null;
                $category = $row['category'] ?? null;
                $name = (string) ($row['name'] ?? '');
            } elseif (is_object($row)) {
                $amount = (float) ($row->amount ?? 0);
                $componentId = $row->salary_component_id ?? null;
                $category = $row->category instanceof \BackedEnum ? $row->category->value : (string) ($row->category ?? '');
                $name = (string) ($row->name ?? '');
            }

            // Exclude gross-up tax allowance row itself if present in rows to prevent infinite compounding
            if ($category === 'tax' || strcasecmp(trim($name), 'tunjangan pph 21 (gross-up)') === 0) {
                continue;
            }

            if ($componentId) {
                $comp = $components->get($componentId);
                if ($comp && ($comp->code === 'TUNJ_PPH21' || ! $comp->is_taxable)) {
                    continue;
                }
            }

            $total += $amount;
        }

        return $total;
    }

    /**
     * Get TER Rate strictly according to PP 58/2023.
     */
    public function getTerRate(string $category, float $gross): float
    {
        if ($gross <= 0) {
            return 0.0;
        }

        // Kategori A (TK/0, TK/1, K/0)
        if ($category === 'A') {
            if ($gross <= 5_400_000) {
                return 0.0;
            }
            if ($gross <= 5_650_000) {
                return 0.0025;
            }
            if ($gross <= 5_950_000) {
                return 0.0050;
            }
            if ($gross <= 6_300_000) {
                return 0.0075;
            }
            if ($gross <= 6_750_000) {
                return 0.0100;
            }
            if ($gross <= 7_500_000) {
                return 0.0125;
            }
            if ($gross <= 8_550_000) {
                return 0.0150;
            }
            if ($gross <= 9_650_000) {
                return 0.0175;
            }
            if ($gross <= 10_050_000) {
                return 0.0200;
            }
            if ($gross <= 10_350_000) {
                return 0.0225;
            }
            if ($gross <= 10_700_000) {
                return 0.0250;
            }
            if ($gross <= 11_050_000) {
                return 0.0300;
            }
            if ($gross <= 11_600_000) {
                return 0.0350;
            }
            if ($gross <= 12_500_000) {
                return 0.0400;
            }
            if ($gross <= 13_750_000) {
                return 0.0500;
            }
            if ($gross <= 15_100_000) {
                return 0.0600;
            }
            if ($gross <= 16_950_000) {
                return 0.0700;
            }
            if ($gross <= 19_750_000) {
                return 0.0800;
            }
            if ($gross <= 24_150_000) {
                return 0.0900;
            }
            if ($gross <= 26_450_000) {
                return 0.1000;
            }
            if ($gross <= 28_000_000) {
                return 0.1100;
            }
            if ($gross <= 30_050_000) {
                return 0.1200;
            }
            if ($gross <= 32_400_000) {
                return 0.1300;
            }
            if ($gross <= 35_400_000) {
                return 0.1400;
            }
            if ($gross <= 39_100_000) {
                return 0.1500;
            }
            if ($gross <= 43_850_000) {
                return 0.1600;
            }
            if ($gross <= 47_800_000) {
                return 0.1700;
            }
            if ($gross <= 51_400_000) {
                return 0.1800;
            }
            if ($gross <= 56_300_000) {
                return 0.1900;
            }
            if ($gross <= 62_200_000) {
                return 0.2000;
            }
            if ($gross <= 68_600_000) {
                return 0.2100;
            }
            if ($gross <= 77_500_000) {
                return 0.2200;
            }
            if ($gross <= 89_000_000) {
                return 0.2300;
            }
            if ($gross <= 103_000_000) {
                return 0.2400;
            }
            if ($gross <= 125_000_000) {
                return 0.2500;
            }
            if ($gross <= 157_000_000) {
                return 0.2600;
            }
            if ($gross <= 206_000_000) {
                return 0.2700;
            }
            if ($gross <= 337_000_000) {
                return 0.2800;
            }
            if ($gross <= 454_000_000) {
                return 0.2900;
            }
            if ($gross <= 550_000_000) {
                return 0.3000;
            }
            if ($gross <= 695_000_000) {
                return 0.3100;
            }
            if ($gross <= 910_000_000) {
                return 0.3200;
            }
            if ($gross <= 1_400_000_000) {
                return 0.3300;
            }

            return 0.3400;
        }

        // Kategori B (TK/2, TK/3, K/1, K/2)
        if ($category === 'B') {
            if ($gross <= 6_200_000) {
                return 0.0;
            }
            if ($gross <= 6_500_000) {
                return 0.0025;
            }
            if ($gross <= 6_850_000) {
                return 0.0050;
            }
            if ($gross <= 7_300_000) {
                return 0.0075;
            }
            if ($gross <= 9_200_000) {
                return 0.0100;
            }
            if ($gross <= 10_750_000) {
                return 0.0150;
            }
            if ($gross <= 11_250_000) {
                return 0.0200;
            }
            if ($gross <= 11_600_000) {
                return 0.0250;
            }
            if ($gross <= 12_600_000) {
                return 0.0300;
            }
            if ($gross <= 13_600_000) {
                return 0.0400;
            }
            if ($gross <= 14_950_000) {
                return 0.0500;
            }
            if ($gross <= 16_400_000) {
                return 0.0600;
            }
            if ($gross <= 18_450_000) {
                return 0.0700;
            }
            if ($gross <= 21_850_000) {
                return 0.0800;
            }
            if ($gross <= 26_000_000) {
                return 0.0900;
            }
            if ($gross <= 27_700_000) {
                return 0.1000;
            }
            if ($gross <= 29_350_000) {
                return 0.1100;
            }
            if ($gross <= 31_450_000) {
                return 0.1200;
            }
            if ($gross <= 33_950_000) {
                return 0.1300;
            }
            if ($gross <= 37_100_000) {
                return 0.1400;
            }
            if ($gross <= 41_100_000) {
                return 0.1500;
            }
            if ($gross <= 45_800_000) {
                return 0.1600;
            }
            if ($gross <= 49_500_000) {
                return 0.1700;
            }
            if ($gross <= 53_800_000) {
                return 0.1800;
            }
            if ($gross <= 58_500_000) {
                return 0.1900;
            }
            if ($gross <= 64_000_000) {
                return 0.2000;
            }
            if ($gross <= 71_000_000) {
                return 0.2100;
            }
            if ($gross <= 80_000_000) {
                return 0.2200;
            }
            if ($gross <= 93_000_000) {
                return 0.2300;
            }
            if ($gross <= 109_000_000) {
                return 0.2400;
            }
            if ($gross <= 129_000_000) {
                return 0.2500;
            }
            if ($gross <= 163_000_000) {
                return 0.2600;
            }
            if ($gross <= 211_000_000) {
                return 0.2700;
            }
            if ($gross <= 374_000_000) {
                return 0.2800;
            }
            if ($gross <= 459_000_000) {
                return 0.2900;
            }
            if ($gross <= 555_000_000) {
                return 0.3000;
            }
            if ($gross <= 704_000_000) {
                return 0.3100;
            }
            if ($gross <= 957_000_000) {
                return 0.3200;
            }
            if ($gross <= 1_405_000_000) {
                return 0.3300;
            }

            return 0.3400;
        }

        // Kategori C (K/3)
        if ($category === 'C') {
            if ($gross <= 6_600_000) {
                return 0.0;
            }
            if ($gross <= 6_950_000) {
                return 0.0025;
            }
            if ($gross <= 7_350_000) {
                return 0.0050;
            }
            if ($gross <= 7_800_000) {
                return 0.0075;
            }
            if ($gross <= 8_850_000) {
                return 0.0100;
            }
            if ($gross <= 9_800_000) {
                return 0.0125;
            }
            if ($gross <= 10_950_000) {
                return 0.0150;
            }
            if ($gross <= 11_200_000) {
                return 0.0175;
            }
            if ($gross <= 12_050_000) {
                return 0.0200;
            }
            if ($gross <= 12_950_000) {
                return 0.0300;
            }
            if ($gross <= 14_150_000) {
                return 0.0400;
            }
            if ($gross <= 15_550_000) {
                return 0.0500;
            }
            if ($gross <= 17_050_000) {
                return 0.0600;
            }
            if ($gross <= 19_500_000) {
                return 0.0700;
            }
            if ($gross <= 22_700_000) {
                return 0.0800;
            }
            if ($gross <= 26_600_000) {
                return 0.0900;
            }
            if ($gross <= 28_100_000) {
                return 0.1000;
            }
            if ($gross <= 30_100_000) {
                return 0.1100;
            }
            if ($gross <= 32_600_000) {
                return 0.1200;
            }
            if ($gross <= 35_400_000) {
                return 0.1300;
            }
            if ($gross <= 38_900_000) {
                return 0.1400;
            }
            if ($gross <= 43_000_000) {
                return 0.1500;
            }
            if ($gross <= 47_400_000) {
                return 0.1600;
            }
            if ($gross <= 51_200_000) {
                return 0.1700;
            }
            if ($gross <= 55_800_000) {
                return 0.1800;
            }
            if ($gross <= 60_400_000) {
                return 0.1900;
            }
            if ($gross <= 66_700_000) {
                return 0.2000;
            }
            if ($gross <= 74_500_000) {
                return 0.2100;
            }
            if ($gross <= 83_200_000) {
                return 0.2200;
            }
            if ($gross <= 95_600_000) {
                return 0.2300;
            }
            if ($gross <= 110_000_000) {
                return 0.2400;
            }
            if ($gross <= 134_000_000) {
                return 0.2500;
            }
            if ($gross <= 169_000_000) {
                return 0.2600;
            }
            if ($gross <= 221_000_000) {
                return 0.2700;
            }
            if ($gross <= 390_000_000) {
                return 0.2800;
            }
            if ($gross <= 463_000_000) {
                return 0.2900;
            }
            if ($gross <= 561_000_000) {
                return 0.3000;
            }
            if ($gross <= 709_000_000) {
                return 0.3100;
            }
            if ($gross <= 965_000_000) {
                return 0.3200;
            }
            if ($gross <= 1_419_000_000) {
                return 0.3300;
            }

            return 0.3400;
        }

        return 0.0;
    }

    /**
     * Get all official TER tiers with bracket ranges and rates according to PP 58/2023 & PMK 168/2023.
     */
    public function getTerTiers(string $category): array
    {
        return match ($category) {
            'A' => [
                ['tier' => 1, 'min' => 0, 'max' => 5_400_000, 'rate' => 0.0],
                ['tier' => 2, 'min' => 5_400_000, 'max' => 5_650_000, 'rate' => 0.0025],
                ['tier' => 3, 'min' => 5_650_000, 'max' => 5_950_000, 'rate' => 0.0050],
                ['tier' => 4, 'min' => 5_950_000, 'max' => 6_300_000, 'rate' => 0.0075],
                ['tier' => 5, 'min' => 6_300_000, 'max' => 6_750_000, 'rate' => 0.0100],
                ['tier' => 6, 'min' => 6_750_000, 'max' => 7_500_000, 'rate' => 0.0125],
                ['tier' => 7, 'min' => 7_500_000, 'max' => 8_550_000, 'rate' => 0.0150],
                ['tier' => 8, 'min' => 8_550_000, 'max' => 9_650_000, 'rate' => 0.0175],
                ['tier' => 9, 'min' => 9_650_000, 'max' => 10_050_000, 'rate' => 0.0200],
                ['tier' => 10, 'min' => 10_050_000, 'max' => 10_350_000, 'rate' => 0.0225],
                ['tier' => 11, 'min' => 10_350_000, 'max' => 10_700_000, 'rate' => 0.0250],
                ['tier' => 12, 'min' => 10_700_000, 'max' => 11_050_000, 'rate' => 0.0300],
                ['tier' => 13, 'min' => 11_050_000, 'max' => 11_600_000, 'rate' => 0.0350],
                ['tier' => 14, 'min' => 11_600_000, 'max' => 12_500_000, 'rate' => 0.0400],
                ['tier' => 15, 'min' => 12_500_000, 'max' => 13_750_000, 'rate' => 0.0500],
                ['tier' => 16, 'min' => 13_750_000, 'max' => 15_100_000, 'rate' => 0.0600],
                ['tier' => 17, 'min' => 15_100_000, 'max' => 16_950_000, 'rate' => 0.0700],
                ['tier' => 18, 'min' => 16_950_000, 'max' => 19_750_000, 'rate' => 0.0800],
                ['tier' => 19, 'min' => 19_750_000, 'max' => 24_150_000, 'rate' => 0.0900],
                ['tier' => 20, 'min' => 24_150_000, 'max' => 26_450_000, 'rate' => 0.1000],
                ['tier' => 21, 'min' => 26_450_000, 'max' => 28_000_000, 'rate' => 0.1100],
                ['tier' => 22, 'min' => 28_000_000, 'max' => 30_050_000, 'rate' => 0.1200],
                ['tier' => 23, 'min' => 30_050_000, 'max' => 32_400_000, 'rate' => 0.1300],
                ['tier' => 24, 'min' => 32_400_000, 'max' => 35_400_000, 'rate' => 0.1400],
                ['tier' => 25, 'min' => 35_400_000, 'max' => 39_100_000, 'rate' => 0.1500],
                ['tier' => 26, 'min' => 39_100_000, 'max' => 43_850_000, 'rate' => 0.1600],
                ['tier' => 27, 'min' => 43_850_000, 'max' => 47_800_000, 'rate' => 0.1700],
                ['tier' => 28, 'min' => 47_800_000, 'max' => 51_400_000, 'rate' => 0.1800],
                ['tier' => 29, 'min' => 51_400_000, 'max' => 56_300_000, 'rate' => 0.1900],
                ['tier' => 30, 'min' => 56_300_000, 'max' => 62_200_000, 'rate' => 0.2000],
                ['tier' => 31, 'min' => 62_200_000, 'max' => 68_600_000, 'rate' => 0.2100],
                ['tier' => 32, 'min' => 68_600_000, 'max' => 77_500_000, 'rate' => 0.2200],
                ['tier' => 33, 'min' => 77_500_000, 'max' => 89_000_000, 'rate' => 0.2300],
                ['tier' => 34, 'min' => 89_000_000, 'max' => 103_000_000, 'rate' => 0.2400],
                ['tier' => 35, 'min' => 103_000_000, 'max' => 125_000_000, 'rate' => 0.2500],
                ['tier' => 36, 'min' => 125_000_000, 'max' => 157_000_000, 'rate' => 0.2600],
                ['tier' => 37, 'min' => 157_000_000, 'max' => 206_000_000, 'rate' => 0.2700],
                ['tier' => 38, 'min' => 206_000_000, 'max' => 337_000_000, 'rate' => 0.2800],
                ['tier' => 39, 'min' => 337_000_000, 'max' => 454_000_000, 'rate' => 0.2900],
                ['tier' => 40, 'min' => 454_000_000, 'max' => 550_000_000, 'rate' => 0.3000],
                ['tier' => 41, 'min' => 550_000_000, 'max' => 695_000_000, 'rate' => 0.3100],
                ['tier' => 42, 'min' => 695_000_000, 'max' => 910_000_000, 'rate' => 0.3200],
                ['tier' => 43, 'min' => 910_000_000, 'max' => 1_400_000_000, 'rate' => 0.3300],
                ['tier' => 44, 'min' => 1_400_000_000, 'max' => null, 'rate' => 0.3400],
            ],
            'B' => [
                ['tier' => 1, 'min' => 0, 'max' => 6_200_000, 'rate' => 0.0],
                ['tier' => 2, 'min' => 6_200_000, 'max' => 6_500_000, 'rate' => 0.0025],
                ['tier' => 3, 'min' => 6_500_000, 'max' => 6_850_000, 'rate' => 0.0050],
                ['tier' => 4, 'min' => 6_850_000, 'max' => 7_300_000, 'rate' => 0.0075],
                ['tier' => 5, 'min' => 7_300_000, 'max' => 9_200_000, 'rate' => 0.0100],
                ['tier' => 6, 'min' => 9_200_000, 'max' => 10_750_000, 'rate' => 0.0150],
                ['tier' => 7, 'min' => 10_750_000, 'max' => 11_250_000, 'rate' => 0.0200],
                ['tier' => 8, 'min' => 11_250_000, 'max' => 11_600_000, 'rate' => 0.0250],
                ['tier' => 9, 'min' => 11_600_000, 'max' => 12_600_000, 'rate' => 0.0300],
                ['tier' => 10, 'min' => 12_600_000, 'max' => 13_600_000, 'rate' => 0.0400],
                ['tier' => 11, 'min' => 13_600_000, 'max' => 14_950_000, 'rate' => 0.0500],
                ['tier' => 12, 'min' => 14_950_000, 'max' => 16_400_000, 'rate' => 0.0600],
                ['tier' => 13, 'min' => 16_400_000, 'max' => 18_450_000, 'rate' => 0.0700],
                ['tier' => 14, 'min' => 18_450_000, 'max' => 21_850_000, 'rate' => 0.0800],
                ['tier' => 15, 'min' => 21_850_000, 'max' => 26_000_000, 'rate' => 0.0900],
                ['tier' => 16, 'min' => 26_000_000, 'max' => 27_700_000, 'rate' => 0.1000],
                ['tier' => 17, 'min' => 27_700_000, 'max' => 29_350_000, 'rate' => 0.1100],
                ['tier' => 18, 'min' => 29_350_000, 'max' => 31_450_000, 'rate' => 0.1200],
                ['tier' => 19, 'min' => 31_450_000, 'max' => 33_950_000, 'rate' => 0.1300],
                ['tier' => 20, 'min' => 33_950_000, 'max' => 37_100_000, 'rate' => 0.1400],
                ['tier' => 21, 'min' => 37_100_000, 'max' => 41_100_000, 'rate' => 0.1500],
                ['tier' => 22, 'min' => 41_100_000, 'max' => 45_800_000, 'rate' => 0.1600],
                ['tier' => 23, 'min' => 45_800_000, 'max' => 49_500_000, 'rate' => 0.1700],
                ['tier' => 24, 'min' => 49_500_000, 'max' => 53_800_000, 'rate' => 0.1800],
                ['tier' => 25, 'min' => 53_800_000, 'max' => 58_500_000, 'rate' => 0.1900],
                ['tier' => 26, 'min' => 58_500_000, 'max' => 64_000_000, 'rate' => 0.2000],
                ['tier' => 27, 'min' => 64_000_000, 'max' => 71_000_000, 'rate' => 0.2100],
                ['tier' => 28, 'min' => 71_000_000, 'max' => 80_000_000, 'rate' => 0.2200],
                ['tier' => 29, 'min' => 80_000_000, 'max' => 93_000_000, 'rate' => 0.2300],
                ['tier' => 30, 'min' => 93_000_000, 'max' => 109_000_000, 'rate' => 0.2400],
                ['tier' => 31, 'min' => 109_000_000, 'max' => 129_000_000, 'rate' => 0.2500],
                ['tier' => 32, 'min' => 129_000_000, 'max' => 163_000_000, 'rate' => 0.2600],
                ['tier' => 33, 'min' => 163_000_000, 'max' => 211_000_000, 'rate' => 0.2700],
                ['tier' => 34, 'min' => 211_000_000, 'max' => 374_000_000, 'rate' => 0.2800],
                ['tier' => 35, 'min' => 374_000_000, 'max' => 459_000_000, 'rate' => 0.2900],
                ['tier' => 36, 'min' => 459_000_000, 'max' => 555_000_000, 'rate' => 0.3000],
                ['tier' => 37, 'min' => 555_000_000, 'max' => 704_000_000, 'rate' => 0.3100],
                ['tier' => 38, 'min' => 704_000_000, 'max' => 957_000_000, 'rate' => 0.3200],
                ['tier' => 39, 'min' => 957_000_000, 'max' => 1_405_000_000, 'rate' => 0.3300],
                ['tier' => 40, 'min' => 1_405_000_000, 'max' => null, 'rate' => 0.3400],
            ],
            'C' => [
                ['tier' => 1, 'min' => 0, 'max' => 6_600_000, 'rate' => 0.0],
                ['tier' => 2, 'min' => 6_600_000, 'max' => 6_950_000, 'rate' => 0.0025],
                ['tier' => 3, 'min' => 6_950_000, 'max' => 7_350_000, 'rate' => 0.0050],
                ['tier' => 4, 'min' => 7_350_000, 'max' => 7_800_000, 'rate' => 0.0075],
                ['tier' => 5, 'min' => 7_800_000, 'max' => 8_850_000, 'rate' => 0.0100],
                ['tier' => 6, 'min' => 8_850_000, 'max' => 9_800_000, 'rate' => 0.0125],
                ['tier' => 7, 'min' => 9_800_000, 'max' => 10_950_000, 'rate' => 0.0150],
                ['tier' => 8, 'min' => 10_950_000, 'max' => 11_200_000, 'rate' => 0.0175],
                ['tier' => 9, 'min' => 11_200_000, 'max' => 12_050_000, 'rate' => 0.0200],
                ['tier' => 10, 'min' => 12_050_000, 'max' => 12_950_000, 'rate' => 0.0300],
                ['tier' => 11, 'min' => 12_950_000, 'max' => 14_150_000, 'rate' => 0.0400],
                ['tier' => 12, 'min' => 14_150_000, 'max' => 15_550_000, 'rate' => 0.0500],
                ['tier' => 13, 'min' => 15_550_000, 'max' => 17_050_000, 'rate' => 0.0600],
                ['tier' => 14, 'min' => 17_050_000, 'max' => 19_500_000, 'rate' => 0.0700],
                ['tier' => 15, 'min' => 19_500_000, 'max' => 22_700_000, 'rate' => 0.0800],
                ['tier' => 16, 'min' => 22_700_000, 'max' => 26_600_000, 'rate' => 0.0900],
                ['tier' => 17, 'min' => 26_600_000, 'max' => 28_100_000, 'rate' => 0.1000],
                ['tier' => 18, 'min' => 28_100_000, 'max' => 30_100_000, 'rate' => 0.1100],
                ['tier' => 19, 'min' => 30_100_000, 'max' => 32_600_000, 'rate' => 0.1200],
                ['tier' => 20, 'min' => 32_600_000, 'max' => 35_400_000, 'rate' => 0.1300],
                ['tier' => 21, 'min' => 35_400_000, 'max' => 38_900_000, 'rate' => 0.1400],
                ['tier' => 22, 'min' => 38_900_000, 'max' => 43_000_000, 'rate' => 0.1500],
                ['tier' => 23, 'min' => 43_000_000, 'max' => 47_400_000, 'rate' => 0.1600],
                ['tier' => 24, 'min' => 47_400_000, 'max' => 51_200_000, 'rate' => 0.1700],
                ['tier' => 25, 'min' => 51_200_000, 'max' => 55_800_000, 'rate' => 0.1800],
                ['tier' => 26, 'min' => 55_800_000, 'max' => 60_400_000, 'rate' => 0.1900],
                ['tier' => 27, 'min' => 60_400_000, 'max' => 66_700_000, 'rate' => 0.2000],
                ['tier' => 28, 'min' => 66_700_000, 'max' => 74_500_000, 'rate' => 0.2100],
                ['tier' => 29, 'min' => 74_500_000, 'max' => 83_200_000, 'rate' => 0.2200],
                ['tier' => 30, 'min' => 83_200_000, 'max' => 95_600_000, 'rate' => 0.2300],
                ['tier' => 31, 'min' => 95_600_000, 'max' => 110_000_000, 'rate' => 0.2400],
                ['tier' => 32, 'min' => 110_000_000, 'max' => 134_000_000, 'rate' => 0.2500],
                ['tier' => 33, 'min' => 134_000_000, 'max' => 169_000_000, 'rate' => 0.2600],
                ['tier' => 34, 'min' => 169_000_000, 'max' => 221_000_000, 'rate' => 0.2700],
                ['tier' => 35, 'min' => 221_000_000, 'max' => 390_000_000, 'rate' => 0.2800],
                ['tier' => 36, 'min' => 390_000_000, 'max' => 463_000_000, 'rate' => 0.2900],
                ['tier' => 37, 'min' => 463_000_000, 'max' => 561_000_000, 'rate' => 0.3000],
                ['tier' => 38, 'min' => 561_000_000, 'max' => 709_000_000, 'rate' => 0.3100],
                ['tier' => 39, 'min' => 709_000_000, 'max' => 965_000_000, 'rate' => 0.3200],
                ['tier' => 40, 'min' => 965_000_000, 'max' => 1_419_000_000, 'rate' => 0.3300],
                ['tier' => 41, 'min' => 1_419_000_000, 'max' => null, 'rate' => 0.3400],
            ],
            default => [],
        };
    }

    /**
     * Get detailed tier info (tier number, bracket range, and rate) for a given gross amount and category.
     */
    public function getTerTierInfo(string $category, float $gross): array
    {
        $tiers = $this->getTerTiers($category);
        if (empty($tiers) || $gross <= 0) {
            return [
                'category' => $category,
                'tier_number' => 1,
                'total_tiers' => count($tiers),
                'min' => 0,
                'max' => $tiers[0]['max'] ?? 0,
                'rate' => 0.0,
                'range_label' => 's.d. Rp '.number_format($tiers[0]['max'] ?? 0, 0, ',', '.'),
            ];
        }

        foreach ($tiers as $tier) {
            if ($tier['max'] === null || $gross <= $tier['max']) {
                $rangeLabel = $tier['min'] === 0
                    ? 's.d. Rp '.number_format($tier['max'], 0, ',', '.')
                    : ($tier['max'] === null
                        ? '> Rp '.number_format($tier['min'], 0, ',', '.')
                        : '> Rp '.number_format($tier['min'], 0, ',', '.').' s.d. Rp '.number_format($tier['max'], 0, ',', '.'));

                return [
                    'category' => $category,
                    'tier_number' => $tier['tier'],
                    'total_tiers' => count($tiers),
                    'min' => $tier['min'],
                    'max' => $tier['max'],
                    'rate' => $tier['rate'],
                    'range_label' => $rangeLabel,
                ];
            }
        }

        $last = end($tiers);

        return [
            'category' => $category,
            'tier_number' => $last['tier'],
            'total_tiers' => count($tiers),
            'min' => $last['min'],
            'max' => $last['max'],
            'rate' => $last['rate'],
            'range_label' => '> Rp '.number_format($last['min'], 0, ',', '.'),
        ];
    }
}
