<?php

namespace Tests\Feature;

use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OvertimePeriod;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class WeeklyPayrollTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected User $direkturUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $direkturRole = Role::firstOrCreate(['name' => 'direktur'], ['display_name' => 'Direktur']);
        $karyawanRole = Role::firstOrCreate(['name' => 'karyawan'], ['display_name' => 'Karyawan']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin_test_' . uniqid() . '@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->adminUser->addRole($adminRole);

        $this->direkturUser = User::factory()->create([
            'email' => 'direktur_test_' . uniqid() . '@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->direkturUser->addRole($direkturRole);

        $this->karyawanUser = User::factory()->create([
            'email' => 'karyawan_test_' . uniqid() . '@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->karyawanUser->addRole($karyawanRole);

        $dept = Department::firstOrCreate(['name' => 'Operasional']);
        $pos = Position::firstOrCreate(['name' => 'Staff']);

        $this->employee = Employee::firstOrCreate(
            ['fingerprint_id' => '888'],
            [
                'user_id' => $this->karyawanUser->id,
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'full_name' => 'Karyawan Weekly Test',
                'join_date' => Carbon::now()->subYears(1)->toDateString(),
                'employment_status' => EmploymentStatus::HARIAN,
                'status' => 'active',
                'daily_rate' => 125000,
                'meal_allowance_per_day' => 25000,
            ]
        );
    }

    public function test_admin_can_access_weekly_payroll_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('payrolls.weekly'));

        $response->assertOk();
        $response->assertSee('Gaji &amp; Slip Mingguan', false);
        $response->assertSee('Buat Periode Mingguan');
        $response->assertSee('Total Biaya Mingguan');
        $response->assertSee('Total Upah Harian');
        $response->assertSee('Total Uang Makan');
        $response->assertSee('Total Upah Lembur');
    }

    public function test_admin_can_create_weekly_period_with_friday_to_thursday_cycle(): void
    {
        $startDate = '2026-10-23'; // Jumat
        $endDate = '2026-10-29';   // Kamis (7 hari)

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Payrolls\WeeklyPayrollIndex::class)
            ->set('period_start_date', $startDate)
            ->set('period_end_date', $endDate)
            ->set('period_name', 'Gaji Mingguan W43 Test')
            ->call('storePeriod')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'weeklyPeriodModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('overtime_periods', [
            'name' => 'Gaji Mingguan W43 Test',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function test_default_period_name_is_without_parentheses_dates(): void
    {
        $comp = Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Payrolls\WeeklyPayrollIndex::class);

        $periodName = $comp->get('period_name');
        $this->assertMatchesRegularExpression('/^Gaji Mingguan W\d+$/', $periodName);
        $this->assertStringNotContainsString('(', $periodName);
    }

    public function test_updating_start_date_snaps_to_friday_and_calculates_thursday_end_date(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Payrolls\WeeklyPayrollIndex::class)
            ->set('period_start_date', '2026-10-28')
            ->assertSet('period_start_date', '2026-10-23')
            ->assertSet('period_end_date', '2026-10-29')
            ->assertSet('period_name', 'Gaji Mingguan W43');
    }

    public function test_validation_rejects_invalid_cycle_end_date(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Payrolls\WeeklyPayrollIndex::class)
            ->set('period_start_date', '2026-10-23') // Friday
            ->set('period_end_date', '2026-10-28')   // Wednesday (Invalid)
            ->set('period_name', 'Gaji Mingguan Test')
            ->call('storePeriod')
            ->assertHasErrors(['period_end_date']);
    }

    public function test_admin_and_direktur_can_delete_draft_period(): void
    {
        $period = OvertimePeriod::create([
            'reference_number' => 'PER-TEST-' . uniqid(),
            'name' => 'Gaji Mingguan W99 To Delete',
            'start_date' => '2026-10-23',
            'end_date' => '2026-10-29',
            'status' => \App\Enums\OvertimePeriodStatus::DRAFT,
            'created_by' => $this->adminUser->id,
            'employee_count' => 1,
            'grand_total' => 0,
        ]);

        Livewire::actingAs($this->direkturUser)
            ->test(\App\Livewire\Payrolls\WeeklyPayrollIndex::class)
            ->call('confirmDelete', $period->id)
            ->assertDispatched('swal:confirm-delete')
            ->call('deletePeriod', $period->id)
            ->assertDispatched('swal:alert');

        $this->assertSoftDeleted('overtime_periods', ['id' => $period->id]);
    }

    public function test_cannot_delete_paid_period(): void
    {
        $paidPeriod = OvertimePeriod::create([
            'reference_number' => 'PER-PAID-' . uniqid(),
            'name' => 'Gaji Mingguan W98 Paid',
            'start_date' => '2026-10-23',
            'end_date' => '2026-10-29',
            'status' => \App\Enums\OvertimePeriodStatus::PAID,
            'created_by' => $this->adminUser->id,
            'employee_count' => 1,
            'grand_total' => 1000000,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Payrolls\WeeklyPayrollIndex::class)
            ->call('deletePeriod', $paidPeriod->id)
            ->assertDispatched('swal:alert', fn ($event, $params) => ($params[0]['type'] ?? '') === 'error');

        $this->assertDatabaseHas('overtime_periods', [
            'id' => $paidPeriod->id,
            'deleted_at' => null,
        ]);
    }

    public function test_karyawan_cannot_delete_period(): void
    {
        $period = OvertimePeriod::create([
            'reference_number' => 'PER-DRAFT-' . uniqid(),
            'name' => 'Gaji Mingguan W97 Draft',
            'start_date' => '2026-10-23',
            'end_date' => '2026-10-29',
            'status' => \App\Enums\OvertimePeriodStatus::DRAFT,
            'created_by' => $this->adminUser->id,
            'employee_count' => 1,
            'grand_total' => 0,
        ]);

        Livewire::actingAs($this->karyawanUser)
            ->test(\App\Livewire\Payrolls\WeeklyPayrollIndex::class)
            ->call('deletePeriod', $period->id)
            ->assertForbidden();

        $this->assertDatabaseHas('overtime_periods', ['id' => $period->id]);
    }

    public function test_admin_can_confirm_and_mark_period_paid(): void
    {
        $period = OvertimePeriod::create([
            'reference_number' => 'PER-DRAFT-' . uniqid(),
            'name' => 'Gaji Mingguan W96 Paid Test',
            'start_date' => '2026-10-23',
            'end_date' => '2026-10-29',
            'status' => \App\Enums\OvertimePeriodStatus::DRAFT,
            'created_by' => $this->adminUser->id,
            'employee_count' => 1,
            'grand_total' => 500000,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Payrolls\WeeklyPayrollIndex::class)
            ->call('confirmMarkPaid', $period->id)
            ->assertDispatched('swal:confirm')
            ->call('markPeriodPaid', $period->id)
            ->assertDispatched('swal:alert');

        $this->assertEquals(
            \App\Enums\OvertimePeriodStatus::PAID,
            $period->fresh()->status
        );
    }

    public function test_cannot_create_weekly_payroll_period_when_no_eligible_employees_exist(): void
    {
        // Change employee to non-harian and ensure no attendance/overtime exists
        $this->employee->update(['employment_status' => EmploymentStatus::TETAP]);

        $startDate = '2026-10-23';
        $endDate = '2026-10-29';

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Payrolls\WeeklyPayrollIndex::class)
            ->set('period_start_date', $startDate)
            ->set('period_end_date', $endDate)
            ->set('period_name', 'Gaji Mingguan W43 Empty Test')
            ->call('storePeriod')
            ->assertDispatched('swal:alert', function ($event, $params) {
                return ($params[0]['type'] ?? '') === 'error';
            });

        $this->assertDatabaseMissing('overtime_periods', [
            'name' => 'Gaji Mingguan W43 Empty Test',
        ]);
    }
}
