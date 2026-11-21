<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLocationAssignment;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Service CRUD untuk master WorkLocation (Lokasi Kerja).
 *
 * Mengelola data lokasi kerja absensi (geofencing) perusahaan
 * beserta logging ActivityLog dan transaksi basis data.
 */
class WorkLocationService extends SimpleCrudService
{
    /**
     * Nama class model yang dikelola service ini.
     */
    protected function modelClass(): string
    {
        return WorkLocation::class;
    }

    /**
     * Nama module untuk ActivityLog.
     */
    protected function module(): string
    {
        return 'work_locations';
    }

    /**
     * Label entitas untuk pesan log.
     */
    protected function entityLabel(): string
    {
        return 'Lokasi Kerja';
    }

    /**
     * Key ID entitas dalam context log.
     */
    protected function logIdKey(): string
    {
        return 'work_location_id';
    }

    /**
     * Kolom model yang dicatat sebagai old_values/new_values pada update.
     *
     * @return array<int, string>
     */
    protected function trackedColumns(): array
    {
        return [
            'name',
            'address',
            'latitude',
            'longitude',
            'radius_meters',
            'is_active',
        ];
    }

    /**
     * Retrieve paginated work locations with optional search and department filtering.
     */
    public function getPaginatedWorkLocations(string $search = '', int $perPage = 10, ?int $departmentId = null): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return WorkLocation::query()
            ->with(['departments:id,name'])
            ->withCount(['employees', 'departments'])
            ->when($search, fn ($q) => $q->search($search))
            ->when($departmentId, fn ($q) => $q->whereHas('departments', fn ($dq) => $dq->where('departments.id', $departmentId)))
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Retrieve active employees available for a permanent default location.
     */
    public function getDefaultEmployeeCandidates(
        string $search = '',
        int $perPage = 10,
        string $pageName = 'defaultEmployeesPage'
    ): LengthAwarePaginator {
        $perPage = max(1, $perPage);
        $term = '%'.trim($search).'%';

        return Employee::query()
            ->where('status', 'active')
            ->with(['department:id,name', 'position:id,name', 'workLocation:id,name', 'defaultWorkLocations:id,name'])
            ->when(filled($search), function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('full_name', 'like', $term)
                        ->orWhereHas('department', fn (Builder $departmentQuery) => $departmentQuery->where('name', 'like', $term))
                        ->orWhereHas('position', fn (Builder $positionQuery) => $positionQuery->where('name', 'like', $term))
                        ->orWhereHas('workLocation', fn (Builder $locationQuery) => $locationQuery->where('name', 'like', $term))
                        ->orWhereHas('defaultWorkLocations', fn (Builder $locationQuery) => $locationQuery->where('name', 'like', $term));
                });
            })
            ->orderBy('full_name')
            ->orderBy('id')
            ->paginate($perPage, ['*'], $pageName);
    }

    /**
     * Synchronize active employees' membership in one permanent default location.
     *
     * @param  array<int, int>  $employeeIds
     */
    public function syncDefaultEmployees(int $workLocationId, array $employeeIds, ?User $actor = null): void
    {
        $employeeIds = array_values(array_unique($employeeIds));

        DB::transaction(function () use ($workLocationId, $employeeIds, $actor): void {
            $workLocation = WorkLocation::query()->findOrFail($workLocationId);
            $employeeIds = Employee::query()
                ->where('status', 'active')
                ->whereIn('id', $employeeIds)
                ->pluck('id')
                ->map(static fn (int $employeeId): int => $employeeId)
                ->all();

            $currentEmployeeIds = $workLocation->employees()
                ->where('employees.status', 'active')
                ->pluck('employees.id')
                ->all();
            $employeeIdsToRemove = array_values(array_diff($currentEmployeeIds, $employeeIds));

            if ($employeeIdsToRemove !== []) {
                $workLocation->employees()->detach($employeeIdsToRemove);

                Employee::query()
                    ->whereIn('id', $employeeIdsToRemove)
                    ->where('work_location_id', $workLocation->id)
                    ->update(['work_location_id' => null]);

                $remainingDefaultLocations = DB::table('employee_default_work_locations')
                    ->select('employee_id')
                    ->selectRaw('MIN(work_location_id) as work_location_id')
                    ->whereIn('employee_id', $employeeIdsToRemove)
                    ->groupBy('employee_id');

                DB::table('employees')
                    ->joinSub(
                        $remainingDefaultLocations,
                        'remaining_default_locations',
                        'remaining_default_locations.employee_id',
                        '=',
                        'employees.id'
                    )
                    ->whereIn('employees.id', $employeeIdsToRemove)
                    ->update([
                        'employees.work_location_id' => DB::raw('remaining_default_locations.work_location_id'),
                    ]);
            }

            if ($employeeIds !== []) {
                $workLocation->employees()->syncWithoutDetaching($employeeIds);

                Employee::query()
                    ->where('status', 'active')
                    ->whereIn('id', $employeeIds)
                    ->whereNull('work_location_id')
                    ->update(['work_location_id' => $workLocation->id]);
            }

            $this->logger->log(
                action: 'sync_default_work_location_employees',
                module: $this->module(),
                level: 'info',
                message: 'Karyawan default lokasi kerja diperbarui',
                context: [
                    'work_location_id' => $workLocation->id,
                    'employee_ids' => $employeeIds,
                    'employee_count' => count($employeeIds),
                ],
                userId: $actor?->id ?? auth()->id()
            );
        });
    }

    /**
     * Store a newly created work location record.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function store(array $data, ?User $actor = null): WorkLocation
    {
        $departmentIds = $data['department_ids'] ?? null;
        unset($data['department_ids']);

        /** @var WorkLocation $workLocation */
        $workLocation = $this->create($data);

        if ($departmentIds !== null) {
            $workLocation->departments()->sync($departmentIds);
        }

        return $workLocation;
    }

    /**
     * Create alias matching standard naming convention.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createWorkLocation(array $data, ?User $actor = null): WorkLocation
    {
        return $this->store($data, $actor);
    }

    /**
     * Update an existing work location record.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function update(Model $model, array $data, ?User $actor = null): Model
    {
        return parent::update($model, $data);
    }

    /**
     * Type-hinted update method for WorkLocation.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateWorkLocation(WorkLocation $workLocation, array $data, ?User $actor = null): WorkLocation
    {
        $departmentIds = $data['department_ids'] ?? null;
        unset($data['department_ids']);

        /** @var WorkLocation $updated */
        $updated = $this->update($workLocation, $data, $actor);

        if ($departmentIds !== null) {
            $updated->departments()->sync($departmentIds);
        }

        return $updated;
    }

    /**
     * Delete a work location record.
     *
     * @throws Throwable
     */
    public function delete(Model $model, ?User $actor = null): bool
    {
        return parent::delete($model);
    }

    /**
     * Type-hinted delete method for WorkLocation.
     *
     * @throws Throwable
     */
    public function deleteWorkLocation(WorkLocation $workLocation, ?User $actor = null): bool
    {
        return $this->delete($workLocation, $actor);
    }

    /**
     * Determine whether a location is one of an employee's permanent defaults.
     */
    public function isDefaultWorkLocationForEmployee(int $employeeId, int $workLocationId): bool
    {
        return Employee::query()
            ->whereKey($employeeId)
            ->where(function (Builder $query) use ($workLocationId): void {
                $query->where('work_location_id', $workLocationId)
                    ->orWhereHas('defaultWorkLocations', fn (Builder $locations): Builder => $locations->whereKey($workLocationId));
            })
            ->exists();
    }

    /**
     * Retrieve the permanent default locations for an active employee.
     *
     * @return array<int, int>
     */
    public function getDefaultWorkLocationIdsForEmployee(int $employeeId): array
    {
        $employee = Employee::query()
            ->where('status', 'active')
            ->with('defaultWorkLocations:id')
            ->findOrFail($employeeId);

        $locationIds = $employee->defaultWorkLocations->modelKeys();
        if ($employee->work_location_id !== null) {
            $locationIds[] = (int) $employee->work_location_id;
        }

        $locationIds = array_values(array_unique(array_map('intval', $locationIds)));
        sort($locationIds);

        return $locationIds;
    }

    /**
     * Assign an employee to a work location for a specific date range.
     *
     * @param  array{employee_id: int, work_location_id: int, start_date: string, end_date: string, notes: ?string}  $data
     */
    public function assignEmployee(array $data, ?User $actor = null): EmployeeLocationAssignment
    {
        return DB::transaction(function () use ($data, $actor) {
            if ($this->isDefaultWorkLocationForEmployee((int) $data['employee_id'], (int) $data['work_location_id'])) {
                throw ValidationException::withMessages([
                    'assignmentForm.work_location_id' => 'Lokasi default karyawan tidak dapat dipilih sebagai lokasi penugasan sementara.',
                ]);
            }

            $assignment = EmployeeLocationAssignment::create([
                'employee_id' => $data['employee_id'],
                'work_location_id' => $data['work_location_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->logger->log(
                action: 'assign_employee_location',
                module: $this->module(),
                level: 'info',
                message: 'Karyawan berhasil ditugaskan ke lokasi kerja',
                context: [
                    'assignment_id' => $assignment->id,
                    'employee_id' => $data['employee_id'],
                    'work_location_id' => $data['work_location_id'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                ],
                userId: $actor?->id ?? auth()->id()
            );

            return $assignment;
        });
    }

    /**
     * Delete an employee location assignment.
     */
    public function deleteAssignment(int $assignmentId, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($assignmentId, $actor) {
            $assignment = EmployeeLocationAssignment::findOrFail($assignmentId);
            $deleted = $assignment->delete();

            $this->logger->log(
                action: 'delete_employee_location_assignment',
                module: $this->module(),
                level: 'warning',
                message: 'Penugasan lokasi kerja karyawan dihapus',
                context: [
                    'assignment_id' => $assignmentId,
                    'employee_id' => $assignment->employee_id,
                    'work_location_id' => $assignment->work_location_id,
                ],
                userId: $actor?->id ?? auth()->id()
            );

            return (bool) $deleted;
        });
    }

    /**
     * Get active & upcoming employee location assignments.
     */
    public function getActiveAndUpcomingAssignments(
        int $perPage = 10,
        string $pageName = 'assignmentsPage',
        string $search = ''
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 100));

        return EmployeeLocationAssignment::query()
            ->with(['employee.department', 'employee.position', 'workLocation'])
            ->whereDate('end_date', '>=', now()->toDateString())
            ->when(filled($search), function (Builder $query) use ($search): void {
                $term = '%'.trim($search).'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('notes', 'like', $term)
                        ->orWhereHas('employee', function (Builder $employeeQuery) use ($term): void {
                            $employeeQuery->where('full_name', 'like', $term)
                                ->orWhereHas('position', fn (Builder $positionQuery) => $positionQuery->where('name', 'like', $term))
                                ->orWhereHas('department', fn (Builder $departmentQuery) => $departmentQuery->where('name', 'like', $term));
                        })
                        ->orWhereHas('workLocation', function (Builder $locationQuery) use ($term): void {
                            $locationQuery->where('name', 'like', $term)
                                ->orWhere('address', 'like', $term);
                        });
                });
            })
            ->orderBy('start_date', 'asc')
            ->orderBy('id')
            ->paginate($perPage, ['*'], $pageName);
    }
}
