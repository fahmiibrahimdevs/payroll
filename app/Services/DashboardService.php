<?php

namespace App\Services;

use App\Enums\CashAdvancePaymentStatus;
use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PayrollPeriodStatus;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\CashAdvancePayment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public static function clearPayrollTrendCache(?int $year = null): void
    {
        if ($year !== null) {
            Cache::forget("dashboard_payroll_trend_{$year}");
        } else {
            $curr = (int) date('Y');
            for ($y = $curr - 2; $y <= $curr + 2; $y++) {
                Cache::forget("dashboard_payroll_trend_{$y}");
            }
        }
    }

    /**
     * @return array{employee: ?Employee, remaining_cash_advance: float, active_cash_advance_count: int, latest_payroll_item: ?PayrollItem, active_cash_advance: ?CashAdvance}
     */
    public function getEmployeeDashboardData(User $user): array
    {
        $employee = $user->getEmployeeRecord();

        if (! $employee) {
            return [
                'employee' => null,
                'remaining_cash_advance' => 0,
                'active_cash_advance_count' => 0,
                'latest_payroll_item' => null,
                'active_cash_advance' => null,
            ];
        }

        $employee->loadMissing(['detail', 'department', 'position']);

        $activeStatuses = [
            CashAdvanceStatus::ACTIVE->value,
            CashAdvanceStatus::APPROVED->value,
        ];

        $cashAdvanceMetrics = CashAdvance::query()
            ->where('employee_id', $employee->id)
            ->selectRaw('COALESCE(SUM(CASE WHEN status IN (?, ?) AND remaining_amount > 0 THEN remaining_amount ELSE 0 END), 0) as remaining_amount', $activeStatuses)
            ->selectRaw('COUNT(CASE WHEN status IN (?, ?) AND remaining_amount > 0 THEN 1 END) as active_count', $activeStatuses)
            ->first();

        $latestPayrollItem = PayrollItem::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollPeriod', fn ($q) => $q->whereIn('status', [
                PayrollPeriodStatus::APPROVED,
                PayrollPeriodStatus::PAID,
            ]))
            ->with(['payrollPeriod', 'lines.salaryComponent'])
            ->latest('id')
            ->first();

        $activeCashAdvance = CashAdvance::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', $activeStatuses)
            ->where('remaining_amount', '>', 0)
            ->latest('id')
            ->first();

        return [
            'employee' => $employee,
            'remaining_cash_advance' => (float) $cashAdvanceMetrics->remaining_amount,
            'active_cash_advance_count' => (int) $cashAdvanceMetrics->active_count,
            'latest_payroll_item' => $latestPayrollItem,
            'active_cash_advance' => $activeCashAdvance,
        ];
    }

    /**
     * @return array{total_users: int, active_users: int, inactive_users: int, total_roles: int}
     */
    public function getStatistics(): array
    {
        $userCounts = User::query()
            ->selectRaw('COUNT(*) as total_users')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users')
            ->first();

        return [
            'total_users' => (int) ($userCounts?->total_users ?? 0),
            'active_users' => (int) ($userCounts?->active_users ?? 0),
            'inactive_users' => (int) ($userCounts?->inactive_users ?? 0),
            'total_roles' => Role::query()->count(),
        ];
    }

    /**
     * Get comprehensive statistics and metrics for the Admin / Direktur HR dashboard.
     *
     * @return array<string, mixed>
     */
    public function getAdminStatistics(int $year, int $month): array
    {
        $monthsList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->addMonth();

        // 1. Employee Headcount Metrics
        $employeeCounts = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE)
            ->selectRaw('COUNT(*) as active_count')
            ->selectRaw('SUM(CASE WHEN employment_status = ? THEN 1 ELSE 0 END) as permanent_count', [EmploymentStatus::TETAP->value])
            ->selectRaw('SUM(CASE WHEN employment_status = ? THEN 1 ELSE 0 END) as contract_count', [EmploymentStatus::KONTRAK->value])
            ->selectRaw('SUM(CASE WHEN employment_status = ? THEN 1 ELSE 0 END) as intern_count', [EmploymentStatus::MAGANG->value])
            ->selectRaw('SUM(CASE WHEN join_date >= ? AND join_date < ? THEN 1 ELSE 0 END) as new_employees_count', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->first();

        $totalActiveEmployees = (int) $employeeCounts->active_count;
        $permanentCount = (int) $employeeCounts->permanent_count;
        $contractCount = (int) $employeeCounts->contract_count;
        $internCount = (int) $employeeCounts->intern_count;
        $newEmployeesCount = (int) $employeeCounts->new_employees_count;

        // 2. Selected Payroll Period Metrics
        $currentPayrollPeriod = PayrollPeriod::query()
            ->withCount([
                'items',
                'items as verified_items_count' => fn ($query) => $query->whereNotNull('verified_at'),
            ])
            ->withSum('items', 'pph21_amount')
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $totalNetSalary = $currentPayrollPeriod ? (float) $currentPayrollPeriod->total_net_salary : 0;
        $payrollVerifiedCount = (int) ($currentPayrollPeriod?->verified_items_count ?? 0);
        $payrollTotalItemsCount = (int) ($currentPayrollPeriod?->items_count ?? 0);
        $totalPph21 = (float) ($currentPayrollPeriod?->items_sum_pph21_amount ?? 0);

        // 3. Cash Advance Metrics (Real-time Active)
        $cashAdvanceMetrics = CashAdvance::query()
            ->selectRaw('COALESCE(SUM(CASE WHEN status IN (?, ?) THEN remaining_amount ELSE 0 END), 0) as remaining_amount', [CashAdvanceStatus::APPROVED->value, CashAdvanceStatus::ACTIVE->value])
            ->selectRaw('COUNT(DISTINCT CASE WHEN status IN (?, ?) AND remaining_amount > 0 THEN employee_id END) as borrowers_count', [CashAdvanceStatus::APPROVED->value, CashAdvanceStatus::ACTIVE->value])
            ->first();

        $totalRemainingCashAdvance = (float) $cashAdvanceMetrics->remaining_amount;
        $activeCashAdvanceBorrowersCount = (int) $cashAdvanceMetrics->borrowers_count;

        // 4. Monthly Payroll Trend (12 Months in $year)
        $trendData = Cache::remember("dashboard_payroll_trend_{$year}", 1800, function () use ($year) {
            $taxByPeriod = PayrollItem::query()
                ->join('payroll_periods as tax_periods', 'payroll_items.payroll_period_id', '=', 'tax_periods.id')
                ->where('tax_periods.year', $year)
                ->select('payroll_items.payroll_period_id')
                ->selectRaw('SUM(payroll_items.pph21_amount) as tax_total')
                ->groupBy('payroll_items.payroll_period_id');

            $periodsInYear = PayrollPeriod::query()
                ->leftJoinSub($taxByPeriod, 'tax_totals', fn ($join) => $join->on('payroll_periods.id', '=', 'tax_totals.payroll_period_id'))
                ->where('payroll_periods.year', $year)
                ->select(['payroll_periods.month', 'payroll_periods.total_basic_salary', 'payroll_periods.total_allowances', 'payroll_periods.total_net_salary', 'tax_totals.tax_total'])
                ->selectRaw('(payroll_periods.total_basic_salary + payroll_periods.total_allowances) as total_gross_salary')
                ->get()
                ->keyBy('month');

            $trendMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $grossTrend = [];
            $netTrend = [];
            $taxTrend = [];

            for ($m = 1; $m <= 12; $m++) {
                $p = $periodsInYear->get($m);
                $grossTrend[] = $p ? (float) $p->total_gross_salary : 0;
                $netTrend[] = $p ? (float) $p->total_net_salary : 0;
                $taxTrend[] = $p ? (float) ($p->tax_total ?? 0) : 0;
            }

            return [
                'trend_months' => $trendMonths,
                'gross_trend' => $grossTrend,
                'net_trend' => $netTrend,
                'tax_trend' => $taxTrend,
            ];
        });

        $trendMonths = $trendData['trend_months'];
        $grossTrend = $trendData['gross_trend'];
        $netTrend = $trendData['net_trend'];
        $taxTrend = $trendData['tax_trend'];

        // 5. Department Headcount Distribution
        $departments = Department::query()
            ->withCount(['employees' => fn ($q) => $q->where('status', EmployeeStatus::ACTIVE)])
            ->get();

        $noDeptCount = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE)
            ->whereNull('department_id')
            ->count();

        $palette = [
            '#0b52aa', '#10b981', '#f59e0b', '#8b5cf6',
            '#ef4444', '#06b6d4', '#ec4899', '#64748b',
            '#14b8a6', '#f97316', '#6366f1', '#84cc16',
        ];

        $deptLabels = $departments->pluck('name')->all();
        $deptCounts = $departments->pluck('employees_count')->all();

        if ($noDeptCount > 0) {
            $deptLabels[] = 'Tanpa Departemen';
            $deptCounts[] = $noDeptCount;
        }

        // 6. Recent Cash Advances
        $recentCashAdvances = CashAdvance::query()
            ->with(['employee.department', 'employee.position'])
            ->latest()
            ->take(4)
            ->get();

        // 7. Available Years List
        $payrollYears = PayrollPeriod::query()->distinct()->pluck('year')->all();
        $currentYear = (int) now()->format('Y');
        $availableYears = array_unique(array_merge([$currentYear - 1, $currentYear, $currentYear + 1], $payrollYears));
        sort($availableYears);

        return [
            'active_employees_count' => $totalActiveEmployees,
            'permanent_count' => $permanentCount,
            'contract_count' => $contractCount,
            'intern_count' => $internCount,
            'new_employees_count' => $newEmployeesCount,
            'current_payroll_period' => $currentPayrollPeriod,
            'total_net_salary' => $totalNetSalary,
            'formatted_net_salary' => 'Rp '.number_format($totalNetSalary, 0, ',', '.'),
            'payroll_verified_count' => $payrollVerifiedCount,
            'payroll_total_items_count' => $payrollTotalItemsCount,
            'total_remaining_cash_advance' => $totalRemainingCashAdvance,
            'formatted_remaining_cash_advance' => 'Rp '.number_format($totalRemainingCashAdvance, 0, ',', '.'),
            'active_cash_advance_borrowers_count' => $activeCashAdvanceBorrowersCount,
            'total_pph21' => $totalPph21,
            'formatted_pph21' => 'Rp '.number_format($totalPph21, 0, ',', '.'),
            'payroll_trend' => [
                'labels' => $trendMonths,
                'gross' => $grossTrend,
                'net' => $netTrend,
                'tax' => $taxTrend,
            ],
            'department_distribution' => [
                'labels' => $deptLabels,
                'counts' => $deptCounts,
                'colors' => array_slice($palette, 0, count($deptLabels)),
                'total' => $totalActiveEmployees,
            ],
            'recent_cash_advances' => $recentCashAdvances,
            'available_years' => $availableYears,
            'months_list' => $monthsList,
        ];
    }

    /**
     * In-memory cache for action items within the current request.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected static array $memoizedActionItems = [];

    public static function clearActionItemsCache(): void
    {
        static::$memoizedActionItems = [];
    }

    /**
     * Get urgent action items / notifications for admin and management.
     *
     * @return array<int, array{type: string, icon: string, icon_color: string, title: string, text: string, action_label: string, url: string}>
     */
    public function getActionItems(?int $month = null, ?int $year = null): array
    {
        $month ??= (int) now()->format('n');
        $year ??= (int) now()->format('Y');
        $currentUser = auth()->user();
        $userId = $currentUser?->id ?? 0;
        $cacheKey = "{$userId}_{$month}_{$year}";

        if (isset(static::$memoizedActionItems[$cacheKey])) {
            return static::$memoizedActionItems[$cacheKey];
        }

        $isDirector = $currentUser?->hasRole('direktur') ?? false;
        $isAdmin = $currentUser?->hasRole('admin') ?? false;

        $currentPayrollPeriod = PayrollPeriod::query()
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $payrollCounts = $currentPayrollPeriod
            ? PayrollItem::query()
                ->where('payroll_period_id', $currentPayrollPeriod->id)
                ->selectRaw('COUNT(*) as total_count')
                ->selectRaw('SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
                ->first()
            : null;

        $payrollTotalItemsCount = (int) ($payrollCounts?->total_count ?? 0);
        $payrollVerifiedCount = (int) ($payrollCounts?->verified_count ?? 0);

        $pendingCashAdvancesCount = $isDirector || $isAdmin
            ? CashAdvance::query()->where('status', CashAdvanceStatus::PENDING)->count()
            : 0;

        $pendingCashAdvancePaymentsCount = $isDirector || $isAdmin
            ? CashAdvancePayment::query()->where('status', CashAdvancePaymentStatus::PENDING)->count()
            : 0;

        return static::$memoizedActionItems[$cacheKey] = $this->buildActionItems(
            $month,
            $year,
            $currentPayrollPeriod,
            $payrollTotalItemsCount,
            $payrollVerifiedCount,
            $pendingCashAdvancesCount,
            $pendingCashAdvancePaymentsCount,
            $isDirector,
            $isAdmin,
        );
    }

    /**
     * @return array<int, array{type: string, icon: string, icon_color: string, title: string, text: string, action_label: string, url: string}>
     */
    private function buildActionItems(
        int $month,
        int $year,
        ?PayrollPeriod $currentPayrollPeriod,
        int $payrollTotalItemsCount,
        int $payrollVerifiedCount,
        int $pendingCashAdvancesCount,
        int $pendingCashAdvancePaymentsCount,
        bool $isDirector,
        bool $isAdmin,
    ): array {
        $monthsList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $actionItems = [];

        // 1. Penggajian: Status dan Tindakan
        if ($currentPayrollPeriod && ($isAdmin || $isDirector) && $currentPayrollPeriod->employee_count > $payrollTotalItemsCount) {
            $missingSlipsCount = $currentPayrollPeriod->employee_count - $payrollTotalItemsCount;
            $actionItems[] = [
                'type' => 'danger',
                'icon' => 'fas fa-exclamation-triangle',
                'icon_color' => 'text-danger',
                'title' => "Slip Penggajian {$monthsList[$month]} {$year}",
                'text' => "Jumlah slip tercatat {$missingSlipsCount} lebih sedikit dari jumlah karyawan pada periode ini.",
                'action_label' => 'Periksa Periode Payroll',
                'url' => route('payrolls.show', $currentPayrollPeriod->id),
            ];
        }

        if ($currentPayrollPeriod && ($isAdmin || $isDirector)) {
            $negativeNetItemsCount = PayrollItem::query()
                ->where('payroll_period_id', $currentPayrollPeriod->id)
                ->where('net_salary', '<', 0)
                ->count();

            if ($negativeNetItemsCount > 0) {
                $negativeNetItem = PayrollItem::query()
                    ->where('payroll_period_id', $currentPayrollPeriod->id)
                    ->where('net_salary', '<', 0)
                    ->orderBy('id')
                    ->first(['id']);

                $actionItems[] = [
                    'type' => 'danger',
                    'icon' => 'fas fa-exclamation-circle',
                    'icon_color' => 'text-danger',
                    'title' => "Nilai Slip Perlu Diperiksa — {$monthsList[$month]} {$year}",
                    'text' => "Ada {$negativeNetItemsCount} slip dengan gaji bersih negatif.",
                    'action_label' => 'Periksa Slip',
                    'url' => $negativeNetItem
                        ? route('payrolls.items.edit', [$currentPayrollPeriod->id, $negativeNetItem->id])
                        : route('payrolls.show', $currentPayrollPeriod->id),
                ];
            }
        }

        if ($currentPayrollPeriod && $currentPayrollPeriod->status === PayrollPeriodStatus::DRAFT && $isAdmin) {
            $unverified = $payrollTotalItemsCount - $payrollVerifiedCount;
            if ($unverified > 0) {
                $nextUnverifiedItem = PayrollItem::query()
                    ->where('payroll_period_id', $currentPayrollPeriod->id)
                    ->whereNull('verified_at')
                    ->orderBy('id')
                    ->first(['id']);

                $actionItems[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-clipboard-check',
                    'icon_color' => 'text-warning',
                    'title' => "Penggajian {$monthsList[$month]} {$year}",
                    'text' => "{$unverified} dari {$payrollTotalItemsCount} karyawan belum diverifikasi HRD.",
                    'action_label' => 'Verifikasi Slip',
                    'url' => $nextUnverifiedItem
                        ? route('payrolls.items.edit', [$currentPayrollPeriod->id, $nextUnverifiedItem->id])
                        : route('payrolls.show', $currentPayrollPeriod->id),
                ];
            } elseif ($payrollTotalItemsCount > 0) {
                $actionItems[] = [
                    'type' => 'success',
                    'icon' => 'fas fa-check-double',
                    'icon_color' => 'text-success',
                    'title' => "Penggajian {$monthsList[$month]} {$year}",
                    'text' => "Seluruh slip gaji ({$payrollTotalItemsCount} karyawan) telah diverifikasi HRD. Siap diajukan ke Direktur.",
                    'action_label' => 'Ajukan ke Direktur',
                    'url' => route('payrolls.show', $currentPayrollPeriod->id),
                ];
            }
        } elseif ($currentPayrollPeriod && $currentPayrollPeriod->status === PayrollPeriodStatus::APPROVED && $isAdmin) {
            $unpaidItemsCount = PayrollItem::query()
                ->where('payroll_period_id', $currentPayrollPeriod->id)
                ->where(function ($query): void {
                    $query->whereNull('payment_status')->orWhere('payment_status', '!=', 'paid');
                })
                ->count();

            if ($unpaidItemsCount > 0) {
                $actionItems[] = [
                    'type' => 'info',
                    'icon' => 'fas fa-money-check-alt',
                    'icon_color' => 'text-primary',
                    'title' => "Penggajian {$monthsList[$month]} {$year}",
                    'text' => "Disetujui Direktur; {$unpaidItemsCount} slip masih belum dibayar.",
                    'action_label' => 'Proses Pembayaran',
                    'url' => route('payrolls.show', $currentPayrollPeriod->id),
                ];
            }
        } elseif ($currentPayrollPeriod && $currentPayrollPeriod->status === PayrollPeriodStatus::REJECTED && $isAdmin) {
            $actionItems[] = [
                'type' => 'danger',
                'icon' => 'fas fa-exclamation-circle',
                'icon_color' => 'text-danger',
                'title' => "Revisi Penggajian {$monthsList[$month]} {$year}",
                'text' => 'Penggajian ditolak/memerlukan revisi dari Direktur.',
                'action_label' => 'Periksa Catatan',
                'url' => route('payrolls.show', $currentPayrollPeriod->id),
            ];
        } elseif (! $currentPayrollPeriod && $isAdmin) {
            $actionItems[] = [
                'type' => 'info',
                'icon' => 'fas fa-calendar-plus',
                'icon_color' => 'text-primary',
                'title' => 'Periode Penggajian',
                'text' => "Periode Penggajian {$monthsList[$month]} {$year} belum dibuat.",
                'action_label' => 'Kelola Penggajian',
                'url' => route('payrolls.index'),
            ];
        }

        if ($isDirector) {
            $pendingPayrollPeriods = PayrollPeriod::query()
                ->where('status', PayrollPeriodStatus::SUBMITTED)
                ->orderBy('submitted_at')
                ->get(['id', 'month', 'year', 'submitted_at']);

            foreach ($pendingPayrollPeriods as $pendingPayrollPeriod) {
                $periodMonth = $monthsList[(int) $pendingPayrollPeriod->month] ?? (string) $pendingPayrollPeriod->month;
                $waitingDays = $pendingPayrollPeriod->submitted_at
                    ? (int) $pendingPayrollPeriod->submitted_at->diffInDays(now())
                    : null;
                $isDelayed = $waitingDays !== null && $waitingDays >= 3;

                $actionItems[] = [
                    'type' => 'warning',
                    'icon' => $isDelayed ? 'fas fa-hourglass-half' : 'fas fa-file-signature',
                    'icon_color' => 'text-warning',
                    'title' => $isDelayed ? 'Persetujuan Penggajian Tertunda' : "Penggajian {$periodMonth} {$pendingPayrollPeriod->year}",
                    'text' => $isDelayed
                        ? "Penggajian {$periodMonth} {$pendingPayrollPeriod->year} sudah menunggu {$waitingDays} hari."
                        : "Penggajian {$periodMonth} {$pendingPayrollPeriod->year} menunggu persetujuan Direktur.",
                    'action_label' => 'Tinjau Penggajian',
                    'url' => route('payrolls.show', $pendingPayrollPeriod->id),
                ];
            }
        }

        // 2. Kasbon: Pengajuan Baru Menunggu Persetujuan
        if ($pendingCashAdvancesCount > 0 && ($isDirector || $isAdmin)) {
            $oldestPendingAdvance = CashAdvance::query()
                ->where('status', CashAdvanceStatus::PENDING)
                ->oldest('created_at')
                ->first(['id', 'created_at']);
            $agingRequestsCount = CashAdvance::query()
                ->where('status', CashAdvanceStatus::PENDING)
                ->where('created_at', '<=', now()->subDays(3))
                ->count();
            $actionItems[] = [
                'type' => 'warning',
                'icon' => 'fas fa-hand-holding-usd',
                'icon_color' => 'text-warning',
                'title' => 'Persetujuan Kasbon',
                'text' => "Terdapat {$pendingCashAdvancesCount} pengajuan kasbon menunggu persetujuan Direktur.".($agingRequestsCount > 0 ? " {$agingRequestsCount} pengajuan sudah lebih dari 3 hari." : ''),
                'action_label' => $isDirector ? 'Tinjau Pengajuan' : 'Lihat Kasbon',
                'url' => ($isDirector && $oldestPendingAdvance)
                    ? route('cash-advances.review', ['advance' => $oldestPendingAdvance->id, 'action' => 'approval'])
                    : route('cash-advances.index'),
            ];
        }

        if ($pendingCashAdvancePaymentsCount > 0 && $isDirector) {
            $oldestPendingPayment = CashAdvancePayment::query()
                ->with('cashAdvance:id')
                ->where('status', CashAdvancePaymentStatus::PENDING)
                ->oldest('created_at')
                ->first(['id', 'cash_advance_id', 'created_at']);
            $agingPaymentsCount = CashAdvancePayment::query()
                ->where('status', CashAdvancePaymentStatus::PENDING)
                ->where('created_at', '<=', now()->subDays(3))
                ->count();
            $actionItems[] = [
                'type' => 'warning',
                'icon' => 'fas fa-money-check-alt',
                'icon_color' => 'text-warning',
                'title' => 'Persetujuan Pembayaran Kasbon',
                'text' => "Terdapat {$pendingCashAdvancePaymentsCount} pembayaran kasbon menunggu persetujuan Direktur.".($agingPaymentsCount > 0 ? " {$agingPaymentsCount} pembayaran sudah lebih dari 3 hari." : ''),
                'action_label' => 'Tinjau Pembayaran',
                'url' => $oldestPendingPayment
                    ? route('cash-advances.review', ['advance' => $oldestPendingPayment->cash_advance_id, 'action' => 'payment', 'payment' => $oldestPendingPayment->id])
                    : route('cash-advances.index'),
            ];
        } elseif ($pendingCashAdvancePaymentsCount > 0 && $isAdmin) {
            $oldestPendingPayment = CashAdvancePayment::query()
                ->where('status', CashAdvancePaymentStatus::PENDING)
                ->oldest('created_at')
                ->first(['id', 'cash_advance_id']);
            $actionItems[] = [
                'type' => 'info',
                'icon' => 'fas fa-hourglass-half',
                'icon_color' => 'text-info',
                'title' => 'Pembayaran Kasbon',
                'text' => "{$pendingCashAdvancePaymentsCount} pembayaran kasbon menunggu persetujuan Direktur.",
                'action_label' => 'Lihat Status',
                'url' => $oldestPendingPayment
                    ? route('cash-advances.review', ['advance' => $oldestPendingPayment->cash_advance_id, 'action' => 'payment', 'payment' => $oldestPendingPayment->id])
                    : route('cash-advances.index'),
            ];
        }

        // 3. Kasbon: Cicilan Jatuh Tempo Terlewat (Overdue)
        $startOfMonth = now()->startOfMonth()->toDateString();
        $overdueCashAdvancesCount = (int) CashAdvanceInstallment::query()
            ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_installments.cash_advance_id')
            ->whereIn('cash_advances.status', [CashAdvanceStatus::ACTIVE->value, CashAdvanceStatus::APPROVED->value])
            ->whereDate('cash_advance_installments.due_date', '<', $startOfMonth)
            ->whereIn('cash_advance_installments.status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
            ->distinct('cash_advances.employee_id')
            ->count('cash_advances.employee_id');

        if ($overdueCashAdvancesCount > 0) {
            $overdueAdvance = CashAdvance::query()
                ->whereIn('status', [CashAdvanceStatus::ACTIVE, CashAdvanceStatus::APPROVED])
                ->whereHas('installments', function ($query) use ($startOfMonth): void {
                    $query->whereDate('due_date', '<', $startOfMonth)
                        ->whereIn('status', [InstallmentStatus::PENDING, InstallmentStatus::PARTIALLY_PAID]);
                })
                ->orderBy('id')
                ->first(['id']);

            $actionItems[] = [
                'type' => 'warning',
                'icon' => 'fas fa-clock',
                'icon_color' => 'text-danger',
                'title' => 'Cicilan Jatuh Tempo',
                'text' => "Terdapat {$overdueCashAdvancesCount} karyawan memiliki cicilan kasbon terlewat jatuh tempo.",
                'action_label' => 'Tinjau Kasbon',
                'url' => $overdueAdvance
                    ? route('cash-advances.review', ['advance' => $overdueAdvance->id, 'action' => 'detail'])
                    : route('cash-advances.index'),
            ];
        }

        $inactiveEmployeesList = CashAdvance::query()
            ->whereIn('status', [CashAdvanceStatus::ACTIVE, CashAdvanceStatus::APPROVED])
            ->where('remaining_amount', '>', 0)
            ->whereHas('employee', fn ($query) => $query->where('status', '!=', EmployeeStatus::ACTIVE))
            ->with('employee:id,full_name')
            ->orderBy('id')
            ->take(6)
            ->get(['id', 'employee_id']);

        $inactiveEmployeesWithDebt = $inactiveEmployeesList->take(5);
        $inactiveEmployeesWithDebtCount = $inactiveEmployeesList->count() > 5
            ? CashAdvance::query()
                ->whereIn('status', [CashAdvanceStatus::ACTIVE, CashAdvanceStatus::APPROVED])
                ->where('remaining_amount', '>', 0)
                ->whereHas('employee', fn ($query) => $query->where('status', '!=', EmployeeStatus::ACTIVE))
                ->count()
            : $inactiveEmployeesList->count();

        foreach ($inactiveEmployeesWithDebt as $advance) {
            $actionItems[] = [
                'type' => 'warning',
                'icon' => 'fas fa-user-slash',
                'icon_color' => 'text-danger',
                'title' => 'Kasbon Karyawan Nonaktif',
                'text' => ($advance->employee?->full_name ?? 'Seorang karyawan').' memiliki sisa kasbon yang perlu ditinjau.',
                'action_label' => 'Buka Rincian Kasbon',
                'url' => route('cash-advances.review', ['advance' => $advance->id, 'action' => 'detail']),
            ];
        }

        if ($inactiveEmployeesWithDebtCount > $inactiveEmployeesWithDebt->count()) {
            $actionItems[] = [
                'type' => 'warning',
                'icon' => 'fas fa-users',
                'icon_color' => 'text-warning',
                'title' => 'Kasbon Karyawan Nonaktif Lainnya',
                'text' => ($inactiveEmployeesWithDebtCount - $inactiveEmployeesWithDebt->count()).' kasbon lainnya masih memiliki saldo untuk ditinjau.',
                'action_label' => 'Buka Daftar Kasbon',
                'url' => route('cash-advances.index'),
            ];
        }

        // Kasbon pada Karyawan yang Kontraknya Berakhir dalam 30 Hari
        $expiringEmployeesWithDebt = CashAdvance::query()
            ->whereIn('status', [CashAdvanceStatus::ACTIVE, CashAdvanceStatus::APPROVED])
            ->where('remaining_amount', '>', 0)
            ->whereHas('employee', function ($query): void {
                $query->where('status', EmployeeStatus::ACTIVE)
                    ->whereNotNull('contract_end_date')
                    ->whereBetween('contract_end_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()]);
            })
            ->with('employee:id,full_name,contract_end_date')
            ->take(5)
            ->get(['id', 'employee_id', 'remaining_amount']);

        foreach ($expiringEmployeesWithDebt as $advance) {
            $actionItems[] = [
                'type' => 'warning',
                'icon' => 'fas fa-hand-holding-usd',
                'icon_color' => 'text-danger',
                'title' => 'Sisa Kasbon Kontrak Berakhir',
                'text' => ($advance->employee?->full_name ?? 'Karyawan').' berakhir kontrak pada '.($advance->employee?->contract_end_date?->format('d M Y') ?? '-').' dengan sisa kasbon Rp '.number_format((float) $advance->remaining_amount, 0, ',', '.').'. Pastikan pelunasan pada slip gaji terakhir.',
                'action_label' => 'Tinjau Kasbon',
                'url' => route('cash-advances.review', ['advance' => $advance->id, 'action' => 'detail']),
            ];
        }

        // 4. Kepegawaian: Kontrak Berakhir dalam 30 Hari
        $expiringContracts = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE)
            ->where('employment_status', EmploymentStatus::KONTRAK)
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->orderBy('contract_end_date')
            ->get(['id']);
        $expiringContractsCount = $expiringContracts->count();

        if ($expiringContractsCount > 0) {
            $nextExpiringContract = $expiringContracts->first();

            $actionItems[] = [
                'type' => 'info',
                'icon' => 'fas fa-user-clock',
                'icon_color' => 'text-info',
                'title' => 'Masa Kontrak Karyawan',
                'text' => "Terdapat {$expiringContractsCount} karyawan yang masa kontraknya berakhir dalam 30 hari ke depan.",
                'action_label' => 'Tinjau Karyawan',
                'url' => $nextExpiringContract
                    ? ($isAdmin
                        ? route('employees.edit', ['employee' => $nextExpiringContract->id, 'section' => 'kepegawaian'])
                        : route('employees.show', ['employee' => $nextExpiringContract->id, 'tab' => 'kepegawaian']))
                    : route('employees.index'),
            ];
        }

        $expiredContractsList = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE)
            ->where('employment_status', EmploymentStatus::KONTRAK)
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '<', now()->toDateString())
            ->orderBy('contract_end_date')
            ->take(6)
            ->get(['id', 'full_name', 'contract_end_date']);

        $expiredContracts = $expiredContractsList->take(5);
        $expiredContractsCount = $expiredContractsList->count() > 5
            ? Employee::query()
                ->where('status', EmployeeStatus::ACTIVE)
                ->where('employment_status', EmploymentStatus::KONTRAK)
                ->whereNotNull('contract_end_date')
                ->whereDate('contract_end_date', '<', now()->toDateString())
                ->count()
            : $expiredContractsList->count();

        foreach ($expiredContracts as $employee) {
            $actionItems[] = [
                'type' => 'danger',
                'icon' => 'fas fa-user-clock',
                'icon_color' => 'text-danger',
                'title' => "Kontrak Berakhir: {$employee->full_name}",
                'text' => 'Tanggal akhir kontrak sudah lewat pada '.$employee->contract_end_date?->format('d M Y').'.',
                'action_label' => 'Tinjau Data Kepegawaian',
                'url' => $isAdmin
                    ? route('employees.edit', ['employee' => $employee->id, 'section' => 'kepegawaian'])
                    : route('employees.show', ['employee' => $employee->id, 'tab' => 'kepegawaian']),
            ];
        }

        if ($expiredContractsCount > $expiredContracts->count()) {
            $actionItems[] = [
                'type' => 'danger',
                'icon' => 'fas fa-users',
                'icon_color' => 'text-danger',
                'title' => 'Kontrak Berakhir Lainnya',
                'text' => ($expiredContractsCount - $expiredContracts->count()).' karyawan kontrak aktif lainnya memiliki tanggal akhir kontrak yang sudah lewat.',
                'action_label' => 'Buka Daftar Karyawan',
                'url' => route('employees.index'),
            ];
        }

        // 5. Data penting karyawan yang belum lengkap untuk payroll.
        if ($isAdmin || $isDirector) {
            $incompleteEmployeesList = Employee::query()
                ->where('status', EmployeeStatus::ACTIVE)
                ->with('detail:id,employee_id,bank_name,bank_account_number,bank_account_name,tax_recipient_type,nik,bpjs_kesehatan_number,bpjs_ketenagakerjaan_number')
                ->where(function ($query): void {
                    $query->whereDoesntHave('detail')
                        ->orWhereHas('detail', function ($detailQuery): void {
                            $detailQuery->whereNull('bank_name')->orWhere('bank_name', '')
                                ->orWhereNull('bank_account_number')->orWhere('bank_account_number', '')
                                ->orWhereNull('bank_account_name')->orWhere('bank_account_name', '')
                                ->orWhereNull('tax_recipient_type')->orWhere('tax_recipient_type', '')
                                ->orWhereNull('nik')->orWhere('nik', '')
                                ->orWhereNull('bpjs_kesehatan_number')->orWhere('bpjs_kesehatan_number', '')
                                ->orWhereNull('bpjs_ketenagakerjaan_number')->orWhere('bpjs_ketenagakerjaan_number', '');
                        });
                })
                ->orderBy('full_name')
                ->take(6)
                ->get(['id', 'full_name']);

            $employeesNeedingPayrollData = $incompleteEmployeesList->take(5);
            $incompleteEmployeeCount = $incompleteEmployeesList->count() > 5
                ? Employee::query()
                    ->where('status', EmployeeStatus::ACTIVE)
                    ->where(function ($query): void {
                        $query->whereDoesntHave('detail')
                            ->orWhereHas('detail', function ($detailQuery): void {
                                $detailQuery->whereNull('bank_name')->orWhere('bank_name', '')
                                    ->orWhereNull('bank_account_number')->orWhere('bank_account_number', '')
                                    ->orWhereNull('bank_account_name')->orWhere('bank_account_name', '')
                                    ->orWhereNull('tax_recipient_type')->orWhere('tax_recipient_type', '')
                                    ->orWhereNull('nik')->orWhere('nik', '')
                                    ->orWhereNull('bpjs_kesehatan_number')->orWhere('bpjs_kesehatan_number', '')
                                    ->orWhereNull('bpjs_ketenagakerjaan_number')->orWhere('bpjs_ketenagakerjaan_number', '');
                            });
                    })
                    ->count()
                : $incompleteEmployeesList->count();

            foreach ($employeesNeedingPayrollData->take(5) as $employee) {
                $detail = $employee->detail;
                $missingFields = [];

                if (! $detail || blank($detail->bank_name) || blank($detail->bank_account_number) || blank($detail->bank_account_name)) {
                    $missingFields[] = 'rekening bank';
                }

                if (! $detail?->tax_recipient_type) {
                    $missingFields[] = 'klasifikasi pajak';
                }

                if (! $detail || blank($detail->nik)) {
                    $missingFields[] = 'NIK';
                }

                if (! $detail || blank($detail->bpjs_kesehatan_number) || blank($detail->bpjs_ketenagakerjaan_number)) {
                    $missingFields[] = 'data nomor BPJS';
                }

                $opensEmploymentTab = in_array('rekening bank', $missingFields, true);

                $actionItems[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-user-edit',
                    'icon_color' => 'text-warning',
                    'title' => "Data Payroll: {$employee->full_name}",
                    'text' => 'Lengkapi '.implode(' dan ', $missingFields).' sebelum proses payroll.',
                    'action_label' => $opensEmploymentTab
                        ? 'Buka Data Kepegawaian'
                        : 'Buka Data Pajak & BPJS',
                    'url' => $isAdmin
                        ? route('employees.edit', [
                            'employee' => $employee->id,
                            'section' => $opensEmploymentTab ? 'kepegawaian' : 'pajak',
                        ])
                        : route('employees.show', [
                            'employee' => $employee->id,
                            'tab' => $opensEmploymentTab ? 'kepegawaian' : 'pajak-bpjs',
                        ]),
                ];
            }

            $remainingIncompleteEmployees = $incompleteEmployeeCount - $employeesNeedingPayrollData->count();
            if ($remainingIncompleteEmployees > 0) {
                $actionItems[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-users',
                    'icon_color' => 'text-warning',
                    'title' => 'Data Payroll Karyawan Lainnya',
                    'text' => "{$remainingIncompleteEmployees} karyawan aktif lainnya memiliki data rekening atau klasifikasi pajak yang belum lengkap.",
                    'action_label' => 'Buka Daftar Karyawan',
                    'url' => route('employees.index'),
                ];
            }
        }

        return $actionItems;
    }
}
