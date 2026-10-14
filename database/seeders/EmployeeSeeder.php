<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\TaxMethod;
use App\Enums\TaxRecipientType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    /**
     * Seed fictional employee profiles for a client demonstration.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $departments = [];
            foreach ([
                'Teknologi Informasi' => 'Departemen teknologi dan pengembangan sistem.',
                'Sumber Daya Manusia' => 'Departemen pengelolaan sumber daya manusia.',
                'Keuangan' => 'Departemen keuangan dan akuntansi.',
                'Operasional' => 'Departemen operasional dan layanan.',
                'Pemasaran' => 'Departemen pemasaran dan penjualan.',
            ] as $name => $description) {
                $departments[$name] = Department::firstOrCreate(['name' => $name], ['description' => $description]);
            }

            $positions = [];
            foreach ([
                'Software Engineer' => 'Pengembang aplikasi dan sistem internal.',
                'HR Manager' => 'Pengelola fungsi sumber daya manusia.',
                'Finance Analyst' => 'Analis keuangan dan pelaporan.',
                'Marketing Executive' => 'Pelaksana pemasaran dan pengembangan pelanggan.',
                'Operations Supervisor' => 'Koordinator kegiatan operasional.',
                'Customer Support Specialist' => 'Petugas layanan pelanggan.',
                'IT Intern' => 'Peserta magang pada tim teknologi informasi.',
                'HR Intern' => 'Peserta magang pada tim sumber daya manusia.',
            ] as $name => $description) {
                $positions[$name] = Position::firstOrCreate(['name' => $name], ['description' => $description]);
            }

            $employees = [
                [
                    'code' => 'DEMO-001', 'name' => 'DEMO - Andi Pratama',
                    'department' => 'Teknologi Informasi', 'position' => 'Software Engineer',
                    'employment_status' => EmploymentStatus::TETAP, 'join_date' => now()->subYears(3),
                    'contract_months' => null, 'salary' => 9_500_000, 'gender' => Gender::MALE,
                    'marital_status' => MaritalStatus::TK, 'dependents' => 0,
                    'tax_method' => TaxMethod::GROSS, 'tax_recipient_type' => TaxRecipientType::MonthlyEmployee,
                    'has_npwp' => true,
                ],
                [
                    'code' => 'DEMO-002', 'name' => 'DEMO - Dina Maharani',
                    'department' => 'Sumber Daya Manusia', 'position' => 'HR Manager',
                    'employment_status' => EmploymentStatus::TETAP, 'join_date' => now()->subYears(4),
                    'contract_months' => null, 'salary' => 12_500_000, 'gender' => Gender::FEMALE,
                    'marital_status' => MaritalStatus::K, 'dependents' => 2,
                    'tax_method' => TaxMethod::GROSS_UP, 'tax_recipient_type' => TaxRecipientType::MonthlyEmployee,
                    'has_npwp' => true,
                ],
                [
                    'code' => 'DEMO-003', 'name' => 'DEMO - Reza Firmansyah',
                    'department' => 'Keuangan', 'position' => 'Finance Analyst',
                    'employment_status' => EmploymentStatus::TETAP, 'join_date' => now()->subYears(2),
                    'contract_months' => null, 'salary' => 8_250_000, 'gender' => Gender::MALE,
                    'marital_status' => MaritalStatus::K, 'dependents' => 1,
                    'tax_method' => TaxMethod::GROSS, 'tax_recipient_type' => TaxRecipientType::MonthlyEmployee,
                    'has_npwp' => false,
                ],
                [
                    'code' => 'DEMO-004', 'name' => 'DEMO - Rani Oktavia',
                    'department' => 'Pemasaran', 'position' => 'Marketing Executive',
                    'employment_status' => EmploymentStatus::KONTRAK, 'join_date' => now()->subMonths(9),
                    'contract_months' => 5, 'salary' => 7_100_000, 'gender' => Gender::FEMALE,
                    'marital_status' => MaritalStatus::TK, 'dependents' => 1,
                    'tax_method' => TaxMethod::GROSS, 'tax_recipient_type' => TaxRecipientType::MonthlyEmployee,
                    'has_npwp' => true,
                ],
                [
                    'code' => 'DEMO-005', 'name' => 'DEMO - Galih Ramadhan',
                    'department' => 'Operasional', 'position' => 'Operations Supervisor',
                    'employment_status' => EmploymentStatus::KONTRAK, 'join_date' => now()->subMonths(7),
                    'contract_months' => 9, 'salary' => 6_500_000, 'gender' => Gender::MALE,
                    'marital_status' => MaritalStatus::K, 'dependents' => 0,
                    'tax_method' => TaxMethod::GROSS_UP, 'tax_recipient_type' => TaxRecipientType::MonthlyEmployee,
                    'has_npwp' => false,
                ],
                [
                    'code' => 'DEMO-006', 'name' => 'DEMO - Tania Putri',
                    'department' => 'Operasional', 'position' => 'Customer Support Specialist',
                    'employment_status' => EmploymentStatus::KONTRAK, 'join_date' => now()->subMonths(5),
                    'contract_months' => 7, 'salary' => 5_250_000, 'gender' => Gender::FEMALE,
                    'marital_status' => MaritalStatus::TK, 'dependents' => 0,
                    'tax_method' => TaxMethod::GROSS, 'tax_recipient_type' => TaxRecipientType::MonthlyEmployee,
                    'has_npwp' => true,
                ],
                [
                    'code' => 'DEMO-007', 'name' => 'DEMO - Fikri Alamsyah',
                    'department' => 'Teknologi Informasi', 'position' => 'IT Intern',
                    'employment_status' => EmploymentStatus::MAGANG, 'join_date' => now()->subMonths(2),
                    'contract_months' => 4, 'salary' => 2_800_000, 'gender' => Gender::MALE,
                    'marital_status' => MaritalStatus::TK, 'dependents' => 0,
                    'tax_method' => TaxMethod::GROSS, 'tax_recipient_type' => TaxRecipientType::OutsideScope,
                    'has_npwp' => false,
                ],
                [
                    'code' => 'DEMO-008', 'name' => 'DEMO - Nisa Rahma',
                    'department' => 'Sumber Daya Manusia', 'position' => 'HR Intern',
                    'employment_status' => EmploymentStatus::MAGANG, 'join_date' => now()->subMonths(1),
                    'contract_months' => 5, 'salary' => 2_500_000, 'gender' => Gender::FEMALE,
                    'marital_status' => MaritalStatus::TK, 'dependents' => 0,
                    'tax_method' => TaxMethod::GROSS, 'tax_recipient_type' => TaxRecipientType::OutsideScope,
                    'has_npwp' => false,
                ],
            ];

            foreach ($employees as $data) {
                $detail = EmployeeDetail::query()->where('employee_code', $data['code'])->first();
                $employee = $detail?->employee()->withTrashed()->first();
                $contractEndDate = $data['contract_months'] === null
                    ? null
                    : Carbon::today()->addMonths($data['contract_months'])->toDateString();

                if (! $employee) {
                    $employee = new Employee;
                } elseif ($employee->trashed()) {
                    $employee->restore();
                }

                $employee->fill([
                    'user_id' => $data['code'] === 'DEMO-001'
                        ? User::query()->where('email', 'karyawan@payroll.com')->value('id')
                        : null,
                    'department_id' => $departments[$data['department']]->id,
                    'position_id' => $positions[$data['position']]->id,
                    'full_name' => $data['name'],
                    'join_date' => Carbon::parse($data['join_date'])->toDateString(),
                    'contract_end_date' => $contractEndDate,
                    'employment_status' => $data['employment_status'],
                    'status' => EmployeeStatus::ACTIVE,
                    'basic_salary' => $data['salary'],
                    'photo_path' => null,
                ])->save();

                EmployeeDetail::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'employee_code' => $data['code'],
                        'gender' => $data['gender'],
                        'birth_place' => 'Jakarta',
                        'birth_date' => Carbon::parse('1998-05-15')->subYears((int) substr($data['code'], -1))->toDateString(),
                        'address' => 'Alamat fiktif untuk demonstrasi.',
                        'phone' => null,
                        'email' => strtolower(str_replace(' ', '.', $data['code'])).'@example.test',
                        'bank_name' => 'Bank Demo',
                        'bank_account_number' => null,
                        'bank_account_name' => $data['name'],
                        'marital_status' => $data['marital_status'],
                        'dependents_count' => $data['dependents'],
                        'tax_method' => $data['tax_method'],
                        'tax_recipient_type' => $data['tax_recipient_type'],
                        'npwp_number' => $data['has_npwp'] ? '00.000.000.0-000.000' : null,
                        'nik' => null,
                        'bpjs_kesehatan_number' => null,
                        'bpjs_ketenagakerjaan_number' => null,
                    ]
                );
            }
        });
    }
}
