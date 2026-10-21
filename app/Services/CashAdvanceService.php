<?php

namespace App\Services;

use App\Enums\CashAdvancePaymentStatus;
use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\RepaymentScheme;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\CashAdvancePayment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollItemLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class CashAdvanceService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Retrieve paginated cash advances with search and filters.
     */
    public function getPaginatedCashAdvances(
        ?string $search = null,
        ?string $status = null,
        ?int $employeeId = null,
        ?string $repaymentScheme = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15,
        string $sortField = 'created_at',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 100));
        $allowedSorts = [
            'reference_number',
            'amount',
            'remaining_amount',
            'disbursement_date',
            'status',
            'repayment_scheme',
            'created_at',
        ];
        $sortColumn = in_array($sortField, $allowedSorts, true) ? $sortField : 'created_at';
        $direction = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return CashAdvance::query()
            ->with([
                'employee.department',
                'employee.position',
                'submitter',
                'approver',
                'installments.payments.recorder',
                'payments.recorder',
            ])
            ->when($search, fn ($q) => $q->search($search))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($repaymentScheme, fn ($q) => $q->where('repayment_scheme', $repaymentScheme))
            ->when($startDate, fn ($q) => $q->whereDate('disbursement_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('disbursement_date', '<=', $endDate))
            ->orderBy($sortColumn, $direction)
            ->orderBy('id', $direction)
            ->paginate($perPage);
    }

    /**
     * Get summary metrics for cash advances.
     *
    /**
     * Retrieve summary statistics for dashboard / index header metrics.
     *
     * @return array{
     *     total_active_count: int,
     *     total_employees_with_active_advances: int,
     *     total_employees_count: int,
     *     total_remaining_balance: float,
     *     formatted_total_remaining_balance: string,
     *     total_pending_count: int,
     *     total_disbursed_this_month: float,
     *     formatted_total_disbursed_this_month: string,
     *     total_approved_amount: float,
     *     formatted_total_approved_amount: string,
     *     total_approved_count: int,
     *     total_paid_amount: float,
     *     formatted_total_paid_amount: string,
     *     total_paid_transactions_count: int,
     * }
     */
    public function getSummaryStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $activeStatuses = [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value];
        $approvedStatuses = [
            CashAdvanceStatus::ACTIVE->value,
            CashAdvanceStatus::APPROVED->value,
            CashAdvanceStatus::PAID->value,
        ];
        $pendingInstallmentStatuses = [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value];

        $advanceStats = CashAdvance::query()
            ->leftJoin('employees', 'cash_advances.employee_id', '=', 'employees.id')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN cash_advances.status IN (?, ?) AND cash_advances.remaining_amount > 0 THEN cash_advances.remaining_amount ELSE 0 END), 0) as total_remaining',
                $activeStatuses
            )
            ->selectRaw(
                'COUNT(CASE WHEN cash_advances.status IN (?, ?) AND cash_advances.remaining_amount > 0 THEN 1 END) as active_count',
                $activeStatuses
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN cash_advances.status IN (?, ?) AND cash_advances.remaining_amount > 0 THEN cash_advances.employee_id END) as active_employees_count',
                $activeStatuses
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN cash_advances.status IN (?, ?) AND cash_advances.remaining_amount > 0 THEN cash_advances.amount ELSE 0 END), 0) as total_active_loan_amount',
                $activeStatuses
            )
            ->selectRaw(
                'COUNT(CASE WHEN cash_advances.status = ? THEN 1 END) as pending_count',
                [CashAdvanceStatus::PENDING->value]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN cash_advances.status = ? THEN cash_advances.amount ELSE 0 END), 0) as pending_amount',
                [CashAdvanceStatus::PENDING->value]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN cash_advances.status IN (?, ?, ?) AND cash_advances.disbursement_date >= ? AND cash_advances.disbursement_date <= ? THEN cash_advances.amount ELSE 0 END), 0) as disbursed_this_month',
                [...$approvedStatuses, $startOfMonth, $endOfMonth]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN cash_advances.status IN (?, ?, ?) THEN cash_advances.amount ELSE 0 END), 0) as total_approved_amount',
                $approvedStatuses
            )
            ->selectRaw(
                'COUNT(CASE WHEN cash_advances.status IN (?, ?, ?) THEN 1 END) as total_approved_count',
                $approvedStatuses
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN cash_advances.status IN (?, ?, ?) THEN cash_advances.amount - cash_advances.remaining_amount ELSE 0 END), 0) as total_paid_from_advances',
                $approvedStatuses
            )
            ->selectRaw(
                'COUNT(CASE WHEN cash_advances.status = ? THEN 1 END) as paid_advance_count',
                [CashAdvanceStatus::PAID->value]
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN cash_advances.status IN (?, ?) AND cash_advances.remaining_amount > 0 AND employees.status != ? THEN cash_advances.employee_id END) as inactive_with_debt_count',
                [...$activeStatuses, EmployeeStatus::ACTIVE->value]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN cash_advances.status IN (?, ?) AND cash_advances.remaining_amount > 0 THEN LEAST(cash_advances.remaining_amount, CASE WHEN cash_advances.monthly_installment > 0 THEN cash_advances.monthly_installment ELSE cash_advances.remaining_amount END) ELSE 0 END), 0) as fallback_deduction',
                $activeStatuses
            )
            ->first();

        $totalRemaining = (float) $advanceStats->total_remaining;
        $activeCount = (int) $advanceStats->active_count;
        $activeEmployeesCount = (int) $advanceStats->active_employees_count;
        $totalActiveLoanAmount = (float) $advanceStats->total_active_loan_amount;
        $totalActivePaidAmount = max(0, $totalActiveLoanAmount - $totalRemaining);
        $activePaidPercentage = $totalActiveLoanAmount > 0
            ? (int) round(($totalActivePaidAmount / $totalActiveLoanAmount) * 100)
            : 0;

        $pendingCount = (int) $advanceStats->pending_count;
        $pendingAmount = (float) $advanceStats->pending_amount;

        // 3. Potongan gaji bulan ini (jadwal cicilan yang jatuh tempo di bulan berjalan)
        $installmentStats = CashAdvanceInstallment::query()
            ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_installments.cash_advance_id')
            ->whereIn('cash_advances.status', $activeStatuses)
            ->whereIn('cash_advance_installments.status', $pendingInstallmentStatuses)
            ->where('cash_advance_installments.due_date', '<=', $endOfMonth)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN cash_advance_installments.due_date >= ? AND cash_advance_installments.due_date <= ? THEN cash_advance_installments.amount - cash_advance_installments.paid_amount ELSE 0 END), 0) as payroll_deduction_this_month',
                [$startOfMonth, $endOfMonth]
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN cash_advance_installments.due_date >= ? AND cash_advance_installments.due_date <= ? THEN cash_advances.employee_id END) as payroll_deduction_employees_count',
                [$startOfMonth, $endOfMonth]
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN cash_advance_installments.due_date < ? THEN cash_advances.employee_id END) as overdue_employees_count',
                [$startOfMonth]
            )
            ->first();

        $payrollDeductionThisMonth = (float) $installmentStats->payroll_deduction_this_month;
        $payrollDeductionEmployeesCount = (int) $installmentStats->payroll_deduction_employees_count;

        if ($payrollDeductionThisMonth <= 0 && $activeCount > 0) {
            $fallbackDeduction = (float) $advanceStats->fallback_deduction;
            if ($fallbackDeduction > 0) {
                $payrollDeductionThisMonth = $fallbackDeduction;
                $payrollDeductionEmployeesCount = $activeEmployeesCount;
            }
        }

        // 4. Perlu perhatian (cicilan terlewat jatuh tempo sebelum bulan ini atau karyawan non-aktif dengan sisa saldo)
        $overdueEmployeesCount = (int) $installmentStats->overdue_employees_count;
        $inactiveWithDebtCount = (int) $advanceStats->inactive_with_debt_count;

        $attentionEmployeesCount = max($overdueEmployeesCount, $inactiveWithDebtCount);
        $attentionSubtitle = $overdueEmployeesCount > 0
            ? 'Cicilan terlewat bulan lalu'
            : ($inactiveWithDebtCount > 0 ? 'Karyawan non-aktif bersaldo' : 'Semua cicilan lancar');

        $currentMonthShort = $now->translatedFormat('M');

        $disbursedThisMonth = (float) $advanceStats->disbursed_this_month;
        $totalApprovedAmount = (float) $advanceStats->total_approved_amount;
        $totalApprovedCount = (int) $advanceStats->total_approved_count;

        $paymentStats = CashAdvancePayment::query()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as approved_amount',
                [CashAdvancePaymentStatus::APPROVED->value]
            )
            ->selectRaw(
                'COUNT(CASE WHEN status = ? THEN 1 END) as approved_count',
                [CashAdvancePaymentStatus::APPROVED->value]
            )
            ->selectRaw(
                'COUNT(CASE WHEN status = ? THEN 1 END) as pending_count',
                [CashAdvancePaymentStatus::PENDING->value]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as pending_amount',
                [CashAdvancePaymentStatus::PENDING->value]
            )
            ->first();

        $paymentsSum = (float) $paymentStats->approved_amount;
        $diffPaidSum = (float) $advanceStats->total_paid_from_advances;
        $totalPaidAmount = max($paymentsSum, $diffPaidSum);
        $paymentsCount = (int) $paymentStats->approved_count;
        $totalPaidTransactionsCount = $paymentsCount > 0 ? $paymentsCount : (int) $advanceStats->paid_advance_count;
        $totalPendingPaymentsCount = (int) $paymentStats->pending_count;
        $totalPendingPaymentsAmount = (float) $paymentStats->pending_amount;

        $totalEmployeesCount = Employee::query()->count();

        $compactRemaining = $totalRemaining >= 1000000000
            ? 'Rp '.number_format($totalRemaining / 1000000000, 1, ',', '.').' M'
            : ($totalRemaining >= 1000000
                ? 'Rp '.number_format($totalRemaining / 1000000, 1, ',', '.').' jt'
                : ($totalRemaining >= 1000
                    ? 'Rp '.number_format($totalRemaining / 1000, 0, ',', '.').' rb'
                    : 'Rp '.number_format($totalRemaining, 0, ',', '.')));

        return [
            'total_active_count' => $activeCount,
            'total_employees_with_active_advances' => $activeEmployeesCount,
            'total_employees_count' => $totalEmployeesCount,
            'total_remaining_balance' => $totalRemaining,
            'formatted_total_remaining_balance' => 'Rp '.number_format($totalRemaining, 0, ',', '.'),
            'compact_total_remaining_balance' => $compactRemaining,
            'total_pending_count' => $pendingCount,
            'total_pending_payments_count' => $totalPendingPaymentsCount,
            'total_pending_payments_amount' => $totalPendingPaymentsAmount,
            'formatted_pending_payments_amount' => 'Rp '.number_format($totalPendingPaymentsAmount, 0, ',', '.'),
            'total_disbursed_this_month' => $disbursedThisMonth,
            'formatted_total_disbursed_this_month' => 'Rp '.number_format($disbursedThisMonth, 0, ',', '.'),
            'total_approved_amount' => $totalApprovedAmount,
            'formatted_total_approved_amount' => 'Rp '.number_format($totalApprovedAmount, 0, ',', '.'),
            'total_approved_count' => $totalApprovedCount,
            'total_paid_amount' => $totalPaidAmount,
            'formatted_total_paid_amount' => 'Rp '.number_format($totalPaidAmount, 0, ',', '.'),
            'total_paid_transactions_count' => $totalPaidTransactionsCount,
            'active_paid_percentage' => $activePaidPercentage,
            'pending_amount' => $pendingAmount,
            'formatted_pending_amount' => 'Rp '.number_format($pendingAmount, 0, ',', '.'),
            'payroll_deduction_this_month' => $payrollDeductionThisMonth,
            'formatted_payroll_deduction_this_month' => 'Rp '.number_format($payrollDeductionThisMonth, 0, ',', '.'),
            'payroll_deduction_employees_count' => $payrollDeductionEmployeesCount,
            'current_month_short' => $currentMonthShort,
            'attention_employees_count' => $attentionEmployeesCount,
            'attention_subtitle' => $attentionSubtitle,
        ];
    }

    /**
     * Get pending cash advances for quick review modal.
     *
     * @return Collection<int, CashAdvance>
     */
    public function getPendingAdvancesForReview(): Collection
    {
        return CashAdvance::query()
            ->with([
                'employee' => fn ($q) => $q->withTrashed()->with(['department', 'position']),
                'submitter',
            ])
            ->where('status', CashAdvanceStatus::PENDING->value)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get pending cash advance payments for quick review modal.
     *
     * @return Collection<int, CashAdvancePayment>
     */
    public function getPendingPaymentsForReview(): Collection
    {
        return CashAdvancePayment::query()
            ->with([
                'cashAdvance.employee' => fn ($eq) => $eq->withTrashed()->with(['department', 'position']),
                'installment',
                'recorder',
            ])
            ->where('status', CashAdvancePaymentStatus::PENDING->value)
            ->orderBy('payment_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Get overdue installments grouped by cash advance/employee for quick review modal.
     *
     * @return \Illuminate\Support\Collection<int, array{employee_id: int, employee_name: string, department_name: string, position_name: string, cash_advance_id: int, remaining_amount: float, overdue_count: int, overdue_amount: float, formatted_overdue_amount: string, oldest_due_date: string, formatted_due_date: string}>
     */
    public function getOverdueAdvancesForReview(): \Illuminate\Support\Collection
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();

        $overdueInstallments = CashAdvanceInstallment::query()
            ->with([
                'cashAdvance.employee' => fn ($q) => $q->withTrashed()->with(['department', 'position']),
            ])
            ->whereHas('cashAdvance', function ($q) {
                $q->whereIn('status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value]);
            })
            ->whereDate('due_date', '<', $startOfMonth)
            ->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
            ->orderBy('due_date', 'asc')
            ->get();

        return $overdueInstallments
            ->groupBy('cash_advance_id')
            ->map(function ($installments) {
                $first = $installments->first();
                $advance = $first->cashAdvance;
                $employee = $advance?->employee;
                $overdueCount = $installments->count();
                $overdueAmount = (float) $installments->sum(fn ($i) => (float) ($i->amount - $i->paid_amount));

                return [
                    'employee_id' => (int) ($employee?->id ?? 0),
                    'employee_name' => $employee?->full_name ?? '-',
                    'department_name' => $employee?->department?->name ?? 'Tanpa Departemen',
                    'position_name' => $employee?->position?->name ?? 'Karyawan',
                    'cash_advance_id' => (int) ($advance?->id ?? 0),
                    'remaining_amount' => (float) ($advance?->remaining_amount ?? 0),
                    'overdue_count' => $overdueCount,
                    'overdue_amount' => $overdueAmount,
                    'formatted_overdue_amount' => 'Rp '.number_format($overdueAmount, 0, ',', '.'),
                    'oldest_due_date' => $first->due_date?->toDateString() ?? '',
                    'formatted_due_date' => $first->due_date?->translatedFormat('d M Y') ?? '-',
                ];
            })
            ->values();
    }

    /**
     * Get analytics and statistics chart data for the cash advance recap dashboard.
     *
     * @return array{
     *     monthly: array{labels: array<int, string>, disbursed: array<int, float>, paid: array<int, float>, has_data: bool},
     *     top_debtors: array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>, has_data: bool},
     *     departments: array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>, has_data: bool}
     * }
     */
    public function getRecapChartData(): array
    {
        $now = Carbon::now();
        $startDate = (clone $now)->subMonths(5)->startOfMonth()->toDateString();
        $endDate = (clone $now)->endOfMonth()->toDateString();

        $disbursedByMonth = CashAdvance::query()
            ->whereIn('status', [
                CashAdvanceStatus::ACTIVE->value,
                CashAdvanceStatus::APPROVED->value,
                CashAdvanceStatus::PAID->value,
            ])
            ->whereDate('disbursement_date', '>=', $startDate)
            ->whereDate('disbursement_date', '<=', $endDate)
            ->selectRaw("{$this->dateToMonthExpression('disbursement_date')} as m_key, SUM(amount) as total")
            ->groupBy('m_key')
            ->pluck('total', 'm_key');

        $paidByMonth = CashAdvancePayment::query()
            ->where('status', CashAdvancePaymentStatus::APPROVED->value)
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate)
            ->selectRaw("{$this->dateToMonthExpression('payment_date')} as m_key, SUM(amount) as total")
            ->groupBy('m_key')
            ->pluck('total', 'm_key');

        // 1. Tren Bulanan (6 bulan terakhir: mundur 5 bulan ke bulan berjalan)
        $monthlyLabels = [];
        $monthlyDisbursed = [];
        $monthlyPaid = [];

        for ($i = 5; $i >= 0; $i--) {
            $mDate = (clone $now)->subMonths($i);
            $key = $mDate->format('Y-m');

            $monthlyLabels[] = $mDate->translatedFormat('M Y');
            $monthlyDisbursed[] = (float) ($disbursedByMonth->get($key) ?? 0);
            $monthlyPaid[] = (float) ($paidByMonth->get($key) ?? 0);
        }

        // 2. Top 5 Kasbon Terbesar (berdasarkan sisa saldo kasbon aktif)
        $topDebtorsQuery = CashAdvance::query()
            ->whereIn('status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value])
            ->where('remaining_amount', '>', 0)
            ->with('employee:id,full_name')
            ->selectRaw('employee_id, SUM(remaining_amount) as total_debt')
            ->groupBy('employee_id')
            ->orderByDesc('total_debt')
            ->get();

        $top5Debtors = $topDebtorsQuery->take(5);
        $otherDebtorsSum = (float) $topDebtorsQuery->slice(5)->sum('total_debt');

        $debtorLabels = [];
        $debtorData = [];
        $debtorColors = ['#0b52aa', '#2563eb', '#38bdf8', '#059669', '#10b981', '#94a3b8'];

        foreach ($top5Debtors as $item) {
            $empName = $item->employee?->full_name ?? ('Karyawan #'.$item->employee_id);
            $debtorLabels[] = Str::limit($empName, 18);
            $debtorData[] = round((float) $item->total_debt, 2);
        }

        if ($otherDebtorsSum > 0) {
            $debtorLabels[] = 'Lainnya';
            $debtorData[] = round($otherDebtorsSum, 2);
        }

        // 3. Distribusi Kasbon per Departemen
        $deptDistributionQuery = CashAdvance::query()
            ->whereIn('cash_advances.status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value])
            ->where('cash_advances.remaining_amount', '>', 0)
            ->join('employees', 'employees.id', '=', 'cash_advances.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw('COALESCE(departments.name, "Tanpa Departemen") as dept_name, SUM(cash_advances.remaining_amount) as total_dept_debt')
            ->groupBy('dept_name')
            ->orderByDesc('total_dept_debt')
            ->get();

        $deptLabels = [];
        $deptData = [];
        $deptColors = ['#0b52aa', '#f59e0b', '#10b981', '#8b5cf6', '#ec4899', '#64748b', '#06b6d4'];

        foreach ($deptDistributionQuery as $row) {
            $deptLabels[] = $row->dept_name;
            $deptData[] = round((float) $row->total_dept_debt, 2);
        }

        return [
            'monthly' => [
                'labels' => $monthlyLabels,
                'disbursed' => $monthlyDisbursed,
                'paid' => $monthlyPaid,
                'has_data' => (array_sum($monthlyDisbursed) > 0 || array_sum($monthlyPaid) > 0),
            ],
            'top_debtors' => [
                'labels' => $debtorLabels,
                'data' => $debtorData,
                'colors' => array_slice($debtorColors, 0, max(1, count($debtorData))),
                'has_data' => count($debtorData) > 0,
            ],
            'departments' => [
                'labels' => $deptLabels,
                'data' => $deptData,
                'colors' => array_slice($deptColors, 0, max(1, count($deptData))),
                'has_data' => count($deptData) > 0,
            ],
        ];
    }

    /**
     * Generate unique reference number (e.g. KB-202610-0001).
     */
    public function generateReferenceNumber(?Carbon $date = null): string
    {
        $callback = fn (): string => $this->reserveSequenceNumber('cash_advance', $date ?? Carbon::now());

        return DB::transactionLevel() > 0 ? $callback() : DB::transaction($callback);
    }

    /**
     * Generate unique payment number (e.g. PAY-KB-202610-0001).
     */
    public function generatePaymentNumber(?Carbon $date = null): string
    {
        $callback = fn (): string => $this->reserveSequenceNumber('payment', $date ?? Carbon::now());

        return DB::transactionLevel() > 0 ? $callback() : DB::transaction($callback);
    }

    /**
     * Reserve the next number for a monthly transaction sequence.
     */
    private function reserveSequenceNumber(string $type, Carbon $date): string
    {
        $prefix = $type === 'cash_advance'
            ? 'KB-'.$date->format('Ym').'-'
            : 'PAY-KB-'.$date->format('Ym').'-';
        $sequenceKey = rtrim($prefix, '-');
        $sourceTable = $type === 'cash_advance' ? 'cash_advances' : 'cash_advance_payments';
        $sourceColumn = $type === 'cash_advance' ? 'reference_number' : 'payment_number';
        $latest = DB::table($sourceTable)
            ->where($sourceColumn, 'like', $prefix.'%')
            ->orderByDesc($sourceColumn)
            ->value($sourceColumn);
        $initialNumber = $latest && preg_match('/(\d{4,})$/', (string) $latest, $matches)
            ? (int) $matches[1]
            : 0;

        DB::table('cash_advance_sequences')->insertOrIgnore([
            'sequence_key' => $sequenceKey,
            'last_number' => $initialNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('cash_advance_sequences')
            ->where('sequence_key', $sequenceKey)
            ->lockForUpdate()
            ->first();

        $lastNumber = max((int) $sequence->last_number, $initialNumber);
        $nextNumber = $lastNumber + 1;

        DB::table('cash_advance_sequences')
            ->where('sequence_key', $sequenceKey)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new cash advance submission.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createCashAdvance(array $data, User $user): CashAdvance
    {
        Gate::forUser($user)->authorize('create', CashAdvance::class);

        try {
            return DB::transaction(function () use ($data, $user) {
                $disbursementDate = Carbon::parse($data['disbursement_date']);
                $referenceNumber = $this->generateReferenceNumber($disbursementDate);

                $amount = (float) $data['amount'];
                $scheme = $data['repayment_scheme'] instanceof RepaymentScheme
                    ? $data['repayment_scheme']->value
                    : (string) $data['repayment_scheme'];

                $installmentCount = ($scheme === RepaymentScheme::SALARY_INSTALLMENT->value)
                    ? max(1, (int) ($data['installment_count'] ?? 1))
                    : 1;

                $monthlyInstallment = ($installmentCount > 0)
                    ? round($amount / $installmentCount, 2)
                    : $amount;

                $cashAdvance = CashAdvance::create([
                    'reference_number' => $referenceNumber,
                    'employee_id' => $data['employee_id'],
                    'amount' => $amount,
                    'remaining_amount' => $amount,
                    'repayment_scheme' => $scheme,
                    'installment_count' => $installmentCount,
                    'monthly_installment' => $monthlyInstallment,
                    'disbursement_date' => $disbursementDate->toDateString(),
                    'reason' => trim((string) $data['reason']),
                    'status' => CashAdvanceStatus::PENDING->value,
                    'submitted_by' => $user->id,
                    'notes' => ! empty($data['notes']) ? trim((string) $data['notes']) : null,
                ]);

                $this->logger->info(
                    action: 'CREATE',
                    module: 'cash_advances',
                    message: 'Cash advance requested',
                    context: [
                        'cash_advance_id' => $cashAdvance->id,
                        'reference_number' => $cashAdvance->reference_number,
                        'employee_id' => $cashAdvance->employee_id,
                        'amount' => $cashAdvance->amount,
                        'repayment_scheme' => $cashAdvance->repayment_scheme instanceof RepaymentScheme ? $cashAdvance->repayment_scheme->value : $cashAdvance->repayment_scheme,
                    ]
                );

                return $cashAdvance;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'CREATE_FAILED',
                module: 'cash_advances',
                message: 'Cash advance creation failed',
                context: ['payload' => $data],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Update an existing pending cash advance.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateCashAdvance(CashAdvance $cashAdvance, array $data, User $user): CashAdvance
    {
        Gate::forUser($user)->authorize('update', $cashAdvance);

        try {
            return DB::transaction(function () use ($cashAdvance, $data) {
                $lockedCashAdvance = CashAdvance::query()
                    ->lockForUpdate()
                    ->findOrFail($cashAdvance->id);

                if ($lockedCashAdvance->status !== CashAdvanceStatus::PENDING) {
                    throw new InvalidArgumentException('Hanya pengajuan kasbon berstatus Menunggu Persetujuan yang dapat diubah.');
                }

                $amount = (float) $data['amount'];
                $scheme = $data['repayment_scheme'] instanceof RepaymentScheme
                    ? $data['repayment_scheme']->value
                    : (string) $data['repayment_scheme'];

                $installmentCount = ($scheme === RepaymentScheme::SALARY_INSTALLMENT->value)
                    ? max(1, (int) ($data['installment_count'] ?? 1))
                    : 1;

                $monthlyInstallment = ($installmentCount > 0)
                    ? round($amount / $installmentCount, 2)
                    : $amount;

                $disbursementDate = Carbon::parse($data['disbursement_date'])->toDateString();

                $lockedCashAdvance->update([
                    'employee_id' => $data['employee_id'],
                    'amount' => $amount,
                    'remaining_amount' => $amount,
                    'repayment_scheme' => $scheme,
                    'installment_count' => $installmentCount,
                    'monthly_installment' => $monthlyInstallment,
                    'disbursement_date' => $disbursementDate,
                    'reason' => trim((string) $data['reason']),
                    'notes' => ! empty($data['notes']) ? trim((string) $data['notes']) : null,
                ]);

                $this->logger->info(
                    action: 'UPDATE',
                    module: 'cash_advances',
                    message: 'Cash advance updated',
                    context: [
                        'cash_advance_id' => $lockedCashAdvance->id,
                        'reference_number' => $lockedCashAdvance->reference_number,
                        'amount' => $lockedCashAdvance->amount,
                    ]
                );

                return $lockedCashAdvance;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'UPDATE_FAILED',
                module: 'cash_advances',
                message: 'Cash advance update failed',
                context: [
                    'cash_advance_id' => $cashAdvance->id,
                    'payload' => $data,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Approve cash advance request and generate installment schedule.
     *
     * @throws Throwable
     */
    public function approveCashAdvance(CashAdvance $cashAdvance, User $approver, ?string $notes = null): CashAdvance
    {
        Gate::forUser($approver)->authorize('approve', $cashAdvance);

        try {
            return DB::transaction(function () use ($cashAdvance, $approver, $notes) {
                $lockedCashAdvance = CashAdvance::query()
                    ->lockForUpdate()
                    ->findOrFail($cashAdvance->id);

                if ($lockedCashAdvance->status !== CashAdvanceStatus::PENDING) {
                    throw new InvalidArgumentException('Kasbon ini sudah tidak dalam status Menunggu Persetujuan.');
                }

                $lockedCashAdvance->update([
                    'status' => CashAdvanceStatus::ACTIVE->value,
                    'approved_by' => $approver->id,
                    'approved_at' => Carbon::now(),
                    'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : $lockedCashAdvance->notes,
                ]);

                // Delete any existing installments if regenerated
                $lockedCashAdvance->installments()->delete();

                // Generate installment schedule
                $count = $lockedCashAdvance->installment_count;
                $amount = (float) $lockedCashAdvance->amount;
                $baseMonthly = (float) floor($amount / $count);
                $startDate = Carbon::parse($lockedCashAdvance->disbursement_date);

                $allocatedTotal = 0.0;
                for ($i = 1; $i <= $count; $i++) {
                    $installmentAmount = ($i === $count)
                        ? round($amount - $allocatedTotal, 2)
                        : $baseMonthly;

                    $allocatedTotal += $installmentAmount;
                    $dueDate = $startDate->copy()->addMonths($i);

                    CashAdvanceInstallment::create([
                        'cash_advance_id' => $lockedCashAdvance->id,
                        'installment_number' => $i,
                        'due_date' => $dueDate->toDateString(),
                        'amount' => $installmentAmount,
                        'paid_amount' => 0,
                        'status' => InstallmentStatus::PENDING->value,
                    ]);
                }

                $this->logger->info(
                    action: 'APPROVE',
                    module: 'cash_advances',
                    message: 'Cash advance approved',
                    context: [
                        'cash_advance_id' => $lockedCashAdvance->id,
                        'reference_number' => $lockedCashAdvance->reference_number,
                        'approved_by' => $approver->id,
                        'installments_created' => $count,
                    ]
                );

                return $lockedCashAdvance->fresh(['installments']);
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'APPROVE_FAILED',
                module: 'cash_advances',
                message: 'Cash advance approval failed',
                context: ['cash_advance_id' => $cashAdvance->id],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Reject cash advance request.
     *
     * @throws Throwable
     */
    public function rejectCashAdvance(CashAdvance $cashAdvance, User $approver, string $reason): CashAdvance
    {
        Gate::forUser($approver)->authorize('reject', $cashAdvance);

        try {
            return DB::transaction(function () use ($cashAdvance, $approver, $reason) {
                $lockedCashAdvance = CashAdvance::query()
                    ->lockForUpdate()
                    ->findOrFail($cashAdvance->id);

                if ($lockedCashAdvance->status !== CashAdvanceStatus::PENDING) {
                    throw new InvalidArgumentException('Kasbon ini sudah tidak dalam status Menunggu Persetujuan.');
                }

                $lockedCashAdvance->update([
                    'status' => CashAdvanceStatus::REJECTED->value,
                    'approved_by' => $approver->id,
                    'approved_at' => Carbon::now(),
                    'rejection_reason' => trim($reason),
                ]);

                $this->logger->info(
                    action: 'REJECT',
                    module: 'cash_advances',
                    message: 'Cash advance rejected',
                    context: [
                        'cash_advance_id' => $lockedCashAdvance->id,
                        'reference_number' => $lockedCashAdvance->reference_number,
                        'approved_by' => $approver->id,
                        'reason' => $reason,
                    ]
                );

                return $lockedCashAdvance;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'REJECT_FAILED',
                module: 'cash_advances',
                message: 'Cash advance rejection failed',
                context: ['cash_advance_id' => $cashAdvance->id],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Record a payment against a cash advance.
     *
     * @param  array<string, mixed>  $paymentData
     *
     * @throws Throwable
     */
    public function recordPayment(CashAdvance $cashAdvance, array $paymentData, User $user): CashAdvancePayment
    {
        Gate::forUser($user)->authorize('recordPayment', $cashAdvance);
        $temporaryProofPath = $this->storeTemporaryProofDocument($paymentData['proof_document'] ?? null);
        $paymentNumber = null;
        $storedProofPath = null;

        try {
            $storedProofPath = $temporaryProofPath
                ? $this->moveProofDocument($temporaryProofPath, 'pending-'.Str::uuid())
                : null;
            $payment = DB::transaction(function () use ($cashAdvance, $paymentData, $user, &$paymentNumber, $storedProofPath) {
                $lockedCashAdvance = CashAdvance::query()
                    ->lockForUpdate()
                    ->findOrFail($cashAdvance->id);

                if (! in_array($lockedCashAdvance->status, [CashAdvanceStatus::ACTIVE, CashAdvanceStatus::APPROVED], true)) {
                    throw new InvalidArgumentException('Hanya kasbon yang aktif/berjalan yang dapat menerima pembayaran.');
                }

                $paymentAmount = round((float) ($paymentData['amount'] ?? 0), 2);
                $remaining = (float) $lockedCashAdvance->remaining_amount;

                if ($paymentAmount <= 0) {
                    throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari 0.');
                }

                if ($paymentAmount > $remaining + 0.001) {
                    throw new InvalidArgumentException('Nominal pembayaran (Rp '.number_format($paymentAmount, 0, ',', '.').') tidak boleh melebihi sisa kasbon (Rp '.number_format($remaining, 0, ',', '.').').');
                }

                $paymentDate = Carbon::parse($paymentData['payment_date']);
                if ($paymentDate->isFuture() || $paymentDate->lt(Carbon::parse($lockedCashAdvance->disbursement_date))) {
                    throw new InvalidArgumentException('Tanggal pembayaran harus berada setelah tanggal pencairan dan tidak boleh di masa depan.');
                }
                $paymentNumber = $this->generatePaymentNumber($paymentDate);
                $targetInstallmentId = $paymentData['cash_advance_installment_id'] ?? null;
                $unpaidInstallments = $lockedCashAdvance->installments()
                    ->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                    ->orderBy('installment_number', 'asc')
                    ->lockForUpdate()
                    ->get();

                if ($targetInstallmentId) {
                    $targetInstallment = $lockedCashAdvance->installments()
                        ->whereKey($targetInstallmentId)
                        ->lockForUpdate()
                        ->first();

                    if (! $targetInstallment || ! $unpaidInstallments->contains('id', (int) $targetInstallmentId)) {
                        throw new InvalidArgumentException('Cicilan yang dipilih tidak dimiliki kasbon ini atau sudah lunas.');
                    }

                    $unpaidInstallments = $unpaidInstallments->sortBy(
                        fn (CashAdvanceInstallment $installment): int => (int) $installment->id === (int) $targetInstallmentId ? 0 : 1
                    )->values();
                }

                $isAutoSalary = ($paymentData['payment_method'] ?? null) === PaymentMethod::SALARY_DEDUCTION
                    || ($paymentData['payment_method'] ?? null) === PaymentMethod::SALARY_DEDUCTION->value;

                $payment = CashAdvancePayment::create([
                    'payment_number' => $paymentNumber,
                    'cash_advance_id' => $lockedCashAdvance->id,
                    'cash_advance_installment_id' => $targetInstallmentId,
                    'payment_date' => $paymentDate->toDateString(),
                    'amount' => $paymentAmount,
                    'payment_method' => $paymentData['payment_method'],
                    'status' => $isAutoSalary ? CashAdvancePaymentStatus::APPROVED : CashAdvancePaymentStatus::PENDING,
                    'proof_document' => $storedProofPath,
                    'notes' => ! empty($paymentData['notes']) ? trim((string) $paymentData['notes']) : null,
                    'recorded_by' => $user->id,
                    'approved_by' => $isAutoSalary ? $user->id : null,
                    'approved_at' => $isAutoSalary ? Carbon::now() : null,
                ]);

                if ($isAutoSalary) {
                    // Reduce remaining amount
                    $newRemaining = max(0, round($remaining - $paymentAmount, 2));
                    $newStatus = ($newRemaining <= 0.001) ? CashAdvanceStatus::PAID->value : $lockedCashAdvance->status->value;

                    $lockedCashAdvance->update([
                        'remaining_amount' => $newRemaining,
                        'status' => $newStatus,
                    ]);

                    // Allocate payment to installments
                    $leftToAllocate = $paymentAmount;
                    foreach ($unpaidInstallments as $installment) {
                        if ($leftToAllocate <= 0) {
                            break;
                        }

                        $installmentRemaining = (float) $installment->amount - (float) $installment->paid_amount;
                        $allocation = min($leftToAllocate, $installmentRemaining);

                        $newPaidAmount = round((float) $installment->paid_amount + $allocation, 2);
                        $installmentStatus = ($newPaidAmount >= (float) $installment->amount)
                            ? InstallmentStatus::PAID->value
                            : InstallmentStatus::PARTIALLY_PAID->value;

                        $installment->update([
                            'paid_amount' => $newPaidAmount,
                            'status' => $installmentStatus,
                            'paid_at' => ($installmentStatus === InstallmentStatus::PAID->value) ? Carbon::now() : $installment->paid_at,
                        ]);

                        $this->syncOpenPayrollLinesForInstallment($installment);

                        $leftToAllocate -= $allocation;
                    }

                    if ($unpaidInstallments->isNotEmpty() && $leftToAllocate > 0.001) {
                        throw new InvalidArgumentException('Nominal pembayaran tidak dapat dialokasikan ke cicilan kasbon.');
                    }
                }

                $this->logger->info(
                    action: $isAutoSalary ? 'PAYMENT' : 'PAYMENT_PENDING',
                    module: 'cash_advances',
                    message: $isAutoSalary ? 'Cash advance payment recorded' : 'Cash advance payment recorded (pending director approval)',
                    context: [
                        'cash_advance_id' => $lockedCashAdvance->id,
                        'payment_id' => $payment->id,
                        'payment_number' => $payment->payment_number,
                        'amount' => $paymentAmount,
                        'status' => $payment->status->value,
                    ]
                );

                return $payment;
            });

            return $payment;
        } catch (Throwable $e) {
            $this->cleanupProofDocument($temporaryProofPath, $storedProofPath);
            $this->logger->error(
                action: 'PAYMENT_FAILED',
                module: 'cash_advances',
                message: 'Cash advance payment failed',
                context: [
                    'cash_advance_id' => $cashAdvance->id,
                    'payment_number' => $paymentNumber,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Approve a pending cash advance payment (Direktur only).
     *
     * @throws Throwable
     */
    public function approvePayment(CashAdvancePayment $payment, User $user): CashAdvancePayment
    {
        Gate::forUser($user)->authorize('approve', $payment);

        return DB::transaction(function () use ($payment, $user) {
            $lockedPayment = CashAdvancePayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($lockedPayment->status !== CashAdvancePaymentStatus::PENDING) {
                throw new InvalidArgumentException('Hanya pembayaran berstatus Menunggu Persetujuan yang dapat disetujui.');
            }

            $lockedCashAdvance = CashAdvance::query()
                ->lockForUpdate()
                ->findOrFail($lockedPayment->cash_advance_id);

            $paymentAmount = (float) $lockedPayment->amount;
            $remaining = (float) $lockedCashAdvance->remaining_amount;

            if ($paymentAmount > $remaining + 0.001) {
                throw new InvalidArgumentException('Nominal pembayaran (Rp '.number_format($paymentAmount, 0, ',', '.').') melebihi sisa kasbon saat ini (Rp '.number_format($remaining, 0, ',', '.').').');
            }

            $targetInstallmentId = $lockedPayment->cash_advance_installment_id;
            $unpaidInstallments = $lockedCashAdvance->installments()
                ->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                ->orderBy('installment_number', 'asc')
                ->lockForUpdate()
                ->get();

            if ($targetInstallmentId) {
                $unpaidInstallments = $unpaidInstallments->sortBy(
                    fn (CashAdvanceInstallment $installment): int => (int) $installment->id === (int) $targetInstallmentId ? 0 : 1
                )->values();
            }

            // Reduce remaining amount
            $newRemaining = max(0, round($remaining - $paymentAmount, 2));
            $newStatus = ($newRemaining <= 0.001) ? CashAdvanceStatus::PAID->value : $lockedCashAdvance->status->value;

            $lockedCashAdvance->update([
                'remaining_amount' => $newRemaining,
                'status' => $newStatus,
            ]);

            // Allocate payment to installments
            $leftToAllocate = $paymentAmount;
            foreach ($unpaidInstallments as $installment) {
                if ($leftToAllocate <= 0) {
                    break;
                }

                $installmentRemaining = (float) $installment->amount - (float) $installment->paid_amount;
                $allocation = min($leftToAllocate, $installmentRemaining);

                $newPaidAmount = round((float) $installment->paid_amount + $allocation, 2);
                $installmentStatus = ($newPaidAmount >= (float) $installment->amount - 0.01)
                    ? InstallmentStatus::PAID->value
                    : InstallmentStatus::PARTIALLY_PAID->value;

                $installment->update([
                    'paid_amount' => $newPaidAmount,
                    'status' => $installmentStatus,
                    'paid_at' => ($installmentStatus === InstallmentStatus::PAID->value) ? Carbon::now() : $installment->paid_at,
                ]);

                $this->syncOpenPayrollLinesForInstallment($installment);

                $leftToAllocate -= $allocation;
            }

            $lockedPayment->update([
                'status' => CashAdvancePaymentStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => Carbon::now(),
                'rejection_reason' => null,
            ]);

            $this->logger->info(
                action: 'PAYMENT_APPROVED',
                module: 'cash_advances',
                message: 'Cash advance payment approved by Direktur',
                context: [
                    'cash_advance_id' => $lockedCashAdvance->id,
                    'payment_id' => $lockedPayment->id,
                    'payment_number' => $lockedPayment->payment_number,
                    'amount' => $paymentAmount,
                    'remaining_amount' => $newRemaining,
                    'approved_by' => $user->id,
                ]
            );

            return $lockedPayment;
        });
    }

    /**
     * Reject a pending cash advance payment (Direktur only).
     *
     * @throws Throwable
     */
    public function rejectPayment(CashAdvancePayment $payment, string $reason, User $user): CashAdvancePayment
    {
        Gate::forUser($user)->authorize('reject', $payment);

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new InvalidArgumentException('Alasan penolakan pembayaran wajib diisi.');
        }

        return DB::transaction(function () use ($payment, $trimmedReason, $user) {
            $lockedPayment = CashAdvancePayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($lockedPayment->status !== CashAdvancePaymentStatus::PENDING) {
                throw new InvalidArgumentException('Hanya pembayaran berstatus Menunggu Persetujuan yang dapat ditolak.');
            }

            $lockedPayment->update([
                'status' => CashAdvancePaymentStatus::REJECTED,
                'rejection_reason' => $trimmedReason,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            $this->logger->info(
                action: 'PAYMENT_REJECTED',
                module: 'cash_advances',
                message: 'Cash advance payment rejected by Direktur',
                context: [
                    'cash_advance_id' => $lockedPayment->cash_advance_id,
                    'payment_id' => $lockedPayment->id,
                    'payment_number' => $lockedPayment->payment_number,
                    'reason' => $trimmedReason,
                    'rejected_by' => $user->id,
                ]
            );

            return $lockedPayment;
        });
    }

    /**
     * Delete a pending or rejected cash advance.
     *
     * @throws Throwable
     */
    public function deleteCashAdvance(CashAdvance $cashAdvance, User $user): bool
    {
        Gate::forUser($user)->authorize('delete', $cashAdvance);

        try {
            return DB::transaction(function () use ($cashAdvance) {
                $lockedCashAdvance = CashAdvance::query()
                    ->lockForUpdate()
                    ->findOrFail($cashAdvance->id);

                if (! in_array($lockedCashAdvance->status, [CashAdvanceStatus::PENDING, CashAdvanceStatus::REJECTED, CashAdvanceStatus::CANCELLED], true)) {
                    throw new InvalidArgumentException('Kasbon yang sudah berjalan atau memiliki pembayaran tidak dapat dihapus.');
                }

                $refNumber = $lockedCashAdvance->reference_number;
                $id = $lockedCashAdvance->id;

                $lockedCashAdvance->delete();

                $this->logger->info(
                    action: 'DELETE',
                    module: 'cash_advances',
                    message: 'Cash advance deleted',
                    context: [
                        'cash_advance_id' => $id,
                        'reference_number' => $refNumber,
                    ]
                );

                return true;
            });
        } catch (Throwable $e) {
            $this->logger->error(
                action: 'DELETE_FAILED',
                module: 'cash_advances',
                message: 'Cash advance deletion failed',
                context: ['cash_advance_id' => $cashAdvance->id],
                exception: $e
            );

            throw $e;
        }
    }

    /**
     * Store an uploaded proof document outside the final payment directory.
     */
    private function storeTemporaryProofDocument(mixed $proofDocument): ?string
    {
        if (! $proofDocument instanceof UploadedFile) {
            return null;
        }

        return $proofDocument->store('cash-advances/.tmp', 'local');
    }

    /**
     * Move a temporary proof document to its permanent location after commit.
     */
    private function moveProofDocument(?string $temporaryPath, string $paymentNumber): ?string
    {
        if (! $temporaryPath) {
            return null;
        }

        $extension = pathinfo($temporaryPath, PATHINFO_EXTENSION) ?: 'bin';
        $permanentPath = 'cash-advances/payments/'.$paymentNumber.'-'.Str::uuid().'.'.$extension;
        $disk = Storage::disk('local');

        if (! $disk->move($temporaryPath, $permanentPath)) {
            throw new \RuntimeException('Bukti pembayaran gagal dipindahkan ke penyimpanan permanen.');
        }

        return $permanentPath;
    }

    /**
     * Remove temporary or partially persisted proof documents after a failure.
     */
    private function cleanupProofDocument(?string $temporaryPath, ?string $storedPath): void
    {
        $disk = Storage::disk('local');

        foreach (array_unique(array_filter([$temporaryPath, $storedPath])) as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    /**
     * Get active cash advance model for an employee if any.
     */
    public function getActiveAdvanceByEmployee(int $employeeId, ?int $exceptId = null): ?CashAdvance
    {
        return CashAdvance::query()
            ->where('employee_id', $employeeId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->whereIn('status', [
                CashAdvanceStatus::PENDING->value,
                CashAdvanceStatus::APPROVED->value,
                CashAdvanceStatus::ACTIVE->value,
            ])
            ->where('remaining_amount', '>', 0)
            ->first();
    }

    /**
     * Get active cash advances summary for an employee (for blocking alert on new submission).
     *
     * @return array{has_active: bool, count: int, total_remaining: float, formatted_total_remaining: string, advances: array<int, array<string, mixed>>}
     */
    public function getActiveCashAdvanceSummaryByEmployee(int $employeeId, ?int $exceptId = null): array
    {
        $activeQuery = CashAdvance::query()
            ->where('employee_id', $employeeId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->whereIn('status', [
                CashAdvanceStatus::PENDING->value,
                CashAdvanceStatus::APPROVED->value,
                CashAdvanceStatus::ACTIVE->value,
            ])
            ->where('remaining_amount', '>', 0)
            ->select(['id', 'reference_number', 'status', 'amount', 'remaining_amount', 'disbursement_date']);

        $count = (clone $activeQuery)->count();
        $totalRemaining = (float) (clone $activeQuery)->sum('remaining_amount');
        $activeAdvances = $activeQuery->orderBy('disbursement_date')->limit(10)->get();

        return [
            'has_active' => $count > 0,
            'count' => $count,
            'total_remaining' => $totalRemaining,
            'formatted_total_remaining' => 'Rp '.number_format($totalRemaining, 0, ',', '.'),
            'advances' => $activeAdvances->map(fn (CashAdvance $ca) => [
                'id' => $ca->id,
                'reference_number' => $ca->reference_number,
                'status_label' => $ca->status_label,
                'status_badge' => $ca->status_badge_class,
                'amount' => $ca->formatted_amount,
                'remaining' => $ca->formatted_remaining_amount,
                'date' => $ca->disbursement_date?->translatedFormat('d M Y'),
            ])->toArray(),
        ];
    }

    /**
     * Get summary overview for a selected employee.
     *
     * @return array{
     *     employee: Employee|null,
     *     total_balance: float,
     *     formatted_total_balance: string,
     *     active_advances_count: int,
     *     total_advance_amount: float,
     *     formatted_total_advance_amount: string,
     *     total_paid: float,
     *     formatted_total_paid: string,
     *     paid_percentage: float,
     *     remaining_percentage: float,
     *     has_advances: bool
     * }
     */
    public function getEmployeeSummary(?int $employeeId): array
    {
        if (! $employeeId) {
            return [
                'employee' => null,
                'total_balance' => 0.0,
                'formatted_total_balance' => 'Rp 0',
                'active_advances_count' => 0,
                'total_advance_amount' => 0.0,
                'formatted_total_advance_amount' => 'Rp 0',
                'total_paid' => 0.0,
                'formatted_total_paid' => 'Rp 0',
                'paid_percentage' => 0.0,
                'remaining_percentage' => 0.0,
                'has_advances' => false,
            ];
        }

        $employee = Employee::with(['department', 'position', 'detail'])->find($employeeId);

        $activeAdvances = CashAdvance::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', [
                CashAdvanceStatus::APPROVED->value,
                CashAdvanceStatus::ACTIVE->value,
            ])
            ->where('remaining_amount', '>', 0)
            ->get();

        $totalBalance = (float) $activeAdvances->sum('remaining_amount');
        $activeCount = $activeAdvances->count();

        // Cari seluruh kasbon yang disetujui / aktif / lunas untuk rasio pelunasan
        $allAdvances = CashAdvance::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', [
                CashAdvanceStatus::APPROVED->value,
                CashAdvanceStatus::ACTIVE->value,
                CashAdvanceStatus::PAID->value,
            ])
            ->get();

        $targetCollection = $activeAdvances->isNotEmpty() ? $activeAdvances : $allAdvances;
        $totalAdvanceAmount = (float) $targetCollection->sum('amount');
        $totalPaid = max(0, (float) $targetCollection->sum(fn ($a) => max(0, (float) $a->amount - (float) $a->remaining_amount)));
        $paidPercentage = $totalAdvanceAmount > 0
            ? round(($totalPaid / $totalAdvanceAmount) * 100, 1)
            : 0.0;
        $remainingPercentage = max(0, 100 - $paidPercentage);
        $hasAdvances = $targetCollection->isNotEmpty() && $totalAdvanceAmount > 0;

        return [
            'employee' => $employee,
            'total_balance' => $totalBalance,
            'formatted_total_balance' => 'Rp '.number_format($totalBalance, 0, ',', '.'),
            'active_advances_count' => $activeCount,
            'total_advance_amount' => $totalAdvanceAmount,
            'formatted_total_advance_amount' => 'Rp '.number_format($totalAdvanceAmount, 0, ',', '.'),
            'total_paid' => $totalPaid,
            'formatted_total_paid' => 'Rp '.number_format($totalPaid, 0, ',', '.'),
            'paid_percentage' => $paidPercentage,
            'remaining_percentage' => $remainingPercentage,
            'has_advances' => $hasAdvances,
        ];
    }

    /**
     * Get paginated summary of employees with their cash advances for the overview table.
     */
    public function getEmployeesCashAdvanceSummary(
        ?string $search = null,
        ?int $departmentId = null,
        string $filter = 'all',
        int $perPage = 10,
        int $page = 1
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 100));

        $query = Employee::query()
            ->where('status', 'active')
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId));

        if ($filter === 'active_balance') {
            $query->whereHas('cashAdvances', function ($q) {
                $q->whereIn('status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value])
                    ->where('remaining_amount', '>', 0);
            });
        } elseif ($filter === 'pending') {
            $query->whereHas('cashAdvances', function ($q) {
                $q->where('status', CashAdvanceStatus::PENDING->value);
            });
        } elseif ($filter === 'has_advance') {
            $query->whereHas('cashAdvances');
        } else {
            // 'all' (default)
            // No whereHas restriction
        }

        if (! empty(trim((string) $search))) {
            $searchTerm = trim((string) $search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('department', fn ($d) => $d->where('name', 'like', "%{$searchTerm}%"))
                    ->orWhereHas('position', fn ($p) => $p->where('name', 'like', "%{$searchTerm}%"));
            });
        }

        $query->with([
            'department:id,name',
            'position:id,name',
            'cashAdvances' => fn ($q) => $q
                ->whereIn('status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value])
                ->select(['id', 'employee_id', 'amount', 'remaining_amount', 'installment_count', 'monthly_installment'])
                ->withCount([
                    'installments',
                    'installments as paid_installments_count' => fn ($installments) => $installments
                        ->where('status', InstallmentStatus::PAID->value),
                ])
                ->orderByDesc('id'),
        ])
            ->orderByRaw('CASE WHEN employees.department_id IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy(
                Department::select('name')->whereColumn('departments.id', 'employees.department_id')
            )
            ->orderBy('employees.full_name', 'asc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $employeeIds = $paginator->getCollection()->modelKeys();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();
        $monthlyDeductions = CashAdvanceInstallment::query()
            ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_installments.cash_advance_id')
            ->whereIn('cash_advances.employee_id', $employeeIds)
            ->whereIn('cash_advances.status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value])
            ->where('cash_advance_installments.due_date', '<=', $endOfMonth)
            ->where('cash_advance_installments.status', '!=', InstallmentStatus::PAID->value)
            ->selectRaw('cash_advances.employee_id')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN cash_advance_installments.amount > cash_advance_installments.paid_amount THEN cash_advance_installments.amount - cash_advance_installments.paid_amount ELSE 0 END), 0) as monthly_deduction'
            )
            ->groupBy('cash_advances.employee_id')
            ->pluck('monthly_deduction', 'employee_id');

        $paginator->getCollection()->transform(function (Employee $emp) use ($monthlyDeductions): array {
            $advances = $emp->cashAdvances;
            $totalInitial = (float) $advances->sum('amount');
            $totalRemaining = (float) $advances->sum('remaining_amount');
            $totalPaid = max(0.0, round($totalInitial - $totalRemaining, 2));

            $monthlyDeduction = (float) ($monthlyDeductions->get($emp->id) ?? 0);
            $hasActive = $advances->isNotEmpty();
            $totalInstallments = 0;
            $paidInstallments = 0;

            foreach ($advances as $advance) {
                if ($advance->installments_count > 0) {
                    $totalInstallments += (int) $advance->installments_count;
                    $paidInstallments += (int) $advance->paid_installments_count;
                } elseif ($advance->installment_count > 0) {
                    $totalInstallments += (int) $advance->installment_count;
                    if ($advance->monthly_installment > 0) {
                        $paidInstallments += (int) floor(($advance->amount - $advance->remaining_amount) / $advance->monthly_installment);
                    }
                } else {
                    $totalInstallments += 1;
                    if ($advance->remaining_amount <= 0) {
                        $paidInstallments += 1;
                    }
                }
            }

            $repaymentPercentage = 0.0;
            $repaymentText = null;

            if ($hasActive) {
                $totalInstallments = max(1, $totalInstallments);
                $paidInstallments = min($totalInstallments, max(0, $paidInstallments));
                $repaymentPercentage = $totalInitial > 0
                    ? min(100.0, max(0.0, round(($totalPaid / $totalInitial) * 100, 1)))
                    : min(100.0, max(0.0, round(($paidInstallments / $totalInstallments) * 100, 1)));
                $repaymentText = "{$paidInstallments} dari {$totalInstallments} cicilan";
            }

            return [
                'id' => $emp->id,
                'name' => $emp->full_name,
                'photo_url' => $emp->photo_url,
                'department' => $emp->department?->name ?: 'Tanpa Departemen',
                'position' => $emp->position?->name ?: 'Tanpa Jabatan',
                'initial_amount' => $totalInitial,
                'formatted_initial_amount' => 'Rp '.number_format($totalInitial, 0, ',', '.'),
                'paid_amount' => $totalPaid,
                'formatted_paid_amount' => 'Rp '.number_format($totalPaid, 0, ',', '.'),
                'remaining_amount' => $totalRemaining,
                'formatted_remaining_amount' => 'Rp '.number_format($totalRemaining, 0, ',', '.'),
                'monthly_deduction' => $monthlyDeduction,
                'formatted_monthly_deduction' => 'Rp '.number_format($monthlyDeduction, 0, ',', '.'),
                'has_active' => $hasActive,
                'paid_installments' => $paidInstallments,
                'total_installments' => $totalInstallments,
                'repayment_percentage' => $repaymentPercentage,
                'repayment_text' => $repaymentText,
            ];
        });

        return $paginator;
    }

    /**
     * Get paginated cash advances for an employee with eager loaded relations.
     */
    public function getEmployeeAdvances(int $employeeId, ?string $search = null, ?string $filterStatus = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = CashAdvance::query()
            ->with([
                'employee.department',
                'employee.position',
                'submitter',
                'approver',
                'installments.payments.recorder',
                'payments.recorder',
            ])
            ->where('employee_id', $employeeId);

        if (! empty(trim((string) $search))) {
            $term = trim((string) $search);
            $query->where(function ($q) use ($term) {
                $q->where('reference_number', 'like', "%{$term}%")
                    ->orWhere('reason', 'like', "%{$term}%")
                    ->orWhere('amount', 'like', "%{$term}%");
            });
        }

        if (! empty($filterStatus)) {
            $query->where('status', $filterStatus);
        }

        $perPage = max(1, min($perPage, 100));

        return $query->orderBy('created_at', 'desc')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get chronological transaction ledger (pengajuan + pelunasan) for a specific employee.
     */
    public function getEmployeeLedger(int $employeeId, ?string $search = null, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        return $this->getEmployeeLedgerFromDatabase($employeeId, $search, $perPage, $page);
    }

    /**
     * Find page number for a given transaction in the employee's ledger.
     */
    public function getLedgerPageForTransaction(int $employeeId, string $type, int $id, int $perPage = 25): int
    {
        $advanceQuery = DB::table('cash_advances')
            ->where('cash_advances.employee_id', $employeeId)
            ->select([
                'cash_advances.id as id',
                DB::raw("'advance' as type"),
                DB::raw('0 as type_order'),
                DB::raw('COALESCE(cash_advances.disbursement_date, DATE(cash_advances.created_at)) as transaction_date'),
                'cash_advances.created_at as transaction_time',
            ]);

        $paymentQuery = DB::table('cash_advance_payments')
            ->join('cash_advances as payment_advances', 'payment_advances.id', '=', 'cash_advance_payments.cash_advance_id')
            ->where('payment_advances.employee_id', $employeeId)
            ->select([
                'cash_advance_payments.id as id',
                DB::raw("'payment' as type"),
                DB::raw('1 as type_order'),
                'cash_advance_payments.payment_date as transaction_date',
                'cash_advance_payments.created_at as transaction_time',
            ]);

        $baseUnion = $advanceQuery->unionAll($paymentQuery);
        $allRows = DB::query()
            ->fromSub($baseUnion, 'ledger_all')
            ->orderBy('transaction_date')
            ->orderBy('transaction_time')
            ->orderBy('type_order')
            ->orderBy('id')
            ->get(['id', 'type']);

        $index = $allRows->search(function ($item) use ($type, $id) {
            return $item->type === $type && (int) $item->id === $id;
        });

        if ($index === false) {
            return 1;
        }

        return (int) floor($index / max(1, $perPage)) + 1;
    }

    private function getEmployeeLedgerFromDatabase(int $employeeId, ?string $search, int $perPage, int $page): LengthAwarePaginator
    {
        $advanceQuery = DB::table('cash_advances')
            ->leftJoin('users as submitters', 'submitters.id', '=', 'cash_advances.submitted_by')
            ->where('cash_advances.employee_id', $employeeId)
            ->select([
                'cash_advances.id as id',
                DB::raw("'advance' as type"),
                DB::raw('0 as type_order'),
                DB::raw('cash_advances.id as cash_advance_id'),
                'cash_advances.reference_number',
                DB::raw('COALESCE(cash_advances.disbursement_date, DATE(cash_advances.created_at)) as transaction_date'),
                'cash_advances.created_at as transaction_time',
                'submitters.name as creator_name',
                'cash_advances.reason as description',
                'cash_advances.status',
                DB::raw("CASE cash_advances.status
                    WHEN 'active' THEN 'Disetujui'
                    WHEN 'approved' THEN 'Disetujui'
                    WHEN 'pending' THEN 'Menunggu Persetujuan'
                    WHEN 'paid' THEN 'Lunas'
                    WHEN 'rejected' THEN 'Ditolak'
                    WHEN 'cancelled' THEN 'Dibatalkan'
                    ELSE cash_advances.status
                END as status_label"),
                'cash_advances.amount as advance_amount',
                DB::raw('NULL as payment_amount'),
            ]);

        $paymentQuery = DB::table('cash_advance_payments')
            ->join('cash_advances as payment_advances', 'payment_advances.id', '=', 'cash_advance_payments.cash_advance_id')
            ->leftJoin('users as recorders', 'recorders.id', '=', 'cash_advance_payments.recorded_by')
            ->where('payment_advances.employee_id', $employeeId)
            ->select([
                'cash_advance_payments.id as id',
                DB::raw("'payment' as type"),
                DB::raw('1 as type_order'),
                'cash_advance_payments.cash_advance_id',
                'cash_advance_payments.payment_number as reference_number',
                'cash_advance_payments.payment_date as transaction_date',
                'cash_advance_payments.created_at as transaction_time',
                'recorders.name as creator_name',
                'cash_advance_payments.notes as description',
                'cash_advance_payments.status',
                DB::raw("CASE cash_advance_payments.status
                    WHEN 'approved' THEN 'Disetujui'
                    WHEN 'pending' THEN 'Menunggu ACC'
                    WHEN 'rejected' THEN 'Ditolak'
                    ELSE cash_advance_payments.status
                END as status_label"),
                DB::raw('NULL as advance_amount'),
                'cash_advance_payments.amount as payment_amount',
            ]);

        $baseUnion = $advanceQuery->unionAll($paymentQuery);
        $ledgerQuery = DB::query()->fromSub($baseUnion, 'ledger');
        $term = trim((string) $search);

        if ($term !== '') {
            $ledgerQuery->where(function ($query) use ($term) {
                $query
                    ->where('reference_number', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('creator_name', 'like', "%{$term}%")
                    ->orWhere('status', 'like', "%{$term}%")
                    ->orWhere('status_label', 'like', "%{$term}%");
            });
        }

        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);
        $paginator = $ledgerQuery
            ->orderBy('transaction_date')
            ->orderBy('transaction_time')
            ->orderBy('type_order')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
        $rows = collect($paginator->items());

        if ($rows->isEmpty()) {
            $paginator->setCollection(collect());

            return $paginator;
        }

        $allRows = DB::query()
            ->fromSub($baseUnion, 'ledger_all')
            ->orderBy('transaction_date')
            ->orderBy('transaction_time')
            ->orderBy('type_order')
            ->orderBy('id')
            ->get([
                'id',
                'type',
                'status',
                'type_order',
                'transaction_date',
                'transaction_time',
                'advance_amount',
                'payment_amount',
            ]);
        $balanceByKey = [];
        $runningBalance = 0.0;

        foreach ($allRows as $allRow) {
            if ($allRow->type === 'advance' && in_array($allRow->status, ['active', 'approved', 'paid'], true)) {
                $runningBalance += (float) $allRow->advance_amount;
            } elseif ($allRow->type === 'payment' && $allRow->status === 'approved') {
                $runningBalance = max(0.0, $runningBalance - (float) $allRow->payment_amount);
            }

            $balanceByKey[$allRow->type.':'.$allRow->id] = $runningBalance;
        }

        $advanceIds = $rows->where('type', 'advance')->pluck('id')->map(fn ($id): int => (int) $id);
        $paymentIds = $rows->where('type', 'payment')->pluck('id')->map(fn ($id): int => (int) $id);
        $advanceModels = CashAdvance::query()->with(['submitter', 'approver'])->whereIn('id', $advanceIds)->get()->keyBy('id');
        $paymentModels = CashAdvancePayment::query()->with(['recorder', 'cashAdvance', 'installment'])->whereIn('id', $paymentIds)->get()->keyBy('id');
        $ledgerItems = $rows->map(function ($row) use ($advanceModels, $paymentModels, $balanceByKey) {
            $transactionDate = Carbon::parse($row->transaction_date ?? $row->transaction_time);
            $transactionTime = Carbon::parse($row->transaction_time);
            $transactionDate->setTime($transactionTime->hour, $transactionTime->minute, $transactionTime->second);

            if ($row->type === 'advance') {
                $advance = $advanceModels->get((int) $row->id);
                if (! $advance) {
                    return null;
                }

                $status = $advance->status->value;
                $schemeLabel = match ($advance->repayment_scheme) {
                    RepaymentScheme::SALARY_INSTALLMENT => "Cicilan {$advance->installment_count} Bln (@ {$advance->formatted_monthly_installment})",
                    RepaymentScheme::SALARY_SINGLE => 'Potong Gaji 1x'.($advance->disbursement_date ? ' ('.$advance->disbursement_date->format('M Y').')' : ''),
                    RepaymentScheme::MANUAL => "Manual ({$advance->installment_count} Bln)",
                    default => $advance->scheme_label,
                };
                $schemeBadge = match ($advance->repayment_scheme) {
                    RepaymentScheme::SALARY_INSTALLMENT => 'badge badge-light text-primary border',
                    RepaymentScheme::SALARY_SINGLE => 'badge badge-light text-dark border',
                    RepaymentScheme::MANUAL => 'badge badge-light text-info border',
                    default => 'badge badge-light text-muted border',
                };

                return [
                    'id' => $advance->id,
                    'type' => 'advance',
                    'created_at' => $advance->created_at ?? $advance->disbursement_date,
                    'sort_date' => $transactionDate->timestamp,
                    'formatted_date' => $transactionDate->translatedFormat('d M Y H:i'),
                    'reference_number' => $advance->reference_number,
                    'creator_name' => $advance->submitter?->name ?? 'System',
                    'sub_badge_label' => $schemeLabel,
                    'sub_badge_class' => $schemeBadge,
                    'advance_amount' => (float) $advance->amount,
                    'formatted_advance_amount' => $advance->formatted_amount,
                    'payment_amount' => null,
                    'formatted_payment_amount' => '-',
                    'description' => $advance->reason ?: '-',
                    'status' => $status,
                    'status_label' => $advance->status_label,
                    'status_badge' => $advance->status_badge_class,
                    'model' => $advance,
                    'saldo_akhir' => $balanceByKey['advance:'.$row->id] ?? 0,
                    'formatted_saldo_akhir' => 'Rp '.number_format($balanceByKey['advance:'.$row->id] ?? 0, 0, ',', '.'),
                ];
            }

            $payment = $paymentModels->get((int) $row->id);
            if (! $payment) {
                return null;
            }

            $paymentContext = 'Setoran Kasbon';
            if ($payment->installment) {
                $installmentNumber = $payment->installment->installment_number;
                $isEarly = $payment->payment_date && $payment->installment->due_date && $payment->payment_date->lt($payment->installment->due_date);
                $paymentContext = "Cicilan #{$installmentNumber}".($isEarly ? ' (Bayar Awal)' : '');
            } elseif ($payment->cashAdvance?->repayment_scheme === RepaymentScheme::SALARY_SINGLE) {
                $paymentContext = 'Lunas Sebelum Payroll';
            } elseif ((float) $payment->amount >= (float) ($payment->cashAdvance?->amount ?? 0) || (float) ($payment->cashAdvance?->remaining_amount ?? 0) <= 0) {
                $paymentContext = 'Pelunasan Penuh (Lunas Awal)';
            }

            $subBadgeLabel = $payment->method_label.' • '.$paymentContext;
            $subBadgeClass = match ($payment->payment_method) {
                PaymentMethod::SALARY_DEDUCTION => 'badge badge-light text-success border',
                PaymentMethod::BANK_TRANSFER => 'badge badge-light text-info border',
                PaymentMethod::CASH => 'badge badge-light text-warning border',
                default => 'badge badge-light text-secondary border',
            };

            return [
                'id' => $payment->id,
                'type' => 'payment',
                'created_at' => $payment->created_at ?? $payment->payment_date,
                'sort_date' => $transactionDate->timestamp,
                'formatted_date' => $transactionDate->translatedFormat('d M Y H:i'),
                'reference_number' => $payment->payment_number,
                'creator_name' => $payment->recorder?->name ?? 'System',
                'sub_badge_label' => $subBadgeLabel,
                'sub_badge_class' => $subBadgeClass,
                'advance_amount' => null,
                'formatted_advance_amount' => '-',
                'payment_amount' => (float) $payment->amount,
                'formatted_payment_amount' => $payment->formatted_amount,
                'description' => $payment->notes ?: $subBadgeLabel,
                'status' => $payment->status instanceof CashAdvancePaymentStatus ? $payment->status->value : (string) ($payment->status ?? 'approved'),
                'status_label' => $payment->status_label,
                'status_badge' => $payment->status_badge_class,
                'model' => $payment,
                'cash_advance_id' => $payment->cash_advance_id,
                'saldo_akhir' => $balanceByKey['payment:'.$row->id] ?? 0,
                'formatted_saldo_akhir' => 'Rp '.number_format($balanceByKey['payment:'.$row->id] ?? 0, 0, ',', '.'),
            ];
        })->filter()->values();

        $paginator->setCollection($ledgerItems);

        return $paginator;
    }

    /**
     * Get monthly billing statements breakdown for an employee.
     *
     * @return array{
     *     unpaid_statements: array<int, array<string, mixed>>,
     *     paid_statements: array<string, array<int, array<string, mixed>>>,
     *     next_statement: array<string, mixed>|null,
     *     total_unpaid_amount: float,
     *     formatted_total_unpaid_amount: string,
     *     total_paid_amount: float,
     *     formatted_total_paid_amount: string,
     *     unpaid_count: int,
     *     paid_count: int
     * }
     */
    public function getEmployeeMonthlyStatements(
        int $employeeId,
        ?string $search = null,
        int $perPage = 10,
        int $page = 1
    ): array {
        $advances = CashAdvance::query()
            ->with('installments')
            ->where('employee_id', $employeeId)
            ->whereIn('status', [
                CashAdvanceStatus::ACTIVE->value,
                CashAdvanceStatus::APPROVED->value,
                CashAdvanceStatus::PAID->value,
            ])
            ->get();

        $installments = collect();
        foreach ($advances as $adv) {
            foreach ($adv->installments as $inst) {
                $installments->push([
                    'installment' => $inst,
                    'cash_advance' => $adv,
                ]);
            }
        }

        if ($installments->isEmpty()) {
            $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page']
            );

            return [
                'paginated_statements' => $emptyPaginator,
                'all_statements' => [],
                'unpaid_statements' => [],
                'paid_statements' => [],
                'next_statement' => null,
                'total_unpaid_amount' => 0.0,
                'formatted_total_unpaid_amount' => 'Rp 0',
                'total_paid_amount' => 0.0,
                'formatted_total_paid_amount' => 'Rp 0',
                'unpaid_count' => 0,
                'paid_count' => 0,
            ];
        }

        // Group installments by due_date Year-Month (e.g. '2026-10')
        $grouped = $installments->groupBy(function ($item) {
            $dueDate = $item['installment']->due_date ?? Carbon::now();

            return $dueDate->format('Y-m');
        });

        $statements = collect();

        foreach ($grouped as $monthKey => $items) {
            $firstDueDate = $items->first()['installment']->due_date ?? Carbon::parse($monthKey.'-01');
            $year = $firstDueDate->format('Y');
            $monthNameShort = $firstDueDate->translatedFormat('M');
            $monthNameFull = $firstDueDate->translatedFormat('F Y');
            $formattedDueDate = $firstDueDate->translatedFormat('d M Y');

            $totalAmount = (float) $items->sum(fn ($i) => (float) $i['installment']->amount);
            $totalPaid = (float) $items->sum(fn ($i) => (float) $i['installment']->paid_amount);
            $remaining = max(0.0, round($totalAmount - $totalPaid, 2));
            $isPaid = $remaining <= 0.001;

            $itemList = $items->map(function ($item) {
                /** @var CashAdvanceInstallment $inst */
                $inst = $item['installment'];
                /** @var CashAdvance $adv */
                $adv = $item['cash_advance'];

                $instRemaining = max(0.0, round((float) $inst->amount - (float) $inst->paid_amount, 2));
                $isInstPaid = $instRemaining <= 0.001;

                $tenorInfo = "[{$inst->installment_number}/{$adv->installment_count}]";
                $title = "{$tenorInfo} ".($adv->reason ?: 'Kasbon Karyawan');

                return [
                    'id' => $inst->id,
                    'cash_advance_id' => $adv->id,
                    'reference_number' => $adv->reference_number,
                    'installment_number' => $inst->installment_number,
                    'total_installments' => $adv->installment_count,
                    'title' => $title,
                    'reason' => $adv->reason,
                    'due_date' => $inst->due_date?->toDateString(),
                    'formatted_due_date' => $inst->due_date?->translatedFormat('d M Y'),
                    'amount' => (float) $inst->amount,
                    'formatted_amount' => 'Rp '.number_format((float) $inst->amount, 0, ',', '.'),
                    'paid_amount' => (float) $inst->paid_amount,
                    'formatted_paid_amount' => 'Rp '.number_format((float) $inst->paid_amount, 0, ',', '.'),
                    'remaining_amount' => $instRemaining,
                    'formatted_remaining_amount' => 'Rp '.number_format($instRemaining, 0, ',', '.'),
                    'is_paid' => $isInstPaid,
                    'status' => $inst->status instanceof InstallmentStatus ? $inst->status->value : (string) $inst->status,
                    'status_label' => $inst->status_label,
                    'status_badge_class' => $inst->status_badge_class,
                    'paid_at' => $inst->paid_at?->translatedFormat('d M Y H:i'),
                ];
            })->values()->toArray();

            $statements->push([
                'month_key' => $monthKey,
                'year' => $year,
                'month_name_short' => $monthNameShort,
                'month_name_full' => $monthNameFull,
                'due_date' => $firstDueDate->toDateString(),
                'formatted_due_date' => $formattedDueDate,
                'total_amount' => $totalAmount,
                'formatted_total_amount' => 'Rp '.number_format($totalAmount, 0, ',', '.'),
                'paid_amount' => $totalPaid,
                'formatted_paid_amount' => 'Rp '.number_format($totalPaid, 0, ',', '.'),
                'remaining_amount' => $remaining,
                'formatted_remaining_amount' => 'Rp '.number_format($remaining, 0, ',', '.'),
                'is_paid' => $isPaid,
                'is_overdue' => ! $isPaid && $firstDueDate->startOfDay()->isBefore(Carbon::today()),
                'status' => $isPaid ? 'paid' : 'unpaid',
                'status_label' => $isPaid ? 'Lunas' : 'Belum Lunas',
                'status_badge_class' => $isPaid ? 'badge badge-success' : 'badge badge-warning',
                'items_count' => count($itemList),
                'items' => $itemList,
            ]);
        }

        // Unpaid statements sorted chronologically (earliest month first)
        $unpaidStatements = $statements
            ->filter(fn ($s) => ! $s['is_paid'])
            ->sortBy('month_key')
            ->values()
            ->toArray();

        // Paid statements sorted descending (newest month first), grouped by year
        $paidGroupedByYear = $statements
            ->filter(fn ($s) => $s['is_paid'])
            ->sortByDesc('month_key')
            ->groupBy('year')
            ->map(fn ($group) => $group->values()->toArray())
            ->toArray();

        $paidCount = 0;
        foreach ($paidGroupedByYear as $yearStatements) {
            $paidCount += count($yearStatements);
        }

        $nextStatement = ! empty($unpaidStatements) ? $unpaidStatements[0] : null;

        $totalUnpaid = (float) $statements->filter(fn ($s) => ! $s['is_paid'])->sum('remaining_amount');
        $totalPaid = (float) $statements->sum('paid_amount');

        $allStatements = $statements->sortBy('month_key')->values();

        // Search filtering if search is given
        if ($search !== null && trim($search) !== '') {
            $searchTerm = mb_strtolower(trim($search));
            $allStatements = $allStatements->filter(function ($st) use ($searchTerm) {
                if (str_contains(mb_strtolower($st['month_name_full']), $searchTerm)) {
                    return true;
                }
                if (str_contains(mb_strtolower($st['formatted_due_date']), $searchTerm)) {
                    return true;
                }
                if (str_contains(mb_strtolower($st['status_label']), $searchTerm)) {
                    return true;
                }
                if (str_contains(mb_strtolower($st['formatted_total_amount']), $searchTerm)) {
                    return true;
                }
                if (str_contains(mb_strtolower($st['formatted_remaining_amount']), $searchTerm)) {
                    return true;
                }
                foreach ($st['items'] as $item) {
                    if (str_contains(mb_strtolower($item['reference_number']), $searchTerm)) {
                        return true;
                    }
                    if (! empty($item['reason']) && str_contains(mb_strtolower($item['reason']), $searchTerm)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $totalItems = $allStatements->count();
        $pagedItems = $allStatements->forPage($page, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedItems,
            $totalItems,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page']
        );

        return [
            'paginated_statements' => $paginator,
            'all_statements' => $allStatements->toArray(),
            'unpaid_statements' => $unpaidStatements,
            'paid_statements' => $paidGroupedByYear,
            'next_statement' => $nextStatement,
            'total_unpaid_amount' => $totalUnpaid,
            'formatted_total_unpaid_amount' => 'Rp '.number_format($totalUnpaid, 0, ',', '.'),
            'total_paid_amount' => $totalPaid,
            'formatted_total_paid_amount' => 'Rp '.number_format($totalPaid, 0, ',', '.'),
            'unpaid_count' => count($unpaidStatements),
            'paid_count' => $paidCount,
        ];
    }

    /**
     * Get a paginated, database-aggregated monthly statement list for the page table.
     * Detailed installment rows are loaded separately when a user opens a statement.
     *
     * @return array{paginated_statements: LengthAwarePaginator, unpaid_statements: array<int, array<string, mixed>>}
     */
    public function getPaginatedEmployeeMonthlyStatementSummaries(
        int $employeeId,
        ?string $search = null,
        int $perPage = 10,
        int $page = 1
    ): array {
        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);
        $monthExpression = $this->cashAdvanceStatementMonthExpression();

        $baseQuery = CashAdvanceInstallment::query()
            ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_installments.cash_advance_id')
            ->where('cash_advances.employee_id', $employeeId)
            ->whereIn('cash_advances.status', [
                CashAdvanceStatus::ACTIVE->value,
                CashAdvanceStatus::APPROVED->value,
                CashAdvanceStatus::PAID->value,
            ]);

        $monthlyRows = (clone $baseQuery)
            ->selectRaw("{$monthExpression} as month_key")
            ->selectRaw('MIN(cash_advance_installments.due_date) as first_due_date')
            ->selectRaw('COUNT(*) as items_count')
            ->selectRaw('COALESCE(SUM(cash_advance_installments.amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(cash_advance_installments.paid_amount), 0) as paid_amount')
            ->groupByRaw($monthExpression)
            ->orderBy('month_key')
            ->get();

        $statements = $monthlyRows->map(function ($row): array {
            $dueDate = Carbon::parse($row->first_due_date);
            $monthKey = (string) $row->month_key;
            $totalAmount = (float) $row->total_amount;
            $paidAmount = (float) $row->paid_amount;
            $remaining = max(0.0, round($totalAmount - $paidAmount, 2));
            $isPaid = $remaining <= 0.001;

            return [
                'month_key' => $monthKey,
                'year' => $dueDate->format('Y'),
                'month_name_short' => $dueDate->translatedFormat('M'),
                'month_name_full' => $dueDate->translatedFormat('F Y'),
                'due_date' => $dueDate->toDateString(),
                'formatted_due_date' => $dueDate->translatedFormat('d M Y'),
                'total_amount' => $totalAmount,
                'formatted_total_amount' => 'Rp '.number_format($totalAmount, 0, ',', '.'),
                'paid_amount' => $paidAmount,
                'formatted_paid_amount' => 'Rp '.number_format($paidAmount, 0, ',', '.'),
                'remaining_amount' => $remaining,
                'formatted_remaining_amount' => 'Rp '.number_format($remaining, 0, ',', '.'),
                'is_paid' => $isPaid,
                'is_overdue' => ! $isPaid && $dueDate->copy()->startOfDay()->isBefore(Carbon::today()),
                'status' => $isPaid ? 'paid' : 'unpaid',
                'status_label' => $isPaid ? 'Lunas' : 'Belum Lunas',
                'status_badge_class' => $isPaid ? 'badge badge-success' : 'badge badge-warning',
                'items_count' => (int) $row->items_count,
            ];
        });

        // Keep the complete unpaid list for the payment modal, independent of
        // the current table search and pagination state.
        $unpaidStatements = $statements
            ->filter(fn (array $statement): bool => ! $statement['is_paid'])
            ->values()
            ->all();

        $searchTerm = mb_strtolower(trim((string) $search));
        if ($searchTerm !== '') {
            $matchedMonthKeys = (clone $baseQuery)
                ->where(function ($query) use ($searchTerm) {
                    $query->whereRaw('LOWER(cash_advances.reference_number) LIKE ?', ['%'.$searchTerm.'%'])
                        ->orWhereRaw('LOWER(cash_advances.reason) LIKE ?', ['%'.$searchTerm.'%']);
                })
                ->selectRaw("DISTINCT {$monthExpression} as month_key")
                ->pluck('month_key')
                ->map(fn ($monthKey) => (string) $monthKey)
                ->all();

            $statements = $statements->filter(function (array $statement) use ($searchTerm, $matchedMonthKeys): bool {
                foreach ([
                    $statement['month_name_full'],
                    $statement['formatted_due_date'],
                    $statement['status_label'],
                    $statement['formatted_total_amount'],
                    $statement['formatted_remaining_amount'],
                ] as $value) {
                    if (str_contains(mb_strtolower($value), $searchTerm)) {
                        return true;
                    }
                }

                return in_array($statement['month_key'], $matchedMonthKeys, true);
            })->values();
        }

        $total = $statements->count();
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $statements->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page']
        );

        return [
            'paginated_statements' => $paginated,
            'unpaid_statements' => $unpaidStatements,
        ];
    }

    /**
     * Get compact unpaid statement balances for the early-payment workflow.
     * This intentionally returns monthly totals without hydrating installment models.
     *
     * @return array<int, array{month_key: string, total_amount: float, paid_amount: float, remaining_amount: float}>
     */
    public function getEmployeeUnpaidMonthlyStatementSummaries(int $employeeId): array
    {
        $monthExpression = $this->cashAdvanceStatementMonthExpression();
        $rows = CashAdvanceInstallment::query()
            ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_installments.cash_advance_id')
            ->where('cash_advances.employee_id', $employeeId)
            ->whereIn('cash_advances.status', [
                CashAdvanceStatus::ACTIVE->value,
                CashAdvanceStatus::APPROVED->value,
                CashAdvanceStatus::PAID->value,
            ])
            ->selectRaw("{$monthExpression} as month_key")
            ->selectRaw('COALESCE(SUM(cash_advance_installments.amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(cash_advance_installments.paid_amount), 0) as paid_amount')
            ->groupByRaw($monthExpression)
            ->orderBy('month_key')
            ->get();

        return $rows->map(function ($row): ?array {
            $totalAmount = (float) $row->total_amount;
            $paidAmount = (float) $row->paid_amount;
            $remainingAmount = max(0.0, round($totalAmount - $paidAmount, 2));

            if ($remainingAmount <= 0.001) {
                return null;
            }

            return [
                'month_key' => (string) $row->month_key,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
            ];
        })->filter()->values()->all();
    }

    /**
     * Get one month's statement details without loading the employee's other installments.
     *
     * @return array<string, mixed>|null
     */
    public function getEmployeeMonthlyStatementDetail(int $employeeId, string $monthKey): ?array
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthKey)) {
            return null;
        }

        $month = Carbon::createFromFormat('!Y-m', $monthKey);
        $installments = CashAdvanceInstallment::query()
            ->with(['cashAdvance' => fn ($query) => $query->select([
                'id',
                'employee_id',
                'reference_number',
                'reason',
                'installment_count',
                'status',
            ])])
            ->whereHas('cashAdvance', function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId)
                    ->whereIn('status', [
                        CashAdvanceStatus::ACTIVE->value,
                        CashAdvanceStatus::APPROVED->value,
                        CashAdvanceStatus::PAID->value,
                    ]);
            })
            ->whereBetween('due_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        if ($installments->isEmpty()) {
            return null;
        }

        $items = $installments->map(function (CashAdvanceInstallment $installment): array {
            $advance = $installment->cashAdvance;
            $remaining = max(0.0, round((float) $installment->amount - (float) $installment->paid_amount, 2));
            $isPaid = $remaining <= 0.001;
            $tenorInfo = "[{$installment->installment_number}/{$advance->installment_count}]";

            return [
                'id' => $installment->id,
                'cash_advance_id' => $advance->id,
                'reference_number' => $advance->reference_number,
                'installment_number' => $installment->installment_number,
                'total_installments' => $advance->installment_count,
                'title' => $tenorInfo.' '.($advance->reason ?: 'Kasbon Karyawan'),
                'reason' => $advance->reason,
                'due_date' => $installment->due_date?->toDateString(),
                'formatted_due_date' => $installment->due_date?->translatedFormat('d M Y'),
                'amount' => (float) $installment->amount,
                'formatted_amount' => 'Rp '.number_format((float) $installment->amount, 0, ',', '.'),
                'paid_amount' => (float) $installment->paid_amount,
                'formatted_paid_amount' => 'Rp '.number_format((float) $installment->paid_amount, 0, ',', '.'),
                'remaining_amount' => $remaining,
                'formatted_remaining_amount' => 'Rp '.number_format($remaining, 0, ',', '.'),
                'is_paid' => $isPaid,
                'status' => $installment->status instanceof InstallmentStatus
                    ? $installment->status->value
                    : (string) $installment->status,
                'status_label' => $installment->status_label,
                'status_badge_class' => $installment->status_badge_class,
                'paid_at' => $installment->paid_at?->translatedFormat('d M Y H:i'),
            ];
        })->values()->all();

        $firstDueDate = $installments->first()->due_date;
        $totalAmount = (float) $installments->sum('amount');
        $totalPaid = (float) $installments->sum('paid_amount');
        $remaining = max(0.0, round($totalAmount - $totalPaid, 2));
        $isPaid = $remaining <= 0.001;

        return [
            'month_key' => $monthKey,
            'year' => $firstDueDate->format('Y'),
            'month_name_short' => $firstDueDate->translatedFormat('M'),
            'month_name_full' => $firstDueDate->translatedFormat('F Y'),
            'due_date' => $firstDueDate->toDateString(),
            'formatted_due_date' => $firstDueDate->translatedFormat('d M Y'),
            'total_amount' => $totalAmount,
            'formatted_total_amount' => 'Rp '.number_format($totalAmount, 0, ',', '.'),
            'paid_amount' => $totalPaid,
            'formatted_paid_amount' => 'Rp '.number_format($totalPaid, 0, ',', '.'),
            'remaining_amount' => $remaining,
            'formatted_remaining_amount' => 'Rp '.number_format($remaining, 0, ',', '.'),
            'is_paid' => $isPaid,
            'is_overdue' => ! $isPaid && $firstDueDate->copy()->startOfDay()->isBefore(Carbon::today()),
            'status' => $isPaid ? 'paid' : 'unpaid',
            'status_label' => $isPaid ? 'Lunas' : 'Belum Lunas',
            'status_badge_class' => $isPaid ? 'badge badge-success' : 'badge badge-warning',
            'items_count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * Record early payment for selected month(s) or installments.
     *
     * @param  array<string, mixed>  $paymentData
     * @return array<int, CashAdvancePayment>
     *
     * @throws Throwable
     */
    public function recordMonthlySettlement(int $employeeId, array $paymentData, User $user): array
    {
        Gate::forUser($user)->authorize('recordEarlySettlement', CashAdvance::class);

        $selectedMonthKeys = array_values(array_unique(array_filter((array) ($paymentData['month_keys'] ?? []), 'is_string')));
        if (empty($selectedMonthKeys) || collect($selectedMonthKeys)->contains(fn (string $month): bool => ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month))) {
            throw new InvalidArgumentException('Silakan pilih minimal 1 bulan tagihan untuk pelunasan.');
        }

        $paymentDate = Carbon::parse($paymentData['payment_date'] ?? Carbon::today());
        if ($paymentDate->isFuture()) {
            throw new InvalidArgumentException('Tanggal pembayaran tidak boleh di masa depan.');
        }
        $paymentMethod = $paymentData['payment_method'] instanceof PaymentMethod
            ? $paymentData['payment_method']->value
            : (string) ($paymentData['payment_method'] ?? PaymentMethod::CASH->value);
        $notes = ! empty($paymentData['notes']) ? trim((string) $paymentData['notes']) : null;
        $proofDocument = $paymentData['proof_document'] ?? null;
        $temporaryProofPath = $this->storeTemporaryProofDocument($proofDocument);
        $storedProofPath = null;

        try {
            $storedProofPath = $temporaryProofPath
                ? $this->moveProofDocument($temporaryProofPath, 'pending-'.Str::uuid())
                : null;
            $payments = DB::transaction(function () use ($employeeId, $selectedMonthKeys, $paymentDate, $paymentMethod, $notes, $user, $paymentData, $storedProofPath) {
                $advances = CashAdvance::query()
                    ->where('employee_id', $employeeId)
                    ->whereIn('status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $earliestDisbursement = $advances->min(fn (CashAdvance $advance): string => $advance->disbursement_date->toDateString());
                if ($earliestDisbursement && $paymentDate->lt(Carbon::parse($earliestDisbursement))) {
                    throw new InvalidArgumentException('Tanggal pembayaran tidak boleh sebelum tanggal pencairan kasbon.');
                }
                $matchingInstallments = collect();

                foreach ($advances as $advance) {
                    $installments = CashAdvanceInstallment::query()
                        ->where('cash_advance_id', $advance->id)
                        ->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                        ->orderBy('due_date')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    foreach ($installments as $installment) {
                        $monthKey = $installment->due_date?->format('Y-m');
                        $remaining = max(0.0, round((float) $installment->amount - (float) $installment->paid_amount, 2));

                        if ($monthKey && in_array($monthKey, $selectedMonthKeys, true) && $remaining > 0) {
                            $matchingInstallments->push([
                                'installment' => $installment,
                                'cash_advance' => $advance,
                                'remaining' => $remaining,
                            ]);
                        }
                    }
                }

                if ($matchingInstallments->isEmpty()) {
                    throw new InvalidArgumentException('Tidak ada tagihan yang belum lunas pada bulan yang dipilih.');
                }

                $totalRemainingDue = (float) $matchingInstallments->sum('remaining');
                $maxAllowed = $totalRemainingDue;
                $customAmount = isset($paymentData['amount']) ? (float) $paymentData['amount'] : $totalRemainingDue;

                if ($customAmount <= 0 || $customAmount > $maxAllowed + 0.001) {
                    throw new InvalidArgumentException('Nominal pembayaran tidak boleh melebihi total tagihan bulan terpilih (Rp '.number_format($maxAllowed, 0, ',', '.').').');
                }

                $leftToAllocate = $customAmount;
                $allocatedItems = collect();
                $sortedItems = $matchingInstallments->sortBy(fn (array $item): int => $item['installment']->due_date?->timestamp ?? 0)->values();
                $itemsCount = $sortedItems->count();

                foreach ($sortedItems as $index => $item) {
                    if ($leftToAllocate <= 0.001) {
                        break;
                    }

                    $rem = (float) $item['remaining'];
                    $allocation = min($leftToAllocate, $rem);
                    if ($index === $itemsCount - 1 && $leftToAllocate >= $rem) {
                        $allocation = $rem;
                    }

                    $allocatedItems->push([...$item, 'allocation' => $allocation]);
                    $leftToAllocate -= $allocation;
                }

                if ($leftToAllocate > 0.001) {
                    throw new InvalidArgumentException('Nominal pembayaran tidak dapat dialokasikan ke tagihan yang dipilih.');
                }

                $paymentsCreated = [];
                $monthLabels = implode(', ', $selectedMonthKeys);
                $paymentNotes = $notes ?: "Pelunasan Tagihan Bulan [{$monthLabels}]";

                foreach ($allocatedItems as $item) {
                    $allocation = (float) $item['allocation'];
                    if ($allocation <= 0.001) {
                        continue;
                    }

                    /** @var CashAdvance $advance */
                    $advance = $item['cash_advance'];
                    /** @var CashAdvanceInstallment $installment */
                    $installment = $item['installment'];
                    $paymentNumber = $this->generatePaymentNumber($paymentDate);
                    $installmentMonth = Carbon::parse($installment->due_date)->translatedFormat('F Y');
                    $itemNotes = $notes ?: "Pelunasan Tagihan Bulan {$installmentMonth}";

                    $payment = CashAdvancePayment::create([
                        'payment_number' => $paymentNumber,
                        'cash_advance_id' => $advance->id,
                        'cash_advance_installment_id' => $installment->id,
                        'payment_date' => $paymentDate->toDateString(),
                        'amount' => $allocation,
                        'payment_method' => $paymentMethod,
                        'status' => CashAdvancePaymentStatus::PENDING,
                        'proof_document' => $storedProofPath,
                        'notes' => $itemNotes,
                        'recorded_by' => $user->id,
                    ]);

                    $paymentsCreated[] = $payment;

                    $this->logger->info(
                        action: 'MONTHLY_PAYMENT_PENDING',
                        module: 'cash_advances',
                        message: 'Cash advance monthly payment recorded (pending director approval)',
                        context: [
                            'cash_advance_id' => $advance->id,
                            'payment_id' => $payment->id,
                            'payment_number' => $payment->payment_number,
                            'amount' => $allocation,
                            'installment_id' => $installment->id,
                            'status' => $payment->status->value,
                        ],
                    );
                }

                return $paymentsCreated;
            });

            return $payments;
        } catch (Throwable $e) {
            $this->cleanupProofDocument($temporaryProofPath, $storedProofPath);
            $this->logger->error(
                action: 'MONTHLY_PAYMENT_FAILED',
                module: 'cash_advances',
                message: 'Monthly settlement payment failed',
                context: [
                    'employee_id' => $employeeId,
                    'months' => $selectedMonthKeys,
                ],
                exception: $e
            );

            throw $e;
        }
    }

    private function dateToMonthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function cashAdvanceStatementMonthExpression(): string
    {
        return $this->dateToMonthExpression('cash_advance_installments.due_date');
    }

    /**
     * Synchronize open/editable payroll lines when an installment receives a payment.
     */
    protected function syncOpenPayrollLinesForInstallment(CashAdvanceInstallment $installment): void
    {
        $openLines = PayrollItemLine::query()
            ->where('cash_advance_installment_id', $installment->id)
            ->whereHas('item.payrollPeriod', fn ($q) => $q->whereNotIn('status', ['paid', 'completed']))
            ->with('item')
            ->get();

        if ($openLines->isEmpty()) {
            return;
        }

        $unpaidBalance = max(0.0, round((float) $installment->amount - (float) $installment->paid_amount, 2));
        $payrollService = app(PayrollService::class);

        foreach ($openLines as $line) {
            $line->update(['amount' => $unpaidBalance]);
            if ($line->item) {
                $payrollService->recalculateItem($line->item);
            }
        }
    }
}
