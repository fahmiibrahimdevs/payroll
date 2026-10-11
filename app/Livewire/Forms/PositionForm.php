<?php

namespace App\Livewire\Forms;

use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PositionForm extends Form
{
    public ?Position $position = null;

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
                Rule::unique('positions', 'name')->ignore($this->position?->id),
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
            'name' => 'Nama Jabatan',
            'description' => 'Deskripsi',
        ];
    }

    /**
     * Populate form fields from an existing Position model for editing.
     */
    public function setPosition(Position $position): void
    {
        $this->position = $position;
        $this->name = $position->name;
        $this->description = $position->description ?? '';
        $this->resetValidation();
    }

    /**
     * Reset form fields to clean defaults.
     */
    public function resetForm(): void
    {
        $this->reset(['position', 'name', 'description']);
        $this->resetValidation();
    }

    /**
     * Validate and create a new position using PositionService.
     */
    public function store(PositionService $positionService): Position
    {
        $validated = $this->validate();

        $position = $positionService->createPosition($validated);
        $this->resetForm();

        return $position;
    }

    /**
     * Validate and update an existing position using PositionService.
     */
    public function update(PositionService $positionService): Position
    {
        $validated = $this->validate();

        $position = $positionService->updatePosition($this->position, $validated);
        $this->resetForm();

        return $position;
    }
}
