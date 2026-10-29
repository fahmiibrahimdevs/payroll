<?php

namespace Tests\Feature;

use App\Livewire\CashAdvances\Index;
use App\Models\CashAdvance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class KaryawanRoleAccessTest extends TestCase
{
    use DatabaseTransactions;

    private User $karyawanUser;

    private Employee $ownEmployee;

    private Employee $otherEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::firstOrCreate(['name' => 'IT'], ['description' => 'IT Dept']);
        $pos = Position::firstOrCreate(['name' => 'Staff'], ['description' => 'Staff Pos']);

        $role = Role::firstOrCreate(
            ['name' => 'karyawan'],
            ['display_name' => 'Karyawan', 'description' => 'Pegawai']
        );

        $this->karyawanUser = User::firstOrCreate(
            ['email' => 'test.karyawan.unique@larashiz.com'],
            [
                'name' => 'Test Karyawan',
                'password' => bcrypt('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $this->karyawanUser->syncRoles(['karyawan']);

        $this->ownEmployee = Employee::firstOrCreate(
            ['full_name' => 'Test Karyawan Unique'],
            [
                'user_id' => $this->karyawanUser->id,
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'join_date' => now()->subYear(),
                'employment_status' => 'tetap',
                'status' => 'active',
                'basic_salary' => 7000000,
            ]
        );
        $this->ownEmployee->update(['user_id' => $this->karyawanUser->id]);

        EmployeeDetail::firstOrCreate(
            ['employee_id' => $this->ownEmployee->id],
            [
                'employee_code' => 'EMP-TEST-99',
                'email' => 'test.karyawan.unique@larashiz.com',
                'phone' => '081234567890',
            ]
        );

        $this->otherEmployee = Employee::firstOrCreate(
            ['full_name' => 'Other Employee Unique'],
            [
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'join_date' => now()->subYear(),
                'employment_status' => 'tetap',
                'status' => 'active',
                'basic_salary' => 8000000,
            ]
        );
    }

    public function test_karyawan_can_access_dashboard_with_personalized_summary(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Ringkasan Karyawan');
        $response->assertSee('Test Karyawan Unique');
        $response->assertSee('Rp 7.000.000');
    }

    public function test_karyawan_accessing_employees_index_is_redirected_to_own_profile(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('employees.index'));

        $response->assertRedirect(route('employees.show', $this->ownEmployee->id));
    }

    public function test_karyawan_can_view_own_employee_profile(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('employees.show', $this->ownEmployee->id));

        $response->assertStatus(200);
        $response->assertSee($this->ownEmployee->full_name);
        $response->assertSee('Rp 7.000.000');
    }

    public function test_karyawan_cannot_view_other_employee_profile(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('employees.show', $this->otherEmployee->id));

        $response->assertStatus(403);
    }

    public function test_karyawan_can_access_cash_advances_page(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('cash-advances.index'));

        $response->assertStatus(200);
        $response->assertSee($this->ownEmployee->full_name);
    }

    public function test_karyawan_cannot_access_administrative_master_data_pages(): void
    {
        $this->actingAs($this->karyawanUser)->get(route('departments.index'))->assertStatus(403);
        $this->actingAs($this->karyawanUser)->get(route('positions.index'))->assertStatus(403);
        $this->actingAs($this->karyawanUser)->get(route('salary-components.index'))->assertStatus(403);
        $this->actingAs($this->karyawanUser)->get(route('payrolls.index'))->assertStatus(403);
    }

    public function test_karyawan_can_submit_cash_advance_for_self(): void
    {
        Livewire::actingAs($this->karyawanUser)
            ->test(Index::class)
            ->call('createPengajuan')
            ->set('form.disbursement_date', now()->format('Y-m-d'))
            ->set('form.amount', 1500000)
            ->set('form.repayment_scheme', 'salary_installment')
            ->set('form.installment_count', 3)
            ->set('form.reason', 'Biaya darurat')
            ->call('store');

        $this->assertDatabaseHas('cash_advances', [
            'employee_id' => $this->ownEmployee->id,
            'amount' => 1500000,
            'installment_count' => 3,
            'status' => 'pending',
        ]);
    }

    public function test_karyawan_can_print_own_payslip_but_not_others(): void
    {
        $period = PayrollPeriod::firstOrCreate(
            ['reference_number' => 'PR-2026-10'],
            [
                'name' => 'Payroll Oktober 2026',
                'month' => 10,
                'year' => 2026,
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'status' => 'paid',
            ]
        );
        $period->update(['status' => 'paid']);

        $ownItem = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $this->ownEmployee->id,
            'payslip_number' => 'SLIP-OWN-01',
            'basic_salary' => 7000000,
            'net_salary' => 7000000,
            'approval_status' => 'approved',
        ]);

        $otherItem = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $this->otherEmployee->id,
            'payslip_number' => 'SLIP-OTHER-01',
            'basic_salary' => 8000000,
            'net_salary' => 8000000,
        ]);

        $this->actingAs($this->karyawanUser)
            ->get(route('payrolls.items.print', [$period, $ownItem]))
            ->assertStatus(200);

        $this->actingAs($this->karyawanUser)
            ->get(route('payrolls.items.print', [$period, $otherItem]))
            ->assertStatus(403);
    }

    public function test_karyawan_cannot_perform_manual_repayment_or_early_settlement(): void
    {
        $advance = CashAdvance::create([
            'reference_number' => 'KB-KARYAWAN-TEST',
            'employee_id' => $this->ownEmployee->id,
            'amount' => 2000000,
            'remaining_amount' => 2000000,
            'repayment_scheme' => 'salary_installment',
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => now()->toDateString(),
            'reason' => 'Keperluan mendesak',
            'status' => 'approved',
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $this->assertFalse(Gate::forUser($this->karyawanUser)->allows('recordPayment', $advance));
        $this->assertFalse(Gate::forUser($this->karyawanUser)->allows('recordEarlySettlement', CashAdvance::class));

        // Test Livewire actions are forbidden for karyawan
        $this->actingAs($this->karyawanUser);

        Livewire::test(Index::class)
            ->call('createPelunasan')
            ->assertForbidden();

        Livewire::test(Index::class)
            ->call('openEarlyPaymentModal')
            ->assertForbidden();

        Livewire::test(Index::class)
            ->call('storePayment')
            ->assertForbidden();

        Livewire::test(Index::class)
            ->call('storeEarlyPayment')
            ->assertForbidden();
    }

    public function test_karyawan_profile_hides_hr_salary_progression_status_and_alerts(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('employees.show', $this->ownEmployee->id));

        $response->assertStatus(200);
        $response->assertSee('Profil Saya');
        $response->assertSee('Pajak & BPJS');
        $response->assertSee('Gaji Pokok');

        // Must not see internal HR status gaji evaluation
        $response->assertDontSee('Status Gaji');
        $response->assertDontSee('Perhatian: Karyawan Mengalami Stagnasi Gaji');
        $response->assertDontSee('Status Gaji Terkini');
        $response->assertDontSee('Riwayat Gaji (Riwayat Kenaikan & Penyesuaian)');
        $response->assertDontSee('Penggajian yang masih berstatus Draf atau Menunggu Approval');
        $response->assertDontSee('Kembali ke Daftar Karyawan');
        $response->assertDontSee('Detail Profil Karyawan');
    }

    public function test_karyawan_dashboard_displays_active_cash_advance_balance(): void
    {
        CashAdvance::create([
            'reference_number' => 'KB-DASHBOARD-TEST',
            'employee_id' => $this->ownEmployee->id,
            'amount' => 2000000,
            'remaining_amount' => 2000000,
            'repayment_scheme' => 'salary_installment',
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => now()->toDateString(),
            'reason' => 'Keperluan berobat',
            'status' => 'active',
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $response = $this->actingAs($this->karyawanUser)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Sisa Saldo Kasbon');
        $response->assertSee('Rp 2.000.000');
        $response->assertSee('1 Pinjaman Aktif');
    }

    public function test_karyawan_profile_displays_calculated_total_kasbon_and_remaining_balance(): void
    {
        CashAdvance::create([
            'reference_number' => 'KB-PROFILE-TEST-1',
            'employee_id' => $this->ownEmployee->id,
            'amount' => 3000000,
            'remaining_amount' => 0,
            'repayment_scheme' => 'salary_installment',
            'installment_count' => 3,
            'monthly_installment' => 1000000,
            'disbursement_date' => now()->subMonths(3)->toDateString(),
            'reason' => 'Pinjaman 1',
            'status' => 'paid',
            'submitted_by' => $this->karyawanUser->id,
        ]);

        CashAdvance::create([
            'reference_number' => 'KB-PROFILE-TEST-2',
            'employee_id' => $this->ownEmployee->id,
            'amount' => 2000000,
            'remaining_amount' => 2000000,
            'repayment_scheme' => 'salary_installment',
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => now()->toDateString(),
            'reason' => 'Pinjaman 2',
            'status' => 'active',
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $response = $this->actingAs($this->karyawanUser)->get(route('employees.show', $this->ownEmployee->id));

        $response->assertStatus(200);
        $response->assertSee('Total Kasbon');
        $response->assertSee('Rp 5.000.000');
        $response->assertSee('(Sisa: Rp 2.000.000)');
    }

    public function test_unlinked_karyawan_cannot_access_cash_advances_module(): void
    {
        $unlinkedUser = User::create([
            'name' => 'Unlinked Karyawan',
            'email' => 'unlinked.karyawan@larashiz.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $unlinkedUser->syncRoles(['karyawan']);

        $response = $this->actingAs($unlinkedUser)->get(route('cash-advances.index'));
        $response->assertStatus(403);
    }

    public function test_unlinked_karyawan_cannot_access_overtimes_and_attendances_module(): void
    {
        $unlinkedUser = User::create([
            'name' => 'Unlinked Karyawan 2',
            'email' => 'unlinked2.karyawan@larashiz.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $unlinkedUser->syncRoles(['karyawan']);

        $this->actingAs($unlinkedUser)->get(route('overtimes.index'))->assertStatus(403);
        $this->actingAs($unlinkedUser)->get(route('attendances.index'))->assertStatus(403);
    }

    public function test_karyawan_cannot_access_weekly_payroll_page(): void
    {
        $this->actingAs($this->karyawanUser)
            ->get(route('payrolls.weekly'))
            ->assertStatus(403);
    }

    public function test_director_cannot_create_overtime_submission(): void
    {
        $directorRole = Role::firstOrCreate(['name' => 'direktur'], ['display_name' => 'Direktur']);
        $directorUser = User::factory()->create(['is_active' => true]);
        $directorUser->syncRoles(['direktur']);

        Livewire::actingAs($directorUser)
            ->test(\App\Livewire\Overtimes\Index::class)
            ->call('openCreateModal')
            ->assertForbidden();

        Livewire::actingAs($directorUser)
            ->test(\App\Livewire\Overtimes\Index::class)
            ->call('storeSubmission')
            ->assertForbidden();
    }

    public function test_karyawan_sidebar_navigation_and_dashboard_hub(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('dashboard'));

        $response->assertStatus(200);

        // Sidebar check: Profil Saya & Riwayat Slip Gaji removed from sidebar menu
        // but Kasbon Saya remains in sidebar
        $response->assertSee('Kasbon Saya');
        $response->assertSee('Analisis Gaji Bersih');
        $response->assertSee('Total Diterima');
        $response->assertSee('flutter-stat-card');
    }
}
