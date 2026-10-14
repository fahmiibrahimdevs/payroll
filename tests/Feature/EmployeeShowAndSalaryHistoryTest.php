<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\PayrollPeriodStatus;
use App\Enums\SalaryAdjustmentType;
use App\Livewire\Employees\Show;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalaryHistory;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeShowAndSalaryHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected function createEmployeeWithDetail(array $overrides = []): Employee
    {
        $department = Department::factory()->create(['name' => 'Teknologi']);
        $position = Position::factory()->create(['name' => 'Lead Developer']);

        $employee = Employee::factory()->create(array_merge([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'full_name' => 'Fahmi Ibrahim',
            'join_date' => Carbon::now()->subMonths(14)->format('Y-m-d'),
            'basic_salary' => 6000000,
            'employment_status' => EmploymentStatus::TETAP->value,
            'status' => EmployeeStatus::ACTIVE->value,
        ], $overrides));

        $employee->detail()->create([
            'employee_code' => 'EMP-2025-0012',
            'gender' => 'male',
            'phone' => '081234567890',
            'email' => 'fahmi@example.com',
            'address' => 'Jl. Merdeka No. 45',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Fahmi Ibrahim',
        ]);

        return $employee;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $employee = $this->createEmployeeWithDetail();

        $response = $this->get(route('employees.show', $employee));

        $response->assertRedirect(route('login'));
    }

    public function test_non_authorized_user_is_forbidden(): void
    {
        $employee = $this->createEmployeeWithDetail();
        $user = User::factory()->create(['is_active' => true]); // No admin or direktur role

        $response = $this->actingAs($user)->get(route('employees.show', $employee));

        $response->assertForbidden();
    }

    public function test_admin_can_view_employee_show_page(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeWithDetail();

        $response = $this->actingAs($admin)->get(route('employees.show', $employee));

        $response->assertOk();
        $response->assertSee('Detail Profil Karyawan');
        $response->assertSee($employee->full_name);
        $response->assertSee('Teknologi');
        $response->assertSee('Lead Developer');
        $response->assertSee('Kepegawaian');
        $response->assertSee('Pribadi');
        $response->assertSee('Riwayat Gaji');
    }

    public function test_can_switch_tabs_on_employee_show(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeWithDetail();
        $employee->salaryHistories()->create([
            'previous_salary' => 6000000,
            'new_salary' => 6500000,
            'change_amount' => 500000,
            'percentage_change' => 8.33,
            'effective_date' => '2026-10-01',
            'type' => SalaryAdjustmentType::ANNUAL_ADJUSTMENT,
            'reason' => 'Review gaji tahunan',
            'changed_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(Show::class, ['employee' => $employee])
            ->assertSet('activeTab', 'kepegawaian')
            ->call('setTab', 'pribadi')
            ->assertSet('activeTab', 'pribadi')
            ->assertSee('Data Pribadi')
            ->call('setTab', 'riwayat-gaji')
            ->assertSet('activeTab', 'riwayat-gaji')
            ->assertSee('Riwayat Kenaikan')
            ->assertSee($admin->name);
    }

    public function test_admin_can_record_salary_adjustment_and_it_updates_basic_salary(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeWithDetail(['basic_salary' => 6000000]);

        $this->actingAs($admin);

        Livewire::test(Show::class, ['employee' => $employee])
            ->call('openAdjustmentModal')
            ->assertDispatched('open-salary-modal')
            ->set('adj_new_salary', '7500000')
            ->set('adj_effective_date', '2026-10-01')
            ->set('adj_type', SalaryAdjustmentType::PERFORMANCE_REVIEW->value)
            ->set('adj_reason', 'Kenaikan atas pencapaian target Q3')
            ->call('saveSalaryAdjustment')
            ->assertDispatched('swal:alert');

        $employee->refresh();

        $this->assertEquals(7500000, (float) $employee->basic_salary);

        $this->assertDatabaseHas('employee_salary_histories', [
            'employee_id' => $employee->id,
            'previous_salary' => 6000000,
            'new_salary' => 7500000,
            'change_amount' => 1500000,
            'percentage_change' => 25.00,
            'type' => SalaryAdjustmentType::PERFORMANCE_REVIEW->value,
            'reason' => 'Kenaikan atas pencapaian target Q3',
            'changed_by' => $admin->id,
        ]);
    }

    public function test_validation_fails_when_new_salary_is_empty(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeWithDetail();

        $this->actingAs($admin);

        Livewire::test(Show::class, ['employee' => $employee])
            ->set('adj_new_salary', '')
            ->call('saveSalaryAdjustment')
            ->assertHasErrors(['adj_new_salary']);
    }

    public function test_salary_progression_status_detects_stagnancy_and_recent_raise(): void
    {
        // 1. Employee joined 15 months ago, no raise -> stagnant (> 12 months)
        $employee = $this->createEmployeeWithDetail([
            'join_date' => Carbon::now()->subMonths(15)->format('Y-m-d'),
            'basic_salary' => 5000000,
        ]);

        $status = $employee->salary_progression_status;
        $this->assertEquals('Belum Naik Gaji > 1 Thn', $status['label']);
        $this->assertStringContainsString('badge-warning', $status['badge_class']);

        // 2. Add history entry 1 month ago -> recently raised (<= 3 months)
        EmployeeSalaryHistory::create([
            'employee_id' => $employee->id,
            'previous_salary' => 5000000,
            'new_salary' => 6000000,
            'change_amount' => 1000000,
            'percentage_change' => 20,
            'effective_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
            'type' => SalaryAdjustmentType::ANNUAL_ADJUSTMENT,
            'reason' => 'Evaluasi tahunan',
            'changed_by' => null,
        ]);

        $employee->refresh();
        $statusRecent = $employee->salary_progression_status;
        $this->assertEquals('Baru Naik Gaji', $statusRecent['label']);
        $this->assertStringContainsString('badge-success', $statusRecent['badge_class']);
    }

    public function test_admin_can_delete_salary_history(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeWithDetail(['basic_salary' => 5000000]);

        $initial = EmployeeSalaryHistory::create([
            'employee_id' => $employee->id,
            'previous_salary' => 0,
            'new_salary' => 5000000,
            'change_amount' => 5000000,
            'percentage_change' => 0,
            'effective_date' => Carbon::now()->subMonths(6),
            'type' => SalaryAdjustmentType::INITIAL,
            'reason' => 'Gaji awal',
            'changed_by' => $admin->id,
        ]);

        $second = EmployeeSalaryHistory::create([
            'employee_id' => $employee->id,
            'previous_salary' => 5000000,
            'new_salary' => 7000000,
            'change_amount' => 2000000,
            'percentage_change' => 40,
            'effective_date' => Carbon::now()->subMonths(1),
            'type' => SalaryAdjustmentType::PROMOTION,
            'reason' => 'Promosi',
            'changed_by' => $admin->id,
        ]);

        $employee->update(['basic_salary' => 7000000]);

        $this->actingAs($admin);

        Livewire::test(Show::class, ['employee' => $employee])
            ->call('deleteSalaryHistory', $second->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('employee_salary_histories', ['id' => $second->id]);

        $employee->refresh();
        // Fallback to remaining history
        $this->assertEquals(5000000, (float) $employee->basic_salary);
    }

    public function test_admin_cannot_delete_initial_salary_history(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeWithDetail(['basic_salary' => 5000000]);

        $initial = EmployeeSalaryHistory::create([
            'employee_id' => $employee->id,
            'previous_salary' => 0,
            'new_salary' => 5000000,
            'change_amount' => 5000000,
            'percentage_change' => 0,
            'effective_date' => Carbon::now()->subMonths(6),
            'type' => SalaryAdjustmentType::INITIAL,
            'reason' => 'Gaji awal saat bergabung',
            'changed_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(Show::class, ['employee' => $employee])
            ->call('deleteSalaryHistory', $initial->id)
            ->assertDispatched('swal:alert', fn ($event, $params) => ($params[0]['type'] ?? '') === 'error');

        $this->assertDatabaseHas('employee_salary_histories', ['id' => $initial->id]);
    }

    public function test_initial_salary_type_is_excluded_from_select_options(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeWithDetail();

        $this->actingAs($admin);

        Livewire::test(Show::class, ['employee' => $employee])
            ->assertViewHas('salaryAdjustmentTypes', function ($types) {
                return ! $types->contains(SalaryAdjustmentType::INITIAL);
            });
    }

    public function test_unapproved_payroll_item_is_excluded_from_chart_and_metrics(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeWithDetail(['basic_salary' => 5000000]);

        $period = PayrollPeriod::create([
            'reference_number' => 'PR-TEST-UNAPPROVED',
            'name' => 'Payroll Oktober 2026',
            'month' => 10,
            'year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::DRAFT->value,
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'payslip_number' => 'SLIP-TEST-DRAFT',
            'basic_salary' => 5000000,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'net_salary' => 5000000,
            'approval_status' => 'draft',
        ]);

        $this->actingAs($admin);

        // When in draft, chart bar for October and metrics must be 0
        $test = Livewire::test(Show::class, ['employee' => $employee]);
        $data = $test->get('monthlySalaryData');

        $this->assertSame(0.0, (float) $data['net_salaries'][9], 'October bar must be 0 when unapproved');
        $this->assertSame(0.0, (float) $data['total_net'], 'Total net must be 0 when unapproved');
        $this->assertSame(0, $data['count'], 'Slip count must be 0 when unapproved');
        $this->assertTrue($data['has_unapproved_items'], 'has_unapproved_items must be true');

        // Now approve the period
        $period->update([
            'status' => PayrollPeriodStatus::APPROVED->value,
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);
        $item->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        $testApproved = Livewire::test(Show::class, ['employee' => $employee]);
        $dataApproved = $testApproved->get('monthlySalaryData');

        $this->assertSame(5000000.0, (float) $dataApproved['net_salaries'][9], 'October bar must reflect net salary when approved');
        $this->assertSame(5000000.0, (float) $dataApproved['total_net'], 'Total net must include approved salary');
        $this->assertSame(1, $dataApproved['count'], 'Slip count must be 1 when approved');
        $this->assertFalse($dataApproved['has_unapproved_items'], 'has_unapproved_items must be false');
    }
}
