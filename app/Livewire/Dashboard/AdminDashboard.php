<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminDashboard extends Component
{
    public int $selectedYear;

    public int $selectedMonth;

    public function mount(): void
    {
        $this->selectedYear = (int) now()->format('Y');
        $this->selectedMonth = (int) now()->format('n');
    }

    public function updatedSelectedYear(): void
    {
        $this->dispatch('dashboard-updated');
    }

    public function updatedSelectedMonth(): void
    {
        $this->dispatch('dashboard-updated');
    }

    public function render(): View
    {
        $stats = $this->stats;

        return view('livewire.dashboard.admin-dashboard', [
            'stats' => $stats,
            'monthsList' => $stats['months_list'],
            'availableYears' => $stats['available_years'],
            'isDirector' => auth()->user()?->hasRole('direktur') ?? false,
        ]);
    }

    /**
     * Admin statistics for the selected period.
     */
    public function getStatsProperty(): array
    {
        return app(DashboardService::class)->getAdminStatistics($this->selectedYear, $this->selectedMonth);
    }

    /**
     * Ready-to-display department distribution (count + percentage per department).
     *
     * @return array<int, array{label: string, count: int, percentage: float|int, color: string}>
     */
    public function getDepartmentDistributionProperty(): array
    {
        $distribution = $this->stats['department_distribution'] ?? [];
        $total = $distribution['total'] ?? 0;
        $items = [];

        foreach ($distribution['labels'] ?? [] as $idx => $label) {
            $count = $distribution['counts'][$idx] ?? 0;
            $items[] = [
                'label' => $label,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                'color' => $distribution['colors'][$idx] ?? '#0b52aa',
            ];
        }

        return $items;
    }

    /**
     * Current payroll period with its verification percentage.
     *
     * @return array{period: mixed, verified_percentage: int}
     */
    public function getCurrentPeriodSummaryProperty(): array
    {
        $stats = $this->stats;

        return [
            'period' => $stats['current_payroll_period'],
            'verified_percentage' => $stats['payroll_total_items_count'] > 0
                ? round(($stats['payroll_verified_count'] / $stats['payroll_total_items_count']) * 100)
                : 0,
        ];
    }
}
