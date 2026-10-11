<?php

namespace Tests\Feature;

use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\InstallmentStatus;
use App\Enums\MaritalStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayrollLineCategory;
use App\Enums\PayrollLineType;
use App\Enums\PayrollPeriodStatus;
use App\Enums\PtkpStatus;
use App\Enums\RepaymentScheme;
use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentCategory;
use App\Enums\SalaryComponentType;
use App\Enums\TaxMethod;
use App\Livewire\CompanyProfile\Index as CompanyProfileIndex;
use App\Livewire\Payrolls\Index as PayrollIndex;
use App\Livewire\Payrolls\ManageItem as PayrollManageItem;
use App\Livewire\Payrolls\Show;
use App\Livewire\Payrolls\Show as PayrollShow;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\CashAdvancePayment;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use DatabaseTransactions;

    protected function createDirectorUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'direktur'],
            ['display_name' => 'Direktur', 'description' => 'Director'],
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('payrolls.index'))->assertRedirect(route('login'));
        $this->get(route('company-profile.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_update_company_profile(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin);

        Livewire::test(CompanyProfileIndex::class)
            ->set('name', 'PT Larashiz Digital Indonesia')
            ->set('phone', '081234567890')
            ->set('email', 'corporate@larashiz.com')
            ->set('address', 'Jl. Merdeka No. 45, Jakarta')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('company_profiles', [
            'name' => 'PT Larashiz Digital Indonesia',
            'phone' => '081234567890',
            'email' => 'corporate@larashiz.com',
        ]);
    }

    public function test_admin_can_create_payroll_period_and_pulls_active_employees_and_cash_advance(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'IT Department'], ['description' => 'IT Dept']);
        $pos = Position::firstOrCreate(['name' => 'Software Engineer'], ['description' => 'SE Pos']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Santoso',
            'join_date' => Carbon::now()->subMonths(6),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 6000000,
        ]);

        // Create an active cash advance with an installment due this month
        $currentMonth = (int) Carbon::now()->month;
        $currentYear = (int) Carbon::now()->year;

        $advance = CashAdvance::create([
            'reference_number' => 'KB-TEST-001',
            'employee_id' => $employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::now()->startOfMonth()->toDateString(),
            'reason' => 'Keperluan mendadak',
            'status' => 'active',
            'submitted_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => Carbon::now(),
        ]);

        $installment1 = CashAdvanceInstallment::create([
            'cash_advance_id' => $advance->id,
            'installment_number' => 1,
            'due_date' => Carbon::now()->startOfMonth()->addDays(24)->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        // Create payroll period via Livewire
        Livewire::test(PayrollIndex::class)
            ->set('newMonth', $currentMonth)
            ->set('newYear', $currentYear)
            ->set('newName', "Penggajian Bulan {$currentMonth} {$currentYear}")
            ->call('storePeriod')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $period = PayrollPeriod::where('month', $currentMonth)->where('year', $currentYear)->first();
        $this->assertNotNull($period);
        $this->assertEquals(PayrollPeriodStatus::DRAFT, $period->status);
        $expectedRef = 'PR-'.$currentYear.str_pad((string) $currentMonth, 2, '0', STR_PAD_LEFT);
        $this->assertEquals($expectedRef, $period->reference_number);

        // Verify the employee item was created
        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(6000000, (float) $item->basic_salary);

        // Verify the cash advance deduction line was automatically created!
        $advanceLine = $item->lines()->where('cash_advance_installment_id', $installment1->id)->first();
        $this->assertNotNull($advanceLine);
        $this->assertEquals(500000, (float) $advanceLine->amount);
        $this->assertEquals(5500000, (float) $item->net_salary);
    }

    public function test_admin_can_add_custom_allowance_and_submit_to_director(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'HR Dept'], ['description' => 'HR']);
        $pos = Position::firstOrCreate(['name' => 'HR Officer'], ['description' => 'HRO']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Rina Wijaya',
            'join_date' => Carbon::now()->subMonths(3),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 4500000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 11,
            'year' => 2026,
            'name' => 'Penggajian Periode November 2026',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        $initialNet = (float) $item->net_salary;

        // Add allowance component via repeater
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->call('openComponentModal', $item->id)
            ->set('allowanceRows', [
                ['name' => 'Bonus Kinerja', 'amount' => 500000],
            ])
            ->call('saveComponents')
            ->assertHasNoErrors();

        $item->refresh();
        $this->assertEquals($initialNet + 500000, (float) $item->net_salary);
        $this->assertEquals(500000, (float) $item->total_allowances);

        // Submit to director
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->call('submitForApproval')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $period->refresh();
        $this->assertEquals(PayrollPeriodStatus::SUBMITTED, $period->status);
    }

    public function test_director_can_approve_and_admin_can_mark_as_paid_triggering_kasbon_deduction(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'Finance Dept'], ['description' => 'Finance']);
        $pos = Position::firstOrCreate(['name' => 'Finance Officer'], ['description' => 'FO']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Siti Rahma',
            'join_date' => Carbon::now()->subMonths(10),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5000000,
        ]);

        $advance = CashAdvance::create([
            'reference_number' => 'KB-TEST-002',
            'employee_id' => $employee->id,
            'amount' => 600000,
            'remaining_amount' => 600000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 1,
            'monthly_installment' => 600000,
            'disbursement_date' => Carbon::now()->startOfMonth()->toDateString(),
            'reason' => 'Biaya medis',
            'status' => 'active',
            'submitted_by' => $admin->id,
            'approved_by' => $director->id,
            'approved_at' => Carbon::now(),
        ]);

        $installment = CashAdvanceInstallment::create([
            'cash_advance_id' => $advance->id,
            'installment_number' => 1,
            'due_date' => Carbon::now()->startOfMonth()->addDays(20)->toDateString(),
            'amount' => 600000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 12,
            'year' => 2026,
            'name' => 'Penggajian Periode Desember 2026',
        ], $admin);

        // Admin submits
        $service->submitPeriod($period, $admin);

        // Direktur approves
        $this->actingAs($director);
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->call('approvePeriod')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $period->refresh();
        $this->assertEquals(PayrollPeriodStatus::APPROVED, $period->status);

        // Admin records payment
        $this->actingAs($admin);
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->call('openPaymentModal')
            ->set('paymentDate', Carbon::now()->toDateString())
            ->set('paymentMethod', 'bank_transfer')
            ->call('processPayment')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $period->refresh();
        $this->assertEquals(PayrollPeriodStatus::PAID, $period->status);

        // Verify Cash Advance was automatically deducted and settled!
        $advance->refresh();
        $installment->refresh();

        $this->assertEquals(0, (float) $advance->remaining_amount);
        $this->assertEquals('paid', $advance->status->value);
        $this->assertEquals(InstallmentStatus::PAID, $installment->status);
        $this->assertEquals(600000, (float) $installment->paid_amount);

        // Verify CashAdvancePayment was created
        $payment = CashAdvancePayment::where('cash_advance_id', $advance->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(600000, (float) $payment->amount);
        $this->assertEquals(PaymentMethod::SALARY_DEDUCTION, $payment->payment_method);
    }

    public function test_director_can_reject_submitted_period(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'Marketing Dept'], ['description' => 'Marketing']);
        $pos = Position::firstOrCreate(['name' => 'Marketing Staff'], ['description' => 'Staff']);

        Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Joko Anwar',
            'join_date' => Carbon::now()->subMonths(5),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 4000000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 1,
            'year' => 2027,
            'name' => 'Penggajian Periode Januari 2027',
        ], $admin);

        $service->submitPeriod($period, $admin);

        $this->actingAs($director);
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->set('rejectionReason', 'Nominal tunjangan ada yang belum sesuai')
            ->call('rejectPeriod')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $period->refresh();
        $this->assertEquals(PayrollPeriodStatus::REJECTED, $period->status);
        $this->assertEquals('Nominal tunjangan ada yang belum sesuai', $period->rejection_reason);
    }

    public function test_can_view_printable_payslip(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'Legal Dept'], ['description' => 'Legal']);
        $pos = Position::firstOrCreate(['name' => 'Legal Officer'], ['description' => 'Legal']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Dewi Sartika',
            'join_date' => Carbon::now()->subMonths(8),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5500000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 2,
            'year' => 2027,
            'name' => 'Penggajian Periode Februari 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        $response = $this->get(route('payrolls.items.print', [$period, $item]));
        $response->assertOk();
        $response->assertSee('SLIP GAJI');
        $response->assertSee('Dewi Sartika');
        $response->assertSee($item->payslip_number);
    }

    public function test_can_apply_master_salary_components_in_modal(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'IT Dept'], ['description' => 'IT']);
        $pos = Position::firstOrCreate(['name' => 'Programmer'], ['description' => 'Dev']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Santoso',
            'join_date' => Carbon::now()->subMonths(6),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 6000000,
        ]);

        $allowanceComp = SalaryComponent::create([
            'name' => 'Tunjangan Transportasi',
            'code' => 'T-TRANS',
            'type' => SalaryComponentType::ALLOWANCE,
            'is_active' => true,
        ]);

        $deductionComp = SalaryComponent::create([
            'name' => 'Potongan BPJS',
            'code' => 'P-BPJS',
            'type' => SalaryComponentType::DEDUCTION,
            'is_active' => true,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 3,
            'year' => 2027,
            'name' => 'Penggajian Periode Maret 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        Livewire::test(PayrollShow::class, ['period' => $period])
            ->call('openComponentModal', $item->id)
            ->call('applyMasterAllowance', 0, $allowanceComp->id)
            ->call('applyMasterDeduction', 0, $deductionComp->id)
            ->assertSet('allowanceRows.0.name', 'Tunjangan Transportasi')
            ->assertSet('deductionRows.0.name', 'Potongan BPJS')
            ->set('allowanceRows.0.amount', 750000)
            ->set('deductionRows.0.amount', 150000)
            ->call('saveComponents')
            ->assertHasNoErrors();

        $item->refresh();
        $this->assertEquals(750000, (float) $item->total_allowances);
        $this->assertEquals(150000, (float) $item->total_deductions);
        $this->assertEquals(6000000 + 750000 - 150000, (float) $item->net_salary);
    }

    public function test_can_access_manage_payroll_item_page(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'Finance Dept'], ['description' => 'Finance']);
        $pos = Position::firstOrCreate(['name' => 'Finance Officer'], ['description' => 'FO']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Ahmad Fauzi',
            'join_date' => Carbon::now()->subMonths(4),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5200000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 4,
            'year' => 2027,
            'name' => 'Penggajian Periode April 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        $response = $this->get(route('payrolls.items.edit', [$period, $item]));
        $response->assertOk();
        $response->assertSee('Kelola Komponen Gaji');
        $response->assertSee('Ahmad Fauzi');
        $response->assertSee('Penghasilan');
        $response->assertSee('Potongan');
        $response->assertSee('Ringkasan Slip Gaji');
        $response->assertSee('Riwayat Data');
        $response->assertSee($admin->name);
        $this->assertEquals($admin->id, $period->created_by);
    }

    public function test_manage_payroll_item_livewire_component_interactivity_and_persistence(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'IT Dept'], ['description' => 'IT']);
        $pos = Position::firstOrCreate(['name' => 'Backend Engineer'], ['description' => 'BE']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Dimas Anggara',
            'join_date' => Carbon::now()->subMonths(7),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 7000000,
        ]);

        $allowanceComp = SalaryComponent::create([
            'name' => 'Tunjangan Operasional',
            'code' => 'T-OPS',
            'type' => SalaryComponentType::ALLOWANCE,
            'is_active' => true,
        ]);

        $deductionComp = SalaryComponent::create([
            'name' => 'Potongan Simpanan',
            'code' => 'P-SIMP',
            'type' => SalaryComponentType::DEDUCTION,
            'is_active' => true,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 5,
            'year' => 2027,
            'name' => 'Penggajian Periode Mei 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);

        $component = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        // Add an extra row
        $component->call('addAllowanceRow');
        $this->assertCount(2, $component->get('allowanceRows'));

        // Remove the row
        $component->call('removeAllowanceRow', 1);
        $this->assertCount(1, $component->get('allowanceRows'));

        // Apply master components
        $component->call('applyMasterAllowance', 0, $allowanceComp->id);
        $component->call('applyMasterDeduction', 0, $deductionComp->id);

        $this->assertEquals('Tunjangan Operasional', $component->get('allowanceRows.0.name'));
        $this->assertEquals('Potongan Simpanan', $component->get('deductionRows.0.name'));

        $component->set('allowanceRows.0.amount', 800000);
        $component->set('deductionRows.0.amount', 200000);

        // Check computed values
        $this->assertEquals(800000.0, (float) $component->get('totalAllowances'));
        $this->assertEquals(200000.0, (float) $component->get('totalDeductions'));
        $this->assertEquals(7600000.0, (float) $component->get('estimatedNetSalary'));

        // Save
        $component->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $item->refresh();
        $this->assertEquals(800000, (float) $item->total_allowances);
        $this->assertEquals(200000, (float) $item->total_deductions);
        $this->assertEquals(7600000, (float) $item->net_salary);

        // Verify period totals were also updated
        $period->refresh();
        $this->assertEquals(800000, (float) $period->total_allowances);
        $this->assertEquals(200000, (float) $period->total_deductions);
        $this->assertEquals(7600000, (float) $period->total_net_salary);
    }

    public function test_manage_payroll_item_modal_interactions(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'IT Dept'], ['description' => 'IT']);
        $pos = Position::firstOrCreate(['name' => 'Lead Dev'], ['description' => 'Lead']);

        $emp1 = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Karyawan Satu',
            'join_date' => Carbon::now()->subMonths(12),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 8000000,
        ]);

        $emp2 = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Karyawan Dua',
            'join_date' => Carbon::now()->subMonths(6),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 6000000,
        ]);

        $allowanceComp = SalaryComponent::create([
            'name' => 'Tunjangan Jabatan',
            'code' => 'T-JAB',
            'type' => SalaryComponentType::ALLOWANCE,
            'is_active' => true,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 6,
            'year' => 2027,
            'name' => 'Penggajian Periode Juni 2027',
        ], $admin);

        $item1 = $period->items()->where('employee_id', $emp1->id)->first();
        $item2 = $period->items()->where('employee_id', $emp2->id)->first();

        $component = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item1,
        ]);

        // Open Add Allowance Modal and Apply Master
        $component->call('openAddAllowanceModal')
            ->assertDispatched('open-modal', id: 'componentModal')
            ->call('applyModalMaster', $allowanceComp->id)
            ->assertSet('modalName', 'Tunjangan Jabatan')
            ->set('modalAmount', 1500000)
            ->call('saveModalComponent')
            ->assertDispatched('close-modal', id: 'componentModal');

        // Verify allowance added
        $this->assertEquals(1500000.0, (float) $component->get('totalAllowances'));

        // Test add deduction using nominal
        $component->call('openAddDeductionModal')
            ->set('modalName', 'Potongan Keterlambatan')
            ->set('modalAmount', 100000)
            ->call('saveModalComponent')
            ->assertDispatched('close-modal', id: 'componentModal');

        $this->assertEquals(100000.0, (float) $component->get('totalDeductions'));

        // Test add deduction using formatted nominal string (e.g. '250.000')
        $component->call('openAddDeductionModal')
            ->set('modalName', 'Potongan Koperasi')
            ->set('modalAmount', '250.000')
            ->call('saveModalComponent')
            ->assertDispatched('close-modal', id: 'componentModal');

        $this->assertEquals(350000.0, (float) $component->get('totalDeductions'));

        // Test apply percentage master deduction (BPJS Kesehatan 1% of basic salary 8,000,000 = 80,000)
        $bpjsComp = SalaryComponent::create([
            'name' => 'BPJS Kesehatan (1%)',
            'code' => 'BPJS-KES-TEST',
            'category' => SalaryComponentCategory::DEDUCTION,
            'type' => SalaryComponentType::DEDUCTION,
            'calculation_type' => SalaryCalculationType::PERCENTAGE_OF_BASIC_SALARY,
            'default_value' => 1.00,
            'is_active' => true,
        ]);

        $component->call('openAddDeductionModal')
            ->call('applyModalMaster', $bpjsComp->id)
            ->assertSet('modalName', 'BPJS Kesehatan (1%)')
            ->assertSet('modalCalcMode', 'percentage')
            ->assertSet('modalPercentage', 1.0)
            ->assertSet('modalAmount', 80000.0)
            ->call('saveModalComponent')
            ->assertDispatched('close-modal', id: 'componentModal');

        $this->assertEquals(430000.0, (float) $component->get('totalDeductions'));

        // Test add allowance using percentage calculation (e.g. 5% of basic salary 8,000,000 = 400,000)
        $component->call('openAddAllowanceModal')
            ->set('modalCalcMode', 'percentage')
            ->set('modalName', 'Bonus Kinerja 5%')
            ->set('modalPercentage', 5)
            ->assertSet('modalAmount', 400000.0)
            ->call('saveModalComponent')
            ->assertDispatched('close-modal', id: 'componentModal');

        $this->assertEquals(1900000.0, (float) $component->get('totalAllowances'));

        // Assert employee switcher rendered as Select2 and master datalists rendered for autocomplete
        $component->assertSeeHtml('id="select_employee" class="form-control select2"');
        $component->assertSeeHtml('id="master-allowance-list"');
        $component->assertSeeHtml('id="master-deduction-list"');
        $component->assertSee($emp1->full_name);
        $component->assertSee($emp2->full_name);

        // Test switchEmployee
        $response = $component->call('switchEmployee', $item2->id);
        $response->assertRedirect(route('payrolls.items.edit', [$period, $item2]));
    }

    public function test_manage_payroll_item_prevents_duplicate_master_components(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'HR Dept'], ['description' => 'HR']);
        $pos = Position::firstOrCreate(['name' => 'Staff'], ['description' => 'Staff']);

        $emp = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Duplicate Test',
            'join_date' => Carbon::now()->subMonths(6),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5000000,
        ]);

        $comp1 = SalaryComponent::create([
            'name' => 'Bonus & Insentif Project',
            'code' => 'BIP',
            'type' => SalaryComponentType::ALLOWANCE,
            'is_active' => true,
        ]);

        $comp2 = SalaryComponent::create([
            'name' => 'Tunjangan Transportasi',
            'code' => 'TJ-TRANS',
            'type' => SalaryComponentType::ALLOWANCE,
            'is_active' => true,
        ]);

        $deductComp1 = SalaryComponent::create([
            'name' => 'BPJS Kesehatan',
            'code' => 'BPJS-KES',
            'type' => SalaryComponentType::DEDUCTION,
            'is_active' => true,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 7,
            'year' => 2027,
            'name' => 'Penggajian Periode Juli 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $emp->id)->first();

        $livewire = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        // Initially both components are available
        $availableOptions = $livewire->instance()->getAvailableMasterComponents('allowance');
        $availableIds = collect($availableOptions)->pluck('id')->all();
        $this->assertContains($comp1->id, $availableIds);
        $this->assertContains($comp2->id, $availableIds);

        // Add comp1 (Bonus & Insentif Project)
        $livewire->call('openAddAllowanceModal')
            ->call('applyModalMaster', $comp1->id)
            ->set('modalAmount', 500000)
            ->call('saveModalComponent')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'componentModal');

        // Now comp1 should NOT be in available master components dropdown options
        $availableAfter = $livewire->instance()->getAvailableMasterComponents('allowance');
        $availableAfterIds = collect($availableAfter)->pluck('id')->all();
        $this->assertNotContains($comp1->id, $availableAfterIds);
        $this->assertContains($comp2->id, $availableAfterIds);

        // Try to add duplicate comp1 via componentId
        $livewire->call('openAddAllowanceModal')
            ->set('modalComponentId', $comp1->id)
            ->set('modalName', 'Bonus Lain')
            ->set('modalAmount', 200000)
            ->call('saveModalComponent')
            ->assertHasErrors(['modalComponentId']);

        // Try to add duplicate by exact name
        $livewire->call('openAddAllowanceModal')
            ->set('modalComponentId', null)
            ->set('modalName', 'Bonus & Insentif Project')
            ->set('modalAmount', 200000)
            ->call('saveModalComponent')
            ->assertHasErrors(['modalName']);

        // When editing the existing row (index 0), comp1 SHOULD still be in available options
        $optionsForEdit = $livewire->instance()->getAvailableMasterComponents('allowance', 0);
        $optionsForEditIds = collect($optionsForEdit)->pluck('id')->all();
        $this->assertContains($comp1->id, $optionsForEditIds);

        // And saving without changing should succeed without duplicate error
        $livewire->call('openEditAllowanceModal', 0)
            ->call('saveModalComponent')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'componentModal');

        // Test Deductions duplicate prevention
        $livewire->call('openAddDeductionModal')
            ->call('applyModalMaster', $deductComp1->id)
            ->set('modalAmount', 100000)
            ->call('saveModalComponent')
            ->assertHasNoErrors();

        $deductOptionsAfter = $livewire->instance()->getAvailableMasterComponents('deduction');
        $deductOptionsAfterIds = collect($deductOptionsAfter)->pluck('id')->all();
        $this->assertNotContains($deductComp1->id, $deductOptionsAfterIds);

        // Try duplicate deduction
        $livewire->call('openAddDeductionModal')
            ->set('modalComponentId', $deductComp1->id)
            ->set('modalName', 'BPJS KES')
            ->set('modalAmount', 50000)
            ->call('saveModalComponent')
            ->assertHasErrors(['modalComponentId']);
    }

    public function test_manage_payroll_item_inline_table_interactions_and_auto_append(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $dept = Department::firstOrCreate(['name' => 'Tech'], ['description' => 'Tech']);
        $pos = Position::firstOrCreate(['name' => 'Engineer'], ['description' => 'Eng']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Doni Inline Test',
            'join_date' => Carbon::now()->subMonths(5),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 10_000_000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'month' => 10,
            'year' => 2027,
            'name' => 'Penggajian Periode Oktober 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();

        $component = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        // Initially there should be 1 trailing empty row
        $this->assertCount(1, $component->get('allowanceRows'));
        $this->assertEquals('', $component->get('allowanceRows.0.name'));

        // Typing in row 0 automatically appends row 1!
        $component->set('allowanceRows.0.name', 'Tunjangan Transport')
            ->set('allowanceRows.0.amount', 500000);

        $this->assertCount(2, $component->get('allowanceRows'));
        $this->assertEquals('Tunjangan Transport', $component->get('allowanceRows.0.name'));
        $this->assertEquals('', $component->get('allowanceRows.1.name'));

        // Test toggle percentage mode on row 0
        $component->call('toggleCalcMode', 'allowance', 0);
        $this->assertEquals('percentage', $component->get('allowanceRows.0.calc_mode'));
        // 500,000 / 10,000,000 = 5%
        $this->assertEquals(5.0, (float) $component->get('allowanceRows.0.percentage'));

        // Changing percentage to 10% recalculates amount to 1,000,000 (10% of 10,000,000)
        $component->set('allowanceRows.0.percentage', 10);
        $this->assertEquals(1000000.0, (float) $component->get('allowanceRows.0.amount'));
        $this->assertEquals(1000000.0, (float) $component->get('totalAllowances'));

        // Type in row 1 -> row 2 is automatically appended!
        $component->set('allowanceRows.1.name', 'Bonus Kerajinan')
            ->set('allowanceRows.1.amount', 250000);

        $this->assertCount(3, $component->get('allowanceRows'));
        $this->assertEquals(1250000.0, (float) $component->get('totalAllowances'));

        // Test Deductions auto-append
        $this->assertCount(1, $component->get('deductionRows'));
        $component->set('deductionRows.0.name', 'Potongan Keterlambatan')
            ->set('deductionRows.0.amount', 50000);

        $this->assertCount(2, $component->get('deductionRows'));
        $this->assertEquals(50000.0, (float) $component->get('totalDeductions'));

        // Net salary = 10,000,000 + 1,250,000 - 50,000 = 11,200,000
        $this->assertEquals(11200000.0, (float) $component->get('estimatedNetSalary'));

        // Save
        $component->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $item->refresh();
        $this->assertEquals(1250000.0, (float) $item->total_allowances);
        $this->assertEquals(50000.0, (float) $item->total_deductions);
        $this->assertEquals(11200000.0, (float) $item->net_salary);
    }

    public function test_payroll_item_tracks_updater_when_modified_by_different_admin(): void
    {
        $admin1 = $this->createAdminUser();
        $admin2 = $this->createAdminUser();

        $dept = Department::firstOrCreate(['name' => 'Finance Dept'], ['description' => 'Finance']);
        $pos = Position::firstOrCreate(['name' => 'Financial Analyst'], ['description' => 'Finance']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Siti Nurhaliza',
            'join_date' => Carbon::now()->subMonths(10),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 8000000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2027,
            'month' => 5,
            'name' => 'Penggajian Periode Mei 2027',
        ], $admin1);

        $item = $period->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals($admin1->id, $period->created_by);

        // Admin 2 modifies the item
        $this->actingAs($admin2);

        Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ])
            ->set('allowanceRows.0.name', 'Tunjangan Transport')
            ->set('allowanceRows.0.amount', 300000)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $item->refresh();
        $period->refresh();

        $this->assertEquals($admin2->id, $item->updated_by);
        $this->assertEquals($admin2->id, $period->updated_by);

        // View displays Riwayat Data with both creator and updater
        $response = $this->get(route('payrolls.items.edit', [$period, $item]));
        $response->assertOk();
        $response->assertSee('Riwayat Data');
        $response->assertSee('Editor Terakhir');
        $response->assertSee($admin2->name);
        $response->assertSee($admin1->name);
    }

    public function test_approved_payroll_item_is_strictly_read_only_without_inputs_or_toggles(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'Ops Dept'], ['description' => 'Ops']);
        $pos = Position::firstOrCreate(['name' => 'Ops Lead'], ['description' => 'Ops']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Santoso',
            'join_date' => Carbon::now()->subMonths(12),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 9000000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2027,
            'month' => 6,
            'name' => 'Penggajian Periode Juni 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();

        // Director approves the payroll period
        $period->update([
            'status' => PayrollPeriodStatus::APPROVED,
            'approved_by' => $director->id,
            'approved_at' => now(),
        ]);
        $period->refresh();
        $this->assertFalse($period->status->isEditable());

        $this->actingAs($admin);

        // 1. HTTP View assertion: NO input tags in table, NO toggle labels, NO save button
        $response = $this->get(route('payrolls.items.edit', [$period, $item]));
        $response->assertOk();
        $response->assertSee('Mode Hanya Baca');
        $response->assertSee('Disetujui');
        $response->assertSee('ACC: '.$director->name);
        $response->assertDontSee('<input type="checkbox"', false);
        $response->assertDontSee('<label class="custom-switch"', false);
        $response->assertDontSee('class="form-control payroll-table-input"', false);
        $response->assertDontSee('Ketik nama tunjangan');
        $response->assertDontSee('Ketik nama potongan');

        // 2. Component execution assertion: mutations are completely blocked
        $lw = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        $allowanceCountBefore = count($lw->get('allowanceRows'));
        $lw->call('addAllowanceRow');
        $this->assertCount($allowanceCountBefore, $lw->get('allowanceRows'));

        $lw->call('save')
            ->assertDispatched('swal:alert', function ($name, $params = []) {
                $payload = isset($params[0]) && is_array($params[0]) ? $params[0] : (is_array($params) ? $params : []);

                return ($payload['title'] ?? '') === 'Tidak Dapat Diubah';
            });
    }

    public function test_payroll_show_has_manage_components_button_for_admin_and_director_and_no_slip_button(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'Support Dept'], ['description' => 'Support']);
        $pos = Position::firstOrCreate(['name' => 'Agent'], ['description' => 'Support Agent']);

        $employee = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Ratna Sari',
            'join_date' => Carbon::now()->subMonths(5),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5000000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2027,
            'month' => 8,
            'name' => 'Penggajian Periode Agustus 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->first();

        // 1. Check Admin view
        $this->actingAs($admin);
        $responseAdmin = $this->get(route('payrolls.show', $period));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Kelola Komponen Gaji');
        $responseAdmin->assertSee(route('payrolls.items.edit', [$period, $item]));
        $responseAdmin->assertDontSee('> Slip</button>', false);

        // 2. Check Director view
        $this->actingAs($director);
        $responseDirector = $this->get(route('payrolls.show', $period));
        $responseDirector->assertOk();
        $responseDirector->assertSee('Kelola Komponen Gaji');
        $responseDirector->assertSee(route('payrolls.items.edit', [$period, $item]));
        $responseDirector->assertDontSee('> Slip</button>', false);

        // Director can access manage components directly
        $editResponse = $this->get(route('payrolls.items.edit', [$period, $item]));
        $editResponse->assertOk();
        $editResponse->assertSee('Ratna Sari');
    }

    public function test_director_can_approve_individual_payroll_item(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'Sales Dept'], ['description' => 'Sales']);
        $pos = Position::firstOrCreate(['name' => 'Sales Rep'], ['description' => 'Sales Rep']);

        $employee1 = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Doni Siregar',
            'join_date' => Carbon::now()->subMonths(3),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5500000,
        ]);

        $employee2 = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Eka Prasetya',
            'join_date' => Carbon::now()->subMonths(2),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 6000000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2027,
            'month' => 9,
            'name' => 'Penggajian Periode September 2027',
        ], $admin);

        $item1 = $period->items()->where('employee_id', $employee1->id)->first();
        $item2 = $period->items()->where('employee_id', $employee2->id)->first();

        $this->actingAs($director);

        // Approve item 1 individually
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->call('approveItem', $item1->id)
            ->assertDispatched('swal:toast');

        $item1->refresh();
        $item2->refresh();
        $period->refresh();

        $this->assertTrue($item1->isApproved());
        $this->assertEquals($director->id, $item1->approved_by);
        $this->assertFalse($item2->isApproved());
        // Period is not approved yet because item 2 is still pending
        $this->assertNotEquals(PayrollPeriodStatus::APPROVED, $period->status);

        // Approve item 2 individually -> should automatically sync period status to approved
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->call('approveItem', $item2->id)
            ->assertDispatched('swal:toast');

        $item2->refresh();
        $period->refresh();

        $this->assertTrue($item2->isApproved());
        $this->assertEquals(PayrollPeriodStatus::APPROVED, $period->status);
        $this->assertEquals($director->id, $period->approved_by);
    }

    public function test_director_can_bulk_approve_and_bulk_reject_selected_payroll_items(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'Mkt Dept'], ['description' => 'Mkt']);
        $pos = Position::firstOrCreate(['name' => 'Mkt Staff'], ['description' => 'Mkt Staff']);

        $emp1 = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Fajar Pratama',
            'join_date' => Carbon::now()->subMonths(4),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5000000,
        ]);

        $emp2 = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Gita Gutawa',
            'join_date' => Carbon::now()->subMonths(4),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5200000,
        ]);

        $emp3 = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Hadi Sucipto',
            'join_date' => Carbon::now()->subMonths(4),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5300000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2027,
            'month' => 10,
            'name' => 'Penggajian Periode Oktober 2027',
        ], $admin);

        $item1 = $period->items()->where('employee_id', $emp1->id)->first();
        $item2 = $period->items()->where('employee_id', $emp2->id)->first();
        $item3 = $period->items()->where('employee_id', $emp3->id)->first();

        $this->actingAs($director);

        // Bulk approve items 1 & 2
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->set('selectedItems', [$item1->id, $item2->id])
            ->call('approveSelected')
            ->assertDispatched('swal:toast');

        $item1->refresh();
        $item2->refresh();
        $item3->refresh();

        $this->assertTrue($item1->isApproved());
        $this->assertTrue($item2->isApproved());
        $this->assertFalse($item3->isApproved());

        // Bulk reject item 3
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->set('selectedItems', [$item3->id])
            ->set('itemRejectionReason', 'Tolong sesuaikan lembur')
            ->set('isBulkReject', true)
            ->call('confirmRejectItem')
            ->assertDispatched('swal:toast');

        $item3->refresh();
        $this->assertTrue($item3->isRejected());
        $this->assertEquals('Tolong sesuaikan lembur', $item3->rejection_reason);

        // Test Approve All
        Livewire::test(PayrollShow::class, ['period' => $period])
            ->call('approveAll')
            ->assertDispatched('swal:alert');

        $item1->refresh();
        $item2->refresh();
        $item3->refresh();
        $period->refresh();

        $this->assertTrue($item1->isApproved());
        $this->assertTrue($item2->isApproved());
        $this->assertTrue($item3->isApproved());
        $this->assertEquals(PayrollPeriodStatus::APPROVED, $period->status);
    }

    public function test_director_can_approve_directly_from_manage_item_page(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'Finance Dept'], ['description' => 'Finance']);
        $pos = Position::firstOrCreate(['name' => 'Accountant'], ['description' => 'Acc']);

        $emp = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Indah Permata',
            'join_date' => Carbon::now()->subMonths(7),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 7000000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2027,
            'month' => 11,
            'name' => 'Penggajian Periode November 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $emp->id)->first();

        $this->actingAs($director);

        Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ])
            ->assertSee('Persetujuan Direktur')
            ->assertDontSee('btn-outline-danger')
            ->call('approveItem')
            ->assertDispatched('swal:alert');

        $item->refresh();
        $this->assertTrue($item->isApproved());
        $this->assertEquals($director->id, $item->approved_by);
    }

    public function test_director_can_reject_with_sweetalert_prompt_reason_from_manage_item_page(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'Tech Dept'], ['description' => 'Tech']);
        $pos = Position::firstOrCreate(['name' => 'Engineer'], ['description' => 'Eng']);

        $emp = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Santoso',
            'join_date' => Carbon::now()->subMonths(5),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 8000000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2027,
            'month' => 12,
            'name' => 'Penggajian Periode Desember 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $emp->id)->first();

        $this->actingAs($director);

        Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ])
            ->assertSee('Persetujuan Direktur')
            ->assertDontSee('rejectItemModal')
            ->call('confirmRejectItem', 'Tolong perbaiki tunjangan komunikasi')
            ->assertDispatched('swal:alert');

        $item->refresh();
        $this->assertTrue($item->isRejected());
        $this->assertEquals('Tolong perbaiki tunjangan komunikasi', $item->rejection_reason);
    }

    public function test_director_can_unapprove_item_from_manage_item_page(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'HR Dept'], ['description' => 'HR']);
        $pos = Position::firstOrCreate(['name' => 'HR Staff'], ['description' => 'Staff']);

        $emp = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Rina Wijaya',
            'join_date' => Carbon::now()->subMonths(8),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 6000000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2027,
            'month' => 10,
            'name' => 'Penggajian Periode Oktober 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $emp->id)->first();
        $service->approveItem($item, $director);

        $this->actingAs($director);

        Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ])
            ->assertSee('Disetujui (ACC)')
            ->call('unapproveItem')
            ->assertDispatched('swal:alert');

        $item->refresh();
        $this->assertTrue($item->isPending());
        $this->assertNull($item->approved_by);
        $this->assertNull($item->approved_at);
        $this->assertNull($item->rejection_reason);
    }

    public function test_approved_payroll_item_is_strictly_read_only(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $dept = Department::firstOrCreate(['name' => 'IT Dept'], ['description' => 'IT']);
        $pos = Position::firstOrCreate(['name' => 'Developer'], ['description' => 'Dev']);

        $emp = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Budi Santoso',
            'join_date' => Carbon::now()->subMonths(10),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 8_000_000,
        ]);

        $emp2 = Employee::create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Siti Aminah',
            'join_date' => Carbon::now()->subMonths(5),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 7_000_000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2027,
            'month' => 11,
            'name' => 'Penggajian Periode November 2027',
        ], $admin);

        $item = $period->items()->where('employee_id', $emp->id)->first();
        $service->approveItem($item, $director);

        $period->refresh();
        $item->refresh();

        // Period is still editable because second employee is still pending
        $this->assertTrue($period->status->isEditable());
        $this->assertTrue($item->isApproved());

        $this->actingAs($admin);

        $lw = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        // isEditable computed property should be false because item is approved
        $this->assertFalse($lw->get('isEditable'));

        // Input placeholders, save button, and toggle buttons should not be present
        $lw->assertDontSee('Ketik nama tunjangan atau pilih master...')
            ->assertDontSee('Ketik nama potongan atau pilih master...')
            ->assertDontSee('Simpan Komponen Gaji')
            ->assertSee('Slip Gaji Telah Disetujui')
            ->assertSee('Mode Hanya Baca');

        // Trying to add an allowance row should be blocked
        $initialRowCount = count($lw->get('allowanceRows'));
        $lw->call('addAllowanceRow');
        $this->assertCount($initialRowCount, $lw->get('allowanceRows'));
    }

    public function test_rejected_payroll_item_is_editable_during_submitted_period(): void
    {
        $admin = $this->createAdminUser();
        $director = $this->createDirectorUser();

        $emp = Employee::factory()->create([
            'join_date' => Carbon::now()->subMonths(5),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 8_500_000,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2026,
            'month' => 10,
            'name' => 'Penggajian Periode Oktober 2026',
        ], $admin);

        // Submit the period
        $service->submitPeriod($period, $admin);
        $this->assertEquals(PayrollPeriodStatus::SUBMITTED, $period->fresh()->status);

        $item = $period->items()->where('employee_id', $emp->id)->first();
        $service->rejectItem($item, 'rev: tambahkan lembur', $director);

        $period->refresh();
        $item->refresh();

        $this->assertTrue($item->isRejected());
        $this->assertEquals(PayrollPeriodStatus::SUBMITTED, $period->status);

        // Test as Admin
        $this->actingAs($admin);
        $lw = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        // isEditable should be TRUE for rejected item
        $this->assertTrue($lw->get('isEditable'));

        // Should see revision callout, inputs, save button, but NOT read-only banner
        $lw->assertSee('Catatan Revisi dari Direktur')
            ->assertSee('rev: tambahkan lembur')
            ->assertDontSee('Periode penggajian ini berstatus Menunggu Approval sehingga komponen gaji tidak dapat diubah kembali')
            ->assertSee('Simpan Komponen Gaji');

        // Test as Director
        $this->actingAs($director);
        $lwDir = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);
        $this->assertTrue($lwDir->get('isEditable'));
        $lwDir->assertSee('Catatan Revisi dari Direktur')
            ->assertSee('Simpan Komponen Gaji');
    }

    public function test_cash_advance_deduction_reflects_remaining_unpaid_balance_after_partial_payment(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $emp = Employee::factory()->create([
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5_000_000,
        ]);

        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-CA-PARTIAL',
            'name' => 'Payroll Test CA Partial',
            'year' => 2026,
            'month' => 10,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $emp->id,
            'payslip_number' => 'SLP-TEST-PARTIAL',
            'basic_salary' => 5000000,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'net_salary' => 5000000,
        ]);

        $ca = CashAdvance::create([
            'employee_id' => $emp->id,
            'reference_number' => 'KB-TEST-PARTIAL',
            'amount' => 1000000,
            'remaining_amount' => 600000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => '2026-10-01',
            'reason' => 'Test Partial',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $inst = CashAdvanceInstallment::create([
            'cash_advance_id' => $ca->id,
            'installment_number' => 1,
            'due_date' => '2026-10-15',
            'amount' => 500000,
            'paid_amount' => 200000,
            'status' => InstallmentStatus::PARTIALLY_PAID->value,
        ]);

        $line = PayrollItemLine::create([
            'payroll_item_id' => $item->id,
            'cash_advance_installment_id' => $inst->id,
            'name' => 'Potongan Kasbon (KB-TEST-PARTIAL Cicilan #1)',
            'type' => PayrollLineType::DEDUCTION,
            'category' => PayrollLineCategory::CASH_ADVANCE,
            'amount' => 500000,
        ]);

        $lw = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        $deductionRows = $lw->get('deductionRows');
        $caRow = collect($deductionRows)->firstWhere('cash_advance_installment_id', $inst->id);

        $this->assertNotNull($caRow);
        $this->assertEquals(300000, $caRow['amount']);

        $line->refresh();
        $this->assertEquals(300000, (float) $line->amount);
    }

    public function test_mark_as_paid_generates_unique_cash_advance_payment_numbers_without_collision(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $emp = Employee::factory()->create([
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5_000_000,
        ]);

        $ca = CashAdvance::create([
            'employee_id' => $emp->id,
            'reference_number' => 'KB-TEST-PAY-COLLISION',
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => '2026-10-01',
            'reason' => 'Test Collision Prevention',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $inst = CashAdvanceInstallment::create([
            'cash_advance_id' => $ca->id,
            'installment_number' => 1,
            'due_date' => '2026-10-15',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        // Pre-create an early payment with number PAY-KB-202610-0001
        CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-0001',
            'cash_advance_id' => $ca->id,
            'cash_advance_installment_id' => null,
            'payment_date' => '2026-10-05',
            'amount' => 100000,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            'notes' => 'Early partial payment',
            'recorded_by' => $admin->id,
        ]);

        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-PAY-SEQ',
            'name' => 'Payroll Test Sequence Collision',
            'year' => 2026,
            'month' => 10,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::APPROVED,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $emp->id,
            'payslip_number' => 'SLP-TEST-PAY-SEQ',
            'basic_salary' => 5000000,
            'total_allowances' => 0,
            'total_deductions' => 500000,
            'net_salary' => 4500000,
            'payment_status' => 'unpaid',
        ]);

        PayrollItemLine::create([
            'payroll_item_id' => $item->id,
            'cash_advance_installment_id' => $inst->id,
            'name' => 'Potongan Kasbon (KB-TEST-PAY-COLLISION Cicilan #1)',
            'type' => PayrollLineType::DEDUCTION,
            'category' => PayrollLineCategory::CASH_ADVANCE,
            'amount' => 500000,
        ]);

        // Mark payroll as paid - must NOT crash with duplicate key on PAY-KB-202610-0001
        $service = app(PayrollService::class);
        $updatedPeriod = $service->markAsPaid($period, [
            'payment_date' => '2026-10-31',
            'payment_method' => 'bank_transfer',
            'notes' => 'Paid via test',
        ], $admin);

        $this->assertEquals(PayrollPeriodStatus::PAID, $updatedPeriod->status);

        // Verify the payment number was generated using CashAdvanceService sequence (should NOT be 0001)
        $payrollPayment = CashAdvancePayment::where('cash_advance_installment_id', $inst->id)->first();
        $this->assertNotNull($payrollPayment);
        $this->assertNotEquals('PAY-KB-202610-0001', $payrollPayment->payment_number);
        $this->assertStringStartsWith('PAY-KB-202610-', $payrollPayment->payment_number);

        $inst->refresh();
        $this->assertEquals(InstallmentStatus::PAID, $inst->status);
        $this->assertEquals(500000, (float) $inst->paid_amount);
    }

    public function test_auto_injects_all_active_cash_advances_when_employee_has_multiple_advances(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $emp = Employee::factory()->create([
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 5_000_000,
        ]);

        $ca1 = CashAdvance::create([
            'employee_id' => $emp->id,
            'reference_number' => 'KB-MULTI-01',
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => '2026-10-01',
            'reason' => 'Advance 1',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $inst1 = CashAdvanceInstallment::create([
            'cash_advance_id' => $ca1->id,
            'installment_number' => 1,
            'due_date' => '2026-10-15',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $ca2 = CashAdvance::create([
            'employee_id' => $emp->id,
            'reference_number' => 'KB-MULTI-02',
            'amount' => 600000,
            'remaining_amount' => 600000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 3,
            'monthly_installment' => 200000,
            'disbursement_date' => '2026-10-01',
            'reason' => 'Advance 2',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $inst2 = CashAdvanceInstallment::create([
            'cash_advance_id' => $ca2->id,
            'installment_number' => 1,
            'due_date' => '2026-10-15',
            'amount' => 200000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-MULTI-ADV',
            'name' => 'Payroll Test Multi Adv',
            'year' => 2026,
            'month' => 10,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $emp->id,
            'payslip_number' => 'SLP-TEST-MULTI',
            'basic_salary' => 5000000,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'net_salary' => 5000000,
        ]);

        $lw = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        $deductionRows = $lw->get('deductionRows');
        $caRow1 = collect($deductionRows)->firstWhere('cash_advance_installment_id', $inst1->id);
        $caRow2 = collect($deductionRows)->firstWhere('cash_advance_installment_id', $inst2->id);

        $this->assertNotNull($caRow1, 'Advance 1 installment must be auto-injected');
        $this->assertEquals(500000, $caRow1['amount']);

        $this->assertNotNull($caRow2, 'Advance 2 installment must be auto-injected');
        $this->assertEquals(200000, $caRow2['amount']);
    }

    public function test_company_contributions_are_stored_in_payroll_lines_and_aggregated_on_header(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $emp = Employee::factory()->create([
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 10_000_000,
        ]);
        $emp->detail()->create([
            'tax_method' => TaxMethod::GROSS,
            'ptkp_status' => PtkpStatus::TK_0,
            'has_npwp' => true,
        ]);

        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-COMP-CONTRIB',
            'name' => 'Payroll Test Company Contribution',
            'year' => 2026,
            'month' => 10,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $emp->id,
            'payslip_number' => 'SLP-TEST-COMP-01',
            'basic_salary' => 10000000,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'net_salary' => 10000000,
        ]);

        $service = app(PayrollService::class);
        $company = CompanyProfile::getProfile();

        $service->recalculateItemTax($item, $company);
        $service->recalculateItem($item);
        $item->refresh();
        $period->refresh();

        // 1. Verify lines exist with type = company_contribution
        $compLines = $item->companyContributionLines()->get();
        $this->assertCount(5, $compLines);

        // 2. Verify total_company_contributions on item is > 0 and equals sum of lines
        $this->assertGreaterThan(0, (float) $item->total_company_contributions);
        $this->assertEquals((float) $compLines->sum('amount'), (float) $item->total_company_contributions);
        $this->assertGreaterThan((float) $item->basic_salary, (float) $item->cost_to_company);

        // 3. Verify total_company_contributions on period
        $this->assertEquals((float) $item->total_company_contributions, (float) $period->total_company_contributions);
    }

    public function test_individual_payroll_item_payment_records_confirming_user_and_displays_confirmer(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $emp = Employee::factory()->create([
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-PAYER-CONFIRM',
            'name' => 'Payroll Test Confirmer',
            'year' => 2026,
            'month' => 10,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::APPROVED,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $emp->id,
            'payslip_number' => 'SLP-TEST-PAYER-01',
            'basic_salary' => 5000000,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'net_salary' => 5000000,
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $service = app(PayrollService::class);
        $service->markItemAsPaid($item, [
            'payment_date' => '2026-10-31',
            'payment_method' => 'bank_transfer',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => $emp->full_name,
            'payment_notes' => 'Dibayar via M-Banking',
        ], $admin);

        $item->refresh();

        $this->assertEquals('paid', $item->payment_status);
        $this->assertEquals($admin->id, $item->paid_by);
        $this->assertEquals($admin->name, $item->payer->name);

        Livewire::test(Show::class, ['period' => $period])
            ->call('openPaymentDetailModal', $item->id)
            ->assertSeeHtml(e($admin->name));
    }

    public function test_individual_payroll_item_payment_can_be_edited(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-EDIT-PAYMENT',
            'name' => 'Payroll Oktober 2026 Test Edit',
            'year' => 2026,
            'month' => 10,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::APPROVED,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
        $emp = Employee::factory()->create();

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $emp->id,
            'payslip_number' => 'SLP-TEST-EDIT-001',
            'basic_salary' => 5000000,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'net_salary' => 5000000,
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $service = app(PayrollService::class);
        $service->markItemAsPaid($item, [
            'payment_date' => '2026-10-31',
            'payment_method' => 'bank_transfer',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => $emp->full_name,
            'payment_notes' => 'Catatan Awal',
        ], $admin);

        $item->refresh();
        $this->assertEquals('paid', $item->payment_status);

        Livewire::test(Show::class, ['period' => $period])
            ->call('openPaymentDetailModal', $item->id)
            ->call('openEditItemPaymentModal', $item->id)
            ->assertSet('isEditingPayment', true)
            ->assertSet('itemBankName', 'BCA')
            ->set('itemBankName', 'Mandiri')
            ->set('itemBankAccountNumber', '9876543210')
            ->set('itemPaymentNotes', 'Catatan Revisi')
            ->call('processItemPayment')
            ->assertDispatched('close-modal');

        $item->refresh();
        $this->assertEquals('Mandiri', $item->bank_name);
        $this->assertEquals('9876543210', $item->bank_account_number);
        $this->assertEquals('Catatan Revisi', $item->payment_notes);
    }

    public function test_prorated_salary_calculation_six_day_workweek(): void
    {
        $service = app(PayrollService::class);
        $admin = $this->createAdminUser();

        // October 2026 has 31 days, 4 Sundays (4, 11, 18, 25 Oct), so 27 working days (Mon-Sat)
        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-PRORATE-1',
            'name' => 'Payroll Test Prorate 1',
            'month' => 10,
            'year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
        ]);

        // Employee joined mid-month: 15 Oct 2026
        // 15 to 31 Oct has 17 calendar days, 2 Sundays (18 & 25 Oct), so 15 working days
        $empMid = Employee::factory()->active()->create([
            'basic_salary' => 6000000,
            'join_date' => '2026-10-15',
        ]);

        $resMid = $service->calculateProratedSalary($empMid, $period);
        $this->assertTrue($resMid['is_prorated']);
        $this->assertEquals(27, $resMid['total_working_days']);
        $this->assertEquals(15, $resMid['worked_days']);
        $this->assertEquals(3333333, (int) $resMid['basic_salary']);

        // Employee joined on 1st of month: full salary
        $empFull = Employee::factory()->active()->create([
            'basic_salary' => 6000000,
            'join_date' => '2026-10-01',
        ]);
        $resFull = $service->calculateProratedSalary($empFull, $period);
        $this->assertFalse($resFull['is_prorated']);
        $this->assertEquals(6000000, (int) $resFull['basic_salary']);

        // Employee joined in previous month: full salary
        $empOld = Employee::factory()->active()->create([
            'basic_salary' => 6000000,
            'join_date' => '2026-09-01',
        ]);
        $resOld = $service->calculateProratedSalary($empOld, $period);
        $this->assertFalse($resOld['is_prorated']);
        $this->assertEquals(6000000, (int) $resOld['basic_salary']);
    }

    public function test_sync_missing_employees_and_banner_detection(): void
    {
        $service = app(PayrollService::class);
        $admin = $this->createAdminUser();

        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-SYNC-MISSING',
            'name' => 'Payroll Test Sync Missing',
            'month' => 10,
            'year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
            'employee_count' => 0,
            'total_basic_salary' => 0,
        ]);

        // Create a new employee joined on 15 Oct 2026
        $newEmp = Employee::factory()->active()->withDetail()->create([
            'full_name' => 'Karyawan Baru Prorata',
            'basic_salary' => 6000000,
            'join_date' => '2026-10-15',
        ]);

        $this->actingAs($admin);

        // Test Livewire Show detects the missing employee
        $test = Livewire::test(Show::class, ['period' => $period])
            ->assertSee('Karyawan Baru Terdeteksi')
            ->assertSee('Karyawan Baru Prorata')
            ->assertSee('15 dari 27 hari kerja')
            ->call('syncMissingEmployees')
            ->assertDispatched('swal:alert');

        // Verify the item was created in DB with prorated salary
        $item = $period->items()->where('employee_id', $newEmp->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(3333333, (int) $item->basic_salary);
        $this->assertStringContainsString('15 dari 27 hari kerja', $item->notes);

        // After syncing, banner should no longer appear
        Livewire::test(Show::class, ['period' => $period->fresh()])
            ->assertDontSee('Karyawan Baru Terdeteksi');

        // Period employee count and basic salary should be updated
        $period->refresh();
        $this->assertEquals(1, $period->employee_count);
        $this->assertEquals(3333333, (int) $period->total_basic_salary);
    }

    public function test_handles_soft_deleted_employee_gracefully_in_payroll_show(): void
    {
        $admin = $this->createAdminUser();
        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-SOFT-DEL',
            'name' => 'Payroll Test Soft Del',
            'month' => 10,
            'year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
            'employee_count' => 0,
            'total_basic_salary' => 0,
        ]);

        $emp = Employee::factory()->active()->withDetail()->create([
            'full_name' => 'Karyawan Akan Dihapus',
            'basic_salary' => 5000000,
        ]);

        $service = app(PayrollService::class);
        $item = $service->addEmployeeToPeriod($period, $emp, $admin);

        // Soft delete the employee
        $emp->delete();
        $this->assertSoftDeleted('employees', ['id' => $emp->id]);

        $this->actingAs($admin);

        // Livewire show should render without error and automatically exclude/clean up deleted employee from draft
        Livewire::test(Show::class, ['period' => $period])
            ->assertDontSee('Karyawan Akan Dihapus');

        $this->assertDatabaseMissing('payroll_items', ['id' => $item->id]);
        $this->assertEquals(0, $period->fresh()->employee_count);
    }

    public function test_can_remove_employee_item_from_draft_period(): void
    {
        $admin = $this->createAdminUser();
        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-REMOVE-ITEM',
            'name' => 'Payroll Test Remove Item',
            'month' => 10,
            'year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
            'employee_count' => 0,
            'total_basic_salary' => 0,
        ]);

        $emp = Employee::factory()->active()->withDetail()->create([
            'full_name' => 'Karyawan Dihapus Dari Draf',
            'basic_salary' => 5000000,
        ]);

        $service = app(PayrollService::class);
        $item = $service->addEmployeeToPeriod($period, $emp, $admin);

        $this->assertEquals(1, $period->fresh()->employee_count);

        $this->actingAs($admin);

        Livewire::test(Show::class, ['period' => $period])
            ->call('removeItem', $item->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('payroll_items', ['id' => $item->id]);
        $this->assertEquals(0, $period->fresh()->employee_count);
        $this->assertEquals(0, (int) $period->fresh()->total_basic_salary);
    }

    public function test_deleting_employee_via_service_removes_from_draft_period(): void
    {
        $admin = $this->createAdminUser();
        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-EMP-DEL-CLEAN',
            'name' => 'Payroll Test Emp Del Clean',
            'month' => 10,
            'year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
            'employee_count' => 0,
            'total_basic_salary' => 0,
        ]);

        $emp = Employee::factory()->active()->withDetail()->create([
            'full_name' => 'Karyawan Master Dihapus',
            'basic_salary' => 5000000,
        ]);

        $payrollService = app(PayrollService::class);
        $item = $payrollService->addEmployeeToPeriod($period, $emp, $admin);

        $this->assertEquals(1, $period->fresh()->employee_count);

        $empService = app(EmployeeService::class);
        $empService->deleteEmployee($emp);

        $this->assertSoftDeleted('employees', ['id' => $emp->id]);
        $this->assertDatabaseMissing('payroll_items', ['id' => $item->id]);
        $this->assertEquals(0, $period->fresh()->employee_count);
    }

    public function test_updating_employee_basic_salary_auto_syncs_to_draft_payroll_period(): void
    {
        $admin = $this->createAdminUser();
        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-SYNC-SAL-01',
            'name' => 'Payroll Test Sync Sal 01',
            'month' => 10,
            'year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
            'employee_count' => 0,
            'total_basic_salary' => 0,
        ]);

        $emp = Employee::factory()->active()->withDetail()->create([
            'full_name' => 'Budi Santoso',
            'basic_salary' => 5000000,
            'join_date' => '2024-01-01',
        ]);

        $payrollService = app(PayrollService::class);
        $item = $payrollService->addEmployeeToPeriod($period, $emp, $admin);

        $this->assertEquals(5000000, (float) $item->fresh()->basic_salary);

        // Now update employee's basic salary via EmployeeService
        $empService = app(EmployeeService::class);
        $empService->updateEmployee($emp, [
            'full_name' => 'Budi Santoso',
            'basic_salary' => 7500000,
            'join_date' => '2024-01-01',
            'status' => EmployeeStatus::ACTIVE->value,
            'employment_status' => $emp->employment_status->value,
        ]);

        // Verify draft payroll item has automatically updated to 7.500.000
        $updatedItem = $item->fresh();
        $this->assertEquals(7500000, (float) $updatedItem->basic_salary);
        $this->assertEquals(7500000, (float) $period->fresh()->total_basic_salary);
    }

    public function test_sync_period_with_master_employees_action_in_show(): void
    {
        $admin = $this->createAdminUser();
        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-SYNC-SAL-02',
            'name' => 'Payroll Test Sync Sal 02',
            'month' => 10,
            'year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
            'employee_count' => 0,
            'total_basic_salary' => 0,
        ]);

        $emp = Employee::factory()->active()->withDetail()->create([
            'full_name' => 'Dewi Lestari',
            'basic_salary' => 4000000,
            'join_date' => '2024-01-01',
        ]);

        $payrollService = app(PayrollService::class);
        $item = $payrollService->addEmployeeToPeriod($period, $emp, $admin);

        // Directly change employee salary in db (simulating external update)
        $emp->update(['basic_salary' => 6000000]);

        $this->assertEquals(4000000, (float) $item->fresh()->basic_salary);

        // Call syncWithMasterEmployees on Show component
        Livewire::actingAs($admin)
            ->test(Show::class, ['period' => $period])
            ->call('syncWithMasterEmployees')
            ->assertDispatched('swal:alert');

        $this->assertEquals(6000000, (float) $item->fresh()->basic_salary);
    }

    public function test_opening_manage_item_automatically_syncs_basic_salary_in_draft_period(): void
    {
        $admin = $this->createAdminUser();
        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-SYNC-AUTO-MOUNT',
            'name' => 'Payroll Test Sync Auto Mount',
            'month' => 10,
            'year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT,
            'created_by' => $admin->id,
            'employee_count' => 0,
            'total_basic_salary' => 0,
        ]);

        $emp = Employee::factory()->active()->withDetail()->create([
            'full_name' => 'Citra Kirana',
            'basic_salary' => 5000000,
            'join_date' => '2024-01-01',
        ]);

        $payrollService = app(PayrollService::class);
        $item = $payrollService->addEmployeeToPeriod($period, $emp, $admin);

        // Change master salary directly in db
        $emp->update(['basic_salary' => 8000000]);

        $this->assertEquals(5000000, (float) $item->fresh()->basic_salary);

        // Just mounting/opening ManageItem automatically syncs it!
        Livewire::actingAs($admin)
            ->test(PayrollManageItem::class, ['period' => $period, 'item' => $item]);

        $this->assertEquals(8000000, (float) $item->fresh()->basic_salary);
    }

    public function test_updating_employee_basic_salary_does_not_affect_approved_or_paid_payroll_periods(): void
    {
        $admin = $this->createAdminUser();
        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-SYNC-LOCKED-01',
            'name' => 'Payroll Test Sync Locked 01',
            'month' => 9,
            'year' => 2026,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'payment_date' => '2026-09-30',
            'status' => PayrollPeriodStatus::APPROVED,
            'created_by' => $admin->id,
            'employee_count' => 0,
            'total_basic_salary' => 0,
        ]);

        $emp = Employee::factory()->active()->withDetail()->create([
            'full_name' => 'Karyawan Tetap Terkunci',
            'basic_salary' => 5000000,
            'join_date' => '2024-01-01',
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $emp->id,
            'payslip_number' => 'SLP-TEST-LOCKED',
            'basic_salary' => 5000000,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'net_salary' => 5000000,
            'payment_status' => 'unpaid',
            'approval_status' => 'approved',
        ]);

        // Update employee's basic salary via EmployeeService
        $empService = app(EmployeeService::class);
        $empService->updateEmployee($emp, [
            'full_name' => 'Karyawan Tetap Terkunci',
            'basic_salary' => 9000000,
            'join_date' => '2024-01-01',
            'status' => EmployeeStatus::ACTIVE->value,
            'employment_status' => $emp->employment_status->value,
        ]);

        // The approved payroll item must NOT change
        $this->assertEquals(5000000, (float) $item->fresh()->basic_salary);
    }

    public function test_global_company_profile_switches_act_as_default_and_allow_per_employee_override(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Turn off global settings
        $company = CompanyProfile::getProfile();
        $company->update([
            'enable_pph21' => false,
            'include_bpjs_in_tax' => false,
        ]);

        $emp = Employee::factory()->create([
            'join_date' => Carbon::now()->subMonths(6),
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => 10_000_000,
        ]);

        EmployeeDetail::create([
            'employee_id' => $emp->id,
            'marital_status' => MaritalStatus::TK,
            'dependents_count' => 0,
            'ptkp_status' => PtkpStatus::TK_0,
            'tax_method' => TaxMethod::GROSS,
        ]);

        $service = app(PayrollService::class);
        $period = $service->createPeriod([
            'year' => 2026,
            'month' => 11,
            'name' => 'Penggajian Periode November 2026',
        ], $admin);

        $item = $period->items()->where('employee_id', $emp->id)->first();
        $this->assertNotNull($item);

        // Open ManageItem
        $lw = Livewire::test(PayrollManageItem::class, [
            'period' => $period,
            'item' => $item,
        ]);

        // 1. Verify deduction rows default to disabled because global is OFF
        $deductions = collect($lw->get('deductionRows'));
        $pphRow = $deductions->first(fn ($r) => ($r['category'] ?? '') === 'tax' || strcasecmp(trim($r['name'] ?? ''), 'pph 21') === 0);
        $bpjsKesRow = $deductions->first(fn ($r) => str_starts_with(strtolower($r['name'] ?? ''), 'bpjs kesehatan'));

        $this->assertNotNull($pphRow);
        $this->assertFalse($pphRow['is_enabled']);
        $this->assertEquals(0, (float) $pphRow['amount']);

        $this->assertNotNull($bpjsKesRow);
        $this->assertFalse($bpjsKesRow['is_enabled']);
        $this->assertEquals(0, (float) $bpjsKesRow['amount']);

        // Company BPJS toggles should also default to false
        $companyBpjs = $lw->get('companyBpjsStatus');
        $this->assertFalse($companyBpjs['bpjs_kes']);

        // 2. Toggle ON PPh 21 and BPJS for this employee
        $pphIdx = $deductions->search(fn ($r) => ($r['category'] ?? '') === 'tax' || strcasecmp(trim($r['name'] ?? ''), 'pph 21') === 0);
        $kesIdx = $deductions->search(fn ($r) => str_starts_with(strtolower($r['name'] ?? ''), 'bpjs kesehatan'));

        $lw->call('toggleDeductionEnabled', $pphIdx);
        $lw->call('toggleDeductionEnabled', $kesIdx);
        $lw->call('toggleCompanyBpjs', 'bpjs_kes');

        // Check values are calculated in component
        $updatedDeductions = collect($lw->get('deductionRows'));
        $this->assertTrue($updatedDeductions[$pphIdx]['is_enabled']);
        $this->assertGreaterThan(0, (float) $updatedDeductions[$pphIdx]['amount']);
        $this->assertTrue($updatedDeductions[$kesIdx]['is_enabled']);
        $this->assertGreaterThan(0, (float) $updatedDeductions[$kesIdx]['amount']);

        // 3. Save and verify persisted in DB
        $lw->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $item->refresh();
        $this->assertGreaterThan(0, (float) $item->pph21_amount);

        $dbPph = $item->lines()->where('category', PayrollLineCategory::TAX)->first();
        $this->assertNotNull($dbPph);
        $this->assertGreaterThan(0, (float) $dbPph->amount);

        $dbKes = $item->lines()->where('category', PayrollLineCategory::BPJS)->where('name', 'like', 'BPJS Kesehatan%')->first();
        $this->assertNotNull($dbKes);
        $this->assertGreaterThan(0, (float) $dbKes->amount);

        $dbCompKes = $item->lines()->where('type', PayrollLineType::COMPANY_CONTRIBUTION)->where('name', 'like', 'BPJS Kesehatan%')->first();
        $this->assertNotNull($dbCompKes);
        $this->assertGreaterThan(0, (float) $dbCompKes->amount);
    }
}
