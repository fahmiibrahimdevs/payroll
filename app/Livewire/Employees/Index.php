<?php

namespace App\Livewire\Employees;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Livewire\Forms\EmployeeForm;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\EmployeeService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Kelola Karyawan')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public EmployeeForm $form;

    #[Url(as: 'view')]
    public string $viewMode = 'table';

    public string $search = '';

    public ?int $filterDepartment = null;

    public ?int $filterPosition = null;

    public ?string $filterStatus = null;

    public ?string $filterEmploymentStatus = null;

    public int $perPage = 20;

    public bool $isEditMode = false;

    public ?int $editingEmployeeId = null;

    public bool $isEmailManuallyModified = false;

    public string $companyEmailDomain = '';

    public ?Employee $selectedEmployee = null;

    public function mount(): void
    {
        $this->companyEmailDomain = CompanyProfile::getProfile()->getEmailDomain();

        $user = auth()->user();
        if ($user?->hasRole('karyawan')) {
            $emp = $user->getEmployeeRecord();
            if ($emp) {
                $this->redirect(route('employees.show', $emp->id), navigate: true);

                return;
            }

            abort(403, 'Akun Anda belum terhubung dengan data profil karyawan.');
        }

        abort_unless($user?->hasRole('admin') || $user?->hasRole('direktur'), 403);

        if (session()->has('employee_view_mode') && ! request()->has('view')) {
            $this->viewMode = session('employee_view_mode');
        }

        $editHandoff = session()->pull('employee_edit_handoff');
        if (is_array($editHandoff) && isset($editHandoff['id']) && $user?->hasRole('admin')) {
            $this->edit((int) $editHandoff['id'], $editHandoff['section'] ?? null);
        }
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['card', 'table'], true)) {
            $this->viewMode = $mode;
            session(['employee_view_mode' => $mode]);
        }
    }

    /**
     * Reset pagination when search or filters change.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDepartment(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPosition(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEmploymentStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Reset all filters to default.
     */
    public function resetFilters(): void
    {
        $this->reset(['filterDepartment', 'filterPosition', 'filterStatus', 'filterEmploymentStatus', 'search']);
        $this->resetPage();
        $this->dispatch('reset-employee-filters');
    }

    /**
     * Auto generate email when employee full name is modified in create mode.
     */
    public function updatedFormFullName(string $value): void
    {
        $this->autoGenerateEmail($value);
    }

    public function updatedFormEmail(string $value): void
    {
        $this->isEmailManuallyModified = true;
        $this->form->email_username = Str::before($value, '@');
    }

    public function updatedFormEmailUsername(): void
    {
        $this->isEmailManuallyModified = true;
        $domain = $this->companyEmailDomain ?: CompanyProfile::getProfile()->getEmailDomain();
        $cleanUsername = ltrim(trim(Str::before($this->form->email_username, '@')));
        $this->form->email = $cleanUsername !== '' ? strtolower($cleanUsername.'@'.$domain) : '';
    }

    public function updated(string $property, mixed $value): void
    {
        if ($property === 'form.full_name') {
            $this->autoGenerateEmail((string) $value);
        } elseif ($property === 'form.email' || $property === 'form.email_username') {
            $this->isEmailManuallyModified = true;
        }
    }

    protected function autoGenerateEmail(string $name): void
    {
        if (! $this->isEmailManuallyModified && (! $this->isEditMode || empty($this->form->email_username))) {
            if (! empty(trim($name))) {
                $this->form->email_username = CompanyProfile::generateEmployeeUsername($name);
                $domain = $this->companyEmailDomain ?: CompanyProfile::getProfile()->getEmailDomain();
                $this->form->email = strtolower($this->form->email_username.'@'.$domain);
            }
        }
    }

    /**
     * Explicitly generate email from full name using company profile email domain.
     */
    public function generateEmailFromName(): void
    {
        if (! empty(trim($this->form->full_name))) {
            $this->form->email_username = CompanyProfile::generateEmployeeUsername($this->form->full_name);
            $domain = $this->companyEmailDomain ?: CompanyProfile::getProfile()->getEmailDomain();
            $this->form->email = strtolower($this->form->email_username.'@'.$domain);
            $this->isEmailManuallyModified = false;
        }
    }

    /**
     * Open modal for creating a new employee.
     */
    public function create(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        $this->isEditMode = false;
        $this->editingEmployeeId = null;
        $this->isEmailManuallyModified = false;
        $this->companyEmailDomain = CompanyProfile::getProfile()->getEmailDomain();
        $this->form->resetForm();
        $this->dispatch('open-modal', [
            'id' => 'employeeModal',
            'department_id' => '',
            'position_id' => '',
            'employment_status' => $this->form->employment_status,
            'status' => $this->form->status,
            'gender' => $this->form->gender,
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(EmployeeService $employeeService): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        try {
            $employee = $this->form->store($employeeService);
            $this->resetPage('expiringPage');
            $this->dispatch('close-modal', id: 'employeeModal');
            $roleName = $employee->user?->roles->first()?->name ?? 'karyawan';
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Data karyawan ditambahkan. Akun login: '.($employee->detail?->email ?? '-').' (Role: '.ucfirst($roleName).') dengan password awal 1.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            if (app()->environment('testing')) {
                throw $e;
            }
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan!',
                'message' => 'Terjadi kesalahan sistem saat menyimpan data karyawan.',
            ]);
        }
    }

    /**
     * Open modal and populate form for editing an existing employee.
     */
    public function edit(int $id, ?string $section = null): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        try {
            $employee = Employee::with(['department', 'position', 'detail', 'user.roles'])->findOrFail($id);
            $this->isEditMode = true;
            $this->editingEmployeeId = $id;
            $this->isEmailManuallyModified = true;
            $this->companyEmailDomain = CompanyProfile::getProfile()->getEmailDomain();
            $this->form->setEmployee($employee);
            $this->dispatch('open-modal', [
                'id' => 'employeeModal',
                'department_id' => $this->form->department_id ? (string) $this->form->department_id : '',
                'position_id' => $this->form->position_id ? (string) $this->form->position_id : '',
                'employment_status' => $this->form->employment_status,
                'status' => $this->form->status,
                'gender' => $this->form->gender,
                'section' => in_array($section, ['kepegawaian', 'pribadi', 'pajak'], true) ? $section : 'kepegawaian',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data karyawan tidak ditemukan.',
            ]);
        }
    }

    /**
     * Close modal and reset form state.
     */
    public function closeModal(): void
    {
        $this->isEditMode = false;
        $this->editingEmployeeId = null;
        $this->isEmailManuallyModified = false;
        $this->form->resetForm();
    }

    /**
     * Update an existing employee.
     */
    public function update(EmployeeService $employeeService): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        try {
            $this->form->update($employeeService);
            $this->resetPage('expiringPage');
            $this->isEditMode = false;
            $this->editingEmployeeId = null;
            $this->dispatch('close-modal', id: 'employeeModal');
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Data karyawan berhasil diperbarui!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Memperbarui!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui data karyawan.',
            ]);
        }
    }

    public function confirmToggleAccountStatus(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $employee = Employee::with('user.roles')->findOrFail($id);
        if (! $employee->user) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Akun Belum Tersedia',
                'message' => 'Akun karyawan ini belum dibuat atau belum terhubung.',
            ]);

            return;
        }

        if ($employee->user->id === auth()->id()) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Aksi Ditolak',
                'message' => 'Anda tidak dapat menonaktifkan akun yang sedang digunakan untuk login.',
            ]);

            return;
        }

        $isActive = $employee->user->is_active;
        $this->dispatch('swal:confirm', [
            'title' => $isActive ? 'Nonaktifkan akun?' : 'Aktifkan akun?',
            'text' => $isActive
                ? "{$employee->full_name} tidak dapat login setelah akun dinonaktifkan."
                : "{$employee->full_name} dapat login setelah akun diaktifkan.",
            'icon' => 'warning',
            'confirmButtonText' => $isActive ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan',
            'confirmButtonColor' => $isActive ? '#ffa426' : '#47c363',
            'action' => 'toggle-employee-account-status',
            'params' => ['id' => $employee->id],
        ]);
    }

    #[On('toggle-employee-account-status')]
    public function toggleEmployeeAccountStatus(int $id, UserService $userService): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $employee = Employee::with('user.roles')->findOrFail($id);
        if (! $employee->user) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Akun Belum Tersedia',
                'message' => 'Akun karyawan ini belum dibuat atau belum terhubung.',
            ]);

            return;
        }

        if ($employee->user->id === auth()->id()) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Aksi Ditolak',
                'message' => 'Anda tidak dapat menonaktifkan akun yang sedang digunakan untuk login.',
            ]);

            return;
        }

        try {
            $user = $userService->toggleUserStatus($employee->user->id, (int) auth()->id());
            $status = $user->is_active ? 'diaktifkan.' : 'dinonaktifkan.';
            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Status Akun Diperbarui',
                'message' => "Akun {$employee->full_name} berhasil {$status}",
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mengubah Status',
                'message' => 'Status akun karyawan tidak dapat diperbarui.',
            ]);
        }
    }

    /**
     * Show detail profile modal for an employee.
     */
    public function showDetail(int $id): void
    {
        try {
            $isAdmin = auth()->user()?->hasRole('admin');
            $isPrivileged = auth()->user()?->hasRole(['admin', 'direktur']);
            $detailColumns = $isPrivileged
                ? [
                    'employee_id', 'employee_code', 'gender', 'birth_place', 'birth_date',
                    'address', 'phone', 'email', 'bank_name', 'bank_account_number',
                    'bank_account_name', 'curriculum_vitae', 'marital_status', 'dependents_count',
                    'ptkp_status', 'tax_method', 'tax_recipient_type', 'has_npwp', 'npwp_number', 'nik',
                    'bpjs_kesehatan_number', 'bpjs_ketenagakerjaan_number',
                ]
                : ['employee_id', 'employee_code', 'gender', 'birth_place', 'birth_date', 'phone', 'email', 'address'];

            $employee = Employee::with([
                'department:id,name',
                'position:id,name',
                'detail:'.implode(',', $detailColumns),
            ])->findOrFail($id);

            if (! $isAdmin) {
                $employee->makeHidden(['basic_salary', 'total_cash_receipt']);
            }

            $this->selectedEmployee = $employee;
            $this->dispatch('open-modal', id: 'employeeDetailModal');
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data detail karyawan tidak ditemukan.',
            ]);
        }
    }

    /**
     * Trigger SweetAlert2 delete confirmation dialog.
     */
    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        $employee = Employee::find($id);

        if (! $employee) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Data karyawan tidak ditemukan.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $id,
            'title' => $employee->full_name,
            'action' => 'delete-employee',
        ]);
    }

    /**
     * Delete an employee after confirmation.
     */
    #[On('delete-employee')]
    public function delete(int $id, EmployeeService $employeeService): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        try {
            $employee = Employee::with('detail')->findOrFail($id);
            $employeeService->deleteEmployee($employee);
            $this->resetPage('expiringPage');

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Data karyawan berhasil diarsipkan!',
            ]);
        } catch (Throwable) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menghapus!',
                'message' => 'Terjadi kesalahan sistem saat menghapus data karyawan.',
            ]);
        }
    }

    /**
     * Render the Livewire component view.
     */
    public function render(EmployeeService $employeeService): View
    {
        $employees = $employeeService->getPaginatedEmployees(
            search: $this->search,
            departmentId: $this->filterDepartment,
            positionId: $this->filterPosition,
            status: $this->filterStatus,
            employmentStatus: $this->filterEmploymentStatus,
            perPage: $this->perPage,
            includeSensitive: auth()->user()?->hasRole('admin') ?? false,
        );

        $dashboardData = $employeeService->getEmployeeDashboardData();

        return view('livewire.employees.index', [
            'employees' => $employees,
            'departments' => Department::getLookup(),
            'positions' => Position::getLookup(),
            'statuses' => EmployeeStatus::cases(),
            'employmentStatuses' => EmploymentStatus::cases(),
            'genders' => Gender::cases(),
            'employeeStats' => $dashboardData['stats'],
            'expiringEmployees' => $dashboardData['expiring_employees'],
            'employeeCharts' => $dashboardData['charts'],
            'companyEmailDomain' => $this->companyEmailDomain ?: CompanyProfile::getProfile()->getEmailDomain(),
        ]);
    }
}
