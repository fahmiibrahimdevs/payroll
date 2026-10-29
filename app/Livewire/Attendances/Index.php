<?php

namespace App\Livewire\Attendances;

use App\Enums\EmploymentStatus;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Services\ActivityLogService;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // Active View Tab: 'matrix' (Matriks Rekap) vs 'log' (Log Detail Harian)
    public string $activeTab = 'matrix';

    // Filters
    public string $startDate = '';

    public string $endDate = '';

    public string $search = '';

    public string $departmentFilter = '';

    public string $employmentStatusFilter = '';

    public string $statusFilter = '';

    public int $perPage = 25;

    // Excel Import
    public mixed $excelFile = null;

    public bool $isImporting = false;

    // Manual Entry / Edit Form
    public ?int $editingAttendanceId = null;

    public ?string $editingAttendanceSource = null;

    public ?string $editingAttendanceRawLog = null;

    public ?int $manualEmployeeId = null;

    public string $manualDate = '';

    public string $manualClockIn = '';

    public string $manualClockOut = '';

    public string $manualOvertimeIn = '';

    public string $manualOvertimeOut = '';

    public string $manualStatus = 'hadir';

    public string $manualNotes = '';

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->hasRole('karyawan') && ! $user?->hasRole(['admin', 'direktur', 'developer'])) {
            abort_unless($user->getEmployeeRecord() !== null, 403, 'Akun Anda belum terhubung dengan data profil karyawan.');
        }

        // Default to full month of imported fingerprint logs or current month (1 s/d akhir bulan)
        $maxDate = Attendance::max('date');
        $anchor = $maxDate ? Carbon::parse($maxDate) : Carbon::today();
        $this->startDate = $anchor->copy()->startOfMonth()->toDateString();
        $this->endDate = $anchor->copy()->endOfMonth()->toDateString();

        $this->manualDate = Carbon::today()->toDateString();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['matrix', 'log'], true) ? $tab : 'matrix';
    }

    public function setPreset(string $preset): void
    {
        $maxDate = Attendance::max('date');
        $anchor = $maxDate ? Carbon::parse($maxDate) : Carbon::now();

        if ($preset === 'last_import') {
            if ($maxDate) {
                $this->endDate = $anchor->toDateString();
                $minDate = Attendance::where('date', '>=', $anchor->copy()->subDays(7))->min('date');
                $this->startDate = $minDate ? Carbon::parse($minDate)->toDateString() : $anchor->copy()->subDays(7)->toDateString();
            }
        } elseif ($preset === 'this_week') {
            $this->startDate = Carbon::now()->startOfWeek()->toDateString();
            $this->endDate = Carbon::now()->endOfWeek()->toDateString();
        } elseif ($preset === 'this_month') {
            $this->startDate = Carbon::now()->startOfMonth()->toDateString();
            $this->endDate = Carbon::now()->endOfMonth()->toDateString();
        }

        $this->resetPage();
    }

    public function previousMonth(): void
    {
        $current = Carbon::parse($this->startDate ?: now());
        $prev = $current->copy()->subMonthNoOverflow();
        $this->startDate = $prev->startOfMonth()->toDateString();
        $this->endDate = $prev->endOfMonth()->toDateString();
        $this->resetPage();
    }

    public function nextMonth(): void
    {
        $current = Carbon::parse($this->startDate ?: now());
        $next = $current->copy()->addMonthNoOverflow();
        $this->startDate = $next->startOfMonth()->toDateString();
        $this->endDate = $next->endOfMonth()->toDateString();
        $this->resetPage();
    }

    public function goToCurrentMonth(): void
    {
        $now = Carbon::now();
        $this->startDate = $now->startOfMonth()->toDateString();
        $this->endDate = $now->endOfMonth()->toDateString();
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingEmploymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->resetPage();
    }

    public function updatingEndDate(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'departmentFilter', 'employmentStatusFilter', 'statusFilter']);
        $maxDate = Attendance::max('date');
        $anchor = $maxDate ? Carbon::parse($maxDate) : Carbon::today();
        $this->startDate = $anchor->copy()->startOfMonth()->toDateString();
        $this->endDate = $anchor->copy()->endOfMonth()->toDateString();
        $this->resetPage();
    }

    /**
     * Import attendance records from uploaded fingerprint Excel report.
     */
    public function importFingerprint(AttendanceService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->validate([
            'excelFile' => ['required', 'file', 'extensions:xls,xlsx', 'max:10240'],
        ], [
            'excelFile.required' => 'Silakan pilih berkas Excel laporan absensi terlebih dahulu.',
            'excelFile.extensions' => 'Format berkas harus berupa .xls atau .xlsx.',
            'excelFile.mimes' => 'Format berkas harus berupa .xls atau .xlsx.',
            'excelFile.max' => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $this->isImporting = true;

        try {
            $result = $service->importFingerprintReport($this->excelFile, auth()->user());

            $this->dispatch('close-modal', id: 'importAttendanceModal');
            $this->reset('excelFile');

            // 1. Jika ada ID Fingerprint yang belum terdaftar: Batalkan seluruh import & tampilkan rincian
            if (($result['status'] ?? '') === 'unregistered_fingerprint' || ! empty($result['unregistered_list'])) {
                $unregisteredList = $result['unregistered_list'] ?? [];
                $count = count($unregisteredList);

                // Buat list HTML yang rapi untuk SweetAlert2
                $listHtml = '<div style="text-align: left; font-size: 13px; line-height: 1.5;">';
                $listHtml .= "<p style='margin-bottom: 10px; color: #334155;'>Ditemukan <strong style=\"color:#e11d48;\">{$count} Karyawan</strong> pada berkas Excel yang <strong>ID Fingerprint-nya belum terdaftar</strong> di Data Karyawan:</p>";
                $listHtml .= '<div style="max-height: 200px; overflow-y: auto; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px;">';
                $listHtml .= '<ul style="margin: 0; padding-left: 18px; color: #1e293b;">';
                foreach ($unregisteredList as $u) {
                    $listHtml .= "<li style='margin-bottom: 6px;'>";
                    $listHtml .= "<strong style='color:#0b52aa;'>ID Fingerprint: {$u['fingerprint_id']}</strong> &bull; ";
                    $listHtml .= "<span>Nama: <strong>{$u['name']}</strong></span>";
                    if (! empty($u['department']) && $u['department'] !== '-') {
                        $listHtml .= " <span style='color:#64748b;'>({$u['department']})</span>";
                    }
                    $listHtml .= '</li>';
                }
                $listHtml .= '</ul></div>';
                $listHtml .= '<div style="padding: 10px 12px; background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; color: #9f1239; font-size: 12px; font-weight: 500;">';
                $listHtml .= '<i class="fas fa-exclamation-triangle mr-1"></i> Data presensi <strong>TIDAK DISIMPAN</strong> ke database. Harap daftarkan nomor PIN Fingerprint pada profil karyawan terlebih dahulu sebelum mengimpor.';
                $listHtml .= '</div></div>';

                $this->dispatch('swal:alert', [
                    'type' => 'warning',
                    'title' => 'Import Dibatalkan',
                    'html' => $listHtml,
                    'message' => "Ditemukan {$count} karyawan yang ID Fingerprint-nya belum terdaftar di sistem.",
                ]);

                return;
            }

            // 2. Jika seluruh karyawan terdaftar: Sukses disimpan ke database
            $anchor = Carbon::parse($result['start_date']);
            $this->startDate = $anchor->copy()->startOfMonth()->toDateString();
            $this->endDate = $anchor->copy()->endOfMonth()->toDateString();
            $this->activeTab = 'matrix';

            $manualNote = '';
            if (($result['manual_preserved'] ?? 0) > 0) {
                $manualNote = " ({$result['manual_preserved']} koreksi manual admin tetap dipertahankan)";
            }

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Import Absensi Berhasil',
                'message' => "Periode {$result['start_date']} s/d {$result['end_date']}: Berhasil menyimpan {$result['records_saved']} data presensi dari {$result['matched_count']} karyawan{$manualNote}.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mengimpor Berkas',
                'message' => $e->getMessage(),
            ]);
        } finally {
            $this->isImporting = false;
        }
    }

    /**
     * Open modal when a cell in the matrix is clicked.
     */
    public function openCellModal(int $employeeId, string $dateString): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $attendance = Attendance::where('employee_id', $employeeId)->where('date', $dateString)->first();

        if ($attendance) {
            $this->openEditModal($attendance->id);
        } else {
            $this->resetValidation();
            $this->editingAttendanceId = null;
            $this->editingAttendanceSource = 'manual';
            $this->editingAttendanceRawLog = null;
            $this->manualEmployeeId = $employeeId;
            $this->manualDate = $dateString;
            $this->manualClockIn = '08:00';
            $this->manualClockOut = '17:00';
            $this->manualOvertimeIn = '';
            $this->manualOvertimeOut = '';
            $this->manualStatus = 'hadir';
            $this->manualNotes = '';
            $this->dispatch('open-modal', id: 'manualAttendanceModal');
        }
    }

    /**
     * Open manual create attendance modal.
     */
    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->resetValidation();
        $this->editingAttendanceId = null;
        $this->editingAttendanceSource = 'manual';
        $this->editingAttendanceRawLog = null;
        $this->manualEmployeeId = null;
        $this->manualDate = Carbon::today()->toDateString();
        $this->manualClockIn = '08:00';
        $this->manualClockOut = '17:00';
        $this->manualOvertimeIn = '';
        $this->manualOvertimeOut = '';
        $this->manualStatus = 'hadir';
        $this->manualNotes = '';

        $this->dispatch('open-modal', id: 'manualAttendanceModal');
    }

    /**
     * Open manual edit attendance modal.
     */
    public function openEditModal(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->resetValidation();
        $attendance = Attendance::findOrFail($id);

        $this->editingAttendanceId = $attendance->id;
        $this->editingAttendanceSource = $attendance->source;
        $this->editingAttendanceRawLog = $attendance->raw_log;
        $this->manualEmployeeId = $attendance->employee_id;
        $this->manualDate = $attendance->date ? $attendance->date->toDateString() : '';
        $this->manualClockIn = $attendance->clock_in ? substr($attendance->clock_in, 0, 5) : '';
        $this->manualClockOut = $attendance->clock_out ? substr($attendance->clock_out, 0, 5) : '';
        $this->manualOvertimeIn = $attendance->overtime_in ? substr($attendance->overtime_in, 0, 5) : '';
        $this->manualOvertimeOut = $attendance->overtime_out ? substr($attendance->overtime_out, 0, 5) : '';
        $this->manualStatus = $attendance->status ?? 'hadir';
        $this->manualNotes = $attendance->notes ?? '';

        $this->dispatch('open-modal', id: 'manualAttendanceModal');
    }

    /**
     * Save manual attendance create or update.
     */
    public function saveManualAttendance(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->validate([
            'manualEmployeeId' => ['required', 'exists:employees,id'],
            'manualDate' => ['required', 'date'],
            'manualClockIn' => ['nullable', 'date_format:H:i'],
            'manualClockOut' => ['nullable', 'date_format:H:i'],
            'manualOvertimeIn' => ['nullable', 'date_format:H:i'],
            'manualOvertimeOut' => ['nullable', 'date_format:H:i'],
            'manualStatus' => ['required', 'string', 'in:hadir,terlambat,pulang_cepat,absen,libur,cuti,sakit,izin'],
            'manualNotes' => ['nullable', 'string', 'max:500'],
        ], [
            'manualEmployeeId.required' => 'Pilih karyawan terlebih dahulu.',
            'manualDate.required' => 'Tanggal presensi wajib diisi.',
            'manualStatus.required' => 'Status kehadiran wajib dipilih.',
        ]);

        $durationMins = 0;
        $lateMins = 0;
        $overtimeMins = 0;

        if ($this->manualClockIn) {
            [$inH, $inM] = explode(':', $this->manualClockIn);
            $inMinutes = ((int) $inH * 60) + (int) $inM;
            if ($inMinutes > 480 && $inMinutes < 720) { // Masuk shift pagi lewat 08:00
                $lateMins = $inMinutes - 480;
            } elseif ($inMinutes > 1320) { // Masuk shift malam lewat 22:00
                $lateMins = $inMinutes - 1320;
            }
        }

        if ($this->manualClockIn && $this->manualClockOut) {
            [$inH, $inM] = explode(':', $this->manualClockIn);
            [$outH, $outM] = explode(':', $this->manualClockOut);
            $inMinutes = ((int) $inH * 60) + (int) $inM;
            $outMinutes = ((int) $outH * 60) + (int) $outM;
            if ($outMinutes < $inMinutes) {
                // Melintasi tengah malam (misal 22:00 - 06:00)
                $durationMins = ($outMinutes + 1440) - $inMinutes;
            } else {
                $durationMins = $outMinutes - $inMinutes;
            }
        }

        if ($this->manualOvertimeIn && $this->manualOvertimeOut) {
            [$otInH, $otInM] = explode(':', $this->manualOvertimeIn);
            [$otOutH, $otOutM] = explode(':', $this->manualOvertimeOut);
            $otInMinutes = ((int) $otInH * 60) + (int) $otInM;
            $otOutMinutes = ((int) $otOutH * 60) + (int) $otOutM;
            if ($otOutMinutes < $otInMinutes) {
                // Lembur melintasi tengah malam (misal 22:00 - 02:00)
                $overtimeMins = ($otOutMinutes + 1440) - $otInMinutes;
            } else {
                $overtimeMins = $otOutMinutes - $otInMinutes;
            }
        }

        $existing = Attendance::where('employee_id', $this->manualEmployeeId)->where('date', $this->manualDate)->first();
        $action = $existing ? 'ATTENDANCE_MANUAL_UPDATED' : 'ATTENDANCE_MANUAL_CREATED';

        DB::transaction(function () use ($durationMins, $overtimeMins, $lateMins) {
            Attendance::updateOrCreate(
                [
                    'employee_id' => $this->manualEmployeeId,
                    'date' => $this->manualDate,
                ],
                [
                    'clock_in' => $this->manualClockIn ? $this->manualClockIn . ':00' : null,
                    'clock_out' => $this->manualClockOut ? $this->manualClockOut . ':00' : null,
                    'overtime_in' => $this->manualOvertimeIn ? $this->manualOvertimeIn . ':00' : null,
                    'overtime_out' => $this->manualOvertimeOut ? $this->manualOvertimeOut . ':00' : null,
                    'status' => $this->manualStatus,
                    'duration_minutes' => $durationMins,
                    'overtime_minutes' => $overtimeMins,
                    'late_minutes' => $lateMins,
                    'source' => 'manual',
                    'notes' => $this->manualNotes ?: null,
                ]
            );
        });

        app(ActivityLogService::class)->info(
            action: $action,
            module: 'attendances',
            message: 'Data presensi karyawan berhasil disimpan manual',
            context: [
                'employee_id' => $this->manualEmployeeId,
                'date' => $this->manualDate,
                'status' => $this->manualStatus,
                'clock_in' => $this->manualClockIn,
                'clock_out' => $this->manualClockOut,
                'overtime_in' => $this->manualOvertimeIn,
                'overtime_out' => $this->manualOvertimeOut,
                'duration_minutes' => $durationMins,
                'overtime_minutes' => $overtimeMins,
                'late_minutes' => $lateMins,
            ]
        );

        $this->dispatch('close-modal', id: 'manualAttendanceModal');
        $this->dispatch('swal:alert', [
            'type' => 'success',
            'title' => 'Data Disimpan',
            'message' => 'Data presensi karyawan berhasil disimpan.',
        ]);
    }

    /**
     * Trigger SweetAlert2 delete confirmation for an attendance record.
     */
    public function confirmDeleteAttendance(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $attendance = Attendance::with('employee')->findOrFail($id);
        $date = $attendance->date ? $attendance->date->isoFormat('D MMMM Y') : '-';
        $empName = $attendance->employee?->full_name ?? 'Karyawan';

        $this->dispatch('swal:confirm-delete', [
            'action' => 'delete-attendance',
            'id' => $id,
            'title' => "Presensi {$empName} ({$date})",
            'text' => "Data presensi tanggal {$date} ini akan dihapus dari sistem.",
        ]);
    }

    /**
     * Delete an attendance record after user confirmation.
     */
    #[On('delete-attendance')]
    public function deleteAttendance(int|array $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $attendanceId = is_array($id) ? (int) ($id['id'] ?? 0) : (int) $id;
        $attendance = Attendance::with('employee')->findOrFail($attendanceId);
        $context = [
            'attendance_id' => $attendance->id,
            'employee_id' => $attendance->employee_id,
            'employee_name' => $attendance->employee?->full_name,
            'date' => $attendance->date?->toDateString(),
            'status' => $attendance->status,
        ];

        $attendance->delete();

        app(ActivityLogService::class)->warning(
            action: 'ATTENDANCE_DELETED',
            module: 'attendances',
            message: 'Data presensi karyawan berhasil dihapus',
            context: $context
        );

        $this->dispatch('close-modal', id: 'manualAttendanceModal');
        $this->dispatch('swal:alert', [
            'type' => 'success',
            'title' => 'Data Dihapus',
            'message' => 'Data presensi karyawan berhasil dihapus.',
        ]);
    }

    /**
     * Revert manual correction back to machine fingerprint log.
     */
    public function resetToFingerprint(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $attendance = Attendance::findOrFail($id);

        if (! $attendance->raw_log) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Tidak Ada Log Mesin',
                'message' => 'Data presensi ini tidak memiliki catatan log mesin fingerprint asli.',
            ]);

            return;
        }

        $clockIn = null;
        $clockOut = null;
        $overtimeIn = null;
        $overtimeOut = null;
        $durationMins = 0;
        $overtimeMins = 0;

        if (str_contains($attendance->raw_log, 'Reg:') || str_contains($attendance->raw_log, 'OT:')) {
            $parts = explode('|', $attendance->raw_log);
            foreach ($parts as $part) {
                $part = trim($part);
                if (str_starts_with($part, 'Reg:')) {
                    $regContent = trim(substr($part, 4));
                    preg_match_all('/\d{1,2}:\d{2}/', $regContent, $regMatches);
                    $regTimes = $regMatches[0] ?? [];
                    if (! empty($regTimes)) {
                        $clockIn = (strlen($regTimes[0]) === 4 ? '0' . $regTimes[0] : $regTimes[0]) . ':00';
                        if (count($regTimes) > 1) {
                            $clockOut = (strlen($regTimes[1]) === 4 ? '0' . $regTimes[1] : $regTimes[1]) . ':00';
                        }
                    }
                } elseif (str_starts_with($part, 'OT:')) {
                    $otContent = trim(substr($part, 3));
                    preg_match_all('/\d{1,2}:\d{2}/', $otContent, $otMatches);
                    $otTimes = $otMatches[0] ?? [];
                    if (! empty($otTimes)) {
                        $overtimeIn = (strlen($otTimes[0]) === 4 ? '0' . $otTimes[0] : $otTimes[0]) . ':00';
                        if (count($otTimes) > 1) {
                            $overtimeOut = (strlen($otTimes[1]) === 4 ? '0' . $otTimes[1] : $otTimes[1]) . ':00';
                        }
                    }
                }
            }
        } else {
            preg_match_all('/\d{1,2}:\d{2}/', $attendance->raw_log, $timeMatches);
            $times = $timeMatches[0] ?? [];

            if (empty($times)) {
                $this->dispatch('swal:alert', [
                    'type' => 'warning',
                    'title' => 'Format Log Tidak Valid',
                    'message' => 'Tidak ditemukan waktu jam pada log mesin asli: ' . $attendance->raw_log,
                ]);

                return;
            }

            $clockIn = (strlen($times[0]) === 4 ? '0' . $times[0] : $times[0]) . ':00';
            $latestTime = end($times);
            if (strlen($latestTime) === 4) {
                $latestTime = '0' . $latestTime;
            }
            $clockOut = ($latestTime !== substr($clockIn, 0, 5)) ? $latestTime . ':00' : null;
        }

        if ($clockIn && $clockOut) {
            $inSec = Carbon::parse($clockIn)->secondsSinceMidnight();
            $outSec = Carbon::parse($clockOut)->secondsSinceMidnight();
            if ($outSec > $inSec) {
                $durationMins = (int) round(($outSec - $inSec) / 60);
            }
        }

        if ($overtimeIn && $overtimeOut) {
            $otInSec = Carbon::parse($overtimeIn)->secondsSinceMidnight();
            $otOutSec = Carbon::parse($overtimeOut)->secondsSinceMidnight();
            if ($otOutSec > $otInSec) {
                $overtimeMins = (int) round(($otOutSec - $otInSec) / 60);
            }
        }

        $dateObj = $attendance->date ? Carbon::parse($attendance->date) : now();
        $isSunday = $dateObj->isSunday();

        $status = 'hadir';
        if ($clockIn === null) {
            $status = $isSunday ? 'libur' : 'absen';
        }

        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'overtime_in' => $overtimeIn,
            'overtime_out' => $overtimeOut,
            'status' => $status,
            'duration_minutes' => $durationMins,
            'overtime_minutes' => $overtimeMins,
            'late_minutes' => 0,
            'source' => 'fingerprint',
            'notes' => null,
        ]);

        app(ActivityLogService::class)->info(
            action: 'ATTENDANCE_RESET_TO_FINGERPRINT',
            module: 'attendances',
            message: 'Koreksi presensi manual berhasil dikembalikan ke log mesin fingerprint asli',
            context: [
                'attendance_id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
                'date' => $attendance->date?->toDateString(),
                'restored_clock_in' => $clockIn,
                'restored_clock_out' => $clockOut,
                'restored_overtime_in' => $overtimeIn,
                'restored_overtime_out' => $overtimeOut,
            ]
        );

        $this->dispatch('close-modal', id: 'manualAttendanceModal');
        $this->dispatch('swal:alert', [
            'type' => 'success',
            'title' => 'Berhasil Direset',
            'message' => 'Data presensi berhasil dikembalikan ke catatan mesin fingerprint asli.',
        ]);
    }

    public function render(AttendanceService $service): View
    {
        $start = ! empty($this->startDate) ? Carbon::parse($this->startDate) : Carbon::now()->startOfWeek();
        $end = ! empty($this->endDate) ? Carbon::parse($this->endDate) : Carbon::now()->endOfWeek();

        // Prevent performance issues by capping matrix date range to 31 days max
        if ($start->diffInDays($end) > 31) {
            $end = $start->copy()->addDays(30);
            $this->endDate = $end->toDateString();
        }

        // Generate matrix dates list
        $matrixDates = [];
        $cur = $start->copy();
        while ($cur->lte($end)) {
            $matrixDates[] = [
                'date' => $cur->toDateString(),
                'day' => $cur->day,
                'dow_name' => $cur->locale('id')->isoFormat('ddd'),
                'is_sunday' => $cur->isSunday(),
                'is_weekend' => $cur->isWeekend(),
            ];
            $cur->addDay();
        }

        // Query active employees for matrix
        $empQuery = Employee::where('status', 'active')->with(['department', 'position', 'detail']);

        if (! empty($this->departmentFilter)) {
            $empQuery->where('department_id', (int) $this->departmentFilter);
        }

        if (! empty($this->employmentStatusFilter)) {
            $empQuery->where('employment_status', $this->employmentStatusFilter);
        }

        if (! empty($this->search)) {
            $term = $this->search;
            $empQuery->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                  ->orWhere('fingerprint_id', 'like', "%{$term}%")
                  ->orWhereHas('detail', fn ($sub) => $sub->where('employee_code', 'like', "%{$term}%"))
                  ->orWhereHas('position', fn ($sub) => $sub->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('department', fn ($sub) => $sub->where('name', 'like', "%{$term}%"));
            });
        }

        // Restrict non-admin karyawan
        $user = auth()->user();
        $isKaryawan = $user?->hasRole('karyawan') && ! $user?->hasRole(['admin', 'direktur', 'developer']);
        $karyawanEmployee = $isKaryawan ? $user->getEmployeeRecord() : null;

        if ($isKaryawan) {
            $empQuery->where('id', $karyawanEmployee?->id ?? -1);
        }

        $matrixEmployees = $empQuery
            ->orderByRaw("CASE 
                WHEN employment_status = 'tetap' THEN 1 
                WHEN employment_status = 'kontrak' THEN 2 
                WHEN employment_status = 'harian' THEN 3 
                WHEN employment_status = 'magang' THEN 4 
                ELSE 5 
            END")
            ->orderByRaw('CAST(fingerprint_id AS UNSIGNED) ASC')
            ->orderBy('full_name')
            ->paginate($this->perPage);

        // Fetch attendances in range with minimal columns using DB query (stdClass) to avoid hydrating 200+ Eloquent models
        $attendancesInRange = DB::table('attendances')
            ->select([
                'id', 'employee_id', 'date', 'clock_in', 'clock_out',
                'overtime_in', 'overtime_out', 'status', 'source',
            ])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('employee_id', $matrixEmployees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        // Detailed log listing query (only executed if activeTab is 'log' to eliminate unused query overhead)
        $attendancesLog = null;
        if ($this->activeTab === 'log') {
            $filters = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'search' => $this->search,
                'department_id' => $this->departmentFilter,
                'employment_status' => $this->employmentStatusFilter,
                'status' => $this->statusFilter,
            ];

            if ($isKaryawan && $karyawanEmployee) {
                $filters['employee_id'] = $karyawanEmployee->id;
            }

            $logQuery = $service->getAttendancesQuery($filters);
            $attendancesLog = $logQuery->paginate($this->perPage);
        }

        // Consolidated single metric query with indexed JOIN and conditional aggregates (1 query instead of 4 separate queries)
        $metricRow = DB::table('attendances')
            ->join('employees', 'employees.id', '=', 'attendances.employee_id')
            ->whereBetween('attendances.date', [$start->toDateString(), $end->toDateString()])
            ->when($isKaryawan && $karyawanEmployee, fn ($q) => $q->where('employees.id', $karyawanEmployee->id))
            ->when($this->departmentFilter, fn ($q) => $q->where('employees.department_id', (int) $this->departmentFilter))
            ->when($this->employmentStatusFilter, fn ($q) => $q->where('employees.employment_status', $this->employmentStatusFilter))
            ->selectRaw("
                COUNT(*) as total_logs,
                COUNT(CASE WHEN attendances.status IN ('hadir', 'terlambat', 'pulang_cepat') THEN 1 END) as total_hadir,
                COUNT(DISTINCT CASE WHEN attendances.status IN ('hadir', 'terlambat', 'pulang_cepat') THEN attendances.employee_id END) as total_karyawan_hadir,
                COUNT(CASE WHEN employees.employment_status = ? THEN 1 END) as total_harian
            ", [EmploymentStatus::HARIAN->value])
            ->first();

        $metrics = [
            'total_logs' => (int) ($metricRow?->total_logs ?? 0),
            'total_hadir' => (int) ($metricRow?->total_hadir ?? 0),
            'total_karyawan_hadir' => (int) ($metricRow?->total_karyawan_hadir ?? 0),
            'total_harian' => (int) ($metricRow?->total_harian ?? 0),
        ];

        $departments = DB::table('departments')->orderBy('name')->get(['id', 'name']);
        $allEmployees = DB::table('employees')
            ->where('status', 'active')
            ->orderByRaw("CASE 
                WHEN employment_status = 'tetap' THEN 1 
                WHEN employment_status = 'kontrak' THEN 2 
                WHEN employment_status = 'harian' THEN 3 
                WHEN employment_status = 'magang' THEN 4 
                ELSE 5 
            END")
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'fingerprint_id', 'employment_status']);

        return view('livewire.attendances.index', [
            'matrixDates' => $matrixDates,
            'matrixEmployees' => $matrixEmployees,
            'attendancesInRange' => $attendancesInRange,
            'attendancesLog' => $attendancesLog,
            'metrics' => $metrics,
            'departments' => $departments,
            'allEmployees' => $allEmployees,
            'employmentStatuses' => EmploymentStatus::cases(),
            'periodMonthLabel' => $start->locale('id')->isoFormat('MMMM Y'),
            'periodFullRangeLabel' => $start->locale('id')->isoFormat('D MMMM') . ' – ' . $end->locale('id')->isoFormat('D MMMM Y'),
            'isCurrentMonth' => $start->isSameMonth(Carbon::now()),
        ])->layout('layouts.app');
    }
}
