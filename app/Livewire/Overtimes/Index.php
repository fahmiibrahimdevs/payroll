<?php

namespace App\Livewire\Overtimes;

use App\Enums\OvertimePeriodStatus;
use App\Enums\OvertimeStatus;
use App\Livewire\Forms\OvertimeSubmissionForm;
use App\Models\Employee;
use App\Models\OvertimePeriod;
use App\Models\OvertimeSubmission;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Kelola Lembur')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public OvertimeSubmissionForm $form;

    public string $activeTab = 'submissions'; // submissions | periods

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $employeeFilter = null;

    public ?string $startDateFilter = null;

    public ?string $endDateFilter = null;

    public int $perPage = 15;

    // Period creation modal
    public string $period_name = '';

    public string $period_start_date = '';

    public string $period_end_date = '';

    public string $period_notes = '';

    // Admin adjust modal
    public ?int $adjustingSubmissionId = null;

    public ?OvertimeSubmission $adjustingSubmission = null;

    public string $adjust_start_time = '';

    public string $adjust_end_time = '';

    public float $adjust_duration_hours = 0;

    public float $adjust_meal_allowance = 0;

    public string $adjust_admin_notes = '';

    // Rejection modal
    public ?int $rejectingSubmissionId = null;

    public string $rejectionReason = '';

    // Detail modal
    public ?int $detailSubmissionId = null;

    public ?OvertimeSubmission $detailSubmission = null;

    // Attendance & Meal Allowance modal state
    public ?int $managingPeriodId = null;

    public ?OvertimePeriod $managingPeriod = null;

    public array $periodAttendances = [];

    public function mount(): void
    {
        $this->form->date = date('Y-m-d');
        $now = Carbon::now();
        $start = $now->isFriday() ? $now->copy() : $now->copy()->previous(Carbon::FRIDAY);
        $end = $start->copy()->addDays(6);
        $this->period_start_date = $start->format('Y-m-d');
        $this->period_end_date = $end->format('Y-m-d');

        $user = auth()->user();
        if ($user && $user->hasRole('karyawan')) {
            $emp = $user->getEmployeeRecord();
            abort_unless($emp !== null, 403, 'Akun Anda belum terhubung dengan data profil karyawan.');
            $this->employeeFilter = $emp->id;
            $this->form->employee_id = $emp->id;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingEmployeeFilter(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        if (auth()->user()?->hasRole('karyawan')) {
            $this->activeTab = 'submissions';
            $this->resetPage();

            return;
        }

        $this->activeTab = in_array($tab, ['submissions', 'periods'], true) ? $tab : 'submissions';
        $this->resetPage();
    }

    public function quickFilterStatus(?string $status = null): void
    {
        $this->statusFilter = $status ?? 'all';
        $this->activeTab = 'submissions';
        $this->resetPage();
    }

    /**
     * Open create submission modal.
     */
    public function openCreateModal(): void
    {
        $user = auth()->user();
        abort_if($user?->hasRole('direktur'), 403, 'Direktur hanya memiliki hak akses untuk menyetujui pengajuan lembur.');

        $this->form->resetForm();
        $this->form->date = date('Y-m-d');

        if ($user->hasRole('karyawan')) {
            $emp = $user->getEmployeeRecord();
            if (! $emp) {
                $this->dispatch('swal:alert', [
                    'type' => 'error',
                    'title' => 'Akses Ditolak',
                    'message' => 'Profil karyawan Anda tidak ditemukan di sistem.',
                ]);
                return;
            }

            if ((float) ($emp->overtime_rate_per_hour ?? 0) <= 0 && (float) ($emp->overtime_rate_per_day ?? 0) <= 0) {
                $this->dispatch('swal:alert', [
                    'type' => 'warning',
                    'title' => 'Tarif Lembur Belum Diset',
                    'message' => 'Anda belum memiliki tarif lembur (per jam atau harian). Silakan koordinasi dengan Admin HRD terlebih dahulu.',
                ]);
                return;
            }

            $this->form->employee_id = $emp->id;
        }

        $this->dispatch('open-modal', id: 'overtimeSubmissionModal');
    }

    /**
     * Switch overtime type between hourly and daily.
     */
    public function setOvertimeType(string $type): void
    {
        $this->form->overtime_type = in_array($type, ['hourly', 'daily'], true) ? $type : 'hourly';
        if ($this->form->overtime_type === 'daily') {
            $this->form->start_time = '';
            $this->form->end_time = '';
            $this->form->duration_hours = null;
        }
    }

    /**
     * Store new overtime submission.
     */
    public function storeSubmission(OvertimeService $service): void
    {
        $user = auth()->user();
        abort_if($user?->hasRole('direktur'), 403, 'Direktur hanya memiliki hak akses untuk menyetujui pengajuan lembur.');

        if ($user->hasRole('karyawan')) {
            $emp = $user->getEmployeeRecord();
            $this->form->employee_id = $emp?->id;
        }

        $validated = $this->form->validate();

        try {
            $service->createSubmission($validated, $user);

            $this->dispatch('close-modal', id: 'overtimeSubmissionModal');
            $this->form->resetForm();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => $user->hasRole('admin')
                    ? 'Pengajuan lembur berhasil dibuat dan langsung diajukan ke Direktur.'
                    : 'Pengajuan lembur berhasil dikirim. Menunggu verifikasi dari Admin.',
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mengajukan',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open admin adjustment modal.
     */
    public function openAdjustModal(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya Admin yang dapat menyesuaikan pengajuan lembur.');

        $submission = OvertimeSubmission::with('employee')->findOrFail($id);

        if ($submission->status !== OvertimeStatus::PENDING_ADMIN) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Tidak Dapat Disesuaikan',
                'message' => 'Pengajuan ini sudah tidak berstatus Menunggu Verifikasi Admin.',
            ]);
            return;
        }

        $this->adjustingSubmissionId = $submission->id;
        $this->adjustingSubmission = $submission;
        $this->adjust_start_time = ! empty($submission->start_time) ? Carbon::parse($submission->start_time)->format('H:i') : '';
        $this->adjust_end_time = ! empty($submission->end_time) ? Carbon::parse($submission->end_time)->format('H:i') : '';
        $this->adjust_duration_hours = (float) $submission->duration_hours;
        $this->adjust_meal_allowance = (float) $submission->meal_allowance;
        $this->adjust_admin_notes = $submission->admin_notes ?? '';

        $this->dispatch('open-modal', id: 'overtimeAdjustModal');
    }

    public function updatedAdjustStartTime(): void
    {
        $this->recalculateAdjustDuration();
    }

    public function updatedAdjustEndTime(): void
    {
        $this->recalculateAdjustDuration();
    }

    protected function recalculateAdjustDuration(): void
    {
        if (! empty($this->adjust_start_time) && ! empty($this->adjust_end_time)) {
            try {
                $start = Carbon::parse($this->adjust_start_time);
                $end = Carbon::parse($this->adjust_end_time);
                if (! $start->equalTo($end)) {
                    $this->adjust_duration_hours = app(OvertimeService::class)->calculateOvertimeDuration(
                        $this->adjust_start_time,
                        $this->adjust_end_time
                    );
                }
            } catch (\Throwable) {
                // ignore
            }
        }
    }

    /**
     * Admin submits adjustment and forwards to Direktur.
     */
    public function submitAdjustment(OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $submission = OvertimeSubmission::findOrFail($this->adjustingSubmissionId);

        $rules = [
            'adjust_meal_allowance' => ['nullable', 'numeric', 'min:0'],
            'adjust_admin_notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($submission->isHourly()) {
            $rules['adjust_start_time'] = ['required', 'date_format:H:i'];
            $rules['adjust_end_time'] = ['required', 'date_format:H:i', 'different:adjust_start_time'];
            $rules['adjust_duration_hours'] = ['required', 'numeric', 'min:0.25', 'max:24'];
        } else {
            $rules['adjust_start_time'] = ['nullable'];
            $rules['adjust_end_time'] = ['nullable'];
            $rules['adjust_duration_hours'] = ['nullable', 'numeric'];
        }

        $this->validate($rules, [
            'adjust_end_time.different' => 'Jam selesai tidak boleh sama dengan jam mulai.',
        ], [
            'adjust_start_time' => 'Jam Mulai',
            'adjust_end_time' => 'Jam Selesai',
            'adjust_duration_hours' => 'Durasi Jam',
            'adjust_meal_allowance' => 'Uang Makan',
            'adjust_admin_notes' => 'Catatan Admin',
        ]);

        try {
            $service->adjustAndForwardToDirector($submission, [
                'start_time' => $this->adjust_start_time,
                'end_time' => $this->adjust_end_time,
                'duration_hours' => $this->adjust_duration_hours,
                'meal_allowance' => $this->adjust_meal_allowance,
                'admin_notes' => $this->adjust_admin_notes,
            ], auth()->user());

            $this->dispatch('close-modal', id: 'overtimeAdjustModal');

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Verifikasi Selesai',
                'message' => 'Lembur berhasil disesuaikan dan telah diteruskan ke Direktur untuk approval final.',
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memproses',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Direktur approves submission (invokable directly or via SweetAlert2 Livewire event).
     */
    #[On('approve-overtime-submission')]
    public function approveSubmission(int|string|array $id, OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole('direktur'), 403, 'Hanya Direktur yang dapat menyetujui pengajuan lembur.');

        $submissionId = is_array($id) ? (int) ($id['id'] ?? reset($id)) : (int) $id;

        try {
            $submission = OvertimeSubmission::findOrFail($submissionId);
            $service->approveByDirector($submission, auth()->user());

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Lembur Disetujui',
                'message' => "Pengajuan lembur {$submission->reference_number} telah disetujui.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyetujui',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reject overtime submission via SweetAlert2 Livewire event.
     */
    #[On('reject-overtime-submission')]
    public function rejectSubmission(int|string|array $id, ?string $reason = null, ?OvertimeService $service = null): void
    {
        $service = $service ?? app(OvertimeService::class);
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        if (is_array($id)) {
            $submissionId = (int) ($id['id'] ?? 0);
            $cleanReason = trim((string) ($id['reason'] ?? $reason ?? ''));
        } else {
            $submissionId = (int) $id;
            $cleanReason = trim((string) ($reason ?? ''));
        }

        if (strlen($cleanReason) < 5) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Alasan Penolakan Tidak Valid',
                'message' => 'Alasan penolakan minimal 5 karakter.',
            ]);
            return;
        }

        try {
            $submission = OvertimeSubmission::findOrFail($submissionId);
            $service->rejectSubmission($submission, $cleanReason, auth()->user());

            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Pengajuan Ditolak',
                'message' => "Pengajuan lembur {$submission->reference_number} telah ditolak.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menolak',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open rejection modal.
     */
    public function openRejectModal(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->rejectingSubmissionId = $id;
        $this->rejectionReason = '';
        $this->dispatch('open-modal', id: 'overtimeRejectModal');
    }

    /**
     * Reject submission with reason.
     */
    public function submitRejection(OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [], ['rejectionReason' => 'Alasan Penolakan']);

        try {
            $submission = OvertimeSubmission::findOrFail($this->rejectingSubmissionId);
            $service->rejectSubmission($submission, $this->rejectionReason, auth()->user());

            $this->dispatch('close-modal', id: 'overtimeRejectModal');

            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Pengajuan Ditolak',
                'message' => "Pengajuan lembur {$submission->reference_number} telah ditolak.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menolak',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger SweetAlert2 confirmation dialog for deleting an overtime submission.
     */
    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $submission = OvertimeSubmission::with('employee')->findOrFail($id);

        if ($submission->overtime_period_id !== null) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Tidak Dapat Dihapus',
                'message' => 'Pengajuan lembur ini sudah masuk ke dalam periode lembur mingguan.',
            ]);
            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'action' => 'delete-overtime-submission',
            'id' => $id,
            'title' => "Pengajuan Lembur {$submission->reference_number}",
            'text' => "Data pengajuan lembur {$submission->reference_number} ({$submission->employee?->full_name}) akan dihapus permanen.",
        ]);
    }

    /**
     * Delete overtime submission.
     */
    #[On('delete-overtime-submission')]
    public function deleteSubmission(int|string|array $id, OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $submissionId = is_array($id) ? ($id['id'] ?? reset($id)) : (int) $id;

        try {
            $service->deleteSubmission((int) $submissionId, auth()->user());

            if ($this->detailSubmissionId === (int) $submissionId) {
                $this->detailSubmissionId = null;
                $this->detailSubmission = null;
                $this->dispatch('close-modal', id: 'overtimeDetailModal');
            }

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Pengajuan lembur berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * View detail of submission.
     */
    public function openDetailModal(int $id): void
    {
        $submission = OvertimeSubmission::with([
            'employee.department',
            'employee.position',
            'submitter',
            'verifiedByAdmin',
            'approvedByDirektur',
            'rejectedBy',
            'overtimePeriod',
        ])->findOrFail($id);

        $user = auth()->user();
        if ($user->hasRole('karyawan')) {
            $emp = $user->getEmployeeRecord();
            abort_unless($emp && $submission->employee_id === $emp->id, 403);
        }

        $this->detailSubmissionId = $submission->id;
        $this->detailSubmission = $submission;
        $this->dispatch('open-modal', id: 'overtimeDetailModal');
    }

    /**
     * Direct navigate from review modal to overtime submission row in table.
     */
    public function directToSubmission(int $id): void
    {
        $this->activeTab = 'submissions';
        $this->statusFilter = 'all';
        $this->search = '';
        $this->startDateFilter = null;
        $this->endDateFilter = null;

        $user = auth()->user();
        $isKaryawan = $user && $user->hasRole('karyawan');
        if (! $isKaryawan) {
            $this->employeeFilter = null;
        }
        $effectiveEmployeeId = $isKaryawan ? $user->getEmployeeRecord()?->id : null;

        $allIds = OvertimeSubmission::query()
            ->when($effectiveEmployeeId, fn ($q) => $q->where('employee_id', $effectiveEmployeeId))
            ->latest('date')
            ->latest('id')
            ->pluck('id')
            ->toArray();

        $pos = array_search($id, $allIds);
        if ($pos !== false) {
            $page = (int) ceil(($pos + 1) / $this->perPage);
            $this->setPage($page);
        } else {
            $this->resetPage();
        }

        $this->dispatch('scroll-to-submission-row', id: $id);
    }

    /**
     * Employee can re-submit a rejected overtime by copying old data into form.
     */
    public function reSubmit(int $id): void
    {
        $submission = OvertimeSubmission::findOrFail($id);
        $user = auth()->user();

        if ($user->hasRole('karyawan')) {
            $emp = $user->getEmployeeRecord();
            abort_unless($emp && $submission->employee_id === $emp->id, 403);
        }

        $this->form->resetForm();
        $this->form->employee_id = $submission->employee_id;
        $this->form->date = $submission->date->format('Y-m-d');
        $this->form->start_time = Carbon::parse($submission->start_time)->format('H:i');
        $this->form->end_time = Carbon::parse($submission->end_time)->format('H:i');
        $this->form->reason = $submission->reason;

        $this->dispatch('open-modal', id: 'overtimeSubmissionModal');
    }

    /**
     * Open create weekly period modal.
     */
    public function openCreatePeriodModal(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $start = Carbon::parse($this->period_start_date);
        $this->period_name = sprintf('Lembur Minggu %s', $start->format('W'));
        $this->period_notes = '';

        $this->dispatch('open-modal', id: 'overtimePeriodModal');
    }

    /**
     * Store weekly period.
     */
    public function storePeriod(OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $this->validate([
            'period_start_date' => ['required', 'date'],
            'period_end_date' => ['required', 'date', 'after_or_equal:period_start_date'],
            'period_name' => ['required', 'string', 'max:150'],
            'period_notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'period_start_date' => 'Tanggal Mulai Periode',
            'period_end_date' => 'Tanggal Akhir Periode',
            'period_name' => 'Nama Periode',
            'period_notes' => 'Catatan Periode',
        ]);

        try {
            $period = $service->createPeriod([
                'start_date' => $this->period_start_date,
                'end_date' => $this->period_end_date,
                'name' => $this->period_name,
                'notes' => $this->period_notes,
            ], auth()->user());

            $this->dispatch('close-modal', id: 'overtimePeriodModal');

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Periode Mingguan Dibuat',
                'message' => "Periode lembur {$period->reference_number} berhasil dibuat dengan total {$period->employee_count} karyawan.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Membuat Periode',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark weekly period as paid.
     */
    public function markPeriodPaid(int $id, OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        try {
            $period = OvertimePeriod::findOrFail($id);
            $service->markPeriodAsPaid($period, auth()->user());

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Pembayaran Selesai',
                'message' => "Periode lembur {$period->reference_number} telah ditandai lunas dibayar.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memproses',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open attendance & meal allowance modal for a period.
     */
    public function openAttendanceModal(int $periodId): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $period = OvertimePeriod::with(['attendances.employee'])->findOrFail($periodId);
        $this->managingPeriodId = $period->id;
        $this->managingPeriod = $period;

        // Ensure all active employees (and any with overtime in this period) exist in attendance
        $linkedEmpIds = $period->submissions()->pluck('employee_id')->unique()->all();
        $targetEmployees = Employee::where('status', 'active')
            ->orWhereIn('id', $linkedEmpIds)
            ->orderBy('full_name')
            ->get();

        $existingEmpIds = $period->attendances->pluck('employee_id')->all();
        $defaultDaysCount = 6;
        $defaultDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $hasNew = false;

        foreach ($targetEmployees as $emp) {
            if (! in_array($emp->id, $existingEmpIds, true)) {
                $rate = $emp->getEffectiveMealAllowancePerDay();
                $att = \App\Models\OvertimePeriodAttendance::create([
                    'overtime_period_id' => $period->id,
                    'employee_id' => $emp->id,
                    'meal_allowance_type' => $emp->meal_allowance_type ?: 'standar',
                    'meal_allowance_per_day' => $rate,
                    'attended_days' => $defaultDaysCount,
                    'attendance_days' => $defaultDays,
                    'total_meal_allowance' => $defaultDaysCount * $rate,
                ]);
                $period->attendances->push($att);
                $hasNew = true;
            }
        }

        if ($hasNew) {
            $period->recalculateTotals();
            $period->load(['attendances.employee']);
        }

        $this->periodAttendances = [];
        $sorted = $period->attendances->sortBy(fn ($att) => $att->employee?->full_name ?? '');
        foreach ($sorted as $att) {
            $this->periodAttendances[$att->employee_id] = [
                'employee_name' => $att->employee?->full_name ?? 'Karyawan',
                'attended_days' => (int) $att->attended_days,
                'attendance_days' => is_array($att->attendance_days) ? $att->attendance_days : [],
                'meal_allowance_per_day' => (float) $att->meal_allowance_per_day,
                'meal_allowance_type' => $att->meal_allowance_type,
                'total_meal_allowance' => (float) $att->total_meal_allowance,
            ];
        }

        $this->dispatch('open-modal', id: 'overtimeAttendanceModal');
    }

    /**
     * Toggle a day in employee's weekly attendance (Sen, Sel, Rab, Kam, Jum, Sab).
     */
    public function toggleAttendanceDay(int $employeeId, string $day): void
    {
        if (! isset($this->periodAttendances[$employeeId])) return;

        $days = $this->periodAttendances[$employeeId]['attendance_days'];
        $day = strtolower($day);

        if (in_array($day, $days, true)) {
            $days = array_values(array_diff($days, [$day]));
        } else {
            $days[] = $day;
        }

        $this->periodAttendances[$employeeId]['attendance_days'] = $days;
        $this->periodAttendances[$employeeId]['attended_days'] = count($days);
        $this->recalcEmployeeMealTotal($employeeId);
    }

    /**
     * Directly update numeric attended days count (Opsi A) syncing with checklist (Opsi B).
     */
    public function updateAttendedDaysCount(int $employeeId, mixed $count): void
    {
        if (! isset($this->periodAttendances[$employeeId])) return;

        $count = max(0, min(6, (int) $count));
        $allDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $selectedDays = array_slice($allDays, 0, $count);

        $this->periodAttendances[$employeeId]['attended_days'] = $count;
        $this->periodAttendances[$employeeId]['attendance_days'] = $selectedDays;
        $this->recalcEmployeeMealTotal($employeeId);
    }

    /**
     * 1-click shortcut: set all 6 days present.
     */
    public function setAllDaysPresent(int $employeeId): void
    {
        $this->updateAttendedDaysCount($employeeId, 6);
    }

    /**
     * Update employee's meal allowance per day rate inside period.
     */
    public function updateMealAllowanceRate(int $employeeId, mixed $rate): void
    {
        if (! isset($this->periodAttendances[$employeeId])) return;

        $this->periodAttendances[$employeeId]['meal_allowance_per_day'] = max(0, (float) $rate);
        $this->recalcEmployeeMealTotal($employeeId);
    }

    protected function recalcEmployeeMealTotal(int $employeeId): void
    {
        $days = (int) $this->periodAttendances[$employeeId]['attended_days'];
        $rate = (float) $this->periodAttendances[$employeeId]['meal_allowance_per_day'];
        $this->periodAttendances[$employeeId]['total_meal_allowance'] = round($days * $rate, 2);
    }

    /**
     * Save weekly attendances and recalculate period totals.
     */
    public function savePeriodAttendances(OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $period = OvertimePeriod::findOrFail($this->managingPeriodId);

        foreach ($this->periodAttendances as $employeeId => $data) {
            $service->updatePeriodAttendance($period, (int) $employeeId, [
                'attended_days' => $data['attended_days'],
                'attendance_days' => $data['attendance_days'],
                'meal_allowance_per_day' => $data['meal_allowance_per_day'],
                'meal_allowance_type' => $data['meal_allowance_type'],
            ], auth()->user());
        }

        $this->dispatch('close-modal', id: 'overtimeAttendanceModal');
        $this->dispatch('swal:alert', [
            'type' => 'success',
            'title' => 'Kehadiran Disimpan',
            'message' => 'Rincian hari masuk dan uang makan mingguan berhasil diperbarui.',
        ]);
    }

    public function render(OvertimeService $service): View
    {
        $user = auth()->user();
        $isKaryawan = $user && $user->hasRole('karyawan');
        $effectiveEmployeeId = $isKaryawan ? ($user->getEmployeeRecord()?->id ?? -1) : $this->employeeFilter;

        // Submissions query
        $submissions = $service->getPaginatedSubmissions(
            search: $this->search,
            status: $this->statusFilter,
            employeeId: $effectiveEmployeeId,
            startDate: $this->startDateFilter,
            endDate: $this->endDateFilter,
            perPage: $this->perPage
        );

        // Periods query (only executed when active tab is periods and user is not restricted karyawan)
        $periods = (! $isKaryawan && $this->activeTab === 'periods')
            ? OvertimePeriod::query()
                ->with(['creator', 'payer'])
                ->withCount('submissions')
                ->latest('start_date')
                ->paginate($this->perPage, ['*'], 'periodsPage')
            : (new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage, 1, ['pageName' => 'periodsPage']));

        // Statistics: Consolidated single query with conditional aggregation (1 query instead of 6 separate queries)
        $currentMonthStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $currentMonthEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        $statsRow = OvertimeSubmission::query()
            ->when($isKaryawan, fn ($q) => $q->where('employee_id', $effectiveEmployeeId))
            ->selectRaw("
                COUNT(CASE WHEN date BETWEEN ? AND ? THEN 1 END) as total_this_month,
                COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN duration_hours ELSE 0 END), 0) as total_hours_month,
                COUNT(CASE WHEN status = ? THEN 1 END) as pending_admin,
                COUNT(CASE WHEN status = ? THEN 1 END) as pending_direktur,
                COUNT(CASE WHEN status = ? AND date BETWEEN ? AND ? THEN 1 END) as approved_this_month,
                COALESCE(SUM(CASE WHEN status = ? AND date BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) as total_amount_approved
            ", [
                $currentMonthStart, $currentMonthEnd,
                $currentMonthStart, $currentMonthEnd,
                OvertimeStatus::PENDING_ADMIN->value,
                OvertimeStatus::PENDING_DIREKTUR->value,
                OvertimeStatus::APPROVED->value, $currentMonthStart, $currentMonthEnd,
                OvertimeStatus::APPROVED->value, $currentMonthStart, $currentMonthEnd,
            ])
            ->first();

        $totalAmountApproved = (float) ($statsRow?->total_amount_approved ?? 0);

        $stats = [
            'total_this_month' => (int) ($statsRow?->total_this_month ?? 0),
            'total_hours_month' => round((float) ($statsRow?->total_hours_month ?? 0), 1),
            'pending_admin' => (int) ($statsRow?->pending_admin ?? 0),
            'pending_direktur' => (int) ($statsRow?->pending_direktur ?? 0),
            'approved_this_month' => (int) ($statsRow?->approved_this_month ?? 0),
            'total_amount_approved' => $totalAmountApproved,
            'formatted_approved_amount' => 'Rp ' . number_format($totalAmountApproved, 0, ',', '.'),
        ];

        // Eligible employees for admin selection (either has hourly rate or daily rate)
        $eligibleEmployees = $user->hasRole('admin')
            ? Employee::query()
                ->with('department:id,name')
                ->where(function ($q) {
                    $q->where('overtime_rate_per_hour', '>', 0)
                        ->orWhere('overtime_rate_per_day', '>', 0);
                })
                ->where('status', 'active')
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'department_id', 'overtime_rate_per_hour', 'overtime_rate_per_day'])
            : collect();

        // Count approved submissions ready for period
        $pendingPeriodCount = OvertimeSubmission::query()
            ->where('status', OvertimeStatus::APPROVED)
            ->whereNull('overtime_period_id')
            ->whereBetween('date', [
                Carbon::parse($this->period_start_date)->format('Y-m-d'),
                Carbon::parse($this->period_end_date)->format('Y-m-d'),
            ])
            ->count();

        $chartData = $service->getOvertimeChartData($isKaryawan ? $effectiveEmployeeId : null);

        // Submissions for Review Modals (Optimized: only query when pending count > 0 with targeted column eager loading)
        $pendingAdminSubmissions = $stats['pending_admin'] > 0
            ? OvertimeSubmission::query()
                ->with(['employee.department:id,name', 'employee.position:id,name', 'submitter:id,name'])
                ->where('status', OvertimeStatus::PENDING_ADMIN)
                ->when($isKaryawan && $effectiveEmployeeId, fn ($q) => $q->where('employee_id', $effectiveEmployeeId))
                ->latest('date')->latest('id')->take(50)->get()
            : collect();

        $pendingDirectorSubmissions = $stats['pending_direktur'] > 0
            ? OvertimeSubmission::query()
                ->with(['employee.department:id,name', 'employee.position:id,name', 'submitter:id,name', 'verifiedByAdmin:id,name'])
                ->where('status', OvertimeStatus::PENDING_DIREKTUR)
                ->when($isKaryawan && $effectiveEmployeeId, fn ($q) => $q->where('employee_id', $effectiveEmployeeId))
                ->latest('date')->latest('id')->take(50)->get()
            : collect();

        return view('livewire.overtimes.index', [
            'submissions' => $submissions,
            'periods' => $periods,
            'stats' => $stats,
            'chartData' => $chartData,
            'eligibleEmployees' => $eligibleEmployees,
            'pendingPeriodCount' => $pendingPeriodCount,
            'isKaryawan' => $isKaryawan,
            'pendingAdminSubmissions' => $pendingAdminSubmissions,
            'pendingDirectorSubmissions' => $pendingDirectorSubmissions,
        ]);
    }
}
