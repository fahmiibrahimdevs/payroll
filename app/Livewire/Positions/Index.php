<?php

namespace App\Livewire\Positions;

use App\Livewire\Forms\PositionForm;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Kelola Jabatan')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public PositionForm $form;

    public string $search = '';

    public int $perPage = 10;

    public bool $isEditMode = false;

    public ?int $editingPositionId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
    }

    /**
     * Reset pagination when search input changes.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Open modal for creating a new position.
     */
    public function create(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $this->isEditMode = false;
        $this->editingPositionId = null;
        $this->form->resetForm();
        $this->dispatch('open-modal', [
            'id' => 'positionModal',
        ]);
    }

    /**
     * Store a newly created position.
     */
    public function store(PositionService $positionService): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        try {
            $this->form->store($positionService);
            $this->dispatch('close-modal', id: 'positionModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Jabatan baru berhasil ditambahkan!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan!',
                'message' => 'Terjadi kesalahan sistem saat membuat jabatan.',
            ]);
        }
    }

    /**
     * Open modal and populate form for editing an existing position.
     */
    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        try {
            $position = Position::findOrFail($id);
            $this->isEditMode = true;
            $this->editingPositionId = $id;
            $this->form->setPosition($position);
            $this->dispatch('open-modal', [
                'id' => 'positionModal',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data jabatan tidak ditemukan.',
            ]);
        }
    }

    /**
     * Update an existing position.
     */
    public function update(PositionService $positionService): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        try {
            $this->form->update($positionService);
            $this->isEditMode = false;
            $this->editingPositionId = null;
            $this->dispatch('close-modal', id: 'positionModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Jabatan berhasil diperbarui!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui jabatan.',
            ]);
        }
    }

    /**
     * Trigger SweetAlert2 delete confirmation dialog.
     */
    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $position = Position::find($id);

        if (! $position) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Jabatan tidak ditemukan.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $id,
            'title' => $position->name,
            'action' => 'delete-position',
        ]);
    }

    /**
     * Delete a position after confirmation.
     */
    #[On('delete-position')]
    public function delete(int $id, PositionService $positionService): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        try {
            $position = Position::findOrFail($id);
            $positionService->deletePosition($position);

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Jabatan berhasil dihapus!',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus!',
                'message' => 'Terjadi kesalahan sistem saat menghapus jabatan.',
            ]);
        }
    }

    /**
     * Render the Livewire component view.
     */
    public function render(PositionService $positionService): View
    {
        $positions = $positionService->getPaginatedPositions(
            search: $this->search,
            perPage: $this->perPage
        );

        return view('livewire.positions.index', [
            'positions' => $positions,
        ]);
    }
}
