<?php

namespace App\Services;

use App\Models\SalaryComponent;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class SalaryComponentService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Retrieve paginated salary components with filters.
     */
    public function getPaginatedSalaryComponents(
        ?string $search = null,
        ?string $category = null,
        ?string $type = null,
        ?bool $isActive = null,
        int $perPage = 20,
        string $sortField = 'is_active',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 100));

        $allowedSorts = ['name', 'code', 'category', 'type', 'is_active', 'created_at'];
        $sortColumn = in_array($sortField, $allowedSorts, true) ? $sortField : 'is_active';
        $direction = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return SalaryComponent::query()
            ->when($search, fn ($q) => $q->search($search))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when(! is_null($isActive), fn ($q) => $q->where('is_active', $isActive))
            ->orderByDesc('is_active')
            ->orderByRaw("CASE WHEN category = 'pendapatan_tetap' THEN 1 WHEN category = 'tunjangan_tidak_tetap' THEN 2 ELSE 3 END")
            ->orderBy('name', 'asc')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Create a new salary component.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createSalaryComponent(array $data): SalaryComponent
    {
        try {
            return DB::transaction(function () use ($data) {
                if (array_key_exists('code', $data)) {
                    $data['code'] = ! empty(trim((string) ($data['code'] ?? ''))) ? strtoupper(trim((string) $data['code'])) : null;
                }
                if (array_key_exists('description', $data)) {
                    $data['description'] = ! empty(trim((string) ($data['description'] ?? ''))) ? trim((string) $data['description']) : null;
                }

                $component = SalaryComponent::create($data);

                $this->logger->info(
                    action: 'CREATE',
                    module: 'salary_components',
                    message: 'Salary component created',
                    context: [
                        'salary_component_id' => $component->id,
                        'name' => $component->name,
                        'code' => $component->code,
                        'category' => is_object($component->category) ? $component->category->value : $component->category,
                        'type' => is_object($component->type) ? $component->type->value : $component->type,
                    ]
                );

                return $component;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'CREATE_FAILED',
                module: 'salary_components',
                message: 'Salary component creation failed',
                context: [
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Update an existing salary component.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateSalaryComponent(SalaryComponent $salaryComponent, array $data): SalaryComponent
    {
        try {
            return DB::transaction(function () use ($salaryComponent, $data) {
                if (array_key_exists('code', $data)) {
                    $data['code'] = ! empty(trim((string) ($data['code'] ?? ''))) ? strtoupper(trim((string) $data['code'])) : null;
                }
                if (array_key_exists('description', $data)) {
                    $data['description'] = ! empty(trim((string) ($data['description'] ?? ''))) ? trim((string) $data['description']) : null;
                }

                $oldValues = $salaryComponent->only(['name', 'code', 'category', 'type', 'calculation_type', 'default_value', 'is_taxable', 'is_system', 'is_active', 'sort_order', 'description']);

                $salaryComponent->update($data);

                $this->logger->info(
                    action: 'UPDATE',
                    module: 'salary_components',
                    message: 'Salary component updated',
                    context: [
                        'salary_component_id' => $salaryComponent->id,
                        'old_values' => $oldValues,
                        'new_values' => $salaryComponent->only(['name', 'code', 'category', 'type', 'calculation_type', 'default_value', 'is_taxable', 'is_system', 'is_active', 'sort_order', 'description']),
                    ]
                );

                return $salaryComponent;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'UPDATE_FAILED',
                module: 'salary_components',
                message: 'Salary component update failed',
                context: [
                    'salary_component_id' => $salaryComponent->id,
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Delete a salary component.
     *
     * @throws Throwable
     */
    public function deleteSalaryComponent(SalaryComponent $salaryComponent): bool
    {
        if ($salaryComponent->is_system) {
            throw new DomainException('Komponen sistem tidak dapat dihapus.');
        }

        try {
            return DB::transaction(function () use ($salaryComponent) {
                $deletedInfo = [
                    'salary_component_id' => $salaryComponent->id,
                    'name' => $salaryComponent->name,
                    'code' => $salaryComponent->code,
                ];

                $deleted = $salaryComponent->delete();

                $this->logger->info(
                    action: 'DELETE',
                    module: 'salary_components',
                    message: 'Salary component deleted',
                    context: $deletedInfo
                );

                return $deleted;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'DELETE_FAILED',
                module: 'salary_components',
                message: 'Salary component deletion failed',
                context: [
                    'salary_component_id' => $salaryComponent->id,
                ],
                exception: $e
            );

            throw $e;
        }
    }
}
