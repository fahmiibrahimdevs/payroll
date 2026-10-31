<?php

namespace App\Livewire\Payrolls;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\PayrollPeriodStatus;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use App\Support\MonthNames;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Penggajian - Larashiz HRM')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    // Filter Rekap Tahunan Property
    public int $selectedYear;

    // Create Modal Properties
    public int $newMonth;

    public int $newYear;

    public string $newName = '';

    public string $newNotes = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $now = Carbon::now();
        $this->selectedYear = (int) $now->year;
        $this->newMonth = (int) $now->month;
        $this->newYear = (int) $now->year;
        $this->updateDefaultPeriodName();
    }

    public function updatedNewMonth(): void
    {
        $this->updateDefaultPeriodName();
    }

    public function updatedNewYear(): void
    {
        $existingMonths = PayrollPeriod::where('year', $this->newYear)->pluck('month')->all();
        if (in_array($this->newMonth, $existingMonths, true)) {
            for ($m = 1; $m <= 12; $m++) {
                if (! in_array($m, $existingMonths, true)) {
                    $this->newMonth = $m;
                    break;
                }
            }
        }
        $this->updateDefaultPeriodName();
    }

    private function updateDefaultPeriodName(): void
    {
        $monthName = MonthNames::full()[$this->newMonth] ?? (string) $this->newMonth;
        $this->newName = "Penggajian Periode {$monthName} {$this->newYear}";
    }

    /** Nama bulan Indonesia (1 => 'Januari' ... 12 => 'Desember') untuk dropdown periode. */
    public function getMonthNamesProperty(): array
    {
        return MonthNames::full();
    }

    /** Statistik agregat penggajian untuk tahun terpilih. */
    public function getStatisticsProperty(): array
    {
        return app(PayrollService::class)->getStatistics($this->selectedYear);
    }

    /** Data donut chart biaya perusahaan per bulan aktif pada tahun terpilih. */
    public function getChartDataProperty(): array
    {
        $activeMonthsInYear = collect($this->statistics['twelve_months_data'])
            ->filter(fn ($tm) => $tm['cost'] > 0)
            ->values();

        $hasChartData = $activeMonthsInYear->isNotEmpty();

        $donutPalette = ['#0b52aa', '#2563eb', '#3b82f6', '#0284c7', '#0ea5e9', '#06b6d4', '#10b981', '#14b8a6', '#6366f1', '#8b5cf6', '#f59e0b', '#ec4899'];

        return [
            'has_data' => $hasChartData,
            'labels' => $hasChartData ? $activeMonthsInYear->pluck('month_name')->all() : ['Belum ada data'],
            'values' => $hasChartData ? $activeMonthsInYear->pluck('cost')->all() : [1],
            'colors' => $hasChartData
                ? ($activeMonthsInYear->count() === 1 ? ['#2563eb'] : array_slice($donutPalette, 0, $activeMonthsInYear->count()))
                : ['#e2e8f0'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getEstimatedCycleProperty(): array
    {
        $company = CompanyProfile::getProfile();

        return $company->resolvePayrollCycleDates((int) ($this->newYear ?: now()->year), (int) ($this->newMonth ?: now()->month));
    }

    public function getEligibleEmployeesCountProperty(): int
    {
        $cycle = $this->estimatedCycle;
        $startDate = $cycle['start_date']->toDateString();
        $endDate = $cycle['end_date']->toDateString();

        return Employee::query()
            ->where('status', EmployeeStatus::ACTIVE)
            ->where('employment_status', '!=', EmploymentStatus::HARIAN)
            ->employedDuring($startDate, $endDate)
            ->count();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $now = Carbon::now();
        $this->newYear = (int) $now->year;

        $existingMonths = PayrollPeriod::where('year', $this->newYear)->pluck('month')->all();

        // Pick first available month, prioritizing current month
        $candidate = (int) $now->month;
        if (in_array($candidate, $existingMonths, true)) {
            $candidate = null;
            for ($m = (int) $now->month; $m <= 12; $m++) {
                if (! in_array($m, $existingMonths, true)) {
                    $candidate = $m;
                    break;
                }
            }
            if ($candidate === null) {
                for ($m = 1; $m <= 12; $m++) {
                    if (! in_array($m, $existingMonths, true)) {
                        $candidate = $m;
                        break;
                    }
                }
            }
        }

        $this->newMonth = $candidate ?? (int) $now->month;
        $this->newNotes = '';
        $this->updateDefaultPeriodName();

        $this->dispatch('open-modal', ['id' => 'createPeriodModal']);
    }

    public function storePeriod(PayrollService $service): void
    {
        $this->validate([
            'newMonth' => 'required|integer|between:1,12',
            'newYear' => 'required|integer|min:2020|max:2099',
            'newName' => 'required|string|max:150',
            'newNotes' => 'nullable|string|max:500',
        ], [
            'newMonth.required' => 'Bulan wajib dipilih.',
            'newYear.required' => 'Tahun wajib dipilih.',
            'newName.required' => 'Nama periode wajib diisi.',
        ]);

        $monthName = MonthNames::full()[$this->newMonth] ?? (string) $this->newMonth;

        $exists = PayrollPeriod::query()
            ->where('month', $this->newMonth)
            ->where('year', $this->newYear)
            ->exists();

        if ($exists) {
            $this->addError('newMonth', "Periode penggajian untuk bulan {$monthName} {$this->newYear} sudah ada.");

            return;
        }

        if ($this->eligibleEmployeesCount === 0) {
            $this->addError('newMonth', 'Tidak dapat membuat periode: Tidak ada karyawan bulanan aktif yang terdaftar untuk periode ini (minimal 1 karyawan).');

            return;
        }

        try {
            $period = $service->createPeriod([
                'month' => $this->newMonth,
                'year' => $this->newYear,
                'name' => $this->newName,
                'notes' => $this->newNotes,
            ], auth()->user());

            $this->dispatch('close-modal', ['id' => 'createPeriodModal']);
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Periode {$period->reference_number} berhasil dibuat dengan {$period->employee_count} karyawan.",
            ]);

            $this->redirect(route('payrolls.show', $period), navigate: true);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Gagal membuat periode penggajian: '.$e->getMessage(),
            ]);
        }
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        $period = PayrollPeriod::find($id);

        if (! $period) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data periode tidak ditemukan.',
            ]);

            return;
        }

        if (! $period->status->isEditable()) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Hanya periode berstatus Draf atau Revisi yang dapat dihapus.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $id,
            'title' => "Periode {$period->period_label} ({$period->reference_number})",
            'text' => "Draf periode {$period->reference_number} dan seluruh rincian slip karyawan di dalamnya akan dihapus permanen!",
            'action' => 'delete-period',
        ]);
    }

    #[On('delete-period')]
    public function deletePeriod(int $id, PayrollService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        try {
            $period = PayrollPeriod::findOrFail($id);
            $ref = $period->reference_number;
            $service->deletePeriod($period, auth()->user());

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Periode {$ref} berhasil dihapus.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function render(PayrollService $service): View
    {
        $periods = $service->getPaginatedPeriods(
            perPage: $this->perPage,
            search: $this->search ?: null,
        );

        $statistics = $this->statistics;
        $existingMonthsForNewYear = PayrollPeriod::where('year', $this->newYear)->pluck('month')->all();

        return view('livewire.payrolls.index', [
            'periods' => $periods,
            'statistics' => $statistics,
            'selectedYear' => $this->selectedYear,
            'activePeriod' => $statistics['active_period'] ?? null,
            'statuses' => PayrollPeriodStatus::cases(),
            'availableYears' => range((int) Carbon::now()->year + 1, (int) Carbon::now()->year - 3),
            'existingMonthsForNewYear' => $existingMonthsForNewYear,
        ]);
    }
}
