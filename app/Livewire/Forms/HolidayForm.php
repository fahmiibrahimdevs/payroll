<?php

namespace App\Livewire\Forms;

use App\Models\Holiday;
use Illuminate\Validation\Rule;
use Livewire\Form;

class HolidayForm extends Form
{
    public ?Holiday $holiday = null;

    public string $date = '';

    public string $name = '';

    /**
     * Define the validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date',
                Rule::unique('holidays', 'date')->ignore($this->holiday?->id),
            ],
            'name' => ['required', 'string', 'min:3', 'max:150'],
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
            'date' => 'Tanggal Libur',
            'name' => 'Nama Hari Libur',
        ];
    }

    /**
     * Populate form fields from an existing Holiday model for editing.
     */
    public function setHoliday(Holiday $holiday): void
    {
        $this->holiday = $holiday;
        $this->date = $holiday->date->toDateString();
        $this->name = $holiday->name;
        $this->resetValidation();
    }

    /**
     * Reset form fields to clean defaults.
     */
    public function resetForm(): void
    {
        $this->reset(['holiday', 'date', 'name']);
        $this->resetValidation();
    }

    /**
     * Validate and create a new holiday.
     */
    public function store(): Holiday
    {
        $validated = $this->validate();

        $holiday = Holiday::create($validated);
        $this->resetForm();

        return $holiday;
    }

    /**
     * Validate and update an existing holiday.
     */
    public function update(): Holiday
    {
        $validated = $this->validate();

        $this->holiday->update($validated);
        $this->resetForm();

        return $this->holiday;
    }
}
