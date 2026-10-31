<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Livewire\Holidays\Index as HolidayIndex;
use App\Models\Attendance;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\OvertimePeriodAttendance;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\User;
use App\Services\OvertimeService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class HolidayTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected User $direkturUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $direkturRole = Role::firstOrCreate(['name' => 'direktur'], ['display_name' => 'Direktur']);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->addRole($adminRole);

        $this->direkturUser = User::factory()->create(['is_active' => true]);
        $this->direkturUser->addRole($direkturRole);
    }

    public function test_admin_can_create_edit_and_delete_holiday(): void
    {
        $this->actingAs($this->adminUser);

        // CREATE
        Livewire::test(HolidayIndex::class)
            ->call('create')
            ->assertDispatched('open-modal')
            ->set('form.date', '2026-10-05')
            ->set('form.name', 'Hari Libur Uji Kalender')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'holidayModal')
            ->assertDispatched('swal:alert');

        $holiday = Holiday::where('date', '2026-10-05')->firstOrFail();
        $this->assertSame('Hari Libur Uji Kalender', $holiday->name);

        // UPDATE
        Livewire::test(HolidayIndex::class)
            ->call('edit', $holiday->id)
            ->assertSet('isEditMode', true)
            ->set('form.name', 'Hari Libur Uji Kalender Revisi')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('holidays', [
            'id' => $holiday->id,
            'name' => 'Hari Libur Uji Kalender Revisi',
        ]);

        // DELETE
        Livewire::test(HolidayIndex::class)
            ->call('confirmDelete', $holiday->id)
            ->assertDispatched('swal:confirm-delete');

        Livewire::test(HolidayIndex::class)
            ->call('delete', $holiday->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    public function test_direktur_can_view_but_cannot_mutate_holidays(): void
    {
        $holiday = Holiday::create(['date' => '2026-10-05', 'name' => 'Libur Uji Direktur']);

        $this->actingAs($this->direkturUser)
            ->get(route('holidays.index'))
            ->assertOk();

        Livewire::actingAs($this->direkturUser)
            ->test(HolidayIndex::class)
            ->call('create')
            ->assertForbidden();

        Livewire::actingAs($this->direkturUser)
            ->test(HolidayIndex::class)
            ->call('delete', $holiday->id)
            ->assertForbidden();
    }

    public function test_holiday_is_excluded_from_prorated_working_days(): void
    {
        $admin = $this->createAdminUser();
        $service = app(PayrollService::class);

        $company = CompanyProfile::getProfile();
        $company->update(['work_days_per_week' => 6]); // Senin-Sabtu (27 hari kerja di Okt 2026)

        $employee = Employee::factory()->active()->create([
            'basic_salary' => 5400000,
            'full_name' => 'Karyawan Libur Nasional',
            'join_date' => '2026-01-01',
        ]);

        // Libur nasional Senin, 5 Okt 2026.
        Holiday::create(['date' => '2026-10-05', 'name' => 'Libur Nasional Uji Prorata']);

        // Karyawan hadir di semua hari kerja KECUALI tanggal libur: 26 hari.
        for ($d = 1; $d <= 31; $d++) {
            $date = sprintf('2026-10-%02d', $d);
            $cDate = Carbon::parse($date);
            if ($cDate->isSunday() || $date === '2026-10-05') {
                continue;
            }
            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $date,
                'clock_in' => '08:00:00',
                'clock_out' => '17:00:00',
                'status' => 'hadir',
                'duration_minutes' => 540,
                'source' => 'fingerprint',
            ]);
        }

        PayrollPeriod::where('month', 10)->where('year', 2026)->delete();

        $period = $service->createPeriod([
            'month' => 10,
            'year' => 2026,
            'name' => 'Payroll Libur Okt 2026',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ], $admin);

        $item = $period->items()->where('employee_id', $employee->id)->firstOrFail();

        // 26/26 hari kerja efektif (libur tidak mengurangi gaji karyawan yang absen pada hari libur).
        $this->assertEquals(5400000, (float) $item->basic_salary);
        $this->assertEquals('26 dari 26 hari hadir', $item->notes);
    }

    public function test_weekly_fallback_excludes_sundays_and_holidays(): void
    {
        $service = app(OvertimeService::class);

        // Karyawan harian aktif agar masuk kandidat fallback tanpa log absensi.
        $employee = Employee::factory()->create([
            'full_name' => 'Harian Fallback Libur',
            'employment_status' => EmploymentStatus::HARIAN,
            'status' => EmployeeStatus::ACTIVE,
            'daily_rate' => 100000,
            'join_date' => '2026-01-01',
        ]);

        // Pekan Jumat 2 Okt - Kamis 8 Okt 2026: Minggu 4 Okt + libur Senin 5 Okt.
        Holiday::create(['date' => '2026-10-05', 'name' => 'Libur Nasional Fallback']);
        // Log absensi kosong: buat rentang tanpa attendance.

        $period = $service->createPeriod([
            'name' => 'Periode Fallback Libur',
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-08',
        ], $this->adminUser);

        $att = OvertimePeriodAttendance::where('overtime_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        // Jum, Sab, Sel, Rab, Kam = 5 hari (Min & hari libur dikecualikan).
        $this->assertEquals(5, $att->attended_days);
        $this->assertEqualsCanonicalizing(['fri', 'sat', 'tue', 'wed', 'thu'], $att->attendance_days);
    }
}
