<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Base class untuk service CRUD master sederhana (Department, Position, dsb.).
 *
 * Menampung logika paginasi, create/update/delete dalam DB::transaction,
 * serta pencatatan ActivityLog yang seragam. Subclass hanya mendefinisikan
 * model yang dikelola, module log, label entitas, key ID log, dan kolom
 * yang dilacak pada update.
 */
abstract class SimpleCrudService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Nama class model yang dikelola service ini.
     */
    abstract protected function modelClass(): string;

    /**
     * Nama module untuk ActivityLog (mis. 'departments').
     */
    abstract protected function module(): string;

    /**
     * Label entitas untuk pesan log (mis. 'Department').
     */
    abstract protected function entityLabel(): string;

    /**
     * Key ID entitas dalam context log (mis. 'department_id').
     */
    abstract protected function logIdKey(): string;

    /**
     * Kolom model yang dicatat sebagai old_values/new_values pada update.
     *
     * @return array<int, string>
     */
    abstract protected function trackedColumns(): array;

    /**
     * Retrieve paginated records with optional search filtering.
     */
    public function getPaginated(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $modelClass = $this->modelClass();

        return $modelClass::query()
            ->when($search, fn ($q) => $q->search($search))
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function create(array $data): Model
    {
        $modelClass = $this->modelClass();
        $module = $this->module();
        $label = $this->entityLabel();
        $logIdKey = $this->logIdKey();

        try {
            return DB::transaction(function () use ($modelClass, $data, $module, $label, $logIdKey) {
                $model = $modelClass::create($data);

                $this->logger->info(
                    action: 'CREATE',
                    module: $module,
                    message: "{$label} created",
                    context: [
                        $logIdKey => $model->id,
                        'name' => $model->name,
                    ]
                );

                return $model;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'CREATE_FAILED',
                module: $module,
                message: "{$label} creation failed",
                context: [
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Update an existing record.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function update(Model $model, array $data): Model
    {
        $module = $this->module();
        $label = $this->entityLabel();
        $logIdKey = $this->logIdKey();
        $columns = $this->trackedColumns();

        try {
            return DB::transaction(function () use ($model, $data, $module, $label, $logIdKey, $columns) {
                $oldValues = $model->only($columns);

                $model->update($data);

                $this->logger->info(
                    action: 'UPDATE',
                    module: $module,
                    message: "{$label} updated",
                    context: [
                        $logIdKey => $model->id,
                        'old_values' => $oldValues,
                        'new_values' => $model->only($columns),
                    ]
                );

                return $model;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'UPDATE_FAILED',
                module: $module,
                message: "{$label} update failed",
                context: [
                    $logIdKey => $model->id,
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Delete a record.
     *
     * @throws Throwable
     */
    public function delete(Model $model): bool
    {
        $module = $this->module();
        $label = $this->entityLabel();
        $logIdKey = $this->logIdKey();

        try {
            return DB::transaction(function () use ($model, $module, $label, $logIdKey) {
                $deletedInfo = [
                    $logIdKey => $model->id,
                    'name' => $model->name,
                ];

                $deleted = $model->delete();

                $this->logger->info(
                    action: 'DELETE',
                    module: $module,
                    message: "{$label} deleted",
                    context: $deletedInfo
                );

                return $deleted;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'DELETE_FAILED',
                module: $module,
                message: "{$label} deletion failed",
                context: [
                    $logIdKey => $model->id,
                ],
                exception: $e
            );

            throw $e;
        }
    }
}
