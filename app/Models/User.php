<?php

namespace App\Models;

use BackedEnum;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Ramsey\Uuid\UuidInterface;

#[Fillable(['name', 'email', 'password', 'is_active', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements LaratrustUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRolesAndPermissions {
        HasRolesAndPermissions::hasRole as laratrustHasRole;
        HasRolesAndPermissions::syncRoles as laratrustSyncRoles;
        HasRolesAndPermissions::addRole as laratrustAddRole;
        HasRolesAndPermissions::removeRole as laratrustRemoveRole;
    }

    /**
     * In-memory cache for user's role names within the current request lifecycle.
     *
     * @var array<int, string>|null
     */
    protected ?array $memoizedRoleNames = null;

    /**
     * Check if user has a role with request-level memoization.
     */
    public function hasRole(string|array|BackedEnum $name, mixed $team = null, bool $requireAll = false): bool
    {
        if ($team !== null) {
            return $this->laratrustHasRole($name, $team, $requireAll);
        }

        if ($this->memoizedRoleNames === null) {
            $this->memoizedRoleNames = $this->relationLoaded('roles')
                ? $this->roles->pluck('name')->all()
                : $this->roles()->pluck('name')->all();
        }

        if (is_array($name)) {
            $names = array_map(fn ($n) => $n instanceof BackedEnum ? (string) $n->value : (string) $n, $name);
            $matches = array_intersect($names, $this->memoizedRoleNames);

            return $requireAll ? count($matches) === count($names) : count($matches) > 0;
        }

        $roleName = $name instanceof BackedEnum ? (string) $name->value : (string) $name;

        return in_array($roleName, $this->memoizedRoleNames, true);
    }

    /**
     * Flush memoized role names when roles relation changes.
     */
    public function flushRoleCache(): static
    {
        $this->memoizedRoleNames = null;

        return $this;
    }

    public function syncRoles(array $roles = [], mixed $team = null, bool $detaching = true): static
    {
        $this->flushRoleCache();

        return $this->laratrustSyncRoles($roles, $team, $detaching);
    }

    public function addRole(array|string|int|Model|UuidInterface|BackedEnum $role, mixed $team = null): static
    {
        $this->flushRoleCache();

        return $this->laratrustAddRole($role, $team);
    }

    public function removeRole(array|string|int|Model|UuidInterface|BackedEnum $role, mixed $team = null): static
    {
        $this->flushRoleCache();

        return $this->laratrustRemoveRole($role, $team);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the employee profile associated with the user.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Resolve the employee record linked to this user,
     * with fallback to matching email in employee details.
     */
    public function getEmployeeRecord(): ?Employee
    {
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee;
        }

        $emp = $this->employee()->first();
        if ($emp) {
            return $emp;
        }

        $empByEmail = Employee::whereHas('detail', function ($q) {
            $q->where('email', $this->email);
        })->first();

        if ($empByEmail) {
            $empByEmail->updateQuietly(['user_id' => $this->id]);

            return $empByEmail;
        }

        return null;
    }
}
