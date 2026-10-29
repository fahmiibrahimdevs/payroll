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

    <!-- 4 Modern Summary Statistic Cards (Iconless & Compact on Mobile 2x2, Rich with Stat-Icon on Desktop 1x4) -->
    <div class="cash-advance-stat-grid mb-4">
        <!-- 1. Total Karyawan Aktif -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Karyawan Aktif">Total Karyawan Aktif</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $employeeStats['total_active'] ?? 0 }} Orang">
                            <span>{{ $employeeStats['total_active'] ?? 0 }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Orang</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-user-check"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $employeeStats['permanent_count'] ?? 0 }} Tetap &bull; {{ $employeeStats['contract_count'] ?? 0 }} Kontrak &bull; {{ $employeeStats['intern_count'] ?? 0 }} Magang
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Karyawan Tetap (PKWTT) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-id-badge"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Karyawan Tetap">Karyawan Tetap</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $employeeStats['permanent_count'] ?? 0 }} Orang">
                            <span>{{ $employeeStats['permanent_count'] ?? 0 }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Orang</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    </div>
                    <div class="progress mt-1 mb-1" style="height: 4px; background-color: #e2e8f0; border-radius: 9999px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ min(100, max(0, $employeeStats['permanent_percentage'] ?? 0)) }}%; border-radius: 9999px;"></div>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $employeeStats['permanent_percentage'] ?? 0 }}% dari total karyawan aktif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Kontrak & Magang (PKWT) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Kontrak & Magang">Kontrak &amp; Magang</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $employeeStats['contract_and_intern_count'] ?? 0 }} Orang">
                            <span>{{ $employeeStats['contract_and_intern_count'] ?? 0 }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Orang</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-business-time"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $employeeStats['contract_count'] ?? 0 }} Kontrak &bull; {{ $employeeStats['intern_count'] ?? 0 }} Magang aktif
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Kontrak Segera Berakhir / Perhatian -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Perlu Perhatian Kontrak">Perlu Perhatian Kontrak</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $employeeStats['expiring_contracts_count'] ?? 0 }} Karyawan">
                            <span>{{ $employeeStats['expiring_contracts_count'] ?? 0 }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Karyawan</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-purple">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        @if (($employeeStats['expiring_contracts_count'] ?? 0) > 0)
                            <span class="text-danger font-weight-500">Berakhir dlm &le; 30 hari</span> &bull;
                            <a href="javascript:void(0)" onclick="$('#expiringContractsModal').modal('show')" class="text-danger font-weight-600 ml-1">Tinjau</a>
                        @else
                            <span>Semua kontrak aman</span> &bull;
                            <span class="text-muted ml-1">Tinjau</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row: 3 cols Filter Sidebar + 9 cols Data Table -->
    <div class="row align-items-start">
        <!-- Left Sidebar: Filter Panel & Distribusi Grafik di Card yang Sama (Desktop: 3 cols / col-lg-3 col-md-4 d-none d-md-block) -->
        <div class="col-lg-3 col-md-4 d-none d-md-block">
            <div class="card shadow-sm border-0 mb-0" style="border-radius: 12px; overflow: hidden; background: #ffffff; border: 1px solid #e9edf2;">
                <!-- Header Filter Card -->
                <div class="border-bottom d-flex align-items-center justify-content-between" style="background: #ffffff; padding: 14px 20px;">
                    <div>
                        <h4 class="mb-0" style="font-size: 14px; font-weight: 600; color: #0f172a; line-height: 1.35;">
                            Filter Karyawan
                        </h4>
                        <div style="font-size: 11px; font-weight: 400; color: #64748b; margin-top: 2px;">
                            Penyaringan data tabel
                        </div>
                    </div>
                    @if ($search || $filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)
                        <div class="card-header-action">
                            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters" class="btn btn-sm btn-link text-danger font-weight-600 text-decoration-none p-0"><i wire:loading wire:target="resetFilters" class="fas fa-spinner fa-spin mr-1"></i><i wire:loading.remove wire:target="resetFilters" class="fas fa-undo mr-1"></i> Reset</button>
                        </div>
                    @endif
                </div>

                <!-- Form Filter Input -->
                <div style="padding: 16px 20px;">
                    @include('livewire.employees.filter-content', ['filterContext' => 'desktop'])
                </div>

                <!-- Grafik Sebaran Departemen (Di Card Yang Sama dengan Filter) -->
                <div style="border-top: 1px solid #f1f5f9; padding: 16px 20px 18px;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <div style="font-size: 13px; font-weight: 600; color: #0f172a;">
                                Sebaran Departemen
                            </div>
                            <div style="font-size: 11px; font-weight: 400; color: #64748b; margin-top: 1px;">
                                Proporsi karyawan per unit
                            </div>
                        </div>
                        <span class="badge border font-weight-500 px-2 py-0.5" style="font-size: 10px; border-radius: 6px; background-color: #f8fafc; color: #475569; border-color: #e2e8f0;">
                            Aktif
                        </span>
                    </div>

                    <!-- Doughnut Chart Container -->
                    <div wire:key="emp-dept-chart-{{ md5(json_encode($employeeCharts['departments']['counts'] ?? [])) }}"
                         x-data="{
                             chartInstance: null,
                             initChart() {
                                 const labels = @js($employeeCharts['departments']['labels'] ?? []);
                                 const dataVals = @js($employeeCharts['departments']['counts'] ?? []);
                                 const colors = @js($employeeCharts['departments']['colors'] ?? []);
                                 const hasData = @js($employeeCharts['departments']['has_data'] ?? false);

                                 const render = () => {
                                     const canvas = this.$refs.canvas;
                                     if (!canvas) return;
                                     if (typeof Chart === 'undefined') {
                                         setTimeout(render, 100);
                                         return;
                                     }
                                     const ctx = canvas.getContext('2d');
                                     if (!ctx) return;

                                     if (this.chartInstance) {
                                         try { this.chartInstance.destroy(); } catch (e) {}
                                         this.chartInstance = null;
                                     }

                                     this.chartInstance = new Chart(canvas, {
                                         type: 'doughnut',
                                         data: {
                                             labels: labels,
                                             datasets: [{
                                                 data: dataVals,
                                                 backgroundColor: colors,
                                                 hoverBackgroundColor: colors,
                                                 borderWidth: hasData ? 2 : 0,
                                                 borderColor: '#ffffff',
                                                 hoverOffset: hasData ? 4 : 0
                                             }]
                                         },
                                         options: {
                                             responsive: true,
                                             maintainAspectRatio: false,
                                             cutout: '72%',
                                             animation: { duration: 400 },
                                             plugins: {
                                                 legend: { display: false },
                                                 tooltip: {
                                                     enabled: false,
                                                     external: function(ctx) {
                                                         if (typeof window.customChartTooltip === 'function') {
                                                             window.customChartTooltip(ctx);
                                                         }
                                                     },
                                                     callbacks: {
                                                         title: function(items) {
                                                             return items && items.length ? items[0].label : '';
                                                         },
                                                         label: function(context) {
                                                             const val = Number(context.raw) || 0;
                                                             const dataset = context.dataset || {};
                                                             const total = (dataset.data || []).reduce((a, b) => a + (Number(b) || 0), 0);
                                                             const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                                             return val + ' Karyawan (' + pct + '%)';
                                                         }
                                                     }
                                                 }
                                             }
                                         }
                                     });
                                 };

                                 this.$nextTick(() => { render(); });
                                 if (typeof this.$cleanup === 'function') {
                                     this.$cleanup(() => {
                                         if (this.chartInstance) {
                                             try { this.chartInstance.destroy(); } catch (e) {}
                                             this.chartInstance = null;
                                         }
                                         const tip = this.$el ? this.$el.querySelector('.custom-chartjs-tooltip') : null;
                                         if (tip) tip.remove();
                                     });
                                 }
                             }
                         }"
                         x-init="initChart()"
                         class="position-relative d-flex justify-content-center align-items-center my-2"
                         style="height: 140px; width: 100%;">

                        <canvas x-ref="canvas" style="max-height: 140px; max-width: 140px;"></canvas>

                        <!-- Center Text Overlay -->
                        <div class="chart-center-overlay position-absolute text-center d-flex flex-column justify-content-center align-items-center" style="pointer-events: none; max-width: 105px; transition: opacity 0.2s ease;">
                            <span style="font-size: 9.5px; font-weight: 600; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                TOTAL
                            </span>
                            <span style="font-size: 16px; font-weight: 700; color: #0f172a; line-height: 1.25; margin-top: 2px;">
                                {{ $employeeCharts['departments']['total'] ?? 0 }} Org
                            </span>
                        </div>
                    </div>

                    <!-- Breakdown List Departemen -->
                    <div class="d-flex flex-column mt-2" style="gap: 4px;">
                        @if (!empty($employeeCharts['departments']['labels']) && ($employeeCharts['departments']['has_data'] ?? false))
                            @php
                                $deptTotal = $employeeCharts['departments']['total'] ?? array_sum($employeeCharts['departments']['counts']);
                            @endphp
                            @foreach ($employeeCharts['departments']['labels'] as $idx => $deptLabel)
                                @php
                                    $cCount = $employeeCharts['departments']['counts'][$idx] ?? 0;
                                    $cPct = $deptTotal > 0 ? round(($cCount / $deptTotal) * 100, 1) : 0;
                                    $cColor = $employeeCharts['departments']['colors'][$idx] ?? '#0b52aa';
                                @endphp
                                <div class="d-flex align-items-center justify-content-between py-1 px-1 rounded" style="font-size: 11.5px; line-height: 1.4; transition: background 0.15s ease;">
                                    <div class="d-flex align-items-center text-truncate mr-2">
                                        <span style="width: 8px; height: 8px; border-radius: 2px; background-color: {{ $cColor }}; margin-right: 7px; flex-shrink: 0;"></span>
                                        <span class="text-truncate" style="color: #475569; font-weight: 400;" title="{{ $deptLabel }}">{{ $deptLabel }}</span>
                                    </div>
                                    <span style="font-weight: 500; color: #0f172a; flex-shrink: 0; font-variant-numeric: tabular-nums;">
                                        {{ $cCount }} orang · {{ $cPct }}%
                                    </span>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-2 text-muted" style="font-size: 11px;">
                                Belum ada data departemen
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Employee Data (Desktop: 9 cols / col-lg-9 col-md-8 col-12) -->
        <div class="col-lg-9 col-md-8 col-12">
            <!-- Unified Main Card (Matching Kasbon: Header, Search/Entries, Table, Footer in ONE Card) -->
            <div class="card cash-advance-main-card mb-0">
                <!-- Row 1: Header & Controls Card -->
                <div class="cash-advance-top-card">
                    <div class="card-header">
                        <h4 class="mb-0">Data Karyawan</h4>
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
                            <button type="button" class="btn btn-primary btn-block shadow-none font-weight-600" data-toggle="modal" data-target="#employeeFilterMobileModal">
                                <i class="fas fa-filter mr-1.5"></i> Filter Karyawan 
                                @if ($filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)
                                    <span class="badge badge-light text-primary ml-1 py-0.5 px-1.5 text-xs font-weight-600">Aktif</span>
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
                                <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 50px; color: #475569; font-size: 11.5px; font-weight: 600; letter-spacing: 0.5px;">NO</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="color: #475569; font-size: 11.5px; font-weight: 600; letter-spacing: 0.5px;">KARYAWAN</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 140px; color: #475569; font-size: 11.5px; font-weight: 600; letter-spacing: 0.5px;">STATUS KERJA</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 140px; color: #475569; font-size: 11.5px; font-weight: 600; letter-spacing: 0.5px;">TANGGAL MASUK</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 130px; color: #475569; font-size: 11.5px; font-weight: 600; letter-spacing: 0.5px;">SISA KONTRAK</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 100px; color: #475569; font-size: 11.5px; font-weight: 600; letter-spacing: 0.5px;">STATUS AKUN</th>
                                <th class="text-nowrap !px-3 !py-2.5" style="width: 120px; color: #475569; font-size: 11.5px; font-weight: 600; letter-spacing: 0.5px;">TELEPON</th>
                                <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 120px; color: #475569; font-size: 11.5px; font-weight: 600; letter-spacing: 0.5px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
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
                                                     <span class="font-weight-600" style="font-size: 13px; letter-spacing: 0.3px; color: #0f172a;">
                                                         DEPARTEMEN {{ strtoupper($departmentName) }}
                                                     </span>
                                                     @if (! $isOfficialDept)
                                                         <span class="badge ml-2 font-weight-500" style="font-size: 10.5px; padding: 2.5px 7px; border-radius: 12px; background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
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
                                        <td class="text-center align-middle !px-3 !py-2.5 text-nowrap" style="color: #64748b; font-size: 12.5px; font-weight: 500;">
                                            {{ $employees->firstItem() + $rowNumber - 1 }}
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5">
                                            <div class="d-flex align-items-center text-nowrap">
                                                <img src="{{ $employee->photo_url }}" alt="{{ $employee->full_name }}" class="rounded-circle mr-2.5" style="width: 38px; height: 38px; object-fit: cover; border: 1.5px solid #cbd5e1; flex-shrink: 0;">
                                                <div class="text-nowrap">
                                                    <div class="font-weight-600 leading-tight" style="color: #0f172a; font-size: 13.5px;">{{ $employee->full_name }}</div>
                                                    <div class="leading-tight mt-1 d-flex align-items-center flex-wrap" style="font-size: 12px; color: #64748b; font-weight: 400; gap: 4px;">
                                                        <span>{{ $employee->position?->name ?: 'Tanpa Jabatan' }}</span>
                                                        @if ($employee->user?->hasRole('direktur'))
                                                            <span class="badge border font-weight-500 px-1.5 py-0.5" style="font-size: 10px; border-radius: 4px; background-color: #fef3c7; color: #92400e; border-color: #fde68a;">Direktur</span>
                                                        @elseif ($employee->user?->hasRole('admin'))
                                                            <span class="badge border font-weight-500 px-1.5 py-0.5" style="font-size: 10px; border-radius: 4px; background-color: #e0f2fe; color: #0369a1; border-color: #bae6fd;">Admin</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5">
                                            <span style="color: {{ $employee->employment_status_text_color }}; font-size: 12.5px; font-weight: 500;">
                                                {{ $employee->employment_status_label }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5">
                                            <div style="color: #334155; font-size: 12.5px; font-weight: 500;">{{ $employee->join_date ? $employee->join_date->format('d M Y') : '-' }}</div>
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5" style="font-size: 12.5px; color: #334155; font-weight: 400;">
                                            {{ $employee->remaining_contract_label }}
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5">
                                            @if ($employee->user)
                                                <label class="custom-switch pl-0 m-0" title="{{ $employee->user->is_active ? 'Akun aktif' : 'Akun nonaktif' }}">
                                                    <input type="checkbox" class="custom-switch-input" aria-label="Status akun {{ $employee->full_name }}" @checked($employee->user->is_active) @disabled(! auth()->user()->hasRole('admin')) wire:click.prevent="confirmToggleAccountStatus({{ $employee->id }})" wire:loading.attr="disabled" wire:target="confirmToggleAccountStatus({{ $employee->id }}),toggleEmployeeAccountStatus">
                                                    <span class="custom-switch-indicator"></span>
                                                    <span class="custom-switch-description">{{ $employee->user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                                </label>
                                            @else
                                                <span class="text-dark">Belum ada akun</span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-nowrap !px-3 !py-2.5" style="font-size: 12.5px; color: #475569; font-weight: 400; font-variant-numeric: tabular-nums;">
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
                                    <td colspan="8" class="p-0 border-0">
                                        <x-empty-state
                                            icon="fas fa-users"
                                            title="Tidak Ada Data Karyawan"
                                            :subtitle="($search || $filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)
                                                ? 'Tidak ditemukan data karyawan yang cocok dengan kriteria filter pencarian.'
                                                : 'Belum ada data master karyawan yang terdaftar di dalam sistem.'"
                                            :action-label="auth()->user()?->hasRole(['admin', 'direktur']) ? 'Tambah Karyawan Baru' : null"
                                            action-wire-click="openCreateModal"
                                            action-icon="fas fa-user-plus"
                                        />
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

    <!-- Modal Tinjau Kontrak Segera Berakhir -->
    @include('livewire.employees.expiring-contracts-modal')

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

                        // Sidebar Desktop: Filter Status Karyawan
                        if ($('#filterStatus_desktop').length) {
                            $('#filterStatus_desktop').select2({
                                width: '100%',
                                placeholder: 'Semua Status Karyawan',
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

                        // Mobile Modal: Filter Status Karyawan
                        if ($('#filterStatus_mobile').length) {
                            $('#filterStatus_mobile').select2({
                                dropdownParent: $('#employeeFilterMobileModal'),
                                width: '100%',
                                placeholder: 'Semua Status Karyawan',
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
                    }).on('hidden.bs.modal', function () {
                        if (typeof @this !== 'undefined') {
                            @this.call('closeModal');
                        }
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
                                const sectionTabs = {
                                    kepegawaian: '#employment-tab',
                                    pribadi: '#personal-tab',
                                    pajak: '#tax-tab',
                                };
                                $(sectionTabs[data.section] || '#employment-tab').tab('show');
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
