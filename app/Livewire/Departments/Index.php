<?php

namespace App\Livewire\Departments;

use App\Livewire\Forms\DepartmentForm;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Kelola Department')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public DepartmentForm $form;

    public string $search = '';

    public int $perPage = 10;

    public bool $isEditMode = false;

    public ?int $editingDepartmentId = null;

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
     * Open modal for creating a new department.
     */
    public function create(): void
    {
        $this->isEditMode = false;
        $this->editingDepartmentId = null;
        $this->form->resetForm();
        $this->dispatch('open-modal', [
            'id' => 'departmentModal',
        ]);
    }

    /**
     * Store a newly created department.
     */
    public function store(DepartmentService $departmentService): void
    {
        try {
            $this->form->store($departmentService);
            $this->dispatch('close-modal', id: 'departmentModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Department baru berhasil ditambahkan!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan!',
                'message' => 'Terjadi kesalahan sistem saat membuat department.',
            ]);
        }
    }

    /**
     * Open modal and populate form for editing an existing department.
     */
    public function edit(int $id): void
    {
        try {
            $department = Department::findOrFail($id);
            $this->isEditMode = true;
            $this->editingDepartmentId = $id;
            $this->form->setDepartment($department);
            $this->dispatch('open-modal', [
                'id' => 'departmentModal',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data department tidak ditemukan.',
            ]);
        }
    }

    /**
     * Update an existing department.
     */
    public function update(DepartmentService $departmentService): void
    {
        try {
            $this->form->update($departmentService);
            $this->isEditMode = false;
            $this->editingDepartmentId = null;
            $this->dispatch('close-modal', id: 'departmentModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Department berhasil diperbarui!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui department.',
            ]);
        }
    }

    /**
     * Trigger SweetAlert2 delete confirmation dialog.
     */
    public function confirmDelete(int $id): void
    {
        $department = Department::find($id);

        if (! $department) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Department tidak ditemukan.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $id,
            'title' => $department->name,
            'action' => 'delete-department',
        ]);
    }

    /**
     * Delete a department after confirmation.
     */
    #[On('delete-department')]
    public function delete(int $id, DepartmentService $departmentService): void
    {
        try {
            $department = Department::findOrFail($id);
            $departmentService->deleteDepartment($department);

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Department berhasil dihapus!',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus!',
                'message' => 'Terjadi kesalahan sistem saat menghapus department.',
            ]);
        }
    }

    /**
     * Render the Livewire component view.
     */
    public function render(DepartmentService $departmentService): View
    {
        $departments = $departmentService->getPaginatedDepartments(
            search: $this->search,
            perPage: $this->perPage
        );

        return view('livewire.departments.index', [
            'departments' => $departments,
        ]);
    }
}
