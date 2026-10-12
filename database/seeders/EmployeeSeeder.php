<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds for departments, positions, and requested employees.
     */
    public function run(): void
    {
        // 1. Departments
        $deptNames = [
            'IT' => 'Departemen Information & Technology',
            'Mikrokontroler' => 'Departemen Mikrokontroler & Hardware',
            'Support' => 'Departemen Layanan Dukungan Teknis & Operasional',
            'HRD' => 'Departemen Sumber Daya Manusia (Human Resources)',
            'Mekanik' => 'Departemen Mekanik & Perawatan Mesin',
            'Programmer' => 'Departemen Pengembangan Aplikasi & Software',
        ];

        $departments = [];
        foreach ($deptNames as $name => $desc) {
            $departments[$name] = Department::firstOrCreate(
                ['name' => $name],
                ['description' => $desc]
            );
        }

        // 2. Positions (Jabatan)
        $positionNames = [
            'Manager' => 'Kepala Divisi / Pengelola Manajerial',
            'Supervisor' => 'Penyelia & Koordinator Tim',
            'Staff' => 'Staf Pelaksana & Operasional',
            'Magang / PKL' => 'Peserta Magang & Praktik Kerja Lapangan',
        ];

        $positions = [];
        foreach ($positionNames as $name => $desc) {
            $positions[$name] = Position::firstOrCreate(
                ['name' => $name],
                ['description' => $desc]
            );
        }

        // 3. Employees (Karyawan)
        $employeeData = [
            [
                'full_name' => 'Fahmi Ibrahim',
                'dept' => 'Mikrokontroler',
                'pos' => 'Supervisor',
                'phone' => '085691253593',
                'email' => 'fahmi.ibrahim@larashiz.com',
                'gender' => Gender::MALE->value,
                'code' => 'EMP-001',
                'salary' => 8500000,
                'status' => EmployeeStatus::ACTIVE,
                'emp_status' => EmploymentStatus::TETAP,
            ],
            [
                'full_name' => 'Dede Andri',
                'dept' => 'IT',
                'pos' => 'Manager',
                'phone' => '081234567891',
                'email' => 'dede.andri@larashiz.com',
                'gender' => Gender::MALE->value,
                'code' => 'EMP-002',
                'salary' => 12000000,
                'status' => EmployeeStatus::ACTIVE,
                'emp_status' => EmploymentStatus::TETAP,
            ],
            [
                'full_name' => 'Arif Fahmi Abdillah',
                'dept' => 'Mikrokontroler',
                'pos' => 'Staff',
                'phone' => '082233445566',
                'email' => 'karyawan@larashiz.com',
                'gender' => Gender::MALE->value,
                'code' => 'EMP-003',
                'salary' => 6000000,
                'status' => EmployeeStatus::ACTIVE,
                'emp_status' => EmploymentStatus::TETAP,
            ],
            [
                'full_name' => 'Purwanta',
                'dept' => 'Support',
                'pos' => 'Staff',
                'phone' => '083344556677',
                'email' => 'purwanta@larashiz.com',
                'gender' => Gender::MALE->value,
                'code' => 'EMP-004',
                'salary' => 5500000,
                'status' => EmployeeStatus::ACTIVE,
                'emp_status' => EmploymentStatus::TETAP,
            ],
        ];

        foreach ($employeeData as $data) {
            $matchedUser = User::where('email', $data['email'])->first();

            $employee = Employee::firstOrCreate(
                ['full_name' => $data['full_name']],
                [
                    'user_id' => $matchedUser?->id,
                    'department_id' => $departments[$data['dept']]->id,
                    'position_id' => $positions[$data['pos']]->id,
                    'join_date' => Carbon::now()->subMonths(12)->toDateString(),
                    'employment_status' => $data['emp_status'],
                    'status' => $data['status'],
                    'basic_salary' => $data['salary'],
                ]
            );

            // Update if existing to match new department/position/salary
            $employee->update([
                'user_id' => $matchedUser?->id ?? $employee->user_id,
                'department_id' => $departments[$data['dept']]->id,
                'position_id' => $positions[$data['pos']]->id,
                'employment_status' => $data['emp_status'],
                'status' => $data['status'],
                'basic_salary' => $data['salary'],
            ]);

            EmployeeDetail::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'employee_code' => $data['code'],
                    'gender' => $data['gender'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'address' => 'Jl. Kawasan Industri No. 12',
                    'bank_name' => 'BCA',
                    'bank_account_number' => '1234567890',
                    'bank_account_name' => $data['full_name'],
                ]
            );
        }
    }
}
