<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\TaxRecipientType;
use App\Livewire\Employees\Index;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('employees.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_employees_page(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('employees.index'));

        $response->assertOk();
        $response->assertSee('Kelola Karyawan');
    }

    public function test_can_create_employee_with_detail_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $department = Department::factory()->create(['name' => 'Engineering']);
        $position = Position::factory()->create(['name' => 'Backend Developer']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->assertDispatched('open-modal')
            ->set('form.full_name', 'Ahmad Fadillah')
            ->set('form.department_id', $department->id)
            ->set('form.position_id', $position->id)
            ->set('form.employment_status', EmploymentStatus::TETAP->value)
            ->set('form.tax_recipient_type', TaxRecipientType::MonthlyEmployee->value)
            ->set('form.status', EmployeeStatus::ACTIVE->value)
            ->set('form.join_date', '2026-01-15')
            ->set('form.basic_salary', 8500000)
            ->set('form.employee_code', 'EMP-2026-0001')
            ->set('form.gender', Gender::MALE->value)
            ->set('form.birth_place', 'Bandung')
            ->set('form.birth_date', '1995-08-17')
            ->set('form.phone', '081234567890')
            ->set('form.email', 'ahmad.fadillah@perusahaan.com')
            ->set('form.address', 'Jl. Merdeka No. 45 Bandung')
            ->set('form.bank_name', 'BCA')
            ->set('form.bank_account_number', '1234567890')
            ->set('form.bank_account_name', 'Ahmad Fadillah')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'employeeModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('employees', [
            'full_name' => 'Ahmad Fadillah',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_status' => 'tetap',
            'status' => 'active',
            'basic_salary' => 8500000,
        ]);

        $this->assertDatabaseHas('employee_details', [
            'employee_code' => 'EMP-2026-0001',
            'gender' => 'male',
            'birth_place' => 'Bandung',
            'phone' => '081234567890',
            'email' => 'ahmad.fadillah@perusahaan.com',
            'bank_name' => 'BCA',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'employees',
            'action' => 'CREATE',
            'level' => 'info',
        ]);
    }

    public function test_can_create_employee_with_photo_and_cv_upload(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = $this->createAdminUser();
        $photo = UploadedFile::fake()->image('profile.jpg');
        $cv = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.full_name', 'Siti Nurhaliza')
            ->set('form.join_date', '2026-02-01')
            ->set('form.basic_salary', 7000000)
            ->set('form.tax_recipient_type', TaxRecipientType::MonthlyEmployee->value)
            ->set('form.gender', Gender::FEMALE->value)
            ->set('form.photo', $photo)
            ->set('form.curriculum_vitae', $cv)
            ->call('store')
            ->assertHasNoErrors();

        $employee = Employee::where('full_name', 'Siti Nurhaliza')->firstOrFail();

        $this->assertNotNull($employee->photo_path);
        $this->assertNotNull($employee->detail?->curriculum_vitae);
        Storage::disk('public')->assertExists($employee->photo_path);
        Storage::disk('local')->assertExists($employee->detail->curriculum_vitae);
    }

    public function test_can_update_employee_and_detail_and_logs_activity(): void
    {
        $user = $this->createAdminUser();
        $employee = Employee::factory()->withDetail([
            'employee_code' => 'EMP-2026-0010',
            'email' => 'old.email@test.com',
            'phone' => '0811111111',
            'tax_recipient_type' => TaxRecipientType::MonthlyEmployee->value,
        ])->create([
            'full_name' => 'Budi Santoso',
            'basic_salary' => 6000000,
            'employment_status' => EmploymentStatus::TETAP->value,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('edit', $employee->id)
            ->assertSet('form.full_name', 'Budi Santoso')
            ->set('form.full_name', 'Budi Santoso, S.Kom')
            ->set('form.basic_salary', 7500000)
            ->set('form.email', 'budi.new@test.com')
            ->set('form.status', EmployeeStatus::INACTIVE->value)
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'employeeModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'full_name' => 'Budi Santoso, S.Kom',
            'basic_salary' => 7500000,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('employee_details', [
            'employee_id' => $employee->id,
            'email' => 'budi.new@test.com',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'employees',
            'action' => 'UPDATE',
            'level' => 'info',
        ]);
    }

    public function test_can_show_employee_detail_modal(): void
    {
        $user = $this->createAdminUser();
        $employee = Employee::factory()->withDetail([
            'employee_code' => 'EMP-2026-0099',
            'bank_name' => 'Bank Mandiri',
        ])->create([
            'full_name' => 'Rian Ardiansyah',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('showDetail', $employee->id)
            ->assertDispatched('open-modal', id: 'employeeDetailModal')
            ->assertSet('selectedEmployee.id', $employee->id);
    }

    public function test_can_delete_employee_and_detail_and_logs_activity(): void
    {
        Storage::fake('public');

        $user = $this->createAdminUser();
        $employee = Employee::factory()->withDetail()->create([
            'full_name' => 'Karyawan Hapus Test',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('confirmDelete', $employee->id)
            ->assertDispatched('swal:confirm-delete')
            ->call('delete', $employee->id)
            ->assertDispatched('swal:alert');

        $this->assertSoftDeleted('employees', [
            'id' => $employee->id,
        ]);

        $this->assertDatabaseHas('employee_details', [
            'employee_id' => $employee->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'employees',
            'action' => 'DELETE',
            'level' => 'info',
        ]);
    }

    public function test_search_and_filters_return_matching_employees(): void
    {
        $user = $this->createAdminUser();
        $dept1 = Department::factory()->create(['name' => 'Finance']);
        $dept2 = Department::factory()->create(['name' => 'Marketing']);

        $emp1 = Employee::factory()->withDetail(['employee_code' => 'FIN-001'])->create([
            'full_name' => 'Dewi Sartika',
            'department_id' => $dept1->id,
            'status' => EmployeeStatus::ACTIVE,
            'employment_status' => EmploymentStatus::TETAP,
        ]);

        $emp2 = Employee::factory()->withDetail(['employee_code' => 'MKT-002'])->create([
            'full_name' => 'Joko Widodo',
            'department_id' => $dept2->id,
            'status' => EmployeeStatus::INACTIVE,
            'employment_status' => EmploymentStatus::KONTRAK,
        ]);

        $this->actingAs($user);

        // Search test
        Livewire::test(Index::class)
            ->set('search', 'Dewi')
            ->assertSee('Dewi Sartika')
            ->assertDontSee('Joko Widodo');

        // Department filter test
        Livewire::test(Index::class)
            ->set('filterDepartment', $dept2->id)
            ->assertSee('Joko Widodo')
            ->assertDontSee('Dewi Sartika');

        // Status filter test
        Livewire::test(Index::class)
            ->set('filterStatus', EmployeeStatus::INACTIVE->value)
            ->assertSee('Joko Widodo')
            ->assertDontSee('Dewi Sartika');
    }

    public function test_validates_required_fields_and_uniqueness(): void
    {
        $user = $this->createAdminUser();
        Employee::factory()->withDetail([
            'employee_code' => 'EMP-EXISTING',
            'email' => 'duplicate@test.com',
        ])->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.full_name', '')
            ->set('form.join_date', '')
            ->set('form.employee_code', 'EMP-EXISTING')
            ->set('form.email', 'duplicate@test.com')
            ->call('store')
            ->assertHasErrors([
                'form.full_name' => 'required',
                'form.join_date' => 'required',
                'form.employee_code' => 'unique',
                'form.email' => 'unique',
            ]);
    }

    public function test_daily_worker_requires_daily_rate_and_allows_null_basic_salary(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        // Validation failure when daily_rate is missing for harian
        Livewire::test(Index::class)
            ->call('create')
            ->set('form.full_name', 'Pegawai Harian Baru')
            ->set('form.join_date', '2026-01-01')
            ->set('form.employment_status', 'harian')
            ->set('form.daily_rate', '')
            ->set('form.basic_salary', '')
            ->call('store')
            ->assertHasErrors(['form.daily_rate' => 'required'])
            ->assertHasNoErrors(['form.basic_salary']);

        // Success when daily_rate is provided
        Livewire::test(Index::class)
            ->call('create')
            ->set('form.full_name', 'Pegawai Harian Berhasil')
            ->set('form.join_date', '2026-01-01')
            ->set('form.employment_status', 'harian')
            ->set('form.daily_rate', 175000)
            ->set('form.basic_salary', 0)
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'employeeModal');

        $emp = Employee::where('full_name', 'Pegawai Harian Berhasil')->firstOrFail();
        $this->assertEquals(175000, (float) $emp->daily_rate);
        $this->assertEquals(0, (float) $emp->basic_salary);
        $this->assertEquals(EmploymentStatus::HARIAN, $emp->employment_status);
    }

    public function test_total_cash_receipt_is_protected_and_cannot_be_manipulated_via_form(): void
    {
        $user = $this->createAdminUser();
        $employee = Employee::factory()->withDetail([
            'tax_recipient_type' => TaxRecipientType::MonthlyEmployee->value,
        ])->create([
            'full_name' => 'Testing Kasbon Readonly',
            'total_cash_receipt' => 1500000,
            'employment_status' => EmploymentStatus::TETAP->value,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('edit', $employee->id)
            ->assertSet('form.formatted_total_cash_receipt', 'Rp 1.500.000')
            ->set('form.full_name', 'Testing Kasbon Readonly Updated')
            ->call('update')
            ->assertHasNoErrors();

        $employee->refresh();
        $this->assertEquals(1500000, (float) $employee->total_cash_receipt);
        $this->assertEquals('Testing Kasbon Readonly Updated', $employee->full_name);
    }

    public function test_view_mode_renders_unified_table_mode(): void
    {
        $user = $this->createAdminUser();
        $employee = Employee::factory()->withDetail()->create([
            'full_name' => 'Budi Santoso',
        ]);

        $this->actingAs($user);

        $test = Livewire::test(Index::class)
            ->set('search', 'Budi Santoso');

        // Should render unified table mode without card grid
        $test->assertSet('viewMode', 'table')
            ->assertDontSeeHtml('emp-card-grid')
            ->assertSeeHtml('table-clean')
            ->assertSee('Budi Santoso')
            ->assertSee('DEPARTEMEN');
    }

    public function test_table_mode_groups_employees_by_department(): void
    {
        $user = $this->createAdminUser();
        $deptA = Department::factory()->create(['name' => 'Divisi Teknologi']);
        $deptB = Department::factory()->create(['name' => 'Divisi Operasional']);

        Employee::factory()->withDetail()->create([
            'full_name' => 'Fahmi Ibrahim',
            'department_id' => $deptA->id,
        ]);
        Employee::factory()->withDetail()->create([
            'full_name' => 'Syafiq Aziz',
            'department_id' => $deptB->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('filterDepartment', $deptA->id)
            ->assertSet('viewMode', 'table')
            ->assertSee('DEPARTEMEN DIVISI TEKNOLOGI')
            ->assertSee('Fahmi Ibrahim');

        Livewire::test(Index::class)
            ->set('filterDepartment', $deptB->id)
            ->assertSet('viewMode', 'table')
            ->assertSee('DEPARTEMEN DIVISI OPERASIONAL')
            ->assertSee('Syafiq Aziz');
    }

    public function test_table_mode_sorting_hierarchy_and_removed_kasbon_column(): void
    {
        $user = $this->createAdminUser();
        $dept = Department::firstOrCreate(['name' => 'Divisi Rekayasa']);
        $posManager = Position::firstOrCreate(['name' => 'Manager']);
        $posMagang = Position::firstOrCreate(['name' => 'Magang']);
        $posStaff = Position::firstOrCreate(['name' => 'Staff']);

        Employee::factory()->withDetail()->create([
            'full_name' => 'Karyawan Magang',
            'department_id' => $dept->id,
            'position_id' => $posMagang->id,
            'employment_status' => EmploymentStatus::MAGANG->value,
            'contract_end_date' => now()->addDays(30),
        ]);

        Employee::factory()->withDetail()->create([
            'full_name' => 'Karyawan Manager',
            'department_id' => $dept->id,
            'position_id' => $posManager->id,
            'employment_status' => EmploymentStatus::TETAP->value,
        ]);

        Employee::factory()->withDetail()->create([
            'full_name' => 'Staff Kontrak Urgent',
            'department_id' => $dept->id,
            'position_id' => $posStaff->id,
            'employment_status' => EmploymentStatus::KONTRAK->value,
            'contract_end_date' => now()->addDays(10),
        ]);

        Employee::factory()->withDetail()->create([
            'full_name' => 'Staff Kontrak Santai',
            'department_id' => $dept->id,
            'position_id' => $posStaff->id,
            'employment_status' => EmploymentStatus::KONTRAK->value,
            'contract_end_date' => now()->addDays(100),
        ]);

        $this->actingAs($user);

        $test = Livewire::test(Index::class)
            ->set('filterDepartment', $dept->id)
            ->assertSet('viewMode', 'table')
            ->assertDontSeeHtml('TOTAL KASBON')
            ->assertSee('TANGGAL MASUK')
            ->assertSee('DEPARTEMEN DIVISI REKAYASA');

        $html = $test->html();
        $posMgr = strpos($html, 'Karyawan Manager');
        $posStaffUrg = strpos($html, 'Staff Kontrak Urgent');
        $posStaffLat = strpos($html, 'Staff Kontrak Santai');
        $posMg = strpos($html, 'Karyawan Magang');

        $this->assertTrue($posMgr < $posStaffUrg, 'Manager should appear before Staff');
        $this->assertTrue($posStaffUrg < $posStaffLat, 'Contract expiring sooner should appear before later contract');
        $this->assertTrue($posStaffLat < $posMg, 'Staff should appear before Magang');
    }

    public function test_auto_generates_employee_email_from_full_name_and_company_domain(): void
    {
        $profile = CompanyProfile::getProfile();
        $profile->update(['email_domain' => 'namaperusahaan.com']);

        $user = $this->createAdminUser();
        $department = Department::firstOrCreate(['name' => 'IT Department']);
        $position = Position::firstOrCreate(['name' => 'Software Engineer']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.full_name', 'Budi Santoso Gunawan')
            ->assertSet('form.email', 'budi.santoso.gunawan@namaperusahaan.com')
            ->set('form.department_id', $department->id)
            ->set('form.position_id', $position->id)
            ->set('form.tax_recipient_type', TaxRecipientType::MonthlyEmployee->value)
            ->set('form.join_date', '2026-02-01')
            ->set('form.basic_salary', 7000000)
            ->call('store')
            ->assertHasNoErrors();

        $employee = Employee::where('full_name', 'Budi Santoso Gunawan')->first();
        $this->assertNotNull($employee);
        $this->assertSame('budi.santoso.gunawan@namaperusahaan.com', $employee->detail->email);
    }

    public function test_employee_index_renders_stat_cards_department_chart_and_expiring_contracts_review_modal(): void
    {
        $user = $this->createAdminUser();
        $dept = Department::firstOrCreate(['name' => 'Operasional']);
        $pos = Position::firstOrCreate(['name' => 'Operator']);

        // Karyawan tetap
        Employee::factory()->withDetail()->create([
            'full_name' => 'Karyawan Tetap 1',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'status' => EmployeeStatus::ACTIVE,
            'employment_status' => EmploymentStatus::TETAP,
        ]);

        // Karyawan kontrak akan segera berakhir dalam 10 hari
        $expiringEmp = Employee::factory()->withDetail(['employee_code' => 'OPS-EXP-001'])->create([
            'full_name' => 'Karyawan Kontrak Kritis',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'status' => EmployeeStatus::ACTIVE,
            'employment_status' => EmploymentStatus::KONTRAK,
            'contract_end_date' => now()->addDays(1)->toDateString(),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->assertSee('Total Karyawan Aktif')
            ->assertSee('Karyawan Tetap')
            ->assertSee('Kontrak &amp; Magang', false)
            ->assertSee('Perlu Perhatian Kontrak')
            ->assertSee('Sebaran Departemen')
            ->assertSee('orang ·', false)
            ->assertSee('Tinjau')
            ->assertSeeHtml('id="expiringContractsModal"')
            ->assertSee('Tinjau Kontrak Karyawan');

        if (! str_contains($component->html(), 'Karyawan Kontrak Kritis')) {
            $component->call('gotoPage', 2, 'expiringPage');
        }
        $component->assertSee('Karyawan Kontrak Kritis');

        $html = $component->html();
        preg_match('/<tr class="table-group-header".*?<\/tr>/s', $html, $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('Karyawan', $matches[0]);

        $tetapEmp = Employee::where('full_name', 'Karyawan Tetap 1')->first();
        $this->assertSame('-', $tetapEmp->remaining_contract_label);
        $this->assertStringEndsWith('lagi', $expiringEmp->remaining_contract_label);
    }

    public function test_switching_between_edited_employees_does_not_leak_form_values(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $empA = Employee::factory()->withDetail()->create([
            'full_name' => 'Karyawan Alpha',
            'overtime_rate_per_day' => 100000,
            'overtime_rate_per_hour' => 20000,
        ]);

        $empB = Employee::factory()->withDetail()->create([
            'full_name' => 'Karyawan Beta',
            'overtime_rate_per_day' => 75000,
            'overtime_rate_per_hour' => 15000,
        ]);

        $test = Livewire::test(Index::class)
            // Edit Employee A and modify rate to 150.000
            ->call('edit', $empA->id)
            ->set('form.overtime_rate_per_day', 150000)
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('swal:alert');

        $empA->refresh();
        $this->assertEquals(150000, (float) $empA->overtime_rate_per_day);

        // Edit Employee B: must load Employee B's actual rates (75.000 & 15.000), not Employee A's 150.000
        $test->call('edit', $empB->id)
            ->assertSet('form.overtime_rate_per_day', 75000)
            ->assertSet('form.overtime_rate_per_hour', 15000)
            ->assertSeeHtml('wire:key="emp-ot-day-wrap-' . $empB->id . '"');
    }
}
