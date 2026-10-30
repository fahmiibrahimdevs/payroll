<?php

namespace Tests\Feature;

use App\Enums\CashAdvanceStatus;
use App\Enums\InstallmentStatus;
use App\Enums\OvertimeStatus;
use App\Enums\OvertimeSubmissionSource;
use App\Enums\RepaymentScheme;
use App\Livewire\Notifications\Index;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OvertimeSubmission;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\CashAdvanceService;
use App\Services\DashboardService;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected User $direkturUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $direkturRole = Role::firstOrCreate(['name' => 'direktur'], ['display_name' => 'Direktur']);
        $karyawanRole = Role::firstOrCreate(['name' => 'karyawan'], ['display_name' => 'Karyawan']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin_'.uniqid().'@example.com',
            'is_active' => true,
        ]);
        $this->adminUser->addRole($adminRole);

        $this->direkturUser = User::factory()->create([
            'email' => 'direktur_'.uniqid().'@example.com',
            'is_active' => true,
        ]);
        $this->direkturUser->addRole($direkturRole);

        $this->karyawanUser = User::factory()->create([
            'email' => 'karyawan_'.uniqid().'@example.com',
            'is_active' => true,
        ]);
        $this->karyawanUser->addRole($karyawanRole);

        $dept = Department::firstOrCreate(['name' => 'Dept Notifikasi '.uniqid()]);
        $pos = Position::firstOrCreate(['name' => 'Staff Notifikasi '.uniqid()]);

        $this->employee = Employee::create([
            'user_id' => $this->karyawanUser->id,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'full_name' => 'Karyawan Test Notifikasi',
            'join_date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            'employment_status' => 'tetap',
            'status' => 'active',
            'basic_salary' => 5000000,
            'overtime_rate_per_hour' => 25000,
        ]);
    }

    private function createSubmission(OvertimeStatus $status): OvertimeSubmission
    {
        return OvertimeSubmission::create([
            'reference_number' => app(OvertimeService::class)->generateSubmissionReference(),
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '17:00',
            'end_time' => '21:00',
            'duration_hours' => 4.00,
            'hourly_rate' => 25000.00,
            'meal_allowance' => 0,
            'total_amount' => 100000.00,
            'reason' => 'Lembur uji notifikasi',
            'submission_source' => OvertimeSubmissionSource::KARYAWAN,
            'status' => $status,
            'submitted_by' => $this->karyawanUser->id,
        ]);
    }

    public function test_overtime_approval_notifies_employee(): void
    {
        $submission = $this->createSubmission(OvertimeStatus::PENDING_DIREKTUR);

        app(OvertimeService::class)->approveByDirector($submission, $this->direkturUser);

        $this->karyawanUser->refresh();
        $notification = $this->karyawanUser->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Lembur Disetujui', $notification->data['title']);
        $this->assertSame('overtime', $notification->data['category']);
        $this->assertNull($notification->read_at);
    }

    public function test_overtime_rejection_notifies_employee_with_reason(): void
    {
        $submission = $this->createSubmission(OvertimeStatus::PENDING_ADMIN);

        app(OvertimeService::class)->rejectSubmission($submission, 'Tidak ada anggaran lembur', $this->adminUser);

        $notification = $this->karyawanUser->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Lembur Ditolak', $notification->data['title']);
        $this->assertStringContainsString('Tidak ada anggaran lembur', $notification->data['body']);
    }

    public function test_overtime_rejection_by_admin_does_not_notify_the_admin_actor(): void
    {
        $submission = $this->createSubmission(OvertimeStatus::PENDING_ADMIN);

        // Admin yang menolak sekaligus pemilik pengajuan (edge case) tidak menerima notif
        $submission->update(['employee_id' => Employee::where('user_id', $this->adminUser->id)->value('id') ?? $submission->employee_id]);

        app(OvertimeService::class)->rejectSubmission($submission, 'Sebab uji', $this->adminUser);

        $this->assertSame(0, $this->adminUser->notifications()->count());
    }

    public function test_cash_advance_approval_notifies_employee(): void
    {
        $advance = CashAdvance::create([
            'reference_number' => 'KB-NOTIF-1',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::today()->toDateString(),
            'reason' => 'Uji notif kasbon',
            'status' => CashAdvanceStatus::PENDING->value,
            'submitted_by' => $this->karyawanUser->id,
        ]);

        $this->actingAs($this->direkturUser);
        $this->assertTrue(Gate::forUser($this->direkturUser)->allows('approve', $advance));

        app(CashAdvanceService::class)->approveCashAdvance($advance, $this->direkturUser);

        $notification = $this->karyawanUser->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Kasbon Disetujui', $notification->data['title']);
        $this->assertSame('cash_advance', $notification->data['category']);
    }

    public function test_action_items_include_pending_overtime_for_admin_and_director(): void
    {
        $this->createSubmission(OvertimeStatus::PENDING_ADMIN);
        $this->createSubmission(OvertimeStatus::PENDING_DIREKTUR);

        $this->actingAs($this->adminUser);
        $adminItems = collect(app(DashboardService::class)->getActionItems());
        $this->assertTrue($adminItems->contains(fn ($item) => $item['title'] === 'Verifikasi Lembur'));

        $this->actingAs($this->direkturUser);
        $directorItems = collect(app(DashboardService::class)->getActionItems());
        $this->assertTrue($directorItems->contains(fn ($item) => $item['title'] === 'Persetujuan Lembur'));
    }

    public function test_karyawan_action_items_show_own_overdue_installment_and_rejected_overtime(): void
    {
        $advance = CashAdvance::create([
            'reference_number' => 'KB-NOTIF-2',
            'employee_id' => $this->employee->id,
            'amount' => 1000000,
            'remaining_amount' => 1000000,
            'repayment_scheme' => RepaymentScheme::SALARY_INSTALLMENT->value,
            'installment_count' => 2,
            'monthly_installment' => 500000,
            'disbursement_date' => Carbon::today()->subMonths(2)->toDateString(),
            'reason' => 'Uji notif cicilan',
            'status' => CashAdvanceStatus::ACTIVE->value,
            'submitted_by' => $this->karyawanUser->id,
            'approved_by' => $this->direkturUser->id,
            'approved_at' => Carbon::now()->subMonths(2),
        ]);

        CashAdvanceInstallment::create([
            'cash_advance_id' => $advance->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->startOfMonth()->subMonth()->toDateString(),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::PENDING->value,
        ]);

        $this->createSubmission(OvertimeStatus::REJECTED_ADMIN);

        $this->actingAs($this->karyawanUser);
        $items = collect(app(DashboardService::class)->getActionItems());

        $this->assertTrue($items->contains(fn ($item) => $item['title'] === 'Cicilan Kasbon Terlewat'));
        $this->assertTrue($items->contains(fn ($item) => $item['title'] === 'Lembur Ditolak Admin'));
    }

    public function test_notification_page_lists_own_notifications_and_mark_read_works(): void
    {
        $submission = $this->createSubmission(OvertimeStatus::PENDING_DIREKTUR);
        app(OvertimeService::class)->approveByDirector($submission, $this->direkturUser);

        $otherUser = User::factory()->create(['is_active' => true]);

        $this->actingAs($this->karyawanUser);
        Livewire::test(Index::class)
            ->assertSee('Lembur Disetujui')
            ->assertViewHas('unreadCount', 1);

        $notificationId = $this->karyawanUser->notifications()->first()->id;

        Livewire::test(Index::class)
            ->call('markRead', $notificationId)
            ->assertHasNoErrors();

        $this->assertNotNull($this->karyawanUser->notifications()->first()->fresh()->read_at);

        // Notifikasi milik orang lain tidak bisa ditandai
        Livewire::test(Index::class, [])
            ->call('markRead', $notificationId);

        $this->actingAs($otherUser);
        Livewire::test(Index::class)
            ->call('markRead', $notificationId);

        $this->assertNotNull($this->karyawanUser->notifications()->first()->fresh()->read_at);
    }

    public function test_mark_all_read_clears_unread(): void
    {
        $first = $this->createSubmission(OvertimeStatus::PENDING_DIREKTUR);
        $second = $this->createSubmission(OvertimeStatus::PENDING_DIREKTUR);
        app(OvertimeService::class)->approveByDirector($first, $this->direkturUser);
        app(OvertimeService::class)->approveByDirector($second, $this->direkturUser);

        $this->assertSame(2, $this->karyawanUser->unreadNotifications()->count());

        $this->actingAs($this->karyawanUser);
        Livewire::test(Index::class)
            ->call('markAllRead')
            ->assertHasNoErrors();

        $this->assertSame(0, $this->karyawanUser->unreadNotifications()->count());
    }
}
