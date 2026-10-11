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
            <!-- Row 1: Header & Controls Card (Data Karyawan, Show Entries & Search Bar) -->
            <div class="card mb-3">
                <div class="card-header">
                    <h4>Data Karyawan</h4>
                    <div class="card-header-action">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Mode Tampilan">
                            <button type="button" wire:click="setViewMode('card')" wire:loading.attr="disabled" wire:target="setViewMode('card')" class="btn {{ $viewMode === 'card' ? 'btn-primary' : 'btn-outline-primary' }}" title="Mode Kartu"><i class="fas fa-th-large mr-1"></i> Kartu</button>
                            <button type="button" wire:click="setViewMode('table')" wire:loading.attr="disabled" wire:target="setViewMode('table')" class="btn {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary' }}" title="Mode Tabel"><i class="fas fa-table mr-1"></i> Tabel</button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="dt-control-wrapper">
                        <div class="dt-show-entries">
                            Show
                            <select wire:model.live="perPage" class="dt-select-entries">
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
                            <button type="button" class="btn btn-primary btn-block shadow-none" data-toggle="modal" data-target="#employeeFilterMobileModal"><i class="fas fa-filter mr-1.5"></i> Filter Karyawan @if ($filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)<span class="badge badge-light text-primary ml-1 py-0.5 px-1.5 text-xs font-weight-bold">Aktif</span>@endif</button>
                        </div>
                    </div>
                </div>
            </div>

            @if ($viewMode === 'table')
                <!-- Mode Tabel: Clean Responsive Data Table -->
                <div class="card mb-0">
                    <div class="table-responsive">
                        <table class="table table-clean">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-center text-nowrap" style="width: 50px;">NO</th>
                                    <th class="text-nowrap">KARYAWAN</th>
                                    <th class="text-nowrap" style="width: 140px;">STATUS KERJA</th>
                                    <th class="text-nowrap" style="width: 140px;">TANGGAL MASUK</th>
                                    <th class="text-nowrap" style="width: 130px;">SISA KONTRAK</th>
                                    <th class="text-nowrap" style="width: 100px;">STATUS AKUN</th>
                                    <th class="text-nowrap" style="width: 120px;">TELEPON</th>
                                    <th class="text-center text-nowrap" style="width: 120px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNumber = 0; @endphp
                                @forelse ($employees->groupBy(fn ($e) => $e->department?->name ?: 'Tanpa Department') as $departmentName => $deptEmployees)
                                    <tr class="bg-gray-50 border-top" wire:key="dept-header-{{ Str::slug($departmentName) }}-{{ $loop->index }}">
                                        <td colspan="8" class="text-dark font-weight-600 bg-gray-50 align-middle py-2 px-4 text-nowrap">
                                            {{ $departmentName === 'Tanpa Department' ? 'Tanpa Department' : 'Department: ' . $departmentName }}
                                        </td>
                                    </tr>
                                    @foreach ($deptEmployees as $employee)
                                        @php $rowNumber++; @endphp
                                        <tr wire:key="employee-table-{{ $employee->id }}">
                                            <td class="text-center text-muted align-middle text-nowrap">
                                                {{ $employees->firstItem() + $rowNumber - 1 }}
                                            </td>
                                            <td class="align-middle text-nowrap">
                                                <div class="d-flex align-items-center text-nowrap">
                                                    <img src="{{ $employee->photo_url }}" alt="{{ $employee->full_name }}" class="rounded-circle mr-2.5" style="width: 38px; height: 38px; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                                    <div class="text-nowrap">
                                                        <div class="text-dark text-nowrap" style="font-size: 13px; line-height: 1.3;">{{ $employee->full_name }}</div>
                                                        <div class="text-muted text-nowrap" style="font-size: 11.5px; line-height: 1.3;">
                                                            {{ $employee->position?->name ?: 'Tanpa Jabatan' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle text-nowrap">
                                                <span class="{{ $employee->employment_status_badge_class }} text-xs">
                                                    {{ $employee->employment_status_label }}
                                                </span>
                                            </td>
                                            <td class="align-middle text-nowrap" style="font-size: 12.5px;">
                                                <div class="text-dark">{{ $employee->join_date ? $employee->join_date->format('d M Y') : '-' }}</div>
                                                @if ($employee->join_date)
                                                    <div class="text-muted" style="font-size: 11px; line-height: 1.2;">{{ $employee->tenure_label }}</div>
                                                @endif
                                            </td>
                                            <td class="align-middle text-nowrap" style="font-size: 12.5px;">
                                                {{ $employee->remaining_contract_label }}
                                            </td>
                                            <td class="align-middle text-nowrap">
                                                <span class="{{ $employee->status_badge_class }} text-xs">
                                                    {{ $employee->status_label }}
                                                </span>
                                            </td>
                                            <td class="align-middle text-nowrap" style="font-size: 12.5px;">
                                                {{ $employee->detail?->phone ?: '-' }}
                                            </td>
                                            <td class="text-center align-middle text-nowrap">
                                                <button type="button" wire:click="showDetail({{ $employee->id }})" wire:loading.attr="disabled" wire:target="showDetail({{ $employee->id }})" class="btn btn-info btn-action mr-1" title="Lihat Profil"><i wire:loading wire:target="showDetail({{ $employee->id }})" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="showDetail({{ $employee->id }})" class="fas fa-eye"></i></button>
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
                                            <div class="py-4">
                                                <i class="fas fa-folder-open text-muted mb-2" style="font-size: 36px;"></i>
                                                <div class="font-weight-600 text-dark mt-2">Tidak ada data karyawan ditemukan</div>
                                                <small class="text-muted d-block mt-1">
                                                    @if($search || $filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)
                                                        Tidak ada karyawan yang sesuai dengan kriteria filter pencarian.
                                                    @else
                                                        Belum ada data master karyawan yang tersedia.
                                                    @endif
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <!-- Row 2: Centered Employee Card Grid (Modern CSS Grid: 4 cards on desktop, 2 on tablet, 1 on mobile with equal gap-x & gap-y) -->
                <div class="emp-card-grid">
                    @forelse ($employees as $employee)
                        <div class="card h-100 mb-0" wire:key="employee-card-{{ $employee->id }}">
                            <div class="card-body p-3 d-flex flex-column justify-content-between text-center">
                                <!-- Top: Avatar, Name, & Badges (Centered) -->
                                <div>
                                    <div class="d-flex justify-content-center mb-2">
                                        <img src="{{ $employee->photo_url }}" alt="{{ $employee->full_name }}" class="emp-profile-avatar-center" style="width: 56px; height: 56px;">
                                    </div>
                                    <div class="emp-profile-name text-center" title="{{ $employee->full_name }}">
                                        {{ $employee->full_name }}
                                    </div>
                                    <div class="emp-profile-role text-center mb-2.5">
                                        {{ $employee->department?->name ?: 'Tanpa Departemen' }} &bull; {{ $employee->position?->name ?: 'Tanpa Jabatan' }}
                                    </div>

                                    <!-- Badges Strip (Centered) -->
                                    <div class="d-flex align-items-center justify-content-center flex-wrap gap-1 mb-3">
                                        <span class="{{ $employee->employment_status_badge_class }} text-xs">
                                            {{ $employee->employment_status_label }}
                                        </span>
                                        <span class="{{ $employee->status_badge_class }} text-xs">
                                            {{ $employee->status_label }}
                                        </span>
                                    </div>

                                    <!-- Operational Info: Flutter-Style ListTile Group (Telepon, Sisa Kontrak, Kasbon) -->
                                    <div class="emp-tile-group mb-1">
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-phone"></i></div>
                                                <span>Telepon</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->phone ?: '-' }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-calendar-times"></i></div>
                                                <span>Sisa Kontrak</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->remaining_contract_label }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-hand-holding-usd"></i></div>
                                                <span>Total Kasbon</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                @if (auth()->user()->hasRole('admin'))
                                                    {{ $employee->formatted_total_cash_receipt }}
                                                @else
                                                    <span class="text-muted">Terbatas</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom: Action Buttons -->
                                <div class="d-flex align-items-center justify-content-center -mt-1 lg:mt-2">
                                    <button type="button" wire:click="showDetail({{ $employee->id }})" wire:loading.attr="disabled" wire:target="showDetail({{ $employee->id }})" class="btn btn-info btn-action mr-1" title="Lihat Profil"><i wire:loading wire:target="showDetail({{ $employee->id }})" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="showDetail({{ $employee->id }})" class="fas fa-eye"></i></button>
                                    @if (auth()->user()->hasRole('admin'))
                                        <button type="button" wire:click="edit({{ $employee->id }})" wire:loading.attr="disabled" wire:target="edit({{ $employee->id }})" class="btn btn-primary btn-action mr-1" title="Edit Data"><i wire:loading wire:target="edit({{ $employee->id }})" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="edit({{ $employee->id }})" class="fas fa-pencil-alt"></i></button>
                                        <button type="button" wire:click="confirmDelete({{ $employee->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $employee->id }})" class="btn btn-danger btn-action" title="Hapus Data"><i wire:loading wire:target="confirmDelete({{ $employee->id }})" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="confirmDelete({{ $employee->id }})" class="fas fa-trash"></i></button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="card grid-column-span-full">
                            <div class="card-body text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fas fa-folder-open text-muted mb-2" style="font-size: 36px;"></i>
                                    <div class="font-weight-600 text-dark mt-2">Tidak ada data karyawan ditemukan</div>
                                    <small class="text-muted d-block mt-1">
                                        @if($search || $filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)
                                            Tidak ada karyawan yang sesuai dengan kriteria filter pencarian.
                                        @else
                                            Belum ada data master karyawan yang tersedia.
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            @endif

            <!-- Row 3: Pagination Card -->
            <div class="card mt-2">
                <div class="card-body p-0">
                    <div class="dt-footer-wrapper">
                        <div>
                            Showing {{ $employees->total() > 0 ? $employees->firstItem() : 0 }} to {{ $employees->total() > 0 ? $employees->lastItem() : 0 }} of {{ $employees->total() }} entries
                        </div>
                        <div>
                            {{ $employees->links('livewire.custom-pagination') }}
                        </div>
                    </div>
                </div>
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
