<?php

namespace Database\Seeders;

use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\InstallmentStatus;
use App\Enums\MaritalStatus;
use App\Enums\PayrollPeriodStatus;
use App\Enums\PtkpStatus;
use App\Enums\RepaymentScheme;
use App\Enums\TaxMethod;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the complete dummy testing seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Setup Users for 3 Essential Roles
            $adminUser = User::firstOrCreate(
                ['email' => 'fahmi@admin.com'],
                [
                    'name' => 'Fahmi Admin',
                    'password' => '1',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $adminUser->forceFill(['is_active' => true, 'email_verified_at' => now()])->save();
            $adminUser->syncRoles(['admin']);

            $direkturUser = User::firstOrCreate(
                ['email' => 'direktur@larashiz.com'],
                [
                    'name' => 'Direktur Utama',
                    'password' => 'password',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $direkturUser->forceFill(['is_active' => true, 'email_verified_at' => now()])->save();
            $direkturUser->syncRoles(['direktur']);

            $karyawanUser = User::firstOrCreate(
                ['email' => 'karyawan@larashiz.com'],
                [
                    'name' => 'Arif Fahmi Abdillah',
                    'password' => 'password',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $karyawanUser->forceFill(['is_active' => true, 'email_verified_at' => now()])->save();
            $karyawanUser->syncRoles(['karyawan']);

            $budiUser = User::firstOrCreate(
                ['email' => 'budi@larashiz.com'],
                [
                    'name' => 'Budi Santoso',
                    'password' => 'password',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $budiUser->forceFill(['is_active' => true, 'email_verified_at' => now()])->save();
            $budiUser->syncRoles(['karyawan']);

            $sitiUser = User::firstOrCreate(
                ['email' => 'siti@larashiz.com'],
                [
                    'name' => 'Siti Nurhaliza',
                    'password' => 'password',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $sitiUser->forceFill(['is_active' => true, 'email_verified_at' => now()])->save();
            $sitiUser->syncRoles(['karyawan']);

            // 2. Setup Departments
            $departments = [
                'IT' => Department::firstOrCreate(['name' => 'IT'], ['description' => 'Departemen Teknologi Informasi & Software']),
                'HRD' => Department::firstOrCreate(['name' => 'HRD'], ['description' => 'Departemen Sumber Daya Manusia']),
                'Finance' => Department::firstOrCreate(['name' => 'Keuangan'], ['description' => 'Departemen Finansial & Akuntansi']),
                'Ops' => Department::firstOrCreate(['name' => 'Operasional'], ['description' => 'Departemen Lapangan & Fasilitas']),
                'Hardware' => Department::firstOrCreate(['name' => 'Mikrokontroler'], ['description' => 'Departemen IoT & Hardware']),
                'Marketing' => Department::firstOrCreate(['name' => 'Marketing'], ['description' => 'Departemen Penjualan & Kemitraan']),
            ];

            // 3. Setup Positions
            $positions = [
                'Lead Dev' => Position::firstOrCreate(['name' => 'Senior Software Engineer'], ['description' => 'Arsitek & Pengembang Utama']),
                'Dev' => Position::firstOrCreate(['name' => 'Software Engineer'], ['description' => 'Pengembang Aplikasi']),
                'HR Specialist' => Position::firstOrCreate(['name' => 'HR Specialist'], ['description' => 'Spesialis Personalia & Penggajian']),
                'Finance Officer' => Position::firstOrCreate(['name' => 'Finance Officer'], ['description' => 'Staf Keuangan & Pembukuan']),
                'Ops Supervisor' => Position::firstOrCreate(['name' => 'Field Supervisor'], ['description' => 'Penyelia Lapangan']),
                'Hardware Tech' => Position::firstOrCreate(['name' => 'Hardware Technician'], ['description' => 'Teknisi Perangkat Keras']),
                'Marketing Exec' => Position::firstOrCreate(['name' => 'Marketing Executive'], ['description' => 'Eksekutif Pemasaran']),
                'Intern' => Position::firstOrCreate(['name' => 'Staff Magang'], ['description' => 'Peserta Praktik Magang']),
            ];

            // 4. Setup Employees
            $employeesData = [
                [
                    'user_id' => $karyawanUser->id,
                    'full_name' => 'Arif Fahmi Abdillah',
                    'department' => $departments['IT'],
                    'position' => $positions['Lead Dev'],
                    'employment_status' => EmploymentStatus::TETAP,
                    'join_date' => '2024-01-15',
                    'contract_end_date' => null,
                    'basic_salary' => 9500000,
                    'bank' => 'BCA',
                    'account_no' => '8210394812',
                    'ptkp' => PtkpStatus::K_1,
                    'gender' => Gender::MALE,
                ],
                [
                    'user_id' => $budiUser->id,
                    'full_name' => 'Budi Santoso',
                    'department' => $departments['Hardware'],
                    'position' => $positions['Hardware Tech'],
                    'employment_status' => EmploymentStatus::TETAP,
                    'join_date' => '2024-03-01',
                    'contract_end_date' => null,
                    'basic_salary' => 8000000,
                    'bank' => 'Mandiri',
                    'account_no' => '1370019283941',
                    'ptkp' => PtkpStatus::K_0,
                    'gender' => Gender::MALE,
                ],
                [
                    'user_id' => $sitiUser->id,
                    'full_name' => 'Siti Nurhaliza',
                    'department' => $departments['HRD'],
                    'position' => $positions['HR Specialist'],
                    'employment_status' => EmploymentStatus::TETAP,
                    'join_date' => '2024-05-10',
                    'contract_end_date' => null,
                    'basic_salary' => 6500000,
                    'bank' => 'BCA',
                    'account_no' => '7129384729',
                    'ptkp' => PtkpStatus::TK_0,
                    'gender' => Gender::FEMALE,
                ],
                [
                    'user_id' => null,
                    'full_name' => 'Rian Hidayat',
                    'department' => $departments['Finance'],
                    'position' => $positions['Finance Officer'],
                    'employment_status' => EmploymentStatus::TETAP,
                    'join_date' => '2024-08-01',
                    'contract_end_date' => null,
                    'basic_salary' => 6200000,
                    'bank' => 'BRI',
                    'account_no' => '029301928374829',
                    'ptkp' => PtkpStatus::TK_0,
                    'gender' => Gender::MALE,
                ],
                [
                    'user_id' => null,
                    'full_name' => 'Dimas Pratama',
                    'department' => $departments['Ops'],
                    'position' => $positions['Ops Supervisor'],
                    'employment_status' => EmploymentStatus::TETAP,
                    'join_date' => '2024-02-15',
                    'contract_end_date' => null,
                    'basic_salary' => 7500000,
                    'bank' => 'BNI',
                    'account_no' => '0839281749',
                    'ptkp' => PtkpStatus::K_2,
                    'gender' => Gender::MALE,
                ],
                [
                    'user_id' => null,
                    'full_name' => 'Eko Prasetyo',
                    'department' => $departments['IT'],
                    'position' => $positions['Dev'],
                    'employment_status' => EmploymentStatus::KONTRAK,
                    'join_date' => '2025-11-01',
                    'contract_end_date' => Carbon::now()->addDays(15)->toDateString(), // Memicu alert kontrak habis <= 30 hari!
                    'basic_salary' => 7000000,
                    'bank' => 'BCA',
                    'account_no' => '8492019482',
                    'ptkp' => PtkpStatus::TK_0,
                    'gender' => Gender::MALE,
                ],
                [
                    'user_id' => null,
                    'full_name' => 'Nadia Safitri',
                    'department' => $departments['Marketing'],
                    'position' => $positions['Marketing Exec'],
                    'employment_status' => EmploymentStatus::KONTRAK,
                    'join_date' => '2025-06-01',
                    'contract_end_date' => Carbon::now()->addMonths(8)->toDateString(),
                    'basic_salary' => 5500000,
                    'bank' => 'Mandiri',
                    'account_no' => '1420019283746',
                    'ptkp' => PtkpStatus::TK_0,
                    'gender' => Gender::FEMALE,
                ],
                [
                    'user_id' => null,
                    'full_name' => 'Ahmad Zaki',
                    'department' => $departments['Hardware'],
                    'position' => $positions['Hardware Tech'],
                    'employment_status' => EmploymentStatus::KONTRAK,
                    'join_date' => '2025-07-01',
                    'contract_end_date' => Carbon::now()->addMonths(5)->toDateString(),
                    'basic_salary' => 5200000,
                    'bank' => 'BRI',
                    'account_no' => '039401928374628',
                    'ptkp' => PtkpStatus::TK_0,
                    'gender' => Gender::MALE,
                ],
                [
                    'user_id' => null,
                    'full_name' => 'Putri Wulandari',
                    'department' => $departments['HRD'],
                    'position' => $positions['Intern'],
                    'employment_status' => EmploymentStatus::MAGANG,
                    'join_date' => '2026-08-01',
                    'contract_end_date' => Carbon::now()->addMonths(2)->toDateString(),
                    'basic_salary' => 2800000,
                    'bank' => 'BCA',
                    'account_no' => '8392019481',
                    'ptkp' => PtkpStatus::TK_0,
                    'gender' => Gender::FEMALE,
                ],
            ];

            $createdEmployees = [];
            foreach ($employeesData as $idx => $data) {
                $emp = Employee::updateOrCreate(
                    ['full_name' => $data['full_name']],
                    [
                        'user_id' => $data['user_id'],
                        'department_id' => $data['department']->id,
                        'position_id' => $data['position']->id,
                        'employment_status' => $data['employment_status'],
                        'status' => EmployeeStatus::ACTIVE,
                        'join_date' => $data['join_date'],
                        'contract_end_date' => $data['contract_end_date'],
                        'basic_salary' => $data['basic_salary'],
                    ]
                );

                $existingDetail = EmployeeDetail::where('employee_id', $emp->id)->first();
                $empCode = $existingDetail?->employee_code ?? ('EMP-'.str_pad((string) ($emp->id), 3, '0', STR_PAD_LEFT));

                EmployeeDetail::updateOrCreate(
                    ['employee_id' => $emp->id],
                    [
                        'employee_code' => $empCode,
                        'gender' => $data['gender'],
                        'birth_place' => 'Jakarta',
                        'birth_date' => '1995-05-15',
                        'address' => 'Jl. Merdeka No. '.($idx + 10).', Jakarta Selatan',
                        'phone' => '0812'.rand(10000000, 99999999),
                        'email' => strtolower(str_replace(' ', '.', $data['full_name'])).'@larashiz.com',
                        'bank_name' => $data['bank'],
                        'bank_account_number' => $data['account_no'],
                        'bank_account_name' => $data['full_name'],
                        'marital_status' => MaritalStatus::TK,
                        'dependents_count' => 0,
                        'ptkp_status' => $data['ptkp'],
                        'tax_method' => TaxMethod::GROSS,
                        'has_npwp' => true,
                        'npwp_number' => '09.283.472.1-019.000',
                        'nik' => '31710129384700'.str_pad((string) $emp->id, 2, '0', STR_PAD_LEFT),
                        'bpjs_kesehatan_number' => '00019283746'.str_pad((string) $emp->id, 2, '0', STR_PAD_LEFT),
                        'bpjs_ketenagakerjaan_number' => '192837465'.str_pad((string) $emp->id, 2, '0', STR_PAD_LEFT),
                    ]
                );

                $createdEmployees[$data['full_name']] = $emp;
            }

            // 5. Setup Kasbon (Cash Advances) dengan Beragam Status untuk Testing

            // Skenario A: 2 Pengajuan Kasbon Pending (Menunggu Persetujuan Admin)
            CashAdvance::updateOrCreate(
                ['reference_number' => 'CA-202610-001'],
                [
                    'employee_id' => $createdEmployees['Dimas Pratama']->id,
                    'amount' => 2000000,
                    'remaining_amount' => 2000000,
                    'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT,
                    'installment_count' => 4,
                    'monthly_installment' => 500000,
                    'disbursement_date' => '2026-10-15',
                    'reason' => 'Biaya perbaikan kendaraan operasional dinas',
                    'status' => CashAdvanceStatus::PENDING,
                    'submitted_by' => $adminUser->id,
                ]
            );

            CashAdvance::updateOrCreate(
                ['reference_number' => 'CA-202610-002'],
                [
                    'employee_id' => $createdEmployees['Arif Fahmi Abdillah']->id,
                    'amount' => 1500000,
                    'remaining_amount' => 1500000,
                    'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT,
                    'installment_count' => 3,
                    'monthly_installment' => 500000,
                    'disbursement_date' => '2026-10-16',
                    'reason' => 'Kebutuhan mendesak renovasi atap rumah',
                    'status' => CashAdvanceStatus::PENDING,
                    'submitted_by' => $karyawanUser->id,
                ]
            );

            // Skenario B: 1 Kasbon Sedang Berjalan (Active) - Lancar
            $caActive = CashAdvance::updateOrCreate(
                ['reference_number' => 'CA-202607-003'],
                [
                    'employee_id' => $createdEmployees['Budi Santoso']->id,
                    'amount' => 3000000,
                    'remaining_amount' => 2000000,
                    'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT,
                    'installment_count' => 3,
                    'monthly_installment' => 1000000,
                    'disbursement_date' => '2026-07-10',
                    'reason' => 'Pembelian suku cadang peralatan IoT lab',
                    'status' => CashAdvanceStatus::ACTIVE,
                    'submitted_by' => $adminUser->id,
                    'approved_by' => $adminUser->id,
                    'approved_at' => '2026-07-11 10:00:00',
                ]
            );

            // Buat installments untuk CA-Active
            CashAdvanceInstallment::updateOrCreate(
                ['cash_advance_id' => $caActive->id, 'installment_number' => 1],
                [
                    'amount' => 1000000,
                    'paid_amount' => 1000000,
                    'due_date' => '2026-08-25',
                    'paid_at' => '2026-08-25 15:00:00',
                    'status' => InstallmentStatus::PAID,
                ]
            );
            CashAdvanceInstallment::updateOrCreate(
                ['cash_advance_id' => $caActive->id, 'installment_number' => 2],
                [
                    'amount' => 1000000,
                    'paid_amount' => 0,
                    'due_date' => '2026-09-25',
                    'status' => InstallmentStatus::PENDING,
                ]
            );
            CashAdvanceInstallment::updateOrCreate(
                ['cash_advance_id' => $caActive->id, 'installment_number' => 3],
                [
                    'amount' => 1000000,
                    'paid_amount' => 0,
                    'due_date' => '2026-10-25',
                    'status' => InstallmentStatus::PENDING,
                ]
            );

            // Skenario C: 1 Kasbon Terlewat Jatuh Tempo (Overdue)
            $caOverdue = CashAdvance::updateOrCreate(
                ['reference_number' => 'CA-202608-004'],
                [
                    'employee_id' => $createdEmployees['Rian Hidayat']->id,
                    'amount' => 2000000,
                    'remaining_amount' => 2000000,
                    'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT,
                    'installment_count' => 2,
                    'monthly_installment' => 1000000,
                    'disbursement_date' => '2026-08-05',
                    'reason' => 'Biaya medis keluarga mendesak',
                    'status' => CashAdvanceStatus::ACTIVE,
                    'submitted_by' => $adminUser->id,
                    'approved_by' => $adminUser->id,
                    'approved_at' => '2026-08-06 09:00:00',
                ]
            );

            // Installment ke-1 jatuh tempo bulan September tapi belum lunas (overdue)
            CashAdvanceInstallment::updateOrCreate(
                ['cash_advance_id' => $caOverdue->id, 'installment_number' => 1],
                [
                    'amount' => 1000000,
                    'paid_amount' => 0,
                    'due_date' => '2026-09-20', // Jatuh tempo bulan lalu!
                    'status' => InstallmentStatus::PENDING,
                ]
            );
            CashAdvanceInstallment::updateOrCreate(
                ['cash_advance_id' => $caOverdue->id, 'installment_number' => 2],
                [
                    'amount' => 1000000,
                    'paid_amount' => 0,
                    'due_date' => '2026-10-20',
                    'status' => InstallmentStatus::PENDING,
                ]
            );

            // Skenario D: 1 Kasbon Lunas (PAID)
            $caPaid = CashAdvance::updateOrCreate(
                ['reference_number' => 'CA-202605-005'],
                [
                    'employee_id' => $createdEmployees['Siti Nurhaliza']->id,
                    'amount' => 1000000,
                    'remaining_amount' => 0,
                    'repayment_scheme' => RepaymentScheme::SALARY_SINGLE,
                    'installment_count' => 1,
                    'monthly_installment' => 1000000,
                    'disbursement_date' => '2026-05-10',
                    'reason' => 'Keperluan kursus sertifikasi HR',
                    'status' => CashAdvanceStatus::PAID,
                    'submitted_by' => $adminUser->id,
                    'approved_by' => $adminUser->id,
                    'approved_at' => '2026-05-11 11:00:00',
                ]
            );
            CashAdvanceInstallment::updateOrCreate(
                ['cash_advance_id' => $caPaid->id, 'installment_number' => 1],
                [
                    'amount' => 1000000,
                    'paid_amount' => 1000000,
                    'due_date' => '2026-06-25',
                    'paid_at' => '2026-06-25 14:00:00',
                    'status' => InstallmentStatus::PAID,
                ]
            );

            // 6. Setup Penggajian (Payroll): Riwayat 9 Bulan Lalu (Jan - Sep 2026) -> Status PAID
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];

            for ($m = 1; $m <= 9; $m++) {
                $refNo = 'PR-2026'.str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                $startDate = Carbon::create(2026, $m, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();

                $period = PayrollPeriod::updateOrCreate(
                    ['month' => $m, 'year' => 2026],
                    [
                        'reference_number' => $refNo,
                        'name' => 'Penggajian Periode '.$monthNames[$m].' 2026',
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'payment_date' => $endDate->toDateString(),
                        'status' => PayrollPeriodStatus::PAID,
                        'created_by' => $adminUser->id,
                        'submitted_by' => $adminUser->id,
                        'approved_by' => $direkturUser->id,
                        'notes' => 'Periode penggajian bulanan telah selesai diproses dan dibayarkan.',
                    ]
                );

                // Buat item slip gaji untuk setiap karyawan aktif
                $periodGross = 0;
                $periodDeductions = 0;
                $periodNet = 0;

                foreach ($createdEmployees as $emp) {
                    $basic = (float) $emp->basic_salary;
                    $allowances = 500000; // Tunjangan tetap
                    $taxAllowance = 150000;
                    $pph21 = round($basic * 0.05); // Estimasi PPh 21
                    $deductions = $pph21 + 100000; // Pajak + BPJS
                    $net = $basic + $allowances - $deductions;

                    $pItem = PayrollItem::updateOrCreate(
                        ['payroll_period_id' => $period->id, 'employee_id' => $emp->id],
                        [
                            'payslip_number' => 'SLIP-2026'.str_pad((string) $m, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $emp->id, 4, '0', STR_PAD_LEFT),
                            'basic_salary' => $basic,
                            'total_allowances' => $allowances,
                            'total_deductions' => $deductions,
                            'net_salary' => $net,
                            'tax_method' => TaxMethod::GROSS,
                            'ptkp_status' => PtkpStatus::TK_0,
                            'taxable_gross' => $basic + $allowances,
                            'tax_allowance' => $taxAllowance,
                            'pph21_amount' => $pph21,
                            'total_company_contributions' => 250000,
                            'bank_name' => $emp->detail?->bank_name ?? 'BCA',
                            'bank_account_number' => $emp->detail?->bank_account_number ?? '1234567890',
                            'bank_account_name' => $emp->full_name,
                            'payment_status' => 'paid',
                            'paid_at' => $endDate->toDateString(),
                            'approval_status' => 'approved',
                            'approved_by' => $direkturUser->id,
                            'approved_at' => $endDate->toDateString(),
                            'verified_by' => $adminUser->id,
                            'verified_at' => $endDate->toDateString(),
                        ]
                    );

                    $periodGross += ($basic + $allowances);
                    $periodDeductions += $deductions;
                    $periodNet += $net;
                }

                $period->update([
                    'total_basic_salary' => $periodGross - (count($createdEmployees) * 500000),
                    'total_allowances' => count($createdEmployees) * 500000,
                    'total_deductions' => $periodDeductions,
                    'total_net_salary' => $periodNet,
                    'total_company_contributions' => count($createdEmployees) * 250000,
                    'employee_count' => count($createdEmployees),
                ]);
            }

            // 7. Setup Penggajian Bulan Berjalan (Oktober 2026) -> Status DRAFT (Untuk Test Verifikasi Slip!)
            $octStart = Carbon::create(2026, 10, 1)->startOfMonth();
            $octEnd = $octStart->copy()->endOfMonth();

            $octPeriod = PayrollPeriod::updateOrCreate(
                ['month' => 10, 'year' => 2026],
                [
                    'reference_number' => 'PR-202610',
                    'name' => 'Penggajian Periode Oktober 2026',
                    'start_date' => $octStart->toDateString(),
                    'end_date' => $octEnd->toDateString(),
                    'status' => PayrollPeriodStatus::DRAFT,
                    'created_by' => $adminUser->id,
                    'notes' => 'Draf penggajian operasional bulan Oktober 2026 sedang dalam peninjauan & verifikasi.',
                ]
            );

            $octGross = 0;
            $octDeductions = 0;
            $octNet = 0;
            $empList = array_values($createdEmployees);

            // Verifikasi 6 karyawan pertama, tinggalkan 3 karyawan belum diverifikasi!
            foreach ($empList as $idx => $emp) {
                $basic = (float) $emp->basic_salary;
                $allowances = 500000;
                $pph21 = round($basic * 0.05);
                $deductions = $pph21 + 100000;
                $net = $basic + $allowances - $deductions;

                $isVerified = ($idx < 6); // 6 verif, 3 belum verif (draf)

                $pItem = PayrollItem::updateOrCreate(
                    ['payroll_period_id' => $octPeriod->id, 'employee_id' => $emp->id],
                    [
                        'payslip_number' => 'SLIP-202610-'.str_pad((string) $emp->id, 4, '0', STR_PAD_LEFT),
                        'basic_salary' => $basic,
                        'total_allowances' => $allowances,
                        'total_deductions' => $deductions,
                        'net_salary' => $net,
                        'tax_method' => TaxMethod::GROSS,
                        'ptkp_status' => PtkpStatus::TK_0,
                        'taxable_gross' => $basic + $allowances,
                        'pph21_amount' => $pph21,
                        'total_company_contributions' => 250000,
                        'bank_name' => $emp->detail?->bank_name ?? 'BCA',
                        'bank_account_number' => $emp->detail?->bank_account_number ?? '1234567890',
                        'bank_account_name' => $emp->full_name,
                        'payment_status' => 'unpaid',
                        'approval_status' => 'pending',
                        'verified_by' => $isVerified ? $adminUser->id : null,
                        'verified_at' => $isVerified ? now() : null,
                    ]
                );

                $octGross += ($basic + $allowances);
                $octDeductions += $deductions;
                $octNet += $net;
            }

            $octPeriod->update([
                'total_basic_salary' => $octGross - (count($empList) * 500000),
                'total_allowances' => count($empList) * 500000,
                'total_deductions' => $octDeductions,
                'total_net_salary' => $octNet,
                'total_company_contributions' => count($empList) * 250000,
                'employee_count' => count($empList),
            ]);
        });
    }
}
