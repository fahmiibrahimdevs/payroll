<?php

namespace App\Livewire\Forms;

use App\Models\WorkLocation;
use App\Services\SimpleCrudService;
use App\Services\WorkLocationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Form;

class WorkLocationForm extends Form
{
    public ?WorkLocation $workLocation = null;

    public string $name = '';

    public ?string $address = '';

    public ?string $latitude = '';

    public ?string $longitude = '';

    public int $radius_meters = 50;

    public bool $is_active = true;

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
                Rule::unique('work_locations', 'name')->ignore($this->workLocation?->id),
            ],
            'address' => [
                'nullable',
                'string',
                'max:500',
            ],
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'radius_meters' => [
                'required',
                'integer',
                'min:5',
                'max:10000',
            ],
            'is_active' => [
                'boolean',
            ],
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
            'name' => 'Nama Lokasi',
            'address' => 'Alamat',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'radius_meters' => 'Radius Toleransi',
            'is_active' => 'Status Aktif',
        ];
    }

    /**
     * Populate form fields from an existing WorkLocation model for editing.
     */
    public function setWorkLocation(WorkLocation $workLocation): void
    {
        $this->workLocation = $workLocation;
        $this->name = $workLocation->name;
        $this->address = $workLocation->address ?? '';
        $this->latitude = (string) $workLocation->latitude;
        $this->longitude = (string) $workLocation->longitude;
        $this->radius_meters = (int) $workLocation->radius_meters;
        $this->is_active = (bool) $workLocation->is_active;
        $this->resetValidation();
    }

    /**
     * Hook trait HasSimpleCrudModal: populate form fields from a model.
     */
    public function setEntity(Model $model): void
    {
        if ($model instanceof WorkLocation) {
            $this->setWorkLocation($model);
        }
    }

    /**
     * Reset form fields to clean defaults.
     */
    public function resetForm(): void
    {
        $this->reset(['workLocation', 'name', 'address', 'latitude', 'longitude']);
        $this->radius_meters = 50;
        $this->is_active = true;
        $this->resetValidation();
    }

    /**
     * Validate and create a new work location using WorkLocationService.
     */
    public function store(SimpleCrudService|WorkLocationService $service): WorkLocation
    {
        $validated = $this->validate();

        if ($service instanceof WorkLocationService) {
            $location = $service->store($validated);
        } else {
            /** @var WorkLocation $location */
            $location = $service->create($validated);
        }

        $this->resetForm();

        return $location;
    }

    /**
     * Validate and update an existing work location using WorkLocationService.
     */
    public function update(SimpleCrudService|WorkLocationService $service): WorkLocation
    {
        $validated = $this->validate();

        if ($service instanceof WorkLocationService) {
            $location = $service->updateWorkLocation($this->workLocation, $validated);
        } else {
            /** @var WorkLocation $location */
            $location = $service->update($this->workLocation, $validated);
        }

        $this->resetForm();

        return $location;
    }
}
