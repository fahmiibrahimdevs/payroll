<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Throwable;

/**
 * Service CRUD untuk master Position.
 *
 * Logika transaksi dan ActivityLog ditangani oleh SimpleCrudService;
 * class ini hanya mendefinisikan konfigurasi entitas dan mengekspos
 * API ber-suffix Position.
 */
class PositionService extends SimpleCrudService
{
    protected function modelClass(): string
    {
        return Position::class;
    }

    protected function module(): string
    {
        return 'positions';
    }

    protected function entityLabel(): string
    {
        return 'Position';
    }

    protected function logIdKey(): string
    {
        return 'position_id';
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
     * Retrieve paginated positions with optional search filtering.
     */
    public function getPaginatedPositions(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return $this->getPaginated($search, $perPage);
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
        /** @var Position $position */
        $position = $this->create($data);

        return $position;
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
        /** @var Position $updated */
        $updated = $this->update($position, $data);

        return $updated;
    }

    /**
     * Delete a position.
     *
     * @throws Throwable
     */
    public function deletePosition(Position $position): bool
    {
        return $this->delete($position);
    }
}
