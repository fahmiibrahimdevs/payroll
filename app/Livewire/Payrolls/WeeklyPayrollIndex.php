<?php

namespace App\Livewire\Payrolls;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\OvertimePeriodStatus;
use App\Enums\OvertimeStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimePeriod;
use App\Models\OvertimeSubmission;
use App\Services\AttendanceService;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gaji & Slip Mingguan - Larashiz HRM')]
class WeeklyPayrollIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public int $perPage = 10;

    // Form Buat Periode Baru
    public string $period_start_date = '';

    public string $period_end_date = '';

    public string $period_name = '';

    public string $period_notes = '';

    public function mount(): void
    {
        $this->initDefaultFridayCycle();
    }

    /**
     * Inisialisasi siklus default: Jum'at s/d Kamis (7 Hari - Bebas Overlap).
     */
    public function initDefaultFridayCycle(): void
    {
        $latestPeriod = OvertimePeriod::orderByDesc('end_date')->first();

        if ($latestPeriod && $latestPeriod->end_date) {
            $lastEnd = Carbon::parse($latestPeriod->end_date);
            $start = $lastEnd->copy()->addDay();
            if (! $start->isFriday()) {
                $start = $start->next(Carbon::FRIDAY);
            }
        } else {
            $now = Carbon::now();
            $start = $now->isFriday() ? $now->copy() : $now->copy()->previous(Carbon::FRIDAY);
        }

        $end = $start->copy()->addDays(6); // Kamis

        $this->period_start_date = $start->format('Y-m-d');
        $this->period_end_date = $end->format('Y-m-d');
        $this->period_name = sprintf('Gaji Mingguan W%s', $start->format('W'));
    }

    /**
     * Otomatis sesuaikan tanggal akhir (Kamis) ketika tanggal mulai (Jum'at) diubah.
     */
    public function updatedPeriodStartDate(string $val): void
    {
        if (! empty($val)) {
            try {
                $picked = Carbon::parse($val);
                // Smart Friday Snap: jika hari yang dipilih bukan Jum'at, otomatis sesuaikan ke hari Jum'at terdekat
                $start = $picked->isFriday() ? $picked->copy() : $picked->copy()->previous(Carbon::FRIDAY);
                $end = $start->copy()->addDays(6); // 7 hari: Jum'at s/d Kamis

                $this->period_start_date = $start->format('Y-m-d');
                $this->period_end_date = $end->format('Y-m-d');
                $this->period_name = sprintf('Gaji Mingguan W%s', $start->format('W'));
            } catch (\Throwable $e) {
                // Ignore parse errors while typing
            }
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreatePeriodModal(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->resetValidation();
        $this->initDefaultFridayCycle();
        $this->period_notes = '';

        $this->dispatch('open-modal', id: 'weeklyPeriodModal');
    }

    /**
     * Alias for openCreatePeriodModal() to support openCreateModal callers.
     */
    public function openCreateModal(): void
    {
        $this->openCreatePeriodModal();
    }

    public function getEligibleCandidatesCountProperty(): int
    {
        if (empty($this->period_start_date) || empty($this->period_end_date)) {
            return 0;
        }

        $startDate = Carbon::parse($this->period_start_date)->startOfDay();
        $endDate = Carbon::parse($this->period_end_date)->endOfDay();

        $approvedSubmissions = OvertimeSubmission::query()
            ->where('status', OvertimeStatus::APPROVED)
            ->whereNull('overtime_period_id')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->pluck('employee_id')
            ->unique()
            ->all();

        $attendances = Attendance::query()
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('status', ['hadir', 'terlambat', 'pulang_cepat'])
            ->pluck('employee_id')
            ->unique()
            ->all();

        $hasAttendanceLog = ! empty($attendances);

        return Employee::query()
            ->where('status', EmployeeStatus::ACTIVE)
            ->where(function ($q) use ($approvedSubmissions, $attendances, $hasAttendanceLog) {
                $q->where('employment_status', EmploymentStatus::HARIAN)
                  ->orWhereIn('id', $approvedSubmissions);
                if ($hasAttendanceLog) {
                    $q->orWhereIn('id', $attendances);
                }
            })
            ->count();
    }

    public function storePeriod(OvertimeService $service, AttendanceService $attendanceService): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->validate([
            'period_start_date' => ['required', 'date'],
            'period_end_date' => ['required', 'date', 'after_or_equal:period_start_date'],
            'period_name' => ['required', 'string', 'max:150'],
            'period_notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'period_start_date' => 'Tanggal Mulai Periode (Jum\'at)',
            'period_end_date' => 'Tanggal Akhir Periode (Kamis)',
            'period_name' => 'Nama Periode Gaji Mingguan',
            'period_notes' => 'Catatan Periode',
        ]);

        $startDate = Carbon::parse($this->period_start_date);
        $endDate = Carbon::parse($this->period_end_date);

        if (! $startDate->isFriday()) {
            $this->addError('period_start_date', 'Tanggal mulai harus jatuh pada hari Jum\'at.');
            return;
        }

        if (! $endDate->isThursday() || $startDate->copy()->addDays(6)->format('Y-m-d') !== $endDate->format('Y-m-d')) {
            $this->addError('period_end_date', 'Tanggal akhir harus jatuh pada hari Kamis (persis 7 hari siklus Jum\'at s/d Kamis).');
            return;
        }

        if ($this->eligibleCandidatesCount === 0) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Karyawan Tidak Ditemukan',
                'text' => 'Tidak dapat membuat periode mingguan: Tidak ada data karyawan harian lepas, catatan presensi hadir, atau pengajuan lembur yang disetujui pada siklus ini (minimal 1 orang).',
            ]);

            return;
        }

        try {
            $period = $service->createPeriod([
                'start_date' => $this->period_start_date,
                'end_date' => $this->period_end_date,
                'name' => $this->period_name,
                'notes' => $this->period_notes,
            ], auth()->user());

            // Otomatis tarik data absensi jika log mesin sudah tersedia untuk rentang tersebut
            try {
                $attendanceService->syncToWeeklyOvertimePeriod($period, auth()->user());
            } catch (\Throwable $syncErr) {
                Log::warning('Sinkronisasi otomatis saat buat periode gagal/parsial: ' . $syncErr->getMessage());
            }

            $this->dispatch('close-modal', id: 'weeklyPeriodModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Periode Gaji Mingguan Dibuat',
                'text' => "Periode {$period->name} berhasil dibuat dengan siklus Jum'at s/d Kamis.",
            ]);

            $this->initDefaultFridayCycle();
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Membuat Periode',
                'text' => $e->getMessage(),
            ]);
        }
    }

    public function confirmMarkPaid(int $periodId): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $period = OvertimePeriod::find($periodId);

        if (! $period) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'text' => 'Data periode tidak ditemukan.',
            ]);

            return;
        }

        if ($period->status !== OvertimePeriodStatus::DRAFT) {
            $this->dispatch('swal:alert', [
                'type' => 'info',
                'title' => 'Sudah Dibayar',
                'text' => 'Periode ini sudah berstatus Selesai Dibayar.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm', [
            'id' => $period->id,
            'title' => 'Tandai Selesai Dibayar?',
            'text' => "Apakah Anda yakin ingin menandai seluruh gaji & slip mingguan {$period->name} telah selesai dibayarkan?",
            'icon' => 'question',
            'confirmButtonColor' => '#28a745',
            'confirmButtonText' => '<i class="fas fa-check-double mr-1"></i> Ya, Tandai Dibayar!',
            'cancelButtonText' => 'Batal',
            'action' => 'mark-weekly-period-paid',
        ]);
    }

    #[On('mark-weekly-period-paid')]
    public function markPeriodPaid(int $id, OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        try {
            $period = OvertimePeriod::findOrFail($id);
            $service->markPeriodAsPaid($period, auth()->user());

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Periode Ditandai Selesai Dibayar',
                'text' => "Seluruh upah mingguan untuk periode {$period->reference_number} telah ditandai lunas dibayar.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui Status',
                'text' => $e->getMessage(),
            ]);
        }
    }

    public function confirmDelete(int $periodId): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $period = OvertimePeriod::find($periodId);

        if (! $period) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'text' => 'Data periode tidak ditemukan.',
            ]);

            return;
        }

        if ($period->status !== OvertimePeriodStatus::DRAFT) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'text' => 'Hanya periode berstatus Draf (Belum Dibayar) yang dapat dihapus.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $period->id,
            'title' => $period->name,
            'text' => "Apakah Anda yakin ingin menghapus {$period->name}? Seluruh data presensi mingguan pada periode ini akan dibatalkan.",
            'action' => 'delete-weekly-period',
        ]);
    }

    #[On('delete-weekly-period')]
    public function deletePeriod(int $id, OvertimeService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403, 'Hanya Admin dan Direktur yang dapat menghapus periode gaji mingguan.');

        try {
            $period = OvertimePeriod::findOrFail($id);
            $service->deletePeriod($period, auth()->user());

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Periode Mingguan Dihapus',
                'text' => "Periode {$period->reference_number} ({$period->name}) berhasil dihapus.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus Periode',
                'text' => $e->getMessage(),
            ]);
        }
    }

    public function render(): View
    {
        $query = OvertimePeriod::query()
            ->withCount([
                'attendances',
                'attendances as paid_attendances_count' => fn ($q) => $q->where('payment_status', 'paid'),
            ]);

        if (! empty($this->search)) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('reference_number', 'like', "%{$term}%");
            });
        }

        $periods = $query->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        // Agregat Statistik Gaji Mingguan
        $allPeriods = OvertimePeriod::query()->get();
        $totalGrandTotal = (float) $allPeriods->sum('grand_total');
        $totalDailyWage = (float) $allPeriods->sum('total_daily_wage');
        $totalMealAllowance = (float) $allPeriods->sum('total_meal_allowance');
        $totalOvertimePay = (float) $allPeriods->sum('total_overtime_pay');
        $periodCount = $allPeriods->count();

        $stats = [
            'total_periods' => $periodCount,
            'total_grand_total' => $totalGrandTotal,
            'total_daily_wage' => $totalDailyWage,
            'total_meal_allowance' => $totalMealAllowance,
            'total_overtime_pay' => $totalOvertimePay,
            'draft_count' => $allPeriods->where('status', OvertimePeriodStatus::DRAFT)->count(),
            'paid_count' => $allPeriods->where('status', OvertimePeriodStatus::PAID)->count(),
            'avg_per_period' => $periodCount > 0 ? round($totalGrandTotal / $periodCount, 2) : 0,
        ];

        // Format data donat grafik 3 porsi biaya mingguan
        $hasChartData = $totalGrandTotal > 0;
        $chartValues = $hasChartData ? [
            $totalDailyWage,
            $totalMealAllowance,
            $totalOvertimePay,
        ] : [1];

        $chartLabels = $hasChartData ? [
            'Upah Harian',
            'Uang Makan',
            'Upah Lembur',
        ] : ['Belum ada biaya'];

        $chartColors = $hasChartData ? [
            '#10b981', // Upah Harian (Emerald)
            '#f59e0b', // Uang Makan (Amber)
            '#0b52aa', // Upah Lembur (Primary Blue)
        ] : ['#e2e8f0'];

        $pctDaily = $totalGrandTotal > 0 ? round(($totalDailyWage / $totalGrandTotal) * 100, 1) : 0;
        $pctMeal = $totalGrandTotal > 0 ? round(($totalMealAllowance / $totalGrandTotal) * 100, 1) : 0;
        $pctOt = $totalGrandTotal > 0 ? round(($totalOvertimePay / $totalGrandTotal) * 100, 1) : 0;

        return view('livewire.payrolls.weekly-payroll-index', [
            'periods' => $periods,
            'stats' => $stats,
            'hasChartData' => $hasChartData,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'chartColors' => $chartColors,
            'pctDaily' => $pctDaily,
            'pctMeal' => $pctMeal,
            'pctOt' => $pctOt,
            'periodStatuses' => OvertimePeriodStatus::cases(),
            'isKaryawan' => auth()->user()?->hasRole('karyawan') && ! auth()->user()?->hasRole(['admin', 'direktur', 'developer']),
        ]);
    }
}
