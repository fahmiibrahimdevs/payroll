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
        $seeders = [
            LaratrustSeeder::class,
            CompanyProfileSeeder::class,
            SalaryComponentSeeder::class,
        ];

        /*
        if (class_exists(\Faker\Factory::class)) {
            $seeders[] = PostSeeder::class;
        }
        */

        $this->call($seeders);

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
            ['role' => 'direktur', 'name' => 'Direktur Demo', 'email' => 'direktur@payroll.com', 'password' => '31750321@direktur'],
            ['role' => 'admin', 'name' => 'Admin Demo', 'email' => 'admin@payroll.com', 'password' => '31750321@admin'],
            ['role' => 'karyawan', 'name' => 'Karyawan Demo', 'email' => 'karyawan@payroll.com', 'password' => '31750321@karyawan'],
        ] as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $account['password'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$account['role']]);
        }

        $this->call([
            // EmployeeSeeder::class,
            // AttendanceSeeder::class,
            DeveloperUserSeeder::class,
        ]);
    }
}
