<?php

namespace App\Livewire\SalaryComponents;

use App\Enums\SalaryComponentCategory;
use App\Enums\SalaryComponentType;
use App\Livewire\Forms\SalaryComponentForm;
use App\Models\SalaryComponent;
use App\Services\SalaryComponentService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Kelola Komponen Gaji')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public SalaryComponentForm $form;

    public string $search = '';

    public string $filterCategory = '';

    public string $filterType = '';

    public string $filterStatus = '';

    public int $perPage = 20;

    public bool $isEditMode = false;

    public ?int $editingSalaryComponentId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
    }

    /**
     * Reset pagination when search or filters change.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Reset all filters.
     */
    public function resetFilters(): void
    {
        $this->filterCategory = '';
        $this->filterType = '';
        $this->filterStatus = '';
        $this->search = '';
        $this->resetPage();
        $this->dispatch('reset-salary-component-filters');
    }

    /**
     * Open modal for creating a new salary component.
     */
    public function create(): void
    {
        $this->isEditMode = false;
        $this->editingSalaryComponentId = null;
        $this->form->resetForm();
        $this->dispatch('open-modal', [
            'id' => 'salaryComponentModal',
        ]);
    }

    /**
     * Store a newly created salary component.
     */
    public function store(SalaryComponentService $service): void
    {
        try {
            $this->form->store($service);
            $this->dispatch('close-modal', id: 'salaryComponentModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Komponen gaji baru berhasil ditambahkan!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan!',
                'message' => 'Terjadi kesalahan sistem saat membuat komponen gaji.',
            ]);
        }
    }

    /**
     * Open modal and populate form for editing an existing salary component.
     */
    public function edit(int $id): void
    {
        try {
            $component = SalaryComponent::findOrFail($id);
            $this->isEditMode = true;
            $this->editingSalaryComponentId = $id;
            $this->form->setSalaryComponent($component);
            $this->dispatch('open-modal', [
                'id' => 'salaryComponentModal',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data komponen gaji tidak ditemukan.',
            ]);
        }
    }

    /**
     * Update an existing salary component.
     */
    public function update(SalaryComponentService $service): void
    {
        try {
            $this->form->update($service);
            $this->isEditMode = false;
            $this->editingSalaryComponentId = null;
            $this->dispatch('close-modal', id: 'salaryComponentModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Komponen gaji berhasil diperbarui!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui komponen gaji.',
            ]);
        }
    }

    /**
     * Toggle the active status of a salary component.
     */
    public function toggleStatus(int $id, SalaryComponentService $service): void
    {
        try {
            $component = SalaryComponent::findOrFail($id);

            if ($component->is_system && $component->code === 'GAJI_POKOK' && $component->is_active) {
                $this->dispatch('swal:alert', [
                    'type' => 'warning',
                    'title' => 'Tidak Dapat Dinonaktifkan',
                    'message' => 'Komponen Gaji Pokok merupakan upah pokok sistem dan harus selalu aktif.',
                ]);

                return;
            }

            $newStatus = ! $component->is_active;
            $service->updateSalaryComponent($component, ['is_active' => $newStatus]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mengubah Status!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui status komponen gaji.',
            ]);
        }
    }

    /**
     * Toggle the taxable status of a salary component.
     */
    public function toggleTaxable(int $id, SalaryComponentService $service): void
    {
        try {
            $component = SalaryComponent::findOrFail($id);

            if ($component->is_system && $component->code === 'GAJI_POKOK' && $component->is_taxable) {
                $this->dispatch('swal:alert', [
                    'type' => 'warning',
                    'title' => 'Tidak Dapat Diubah',
                    'message' => 'Komponen Gaji Pokok wajib dikenakan pajak (PPh 21).',
                ]);

                return;
            }

            $newTaxable = ! $component->is_taxable;
            $service->updateSalaryComponent($component, ['is_taxable' => $newTaxable]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mengubah Status Pajak!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui status pajak komponen gaji.',
            ]);
        }
    }

    /**
     * Trigger SweetAlert2 delete confirmation dialog.
     */
    public function confirmDelete(int $id): void
    {
        $component = SalaryComponent::find($id);

        if (! $component) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Komponen gaji tidak ditemukan.',
            ]);

            return;
        }

        if ($component->is_system) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'message' => 'Komponen bawaan sistem tidak boleh dihapus.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $id,
            'title' => $component->name,
            'action' => 'delete-salary-component',
        ]);
    }

    /**
     * Delete a salary component after confirmation.
     */
    #[On('delete-salary-component')]
    public function delete(int $id, SalaryComponentService $service): void
    {
        try {
            $component = SalaryComponent::findOrFail($id);
            $service->deleteSalaryComponent($component);

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Komponen gaji berhasil dihapus!',
            ]);
        } catch (DomainException $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus!',
                'message' => 'Terjadi kesalahan sistem saat menghapus komponen gaji.',
            ]);
        }
    }

    /**
     * Render the Livewire component view.
     */
    public function render(SalaryComponentService $service): View
    {
        $isActive = match ($this->filterStatus) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $salaryComponents = $service->getPaginatedSalaryComponents(
            search: $this->search,
            category: ! empty($this->filterCategory) ? $this->filterCategory : null,
            type: ! empty($this->filterType) ? $this->filterType : null,
            isActive: $isActive,
            perPage: $this->perPage,
            sortField: 'sort_order',
            sortDir: 'asc'
        );

        return view('livewire.salary-components.index', [
            'salaryComponents' => $salaryComponents,
            'categories' => SalaryComponentCategory::cases(),
            'types' => SalaryComponentType::cases(),
        ]);
    }
}
