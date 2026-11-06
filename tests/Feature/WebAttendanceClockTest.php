<?php

namespace Tests\Feature;

use App\Enums\OvertimeStatus;
use App\Livewire\Attendances\DashboardClock;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimeSubmission;
use App\Models\WorkLocation;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WebAttendanceClockTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_haversine_distance_calculation(): void
    {
        $service = app(AttendanceService::class);

        // Subang to Bandung is roughly ~40-45 km
        $dist = $service->calculateHaversineDistance(-6.5715, 107.7587, -6.9175, 107.6191);
        $this->assertGreaterThan(35000, $dist);
        $this->assertLessThan(55000, $dist);

        // Exact same coordinate should be 0 meters
        $zeroDist = $service->calculateHaversineDistance(-6.5715, 107.7587, -6.5715, 107.7587);
        $this->assertEquals(0.0, $zeroDist);
    }

    public function test_employee_can_clock_in_within_geofence_as_approved(): void
    {
        $office = WorkLocation::create([
            'name' => 'Kantor Pusat Subang',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $user = $this->createKaryawanUser();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'work_location_id' => $office->id,
            'is_free_location' => false,
        ]);

        $this->actingAs($user);

        // Dummy 1x1 transparent PNG base64
        $fakePhotoBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        Livewire::test(DashboardClock::class)
            ->call('recordClock', 'clock_in', $fakePhotoBase64, -6.5715100, 107.7587100, null, 'Test Browser')
            ->assertDispatched('close-web-clock-modal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'status' => 'hadir',
            'review_status' => 'approved',
            'source' => 'web_gps',
        ]);
    }

    public function test_employee_outside_geofence_records_pending_review(): void
    {
        $office = WorkLocation::create([
            'name' => 'Kantor Pusat Subang',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 50,
            'is_active' => true,
        ]);

        $user = $this->createKaryawanUser();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'work_location_id' => $office->id,
            'is_free_location' => false,
        ]);

        $this->actingAs($user);

        $fakePhotoBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        Livewire::test(DashboardClock::class)
            ->call('recordClock', 'clock_in', $fakePhotoBase64, -6.6000000, 107.7587000, 'Maintenance server di site klien PT ABC', 'Test Browser')
            ->assertDispatched('close-web-clock-modal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'review_status' => 'pending_review',
            'notes' => '[Masuk] Maintenance server di site klien PT ABC',
        ]);
    }

    public function test_free_location_employee_records_approved_anywhere(): void
    {
        $user = $this->createKaryawanUser();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'is_free_location' => true,
        ]);

        $this->actingAs($user);

        $fakePhotoBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        Livewire::test(DashboardClock::class)
            ->call('recordClock', 'clock_in', $fakePhotoBase64, -6.9175000, 107.6191000, null, 'Test Browser')
            ->assertDispatched('close-web-clock-modal');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'review_status' => 'approved',
        ]);
    }

    public function test_employee_can_clock_out_with_photo_and_location(): void
    {
        $office = WorkLocation::create([
            'name' => 'Kantor Pusat Subang',
            'latitude' => -6.5715000,
            'longitude' => 107.7587000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $user = $this->createKaryawanUser();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'work_location_id' => $office->id,
            'is_free_location' => false,
        ]);

        // Pre-create clock in
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '08:00:00',
            'status' => 'hadir',
            'source' => 'web_gps',
            'review_status' => 'approved',
        ]);

        $this->actingAs($user);

        $fakePhotoBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        Livewire::test(DashboardClock::class)
            ->call('recordClock', 'clock_out', $fakePhotoBase64, -6.5715100, 107.7587100, 'Selesai shift kantor', 'Test Browser')
            ->assertDispatched('close-web-clock-modal');

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($attendance->clock_out);
        $this->assertNotNull($attendance->clock_out_photo_path);
        $this->assertEquals(-6.5715100, (float) $attendance->clock_out_latitude);
    }

    public function test_employee_cannot_clock_in_twice_in_one_day(): void
    {
        $user = $this->createKaryawanUser();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '08:00:00',
            'status' => 'hadir',
            'source' => 'web_gps',
            'review_status' => 'approved',
        ]);

        $this->actingAs($user);

        Livewire::test(DashboardClock::class)
            ->call('recordClock', 'clock_in', null, -6.5715, 107.7587, null, 'Test Browser')
            ->assertDispatched('swal:alert', function ($event, $params) {
                $payload = $params[0] ?? $params;

                return $payload['title'] === 'Presensi Sudah Tercatat';
            });
    }

    public function test_employee_cannot_clock_out_before_clock_in(): void
    {
        $user = $this->createKaryawanUser();
        Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(DashboardClock::class)
            ->call('openClockModal', 'clock_out')
            ->assertDispatched('swal:alert', function ($event, $params) {
                $payload = $params[0] ?? $params;

                return $payload['title'] === 'Belum Absen Masuk';
            });
    }

    public function test_open_clock_modal_dispatches_read_only_when_already_clocked_in(): void
    {
        $user = $this->createKaryawanUser();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '08:15:00',
            'clock_in_latitude' => -6.21639,
            'clock_in_longitude' => 106.89479,
            'status' => 'hadir',
            'source' => 'web_gps',
            'review_status' => 'approved',
        ]);

        $this->actingAs($user);

        Livewire::test(DashboardClock::class)
            ->call('openClockModal', 'clock_in')
            ->assertDispatched('open-web-clock-modal', function ($event, $params) {
                $payload = $params[0] ?? $params;

                return $payload['isReadOnly'] === true
                    && (float) $payload['readOnlyData']['latitude'] === -6.21639;
            });
    }

    public function test_admin_can_approve_pending_attendance(): void
    {
        $admin = $this->createAdminUser();
        $employee = Employee::factory()->create();

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '08:15:00',
            'status' => 'hadir',
            'source' => 'web_gps',
            'review_status' => 'pending_review',
            'notes' => 'Tugas luar kota mendadak',
        ]);

        $service = app(AttendanceService::class);
        $updated = $service->reviewAttendance($attendance, 'approved', 'Disetujui atas arahan Pak Direktur', $admin);

        $this->assertEquals('approved', $updated->review_status);
        $this->assertEquals('hadir', $updated->status);
        $this->assertEquals($admin->id, $updated->reviewed_by);
        $this->assertNotNull($updated->reviewed_at);
        $this->assertStringContainsString('Disetujui atas arahan Pak Direktur', $updated->review_notes);
    }

    public function test_admin_can_reject_pending_attendance(): void
    {
        $admin = $this->createAdminUser();
        $employee = Employee::factory()->create();

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '08:15:00',
            'status' => 'hadir',
            'source' => 'web_gps',
            'review_status' => 'pending_review',
            'notes' => 'Ngopi di mall',
        ]);

        $service = app(AttendanceService::class);
        $updated = $service->reviewAttendance($attendance, 'rejected', 'Lokasi tidak sesuai tanpa surat penugasan', $admin);

        $this->assertEquals('rejected', $updated->review_status);
        $this->assertEquals('absen', $updated->status); // Ditolak -> diubah jadi absen/mangkir
        $this->assertEquals($admin->id, $updated->reviewed_by);
        $this->assertNotNull($updated->reviewed_at);
    }

    public function test_employee_must_clock_out_before_starting_overtime_on_regular_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 17:30:00')); // Rabu (hari kerja)

        $user = $this->createKaryawanUser();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'is_free_location' => true,
        ]);

        // Absen masuk tapi belum absen pulang
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '08:00:00',
            'status' => 'hadir',
            'source' => 'web_gps',
        ]);

        $this->actingAs($user);

        Livewire::test(DashboardClock::class)
            ->call('openClockModal', 'overtime_in')
            ->assertDispatched('swal:alert', function ($event, $params) {
                $payload = $params[0] ?? $params;

                return $payload['type'] === 'warning'
                    && str_contains($payload['title'], 'Wajib Absen Pulang Terlebih Dahulu');
            });

        Carbon::setTestNow();
    }

    public function test_employee_can_clock_in_and_out_for_overtime_and_sync_submission(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 18:00:00'));

        $user = $this->createKaryawanUser();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'overtime_rate_per_hour' => 25000,
            'is_free_location' => true,
        ]);

        // Karyawan sudah selesai shift reguler (sudah absen pulang)
        $att = Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'status' => 'hadir',
            'source' => 'web_gps',
        ]);

        $this->actingAs($user);

        // 1. Mulai Lembur
        Livewire::test(DashboardClock::class)
            ->call(
                'recordClock',
                'overtime_in',
                'data:image/jpeg;base64,'.base64_encode('fake_overtime_in'),
                -6.5715,
                107.7587,
                'Lembur cetak spanduk event',
                'Chrome / Linux'
            )
            ->assertDispatched('swal:alert', function ($event, $params) {
                $payload = $params[0] ?? $params;

                return $payload['type'] === 'success' && str_contains($payload['title'], 'Mulai Lembur');
            });

        $att->refresh();
        $this->assertTrue((bool) $att->is_overtime);
        $this->assertEquals('in_progress', $att->overtime_status);
        $this->assertNotNull($att->overtime_in);
        $this->assertNotNull($att->overtime_in_photo_path);

        // 2. Selesai Lembur 3 jam kemudian (21:00)
        Carbon::setTestNow(Carbon::parse('2026-11-04 21:00:00'));

        Livewire::test(DashboardClock::class)
            ->call(
                'recordClock',
                'overtime_out',
                'data:image/jpeg;base64,'.base64_encode('fake_overtime_out'),
                -6.5715,
                107.7587,
                'Selesai cetak',
                'Chrome / Linux'
            )
            ->assertDispatched('swal:alert', function ($event, $params) {
                $payload = $params[0] ?? $params;

                return $payload['type'] === 'success' && str_contains($payload['title'], 'Selesai Lembur');
            });

        $att->refresh();
        $this->assertEquals('completed', $att->overtime_status);
        $this->assertNotNull($att->overtime_out);
        $this->assertEquals(180, $att->overtime_minutes);

        // Pastikan OvertimeSubmission otomatis dibuat untuk verifikasi admin
        $submission = OvertimeSubmission::where('attendance_id', $att->id)->first();
        $this->assertNotNull($submission);
        $this->assertEquals($employee->id, $submission->employee_id);
        $this->assertEquals(OvertimeStatus::PENDING_ADMIN, $submission->status);
        $this->assertEquals(3.0, (float) $submission->duration_hours);
        $this->assertEquals(25000.0, (float) $submission->hourly_rate);

        Carbon::setTestNow();
    }

    public function test_overtime_auto_cutoff_at_06_am_closes_orphaned_session(): void
    {
        $user = $this->createKaryawanUser();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'is_free_location' => true,
        ]);

        // Karyawan lembur kemarin malam pukul 19:00 dan lupa tap selesai
        $yesterday = Carbon::parse('2026-11-03');
        $att = Attendance::create([
            'employee_id' => $employee->id,
            'date' => $yesterday->toDateString(),
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'status' => 'hadir',
            'is_overtime' => true,
            'overtime_status' => 'in_progress',
            'overtime_in' => '19:00:00',
            'source' => 'web_gps',
        ]);

        // Keesokan harinya pukul 06:15 pagi
        Carbon::setTestNow(Carbon::parse('2026-11-04 06:15:00'));

        $service = app(AttendanceService::class);
        $service->processEmployeeOvertimeAutoCutoff($employee);

        $att->refresh();
        $this->assertEquals('auto_cutoff', $att->overtime_status);
        $this->assertEquals('06:00:00', $att->overtime_out);
        $this->assertStringContainsString('Auto Cut-off 06:00', $att->notes);

        // Submission otomatis dibuat dengan status pending_admin
        $submission = OvertimeSubmission::where('attendance_id', $att->id)->first();
        $this->assertNotNull($submission);
        $this->assertEquals(OvertimeStatus::PENDING_ADMIN, $submission->status);
        $this->assertStringContainsString('Auto Cut-off 06:00', $submission->reason);

        Carbon::setTestNow();
    }
}
