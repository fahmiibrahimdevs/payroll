<?php

namespace App\Livewire\Concerns;

use App\Services\SimpleCrudService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Throwable;

/**
 * Trait untuk komponen Livewire CRUD master sederhana berbasis modal + SweetAlert.
 *
 * Menampung aksi mount, create, edit, store, update, confirmDelete, dan delete
 * yang identik antar komponen. Komponen pemakai tetap terpisah dan hanya
 * mendefinisikan properti publik (form, search, perPage, isEditMode, ID editing),
 * hook konfigurasi entitas di bawah, wrapper aksi delete ber-atribut #[On(...)],
 * serta method render masing-masing.
 */
trait HasSimpleCrudModal
{
    /**
     * ID modal Livewire (mis. 'departmentModal').
     */
    abstract protected function simpleCrudModalId(): string;

    /**
     * Label entitas dalam pesan SweetAlert, huruf kecil (mis. 'department').
     */
    abstract protected function simpleCrudEntityLabel(): string;

    /**
     * Nama browser event konfirmasi delete (mis. 'delete-department').
     */
    abstract protected function simpleCrudDeleteAction(): string;

    /**
     * Nama class model yang dikelola komponen (mis. Department::class).
     */
    abstract protected function simpleCrudModelClass(): string;

    /**
     * Nama properti publik penanda ID yang sedang diedit
     * (mis. 'editingDepartmentId').
     */
    abstract protected function simpleCrudEditingIdProperty(): string;

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
     * Open modal for creating a new record.
     */
    public function create(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $this->isEditMode = false;
        $this->{$this->simpleCrudEditingIdProperty()} = null;
        $this->form->resetForm();
        $this->dispatch('open-modal', [
            'id' => $this->simpleCrudModalId(),
        ]);
    }

    /**
     * Store a newly created record.
     */
    protected function storeRecord(SimpleCrudService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $label = $this->simpleCrudEntityLabel();

        try {
            $this->form->store($service);
            $this->dispatch('close-modal', id: $this->simpleCrudModalId());
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => ucfirst($label).' baru berhasil ditambahkan!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan!',
                'message' => 'Terjadi kesalahan sistem saat membuat '.$label.'.',
            ]);
        }
    }

    /**
     * Open modal and populate form for editing an existing record.
     */
    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $label = $this->simpleCrudEntityLabel();
        $modelClass = $this->simpleCrudModelClass();

        try {
            $model = $modelClass::findOrFail($id);
            $this->isEditMode = true;
            $this->{$this->simpleCrudEditingIdProperty()} = $id;
            $this->form->setEntity($model);
            $this->dispatch('open-modal', [
                'id' => $this->simpleCrudModalId(),
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data '.$label.' tidak ditemukan.',
            ]);
        }
    }

    /**
     * Update an existing record.
     */
    protected function updateRecord(SimpleCrudService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $label = $this->simpleCrudEntityLabel();

        try {
            $this->form->update($service);
            $this->isEditMode = false;
            $this->{$this->simpleCrudEditingIdProperty()} = null;
            $this->dispatch('close-modal', id: $this->simpleCrudModalId());
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => ucfirst($label).' berhasil diperbarui!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui '.$label.'.',
            ]);
        }
    }

    /**
     * Trigger SweetAlert2 delete confirmation dialog.
     */
    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $label = $this->simpleCrudEntityLabel();
        $modelClass = $this->simpleCrudModelClass();
        $model = $modelClass::find($id);

        if (! $model) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => ucfirst($label).' tidak ditemukan.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $id,
            'title' => $model->name,
            'action' => $this->simpleCrudDeleteAction(),
        ]);
    }

    /**
     * Delete a record after confirmation (dipanggil wrapper #[On] di komponen).
     */
    protected function deleteRecord(int $id, SimpleCrudService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $label = $this->simpleCrudEntityLabel();
        $modelClass = $this->simpleCrudModelClass();

        try {
            $model = $modelClass::findOrFail($id);
            $service->delete($model);

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => ucfirst($label).' berhasil dihapus!',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus!',
                'message' => 'Terjadi kesalahan sistem saat menghapus '.$label.'.',
            ]);
        }
    }
}
