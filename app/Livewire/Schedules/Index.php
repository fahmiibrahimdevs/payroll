<?php

namespace App\Livewire\Schedules;

use App\Models\CompanyProfile;
use App\Models\Department;
use App\Services\WorkScheduleService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    use WithFileUploads;

    public ?int $year = null;

    public ?int $month = null;

    public ?int $departmentId = null;

    public string $search = '';

    public string $sortBy = 'custom';

    public int $perPage = 0; // 0 = Semua Karyawan

    public array $customEmployeeOrder = [];

    public array $employeeColors = [];

    public ?int $selectedEmployeeForColor = null;

    public ?string $selectedEmployeeName = null;

    public bool $showImportModal = false;

    public $importFile = null;

    protected $queryString = [
        'year' => ['except' => null],
        'month' => ['except' => null],
        'departmentId' => ['except' => null],
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'custom'],
        'perPage' => ['except' => 0],
    ];

    public function mount(): void
    {
        $now = Carbon::now();
        $this->year = (int) ($this->year ?: $now->year);
        $this->month = (int) ($this->month ?: $now->month);
        $this->customEmployeeOrder = cache()->get('roster_employee_order', []);
        $this->employeeColors = cache()->get('roster_employee_colors', []);
    }

    public function prevMonth(): void
    {
        $c = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year = (int) $c->year;
        $this->month = (int) $c->month;
    }

    public function nextMonth(): void
    {
        $c = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year = (int) $c->year;
        $this->month = (int) $c->month;
    }

    public function currentMonth(): void
    {
        $now = Carbon::now();
        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
    }

    /**
     * Membuka modal pemilih warna untuk karyawan tertentu.
     */
    public function openColorModal(int $employeeId, string $employeeName): void
    {
        $this->selectedEmployeeForColor = $employeeId;
        $this->selectedEmployeeName = $employeeName;
    }

    public function closeColorModal(): void
    {
        $this->selectedEmployeeForColor = null;
        $this->selectedEmployeeName = null;
    }

    /**
     * Mengatur warna background kolom nama & jobdesk untuk karyawan tertentu.
     */
    public function setEmployeeColor(int $employeeId, string $color): void
    {
        if (! auth()->user()?->hasRole('admin')) {
            return;
        }

        if ($color === 'none' || empty($color)) {
            unset($this->employeeColors[$employeeId]);
        } else {
            $this->employeeColors[$employeeId] = $color;
        }

        cache()->put('roster_employee_colors', $this->employeeColors, now()->addYears(2));
        $this->selectedEmployeeForColor = null;
        $this->selectedEmployeeName = null;
    }

    /**
     * Memperbarui urutan karyawan dari hasil drag & drop.
     */
    public function reorderEmployees(array $orderedIds): void
    {
        if (! auth()->user()?->hasRole('admin')) {
            return;
        }

        $this->customEmployeeOrder = array_values(array_map('intval', $orderedIds));
        $this->sortBy = 'custom';
        cache()->put('roster_employee_order', $this->customEmployeeOrder, now()->addYears(2));
    }

    /**
     * Memindahkan urutan karyawan (geser ke atas / bawah via tombol).
     */
    public function moveEmployee(int $employeeId, string $direction, WorkScheduleService $service): void
    {
        if (! auth()->user()?->hasRole('admin')) {
            return;
        }

        $roster = $service->getMonthlyRoster($this->year, $this->month, $this->departmentId, $this->search);
        $orderedEmployees = $this->sortEmployeeCollection($roster['employees']);
        $currentOrder = $orderedEmployees->pluck('id')->values()->all();

        $index = array_search($employeeId, $currentOrder, true);
        if ($index === false) {
            return;
        }

        if ($direction === 'up' && $index > 0) {
            $prev = $currentOrder[$index - 1];
            $currentOrder[$index - 1] = $employeeId;
            $currentOrder[$index] = $prev;
        } elseif ($direction === 'down' && $index < count($currentOrder) - 1) {
            $next = $currentOrder[$index + 1];
            $currentOrder[$index + 1] = $employeeId;
            $currentOrder[$index] = $next;
        }

        $this->customEmployeeOrder = $currentOrder;
        $this->sortBy = 'custom';
        cache()->put('roster_employee_order', $currentOrder, now()->addYears(2));
    }

    /**
     * Toggle status hari kerja/libur (work <-> off) untuk karyawan pada tanggal tertentu.
     */
    public function toggleDay(int $employeeId, string $date, WorkScheduleService $service): void
    {
        if (! auth()->user()?->hasRole('admin')) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Akses Ditolak',
                'message' => 'Hanya Admin yang dapat mengubah jadwal kerja.',
            ]);

            return;
        }

        try {
            $schedule = $service->toggleSchedule($employeeId, $date, auth()->user());

            $this->dispatch('schedule-toggled', [
                'employee_id' => $employeeId,
                'date' => $date,
                'status' => $schedule->status,
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mengubah Jadwal',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Salin pola jadwal kerja dari bulan sebelumnya.
     */
    public function copyFromPreviousMonth(WorkScheduleService $service): void
    {
        if (! auth()->user()?->hasRole('admin')) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Akses Ditolak',
                'message' => 'Hanya Admin yang dapat menyalin jadwal kerja.',
            ]);

            return;
        }

        try {
            $copied = $service->copyScheduleFromPreviousMonth($this->year, $this->month, auth()->user());

            if ($copied > 0) {
                $this->dispatch('swal:alert', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => "Berhasil menyalin {$copied} jadwal kerja dari bulan sebelumnya.",
                ]);
            } else {
                $this->dispatch('swal:alert', [
                    'type' => 'info',
                    'title' => 'Tidak Ada Data',
                    'message' => 'Tidak ditemukan jadwal pada bulan sebelumnya untuk disalin.',
                ]);
            }
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyalin Jadwal',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function openImportModal(): void
    {
        $this->resetErrorBag();
        $this->importFile = null;
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
    }

    /**
     * Import jadwal dari file Excel.
     */
    public function processImport(WorkScheduleService $service): void
    {
        if (! auth()->user()?->hasRole('admin')) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Akses Ditolak',
                'message' => 'Hanya Admin yang dapat mengimpor jadwal.',
            ]);

            return;
        }

        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'importFile.required' => 'Pilih file Excel jadwal terlebih dahulu.',
            'importFile.mimes' => 'Format file harus berupa .xlsx atau .xls.',
            'importFile.max' => 'Ukuran file maksimal 10MB.',
        ]);

        try {
            $result = $service->importRosterFromExcel($this->importFile, $this->year, $this->month, auth()->user());

            $this->closeImportModal();

            $msg = "Berhasil mengimpor {$result['records_saved']} jadwal untuk {$result['matched_employees']} karyawan.";
            if (! empty($result['unmatched_names'])) {
                $unmatchedList = implode(', ', array_slice($result['unmatched_names'], 0, 5));
                $msg .= " (Karyawan tidak cocok: {$unmatchedList})";
            }

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Impor Berhasil!',
                'message' => $msg,
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Impor Jadwal',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Export jadwal bulan berjalan ke format Excel (.xlsx).
     */
    public function exportExcel(WorkScheduleService $service): StreamedResponse
    {
        $spreadsheet = $service->exportRosterToExcel($this->year, $this->month);
        $monthName = Carbon::createFromDate($this->year, $this->month, 1)->format('Y-m');
        $fileName = "Jadwal_Kerja_Roster_{$monthName}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function sortEmployeeCollection(Collection $employees): Collection
    {
        if ($this->sortBy === 'name_asc') {
            return $employees->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }
        if ($this->sortBy === 'name_desc') {
            return $employees->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }
        if ($this->sortBy === 'position_asc') {
            return $employees->sortBy('jobdesk', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }
        if ($this->sortBy === 'department_asc') {
            return $employees->sortBy('department', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        // Custom order fallback
        if (! empty($this->customEmployeeOrder)) {
            $orderMap = array_flip($this->customEmployeeOrder);

            return $employees->sortBy(function ($emp) use ($orderMap) {
                return $orderMap[$emp['id']] ?? 999999;
            })->values();
        }

        return $employees->values();
    }

    public function render(WorkScheduleService $service): View
    {
        $year = (int) ($this->year ?: now()->year);
        $month = (int) ($this->month ?: now()->month);
        $this->year = $year;
        $this->month = $month;

        $roster = $service->getMonthlyRoster($year, $month, $this->departmentId, $this->search);
        $sortedEmployees = $this->sortEmployeeCollection($roster['employees']);

        if ($this->perPage > 0) {
            $sortedEmployees = $sortedEmployees->take($this->perPage);
        }

        // Sisipkan info warna latar per karyawan
        $roster['employees'] = $sortedEmployees->map(function ($emp) {
            $emp['color'] = $this->employeeColors[$emp['id']] ?? 'none';

            return $emp;
        });

        $departments = Department::orderBy('name')->get();
        $company = CompanyProfile::getProfile();

        $monthName = Carbon::createFromDate($year, $month, 1)
            ->locale('id')
            ->translatedFormat('F Y');

        return view('livewire.schedules.index', [
            'roster' => $roster,
            'departments' => $departments,
            'company' => $company,
            'monthName' => $monthName,
            'showImportModal' => $this->showImportModal,
            'selectedEmployeeForColor' => $this->selectedEmployeeForColor,
            'selectedEmployeeName' => $this->selectedEmployeeName,
        ]);
    }
}
