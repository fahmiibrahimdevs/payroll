<?php

namespace App\Livewire\Forms;

use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Validation\Rule;
use Livewire\Form;

class DepartmentForm extends Form
{
    public ?Department $department = null;

    public string $name = '';

    public string $description = '';

    /**
     * Define the validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('departments', 'name')->ignore($this->department?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom validation attribute names.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'name' => 'Nama Department',
            'description' => 'Deskripsi',
        ];
    }

    /**
     * Populate form fields from an existing Department model for editing.
     */
    public function setDepartment(Department $department): void
    {
        $this->department = $department;
        $this->name = $department->name;
        $this->description = $department->description ?? '';
        $this->resetValidation();
    }

    /**
     * Reset form fields to clean defaults.
     */
    public function resetForm(): void
    {
        $this->reset(['department', 'name', 'description']);
        $this->resetValidation();
    }

    /**
     * Validate and create a new department using DepartmentService.
     */
    public function store(DepartmentService $departmentService): Department
    {
        $validated = $this->validate();

        $department = $departmentService->createDepartment($validated);
        $this->resetForm();

        return $department;
    }

    /**
     * Validate and update an existing department using DepartmentService.
     */
    public function update(DepartmentService $departmentService): Department
    {
        $validated = $this->validate();

        $department = $departmentService->updateDepartment($this->department, $validated);
        $this->resetForm();

        return $department;
    }
}
