<?php

use App\Http\Controllers\PayrollPrintController;
use App\Http\Controllers\SecureDocumentController;
use App\Livewire\CashAdvances\Index as CashAdvanceIndex;
use App\Livewire\Departments\Index as DepartmentIndex;
use App\Livewire\Employees\Index as EmployeeIndex;
use App\Livewire\Logs\Index as LogIndex;
use App\Livewire\Payrolls\ManageItem;
use App\Livewire\Payrolls\Show;
use App\Livewire\Positions\Index as PositionIndex;
use App\Livewire\Posts\Index;
use App\Livewire\SalaryComponents\Index as SalaryComponentIndex;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/login');

$dashboardMiddleware = ['auth', 'active'];

if (! app()->environment(['local', 'development', 'testing'])) {
    $dashboardMiddleware[] = 'verified';
}

$dashboardMiddleware[] = 'role:admin|direktur|karyawan';

Route::get('dashboard', function (DashboardService $dashboardService): View {
    return view('dashboard', ['statistics' => $dashboardService->getStatistics()]);
})
    ->middleware($dashboardMiddleware)
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'active'])
    ->name('profile');

Route::get('departments', DepartmentIndex::class)
    ->middleware(['auth', 'active', 'role:admin|direktur'])
    ->name('departments.index');

Route::get('positions', PositionIndex::class)
    ->middleware(['auth', 'active', 'role:admin|direktur'])
    ->name('positions.index');

Route::get('employees', EmployeeIndex::class)
    ->middleware(['auth', 'active', 'role:admin|direktur|karyawan'])
    ->name('employees.index');

Route::get('employees/{employee}', App\Livewire\Employees\Show::class)
    ->middleware(['auth', 'active', 'role:admin|direktur|karyawan'])
    ->name('employees.show');

Route::get('employees/{employee}/documents/cv', [SecureDocumentController::class, 'employeeCv'])
    ->middleware(['auth', 'active', 'role:admin|karyawan'])
    ->name('employees.documents.cv');

Route::get('salary-components', SalaryComponentIndex::class)
    ->middleware(['auth', 'active', 'role:admin|direktur'])
    ->name('salary-components.index');

Route::get('cash-advances', CashAdvanceIndex::class)
    ->middleware(['auth', 'active', 'role:admin|direktur|karyawan'])
    ->name('cash-advances.index');

Route::get('cash-advances/payments/{payment}/proof', [SecureDocumentController::class, 'paymentProof'])
    ->middleware(['auth', 'active', 'role:admin|direktur|karyawan'])
    ->name('cash-advances.documents.proof');

Route::get('payrolls', App\Livewire\Payrolls\Index::class)
    ->middleware(['auth', 'active', 'role:admin|direktur'])
    ->name('payrolls.index');

Route::get('payrolls/{period}', Show::class)
    ->middleware(['auth', 'active', 'role:admin|direktur'])
    ->name('payrolls.show');

Route::get('payrolls/{period}/items/{item}/edit', ManageItem::class)
    ->middleware(['auth', 'active', 'role:admin|direktur'])
    ->name('payrolls.items.edit');

Route::get('payrolls/{period}/items/{item}/print', [PayrollPrintController::class, 'printSlip'])
    ->middleware(['auth', 'active', 'role:admin|direktur|karyawan'])
    ->name('payrolls.items.print');

Route::get('payrolls/yearly-summary/{year}/print', [PayrollPrintController::class, 'printYearlySummary'])
    ->middleware(['auth', 'active', 'role:admin|direktur'])
    ->name('payrolls.yearly-summary.print');

Route::get('company-profile', App\Livewire\CompanyProfile\Index::class)
    ->middleware(['auth', 'active', 'role:admin|direktur'])
    ->name('company-profile.index');

Route::get('posts', Index::class)
    ->middleware(['auth', 'active', 'role:admin'])
    ->name('posts.index');

Route::get('logs', LogIndex::class)
    ->middleware(['auth', 'active', 'role:admin|developer'])
    ->name('logs.index');

Route::middleware(['auth', 'active', 'role:admin'])->group(function () {
    Volt::route('admin/users', 'pages.admin.user-index')
        ->name('admin.users');
});

Route::get('shizuefi/{page?}', function (string $page = 'index') {
    if (! view()->exists("shizuefi.{$page}")) {
        abort(404);
    }

    return view("shizuefi.{$page}");
})->middleware(['auth', 'active', 'role:admin'])->name('shizuefi.page');

require __DIR__.'/auth.php';
