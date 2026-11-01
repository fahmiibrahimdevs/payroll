<?php

namespace App\Livewire\Holidays;

use App\Livewire\Forms\HolidayForm;
use App\Models\Holiday;
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
#[Title('Hari Libur Nasional')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public HolidayForm $form;

    public string $search = '';

    public string $viewTab = 'calendar';

    public int $perPage = 10;

    public int $year = 0;

    public bool $isEditMode = false;

    public ?int $editingHolidayId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->year = (int) now()->year;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function switchViewTab(string $tab): void
    {
        if (in_array($tab, ['calendar', 'table'], true)) {
            $this->viewTab = $tab;
            $this->resetPage();
        }
    }

    /**
     * Open modal for creating a new holiday.
     */
    public function create(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        $this->isEditMode = false;
        $this->editingHolidayId = null;
        $this->form->resetForm();
        $this->form->date = $this->year.'-01-01';
        $this->dispatch('open-modal', id: 'holidayModal');
    }

    /**
     * Store a newly created holiday.
     */
    public function store(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        try {
            $this->form->store();
            $this->dispatch('close-modal', id: 'holidayModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Hari libur baru berhasil ditambahkan!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan!',
                'message' => 'Terjadi kesalahan sistem saat menyimpan hari libur.',
            ]);
        }
    }

    /**
     * Open modal and populate form for editing an existing holiday.
     */
    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        try {
            $holiday = Holiday::findOrFail($id);
            $this->isEditMode = true;
            $this->editingHolidayId = $id;
            $this->form->setHoliday($holiday);
            $this->dispatch('open-modal', id: 'holidayModal');
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data hari libur tidak ditemukan.',
            ]);
        }
    }

    /**
     * Update an existing holiday.
     */
    public function update(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        try {
            $this->form->update();
            $this->isEditMode = false;
            $this->editingHolidayId = null;
            $this->dispatch('close-modal', id: 'holidayModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Hari libur berhasil diperbarui!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui hari libur.',
            ]);
        }
    }

    /**
     * Trigger SweetAlert2 delete confirmation dialog.
     */
    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        $holiday = Holiday::find($id);

        if (! $holiday) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Hari libur tidak ditemukan.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $id,
            'title' => $holiday->name.' ('.$holiday->date->translatedFormat('d M Y').')',
            'action' => 'delete-holiday',
        ]);
    }

    /**
     * Delete a holiday after confirmation.
     */
    #[On('delete-holiday')]
    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        try {
            Holiday::findOrFail($id)->delete();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Hari libur berhasil dihapus!',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus!',
                'message' => 'Terjadi kesalahan sistem saat menghapus hari libur.',
            ]);
        }
    }

    /**
     * Render the Livewire component view.
     */
    public function render(): View
    {
        $search = trim($this->search);

        // Info libur per tanggal untuk sel kalender (dipakai popup aksi juga)
        $allHolidays = Holiday::query()
            ->when($this->year > 0, fn ($q) => $q->whereYear('date', $this->year))
            ->orderBy('date')
            ->get();

        $holidayInfo = $allHolidays->mapWithKeys(fn (Holiday $h) => [
            $h->date->toDateString() => [
                'id' => $h->id,
                'name' => $h->name,
                'label' => $h->date->locale('id')->translatedFormat('l, d F Y'),
            ],
        ])->all();

        // Saat mencari, hanya libur yang cocok yang di-highlight penuh
        $matchingDates = [];
        if ($search !== '') {
            $term = mb_strtolower($search);
            $matchingDates = $allHolidays
                ->filter(fn (Holiday $h) => str_contains(mb_strtolower($h->name), $term))
                ->map(fn (Holiday $h) => $h->date->toDateString())
                ->values()
                ->all();
        }

        // Tampilan tabel memakai paginasi standar
        $holidays = Holiday::query()
            ->when($this->year > 0, fn ($q) => $q->whereYear('date', $this->year))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('date')
            ->paginate($this->perPage);

        return view('livewire.holidays.index', [
            'holidayInfo' => $holidayInfo,
            'matchingDates' => $matchingDates,
            'holidays' => $holidays,
            'yearOptions' => $this->yearOptions(),
        ]);
    }

    /**
     * Daftar tahun yang punya data libur, plus tahun berjalan ± 1.
     *
     * @return array<int, int>
     */
    private function yearOptions(): array
    {
        // Ekstrak tahun di PHP - tabel libur kecil, jauh lebih portable
        // daripada fungsi YEAR()/strftime() yang spesifik driver.
        $fromDb = Holiday::query()
            ->pluck('date')
            ->map(fn ($d) => (int) substr($d instanceof Carbon ? $d->toDateString() : (string) $d, 0, 4));

        return $fromDb
            ->push((int) now()->year, (int) now()->year + 1)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
