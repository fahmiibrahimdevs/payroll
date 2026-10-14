<?php

namespace App\Services;

use App\Enums\CashAdvancePaymentStatus;
use App\Enums\CashAdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayrollLineCategory;
use App\Enums\PayrollLineType;
use App\Enums\PayrollPeriodStatus;
use App\Enums\TaxMethod;
use App\Models\CashAdvance;
use App\Models\CashAdvanceInstallment;
use App\Models\CashAdvancePayment;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use App\Models\PayrollPeriod;
use App\Models\PayrollSequence;
use App\Models\SalaryComponent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class PayrollService
{
    /**
     * Get paginated payroll periods for index list.
     */
    public function getPaginatedPeriods(
        int $perPage = 10,
        ?string $search = null,
        ?string $status = null,
        ?int $year = null
    ): LengthAwarePaginator {
        return PayrollPeriod::query()
            ->with(['creator', 'submitter', 'approver'])
            ->withCount('items')
            ->when($search, function (Builder $query, string $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($year, fn (Builder $q) => $q->where('year', $year))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Get dashboard summary statistics.
     */
    public function getStatistics(?int $year = null): array
    {
        $currentYear = $year ?? (int) Carbon::now()->year;
        $currentMonth = (int) Carbon::now()->month;

        $totalPaidThisYear = (float) PayrollPeriod::where('status', PayrollPeriodStatus::PAID->value)
            ->where('year', $currentYear)
            ->sum('total_net_salary');

        $pendingApprovalCount = PayrollPeriod::where('status', PayrollPeriodStatus::SUBMITTED->value)->count();
        $draftCount = PayrollPeriod::where('status', PayrollPeriodStatus::DRAFT->value)->count();
        $totalActiveEmployees = Employee::where('status', EmployeeStatus::ACTIVE)->count();

        $activePeriod = PayrollPeriod::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->first() ?? PayrollPeriod::orderByDesc('year')->orderByDesc('month')->first();

        $activeNetSalary = (float) ($activePeriod?->total_net_salary ?? 0);
        $activePph21 = 0;
        $activeKasbon = 0;
        $activeBpjsKaryawan = 0;
        $activeBpjsPerusahaan = (float) ($activePeriod?->total_company_contributions ?? 0);
        $activeKasbonCount = 0;

        if ($activePeriod) {
            $itemIds = $activePeriod->items()->pluck('id');
            $activePph21 = (float) $activePeriod->items()->sum('pph21_amount');

            $activeKasbon = (float) PayrollItemLine::whereIn('payroll_item_id', $itemIds)
                ->where('category', PayrollLineCategory::CASH_ADVANCE)
                ->sum('amount');

            $activeKasbonCount = PayrollItemLine::whereIn('payroll_item_id', $itemIds)
                ->where('category', PayrollLineCategory::CASH_ADVANCE)
                ->count();

            $activeBpjsKaryawan = (float) PayrollItemLine::whereIn('payroll_item_id', $itemIds)
                ->where('category', PayrollLineCategory::BPJS)
                ->sum('amount');
        }

        $activeTotalBpjs = $activeBpjsKaryawan + $activeBpjsPerusahaan;

        // Distribusi Pengeluaran Bulanan Tahun Berjalan
        $yearlyPeriods = PayrollPeriod::where('year', $currentYear)
            ->orderBy('month')
            ->get();

        $yearlyMonthlyData = [];
        $totalYearlyExpense = 0;
        $totalYearlyBasic = 0;
        $totalYearlyAllowances = 0;
        $totalYearlyDeductions = 0;
        $totalYearlyCompanyContributions = 0;
        $totalYearlyHeadcount = 0;

        foreach ($yearlyPeriods as $period) {
            $net = (float) $period->total_net_salary;
            $basic = (float) $period->total_basic_salary;
            $allowances = (float) $period->total_allowances;
            $deductions = (float) $period->total_deductions;
            $companyContributions = (float) $period->total_company_contributions;
            $empCount = (int) $period->employee_count;

            $totalYearlyExpense += $net;
            $totalYearlyBasic += $basic;
            $totalYearlyAllowances += $allowances;
            $totalYearlyDeductions += $deductions;
            $totalYearlyCompanyContributions += $companyContributions;
            $totalYearlyHeadcount += $empCount;

            $avgPerEmp = $empCount > 0 ? ($net / $empCount) : 0;

            $yearlyMonthlyData[] = [
                'id' => $period->id,
                'reference_number' => $period->reference_number,
                'name' => $period->name,
                'month' => $period->month,
                'month_name' => $period->month_name,
                'year' => $period->year,
                'net_salary' => $net,
                'formatted_net_salary' => 'Rp '.number_format($net, 0, ',', '.'),
                'basic_salary' => $basic,
                'formatted_basic_salary' => 'Rp '.number_format($basic, 0, ',', '.'),
                'allowances' => $allowances,
                'formatted_allowances' => 'Rp '.number_format($allowances, 0, ',', '.'),
                'deductions' => $deductions,
                'formatted_deductions' => 'Rp '.number_format($deductions, 0, ',', '.'),
                'employee_count' => $empCount,
                'average_per_employee' => $avgPerEmp,
                'formatted_average_per_employee' => 'Rp '.number_format($avgPerEmp, 0, ',', '.'),
                'status' => $period->status,
                'status_label' => $period->status_label,
                'status_badge_class' => $period->status_badge_class,
            ];
        }

        $averageMonthlyExpense = count($yearlyMonthlyData) > 0
            ? $totalYearlyExpense / count($yearlyMonthlyData)
            : 0;

        $averageCostPerEmployee = $totalYearlyHeadcount > 0
            ? $totalYearlyExpense / $totalYearlyHeadcount
            : 0;

        $totalYearlyGross = $totalYearlyBasic + $totalYearlyAllowances;
        $basicPercentage = $totalYearlyGross > 0 ? round(($totalYearlyBasic / $totalYearlyGross) * 100, 1) : 0;
        $allowancesPercentage = $totalYearlyGross > 0 ? round(($totalYearlyAllowances / $totalYearlyGross) * 100, 1) : 0;

        $compactYearlyTotal = $totalYearlyExpense >= 1000000000
            ? 'Rp '.number_format($totalYearlyExpense / 1000000000, 1, ',', '.').' M'
            : ($totalYearlyExpense >= 1000000
                ? 'Rp '.number_format($totalYearlyExpense / 1000000, 1, ',', '.').' jt'
                : 'Rp '.number_format($totalYearlyExpense, 0, ',', '.'));

        // 1. Total Biaya Perusahaan Periode Aktif
        $activeCompanyCost = $activeNetSalary + $activeTotalBpjs + $activePph21;
        $activeEmpCount = $activePeriod?->employee_count ?? 0;
        $activeAvgCostPerPerson = $activeEmpCount > 0 ? round($activeCompanyCost / $activeEmpCount) : 0;

        $compactCompanyCost = $activeCompanyCost >= 1000000000
            ? 'Rp '.number_format($activeCompanyCost / 1000000000, 1, ',', '.').' M'
            : ($activeCompanyCost >= 1000000
                ? 'Rp '.number_format($activeCompanyCost / 1000000, 1, ',', '.').' jt'
                : 'Rp '.number_format($activeCompanyCost, 0, ',', '.'));

        // 2. Tren vs Bulan Lalu
        $trendText = 'Stabil vs periode lalu';
        $trendIsUp = true;
        if ($activePeriod) {
            $prevPeriod = PayrollPeriod::where(function ($q) use ($activePeriod) {
                $q->where('year', '<', $activePeriod->year)
                    ->orWhere(function ($sub) use ($activePeriod) {
                        $sub->where('year', $activePeriod->year)
                            ->where('month', '<', $activePeriod->month);
                    });
            })->orderByDesc('year')->orderByDesc('month')->first();

            if ($prevPeriod) {
                $prevItemIds = $prevPeriod->items()->pluck('id');
                $prevNet = (float) $prevPeriod->total_net_salary;
                $prevBpjs = (float) $prevPeriod->total_company_contributions + (float) PayrollItemLine::whereIn('payroll_item_id', $prevItemIds)->where('category', PayrollLineCategory::BPJS)->sum('amount');
                $prevPph = (float) $prevPeriod->items()->sum('pph21_amount');
                $prevCost = $prevNet + $prevBpjs + $prevPph;

                if ($prevCost > 0) {
                    $diffPct = round((($activeCompanyCost - $prevCost) / $prevCost) * 100, 1);
                    $trendIsUp = $diffPct >= 0;
                    $trendText = ($diffPct >= 0 ? '↗ +'.$diffPct.'%' : '↘ '.$diffPct.'%').' vs '.$prevPeriod->month_name;
                }
            }
        }

        // 3. Uang Gaji Dipakai Untuk Apa (% Alokasi)
        $allocBase = $activeCompanyCost > 0 ? $activeCompanyCost : 1;
        $thpPct = round(($activeNetSalary / $allocBase) * 100);
        $bpjsPct = round(($activeTotalBpjs / $allocBase) * 100);
        $pphPct = max(0, 100 - $thpPct - $bpjsPct);

        // 4. Biaya per Departemen
        $departmentBreakdown = [];
        if ($activePeriod) {
            $periodItems = $activePeriod->items()->with('employee.department')->get();
            if ($periodItems->isNotEmpty()) {
                $deptGrouped = $periodItems->groupBy(fn ($item) => $item->employee?->department?->name ?? 'Umum');
                $maxDeptCost = 0;
                foreach ($deptGrouped as $dName => $dItems) {
                    $dItemIds = $dItems->pluck('id');
                    $dNet = (float) $dItems->sum('net_salary');
                    $dPph = (float) $dItems->sum('pph21_amount');
                    $dBpjs = (float) $dItems->sum('total_company_contributions') + (float) PayrollItemLine::whereIn('payroll_item_id', $dItemIds)->where('category', PayrollLineCategory::BPJS)->sum('amount');
                    $dCost = $dNet + $dPph + $dBpjs;
                    if ($dCost > $maxDeptCost) {
                        $maxDeptCost = $dCost;
                    }
                    $departmentBreakdown[] = [
                        'name' => $dName,
                        'count' => $dItems->count(),
                        'total_cost' => $dCost,
                        'formatted_cost' => 'Rp '.number_format($dCost, 0, ',', '.'),
                        'compact_cost' => $dCost >= 1000000000 ? 'Rp '.number_format($dCost / 1000000000, 1, ',', '.').' M' : ($dCost >= 1000000 ? 'Rp '.number_format($dCost / 1000000, 1, ',', '.').' jt' : 'Rp '.number_format($dCost, 0, ',', '.')),
                    ];
                }
                usort($departmentBreakdown, fn ($a, $b) => $b['total_cost'] <=> $a['total_cost']);
                foreach ($departmentBreakdown as &$dept) {
                    $dept['bar_pct'] = $maxDeptCost > 0 ? max(15, round(($dept['total_cost'] / $maxDeptCost) * 100)) : 15;
                }
            }
        }

        // 5. Tren 6 Bulan (Total Biaya)
        $monthNamesShort = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
        $actMonth = $activePeriod?->month ?? $currentMonth;
        $actYear = $activePeriod?->year ?? $currentYear;
        $sixMonthsData = [];
        $maxSixMonthCost = 0;

        for ($i = 5; $i >= 0; $i--) {
            $tDate = Carbon::createFromDate($actYear, $actMonth, 1)->subMonths($i);
            $tMonth = (int) $tDate->month;
            $tYear = (int) $tDate->year;
            $p = PayrollPeriod::where('year', $tYear)->where('month', $tMonth)->first();
            $pCost = 0;
            if ($p) {
                $pItemIds = $p->items()->pluck('id');
                $pNet = (float) $p->total_net_salary;
                $pBpjs = (float) $p->total_company_contributions + (float) PayrollItemLine::whereIn('payroll_item_id', $pItemIds)->where('category', PayrollLineCategory::BPJS)->sum('amount');
                $pPph = (float) $p->items()->sum('pph21_amount');
                $pCost = $pNet + $pBpjs + $pPph;
            }
            if ($pCost > $maxSixMonthCost) {
                $maxSixMonthCost = $pCost;
            }
            $sixMonthsData[] = [
                'month' => $tMonth,
                'month_short' => $monthNamesShort[$tMonth] ?? (string) $tMonth,
                'year' => $tYear,
                'cost' => $pCost,
                'is_active' => ($tMonth === $actMonth && $tYear === $actYear),
            ];
        }

        foreach ($sixMonthsData as &$sm) {
            $sm['height_pct'] = $maxSixMonthCost > 0 && $sm['cost'] > 0
                ? max(25, round(($sm['cost'] / $maxSixMonthCost) * 100))
                : 20;
        }

        // 6. Perlu Perhatian (Executive Alerts)
        $missingBankCount = $activePeriod
            ? $activePeriod->items()->where(function ($q) {
                $q->whereNull('bank_account_number')->orWhere('bank_account_number', '');
            })->count()
            : 0;

        $unpaidCashAdvances = CashAdvance::whereIn('status', [CashAdvanceStatus::APPROVED, CashAdvanceStatus::ACTIVE])
            ->where('remaining_amount', '>', 0)
            ->get();
        $remainingKasbon = (float) $unpaidCashAdvances->sum('remaining_amount');
        $kasbonPeopleCount = $unpaidCashAdvances->pluck('employee_id')->unique()->count();

        // 7. Rekap Tahunan untuk 4 Kartu Statistik Atas & Card Rekap Payroll
        $fullMonthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $yearlyDistinctPeriods = $yearlyPeriods->groupBy('month')->map(fn ($group) => $group->sortByDesc('id')->first());
        $totalYearlyCompanyCost = 0;
        $totalYearlyThp = 0;
        $totalYearlyBpjs = 0;
        $totalYearlyPph = 0;
        $totalYearlySetoran = 0;

        foreach ($yearlyDistinctPeriods as $p) {
            $pNet = (float) $p->total_net_salary;
            $pItemIds = $p->items()->pluck('id');
            $pBpjs = (float) $p->total_company_contributions + (float) PayrollItemLine::whereIn('payroll_item_id', $pItemIds)->where('category', PayrollLineCategory::BPJS)->sum('amount');
            $pPph = (float) $p->items()->sum('pph21_amount');

            $totalYearlyThp += $pNet;
            $totalYearlyBpjs += $pBpjs;
            $totalYearlyPph += $pPph;
            $totalYearlySetoran += ($pBpjs + $pPph);
            $totalYearlyCompanyCost += ($pNet + $pBpjs + $pPph);
        }

        $formatCompact = function (float $amount, int $decimals = 1): string {
            if ($amount >= 1000000000) {
                return 'Rp '.number_format($amount / 1000000000, $decimals, ',', '.').' M';
            }
            if ($amount >= 1000000) {
                return 'Rp '.number_format($amount / 1000000, $decimals, ',', '.').' jt';
            }
            if ($amount >= 1000) {
                return 'Rp '.number_format($amount / 1000, 0, ',', '.').' rb';
            }

            return 'Rp '.number_format($amount, 0, ',', '.');
        };

        $compactYearlyCompanyCost = $formatCompact($totalYearlyCompanyCost, 1);
        $compactYearlyThp = $formatCompact($totalYearlyThp, 2);
        $compactYearlySetoran = $formatCompact($totalYearlySetoran, 1);
        $compactRemainingKasbon = $formatCompact($remainingKasbon, 1);

        $latestMonthInYear = $yearlyDistinctPeriods->keys()->max() ?? $currentMonth;
        $latestMonthNameShort = $monthNamesShort[$latestMonthInYear] ?? 'Okt';
        $yearlyCompanyCostSubtitle = "Jan sampai {$latestMonthNameShort} {$currentYear} (bruto + BPJS perusahaan)";

        $paidCount = $yearlyDistinctPeriods->filter(fn ($p) => $p->status === PayrollPeriodStatus::PAID)->count();
        $processedCount = $yearlyDistinctPeriods->count();
        $periodsCountForThp = $paidCount > 0 ? $paidCount : max(1, $processedCount);
        $avgMonthlyThp = $periodsCountForThp > 0 ? ($totalYearlyThp / $periodsCountForThp) : 0;
        $periodLabelText = $paidCount > 0 ? "{$paidCount} periode dibayar" : "{$processedCount} periode dibayar";
        $yearlyThpSubtitle = $periodLabelText.', rata-rata '.$formatCompact($avgMonthlyThp, 2).' per bulan';

        $yearlySetoranSubtitle = 'PPh 21 dan BPJS tahun ini';
        $remainingKasbonSubtitle = "Belum lunas, {$kasbonPeopleCount} orang";

        // Metrics spesifik untuk Card Rekap Payroll
        $latestMonthNameFull = $fullMonthNames[$latestMonthInYear] ?? 'Oktober';
        $rekapPayrollSubtitle = "Januari sampai {$latestMonthNameFull} · {$processedCount} periode";

        $yearlyAvgCostPerMonth = $processedCount > 0 ? round($totalYearlyCompanyCost / $processedCount) : 0;
        $formattedYearlyAvgCostPerMonth = 'Rp '.number_format($yearlyAvgCostPerMonth, 0, ',', '.');

        $totalHeadcount = $yearlyDistinctPeriods->sum('employee_count');
        $yearlyAvgCostPerPerson = $totalHeadcount > 0 ? round($totalYearlyCompanyCost / $totalHeadcount) : 0;
        $formattedYearlyAvgCostPerPerson = 'Rp '.number_format($yearlyAvgCostPerPerson, 0, ',', '.');

        $yearlyAllocBase = $totalYearlyCompanyCost > 0 ? $totalYearlyCompanyCost : 1;
        $yearlyThpPct = round(($totalYearlyThp / $yearlyAllocBase) * 100);
        $yearlyBpjsPct = round(($totalYearlyBpjs / $yearlyAllocBase) * 100);
        $yearlyPphPct = max(0, 100 - $yearlyThpPct - $yearlyBpjsPct);

        // Tren 12 Bulan Penuh (J F M A M J J A S O N D)
        $twelveMonthsInitials = [
            1 => 'J', 2 => 'F', 3 => 'M', 4 => 'A', 5 => 'M', 6 => 'J',
            7 => 'J', 8 => 'A', 9 => 'S', 10 => 'O', 11 => 'N', 12 => 'D',
        ];
        $twelveMonthsData = [];
        $maxTwelveMonthCost = 0;
        foreach (range(1, 12) as $m) {
            $p = $yearlyDistinctPeriods->get($m);
            $mCost = 0;
            if ($p) {
                $pItemIds = $p->items()->pluck('id');
                $pNet = (float) $p->total_net_salary;
                $pBpjs = (float) $p->total_company_contributions + (float) PayrollItemLine::whereIn('payroll_item_id', $pItemIds)->where('category', PayrollLineCategory::BPJS)->sum('amount');
                $pPph = (float) $p->items()->sum('pph21_amount');
                $mCost = $pNet + $pBpjs + $pPph;
            }
            if ($mCost > $maxTwelveMonthCost) {
                $maxTwelveMonthCost = $mCost;
            }
            $twelveMonthsData[$m] = [
                'month' => $m,
                'initial' => $twelveMonthsInitials[$m],
                'month_name' => $fullMonthNames[$m],
                'cost' => $mCost,
                'formatted_cost' => 'Rp '.number_format($mCost, 0, ',', '.'),
                'has_data' => $mCost > 0,
                'is_active' => ($m === $latestMonthInYear),
            ];
        }
        foreach ($twelveMonthsData as &$tm) {
            $tm['height_pct'] = $maxTwelveMonthCost > 0 && $tm['cost'] > 0
                ? max(20, round(($tm['cost'] / $maxTwelveMonthCost) * 100))
                : 0;
        }

        return [
            'total_paid_this_year' => $totalPaidThisYear,
            'formatted_total_paid_this_year' => 'Rp '.number_format($totalPaidThisYear, 0, ',', '.'),
            'pending_approval_count' => $pendingApprovalCount,
            'draft_count' => $draftCount,
            'total_active_employees' => $totalActiveEmployees,
            'current_period_status' => $activePeriod?->status->label() ?? 'Belum Dibuat',
            'current_period_badge' => $activePeriod?->status_badge_class ?? 'badge badge-secondary',

            // Top 4 Annual Executive Stat Cards
            'yearly_total_company_cost' => $totalYearlyCompanyCost,
            'formatted_yearly_total_company_cost' => 'Rp '.number_format($totalYearlyCompanyCost, 0, ',', '.'),
            'compact_yearly_company_cost' => $compactYearlyCompanyCost,
            'yearly_company_cost_subtitle' => $yearlyCompanyCostSubtitle,

            'yearly_total_thp' => $totalYearlyThp,
            'formatted_yearly_total_thp' => 'Rp '.number_format($totalYearlyThp, 0, ',', '.'),
            'compact_yearly_thp' => $compactYearlyThp,
            'yearly_thp_subtitle' => $yearlyThpSubtitle,

            'yearly_total_setoran' => $totalYearlySetoran,
            'formatted_yearly_total_setoran' => 'Rp '.number_format($totalYearlySetoran, 0, ',', '.'),
            'compact_yearly_setoran' => $compactYearlySetoran,
            'yearly_setoran_subtitle' => $yearlySetoranSubtitle,

            'compact_remaining_kasbon' => $compactRemainingKasbon,
            'remaining_kasbon_subtitle' => $remainingKasbonSubtitle,

            // Card Rekap Payroll Spesifik
            'rekap_payroll_subtitle' => $rekapPayrollSubtitle,
            'yearly_avg_cost_per_month' => $yearlyAvgCostPerMonth,
            'formatted_yearly_avg_cost_per_month' => $formattedYearlyAvgCostPerMonth,
            'yearly_avg_cost_per_person' => $yearlyAvgCostPerPerson,
            'formatted_yearly_avg_cost_per_person' => $formattedYearlyAvgCostPerPerson,
            'yearly_thp_pct' => $yearlyThpPct,
            'yearly_bpjs_pct' => $yearlyBpjsPct,
            'yearly_pph_pct' => $yearlyPphPct,
            'yearly_total_bpjs' => $totalYearlyBpjs,
            'formatted_yearly_total_bpjs' => 'Rp '.number_format($totalYearlyBpjs, 0, ',', '.'),
            'yearly_total_pph' => $totalYearlyPph,
            'formatted_yearly_total_pph' => 'Rp '.number_format($totalYearlyPph, 0, ',', '.'),
            'twelve_months_data' => $twelveMonthsData,

            // HRD Operational Metrics
            'active_period' => $activePeriod,
            'active_net_salary' => $activeNetSalary,
            'formatted_active_net_salary' => 'Rp '.number_format($activeNetSalary, 0, ',', '.'),
            'active_pph21' => $activePph21,
            'formatted_active_pph21' => 'Rp '.number_format($activePph21, 0, ',', '.'),
            'active_kasbon' => $activeKasbon,
            'formatted_active_kasbon' => 'Rp '.number_format($activeKasbon, 0, ',', '.'),
            'active_kasbon_count' => $activeKasbonCount,
            'active_total_bpjs' => $activeTotalBpjs,
            'formatted_active_total_bpjs' => 'Rp '.number_format($activeTotalBpjs, 0, ',', '.'),

            // Executive Summary Card Metrics (Mockup Alignment)
            'active_company_cost' => $activeCompanyCost,
            'formatted_active_company_cost' => 'Rp '.number_format($activeCompanyCost, 0, ',', '.'),
            'compact_total_company_cost' => $compactCompanyCost,
            'active_avg_cost_per_person' => $activeAvgCostPerPerson,
            'formatted_average_cost_per_person' => 'Rp '.number_format($activeAvgCostPerPerson, 0, ',', '.'),
            'trend_text' => $trendText,
            'trend_is_up' => $trendIsUp,
            'thp_percentage' => $thpPct,
            'bpjs_percentage' => $bpjsPct,
            'pph_percentage' => $pphPct,
            'department_breakdown' => $departmentBreakdown,
            'six_months_data' => $sixMonthsData,
            'missing_bank_count' => $missingBankCount,
            'remaining_kasbon' => $remainingKasbon,
            'formatted_remaining_kasbon' => 'Rp '.number_format($remainingKasbon, 0, ',', '.'),
            'kasbon_people_count' => $kasbonPeopleCount,

            // Yearly Monthly Metrics
            'current_year' => $currentYear,
            'yearly_monthly_data' => $yearlyMonthlyData,
            'total_yearly_expense' => $totalYearlyExpense,
            'formatted_total_yearly_expense' => 'Rp '.number_format($totalYearlyExpense, 0, ',', '.'),
            'compact_total_yearly_expense' => $compactYearlyTotal,
            'average_monthly_expense' => $averageMonthlyExpense,
            'formatted_average_monthly_expense' => 'Rp '.number_format($averageMonthlyExpense, 0, ',', '.'),
            'yearly_period_count' => count($yearlyMonthlyData),

            // HR Executive Annual Metrics
            'total_yearly_basic' => $totalYearlyBasic,
            'formatted_total_yearly_basic' => 'Rp '.number_format($totalYearlyBasic, 0, ',', '.'),
            'total_yearly_allowances' => $totalYearlyAllowances,
            'formatted_total_yearly_allowances' => 'Rp '.number_format($totalYearlyAllowances, 0, ',', '.'),
            'total_yearly_deductions' => $totalYearlyDeductions,
            'formatted_total_yearly_deductions' => 'Rp '.number_format($totalYearlyDeductions, 0, ',', '.'),
            'total_yearly_company_contributions' => $totalYearlyCompanyContributions,
            'formatted_total_yearly_company_contributions' => 'Rp '.number_format($totalYearlyCompanyContributions, 0, ',', '.'),
            'average_cost_per_employee' => $averageCostPerEmployee,
            'formatted_average_cost_per_employee' => 'Rp '.number_format($averageCostPerEmployee, 0, ',', '.'),
            'basic_percentage' => $basicPercentage,
            'allowances_percentage' => $allowancesPercentage,
        ];
    }

    /**
     * Generate unique reference number for payroll period (e.g. PR-202610).
     */
    public function generatePeriodReferenceNumber(Carbon $date): string
    {
        $baseRef = 'PR-'.$date->format('Ym');

        $exists = PayrollPeriod::query()
            ->where('reference_number', $baseRef)
            ->exists();

        if (! $exists) {
            return $baseRef;
        }

        $count = PayrollPeriod::query()
            ->where('reference_number', 'like', "{$baseRef}-%")
            ->count();

        return $baseRef.'-'.($count + 2);
    }

    /**
     * Generate unique payslip number (e.g. SLP-202610-0001).
     */
    public function generatePayslipNumber(Carbon $date): string
    {
        $sequenceKey = 'SLP-'.$date->format('Ym');

        return $this->reserveSequenceNumber($sequenceKey, 'SLP-'.$date->format('Ym').'-');
    }

    /**
     * Reserve sequence number atomically to prevent race conditions.
     */
    private function reserveSequenceNumber(string $sequenceKey, string $prefix): string
    {
        $seq = PayrollSequence::query()
            ->lockForUpdate()
            ->firstOrCreate(
                ['sequence_key' => $sequenceKey],
                ['current_number' => 0]
            );

        $seq->increment('current_number');

        return $prefix.str_pad((string) $seq->current_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new payroll period and draft items for all active employees.
     */
    public function createPeriod(array $data, User $user): PayrollPeriod
    {
        Gate::forUser($user)->authorize('create', PayrollPeriod::class);

        $month = (int) $data['month'];
        $year = (int) $data['year'];

        // Validate month and year
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Bulan harus berada di antara 1 dan 12.');
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$month] ?? (string) $month;

        $exists = PayrollPeriod::query()
            ->where('month', $month)
            ->where('year', $year)
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException("Periode penggajian untuk bulan {$monthName} {$year} sudah ada.");
        }

        $date = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        $name = $data['name'] ?? "Penggajian Periode {$monthName} {$year}";

        return DB::transaction(function () use ($month, $year, $startDate, $endDate, $name, $data, $date, $user) {
            $refNumber = $this->generatePeriodReferenceNumber($date);

            $period = PayrollPeriod::create([
                'reference_number' => $refNumber,
                'name' => $name,
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => PayrollPeriodStatus::DRAFT,
                'created_by' => $user->id,
                'notes' => ! empty($data['notes']) ? trim((string) $data['notes']) : null,
            ]);

            // Pull active employees with details
            $activeEmployees = Employee::query()
                ->where('status', EmployeeStatus::ACTIVE)
                ->with(['detail', 'cashAdvances.installments'])
                ->orderBy('full_name')
                ->get();

            $totalBasic = 0;
            $totalAllowances = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            foreach ($activeEmployees as $employee) {
                $payslipNumber = $this->generatePayslipNumber($date);
                $salaryInfo = $this->calculateProratedSalary($employee, $period);
                $basicSalary = (float) $salaryInfo['basic_salary'];
                $prorateNote = $salaryInfo['is_prorated']
                    ? 'Gaji prorata Rp '.number_format($salaryInfo['basic_salary'], 0, ',', '.').' dari Rp '.number_format($salaryInfo['full_salary'], 0, ',', '.')." ({$salaryInfo['worked_days']} dari {$salaryInfo['total_working_days']} hari kerja • masuk ".$employee->join_date?->translatedFormat('d M Y').')'
                    : null;

                $item = PayrollItem::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'payslip_number' => $payslipNumber,
                    'basic_salary' => $basicSalary,
                    'total_allowances' => 0,
                    'total_deductions' => 0,
                    'net_salary' => $basicSalary,
                    'bank_name' => $employee->detail?->bank_name,
                    'bank_account_number' => $employee->detail?->bank_account_number,
                    'bank_account_name' => $employee->detail?->bank_account_name,
                    'payment_status' => 'unpaid',
                    'notes' => $prorateNote,
                ]);

                // Check pending cash advance installments due in or before this month
                $itemAllowances = 0;
                $itemDeductions = 0;

                // Active cash advances for this employee
                $activeAdvances = $employee->cashAdvances()
                    ->whereIn('status', ['active', 'approved'])
                    ->with(['installments' => function ($q) use ($endDate) {
                        $q->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                            ->where('due_date', '<=', $endDate->toDateString())
                            ->orderBy('installment_number');
                    }])
                    ->get();

                foreach ($activeAdvances as $advance) {
                    $installment = $advance->installments->first();
                    if ($installment) {
                        $deductionAmount = (float) ($installment->amount - $installment->paid_amount);
                        if ($deductionAmount > 0) {
                            PayrollItemLine::create([
                                'payroll_item_id' => $item->id,
                                'cash_advance_installment_id' => $installment->id,
                                'name' => "Potongan Kasbon ({$advance->reference_number} Cicilan #{$installment->installment_number})",
                                'type' => PayrollLineType::DEDUCTION,
                                'category' => PayrollLineCategory::CASH_ADVANCE,
                                'amount' => $deductionAmount,
                                'notes' => 'Potongan otomatis dari kasbon aktif',
                            ]);
                            $itemDeductions += $deductionAmount;
                        }
                    }
                }

                $company = CompanyProfile::getProfile();
                if ($employee->detail && $company->enable_pph21) {
                    $this->recalculateItemTax($item, $company);
                    $this->recalculateItem($item);
                    $item->refresh();
                    $itemAllowances = (float) $item->total_allowances;
                    $itemDeductions = (float) $item->total_deductions;
                    $netSalary = (float) $item->net_salary;
                } else {
                    $netSalary = max(0, $basicSalary + $itemAllowances - $itemDeductions);
                    $item->update([
                        'total_allowances' => $itemAllowances,
                        'total_deductions' => $itemDeductions,
                        'net_salary' => $netSalary,
                    ]);
                }

                $totalBasic += $basicSalary;
                $totalAllowances += $itemAllowances;
                $totalDeductions += $itemDeductions;
                $totalNet += $netSalary;
            }

            $period->update([
                'total_basic_salary' => $totalBasic,
                'total_allowances' => $totalAllowances,
                'total_deductions' => $totalDeductions,
                'total_net_salary' => $totalNet,
                'employee_count' => $activeEmployees->count(),
            ]);

            return $period;
        });
    }

    /**
     * Calculate prorated basic salary for an employee in a given period (6-day work week: Monday - Saturday).
     *
     * @return array{basic_salary: float, full_salary: float, is_prorated: bool, worked_days: int, total_working_days: int}
     */
    public function calculateProratedSalary(Employee $employee, PayrollPeriod $period): array
    {
        $fullSalary = (float) ($employee->basic_salary ?? 0);
        $startDate = Carbon::parse($period->start_date)->startOfDay();
        $endDate = Carbon::parse($period->end_date)->endOfDay();

        // Total working days in period (Monday - Saturday, exclude Sunday)
        $totalWorkingDays = 0;
        $curr = $startDate->copy();
        while ($curr->lte($endDate)) {
            if (! $curr->isSunday()) {
                $totalWorkingDays++;
            }
            $curr->addDay();
        }

        $joinDate = $employee->join_date ? Carbon::parse($employee->join_date)->startOfDay() : null;

        // Prorata only applies if employee joined during this period (startDate < joinDate <= endDate)
        if (! $joinDate || $joinDate->lte($startDate) || $joinDate->gt($endDate)) {
            return [
                'basic_salary' => $fullSalary,
                'full_salary' => $fullSalary,
                'is_prorated' => false,
                'worked_days' => $totalWorkingDays,
                'total_working_days' => $totalWorkingDays,
            ];
        }

        // Joined during the period: count Monday - Saturday from join_date to endDate
        $workedDays = 0;
        $curr = $joinDate->copy();
        while ($curr->lte($endDate)) {
            if (! $curr->isSunday()) {
                $workedDays++;
            }
            $curr->addDay();
        }

        $ratio = $totalWorkingDays > 0 ? ($workedDays / $totalWorkingDays) : 1;
        $proratedSalary = round($fullSalary * $ratio);

        return [
            'basic_salary' => (float) $proratedSalary,
            'full_salary' => $fullSalary,
            'is_prorated' => true,
            'worked_days' => $workedDays,
            'total_working_days' => $totalWorkingDays,
        ];
    }

    /**
     * Add an employee to an existing draft/revised payroll period with prorated calculation.
     */
    public function addEmployeeToPeriod(PayrollPeriod $period, Employee $employee, User $user): PayrollItem
    {
        Gate::forUser($user)->authorize('update', $period);

        if (! $period->status->isEditable()) {
            throw new InvalidArgumentException('Karyawan hanya dapat ditambahkan pada periode berstatus Draf atau Revisi.');
        }

        $existing = $period->items()->where('employee_id', $employee->id)->first();
        if ($existing) {
            return $existing;
        }

        $employee->loadMissing(['detail', 'position', 'department']);

        return DB::transaction(function () use ($period, $employee) {
            $date = Carbon::createFromDate($period->year, $period->month, 1)->startOfMonth();
            $payslipNumber = $this->generatePayslipNumber($date);

            $salaryInfo = $this->calculateProratedSalary($employee, $period);
            $basicSalary = $salaryInfo['basic_salary'];

            $prorateNote = $salaryInfo['is_prorated']
                ? 'Gaji prorata Rp '.number_format($salaryInfo['basic_salary'], 0, ',', '.').' dari Rp '.number_format($salaryInfo['full_salary'], 0, ',', '.')." ({$salaryInfo['worked_days']} dari {$salaryInfo['total_working_days']} hari kerja • masuk ".$employee->join_date?->translatedFormat('d M Y').')'
                : null;

            $item = PayrollItem::create([
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'payslip_number' => $payslipNumber,
                'basic_salary' => $basicSalary,
                'total_allowances' => 0,
                'total_deductions' => 0,
                'net_salary' => $basicSalary,
                'bank_name' => $employee->detail?->bank_name,
                'bank_account_number' => $employee->detail?->bank_account_number,
                'bank_account_name' => $employee->detail?->bank_account_name,
                'payment_status' => 'unpaid',
                'notes' => $prorateNote,
            ]);

            // Check pending cash advance installments due in or before this month
            $endDate = Carbon::parse($period->end_date)->endOfDay();
            $activeAdvances = $employee->cashAdvances()
                ->whereIn('status', ['active', 'approved'])
                ->with(['installments' => function ($q) use ($endDate) {
                    $q->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                        ->where('due_date', '<=', $endDate->toDateString())
                        ->orderBy('installment_number');
                }])
                ->get();

            foreach ($activeAdvances as $advance) {
                $installment = $advance->installments->first();
                if ($installment) {
                    $deductionAmount = (float) ($installment->amount - $installment->paid_amount);
                    if ($deductionAmount > 0) {
                        PayrollItemLine::create([
                            'payroll_item_id' => $item->id,
                            'cash_advance_installment_id' => $installment->id,
                            'name' => "Potongan Kasbon ({$advance->reference_number} Cicilan #{$installment->installment_number})",
                            'type' => PayrollLineType::DEDUCTION,
                            'category' => PayrollLineCategory::CASH_ADVANCE,
                            'amount' => $deductionAmount,
                            'notes' => 'Potongan otomatis dari kasbon aktif',
                        ]);
                    }
                }
            }

            $company = CompanyProfile::getProfile();
            if ($employee->detail && $company->enable_pph21) {
                $this->recalculateItemTax($item, $company);
                $this->recalculateItem($item);
                $item->refresh();
            } else {
                $this->recalculateItem($item);
                $item->refresh();
            }

            $this->recalculatePeriod($period);

            return $item;
        });
    }

    /**
     * Synchronize all missing active employees into an editable period.
     *
     * @return Collection<int, PayrollItem>
     */
    public function syncMissingEmployees(PayrollPeriod $period, User $user): Collection
    {
        Gate::forUser($user)->authorize('update', $period);

        if (! $period->status->isEditable()) {
            throw new InvalidArgumentException('Hanya periode berstatus Draf atau Revisi yang dapat disinkronkan.');
        }

        $existingIds = $period->items()->pluck('employee_id')->all();

        $missingEmployees = Employee::query()
            ->with(['detail', 'position', 'department'])
            ->where('status', EmployeeStatus::ACTIVE)
            ->whereNotIn('id', $existingIds)
            ->where(function ($q) use ($period) {
                $q->whereNull('join_date')
                    ->orWhere('join_date', '<=', $period->end_date);
            })
            ->orderBy('full_name')
            ->get();

        $createdItems = collect();
        foreach ($missingEmployees as $employee) {
            $createdItems->push($this->addEmployeeToPeriod($period, $employee, $user));
        }

        return $createdItems;
    }

    /**
     * Synchronize all existing employees' basic salaries and bank details in an editable period with master data.
     */
    public function syncPeriodWithMasterEmployees(PayrollPeriod $period, User $user): int
    {
        Gate::forUser($user)->authorize('update', $period);

        if (! $period->status->isEditable()) {
            throw new InvalidArgumentException('Hanya periode berstatus Draf atau Ditolak yang dapat disinkronkan.');
        }

        $company = CompanyProfile::getProfile();
        $items = $period->items()->with(['employee.detail', 'lines'])->get();
        $updatedCount = 0;

        return DB::transaction(function () use ($period, $items, $company, &$updatedCount) {
            foreach ($items as $item) {
                $employee = $item->employee;
                if (! $employee) {
                    continue;
                }

                $salaryInfo = $this->calculateProratedSalary($employee, $period);
                $newBasic = (float) $salaryInfo['basic_salary'];

                $item->update([
                    'basic_salary' => $newBasic,
                    'notes' => $salaryInfo['is_prorated']
                        ? 'Gaji prorata Rp '.number_format($salaryInfo['basic_salary'], 0, ',', '.').' dari Rp '.number_format($salaryInfo['full_salary'], 0, ',', '.')." ({$salaryInfo['worked_days']} dari {$salaryInfo['total_working_days']} hari kerja • masuk ".$employee->join_date?->translatedFormat('d M Y').')'
                        : null,
                    'bank_name' => $employee->detail?->bank_name,
                    'bank_account_number' => $employee->detail?->bank_account_number,
                    'bank_account_name' => $employee->detail?->bank_account_name,
                ]);

                $this->recalculateItemTax($item, $company);
                $this->recalculateItem($item);
                $updatedCount++;
            }

            $this->recalculatePeriod($period);

            return $updatedCount;
        });
    }

    /**
     * Synchronize a single item's basic salary and bank details with master employee data.
     */
    public function syncItemWithMasterEmployee(PayrollItem $item, User $user): PayrollItem
    {
        $period = $item->payrollPeriod;
        Gate::forUser($user)->authorize('update', $period);

        if (! $period->status->isEditable() && ! $item->isRejected()) {
            throw new InvalidArgumentException('Hanya draf gaji atau revisi yang dapat diubah.');
        }

        $employee = $item->employee;
        if (! $employee) {
            return $item;
        }

        return DB::transaction(function () use ($item, $period, $employee) {
            $company = CompanyProfile::getProfile();
            $salaryInfo = $this->calculateProratedSalary($employee, $period);
            $newBasic = (float) $salaryInfo['basic_salary'];

            $item->update([
                'basic_salary' => $newBasic,
                'notes' => $salaryInfo['is_prorated']
                    ? 'Gaji prorata Rp '.number_format($salaryInfo['basic_salary'], 0, ',', '.').' dari Rp '.number_format($salaryInfo['full_salary'], 0, ',', '.')." ({$salaryInfo['worked_days']} dari {$salaryInfo['total_working_days']} hari kerja • masuk ".$employee->join_date?->translatedFormat('d M Y').')'
                    : null,
                'bank_name' => $employee->detail?->bank_name,
                'bank_account_number' => $employee->detail?->bank_account_number,
                'bank_account_name' => $employee->detail?->bank_account_name,
            ]);

            $this->recalculateItemTax($item, $company);
            $this->recalculateItem($item);
            $this->recalculatePeriod($period);

            return $item->fresh();
        });
    }

    /**
     * Synchronize all allowance and deduction lines for an item at once.
     */
    public function syncItemLines(PayrollItem $item, array $allowanceRows, array $deductionRows, User $user): PayrollItem
    {
        Gate::forUser($user)->authorize('update', $item->payrollPeriod);

        if (! $item->payrollPeriod->status->isEditable() && ! $item->isRejected()) {
            throw new InvalidArgumentException('Hanya draf gaji atau revisi yang dapat diubah.');
        }

        return DB::transaction(function () use ($item, $allowanceRows, $deductionRows, $user) {
            $existingLines = $item->lines()->get()->keyBy('id');
            $retainedIds = [];

            // 1. Process Allowances
            foreach ($allowanceRows as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $amount = round((float) ($row['amount'] ?? 0), 2);

                if ($name === '' && $amount <= 0) {
                    continue;
                }

                $rowId = ! empty($row['id']) ? (int) $row['id'] : null;

                // Skip tax allowance line here; it will be synced by recalculateItemTax
                if (($row['category'] ?? null) === PayrollLineCategory::TAX->value
                    || ($rowId && $existingLines->get($rowId)?->category === PayrollLineCategory::TAX)
                    || $name === 'Tunjangan PPh 21 (Gross-Up)') {
                    if ($rowId) {
                        $retainedIds[] = $rowId;
                    }

                    continue;
                }

                if ($name === '') {
                    throw new InvalidArgumentException('Nama tunjangan tidak boleh kosong jika nominal diisi.');
                }

                if ($amount < 0) {
                    throw new InvalidArgumentException('Nominal tunjangan tidak boleh negatif.');
                }

                if ($rowId && $existingLines->has($rowId)) {
                    $line = $existingLines->get($rowId);
                    $line->update([
                        'name' => $name,
                        'amount' => $amount,
                        'type' => PayrollLineType::ALLOWANCE,
                        'category' => PayrollLineCategory::ALLOWANCE,
                        'notes' => ! empty($row['notes']) ? trim((string) $row['notes']) : null,
                    ]);
                    $retainedIds[] = $rowId;
                } else {
                    $newLine = PayrollItemLine::create([
                        'payroll_item_id' => $item->id,
                        'salary_component_id' => $row['salary_component_id'] ?? null,
                        'name' => $name,
                        'type' => PayrollLineType::ALLOWANCE,
                        'category' => PayrollLineCategory::ALLOWANCE,
                        'amount' => $amount,
                        'notes' => ! empty($row['notes']) ? trim((string) $row['notes']) : null,
                    ]);
                    $retainedIds[] = $newLine->id;
                }
            }

            // 2. Process Deductions
            foreach ($deductionRows as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $amount = round((float) ($row['amount'] ?? 0), 2);

                if ($name === '' && $amount <= 0) {
                    continue;
                }

                $rowId = ! empty($row['id']) ? (int) $row['id'] : null;

                // Skip tax and BPJS deduction lines here; they will be synced by recalculateItemTax
                if (($row['category'] ?? null) === PayrollLineCategory::TAX->value
                    || ($rowId && $existingLines->get($rowId)?->category === PayrollLineCategory::TAX)
                    || str_starts_with($name, 'PPh 21')
                    || ($row['category'] ?? null) === PayrollLineCategory::BPJS->value
                    || ($rowId && $existingLines->get($rowId)?->category === PayrollLineCategory::BPJS)) {
                    if ($rowId) {
                        $retainedIds[] = $rowId;
                    }

                    continue;
                }

                if ($name === '') {
                    throw new InvalidArgumentException('Nama potongan tidak boleh kosong jika nominal diisi.');
                }

                if ($amount < 0) {
                    throw new InvalidArgumentException('Nominal potongan tidak boleh negatif.');
                }

                $category = ! empty($row['cash_advance_installment_id'])
                    ? PayrollLineCategory::CASH_ADVANCE
                    : PayrollLineCategory::DEDUCTION;

                if ($rowId && $existingLines->has($rowId)) {
                    $line = $existingLines->get($rowId);
                    $line->update([
                        'name' => $name,
                        'amount' => $amount,
                        'type' => PayrollLineType::DEDUCTION,
                        'category' => $category,
                        'cash_advance_installment_id' => $row['cash_advance_installment_id'] ?? $line->cash_advance_installment_id,
                        'notes' => ! empty($row['notes']) ? trim((string) $row['notes']) : null,
                    ]);
                    $retainedIds[] = $rowId;
                } else {
                    $newLine = PayrollItemLine::create([
                        'payroll_item_id' => $item->id,
                        'salary_component_id' => $row['salary_component_id'] ?? null,
                        'cash_advance_installment_id' => $row['cash_advance_installment_id'] ?? null,
                        'name' => $name,
                        'type' => PayrollLineType::DEDUCTION,
                        'category' => $category,
                        'amount' => $amount,
                        'notes' => ! empty($row['notes']) ? trim((string) $row['notes']) : null,
                    ]);
                    $retainedIds[] = $newLine->id;
                }
            }

            // 3. Delete any lines that were removed (preserving tax, BPJS, and company contribution lines)
            $systemLineIds = $existingLines->filter(fn ($l) => in_array($l->category, [PayrollLineCategory::TAX, PayrollLineCategory::BPJS]) || $l->type === PayrollLineType::COMPANY_CONTRIBUTION)->pluck('id')->all();
            $retainedIds = array_values(array_unique(array_merge($retainedIds, $systemLineIds)));
            $item->lines()->whereNotIn('id', $retainedIds)->delete();

            // 4. Auto-recalculate tax and BPJS if employee has detail
            $company = CompanyProfile::getProfile();
            if ($item->employee?->detail) {
                $disabledCodes = [];
                $enabledCodes = [];
                foreach ($deductionRows as $r) {
                    $name = strtolower(trim((string) ($r['name'] ?? '')));
                    $cat = $r['category'] ?? '';
                    $code = null;
                    if ($cat === 'tax' || strcasecmp(trim((string) ($r['name'] ?? '')), 'pph 21') === 0) {
                        $code = 'PPH21';
                    } elseif (str_starts_with($name, 'bpjs kesehatan')) {
                        $code = 'BPJS_KES';
                    } elseif (str_starts_with($name, 'bpjs jht')) {
                        $code = 'BPJS_JHT';
                    } elseif (str_starts_with($name, 'bpjs pensiun') || str_starts_with($name, 'bpjs jp')) {
                        $code = 'BPJS_JP';
                    }

                    if ($code) {
                        if (isset($r['is_enabled']) && $r['is_enabled'] === false) {
                            $disabledCodes[] = $code;
                        } elseif (isset($r['is_enabled']) && $r['is_enabled'] === true) {
                            $enabledCodes[] = $code;
                        }
                    }
                }

                $disabledCompanyKeys = null;
                if (! empty($item->notes) && str_starts_with($item->notes, '{')) {
                    $notesData = json_decode($item->notes, true);
                    if (isset($notesData['disabled_company_bpjs'])) {
                        $disabledCompanyKeys = (array) $notesData['disabled_company_bpjs'];
                    }
                }

                $this->recalculateItemTax($item, $company, $disabledCodes, $disabledCompanyKeys, $enabledCodes);
            }

            // 5. Recalculate totals
            $this->recalculateItem($item);

            // 6. Track modifier & auto-verify HRD
            $item->update([
                'updated_by' => $user->id,
                'verified_by' => $user->id,
                'verified_at' => Carbon::now(),
            ]);
            $item->payrollPeriod->update(['updated_by' => $user->id]);

            return $item->fresh([
                'lines.salaryComponent',
                'lines.cashAdvanceInstallment',
                'employee.detail',
                'updater',
            ]);
        });
    }

    /**
     * Add allowance or deduction line to a specific payslip item.
     */
    public function addLine(PayrollItem $item, array $data, User $user): PayrollItemLine
    {
        Gate::forUser($user)->authorize('update', $item->payrollPeriod);

        if (! $item->payrollPeriod->status->isEditable()) {
            throw new InvalidArgumentException('Hanya draf gaji atau revisi yang dapat diubah.');
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal komponen harus lebih besar dari 0.');
        }

        $type = PayrollLineType::tryFrom($data['type'] ?? '') ?? PayrollLineType::ALLOWANCE;
        $category = PayrollLineCategory::tryFrom($data['category'] ?? '') ?? ($type === PayrollLineType::ALLOWANCE ? PayrollLineCategory::ALLOWANCE : PayrollLineCategory::DEDUCTION);

        return DB::transaction(function () use ($item, $data, $amount, $type, $category) {
            $line = PayrollItemLine::create([
                'payroll_item_id' => $item->id,
                'salary_component_id' => $data['salary_component_id'] ?? null,
                'name' => trim((string) $data['name']),
                'type' => $type,
                'category' => $category,
                'amount' => $amount,
                'notes' => ! empty($data['notes']) ? trim((string) $data['notes']) : null,
            ]);

            $this->recalculateItem($item);

            return $line;
        });
    }

    /**
     * Update an existing payslip line.
     */
    public function updateLine(PayrollItemLine $line, array $data, User $user): PayrollItemLine
    {
        $item = $line->item;
        Gate::forUser($user)->authorize('update', $item->payrollPeriod);

        if (! $item->payrollPeriod->status->isEditable()) {
            throw new InvalidArgumentException('Hanya draf gaji atau revisi yang dapat diubah.');
        }

        $amount = round((float) ($data['amount'] ?? $line->amount), 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal komponen harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($line, $item, $data, $amount) {
            $line->update([
                'name' => trim((string) ($data['name'] ?? $line->name)),
                'amount' => $amount,
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : $line->notes,
            ]);

            $this->recalculateItem($item);

            return $line;
        });
    }

    /**
     * Remove a line from a payslip item.
     */
    public function removeLine(PayrollItemLine $line, User $user): void
    {
        $item = $line->item;
        Gate::forUser($user)->authorize('update', $item->payrollPeriod);

        if (! $item->payrollPeriod->status->isEditable()) {
            throw new InvalidArgumentException('Hanya draf gaji atau revisi yang dapat diubah.');
        }

        DB::transaction(function () use ($line, $item) {
            $line->delete();
            $this->recalculateItem($item);
        });
    }

    /**
     * Automatically calculate and synchronize PPh 21 tax lines and snapshot for a payroll item.
     */
    public function recalculateItemTax(
        PayrollItem $item,
        ?CompanyProfile $company = null,
        ?array $disabledSystemCodes = null,
        ?array $disabledCompanyBpjsKeys = null,
        ?array $enabledSystemCodes = null
    ): PayrollItem {
        $company = $company ?? CompanyProfile::getProfile();
        $item->loadMissing(['employee.detail', 'lines', 'payrollPeriod']);
        $employee = $item->employee;

        if (! $employee) {
            return $item;
        }

        $taxService = app(TaxCalculationService::class);
        $basicSalary = (float) $item->basic_salary;
        $month = (int) $item->payrollPeriod->month;
        $year = (int) $item->payrollPeriod->year;

        // Non-tax allowance lines
        $currentAllowanceLines = $item->lines()
            ->where('type', PayrollLineType::ALLOWANCE)
            ->where('category', '!=', PayrollLineCategory::TAX)
            ->get();

        // Check if PPh 21 is disabled
        // Priority: Explicit per-employee toggle -> Existing line note -> Global CompanyProfile
        $pphDeductionLine = $item->lines()
            ->where('type', PayrollLineType::DEDUCTION)
            ->where('category', PayrollLineCategory::TAX)
            ->first();

        $isPphExplicitlyEnabled = $enabledSystemCodes !== null && in_array('PPH21', $enabledSystemCodes, true);
        $isPphExplicitlyDisabled = $disabledSystemCodes !== null && in_array('PPH21', $disabledSystemCodes, true);

        $isPphDisabled = false;
        if ($isPphExplicitlyEnabled) {
            $isPphDisabled = false;
        } elseif ($isPphExplicitlyDisabled) {
            $isPphDisabled = true;
        } elseif ($pphDeductionLine && $pphDeductionLine->notes === 'Nonaktif') {
            $isPphDisabled = true;
        } elseif (! $company->enable_pph21) {
            $isPphDisabled = true;
        }

        $taxResult = $taxService->calculateMonthlyTax(
            $employee,
            $basicSalary,
            $currentAllowanceLines,
            $company,
            $month,
            $year,
            $isPphExplicitlyEnabled
        );

        // 1. Sync Gross-Up Tax Allowance Line
        $taxAllowanceLine = $item->lines()
            ->where('type', PayrollLineType::ALLOWANCE)
            ->where('category', PayrollLineCategory::TAX)
            ->first();

        if (! $isPphDisabled && $taxResult['tax_method'] === TaxMethod::GROSS_UP && $taxResult['tax_allowance'] > 0) {
            if ($taxAllowanceLine) {
                $taxAllowanceLine->update([
                    'name' => 'Tunjangan PPh 21 (Gross-Up)',
                    'amount' => $taxResult['tax_allowance'],
                    'notes' => 'Tunjangan pajak otomatis skema Gross-Up',
                ]);
            } else {
                $pph21AllowanceComp = SalaryComponent::where('code', 'TUNJ_PPH21')->first();
                PayrollItemLine::create([
                    'payroll_item_id' => $item->id,
                    'salary_component_id' => $pph21AllowanceComp?->id,
                    'name' => 'Tunjangan PPh 21 (Gross-Up)',
                    'type' => PayrollLineType::ALLOWANCE,
                    'category' => PayrollLineCategory::TAX,
                    'amount' => $taxResult['tax_allowance'],
                    'notes' => 'Tunjangan pajak otomatis skema Gross-Up',
                ]);
            }
        } else {
            $taxAllowanceLine?->delete();
        }

        // 2. Sync PPh 21 Deduction Line
        if ($isPphDisabled) {
            if ($isPphExplicitlyDisabled || ($pphDeductionLine && $pphDeductionLine->notes === 'Nonaktif')) {
                $pph21Comp = SalaryComponent::where('code', 'PPH21')->first();
                if ($pphDeductionLine) {
                    $pphDeductionLine->update([
                        'name' => 'PPh 21',
                        'amount' => 0,
                        'notes' => 'Nonaktif',
                    ]);
                } else {
                    PayrollItemLine::create([
                        'payroll_item_id' => $item->id,
                        'salary_component_id' => $pph21Comp?->id,
                        'name' => 'PPh 21',
                        'type' => PayrollLineType::DEDUCTION,
                        'category' => PayrollLineCategory::TAX,
                        'amount' => 0,
                        'notes' => 'Nonaktif',
                    ]);
                }
            } else {
                $pphDeductionLine?->delete();
            }
        } elseif ($taxResult['pph21_amount'] > 0) {
            $pph21Comp = SalaryComponent::where('code', 'PPH21')->first();
            if ($pphDeductionLine) {
                $pphDeductionLine->update([
                    'name' => 'PPh 21',
                    'amount' => $taxResult['pph21_amount'],
                    'notes' => $taxResult['notes'] ?? 'Potongan PPh 21 otomatis',
                ]);
            } else {
                PayrollItemLine::create([
                    'payroll_item_id' => $item->id,
                    'salary_component_id' => $pph21Comp?->id,
                    'name' => 'PPh 21',
                    'type' => PayrollLineType::DEDUCTION,
                    'category' => PayrollLineCategory::TAX,
                    'amount' => $taxResult['pph21_amount'],
                    'notes' => $taxResult['notes'] ?? 'Potongan PPh 21 otomatis',
                ]);
            }
        } elseif ($taxResult['pph21_amount'] < 0) {
            $pph21Comp = SalaryComponent::where('code', 'PPH21')->first();
            $refundName = 'PPh 21 (Lebih Bayar)';
            $refundNotes = 'Kelebihan potong PPh 21 Jan-Nov dikembalikan ke karyawan';
            if ($pphDeductionLine) {
                $pphDeductionLine->update([
                    'name' => $refundName,
                    'amount' => $taxResult['pph21_amount'],
                    'notes' => $refundNotes,
                ]);
            } else {
                PayrollItemLine::create([
                    'payroll_item_id' => $item->id,
                    'salary_component_id' => $pph21Comp?->id,
                    'name' => $refundName,
                    'type' => PayrollLineType::DEDUCTION,
                    'category' => PayrollLineCategory::TAX,
                    'amount' => $taxResult['pph21_amount'],
                    'notes' => $refundNotes,
                ]);
            }
        } else {
            $pphDeductionLine?->delete();
        }

        // 3. Sync BPJS Employee Deductions (BPJS Kesehatan 1%, JHT 2%, JP 1%) based on Gaji Kotor
        $totalAllowances = (float) $currentAllowanceLines->sum('amount');
        $grossSalary = $basicSalary + $totalAllowances;

        if ($grossSalary > 0) {
            $bpjsBreakdown = $taxService->calculateBpjsBreakdown($grossSalary, $company);
            $bpjsConfigs = [
                ['code' => 'BPJS_KES', 'name' => 'BPJS Kesehatan (1%)', 'amount' => $bpjsBreakdown['employee']['bpjs_kes'], 'notes' => 'Iuran BPJS Kesehatan 1% dibayar karyawan'],
                ['code' => 'BPJS_JHT', 'name' => 'BPJS JHT (2%)', 'amount' => $bpjsBreakdown['employee']['bpjs_jht'], 'notes' => 'Iuran BPJS Ketenagakerjaan JHT 2% dibayar karyawan'],
                ['code' => 'BPJS_JP', 'name' => 'BPJS Pensiun (1%)', 'amount' => $bpjsBreakdown['employee']['bpjs_jp'], 'notes' => 'Iuran BPJS Ketenagakerjaan JP 1% dibayar karyawan'],
            ];

            $bpjsComps = SalaryComponent::whereIn('code', ['BPJS_KES', 'BPJS_JHT', 'BPJS_JP'])->get()->keyBy('code');
            $existingBpjsLines = $item->lines()
                ->where('type', PayrollLineType::DEDUCTION)
                ->where('category', PayrollLineCategory::BPJS)
                ->get();

            foreach ($bpjsConfigs as $cfg) {
                $compId = $bpjsComps->get($cfg['code'])?->id;
                $line = $existingBpjsLines->first(fn ($l) => ($compId && $l->salary_component_id === $compId) || strcasecmp(trim($l->name), trim($cfg['name'])) === 0);

                $isBpjsExplicitlyEnabled = $enabledSystemCodes !== null && in_array($cfg['code'], $enabledSystemCodes, true);
                $isBpjsExplicitlyDisabled = $disabledSystemCodes !== null && in_array($cfg['code'], $disabledSystemCodes, true);

                $isBpjsDisabled = false;
                if ($isBpjsExplicitlyEnabled) {
                    $isBpjsDisabled = false;
                } elseif ($isBpjsExplicitlyDisabled) {
                    $isBpjsDisabled = true;
                } elseif ($line && $line->notes === 'Nonaktif') {
                    $isBpjsDisabled = true;
                } elseif (! $company->include_bpjs_in_tax) {
                    $isBpjsDisabled = true;
                }

                if ($isBpjsDisabled) {
                    if ($isBpjsExplicitlyDisabled || ($line && $line->notes === 'Nonaktif')) {
                        if ($line) {
                            $line->update([
                                'name' => $cfg['name'],
                                'amount' => 0,
                                'notes' => 'Nonaktif',
                            ]);
                        } else {
                            PayrollItemLine::create([
                                'payroll_item_id' => $item->id,
                                'salary_component_id' => $compId,
                                'name' => $cfg['name'],
                                'type' => PayrollLineType::DEDUCTION,
                                'category' => PayrollLineCategory::BPJS,
                                'amount' => 0,
                                'notes' => 'Nonaktif',
                            ]);
                        }
                    } else {
                        $line?->delete();
                    }
                } elseif ($cfg['amount'] > 0) {
                    if ($line) {
                        $line->update([
                            'name' => $cfg['name'],
                            'amount' => $cfg['amount'],
                            'notes' => $cfg['notes'],
                        ]);
                    } else {
                        PayrollItemLine::create([
                            'payroll_item_id' => $item->id,
                            'salary_component_id' => $compId,
                            'name' => $cfg['name'],
                            'type' => PayrollLineType::DEDUCTION,
                            'category' => PayrollLineCategory::BPJS,
                            'amount' => $cfg['amount'],
                            'notes' => $cfg['notes'],
                        ]);
                    }
                }
            }
            // 3b. Sync BPJS Company Contributions (Employer share)
            $compBpjsConfigs = [
                ['key' => 'bpjs_kes', 'name' => 'BPJS Kesehatan ('.((float) ($company->bpjs_kes_comp_rate ?? 0.04) * 100).'%)', 'amount' => $bpjsBreakdown['company']['bpjs_kes'], 'notes' => 'Iuran BPJS Kesehatan dibayar oleh perusahaan'],
                ['key' => 'bpjs_jht', 'name' => 'BPJS Ketenagakerjaan JHT ('.((float) ($company->bpjs_jht_comp_rate ?? 0.037) * 100).'%)', 'amount' => $bpjsBreakdown['company']['bpjs_jht'], 'notes' => 'Iuran JHT dibayar oleh perusahaan'],
                ['key' => 'bpjs_jp', 'name' => 'BPJS Ketenagakerjaan JP ('.((float) ($company->bpjs_jp_comp_rate ?? 0.02) * 100).'%)', 'amount' => $bpjsBreakdown['company']['bpjs_jp'], 'notes' => 'Iuran Jaminan Pensiun dibayar oleh perusahaan'],
                ['key' => 'bpjs_jkk', 'name' => 'BPJS Ketenagakerjaan JKK ('.round(((float) ($company->bpjs_jkk_risk_rate ?? 0.0024)) * 100, 3).'%)', 'amount' => $bpjsBreakdown['company']['bpjs_jkk'], 'notes' => 'Jaminan Kecelakaan Kerja dibayar oleh perusahaan'],
                ['key' => 'bpjs_jkm', 'name' => 'BPJS Ketenagakerjaan JKM ('.((float) ($company->bpjs_jkm_rate ?? 0.003) * 100).'%)', 'amount' => $bpjsBreakdown['company']['bpjs_jkm'], 'notes' => 'Jaminan Kematian dibayar oleh perusahaan'],
            ];

            $enabledCompanyBpjsKeys = [];
            $disabledCompanyBpjsKeysList = [];
            $hasExplicitCompanyBpjsMeta = false;

            if (! empty($item->notes) && str_starts_with($item->notes, '{')) {
                $notesData = json_decode($item->notes, true);
                if (isset($notesData['disabled_company_bpjs'])) {
                    $disabledCompanyBpjsKeysList = (array) $notesData['disabled_company_bpjs'];
                    $hasExplicitCompanyBpjsMeta = true;
                }
                if (isset($notesData['enabled_company_bpjs'])) {
                    $enabledCompanyBpjsKeys = (array) $notesData['enabled_company_bpjs'];
                    $hasExplicitCompanyBpjsMeta = true;
                }
            }

            if ($disabledCompanyBpjsKeys !== null) {
                $disabledCompanyBpjsKeysList = $disabledCompanyBpjsKeys;
                $hasExplicitCompanyBpjsMeta = true;
            }

            $existingCompLines = $item->lines()
                ->where('type', PayrollLineType::COMPANY_CONTRIBUTION)
                ->get();

            foreach ($compBpjsConfigs as $cfg) {
                $prefix = explode(' (', $cfg['name'])[0];
                $line = $existingCompLines->first(fn ($l) => str_starts_with(strtoupper($l->name), strtoupper($prefix)));

                $isCompDisabled = false;
                if ($company->include_bpjs_in_tax) {
                    $isCompDisabled = in_array($cfg['key'], $disabledCompanyBpjsKeysList, true);
                } else {
                    $isCompDisabled = ! in_array($cfg['key'], $enabledCompanyBpjsKeys, true);
                }

                if (! $hasExplicitCompanyBpjsMeta && $line && $line->notes === 'Nonaktif') {
                    $isCompDisabled = true;
                }

                if ($isCompDisabled) {
                    if ($hasExplicitCompanyBpjsMeta || ($line && $line->notes === 'Nonaktif')) {
                        if ($line) {
                            $line->update([
                                'name' => $cfg['name'],
                                'amount' => 0,
                                'notes' => 'Nonaktif',
                            ]);
                        } else {
                            PayrollItemLine::create([
                                'payroll_item_id' => $item->id,
                                'name' => $cfg['name'],
                                'type' => PayrollLineType::COMPANY_CONTRIBUTION,
                                'category' => PayrollLineCategory::BPJS,
                                'amount' => 0,
                                'notes' => 'Nonaktif',
                            ]);
                        }
                    } else {
                        $line?->delete();
                    }
                } elseif ($cfg['amount'] > 0) {
                    if ($line) {
                        $line->update([
                            'name' => $cfg['name'],
                            'amount' => $cfg['amount'],
                            'notes' => $cfg['notes'],
                        ]);
                    } else {
                        PayrollItemLine::create([
                            'payroll_item_id' => $item->id,
                            'name' => $cfg['name'],
                            'type' => PayrollLineType::COMPANY_CONTRIBUTION,
                            'category' => PayrollLineCategory::BPJS,
                            'amount' => $cfg['amount'],
                            'notes' => $cfg['notes'],
                        ]);
                    }
                }
            }
        } else {
            $item->lines()->where('category', PayrollLineCategory::BPJS)->delete();
            $item->lines()->where('type', PayrollLineType::COMPANY_CONTRIBUTION)->delete();
        }

        // 4. Update snapshot columns on PayrollItem
        $item->update([
            'tax_method' => $taxResult['tax_method'],
            'ptkp_status' => $taxResult['ptkp_status'],
            'ter_category' => $taxResult['ter_category'],
            'ter_rate' => $taxResult['ter_rate'],
            'taxable_gross' => $taxResult['taxable_gross'],
            'tax_allowance' => $isPphDisabled ? 0 : ($taxResult['tax_method'] === TaxMethod::GROSS_UP ? $taxResult['tax_allowance'] : 0),
            'pph21_amount' => $isPphDisabled ? 0 : $taxResult['pph21_amount'],
        ]);

        return $item;
    }

    /**
     * Recalculate item totals and update parent period totals.
     */
    public function recalculateItem(PayrollItem $item): void
    {
        $lines = $item->lines()->get();
        $totalAllowances = (float) $lines->where('type', PayrollLineType::ALLOWANCE)->sum('amount');
        $totalDeductions = (float) $lines->where('type', PayrollLineType::DEDUCTION)->sum('amount');
        $totalCompanyContributions = (float) $lines->where('type', PayrollLineType::COMPANY_CONTRIBUTION)->sum('amount');
        $basicSalary = (float) $item->basic_salary;
        $netSalary = max(0, round($basicSalary + $totalAllowances - $totalDeductions, 2));

        $item->update([
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'total_company_contributions' => $totalCompanyContributions,
            'net_salary' => $netSalary,
        ]);

        $this->recalculatePeriod($item->payrollPeriod);
    }

    /**
     * Recalculate entire period totals from all its items.
     */
    public function recalculatePeriod(PayrollPeriod $period): void
    {
        $items = $period->items()->get();

        $totalBasic = (float) $items->sum('basic_salary');
        $totalAllowances = (float) $items->sum('total_allowances');
        $totalDeductions = (float) $items->sum('total_deductions');
        $totalNet = (float) $items->sum('net_salary');
        $totalCompanyContributions = (float) $items->sum('total_company_contributions');

        $period->update([
            'total_basic_salary' => $totalBasic,
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'total_net_salary' => $totalNet,
            'total_company_contributions' => $totalCompanyContributions,
            'employee_count' => $items->count(),
        ]);
    }

    /**
     * Verify a single payroll item by HRD / Admin.
     */
    public function verifyItem(PayrollItem $item, User $user): PayrollItem
    {
        Gate::forUser($user)->authorize('update', $item->payrollPeriod);

        if (! $item->payrollPeriod->status->isEditable()) {
            throw new InvalidArgumentException('Hanya draf atau revisi yang dapat diverifikasi oleh HRD.');
        }

        $now = Carbon::now();
        $item->update([
            'verified_by' => $user->id,
            'verified_at' => $now,
            'updated_by' => $user->id,
        ]);

        return $item;
    }

    /**
     * Unverify a single payroll item back to draft.
     */
    public function unverifyItem(PayrollItem $item, User $user): PayrollItem
    {
        Gate::forUser($user)->authorize('update', $item->payrollPeriod);

        if (! $item->payrollPeriod->status->isEditable()) {
            throw new InvalidArgumentException('Hanya draf atau revisi yang dapat dibatalkan verifikasinya.');
        }

        $item->update([
            'verified_by' => null,
            'verified_at' => null,
            'updated_by' => $user->id,
        ]);

        return $item;
    }

    /**
     * Bulk verify all payroll items in a draft period.
     */
    public function verifyAllItems(PayrollPeriod $period, User $user): int
    {
        Gate::forUser($user)->authorize('update', $period);

        if (! $period->status->isEditable()) {
            throw new InvalidArgumentException('Hanya draf atau revisi yang dapat diverifikasi oleh HRD.');
        }

        $now = Carbon::now();

        return $period->items()->whereNull('verified_at')->update([
            'verified_by' => $user->id,
            'verified_at' => $now,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Submit a draft period to Direktur for approval.
     */
    public function submitPeriod(PayrollPeriod $period, User $user, bool $requireVerification = false): PayrollPeriod
    {
        Gate::forUser($user)->authorize('submit', $period);

        if (! $period->status->isEditable()) {
            throw new InvalidArgumentException('Hanya draf periode yang dapat diajukan.');
        }

        if ($period->items()->count() === 0) {
            throw new InvalidArgumentException('Periode penggajian tidak memiliki karyawan.');
        }

        if ($requireVerification) {
            $unverifiedCount = $period->items()->whereNull('verified_at')->count();
            if ($unverifiedCount > 0) {
                throw new InvalidArgumentException("Terdapat {$unverifiedCount} karyawan yang masih berstatus Draf dan belum diverifikasi HRD.");
            }
        }

        $period->update([
            'status' => PayrollPeriodStatus::SUBMITTED,
            'submitted_by' => $user->id,
            'submitted_at' => Carbon::now(),
            'rejection_reason' => null,
        ]);

        return $period;
    }

    /**
     * Approve a submitted payroll period (Direktur).
     */
    public function approvePeriod(PayrollPeriod $period, User $user): PayrollPeriod
    {
        Gate::forUser($user)->authorize('approve', $period);

        if ($period->status !== PayrollPeriodStatus::SUBMITTED && $period->status !== PayrollPeriodStatus::DRAFT) {
            throw new InvalidArgumentException('Hanya periode yang sedang diajukan atau draf yang dapat disetujui.');
        }

        $now = Carbon::now();
        $period->update([
            'status' => PayrollPeriodStatus::APPROVED,
            'approved_by' => $user->id,
            'approved_at' => $now,
            'rejection_reason' => null,
        ]);

        $period->items()->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => $now,
            'rejection_reason' => null,
        ]);

        return $period;
    }

    /**
     * Reject a submitted payroll period (Direktur).
     */
    public function rejectPeriod(PayrollPeriod $period, string $reason, User $user): PayrollPeriod
    {
        Gate::forUser($user)->authorize('reject', $period);

        if (empty(trim($reason))) {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        $period->update([
            'status' => PayrollPeriodStatus::REJECTED,
            'rejection_reason' => trim($reason),
        ]);

        $period->items()->update([
            'approval_status' => 'rejected',
            'rejection_reason' => trim($reason),
        ]);

        return $period;
    }

    /**
     * Approve a single payroll item (Direktur).
     */
    public function approveItem(PayrollItem $item, User $user): PayrollItem
    {
        Gate::forUser($user)->authorize('approve', $item->payrollPeriod);

        $now = Carbon::now();
        $item->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => $now,
            'rejection_reason' => null,
        ]);

        $this->syncPeriodApprovalStatus($item->payrollPeriod, $user);

        return $item;
    }

    /**
     * Reject a single payroll item (Direktur).
     */
    public function rejectItem(PayrollItem $item, string $reason, User $user): PayrollItem
    {
        Gate::forUser($user)->authorize('reject', $item->payrollPeriod);

        if (empty(trim($reason))) {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        $item->update([
            'approval_status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => trim($reason),
        ]);

        $period = $item->payrollPeriod;
        if ($period->status === PayrollPeriodStatus::APPROVED) {
            $period->update([
                'status' => PayrollPeriodStatus::SUBMITTED,
                'rejection_reason' => "Slip {$item->employee->full_name} ditolak: ".trim($reason),
            ]);
        }

        return $item;
    }

    /**
     * Cancel/revoke approval of a single payroll item (Direktur) back to pending.
     */
    public function unapproveItem(PayrollItem $item, User $user): PayrollItem
    {
        Gate::forUser($user)->authorize('approve', $item->payrollPeriod);

        if ($item->payrollPeriod->status === PayrollPeriodStatus::PAID) {
            throw new InvalidArgumentException('Tidak dapat membatalkan persetujuan periode yang sudah dibayar.');
        }

        $item->update([
            'approval_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);

        $period = $item->payrollPeriod;
        if ($period->status === PayrollPeriodStatus::APPROVED) {
            $period->update([
                'status' => PayrollPeriodStatus::SUBMITTED,
                'approved_by' => null,
                'approved_at' => null,
            ]);
        }

        return $item;
    }

    /**
     * Bulk approve multiple payroll items (Direktur).
     */
    public function approveItems(PayrollPeriod $period, array $itemIds, User $user): int
    {
        Gate::forUser($user)->authorize('approve', $period);

        if (empty($itemIds)) {
            return 0;
        }

        $now = Carbon::now();
        $count = $period->items()->whereIn('id', $itemIds)->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => $now,
            'rejection_reason' => null,
        ]);

        $this->syncPeriodApprovalStatus($period, $user);

        return $count;
    }

    /**
     * Bulk reject multiple payroll items (Direktur).
     */
    public function rejectItems(PayrollPeriod $period, array $itemIds, string $reason, User $user): int
    {
        Gate::forUser($user)->authorize('reject', $period);

        if (empty($itemIds)) {
            return 0;
        }

        if (empty(trim($reason))) {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        $count = $period->items()->whereIn('id', $itemIds)->update([
            'approval_status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => trim($reason),
        ]);

        if ($period->status === PayrollPeriodStatus::APPROVED) {
            $period->update([
                'status' => PayrollPeriodStatus::SUBMITTED,
                'rejection_reason' => trim($reason),
            ]);
        }

        return $count;
    }

    /**
     * Approve all items in a period (Direktur).
     */
    public function approveAllItems(PayrollPeriod $period, User $user): int
    {
        Gate::forUser($user)->authorize('approve', $period);

        $now = Carbon::now();
        $count = $period->items()->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => $now,
            'rejection_reason' => null,
        ]);

        $period->update([
            'status' => PayrollPeriodStatus::APPROVED,
            'approved_by' => $user->id,
            'approved_at' => $now,
            'rejection_reason' => null,
        ]);

        return $count;
    }

    /**
     * Synchronize period status when all items are approved.
     */
    protected function syncPeriodApprovalStatus(PayrollPeriod $period, User $user): void
    {
        $totalItems = $period->items()->count();
        $approvedItems = $period->items()->where('approval_status', 'approved')->count();

        if ($totalItems > 0 && $approvedItems === $totalItems) {
            $period->update([
                'status' => PayrollPeriodStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => Carbon::now(),
                'rejection_reason' => null,
            ]);
        }
    }

    /**
     * Mark payroll period as paid and execute automatic cash advance deductions.
     */
    public function markAsPaid(PayrollPeriod $period, array $data, User $user): PayrollPeriod
    {
        Gate::forUser($user)->authorize('recordPayment', $period);

        if ($period->status !== PayrollPeriodStatus::APPROVED) {
            throw new InvalidArgumentException('Hanya periode yang telah disetujui yang dapat dibayar.');
        }

        $paymentDate = Carbon::parse($data['payment_date'] ?? Carbon::now());
        $paymentMethod = $data['payment_method'] ?? 'bank_transfer';

        return DB::transaction(function () use ($period, $paymentDate, $paymentMethod, $data, $user) {
            $lockedPeriod = PayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            if ($lockedPeriod->status !== PayrollPeriodStatus::APPROVED) {
                throw new InvalidArgumentException('Periode telah diproses sebelumnya.');
            }

            // Update period status
            $lockedPeriod->update([
                'status' => PayrollPeriodStatus::PAID,
                'payment_date' => $paymentDate->toDateString(),
                'payment_method' => $paymentMethod,
                'notes' => ! empty($data['notes']) ? trim((string) $data['notes']) : $lockedPeriod->notes,
                'updated_by' => $user->id,
            ]);

            // Unpaid item IDs to process
            $unpaidItemIds = $lockedPeriod->items()->where('payment_status', '!=', 'paid')->pluck('id');

            // Update all unpaid items to paid
            $lockedPeriod->items()->where('payment_status', '!=', 'paid')->update([
                'payment_status' => 'paid',
                'paid_at' => Carbon::now(),
                'payment_method' => $paymentMethod,
                'paid_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Execute cash advance deductions atomically for unpaid items
            $advanceLines = PayrollItemLine::query()
                ->whereIn('payroll_item_id', $unpaidItemIds)
                ->whereNotNull('cash_advance_installment_id')
                ->with(['cashAdvanceInstallment.cashAdvance'])
                ->get();

            foreach ($advanceLines as $line) {
                if (! $line->cashAdvanceInstallment) {
                    continue;
                }

                $installment = CashAdvanceInstallment::query()
                    ->lockForUpdate()
                    ->find($line->cash_advance_installment_id);

                if (! $installment) {
                    continue;
                }

                $cashAdvance = CashAdvance::query()
                    ->lockForUpdate()
                    ->find($installment->cash_advance_id);

                if (! $cashAdvance) {
                    continue;
                }

                $totalToDeduct = min((float) $line->amount, (float) $cashAdvance->remaining_amount);
                if ($totalToDeduct <= 0) {
                    continue;
                }

                $leftToDeduct = $totalToDeduct;
                $installmentsToPay = $cashAdvance->installments()
                    ->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                    ->orderBy('installment_number', 'asc')
                    ->lockForUpdate()
                    ->get();

                $installmentsToPay = $installmentsToPay->sortBy(
                    fn (CashAdvanceInstallment $inst): int => (int) $inst->id === (int) $installment->id ? 0 : 1
                )->values();

                foreach ($installmentsToPay as $inst) {
                    if ($leftToDeduct <= 0) {
                        break;
                    }

                    $instRem = max(0.0, round((float) $inst->amount - (float) $inst->paid_amount, 2));
                    $alloc = min($leftToDeduct, $instRem);
                    if ($alloc <= 0) {
                        continue;
                    }

                    $payNumber = app(CashAdvanceService::class)->generatePaymentNumber($paymentDate);
                    CashAdvancePayment::create([
                        'payment_number' => $payNumber,
                        'cash_advance_id' => $cashAdvance->id,
                        'cash_advance_installment_id' => $inst->id,
                        'payment_date' => $paymentDate->toDateString(),
                        'amount' => $alloc,
                        'payment_method' => PaymentMethod::SALARY_DEDUCTION,
                        'status' => CashAdvancePaymentStatus::APPROVED,
                        'approved_by' => $user->id,
                        'approved_at' => Carbon::now(),
                        'notes' => "Potongan Gaji {$lockedPeriod->reference_number} ({$lockedPeriod->name})",
                        'recorded_by' => $user->id,
                    ]);

                    $newPaid = round((float) $inst->paid_amount + $alloc, 2);
                    $isDone = ($newPaid >= (float) $inst->amount - 0.01);
                    $inst->update([
                        'paid_amount' => $newPaid,
                        'status' => $isDone ? InstallmentStatus::PAID : InstallmentStatus::PARTIALLY_PAID,
                        'paid_at' => $isDone ? Carbon::now() : $inst->paid_at,
                    ]);

                    $leftToDeduct = max(0.0, round($leftToDeduct - $alloc, 2));
                }

                $newRemaining = max(0.0, round((float) $cashAdvance->remaining_amount - $totalToDeduct, 2));
                $newAdvanceStatus = ($newRemaining <= 0.01) ? CashAdvanceStatus::PAID : $cashAdvance->status;
                $cashAdvance->update([
                    'remaining_amount' => $newRemaining,
                    'status' => $newAdvanceStatus,
                ]);
            }

            return $lockedPeriod;
        });
    }

    /**
     * Record payment for an individual payroll item.
     */
    public function markItemAsPaid(PayrollItem $item, array $data, User $user): PayrollItem
    {
        Gate::forUser($user)->authorize('recordPayment', $item->payrollPeriod);

        if (! $item->isApproved() && $item->payrollPeriod->status !== PayrollPeriodStatus::APPROVED) {
            throw new InvalidArgumentException('Hanya slip gaji yang telah disetujui yang dapat dibayar.');
        }

        if ($item->payment_status === 'paid') {
            throw new InvalidArgumentException('Gaji karyawan ini telah dibayar sebelumnya.');
        }

        $paymentDate = Carbon::parse($data['payment_date'] ?? Carbon::now());
        $paymentMethod = $data['payment_method'] ?? 'bank_transfer';

        return DB::transaction(function () use ($item, $paymentDate, $paymentMethod, $data, $user) {
            $lockedItem = PayrollItem::query()
                ->lockForUpdate()
                ->findOrFail($item->id);

            if ($lockedItem->payment_status === 'paid') {
                throw new InvalidArgumentException('Gaji karyawan ini telah dibayar sebelumnya.');
            }

            // Update item payment information
            $lockedItem->update([
                'payment_status' => 'paid',
                'paid_at' => $paymentDate,
                'payment_method' => $paymentMethod,
                'payment_proof_path' => $data['payment_proof_path'] ?? $lockedItem->payment_proof_path,
                'payment_notes' => ! empty($data['payment_notes']) ? trim((string) $data['payment_notes']) : null,
                'bank_name' => $data['bank_name'] ?? $lockedItem->bank_name,
                'bank_account_number' => $data['bank_account_number'] ?? $lockedItem->bank_account_number,
                'bank_account_name' => $data['bank_account_name'] ?? $lockedItem->bank_account_name,
                'paid_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Execute cash advance deductions atomically for this item
            $advanceLines = PayrollItemLine::query()
                ->where('payroll_item_id', $lockedItem->id)
                ->whereNotNull('cash_advance_installment_id')
                ->with(['cashAdvanceInstallment.cashAdvance'])
                ->get();

            $period = $lockedItem->payrollPeriod;

            foreach ($advanceLines as $line) {
                if (! $line->cashAdvanceInstallment) {
                    continue;
                }

                $installment = CashAdvanceInstallment::query()
                    ->lockForUpdate()
                    ->find($line->cash_advance_installment_id);

                if (! $installment) {
                    continue;
                }

                $cashAdvance = CashAdvance::query()
                    ->lockForUpdate()
                    ->find($installment->cash_advance_id);

                if (! $cashAdvance) {
                    continue;
                }

                $totalToDeduct = min((float) $line->amount, (float) $cashAdvance->remaining_amount);
                if ($totalToDeduct <= 0) {
                    continue;
                }

                $leftToDeduct = $totalToDeduct;
                $installmentsToPay = $cashAdvance->installments()
                    ->whereIn('status', [InstallmentStatus::PENDING->value, InstallmentStatus::PARTIALLY_PAID->value])
                    ->orderBy('installment_number', 'asc')
                    ->lockForUpdate()
                    ->get();

                $installmentsToPay = $installmentsToPay->sortBy(
                    fn (CashAdvanceInstallment $inst): int => (int) $inst->id === (int) $installment->id ? 0 : 1
                )->values();

                foreach ($installmentsToPay as $inst) {
                    if ($leftToDeduct <= 0) {
                        break;
                    }

                    $instRem = max(0.0, round((float) $inst->amount - (float) $inst->paid_amount, 2));
                    $alloc = min($leftToDeduct, $instRem);
                    if ($alloc <= 0) {
                        continue;
                    }

                    // Generate payment number for cash advance payment using CashAdvanceService
                    $payNumber = app(CashAdvanceService::class)->generatePaymentNumber($paymentDate);

                    CashAdvancePayment::create([
                        'payment_number' => $payNumber,
                        'cash_advance_id' => $cashAdvance->id,
                        'cash_advance_installment_id' => $inst->id,
                        'payment_date' => $paymentDate->toDateString(),
                        'amount' => $alloc,
                        'payment_method' => PaymentMethod::SALARY_DEDUCTION,
                        'status' => CashAdvancePaymentStatus::APPROVED,
                        'approved_by' => $user->id,
                        'approved_at' => Carbon::now(),
                        'notes' => "Potongan Gaji {$period->reference_number} ({$period->name})",
                        'recorded_by' => $user->id,
                    ]);

                    $newPaid = round((float) $inst->paid_amount + $alloc, 2);
                    $isInstallmentDone = ($newPaid >= (float) $inst->amount - 0.01);

                    $inst->update([
                        'paid_amount' => $newPaid,
                        'status' => $isInstallmentDone ? InstallmentStatus::PAID : InstallmentStatus::PARTIALLY_PAID,
                        'paid_at' => $isInstallmentDone ? Carbon::now() : $inst->paid_at,
                    ]);

                    $leftToDeduct = max(0.0, round($leftToDeduct - $alloc, 2));
                }

                $newRemaining = max(0.0, round((float) $cashAdvance->remaining_amount - $totalToDeduct, 2));
                $newAdvanceStatus = ($newRemaining <= 0.01) ? CashAdvanceStatus::PAID : $cashAdvance->status;

                $cashAdvance->update([
                    'remaining_amount' => $newRemaining,
                    'status' => $newAdvanceStatus,
                ]);
            }

            // Check if all items in period are now paid
            $hasUnpaidItems = PayrollItem::query()
                ->where('payroll_period_id', $period->id)
                ->where('payment_status', '!=', 'paid')
                ->exists();

            if (! $hasUnpaidItems) {
                $period->update([
                    'status' => PayrollPeriodStatus::PAID,
                    'payment_date' => $paymentDate->toDateString(),
                    'updated_by' => $user->id,
                ]);
            }

            return $lockedItem->fresh(['employee', 'payer']);
        });
    }

    /**
     * Update payment details for an already paid item.
     */
    public function updateItemPayment(PayrollItem $item, array $data, User $user): PayrollItem
    {
        if ($item->payment_status !== 'paid') {
            throw new InvalidArgumentException('Hanya data pembayaran gaji yang sudah dibayar yang dapat diperbarui.');
        }

        $paymentDate = Carbon::parse($data['payment_date'] ?? $item->paid_at ?? Carbon::now());
        $paymentMethod = $data['payment_method'] ?? $item->payment_method ?? 'bank_transfer';

        $updateData = [
            'paid_at' => $paymentDate,
            'payment_method' => $paymentMethod,
            'payment_notes' => ! empty($data['payment_notes']) ? trim((string) $data['payment_notes']) : null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_number' => $data['bank_account_number'] ?? null,
            'bank_account_name' => $data['bank_account_name'] ?? null,
            'updated_by' => $user->id,
        ];

        if (! empty($data['payment_proof_path'])) {
            $updateData['payment_proof_path'] = $data['payment_proof_path'];
        }

        $item->update($updateData);

        return $item->fresh(['employee', 'payer', 'updater']);
    }

    /**
     * Remove an individual payroll item from an editable payroll period.
     */
    public function removeItemFromPeriod(PayrollItem $item, User $user): void
    {
        $period = $item->payrollPeriod;
        Gate::forUser($user)->authorize('update', $period);

        if (! $period->status->isEditable()) {
            throw new InvalidArgumentException('Karyawan hanya dapat dihapus dari periode berstatus Draf atau Revisi.');
        }

        DB::transaction(function () use ($item, $period) {
            $item->lines()->delete();
            $item->delete();

            $this->recalculatePeriod($period);
        });
    }

    /**
     * Delete payroll period.
     */
    public function deletePeriod(PayrollPeriod $period, User $user): void
    {
        Gate::forUser($user)->authorize('delete', $period);

        if (! $period->status->isEditable()) {
            throw new InvalidArgumentException('Hanya periode berstatus draf atau ditolak yang dapat dihapus.');
        }

        DB::transaction(function () use ($period) {
            $period->delete();
        });
    }
}
