<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Illuminate\Contracts\View\View;

class PayrollPrintController extends Controller
{
    /**
     * Display printable payslip view.
     */
    public function printSlip(PayrollPeriod $period, PayrollItem $item): View
    {
        abort_unless($item->payroll_period_id === $period->id, 404);

        $user = auth()->user();
        if ($user && $user->hasRole('karyawan')) {
            $emp = $user->getEmployeeRecord();
            abort_unless($emp && (int) $item->employee_id === (int) $emp->id, 403, 'Anda tidak memiliki hak akses untuk mencetak slip gaji karyawan lain.');
        }

        $item->load([
            'employee.department',
            'employee.position',
            'employee.detail',
            'lines.salaryComponent',
            'lines.cashAdvanceInstallment',
            'payrollPeriod',
        ]);

        $company = CompanyProfile::getProfile();

        return view('payrolls.print', [
            'period' => $period,
            'item' => $item,
            'company' => $company,
        ]);
    }

    /**
     * Display printable annual payroll summary.
     */
    public function printYearlySummary(int $year, PayrollService $service): View
    {
        $statistics = $service->getStatistics($year);
        $company = CompanyProfile::getProfile();

        return view('payrolls.print-yearly-summary', [
            'year' => $year,
            'statistics' => $statistics,
            'company' => $company,
        ]);
    }
}
