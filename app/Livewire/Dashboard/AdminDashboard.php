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

    public function render(DashboardService $dashboardService): View
    {
        $stats = $dashboardService->getAdminStatistics($this->selectedYear, $this->selectedMonth);

        return view('livewire.dashboard.admin-dashboard', [
            'stats' => $stats,
            'monthsList' => $stats['months_list'],
            'availableYears' => $stats['available_years'],
            'isDirector' => auth()->user()?->hasRole('direktur') ?? false,
        ]);
    }
}
