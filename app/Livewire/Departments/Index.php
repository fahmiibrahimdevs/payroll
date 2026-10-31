<?php

namespace App\Livewire\Departments;

use App\Livewire\Concerns\HasSimpleCrudModal;
use App\Livewire\Forms\DepartmentForm;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Kelola Department')]
class Index extends Component
{
    use HasSimpleCrudModal;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public DepartmentForm $form;

    public string $search = '';

    public int $perPage = 10;

    public bool $isEditMode = false;

    public ?int $editingDepartmentId = null;

    protected function simpleCrudModalId(): string
    {
        return 'departmentModal';
    }

    protected function simpleCrudEntityLabel(): string
    {
        return 'department';
    }

    protected function simpleCrudDeleteAction(): string
    {
        return 'delete-department';
    }

    protected function simpleCrudModelClass(): string
    {
        return Department::class;
    }

    protected function simpleCrudEditingIdProperty(): string
    {
        return 'editingDepartmentId';
    }

    /**
     * Store a newly created department.
     */
    public function store(DepartmentService $departmentService): void
    {
        $this->storeRecord($departmentService);
    }

    /**
     * Update an existing department.
     */
    public function update(DepartmentService $departmentService): void
    {
        $this->updateRecord($departmentService);
    }

    /**
     * Delete a department after confirmation.
     */
    #[On('delete-department')]
    public function delete(int $id, DepartmentService $departmentService): void
    {
        $this->deleteRecord($id, $departmentService);
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
