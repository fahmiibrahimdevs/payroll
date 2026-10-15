<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createAdminUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Administrator'],
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([$role]);

        return $user;
    }

    protected function createDirectorUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'direktur'],
            ['display_name' => 'Direktur', 'description' => 'Direktur Perusahaan'],
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([$role]);

        return $user;
    }

    protected function createDeveloperUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'developer'],
            ['display_name' => 'Developer', 'description' => 'Developer sistem'],
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([$role]);

        return $user;
    }
}
