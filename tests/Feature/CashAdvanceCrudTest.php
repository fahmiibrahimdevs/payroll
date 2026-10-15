<?php

namespace Tests\Feature;

use App\Enums\CashAdvancePaymentStatus;
use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\RepaymentScheme;
use App\Livewire\CashAdvances\Index;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\CashAdvancePayment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\CashAdvanceService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CashAdvanceCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected Employee $employee;

    protected User $adminUser;

    protected User $direkturUser;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::firstOrCreate(['name' => 'IT Department']);
        $position = Position::firstOrCreate(['name' => 'Developer']);

        $this->employee = Employee::firstOrCreate(
            ['full_name' => 'Test Employee'],
            [
                'department_id' => $department->id,
                'position_id' => $position->id,
                'join_date' => Carbon::now()->subYear()->toDateString(),
                'employment_status' => EmploymentStatus::TETAP,
                'status' => EmployeeStatus::ACTIVE,
                'basic_salary' => 6000000,
            ]
        );

        $this->adminUser = User::factory()->create();
        $this->direkturUser = User::factory()->create();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin', 'description' => 'Administrator']);
        $direkturRole = Role::firstOrCreate(['name' => 'direktur'], ['display_name' => 'Direktur', 'description' => 'Direktur']);
        $this->adminUser->syncRoles([$adminRole]);
        $this->direkturUser->syncRoles([$direkturRole]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('cash-advances.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_cash_advances_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('cash-advances.index'));

        $response->assertOk();
        $response->assertSee('Kasbon Karyawan');
    }

    public function test_can_create_cash_advance_and_logs_activity(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(Index::class)
            ->call('create')
            ->assertDispatched('open-modal')
            ->set('form.employee_id', $this->employee->id)
            ->set('form.amount', 3000000)
            ->set('form.repayment_scheme', RepaymentScheme::SALARY_INSTALLMENT->value)
            ->set('form.installment_count', 3)
            ->set('form.disbursement_date', Carbon::today()->format('Y-m-d'))
            ->set('form.reason', 'Biaya perbaikan rumah')
            ->set('form.notes', 'Mohon disetujui segera')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'cashAdvanceModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('cash_advances', [
            'employee_id' => $this->employee->id,
            'amount' => 3000000,
            'remaining_amount' => 3000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 3,
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->adminUser->id,
            'module' => 'cash_advances',
            'action' => 'CREATE',
            'level' => 'info',
        ]);
    }

    public function test_validation_errors_when_creating_cash_advance(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(Index::class)
            ->set('form.employee_id', '')
            ->set('form.amount', 0)
            ->set('form.reason', '')
            ->call('store')
            ->assertHasErrors(['form.employee_id', 'form.amount', 'form.reason']);
    }

    public function test_prompts_confirmation_and_allows_creating_cash_advance_if_employee_already_has_active_advance(): void
    {
        $this->actingAs($this->adminUser);

        // Existing active cash advance
        CashAdvance::create([
            'reference_number' => 'KB-202610-EXIST',
            'employee_id' => $this->employee->id,
            'amount' => 2000000,
            'remaining_amount' => 2000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pinjaman Pertama',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        // Trying to submit a second cash advance prompts confirmation
        $test = Livewire::test(Index::class)
            ->set('form.employee_id', $this->employee->id)
            ->set('form.amount', 1000000)
            ->set('form.repayment_scheme', RepaymentScheme::SALARY_SINGLE->value)
            ->set('form.installment_count', 1)
            ->set('form.disbursement_date', Carbon::today()->format('Y-m-d'))
            ->set('form.reason', 'Pinjaman Kedua')
            ->call('store')
            ->assertDispatched('swal:confirm');

        $this->assertDatabaseMissing('cash_advances', [
            'reason' => 'Pinjaman Kedua',
        ]);

        // When confirmed, the second cash advance is created
        $test->call('confirmStore')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('cash_advances', [
            'employee_id' => $this->employee->id,
            'reason' => 'Pinjaman Kedua',
            'amount' => 1000000,
        ]);
    }

    public function test_can_create_cash_advance_after_previous_advance_is_fully_paid(): void
    {
        $this->actingAs($this->adminUser);

        // Fully paid previous cash advance
        CashAdvance::create([
            'reference_number' => 'KB-202610-PAID',
            'employee_id' => $this->employee->id,
            'amount' => 2000000,
            'remaining_amount' => 0,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 2000000,
            'disbursement_date' => Carbon::today()->subMonths(2)->toDateString(),
            'reason' => 'Pinjaman Sudah Lunas',
            'status' => CashAdvanceStatus::PAID->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        // Should be allowed to create a new one
        Livewire::test(Index::class)
            ->set('form.employee_id', $this->employee->id)
            ->set('form.amount', 1500000)
            ->set('form.repayment_scheme', RepaymentScheme::SALARY_SINGLE->value)
            ->set('form.installment_count', 1)
            ->set('form.disbursement_date', Carbon::today()->format('Y-m-d'))
            ->set('form.reason', 'Pengajuan Baru Setelah Lunas')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'cashAdvanceModal');
    }

    public function test_can_update_pending_cash_advance(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-TEST1',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Alasan Awal',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        Livewire::test(Index::class)
            ->call('edit', $cashAdvance->id)
            ->assertSet('isEditMode', true)
            ->assertSet('editingCashAdvanceId', $cashAdvance->id)
            ->set('form.amount', 1500000)
            ->set('form.reason', 'Alasan Diperbarui')
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'cashAdvanceModal')
            ->assertDispatched('swal:alert');

        $this->assertDatabaseHas('cash_advances', [
            'id' => $cashAdvance->id,
            'amount' => 1500000,
            'remaining_amount' => 1500000,
            'reason' => 'Alasan Diperbarui',
        ]);
    }

    public function test_director_can_approve_cash_advance_and_generate_installments(): void
    {
        $this->actingAs($this->direkturUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-TEST2',
            'employee_id' => $this->employee->id,
            'amount' => 2000000,
            'remaining_amount' => 2000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Keperluan mendesak',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        Livewire::test(Index::class)
            ->call('openApproval', $cashAdvance->id)
            ->assertSet('selectedCashAdvanceId', $cashAdvance->id)
            ->set('approvalNotes', 'Disetujui untuk dicicil 2 bulan')
            ->call('approve')
            ->assertDispatched('close-modal', id: 'cashAdvanceApprovalModal')
            ->assertDispatched('swal:alert');

        $cashAdvance->refresh();
        $this->assertEquals(CashAdvanceStatus::ACTIVE, $cashAdvance->status);
        $this->assertEquals($this->direkturUser->id, $cashAdvance->approved_by);
        $this->assertNotNull($cashAdvance->approved_at);

        // Verify installments generated
        $this->assertCount(2, $cashAdvance->installments);
        $this->assertEquals(1000000, $cashAdvance->installments->first()->amount);
        $this->assertEquals(InstallmentStatus::PENDING, $cashAdvance->installments->first()->status);
    }

    public function test_director_can_reject_cash_advance_with_reason(): void
    {
        $this->actingAs($this->direkturUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-TEST3',
            'employee_id' => $this->employee->id,
            'amount' => 5000000,
            'remaining_amount' => 5000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 5000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pengajuan besar',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        Livewire::test(Index::class)
            ->call('openApproval', $cashAdvance->id)
            ->set('rejectionReason', 'Nominal terlalu besar untuk 1x potong gaji')
            ->call('reject')
            ->assertDispatched('close-modal', id: 'cashAdvanceApprovalModal')
            ->assertDispatched('swal:alert');

        $cashAdvance->refresh();
        $this->assertEquals(CashAdvanceStatus::REJECTED, $cashAdvance->status);
        $this->assertEquals('Nominal terlalu besar untuk 1x potong gaji', $cashAdvance->rejection_reason);
    }

    public function test_admin_cannot_approve_cash_advance(): void
    {
        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-AUTH-1',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Uji otorisasi',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $this->expectException(AuthorizationException::class);

        app(CashAdvanceService::class)->approveCashAdvance($cashAdvance, $this->adminUser);
    }

    public function test_user_without_role_cannot_view_or_mutate_cash_advances(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cash-advances.index'))
            ->assertForbidden();
    }

    public function test_ledger_actions_are_rendered_for_their_authorized_role_only(): void
    {
        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-FRONTEND-1',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Uji visibilitas tombol',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->set('activeMainTab', 'ledger')
            ->assertDontSee('Persetujuan Direktur')
            ->assertSee('Edit Data')
            ->assertSee('Hapus Pengajuan');

        $this->actingAs($this->direkturUser);

        Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->set('activeMainTab', 'ledger')
            ->assertSee('Persetujuan Direktur')
            ->assertDontSee('Edit Data')
            ->assertDontSee('Hapus Pengajuan');

        $this->assertDatabaseHas('cash_advances', ['id' => $cashAdvance->id]);
    }

    public function test_direktur_cannot_edit_update_or_delete_cash_advance(): void
    {
        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-DIR-RESTRICT',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pengajuan kasbon uji direktur',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->direkturUser);

        $this->assertFalse($this->direkturUser->can('create', CashAdvance::class));
        $this->assertFalse($this->direkturUser->can('update', $cashAdvance));
        $this->assertFalse($this->direkturUser->can('delete', $cashAdvance));
        $this->assertTrue($this->direkturUser->can('approve', $cashAdvance));
        $this->assertTrue($this->direkturUser->can('reject', $cashAdvance));

        Livewire::test(Index::class)
            ->call('edit', $cashAdvance->id)
            ->assertForbidden();

        Livewire::test(Index::class)
            ->call('delete', $cashAdvance->id)
            ->assertForbidden();
    }

    public function test_payment_rejects_installment_from_another_cash_advance(): void
    {
        $firstAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-TAMPER-1',
            'employee_id' => $this->employee->id,
            'amount' => 2000000,
            'remaining_amount' => 2000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Kasbon pertama',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);
        $secondAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-TAMPER-2',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Kasbon kedua',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);
        $foreignInstallment = CashAdvanceInstallment::create([
            'cash_advance_id' => $secondAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->addMonth()->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(CashAdvanceService::class)->recordPayment($firstAdvance, [
            'amount' => 500000,
            'payment_date' => Carbon::today()->toDateString(),
            'payment_method' => PaymentMethod::CASH->value,
            'cash_advance_installment_id' => $foreignInstallment->id,
        ], $this->adminUser);
    }

    public function test_payment_rechecks_balance_after_cash_advance_is_locked(): void
    {
        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-TAMPER-3',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 500000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Saldo berubah',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(CashAdvanceService::class)->recordPayment($cashAdvance, [
            'amount' => 750000,
            'payment_date' => Carbon::today()->toDateString(),
            'payment_method' => PaymentMethod::CASH->value,
        ], $this->adminUser);

        $this->assertDatabaseCount('cash_advance_payments', 0);
    }

    public function test_double_approval_is_rejected_without_duplicate_installments(): void
    {
        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-AUTH-2',
            'employee_id' => $this->employee->id,
            'amount' => 2000000,
            'remaining_amount' => 2000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Approval ganda',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);
        $service = app(CashAdvanceService::class);

        $service->approveCashAdvance($cashAdvance, $this->direkturUser);

        $this->expectException(\InvalidArgumentException::class);
        $service->approveCashAdvance($cashAdvance, $this->direkturUser);

        $this->assertDatabaseCount('cash_advance_installments', 2);
    }

    public function test_transaction_number_sequences_are_unique_per_period(): void
    {
        $service = app(CashAdvanceService::class);
        $date = Carbon::create(2026, 10, 4);

        $referenceOne = $service->generateReferenceNumber($date);
        $referenceTwo = $service->generateReferenceNumber($date);
        $paymentOne = $service->generatePaymentNumber($date);
        $paymentTwo = $service->generatePaymentNumber($date);

        $this->assertNotSame($referenceOne, $referenceTwo);
        $this->assertNotSame($paymentOne, $paymentTwo);
        $this->assertSame('KB-202610-0001', $referenceOne);
        $this->assertSame('KB-202610-0002', $referenceTwo);
        $this->assertSame('PAY-KB-202610-0001', $paymentOne);
        $this->assertSame('PAY-KB-202610-0002', $paymentTwo);
    }

    public function test_can_record_manual_payment_and_update_installment_status(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-TEST4',
            'employee_id' => $this->employee->id,
            'amount' => 2000000,
            'remaining_amount' => 2000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pengajuan cicilan',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $inst1 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->addMonth()->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 2,
            'due_date' => Carbon::today()->addMonths(2)->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        // Pay 1st installment (1.000.000)
        Livewire::test(Index::class)
            ->call('openPayment', $cashAdvance->id)
            ->assertDispatched('open-modal', id: 'cashAdvancePaymentModal')
            ->set('paymentForm.amount', 1000000)
            ->set('paymentForm.payment_date', Carbon::today()->format('Y-m-d'))
            ->set('paymentForm.payment_method', PaymentMethod::BANK_TRANSFER->value)
            ->set('paymentForm.notes', 'Transfer BCA 12345')
            ->call('storePayment')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'cashAdvancePaymentModal')
            ->assertDispatched('swal:alert');

        $cashAdvance->refresh();
        $this->assertEquals(2000000, $cashAdvance->remaining_amount);

        $inst1->refresh();
        $this->assertEquals(0, $inst1->paid_amount);

        $payment1 = CashAdvancePayment::where('cash_advance_id', $cashAdvance->id)->first();
        $this->assertNotNull($payment1);
        $this->assertEquals(CashAdvancePaymentStatus::PENDING, $payment1->status);

        // Direktur approves payment #1
        $this->actingAs($this->direkturUser);
        app(CashAdvanceService::class)->approvePayment($payment1, $this->direkturUser);

        $cashAdvance->refresh();
        $this->assertEquals(1000000, $cashAdvance->remaining_amount);
        $this->assertEquals(CashAdvanceStatus::ACTIVE, $cashAdvance->status);

        $inst1->refresh();
        $this->assertEquals(InstallmentStatus::PAID, $inst1->status);
        $this->assertEquals(1000000, $inst1->paid_amount);

        $this->assertDatabaseHas('cash_advance_payments', [
            'id' => $payment1->id,
            'status' => CashAdvancePaymentStatus::APPROVED->value,
            'approved_by' => $this->direkturUser->id,
        ]);

        // Pay 2nd installment to fully clear cash advance
        $this->actingAs($this->adminUser);
        Livewire::test(Index::class)
            ->call('openPayment', $cashAdvance->id)
            ->set('paymentForm.amount', 1000000)
            ->set('paymentForm.payment_date', Carbon::today()->format('Y-m-d'))
            ->set('paymentForm.payment_method', PaymentMethod::CASH->value)
            ->call('storePayment')
            ->assertHasNoErrors();

        $payment2 = CashAdvancePayment::where('cash_advance_id', $cashAdvance->id)
            ->where('id', '!=', $payment1->id)
            ->first();
        $this->assertNotNull($payment2);
        $this->assertEquals(CashAdvancePaymentStatus::PENDING, $payment2->status);

        // Direktur approves payment #2
        $this->actingAs($this->direkturUser);
        app(CashAdvanceService::class)->approvePayment($payment2, $this->direkturUser);

        $cashAdvance->refresh();
        $this->assertEquals(0, $cashAdvance->remaining_amount);
        $this->assertEquals(CashAdvanceStatus::PAID, $cashAdvance->status);
    }

    public function test_can_record_payment_with_proof_document_upload(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-PROOF',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pinjaman untuk berobat',
            'status' => CashAdvanceStatus::APPROVED->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $file = UploadedFile::fake()->image('bukti_transfer.jpg');

        Livewire::test(Index::class)
            ->call('openPayment', $cashAdvance->id)
            ->set('paymentForm.amount', 1000000)
            ->set('paymentForm.payment_date', Carbon::today()->format('Y-m-d'))
            ->set('paymentForm.payment_method', PaymentMethod::BANK_TRANSFER->value)
            ->set('paymentForm.proof_document', $file)
            ->set('paymentForm.notes', 'Transfer BCA 777')
            ->call('storePayment')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', id: 'cashAdvancePaymentModal');

        $payment = $cashAdvance->payments()->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->proof_document);
        Storage::disk('local')->assertExists($payment->proof_document);
    }

    public function test_can_delete_pending_cash_advance(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-TEST5',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pengajuan batal',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        Livewire::test(Index::class)
            ->call('delete', $cashAdvance->id, app(CashAdvanceService::class))
            ->assertDispatched('swal:alert');

        $this->assertDatabaseMissing('cash_advances', [
            'id' => $cashAdvance->id,
        ]);
    }

    public function test_active_cash_advance_summary_for_employee(): void
    {
        CashAdvance::create([
            'reference_number' => 'KB-202610-ACTIVE1',
            'employee_id' => $this->employee->id,
            'amount' => 2000000,
            'remaining_amount' => 1500000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Kasbon berjalan',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $service = app(CashAdvanceService::class);
        $summary = $service->getActiveCashAdvanceSummaryByEmployee($this->employee->id);

        $this->assertTrue($summary['has_active']);
        $this->assertEquals(1, $summary['count']);
        $this->assertEquals(1500000, $summary['total_remaining']);
    }

    public function test_can_select_target_installment_and_pay_early(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-TARGET',
            'employee_id' => $this->employee->id,
            'amount' => 3000000,
            'remaining_amount' => 3000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 3,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Kasbon 3 bulan',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $inst1 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->addMonth()->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $inst2 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 2,
            'due_date' => Carbon::today()->addMonths(2)->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        // Target installment #2 directly
        Livewire::test(Index::class)
            ->call('openPayment', $cashAdvance->id)
            ->call('selectPaymentTarget', 'installment', $inst2->id)
            ->set('paymentForm.payment_method', PaymentMethod::BANK_TRANSFER->value)
            ->call('storePayment')
            ->assertHasNoErrors();

        $payment = CashAdvancePayment::where('cash_advance_id', $cashAdvance->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(CashAdvancePaymentStatus::PENDING, $payment->status);

        // Approve payment by Direktur
        $this->actingAs($this->direkturUser);
        app(CashAdvanceService::class)->approvePayment($payment, $this->direkturUser);

        $inst2->refresh();
        $this->assertEquals(InstallmentStatus::PAID, $inst2->status);
        $this->assertEquals(1000000, $inst2->paid_amount);

        // Ledger should show sub-badge
        $service = app(CashAdvanceService::class);
        $ledger = $service->getEmployeeLedger($this->employee->id);
        $this->assertNotEmpty($ledger->items());
    }

    public function test_can_toggle_accordion_and_open_payment_for_specific_installment(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-EXPAND',
            'employee_id' => $this->employee->id,
            'amount' => 2000000,
            'remaining_amount' => 2000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pinjaman untuk renovasi',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $inst = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->addMonth()->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->call('openPaymentForInstallment', $cashAdvance->id, $inst->id)
            ->assertDispatched('open-modal', id: 'cashAdvancePaymentModal')
            ->assertSet('paymentForm.payment_target', 'installment')
            ->assertSet('paymentForm.cash_advance_installment_id', $inst->id)
            ->assertSet('paymentForm.amount', 1000000);
    }

    public function test_can_aggregate_monthly_statements_and_switch_tabs(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-STMT',
            'employee_id' => $this->employee->id,
            'amount' => 1500000,
            'remaining_amount' => 1500000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 3,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::parse('2026-10-01')->toDateString(),
            'reason' => 'Beli laptop bekas',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $inst1 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::parse('2026-11-01')->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $inst2 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 2,
            'due_date' => Carbon::parse('2026-12-01')->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $service = app(CashAdvanceService::class);
        $statements = $service->getEmployeeMonthlyStatements($this->employee->id);

        $this->assertEquals(2, $statements['unpaid_count']);
        $this->assertNotNull($statements['next_statement']);
        $this->assertEquals('2026-11', $statements['next_statement']['month_key']);
        $this->assertEquals(1000000, $statements['total_unpaid_amount']);

        // Test Livewire component interactions with statements tab and modals
        Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->assertSet('activeMainTab', 'statements')
            ->call('switchMainTab', 'ledger')
            ->assertSet('activeMainTab', 'ledger')
            ->call('switchMainTab', 'statements')
            ->assertSet('activeMainTab', 'statements')
            ->call('switchStatementSubTab', 'paid')
            ->assertSet('statementSubTab', 'paid')
            ->set('selectedEmployeeId', null)
            ->assertSet('statementSubTab', 'unpaid')
            ->set('selectedEmployeeId', $this->employee->id)
            ->call('switchStatementSubTab', 'unpaid')
            ->assertSet('statementSubTab', 'unpaid')
            ->call('showStatementDetail', '2026-11')
            ->assertDispatched('open-modal', id: 'statementDetailModal')
            ->assertSet('selectedStatementDetail.month_key', '2026-11');
    }

    public function test_can_perform_early_settlement_for_selected_months(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-EARLY',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::parse('2026-10-01')->toDateString(),
            'reason' => 'Perbaikan kendaraan dinas',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $inst1 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::parse('2026-11-01')->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $inst2 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 2,
            'due_date' => Carbon::parse('2026-12-01')->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->call('openEarlyPaymentModal', '2026-11')
            ->assertDispatched('open-modal', id: 'earlyPaymentModal')
            ->assertSet('selectedMonthKeysForPayment', ['2026-11'])
            ->call('toggleMonthSelection', '2026-12')
            ->assertSet('selectedMonthKeysForPayment', ['2026-11', '2026-12'])
            ->set('earlyPaymentDate', Carbon::today()->format('Y-m-d'))
            ->set('earlyPaymentMethod', PaymentMethod::BANK_TRANSFER->value)
            ->set('earlyPaymentNotes', 'Pelunasan 2 bulan sekaligus')
            ->call('storeEarlyPayment')
            ->assertHasNoErrors()
            ->assertDispatched('swal:confirm')
            ->call('confirmEarlyPayment')
            ->assertDispatched('close-modal', id: 'earlyPaymentModal')
            ->assertDispatched('swal:alert');

        $payments = CashAdvancePayment::where('cash_advance_id', $cashAdvance->id)->get();
        $this->assertNotEmpty($payments);
        foreach ($payments as $payment) {
            $this->assertEquals(CashAdvancePaymentStatus::PENDING, $payment->status);
            $this->actingAs($this->direkturUser);
            app(CashAdvanceService::class)->approvePayment($payment, $this->direkturUser);
        }

        $inst1->refresh();
        $inst2->refresh();
        $cashAdvance->refresh();

        $this->assertEquals(InstallmentStatus::PAID, $inst1->status);
        $this->assertEquals(InstallmentStatus::PAID, $inst2->status);
        $this->assertEquals(0, $cashAdvance->remaining_amount);
        $this->assertEquals(CashAdvanceStatus::PAID, $cashAdvance->status);
    }

    public function test_can_perform_partial_early_settlement_for_month(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-PARTIAL',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::parse('2026-10-01')->toDateString(),
            'reason' => 'Keperluan mendadak',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $inst1 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::parse('2026-11-01')->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $inst2 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 2,
            'due_date' => Carbon::parse('2026-12-01')->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        // Pay 300,000 for November 2026 (tagihan 500,000 -> sisa 200,000)
        Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->call('openEarlyPaymentModal', '2026-11')
            ->assertSet('selectedMonthKeysForPayment', ['2026-11'])
            ->assertSet('earlyPaymentAmount', 500000.0)
            ->set('earlyPaymentAmount', 300000)
            ->set('earlyPaymentDate', Carbon::today()->format('Y-m-d'))
            ->set('earlyPaymentMethod', PaymentMethod::CASH->value)
            ->set('earlyPaymentNotes', 'Bayar cicilan sebagian 300rb')
            ->call('storeEarlyPayment')
            ->assertHasNoErrors()
            ->assertDispatched('swal:confirm')
            ->call('confirmEarlyPayment')
            ->assertDispatched('close-modal', id: 'earlyPaymentModal')
            ->assertDispatched('swal:alert');

        $payment = CashAdvancePayment::where('cash_advance_id', $cashAdvance->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(CashAdvancePaymentStatus::PENDING, $payment->status);

        $this->actingAs($this->direkturUser);
        app(CashAdvanceService::class)->approvePayment($payment, $this->direkturUser);

        $inst1->refresh();
        $inst2->refresh();
        $cashAdvance->refresh();

        // Installment 1 must be partially paid with 300k paid and 200k remaining
        $this->assertEquals(InstallmentStatus::PARTIALLY_PAID, $inst1->status);
        $this->assertEquals(300000, $inst1->paid_amount);

        // Cash advance remaining balance must reduce by 300k (from 1,000,000 to 700,000)
        $this->assertEquals(700000, $cashAdvance->remaining_amount);
        $this->assertEquals(CashAdvanceStatus::ACTIVE, $cashAdvance->status);

        // A payment record must exist in history
        $this->assertDatabaseHas('cash_advance_payments', [
            'cash_advance_id' => $cashAdvance->id,
            'cash_advance_installment_id' => $inst1->id,
            'amount' => 300000,
            'payment_method' => PaymentMethod::CASH->value,
            'notes' => 'Bayar cicilan sebagian 300rb',
        ]);
    }

    public function test_early_payment_waterfall_allocation_smart_select(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'CA-WATERFALL-01',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'reason' => 'Waterfall settlement test',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::parse('2026-10-01')->toDateString(),
            'remaining_amount' => 1000000,
            'submitted_by' => $this->adminUser->id,
            'approved_at' => Carbon::now(),
            'approved_by' => $this->direkturUser->id,
        ]);

        $inst1 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::parse('2026-11-01')->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $inst2 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 2,
            'due_date' => Carbon::parse('2026-12-01')->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        // Open modal defaults to selecting month 1
        $component = Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->call('openEarlyPaymentModal');

        $this->assertEquals(['2026-11'], $component->get('selectedMonthKeysForPayment'));

        // Toggle month 2: both month 1 and month 2 are now selected independently
        $component->call('toggleMonthSelection', '2026-12');
        $this->assertEquals(['2026-11', '2026-12'], $component->get('selectedMonthKeysForPayment'));
        $this->assertEquals(1000000.0, $component->get('earlyPaymentAmount'));

        // Input 800,000: waterfall covers month 1 (500k full) and month 2 (300k partial, sisa 200k)
        $component->set('earlyPaymentAmount', 800000);

        // Verify computed waterfall allocation property
        $waterfall = $component->instance()->earlyPaymentWaterfall;
        $this->assertEquals(500000.0, $waterfall['allocations']['2026-11']['allocated']);
        $this->assertTrue($waterfall['allocations']['2026-11']['is_full']);
        $this->assertEquals(0.0, $waterfall['allocations']['2026-11']['remaining_after']);

        $this->assertEquals(300000.0, $waterfall['allocations']['2026-12']['allocated']);
        $this->assertTrue($waterfall['allocations']['2026-12']['is_partial']);
        $this->assertEquals(200000.0, $waterfall['allocations']['2026-12']['remaining_after']);

        // Test independent uncheck: unchecking 2026-11 leaves 2026-12 still checked!
        $component->call('toggleMonthSelection', '2026-11');
        $this->assertEquals(['2026-12'], $component->get('selectedMonthKeysForPayment'));
        $this->assertEquals(500000.0, $component->get('earlyPaymentAmount'));

        // Check 2026-11 back
        $component->call('toggleMonthSelection', '2026-11');
        $this->assertEquals(['2026-11', '2026-12'], $component->get('selectedMonthKeysForPayment'));
        $this->assertEquals(1000000.0, $component->get('earlyPaymentAmount'));

        // Setting an amount exceeding total (e.g. 3,000,000 when total is 1,000,000) triggers live validation error
        $component->set('earlyPaymentAmount', 3000000)
            ->assertHasErrors(['earlyPaymentAmount']);

        // Reset to 800,000 for actual submission
        $component->set('earlyPaymentAmount', 800000)
            ->assertHasNoErrors(['earlyPaymentAmount']);

        // Now submit early payment
        $component->set('earlyPaymentDate', Carbon::today()->format('Y-m-d'))
            ->set('earlyPaymentMethod', PaymentMethod::CASH->value)
            ->set('earlyPaymentNotes', 'Smart waterfall payment 800k')
            ->call('storeEarlyPayment')
            ->assertHasNoErrors()
            ->assertDispatched('swal:confirm')
            ->call('confirmEarlyPayment')
            ->assertDispatched('close-modal', id: 'earlyPaymentModal')
            ->assertDispatched('swal:alert');

        $payments = CashAdvancePayment::where('cash_advance_id', $cashAdvance->id)->get();
        $this->assertNotEmpty($payments);
        foreach ($payments as $payment) {
            $this->assertEquals(CashAdvancePaymentStatus::PENDING, $payment->status);
            $this->actingAs($this->direkturUser);
            app(CashAdvanceService::class)->approvePayment($payment, $this->direkturUser);
        }

        $inst1->refresh();
        $inst2->refresh();
        $cashAdvance->refresh();

        // Month 1 must be fully PAID
        $this->assertEquals(InstallmentStatus::PAID, $inst1->status);
        $this->assertEquals(500000, $inst1->paid_amount);

        // Month 2 must be PARTIALLY_PAID with 300k paid and 200k remaining
        $this->assertEquals(InstallmentStatus::PARTIALLY_PAID, $inst2->status);
        $this->assertEquals(300000, $inst2->paid_amount);
    }

    public function test_can_select_and_pay_non_consecutive_months_independently(): void
    {
        $this->actingAs($this->adminUser);

        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-NONCONSEC',
            'employee_id' => $this->employee->id,
            'amount' => 900000,
            'remaining_amount' => 900000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 3,
            'monthly_installment' => 300000,
            'disbursement_date' => Carbon::parse('2026-10-01')->toDateString(),
            'reason' => 'Non-consecutive months test',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $inst1 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::parse('2026-11-01')->toDateString(),
            'amount' => 300000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $inst2 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 2,
            'due_date' => Carbon::parse('2026-12-01')->toDateString(),
            'amount' => 300000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $inst3 = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 3,
            'due_date' => Carbon::parse('2027-01-01')->toDateString(),
            'amount' => 300000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        // Open modal (defaults to Nov 2026)
        $component = Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->call('openEarlyPaymentModal')
            ->assertSet('selectedMonthKeysForPayment', ['2026-11']);

        // Click Jan 2027: ONLY Jan 2027 is added, Dec 2026 is NOT added!
        $component->call('toggleMonthSelection', '2027-01');
        $this->assertEquals(['2026-11', '2027-01'], $component->get('selectedMonthKeysForPayment'));
        $this->assertEquals(600000.0, $component->get('earlyPaymentAmount'));

        // Toggle Dec 2026 (add it)
        $component->call('toggleMonthSelection', '2026-12');
        $this->assertEquals(['2026-11', '2026-12', '2027-01'], $component->get('selectedMonthKeysForPayment'));
        $this->assertEquals(900000.0, $component->get('earlyPaymentAmount'));

        // Uncheck Dec 2026 only: Jan 2027 and Nov 2026 must remain checked!
        $component->call('toggleMonthSelection', '2026-12');
        $this->assertEquals(['2026-11', '2027-01'], $component->get('selectedMonthKeysForPayment'));
        $this->assertEquals(600000.0, $component->get('earlyPaymentAmount'));

        // Pay the selected months (Nov and Jan, skipping Dec)
        $component->set('earlyPaymentDate', Carbon::today()->format('Y-m-d'))
            ->set('earlyPaymentMethod', PaymentMethod::CASH->value)
            ->call('storeEarlyPayment')
            ->assertHasNoErrors()
            ->assertDispatched('swal:confirm')
            ->call('confirmEarlyPayment')
            ->assertDispatched('close-modal', id: 'earlyPaymentModal');

        $payments = CashAdvancePayment::where('cash_advance_id', $cashAdvance->id)->get();
        $this->assertNotEmpty($payments);
        foreach ($payments as $payment) {
            $this->assertEquals(CashAdvancePaymentStatus::PENDING, $payment->status);
            $this->actingAs($this->direkturUser);
            app(CashAdvanceService::class)->approvePayment($payment, $this->direkturUser);
        }

        $inst1->refresh();
        $inst2->refresh();
        $inst3->refresh();
        $cashAdvance->refresh();

        // Nov 2026 is PAID
        $this->assertEquals(InstallmentStatus::PAID, $inst1->status);
        // Dec 2026 is still PENDING and 0 paid!
        $this->assertEquals(InstallmentStatus::PENDING, $inst2->status);
        $this->assertEquals(0, $inst2->paid_amount);
        // Jan 2027 is PAID
        $this->assertEquals(InstallmentStatus::PAID, $inst3->status);

        // Total cash advance remaining is 300,000 (Dec unpaid)
        $this->assertEquals(300000, $cashAdvance->remaining_amount);
    }

    public function test_employee_ledger_is_strictly_sorted_chronologically_by_date_and_time(): void
    {
        $today = Carbon::today()->toDateString();

        // Advance 1: at 10:00 (1,000,000)
        $adv1 = CashAdvance::create([
            'reference_number' => 'KB-202610-TIME-1',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => $today,
            'reason' => 'Pinjaman Jam 10',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);
        DB::table('cash_advances')->where('id', $adv1->id)->update(['created_at' => Carbon::parse($today.' 10:00:00')]);

        // Advance 2: at 13:00 (500,000)
        $adv2 = CashAdvance::create([
            'reference_number' => 'KB-202610-TIME-2',
            'employee_id' => $this->employee->id,
            'amount' => 500000,
            'remaining_amount' => 500000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 500000,
            'disbursement_date' => $today,
            'reason' => 'Pinjaman Jam 13',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);
        DB::table('cash_advances')->where('id', $adv2->id)->update(['created_at' => Carbon::parse($today.' 13:00:00')]);

        // Payment 1: at 11:00 (400,000)
        $pay1 = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-TIME-1',
            'cash_advance_id' => $adv1->id,
            'payment_date' => $today,
            'amount' => 400000,
            'payment_method' => PaymentMethod::CASH->value,
            'recorded_by' => $this->adminUser->id,
        ]);
        DB::table('cash_advance_payments')->where('id', $pay1->id)->update(['created_at' => Carbon::parse($today.' 11:00:00')]);

        // Payment 2: at 12:00 (200,000)
        $pay2 = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-TIME-2',
            'cash_advance_id' => $adv1->id,
            'payment_date' => $today,
            'amount' => 200000,
            'payment_method' => PaymentMethod::CASH->value,
            'recorded_by' => $this->adminUser->id,
        ]);
        DB::table('cash_advance_payments')->where('id', $pay2->id)->update(['created_at' => Carbon::parse($today.' 12:00:00')]);

        $service = app(CashAdvanceService::class);
        $ledger = $service->getEmployeeLedger($this->employee->id);
        $items = collect($ledger->items());

        $this->assertCount(4, $items);

        // Sequence must strictly be: 10:00 (adv1) -> 11:00 (pay1) -> 12:00 (pay2) -> 13:00 (adv2)
        $this->assertEquals($adv1->reference_number, $items[0]['reference_number']);
        $this->assertEquals(1000000.0, $items[0]['saldo_akhir']);

        $this->assertEquals($pay1->payment_number, $items[1]['reference_number']);
        $this->assertEquals(600000.0, $items[1]['saldo_akhir']);

        $this->assertEquals($pay2->payment_number, $items[2]['reference_number']);
        $this->assertEquals(400000.0, $items[2]['saldo_akhir']);

        $this->assertEquals($adv2->reference_number, $items[3]['reference_number']);
        $this->assertEquals(900000.0, $items[3]['saldo_akhir']);
    }

    public function test_employee_ledger_displays_creator_name_and_disetujui_status(): void
    {
        $this->actingAs($this->adminUser);

        $adv = CashAdvance::create([
            'reference_number' => 'KB-202610-CREATOR',
            'employee_id' => $this->employee->id,
            'amount' => 500000,
            'remaining_amount' => 500000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Keperluan mendesak',
            'status' => CashAdvanceStatus::APPROVED->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $pay = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-CREATOR',
            'cash_advance_id' => $adv->id,
            'payment_date' => Carbon::today()->toDateString(),
            'amount' => 500000,
            'payment_method' => PaymentMethod::CASH->value,
            'recorded_by' => $this->direkturUser->id,
        ]);

        $service = app(CashAdvanceService::class);
        $ledger = $service->getEmployeeLedger($this->employee->id);
        $items = collect($ledger->items());

        $advItem = $items->firstWhere('reference_number', $adv->reference_number);
        $payItem = $items->firstWhere('reference_number', $pay->payment_number);

        $this->assertNotNull($advItem);
        $this->assertEquals('Disetujui', $advItem['status_label']);
        $this->assertEquals($this->adminUser->name, $advItem['creator_name']);

        $this->assertNotNull($payItem);
        $this->assertEquals('Disetujui', $payItem['status_label']);
        $this->assertEquals($this->direkturUser->name, $payItem['creator_name']);

        // Assert rendered Livewire component contains creator name and Disetujui badge
        Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->call('switchMainTab', 'ledger')
            ->assertSeeHtml('DIBUAT OLEH')
            ->assertSee($this->adminUser->name)
            ->assertSee($this->direkturUser->name)
            ->assertSee('Disetujui')
            ->assertDontSee('Disetujui oleh Direktur');
    }

    public function test_employee_ledger_displays_pending_status_for_unapproved_payment(): void
    {
        $this->actingAs($this->adminUser);

        $adv = CashAdvance::create([
            'reference_number' => 'KB-202610-LEDGER-PEND',
            'employee_id' => $this->employee->id,
            'amount' => 500000,
            'remaining_amount' => 500000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pinjaman untuk tes status pending ledger',
            'status' => CashAdvanceStatus::APPROVED->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $pay = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-PENDING',
            'cash_advance_id' => $adv->id,
            'payment_date' => Carbon::today()->toDateString(),
            'amount' => 200000,
            'payment_method' => PaymentMethod::CASH->value,
            'status' => CashAdvancePaymentStatus::PENDING,
            'recorded_by' => $this->adminUser->id,
        ]);

        $service = app(CashAdvanceService::class);
        $ledger = $service->getEmployeeLedger($this->employee->id);
        $items = collect($ledger->items());

        $payItem = $items->firstWhere('reference_number', $pay->payment_number);
        $this->assertNotNull($payItem);
        $this->assertEquals('pending', $payItem['status']);
        $this->assertEquals('Menunggu ACC', $payItem['status_label']);
        $this->assertEquals('badge badge-warning', $payItem['status_badge']);

        // Assert rendered Livewire shows Menunggu ACC badge in ledger
        Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->call('switchMainTab', 'ledger')
            ->assertSee('Menunggu ACC')
            ->assertSee('PAY-KB-202610-PENDING');

        // When direktur approves, ledger updates to Disetujui
        $this->actingAs($this->direkturUser);
        $service->approvePayment($pay, $this->direkturUser);

        $ledgerUpdated = $service->getEmployeeLedger($this->employee->id);
        $itemsUpdated = collect($ledgerUpdated->items());
        $payItemUpdated = $itemsUpdated->firstWhere('reference_number', $pay->payment_number);
        $this->assertEquals('approved', $payItemUpdated['status']);
        $this->assertEquals('Disetujui', $payItemUpdated['status_label']);
        $this->assertEquals('badge badge-info', $payItemUpdated['status_badge']);
    }

    public function test_default_view_shows_all_employees_recap_and_switches_to_specific_employee(): void
    {
        $this->actingAs($this->adminUser);

        // Employee has an active cash advance
        CashAdvance::create([
            'reference_number' => 'KB-202610-RECAP-1',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 750000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 4,
            'monthly_installment' => 250000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pinjaman Recap Test',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $component = Livewire::test(Index::class)
            ->assertSet('selectedEmployeeId', null)
            ->assertSee('Semua Karyawan')
            ->assertSee('Sisa kasbon aktif')
            ->assertSee('ACC Pengajuan Kasbon')
            ->assertSee($this->employee->full_name)
            ->assertSee('IT Department')
            ->assertSee('Developer')
            ->assertSee('SISA KASBON')
            ->assertSee('Rp 750.000');

        // Select the employee: switches to employee mode
        $component->call('selectEmployee', $this->employee->id)
            ->assertSet('selectedEmployeeId', $this->employee->id)
            ->assertDispatched('employee-selected', id: $this->employee->id)
            ->assertSee('Tagihan Bulanan')
            ->assertSee('Semua Karyawan');

        // Clear selection: returns to all employees recap mode
        $component->call('clearSelectedEmployee')
            ->assertSet('selectedEmployeeId', null)
            ->assertDispatched('employee-selected', id: null)
            ->assertSee('Sisa kasbon aktif');
    }

    public function test_all_employees_recap_displays_totals_and_supports_department_position_search(): void
    {
        $this->actingAs($this->adminUser);

        $financeDept = Department::firstOrCreate(['name' => 'Finance Dept']);
        $accountantPos = Position::firstOrCreate(['name' => 'Staff Accountant']);

        $employeeWithoutAdvance = Employee::firstOrCreate(
            ['full_name' => 'Siti Nurhaliza'],
            [
                'department_id' => $financeDept->id,
                'position_id' => $accountantPos->id,
                'join_date' => Carbon::now()->subMonths(6)->toDateString(),
                'employment_status' => EmploymentStatus::KONTRAK,
                'status' => EmployeeStatus::ACTIVE,
                'basic_salary' => 5000000,
            ]
        );

        $component = Livewire::test(Index::class)
            ->assertSet('selectedEmployeeId', null)
            ->assertSee('TOTAL PINJAMAN')
            ->assertSee('SUDAH DIBAYAR')
            ->assertSee('SISA KASBON')
            ->assertSee('POTONGAN BULAN INI')
            ->assertSee($this->employee->full_name)
            ->assertSee('Siti Nurhaliza')
            ->assertSee('Finance Dept')
            ->assertSee('Staff Accountant');

        // Test search by position
        $component->set('search', 'Accountant')
            ->assertSee('Finance Dept &bull; Staff Accountant', false)
            ->assertDontSee('IT Department &bull; Developer', false);

        // Test search by department
        $component->set('search', 'IT Department')
            ->assertSee('IT Department &bull; Developer', false)
            ->assertDontSee('Finance Dept &bull; Staff Accountant', false);
    }

    public function test_direktur_can_approve_pending_payment_via_livewire(): void
    {
        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-APPV',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pinjaman untuk disetujui bayar',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $inst = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->addMonth()->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $payment = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-APPV',
            'cash_advance_id' => $cashAdvance->id,
            'cash_advance_installment_id' => $inst->id,
            'payment_date' => Carbon::today()->toDateString(),
            'amount' => 1000000,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            'status' => CashAdvancePaymentStatus::PENDING,
            'notes' => 'Transfer manual',
            'recorded_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->direkturUser);

        Livewire::test(Index::class)
            ->call('approvePayment', $payment->id)
            ->assertDispatched('swal:alert');

        $payment->refresh();
        $this->assertEquals(CashAdvancePaymentStatus::APPROVED, $payment->status);
        $this->assertEquals($this->direkturUser->id, $payment->approved_by);
        $this->assertNotNull($payment->approved_at);

        $cashAdvance->refresh();
        $this->assertEquals(0, $cashAdvance->remaining_amount);
        $this->assertEquals(CashAdvanceStatus::PAID, $cashAdvance->status);

        $inst->refresh();
        $this->assertEquals(InstallmentStatus::PAID, $inst->status);
    }

    public function test_direktur_can_reject_pending_payment_with_reason_via_livewire(): void
    {
        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-REJ',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pinjaman untuk ditolak bayar',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $inst = CashAdvanceInstallment::create([
            'cash_advance_id' => $cashAdvance->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->addMonth()->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $payment = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-REJ',
            'cash_advance_id' => $cashAdvance->id,
            'cash_advance_installment_id' => $inst->id,
            'payment_date' => Carbon::today()->toDateString(),
            'amount' => 1000000,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            'status' => CashAdvancePaymentStatus::PENDING,
            'notes' => 'Transfer manual mencurigakan',
            'recorded_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->direkturUser);

        Livewire::test(Index::class)
            ->call('rejectPayment', $payment->id, 'Bukti transfer tidak valid atau tidak terverifikasi di mutasi')
            ->assertDispatched('swal:alert');

        $payment->refresh();
        $this->assertEquals(CashAdvancePaymentStatus::REJECTED, $payment->status);
        $this->assertEquals('Bukti transfer tidak valid atau tidak terverifikasi di mutasi', $payment->rejection_reason);
        $this->assertNull($payment->approved_by);

        // Balances must remain unchanged
        $cashAdvance->refresh();
        $this->assertEquals(1000000, $cashAdvance->remaining_amount);
        $this->assertEquals(CashAdvanceStatus::ACTIVE, $cashAdvance->status);

        $inst->refresh();
        $this->assertEquals(InstallmentStatus::PENDING, $inst->status);
        $this->assertEquals(0, $inst->paid_amount);
    }

    public function test_non_direktur_cannot_approve_or_reject_payment(): void
    {
        $cashAdvance = CashAdvance::create([
            'reference_number' => 'KB-202610-FORBID',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_SINGLE->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pinjaman tes otorisasi',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $payment = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-FORBID',
            'cash_advance_id' => $cashAdvance->id,
            'payment_date' => Carbon::today()->toDateString(),
            'amount' => 1000000,
            'payment_method' => PaymentMethod::CASH->value,
            'status' => CashAdvancePaymentStatus::PENDING,
            'recorded_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser);

        $this->expectException(AuthorizationException::class);
        app(CashAdvanceService::class)->approvePayment($payment, $this->adminUser);
    }

    public function test_cash_advances_table_groups_employees_by_department(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(Index::class)
            ->assertOk()
            ->assertSee('table-group-header')
            ->assertSee('DEPARTEMEN IT DEPARTMENT')
            ->assertSee('Karyawan', false);
    }

    public function test_employee_cash_advance_summary_aggregates_installments_without_loading_them(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));

        $advance = CashAdvance::create([
            'reference_number' => 'KB-202610-SUMMARY',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 600000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => '2026-09-01',
            'reason' => 'Kasbon untuk tes ringkasan',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        CashAdvanceInstallment::create([
            'cash_advance_id' => $advance->id,
            'installment_number' => 1,
            'due_date' => '2026-09-10',
            'amount' => 400000,
            'paid_amount' => 400000,
            'status' => InstallmentStatus::PAID->value,
            'paid_at' => Carbon::parse('2026-09-10'),
        ]);

        CashAdvanceInstallment::create([
            'cash_advance_id' => $advance->id,
            'installment_number' => 2,
            'due_date' => '2026-10-10',
            'amount' => 600000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $summary = app(CashAdvanceService::class)
            ->getEmployeesCashAdvanceSummary(filter: 'active_balance')
            ->getCollection()
            ->firstWhere('id', $this->employee->id);

        $this->assertNotNull($summary);
        $this->assertSame(1000000.0, $summary['initial_amount']);
        $this->assertSame(400000.0, $summary['paid_amount']);
        $this->assertSame(600000.0, $summary['remaining_amount']);
        $this->assertSame(600000.0, $summary['monthly_deduction']);
        $this->assertSame(1, $summary['paid_installments']);
        $this->assertSame(2, $summary['total_installments']);
    }

    public function test_summary_stats_calculates_4_metrics_and_renders_correctly(): void
    {
        $this->actingAs($this->adminUser);

        // Create pending advance
        CashAdvance::create([
            'reference_number' => 'KB-202610-PND1',
            'employee_id' => $this->employee->id,
            'amount' => 1500000,
            'remaining_amount' => 1500000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 3,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pengajuan Test Pending',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $service = app(CashAdvanceService::class);
        $stats = $service->getSummaryStats();

        $this->assertEquals(1, $stats['total_pending_count']);
        $this->assertEquals(1500000.0, $stats['pending_amount']);
        $this->assertArrayHasKey('active_paid_percentage', $stats);
        $this->assertArrayHasKey('payroll_deduction_this_month', $stats);
        $this->assertArrayHasKey('attention_employees_count', $stats);
        $this->assertArrayHasKey('attention_subtitle', $stats);

        Livewire::test(Index::class)
            ->assertOk()
            ->assertSee('Sisa kasbon aktif')
            ->assertSee('ACC Pengajuan Kasbon')
            ->assertSee('ACC Pelunasan Kasbon')
            ->assertSee('Perlu perhatian')
            ->assertSee('1 pengajuan')
            ->assertSee('Rp 1.500.000')
            ->assertSee('Tinjau')
            ->assertSeeHtml('id="pendingPaymentReviewModal"');
    }

    public function test_direktur_can_approve_cash_advance_directly_from_review_modal(): void
    {
        $advance = CashAdvance::create([
            'reference_number' => 'KB-202610-PND2',
            'employee_id' => $this->employee->id,
            'amount' => 900000,
            'remaining_amount' => 900000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 3,
            'monthly_installment' => 300000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Butuh dana cepat',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->direkturUser);

        Livewire::test(Index::class)
            ->assertSee('Diajukan oleh:')
            ->assertSee($this->adminUser->name)
            ->call('approveDirect', $advance->id, 'Disetujui langsung oleh direktur')
            ->assertDispatched('swal:alert');

        $this->assertEquals(CashAdvanceStatus::ACTIVE, $advance->fresh()->status);
        $this->assertEquals($this->direkturUser->id, $advance->fresh()->approved_by);
        $this->assertEquals(3, $advance->fresh()->installments()->count());
    }

    public function test_direktur_can_reject_cash_advance_directly_from_review_modal(): void
    {
        $advance = CashAdvance::create([
            'reference_number' => 'KB-202610-PND3',
            'employee_id' => $this->employee->id,
            'amount' => 600000,
            'remaining_amount' => 600000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 300000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pengajuan ditolak',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->direkturUser);

        Livewire::test(Index::class)
            ->call('rejectDirect', $advance->id, 'Plafon belum mencukupi')
            ->assertDispatched('swal:alert');

        $this->assertEquals(CashAdvanceStatus::REJECTED, $advance->fresh()->status);
        $this->assertEquals('Plafon belum mencukupi', $advance->fresh()->rejection_reason);
    }

    public function test_admin_cannot_approve_or_reject_cash_advance_directly(): void
    {
        $advance = CashAdvance::create([
            'reference_number' => 'KB-202610-PND4',
            'employee_id' => $this->employee->id,
            'amount' => 500000,
            'remaining_amount' => 500000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 1,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pengajuan admin test',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(Index::class)
            ->call('approveDirect', $advance->id)
            ->assertDispatched('swal:alert');

        $this->assertEquals(CashAdvanceStatus::PENDING, $advance->fresh()->status);
    }

    public function test_recap_displays_charts_card_and_pelunasan_progress_bar_accurately(): void
    {
        $this->actingAs($this->adminUser);

        $advance = CashAdvance::create([
            'reference_number' => 'KB-202610-CHART-1',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 750000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 4,
            'monthly_installment' => 250000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Chart & Pelunasan Test',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
        ]);

        // 1 installment paid, 3 pending
        CashAdvanceInstallment::create([
            'cash_advance_id' => $advance->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->subMonth()->toDateString(),
            'amount' => 250000,
            'paid_amount' => 250000,
            'status' => InstallmentStatus::PAID->value,
        ]);

        for ($i = 2; $i <= 4; $i++) {
            CashAdvanceInstallment::create([
                'cash_advance_id' => $advance->id,
                'installment_number' => $i,
                'due_date' => Carbon::today()->addMonths($i - 1)->toDateString(),
                'amount' => 250000,
                'paid_amount' => 0,
                'status' => InstallmentStatus::PENDING->value,
            ]);
        }

        Livewire::test(Index::class)
            ->assertSet('selectedEmployeeId', null)
            ->assertSee('PELUNASAN')
            ->assertSee('1 dari 4 cicilan')
            ->assertSee('Analisis Kasbon')
            ->assertSee('Diberikan vs Dibayar')
            ->assertSee('Top 5 Kasbon Terbesar')
            ->assertSee('Distribusi Departemen')
            ->assertViewHas('chartData', function ($chartData) {
                return isset($chartData['monthly'])
                    && isset($chartData['top_debtors'])
                    && isset($chartData['departments']);
            });
    }

    public function test_overdue_card_has_tinjau_button_and_renders_overdue_review_modal(): void
    {
        $this->actingAs($this->adminUser);

        $advance = CashAdvance::create([
            'reference_number' => 'KB-202610-OVERDUE-1',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::today()->subMonths(2)->toDateString(),
            'reason' => 'Uji cicilan overdue',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->adminUser->id,
            'approved_at' => Carbon::now()->subMonths(2),
        ]);

        CashAdvanceInstallment::create([
            'cash_advance_id' => $advance->id,
            'installment_number' => 1,
            'due_date' => Carbon::now()->subMonths(1)->startOfMonth()->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        Livewire::test(Index::class)
            ->assertSet('selectedEmployeeId', null)
            ->assertSee('Perlu perhatian')
            ->assertSee('overdueReviewModal')
            ->assertSee('Tinjau Cicilan Terlewat')
            ->assertSee('cicilan terlewat')
            ->assertViewHas('overdueReviewAdvances', function ($overdue) {
                return $overdue->isNotEmpty();
            });
    }

    public function test_pending_payments_review_modal_and_acc_action(): void
    {
        $this->actingAs($this->direkturUser);

        $advance = CashAdvance::create([
            'reference_number' => 'KB-202610-PMP1',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::MANUAL->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Pinjaman untuk uji ACC pembayaran',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $payment = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-001',
            'cash_advance_id' => $advance->id,
            'payment_date' => Carbon::today()->toDateString(),
            'amount' => 500000,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            'status' => CashAdvancePaymentStatus::PENDING->value,
            'notes' => 'Transfer cicilan ke rekening BCA',
            'recorded_by' => $this->adminUser->id,
        ]);

        $paymentWithProof = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-002',
            'cash_advance_id' => $advance->id,
            'payment_date' => Carbon::today()->toDateString(),
            'amount' => 200000,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            'proof_document' => 'cash-advances/proofs/test-proof.jpg',
            'status' => CashAdvancePaymentStatus::PENDING->value,
            'notes' => 'Transfer cicilan ke rekening BCA dengan bukti',
            'recorded_by' => $this->adminUser->id,
        ]);

        Livewire::test(Index::class)
            ->assertSee('ACC Pelunasan Kasbon')
            ->assertSee('2 pelunasan')
            ->assertSee('Rp 700.000')
            ->assertSee('pendingPaymentReviewModal')
            ->assertSee('Transfer Bank')
            ->assertSee('Catatan:')
            ->assertSee('Lihat Bukti')
            ->assertSee('Tidak ada Bukti')
            ->assertSee('Diinput oleh:')
            ->assertSee($this->adminUser->name)
            ->call('approvePayment', $payment->id)
            ->assertDispatched('swal:alert');

        $this->assertEquals(CashAdvancePaymentStatus::APPROVED, $payment->fresh()->status);
        $this->assertEquals(500000, $advance->fresh()->remaining_amount);
    }

    public function test_can_direct_navigate_to_ledger_transaction_and_highlights_overdue_statements(): void
    {
        $this->actingAs($this->adminUser);

        $advance = CashAdvance::create([
            'reference_number' => 'KB-202609-OVERDUE',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 1,
            'monthly_installment' => 1000000,
            'disbursement_date' => Carbon::parse('2026-08-01')->toDateString(),
            'reason' => 'Pinjaman bulan lalu',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->adminUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now(),
        ]);

        $instOverdue = CashAdvanceInstallment::create([
            'cash_advance_id' => $advance->id,
            'installment_number' => 1,
            'due_date' => Carbon::yesterday()->toDateString(),
            'amount' => 1000000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $pendingPayment = CashAdvancePayment::create([
            'payment_number' => 'PAY-KB-202610-0099',
            'cash_advance_id' => $advance->id,
            'payment_date' => Carbon::today()->toDateString(),
            'amount' => 500000,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            'status' => CashAdvancePaymentStatus::PENDING->value,
            'notes' => 'Cicilan sebagian',
            'recorded_by' => $this->adminUser->id,
        ]);

        Livewire::test(Index::class)
            ->set('selectedEmployeeId', $this->employee->id)
            ->assertSee('table-row-overdue')
            ->assertSee('background-color: #fef2f2;')
            ->assertSee("directToTransaction({$this->employee->id}, 'payment', {$pendingPayment->id})", false)
            ->call('directToTransaction', $this->employee->id, 'advance', $advance->id)
            ->assertSet('activeMainTab', 'ledger')
            ->assertSet('selectedCashAdvanceId', $advance->id)
            ->assertDispatched('scroll-to-ledger-row')
            ->call('directToTransaction', $this->employee->id, 'payment', $pendingPayment->id)
            ->assertSet('activeMainTab', 'ledger')
            ->assertSet('highlightPaymentId', $pendingPayment->id)
            ->assertDispatched('scroll-to-ledger-row');
    }
}
