<?php

namespace Tests\Feature;

use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\PayrollPeriodStatus;
use App\Enums\RepaymentScheme;
use App\Livewire\Dashboard\AdminDashboard;
use App\Models\CashAdvance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use DatabaseTransactions;

    private User $adminUser;

    private User $direkturUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        Role::firstOrCreate(['name' => 'direktur'], ['display_name' => 'Direktur']);
        Role::firstOrCreate(['name' => 'karyawan'], ['display_name' => 'Karyawan']);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->syncRoles(['admin']);

        $this->direkturUser = User::factory()->create(['is_active' => true]);
        $this->direkturUser->syncRoles(['direktur']);
    }

    public function test_admin_can_access_dashboard_and_see_executive_kpi_cards(): void
    {
        $dept = Department::factory()->create(['name' => 'Teknologi Informasi']);
        Employee::factory()->create([
            'status' => EmployeeStatus::ACTIVE,
            'employment_status' => EmploymentStatus::TETAP,
            'department_id' => $dept->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Utama');
        $response->assertSee('Karyawan Aktif');
        $response->assertSee('Gaji Bersih');
        $response->assertSee('Sisa Kasbon');
        $response->assertSee('Pajak PPh 21');
        $response->assertSee('Tren Beban Penggajian');
        $response->assertSee('Sebaran Departemen');
    }

    public function test_admin_dashboard_component_renders_metrics_and_handles_period_change(): void
    {
        $currentYear = (int) now()->format('Y');
        $currentMonth = (int) now()->format('n');

        PayrollPeriod::create([
            'reference_number' => 'PR-'.$currentYear.str_pad((string) $currentMonth, 2, '0', STR_PAD_LEFT),
            'name' => 'Penggajian Periode '.$currentMonth.' '.$currentYear,
            'year' => $currentYear,
            'month' => $currentMonth,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'total_net_salary' => 75000000,
            'status' => PayrollPeriodStatus::DRAFT,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(AdminDashboard::class)
            ->assertSet('selectedYear', $currentYear)
            ->assertSet('selectedMonth', $currentMonth)
            ->assertSee('Rp 75.000.000')
            ->set('selectedMonth', $currentMonth === 1 ? 2 : 1)
            ->assertDispatched('dashboard-updated');
    }

    public function test_direktur_can_also_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->direkturUser)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Utama');
        $response->assertSee('Karyawan Aktif');
    }

    public function test_action_items_include_pending_cash_advance_and_multiple_alerts(): void
    {
        $employee = Employee::factory()->create([
            'status' => EmployeeStatus::ACTIVE,
        ]);

        CashAdvance::create([
            'reference_number' => 'CA-TEST-001',
            'employee_id' => $employee->id,
            'amount' => 1500000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT,
            'installment_count' => 3,
            'monthly_installment' => 500000,
            'remaining_amount' => 1500000,
            'disbursement_date' => now()->toDateString(),
            'submitted_by' => $this->adminUser->id,
            'status' => CashAdvanceStatus::PENDING,
            'reason' => 'Keperluan mendesak',
        ]);

        $actionItems = app(DashboardService::class)->getActionItems();
        $this->assertNotEmpty($actionItems);
        $allTexts = collect($actionItems)->pluck('text')->implode(' ');
        $this->assertStringContainsString('pengajuan kasbon baru menunggu persetujuan admin', $allTexts);

        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Tindakan Perlu Perhatian');
        $response->assertSee('pengajuan kasbon baru menunggu persetujuan admin');
        $response->assertSee('Tinjau Kasbon');
    }
}
