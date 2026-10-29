<?php

namespace App\Livewire\Forms;

use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentCategory;
use App\Models\SalaryComponent;
use App\Services\SalaryComponentService;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SalaryComponentForm extends Form
{
    public ?SalaryComponent $salaryComponent = null;

    public string $name = '';

    public string $code = '';

    public string $type = 'allowance';

    public string $category = 'pendapatan_tetap';

    public string $calculation_type = 'nominal';

    public string|int|float|null $default_value = '';

    public bool $is_taxable = true;

    public bool $is_system = false;

    public bool $is_active = true;

    public int $sort_order = 0;

    public string $description = '';

    public function updatedType(string $val): void
    {
        if ($val === 'deduction') {
            $this->category = 'potongan';
            $this->is_taxable = false;
        } elseif ($val === 'allowance' && $this->category === 'potongan') {
            $this->category = 'pendapatan_tetap';
            $this->is_taxable = true;
        }
    }

    public function updatedCategory(string $val): void
    {
        if ($val === 'potongan') {
            $this->type = 'deduction';
            $this->is_taxable = false;
        } else {
            $this->type = 'allowance';
            $this->is_taxable = true;
        }
    }

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
                'max:100',
            ],
            'code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('salary_components', 'code')->ignore($this->salaryComponent?->id),
            ],
            'category' => [
                'required',
                Rule::enum(SalaryComponentCategory::class),
            ],
            'calculation_type' => [
                'required',
                Rule::enum(SalaryCalculationType::class),
            ],
            'default_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'is_taxable' => ['boolean'],
            'is_system' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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
            'name' => 'Nama Komponen',
            'code' => 'Kode Komponen',
            'category' => 'Kategori Komponen',
            'calculation_type' => 'Metode Perhitungan',
            'default_value' => 'Nilai Default',
            'is_taxable' => 'Kena Pajak',
            'is_system' => 'Status Sistem',
            'is_active' => 'Status Aktif',
            'sort_order' => 'Urutan Tampil',
            'description' => 'Keterangan',
        ];
    }

    /**
     * Populate form fields from an existing SalaryComponent model for editing.
     */
    public function setSalaryComponent(SalaryComponent $salaryComponent): void
    {
        $this->resetForm();
        $this->salaryComponent = $salaryComponent;
        $this->name = $salaryComponent->name;
        $this->code = $salaryComponent->code ?? '';
        $this->category = $salaryComponent->category instanceof SalaryComponentCategory ? $salaryComponent->category->value : (string) $salaryComponent->category;
        $this->calculation_type = $salaryComponent->calculation_type instanceof SalaryCalculationType ? $salaryComponent->calculation_type->value : (string) ($salaryComponent->calculation_type ?? 'nominal');
        $this->default_value = $salaryComponent->default_value !== null ? (float) $salaryComponent->default_value : '';
        $this->is_taxable = (bool) $salaryComponent->is_taxable;
        $this->is_system = (bool) $salaryComponent->is_system;
        $this->is_active = (bool) $salaryComponent->is_active;
        $this->sort_order = (int) ($salaryComponent->sort_order ?? 0);
        $this->description = $salaryComponent->description ?? '';
        $this->resetValidation();
    }

    /**
     * Reset form fields to clean defaults.
     */
    public function resetForm(): void
    {
        $this->reset([
            'salaryComponent',
            'name',
            'code',
            'category',
            'calculation_type',
            'default_value',
            'is_taxable',
            'is_system',
            'is_active',
            'sort_order',
            'description',
        ]);
        $this->category = 'pendapatan_tetap';
        $this->calculation_type = 'nominal';
        $this->default_value = '';
        $this->is_taxable = true;
        $this->is_system = false;
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetValidation();
    }

    /**
     * Validate and create a new salary component.
     */
    public function store(SalaryComponentService $service): SalaryComponent
    {
        $validated = $this->validate();

        if ($this->type === 'deduction' && $validated['category'] !== 'potongan') {
            $validated['category'] = 'potongan';
        }
        $validated['type'] = $this->type;

        $validated['code'] = ! empty(trim((string) ($validated['code'] ?? ''))) ? strtoupper(trim((string) $validated['code'])) : null;
        $validated['description'] = ! empty(trim((string) ($validated['description'] ?? ''))) ? trim((string) $validated['description']) : null;
        $validated['default_value'] = ($validated['default_value'] !== '' && $validated['default_value'] !== null) ? (float) $validated['default_value'] : null;

        $component = $service->createSalaryComponent($validated);
        $this->resetForm();

        return $component;
    }

    /**
     * Validate and update an existing salary component.
     */
    public function update(SalaryComponentService $service): SalaryComponent
    {
        $validated = $this->validate();

        if ($this->type === 'deduction' && $validated['category'] !== 'potongan') {
            $validated['category'] = 'potongan';
        }
        $validated['type'] = $this->type;

        $validated['code'] = ! empty(trim((string) ($validated['code'] ?? ''))) ? strtoupper(trim((string) $validated['code'])) : null;
        $validated['description'] = ! empty(trim((string) ($validated['description'] ?? ''))) ? trim((string) $validated['description']) : null;
        $validated['default_value'] = ($validated['default_value'] !== '' && $validated['default_value'] !== null) ? (float) $validated['default_value'] : null;

        $component = $service->updateSalaryComponent($this->salaryComponent, $validated);
        $this->resetForm();

        return $component;
    }
}
