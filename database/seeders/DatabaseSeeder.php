<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            LaratrustSeeder::class,
            PostSeeder::class,
            SalaryComponentSeeder::class,
        ]);

        User::query()
            ->whereIn('email', [
                'fahmi@admin.com',
                'direktur@larashiz.com',
                'developer@larashiz.com',
                'fahmi@user.com',
                'karyawan@larashiz.com',
            ])
            ->delete();

        foreach ([
            ['role' => 'direktur', 'name' => 'Direktur Demo', 'email' => 'direktur@payroll.com'],
            ['role' => 'admin', 'name' => 'Admin Demo', 'email' => 'admin@payroll.com'],
            ['role' => 'karyawan', 'name' => 'Karyawan Demo', 'email' => 'karyawan@payroll.com'],
        ] as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => '1',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$account['role']]);
        }

        $this->call([
            EmployeeSeeder::class,
        ]);
    }
}
