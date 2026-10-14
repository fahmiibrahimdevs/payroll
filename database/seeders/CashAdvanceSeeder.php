<?php

namespace Database\Seeders;

use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\RepaymentScheme;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\CashAdvancePayment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CashAdvanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure master Department & Position exist
        $itDept = Department::firstOrCreate(['name' => 'Teknologi Informasi'], ['description' => 'Departemen TI & Pengembangan Sistem']);
        $hrDept = Department::firstOrCreate(['name' => 'Sumber Daya Manusia'], ['description' => 'Departemen HR & Rekrutmen']);
        $finDept = Department::firstOrCreate(['name' => 'Keuangan & Akuntansi'], ['description' => 'Departemen Finansial']);

        $devPos = Position::firstOrCreate(['name' => 'Software Engineer'], ['description' => 'Pengembang Aplikasi']);
        $hrPos = Position::firstOrCreate(['name' => 'HR Specialist'], ['description' => 'Spesialis Kepegawaian']);
        $finPos = Position::firstOrCreate(['name' => 'Finance Staff'], ['description' => 'Staf Administrasi Keuangan']);

        // 2. Ensure Employees exist
        $emp1 = Employee::firstOrCreate(
            ['full_name' => 'Budi Santoso'],
            [
                'department_id' => $itDept->id,
                'position_id' => $devPos->id,
                'join_date' => Carbon::now()->subMonths(18)->toDateString(),
                'employment_status' => EmploymentStatus::TETAP,
                'status' => EmployeeStatus::ACTIVE,
                'basic_salary' => 8500000,
            ]
        );

        $emp2 = Employee::firstOrCreate(
            ['full_name' => 'Siti Nurhaliza'],
            [
                'department_id' => $hrDept->id,
                'position_id' => $hrPos->id,
                'join_date' => Carbon::now()->subMonths(12)->toDateString(),
                'employment_status' => EmploymentStatus::TETAP,
                'status' => EmployeeStatus::ACTIVE,
                'basic_salary' => 6500000,
            ]
        );

        $emp3 = Employee::firstOrCreate(
            ['full_name' => 'Rian Pratama'],
            [
                'department_id' => $finDept->id,
                'position_id' => $finPos->id,
                'join_date' => Carbon::now()->subMonths(6)->toDateString(),
                'employment_status' => EmploymentStatus::KONTRAK,
                'status' => EmployeeStatus::ACTIVE,
                'basic_salary' => 5000000,
            ]
        );

        $admin = User::where('email', 'admin@payroll.com')->first() ?? User::first();
        $direktur = User::where('email', 'direktur@payroll.com')->first() ?? $admin;

        if (! $admin) {
            return;
        }

        // 3. Seed Cash Advance 1: ACTIVE (Rp 3.000.000, 3x cicilan, sudah bayar 1x Rp 1.000.000)
        $ca1 = CashAdvance::firstOrCreate(
            ['reference_number' => 'KB-202610-0001'],
            [
                'employee_id' => $emp1->id,
                'amount' => 3000000,
                'remaining_amount' => 2000000,
                'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
                'installment_count' => 3,
                'monthly_installment' => 1000000,
                'disbursement_date' => Carbon::now()->subMonths(1)->toDateString(),
                'reason' => 'Renovasi mendesak tempat tinggal',
                'status' => CashAdvanceStatus::ACTIVE->value,
                'submitted_by' => $admin->id,
                'approved_by' => $direktur->id,
                'approved_at' => Carbon::now()->subMonths(1),
                'notes' => 'Disetujui untuk dicicil 3 bulan',
            ]
        );

        // Installments for CA 1
        $inst1 = CashAdvanceInstallment::firstOrCreate(
            ['cash_advance_id' => $ca1->id, 'installment_number' => 1],
            [
                'due_date' => Carbon::parse($ca1->disbursement_date)->addMonth()->toDateString(),
                'amount' => 1000000,
                'paid_amount' => 1000000,
                'status' => InstallmentStatus::PAID->value,
                'paid_at' => Carbon::now()->subDays(5),
            ]
        );

        CashAdvanceInstallment::firstOrCreate(
            ['cash_advance_id' => $ca1->id, 'installment_number' => 2],
            [
                'due_date' => Carbon::parse($ca1->disbursement_date)->addMonths(2)->toDateString(),
                'amount' => 1000000,
                'paid_amount' => 0,
                'status' => InstallmentStatus::PENDING->value,
            ]
        );

        CashAdvanceInstallment::firstOrCreate(
            ['cash_advance_id' => $ca1->id, 'installment_number' => 3],
            [
                'due_date' => Carbon::parse($ca1->disbursement_date)->addMonths(3)->toDateString(),
                'amount' => 1000000,
                'paid_amount' => 0,
                'status' => InstallmentStatus::PENDING->value,
            ]
        );

        // Payment for CA 1
        CashAdvancePayment::firstOrCreate(
            ['payment_number' => 'PAY-KB-202610-0001'],
            [
                'cash_advance_id' => $ca1->id,
                'cash_advance_installment_id' => $inst1->id,
                'payment_date' => Carbon::now()->subDays(5)->toDateString(),
                'amount' => 1000000,
                'payment_method' => PaymentMethod::SALARY_DEDUCTION->value,
                'notes' => 'Pemotongan gaji periode September 2026',
                'recorded_by' => $admin->id,
            ]
        );

        // 4. Seed Cash Advance 2: PENDING (Rp 1.500.000, Menunggu Persetujuan Direktur)
        CashAdvance::firstOrCreate(
            ['reference_number' => 'KB-202610-0002'],
            [
                'employee_id' => $emp2->id,
                'amount' => 1500000,
                'remaining_amount' => 1500000,
                'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
                'installment_count' => 2,
                'monthly_installment' => 750000,
                'disbursement_date' => Carbon::now()->toDateString(),
                'reason' => 'Keperluan pengobatan keluarga',
                'status' => CashAdvanceStatus::PENDING->value,
                'submitted_by' => $admin->id,
                'notes' => 'Pengajuan baru, butuh approval direktur',
            ]
        );

        // 5. Seed Cash Advance 3: PAID (Rp 1.000.000, Potong Gaji 1x Lunas)
        $ca3 = CashAdvance::firstOrCreate(
            ['reference_number' => 'KB-202610-0003'],
            [
                'employee_id' => $emp3->id,
                'amount' => 1000000,
                'remaining_amount' => 0,
                'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
                'installment_count' => 1,
                'monthly_installment' => 1000000,
                'disbursement_date' => Carbon::now()->subMonths(2)->toDateString(),
                'reason' => 'Biaya perbaikan kendaraan operasional',
                'status' => CashAdvanceStatus::PAID->value,
                'submitted_by' => $admin->id,
                'approved_by' => $direktur->id,
                'approved_at' => Carbon::now()->subMonths(2),
                'notes' => 'Lunas dipotong gaji',
            ]
        );

        $inst3 = CashAdvanceInstallment::firstOrCreate(
            ['cash_advance_id' => $ca3->id, 'installment_number' => 1],
            [
                'due_date' => Carbon::parse($ca3->disbursement_date)->addMonth()->toDateString(),
                'amount' => 1000000,
                'paid_amount' => 1000000,
                'status' => InstallmentStatus::PAID->value,
                'paid_at' => Carbon::now()->subMonth(),
            ]
        );

        CashAdvancePayment::firstOrCreate(
            ['payment_number' => 'PAY-KB-202610-0002'],
            [
                'cash_advance_id' => $ca3->id,
                'cash_advance_installment_id' => $inst3->id,
                'payment_date' => Carbon::now()->subMonth()->toDateString(),
                'amount' => 1000000,
                'payment_method' => PaymentMethod::SALARY_DEDUCTION->value,
                'notes' => 'Lunas potong gaji bulan Agustus',
                'recorded_by' => $admin->id,
            ]
        );

        // 6. Seed Cash Advance 4: REJECTED (Rp 5.000.000)
        CashAdvance::firstOrCreate(
            ['reference_number' => 'KB-202610-0004'],
            [
                'employee_id' => $emp3->id,
                'amount' => 5000000,
                'remaining_amount' => 5000000,
                'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
                'installment_count' => 5,
                'monthly_installment' => 1000000,
                'disbursement_date' => Carbon::now()->subDays(10)->toDateString(),
                'reason' => 'Keperluan konsumtif pribadi',
                'status' => CashAdvanceStatus::REJECTED->value,
                'submitted_by' => $admin->id,
                'approved_by' => $direktur->id,
                'approved_at' => Carbon::now()->subDays(9),
                'rejection_reason' => 'Nominal melebihi batas ketentuan untuk masa kerja karyawan kontrak',
                'notes' => 'Ditolak direktur',
            ]
        );
    }
}
