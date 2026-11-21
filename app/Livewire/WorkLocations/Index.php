<?php

namespace App\Livewire\WorkLocations;

use App\Livewire\Concerns\HasSimpleCrudModal;
use App\Livewire\Forms\WorkLocationForm;
use App\Models\Department;
use App\Models\Employee;
use App\Models\WorkLocation;
use App\Services\WorkLocationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
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

    private const ASSIGNMENTS_PAGE_NAME = 'assignmentsPage';

    private const DEFAULT_EMPLOYEE_BATCH_SIZE = 10;

    private const DEFAULT_EMPLOYEES_PAGE_NAME = 'defaultEmployeesPage';

    protected string $paginationTheme = 'bootstrap';

    public WorkLocationForm $form;

    public string $search = '';

    public ?int $selectedDepartmentId = null;

    public int $perPage = 10;

    public int $assignmentsPerPage = 10;

    public string $assignmentSearch = '';

    public ?int $defaultEmployeeLocationId = null;

    public string $defaultEmployeeLocationName = '';

    /** @var array<int, string|int> */
    public array $selectedDefaultEmployeeIds = [];

    public string $defaultEmployeeSearch = '';

    #[Locked]
    public int $defaultEmployeeLimit = self::DEFAULT_EMPLOYEE_BATCH_SIZE;

    public bool $isEditMode = false;

    public ?int $editingWorkLocationId = null;

    public function updatedSelectedDepartmentId(): void
    {
        $this->resetPage();
    }

    public function updatedAssignmentsPerPage(): void
    {
        $this->resetPage(self::ASSIGNMENTS_PAGE_NAME);
    }

    public function updatedAssignmentSearch(): void
    {
        $this->resetPage(self::ASSIGNMENTS_PAGE_NAME);
    }

    public function updatedDefaultEmployeeSearch(): void
    {
        $this->defaultEmployeeLimit = self::DEFAULT_EMPLOYEE_BATCH_SIZE;
        $this->resetPage(self::DEFAULT_EMPLOYEES_PAGE_NAME);
    }

    /**
     * Open the editor for employees with this permanent default location.
     */
    public function openDefaultEmployeeModal(int $workLocationId): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $workLocation = WorkLocation::findOrFail($workLocationId);
        $this->defaultEmployeeLocationId = $workLocation->id;
        $this->defaultEmployeeLocationName = $workLocation->name;
        $this->selectedDefaultEmployeeIds = $workLocation->employees()
            ->where('status', 'active')
            ->orderBy('full_name')
            ->pluck('employees.id')
            ->map(static fn (int $employeeId): string => (string) $employeeId)
            ->all();
        $this->defaultEmployeeSearch = '';
        $this->defaultEmployeeLimit = self::DEFAULT_EMPLOYEE_BATCH_SIZE;
        $this->resetValidation();

        $this->dispatch('open-modal', id: 'defaultEmployeesModal');
    }

    public function loadMoreDefaultEmployees(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $activeEmployeeCount = Employee::query()
            ->where('status', 'active')
            ->count();

        $this->defaultEmployeeLimit = min(
            $this->defaultEmployeeLimit + self::DEFAULT_EMPLOYEE_BATCH_SIZE,
            $activeEmployeeCount
        );
    }

    /**
     * Save the selected employees as members of this location's default roster.
     */
    public function saveDefaultEmployees(WorkLocationService $workLocationService): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $validated = $this->validate([
            'defaultEmployeeLocationId' => ['required', 'integer', 'exists:work_locations,id'],
            'selectedDefaultEmployeeIds' => ['array'],
            'selectedDefaultEmployeeIds.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('employees', 'id')->where('status', 'active'),
            ],
        ], [
            'selectedDefaultEmployeeIds.*.exists' => 'Pilih karyawan aktif yang valid.',
        ]);

        try {
            $workLocationService->syncDefaultEmployees(
                (int) $validated['defaultEmployeeLocationId'],
                array_map('intval', $validated['selectedDefaultEmployeeIds'] ?? []),
                auth()->user()
            );

            $employeeCount = count($validated['selectedDefaultEmployeeIds'] ?? []);
            $this->dispatch('close-modal', id: 'defaultEmployeesModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Karyawan Default Diperbarui',
                'message' => "{$employeeCount} karyawan aktif disimpan untuk {$this->defaultEmployeeLocationName}.",
            ]);
            $this->reset([
                'defaultEmployeeLocationId',
                'defaultEmployeeLocationName',
                'selectedDefaultEmployeeIds',
                'defaultEmployeeSearch',
                'defaultEmployeeLimit',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan',
                'message' => 'Terjadi kesalahan saat memperbarui karyawan default lokasi kerja.',
            ]);
        }
    }

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
     * Update the assignment employee and provide the employee's current default locations to the picker.
     *
     * @return array{employeeId: string, locationIds: array<int, int>}
     */
    public function selectAssignmentEmployee(?int $employeeId, WorkLocationService $workLocationService): array
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        if ($employeeId === null) {
            $this->assignmentForm['employee_id'] = '';

            return [
                'employeeId' => '',
                'locationIds' => [],
            ];
        }

        $this->assignmentForm['employee_id'] = (string) $employeeId;

        return [
            'employeeId' => (string) $employeeId,
            'locationIds' => $workLocationService->getDefaultWorkLocationIdsForEmployee($employeeId),
        ];
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

        $assignmentData = $validated['assignmentForm'];
        if ($workLocationService->isDefaultWorkLocationForEmployee(
            (int) $assignmentData['employee_id'],
            (int) $assignmentData['work_location_id']
        )) {
            $this->addError(
                'assignmentForm.work_location_id',
                'Lokasi default karyawan tidak dapat dipilih sebagai lokasi penugasan sementara.'
            );

            return;
        }

        try {
            $assignment = $workLocationService->assignEmployee($assignmentData, auth()->user());
            $this->resetPage(self::ASSIGNMENTS_PAGE_NAME);

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
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
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
            $this->resetPage(self::ASSIGNMENTS_PAGE_NAME);

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
            perPage: $this->perPage,
            departmentId: $this->selectedDepartmentId
        );

        $totalLocations = WorkLocation::count();
        $activeLocations = WorkLocation::where('is_active', true)->count();
        $distributedEmployees = Employee::query()
            ->whereHas('defaultWorkLocations')
            ->orWhereNotNull('work_location_id')
            ->count();
        $externalSites = WorkLocation::where('name', 'not like', '%Kantor Pusat%')
            ->where('name', 'not like', '%Head Office%')
            ->count();

        $activeAssignments = $workLocationService->getActiveAndUpcomingAssignments(
            perPage: $this->assignmentsPerPage,
            pageName: self::ASSIGNMENTS_PAGE_NAME,
            search: $this->assignmentSearch
        );
        $allActiveEmployees = Employee::query()
            ->where('status', 'active')
            ->with(['department:id,name', 'position:id,name', 'defaultWorkLocations:id'])
            ->orderBy('full_name')
            ->get();
        $defaultLocationEmployees = $workLocationService->getDefaultEmployeeCandidates(
            search: $this->defaultEmployeeSearch,
            perPage: $this->defaultEmployeeLimit,
            pageName: self::DEFAULT_EMPLOYEES_PAGE_NAME
        );
        $allActiveLocations = WorkLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $departments = Department::getLookup();

        return view('livewire.work-locations.index', [
            'workLocations' => $workLocations,
            'departments' => $departments,
            'totalLocations' => $totalLocations,
            'activeLocations' => $activeLocations,
            'distributedEmployees' => $distributedEmployees,
            'externalSites' => $externalSites,
            'activeAssignments' => $activeAssignments,
            'allActiveEmployees' => $allActiveEmployees,
            'defaultLocationEmployees' => $defaultLocationEmployees,
            'defaultLocationEmployeesTotal' => $defaultLocationEmployees->total(),
            'hasMoreDefaultLocationEmployees' => $defaultLocationEmployees->total() > $defaultLocationEmployees->count(),
            'allActiveLocations' => $allActiveLocations,
        ]);
    }
}
