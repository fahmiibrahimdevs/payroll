<?php

namespace App\Livewire\Employees;

use App\Enums\SalaryAdjustmentType;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Services\EmployeeService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class Show extends Component
{
    public Employee $employee;

    #[Url(as: 'tab')]
    public string $activeTab = 'kepegawaian';

    #[Url(as: 'year')]
    public int $chartYear = 2026;

    public ?PayrollItem $previewItem = null;

    public string $adj_new_salary = '';

    public string $adj_effective_date = '';

    public string $adj_type = 'annual_adjustment';

    public string $adj_reason = '';

    public function mount(Employee $employee): void
    {
        $user = auth()->user();
        if ($user?->hasRole('karyawan')) {
            $empRecord = $user->getEmployeeRecord();
            abort_unless($empRecord && (int) $employee->id === (int) $empRecord->id, 403, 'Anda tidak memiliki hak akses untuk melihat data karyawan lain.');
        } else {
            abort_unless($user?->hasRole('admin') || $user?->hasRole('direktur'), 403);
        }

        $this->employee = $employee->load([
            'department',
            'position',
            'detail',
            'salaryHistories.changer',
        ]);

        $this->chartYear = (int) now()->format('Y');
        $this->adj_effective_date = now()->format('Y-m-d');
        $this->adj_type = SalaryAdjustmentType::ANNUAL_ADJUSTMENT->value;
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['kepegawaian', 'pribadi', 'riwayat-gaji', 'pajak-bpjs'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function editEmployeeData(): mixed
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        session()->put('employee_edit_handoff', [
            'id' => $this->employee->id,
            'section' => 'kepegawaian',
        ]);

        return redirect()->route('employees.index');
    }

    public function openAdjustmentModal(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $this->adj_new_salary = (string) (int) $this->employee->basic_salary;
        $this->adj_effective_date = now()->format('Y-m-d');
        $this->adj_type = SalaryAdjustmentType::ANNUAL_ADJUSTMENT->value;
        $this->adj_reason = '';
        $this->resetValidation();

        $this->dispatch('open-salary-modal');
    }

    public function closeAdjustmentModal(): void
    {
        $this->reset(['adj_new_salary', 'adj_reason']);
        $this->resetValidation();
        $this->dispatch('close-salary-modal');
    }

    public function saveSalaryAdjustment(EmployeeService $employeeService): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $cleanSalary = (float) preg_replace('/[^0-9]/', '', (string) $this->adj_new_salary);

        $this->validate([
            'adj_new_salary' => ['required'],
            'adj_effective_date' => ['required', 'date'],
            'adj_type' => ['required', 'string'],
            'adj_reason' => ['nullable', 'string', 'max:500'],
        ], [
            'adj_new_salary.required' => 'Nominal gaji baru wajib diisi.',
            'adj_effective_date.required' => 'Tanggal berlaku penyesuaian gaji wajib diisi.',
            'adj_effective_date.date' => 'Format tanggal berlaku tidak valid.',
            'adj_type.required' => 'Jenis penyesuaian wajib dipilih.',
        ]);

        if ($cleanSalary < 0) {
            throw ValidationException::withMessages([
                'adj_new_salary' => 'Nominal gaji tidak boleh bernilai negatif.',
            ]);
        }

        try {
            $employeeService->recordSalaryAdjustment(
                employee: $this->employee,
                data: [
                    'new_salary' => $cleanSalary,
                    'effective_date' => $this->adj_effective_date,
                    'type' => $this->adj_type,
                    'reason' => $this->adj_reason ?: null,
                ],
                user: auth()->user()
            );

            $this->employee->refresh();
            $this->employee->load(['salaryHistories.changer']);

            $this->closeAdjustmentModal();

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'text' => 'Penyesuaian gaji berhasil dicatat dan gaji pokok diperbarui.',
            ]);
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'text' => 'Gagal menyimpan penyesuaian gaji: '.$e->getMessage(),
            ]);
        }
    }

    public function deleteSalaryHistory(int $historyId, EmployeeService $employeeService): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $history = $this->employee->salaryHistories()->find($historyId);

        if (! $history) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Ditemukan',
                'text' => 'Data riwayat gaji tidak ditemukan.',
            ]);

            return;
        }

        if ($history->type === SalaryAdjustmentType::INITIAL) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'text' => 'Catatan gaji awal karyawan tidak dapat dihapus.',
            ]);

            return;
        }

        $this->employee = $employeeService->deleteSalaryHistory($history, auth()->user());
        $this->employee->load(['salaryHistories.changer']);

        $this->dispatch('swal:alert', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'text' => 'Riwayat penyesuaian gaji berhasil dihapus.',
        ]);
    }

    public function updatedChartYear(): void
    {
        $this->dispatch('salary-chart-updated', [
            'labels' => $this->monthlySalaryData['labels'],
            'data' => $this->monthlySalaryData['net_salaries'],
        ]);
    }

    public function openPreviewSlip(int $itemId): void
    {
        $this->previewItem = PayrollItem::with([
            'employee.department',
            'employee.position',
            'employee.detail',
            'allowanceLines',
            'deductionLines',
            'payrollPeriod',
            'payer',
            'approver',
        ])->where('employee_id', $this->employee->id)->findOrFail($itemId);

        if (auth()->user()?->hasRole('karyawan')) {
            abort_unless($this->previewItem->isOfficiallyApproved(), 403, 'Slip gaji belum disetujui Direktur.');
        }

        $this->dispatch('open-modal', ['id' => 'previewSlipModal']);
    }

    public function getAvailableYearsProperty(): array
    {
        $years = PayrollPeriod::query()
            ->distinct()
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->sortDesc()
            ->values()
            ->all();

        $currentYear = (int) now()->format('Y');
        if (empty($years)) {
            $years = [$currentYear];
        }

        if (! in_array($currentYear, $years, true)) {
            $years[] = $currentYear;
            rsort($years);
        }

        return $years;
    }

    /**
     * Determine if a payroll item is officially approved by Direktur (approved or paid).
     */
    public function isApprovedItem(?PayrollItem $item): bool
    {
        return $item?->isOfficiallyApproved() ?? false;
    }

    /**
     * Generate WhatsApp click-to-chat link for an employee's payslip.
     */
    public function getWhatsappLink(?PayrollItem $item): string
    {
        if (! $item || ! $item->employee) {
            return '#';
        }

        $phone = $item->employee->detail?->phone ?? '';
        $cleanedPhone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($cleanedPhone, '0')) {
            $cleanedPhone = '62'.substr($cleanedPhone, 1);
        }

        $company = CompanyProfile::getProfile();
        $periodLabel = $item->payrollPeriod?->period_label ?? 'Bulan Ini';
        $message = "Halo {$item->employee->full_name},\n\n";
        $message .= "Berikut adalah ringkasan slip gaji Anda untuk {$periodLabel} dari {$company->name}:\n";
        $message .= "• No Slip: {$item->payslip_number}\n";
        $message .= "• Gaji Pokok: {$item->formatted_basic_salary}\n";
        $message .= "• Total Tunjangan (+): {$item->formatted_total_allowances}\n";
        $message .= "• Total Potongan (-): {$item->formatted_total_deductions}\n";
        $message .= "• Gaji Bersih (Take Home Pay): {$item->formatted_net_salary}\n\n";
        $message .= 'Status Pembayaran: '.($item->payment_status === 'paid' ? 'LUNAS / DIBAYAR' : 'PROSES')."\n";
        $message .= 'Terima kasih atas kerja keras Anda!';

        return 'https://wa.me/'.$cleanedPhone.'?text='.rawurlencode($message);
    }

    public function getMonthlySalaryDataProperty(): array
    {
        $items = PayrollItem::with('payrollPeriod')
            ->where('employee_id', $this->employee->id)
            ->whereHas('payrollPeriod', function ($q) {
                $q->where('year', $this->chartYear);
            })
            ->get();

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        // Hanya slip gaji yang SUDAH DISETUJUI / DIBAYAR DIREKTUR yang masuk ke grafik penerimaan
        $approvedMonthlyItems = [];
        foreach ($items as $item) {
            if ($item->payrollPeriod && $this->isApprovedItem($item)) {
                $approvedMonthlyItems[(int) $item->payrollPeriod->month] = $item;
            }
        }

        $labels = [];
        $netSalaries = [];
        $grossSalaries = [];
        $deductions = [];

        for ($m = 1; $m <= 12; $m++) {
            $labels[] = $monthNames[$m];
            if (isset($approvedMonthlyItems[$m])) {
                $item = $approvedMonthlyItems[$m];
                $netSalaries[] = (float) $item->net_salary;
                $grossSalaries[] = (float) $item->gross_salary;
                $deductions[] = (float) $item->total_deductions;
            } else {
                $netSalaries[] = 0;
                $grossSalaries[] = 0;
                $deductions[] = 0;
            }
        }

        // Metrik penerimaan resmi (Total Diterima, Rata-rata, Gaji Tertinggi, Slip Terbit)
        $approvedItems = $items->filter(fn ($it) => $this->isApprovedItem($it) && (float) $it->net_salary > 0);
        $totalNet = (float) $approvedItems->sum('net_salary');
        $count = $approvedItems->count();
        $avgNet = $count > 0 ? (int) round($totalNet / $count) : 0;

        $maxItem = $approvedItems->sortByDesc('net_salary')->first();
        $maxNet = $maxItem ? (float) $maxItem->net_salary : 0;
        $maxMonth = $maxItem && $maxItem->payrollPeriod ? $monthNames[(int) $maxItem->payrollPeriod->month] : '-';

        // Untuk tabel: role karyawan hanya melihat slip yang sudah disetujui, admin/direktur melihat semua dengan statusnya
        $isKaryawan = auth()->user()?->hasRole('karyawan');
        $displayItems = $isKaryawan
            ? $items->filter(fn ($it) => $this->isApprovedItem($it))
            : $items;

        return [
            'labels' => $labels,
            'net_salaries' => $netSalaries,
            'gross_salaries' => $grossSalaries,
            'deductions' => $deductions,
            'total_net' => $totalNet,
            'formatted_total_net' => 'Rp '.number_format($totalNet, 0, ',', '.'),
            'avg_net' => $avgNet,
            'formatted_avg_net' => 'Rp '.number_format($avgNet, 0, ',', '.'),
            'max_net' => $maxNet,
            'formatted_max_net' => 'Rp '.number_format($maxNet, 0, ',', '.'),
            'max_month' => $maxMonth,
            'count' => $count,
            'has_unapproved_items' => $items->contains(fn ($it) => ! $this->isApprovedItem($it)),
            'items' => $displayItems->sortBy(fn ($it) => $it->payrollPeriod?->month ?? 0)->values(),
        ];
    }

    public function render(): View
    {
        if ($this->activeTab === 'riwayat-gaji') {
            $this->employee->loadMissing(['salaryHistories.changer']);
        }

        return view('livewire.employees.show', [
            'salaryAdjustmentTypes' => collect(SalaryAdjustmentType::cases())
                ->filter(fn ($type) => $type !== SalaryAdjustmentType::INITIAL)
                ->values(),
            'histories' => $this->employee->salaryHistories,
            'progressionStatus' => $this->employee->salary_progression_status,
            'company' => CompanyProfile::getProfile(),
        ])->title(auth()->user()?->hasRole('karyawan') ? 'Profil Saya' : 'Detail Profil Karyawan');
    }
}
