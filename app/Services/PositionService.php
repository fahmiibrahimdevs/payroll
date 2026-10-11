<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class PositionService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Retrieve paginated positions with optional search filtering.
     */
    public function getPaginatedPositions(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return Position::query()
            ->when($search, fn ($q) => $q->search($search))
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Create a new position.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createPosition(array $data): Position
    {
        try {
            return DB::transaction(function () use ($data) {
                $position = Position::create($data);

                $this->logger->info(
                    action: 'CREATE',
                    module: 'positions',
                    message: 'Position created',
                    context: [
                        'position_id' => $position->id,
                        'name' => $position->name,
                    ]
                );

                return $position;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'CREATE_FAILED',
                module: 'positions',
                message: 'Position creation failed',
                context: [
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Update an existing position.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updatePosition(Position $position, array $data): Position
    {
        try {
            return DB::transaction(function () use ($position, $data) {
                $oldValues = $position->only(['name', 'description']);

                $position->update($data);

                $this->logger->info(
                    action: 'UPDATE',
                    module: 'positions',
                    message: 'Position updated',
                    context: [
                        'position_id' => $position->id,
                        'old_values' => $oldValues,
                        'new_values' => $position->only(['name', 'description']),
                    ]
                );

                return $position;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'UPDATE_FAILED',
                module: 'positions',
                message: 'Position update failed',
                context: [
                    'position_id' => $position->id,
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Delete a position.
     *
     * @throws Throwable
     */
    public function deletePosition(Position $position): bool
    {
        try {
            return DB::transaction(function () use ($position) {
                $deletedInfo = [
                    'position_id' => $position->id,
                    'name' => $position->name,
                ];

                $deleted = $position->delete();

                $this->logger->info(
                    action: 'DELETE',
                    module: 'positions',
                    message: 'Position deleted',
                    context: $deletedInfo
                );

                return $deleted;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'DELETE_FAILED',
                module: 'positions',
                message: 'Position deletion failed',
                context: [
                    'position_id' => $position->id,
                ],
                exception: $e
            );

            throw $e;
        }
    }
}
