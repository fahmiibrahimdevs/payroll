<?php

namespace App\Livewire\WorkLocations;

use App\Livewire\Concerns\HasSimpleCrudModal;
use App\Livewire\Forms\WorkLocationForm;
use App\Models\Employee;
use App\Models\EmployeeLocationAssignment;
use App\Models\WorkLocation;
use App\Services\WorkLocationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Master Lokasi Kerja')]
class Index extends Component
{
    use HasSimpleCrudModal;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public WorkLocationForm $form;

    public string $search = '';

    public int $perPage = 10;

    public bool $isEditMode = false;

    public ?int $editingWorkLocationId = null;

    /**
     * Form state for assigning an employee to a work location (site) with date range.
     *
     * @var array{employee_id: string|int, work_location_id: string|int, start_date: string, end_date: string, notes: string}
     */
    public array $assignmentForm = [
        'employee_id' => '',
        'work_location_id' => '',
        'start_date' => '',
        'end_date' => '',
        'notes' => '',
    ];

    protected function simpleCrudModalId(): string
    {
        return 'workLocationModal';
    }

    protected function simpleCrudEntityLabel(): string
    {
        return 'lokasi kerja';
    }

    protected function simpleCrudDeleteAction(): string
    {
        return 'delete-work-location';
    }

    protected function simpleCrudModelClass(): string
    {
        return WorkLocation::class;
    }

    protected function simpleCrudEditingIdProperty(): string
    {
        return 'editingWorkLocationId';
    }

    /**
     * Open modal for creating a new work location.
     */
    public function create(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $this->isEditMode = false;
        $this->editingWorkLocationId = null;
        $this->form->resetForm();

        // Default coordinate center (Subang)
        $defaultLat = -6.5715000;
        $defaultLng = 107.7587000;
        $defaultRadius = 50;

        $this->form->latitude = (string) $defaultLat;
        $this->form->longitude = (string) $defaultLng;
        $this->form->radius_meters = $defaultRadius;

        $this->dispatch('open-modal', [
            'id' => $this->simpleCrudModalId(),
        ]);

        $this->dispatch('work-location-init-map', [
            'lat' => $defaultLat,
            'lng' => $defaultLng,
            'radius' => $defaultRadius,
        ]);
    }

    /**
     * Open modal and populate form for editing an existing work location.
     */
    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        try {
            /** @var WorkLocation $location */
            $location = WorkLocation::findOrFail($id);
            $this->isEditMode = true;
            $this->editingWorkLocationId = $id;
            $this->form->setEntity($location);

            $this->dispatch('open-modal', [
                'id' => $this->simpleCrudModalId(),
            ]);

            $this->dispatch('work-location-init-map', [
                'lat' => (float) $location->latitude,
                'lng' => (float) $location->longitude,
                'radius' => (int) $location->radius_meters,
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data lokasi kerja tidak ditemukan.',
            ]);
        }
    }

    /**
     * Reset form state and validation.
     */
    public function resetForm(): void
    {
        $this->isEditMode = false;
        $this->editingWorkLocationId = null;
        $this->form->resetForm();
        $this->resetValidation();
    }

    /**
     * Store a newly created work location.
     */
    public function store(WorkLocationService $workLocationService): void
    {
        $this->storeRecord($workLocationService);
    }

    /**
     * Update an existing work location.
     */
    public function update(WorkLocationService $workLocationService): void
    {
        $this->updateRecord($workLocationService);
    }

    /**
     * Delete a work location after confirmation.
     */
    #[On('delete-work-location')]
    public function delete(int $id, WorkLocationService $workLocationService): void
    {
        $this->deleteRecord($id, $workLocationService);
    }

    /**
     * Quick toggle for active/inactive status.
     */
    public function toggleStatus(int $id, WorkLocationService $workLocationService): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        try {
            $location = WorkLocation::findOrFail($id);
            $newStatus = ! $location->is_active;
            $workLocationService->update($location, ['is_active' => $newStatus]);

            $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Status Diperbarui',
                'message' => "Lokasi {$location->name} berhasil {$statusText}.",
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui status.',
            ]);
        }
    }

    /**
     * Open modal for assigning an employee to a work location (site) with a date range.
     */
    public function openAssignmentModal(?int $locationId = null): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);
        $this->resetValidation();

        $today = now()->toDateString();
        $threeDaysLater = now()->addDays(2)->toDateString();

        $this->assignmentForm = [
            'employee_id' => '',
            'work_location_id' => $locationId ?? '',
            'start_date' => $today,
            'end_date' => $threeDaysLater,
            'notes' => '',
        ];

        $this->dispatch('open-modal', id: 'locationAssignmentModal');
    }

    /**
     * Quickly set the assignment duration preset.
     */
    public function setAssignmentDuration(int $days): void
    {
        $start = ! empty($this->assignmentForm['start_date'])
            ? Carbon::parse($this->assignmentForm['start_date'])
            : now();

        $this->assignmentForm['start_date'] = $start->toDateString();
        $this->assignmentForm['end_date'] = $days <= 1
            ? $start->toDateString()
            : $start->copy()->addDays($days - 1)->toDateString();
    }

    /**
     * Store the site assignment.
     */
    public function storeAssignment(WorkLocationService $workLocationService): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $validated = $this->validate([
            'assignmentForm.employee_id' => ['required', 'exists:employees,id'],
            'assignmentForm.work_location_id' => ['required', 'exists:work_locations,id'],
            'assignmentForm.start_date' => ['required', 'date'],
            'assignmentForm.end_date' => ['required', 'date', 'after_or_equal:assignmentForm.start_date'],
            'assignmentForm.notes' => ['nullable', 'string', 'max:500'],
        ], [
            'assignmentForm.employee_id.required' => 'Pilih karyawan yang akan ditugaskan.',
            'assignmentForm.work_location_id.required' => 'Pilih lokasi kerja / site tujuan.',
            'assignmentForm.start_date.required' => 'Tanggal mulai wajib diisi.',
            'assignmentForm.end_date.required' => 'Tanggal selesai wajib diisi.',
            'assignmentForm.end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        try {
            $assignment = $workLocationService->assignEmployee($validated['assignmentForm'], auth()->user());

            $this->dispatch('close-modal', id: 'locationAssignmentModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Penugasan Berhasil!',
                'message' => "Karyawan {$assignment->employee?->full_name} berhasil ditugaskan ke {$assignment->workLocation?->name}.",
            ]);

            $this->assignmentForm = [
                'employee_id' => '',
                'work_location_id' => '',
                'start_date' => '',
                'end_date' => '',
                'notes' => '',
            ];
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan!',
                'message' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Confirm deleting an assignment.
     */
    public function confirmDeleteAssignment(int $id): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->dispatch('swal:confirm', [
            'title' => 'Hapus Penugasan Lokasi?',
            'text' => 'Karyawan tidak akan lagi terhubung dengan titik lokasi site ini setelah penugasan dihapus.',
            'icon' => 'warning',
            'action' => 'delete-location-assignment',
            'id' => $id,
        ]);
    }

    /**
     * Delete an assignment record.
     */
    #[On('delete-location-assignment')]
    public function deleteAssignment(int $id, WorkLocationService $workLocationService): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        try {
            $workLocationService->deleteAssignment($id, auth()->user());

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Dihapus!',
                'message' => 'Penugasan lokasi kerja berhasil dibatalkan/dihapus.',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan sistem saat menghapus penugasan.',
            ]);
        }
    }

    /**
     * Render the Livewire component view.
     */
    public function render(WorkLocationService $workLocationService): View
    {
        $workLocations = $workLocationService->getPaginatedWorkLocations(
            search: $this->search,
            perPage: $this->perPage
        );

        $totalLocations = WorkLocation::count();
        $activeLocations = WorkLocation::where('is_active', true)->count();
        $distributedEmployees = Employee::whereNotNull('work_location_id')->count();
        $externalSites = WorkLocation::where('name', 'not like', '%Kantor Pusat%')
            ->where('name', 'not like', '%Head Office%')
            ->count();

        $activeAssignments = $workLocationService->getActiveAndUpcomingAssignments();
        $allActiveEmployees = Employee::query()
            ->where('status', 'active')
            ->with(['department:id,name', 'position:id,name'])
            ->orderBy('full_name')
            ->get();
        $allActiveLocations = WorkLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('livewire.work-locations.index', [
            'workLocations' => $workLocations,
            'totalLocations' => $totalLocations,
            'activeLocations' => $activeLocations,
            'distributedEmployees' => $distributedEmployees,
            'externalSites' => $externalSites,
            'activeAssignments' => $activeAssignments,
            'allActiveEmployees' => $allActiveEmployees,
            'allActiveLocations' => $allActiveLocations,
        ]);
    }
}
