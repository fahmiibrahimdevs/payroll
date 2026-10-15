<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeveloperUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'dev@payroll.com'],
            [
                'name' => 'Developer Demo',
                'password' => '31750321@dev',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles(['developer']);

        $logPermissionId = Permission::query()->where('name', 'logs-read')->value('id');
        $adminRole = Role::query()->where('name', 'admin')->first();

        if ($logPermissionId && $adminRole) {
            $adminRole->permissions()->detach($logPermissionId);
        }
    }
}
