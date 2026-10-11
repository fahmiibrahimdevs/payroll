<?php

namespace App\Livewire\Payrolls;

use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
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
        $this->newMonth = (int) $now->month;
        $this->newYear = (int) $now->year;
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

    public function deletePeriod(int $periodId, PayrollService $service): void
    {
        try {
            $period = PayrollPeriod::findOrFail($periodId);
            $service->deletePeriod($period, auth()->user());

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Periode penggajian berhasil dihapus.',
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

        $statistics = $service->getStatistics();

        return view('livewire.payrolls.index', [
            'periods' => $periods,
            'statistics' => $statistics,
            'statuses' => PayrollPeriodStatus::cases(),
            'availableYears' => range((int) Carbon::now()->year + 1, (int) Carbon::now()->year - 3),
        ]);
    }
}
