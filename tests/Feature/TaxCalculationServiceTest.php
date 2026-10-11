<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\MaritalStatus;
use App\Enums\TaxMethod;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Services\TaxCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaxCalculationServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected TaxCalculationService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxService = new TaxCalculationService;
    }

    protected function createEmployeeWithDetail(
        float $basicSalary = 10_000_000.0,
        TaxMethod $taxMethod = TaxMethod::GROSS,
        MaritalStatus $maritalStatus = MaritalStatus::TK,
        int $dependents = 0,
        bool $hasNpwp = true,
        ?string $nik = null
    ): Employee {
        $dept = Department::firstOrCreate(['name' => 'IT Department'], ['description' => 'IT Dept']);
        $pos = Position::firstOrCreate(['name' => 'Software Engineer'], ['description' => 'SE Pos']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Test Employee',
            'join_date' => Carbon::now()->subYear(),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => $basicSalary,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'employee_code' => 'EMP-TEST-'.uniqid(),
            'marital_status' => $maritalStatus,
            'dependents_count' => $dependents,
            'tax_method' => $taxMethod,
            'has_npwp' => $hasNpwp,
            'npwp_number' => $hasNpwp ? '12.345.678.9-012.000' : null,
            'nik' => $nik,
        ]);

        return $employee->load('detail');
    }

    public function test_ter_rate_lookup_for_categories_a_b_c(): void
    {
        // Below PTKP threshold (rate 0%)
        $this->assertEquals(0.0, $this->taxService->getTerRate('A', 5_000_000));
        $this->assertEquals(0.0, $this->taxService->getTerRate('B', 6_000_000));
        $this->assertEquals(0.0, $this->taxService->getTerRate('C', 6_500_000));

        // Category A (TK/0, TK/1, K/0)
        $this->assertEquals(0.0075, $this->taxService->getTerRate('A', 6_000_000));
        $this->assertEquals(0.0200, $this->taxService->getTerRate('A', 10_000_000));
        $this->assertEquals(0.0900, $this->taxService->getTerRate('A', 20_000_000));

        // Category B (TK/2, TK/3, K/1, K/2)
        $this->assertEquals(0.0050, $this->taxService->getTerRate('B', 6_600_000));
        $this->assertEquals(0.0150, $this->taxService->getTerRate('B', 10_000_000));
        $this->assertEquals(0.0800, $this->taxService->getTerRate('B', 20_000_000));

        // Category C (K/3)
        $this->assertEquals(0.0025, $this->taxService->getTerRate('C', 6_800_000));
        $this->assertEquals(0.0150, $this->taxService->getTerRate('C', 10_000_000));
        $this->assertEquals(0.0700, $this->taxService->getTerRate('C', 18_000_000));
    }

    public function test_bpjs_breakdown_calculation(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'bpjs_jkk_risk_rate' => 0.0024,
            'bpjs_jkm_rate' => 0.0030,
            'bpjs_kes_emp_rate' => 0.0100,
            'bpjs_kes_comp_rate' => 0.0400,
            'bpjs_jht_emp_rate' => 0.0200,
            'bpjs_jht_comp_rate' => 0.0370,
            'bpjs_jp_emp_rate' => 0.0100,
            'bpjs_jp_comp_rate' => 0.0200,
            'bpjs_kes_cap' => 12_000_000.0,
            'bpjs_jp_cap' => 10_042_300.0,
        ]);

        $breakdown = $this->taxService->calculateBpjsBreakdown(10_000_000, $company);

        // Employee: 1% Kes (100k), 2% JHT (200k), 1% JP (100k)
        $this->assertEquals(100_000, $breakdown['employee']['bpjs_kes']);
        $this->assertEquals(200_000, $breakdown['employee']['bpjs_jht']);
        $this->assertEquals(100_000, $breakdown['employee']['bpjs_jp']);
        $this->assertEquals(400_000, $breakdown['employee']['total']);

        // Company: 4% Kes (400k), 3.7% JHT (370k), 2% JP (200k), JKK 0.24% (24k), JKM 0.3% (30k)
        $this->assertEquals(400_000, $breakdown['company']['bpjs_kes']);
        $this->assertEquals(370_000, $breakdown['company']['bpjs_jht']);
        $this->assertEquals(200_000, $breakdown['company']['bpjs_jp']);
        $this->assertEquals(24_000, $breakdown['company']['bpjs_jkk']);
        $this->assertEquals(30_000, $breakdown['company']['bpjs_jkm']);

        // Taxable insurance: Kes (400k) + JKK (24k) + JKM (30k) = 454k
        $this->assertEquals(454_000, $breakdown['taxable_insurance']);
    }

    public function test_calculate_monthly_tax_gross_method(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => true,
            'bpjs_jkk_risk_rate' => 0.0024,
            'bpjs_jkm_rate' => 0.0030,
            'bpjs_kes_comp_rate' => 0.0400,
        ]);

        // TK/0 -> TER A
        $employee = $this->createEmployeeWithDetail(
            basicSalary: 10_000_000.0,
            taxMethod: TaxMethod::GROSS,
            maritalStatus: MaritalStatus::TK,
            dependents: 0
        );

        $result = $this->taxService->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 10_000_000.0,
            allowanceRows: [],
            company: $company,
            month: 5,
            year: 2026
        );

        $this->assertTrue($result['is_enabled']);
        $this->assertEquals(TaxMethod::GROSS, $result['tax_method']);
        $this->assertEquals('A', $result['ter_category']);

        // Taxable gross: 10.000.000 + 454.000 (BPJS insurance) = 10.454.000
        $this->assertEquals(10_454_000.0, $result['taxable_gross']);

        // For 10.454.000 in Category A (bracket <= 10.700.000 is 2.5%)
        $this->assertEquals(0.0250, $result['ter_rate']);
        $this->assertEquals(round(10_454_000 * 0.025), $result['pph21_amount']);
        $this->assertEquals(0.0, $result['tax_allowance']);
    }

    public function test_calculate_monthly_tax_gross_up_method(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        // TK/0 (Category A), Gross-up
        $employee = $this->createEmployeeWithDetail(
            basicSalary: 10_000_000.0,
            taxMethod: TaxMethod::GROSS_UP,
            maritalStatus: MaritalStatus::TK,
            dependents: 0
        );

        $result = $this->taxService->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 10_000_000.0,
            allowanceRows: [],
            company: $company,
            month: 3,
            year: 2026
        );

        $this->assertTrue($result['is_enabled']);
        $this->assertEquals(TaxMethod::GROSS_UP, $result['tax_method']);
        $this->assertGreaterThan(0, $result['pph21_amount']);

        // In Gross-Up, tax allowance provided by company must match PPh 21 amount
        $this->assertEquals($result['tax_allowance'], $result['pph21_amount']);
        $this->assertEquals(10_000_000.0 + $result['tax_allowance'], $result['taxable_gross']);
    }

    public function test_tax_is_zero_when_global_toggle_is_disabled(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update(['enable_pph21' => false]);

        $employee = $this->createEmployeeWithDetail(basicSalary: 15_000_000.0);

        $result = $this->taxService->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 15_000_000.0,
            allowanceRows: [],
            company: $company,
            month: 1,
            year: 2026
        );

        $this->assertFalse($result['is_enabled']);
        $this->assertEquals(0.0, $result['pph21_amount']);
        $this->assertEquals(0.0, $result['tax_allowance']);
    }

    public function test_december_reconciliation_pasal_17(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update(['enable_pph21' => true, 'include_bpjs_in_tax' => false]);

        $employee = $this->createEmployeeWithDetail(
            basicSalary: 10_000_000.0,
            taxMethod: TaxMethod::GROSS,
            maritalStatus: MaritalStatus::TK,
            dependents: 0,
            hasNpwp: true
        );

        // Seed 11 payroll items for Jan - Nov (10.000.000 gross, 200.000 tax/month)
        for ($m = 1; $m <= 11; $m++) {
            $period = PayrollPeriod::create([
                'reference_number' => 'PR-2026'.str_pad((string) $m, 2, '0', STR_PAD_LEFT),
                'name' => "Payroll {$m}",
                'month' => $m,
                'year' => 2026,
                'start_date' => "2026-{$m}-01",
                'end_date' => "2026-{$m}-28",
                'status' => 'paid',
            ]);

            PayrollItem::create([
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'payslip_number' => "SLP-2026{$m}-001",
                'basic_salary' => 10_000_000,
                'total_allowances' => 0,
                'total_deductions' => 200_000,
                'net_salary' => 9_800_000,
                'taxable_gross' => 10_000_000,
                'pph21_amount' => 200_000,
            ]);
        }

        // Now calculate Month 12 (December)
        $result = $this->taxService->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 10_000_000.0,
            allowanceRows: [],
            company: $company,
            month: 12,
            year: 2026
        );

        $this->assertTrue($result['is_december']);
        $this->assertNotNull($result['annual_reconciliation']);

        $recon = $result['annual_reconciliation'];
        // Annual gross: 110.000.000 + 10.000.000 = 120.000.000
        $this->assertEquals(120_000_000.0, $recon['annual_gross']);
        // Biaya jabatan: 5% of 120m = 6.000.000 (capped at 6m)
        $this->assertEquals(6_000_000.0, $recon['biaya_jabatan']);
        // PTKP TK/0: 54.000.000
        $this->assertEquals(54_000_000.0, $recon['ptkp']);
        // Tax paid Jan - Nov: 11 * 200.000 = 2.200.000
        $this->assertEquals(2_200_000.0, $recon['tax_paid_jan_to_nov']);

        // December tax should be annual tax minus tax paid
        $this->assertEquals(round($recon['total_annual_tax'] - $recon['tax_paid_jan_to_nov']), $result['pph21_amount']);
    }

    public function test_calculate_monthly_tax_non_npwp_without_nik_applies_twenty_percent_penalty(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $employee = $this->createEmployeeWithDetail(
            basicSalary: 10_000_000.0,
            taxMethod: TaxMethod::GROSS,
            maritalStatus: MaritalStatus::TK,
            dependents: 0,
            hasNpwp: false,
            nik: null
        );

        $result = $this->taxService->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 10_000_000.0,
            allowanceRows: [],
            company: $company,
            month: 4,
            year: 2026
        );

        $this->assertTrue($result['is_enabled']);
        $this->assertTrue($result['is_non_npwp_penalized']);
        // Category A for 10m is 0.0200. With +20%: 0.0240
        $this->assertEquals(0.0200, $result['base_ter_rate']);
        $this->assertEquals(0.0240, $result['ter_rate']);
        // Tax: 10m * 0.0240 = 240.000
        $this->assertEquals(240_000.0, $result['pph21_amount']);
        $this->assertStringContainsString('+ 20% Non-NPWP', $result['notes']);
    }

    public function test_calculate_monthly_tax_non_npwp_with_valid_nik_exempts_from_penalty(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $employee = $this->createEmployeeWithDetail(
            basicSalary: 10_000_000.0,
            taxMethod: TaxMethod::GROSS,
            maritalStatus: MaritalStatus::TK,
            dependents: 0,
            hasNpwp: false,
            nik: '3201234567890001'
        );

        $result = $this->taxService->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 10_000_000.0,
            allowanceRows: [],
            company: $company,
            month: 4,
            year: 2026
        );

        $this->assertTrue($result['is_enabled']);
        // Under PMK 136/2023 & PENG-6/PJ.09/2024, valid NIK waives the +20% penalty
        $this->assertFalse($result['is_non_npwp_penalized']);
        $this->assertEquals(0.0200, $result['ter_rate']);
        $this->assertEquals(200_000.0, $result['pph21_amount']);
    }

    public function test_calculate_monthly_tax_non_npwp_gross_up_method(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $employee = $this->createEmployeeWithDetail(
            basicSalary: 10_000_000.0,
            taxMethod: TaxMethod::GROSS_UP,
            maritalStatus: MaritalStatus::TK,
            dependents: 0,
            hasNpwp: false,
            nik: null
        );

        $result = $this->taxService->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 10_000_000.0,
            allowanceRows: [],
            company: $company,
            month: 4,
            year: 2026
        );

        $this->assertTrue($result['is_enabled']);
        $this->assertTrue($result['is_non_npwp_penalized']);
        $this->assertEquals(TaxMethod::GROSS_UP, $result['tax_method']);
        $this->assertGreaterThan(200_000, $result['pph21_amount']);
        $this->assertEquals($result['tax_allowance'], $result['pph21_amount']);
    }
}
