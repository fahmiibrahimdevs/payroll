<?php

namespace Tests\Feature;

use App\Enums\EmploymentStatus;
use App\Enums\OvertimePeriodStatus;
use App\Enums\OvertimeStatus;
use App\Enums\OvertimeSubmissionSource;
use App\Livewire\Overtimes\Index as OvertimeIndex;
use App\Livewire\Overtimes\PeriodAttendance;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OvertimePeriod;
use App\Models\OvertimeSubmission;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OvertimeTest extends TestCase
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
            'email' => 'admin_' . uniqid() . '@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->adminUser->addRole($adminRole);

        $this->direkturUser = User::factory()->create([
            'email' => 'direktur_' . uniqid() . '@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->direkturUser->addRole($direkturRole);

        $this->karyawanUser = User::factory()->create([
            'email' => 'karyawan_' . uniqid() . '@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->karyawanUser->addRole($karyawanRole);

        $dept = Department::firstOrCreate(['name' => 'Dept Overtime']);
        $pos = Position::firstOrCreate(['name' => 'Staff Overtime']);

        $this->employee = Employee::create([
            'user_id' => $this->karyawanUser->id,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Karyawan Test Lembur',
            'join_date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            'employment_status' => 'tetap',
            'status' => 'active',
            'basic_salary' => 5000000,
            'overtime_rate_per_hour' => 25000,
        ]);
    }

    public function test_karyawan_can_view_overtime_page_and_see_own_submissions_only(): void
    {
        $this->actingAs($this->karyawanUser);

        $response = $this->get(route('overtimes.index'));
        $response->assertStatus(200);
        $response->assertSee('Lembur Saya');
    }

    public function test_admin_can_view_overtime_page_with_modern_stat_cards(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('overtimes.index'));
        $response->assertStatus(200);
        $response->assertSee('cash-advance-stat-grid');
        $response->assertSee('Total pengajuan bulan ini');
        $response->assertSee('Verifikasi admin');
        $response->assertSee('Approval direktur');
        $response->assertSee('Disetujui bulan ini');
        $response->assertSee('Tambah Pengajuan');
        $response->assertDontSee('Buat Periode Mingguan');
        $response->assertSee('Analisis Lembur');
        $response->assertSee('Tren Jam Lembur');
        $response->assertSee('Sebaran per Departemen');
        $response->assertSee('Lembur - <span class="text-primary font-weight-bold">Semua Karyawan</span>', false);
    }

    public function test_direktur_can_view_overtime_page(): void
    {
        $this->actingAs($this->direkturUser);

        $response = $this->get(route('overtimes.index'));
        $response->assertStatus(200);
        $response->assertSee('cash-advance-stat-grid');
        $response->assertSee('Daftar Pengajuan Lembur');
    }

    public function test_karyawan_with_zero_rate_cannot_submit_overtime(): void
    {
        $this->employee->update([
            'overtime_rate_per_hour' => 0,
            'overtime_rate_per_day' => 0,
        ]);

        $this->actingAs($this->karyawanUser);

        Livewire::test(OvertimeIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('swal:alert');
    }

    public function test_karyawan_can_submit_overtime_and_status_becomes_pending_admin(): void
    {
        $this->actingAs($this->karyawanUser);

        Livewire::test(OvertimeIndex::class)
            ->set('form.date', Carbon::today()->format('Y-m-d'))
            ->set('form.start_time', '18:00')
            ->set('form.end_time', '21:00')
            ->set('form.reason', 'Menyelesaikan laporan bulanan client')
            ->call('storeSubmission')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'overtimeSubmissionModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('overtime_submissions', [
            'employee_id' => $this->employee->id,
            'status' => OvertimeStatus::PENDING_ADMIN->value,
            'submission_source' => OvertimeSubmissionSource::KARYAWAN->value,
            'duration_hours' => 3.00,
            'hourly_rate' => 25000.00,
            'total_amount' => 75000.00,
        ]);
    }

    public function test_admin_can_adjust_hours_and_meal_allowance_then_forward_to_direktur(): void
    {
        $service = app(OvertimeService::class);
        $submission = OvertimeSubmission::create([
            'reference_number' => $service->generateSubmissionReference(),
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '17:00',
            'end_time' => '21:00',
            'duration_hours' => 4.00,
            'hourly_rate' => 25000.00,
            'meal_allowance' => 0,
            'total_amount' => 100000.00,
            'reason' => 'Lembur tugas harian',
            'submission_source' => OvertimeSubmissionSource::KARYAWAN,
            'status' => OvertimeStatus::PENDING_ADMIN,
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(OvertimeIndex::class)
            ->call('openAdjustModal', $submission->id)
            ->set('adjust_start_time', '17:00')
            ->set('adjust_end_time', '20:00')
            ->set('adjust_duration_hours', 3.00)
            ->set('adjust_meal_allowance', 15000)
            ->set('adjust_admin_notes', 'Disepakati 3 jam setelah verifikasi log kerja')
            ->call('submitAdjustment')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'overtimeAdjustModal');

        $submission->refresh();
        $this->assertEquals(OvertimeStatus::PENDING_DIREKTUR, $submission->status);
        $this->assertEquals(3.00, (float) $submission->duration_hours);
        $this->assertEquals(15000.00, (float) $submission->meal_allowance);
        $this->assertEquals(90000.00, (float) $submission->total_amount);
        $this->assertEquals($this->adminUser->id, $submission->verified_by_admin_id);
    }

    public function test_admin_can_submit_overtime_directly_and_it_skips_to_pending_direktur(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(OvertimeIndex::class)
            ->set('form.employee_id', $this->employee->id)
            ->set('form.date', Carbon::today()->format('Y-m-d'))
            ->set('form.start_time', '18:00')
            ->set('form.end_time', '20:00')
            ->set('form.meal_allowance', 20000)
            ->set('form.reason', 'Diinput oleh admin untuk pekerjaan darurat')
            ->call('storeSubmission')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('overtime_submissions', [
            'employee_id' => $this->employee->id,
            'status' => OvertimeStatus::PENDING_DIREKTUR->value,
            'submission_source' => OvertimeSubmissionSource::ADMIN->value,
            'duration_hours' => 2.00,
            'meal_allowance' => 20000.00,
            'total_amount' => 70000.00, // 2 * 25.000 + 20.000
        ]);
    }

    public function test_direktur_can_approve_submission(): void
    {
        $service = app(OvertimeService::class);
        $submission = OvertimeSubmission::create([
            'reference_number' => $service->generateSubmissionReference(),
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'duration_hours' => 2.00,
            'hourly_rate' => 25000.00,
            'meal_allowance' => 10000.00,
            'total_amount' => 60000.00,
            'reason' => 'Perapihan berkas',
            'submission_source' => OvertimeSubmissionSource::ADMIN,
            'status' => OvertimeStatus::PENDING_DIREKTUR,
            'submitted_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->direkturUser);

        Livewire::test(OvertimeIndex::class)
            ->call('approveSubmission', $submission->id)
            ->assertDispatched('swal:alert');

        $submission->refresh();
        $this->assertEquals(OvertimeStatus::APPROVED, $submission->status);
        $this->assertEquals($this->direkturUser->id, $submission->approved_by_direktur_id);
    }

    public function test_direktur_can_approve_submission_via_sweetalert_event(): void
    {
        $service = app(OvertimeService::class);
        $submission = OvertimeSubmission::create([
            'reference_number' => $service->generateSubmissionReference(),
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'duration_hours' => 2.00,
            'hourly_rate' => 25000.00,
            'meal_allowance' => 0,
            'total_amount' => 50000.00,
            'reason' => 'Perbaikan bug production',
            'submission_source' => OvertimeSubmissionSource::KARYAWAN,
            'status' => OvertimeStatus::PENDING_DIREKTUR,
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $this->actingAs($this->direkturUser);

        Livewire::test(OvertimeIndex::class)
            ->dispatch('approve-overtime-submission', id: $submission->id)
            ->assertDispatched('swal:alert');

        $submission->refresh();
        $this->assertEquals(OvertimeStatus::APPROVED, $submission->status);
        $this->assertEquals($this->direkturUser->id, $submission->approved_by_direktur_id);
    }

    public function test_direktur_can_reject_submission_via_sweetalert_event(): void
    {
        $service = app(OvertimeService::class);
        $submission = OvertimeSubmission::create([
            'reference_number' => $service->generateSubmissionReference(),
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'duration_hours' => 2.00,
            'hourly_rate' => 25000.00,
            'meal_allowance' => 0,
            'total_amount' => 50000.00,
            'reason' => 'Perbaikan server',
            'submission_source' => OvertimeSubmissionSource::KARYAWAN,
            'status' => OvertimeStatus::PENDING_DIREKTUR,
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $this->actingAs($this->direkturUser);

        Livewire::test(OvertimeIndex::class)
            ->dispatch('reject-overtime-submission', id: $submission->id, reason: 'Budget lembur divisi IT bulan ini telah melampaui batas')
            ->assertDispatched('swal:alert');

        $submission->refresh();
        $this->assertEquals(OvertimeStatus::REJECTED_DIREKTUR, $submission->status);
        $this->assertEquals('Budget lembur divisi IT bulan ini telah melampaui batas', $submission->rejection_reason);
    }

    public function test_admin_or_direktur_can_reject_submission_with_mandatory_reason(): void
    {
        $service = app(OvertimeService::class);
        $submission = OvertimeSubmission::create([
            'reference_number' => $service->generateSubmissionReference(),
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'duration_hours' => 2.00,
            'hourly_rate' => 25000.00,
            'meal_allowance' => 0,
            'total_amount' => 50000.00,
            'reason' => 'Lembur tanpa instruksi',
            'submission_source' => OvertimeSubmissionSource::KARYAWAN,
            'status' => OvertimeStatus::PENDING_ADMIN,
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(OvertimeIndex::class)
            ->call('openRejectModal', $submission->id)
            ->set('rejectionReason', 'Tidak ada memo instruksi lembur dari atasan')
            ->call('submitRejection')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'overtimeRejectModal');

        $submission->refresh();
        $this->assertEquals(OvertimeStatus::REJECTED_ADMIN, $submission->status);
        $this->assertEquals('Tidak ada memo instruksi lembur dari atasan', $submission->rejection_reason);
    }

    public function test_admin_can_create_weekly_period_and_mark_as_paid(): void
    {
        $service = app(OvertimeService::class);
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        $sub1 = OvertimeSubmission::create([
            'reference_number' => $service->generateSubmissionReference(),
            'employee_id' => $this->employee->id,
            'date' => $startDate->copy()->addDays(1)->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'duration_hours' => 2.00,
            'hourly_rate' => 25000.00,
            'meal_allowance' => 15000.00,
            'total_amount' => 65000.00,
            'reason' => 'Tugas A',
            'submission_source' => OvertimeSubmissionSource::KARYAWAN,
            'status' => OvertimeStatus::APPROVED,
            'submitted_by' => $this->karyawanUser->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(OvertimeIndex::class)
            ->set('period_start_date', $startDate->format('Y-m-d'))
            ->set('period_end_date', $endDate->format('Y-m-d'))
            ->set('period_name', 'Lembur Minggu Test')
            ->call('storePeriod')
            ->assertHasNoErrors();

        $period = OvertimePeriod::latest('id')->first();
        $this->assertNotNull($period);
        $this->assertTrue((float) $period->grand_total >= 65000.00);

        $sub1->refresh();
        $this->assertEquals($period->id, $sub1->overtime_period_id);

        // Mark as paid
        Livewire::test(OvertimeIndex::class)
            ->call('markPeriodPaid', $period->id)
            ->assertDispatched('swal:alert');

        $period->refresh();
        $this->assertEquals(OvertimePeriodStatus::PAID, $period->status);
        $this->assertNotNull($period->paid_at);
    }

    public function test_weekly_slip_is_printable_for_employee_and_admin(): void
    {
        $service = app(OvertimeService::class);
        $period = OvertimePeriod::create([
            'reference_number' => $service->generatePeriodReference(),
            'name' => 'Lembur Minggu 43',
            'start_date' => Carbon::now()->startOfWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfWeek()->format('Y-m-d'),
            'total_hours' => 2,
            'total_overtime_pay' => 50000,
            'total_meal_allowance' => 15000,
            'grand_total' => 65000,
            'employee_count' => 1,
            'status' => OvertimePeriodStatus::PAID,
            'created_by' => $this->adminUser->id,
        ]);

        OvertimeSubmission::create([
            'reference_number' => $service->generateSubmissionReference(),
            'overtime_period_id' => $period->id,
            'employee_id' => $this->employee->id,
            'date' => Carbon::now()->startOfWeek()->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'duration_hours' => 2.00,
            'hourly_rate' => 25000.00,
            'meal_allowance' => 15000.00,
            'total_amount' => 65000.00,
            'reason' => 'Pekerjaan akhir pekan',
            'submission_source' => OvertimeSubmissionSource::KARYAWAN,
            'status' => OvertimeStatus::APPROVED,
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $this->actingAs($this->adminUser);
        $response = $this->get(route('overtimes.periods.print', $period->id));
        $response->assertStatus(200);
        $response->assertSee('SLIP MINGGUAN');
        $response->assertSee('65.000');
    }

    public function test_employee_effective_meal_allowance_standar_vs_khusus(): void
    {
        $company = CompanyProfile::getProfile();
        $company->update(['default_meal_allowance_per_day' => 25000]);

        // Default: meal_allowance_type = 'standar' -> falls back to company profile
        $this->employee->update([
            'meal_allowance_type' => 'standar',
            'meal_allowance_per_day' => 0,
        ]);
        $this->assertEquals(25000.00, $this->employee->getEffectiveMealAllowancePerDay());
        $this->assertFalse($this->employee->isKhususMealAllowance());

        // When company allows flexible rates per employee (is_uniform_meal_allowance = false)
        $company = CompanyProfile::first();
        if ($company) {
            $company->update(['is_uniform_meal_allowance' => false]);
            CompanyProfile::clearProfileCache();
        }

        // Khusus: meal_allowance_type = 'khusus' with custom nominal
        $this->employee->update([
            'meal_allowance_type' => 'khusus',
            'meal_allowance_per_day' => 35000,
        ]);
        $this->assertEquals(35000.00, $this->employee->getEffectiveMealAllowancePerDay());
        $this->assertTrue($this->employee->isKhususMealAllowance());

        // When company enforces uniform standard (is_uniform_meal_allowance = true)
        if ($company) {
            $company->update(['is_uniform_meal_allowance' => true]);
            CompanyProfile::clearProfileCache();
        }
        $this->assertEquals(25000.00, $this->employee->getEffectiveMealAllowancePerDay());
        $this->assertFalse($this->employee->isKhususMealAllowance());
    }

    public function test_admin_can_manage_period_attendance_and_toggle_days(): void
    {
        $service = app(OvertimeService::class);
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        // Create approved submission
        $sub = OvertimeSubmission::create([
            'reference_number' => $service->generateSubmissionReference(),
            'employee_id' => $this->employee->id,
            'date' => $startDate->copy()->addDays(1)->format('Y-m-d'),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'duration_hours' => 2.00,
            'hourly_rate' => 25000.00,
            'meal_allowance' => 0,
            'total_amount' => 50000.00,
            'reason' => 'Kerja tambahan',
            'submission_source' => OvertimeSubmissionSource::KARYAWAN,
            'status' => OvertimeStatus::APPROVED,
            'submitted_by' => $this->karyawanUser->id,
            'approved_at' => now(),
        ]);

        $period = $service->createPeriod([
            'name' => 'Periode Kehadiran Test',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ], $this->adminUser);

        $this->actingAs($this->adminUser);

        // Test Livewire dedicated attendance page
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Overtimes\PeriodAttendance::class, ['period' => $period])
            // Toggle a day (e.g. remove 'sat' from 6 default days -> becomes 5 days)
            ->call('toggleAttendanceDay', $this->employee->id, 'sat')
            ->call('save')
            ->assertDispatched('swal:alert');

        $att = \App\Models\OvertimePeriodAttendance::where('overtime_period_id', $period->id)
            ->where('employee_id', $this->employee->id)
            ->first();

        $this->assertNotNull($att);
        $this->assertEquals(5, $att->attended_days);
        $this->assertNotContains('sat', $att->attendance_days);

        // Also test direct numeric days count update (Opsi A) & rate update
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Overtimes\PeriodAttendance::class, ['period' => $period])
            ->call('updateAttendedDaysCount', $this->employee->id, 4)
            ->call('updateMealAllowanceRate', $this->employee->id, 30000)
            ->call('save');

        $att->refresh();
        $this->assertEquals(4, $att->attended_days);
        $this->assertEquals(30000.00, (float) $att->meal_allowance_per_day);
        $this->assertEquals(120000.00, (float) $att->total_meal_allowance); // 4 * 30.000
    }

    public function test_overtime_duration_rounding_modes(): void
    {
        $service = app(OvertimeService::class);

        // 1. round_30_min (Kelipatan 30 menit / 0.5 jam)
        // 21:25 - 23:31 = 126 menit -> 4 * 0.5 = 2.0 jam
        $dur1 = $service->calculateOvertimeDuration('21:25', '23:31', 'round_30_min');
        $this->assertEquals(2.0, $dur1);

        // 17:00 - 19:20 = 140 menit -> round(140/30) * 0.5 = 5 * 0.5 = 2.5 jam
        $dur2 = $service->calculateOvertimeDuration('17:00', '19:20', 'round_30_min');
        $this->assertEquals(2.5, $dur2);

        // 2. round_60_min (Kelipatan 1 jam penuh)
        // 17:00 - 18:40 = 100 menit -> round(100/60) = 2.0 jam
        $dur3 = $service->calculateOvertimeDuration('17:00', '18:40', 'round_60_min');
        $this->assertEquals(2.0, $dur3);

        // 3. floor_hour (Hanya jam penuh)
        // 17:00 - 18:50 = 110 menit -> floor(110/60) = 1.0 jam
        $dur4 = $service->calculateOvertimeDuration('17:00', '18:50', 'floor_hour');
        $this->assertEquals(1.0, $dur4);

        // 4. exact (Menit riil desimal)
        // 21:25 - 23:31 = 126 menit -> round(126/60, 2) = 2.1 jam
        $dur5 = $service->calculateOvertimeDuration('21:25', '23:31', 'exact');
        $this->assertEquals(2.1, $dur5);
    }

    public function test_overtime_duration_rounding_with_dynamic_threshold(): void
    {
        $service = app(OvertimeService::class);

        // Kasus 1: Threshold default = 10 menit
        // 17:00 - 18:05 = 65 menit (1 jam 5 menit) -> 5 < 10 -> 1.0 jam
        $this->assertEquals(1.0, $service->calculateOvertimeDuration('17:00', '18:05', 'round_60_min', 10));

        // 17:00 - 18:09 = 69 menit (1 jam 9 menit) -> 9 < 10 -> 1.0 jam
        $this->assertEquals(1.0, $service->calculateOvertimeDuration('17:00', '18:09', 'round_60_min', 10));

        // 17:00 - 18:10 = 70 menit (1 jam 10 menit) -> 10 >= 10 -> 2.0 jam (dianggap 1 jam penuh)
        $this->assertEquals(2.0, $service->calculateOvertimeDuration('17:00', '18:10', 'round_60_min', 10));

        // 17:00 - 18:15 = 75 menit (1 jam 15 menit) -> 15 >= 10 -> 2.0 jam
        $this->assertEquals(2.0, $service->calculateOvertimeDuration('17:00', '18:15', 'round_60_min', 10));

        // Lembur di bawah 1 jam:
        // 17:00 - 17:08 = 8 menit -> 8 < 10 -> 0.0 jam
        $this->assertEquals(0.0, $service->calculateOvertimeDuration('17:00', '17:08', 'round_60_min', 10));

        // 17:00 - 17:10 = 10 menit -> 10 >= 10 -> 1.0 jam
        $this->assertEquals(1.0, $service->calculateOvertimeDuration('17:00', '17:10', 'round_60_min', 10));

        // Kasus 2: Threshold dinamis disetel = 15 menit
        // 17:00 - 18:10 = 70 menit (1 jam 10 menit) -> 10 < 15 -> 1.0 jam
        $this->assertEquals(1.0, $service->calculateOvertimeDuration('17:00', '18:10', 'round_60_min', 15));

        // 17:00 - 18:15 = 75 menit (1 jam 15 menit) -> 15 >= 15 -> 2.0 jam
        $this->assertEquals(2.0, $service->calculateOvertimeDuration('17:00', '18:15', 'round_60_min', 15));
    }

    public function test_formatted_work_hours_displays_standard_format(): void
    {
        $submissionRound = new OvertimeSubmission([
            'start_time' => '11:00:00',
            'end_time' => '16:00:00',
        ]);
        $this->assertEquals('11:00 - 16:00', $submissionRound->formatted_work_hours);

        $submissionExact = new OvertimeSubmission([
            'start_time' => '21:25:00',
            'end_time' => '23:31:00',
        ]);
        $this->assertEquals('21:25 - 23:31', $submissionExact->formatted_work_hours);
    }

    public function test_karyawan_can_submit_daily_overtime_without_work_hour_range(): void
    {
        $this->employee->update([
            'overtime_rate_per_day' => 175000,
        ]);

        $this->actingAs($this->karyawanUser);

        Livewire::test(OvertimeIndex::class)
            ->set('form.overtime_type', 'daily')
            ->set('form.date', Carbon::today()->format('Y-m-d'))
            ->set('form.start_time', '')
            ->set('form.end_time', '')
            ->set('form.reason', 'Lembur seharian penuh untuk instalasi server klien')
            ->call('storeSubmission')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'overtimeSubmissionModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('overtime_submissions', [
            'employee_id' => $this->employee->id,
            'overtime_type' => 'daily',
            'status' => OvertimeStatus::PENDING_ADMIN->value,
            'submission_source' => OvertimeSubmissionSource::KARYAWAN->value,
            'start_time' => null,
            'end_time' => null,
            'duration_hours' => 1.00,
            'hourly_rate' => 175000.00,
            'total_amount' => 175000.00,
        ]);
    }

    public function test_admin_can_adjust_daily_overtime_without_hour_validation(): void
    {
        $this->employee->update([
            'overtime_rate_per_day' => 200000,
        ]);

        $service = app(OvertimeService::class);
        $submission = OvertimeSubmission::create([
            'reference_number' => $service->generateSubmissionReference(),
            'employee_id' => $this->employee->id,
            'overtime_type' => \App\Enums\OvertimeType::DAILY,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => null,
            'end_time' => null,
            'duration_hours' => 1.00,
            'hourly_rate' => 200000.00,
            'meal_allowance' => 0.00,
            'total_amount' => 200000.00,
            'reason' => 'Lembur seharian maintenance mesin',
            'submission_source' => OvertimeSubmissionSource::KARYAWAN,
            'status' => OvertimeStatus::PENDING_ADMIN,
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(OvertimeIndex::class)
            ->call('openAdjustModal', $submission->id)
            ->set('adjust_meal_allowance', 30000)
            ->set('adjust_admin_notes', 'Disetujui lembur harian dengan tambahan uang makan')
            ->call('submitAdjustment')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'overtimeAdjustModal')
            ->assertDispatched('swal:alert');

        $submission->refresh();
        $this->assertEquals(OvertimeStatus::PENDING_DIREKTUR, $submission->status);
        $this->assertEquals(1.00, (float) $submission->duration_hours);
        $this->assertEquals(30000.00, (float) $submission->meal_allowance);
        $this->assertEquals(230000.00, (float) $submission->total_amount); // 200.000 + 30.000
        $this->assertNull($submission->start_time);
        $this->assertNull($submission->end_time);
    }

    public function test_daily_overtime_helpers_and_accessors(): void
    {
        $submission = new OvertimeSubmission([
            'overtime_type' => \App\Enums\OvertimeType::DAILY,
            'start_time' => null,
            'end_time' => null,
            'duration_hours' => 1.00,
            'hourly_rate' => 250000,
            'meal_allowance' => 25000,
            'total_amount' => 275000,
        ]);

        $this->assertTrue($submission->isDaily());
        $this->assertFalse($submission->isHourly());
        $this->assertEquals('Lembur Harian', $submission->formatted_work_hours);
        $this->assertEquals('1 Hari', $submission->formatted_duration);
        $this->assertEquals('Rp 250.000 / hari', $submission->formatted_rate_label);
        $this->assertEquals(250000.00, $submission->subtotal_overtime);
        $this->assertEquals('Rp 250.000', $submission->formatted_subtotal_overtime);
    }

    public function test_user_can_switch_overtime_type_via_badge_tab(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Overtimes\Index::class)
            ->assertSet('form.overtime_type', 'hourly')
            ->call('setOvertimeType', 'daily')
            ->assertSet('form.overtime_type', 'daily')
            ->call('setOvertimeType', 'hourly')
            ->assertSet('form.overtime_type', 'hourly');
    }

    public function test_admin_can_delete_submission_inputted_by_admin(): void
    {
        $this->employee->update(['overtime_rate_per_day' => 150000]);
        $service = app(OvertimeService::class);
        $submission = $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'daily',
            'date' => Carbon::now()->toDateString(),
            'meal_allowance' => 0,
            'reason' => 'Pemasangan server malam hari oleh admin',
        ], $this->adminUser);

        $this->assertEquals(\App\Enums\OvertimeSubmissionSource::ADMIN, $submission->submission_source);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Overtimes\Index::class)
            ->assertSeeHtml('wire:click="confirmDelete(\'' . $submission->hashid . '\')')
            ->call('confirmDelete', $submission->hashid)
            ->assertDispatched('swal:confirm-delete')
            ->call('deleteSubmission', ['id' => $submission->id])
            ->assertDispatched('swal:alert');

        $this->assertSoftDeleted('overtime_submissions', ['id' => $submission->id]);
    }

    public function test_admin_cannot_delete_submission_locked_in_period(): void
    {
        $this->employee->update(['overtime_rate_per_day' => 150000]);
        $service = app(OvertimeService::class);
        $submission = $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'daily',
            'date' => Carbon::now()->toDateString(),
            'meal_allowance' => 0,
            'reason' => 'Lembur terjadwal',
        ], $this->adminUser);

        // Simulate submission being locked into a period
        $period = OvertimePeriod::create([
            'reference_number' => 'PRD-' . uniqid(),
            'name' => 'Periode Uji',
            'start_date' => Carbon::now()->startOfWeek()->toDateString(),
            'end_date' => Carbon::now()->endOfWeek()->toDateString(),
            'status' => \App\Enums\OvertimePeriodStatus::DRAFT,
            'total_hours' => 1,
            'total_overtime_pay' => 150000,
            'grand_total' => 150000,
            'created_by' => $this->adminUser->id,
        ]);
        $submission->update(['overtime_period_id' => $period->id]);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Overtimes\Index::class)
            ->call('confirmDelete', $submission->id)
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('overtime_submissions', ['id' => $submission->id, 'deleted_at' => null]);
    }

    public function test_direct_to_submission_navigates_and_dispatches_scroll_event(): void
    {
        $service = app(OvertimeService::class);
        $submission = $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'hourly',
            'date' => Carbon::now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'duration_hours' => 2.0,
            'reason' => 'Direct jump test',
        ], $this->adminUser);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Overtimes\Index::class)
            ->set('activeTab', 'periods')
            ->set('statusFilter', 'approved')
            ->call('directToSubmission', $submission->id)
            ->assertSet('activeTab', 'submissions')
            ->assertSet('statusFilter', 'all')
            ->assertDispatched('scroll-to-submission-row', id: $submission->id);
    }

    public function test_review_modals_render_pending_submissions(): void
    {
        $service = app(OvertimeService::class);
        $subAdmin = $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'hourly',
            'date' => Carbon::now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'duration_hours' => 2.0,
            'reason' => 'Admin pending review test',
        ], $this->karyawanUser);

        $subDirector = $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'hourly',
            'date' => Carbon::now()->toDateString(),
            'start_time' => '19:00',
            'end_time' => '21:00',
            'duration_hours' => 2.0,
            'reason' => 'Director pending review test',
        ], $this->karyawanUser);

        // Adjust subDirector from pending_admin to pending_direktur
        $service->adjustAndForwardToDirector($subDirector, [
            'start_time' => '19:00',
            'end_time' => '21:00',
            'duration_hours' => 2.0,
            'meal_allowance' => 15000,
            'admin_notes' => 'Catatan admin untuk direktur',
        ], $this->adminUser);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Overtimes\Index::class)
            ->assertSeeHtml('id="pendingAdminReviewModal"')
            ->assertSeeHtml('id="pendingDirectorReviewModal"')
            ->assertSee('Admin pending review test')
            ->assertSee('Director pending review test')
            ->assertSee('Catatan admin untuk direktur');
    }

    public function test_all_active_employees_are_included_in_period_attendance(): void
    {
        $this->employee->update([
            'employment_status' => EmploymentStatus::HARIAN,
            'daily_rate' => 125000,
        ]);

        $service = app(OvertimeService::class);
        $period = $service->createPeriod([
            'name' => 'Periode Semua Karyawan Test',
            'start_date' => Carbon::now()->startOfWeek()->toDateString(),
            'end_date' => Carbon::now()->endOfWeek()->toDateString(),
        ], $this->adminUser);

        $activeWeeklyEmployeesCount = Employee::where('status', 'active')
            ->where('employment_status', EmploymentStatus::HARIAN)
            ->count();
        $this->assertGreaterThanOrEqual(1, $period->attendances()->count());
        $this->assertEquals($activeWeeklyEmployeesCount, $period->attendances()->count());
    }

    public function test_period_attendance_page_access_and_bulk_set(): void
    {
        $this->employee->update([
            'employment_status' => EmploymentStatus::HARIAN,
            'daily_rate' => 125000,
        ]);

        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        $service = app(OvertimeService::class);
        $period = $service->createPeriod([
            'name' => 'Periode Bulk Test',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ], $this->adminUser);

        // Karyawan cannot access (403)
        $this->actingAs($this->karyawanUser)
            ->get(route('overtimes.periods.attendances', $period->id))
            ->assertForbidden();

        // Admin can access (200)
        $this->actingAs($this->adminUser)
            ->get(route('overtimes.periods.attendances', $period->id))
            ->assertOk()
            ->assertSee('Kehadiran &amp; Uang Makan Mingguan', false);

        // Bulk action test
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Overtimes\PeriodAttendance::class, ['period' => $period])
            ->call('updateAttendedDaysCount', $this->employee->id, 3)
            ->call('setAllEmployeesFullWeek')
            ->call('save');

        $att = \App\Models\OvertimePeriodAttendance::where('overtime_period_id', $period->id)
            ->where('employee_id', $this->employee->id)
            ->first();

        $this->assertEquals(6, $att->attended_days);
    }

    public function test_period_attendance_excludes_zero_attended_days_from_view(): void
    {
        $this->employee->update([
            'employment_status' => EmploymentStatus::HARIAN,
            'daily_rate' => 125000,
        ]);

        Employee::create([
            'department_id' => $this->employee->department_id,
            'position_id' => $this->employee->position_id,
            'full_name' => 'Karyawan Harian Dua',
            'join_date' => Carbon::now()->subMonths(3)->format('Y-m-d'),
            'employment_status' => EmploymentStatus::HARIAN,
            'status' => 'active',
            'daily_rate' => 125000,
        ]);

        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        $service = app(OvertimeService::class);
        $period = $service->createPeriod([
            'name' => 'Periode Filter Nol Hari Test ' . uniqid(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ], $this->adminUser);

        $attendances = $period->attendances()->with('employee')->take(2)->get();
        $this->assertCount(2, $attendances);

        $attZero = $attendances[0];
        $attActive = $attendances[1];

        $attZero->update([
            'attended_days' => 0,
            'attendance_days' => [],
            'total_meal_allowance' => 0,
            'total_daily_wage' => 0,
        ]);

        $attActive->update([
            'attended_days' => 3,
            'attendance_days' => ['fri', 'sat', 'mon'],
            'total_meal_allowance' => 75000,
            'total_daily_wage' => 0,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Overtimes\PeriodAttendance::class, ['period' => $period]);

        $filtered = $component->viewData('filteredAttendances');
        $this->assertArrayHasKey($attActive->employee_id, $filtered);
        $this->assertArrayNotHasKey($attZero->employee_id, $filtered);

        $html = $component->html();
        $this->assertStringContainsString('wire:key="att-row-'.$attActive->employee_id.'"', $html);
        $this->assertStringNotContainsString('wire:key="att-row-'.$attZero->employee_id.'"', $html);
    }

    public function test_print_weekly_excludes_zero_work_days_employees(): void
    {
        $this->employee->update([
            'employment_status' => EmploymentStatus::HARIAN,
            'daily_rate' => 125000,
        ]);

        Employee::create([
            'department_id' => $this->employee->department_id,
            'position_id' => $this->employee->position_id,
            'full_name' => 'Karyawan Harian Tiga',
            'join_date' => Carbon::now()->subMonths(3)->format('Y-m-d'),
            'employment_status' => EmploymentStatus::HARIAN,
            'status' => 'active',
            'daily_rate' => 125000,
        ]);

        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        $service = app(OvertimeService::class);
        $period = $service->createPeriod([
            'name' => 'Periode Cetak Filter Nol Hari Test ' . uniqid(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ], $this->adminUser);

        $attendances = $period->attendances()->with('employee')->take(2)->get();
        $this->assertCount(2, $attendances);

        $attZero = $attendances[0];
        $attActive = $attendances[1];

        $attZero->update([
            'attended_days' => 0,
            'attendance_days' => [],
            'total_meal_allowance' => 0,
            'total_daily_wage' => 0,
        ]);
        \App\Models\OvertimeSubmission::where('overtime_period_id', $period->id)
            ->where('employee_id', $attZero->employee_id)
            ->delete();

        $attActive->update([
            'attended_days' => 3,
            'attendance_days' => ['fri', 'sat', 'mon'],
            'total_meal_allowance' => 75000,
            'total_daily_wage' => 0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('overtimes.periods.print', $period->id));

        $response->assertOk();
        $response->assertSee($attActive->employee->full_name);
        $response->assertDontSee($attZero->employee->full_name);
    }

    public function test_admin_can_pay_and_edit_employee_weekly_salary(): void
    {
        $this->employee->update([
            'employment_status' => EmploymentStatus::HARIAN,
            'daily_rate' => 125000,
        ]);

        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        $service = app(OvertimeService::class);
        $period = $service->createPeriod([
            'name' => 'Periode Bayar Mingguan Test ' . uniqid(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ], $this->adminUser);

        $attendance = $period->attendances()->first();
        $this->assertNotNull($attendance);

        $attendance->update([
            'attended_days' => 5,
            'attendance_days' => ['fri', 'sat', 'mon', 'tue', 'wed'],
            'total_meal_allowance' => 125000,
            'total_daily_wage' => 375000,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($this->adminUser);

        Storage::fake('public');
        $fakeProof = UploadedFile::fake()->image('bukti_transfer.jpg');

        // 1. Bayar Gaji Karyawan
        Livewire::test(PeriodAttendance::class, ['period' => $period])
            ->call('openPayEmployeeModal', $attendance->employee_id)
            ->assertDispatched('open-modal', id: 'payEmployeeModal')
            ->set('itemPaymentDate', '2026-10-28')
            ->set('itemPaymentMethod', 'bank_transfer')
            ->set('itemBankName', 'BCA Demo')
            ->set('itemBankAccountNumber', '1234567890')
            ->set('itemBankAccountName', 'Karyawan Demo')
            ->set('itemPaymentProof', $fakeProof)
            ->set('itemPaymentNotes', 'Gaji mingguan dibayar penuh via transfer BCA')
            ->call('processEmployeePayment')
            ->assertDispatched('close-modal', id: 'payEmployeeModal')
            ->assertDispatched('swal:alert');

        $attendance->refresh();
        $this->assertEquals('paid', $attendance->payment_status);
        $this->assertEquals('BCA Demo', $attendance->bank_name);
        $this->assertEquals('1234567890', $attendance->bank_account_number);
        $this->assertNotNull($attendance->payment_proof_path);
        Storage::disk('public')->assertExists($attendance->payment_proof_path);
        $this->assertEquals($this->adminUser->id, $attendance->paid_by);

        // 2. Buka Modal Detail Pembayaran
        Livewire::test(PeriodAttendance::class, ['period' => $period])
            ->call('openPaymentDetailModal', $attendance->employee_id)
            ->assertDispatched('open-modal', id: 'paymentDetailModal');

        // 3. Edit Pembayaran
        Livewire::test(PeriodAttendance::class, ['period' => $period])
            ->call('openEditPaymentModal', $attendance->employee_id)
            ->assertDispatched('close-modal', id: 'paymentDetailModal')
            ->assertDispatched('open-modal', id: 'payEmployeeModal')
            ->set('itemPaymentNotes', 'Catatan diperbarui')
            ->call('processEmployeePayment')
            ->assertDispatched('close-modal', id: 'payEmployeeModal')
            ->assertDispatched('swal:alert');

        $attendance->refresh();
        $this->assertEquals('Catatan diperbarui', $attendance->payment_notes);
    }

    public function test_karyawan_can_submit_overnight_overtime_crossing_midnight(): void
    {
        Livewire::actingAs($this->karyawanUser)
            ->test(\App\Livewire\Overtimes\Index::class)
            ->call('openCreateModal')
            ->set('form.overtime_type', 'hourly')
            ->set('form.date', '2026-10-28')
            ->set('form.start_time', '22:00')
            ->set('form.end_time', '02:00')
            ->set('form.reason', 'Lembur shift malam monitoring deployment server')
            ->call('storeSubmission')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'overtimeSubmissionModal')
            ->assertDispatched('swal:alert');

        $submission = OvertimeSubmission::where('employee_id', $this->employee->id)
            ->where('date', '2026-10-28')
            ->firstOrFail();

        $this->assertEquals('22:00:00', $submission->start_time);
        $this->assertEquals('02:00:00', $submission->end_time);
        $this->assertEquals(4.0, (float) $submission->duration_hours);
    }

    public function test_karyawan_cannot_submit_overlapping_hourly_overtime(): void
    {
        $service = app(OvertimeService::class);

        // Submit first overtime 18:00 - 21:00 on 2026-11-10
        $sub1 = $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'hourly',
            'date' => '2026-11-10',
            'start_time' => '18:00',
            'end_time' => '21:00',
            'reason' => 'Tugas Awal',
            'meal_allowance' => 0,
        ], $this->karyawanUser);

        $this->assertNotNull($sub1);

        // Try submitting overlapping overtime 19:30 - 22:30 on same date
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('bertabrakan dengan pengajuan lembur yang sudah ada');

        $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'hourly',
            'date' => '2026-11-10',
            'start_time' => '19:30',
            'end_time' => '22:30',
            'reason' => 'Tugas Tumpang Tindih',
            'meal_allowance' => 0,
        ], $this->karyawanUser);
    }

    public function test_karyawan_cannot_submit_duplicate_daily_overtime(): void
    {
        $this->employee->update(['overtime_rate_per_day' => 150000]);
        $service = app(OvertimeService::class);

        // Submit first daily overtime on 2026-11-12
        $sub1 = $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'daily',
            'date' => '2026-11-12',
            'reason' => 'Lembur Seharian Proyek A',
            'meal_allowance' => 0,
        ], $this->karyawanUser);

        $this->assertNotNull($sub1);

        // Try submitting another daily overtime on same date
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah memiliki pengajuan lembur harian');

        $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'daily',
            'date' => '2026-11-12',
            'reason' => 'Lembur Seharian Proyek B',
            'meal_allowance' => 0,
        ], $this->karyawanUser);
    }

    public function test_atomic_sequence_reservation_for_overtime_submission_and_period(): void
    {
        $service = app(OvertimeService::class);
        $now = Carbon::parse('2026-11-01');

        $ref1 = $service->generateSubmissionReference($now);
        $ref2 = $service->generateSubmissionReference($now);

        $this->assertStringStartsWith('LMB-202611-', $ref1);
        $this->assertStringStartsWith('LMB-202611-', $ref2);
        $this->assertNotEquals($ref1, $ref2);

        $periodRef1 = $service->generatePeriodReference($now);
        $periodRef2 = $service->generatePeriodReference($now);

        $this->assertStringStartsWith('PER-LMB-2026-', $periodRef1);
        $this->assertStringStartsWith('PER-LMB-2026-', $periodRef2);
        $this->assertNotEquals($periodRef1, $periodRef2);

        // Verify rows exist in overtime_sequences table
        $this->assertDatabaseHas('overtime_sequences', [
            'sequence_key' => 'LMB-202611',
        ]);
        $this->assertDatabaseHas('overtime_sequences', [
            'sequence_key' => 'PER-LMB-' . $now->format('Y-W'),
        ]);
    }

    public function test_duration_and_meal_allowance_tampering_via_client_payload_is_neutralized(): void
    {
        $service = app(OvertimeService::class);

        // Karyawan submits 1 hour overtime (17:00 to 18:00), but injects 10 hours and 500,000 meal allowance
        $submission = $service->createSubmission([
            'employee_id' => $this->employee->id,
            'overtime_type' => 'hourly',
            'date' => '2026-11-20',
            'start_time' => '17:00',
            'end_time' => '18:00',
            'duration_hours' => 10.0, // Injected tampering
            'meal_allowance' => 500000, // Injected tampering
            'reason' => 'Uji Coba Manipulasi Client Side',
        ], $this->karyawanUser);

        // Must strictly use calculated duration (1.0 hour) and ignore meal allowance (0)
        $this->assertEquals(1.0, (float) $submission->duration_hours);
        $this->assertEquals(0, (float) $submission->meal_allowance);
        $this->assertEquals(25000, (float) $submission->total_amount); // 1 hr * 25,000 rate
    }
}
