<?php

namespace App\Livewire\CashAdvances;

use App\Enums\CashAdvanceStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\RepaymentScheme;
use App\Livewire\Forms\CashAdvanceForm;
use App\Livewire\Forms\CashAdvancePaymentForm;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\CashAdvancePayment;
use App\Models\Department;
use App\Models\Employee;
use App\Services\CashAdvanceService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Kasbon Karyawan')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public CashAdvanceForm $form;

    public CashAdvancePaymentForm $paymentForm;

    public ?int $selectedDepartmentId = null;

    public ?int $selectedEmployeeId = null;

    public string $search = '';

    public int $perPage = 25;

    public string $recapFilter = 'all';

    public ?int $selectedCashAdvanceIdForPayment = null;

    public bool $isEditMode = false;

    public ?int $editingCashAdvanceId = null;

    public ?int $selectedCashAdvanceId = null;

    public ?CashAdvance $selectedCashAdvance = null;

    public ?int $highlightPaymentId = null;

    public string $detailModalTab = 'installments';

    public string $rejectionReason = '';

    public string $approvalNotes = '';

    /**
     * Main Tab active state: 'statements' or 'ledger'.
     */
    public string $activeMainTab = 'statements';

    /**
     * Statements Sub-Tab active state: 'unpaid' or 'paid'.
     */
    public string $statementSubTab = 'unpaid';

    /**
     * Selected monthly statement detail for modal display.
     *
     * @var array<string, mixed>|null
     */
    public ?array $selectedStatementDetail = null;

    /**
     * Selected month keys for early payment modal.
     *
     * @var array<int, string>
     */
    public array $selectedMonthKeysForPayment = [];

    public ?float $earlyPaymentAmount = null;

    public string $earlyPaymentDate = '';

    public string $earlyPaymentMethod = 'cash';

    public $earlyProofDocument = null;

    public string $earlyPaymentNotes = '';

    /**
     * Blocker alert summary for active cash advances when employee is selected in form.
     *
     * @var array<string, mixed>|null
     */
    public ?array $activeCashAdvanceAlert = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', CashAdvance::class);

        if (auth()->user()?->hasRole('karyawan')) {
            $emp = auth()->user()->getEmployeeRecord();
            $this->selectedEmployeeId = $emp?->id;
            $this->selectedDepartmentId = $emp?->department_id;
        }
    }

    public function updatingSelectedDepartmentId(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedEmployeeId(): void
    {
        $this->resetPage();
        $this->statementSubTab = 'unpaid';
        $this->selectedStatementDetail = null;
    }

    public function updatingRecapFilter(): void
    {
        $this->resetPage();
    }

    public function selectEmployee(int $id): void
    {
        if (auth()->user()?->hasRole('karyawan')) {
            $this->selectedEmployeeId = auth()->user()->getEmployeeRecord()?->id;

            return;
        }

        $this->selectedEmployeeId = $id;
        $this->resetPage();
        $this->statementSubTab = 'unpaid';
        $this->selectedStatementDetail = null;
        $this->dispatch('employee-selected', id: $id);
    }

    public function clearSelectedEmployee(): void
    {
        if (auth()->user()?->hasRole('karyawan')) {
            $this->selectedEmployeeId = auth()->user()->getEmployeeRecord()?->id;

            return;
        }

        $this->selectedEmployeeId = null;
        $this->resetPage();
        $this->statementSubTab = 'unpaid';
        $this->selectedStatementDetail = null;
        $this->dispatch('employee-selected', id: null);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Refresh data.
     */
    public function refreshData(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Check if chosen employee has active cash advances to display form alert.
     */
    public function updatedFormEmployeeId($value, CashAdvanceService $service): void
    {
        if (! empty($value)) {
            $summary = $service->getActiveCashAdvanceSummaryByEmployee((int) $value, $this->editingCashAdvanceId);
            $this->activeCashAdvanceAlert = $summary['has_active'] ? $summary : null;
        } else {
            $this->activeCashAdvanceAlert = null;
        }
    }

    /**
     * Increment installment tenor months.
     */
    public function incrementTenor(): void
    {
        $current = (int) ($this->form->installment_count ?: 1);
        if ($current < 60) {
            $this->form->installment_count = $current + 1;
        }
    }

    /**
     * Decrement installment tenor months.
     */
    public function decrementTenor(): void
    {
        $current = (int) ($this->form->installment_count ?: 1);
        if ($current > 1) {
            $this->form->installment_count = $current - 1;
        }
    }

    /**
     * Set quick tenor value in months.
     */
    public function setTenor(int $months): void
    {
        $this->form->installment_count = max(1, min(60, $months));
    }

    /**
     * Open modal for creating a new cash advance (Pengajuan).
     */
    public function createPengajuan(CashAdvanceService $service): void
    {
        $this->isEditMode = false;
        $this->editingCashAdvanceId = null;
        $this->activeCashAdvanceAlert = null;
        $this->form->resetForm();

        if (auth()->user()?->hasRole('karyawan')) {
            $emp = auth()->user()->getEmployeeRecord();
            $this->selectedEmployeeId = $emp?->id;
        }

        if ($this->selectedEmployeeId) {
            $this->form->employee_id = $this->selectedEmployeeId;
            $summary = $service->getActiveCashAdvanceSummaryByEmployee((int) $this->selectedEmployeeId);
            $this->activeCashAdvanceAlert = $summary['has_active'] ? $summary : null;
        }

        $this->dispatch('open-modal', [
            'id' => 'cashAdvanceModal',
            'employee_id' => $this->form->employee_id,
            'repayment_scheme' => 'salary_installment',
        ]);
    }

    /**
     * Alias for createPengajuan.
     */
    public function create(CashAdvanceService $service): void
    {
        $this->createPengajuan($service);
    }

    /**
     * Open modal for recording repayment (Pelunasan).
     */
    public function createPelunasan(CashAdvanceService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya admin yang dapat mencatat pelunasan kasbon.');

        if (! $this->selectedEmployeeId) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Pilih Karyawan',
                'message' => 'Silakan pilih karyawan terlebih dahulu.',
            ]);

            return;
        }

        $activeAdvance = $service->getActiveAdvanceByEmployee($this->selectedEmployeeId);

        if (! $activeAdvance) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Tidak Ada Kasbon Aktif',
                'message' => 'Karyawan ini saat ini tidak memiliki saldo kasbon aktif yang perlu dilunasi.',
            ]);

            return;
        }

        $this->selectedCashAdvanceIdForPayment = $activeAdvance->id;
        $this->selectedCashAdvance = $activeAdvance->load([
            'employee.department',
            'employee.position',
            'submitter',
            'approver',
            'installments.payments.recorder',
            'payments.recorder',
        ]);
        $this->paymentForm->setCashAdvance($activeAdvance);
        $this->dispatch('open-modal', id: 'cashAdvancePaymentModal');
    }

    /**
     * Store a newly created cash advance.
     */
    public function store(?CashAdvanceService $service = null, bool $confirmed = false): void
    {
        $service ??= app(CashAdvanceService::class);

        $this->validate([
            'approvalNotes' => ['nullable', 'string', 'max:500'],
        ]);

        if (auth()->user()?->hasRole('karyawan')) {
            $emp = auth()->user()->getEmployeeRecord();
            $this->form->employee_id = $emp?->id;
        }

        try {
            $this->form->validate();

            if (! $confirmed) {
                $activeAdvance = $service->getActiveAdvanceByEmployee((int) $this->form->employee_id);
                if ($activeAdvance) {
                    $this->dispatch('swal:confirm', [
                        'title' => 'Masih Ada Kasbon Aktif!',
                        'text' => "Karyawan ini masih memiliki kasbon aktif ({$activeAdvance->reference_number}) dengan sisa kasbon {$activeAdvance->formatted_remaining_amount}. Apakah Anda yakin ingin tetap membuat pengajuan kasbon baru?",
                        'icon' => 'warning',
                        'confirmButtonText' => 'Ya, Tetap Ajukan!',
                        'cancelButtonText' => 'Batal',
                        'confirmButtonColor' => '#0b52aa',
                        'action' => 'confirm-store-cash-advance',
                    ]);

                    return;
                }
            }

            $user = auth()->user();
            $cashAdvance = $this->form->store($service, $user);

            $this->selectedEmployeeId = $cashAdvance->employee_id;
            $this->dispatch('close-modal', id: 'cashAdvanceModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Pengajuan Berhasil!',
                'message' => "Pengajuan kasbon {$cashAdvance->reference_number} berhasil dibuat dan menunggu persetujuan.",
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mengajukan!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan saat menyimpan pengajuan kasbon.'),
            ]);
        }
    }

    /**
     * Confirm and execute storing cash advance when employee already has active cash advance.
     */
    #[On('confirm-store-cash-advance')]
    public function confirmStore(?CashAdvanceService $service = null): void
    {
        $service ??= app(CashAdvanceService::class);
        $this->store($service, confirmed: true);
    }

    /**
     * Open modal and populate form for editing an existing cash advance.
     */
    public function edit(int $id, CashAdvanceService $service): void
    {
        try {
            $cashAdvance = CashAdvance::findOrFail($id);
            Gate::authorize('update', $cashAdvance);

            if ($cashAdvance->status !== CashAdvanceStatus::PENDING) {
                $this->dispatch('swal:alert', [
                    'type' => 'warning',
                    'title' => 'Tidak Dapat Diubah',
                    'message' => 'Hanya kasbon dengan status Menunggu Persetujuan yang dapat diubah.',
                ]);

                return;
            }

            $this->isEditMode = true;
            $this->editingCashAdvanceId = $id;
            $this->form->setCashAdvance($cashAdvance);

            $summary = $service->getActiveCashAdvanceSummaryByEmployee($cashAdvance->employee_id, $id);
            $this->activeCashAdvanceAlert = $summary['has_active'] ? $summary : null;

            $this->dispatch('open-modal', [
                'id' => 'cashAdvanceModal',
                'employee_id' => $cashAdvance->employee_id,
                'repayment_scheme' => $cashAdvance->repayment_scheme instanceof RepaymentScheme
                    ? $cashAdvance->repayment_scheme->value
                    : (string) $cashAdvance->repayment_scheme,
            ]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data kasbon tidak ditemukan.',
            ]);
        }
    }

    /**
     * Update an existing pending cash advance.
     */
    public function update(CashAdvanceService $service): void
    {
        try {
            $cashAdvance = CashAdvance::findOrFail($this->editingCashAdvanceId);
            Gate::authorize('update', $cashAdvance);

            $user = auth()->user();
            $cashAdvance = $this->form->update($service, $user);

            $this->isEditMode = false;
            $this->editingCashAdvanceId = null;
            $this->dispatch('close-modal', id: 'cashAdvanceModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil Diperbarui!',
                'message' => "Data kasbon {$cashAdvance->reference_number} berhasil diperbarui.",
            ]);
        } catch (ValidationException|AuthorizationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan saat memperbarui kasbon.'),
            ]);
        }
    }

    /**
     * Open detail modal for full history, installment schedules, and payment logs.
     */
    public function showDetail(int $id, ?int $paymentId = null): void
    {
        try {
            $this->selectedCashAdvance = CashAdvance::with([
                'employee.department',
                'employee.position',
                'submitter',
                'approver',
                'installments.payments.recorder',
                'payments.recorder',
            ])->findOrFail($id);

            $this->selectedCashAdvanceId = $id;
            $this->highlightPaymentId = $paymentId;
            $this->detailModalTab = $paymentId ? 'payments' : 'installments';

            $this->dispatch('open-modal', [
                'id' => 'cashAdvanceDetailModal',
                'tab' => $this->detailModalTab,
                'highlightPaymentId' => $paymentId,
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data rincian kasbon tidak ditemukan.',
            ]);
        }
    }

    /**
     * Open approval/rejection modal for Directors/Admins.
     */
    public function openApproval(int $id): void
    {
        try {
            $this->selectedCashAdvance = CashAdvance::with(['employee.department', 'employee.position', 'submitter'])->findOrFail($id);
            $this->selectedCashAdvanceId = $id;
            $this->approvalNotes = '';
            $this->rejectionReason = '';
            $this->dispatch('open-modal', id: 'cashAdvanceApprovalModal');
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data kasbon tidak ditemukan.',
            ]);
        }
    }

    /**
     * Approve cash advance.
     */
    #[On('approve-cash-advance')]
    public function approve(?string $notes = null, ?CashAdvanceService $service = null): void
    {
        if (! $this->selectedCashAdvanceId) {
            return;
        }

        if ($notes !== null) {
            $this->approvalNotes = $notes;
        }

        $service ??= app(CashAdvanceService::class);

        try {
            $cashAdvance = CashAdvance::findOrFail($this->selectedCashAdvanceId);
            $user = auth()->user();

            $service->approveCashAdvance($cashAdvance, $user, $this->approvalNotes ?: null);

            $this->dispatch('close-modal', id: 'cashAdvanceApprovalModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Kasbon Disetujui!',
                'message' => "Pengajuan kasbon {$cashAdvance->reference_number} telah disetujui dan jadwal cicilan telah digenerate.",
            ]);
            $this->selectedCashAdvance = null;
            $this->selectedCashAdvanceId = null;
            $this->approvalNotes = '';
            $this->rejectionReason = '';
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyetujui!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan saat menyetujui kasbon.'),
            ]);
        }
    }

    /**
     * Reject cash advance.
     */
    #[On('reject-cash-advance')]
    public function reject(?string $reason = null, ?CashAdvanceService $service = null): void
    {
        if (! $this->selectedCashAdvanceId) {
            return;
        }

        if ($reason !== null) {
            $this->rejectionReason = $reason;
        }

        $service ??= app(CashAdvanceService::class);

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'rejectionReason.required' => 'Alasan penolakan wajib diisi.',
            'rejectionReason.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        try {
            $cashAdvance = CashAdvance::findOrFail($this->selectedCashAdvanceId);
            $user = auth()->user();

            $service->rejectCashAdvance($cashAdvance, $user, $this->rejectionReason);

            $this->dispatch('close-modal', id: 'cashAdvanceApprovalModal');
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Kasbon Ditolak',
                'message' => "Pengajuan kasbon {$cashAdvance->reference_number} telah ditolak.",
            ]);
            $this->selectedCashAdvance = null;
            $this->selectedCashAdvanceId = null;
            $this->approvalNotes = '';
            $this->rejectionReason = '';
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menolak!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan saat menolak kasbon.'),
            ]);
        }
    }

    /**
     * Approve cash advance directly from pending review modal.
     */
    #[On('approve-cash-advance-direct')]
    public function approveDirect(int $advanceId, ?string $notes = null, ?CashAdvanceService $service = null): void
    {
        $service ??= app(CashAdvanceService::class);

        try {
            $cashAdvance = CashAdvance::findOrFail($advanceId);
            $user = auth()->user();

            $service->approveCashAdvance($cashAdvance, $user, $notes ?: null);

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Kasbon Disetujui!',
                'message' => "Pengajuan kasbon {$cashAdvance->reference_number} telah disetujui dan jadwal cicilan telah digenerate.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyetujui!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan saat menyetujui kasbon.'),
            ]);
        }
    }

    /**
     * Reject cash advance directly from pending review modal.
     */
    #[On('reject-cash-advance-direct')]
    public function rejectDirect(int $advanceId, string $reason, ?CashAdvanceService $service = null): void
    {
        $service ??= app(CashAdvanceService::class);

        $cleanReason = trim($reason);
        if (mb_strlen($cleanReason) < 3) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menolak!',
                'message' => 'Alasan penolakan wajib diisi (minimal 3 karakter).',
            ]);

            return;
        }

        try {
            $cashAdvance = CashAdvance::findOrFail($advanceId);
            $user = auth()->user();

            $service->rejectCashAdvance($cashAdvance, $user, $cleanReason);

            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Kasbon Ditolak',
                'message' => "Pengajuan kasbon {$cashAdvance->reference_number} telah ditolak.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menolak!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan saat menolak kasbon.'),
            ]);
        }
    }

    /**
     * Open payment modal for recording manual cash advance repayments.
     */
    public function openPayment(int $id): void
    {
        try {
            $cashAdvance = CashAdvance::with([
                'employee.department',
                'employee.position',
                'submitter',
                'approver',
                'installments.payments.recorder',
                'payments.recorder',
            ])->findOrFail($id);

            if (! in_array($cashAdvance->status, [CashAdvanceStatus::ACTIVE, CashAdvanceStatus::APPROVED], true)) {
                $this->dispatch('swal:alert', [
                    'type' => 'warning',
                    'title' => 'Tidak Dapat Melakukan Pembayaran',
                    'message' => 'Hanya kasbon aktif yang belum lunas yang dapat menerima pembayaran.',
                ]);

                return;
            }

            $this->selectedCashAdvanceIdForPayment = $id;
            $this->selectedCashAdvance = $cashAdvance;
            $this->paymentForm->setCashAdvance($cashAdvance);
            $this->dispatch('open-modal', id: 'cashAdvancePaymentModal');
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data kasbon tidak ditemukan.',
            ]);
        }
    }

    /**
     * Open payment modal directly targeting a specific installment.
     */
    public function openPaymentForInstallment(int $advanceId, int $installmentId): void
    {
        try {
            $cashAdvance = CashAdvance::with([
                'employee.department',
                'employee.position',
                'submitter',
                'approver',
                'installments.payments.recorder',
                'payments.recorder',
            ])->findOrFail($advanceId);

            if (! in_array($cashAdvance->status, [CashAdvanceStatus::ACTIVE, CashAdvanceStatus::APPROVED], true)) {
                $this->dispatch('swal:alert', [
                    'type' => 'warning',
                    'title' => 'Tidak Dapat Melakukan Pembayaran',
                    'message' => 'Hanya kasbon aktif yang belum lunas yang dapat menerima pembayaran.',
                ]);

                return;
            }

            $this->selectedCashAdvanceIdForPayment = $advanceId;
            $this->selectedCashAdvance = $cashAdvance;
            $this->paymentForm->setCashAdvance($cashAdvance);
            $this->paymentForm->applyPaymentTarget('installment', $installmentId);
            $this->dispatch('open-modal', id: 'cashAdvancePaymentModal');
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data kasbon tidak ditemukan.',
            ]);
        }
    }

    /**
     * Select payment target option (specific installment, full early settlement, or custom).
     */
    public function selectPaymentTarget(string $type, ?int $installmentId = null): void
    {
        $this->paymentForm->applyPaymentTarget($type, $installmentId);
    }

    /**
     * Store manual payment transaction.
     */
    public function storePayment(CashAdvanceService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya admin yang dapat mencatat pelunasan kasbon.');

        try {
            $user = auth()->user();
            $payment = $this->paymentForm->store($service, $user);

            $this->dispatch('close-modal', id: 'cashAdvancePaymentModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Pembayaran Berhasil Dicatat!',
                'message' => "Transaksi {$payment->payment_number} sebesar {$payment->formatted_amount} berhasil dicatat dan berstatus Menunggu Persetujuan Direktur.",
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mencatat Pembayaran!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan saat memproses pembayaran.'),
            ]);
        }
    }

    /**
     * Approve a pending cash advance payment (Direktur only).
     */
    #[On('approve-cash-advance-payment')]
    public function approvePayment(int $paymentId, ?CashAdvanceService $service = null): void
    {
        $service ??= app(CashAdvanceService::class);

        try {
            $payment = CashAdvancePayment::with('cashAdvance')->findOrFail($paymentId);
            $user = auth()->user();

            $service->approvePayment($payment, $user);

            if ($this->selectedCashAdvanceId) {
                $this->showDetail($this->selectedCashAdvanceId, $paymentId);
            }

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Pembayaran Disetujui!',
                'message' => "Pembayaran {$payment->payment_number} sebesar {$payment->formatted_amount} telah disetujui (ACC). Saldo dan cicilan kasbon telah diperbarui.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyetujui!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan saat menyetujui pembayaran.'),
            ]);
        }
    }

    /**
     * Reject a pending cash advance payment (Direktur only).
     */
    #[On('reject-cash-advance-payment')]
    public function rejectPayment(int $paymentId, string $reason, ?CashAdvanceService $service = null): void
    {
        $service ??= app(CashAdvanceService::class);

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Alasan Wajib Diisi',
                'message' => 'Silakan isi alasan penolakan pembayaran.',
            ]);

            return;
        }

        try {
            $payment = CashAdvancePayment::with('cashAdvance')->findOrFail($paymentId);
            $user = auth()->user();

            $service->rejectPayment($payment, $trimmedReason, $user);

            if ($this->selectedCashAdvanceId) {
                $this->showDetail($this->selectedCashAdvanceId, $paymentId);
            }

            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Pembayaran Ditolak',
                'message' => "Pembayaran {$payment->payment_number} telah ditolak.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menolak!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan saat menolak pembayaran.'),
            ]);
        }
    }

    /**
     * Trigger SweetAlert2 delete confirmation dialog.
     */
    public function confirmDelete(int $id): void
    {
        $cashAdvance = CashAdvance::find($id);

        if (! $cashAdvance) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data kasbon tidak ditemukan.',
            ]);

            return;
        }

        Gate::authorize('delete', $cashAdvance);

        if (! in_array($cashAdvance->status, [CashAdvanceStatus::PENDING, CashAdvanceStatus::REJECTED, CashAdvanceStatus::CANCELLED], true)) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Tidak Dapat Dihapus',
                'message' => 'Kasbon yang sudah disetujui, aktif, atau memiliki riwayat pembayaran tidak dapat dihapus.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $id,
            'title' => "Pengajuan Kasbon {$cashAdvance->reference_number} ({$cashAdvance->employee?->full_name})",
        ]);
    }

    /**
     * Delete a cash advance after user confirmation.
     */
    #[On('delete-cash-advance')]
    public function delete(int $id, CashAdvanceService $service): void
    {
        try {
            $cashAdvance = CashAdvance::findOrFail($id);
            Gate::authorize('delete', $cashAdvance);

            $user = auth()->user();

            $service->deleteCashAdvance($cashAdvance, $user);

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil Dihapus!',
                'message' => 'Pengajuan kasbon berhasil dihapus dari sistem.',
            ]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan sistem saat menghapus kasbon.'),
            ]);
        }
    }

    /**
     * Switch main view tab ('statements' vs 'ledger').
     */
    public function switchMainTab(string $tab): void
    {
        if (in_array($tab, ['statements', 'ledger'], true)) {
            $this->activeMainTab = $tab;
            $this->resetPage();
        }
    }

    /**
     * Direct navigate from review modal to employee's ledger transaction row.
     */
    public function directToTransaction(?int $employeeId, string $type, int $id): void
    {
        if (auth()->user()?->hasRole('karyawan')) {
            $this->selectedEmployeeId = auth()->user()->getEmployeeRecord()?->id;
        } elseif ($employeeId) {
            $this->selectedEmployeeId = $employeeId;
        }

        $this->activeMainTab = 'ledger';
        $this->search = '';

        if ($type === 'advance') {
            $this->selectedCashAdvanceId = $id;
            $this->highlightPaymentId = null;
        } else {
            $this->highlightPaymentId = $id;
            $payment = CashAdvancePayment::find($id);
            $this->selectedCashAdvanceId = $payment?->cash_advance_id;
            if (! $this->selectedEmployeeId && $payment?->cashAdvance?->employee_id) {
                $this->selectedEmployeeId = $payment->cashAdvance->employee_id;
            }
        }

        if ($this->selectedEmployeeId) {
            $service = app(CashAdvanceService::class);
            $targetPage = $service->getLedgerPageForTransaction($this->selectedEmployeeId, $type, $id, $this->perPage);
            $this->setPage($targetPage);
        } else {
            $this->resetPage();
        }

        $this->dispatch('employee-selected', id: $this->selectedEmployeeId);
        $this->dispatch('scroll-to-ledger-row', [
            'type' => $type,
            'id' => $id,
        ]);
    }

    /**
     * Switch statement sub-tab ('unpaid' vs 'paid').
     */
    public function switchStatementSubTab(string $subTab): void
    {
        if (in_array($subTab, ['unpaid', 'paid'], true)) {
            $this->statementSubTab = $subTab;
        }
    }

    /**
     * Show modal detail for a specific monthly statement.
     */
    public function showStatementDetail(string $monthKey, CashAdvanceService $service): void
    {
        if (! $this->selectedEmployeeId) {
            return;
        }

        $statements = $service->getEmployeeMonthlyStatements($this->selectedEmployeeId);
        $all = array_merge(
            $statements['unpaid_statements'],
            ...array_values($statements['paid_statements'])
        );

        $found = collect($all)->firstWhere('month_key', $monthKey);

        if ($found) {
            $this->selectedStatementDetail = $found;
            $this->dispatch('open-modal', id: 'statementDetailModal');
        } else {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Data Tidak Ditemukan',
                'message' => 'Rincian tagihan untuk bulan ini tidak ditemukan.',
            ]);
        }
    }

    /**
     * Computed waterfall allocation for early payment modal.
     *
     * @return array{
     *     allocations: array<string, array{allocated: float, remaining_after: float, is_full: bool, is_partial: bool, is_covered: bool}>,
     *     total_allocated: float,
     *     total_unpaid_all: float,
     *     total_unpaid_selected: float,
     *     covered_month_keys: array<int, string>
     * }
     */
    public function getEarlyPaymentWaterfallProperty(): array
    {
        if (! $this->selectedEmployeeId) {
            return [
                'allocations' => [],
                'total_allocated' => 0.0,
                'total_unpaid_all' => 0.0,
                'total_unpaid_selected' => 0.0,
                'covered_month_keys' => [],
            ];
        }

        $service = app(CashAdvanceService::class);
        $statements = $service->getEmployeeMonthlyStatements($this->selectedEmployeeId);
        $unpaidList = $statements['unpaid_statements'] ?? [];

        $totalUnpaidAll = (float) array_sum(array_column($unpaidList, 'remaining_amount'));
        $amount = (float) ($this->earlyPaymentAmount ?? 0);

        $totalUnpaidSelected = 0.0;
        foreach ($unpaidList as $st) {
            if (in_array($st['month_key'], $this->selectedMonthKeysForPayment, true)) {
                $totalUnpaidSelected += (float) ($st['remaining_amount'] ?? 0);
            }
        }

        $left = $amount;
        $allocations = [];
        $coveredKeys = [];

        foreach ($unpaidList as $st) {
            $monthKey = $st['month_key'];
            $stDue = (float) ($st['remaining_amount'] ?? 0);
            $isSelected = in_array($monthKey, $this->selectedMonthKeysForPayment, true);

            if (! $isSelected) {
                $allocations[$monthKey] = [
                    'allocated' => 0.0,
                    'remaining_after' => $stDue,
                    'is_full' => false,
                    'is_partial' => false,
                    'is_covered' => false,
                ];

                continue;
            }

            if ($left <= 0) {
                $allocations[$monthKey] = [
                    'allocated' => 0.0,
                    'remaining_after' => $stDue,
                    'is_full' => false,
                    'is_partial' => false,
                    'is_covered' => false,
                ];

                continue;
            }

            $alloc = min($left, $stDue);
            $rem = max(0.0, round($stDue - $alloc, 2));
            $isFull = $rem <= 0.001;
            $isPartial = $rem > 0.001 && $alloc > 0;

            $allocations[$monthKey] = [
                'allocated' => $alloc,
                'remaining_after' => $rem,
                'is_full' => $isFull,
                'is_partial' => $isPartial,
                'is_covered' => true,
            ];

            if ($alloc > 0) {
                $coveredKeys[] = $monthKey;
            }
            $left -= $alloc;
        }

        return [
            'allocations' => $allocations,
            'total_allocated' => $amount,
            'total_unpaid_all' => $totalUnpaidAll,
            'total_unpaid_selected' => $totalUnpaidSelected,
            'covered_month_keys' => $coveredKeys,
        ];
    }

    /**
     * Validate when user types in early payment amount.
     */
    public function updatedEarlyPaymentAmount($value): void
    {
        $selectedSum = $this->getSelectedMonthsRemainingSum();
        $maxAllowed = (float) ceil($selectedSum);

        if (empty($this->selectedMonthKeysForPayment) || $selectedSum <= 0) {
            $this->addError('earlyPaymentAmount', 'Silakan pilih minimal satu bulan tagihan terlebih dahulu.');

            return;
        }

        $this->validateOnly('earlyPaymentAmount', [
            'earlyPaymentAmount' => ['required', 'numeric', 'min:1000', 'max:'.$maxAllowed],
        ], [
            'earlyPaymentAmount.required' => 'Nominal pembayaran wajib diisi.',
            'earlyPaymentAmount.numeric' => 'Nominal pembayaran harus berupa angka.',
            'earlyPaymentAmount.min' => 'Nominal pembayaran minimal Rp 1.000.',
            'earlyPaymentAmount.max' => 'Nominal pembayaran tidak boleh melebihi total tagihan bulan yang dipilih (Maks. Rp '.number_format($maxAllowed, 0, ',', '.').').',
        ]);
    }

    /**
     * Calculate remaining sum of currently selected month keys.
     */
    public function getSelectedMonthsRemainingSum(): float
    {
        if (! $this->selectedEmployeeId || empty($this->selectedMonthKeysForPayment)) {
            return 0.0;
        }

        $service = app(CashAdvanceService::class);
        $statements = $service->getEmployeeMonthlyStatements($this->selectedEmployeeId);
        $sum = 0.0;

        foreach ($statements['unpaid_statements'] as $st) {
            if (in_array($st['month_key'], $this->selectedMonthKeysForPayment, true)) {
                $sum += (float) ($st['remaining_amount'] ?? 0);
            }
        }

        return $sum;
    }

    /**
     * Open modal for early payment (Pelunasan Lebih Awal).
     */
    public function openEarlyPaymentModal(?string $targetMonthKey = null, ?CashAdvanceService $service = null): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya admin yang dapat mencatat pelunasan kasbon.');

        if (! $this->selectedEmployeeId) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Pilih Karyawan',
                'message' => 'Silakan pilih karyawan terlebih dahulu.',
            ]);

            return;
        }

        $service ??= app(CashAdvanceService::class);
        $statements = $service->getEmployeeMonthlyStatements($this->selectedEmployeeId);

        if (empty($statements['unpaid_statements'])) {
            $this->dispatch('swal:alert', [
                'type' => 'info',
                'title' => 'Tidak Ada Tagihan',
                'message' => 'Karyawan ini tidak memiliki tagihan kasbon yang belum lunas.',
            ]);

            return;
        }

        if ($targetMonthKey) {
            $this->selectedMonthKeysForPayment = [$targetMonthKey];
        } else {
            $this->selectedMonthKeysForPayment = [$statements['unpaid_statements'][0]['month_key']];
        }

        $this->earlyPaymentAmount = (float) ceil($this->getSelectedMonthsRemainingSum());
        $this->earlyPaymentDate = Carbon::today()->format('Y-m-d');
        $this->earlyPaymentMethod = 'cash';
        $this->earlyProofDocument = null;
        $this->earlyPaymentNotes = '';

        $this->dispatch('open-modal', id: 'earlyPaymentModal');
    }

    /**
     * Toggle selection of a specific month in early payment modal independently.
     */
    public function toggleMonthSelection(string $monthKey): void
    {
        if (! $this->selectedEmployeeId) {
            return;
        }

        $service = app(CashAdvanceService::class);
        $statements = $service->getEmployeeMonthlyStatements($this->selectedEmployeeId);
        $unpaidList = $statements['unpaid_statements'] ?? [];

        $allKeys = array_column($unpaidList, 'month_key');
        if (! in_array($monthKey, $allKeys, true)) {
            return;
        }

        $selected = $this->selectedMonthKeysForPayment;

        if (in_array($monthKey, $selected, true)) {
            // Uncheck this month only
            $selected = array_values(array_filter($selected, fn ($k) => $k !== $monthKey));
        } else {
            // Check this month only, keep chronological order
            $selected[] = $monthKey;
            usort($selected, fn ($a, $b) => array_search($a, $allKeys, true) <=> array_search($b, $allKeys, true));
        }

        $this->selectedMonthKeysForPayment = $selected;

        // Recalculate amount to match selected months sum
        $newSum = 0.0;
        foreach ($unpaidList as $st) {
            if (in_array($st['month_key'], $selected, true)) {
                $newSum += (float) ($st['remaining_amount'] ?? 0);
            }
        }

        $this->earlyPaymentAmount = $newSum > 0 ? (float) ceil($newSum) : null;
        $this->resetErrorBag(['earlyPaymentAmount', 'selectedMonthKeysForPayment']);
    }

    /**
     * Select all unpaid months for early settlement.
     */
    public function selectAllUnpaidMonths(CashAdvanceService $service): void
    {
        if (! $this->selectedEmployeeId) {
            return;
        }

        $statements = $service->getEmployeeMonthlyStatements($this->selectedEmployeeId);
        $unpaidList = $statements['unpaid_statements'] ?? [];

        $this->selectedMonthKeysForPayment = array_column($unpaidList, 'month_key');
        $this->earlyPaymentAmount = (float) ceil(array_sum(array_column($unpaidList, 'remaining_amount')));
        $this->resetErrorBag(['earlyPaymentAmount', 'selectedMonthKeysForPayment']);
    }

    /**
     * Store early payment transaction for selected months.
     */
    public function storeEarlyPayment(CashAdvanceService $service, bool $confirmed = false): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya admin yang dapat mencatat pelunasan kasbon.');

        if (! $this->selectedEmployeeId) {
            return;
        }

        $selectedSum = $this->getSelectedMonthsRemainingSum();
        $maxAllowed = (float) ceil($selectedSum);
        if (empty($this->selectedMonthKeysForPayment) || $selectedSum <= 0) {
            $this->addError('selectedMonthKeysForPayment', 'Silakan pilih minimal 1 bulan tagihan untuk pelunasan.');

            return;
        }

        $this->validate([
            'earlyPaymentAmount' => ['required', 'numeric', 'min:1000', 'max:'.$maxAllowed],
            'earlyPaymentDate' => ['required', 'date', 'before_or_equal:today'],
            'earlyPaymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'earlyProofDocument' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'earlyPaymentNotes' => ['nullable', 'string', 'max:1000'],
        ], [
            'earlyPaymentAmount.required' => 'Nominal pembayaran wajib diisi.',
            'earlyPaymentAmount.numeric' => 'Nominal pembayaran harus berupa angka.',
            'earlyPaymentAmount.min' => 'Nominal pembayaran minimal Rp 1.000.',
            'earlyPaymentAmount.max' => 'Nominal pembayaran tidak boleh melebihi total tagihan bulan yang dipilih (Rp '.number_format($maxAllowed, 0, ',', '.').').',
            'earlyPaymentDate.required' => 'Tanggal pembayaran wajib diisi.',
            'earlyPaymentMethod.required' => 'Metode pembayaran wajib dipilih.',
        ]);

        $waterfall = $this->earlyPaymentWaterfall;
        $monthKeys = ! empty($waterfall['covered_month_keys'])
            ? $waterfall['covered_month_keys']
            : $this->selectedMonthKeysForPayment;

        if (empty($monthKeys)) {
            $this->addError('earlyPaymentAmount', 'Silakan pilih bulan atau masukkan nominal pembayaran.');

            return;
        }

        if (! $confirmed) {
            $summary = $service->getEmployeeSummary($this->selectedEmployeeId);
            $currentBalance = (float) ($summary['total_balance'] ?? 0);
            $payAmount = (float) $this->earlyPaymentAmount;
            $finalRemaining = max(0.0, round($currentBalance - $payAmount, 2));

            $employeeName = e($summary['employee']?->full_name ?? '-');
            $formattedCurrentBalance = 'Rp '.number_format($currentBalance, 0, ',', '.');
            $formattedPayAmount = 'Rp '.number_format($payAmount, 0, ',', '.');
            $formattedFinalRemaining = $finalRemaining <= 0.001
                ? 'Rp 0 (Lunas)'
                : 'Rp '.number_format($finalRemaining, 0, ',', '.');
            $finalColorClass = $finalRemaining <= 0.001 ? 'text-success' : 'text-primary';
            $monthsCount = count($monthKeys);

            $html = <<<HTML
<div class="text-left mt-2">
    <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
        <div style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; font-size: 12.5px; color: #475569; font-weight: 500;">
                <div style="width: 26px; height: 26px; border-radius: 6px; background-color: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 9px; flex-shrink: 0;">
                    <i class="fas fa-user"></i>
                </div>
                <span>Karyawan</span>
            </div>
            <div style="font-size: 12.5px; color: #0f172a; font-weight: 600; text-align: right; max-width: 55%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {$employeeName}
            </div>
        </div>

        <div style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; font-size: 12.5px; color: #475569; font-weight: 500;">
                <div style="width: 26px; height: 26px; border-radius: 6px; background-color: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 9px; flex-shrink: 0;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <span>Bulan Tagihan</span>
            </div>
            <div style="font-size: 12.5px; color: #0f172a; font-weight: 600; text-align: right;">
                {$monthsCount} Bulan Dipilih
            </div>
        </div>

        <div style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; font-size: 12.5px; color: #475569; font-weight: 500;">
                <div style="width: 26px; height: 26px; border-radius: 6px; background-color: #fefce8; color: #ca8a04; display: flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 9px; flex-shrink: 0;">
                    <i class="fas fa-coins"></i>
                </div>
                <span>Total Sisa Kasbon</span>
            </div>
            <div style="font-size: 12.5px; color: #0f172a; font-weight: 700; text-align: right;">
                {$formattedCurrentBalance}
            </div>
        </div>

        <div style="padding: 9px 12px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; font-size: 12.5px; color: #475569; font-weight: 500;">
                <div style="width: 26px; height: 26px; border-radius: 6px; background-color: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 9px; flex-shrink: 0;">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <span>Nominal Pembayaran</span>
            </div>
            <div style="font-size: 12.5px; color: #dc2626; font-weight: 700; text-align: right;">
                - {$formattedPayAmount}
            </div>
        </div>

        <div style="padding: 10px 12px; display: flex; justify-content: space-between; align-items: center; background-color: #ffffff;">
            <div style="display: flex; align-items: center; font-size: 13px; color: #0f172a; font-weight: 700;">
                <div style="width: 26px; height: 26px; border-radius: 6px; background-color: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 9px; flex-shrink: 0;">
                    <i class="fas fa-wallet"></i>
                </div>
                <span>Sisa Kasbon Akhir</span>
            </div>
            <div style="font-size: 13.5px; font-weight: 700; text-align: right;" class="{$finalColorClass}">
                {$formattedFinalRemaining}
            </div>
        </div>
    </div>
</div>
<div class="mt-3 text-center" style="font-size: 14px; color: #1e293b; font-weight: 600; line-height: 1.4;">
    Apakah Anda yakin ingin memproses pembayaran kasbon ini?
</div>
HTML;

            $this->dispatch('swal:confirm', [
                'title' => 'Konfirmasi Pembayaran Kasbon',
                'html' => $html,
                'icon' => 'question',
                'confirmButtonText' => 'Ya, Bayar Sekarang',
                'cancelButtonText' => 'Batal',
                'confirmButtonColor' => '#0b52aa',
                'action' => 'confirm-early-payment',
            ]);

            return;
        }

        try {
            $user = auth()->user();

            $payments = $service->recordMonthlySettlement(
                employeeId: $this->selectedEmployeeId,
                paymentData: [
                    'month_keys' => $monthKeys,
                    'amount' => (float) $this->earlyPaymentAmount,
                    'payment_date' => $this->earlyPaymentDate,
                    'payment_method' => $this->earlyPaymentMethod,
                    'proof_document' => $this->earlyProofDocument,
                    'notes' => $this->earlyPaymentNotes,
                ],
                user: $user
            );

            $totalPaid = array_sum(array_map(fn ($p) => (float) $p->amount, $payments));
            $formattedTotal = 'Rp '.number_format($totalPaid, 0, ',', '.');
            $monthsCount = count($monthKeys);

            $this->dispatch('close-modal', id: 'earlyPaymentModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Pembayaran Berhasil Dicatat!',
                'message' => "Pembayaran tagihan untuk {$monthsCount} bulan sebesar {$formattedTotal} berhasil dicatat.",
            ]);

            $this->selectedMonthKeysForPayment = [];
            $this->earlyPaymentAmount = null;
            $this->earlyProofDocument = null;
            $this->earlyPaymentNotes = '';
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Melakukan Pelunasan!',
                'message' => $this->safeErrorMessage($e, 'Terjadi kesalahan sistem saat memproses pembayaran tagihan.'),
            ]);
        }
    }

    /**
     * Confirmed handler from SweetAlert2 modal for early payment.
     */
    #[On('confirm-early-payment')]
    public function confirmEarlyPayment(CashAdvanceService $service): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya admin yang dapat mencatat pelunasan kasbon.');

        $this->storeEarlyPayment($service, confirmed: true);
    }

    private function safeErrorMessage(Throwable $exception, string $fallback): string
    {
        return $exception instanceof InvalidArgumentException && $exception->getMessage()
            ? $exception->getMessage()
            : $fallback;
    }

    /**
     * Render the Livewire component view.
     */
    public function render(CashAdvanceService $service): View
    {
        if ($this->selectedCashAdvance instanceof CashAdvance) {
            $this->selectedCashAdvance->load([
                'employee.department',
                'employee.position',
                'submitter',
                'approver',
                'installments.payments.recorder',
                'payments.recorder',
            ]);
        }

        $isKaryawan = auth()->user()?->hasRole('karyawan');
        $karyawanRecord = null;
        if ($isKaryawan) {
            $karyawanRecord = auth()->user()->getEmployeeRecord();
            $this->selectedEmployeeId = $karyawanRecord?->id;
            $this->selectedDepartmentId = $karyawanRecord?->department_id;
        }

        $departments = Department::query()->orderBy('name', 'asc')->get();

        $employeesQuery = Employee::query()
            ->select(['id', 'full_name', 'department_id', 'position_id'])
            ->where('status', 'active')
            ->with([
                'department:id,name',
                'position:id,name',
                'detail:employee_id,employee_code',
            ])
            ->orderBy('full_name', 'asc');

        if ($isKaryawan && $karyawanRecord) {
            $employeesQuery->where('id', $karyawanRecord->id);
        } elseif ($this->selectedDepartmentId) {
            $employeesQuery->where('department_id', $this->selectedDepartmentId);
        }

        $employees = $employeesQuery->get();

        if ($this->selectedEmployeeId && $employees->isNotEmpty() && ! $employees->contains('id', $this->selectedEmployeeId)) {
            $this->selectedEmployeeId = null;
        }

        if ($isKaryawan && $karyawanRecord) {
            $approvedAdvances = CashAdvance::where('employee_id', $karyawanRecord->id)
                ->whereIn('status', [CashAdvanceStatus::APPROVED->value, CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::PAID->value]);
            $totalApproved = (float) $approvedAdvances->sum('amount');
            $totalRemaining = (float) $approvedAdvances->sum('remaining_amount');
            $totalPaid = $totalApproved - $totalRemaining;
            $approvedCount = $approvedAdvances->count();
            $paidCount = CashAdvancePayment::whereHas('cashAdvance', fn ($q) => $q->where('employee_id', $karyawanRecord->id))->count();

            $pendingAdvances = CashAdvance::where('employee_id', $karyawanRecord->id)
                ->where('status', CashAdvanceStatus::PENDING->value);
            $pendingCount = $pendingAdvances->count();
            $pendingAmount = (float) $pendingAdvances->sum('amount');

            $activePaidPct = $totalApproved > 0 ? (int) round(($totalPaid / $totalApproved) * 100) : 0;

            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth()->toDateString();
            $endOfMonth = $now->copy()->endOfMonth()->toDateString();

            $myMonthlyInstallment = (float) CashAdvanceInstallment::whereHas('cashAdvance', fn ($q) => $q->where('employee_id', $karyawanRecord->id)->whereIn('status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value]))
                ->whereDate('due_date', '>=', $startOfMonth)
                ->whereDate('due_date', '<=', $endOfMonth)
                ->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                ->selectRaw('SUM(amount - paid_amount) as remaining')
                ->value('remaining') ?? 0.0;

            $myOverdueCount = (int) CashAdvanceInstallment::whereHas('cashAdvance', fn ($q) => $q->where('employee_id', $karyawanRecord->id)->whereIn('status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value]))
                ->whereDate('due_date', '<', $startOfMonth)
                ->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                ->count();

            $companyStats = [
                'total_approved_amount' => $totalApproved,
                'formatted_total_approved_amount' => 'Rp '.number_format($totalApproved, 0, ',', '.'),
                'total_approved_count' => $approvedCount,
                'total_paid_amount' => $totalPaid,
                'formatted_total_paid_amount' => 'Rp '.number_format($totalPaid, 0, ',', '.'),
                'total_paid_transactions_count' => $paidCount,
                'total_remaining_balance' => $totalRemaining,
                'formatted_total_remaining_balance' => 'Rp '.number_format($totalRemaining, 0, ',', '.'),
                'compact_total_remaining_balance' => $totalRemaining >= 1000000 ? 'Rp '.number_format($totalRemaining / 1000000, 1, ',', '.').' jt' : 'Rp '.number_format($totalRemaining, 0, ',', '.'),
                'total_employees_with_active_advances' => $totalRemaining > 0 ? 1 : 0,
                'total_employees_count' => 1,
                'total_pending_count' => $pendingCount,
                'active_paid_percentage' => $activePaidPct,
                'pending_amount' => $pendingAmount,
                'formatted_pending_amount' => 'Rp '.number_format($pendingAmount, 0, ',', '.'),
                'payroll_deduction_this_month' => $myMonthlyInstallment,
                'formatted_payroll_deduction_this_month' => 'Rp '.number_format($myMonthlyInstallment, 0, ',', '.'),
                'payroll_deduction_employees_count' => $myMonthlyInstallment > 0 ? 1 : 0,
                'current_month_short' => $now->translatedFormat('M'),
                'attention_employees_count' => $myOverdueCount > 0 ? 1 : 0,
                'attention_subtitle' => $myOverdueCount > 0 ? 'Cicilan terlewat bulan lalu' : 'Semua cicilan lancar',
            ];
        } else {
            $companyStats = $service->getSummaryStats();
        }

        $recap = ! $this->selectedEmployeeId
            ? $service->getEmployeesCashAdvanceSummary(
                search: $this->search ?: null,
                departmentId: $this->selectedDepartmentId,
                filter: $this->recapFilter,
                perPage: $this->perPage,
                page: $this->getPage()
            )
            : null;

        $summary = $service->getEmployeeSummary($this->selectedEmployeeId);

        $statements = $this->selectedEmployeeId && $this->activeMainTab === 'statements'
            ? $service->getEmployeeMonthlyStatements(
                employeeId: $this->selectedEmployeeId,
                search: $this->search ?: null,
                perPage: $this->perPage,
                page: $this->getPage()
            )
            : null;

        $ledger = $this->selectedEmployeeId && $this->activeMainTab === 'ledger'
            ? $service->getEmployeeLedger(
                employeeId: $this->selectedEmployeeId,
                search: $this->search ?: null,
                perPage: $this->perPage,
                page: $this->getPage()
            )
            : null;

        $activeAdvance = $this->selectedEmployeeId
            ? $service->getActiveAdvanceByEmployee($this->selectedEmployeeId)
            : null;

        $pendingReviewAdvances = $service->getPendingAdvancesForReview();
        $pendingReviewPayments = $service->getPendingPaymentsForReview();
        $overdueReviewAdvances = $service->getOverdueAdvancesForReview();
        $chartData = $service->getRecapChartData();

        return view('livewire.cash-advances.index', [
            'departments' => $departments,
            'employees' => $employees,
            'companyStats' => $companyStats,
            'pendingReviewAdvances' => $pendingReviewAdvances,
            'pendingReviewPayments' => $pendingReviewPayments,
            'overdueReviewAdvances' => $overdueReviewAdvances,
            'chartData' => $chartData,
            'recap' => $recap,
            'summary' => $summary,
            'statements' => $statements,
            'ledger' => $ledger,
            'activeAdvance' => $activeAdvance,
            'currentEmployee' => $summary['employee'],
        ]);
    }
}
