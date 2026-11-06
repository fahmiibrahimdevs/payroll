<?php

namespace App\Services;

use App\Models\EmployeeLocationAssignment;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
     * Retrieve paginated work locations with optional search filtering.
     */
    public function getPaginatedWorkLocations(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return WorkLocation::query()
            ->withCount('employees')
            ->when($search, fn ($q) => $q->search($search))
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
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
        /** @var WorkLocation $workLocation */
        $workLocation = $this->create($data);

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
        /** @var WorkLocation $updated */
        $updated = $this->update($workLocation, $data, $actor);

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
     * Assign an employee to a work location for a specific date range.
     *
     * @param  array{employee_id: int, work_location_id: int, start_date: string, end_date: string, notes: ?string}  $data
     */
    public function assignEmployee(array $data, ?User $actor = null): EmployeeLocationAssignment
    {
        return DB::transaction(function () use ($data, $actor) {
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
    public function getActiveAndUpcomingAssignments(int $limit = 30): Collection
    {
        return EmployeeLocationAssignment::query()
            ->with(['employee.department', 'employee.position', 'workLocation'])
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();
    }
}
