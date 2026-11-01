<?php

namespace Tests\Feature;

use App\Enums\EmploymentStatus;
use App\Livewire\Attendances\Index;
use App\Models\Attendance;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\OvertimePeriod;
use App\Models\OvertimePeriodAttendance;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Database\Seeders\EmployeeSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $karyawanRole = Role::firstOrCreate(['name' => 'karyawan'], ['display_name' => 'Karyawan']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin_test_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->adminUser->addRole($adminRole);

        $this->karyawanUser = User::factory()->create([
            'email' => 'karyawan_test_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->karyawanUser->addRole($karyawanRole);

        $dept = Department::firstOrCreate(['name' => 'Operasional']);
        $pos = Position::firstOrCreate(['name' => 'Staff']);

        $this->employee = Employee::firstOrCreate(
            ['fingerprint_id' => '999'],
            [
                'user_id' => $this->karyawanUser->id,
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'full_name' => 'Karyawan Test Presensi',
                'join_date' => Carbon::now()->subYears(1)->toDateString(),
                'employment_status' => EmploymentStatus::HARIAN,
                'status' => 'active',
                'basic_salary' => 125000,
                'overtime_rate_per_hour' => 25000,
                'overtime_rate_per_day' => 125000,
                'meal_allowance_per_day' => 25000,
                'meal_allowance_type' => 'standar',
            ]
        );
    }

    public function test_admin_can_access_attendances_index_page(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Rekap Presensi &amp; Jam Kerja Mesin Fingerprint', false)
            ->assertSee('Import Excel Mesin')
            ->assertSee('Matriks Kehadiran', false)
            ->assertSee('REKAP KEHADIRAN', false)
            ->assertSee('modern-attendance-matrix', false)
            ->assertSee('bd-callout-info', false)
            ->assertSee('table-group-header', false);
    }

    public function test_karyawan_can_access_attendances_index_page(): void
    {
        $this->actingAs($this->karyawanUser)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Rekap Presensi &amp; Jam Kerja Mesin Fingerprint', false)
            ->assertDontSee('Import Excel Mesin');
    }

    public function test_admin_can_create_manual_attendance(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->set('manualEmployeeId', $this->employee->id)
            ->set('manualDate', Carbon::today()->toDateString())
            ->set('manualClockIn', '07:55')
            ->set('manualClockOut', '17:05')
            ->set('manualStatus', 'hadir')
            ->set('manualNotes', 'Presensi manual lembur awal')
            ->call('saveManualAttendance')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'manualAttendanceModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '07:55:00',
            'status' => 'hadir',
            'source' => 'manual',
        ]);
    }

    public function test_admin_can_import_real_fingerprint_excel_report(): void
    {
        $realFilePath = '/home/midpc/Downloads/1_StandardReport.xls';

        if (! file_exists($realFilePath)) {
            $this->markTestSkipped('Sample file 1_StandardReport.xls does not exist.');
        }

        $this->seed(EmployeeSeeder::class);

        $fakeFile = UploadedFile::fake()->createWithContent(
            '1_StandardReport.xls',
            file_get_contents($realFilePath)
        );

        // Test service directly and component import
        $service = app(AttendanceService::class);
        $result = $service->importFingerprintReport($realFilePath, $this->adminUser);

        $this->assertGreaterThan(0, $result['records_saved']);
        $this->assertEquals(64, $result['total_parsed']);

        $this->assertDatabaseHas('attendances', [
            'date' => '2026-10-16',
            'source' => 'fingerprint',
        ]);
    }

    public function test_import_aborts_and_reports_unregistered_fingerprint_id(): void
    {
        $realFilePath = '/home/midpc/Downloads/1_StandardReport.xls';
        if (! file_exists($realFilePath)) {
            $this->markTestSkipped('Sample file 1_StandardReport.xls does not exist.');
        }

        $emp = Employee::where('fingerprint_id', '17')->first();
        if (! $emp) {
            $this->markTestSkipped('Employee with fingerprint_id 17 not found.');
        }

        $originalFid = $emp->fingerprint_id;
        $emp->update(['fingerprint_id' => 'UNKNOWN_PIN_9999']);

        try {
            $service = app(AttendanceService::class);
            $result = $service->importFingerprintReport($realFilePath, $this->adminUser);

            $this->assertEquals('unregistered_fingerprint', $result['status']);
            $this->assertEquals(0, $result['records_saved']);
            $this->assertGreaterThan(0, $result['unmatched_count']);
            $this->assertEquals('17', $result['unregistered_list'][0]['fingerprint_id']);
        } finally {
            $emp->update(['fingerprint_id' => $originalFid]);
        }
    }

    public function test_import_preserves_manual_attendance_corrections(): void
    {
        $realFilePath = '/home/midpc/Downloads/1_StandardReport.xls';
        if (! file_exists($realFilePath)) {
            $this->markTestSkipped('Sample file 1_StandardReport.xls does not exist.');
        }

        $emp = Employee::where('fingerprint_id', '10')->first();
        if (! $emp) {
            $this->markTestSkipped('Employee with fingerprint_id 10 not found.');
        }

        $manual = Attendance::updateOrCreate(
            ['employee_id' => $emp->id, 'date' => '2026-10-16'],
            [
                'clock_in' => '07:00:00',
                'clock_out' => '17:00:00',
                'status' => 'hadir',
                'duration_minutes' => 600,
                'source' => 'manual',
                'notes' => 'Koreksi admin: lupa tap pulang',
            ]
        );

        $service = app(AttendanceService::class);
        $result = $service->importFingerprintReport($realFilePath, $this->adminUser);

        $this->assertEquals('success', $result['status']);
        $this->assertGreaterThan(0, $result['manual_preserved']);

        $fresh = $manual->fresh();
        $this->assertEquals('17:00:00', $fresh->clock_out);
        $this->assertEquals('manual', $fresh->source);
        $this->assertEquals('Koreksi admin: lupa tap pulang', $fresh->notes);
        $this->assertNotNull($fresh->raw_log);
    }

    public function test_sync_attendances_to_weekly_period(): void
    {
        // Paksa jadwal 6 hari kerja & bersihkan libur nasional agar deterministik
        CompanyProfile::getProfile()->update(['work_days_per_week' => 6]);
        Holiday::whereBetween('date', ['2026-10-16', '2026-10-23'])->delete();

        // 1. Create dummy attendance records in period date range
        $startDate = Carbon::parse('2026-10-16');
        $endDate = Carbon::parse('2026-10-23');

        Attendance::updateOrCreate(
            ['employee_id' => $this->employee->id, 'date' => '2026-10-16'],
            ['clock_in' => '07:00:00', 'clock_out' => '17:00:00', 'status' => 'hadir']
        );
        Attendance::updateOrCreate(
            ['employee_id' => $this->employee->id, 'date' => '2026-10-17'],
            ['clock_in' => '07:00:00', 'clock_out' => '17:00:00', 'status' => 'hadir']
        );

        $period = OvertimePeriod::create([
            'reference_number' => 'PER-SYNC-'.uniqid(),
            'name' => 'Periode Sync Test',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'created_by' => $this->adminUser->id,
        ]);

        $service = app(AttendanceService::class);
        $synced = $service->syncToWeeklyOvertimePeriod($period, $this->adminUser);

        $this->assertGreaterThan(0, $synced);

        $this->assertDatabaseHas('overtime_period_attendances', [
            'overtime_period_id' => $period->id,
            'employee_id' => $this->employee->id,
            'attended_days' => 2,
        ]);
    }

    public function test_admin_can_navigate_months(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->call('previousMonth')
            ->assertSet('startDate', fn ($date) => str_ends_with($date, '-01'))
            ->call('nextMonth')
            ->assertSet('startDate', fn ($date) => str_ends_with($date, '-01'))
            ->call('goToCurrentMonth')
            ->assertSet('startDate', Carbon::now()->startOfMonth()->toDateString())
            ->assertSet('endDate', Carbon::now()->endOfMonth()->toDateString());
    }

    public function test_matrix_displays_manual_correction_with_primary_badge_and_legend(): void
    {
        $date1 = '2026-10-10';
        $date2 = '2026-10-12';

        Attendance::updateOrCreate(
            ['employee_id' => $this->employee->id, 'date' => $date1],
            ['clock_in' => '08:00:00', 'clock_out' => '17:00:00', 'status' => 'hadir', 'source' => 'fingerprint']
        );

        Attendance::updateOrCreate(
            ['employee_id' => $this->employee->id, 'date' => $date2],
            ['clock_in' => '08:15:00', 'clock_out' => '17:15:00', 'status' => 'hadir', 'source' => 'manual', 'notes' => 'Koreksi jam pulang admin']
        );

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->set('startDate', '2026-10-01')
            ->set('endDate', '2026-10-31')
            ->assertSee('Petunjuk Presensi &amp; Keterangan Warna', false)
            ->assertSee('Hadir (Mesin Fingerprint)')
            ->assertSee('Koreksi / Input Manual')
            ->assertSeeHtml('att-chip-manual')
            ->assertSeeHtml('att-chip-present')
            ->assertSeeHtml('<optgroup label=')
            ->assertSee('ID Finger:')
            ->assertSee('Manual');
    }

    public function test_admin_can_delete_attendance_after_confirmation(): void
    {
        $att = Attendance::updateOrCreate(
            ['employee_id' => $this->employee->id, 'date' => '2026-10-28'],
            [
                'clock_in' => '08:00:00',
                'clock_out' => '17:00:00',
                'status' => 'hadir',
                'source' => 'manual',
            ]
        );

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->call('confirmDeleteAttendance', $att->id)
            ->assertDispatched('swal:confirm-delete')
            ->dispatch('delete-attendance', id: $att->id)
            ->assertDispatched('close-modal', id: 'manualAttendanceModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('attendances', [
            'id' => $att->id,
        ]);
    }

    public function test_admin_can_reset_manual_correction_back_to_original_fingerprint_log(): void
    {
        $att = Attendance::updateOrCreate(
            ['employee_id' => $this->employee->id, 'date' => '2026-10-29'],
            [
                'clock_in' => '08:30:00',
                'clock_out' => '17:00:00',
                'status' => 'hadir',
                'source' => 'manual',
                'raw_log' => '07:45 17:10',
                'notes' => 'Koreksi salah input',
            ]
        );

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->call('resetToFingerprint', $att->id)
            ->assertDispatched('close-modal', id: 'manualAttendanceModal')
            ->assertDispatched('swal:alert');

        $fresh = $att->fresh();
        $this->assertEquals('07:45:00', $fresh->clock_in);
        $this->assertEquals('17:10:00', $fresh->clock_out);
        $this->assertEquals('fingerprint', $fresh->source);
        $this->assertNull($fresh->notes);
    }

    public function test_sync_to_weekly_overtime_period_calculates_daily_wage_for_harian_employee(): void
    {
        // Paksa jadwal 6 hari kerja & bersihkan libur nasional agar deterministik
        CompanyProfile::getProfile()->update(['work_days_per_week' => 6]);
        Holiday::whereBetween('date', ['2026-10-19', '2026-10-25'])->delete();

        $this->employee->update([
            'daily_rate' => 125000,
            'meal_allowance_per_day' => 25000,
        ]);

        $period = OvertimePeriod::create([
            'reference_number' => 'OTP-WEEKLY-'.uniqid(),
            'name' => 'Lembur Mingguan W43',
            'start_date' => '2026-10-19',
            'end_date' => '2026-10-25',
            'created_by' => $this->adminUser->id,
            'status' => 'draft',
        ]);

        // Attendance from Monday to Saturday (6 days)
        $dates = ['2026-10-19', '2026-10-20', '2026-10-21', '2026-10-22', '2026-10-23', '2026-10-24'];
        foreach ($dates as $d) {
            Attendance::updateOrCreate(
                ['employee_id' => $this->employee->id, 'date' => $d],
                [
                    'clock_in' => '08:00:00',
                    'clock_out' => '17:00:00',
                    'status' => 'hadir',
                    'source' => 'fingerprint',
                ]
            );
        }

        $service = app(AttendanceService::class);
        $synced = $service->syncToWeeklyOvertimePeriod($period, $this->adminUser);
        $this->assertGreaterThan(0, $synced);

        $periodAtt = $period->attendances()->where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertEquals(6, $periodAtt->attended_days);
        $this->assertEquals(125000, (float) $periodAtt->daily_rate);
        $this->assertEquals(750000, (float) $periodAtt->total_daily_wage);
        $this->assertEquals(150000, (float) $periodAtt->total_meal_allowance);

        $this->assertEquals(900000, (float) ($periodAtt->total_daily_wage + $periodAtt->total_meal_allowance));
        $this->assertGreaterThanOrEqual(900000, (float) $period->fresh()->grand_total);

        // Test weekly slip print
        $response = $this->actingAs($this->adminUser)
            ->get(route('overtimes.periods.print', [$period, $this->employee]));
        $response->assertOk();
        $response->assertSee('Upah Harian');
        $response->assertSee('750.000');
    }

    public function test_import_fingerprint_kartu_laporan_format_with_dynamic_blocks_and_overtime(): void
    {
        $dept = Department::firstOrCreate(['name' => 'Produksi']);
        $pos = Position::firstOrCreate(['name' => 'Operator']);

        $empAndri = Employee::firstOrCreate(
            ['fingerprint_id' => '5'],
            [
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'full_name' => 'Andri',
                'join_date' => '2025-01-01',
                'employment_status' => EmploymentStatus::HARIAN,
                'status' => 'active',
                'daily_rate' => 100000,
            ]
        );

        $empJojo = Employee::firstOrCreate(
            ['fingerprint_id' => '8'],
            [
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'full_name' => 'Jojo',
                'join_date' => '2025-01-01',
                'employment_status' => EmploymentStatus::TETAP,
                'status' => 'active',
                'basic_salary' => 3000000,
            ]
        );

        $empZaelani = Employee::firstOrCreate(
            ['fingerprint_id' => '91'],
            [
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'full_name' => 'Zaelani',
                'join_date' => '2025-01-01',
                'employment_status' => EmploymentStatus::HARIAN,
                'status' => 'active',
                'daily_rate' => 100000,
            ]
        );

        // Buat file spreadsheet excel dengan 2 sheet Kartu Laporan
        $spreadsheet = new Spreadsheet;

        // Sheet 1: 8.5.78 (memuat Jojo di base Col A, Andri di base Col P)
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('8.5.78');

        $sheet1->setCellValue('A1', 'Kartu Laporan');
        $sheet1->setCellValue('A2', 'Tgl Absen:');
        $sheet1->setCellValue('D2', '2026-10-16 ~ 2026-10-23');

        // Jojo (Col A-N): baseCol = 1 (A)
        $sheet1->setCellValue('A3', 'Dept.');
        $sheet1->setCellValue('B3', 'KANTOR');
        $sheet1->setCellValue('I3', 'Nama');
        $sheet1->setCellValue('J3', 'Jojo');
        $sheet1->setCellValue('A4', 'Tgl');
        $sheet1->setCellValue('B4', '2026-10-16 ~ 2026-10-23');
        $sheet1->setCellValue('I4', 'ID');
        $sheet1->setCellValue('J4', '8');

        $sheet1->setCellValue('A12', '16 JUM');
        $sheet1->setCellValue('B12', '07:55');
        $sheet1->setCellValue('D12', '17:05');

        $sheet1->setCellValue('A14', '18 MIN');
        $sheet1->setCellValue('B14', '08:00'); // Hari Minggu (Karyawan Tetap -> harus diproteksi jadi null/libur)

        // Andri (Col P-AC): baseCol = 16 (P)
        $sheet1->setCellValue('P3', 'Dept.');
        $sheet1->setCellValue('Q3', 'HARIAN');
        $sheet1->setCellValue('X3', 'Nama');
        $sheet1->setCellValue('Y3', 'ANDRI');
        $sheet1->setCellValue('P4', 'Tgl');
        $sheet1->setCellValue('Q4', '2026-10-16 ~ 2026-10-23');
        $sheet1->setCellValue('X4', 'ID');
        $sheet1->setCellValue('Y4', '5');

        // 16 JUM: In 06:40, Pulang kosong, Lembur Masuk 17:40, Lembur Keluar kosong
        $sheet1->setCellValue('P12', '16 JUM');
        $sheet1->setCellValue('Q12', '06:40');
        $sheet1->setCellValue('Z12', '17:40');

        // 18 MIN: In kosong, Lembur Masuk 17:25
        $sheet1->setCellValue('P14', '18 MIN');
        $sheet1->setCellValue('Z14', '17:25');

        // 20 SIL: In 06:29, Lembur Masuk 16:59, Lembur Keluar 18:03
        $sheet1->setCellValue('P16', '20 SIL');
        $sheet1->setCellValue('Q16', '06:29');
        $sheet1->setCellValue('Z16', '16:59');
        $sheet1->setCellValue('AB16', '18:03');

        // Sheet 2: 91 (hanya 1 karyawan: Zaelani di Col A)
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('91');
        $sheet2->setCellValue('A1', 'Kartu Laporan');
        $sheet2->setCellValue('A2', 'Tgl Absen:');
        $sheet2->setCellValue('D2', '2026-10-16 ~ 2026-10-23');

        $sheet2->setCellValue('A3', 'Dept.');
        $sheet2->setCellValue('B3', 'HARIAN');
        $sheet2->setCellValue('I3', 'Nama');
        $sheet2->setCellValue('J3', 'ZAELANI');
        $sheet2->setCellValue('A4', 'Tgl');
        $sheet2->setCellValue('B4', '2026-10-16 ~ 2026-10-23');
        $sheet2->setCellValue('I4', 'ID');
        $sheet2->setCellValue('J4', '91');

        $sheet2->setCellValue('A12', '16 JUM');
        $sheet2->setCellValue('B12', '06:30');

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_kl_').'.xls';
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($tmpFile);

        $service = app(AttendanceService::class);
        $result = $service->importFingerprintReport($tmpFile, $this->adminUser);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(3, $result['matched_count']);

        // Verifikasi Andri:
        // 1. Tgl 16: clock_in = 06:40, clock_out = null, overtime_in = 17:40, overtime_out = null, status = hadir
        $attAndri16 = Attendance::where('employee_id', $empAndri->id)->where('date', '2026-10-16')->firstOrFail();
        $this->assertEquals('06:40:00', $attAndri16->clock_in);
        $this->assertNull($attAndri16->clock_out);
        $this->assertEquals('17:40:00', $attAndri16->overtime_in);
        $this->assertNull($attAndri16->overtime_out);
        $this->assertEquals('hadir', $attAndri16->status);

        // 2. Tgl 18 (Minggu): clock_in = null, clock_out = null, overtime_in = 17:25, status = libur
        $attAndri18 = Attendance::where('employee_id', $empAndri->id)->where('date', '2026-10-18')->firstOrFail();
        $this->assertNull($attAndri18->clock_in);
        $this->assertNull($attAndri18->clock_out);
        $this->assertEquals('17:25:00', $attAndri18->overtime_in);
        $this->assertEquals('libur', $attAndri18->status);

        // 3. Tgl 20: clock_in = 06:29, clock_out = null, overtime_in = 16:59, overtime_out = 18:03, status = hadir
        $attAndri20 = Attendance::where('employee_id', $empAndri->id)->where('date', '2026-10-20')->firstOrFail();
        $this->assertEquals('06:29:00', $attAndri20->clock_in);
        $this->assertEquals('16:59:00', $attAndri20->overtime_in);
        $this->assertEquals('18:03:00', $attAndri20->overtime_out);
        $this->assertEquals(64, $attAndri20->overtime_minutes);
        $this->assertEquals('hadir', $attAndri20->status);

        // Verifikasi Jojo (Tetap):
        // 1. Tgl 16: In 07:55, Out 17:05, status hadir
        $attJojo16 = Attendance::where('employee_id', $empJojo->id)->where('date', '2026-10-16')->firstOrFail();
        $this->assertEquals('07:55:00', $attJojo16->clock_in);
        $this->assertEquals('17:05:00', $attJojo16->clock_out);
        $this->assertEquals('hadir', $attJojo16->status);

        // 2. Tgl 18 (Minggu): Sunday protection aktif untuk Karyawan Tetap
        $attJojo18 = Attendance::where('employee_id', $empJojo->id)->where('date', '2026-10-18')->firstOrFail();
        $this->assertNull($attJojo18->clock_in);
        $this->assertNull($attJojo18->clock_out);
        $this->assertEquals('libur', $attJojo18->status);

        // Verifikasi Zaelani dari sheet single ID (91)
        $attZaelani16 = Attendance::where('employee_id', $empZaelani->id)->where('date', '2026-10-16')->firstOrFail();
        $this->assertEquals('06:30:00', $attZaelani16->clock_in);
        $this->assertEquals('hadir', $attZaelani16->status);

        @unlink($tmpFile);
    }

    public function test_admin_can_save_manual_attendance_with_overtime_and_reset_it(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->set('manualEmployeeId', $this->employee->id)
            ->set('manualDate', '2026-10-16')
            ->set('manualClockIn', '06:40')
            ->set('manualClockOut', '')
            ->set('manualOvertimeIn', '17:40')
            ->set('manualOvertimeOut', '19:40')
            ->set('manualStatus', 'hadir')
            ->call('saveManualAttendance')
            ->assertDispatched('close-modal', id: 'manualAttendanceModal')
            ->assertDispatched('swal:alert');

        $att = Attendance::where('employee_id', $this->employee->id)->where('date', '2026-10-16')->firstOrFail();
        $this->assertEquals('06:40:00', $att->clock_in);
        $this->assertNull($att->clock_out);
        $this->assertEquals('17:40:00', $att->overtime_in);
        $this->assertEquals('19:40:00', $att->overtime_out);
        $this->assertEquals(120, $att->overtime_minutes);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'attendances',
            'action' => 'ATTENDANCE_MANUAL_CREATED',
        ]);

        // Test reset to fingerprint with raw_log
        $att->update([
            'source' => 'manual',
            'raw_log' => 'Reg: 06:40 | OT: 17:40',
            'clock_in' => '08:00:00',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->call('resetToFingerprint', $att->id)
            ->assertDispatched('close-modal', id: 'manualAttendanceModal')
            ->assertDispatched('swal:alert');

        $attFresh = $att->fresh();
        $this->assertEquals('06:40:00', $attFresh->clock_in);
        $this->assertNull($attFresh->clock_out);
        $this->assertEquals('17:40:00', $attFresh->overtime_in);
        $this->assertEquals('fingerprint', $attFresh->source);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'attendances',
            'action' => 'ATTENDANCE_RESET_TO_FINGERPRINT',
        ]);
    }

    public function test_admin_deleting_attendance_creates_activity_log(): void
    {
        $att = Attendance::forceCreate([
            'employee_id' => $this->employee->id,
            'date' => '2026-10-20',
            'status' => 'hadir',
            'clock_in' => '07:30:00',
            'clock_out' => '16:30:00',
            'source' => 'manual',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->call('deleteAttendance', $att->id)
            ->assertDispatched('close-modal', id: 'manualAttendanceModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('attendances', ['id' => $att->id]);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'attendances',
            'action' => 'ATTENDANCE_DELETED',
            'level' => 'warning',
        ]);
    }

    public function test_manual_attendance_overnight_shift_and_overnight_overtime_calculation(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->set('manualEmployeeId', $this->employee->id)
            ->set('manualDate', '2026-11-20')
            ->set('manualClockIn', '22:00')
            ->set('manualClockOut', '06:00')
            ->set('manualOvertimeIn', '22:00')
            ->set('manualOvertimeOut', '02:00')
            ->set('manualStatus', 'hadir')
            ->call('saveManualAttendance')
            ->assertDispatched('close-modal', id: 'manualAttendanceModal')
            ->assertDispatched('swal:alert');

        $att = Attendance::where('employee_id', $this->employee->id)->where('date', '2026-11-20')->firstOrFail();
        $this->assertEquals('22:00:00', $att->clock_in);
        $this->assertEquals('06:00:00', $att->clock_out);
        $this->assertEquals('22:00:00', $att->overtime_in);
        $this->assertEquals('02:00:00', $att->overtime_out);
        $this->assertEquals(480, $att->duration_minutes, 'Durasi kerja shift malam 22:00-06:00 harus 480 menit');
        $this->assertEquals(240, $att->overtime_minutes, 'Durasi lembur lintas malam 22:00-02:00 harus 240 menit');
        $this->assertEquals(0, $att->late_minutes, 'Shift malam tepat waktu tidak boleh dianggap terlambat shift pagi');
    }

    public function test_import_rejects_date_range_longer_than_one_month(): void
    {
        $beforeCount = Attendance::count();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('100');
        $sheet->setCellValue('A1', 'Kartu Laporan');
        $sheet->setCellValue('A2', 'Tgl Absen:');
        $sheet->setCellValue('D2', '2026-10-01 ~ 2026-12-31');

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_range_').'.xls';
        IOFactory::createWriter($spreadsheet, 'Xls')->save($tmpFile);

        try {
            $service = app(AttendanceService::class);
            $service->importFingerprintReport($tmpFile, $this->adminUser);
            $this->fail('Import dengan rentang lebih dari 1 bulan seharusnya ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Rentang tanggal import maksimal 1 bulan', $e->getMessage());
            $this->assertStringContainsString('Silakan impor per bulan', $e->getMessage());
            $this->assertStringContainsString('Oktober 2026', $e->getMessage(), 'Pesan penolakan harus memuat daftar bulan terdeteksi');
        } finally {
            @unlink($tmpFile);
        }

        // Tidak ada row absensi yang tertulis saat import ditolak
        $this->assertSame($beforeCount, Attendance::count());
    }

    public function test_sync_weekly_excludes_sunday_and_holiday_from_attended_days(): void
    {
        // Jadwal 6 hari kerja: Sabtu tetap hari kerja, hanya Minggu + libur nasional yang dikecualikan
        CompanyProfile::getProfile()->update(['work_days_per_week' => 6]);

        $this->employee->update([
            'daily_rate' => 100000,
            'meal_allowance_per_day' => 20000,
        ]);

        // Hari Rabu 2026-10-21 ditandai libur nasional dari kalender Holiday
        Holiday::firstOrCreate(['date' => '2026-10-21'], ['name' => 'Libur Uji Presensi']);

        // Tap Senin s/d Minggu (7 hari): termasuk Rabu libur nasional & Minggu
        $dates = ['2026-10-19', '2026-10-20', '2026-10-21', '2026-10-22', '2026-10-23', '2026-10-24', '2026-10-25'];
        foreach ($dates as $d) {
            Attendance::updateOrCreate(
                ['employee_id' => $this->employee->id, 'date' => $d],
                [
                    'clock_in' => '08:00:00',
                    'clock_out' => '17:00:00',
                    'status' => 'hadir',
                    'source' => 'fingerprint',
                ]
            );
        }

        $period = OvertimePeriod::create([
            'reference_number' => 'OTP-HOLIDAY-'.uniqid(),
            'name' => 'Lembur Mingguan W43 Libur',
            'start_date' => '2026-10-19',
            'end_date' => '2026-10-25',
            'created_by' => $this->adminUser->id,
            'status' => 'draft',
        ]);

        $service = app(AttendanceService::class);
        $synced = $service->syncToWeeklyOvertimePeriod($period, $this->adminUser);
        $this->assertGreaterThan(0, $synced);

        $periodAtt = OvertimePeriodAttendance::where('overtime_period_id', $period->id)
            ->where('employee_id', $this->employee->id)
            ->firstOrFail();

        // Rabu (libur) dan Minggu tidak dihitung; Senin, Selasa, Kamis, Jumat, Sabtu = 5 hari
        $this->assertEquals(5, $periodAtt->attended_days);
        $this->assertEquals(['mon', 'tue', 'thu', 'fri', 'sat'], $periodAtt->attendance_days);
        $this->assertEquals(500000, (float) $periodAtt->total_daily_wage);
        // Tunjangan makan memakai rate seragam perusahaan (Rp 25.000/hari)
        $this->assertEquals(125000, (float) $periodAtt->total_meal_allowance);

        // Data jam hari libur/Minggu tetap tersimpan di absensi (tidak dihapus)
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2026-10-21',
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2026-10-25',
            'status' => 'hadir',
        ]);
    }

    public function test_sync_keeps_weekly_attendance_row_for_soft_deleted_employee(): void
    {
        CompanyProfile::getProfile()->update(['work_days_per_week' => 6]);
        Holiday::whereBetween('date', ['2026-10-19', '2026-10-25'])->delete();

        Attendance::updateOrCreate(
            ['employee_id' => $this->employee->id, 'date' => '2026-10-19'],
            ['clock_in' => '08:00:00', 'clock_out' => '17:00:00', 'status' => 'hadir', 'source' => 'fingerprint']
        );
        Attendance::updateOrCreate(
            ['employee_id' => $this->employee->id, 'date' => '2026-10-20'],
            ['clock_in' => '08:00:00', 'clock_out' => '17:00:00', 'status' => 'hadir', 'source' => 'fingerprint']
        );

        $period = OvertimePeriod::create([
            'reference_number' => 'OTP-RESIGN-'.uniqid(),
            'name' => 'Lembur Mingguan Karyawan Resign',
            'start_date' => '2026-10-19',
            'end_date' => '2026-10-25',
            'created_by' => $this->adminUser->id,
            'status' => 'draft',
        ]);

        $service = app(AttendanceService::class);
        $service->syncToWeeklyOvertimePeriod($period, $this->adminUser);

        $this->assertDatabaseHas('overtime_period_attendances', [
            'overtime_period_id' => $period->id,
            'employee_id' => $this->employee->id,
            'attended_days' => 2,
        ]);

        // Karyawan resign / diarsipkan (soft delete) SETELAH baris presensi mingguan terbentuk
        $this->employee->delete();
        $this->assertTrue($this->employee->fresh()->trashed());

        // Sinkronisasi ulang TIDAK boleh menghapus baris presensi mingguannya
        $service->syncToWeeklyOvertimePeriod($period, $this->adminUser);

        $row = OvertimePeriodAttendance::where('overtime_period_id', $period->id)
            ->where('employee_id', $this->employee->id)
            ->first();

        $this->assertNotNull($row, 'Baris presensi mingguan karyawan yang diarsipkan tidak boleh dihapus saat sync ulang');
        $this->assertEquals(2, $row->attended_days);
    }

    public function test_import_reads_excel_serial_time_values(): void
    {
        $dept = Department::firstOrCreate(['name' => 'Produksi']);
        $pos = Position::firstOrCreate(['name' => 'Operator']);

        $emp = Employee::firstOrCreate(
            ['fingerprint_id' => '777'],
            [
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'full_name' => 'Karyawan Serial Time',
                'join_date' => '2025-01-01',
                'employment_status' => EmploymentStatus::HARIAN,
                'status' => 'active',
                'daily_rate' => 100000,
            ]
        );

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('777');
        $sheet->setCellValue('A1', 'Kartu Laporan');
        $sheet->setCellValue('A2', 'Tgl Absen:');
        $sheet->setCellValue('D2', '2026-10-16 ~ 2026-10-23');

        $sheet->setCellValue('I3', 'Nama');
        $sheet->setCellValue('J3', 'SERIAL TIME');
        $sheet->setCellValue('I4', 'ID');
        $sheet->setCellValue('J4', '777');

        // Senin 2026-10-19: nilai waktu berformat serial Excel (numerik)
        // 0.354166... = fraksi hari 08:30, 0.729166... = 17:30,
        // 0.75 = 18:00, 45000.791666... = serial tanggal + 19:00.
        $sheet->setCellValue('A12', '19 SEN');
        $sheet->setCellValue('B12', 0.3541666666666667);
        $sheet->setCellValue('D12', 0.7291666666666666);
        $sheet->setCellValue('K12', 0.75);
        $sheet->setCellValue('M12', 45000.791666666664);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_serial_').'.xls';
        IOFactory::createWriter($spreadsheet, 'Xls')->save($tmpFile);

        try {
            $service = app(AttendanceService::class);
            $result = $service->importFingerprintReport($tmpFile, $this->adminUser);

            $this->assertEquals('success', $result['status']);
            $this->assertGreaterThanOrEqual(1, $result['records_saved']);

            $att = Attendance::where('employee_id', $emp->id)->where('date', '2026-10-19')->firstOrFail();
            $this->assertEquals('08:30:00', $att->clock_in, 'Serial Excel 0.354166... harus terbaca 08:30');
            $this->assertEquals('17:30:00', $att->clock_out, 'Serial Excel 0.729166... harus terbaca 17:30');
            $this->assertEquals('18:00:00', $att->overtime_in, 'Serial Excel 0.75 harus terbaca 18:00');
            $this->assertEquals('19:00:00', $att->overtime_out, 'Serial Excel 45000.791666... harus terbaca 19:00');
            $this->assertEquals(540, $att->duration_minutes);
            $this->assertEquals(60, $att->overtime_minutes);
            $this->assertEquals('hadir', $att->status);
        } finally {
            @unlink($tmpFile);
        }
    }
}
