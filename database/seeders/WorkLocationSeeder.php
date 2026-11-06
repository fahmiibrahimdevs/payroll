<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\WorkLocation;
use Illuminate\Database\Seeder;

class WorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kantorPusat = WorkLocation::updateOrCreate(
            ['name' => 'Kantor Pusat - Percetakan Subang'],
            [
                'address' => 'Jl. Sompi No. 12, Subang, Jawa Barat',
                'latitude' => -6.5715000,
                'longitude' => 107.7587000,
                'radius_meters' => 50,
                'is_active' => true,
            ]
        );

        WorkLocation::updateOrCreate(
            ['name' => 'Workshop Percetakan Subang'],
            [
                'address' => 'Jl. Otista No. 45, Subang, Jawa Barat',
                'latitude' => -6.5650000,
                'longitude' => 107.7620000,
                'radius_meters' => 100,
                'is_active' => true,
            ]
        );

        // Connect all existing employees without work_location_id to Kantor Pusat
        Employee::whereNull('work_location_id')->update([
            'work_location_id' => $kantorPusat->id,
        ]);
    }
}
