<?php

namespace App\Livewire\Logs;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Monitoring Log Aktivitas')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $startDate = '';

    public string $endDate = '';

    public string $search = '';

    public string $filterLevel = '';

    public string $filterModule = '';

    public int $perPage = 10;

    public ?int $selectedLogId = null;

    public string $activePreset = 'today';

    /**
     * Initialize default filters (Default: Hari Ini).
     */
    public function mount(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $this->startDate = $today;
        $this->endDate = $today;
        $this->activePreset = 'today';
    }

    /**
     * Quick preset selector for date ranges.
     */
    public function setPreset(string $preset): void
    {
        $this->activePreset = $preset;
        $today = Carbon::today();

        match ($preset) {
            'today' => [
                $this->startDate = $today->format('Y-m-d'),
                $this->endDate = $today->format('Y-m-d'),
            ],
            'yesterday' => [
                $this->startDate = $today->copy()->subDay()->format('Y-m-d'),
                $this->endDate = $today->copy()->subDay()->format('Y-m-d'),
            ],
            '7days' => [
                $this->startDate = $today->copy()->subDays(6)->format('Y-m-d'),
                $this->endDate = $today->format('Y-m-d'),
            ],
            'this_month' => [
                $this->startDate = $today->copy()->startOfMonth()->format('Y-m-d'),
                $this->endDate = $today->copy()->endOfMonth()->format('Y-m-d'),
            ],
            'all' => [
                $this->startDate = '',
                $this->endDate = '',
            ],
            default => null,
        };

        $this->resetPage();
        $this->dispatch('sync-log-preset', preset: $preset);
    }

    /**
     * Reset pagination when filters change.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->activePreset = 'custom';
        $this->resetPage();
        $this->dispatch('sync-log-preset', preset: 'custom');
    }

    public function updatingEndDate(): void
    {
        $this->activePreset = 'custom';
        $this->resetPage();
        $this->dispatch('sync-log-preset', preset: 'custom');
    }

    public function updatingFilterLevel(): void
    {
        $this->resetPage();
    }

    public function updatingFilterModule(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Reset all filters to default today.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterLevel = '';
        $this->filterModule = '';
        $this->perPage = 10;
        $this->setPreset('today');
        $this->dispatch('reset-log-filters');
    }

    /**
     * Toggle or set level filter.
     */
    public function setLevel(string $level): void
    {
        $this->filterLevel = $this->filterLevel === $level ? '' : $level;
        $this->resetPage();
    }

    /**
     * Toggle or set module filter.
     */
    public function setModule(string $module): void
    {
        $this->filterModule = $this->filterModule === $module ? '' : $module;
        $this->resetPage();
    }

    /**
     * Show detail context modal for an activity log.
     */
    public function showDetail(int $id): void
    {
        $this->selectedLogId = $id;
        $this->dispatch('open-modal', id: 'logDetailModal');
    }

    /**
     * Render the Livewire component.
     */
    public function render(ActivityLogService $logService): View
    {
        $logs = $logService->getPaginatedLogs(
            search: $this->search,
            startDate: $this->startDate,
            endDate: $this->endDate,
            level: $this->filterLevel,
            module: $this->filterModule,
            perPage: $this->perPage
        );

        $statistics = $logService->getLogStatistics(
            startDate: $this->startDate,
            endDate: $this->endDate
        );

        $modules = $logService->getDistinctModules();

        $selectedLog = $this->selectedLogId ? ActivityLog::with('user')->find($this->selectedLogId) : null;

        return view('livewire.logs.index', [
            'logs' => $logs,
            'statistics' => $statistics,
            'modules' => $modules,
            'selectedLog' => $selectedLog,
        ]);
    }
}
