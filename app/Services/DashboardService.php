<?php

namespace App\Services;

use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PayrollPeriodStatus;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\User;

class DashboardService
{
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

        // 1. Employee Headcount Metrics
        $totalActiveEmployees = Employee::query()->where('status', EmployeeStatus::ACTIVE)->count();
        $permanentCount = Employee::query()->where('status', EmployeeStatus::ACTIVE)->where('employment_status', EmploymentStatus::TETAP)->count();
        $contractCount = Employee::query()->where('status', EmployeeStatus::ACTIVE)->where('employment_status', EmploymentStatus::KONTRAK)->count();
        $internCount = Employee::query()->where('status', EmployeeStatus::ACTIVE)->where('employment_status', EmploymentStatus::MAGANG)->count();
        $newEmployeesCount = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE)
            ->whereMonth('join_date', $month)
            ->whereYear('join_date', $year)
            ->count();

        // 2. Selected Payroll Period Metrics
        $currentPayrollPeriod = PayrollPeriod::query()
            ->with(['items' => fn ($q) => $q->select('id', 'payroll_period_id', 'verified_at', 'pph21_amount', 'net_salary')])
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $totalNetSalary = $currentPayrollPeriod ? (float) $currentPayrollPeriod->total_net_salary : 0;
        $payrollVerifiedCount = $currentPayrollPeriod ? $currentPayrollPeriod->items->whereNotNull('verified_at')->count() : 0;
        $payrollTotalItemsCount = $currentPayrollPeriod ? $currentPayrollPeriod->items->count() : 0;
        $totalPph21 = $currentPayrollPeriod ? (float) $currentPayrollPeriod->items->sum('pph21_amount') : 0;
        $pph21EmployeeCount = $currentPayrollPeriod ? $currentPayrollPeriod->items->count() : 0;

        // 3. Cash Advance Metrics (Real-time Active)
        $totalRemainingCashAdvance = (float) CashAdvance::query()
            ->whereIn('status', [CashAdvanceStatus::APPROVED, CashAdvanceStatus::ACTIVE])
            ->sum('remaining_amount');

        $activeCashAdvanceBorrowersCount = CashAdvance::query()
            ->whereIn('status', [CashAdvanceStatus::APPROVED, CashAdvanceStatus::ACTIVE])
            ->where('remaining_amount', '>', 0)
            ->distinct('employee_id')
            ->count('employee_id');

        $pendingCashAdvancesCount = CashAdvance::query()
            ->where('status', CashAdvanceStatus::PENDING)
            ->count();

        // 4. Action Center Items (Urgent To-Dos)
        $actionItems = $this->getActionItems($month, $year);

        // 5. Monthly Payroll Trend (12 Months in $year)
        $periodsInYear = PayrollPeriod::query()
            ->with(['items' => fn ($q) => $q->select('id', 'payroll_period_id', 'pph21_amount')])
            ->where('year', $year)
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
            $taxTrend[] = $p ? (float) $p->items->sum('pph21_amount') : 0;
        }

        // 6. Department Headcount Distribution
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

        // 7. Recent Cash Advances
        $recentCashAdvances = CashAdvance::query()
            ->with(['employee.department', 'employee.position'])
            ->latest()
            ->take(4)
            ->get();

        // 8. Available Years List
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
            'pph21_employee_count' => $pph21EmployeeCount,
            'action_items' => $actionItems,
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
     * Get urgent action items / notifications for admin and management.
     *
     * @return array<int, array{type: string, icon: string, icon_color: string, title: string, text: string, action_label: string, url: string}>
     */
    public function getActionItems(?int $month = null, ?int $year = null): array
    {
        $month ??= (int) now()->format('n');
        $year ??= (int) now()->format('Y');

        $monthsList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $currentPayrollPeriod = PayrollPeriod::query()
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $payrollTotalItemsCount = $currentPayrollPeriod ? PayrollItem::query()->where('payroll_period_id', $currentPayrollPeriod->id)->count() : 0;
        $payrollVerifiedCount = $currentPayrollPeriod ? PayrollItem::query()->where('payroll_period_id', $currentPayrollPeriod->id)->whereNotNull('verified_at')->count() : 0;

        $pendingCashAdvancesCount = CashAdvance::query()
            ->where('status', CashAdvanceStatus::PENDING)
            ->count();

        $actionItems = [];

        // 1. Penggajian: Status dan Tindakan
        if ($currentPayrollPeriod && $currentPayrollPeriod->status->isEditable()) {
            $unverified = $payrollTotalItemsCount - $payrollVerifiedCount;
            if ($unverified > 0) {
                $actionItems[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-clipboard-check',
                    'icon_color' => 'text-warning',
                    'title' => "Penggajian {$monthsList[$month]} {$year}",
                    'text' => "{$unverified} dari {$payrollTotalItemsCount} karyawan belum diverifikasi HRD.",
                    'action_label' => 'Verifikasi Slip',
                    'url' => route('payrolls.show', $currentPayrollPeriod->id),
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
        } elseif ($currentPayrollPeriod && $currentPayrollPeriod->status === PayrollPeriodStatus::APPROVED) {
            $actionItems[] = [
                'type' => 'info',
                'icon' => 'fas fa-money-check-alt',
                'icon_color' => 'text-primary',
                'title' => "Penggajian {$monthsList[$month]} {$year}",
                'text' => 'Telah disetujui Direktur. Siap diproses pembayaran/transfer.',
                'action_label' => 'Proses Bayar',
                'url' => route('payrolls.show', $currentPayrollPeriod->id),
            ];
        } elseif ($currentPayrollPeriod && $currentPayrollPeriod->status === PayrollPeriodStatus::REJECTED) {
            $actionItems[] = [
                'type' => 'danger',
                'icon' => 'fas fa-exclamation-circle',
                'icon_color' => 'text-danger',
                'title' => "Revisi Penggajian {$monthsList[$month]} {$year}",
                'text' => 'Penggajian ditolak/memerlukan revisi dari Direktur.',
                'action_label' => 'Periksa Catatan',
                'url' => route('payrolls.show', $currentPayrollPeriod->id),
            ];
        } elseif (! $currentPayrollPeriod) {
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

        // 2. Kasbon: Pengajuan Baru Menunggu Persetujuan
        if ($pendingCashAdvancesCount > 0) {
            $actionItems[] = [
                'type' => 'warning',
                'icon' => 'fas fa-hand-holding-usd',
                'icon_color' => 'text-warning',
                'title' => 'Persetujuan Kasbon',
                'text' => "Terdapat {$pendingCashAdvancesCount} pengajuan kasbon baru menunggu persetujuan admin.",
                'action_label' => 'Tinjau Kasbon',
                'url' => route('cash-advances.index'),
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
            $actionItems[] = [
                'type' => 'warning',
                'icon' => 'fas fa-clock',
                'icon_color' => 'text-danger',
                'title' => 'Cicilan Jatuh Tempo',
                'text' => "Terdapat {$overdueCashAdvancesCount} karyawan memiliki cicilan kasbon terlewat jatuh tempo.",
                'action_label' => 'Tinjau Kasbon',
                'url' => route('cash-advances.index'),
            ];
        }

        // 4. Kepegawaian: Kontrak Berakhir dalam 30 Hari
        $expiringContracts = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE)
            ->where('employment_status', EmploymentStatus::KONTRAK)
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->count();

        if ($expiringContracts > 0) {
            $actionItems[] = [
                'type' => 'info',
                'icon' => 'fas fa-user-clock',
                'icon_color' => 'text-info',
                'title' => 'Masa Kontrak Karyawan',
                'text' => "Terdapat {$expiringContracts} karyawan yang masa kontraknya berakhir dalam 30 hari ke depan.",
                'action_label' => 'Tinjau Karyawan',
                'url' => route('employees.index'),
            ];
        }

        return $actionItems;
    }
}
