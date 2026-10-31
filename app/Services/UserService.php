<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class UserService
{
    /**
     * Hidden dummy/system demo accounts that should not be visible in user management.
     *
     * @var array<int, string>
     */
    public const HIDDEN_DUMMY_EMAILS = [
        'direktur@payroll.com',
        'admin@payroll.com',
        'karyawan@payroll.com',
        'dev@payroll.com',
    ];

    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Retrieve paginated users with optional search and role filtering.
     */
    public function getPaginatedUsers(
        string $search = '',
        string $role = '',
        int $perPage = 10
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 100));

        return User::query()
            ->whereNotIn('email', self::HIDDEN_DUMMY_EMAILS)
            ->with('roles')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query) use ($role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Get aggregate statistics for users.
     *
     * @return array{total: int, active: int, inactive: int, admins: int}
     */
    public function getUserStatistics(): array
    {
        $baseQuery = User::query()->whereNotIn('email', self::HIDDEN_DUMMY_EMAILS);

        // Semua angka dihitung dalam satu query (CASE + EXISTS) alih-alih
        // empat count() terpisah karena kartu statistik dirender tiap render.
        $row = $baseQuery
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN NOT is_active THEN 1 ELSE 0 END) as inactive')
            ->selectRaw(<<<'SQL'
SUM(EXISTS (
    SELECT 1 FROM role_user
    INNER JOIN roles ON roles.id = role_user.role_id
    WHERE role_user.user_id = users.id
        AND role_user.user_type = ?
        AND roles.name = ?
)) as admins
SQL, [User::class, 'admin'])
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'active' => (int) ($row->active ?? 0),
            'inactive' => (int) ($row->inactive ?? 0),
            'admins' => (int) ($row->admins ?? 0),
        ];
    }

    /**
     * Toggle the active status of a user.
     *
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function toggleUserStatus(int $userId, int $currentAdminId): User
    {
        if ($userId === $currentAdminId) {
            throw new InvalidArgumentException('Anda tidak dapat menonaktifkan akun sendiri.');
        }

        try {
            return DB::transaction(function () use ($userId, $currentAdminId) {
                $user = User::with('roles')->findOrFail($userId);

                if (in_array($user->email, self::HIDDEN_DUMMY_EMAILS, true)) {
                    throw new InvalidArgumentException('Akun sistem demo tidak dapat diubah.');
                }

                $oldStatus = $user->is_active;
                $newStatus = ! $oldStatus;

                if (! $newStatus && $user->hasRole('admin') && User::whereHas('roles', fn ($query) => $query->where('name', 'admin'))->where('is_active', true)->count() <= 1) {
                    throw new InvalidArgumentException('Minimal harus ada satu akun admin aktif.');
                }

                $user->is_active = $newStatus;
                if (! $newStatus) {
                    $user->setRememberToken(Str::random(60));
                }
                $user->save();

                if (! $newStatus && Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                $action = $newStatus ? 'USER_ACTIVATED' : 'USER_DEACTIVATED';

                $this->logger->info(
                    action: $action,
                    module: 'users',
                    message: 'User status changed',
                    context: [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'changed_by' => $currentAdminId,
                    ],
                    userId: $currentAdminId
                );

                return $user;
            });
        } catch (Throwable $e) {
            if ($e instanceof InvalidArgumentException) {
                throw $e;
            }

            $this->logger->error(
                action: 'USER_TOGGLE_STATUS_FAILED',
                module: 'users',
                message: 'User status change failed',
                context: [
                    'user_id' => $userId,
                ],
                userId: $currentAdminId,
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Archive a user account without destroying audit and transaction history.
     *
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function deleteUser(int $userId, int $currentAdminId): User
    {
        if ($userId === $currentAdminId) {
            throw new InvalidArgumentException('Anda tidak dapat menghapus akun sendiri.');
        }

        try {
            return DB::transaction(function () use ($userId, $currentAdminId) {
                $user = User::with('roles')->findOrFail($userId);

                if (in_array($user->email, self::HIDDEN_DUMMY_EMAILS, true)) {
                    throw new InvalidArgumentException('Akun sistem demo tidak dapat diarsipkan.');
                }

                if ($user->hasRole('admin') && User::whereHas('roles', fn ($query) => $query->where('name', 'admin'))->count() <= 1) {
                    throw new InvalidArgumentException('Akun admin terakhir tidak boleh dihapus.');
                }
                $userData = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'deleted_by' => $currentAdminId,
                ];

                $user->is_active = false;
                $user->setRememberToken(Str::random(60));
                $user->save();

                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                $this->logger->warning(
                    action: 'USER_DELETED',
                    module: 'users',
                    message: 'User account archived',
                    context: $userData,
                    userId: $currentAdminId
                );

                return $user;
            });
        } catch (Throwable $e) {
            if ($e instanceof InvalidArgumentException) {
                throw $e;
            }

            $this->logger->error(
                action: 'USER_DELETE_FAILED',
                module: 'users',
                message: 'User account deletion failed',
                context: [
                    'user_id' => $userId,
                ],
                userId: $currentAdminId,
                exception: $e
            );

            throw $e;
        }
    }
}
