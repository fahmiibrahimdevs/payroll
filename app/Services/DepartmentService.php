<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class DepartmentService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Retrieve paginated departments with optional search filtering.
     */
    public function getPaginatedDepartments(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return Department::query()
            ->when($search, fn ($q) => $q->search($search))
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Create a new department.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createDepartment(array $data): Department
    {
        try {
            return DB::transaction(function () use ($data) {
                $department = Department::create($data);

                $this->logger->info(
                    action: 'CREATE',
                    module: 'departments',
                    message: 'Department created',
                    context: [
                        'department_id' => $department->id,
                        'name' => $department->name,
                    ]
                );

                return $department;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'CREATE_FAILED',
                module: 'departments',
                message: 'Department creation failed',
                context: [
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Update an existing department.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateDepartment(Department $department, array $data): Department
    {
        try {
            return DB::transaction(function () use ($department, $data) {
                $oldValues = $department->only(['name', 'description']);

                $department->update($data);

                $this->logger->info(
                    action: 'UPDATE',
                    module: 'departments',
                    message: 'Department updated',
                    context: [
                        'department_id' => $department->id,
                        'old_values' => $oldValues,
                        'new_values' => $department->only(['name', 'description']),
                    ]
                );

                return $department;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'UPDATE_FAILED',
                module: 'departments',
                message: 'Department update failed',
                context: [
                    'department_id' => $department->id,
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Delete a department.
     *
     * @throws Throwable
     */
    public function deleteDepartment(Department $department): bool
    {
        try {
            return DB::transaction(function () use ($department) {
                $deletedInfo = [
                    'department_id' => $department->id,
                    'name' => $department->name,
                ];

                $deleted = $department->delete();

                $this->logger->info(
                    action: 'DELETE',
                    module: 'departments',
                    message: 'Department deleted',
                    context: $deletedInfo
                );

                return $deleted;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'DELETE_FAILED',
                module: 'departments',
                message: 'Department deletion failed',
                context: [
                    'department_id' => $department->id,
                ],
                exception: $e
            );

            throw $e;
        }
    }
}
