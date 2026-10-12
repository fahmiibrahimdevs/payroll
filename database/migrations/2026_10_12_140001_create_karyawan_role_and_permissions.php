<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'karyawan'],
            [
                'display_name' => 'Karyawan',
                'description' => 'Pegawai / Karyawan Perusahaan',
            ]
        );

        $permissionNames = [
            'employees-read',
            'cash_advances-read',
            'cash_advances-create',
            'payrolls-read',
        ];

        $permissionIds = [];
        foreach ($permissionNames as $name) {
            $perm = Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => ucwords(str_replace(['-', '_'], ' ', $name)),
                    'description' => ucwords(str_replace(['-', '_'], ' ', $name)),
                ]
            );
            $permissionIds[] = $perm->id;
        }

        $role->permissions()->syncWithoutDetaching($permissionIds);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('name', 'karyawan')->first();
        if ($role) {
            $role->permissions()->detach();
            $role->delete();
        }
    }
};
