<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;

#[Fillable(['name', 'email', 'password', 'is_active', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements LaratrustUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRolesAndPermissions, Notifiable;

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
