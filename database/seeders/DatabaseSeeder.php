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

        $admin = User::firstOrCreate(
            ['email' => 'fahmi@admin.com'],
            [
                'name' => 'Fahmi Admin',
                'password' => '1',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->forceFill(['is_active' => true, 'email_verified_at' => $admin->email_verified_at ?? now()])->save();
        $admin->syncRoles(['admin']);

        $direktur = User::firstOrCreate(
            ['email' => 'direktur@larashiz.com'],
            [
                'name' => 'Direktur Utama',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $direktur->forceFill(['is_active' => true, 'email_verified_at' => $direktur->email_verified_at ?? now()])->save();
        $direktur->syncRoles(['direktur']);

        $developer = User::firstOrCreate(
            ['email' => 'developer@larashiz.com'],
            [
                'name' => 'System Developer',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $developer->forceFill(['is_active' => true, 'email_verified_at' => $developer->email_verified_at ?? now()])->save();
        $developer->syncRoles(['developer']);

        $user = User::firstOrCreate(
            ['email' => 'fahmi@user.com'],
            [
                'name' => 'Fahmi User',
                'password' => '1',
                'is_active' => false,
            ]
        );
        $user->syncRoles(['user']);

        $karyawan = User::firstOrCreate(
            ['email' => 'karyawan@larashiz.com'],
            [
                'name' => 'Arif Fahmi Abdillah',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $karyawan->forceFill(['is_active' => true, 'email_verified_at' => $karyawan->email_verified_at ?? now()])->save();
        $karyawan->syncRoles(['karyawan']);

        $this->call([
            EmployeeSeeder::class,
        ]);
    }
}
