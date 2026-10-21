<?php

namespace App\Livewire\Payrolls;

use App\Enums\EmployeeStatus;
use App\Enums\PayrollLineType;
use App\Enums\SalaryComponentCategory;
use App\Enums\SalaryComponentType;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Services\ActivityLogService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Detail Penggajian - Larashiz HRM')]
class Show extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public PayrollPeriod $period;

    protected ?array $payrollItemCountsCache = null;

    // Search, filter, and pagination
    public string $search = '';

    public int $perPage = 10;

    // Manage Component Modal Properties
    public ?PayrollItem $selectedItem = null;

    // Dynamic Repeater Rows for Components (Tunjangan & Potongan)
    public array $allowanceRows = [];

    public array $deductionRows = [];

    // Director Approval / Reject Modal Properties
    public string $rejectionReason = '';

    // Dynamic Multi-Select & Individual Item Approval Properties
    public array $selectedItems = [];

    public bool $selectAll = false;

    public ?int $rejectingItemId = null;

    public string $itemRejectionReason = '';

    public bool $isBulkReject = false;

    // Bulk Payment Modal Properties
    public string $paymentDate = '';

    public string $paymentMethod = 'bank_transfer';

    public string $paymentNotes = '';

    // Individual Employee Payment Modal Properties
    public ?PayrollItem $payingItem = null;

    public string $itemPaymentDate = '';

    public string $itemPaymentMethod = 'bank_transfer';

    public string $itemBankName = '';

    public string $itemBankAccountNumber = '';

    public string $itemBankAccountName = '';

    public string $itemEwalletProvider = 'GoPay';

    public string $itemEwalletNumber = '';

    public $itemPaymentProof = null;

    public string $itemPaymentNotes = '';

    public bool $isEditingPayment = false;

    // Payment Detail Modal
    public ?PayrollItem $paidDetailItem = null;

    // Slip Preview Modal
    public ?PayrollItem $previewItem = null;

    public function mount(PayrollPeriod $period): void
    {
        $this->period = $period->load(['creator', 'submitter', 'approver', 'updater']);
        $this->paymentDate = Carbon::now()->toDateString();

        if ($this->period->status->isEditable()) {
            $this->cleanupTrashedEmployees();
        }
    }

    /**
     * Remove any soft-deleted or orphaned employees from editable draft periods.
     */
    protected function cleanupTrashedEmployees(): void
    {
        $trashedItems = $this->period->items()
            ->where(function ($q) {
                $q->whereHas('employee', fn ($sub) => $sub->onlyTrashed())
                    ->orWhereDoesntHave('employee');
            })
            ->get();

        if ($trashedItems->isNotEmpty()) {
            $service = app(PayrollService::class);
            $removedItemIds = [];
            foreach ($trashedItems as $item) {
                $removedItemIds[] = $item->id;
                $item->lines()->delete();
                $item->delete();
            }
            $service->recalculatePeriod($this->period);
            app(ActivityLogService::class)->info(
                action: 'ORPHANED_PAYSLIPS_CLEANED',
                module: 'payrolls',
                message: 'Orphaned payroll slips cleaned from editable period',
                context: [
                    'payroll_period_id' => $this->period->id,
                    'reference_number' => $this->period->reference_number,
                    'payroll_item_ids' => $removedItemIds,
                    'removed_count' => count($removedItemIds),
                ],
                userId: auth()->id()
            );
            $this->refreshPeriod();
        }
    }

    public function refreshPeriod(): void
    {
        $this->period = PayrollPeriod::query()
            ->with(['creator', 'submitter', 'approver', 'updater'])
            ->findOrFail($this->period->id);
        $this->payrollItemCountsCache = null;
    }

    /**
     * Active employees not yet in this draft period.
     */
    public function getMissingEmployeesProperty(): Collection
    {
        if (! $this->period->status->isEditable()) {
            return collect();
        }

        return Employee::query()
            ->where('status', EmployeeStatus::ACTIVE)
            ->whereDoesntHave('payrollItems', fn ($query) => $query->where('payroll_period_id', $this->period->id))
            ->employedDuring($this->period->start_date->toDateString(), $this->period->end_date->toDateString())
            ->with(['detail', 'position', 'department'])
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Synchronize all missing active employees into this draft period.
     */
    public function syncMissingEmployees(PayrollService $service): void
    {
        if (! $this->period->status->isEditable()) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Dapat Diubah',
                'message' => 'Status periode penggajian tidak dalam tahap draf atau revisi.',
            ]);

            return;
        }

        try {
            $created = $service->syncMissingEmployees($this->period, auth()->user());
            $count = $created->count();

            if ($count === 0) {
                $this->dispatch('swal:alert', [
                    'type' => 'info',
                    'title' => 'Sudah Sinkron',
                    'message' => 'Tidak ada karyawan aktif baru yang perlu ditambahkan.',
                ]);

                return;
            }

            $this->refreshPeriod();
            $this->resetPage();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Karyawan Ditambahkan',
                'message' => "Berhasil menambahkan {$count} karyawan ke draf penggajian dengan perhitungan gaji prorata.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menambahkan Karyawan',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Synchronize all existing employees' basic salaries and bank details with master data.
     */
    public function syncWithMasterEmployees(PayrollService $service): void
    {
        if (! $this->period->status->isEditable()) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Dapat Diubah',
                'message' => 'Hanya periode berstatus Draf atau Ditolak yang dapat disinkronkan.',
            ]);

            return;
        }

        try {
            $count = $service->syncPeriodWithMasterEmployees($this->period, auth()->user());
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Gaji Pokok Disinkronkan',
                'message' => "Berhasil menyinkronkan data gaji pokok {$count} karyawan dari Master Data Karyawan.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Sinkronisasi',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Master Salary Components list for quick selection.
     */
    public function getMasterComponentsProperty(): Collection
    {
        return SalaryComponent::getActiveLookup()->sortBy(['type', 'name'])->values();
    }

    /**
     * Master Allowance Components list.
     */
    public function getMasterAllowancesProperty(): Collection
    {
        return SalaryComponent::getActiveLookup()
            ->filter(fn ($comp) => $comp->type === SalaryComponentType::ALLOWANCE
                && $comp->category !== SalaryComponentCategory::DEDUCTION
                && ! in_array($comp->code, ['GAJI_POKOK', 'TUNJ_PPH21', 'BPJS_KES', 'BPJS_JHT', 'BPJS_JP', 'PPH21', 'KASBON', 'POT-KASBON'], true)
            )
            ->sortBy('name')
            ->values();
    }

    /**
     * Master Deduction Components list.
     */
    public function getMasterDeductionsProperty(): Collection
    {
        return SalaryComponent::getActiveLookup()
            ->filter(fn ($comp) => $comp->type === SalaryComponentType::DEDUCTION
                && $comp->category === SalaryComponentCategory::DEDUCTION
                && ! $comp->is_system
                && ! in_array($comp->code, ['PPH21', 'KASBON', 'POT-KASBON', 'BPJS_KES', 'BPJS_JHT', 'BPJS_JP'], true)
            )
            ->sortBy('name')
            ->values();
    }

    /**
     * Apply a master component to a specific allowance row.
     */
    public function applyMasterAllowance(int $index, mixed $componentId): void
    {
        if (! isset($this->allowanceRows[$index]) || empty($componentId)) {
            return;
        }

        $component = SalaryComponent::find($componentId);
        if ($component) {
            $this->allowanceRows[$index]['salary_component_id'] = $component->id;
            $this->allowanceRows[$index]['name'] = $component->name;
        }
    }

    /**
     * Apply a master component to a specific deduction row.
     */
    public function applyMasterDeduction(int $index, mixed $componentId): void
    {
        if (! isset($this->deductionRows[$index]) || empty($componentId)) {
            return;
        }

        $component = SalaryComponent::find($componentId);
        if ($component) {
            $this->deductionRows[$index]['salary_component_id'] = $component->id;
            $this->deductionRows[$index]['name'] = $component->name;
        }
    }

    /**
     * Open component management modal for a specific employee.
     */
    public function openComponentModal(int $itemId): void
    {
        $this->selectedItem = PayrollItem::with([
            'employee.department',
            'employee.position',
            'employee.detail',
            'lines.salaryComponent',
            'lines.cashAdvanceInstallment',
        ])->findOrFail($itemId);

        $this->allowanceRows = [];
        $this->deductionRows = [];

        foreach ($this->selectedItem->lines as $line) {
            $rowData = [
                'id' => $line->id,
                'name' => $line->name,
                'amount' => (float) $line->amount,
                'salary_component_id' => $line->salary_component_id,
                'cash_advance_installment_id' => $line->cash_advance_installment_id,
                'notes' => $line->notes ?? '',
            ];

            if ($line->type === PayrollLineType::ALLOWANCE) {
                $this->allowanceRows[] = $rowData;
            } else {
                $this->deductionRows[] = $rowData;
            }
        }

        if (empty($this->allowanceRows)) {
            $this->addAllowanceRow();
        }

        if (empty($this->deductionRows)) {
            $this->addDeductionRow();
        }

        $this->resetValidation();
        $this->dispatch('open-modal', ['id' => 'manageComponentModal']);
    }

    public function addAllowanceRow(): void
    {
        $this->allowanceRows[] = [
            'id' => null,
            'name' => '',
            'amount' => '',
            'salary_component_id' => null,
            'cash_advance_installment_id' => null,
            'notes' => '',
        ];
    }

    public function removeAllowanceRow(int $index): void
    {
        unset($this->allowanceRows[$index]);
        $this->allowanceRows = array_values($this->allowanceRows);
    }

    public function addDeductionRow(): void
    {
        $this->deductionRows[] = [
            'id' => null,
            'name' => '',
            'amount' => '',
            'salary_component_id' => null,
            'cash_advance_installment_id' => null,
            'notes' => '',
        ];
    }

    public function removeDeductionRow(int $index): void
    {
        unset($this->deductionRows[$index]);
        $this->deductionRows = array_values($this->deductionRows);
    }

    public function getTotalAllowancesProperty(): float
    {
        $sum = 0;
        foreach ($this->allowanceRows as $row) {
            $sum += (float) ($row['amount'] ?? 0);
        }

        return $sum;
    }

    public function getTotalDeductionsProperty(): float
    {
        $sum = 0;
        foreach ($this->deductionRows as $row) {
            $sum += (float) ($row['amount'] ?? 0);
        }

        return $sum;
    }

    public function getEstimatedNetSalaryProperty(): float
    {
        $basic = (float) ($this->selectedItem?->basic_salary ?? 0);

        return max(0, $basic + $this->totalAllowances - $this->totalDeductions);
    }

    public function saveComponents(PayrollService $service): void
    {
        if (! $this->selectedItem) {
            return;
        }

        try {
            $service->syncItemLines(
                $this->selectedItem,
                $this->allowanceRows,
                $this->deductionRows,
                auth()->user()
            );

            $this->selectedItem->refresh()->load('lines');
            $this->refreshPeriod();
            $this->dispatch('close-modal', ['id' => 'manageComponentModal']);

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Komponen tunjangan dan potongan berhasil diperbarui.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Quick toggle or mark payroll item as verified by HRD.
     */
    public function verifyItem(int $itemId, PayrollService $service): void
    {
        try {
            $item = PayrollItem::where('payroll_period_id', $this->period->id)->findOrFail($itemId);
            $service->verifyItem($item, auth()->user());
            $this->refreshPeriod();

            $empName = $item->employee?->full_name ?? 'Karyawan';
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Verifikasi Berhasil',
                'message' => "Slip gaji {$empName} berhasil ditandai sebagai Terverifikasi HRD.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memverifikasi',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel HRD verification back to draft.
     */
    public function unverifyItem(int $itemId, PayrollService $service): void
    {
        try {
            $item = PayrollItem::where('payroll_period_id', $this->period->id)->findOrFail($itemId);
            $service->unverifyItem($item, auth()->user());
            $this->refreshPeriod();

            $empName = $item->employee?->full_name ?? 'Karyawan';
            $this->dispatch('swal:alert', [
                'type' => 'info',
                'title' => 'Verifikasi Dibatalkan',
                'message' => "Status slip gaji {$empName} dikembalikan ke Draf.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Membatalkan Verifikasi',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bulk verify all payroll items in this period.
     */
    public function verifyAllItems(PayrollService $service): void
    {
        try {
            $count = $service->verifyAllItems($this->period, auth()->user());
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Semua Karyawan Terverifikasi',
                'message' => "Sebanyak {$count} slip gaji karyawan berhasil diverifikasi oleh HRD.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memverifikasi Semua',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getVerifiedCountProperty(): int
    {
        return $this->getPayrollItemCounts()['verified'];
    }

    public function getTotalItemsCountProperty(): int
    {
        return $this->getPayrollItemCounts()['total'];
    }

    public function getPaidItemsCountProperty(): int
    {
        return $this->getPayrollItemCounts()['paid'];
    }

    /**
     * @return array{total: int, verified: int, paid: int}
     */
    private function getPayrollItemCounts(): array
    {
        if ($this->payrollItemCountsCache === null) {
            $counts = $this->period->items()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('COUNT(CASE WHEN verified_at IS NOT NULL THEN 1 END) as verified')
                ->selectRaw('COUNT(CASE WHEN payment_status = ? THEN 1 END) as paid', ['paid'])
                ->first();

            $this->payrollItemCountsCache = [
                'total' => (int) $counts->total,
                'verified' => (int) $counts->verified,
                'paid' => (int) $counts->paid,
            ];
        }

        return $this->payrollItemCountsCache;
    }

    public function getPaymentProgressPercentProperty(): int
    {
        $total = $this->totalItemsCount;

        return $total > 0 ? (int) round(($this->paidItemsCount / $total) * 100) : 0;
    }

    public function getAllVerifiedProperty(): bool
    {
        $total = $this->totalItemsCount;

        return $total > 0 && $this->verifiedCount === $total;
    }

    public function getVerificationPercentProperty(): int
    {
        $total = $this->totalItemsCount;

        return $total > 0 ? (int) round(($this->verifiedCount / $total) * 100) : 0;
    }

    /**
     * Submit period for approval (Admin).
     */
    #[On('submit-period-confirmed')]
    public function submitForApproval(PayrollService $service): void
    {
        try {
            $service->submitPeriod($this->period, auth()->user());
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil Diajukan!',
                'message' => 'Periode penggajian telah diajukan ke Direktur untuk disetujui.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mengajukan!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Approve period (Direktur).
     */
    #[On('approve-period-confirmed')]
    public function approvePeriod(PayrollService $service): void
    {
        try {
            $service->approvePeriod($this->period, auth()->user());
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Disetujui!',
                'message' => 'Penggajian periode ini telah disetujui.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open reject modal.
     */
    public function openRejectModal(): void
    {
        $this->rejectionReason = '';
        $this->dispatch('open-modal', ['id' => 'rejectModal']);
    }

    /**
     * Reject period (Direktur).
     */
    public function rejectPeriod(PayrollService $service): void
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:5|max:500',
        ], [
            'rejectionReason.required' => 'Alasan penolakan wajib diisi.',
            'rejectionReason.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        try {
            $service->rejectPeriod($this->period, $this->rejectionReason, auth()->user());
            $this->dispatch('close-modal', ['id' => 'rejectModal']);
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'info',
                'title' => 'Ditolak',
                'message' => 'Periode dikembalikan ke status draf untuk diperbaiki oleh Admin.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open payment modal (Admin).
     */
    public function openPaymentModal(): void
    {
        $this->paymentDate = Carbon::now()->toDateString();
        $this->paymentMethod = 'bank_transfer';
        $this->paymentNotes = '';
        $this->dispatch('open-modal', ['id' => 'paymentModal']);
    }

    /**
     * Record payment and execute cash advance deductions.
     */
    public function processPayment(PayrollService $service): void
    {
        $this->validate([
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|in:bank_transfer,cash',
            'paymentNotes' => 'nullable|string|max:500',
        ], [
            'paymentDate.required' => 'Tanggal pembayaran wajib diisi.',
        ]);

        try {
            $service->markAsPaid($this->period, [
                'payment_date' => $this->paymentDate,
                'payment_method' => $this->paymentMethod,
                'notes' => $this->paymentNotes,
            ], auth()->user());

            $this->dispatch('close-modal', ['id' => 'paymentModal']);
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Pembayaran Berhasil Dicatat!',
                'message' => 'Status penggajian telah menjadi Dibayar dan seluruh pemotongan cicilan kasbon telah otomatis tercatat.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memproses Pembayaran!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open individual item payment modal.
     */
    public function openPayItemModal(int $itemId): void
    {
        $item = PayrollItem::with(['employee.detail', 'payrollPeriod'])->findOrFail($itemId);

        if (! $item->isApproved() && $item->payrollPeriod->status->value !== 'approved') {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Belum Disetujui!',
                'message' => 'Slip gaji karyawan ini belum disetujui sehingga belum dapat dibayarkan.',
            ]);

            return;
        }

        if ($item->payment_status === 'paid') {
            $this->dispatch('swal:alert', [
                'type' => 'info',
                'title' => 'Sudah Dibayar',
                'message' => 'Gaji karyawan ini sudah berstatus dibayar.',
            ]);

            return;
        }

        $this->payingItem = $item;
        $this->isEditingPayment = false;
        $this->itemPaymentDate = Carbon::now()->toDateString();

        $detail = $item->employee?->detail;

        $this->itemPaymentMethod = 'bank_transfer';
        $this->itemBankName = $item->bank_name ?: ($detail?->bank_name ?? '');
        $this->itemBankAccountNumber = $item->bank_account_number ?: ($detail?->bank_account_number ?? $detail?->phone ?? '');
        $this->itemBankAccountName = $item->bank_account_name ?: ($detail?->bank_account_name ?? $item->employee?->full_name ?? '');
        $this->itemPaymentProof = null;
        $this->itemPaymentNotes = '';

        $this->dispatch('open-modal', ['id' => 'payItemModal']);
    }

    /**
     * Open payment edit modal for an already paid item.
     */
    public function openEditItemPaymentModal(int $itemId): void
    {
        $item = PayrollItem::with(['employee.detail', 'payrollPeriod'])->findOrFail($itemId);

        $this->payingItem = $item;
        $this->isEditingPayment = true;
        $this->itemPaymentDate = $item->paid_at ? $item->paid_at->format('Y-m-d') : Carbon::now()->toDateString();
        $this->itemPaymentMethod = in_array($item->payment_method, ['bank_transfer', 'cash']) ? $item->payment_method : 'bank_transfer';
        $this->itemBankName = $item->bank_name ?? '';
        $this->itemBankAccountNumber = $item->bank_account_number ?? '';
        $this->itemBankAccountName = $item->bank_account_name ?? '';
        $this->itemPaymentProof = null;
        $this->itemPaymentNotes = $item->payment_notes ?? '';

        $this->dispatch('close-modal', ['id' => 'paymentDetailModal']);
        $this->dispatch('open-modal', ['id' => 'payItemModal']);
    }

    /**
     * Process individual item payment or payment update.
     */
    public function processItemPayment(PayrollService $service): void
    {
        if (! $this->payingItem) {
            return;
        }

        $rules = [
            'itemPaymentDate' => 'required|date',
            'itemPaymentMethod' => 'required|in:bank_transfer,cash',
            'itemPaymentProof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:3072',
            'itemPaymentNotes' => 'nullable|string|max:500',
        ];

        if ($this->itemPaymentMethod === 'bank_transfer') {
            $rules['itemBankName'] = 'required|string|max:100';
            $rules['itemBankAccountNumber'] = 'required|string|max:100';
            $rules['itemBankAccountName'] = 'nullable|string|max:150';
        }

        $this->validate($rules, [
            'itemPaymentDate.required' => 'Tanggal pembayaran wajib diisi.',
            'itemPaymentMethod.required' => 'Metode pembayaran wajib dipilih.',
            'itemBankName.required' => 'Nama bank atau e-wallet wajib diisi.',
            'itemBankAccountNumber.required' => 'Nomor rekening atau nomor HP wajib diisi.',
            'itemPaymentProof.max' => 'Ukuran file bukti pembayaran maksimal 3MB.',
            'itemPaymentProof.mimes' => 'Format bukti pembayaran harus JPG, PNG, atau PDF.',
        ]);

        try {
            $proofPath = null;
            if ($this->itemPaymentProof) {
                $proofPath = $this->itemPaymentProof->store('payroll-proofs', 'public');
            }

            $bankName = $this->itemPaymentMethod === 'bank_transfer' ? $this->itemBankName : null;
            $bankAccNumber = $this->itemPaymentMethod === 'bank_transfer' ? $this->itemBankAccountNumber : null;
            $bankAccName = $this->itemPaymentMethod === 'bank_transfer' ? $this->itemBankAccountName : null;

            if ($this->isEditingPayment && $this->payingItem->isPaid()) {
                $service->updateItemPayment($this->payingItem, [
                    'payment_date' => $this->itemPaymentDate,
                    'payment_method' => $this->itemPaymentMethod,
                    'payment_proof_path' => $proofPath,
                    'payment_notes' => $this->itemPaymentNotes,
                    'bank_name' => $bankName,
                    'bank_account_number' => $bankAccNumber,
                    'bank_account_name' => $bankAccName,
                ], auth()->user());

                $message = 'Informasi pembayaran gaji berhasil diperbarui.';
                $title = 'Pembayaran Diperbarui!';
            } else {
                $service->markItemAsPaid($this->payingItem, [
                    'payment_date' => $this->itemPaymentDate,
                    'payment_method' => $this->itemPaymentMethod,
                    'payment_proof_path' => $proofPath,
                    'payment_notes' => $this->itemPaymentNotes,
                    'bank_name' => $bankName,
                    'bank_account_number' => $bankAccNumber,
                    'bank_account_name' => $bankAccName,
                ], auth()->user());

                $message = 'Status gaji karyawan telah berhasil ditandai sebagai Dibayar.';
                $title = 'Pembayaran Berhasil Dicatat!';
            }

            $this->dispatch('close-modal', ['id' => 'payItemModal']);
            $this->refreshPeriod();
            $this->payingItem = null;
            $this->itemPaymentProof = null;
            $this->isEditingPayment = false;

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => $title,
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memproses Pembayaran!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open payment detail and receipt proof modal.
     */
    public function openPaymentDetailModal(int $itemId): void
    {
        $this->paidDetailItem = PayrollItem::with([
            'employee.department',
            'employee.position',
            'payer',
            'updater',
        ])->findOrFail($itemId);

        $this->dispatch('open-modal', ['id' => 'paymentDetailModal']);
    }

    /**
     * Open preview payslip modal.
     */
    public function openPreviewSlip(int $itemId): void
    {
        $this->previewItem = PayrollItem::with([
            'employee.department',
            'employee.position',
            'employee.detail',
            'lines',
            'payrollPeriod',
            'payer',
            'approver',
        ])->findOrFail($itemId);

        $this->dispatch('open-modal', ['id' => 'previewSlipModal']);
    }

    /**
     * Generate WhatsApp click-to-chat link for an employee's payslip.
     */
    public function getWhatsappLink(PayrollItem $item): string
    {
        $phone = $item->employee->detail?->phone ?? '';
        $cleanedPhone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($cleanedPhone, '0')) {
            $cleanedPhone = '62'.substr($cleanedPhone, 1);
        }

        $company = CompanyProfile::getProfile();
        $message = "Halo {$item->employee->full_name},\n\n";
        $message .= "Berikut adalah ringkasan slip gaji Anda untuk {$item->payrollPeriod->period_label} dari {$company->name}:\n";
        $message .= "• No Slip: {$item->payslip_number}\n";
        $message .= "• Gaji Pokok: {$item->formatted_basic_salary}\n";
        $message .= "• Total Tunjangan (+): {$item->formatted_total_allowances}\n";
        $message .= "• Total Potongan (-): {$item->formatted_total_deductions}\n";
        $message .= "• Gaji Bersih (Take Home Pay): {$item->formatted_net_salary}\n\n";
        $message .= 'Status Pembayaran: '.($item->payment_status === 'paid' ? 'LUNAS / DIBAYAR' : 'PROSES')."\n";
        $message .= 'Terima kasih atas kerja keras Anda!';

        return 'https://wa.me/'.$cleanedPhone.'?text='.rawurlencode($message);
    }

    /**
     * Recalculate tax and BPJS for all payroll items in this period.
     */
    public function recalculateAllTaxes(PayrollService $service): void
    {
        if (! $this->period->status->isEditable()) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Dapat Diubah',
                'message' => 'Status penggajian tidak dalam tahap draf atau revisi.',
            ]);

            return;
        }

        try {
            $service->recalculateTaxesForPeriod($this->period, auth()->user());

            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Pajak Diperbarui',
                'message' => 'Kalkulasi PPh 21 untuk seluruh karyawan pada periode ini berhasil dihitung ulang.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghitung Pajak',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedItems = $this->period->items()
                ->when($this->search, function ($q) {
                    $q->whereHas('employee', fn ($sq) => $sq->where('full_name', 'like', "%{$this->search}%"));
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            $this->selectedItems = [];
        }
    }

    public function approveItem(int $itemId, PayrollService $service): void
    {
        try {
            $item = PayrollItem::where('payroll_period_id', $this->period->id)->findOrFail($itemId);
            $service->approveItem($item, auth()->user());
            $this->refreshPeriod();

            $empName = $item->employee?->full_name ?? 'Karyawan';
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Slip Disetujui!',
                'message' => "Slip gaji {$empName} berhasil disetujui (ACC).",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyetujui!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    #[On('approve-payroll-item-from-menu')]
    public function approveItemFromMenu(mixed $id, PayrollService $service): void
    {
        $itemId = is_array($id) ? (int) ($id['id'] ?? 0) : (int) $id;

        $this->approveItem($itemId, $service);
    }

    #[On('unapprove-payroll-item')]
    public function unapproveItem(mixed $id, PayrollService $service): void
    {
        $itemId = is_array($id) ? (int) ($id['id'] ?? 0) : (int) $id;

        try {
            $item = $this->period->items()->with('employee')->findOrFail($itemId);
            $employeeName = $item->employee?->full_name ?? 'Karyawan';

            $service->unapproveItem($item, auth()->user());
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Persetujuan Dibatalkan',
                'message' => "Persetujuan slip gaji {$employeeName} dibatalkan dan dikembalikan ke Menunggu ACC.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Membatalkan Persetujuan',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger confirmation dialog to remove employee from editable draft payroll period.
     */
    public function confirmRemoveItem(int $itemId): void
    {
        $item = PayrollItem::with('employee')->where('payroll_period_id', $this->period->id)->find($itemId);
        if (! $item) {
            return;
        }

        $empName = $item->employee?->full_name ?? 'Karyawan';

        $this->dispatch('swal:confirm-delete', [
            'id' => $itemId,
            'title' => $empName,
            'text' => "Karyawan \"{$empName}\" akan dihapus dari draf penggajian periode ini.",
            'action' => 'delete-payroll-item',
        ]);
    }

    /**
     * Remove employee from editable draft payroll period.
     */
    #[On('delete-payroll-item')]
    public function removeItem(mixed $id, PayrollService $service): void
    {
        $itemId = is_array($id) ? ($id['id'] ?? 0) : (int) $id;

        try {
            $item = PayrollItem::where('payroll_period_id', $this->period->id)->findOrFail($itemId);
            $empName = $item->employee?->full_name ?? 'Karyawan';
            $service->removeItemFromPeriod($item, auth()->user());
            $this->refreshPeriod();
            $this->resetPage();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Karyawan \"{$empName}\" berhasil dihapus dari draf penggajian.",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus Karyawan',
                'message' => $e->getMessage(),
            ]);
        }
    }

    #[On('open-payroll-reject-modal')]
    public function openRejectItemModal(mixed $id = null): void
    {
        $itemId = is_array($id) ? (int) ($id['id'] ?? 0) : (int) $id;
        $this->rejectingItemId = $itemId;
        $this->itemRejectionReason = '';
        $this->isBulkReject = false;
        $this->dispatch('open-modal', ['id' => 'rejectItemModal']);
    }

    #[On('reject-payroll-item-from-menu')]
    public function rejectItemFromMenu(mixed $id, mixed $reason, PayrollService $service): void
    {
        $this->rejectingItemId = is_array($id) ? (int) ($id['id'] ?? 0) : (int) $id;
        $this->itemRejectionReason = trim((string) $reason);
        $this->isBulkReject = false;

        try {
            $this->validate([
                'itemRejectionReason' => 'required|string|min:3|max:500',
            ], [
                'itemRejectionReason.required' => 'Alasan penolakan / revisi wajib diisi.',
                'itemRejectionReason.min' => 'Alasan penolakan minimal 3 karakter.',
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Alasan Revisi Belum Valid',
                'message' => $e->validator->errors()->first(),
            ]);

            return;
        }

        $this->confirmRejectItem($service);
    }

    public function openRejectSelectedModal(): void
    {
        if (empty($this->selectedItems)) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Tidak Ada Karyawan Dipilih',
                'message' => 'Pilih minimal satu karyawan untuk ditolak/diminta revisi.',
            ]);

            return;
        }

        $this->rejectingItemId = null;
        $this->itemRejectionReason = '';
        $this->isBulkReject = true;
        $this->dispatch('open-modal', ['id' => 'rejectItemModal']);
    }

    public function confirmRejectItem(PayrollService $service): void
    {
        $this->validate([
            'itemRejectionReason' => 'required|string|min:3|max:500',
        ], [
            'itemRejectionReason.required' => 'Alasan penolakan / revisi wajib diisi.',
            'itemRejectionReason.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        try {
            if ($this->isBulkReject) {
                $count = $service->rejectItems($this->period, $this->selectedItems, $this->itemRejectionReason, auth()->user());
                $this->selectedItems = [];
                $this->selectAll = false;
                $msg = "{$count} slip karyawan berhasil ditolak untuk revisi.";
            } else {
                $item = PayrollItem::where('payroll_period_id', $this->period->id)->findOrFail($this->rejectingItemId);
                $service->rejectItem($item, $this->itemRejectionReason, auth()->user());
                $msg = "Slip gaji {$item->employee->full_name} berhasil ditolak untuk revisi.";
            }

            $this->dispatch('close-modal', ['id' => 'rejectItemModal']);
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'info',
                'title' => 'Slip Ditolak untuk Revisi',
                'message' => $msg,
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function approveSelected(PayrollService $service): void
    {
        if (empty($this->selectedItems)) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Tidak Ada Karyawan Dipilih',
                'message' => 'Pilih minimal satu karyawan untuk disetujui.',
            ]);

            return;
        }

        try {
            $count = $service->approveItems($this->period, $this->selectedItems, auth()->user());
            $this->selectedItems = [];
            $this->selectAll = false;
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Slip Disetujui!',
                'message' => "{$count} slip gaji karyawan terpilih berhasil disetujui (ACC).",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyetujui!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    #[On('approve-all-items-confirmed')]
    public function approveAll(PayrollService $service): void
    {
        try {
            $count = $service->approveAllItems($this->period, auth()->user());
            $this->selectedItems = [];
            $this->selectAll = false;
            $this->refreshPeriod();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Seluruh Slip Disetujui!',
                'message' => "Seluruh {$count} slip gaji karyawan dalam periode ini telah disetujui (ACC).",
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $items = $this->period->items()
            ->with(['payrollPeriod', 'employee.department', 'employee.position', 'employee.detail', 'lines', 'approver', 'verifier', 'updater', 'payer'])
            ->join('employees', 'payroll_items.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->select('payroll_items.*')
            ->when($this->period->status->isEditable(), function ($query) {
                $query->whereNull('employees.deleted_at');
            })
            ->when($this->search, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('employees.full_name', 'like', "%{$search}%")
                        ->orWhere('payroll_items.payslip_number', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('
                CASE WHEN departments.id IS NOT NULL THEN 0 ELSE 1 END ASC,
                departments.name ASC,
                CASE
                    WHEN LOWER(positions.name) LIKE "%direktur%" OR LOWER(positions.name) LIKE "%director%" THEN 1
                    WHEN LOWER(positions.name) LIKE "%manager%" THEN 2
                    WHEN LOWER(positions.name) LIKE "%supervisor%" OR LOWER(positions.name) LIKE "%spv%" THEN 3
                    WHEN LOWER(positions.name) LIKE "%lead%" THEN 4
                    WHEN LOWER(positions.name) LIKE "%senior%" THEN 5
                    WHEN LOWER(positions.name) LIKE "%staff%" THEN 6
                    WHEN LOWER(positions.name) LIKE "%magang%" OR LOWER(positions.name) LIKE "%intern%" OR LOWER(positions.name) LIKE "%pkl%" THEN 7
                    WHEN positions.id IS NOT NULL THEN 8
                    ELSE 99
                END ASC,
                employees.join_date ASC,
                employees.full_name ASC
            ')
            ->paginate($this->perPage);

        $departmentSummaries = $this->period->items()
            ->leftJoin('employees', 'payroll_items.employee_id', '=', 'employees.id')
            ->when($this->period->status->isEditable(), function ($query) {
                $query->whereNotNull('employees.id')
                    ->whereNull('employees.deleted_at');
            })
            ->selectRaw('COALESCE(employees.department_id, 0) as department_id')
            ->selectRaw('COUNT(*) as item_count')
            ->selectRaw('COALESCE(SUM(payroll_items.basic_salary + payroll_items.total_allowances), 0) as total_gross')
            ->selectRaw('COALESCE(SUM(payroll_items.net_salary), 0) as total_net')
            ->groupByRaw('COALESCE(employees.department_id, 0)')
            ->get()
            ->keyBy('department_id')
            ->map(function ($summary) {
                return [
                    'count' => (int) $summary->item_count,
                    'total_gross' => (float) $summary->total_gross,
                    'total_net' => (float) $summary->total_net,
                    'formatted_total_gross' => 'Rp '.number_format((float) $summary->total_gross, 0, ',', '.'),
                    'formatted_total_net' => 'Rp '.number_format((float) $summary->total_net, 0, ',', '.'),
                ];
            });

        $company = CompanyProfile::getProfile();

        return view('livewire.payrolls.show', [
            'items' => $items,
            'departmentSummaries' => $departmentSummaries,
            'company' => $company,
        ]);
    }
}
