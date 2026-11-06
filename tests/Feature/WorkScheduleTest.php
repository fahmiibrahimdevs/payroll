<?php

namespace Tests\Feature;

use App\Enums\EmploymentStatus;
use App\Livewire\Schedules\Index as SchedulesIndex;
use App\Models\Attendance;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\OvertimePeriodAttendance;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\OvertimeService;
use App\Support\WorkingDays;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class WorkScheduleTest extends TestCase
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

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->addRole($adminRole);

        $this->direkturUser = User::factory()->create(['is_active' => true]);
        $this->direkturUser->addRole($direkturRole);

        $this->karyawanUser = User::factory()->create(['is_active' => true]);
        $this->karyawanUser->addRole($karyawanRole);

        $department = Department::firstOrCreate(['name' => 'Produksi']);

        $this->employee = Employee::create([
            'user_id' => $this->karyawanUser->id,
            'department_id' => $department->id,
            'full_name' => 'Kelvin Desain',
            'employment_status' => EmploymentStatus::TETAP,
            'status' => 'active',
            'join_date' => '2026-01-01',
            'fingerprint_id' => '999',
            'daily_rate' => 120000,
            'meal_allowance_per_day' => 20000,
            'meal_allowance_type' => 'standar',
        ]);

        $this->employee->detail()->create([
            'employee_code' => 'EMP-999',
            'email' => 'kelvin@example.com',
            'phone' => '08123456789',
        ]);
    }

    public function test_admin_and_director_can_access_schedules_page(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Roster Jadwal Kerja');

        $this->actingAs($this->direkturUser)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Roster Jadwal Kerja');
    }

    public function test_employee_can_view_schedules_page(): void
    {
        $this->actingAs($this->karyawanUser)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Roster Jadwal Kerja');
    }

    public function test_admin_can_toggle_employee_schedule_status(): void
    {
        $this->actingAs($this->adminUser);

        // Minggu tgl 2026-11-08 defaultnya off, jika di-toggle jadi work
        Livewire::test(SchedulesIndex::class)
            ->set('year', 2026)
            ->set('month', 11)
            ->call('toggleDay', $this->employee->id, '2026-11-08')
            ->assertDispatched('schedule-toggled');

        $this->assertDatabaseHas('employee_schedules', [
            'employee_id' => $this->employee->id,
            'date' => '2026-11-08',
            'status' => EmployeeSchedule::STATUS_WORK,
        ]);

        // Toggle kedua: work -> off
        Livewire::test(SchedulesIndex::class)
            ->set('year', 2026)
            ->set('month', 11)
            ->call('toggleDay', $this->employee->id, '2026-11-08')
            ->assertDispatched('schedule-toggled');

        $this->assertDatabaseHas('employee_schedules', [
            'employee_id' => $this->employee->id,
            'date' => '2026-11-08',
            'status' => EmployeeSchedule::STATUS_OFF,
        ]);
    }

    public function test_employee_cannot_toggle_schedule_status(): void
    {
        $this->actingAs($this->karyawanUser);

        Livewire::test(SchedulesIndex::class)
            ->set('year', 2026)
            ->set('month', 11)
            ->call('toggleDay', $this->employee->id, '2026-11-08')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('employee_schedules', [
            'employee_id' => $this->employee->id,
            'date' => '2026-11-08',
        ]);
    }

    public function test_admin_can_copy_schedule_from_previous_month(): void
    {
        $this->actingAs($this->adminUser);

        // Buat jadwal di bulan 10
        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-10-15',
            'status' => EmployeeSchedule::STATUS_OFF,
            'notes' => 'Libur Shift',
        ]);

        Livewire::test(SchedulesIndex::class)
            ->set('year', 2026)
            ->set('month', 11)
            ->call('copyFromPreviousMonth')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('employee_schedules', [
            'employee_id' => $this->employee->id,
            'date' => '2026-11-15',
            'status' => EmployeeSchedule::STATUS_OFF,
        ]);
    }

    public function test_admin_can_export_roster_to_excel(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(SchedulesIndex::class)
            ->set('year', 2026)
            ->set('month', 11)
            ->call('exportExcel')
            ->assertFileDownloaded('Jadwal_Kerja_Roster_2026-11.xlsx');
    }

    public function test_roster_mode_calculates_working_days_properly_for_sunday_and_weekday_off(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'work_days_per_week' => 6,
            'work_schedule_mode' => 'roster',
        ]);

        // Jadwal Kelvin:
        // Masuk di hari Minggu (2026-11-08)
        // Libur di hari Selasa (2026-11-10)
        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-11-08',
            'status' => EmployeeSchedule::STATUS_WORK,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-11-10',
            'status' => EmployeeSchedule::STATUS_OFF,
        ]);

        $workDays = WorkingDays::employeeWorkDays($this->employee->id, '2026-11-08', '2026-11-14', $company);

        // Minggu (2026-11-08) HARUS ada di daftar hari kerja
        $this->assertArrayHasKey('2026-11-08', $workDays);
        $this->assertEquals('sun', $workDays['2026-11-08']);

        // Selasa (2026-11-10) TIDAK BOLEH ada di daftar hari kerja karena jadwalnya OFF
        $this->assertArrayNotHasKey('2026-11-10', $workDays);
    }

    public function test_weekly_overtime_sync_calculates_meal_allowance_according_to_roster(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update([
            'work_days_per_week' => 6,
            'work_schedule_mode' => 'roster',
        ]);

        // Kelvin dijadwalkan masuk Minggu (2026-11-08) dan libur Selasa (2026-11-10)
        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-11-08',
            'status' => EmployeeSchedule::STATUS_WORK,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-11-10',
            'status' => EmployeeSchedule::STATUS_OFF,
        ]);

        // Catat kehadiran Kelvin di hari Minggu (2026-11-08) dan Senin (2026-11-09)
        Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-11-08',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'status' => 'hadir',
            'duration_minutes' => 540,
            'source' => 'fingerprint',
        ]);

        Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-11-09',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'status' => 'hadir',
            'duration_minutes' => 540,
            'source' => 'fingerprint',
        ]);

        // Buat periode lembur mingguan 08 Nov s/d 14 Nov 2026
        $otService = app(OvertimeService::class);
        $period = $otService->createPeriod([
            'start_date' => '2026-11-08',
            'end_date' => '2026-11-14',
            'notes' => 'Minggu Toko',
        ], $this->adminUser);

        // Sync absensi ke periode lembur mingguan
        $attService = app(AttendanceService::class);
        $synced = $attService->syncToWeeklyOvertimePeriod($period, $this->adminUser);

        $this->assertGreaterThan(0, $synced);

        $attRecord = OvertimePeriodAttendance::where('overtime_period_id', $period->id)
            ->where('employee_id', $this->employee->id)
            ->first();

        $this->assertNotNull($attRecord);
        // Hadir 2 hari (Minggu & Senin)
        $this->assertEquals(2, $attRecord->attended_days);
        $this->assertContains('sun', $attRecord->attendance_days);
        $this->assertContains('mon', $attRecord->attendance_days);
        // Uang makan: 2 hari x tarif efektif uang makan
        $expectedMeal = 2 * (float) $this->employee->getEffectiveMealAllowancePerDay();
        $this->assertEquals($expectedMeal, (float) $attRecord->total_meal_allowance);
    }

    public function test_admin_can_sort_and_move_employee_order(): void
    {
        $this->actingAs($this->adminUser);

        // Buat karyawan kedua
        $emp2 = Employee::create([
            'fingerprint_id' => '9992',
            'department_id' => $this->employee->department_id,
            'position_id' => $this->employee->position_id,
            'full_name' => 'Budi Santoso',
            'join_date' => '2024-01-01',
            'employment_status' => EmploymentStatus::TETAP,
            'status' => 'active',
            'basic_salary' => 3000000,
        ]);

        $test = Livewire::test(SchedulesIndex::class)
            ->set('year', 2026)
            ->set('month', 11)
            ->set('sortBy', 'name_desc');

        $employees = $test->viewData('roster')['employees'];
        $this->assertEquals($this->employee->id, $employees->first()['id']); // 'Kelvin...' > 'Budi...'

        // Test move employee
        $test->call('moveEmployee', $emp2->id, 'up');
        $this->assertEquals('custom', $test->get('sortBy'));

        // Test reorderEmployees via drag & drop
        $test->call('reorderEmployees', [$emp2->id, $this->employee->id]);
        $orderedEmployees = $test->viewData('roster')['employees'];
        $this->assertEquals($emp2->id, $orderedEmployees->first()['id']);

        // Test setEmployeeColor
        $test->call('setEmployeeColor', $emp2->id, 'yellow');
        $this->assertEquals('yellow', $test->get('employeeColors')[$emp2->id]);
        $firstEmpWithColor = $test->viewData('roster')['employees']->first();
        $this->assertEquals('yellow', $firstEmpWithColor['color']);
    }

    public function test_direktur_is_read_only_and_cannot_modify_schedules(): void
    {
        $this->actingAs($this->direkturUser);

        Livewire::test(SchedulesIndex::class)
            ->set('year', 2026)
            ->set('month', 11)
            ->call('toggleDay', $this->employee->id, '2026-11-04')
            ->assertDispatched('swal:alert')
            ->call('setEmployeeColor', $this->employee->id, 'blue')
            ->assertSet('employeeColors', [])
            ->call('copyFromPreviousMonth')
            ->assertDispatched('swal:alert')
            ->assertDontSee('wire:click="openColorModal')
            ->assertDontSee('class="drag-handle');
    }
}
