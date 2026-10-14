<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\PayrollLineCategory;
use App\Enums\PayrollLineType;
use App\Enums\PtkpStatus;
use App\Enums\TaxMethod;
use App\Enums\TaxRecipientType;
use App\Livewire\CompanyProfile\Index;
use App\Livewire\Payrolls\ManageItem as PayrollManageItem;
use App\Livewire\Payrolls\Show as PayrollShow;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollTaxIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function createAdminUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator', 'description' => 'Admin'],
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_payroll_period_creation_calculates_gross_pph21_for_employee(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $dept = Department::firstOrCreate(['name' => 'Finance Dept'], ['description' => 'Finance']);
        $pos = Position::firstOrCreate(['name' => 'Accountant'], ['description' => 'Accountant']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Siti Aminah',
            'join_date' => Carbon::now()->subMonths(10),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 10_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
            'has_npwp' => true,
            'npwp_number' => '12.345.678.9-012.000',
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        // Create period
        $period = $service->createPeriod([
            'month' => 5,
            'year' => 2026,
            'cutoff_start_date' => '2026-04-26',
            'cutoff_end_date' => '2026-05-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        // Verification of snapshot fields
        $this->assertEquals(TaxMethod::GROSS, $item->tax_method);
        $this->assertEquals(PtkpStatus::TK_0, $item->ptkp_status);
        $this->assertEquals('A', $item->ter_category);
        $this->assertEquals(0.02, (float) $item->ter_rate); // TER A for 10M is 2%
        $this->assertEquals(10_000_000, (float) $item->taxable_gross);
        $this->assertEquals(0, (float) $item->tax_allowance);
        $this->assertEquals(200_000, (float) $item->pph21_amount);

        // Line deduction verification
        $pphLine = $item->lines()->where('category', PayrollLineCategory::TAX)->where('type', PayrollLineType::DEDUCTION)->first();
        $this->assertNotNull($pphLine);
        $this->assertEquals('PPh 21', $pphLine->name);
        $this->assertEquals(200_000, (float) $pphLine->amount);

        // Net salary should be 10M - 200k = 9.8M
        $this->assertEquals(9_800_000, (float) $item->net_salary);
    }

    public function test_payroll_period_creation_keeps_out_of_scope_intern_without_calculating_pph21(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => true,
        ]);

        $department = Department::firstOrCreate(['name' => 'Demo Internship Dept']);
        $position = Position::firstOrCreate(['name' => 'Demo Intern']);
        $employee = Employee::create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'full_name' => 'Intern Outside Tax Scope',
            'join_date' => Carbon::now()->subMonths(2),
            'employment_status' => EmploymentStatus::MAGANG,
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 3_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'tax_method' => TaxMethod::GROSS,
            'tax_recipient_type' => TaxRecipientType::OutsideScope,
        ]);
        $admin = $this->createAdminUser();

        $period = app(PayrollService::class)->createPeriod([
            'month' => 5,
            'year' => 2030,
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame(TaxRecipientType::OutsideScope, $item->tax_recipient_type);
        $this->assertDatabaseMissing('payroll_item_lines', [
            'payroll_item_id' => $item->id,
            'category' => PayrollLineCategory::TAX->value,
        ]);
        $this->assertDatabaseHas('payroll_item_lines', [
            'payroll_item_id' => $item->id,
            'category' => PayrollLineCategory::BPJS->value,
            'type' => PayrollLineType::DEDUCTION->value,
        ]);
    }

    public function test_payroll_period_creation_calculates_non_npwp_gross_pph21_with_penalty(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $dept = Department::firstOrCreate(['name' => 'IT Department'], ['description' => 'IT Dept']);
        $pos = Position::firstOrCreate(['name' => 'Software Engineer'], ['description' => 'SE Pos']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Jane Non-NPWP',
            'join_date' => Carbon::parse('2025-01-01'),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 10_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
            'has_npwp' => false,
            'npwp_number' => null,
            'nik' => null,
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        $period = $service->createPeriod([
            'month' => 5,
            'year' => 2026,
            'cutoff_start_date' => '2026-04-26',
            'cutoff_end_date' => '2026-05-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        // Non-NPWP without NIK: TER A 2.0% + 20% penalty = 2.4% (0.024)
        $this->assertEquals(0.024, (float) $item->ter_rate);
        $this->assertEquals(240_000, (float) $item->pph21_amount);

        // Deduction line verification
        $pphLine = $item->lines()->where('category', PayrollLineCategory::TAX)->where('type', PayrollLineType::DEDUCTION)->first();
        $this->assertNotNull($pphLine);
        $this->assertEquals(240_000, (float) $pphLine->amount);
        $this->assertStringContainsString('+ 20% Non-NPWP', $pphLine->notes);

        // Net salary should be 10M - 240k = 9.76M
        $this->assertEquals(9_760_000, (float) $item->net_salary);
    }

    public function test_payroll_period_creation_calculates_gross_up_tax_allowance_and_deduction(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $dept = Department::firstOrCreate(['name' => 'Engineering'], ['description' => 'Eng']);
        $pos = Position::firstOrCreate(['name' => 'Tech Lead'], ['description' => 'TL']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Ahmad Dani',
            'join_date' => Carbon::now()->subMonths(12),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 10_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS_UP,
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        $period = $service->createPeriod([
            'month' => 6,
            'year' => 2026,
            'cutoff_start_date' => '2026-05-26',
            'cutoff_end_date' => '2026-06-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        // Gross-Up verification: Allowance must equal PPh 21 deduction
        $this->assertEquals(TaxMethod::GROSS_UP, $item->tax_method);
        $this->assertGreaterThan(0, (float) $item->tax_allowance);
        $this->assertEquals((float) $item->tax_allowance, (float) $item->pph21_amount);

        // Net salary remains intact (basic salary 10,000,000)
        $this->assertEquals(10_000_000, (float) $item->net_salary);

        // Ensure both lines exist with category TAX
        $allowanceTaxLine = $item->lines()->where('category', PayrollLineCategory::TAX)->where('type', PayrollLineType::ALLOWANCE)->first();
        $deductionTaxLine = $item->lines()->where('category', PayrollLineCategory::TAX)->where('type', PayrollLineType::DEDUCTION)->first();

        $this->assertNotNull($allowanceTaxLine);
        $this->assertNotNull($deductionTaxLine);
        $this->assertEquals($allowanceTaxLine->amount, $deductionTaxLine->amount);
    }

    public function test_sync_item_lines_does_not_duplicate_tax_lines(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $dept = Department::firstOrCreate(['name' => 'HR Dept'], ['description' => 'HR']);
        $pos = Position::firstOrCreate(['name' => 'HR Manager'], ['description' => 'HRM']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Dewi Lestari',
            'join_date' => Carbon::now()->subMonths(5),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 8_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        $period = $service->createPeriod([
            'month' => 7,
            'year' => 2026,
            'cutoff_start_date' => '2026-06-26',
            'cutoff_end_date' => '2026-07-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertEquals(1, $item->lines()->where('category', PayrollLineCategory::TAX)->count());

        // First sync: add an allowance
        $service->syncItemLines($item, [
            ['name' => 'Tunjangan Transport', 'amount' => 500_000],
        ], [], $admin);

        $item->refresh();
        $this->assertEquals(1, $item->lines()->where('category', PayrollLineCategory::TAX)->count());

        // Second sync: add another allowance and save again
        $service->syncItemLines($item, [
            ['name' => 'Tunjangan Transport', 'amount' => 500_000],
            ['name' => 'Tunjangan Makan', 'amount' => 300_000],
        ], [], $admin);

        $item->refresh();
        $this->assertEquals(1, $item->lines()->where('category', PayrollLineCategory::TAX)->count(), 'PPh 21 tax line must not be duplicated on sync');
    }

    public function test_turning_off_global_pph21_clears_tax_lines_and_snapshot(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $dept = Department::firstOrCreate(['name' => 'Sales Dept'], ['description' => 'Sales']);
        $pos = Position::firstOrCreate(['name' => 'Sales Rep'], ['description' => 'SR']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Bambang Pamungkas',
            'join_date' => Carbon::now()->subMonths(4),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 9_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        $period = $service->createPeriod([
            'month' => 8,
            'year' => 2026,
            'cutoff_start_date' => '2026-07-26',
            'cutoff_end_date' => '2026-08-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertGreaterThan(0, (float) $item->pph21_amount);

        // Turn off PPh 21 globally
        $company->update(['enable_pph21' => false]);

        $service->recalculateItemTax($item, $company);
        $item->refresh();

        $this->assertEquals(0, (float) $item->pph21_amount);
        $this->assertEquals(0, (float) $item->taxable_gross);
        $this->assertEquals(0, $item->lines()->where('category', PayrollLineCategory::TAX)->count());
    }

    public function test_livewire_manage_item_recalculate_tax_action(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $dept = Department::firstOrCreate(['name' => 'Ops Dept'], ['description' => 'Ops']);
        $pos = Position::firstOrCreate(['name' => 'Ops Lead'], ['description' => 'OL']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Rina Nose',
            'join_date' => Carbon::now()->subMonths(2),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 12_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        $period = $service->createPeriod([
            'month' => 9,
            'year' => 2026,
            'cutoff_start_date' => '2026-08-26',
            'cutoff_end_date' => '2026-09-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();

        $this->actingAs($admin);

        Livewire::test(PayrollManageItem::class, ['period' => $period, 'item' => $item])
            ->call('recalculateTax')
            ->assertDispatched('swal:alert');

        $item->refresh();
        $this->assertEquals(1, $item->lines()->where('category', PayrollLineCategory::TAX)->count());
        $this->assertGreaterThan(0, (float) $item->pph21_amount);
    }

    public function test_livewire_show_recalculate_all_taxes_action(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $dept = Department::firstOrCreate(['name' => 'Marketing'], ['description' => 'Mkt']);
        $pos = Position::firstOrCreate(['name' => 'Marketing Specialist'], ['description' => 'MS']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Reza Rahadian',
            'join_date' => Carbon::now()->subMonths(8),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 15_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::K,
            'dependents_count' => 1,
            'ptkp_status' => PtkpStatus::K_1,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        $period = $service->createPeriod([
            'month' => 10,
            'year' => 2026,
            'cutoff_start_date' => '2026-09-26',
            'cutoff_end_date' => '2026-10-25',
        ], $admin);

        $this->actingAs($admin);

        Livewire::test(PayrollShow::class, ['period' => $period])
            ->call('recalculateAllTaxes')
            ->assertDispatched('swal:alert');

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertEquals(PtkpStatus::K_1, $item->ptkp_status);
        $this->assertEquals('B', $item->ter_category);
        $this->assertGreaterThan(0, (float) $item->pph21_amount);
    }

    public function test_admin_can_toggle_pph21_and_bpjs_switches_via_company_profile(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('name', 'PT Larashiz Global Corp')
            ->set('enable_pph21', false)
            ->set('include_bpjs_in_tax', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('company_profiles', [
            'name' => 'PT Larashiz Global Corp',
            'enable_pph21' => false,
            'include_bpjs_in_tax' => false,
        ]);

        Livewire::test(Index::class)
            ->set('enable_pph21', true)
            ->set('include_bpjs_in_tax', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('company_profiles', [
            'enable_pph21' => true,
            'include_bpjs_in_tax' => true,
        ]);
    }

    public function test_admin_can_view_payroll_show_page_without_lazy_loading_violation(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update(['enable_pph21' => true]);

        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'IT'], ['description' => 'IT']);
        $pos = Position::firstOrCreate(['name' => 'Dev'], ['description' => 'Dev']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Test Employee',
            'join_date' => Carbon::now()->subMonths(3),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 7_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 11,
            'year' => 2026,
            'cutoff_start_date' => '2026-10-26',
            'cutoff_end_date' => '2026-11-25',
        ], $admin);

        $response = $this->get(route('payrolls.show', $period));
        $response->assertOk();
    }

    public function test_bpjs_and_pph21_calculated_from_gross_salary_with_allowances(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => true,
            'bpjs_kes_emp_rate' => 0.0100,
            'bpjs_jht_emp_rate' => 0.0200,
            'bpjs_jp_emp_rate' => 0.0100,
        ]);

        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'Operations'], ['description' => 'Operations']);
        $pos = Position::firstOrCreate(['name' => 'Staff'], ['description' => 'Staff']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Santoso',
            'join_date' => Carbon::parse('2025-09-01'),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 6_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 3,
            'year' => 2026,
            'name' => 'Penggajian Maret 2026',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        // Test ManageItem component
        $component = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        // Add allowances: Bonus 100,000 + Makan 50,000 => Total allowances = 150,000
        // Gross Salary = 6,000,000 + 150,000 = 6,150,000
        $component->set('allowanceRows.0.name', 'Bonus')
            ->set('allowanceRows.0.amount', 100000)
            ->set('allowanceRows.1.name', 'Tunjangan Makan')
            ->set('allowanceRows.1.amount', 50000);

        // Verify Gross Salary used for BPJS calculations
        // BPJS Kes 1% of 6,150,000 = 61,500
        // BPJS JHT 2% of 6,150,000 = 123,000
        // BPJS JP 1% of 6,150,000 = 61,500
        $bpjsBreakdown = $component->get('bpjsBreakdown');
        $this->assertEquals(61500.0, (float) $bpjsBreakdown['employee']['bpjs_kes']);
        $this->assertEquals(123000.0, (float) $bpjsBreakdown['employee']['bpjs_jht']);
        $this->assertEquals(61500.0, (float) $bpjsBreakdown['employee']['bpjs_jp']);

        // Check deductions rows synced
        $deductions = collect($component->get('deductionRows'));
        $kesRow = $deductions->first(fn ($r) => str_starts_with(strtolower($r['name'] ?? ''), 'bpjs kesehatan'));
        $jhtRow = $deductions->first(fn ($r) => str_starts_with(strtolower($r['name'] ?? ''), 'bpjs jht'));
        $jpRow = $deductions->first(fn ($r) => str_starts_with(strtolower($r['name'] ?? ''), 'bpjs pensiun'));

        $this->assertNotNull($kesRow);
        $this->assertEquals(61500.0, (float) $kesRow['amount']);
        $this->assertNotNull($jhtRow);
        $this->assertEquals(123000.0, (float) $jhtRow['amount']);
        $this->assertNotNull($jpRow);
        $this->assertEquals(61500.0, (float) $jpRow['amount']);

        // Assert Tanggungan Perusahaan card and table render properly
        $component->assertSee('Tanggungan Perusahaan')
            ->assertSee('BPJS Kesehatan (4%)')
            ->assertSee('BPJS Ketenagakerjaan JHT (3.7%)')
            ->assertSee('BPJS Ketenagakerjaan JP (2%)')
            ->assertSee('BPJS Ketenagakerjaan JKK')
            ->assertSee('BPJS Ketenagakerjaan JKM (0.3%)')
            ->assertSee('Total Tanggungan Perusahaan:');

        // Save
        $component->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert', fn ($event, $params) => ($params[0]['type'] ?? '') === 'success');

        $item->refresh();
        $this->assertEquals(150000.0, (float) $item->total_allowances);

        $dbKes = $item->lines()->where('category', PayrollLineCategory::BPJS)->where('name', 'like', 'BPJS Kesehatan%')->first();
        $this->assertNotNull($dbKes);
        $this->assertEquals(61500.0, (float) $dbKes->amount);

        $dbJht = $item->lines()->where('category', PayrollLineCategory::BPJS)->where('name', 'like', 'BPJS JHT%')->first();
        $this->assertNotNull($dbJht);
        $this->assertEquals(123000.0, (float) $dbJht->amount);

        $dbJp = $item->lines()->where('category', PayrollLineCategory::BPJS)->where('name', 'like', 'BPJS Pensiun%')->first();
        $this->assertNotNull($dbJp);
        $this->assertEquals(61500.0, (float) $dbJp->amount);
    }

    public function test_toggle_off_deduction_and_company_contributions_in_manage_item(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => true,
        ]);

        $dept = Department::firstOrCreate(['name' => 'IT Dept'], ['description' => 'IT']);
        $pos = Position::firstOrCreate(['name' => 'Developer'], ['description' => 'Dev']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Toggle',
            'join_date' => Carbon::now()->subMonths(6),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 6_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        $period = $service->createPeriod([
            'month' => 6,
            'year' => 2026,
            'cutoff_start_date' => '2026-05-26',
            'cutoff_end_date' => '2026-06-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        $component = Livewire::actingAs($admin)
            ->test(PayrollManageItem::class, ['period' => $period, 'item' => $item]);

        $initialDeductions = collect($component->get('deductionRows'));
        $pphIndex = $initialDeductions->search(fn ($r) => ($r['category'] ?? '') === 'tax' || strcasecmp(trim($r['name'] ?? ''), 'pph 21') === 0);
        $kesIndex = $initialDeductions->search(fn ($r) => str_starts_with(strtolower($r['name'] ?? ''), 'bpjs kesehatan'));

        $this->assertNotFalse($pphIndex);
        $this->assertNotFalse($kesIndex);

        $initialTotalDeductions = (float) $component->get('totalDeductions');
        $pphAmount = (float) $initialDeductions[$pphIndex]['amount'];
        $kesAmount = (float) $initialDeductions[$kesIndex]['amount'];

        // 1. Toggle off PPh 21
        $component->call('toggleDeductionEnabled', $pphIndex);
        $afterPphToggleDeductions = (float) $component->get('totalDeductions');
        $this->assertEquals($initialTotalDeductions - $pphAmount, $afterPphToggleDeductions);

        // 2. Toggle off BPJS Kes employee
        $component->call('toggleDeductionEnabled', $kesIndex);
        $afterKesToggleDeductions = (float) $component->get('totalDeductions');
        $this->assertEquals($initialTotalDeductions - $pphAmount - $kesAmount, $afterKesToggleDeductions);

        // 3. Toggle off company BPJS Kes
        $initialCompContrib = (float) $component->get('totalCompanyContributions');
        $compBreakdown = $component->get('bpjsBreakdown');
        $compKesAmount = (float) ($compBreakdown['company']['bpjs_kes'] ?? 0);
        $this->assertGreaterThan(0, $compKesAmount);

        $component->call('toggleCompanyBpjs', 'bpjs_kes');
        $afterCompToggle = (float) $component->get('totalCompanyContributions');
        $this->assertEquals($initialCompContrib - $compKesAmount, $afterCompToggle);

        // 4. Save
        $component->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $item->refresh();
        $this->assertEquals(0, (float) $item->pph21_amount);

        $dbPphLine = $item->lines()->where('category', PayrollLineCategory::TAX)->first();
        $this->assertNotNull($dbPphLine);
        $this->assertEquals(0.0, (float) $dbPphLine->amount);
        $this->assertEquals('Nonaktif', $dbPphLine->notes);

        $dbKesLine = $item->lines()->where('category', PayrollLineCategory::BPJS)->where('name', 'like', 'BPJS Kesehatan%')->first();
        $this->assertNotNull($dbKesLine);
        $this->assertEquals(0.0, (float) $dbKesLine->amount);
        $this->assertEquals('Nonaktif', $dbKesLine->notes);

        // Verify company BPJS state persisted in notes
        $this->assertNotNull($item->notes);
        $this->assertStringContainsString('disabled_company_bpjs', $item->notes);

        // 5. Re-mount component and verify disabled state is restored
        $reloaded = Livewire::actingAs($admin)
            ->test(PayrollManageItem::class, ['period' => $period, 'item' => $item]);

        $this->assertFalse($reloaded->get('companyBpjsStatus')['bpjs_kes']);

        $reloadedDeductions = collect($reloaded->get('deductionRows'));
        $reloadedPph = $reloadedDeductions->first(fn ($r) => ($r['category'] ?? '') === 'tax' || strcasecmp(trim($r['name'] ?? ''), 'pph 21') === 0);
        $reloadedKes = $reloadedDeductions->first(fn ($r) => str_starts_with(strtolower($r['name'] ?? ''), 'bpjs kesehatan'));

        $this->assertFalse($reloadedPph['is_enabled']);
        $this->assertFalse($reloadedKes['is_enabled']);

        // 6. Re-enable them
        $rePphIndex = $reloadedDeductions->search(fn ($r) => ($r['category'] ?? '') === 'tax' || strcasecmp(trim($r['name'] ?? ''), 'pph 21') === 0);
        $reKesIndex = $reloadedDeductions->search(fn ($r) => str_starts_with(strtolower($r['name'] ?? ''), 'bpjs kesehatan'));

        $reloaded->call('toggleDeductionEnabled', $rePphIndex);
        $reloaded->call('toggleDeductionEnabled', $reKesIndex);
        $reloaded->call('toggleCompanyBpjs', 'bpjs_kes');

        $reloaded->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $item->refresh();
        $this->assertGreaterThan(0, (float) $item->pph21_amount);

        $restoredKesLine = $item->lines()->where('category', PayrollLineCategory::BPJS)->where('name', 'like', 'BPJS Kesehatan%')->first();
        $this->assertGreaterThan(0, (float) $restoredKesLine->amount);
        $this->assertNotEquals('Nonaktif', $restoredKesLine->notes);
    }

    public function test_master_header_toggle_disables_and_enables_tax_and_bpjs(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => true,
        ]);

        $dept = Department::firstOrCreate(['name' => 'IT Dept'], ['description' => 'IT']);
        $pos = Position::firstOrCreate(['name' => 'Dev'], ['description' => 'Dev']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Master Toggle',
            'join_date' => Carbon::now()->subMonths(6),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 8_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        $period = $service->createPeriod([
            'month' => 6,
            'year' => 2026,
            'cutoff_start_date' => '2026-05-26',
            'cutoff_end_date' => '2026-06-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        $component = Livewire::actingAs($admin)
            ->test(PayrollManageItem::class, ['period' => $period, 'item' => $item]);

        // 1. Initial State Assertions
        $this->assertTrue($component->get('hasTaxOrBpjsRows'));
        $this->assertTrue($component->get('hasAnyTaxOrBpjsEnabled'));
        $this->assertTrue($component->get('allTaxAndBpjsEnabled'));
        $this->assertTrue($component->get('hasAnyCompanyBpjsEnabled'));
        $this->assertTrue($component->get('allCompanyBpjsEnabled'));

        $initialTotalDeductions = (float) $component->get('totalDeductions');
        $this->assertGreaterThan(0, $initialTotalDeductions);

        // 2. Master Toggle Off Deductions
        $component->call('toggleAllTaxAndBpjs');
        $this->assertFalse($component->get('hasAnyTaxOrBpjsEnabled'));
        $this->assertFalse($component->get('allTaxAndBpjsEnabled'));
        $this->assertEquals(0, (float) $component->get('totalDeductions'));
        $this->assertEquals(8_000_000, (float) $component->get('estimatedNetSalary'));

        // 3. Turn ON one single row
        $deductions = collect($component->get('deductionRows'));
        $kesIdx = $deductions->search(fn ($r) => str_starts_with(strtolower($r['name'] ?? ''), 'bpjs kesehatan'));
        $this->assertNotFalse($kesIdx);

        $component->call('toggleDeductionEnabled', $kesIdx);
        $this->assertTrue($component->get('hasAnyTaxOrBpjsEnabled'));
        $this->assertFalse($component->get('allTaxAndBpjsEnabled'));
        $this->assertGreaterThan(0, (float) $component->get('totalDeductions'));

        // 4. Master Toggle Off Again (turning off the single active one)
        $component->call('toggleAllTaxAndBpjs');
        $this->assertFalse($component->get('hasAnyTaxOrBpjsEnabled'));
        $this->assertFalse($component->get('allTaxAndBpjsEnabled'));
        $this->assertEquals(0, (float) $component->get('totalDeductions'));

        // 5. Master Toggle On (turning all back on)
        $component->call('toggleAllTaxAndBpjs');
        $this->assertTrue($component->get('hasAnyTaxOrBpjsEnabled'));
        $this->assertTrue($component->get('allTaxAndBpjsEnabled'));
        $this->assertEquals($initialTotalDeductions, (float) $component->get('totalDeductions'));

        // 6. Master Toggle Company BPJS
        $initialCompanyContrib = (float) $component->get('totalCompanyContributions');
        $this->assertGreaterThan(0, $initialCompanyContrib);

        $component->call('toggleAllCompanyBpjs');
        $this->assertFalse($component->get('hasAnyCompanyBpjsEnabled'));
        $this->assertFalse($component->get('allCompanyBpjsEnabled'));
        $this->assertEquals(0, (float) $component->get('totalCompanyContributions'));

        // Turn one company BPJS back on
        $component->call('toggleCompanyBpjs', 'bpjs_kes');
        $this->assertTrue($component->get('hasAnyCompanyBpjsEnabled'));
        $this->assertFalse($component->get('allCompanyBpjsEnabled'));
        $this->assertGreaterThan(0, (float) $component->get('totalCompanyContributions'));

        // Toggle all off from master
        $component->call('toggleAllCompanyBpjs');
        $this->assertFalse($component->get('hasAnyCompanyBpjsEnabled'));
        $this->assertEquals(0, (float) $component->get('totalCompanyContributions'));

        // Toggle all back on from master
        $component->call('toggleAllCompanyBpjs');
        $this->assertTrue($component->get('hasAnyCompanyBpjsEnabled'));
        $this->assertTrue($component->get('allCompanyBpjsEnabled'));
        $this->assertEquals($initialCompanyContrib, (float) $component->get('totalCompanyContributions'));
    }

    public function test_show_calculation_detail_for_ter_scheme_explains_rate_and_tier(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => true,
        ]);

        $dept = Department::firstOrCreate(['name' => 'IT Dept'], ['description' => 'IT']);
        $pos = Position::firstOrCreate(['name' => 'Programmer'], ['description' => 'Dev']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Dede Andri',
            'join_date' => Carbon::now()->subMonths(12),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 12_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
            'has_npwp' => true,
        ]);

        $payrollService = app(PayrollService::class);
        $period = $payrollService->createPeriod([
            'month' => 3,
            'year' => 2026,
            'cutoff_start_date' => '2026-02-26',
            'cutoff_end_date' => '2026-03-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->firstOrFail();

        $component = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        // Call showCalculationDetail('ter_scheme')
        $component->call('showCalculationDetail', 'ter_scheme');
        $component->assertDispatched('open-modal', ['id' => 'calcDetailModal']);

        $detail = $component->get('calculationDetail');
        $this->assertNotNull($detail);
        $this->assertEquals('Dasar Penentuan Skema Tarif (PMK 168/2023)', $detail['title']);

        $rows = collect($detail['rows']);
        $tierRow = $rows->first(fn ($r) => str_contains($r['component'], 'Lapisan TER A'));
        $this->assertNotNull($tierRow);
        $this->assertStringContainsString('Tier 15', $tierRow['component']);
        $this->assertEquals('5,00%', $tierRow['amount']);

        // Check summary explanation mentions Tier 14 and Tier 15 and employer insurance
        $this->assertStringContainsString('Tier 14', $detail['summary_text']);
        $this->assertStringContainsString('Tier 15', $detail['summary_text']);
        $this->assertStringContainsString('PMK No. 168/2023', $detail['summary_text']);

        // Also verify pph21 detail shows Tier 15
        $component->call('showCalculationDetail', 'pph21');
        $pphDetail = $component->get('calculationDetail');
        $pphRows = collect($pphDetail['rows']);
        $pphTerRow = $pphRows->first(fn ($r) => str_contains($r['component'], 'TER Kategori A'));
        $this->assertNotNull($pphTerRow);
        $this->assertStringContainsString('Tier 15', $pphTerRow['component']);
    }

    public function test_save_persists_valid_components_and_skips_empty_rows(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'IT Dept'], ['description' => 'IT']);
        $pos = Position::firstOrCreate(['name' => 'Developer'], ['description' => 'Developer']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Santoso',
            'join_date' => Carbon::now()->subMonths(12),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 10_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 4,
            'year' => 2026,
            'name' => 'Penggajian Periode April 2026',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        $component = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        // 1. Initial state: trailing empty rows exist in memory, but NO empty rows in DB
        $this->assertDatabaseMissing('payroll_item_lines', [
            'payroll_item_id' => $item->id,
            'name' => '',
        ]);

        // 2. Typing name & amount in memory: does NOT write to DB before clicking save (ensuring high performance)
        $component->set('allowanceRows.0.name', 'Tunjangan Makan')
            ->set('allowanceRows.0.amount', 500000);

        $this->assertDatabaseMissing('payroll_item_lines', [
            'payroll_item_id' => $item->id,
            'name' => 'Tunjangan Makan',
        ]);

        // 3. User clicks "Simpan Komponen Gaji": now valid rows are persisted, empty trailing rows are skipped
        $component->call('save')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('payroll_item_lines', [
            'payroll_item_id' => $item->id,
            'name' => 'Tunjangan Makan',
            'amount' => 500000,
        ]);

        $item->refresh();
        $this->assertEquals(500000.0, (float) $item->total_allowances);

        // Verify trailing empty row exists in memory for typing, but NOT persisted in DB
        $rows = $component->get('allowanceRows');
        $this->assertGreaterThanOrEqual(2, count($rows));
        $this->assertEquals('', $rows[1]['name']);
        $this->assertDatabaseMissing('payroll_item_lines', [
            'payroll_item_id' => $item->id,
            'amount' => 0,
            'type' => PayrollLineType::ALLOWANCE,
        ]);

        // 4. Modifying existing allowance in memory, then calling save updates DB
        $component->set('allowanceRows.0.amount', 750000)
            ->call('save');

        $this->assertDatabaseHas('payroll_item_lines', [
            'payroll_item_id' => $item->id,
            'name' => 'Tunjangan Makan',
            'amount' => 750000,
        ]);
        $item->refresh();
        $this->assertEquals(750000.0, (float) $item->total_allowances);

        // 5. Add custom deduction row and save
        $dedRows = $component->get('deductionRows');
        $emptyDedIdx = collect($dedRows)->search(fn ($r) => empty($r['is_system']) && empty($r['name']));
        $this->assertNotFalse($emptyDedIdx);

        $component->set("deductionRows.{$emptyDedIdx}.name", 'Potongan Koperasi')
            ->set("deductionRows.{$emptyDedIdx}.amount", 200000)
            ->call('save');

        $this->assertDatabaseHas('payroll_item_lines', [
            'payroll_item_id' => $item->id,
            'name' => 'Potongan Koperasi',
            'amount' => 200000,
        ]);

        // 6. Clearing the allowance and saving removes it from DB
        $component->set('allowanceRows.0.amount', 0)
            ->call('save');

        $this->assertDatabaseMissing('payroll_item_lines', [
            'payroll_item_id' => $item->id,
            'name' => 'Tunjangan Makan',
        ]);
        $item->refresh();
        $this->assertEquals(0.0, (float) $item->total_allowances);
    }

    public function test_december_progressive_tax_calculation_details_modal(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => true,
            'include_bpjs_in_tax' => false,
        ]);

        $dept = Department::firstOrCreate(['name' => 'Accounting'], ['description' => 'Acc']);
        $pos = Position::firstOrCreate(['name' => 'Tax Officer'], ['description' => 'TO']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Siti Nurhaliza',
            'join_date' => Carbon::now()->subMonths(12),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 12_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
            'npwp_number' => '12.345.678.9-012.000',
        ]);

        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        // Create December payroll period
        $period = $service->createPeriod([
            'month' => 12,
            'year' => 2026,
            'cutoff_start_date' => '2026-11-26',
            'cutoff_end_date' => '2026-12-25',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->actingAs($admin);

        // Test showCalculationDetail('ter_scheme')
        $test = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ])
            ->call('showCalculationDetail', 'ter_scheme');

        $calc = $test->get('calculationDetail');
        $this->assertNotNull($calc);
        $this->assertEquals('Dasar Skema Tarif PPh 21 (Masa Desember - UU HPP)', $calc['title']);
        $this->assertNotEmpty($calc['rows']);
        $this->assertStringContainsString('Pasal 17', $calc['summary_text']);

        // Check required progressive reconciliation rows
        $components = collect($calc['rows'])->pluck('component')->all();
        $this->assertContains('Skema Pajak Masa Terakhir', $components);
        $this->assertContains('Status PTKP Karyawan', $components);
        $this->assertContains('Total Penghasilan Bruto Setahun', $components);
        $this->assertContains('Pengurang: Biaya Jabatan Setahun', $components);
        $this->assertContains('Penghasilan Neto Setahun', $components);
        $this->assertContains('Penghasilan Kena Pajak (PKP)', $components);
        $this->assertContains('Total PPh 21 Terutang Setahun', $components);

        // Test showCalculationDetail('pph21')
        $test->call('showCalculationDetail', 'pph21');
        $calcPph = $test->get('calculationDetail');
        $this->assertNotNull($calcPph);
        $this->assertEquals('Rincian Perhitungan PPh 21 (Masa Desember)', $calcPph['title']);
        $this->assertNotEmpty($calcPph['rows']);
    }
}
