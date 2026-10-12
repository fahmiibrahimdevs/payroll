<?php

namespace App\Livewire\Payrolls;

use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
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
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $months[$this->newMonth] ?? (string) $this->newMonth;
        $this->newName = "Penggajian Periode {$monthName} {$this->newYear}";
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $months[$this->newMonth] ?? (string) $this->newMonth;

        $exists = PayrollPeriod::query()
            ->where('month', $this->newMonth)
            ->where('year', $this->newYear)
            ->exists();

        if ($exists) {
            $this->addError('newMonth', "Periode penggajian untuk bulan {$monthName} {$this->newYear} sudah ada.");

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

        $statistics = $service->getStatistics($this->selectedYear);
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
