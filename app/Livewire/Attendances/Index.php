<?php

namespace App\Livewire\Attendances;

use App\Enums\EmploymentStatus;
use App\Models\Attendance;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Services\ActivityLogService;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
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

    public int $perPage = 0; // 0 = Tampilkan Semua Karyawan

    public string $sortBy = 'custom';

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

    public bool $hasOvertime = false;

    public bool $hasFullDayOvertime = false;

    public string $manualOvertimeIn = '';

    public string $manualOvertimeOut = '';

    public string $manualStatus = 'hadir';

    public string $manualNotes = '';

    public bool $lockDateAndEmployee = false;

    public ?Attendance $editingAttendance = null;

    // Audit Modal (Camera Selfie & GPS Map)
    public ?int $auditAttendanceId = null;

    public ?Attendance $auditAttendance = null;

    public string $auditTab = 'clock_in'; // 'clock_in' or 'clock_out'

    public string $auditReviewNotes = '';

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

    public function updatingSortBy(): void
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
            'excelFile' => ['required', 'file', 'mimes:xls,xlsx', 'extensions:xls,xlsx', 'max:10240'],
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
        } catch (InvalidArgumentException $e) {
            // Penolakan validasi import (mis. rentang tanggal > 1 bulan):
            // tampilkan pesan penolakan apa adanya tanpa menutup modal import.
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Import Ditolak',
                'message' => $e->getMessage(),
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
            $this->lockDateAndEmployee = true;
            $this->editingAttendanceId = null;
            $this->editingAttendanceSource = 'manual';
            $this->editingAttendanceRawLog = null;
            $this->manualEmployeeId = $employeeId;
            $this->manualDate = $dateString;

            // Cek apakah tanggal sel ini adalah hari libur (Minggu atau OFF Roster)
            $isSunday = Carbon::parse($dateString)->isSunday();
            $schedule = EmployeeSchedule::where('employee_id', $employeeId)->where('date', $dateString)->first();
            $isOff = $schedule ? ($schedule->status === EmployeeSchedule::STATUS_OFF) : $isSunday;

            if ($isOff) {
                $this->manualStatus = 'libur';
                $this->manualClockIn = '';
                $this->manualClockOut = '';
                $this->hasFullDayOvertime = false;
                $this->hasOvertime = false;
                $this->manualOvertimeIn = '';
                $this->manualOvertimeOut = '';
            } else {
                $this->manualStatus = 'hadir';
                $this->manualClockIn = '08:00';
                $this->manualClockOut = '17:00';
                $this->hasFullDayOvertime = false;
                $this->hasOvertime = false;
                $this->manualOvertimeIn = '';
                $this->manualOvertimeOut = '';
            }

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
        $this->lockDateAndEmployee = false;
        $this->editingAttendanceId = null;
        $this->editingAttendanceSource = 'manual';
        $this->editingAttendanceRawLog = null;
        $this->manualEmployeeId = null;
        $this->manualDate = Carbon::today()->toDateString();
        $this->manualClockIn = '08:00';
        $this->manualClockOut = '17:00';
        $this->hasFullDayOvertime = false;
        $this->hasOvertime = false;
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
        $this->lockDateAndEmployee = true;
        $attendance = Attendance::query()
            ->with(['employee.department', 'employee.position', 'employee.workLocation', 'clockInLocation', 'clockOutLocation', 'overtimeInLocation', 'overtimeOutLocation', 'overtimeSubmission', 'reviewer'])
            ->findOrFail($id);

        $this->editingAttendance = $attendance;
        $this->editingAttendanceId = $attendance->id;
        $this->editingAttendanceSource = $attendance->source;
        $this->editingAttendanceRawLog = $attendance->raw_log;
        $this->manualEmployeeId = $attendance->employee_id;
        $this->manualDate = $attendance->date ? $attendance->date->toDateString() : '';
        $this->manualClockIn = $attendance->clock_in ? substr($attendance->clock_in, 0, 5) : '';
        $this->manualClockOut = $attendance->clock_out ? substr($attendance->clock_out, 0, 5) : '';
        $this->hasFullDayOvertime = ! empty($attendance->clock_in) || ! empty($attendance->clock_out);
        $this->manualOvertimeIn = $attendance->overtime_in ? substr($attendance->overtime_in, 0, 5) : '';
        $this->manualOvertimeOut = $attendance->overtime_out ? substr($attendance->overtime_out, 0, 5) : '';
        $this->hasOvertime = ! empty($attendance->overtime_in) || ! empty($attendance->overtime_out);
        $status = $attendance->status ?? 'hadir';
        $this->manualStatus = in_array($status, ['terlambat', 'pulang_cepat'], true) ? 'hadir' : $status;
        $this->manualNotes = $attendance->notes ?? '';

        // Otomatis siapkan panel audit kanan (Side-by-side view)
        $this->auditAttendance = $attendance;
        $this->auditAttendanceId = $attendance->id;
        $this->auditTab = 'clock_in';
        $this->auditReviewNotes = $attendance->review_notes ?? '';

        $this->dispatch('open-modal', id: 'manualAttendanceModal');
        $this->dispatchMapAuditPayload();
    }

    /**
     * Buka modal audit visual: selfie & Leaflet map interactive.
     * Mengarahkan ke openEditModal agar terbuka dalam tampilan 2 panel berdampingan.
     */
    public function openAuditModal(int $id): void
    {
        $this->openEditModal($id);
    }

    /**
     * Ganti tab lokasi masuk / pulang / lembur pada peta audit.
     */
    public function setAuditTab(string $tab): void
    {
        $this->auditTab = in_array($tab, ['clock_in', 'clock_out', 'overtime'], true) ? $tab : 'clock_in';
        $this->dispatchMapAuditPayload();
    }

    /**
     * Kirim data koordinat GPS ke script Leaflet browser.
     */
    protected function dispatchMapAuditPayload(): void
    {
        if (! $this->auditAttendance) {
            return;
        }

        $att = $this->auditAttendance;

        if ($this->auditTab === 'overtime') {
            $location = $att->overtimeInLocation ?: ($att->overtimeOutLocation ?: ($att->employee?->workLocation ?: $att->clockInLocation));
            $empLat = (float) ($att->overtime_in_latitude ?: $att->overtime_out_latitude ?: 0);
            $empLng = (float) ($att->overtime_in_longitude ?: $att->overtime_out_longitude ?: 0);
            $distance = (int) ($att->overtime_in_distance_meters ?: $att->overtime_out_distance_meters ?: 0);
        } elseif ($this->auditTab === 'clock_out') {
            $location = $att->clockOutLocation ?: ($att->employee?->workLocation ?: $att->clockInLocation);
            $empLat = (float) ($att->clock_out_latitude ?: 0);
            $empLng = (float) ($att->clock_out_longitude ?: 0);
            $distance = (int) ($att->clock_out_distance_meters ?: 0);
        } else {
            $location = $att->clockInLocation ?: $att->employee?->workLocation;
            $empLat = (float) ($att->clock_in_latitude ?: 0);
            $empLng = (float) ($att->clock_in_longitude ?: 0);
            $distance = (int) ($att->clock_in_distance_meters ?: 0);
        }

        $officeLat = $location ? (float) $location->latitude : -6.5715000;
        $officeLng = $location ? (float) $location->longitude : 107.7587000;
        $radiusMeters = $location ? (int) $location->radius_meters : 50;
        $locationName = $location ? $location->name : 'Kantor Pusat - Percetakan Subang';

        $hasEmpGps = ($empLat != 0.0 && $empLng != 0.0);

        $this->dispatch('init-audit-map', [
            'tab' => $this->auditTab,
            'officeLat' => $officeLat,
            'officeLng' => $officeLng,
            'radiusMeters' => $radiusMeters,
            'locationName' => $locationName,
            'empLat' => $hasEmpGps ? $empLat : null,
            'empLng' => $hasEmpGps ? $empLng : null,
            'distanceMeters' => $distance,
            'isInsideRadius' => $hasEmpGps ? ($distance <= $radiusMeters) : false,
        ]);
    }

    /**
     * Setujui presensi yang pending review.
     */
    public function approveAudit(AttendanceService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        if (! $this->auditAttendance) {
            return;
        }

        try {
            $service->reviewAttendance(
                attendance: $this->auditAttendance,
                status: 'approved',
                reviewNotes: $this->auditReviewNotes,
                actor: auth()->user()
            );

            $this->auditAttendance->refresh();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Presensi Disetujui!',
                'message' => 'Status presensi '.$this->auditAttendance->employee?->full_name.' berhasil disetujui.',
            ]);

            $this->dispatch('close-modal', id: 'attendanceAuditModal');
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memproses Review',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Tolak presensi yang pending review (tandai absen/mangkir).
     */
    public function rejectAudit(AttendanceService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        if (! $this->auditAttendance) {
            return;
        }

        try {
            $service->reviewAttendance(
                attendance: $this->auditAttendance,
                status: 'rejected',
                reviewNotes: $this->auditReviewNotes ?: 'Ditolak karena di luar radius kantor tanpa alasan yang valid.',
                actor: auth()->user()
            );

            $this->auditAttendance->refresh();

            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Presensi Ditolak',
                'message' => 'Status presensi '.$this->auditAttendance->employee?->full_name.' ditandai sebagai Mangkir (Absen).',
            ]);

            $this->dispatch('close-modal', id: 'attendanceAuditModal');
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memproses Review',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updatedManualDate(string $value): void
    {
        if (! $this->lockDateAndEmployee) {
            $this->syncScheduleStatus();
        }
    }

    public function updatedManualEmployeeId($value): void
    {
        if (! $this->lockDateAndEmployee) {
            $this->syncScheduleStatus();
        }
    }

    protected function syncScheduleStatus(): void
    {
        if (empty($this->manualDate)) {
            return;
        }

        try {
            $isSunday = Carbon::parse($this->manualDate)->isSunday();
        } catch (\Exception $e) {
            return;
        }

        $schedule = null;
        if (! empty($this->manualEmployeeId)) {
            $schedule = EmployeeSchedule::where('employee_id', $this->manualEmployeeId)
                ->where('date', $this->manualDate)
                ->first();
        }
        $isOff = $schedule ? ($schedule->status === EmployeeSchedule::STATUS_OFF) : $isSunday;

        if ($isOff) {
            $this->manualStatus = 'libur';
            if (! $this->hasFullDayOvertime) {
                $this->manualClockIn = '';
                $this->manualClockOut = '';
            }
        } elseif ($this->manualStatus === 'libur') {
            $this->manualStatus = 'hadir';
            if (empty($this->manualClockIn) && empty($this->manualClockOut)) {
                $this->manualClockIn = '08:00';
                $this->manualClockOut = '17:00';
            }
        }
    }

    public function updatedManualStatus(string $value): void
    {
        if ($value === 'libur') {
            $this->hasFullDayOvertime = ! empty($this->manualClockIn) || ! empty($this->manualClockOut);
        } elseif ($value === 'hadir') {
            if (empty($this->manualClockIn) && empty($this->manualClockOut)) {
                $this->manualClockIn = '08:00';
                $this->manualClockOut = '17:00';
            }
        } else {
            $this->manualClockIn = '';
            $this->manualClockOut = '';
            $this->hasFullDayOvertime = false;
            $this->hasOvertime = false;
            $this->manualOvertimeIn = '';
            $this->manualOvertimeOut = '';
        }
    }

    public function updatedHasFullDayOvertime(bool $value): void
    {
        if ($value) {
            if (empty($this->manualClockIn)) {
                $this->manualClockIn = '08:00';
            }
            if (empty($this->manualClockOut)) {
                $this->manualClockOut = '17:00';
            }
        } else {
            $this->manualClockIn = '';
            $this->manualClockOut = '';
        }
    }

    public function updatedHasOvertime(bool $value): void
    {
        if (! $value) {
            $this->manualOvertimeIn = '';
            $this->manualOvertimeOut = '';
        }
    }

    public function clearClockIn(): void
    {
        $this->manualClockIn = '';
    }

    public function clearClockOut(): void
    {
        $this->manualClockOut = '';
    }

    public function clearOvertimeIn(): void
    {
        $this->manualOvertimeIn = '';
    }

    public function clearOvertimeOut(): void
    {
        $this->manualOvertimeOut = '';
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
            'manualClockIn' => ['required_if:manualStatus,hadir', 'nullable', 'date_format:H:i'],
            'manualClockOut' => ['nullable', 'date_format:H:i'],
            'manualOvertimeIn' => ['nullable', 'date_format:H:i'],
            'manualOvertimeOut' => ['nullable', 'date_format:H:i'],
            'manualStatus' => ['required', 'string', 'in:hadir,terlambat,pulang_cepat,absen,libur,cuti,sakit,izin'],
            'manualNotes' => ['nullable', 'string', 'max:500'],
        ], [
            'manualEmployeeId.required' => 'Pilih karyawan terlebih dahulu.',
            'manualDate.required' => 'Tanggal presensi wajib diisi.',
            'manualClockIn.required_if' => 'Jam masuk wajib diisi jika status kehadiran Hadir.',
            'manualClockIn.date_format' => 'Format jam masuk tidak valid (HH:MM).',
            'manualClockOut.date_format' => 'Format jam keluar tidak valid (HH:MM).',
            'manualOvertimeIn.date_format' => 'Format jam lembur mulai tidak valid (HH:MM).',
            'manualOvertimeOut.date_format' => 'Format jam lembur selesai tidak valid (HH:MM).',
            'manualStatus.required' => 'Status kehadiran wajib dipilih.',
        ]);

        $isWorkStatus = in_array($this->manualStatus, ['hadir', 'terlambat', 'pulang_cepat'], true);
        $allowHours = $isWorkStatus || $this->manualStatus === 'libur';
        $hasOt = $allowHours && ($this->hasOvertime || ! empty($this->manualOvertimeIn) || ! empty($this->manualOvertimeOut));

        $durationMins = 0;
        $lateMins = 0;
        $overtimeMins = 0;

        if (! $allowHours) {
            $this->manualClockIn = '';
            $this->manualClockOut = '';
        }

        if (! $hasOt) {
            $this->manualOvertimeIn = '';
            $this->manualOvertimeOut = '';
        }

        if ($isWorkStatus && $this->manualClockIn) {
            [$inH, $inM] = explode(':', $this->manualClockIn);
            $inMinutes = ((int) $inH * 60) + (int) $inM;
            if ($inMinutes > 480 && $inMinutes < 720) { // Masuk shift pagi lewat 08:00
                $lateMins = $inMinutes - 480;
            } elseif ($inMinutes > 1320) { // Masuk shift malam lewat 22:00
                $lateMins = $inMinutes - 1320;
            }
        }

        if ($allowHours && $this->manualClockIn && $this->manualClockOut) {
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

        if ($hasOt && $this->manualOvertimeIn && $this->manualOvertimeOut) {
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
        } elseif (! $hasOt) {
            $this->manualOvertimeIn = '';
            $this->manualOvertimeOut = '';
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
                    'clock_in' => $this->manualClockIn ? $this->manualClockIn.':00' : null,
                    'clock_out' => $this->manualClockOut ? $this->manualClockOut.':00' : null,
                    'overtime_in' => $this->manualOvertimeIn ? $this->manualOvertimeIn.':00' : null,
                    'overtime_out' => $this->manualOvertimeOut ? $this->manualOvertimeOut.':00' : null,
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
                        $clockIn = (strlen($regTimes[0]) === 4 ? '0'.$regTimes[0] : $regTimes[0]).':00';
                        if (count($regTimes) > 1) {
                            $clockOut = (strlen($regTimes[1]) === 4 ? '0'.$regTimes[1] : $regTimes[1]).':00';
                        }
                    }
                } elseif (str_starts_with($part, 'OT:')) {
                    $otContent = trim(substr($part, 3));
                    preg_match_all('/\d{1,2}:\d{2}/', $otContent, $otMatches);
                    $otTimes = $otMatches[0] ?? [];
                    if (! empty($otTimes)) {
                        $overtimeIn = (strlen($otTimes[0]) === 4 ? '0'.$otTimes[0] : $otTimes[0]).':00';
                        if (count($otTimes) > 1) {
                            $overtimeOut = (strlen($otTimes[1]) === 4 ? '0'.$otTimes[1] : $otTimes[1]).':00';
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
                    'message' => 'Tidak ditemukan waktu jam pada log mesin asli: '.$attendance->raw_log,
                ]);

                return;
            }

            $clockIn = (strlen($times[0]) === 4 ? '0'.$times[0] : $times[0]).':00';
            $latestTime = end($times);
            if (strlen($latestTime) === 4) {
                $latestTime = '0'.$latestTime;
            }
            $clockOut = ($latestTime !== substr($clockIn, 0, 5)) ? $latestTime.':00' : null;
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
            if (CompanyProfile::getProfile()->isRosterMode()) {
                $schedule = EmployeeSchedule::where('employee_id', $attendance->employee_id)
                    ->where('date', $attendance->date?->toDateString() ?? $dateObj->toDateString())
                    ->first();

                $status = ($schedule && $schedule->isOff()) ? 'libur' : 'absen';
            } else {
                $status = $isSunday ? 'libur' : 'absen';
            }
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

    /*
    |--------------------------------------------------------------------------
    | Computed Properties (Matriks Rekap Kehadiran)
    |--------------------------------------------------------------------------
    */

    /**
     * Rentang tanggal matriks: hasil parse filter dengan pembatasan maksimum
     * 31 hari (tanggal akhir otomatis dipangkas bila melebihi).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    #[Computed]
    public function matrixRange(): array
    {
        $start = ! empty($this->startDate) ? Carbon::parse($this->startDate) : Carbon::now()->startOfWeek();
        $end = ! empty($this->endDate) ? Carbon::parse($this->endDate) : Carbon::now()->endOfWeek();

        // Prevent performance issues by capping matrix date range to 31 days max
        if ($start->diffInDays($end) > 31) {
            $end = $start->copy()->addDays(30);
            $this->endDate = $end->toDateString();
        }

        return [$start, $end];
    }

    /**
     * Daftar tanggal matriks 1 s/d 31 hari lengkap dengan penanda Minggu
     * dan hari libur nasional.
     */
    #[Computed]
    public function matrixDates(): array
    {
        [$start, $end] = $this->matrixRange;

        $holidayDates = Holiday::datesBetween($start->toDateString(), $end->toDateString());
        $matrixDates = [];
        $cur = $start->copy();
        static $dowNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        while ($cur->lte($end)) {
            $dateString = $cur->toDateString();
            $matrixDates[] = [
                'date' => $dateString,
                'day' => $cur->day,
                'dow_name' => $dowNames[$cur->dayOfWeek],
                'is_sunday' => $cur->isSunday(),
                'is_weekend' => $cur->isWeekend(),
                'is_holiday' => $holidayDates->has($dateString),
                'holiday_name' => $holidayDates->has($dateString) ? $holidayDates->get($dateString) : null,
            ];
            $cur->addDay();
        }

        return $matrixDates;
    }

    /**
     * Karyawan aktif untuk matriks: filter pencarian/departemen/status,
     * pembatasan role karyawan, dan urutan status kepegawaian.
     */
    #[Computed]
    public function matrixEmployees(): LengthAwarePaginator
    {
        $empQuery = Employee::where('status', 'active')->with(['department', 'position']);

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

        // CAST ke tipe numerik: UNSIGNED hanya ada di MySQL/MariaDB,
        // PostgreSQL memakai INTEGER (dengan NULLIF untuk string kosong).
        $fingerprintOrder = match (DB::connection()->getDriverName()) {
            'pgsql' => "CAST(NULLIF(fingerprint_id, '') AS INTEGER) ASC NULLS LAST",
            'sqlite' => "CAST(CASE WHEN fingerprint_id = '' THEN NULL ELSE fingerprint_id END AS INTEGER) ASC",
            default => 'CAST(fingerprint_id AS UNSIGNED) ASC',
        };

        if ($this->sortBy === 'name_asc') {
            $empQuery->orderBy('full_name', 'asc');
        } elseif ($this->sortBy === 'name_desc') {
            $empQuery->orderBy('full_name', 'desc');
        } elseif ($this->sortBy === 'position_asc') {
            $empQuery->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
                ->orderBy('positions.name', 'asc')
                ->orderBy('employees.full_name', 'asc')
                ->select('employees.*');
        } elseif ($this->sortBy === 'department_asc') {
            $empQuery->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                ->orderBy('departments.name', 'asc')
                ->orderBy('employees.full_name', 'asc')
                ->select('employees.*');
        } elseif ($this->sortBy === 'status_asc') {
            $empQuery->orderByRaw("CASE
                WHEN employment_status = 'tetap' THEN 1
                WHEN employment_status = 'kontrak' THEN 2
                WHEN employment_status = 'harian' THEN 3
                WHEN employment_status = 'magang' THEN 4
                ELSE 5
            END")
                ->orderByRaw($fingerprintOrder)
                ->orderBy('full_name');
        } else {
            // Urutan kustom dari roster jadwal kerja (atau fallback nama A-Z)
            $customOrder = cache()->get('roster_employee_order', []);
            if (! empty($customOrder)) {
                $cases = [];
                foreach ($customOrder as $idx => $id) {
                    $cases[] = 'WHEN '.(int) $id.' THEN '.($idx + 1);
                }
                $caseSql = 'CASE employees.id '.implode(' ', $cases).' ELSE 999999 END';
                $empQuery->orderByRaw($caseSql)->orderBy('full_name');
            } else {
                $empQuery->orderBy('full_name', 'asc');
            }
        }

        $pageSize = $this->perPage > 0 ? $this->perPage : 9999;

        return $empQuery->paginate($pageSize);
    }

    /**
     * Presensi dalam rentang matriks sebagai stdClass ringan, dikelompokkan
     * per employee_id.
     */
    #[Computed]
    public function attendancesInRange(): Collection
    {
        [$start, $end] = $this->matrixRange;

        return DB::table('attendances')
            ->select([
                'id', 'employee_id', 'date', 'clock_in', 'clock_out',
                'overtime_in', 'overtime_out', 'status', 'source',
                'review_status', 'clock_in_distance_meters',
            ])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('employee_id', $this->matrixEmployees->pluck('id'))
            ->get()
            ->groupBy('employee_id');
    }

    /**
     * Karyawan matriks dikelompokkan per status kepegawaian untuk baris
     * sub-header grup.
     */
    #[Computed]
    public function groupedMatrixEmployees(): Collection
    {
        if ($this->sortBy === 'status_asc') {
            return $this->matrixEmployees->getCollection()->groupBy(function ($emp) {
                return $emp->employment_status instanceof EmploymentStatus
                    ? $emp->employment_status->value
                    : (string) ($emp->employment_status ?: 'lainnya');
            });
        }

        return collect([
            'all' => $this->matrixEmployees->getCollection(),
        ]);
    }

    /**
     * Peta label & ikon status kepegawaian untuk header grup matriks.
     */
    #[Computed]
    public function employmentStatusMeta(): array
    {
        $meta = [];
        foreach (EmploymentStatus::cases() as $status) {
            $meta[$status->value] = [
                'label' => $status->label(),
                'icon' => $status->icon(),
            ];
        }

        return $meta;
    }

    /**
     * Jadwal kerja roster karyawan dalam rentang matriks.
     *
     * @return Collection<int, Collection<string, EmployeeSchedule>>
     */
    #[Computed]
    public function employeeSchedules(): Collection
    {
        [$start, $end] = $this->matrixRange;
        $employeeIds = $this->matrixEmployees->pluck('id')->all();

        return EmployeeSchedule::schedulesForEmployees($employeeIds, $start->toDateString(), $end->toDateString());
    }

    /**
     * Data per baris karyawan matriks: presensi yang sudah di-keyBy tanggal
     * (Y-m-d), jadwal roster, label "departemen • posisi", representasi sel HTML terkompilasi,
     * serta ringkasan counter kehadiran dalam satu kali iterasi.
     *
     * @return array<int, array{att: Collection, schedules: Collection, label: string, cells: array, summary: array}>
     */
    #[Computed]
    public function matrixRowData(): array
    {
        $rows = [];
        $schedules = $this->employeeSchedules;
        $matrixDates = $this->matrixDates;
        $attendancesInRange = $this->attendancesInRange;

        foreach ($this->matrixEmployees->getCollection() as $emp) {
            $empAttCollection = $attendancesInRange->get($emp->id, collect())->keyBy(
                fn ($a) => is_string($a->date) ? substr($a->date, 0, 10) : ($a->date ? $a->date->toDateString() : '')
            );
            $empSchedules = $schedules->get($emp->id, collect());
            $deptPosParts = array_filter([$emp->department?->name, $emp->position?->name]);

            $cells = [];
            $counters = ['hadir' => 0, 'manual' => 0, 'cuti' => 0, 'sakit' => 0, 'izin' => 0, 'absen' => 0, 'libur' => 0];

            foreach ($matrixDates as $d) {
                $date = $d['date'];
                $att = $empAttCollection->get($date);
                $sch = $empSchedules->get($date);
                $isOff = $sch ? ($sch->status === EmployeeSchedule::STATUS_OFF) : ($d['is_sunday'] || $d['is_holiday']);

                if ($isOff) {
                    $counters['libur']++;
                }

                $status = $att ? strtolower($att->status) : null;
                $hasOt = $att && (! empty($att->overtime_in) || ($att->overtime_duration_minutes ?? 0) > 0);
                $hasWorkHours = $att && (! empty($att->clock_in) || ! empty($att->clock_out));
                $isRegularWork = $att !== null && in_array($status, ['hadir', 'terlambat', 'pulang_cepat'], true) && $hasWorkHours;

                $cls = '';
                $content = '&nbsp;';
                $title = $emp->full_name." ({$date})";

                if ($isRegularWork) {
                    $counters['hadir']++;
                    if (($att->source ?? '') === 'manual') {
                        $counters['manual']++;
                    }

                    $sourceLabel = match ($att->source) {
                        'web_gps' => ' [Web GPS'.(! empty($att->clock_in_distance_meters) ? ' '.$att->clock_in_distance_meters.'m' : '').']',
                        'manual' => ' [Manual]',
                        default => ' [Fingerprint]',
                    };
                    $reviewLabel = (($att->review_status ?? null) === 'pending_review') ? ' (Perlu Review Geofence)' : '';
                    $title .= ': '.ucfirst($status).($att->clock_in ? ' Masuk '.substr($att->clock_in, 0, 5) : '').($att->clock_out ? ' s/d Pulang '.substr($att->clock_out, 0, 5) : '').($hasOt ? ' | Lembur '.substr($att->overtime_in, 0, 5).($att->overtime_out ? '-'.substr($att->overtime_out, 0, 5) : '') : '').$sourceLabel.$reviewLabel.' (Klik untuk ubah)';

                    $reviewBadge = (($att->review_status ?? null) === 'pending_review')
                        ? '<span class="position-absolute" style="top:1px;right:1px;width:6px;height:6px;background-color:#f59e0b;border-radius:50%;box-shadow:0 0 0 1px #fff" title="Perlu review geofence"></span>'
                        : '';
                    $otBadge = $hasOt ? '<span class="att-ot-ribbon" title="Ada Lembur">OT</span>' : '';

                    if ($status === 'hadir') {
                        $manualClass = ($att->source ?? '') === 'manual' ? ' att-chip-manual' : '';
                        $cls = 'att-cell-present att-chip-present position-relative'.$manualClass;
                        $content = '<i class="fas fa-check att-cell-check"></i>'.$reviewBadge.$otBadge;
                    } elseif ($status === 'terlambat') {
                        $cls = 'att-cell-late position-relative';
                        $content = '<span class="att-cell-letter">T</span>'.$reviewBadge.$otBadge;
                    } else { // pulang_cepat
                        $cls = 'att-cell-early position-relative';
                        $content = '<span class="att-cell-letter" style="font-size:9.5px">PC</span>'.$reviewBadge.$otBadge;
                    }
                } elseif ($status === 'libur' && $hasWorkHours) {
                    $title .= ': Lembur Hari Libur'.($att->clock_in ? ' Masuk '.substr($att->clock_in, 0, 5) : '').($att->clock_out ? ' s/d Pulang '.substr($att->clock_out, 0, 5) : '').($hasOt ? ' | Lembur '.substr($att->overtime_in, 0, 5).($att->overtime_out ? '-'.substr($att->overtime_out, 0, 5) : '') : '').(($att->source ?? '') === 'manual' ? ' [Manual]' : ' [Fingerprint]').' (Klik untuk ubah)';
                    $manualClass = ($att->source ?? '') === 'manual' ? ' att-chip-manual' : '';
                    $cls = 'position-relative'.$manualClass;
                    $otBadge = $hasOt ? '<span class="att-ot-ribbon" title="Ada Lembur">OT</span>' : '';
                    $content = '<i class="fas fa-check att-cell-check text-white"></i>'.$otBadge;
                } elseif ($hasOt) {
                    $title .= ': Lembur '.substr($att->overtime_in, 0, 5).($att->overtime_out ? ' - '.substr($att->overtime_out, 0, 5) : '').' (Klik untuk ubah)';
                    $cls = ! $isOff ? 'att-cell-ot-solo' : '';
                    $content = $isOff
                        ? '<span class="att-cell-letter text-white" style="font-size:10.5px;font-weight:800">OT</span>'
                        : '<span class="att-cell-letter" style="color:#b45309;font-size:10px">OT</span>';
                } elseif ($status === 'cuti') {
                    $counters['cuti']++;
                    $title .= ': Cuti (Klik untuk ubah)';
                    $cls = 'att-cell-cuti';
                    $content = '<span class="att-cell-letter">C</span>';
                } elseif ($status === 'sakit') {
                    $counters['sakit']++;
                    $title .= ': Sakit (Klik untuk ubah)';
                    $cls = 'att-cell-sakit';
                    $content = '<span class="att-cell-letter">S</span>';
                } elseif ($status === 'izin') {
                    $counters['izin']++;
                    $title .= ': Izin (Klik untuk ubah)';
                    $cls = 'att-cell-izin';
                    $content = '<span class="att-cell-letter">I</span>';
                } elseif ($status === 'absen' || (! $isOff && ! $att)) {
                    $counters['absen']++;
                    $title .= $status === 'absen' ? ': Alpa / Mangkir (Klik untuk ubah)' : ': Belum ada presensi (Klik untuk input jam)';
                    if ($status === 'absen') {
                        $cls = 'att-cell-alpa';
                        $content = '<span class="att-cell-letter">A</span>';
                    } else {
                        $cls = 'text-muted';
                        $content = '<span style="color:#cbd5e1;font-size:13px">&ndash;</span>';
                    }
                } elseif ($isOff) {
                    $title .= ': Jadwal Libur / Off (Klik untuk input jam)';
                    $content = '&nbsp;';
                }

                $cells[$date] = [
                    'is_off' => $isOff,
                    'class' => $cls,
                    'content' => $content,
                    'title' => $title,
                ];
            }

            $rows[$emp->id] = [
                'att' => $empAttCollection,
                'schedules' => $empSchedules,
                'label' => ! empty($deptPosParts) ? implode(' • ', $deptPosParts) : '-',
                'cells' => $cells,
                'summary' => $counters,
            ];
        }

        return $rows;
    }

    /**
     * Rekapitulasi kehadiran per karyawan pada matriks.
     * Menggunakan hasil ringkasan yang telah dihitung bersamaan di matrixRowData.
     *
     * @return array<int, array{hadir: int, manual: int, cuti: int, sakit: int, izin: int, absen: int, libur: int}>
     */
    #[Computed]
    public function matrixSummary(): array
    {
        $summary = [];
        foreach ($this->matrixRowData as $empId => $row) {
            $summary[$empId] = $row['summary'];
        }

        return $summary;
    }

    public function render(AttendanceService $service): View
    {
        [$start, $end] = $this->matrixRange;

        // Restrict non-admin karyawan (untuk log detail & metrik)
        $user = auth()->user();
        $isKaryawan = $user?->hasRole('karyawan') && ! $user?->hasRole(['admin', 'direktur', 'developer']);
        $karyawanEmployee = $isKaryawan ? $user->getEmployeeRecord() : null;

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
                COUNT(CASE WHEN employees.employment_status = ? THEN 1 END) as total_harian,
                COUNT(CASE WHEN attendances.review_status = 'pending_review' THEN 1 END) as total_pending_review
            ", [EmploymentStatus::HARIAN->value])
            ->first();

        $metrics = [
            'total_logs' => (int) ($metricRow?->total_logs ?? 0),
            'total_hadir' => (int) ($metricRow?->total_hadir ?? 0),
            'total_karyawan_hadir' => (int) ($metricRow?->total_karyawan_hadir ?? 0),
            'total_harian' => (int) ($metricRow?->total_harian ?? 0),
            'total_pending_review' => (int) ($metricRow?->total_pending_review ?? 0),
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
            'matrixDates' => $this->matrixDates,
            'matrixEmployees' => $this->matrixEmployees,
            'groupedMatrixEmployees' => $this->groupedMatrixEmployees,
            'employmentStatusMeta' => $this->employmentStatusMeta,
            'matrixRowData' => $this->matrixRowData,
            'matrixSummary' => $this->matrixSummary,
            'attendancesLog' => $attendancesLog,
            'metrics' => $metrics,
            'departments' => $departments,
            'allEmployees' => $allEmployees,
            'employmentStatuses' => EmploymentStatus::cases(),
            'periodMonthLabel' => $start->locale('id')->isoFormat('MMMM Y'),
            'periodFullRangeLabel' => $start->locale('id')->isoFormat('D MMMM').' – '.$end->locale('id')->isoFormat('D MMMM Y'),
            'isCurrentMonth' => $start->isSameMonth(Carbon::now()),
        ])->layout('layouts.app');
    }
}
