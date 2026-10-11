<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use Illuminate\Contracts\View\View;

class PayrollPrintController extends Controller
{
    /**
     * Display printable payslip view.
     */
    public function printSlip(PayrollPeriod $period, PayrollItem $item): View
    {
        abort_unless($item->payroll_period_id === $period->id, 404);

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
}
