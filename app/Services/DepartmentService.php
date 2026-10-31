<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Throwable;

/**
 * Service CRUD untuk master Department.
 *
 * Logika transaksi dan ActivityLog ditangani oleh SimpleCrudService;
 * class ini hanya mendefinisikan konfigurasi entitas dan mengekspos
 * API ber-suffix Department.
 */
class DepartmentService extends SimpleCrudService
{
    protected function modelClass(): string
    {
        return Department::class;
    }

    protected function module(): string
    {
        return 'departments';
    }

    protected function entityLabel(): string
    {
        return 'Department';
    }

    protected function logIdKey(): string
    {
        return 'department_id';
    }

    /**
     * Kolom yang dilacak pada log update.
     *
     * @return array<int, string>
     */
    protected function trackedColumns(): array
    {
        return ['name', 'description'];
    }

    /**
     * Retrieve paginated departments with optional search filtering.
     */
    public function getPaginatedDepartments(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return $this->getPaginated($search, $perPage);
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
        /** @var Department $department */
        $department = $this->create($data);

        return $department;
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
        /** @var Department $updated */
        $updated = $this->update($department, $data);

        return $updated;
    }

    /**
     * Delete a department.
     *
     * @throws Throwable
     */
    public function deleteDepartment(Department $department): bool
    {
        return $this->delete($department);
    }
}
