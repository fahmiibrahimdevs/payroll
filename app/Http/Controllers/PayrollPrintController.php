<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Services\ActivityLogService;
use App\Services\PayrollService;
use Illuminate\Contracts\View\View;

class PayrollPrintController extends Controller
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

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
            $item->loadMissing('payrollPeriod');
            abort_unless($item->isOfficiallyApproved(), 403, 'Slip gaji belum disetujui Direktur.');
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

        $this->logger->info(
            action: 'PAYSLIP_PRINTED',
            module: 'payrolls',
            message: 'Payslip print view opened',
            context: [
                'payroll_period_id' => $period->id,
                'payroll_item_id' => $item->id,
                'employee_id' => $item->employee_id,
                'payslip_number' => $item->payslip_number,
            ]
        );

        return view('payrolls.print', [
            'period' => $period,
            'item' => $item,
            'company' => $company,
        ]);
    }

    /**
     * Display printable batch payslips view for all employees in a period.
     */
    public function printAllSlips(PayrollPeriod $period): View
    {
        $user = auth()->user();
        abort_unless($user && $user->hasRole(['admin', 'direktur']), 403, 'Hanya Admin dan Direktur yang dapat mencetak seluruh slip gaji.');

        $period->loadMissing(['creator', 'submitter', 'approver']);

        $items = $period->items()
            ->with([
                'employee.department',
                'employee.position',
                'employee.detail',
                'allowanceLines.salaryComponent',
                'deductionLines.salaryComponent',
                'payrollPeriod',
            ])
            ->orderBy('id')
            ->get();

        $company = CompanyProfile::getProfile();

        $this->logger->info(
            action: 'PAYSLIPS_BATCH_PRINTED',
            module: 'payrolls',
            message: 'All payslips batch print view opened',
            context: [
                'payroll_period_id' => $period->id,
                'items_count' => $items->count(),
            ]
        );

        return view('payrolls.print-batch', [
            'period' => $period,
            'items' => $items,
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

        $this->logger->info(
            action: 'YEARLY_SUMMARY_PRINTED',
            module: 'payrolls',
            message: 'Annual payroll summary print view opened',
            context: ['year' => $year]
        );

        return view('payrolls.print-yearly-summary', [
            'year' => $year,
            'statistics' => $statistics,
            'company' => $company,
        ]);
    }
}
