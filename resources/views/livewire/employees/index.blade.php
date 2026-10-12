<div>
    <x-slot name="header">
        <h1>Kelola Karyawan</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Master Data</div>
            <div class="breadcrumb-item">Karyawan</div>
        </div>
    </x-slot>

    <h2 class="section-title">Karyawan</h2>
    <p class="section-lead mb-3">
        Kelola seluruh data master pegawai, informasi kepegawaian, detail pribadi, hingga informasi payroll.
    </p>

    <!-- Main Content Row: 3 cols Filter Sidebar + 9 cols Data Table -->
    <div class="row align-items-start">
        <!-- Left Sidebar: Filter Panel (Desktop: 3 cols / col-lg-3 col-md-4 d-none d-md-block) -->
        <div class="col-lg-3 col-md-4 d-none d-md-block">
            <div class="card">
                <div class="card-header">
                    <h4>Filter Karyawan</h4>
                    @if ($search || $filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)
                        <div class="card-header-action">
                            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters" class="btn btn-sm btn-link text-danger font-weight-bold text-decoration-none p-0"><i wire:loading wire:target="resetFilters" class="fas fa-spinner fa-spin mr-1"></i><i wire:loading.remove wire:target="resetFilters" class="fas fa-undo mr-1"></i> Reset</button>
                        </div>
                    @endif
                </div>

                <div class="card-body">
                    @include('livewire.employees.filter-content', ['filterContext' => 'desktop'])
                </div>
            </div>
        </div>

        <!-- Right Side: Employee Data (Desktop: 9 cols / col-lg-9 col-md-8 col-12) -->
        <div class="col-lg-9 col-md-8 col-12">
            <!-- Unified Main Card (Matching Kasbon: Header, Search/Entries, Table, Footer in ONE Card) -->
            <div class="card cash-advance-main-card mb-0">
                <!-- Row 1: Header & Controls Card -->
                <div class="cash-advance-top-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
                        <h4 class="mb-0">Data Karyawan</h4>
                        @if (auth()->user()->hasRole('admin'))
                            <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn btn-sm btn-primary font-weight-bold shadow-sm" style="border-radius: 8px;">
                                <i wire:loading.remove wire:target="create" class="fas fa-plus mr-1.5"></i>
                                <i wire:loading wire:target="create" class="fas fa-spinner fa-spin mr-1.5"></i>
                                Tambah Karyawan
                            </button>
                        @endif
                    </div>

                    <!-- Controls: Show Entries & Search Bar -->
                    <div class="dt-control-wrapper">
                        <div class="dt-show-entries">
                            Show
                            <select wire:model.live="perPage" class="dt-select-entries">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="40">40</option>
                                <option value="80">80</option>
                                <option value="160">160</option>
                                <option value="200">200</option>
                            </select>
                            Entries
                        </div>

                        <div class="dt-search-wrapper">
                            <span class="mr-1">Search:</span>
                            <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Nama, kode, email, no hp...">
                        </div>

                        <!-- Mobile Filter Button (Under Search, Full Width, BG Primary, Tanpa Outline) -->
                        <div class="d-md-none w-100">
                            <button type="button" class="btn btn-primary btn-block shadow-none" data-toggle="modal" data-target="#employeeFilterMobileModal">
                                <i class="fas fa-filter mr-1.5"></i> Filter Karyawan 
                                @if ($filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)
                                    <span class="badge badge-light text-primary ml-1 py-0.5 px-1.5 text-xs font-weight-bold">Aktif</span>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Full-Width Data Table -->
                <div class="table-responsive">
                    <table class="table table-clean mb-0">
                        <thead>
                            <tr style="background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;">
                                <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 50px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">NO</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">KARYAWAN</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 140px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">STATUS KERJA</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 140px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">TANGGAL MASUK</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 130px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">SISA KONTRAK</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 100px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">STATUS AKUN</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 120px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">TELEPON</th>
                                <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 120px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $rowNumber = 0;
                                $groupedEmployees = $employees->groupBy(fn ($e) => $e->department?->name ?: 'Tanpa Department');
                                if ($groupedEmployees->has('Tanpa Department')) {
                                    $unassigned = $groupedEmployees->pull('Tanpa Department');
                                    $groupedEmployees->put('Tanpa Department', $unassigned);
                                }
                            @endphp

                            @forelse ($groupedEmployees as $departmentName => $deptEmployees)
                                @php
                                    $isOfficialDept = $departmentName !== 'Tanpa Department';
                                @endphp
                                <!-- Baris Sub-Header Group Departemen (Sesuai Desain Payroll) -->
                                <tr class="table-group-header" style="background-color: {{ $isOfficialDept ? '#f0f6ff' : '#fffdf5' }}; border-top: 1px solid {{ $isOfficialDept ? '#bfdbfe' : '#fde68a' }}; border-bottom: 1px solid {{ $isOfficialDept ? '#dbeafe' : '#fef3c7' }};">
                                    <td colspan="8" class="!py-2.5 !px-3.5" style="border-left: 4px solid {{ $isOfficialDept ? '#0b52aa' : '#f59e0b' }} !important;">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                                            <div class="d-flex align-items-center">
                                                <span class="d-inline-flex align-items-center justify-content-center mr-2" style="width: 26px; height: 26px; border-radius: 6px; background-color: {{ $isOfficialDept ? '#dbeafe' : '#fef3c7' }}; font-size: 12px; flex-shrink: 0;">
                                                    @if ($isOfficialDept)
                                                        <i class="fas fa-building" style="color: #0b52aa;"></i>
                                                    @else
                                                        <i class="fas fa-exclamation-triangle" style="color: #b45309;"></i>
                                                    @endif
                                                </span>
                                                <div>
                                                    <span class="font-weight-bold" style="font-size: 13px; letter-spacing: 0.3px; color: #0f172a;">
                                                        DEPARTEMEN {{ strtoupper($departmentName) }}
                                                    </span>
                                                    <span class="badge ml-2 font-weight-700" style="font-size: 11px; padding: 2.5px 8px; border-radius: 12px; background-color: {{ $isOfficialDept ? '#dbeafe' : '#fef3c7' }}; color: {{ $isOfficialDept ? '#1e40af' : '#92400e' }}; border: 1px solid {{ $isOfficialDept ? '#bfdbfe' : '#fde68a' }};">
                                                        {{ $deptEmployees->count() }} Karyawan
                                                    </span>
                                                    @if (! $isOfficialDept)
                                                        <span class="badge ml-1 font-weight-700" style="font-size: 10.5px; padding: 2.5px 7px; border-radius: 12px; background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                                            Perlu Dilengkapi
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                @foreach ($deptEmployees as $employee)
                                    @php $rowNumber++; @endphp
                                    <tr wire:key="employee-table-{{ $employee->id }}" class="payroll-table-row" style="border-bottom: 1px solid #f1f5f9;">
                                        <td class="text-center font-weight-700 align-middle !px-3 !py-2.5 text-nowrap" style="color: #334155; font-size: 12.5px;">
                                            {{ $employees->firstItem() + $rowNumber - 1 }}
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5">
                                            <div class="d-flex align-items-center text-nowrap">
                                                <img src="{{ $employee->photo_url }}" alt="{{ $employee->full_name }}" class="rounded-circle mr-2.5" style="width: 38px; height: 38px; object-fit: cover; border: 1.5px solid #cbd5e1; flex-shrink: 0;">
                                                <div class="text-nowrap">
                                                    <div class="font-weight-700 leading-tight" style="color: #0f172a; font-size: 13.5px;">{{ $employee->full_name }}</div>
                                                    <div class="leading-tight mt-1" style="font-size: 12px; color: #334155; font-weight: 600;">
                                                        {{ $employee->position?->name ?: 'Tanpa Jabatan' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5">
                                            <span class="{{ $employee->employment_status_badge_class }} text-xs font-weight-600">
                                                {{ $employee->employment_status_label }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5">
                                            <div class="font-weight-600" style="color: #1e293b; font-size: 12.5px;">{{ $employee->join_date ? $employee->join_date->format('d M Y') : '-' }}</div>
                                            @if ($employee->join_date)
                                                <span class="badge font-weight-700 px-2.5 py-1 mt-0.5" style="font-size: 11px; border-radius: 6px; background-color: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1;">
                                                    {{ $employee->tenure_label }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5" style="font-size: 12.5px; color: #1e293b; font-weight: 600;">
                                            {{ $employee->remaining_contract_label }}
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5">
                                            <span class="{{ $employee->status_badge_class }} text-xs font-weight-600">
                                                {{ $employee->status_label }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5" style="font-size: 12.5px; color: #1e293b; font-weight: 600;">
                                            {{ $employee->detail?->phone ?: '-' }}
                                        </td>
                                        <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-info btn-action mr-1" title="Lihat Profil"><i class="fas fa-eye"></i></a>
                                            @if (auth()->user()->hasRole('admin'))
                                                <button type="button" wire:click="edit({{ $employee->id }})" wire:loading.attr="disabled" wire:target="edit({{ $employee->id }})" class="btn btn-primary btn-action mr-1" title="Edit Data"><i wire:loading wire:target="edit({{ $employee->id }})" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="edit({{ $employee->id }})" class="fas fa-pencil-alt"></i></button>
                                                <button type="button" wire:click="confirmDelete({{ $employee->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $employee->id }})" class="btn btn-danger btn-action" title="Hapus Data"><i wire:loading wire:target="confirmDelete({{ $employee->id }})" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="confirmDelete({{ $employee->id }})" class="fas fa-trash"></i></button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <div class="empty-state py-4">
                                            <div class="empty-state-icon bg-light text-muted mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <h5 class="font-weight-bold text-dark mb-1">Tidak Ada Data Karyawan</h5>
                                            <p class="text-muted small mb-0">
                                                @if ($search || $filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)
                                                    Tidak ada karyawan yang sesuai dengan kriteria filter pencarian.
                                                @else
                                                    Belum ada data master karyawan yang tersedia.
                                                @endif
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer: Info & Pagination (Inside the Same Card) -->
                @if ($employees && $employees->total() > 0)
                    <div class="dt-footer-wrapper">
                        <div class="dt-info">
                            Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} entries
                        </div>
                        <div class="dt-pagination">
                            {{ $employees->links('livewire.custom-pagination') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Floating Action Button (Tambah Data) -->
    @if (auth()->user()->hasRole('admin'))
        <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn-floating-add" title="Tambah Karyawan" aria-label="Tambah Karyawan"><i wire:loading wire:target="create" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="create" class="fas fa-plus"></i></button>
    @endif

    <!-- Modal Form (Add / Edit) -->
    @include('livewire.employees.form-modal')

    <!-- Modal Detail Profile -->
    @include('livewire.employees.detail-modal')

    <!-- Modal Filter (Mobile) -->
    @include('livewire.employees.filter-modal')

    @push('scripts')
        <script>
            (function () {
                function initEmployeeScripts() {
                    // Izinkan focus Select2 di dalam modal Bootstrap
                    if ($.fn.modal && $.fn.modal.Constructor) {
                        $.fn.modal.Constructor.prototype._enforceFocus = function () {};
                    }

                    const initSelect2 = () => {
                        // Form Modal: Departemen
                        if ($('#emp_department_id').length) {
                            $('#emp_department_id').select2({
                                dropdownParent: $('#employeeModal'),
                                width: '100%',
                                placeholder: '-- Pilih Departemen --',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('form.department_id', val ? parseInt(val) : null);
                            });
                        }

                        // Form Modal: Jabatan
                        if ($('#emp_position_id').length) {
                            $('#emp_position_id').select2({
                                dropdownParent: $('#employeeModal'),
                                width: '100%',
                                placeholder: '-- Pilih Jabatan --',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('form.position_id', val ? parseInt(val) : null);
                            });
                        }

                        // Sidebar Desktop: Filter Departemen
                        if ($('#filterDepartment_desktop').length) {
                            $('#filterDepartment_desktop').select2({
                                width: '100%',
                                placeholder: 'Semua Departemen',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterDepartment', val ? parseInt(val) : null);
                                if ($('#filterDepartment_mobile').length && $('#filterDepartment_mobile').val() !== val) {
                                    $('#filterDepartment_mobile').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Sidebar Desktop: Filter Jabatan
                        if ($('#filterPosition_desktop').length) {
                            $('#filterPosition_desktop').select2({
                                width: '100%',
                                placeholder: 'Semua Jabatan',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterPosition', val ? parseInt(val) : null);
                                if ($('#filterPosition_mobile').length && $('#filterPosition_mobile').val() !== val) {
                                    $('#filterPosition_mobile').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Sidebar Desktop: Filter Status Kepegawaian
                        if ($('#filterEmploymentStatus_desktop').length) {
                            $('#filterEmploymentStatus_desktop').select2({
                                width: '100%',
                                placeholder: 'Semua Status Kerja',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterEmploymentStatus', val || null);
                                if ($('#filterEmploymentStatus_mobile').length && $('#filterEmploymentStatus_mobile').val() !== val) {
                                    $('#filterEmploymentStatus_mobile').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Sidebar Desktop: Filter Status Akun
                        if ($('#filterStatus_desktop').length) {
                            $('#filterStatus_desktop').select2({
                                width: '100%',
                                placeholder: 'Semua Status Akun',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterStatus', val || null);
                                if ($('#filterStatus_mobile').length && $('#filterStatus_mobile').val() !== val) {
                                    $('#filterStatus_mobile').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Mobile Modal: Filter Departemen
                        if ($('#filterDepartment_mobile').length) {
                            $('#filterDepartment_mobile').select2({
                                dropdownParent: $('#employeeFilterMobileModal'),
                                width: '100%',
                                placeholder: 'Semua Departemen',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterDepartment', val ? parseInt(val) : null);
                                if ($('#filterDepartment_desktop').length && $('#filterDepartment_desktop').val() !== val) {
                                    $('#filterDepartment_desktop').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Mobile Modal: Filter Jabatan
                        if ($('#filterPosition_mobile').length) {
                            $('#filterPosition_mobile').select2({
                                dropdownParent: $('#employeeFilterMobileModal'),
                                width: '100%',
                                placeholder: 'Semua Jabatan',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterPosition', val ? parseInt(val) : null);
                                if ($('#filterPosition_desktop').length && $('#filterPosition_desktop').val() !== val) {
                                    $('#filterPosition_desktop').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Mobile Modal: Filter Status Kepegawaian
                        if ($('#filterEmploymentStatus_mobile').length) {
                            $('#filterEmploymentStatus_mobile').select2({
                                dropdownParent: $('#employeeFilterMobileModal'),
                                width: '100%',
                                placeholder: 'Semua Status Kerja',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterEmploymentStatus', val || null);
                                if ($('#filterEmploymentStatus_desktop').length && $('#filterEmploymentStatus_desktop').val() !== val) {
                                    $('#filterEmploymentStatus_desktop').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Mobile Modal: Filter Status Akun
                        if ($('#filterStatus_mobile').length) {
                            $('#filterStatus_mobile').select2({
                                dropdownParent: $('#employeeFilterMobileModal'),
                                width: '100%',
                                placeholder: 'Semua Status Akun',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterStatus', val || null);
                                if ($('#filterStatus_desktop').length && $('#filterStatus_desktop').val() !== val) {
                                    $('#filterStatus_desktop').val(val || '').trigger('change.select2');
                                }
                            });
                        }
                    };

                    initSelect2();

                    // Handle bootstrap modal tab active
                    $('#employeeModal').on('show.bs.modal', function () {
                        $('#employment-tab').tab('show');
                    });
                    $('#employeeDetailModal').on('show.bs.modal', function () {
                        $('#detail-employment-tab').tab('show');
                    });

                    // Listen open-modal event untuk sinkronisasi nilai Select2
                    Livewire.on('open-modal', (event) => {
                        const data = Array.isArray(event) ? event[0] : event;
                        if (data && data.id === 'employeeModal') {
                            setTimeout(() => {
                                $('#emp_department_id').val(data.department_id || '').trigger('change.select2');
                                $('#emp_position_id').val(data.position_id || '').trigger('change.select2');
                                $('#employment-tab').tab('show');
                            }, 50);
                        }
                        if (data && data.id === 'employeeDetailModal') {
                            setTimeout(() => {
                                $('#detail-employment-tab').tab('show');
                            }, 50);
                        }
                    });

                    // Listen reset-employee-filters event
                    Livewire.on('reset-employee-filters', () => {
                        $('#filterDepartment_desktop, #filterDepartment_mobile').val('').trigger('change.select2');
                        $('#filterPosition_desktop, #filterPosition_mobile').val('').trigger('change.select2');
                        $('#filterEmploymentStatus_desktop, #filterEmploymentStatus_mobile').val('').trigger('change.select2');
                        $('#filterStatus_desktop, #filterStatus_mobile').val('').trigger('change.select2');
                    });
                }

                if (typeof window.Livewire !== 'undefined') {
                    initEmployeeScripts();
                } else {
                    document.addEventListener('livewire:init', initEmployeeScripts);
                }
            })();
        </script>
    @endpush
</div>
