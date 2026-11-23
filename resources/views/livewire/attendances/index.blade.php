<div class="attendances-index-page">
    <x-slot name="header">
        <h1>Absensi Karyawan</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Operasional</div>
            <div class="breadcrumb-item">Absensi Karyawan</div>
        </div>
    </x-slot>

    <!-- Header Section & Action Buttons -->
    <div class="attendance-page-intro d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div class="mr-md-3">
            <h2 class="section-title mt-0"><span class="attendance-page-title-text">Rekap Presensi &amp; Jam Kerja Mesin Fingerprint</span></h2>
            <p class="section-lead mb-0">
                Tampilan Matriks Kehadiran harian seluruh karyawan lengkap dengan jam scan masuk dan scan pulang.
            </p>
        </div>

        @if (auth()->user()?->hasRole(['admin', 'direktur']))
            <div class="attendance-page-actions mt-3 mt-md-0 flex-shrink-0 d-flex align-items-center" style="gap: 8px;">
                <button type="button" onclick="$('#importAttendanceModal').modal('show')" class="btn font-weight-bold d-inline-flex align-items-center text-white" style="background-color: #1d4ed8; border: none; border-radius: 8px; padding: 7px 16px; font-size: 12px; box-shadow: 0 2px 6px rgba(29, 78, 216, 0.25); transition: all 0.15s;">
                    <i class="fas fa-file-excel mr-1.5"></i> Import Excel Mesin
                </button>
                <button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" wire:target="openCreateModal" class="btn font-weight-bold d-inline-flex align-items-center" style="background-color: #f8fafc; border: 1px solid #cbd5e1; color: #1e293b; border-radius: 8px; padding: 7px 16px; font-size: 12px; transition: all 0.15s;">
                    <span wire:loading.remove wire:target="openCreateModal"><i class="fas fa-plus mr-1.5 text-primary"></i> Input Manual</span>
                    <span wire:loading wire:target="openCreateModal"><i class="fas fa-spinner fa-spin mr-1.5 text-primary"></i> Membuka...</span>
                </button>
            </div>
        @endif
    </div>

    @if (! empty($metrics['total_pending_review']) && $metrics['total_pending_review'] > 0)
        <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-lg shadow-sm" style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; color: #92400e;">
            <div class="d-flex align-items-center">
                <div class="mr-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; border-radius: 8px; background: rgba(245, 158, 11, 0.15); color: #d97706;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 16px;"></i>
                </div>
                <div>
                    <strong style="font-size: 13.5px; font-weight: 600;">Menunggu Review Presensi ({{ $metrics['total_pending_review'] }} Catatan)</strong>
                    <div style="font-size: 12px; color: #78350f;">
                        Terdapat presensi selfie yang dilakukan di luar radius kantor resmi dan memerlukan verifikasi Admin. Klik pada sel bertanda kuning untuk mengaudit.
                    </div>
                </div>
            </div>
            <span class="badge badge-warning flex-shrink-0" style="background: #f59e0b; color: #fff; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                Perlu Review
            </span>
        </div>
    @endif

    <!-- 4 Top Metric Cards (Grid Baku Eksekutif) -->
    <div class="cash-advance-stat-grid mb-4">
        <!-- 1. Total Log Presensi (Blue) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Log Presensi">Total Log Presensi</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            <span>{{ number_format($metrics['total_logs']) }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Record</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-calendar-alt text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Presensi pada rentang aktif
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Hadir Tepat Waktu (Green) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Hadir Tepat Waktu">Hadir Tepat Waktu</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            <span>{{ number_format($metrics['total_hadir']) }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Hari</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-clock text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Sebelum batas jam masuk
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Karyawan Hadir (Indigo) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Karyawan Hadir">Karyawan Hadir</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            <span>{{ number_format($metrics['total_karyawan_hadir'] ?? 0) }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Orang</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-users text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Karyawan aktif tercatat hadir
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Pegawai Harian Hadir (Purple) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                    <i class="fas fa-hard-hat"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Pegawai Harian Hadir">Pegawai Harian Hadir</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            <span>{{ number_format($metrics['total_harian']) }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Orang-Hari</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-users-cog text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Dasar upah &amp; uang makan harian
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Filter & Navigasi Bulan (Clean White & Compact, Style Jadwal) -->
    <div class="card border-0 mb-3 roster-toolbar-card">
        <div class="card-body p-2 px-3">
            <div class="attendance-toolbar-controls d-flex flex-column flex-lg-row justify-content-between align-items-lg-center flex-wrap" style="gap: 10px;">
                <!-- Navigasi Bulan & Tahun -->
                <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                    <div class="roster-toolbar-month">
                        <button type="button" wire:click="previousMonth" wire:loading.attr="disabled" wire:target="previousMonth, nextMonth, goToCurrentMonth" class="roster-toolbar-month-btn" title="Bulan Sebelumnya">
                            <span wire:loading.remove wire:target="previousMonth"><i class="fas fa-chevron-left"></i></span>
                            <span wire:loading wire:target="previousMonth"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                        <div class="roster-toolbar-month-text">
                            <span wire:loading.remove wire:target="previousMonth, nextMonth, goToCurrentMonth">
                                <i class="far fa-calendar-alt mr-2 text-primary"></i> {{ $periodMonthLabel }}
                            </span>
                            <span wire:loading wire:target="previousMonth, nextMonth, goToCurrentMonth" class="text-primary font-weight-bold">
                                <i class="fas fa-spinner fa-spin mr-1.5"></i> Memuat...
                            </span>
                        </div>
                        <button type="button" wire:click="nextMonth" wire:loading.attr="disabled" wire:target="previousMonth, nextMonth, goToCurrentMonth" class="roster-toolbar-month-btn" title="Bulan Berikutnya">
                            <span wire:loading.remove wire:target="nextMonth"><i class="fas fa-chevron-right"></i></span>
                            <span wire:loading wire:target="nextMonth"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    </div>

                    @if (! $isCurrentMonth)
                        <button type="button" wire:click="goToCurrentMonth" wire:loading.attr="disabled" wire:target="previousMonth, nextMonth, goToCurrentMonth" class="btn roster-toolbar-btn-current">
                            <span wire:loading.remove wire:target="goToCurrentMonth"><i class="fas fa-undo-alt mr-1.5" style="font-size: 11px;"></i> Bulan Ini</span>
                            <span wire:loading wire:target="goToCurrentMonth"><i class="fas fa-spinner fa-spin mr-1.5" style="font-size: 11px;"></i> Memuat...</span>
                        </button>
                    @endif
                </div>

                <!-- Filter Karyawan (desktop) -->
                <div class="attendance-filter-fields d-none d-md-flex align-items-center flex-wrap" style="gap: 10px;">
                    <!-- Dropdown Sorting Karyawan -->
                    <div style="min-width: 185px; position: relative;">
                        <select wire:model.live="sortBy" wire:loading.attr="disabled" wire:target="sortBy" class="form-control roster-toolbar-select" title="Urutan Karyawan">
                            <option value="custom">&#8645; Urutan Roster (Jadwal)</option>
                            <option value="name_asc">&#8595; Nama (A - Z)</option>
                            <option value="name_desc">&#8593; Nama (Z - A)</option>
                            <option value="position_asc">&#9881; Jobdesk / Posisi</option>
                            <option value="department_asc">&#127970; Departemen</option>
                            <option value="status_asc">&#128101; Status Kepegawaian</option>
                        </select>
                        <div wire:loading wire:target="sortBy" style="position: absolute; right: 28px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <i class="fas fa-spinner fa-spin text-primary" style="font-size: 11px;"></i>
                        </div>
                    </div>

                    <!-- Filter Departemen -->
                    <div style="min-width: 165px; position: relative;">
                        <select wire:model.live="departmentFilter" wire:loading.attr="disabled" wire:target="departmentFilter" class="form-control roster-toolbar-select" title="Filter Departemen">
                            <option value="">Semua Departemen</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <div wire:loading wire:target="departmentFilter" style="position: absolute; right: 28px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <i class="fas fa-spinner fa-spin text-primary" style="font-size: 11px;"></i>
                        </div>
                    </div>

                    <!-- Filter Status Kepegawaian -->
                    <div style="min-width: 155px; position: relative;">
                        <select wire:model.live="employmentStatusFilter" wire:loading.attr="disabled" wire:target="employmentStatusFilter" class="form-control roster-toolbar-select" title="Status Kepegawaian">
                            <option value="">Semua Status</option>
                            @foreach ($employmentStatuses as $es)
                                <option value="{{ $es->value }}">{{ $es->label() }}</option>
                            @endforeach
                        </select>
                        <div wire:loading wire:target="employmentStatusFilter" style="position: absolute; right: 28px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <i class="fas fa-spinner fa-spin text-primary" style="font-size: 11px;"></i>
                        </div>
                    </div>
                </div>

                <!-- Search and page size remain visible on mobile -->
                <div class="attendance-search-controls d-flex align-items-center flex-wrap" style="gap: 8px;">
                    <!-- Search Box -->
                    <div class="attendance-search-field" style="min-width: 200px;">
                        <div class="input-group roster-toolbar-search">
                            <div class="input-group-prepend">
                                <span class="input-group-text roster-toolbar-search-icon">
                                    <span wire:loading.remove wire:target="search"><i class="fas fa-search" style="font-size: 11px;"></i></span>
                                    <span wire:loading wire:target="search"><i class="fas fa-spinner fa-spin text-primary" style="font-size: 11px;"></i></span>
                                </span>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control roster-toolbar-search-input" placeholder="Cari nama, PIN, jabatan...">
                        </div>
                    </div>

                    <!-- Per Page -->
                    <div class="attendance-per-page-control">
                        <select wire:model.live="perPage" wire:loading.attr="disabled" wire:target="perPage" class="form-control roster-toolbar-select" title="Jumlah baris karyawan">
                            <option value="0">Semua</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <div wire:loading wire:target="perPage" style="position: absolute; right: 22px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <i class="fas fa-spinner fa-spin text-primary" style="font-size: 10px;"></i>
                        </div>
                    </div>

                    <button type="button" class="attendance-mobile-filter-button btn btn-primary d-md-none" data-toggle="modal" data-target="#attendanceFilterModal">
                        <i class="fas fa-filter mr-1"></i> Filter
                        @if ($sortBy !== 'custom' || $departmentFilter !== '' || $employmentStatusFilter !== '')
                            <span class="attendance-filter-active-indicator">Aktif</span>
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade attendance-filter-modal" id="attendanceFilterModal" tabindex="-1" role="dialog" aria-labelledby="attendanceFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content bg-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceFilterModalLabel"><i class="fas fa-filter text-primary mr-2"></i>Filter Absensi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="attendanceFilterSortBy">Urutan Karyawan</label>
                        <select id="attendanceFilterSortBy" wire:model.live="sortBy" wire:loading.attr="disabled" wire:target="sortBy" class="form-control roster-toolbar-select">
                            <option value="custom">Urutan Roster (Jadwal)</option>
                            <option value="name_asc">Nama (A - Z)</option>
                            <option value="name_desc">Nama (Z - A)</option>
                            <option value="position_asc">Jobdesk / Posisi</option>
                            <option value="department_asc">Departemen</option>
                            <option value="status_asc">Status Kepegawaian</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="attendanceFilterDepartment">Departemen</label>
                        <select id="attendanceFilterDepartment" wire:model.live="departmentFilter" wire:loading.attr="disabled" wire:target="departmentFilter" class="form-control roster-toolbar-select">
                            <option value="">Semua Departemen</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label for="attendanceFilterEmploymentStatus">Status Kepegawaian</label>
                        <select id="attendanceFilterEmploymentStatus" wire:model.live="employmentStatusFilter" wire:loading.attr="disabled" wire:target="employmentStatusFilter" class="form-control roster-toolbar-select">
                            <option value="">Semua Status</option>
                            @foreach ($employmentStatuses as $es)
                                <option value="{{ $es->value }}">{{ $es->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-block" data-dismiss="modal">Terapkan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Roster Grid (Style Jadwal: Sticky 2-Tier Header, 3 Sticky Columns, Clean White Grid) -->
    <div class="card attendance-table-shell position-relative">
        <!-- Loading Overlay saat Filter / Ganti Bulan / Sorting -->
        <div wire:loading wire:target="previousMonth, nextMonth, goToCurrentMonth, sortBy, departmentFilter, employmentStatusFilter, search, perPage" class="attendance-table-loading-overlay">
            <div class="attendance-table-loading-badge">
                <i class="fas fa-spinner fa-spin mr-2 text-primary" style="font-size: 13px;"></i>
                <span>Memuat data presensi...</span>
            </div>
        </div>

        <div class="attendance-table-scroll-hint d-md-none">
            <i class="fas fa-arrows-alt-h" aria-hidden="true"></i>
            <span>Geser tabel ke samping untuk melihat tanggal lainnya</span>
        </div>

        <div class="attendance-table-body" wire:loading.class="table-loading-dimmed" wire:target="previousMonth, nextMonth, goToCurrentMonth, sortBy, departmentFilter, employmentStatusFilter, search, perPage">
            <div class="table-responsive attendance-table-scroll">
                <table class="table mb-0 text-center attendance-roster-table modern-attendance-matrix" style="border-collapse: collapse; background: #ffffff;">
                    <thead class="sticky-top" style="z-index: 10;">
                        <!-- Baris 1: Kolom Tetap Kiri, Nama Hari (Clean White & Merah Kontras Hari Minggu/Libur), dan Rekap -->
                        <tr style="background: #ffffff;">
                            <th class="sticky-col first-col align-middle text-center" rowspan="2" style="width: 36px; min-width: 36px; max-width: 36px; z-index: 20; color: #0f172a !important; font-weight: 700; font-size: 10px; border-bottom: 2px solid #cbd5e1; background: #ffffff !important;">
                                NO
                            </th>
                            <th class="sticky-col second-col align-middle text-left" rowspan="2" style="width: 160px; min-width: 160px; max-width: 160px; z-index: 20; font-weight: 700; font-size: 11px; letter-spacing: 0.04em; border-bottom: 2px solid #cbd5e1; background: #ffffff !important; color: #0f172a !important; padding-left: 8px !important;">
                                NAMA KARYAWAN
                            </th>
                            <th class="sticky-col third-col align-middle text-left" rowspan="2" style="width: 100px; min-width: 100px; max-width: 100px; z-index: 20; font-weight: 700; font-size: 11px; letter-spacing: 0.04em; border-bottom: 2px solid #cbd5e1; background: #ffffff !important; color: #0f172a !important; padding-left: 8px !important;">
                                JOBDESK
                            </th>

                            @foreach ($matrixDates as $d)@if ($d['is_sunday'])<th class="p-0 font-weight-bold text-nowrap align-middle date-col" style="font-size: 9.5px; background: #dc2626 !important; color: #ffffff !important; border: 1px solid #b91c1c; height: 20px;" title="Hari Minggu">{{ $d['dow_name'] }}</th>@elseif ($d['is_holiday'])<th class="p-0 font-weight-bold text-nowrap align-middle date-col" style="font-size: 9.5px; background: #ef4444 !important; color: #ffffff !important; border: 1px solid #dc2626; height: 20px;" title="{{ $d['holiday_name'] }}">{{ $d['dow_name'] }}</th>@else<th class="p-0 font-weight-bold text-nowrap align-middle date-col" style="font-size: 9.5px; background: #ffffff !important; color: #0f172a !important; border: 1px solid #cbd5e1; height: 20px;">{{ $d['dow_name'] }}</th>@endif @endforeach

                            <th colspan="2" class="align-middle text-center p-0" style="border-left: 2px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; font-weight: 700; font-size: 10px; background: #ffffff !important; color: #334155; height: 20px;">
                                REKAP KEHADIRAN
                            </th>
                        </tr>

                        <!-- Baris 2: Angka Tanggal (Biru Muda untuk Hari Biasa, Merah untuk Hari Minggu / Libur) dan Sub-Header Rekap -->
                        <tr style="background: #ffffff;">
                            @foreach ($matrixDates as $d)@if ($d['is_sunday'])<th class="p-0 font-weight-bold align-middle date-col" style="font-size: 10.5px; background: #b91c1c !important; color: #ffffff !important; border: 1px solid #991b1b; height: 20px;" title="Hari Minggu (Tgl {{ $d['day'] }})">{{ $d['day'] }}</th>@elseif ($d['is_holiday'])<th class="p-0 font-weight-bold align-middle date-col" style="font-size: 10.5px; background: #dc2626 !important; color: #ffffff !important; border: 1px solid #b91c1c; height: 20px;" title="{{ $d['holiday_name'] }} (Tgl {{ $d['day'] }})">{{ $d['day'] }}</th>@else<th class="p-0 font-weight-bold align-middle date-col" style="font-size: 10.5px; background: #e0f2fe !important; color: #0369a1 !important; border: 1px solid #bae6fd; height: 20px;" title="Tanggal {{ $d['day'] }}">{{ $d['day'] }}</th>@endif @endforeach

                            <th class="align-middle text-center p-0 font-weight-bold" style="width: 44px; min-width: 44px; max-width: 44px; color: #047857; font-size: 9.5px; border-left: 2px solid #cbd5e1; border-bottom: 2px solid #cbd5e1; background: #ffffff !important; height: 20px;" title="Total Hari Masuk Kerja">HADIR</th>
                            <th class="align-middle text-center p-0 font-weight-bold" style="width: 44px; min-width: 44px; max-width: 44px; color: #be123c; font-size: 9.5px; border-bottom: 2px solid #cbd5e1; background: #ffffff !important; height: 20px;" title="Total Hari Libur">LIBUR</th>
                        </tr>
                    </thead>
                    <tbody x-on:click="const cell = $event.target.closest('.att-cell-clickable'); if (cell && !cell.classList.contains('is-loading')) { cell.classList.add('is-loading'); $wire.openCellModal(cell.dataset.emp, cell.dataset.date); }">
                        @php
                            $rowNumber = ($matrixEmployees->firstItem() ?? 1);
                        @endphp
                        @forelse ($groupedMatrixEmployees as $statusKey => $groupEmployees)
                            @php
                                $statusMeta = $employmentStatusMeta[$statusKey] ?? [
                                    'label' => ucfirst($statusKey),
                                    'icon' => 'fas fa-users',
                                ];
                            @endphp
                            @if ($statusKey !== 'all')
                                <!-- Sub-Header Group Status Kerja -->
                                <tr class="table-group-header" style="background: #ffffff; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                                    <td class="sticky-col first-col text-center p-0 align-middle" style="background: #f1f5f9 !important; width: 36px; min-width: 36px;">
                                        <i class="{{ $statusMeta['icon'] }}" style="color: #0b52aa; font-size: 11px;"></i>
                                    </td>
                                    <td colspan="2" class="sticky-col second-col text-left align-middle" style="background: #f1f5f9 !important; padding: 5px 8px !important; z-index: 6; box-shadow: 4px 0 8px -2px rgba(0, 0, 0, 0.06); font-weight: 700; font-size: 11px; color: #0f172a; text-transform: uppercase;">
                                        <span>STATUS: {{ $statusMeta['label'] }}</span>
                                        <span class="badge ml-2 px-2 py-0.5" style="background: #cbd5e1; color: #0f172a; font-weight: 700; font-size: 10.5px; border-radius: 9999px;">
                                            {{ $groupEmployees->count() }} Orang
                                        </span>
                                    </td>
                                    <td colspan="{{ count($matrixDates) + 2 }}" style="background: #ffffff;"></td>
                                </tr>
                            @endif

                            @foreach ($groupEmployees as $emp)
                                @php
                                    $empRow = $matrixRowData[$emp->id];
                                    $empSummary = $matrixSummary[$emp->id];
                                @endphp
                                <tr wire:key="matrix-row-{{ $emp->id }}">
                                    <!-- Kolom 1: NO -->
                                    <td class="sticky-col first-col align-middle text-center p-0" style="background: #ffffff; width: 36px; min-width: 36px; max-width: 36px;">
                                        <span class="row-num font-weight-bold" style="color: #0f172a; font-size: 11px;">{{ $rowNumber++ }}</span>
                                    </td>

                                    <!-- Kolom 2: NAMA KARYAWAN -->
                                    <td class="sticky-col second-col align-middle text-left" style="background: #ffffff; padding: 0 6px 0 8px !important; width: 160px; min-width: 160px; max-width: 160px;">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $emp->photo_url }}" alt="{{ $emp->full_name }}" class="rounded-circle mr-1.5 flex-shrink-0" style="width: 22px; height: 22px; object-fit: cover; border: 1px solid #cbd5e1;">
                                            <div class="text-truncate" style="line-height: 1.25;">
                                                <span class="font-weight-bold text-truncate d-block" style="font-size: 11px; color: #0f172a;" title="{{ $emp->full_name }}">
                                                    {{ $emp->full_name }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kolom 3: JOBDESK -->
                                    <td class="sticky-col third-col align-middle text-left" style="background: #ffffff; padding: 0 6px !important; width: 100px; min-width: 100px; max-width: 100px;">
                                        <div class="text-truncate font-weight-500" style="font-size: 10.5px; line-height: 1.2; color: #334155;" title="{{ $emp->position?->name ?: ($emp->department?->name ?: '-') }}">
                                            {{ $emp->position?->name ?: ($emp->department?->name ?: '-') }}
                                        </div>
                                    </td>

                                    <!-- Kolom Tanggal (1 s/d 31) -->
                                    @foreach ($matrixDates as $d)@php $cell = $empRow['cells'][$d['date']]; @endphp<td class="text-center p-0 align-middle date-col {{ $cell['is_off'] ? 'cell-roster-off' : '' }}"><div class="att-cell-wrap att-cell-clickable {{ $cell['class'] }}" data-emp="{{ $emp->id }}" data-date="{{ $d['date'] }}" title="{{ $cell['title'] }}">{!! $cell['content'] !!}</div></td>@endforeach

                                    <!-- Kolom Rekapitulasi Kehadiran -->
                                    <td class="align-middle p-0 font-weight-bold text-success text-center" style="background: #ffffff; border-left: 2px solid #e2e8f0; font-size: 11px; width: 44px; min-width: 44px; max-width: 44px;" title="Total Hadir: {{ $empSummary['hadir'] }} hari">
                                        {{ $empSummary['hadir'] }} hr
                                    </td>
                                    <td class="align-middle p-0 font-weight-bold text-danger text-center" style="background: #ffffff; font-size: 11px; width: 44px; min-width: 44px; max-width: 44px;" title="Total Libur: {{ $empSummary['libur'] }} hari">
                                        {{ $empSummary['libur'] }} hr
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ count($matrixDates) + 4 }}" class="p-0 border-0">
                                    <x-empty-state
                                        icon="fas fa-user-clock"
                                        title="Tidak Ada Data Presensi"
                                        subtitle="Tidak ditemukan catatan log presensi karyawan yang cocok pada periode atau filter yang dipilih."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer: Petunjuk Interaksi, Legenda Simbol & Paginasi -->
            @if ($matrixEmployees && $matrixEmployees->total() > 0)
                <div class="attendance-table-footer">
                    <div class="attendance-table-footer-top">
                        <div class="attendance-table-help">
                            <span class="attendance-table-help-icon" aria-hidden="true"><i class="fas fa-hand-pointer"></i></span>
                            <span>Klik sel pada tabel untuk mengubah atau mengoreksi presensi.</span>
                        </div>
                        @if ($matrixEmployees->hasPages())
                            <div class="attendance-table-pagination">
                                {{ $matrixEmployees->links('livewire.custom-pagination') }}
                            </div>
                        @endif
                    </div>

                    <div class="attendance-table-legend" role="list" aria-label="Legenda status presensi">
                        <span class="attendance-table-legend-title">Keterangan</span>
                        <span class="attendance-legend-item" role="listitem"><span class="attendance-legend-mark attendance-legend-mark-present" aria-hidden="true"><i class="fas fa-check"></i></span>Hadir</span>
                        <span class="attendance-legend-item" role="listitem"><span class="attendance-legend-mark attendance-legend-mark-present-overtime" aria-hidden="true"><i class="fas fa-check"></i><span>OT</span></span>Hadir + Lembur</span>
                        <span class="attendance-legend-item" role="listitem"><span class="attendance-legend-mark attendance-legend-mark-overtime" aria-hidden="true">OT</span>Lembur</span>
                        <span class="attendance-legend-item" role="listitem"><span class="attendance-legend-mark attendance-legend-mark-late" aria-hidden="true">T</span>Terlambat</span>
                        <span class="attendance-legend-item" role="listitem"><span class="attendance-legend-mark attendance-legend-mark-leave" aria-hidden="true">C</span>Cuti</span>
                        <span class="attendance-legend-item" role="listitem"><span class="attendance-legend-mark attendance-legend-mark-sick" aria-hidden="true">S</span>Sakit</span>
                        <span class="attendance-legend-item" role="listitem"><span class="attendance-legend-mark attendance-legend-mark-permission" aria-hidden="true">I</span>Izin</span>
                        <span class="attendance-legend-item" role="listitem"><span class="attendance-legend-mark attendance-legend-mark-absent" aria-hidden="true">A</span>Alpa</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Styles: Modal, Callouts, Roster Toolbar & Table Grid -->
    <style>
        /* Bootstrap Callout (.bd-callout) Component */
        .bd-callout {
            padding: 1rem 1.35rem !important;
            margin-top: 0;
            margin-bottom: 1.25rem !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-left-width: 4px !important;
            border-radius: 9px !important;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04) !important;
            transition: all 0.2s ease-in-out !important;
        }
        .bd-callout-info {
            border-left-color: #0b52aa !important;
            background: linear-gradient(90deg, rgba(11, 82, 170, 0.04) 0%, rgba(255, 255, 255, 0.98) 20%, #ffffff 100%) !important;
        }
        .bd-callout-success {
            border-left-color: #10b981 !important;
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.04) 0%, rgba(255, 255, 255, 0.98) 20%, #ffffff 100%) !important;
        }
        .bd-callout .bd-callout-icon {
            font-size: 20px !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
        }
        .bd-callout-info .bd-callout-icon {
            color: #0b52aa !important;
        }
        .bd-callout .bd-callout-title {
            font-size: 14.5px !important;
            font-weight: 600 !important;
            line-height: 1.3 !important;
            color: #0b52aa !important;
        }
        .bd-callout .bd-callout-desc {
            font-size: 12.5px !important;
            color: #334155 !important;
            line-height: 1.5 !important;
        }

        /* High-Contrast Crisp Labels & Disabled Form Controls in Modals */
        .modal .form-control:disabled,
        .modal select:disabled,
        .modal input:disabled {
            background-color: #f8fafc !important;
            color: #0f172a !important;
            font-weight: 500 !important;
            opacity: 1 !important;
            border-color: #cbd5e1 !important;
            cursor: not-allowed !important;
        }
        .modal .form-group label {
            color: #1e293b !important;
            font-weight: 500 !important;
            font-size: 13px !important;
        }

        /* Select2 Clean Form Control Match (Sesuai Standar Stisla & Modal HRM) */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 1px solid #e4e6fc !important;
            border-radius: 4px !important;
            background-color: #fdfdff !important;
            transition: all 0.2s ease-in-out !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #495057 !important;
            font-size: 13.5px !important;
            line-height: 40px !important;
            padding-left: 15px !important;
            padding-right: 35px !important;
            font-weight: 400 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 10px !important;
            top: 1px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #0b52aa !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(11, 82, 170, 0.15) !important;
        }
        .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: #e9ecef !important;
            border-color: #e4e6fc !important;
            cursor: not-allowed !important;
            opacity: 1 !important;
        }
        .select2-container--default.select2-container--disabled .select2-selection--single .select2-selection__rendered {
            color: #495057 !important;
        }
        .select2-dropdown {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
            font-size: 13px !important;
            z-index: 1060 !important;
        }
        .select2-container--default .select2-results__group {
            background-color: #f8fafc !important;
            color: #0b52aa !important;
            font-weight: 600 !important;
            font-size: 11.5px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding: 6px 12px !important;
            border-bottom: 1px solid #e2e8f0 !important;
            border-top: 1px solid #e2e8f0 !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0b52aa !important;
            color: #ffffff !important;
        }

        /* Toolbar Styling (Persis Jadwal: Anti-Clipping, Perfect Height 36px) */
        .roster-toolbar-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }
        .roster-toolbar-month {
            display: inline-flex;
            align-items: center;
            height: 36px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 2px;
        }
        .roster-toolbar-month-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 6px;
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            padding: 0;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .roster-toolbar-month-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .roster-toolbar-month-text {
            padding: 0 14px;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 155px;
            line-height: normal;
            user-select: none;
        }
        .roster-toolbar-btn-current {
            height: 36px !important;
            background: #eff6ff !important;
            color: #2563eb !important;
            border: 1px solid #bfdbfe !important;
            border-radius: 8px !important;
            padding: 0 14px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: normal !important;
            box-shadow: none !important;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .roster-toolbar-btn-current:hover {
            background: #dbeafe !important;
            color: #1d4ed8 !important;
            border-color: #93c5fd !important;
        }
        select.roster-toolbar-select,
        .roster-toolbar-select {
            height: 36px !important;
            min-height: 36px !important;
            max-height: 36px !important;
            padding: 0 28px 0 10px !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            line-height: normal !important;
            color: #334155 !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            cursor: pointer;
            vertical-align: middle;
        }
        select.roster-toolbar-select:focus,
        .roster-toolbar-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
            outline: none !important;
        }
        .roster-toolbar-search {
            height: 36px !important;
        }
        .roster-toolbar-search-icon {
            height: 36px !important;
            padding: 0 12px !important;
            font-size: 11px !important;
            color: #94a3b8 !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-right: none !important;
            border-radius: 8px 0 0 8px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .roster-toolbar-search-input {
            height: 36px !important;
            padding: 0 12px !important;
            font-size: 12px !important;
            line-height: normal !important;
            color: #334155 !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-left: none !important;
            border-radius: 0 8px 8px 0 !important;
            box-shadow: none !important;
        }
        .roster-toolbar-search-input:focus {
            border-color: #3b82f6 !important;
            box-shadow: none !important;
            outline: none !important;
        }
        .roster-toolbar-search:focus-within .roster-toolbar-search-icon,
        .roster-toolbar-search:focus-within .roster-toolbar-search-input {
            border-color: #3b82f6 !important;
        }

        /* Attendance Table (Excel Grid Roster Style) */
        .attendance-roster-table {
            border-collapse: collapse !important;
            border-spacing: 0;
            background: #ffffff;
            font-size: 11px;
            width: 100%;
        }
        .attendance-roster-table th,
        .attendance-roster-table td {
            border: 1px solid #e2e8f0;
            height: 32px !important;
            padding: 0 !important;
            vertical-align: middle;
        }
        .attendance-roster-table tbody tr {
            transition: background-color 0.15s ease;
        }
        .attendance-roster-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .attendance-roster-table tbody tr:hover .sticky-col {
            background-color: #f8fafc !important;
        }
        .attendance-roster-table td.cell-roster-off {
            background-color: #ef4444 !important;
            border-color: #dc2626 !important;
        }
        .attendance-roster-table tbody tr:hover td.cell-roster-off {
            background-color: #dc2626 !important;
        }

        /* 3 Sticky Left Columns */
        .sticky-col {
            position: sticky;
            z-index: 5;
        }
        .first-col {
            left: 0;
            z-index: 5;
            background-color: #ffffff !important;
            width: 36px;
            min-width: 36px;
            max-width: 36px;
        }
        .second-col {
            left: 36px;
            z-index: 5;
            background-color: #ffffff !important;
            width: 160px;
            min-width: 160px;
            max-width: 160px;
        }
        .third-col {
            left: 196px; /* 36px + 160px */
            z-index: 5;
            background-color: #ffffff !important;
            width: 100px;
            min-width: 100px;
            max-width: 100px;
            box-shadow: 4px 0 8px -2px rgba(0, 0, 0, 0.06);
        }
        thead th.first-col,
        thead th.second-col,
        thead th.third-col {
            z-index: 25 !important;
        }

        /* Date Column Compact Width */
        .attendance-roster-table th.date-col,
        .attendance-roster-table td.date-col {
            width: 30px !important;
            min-width: 28px !important;
            max-width: 32px !important;
            padding: 0 !important;
        }

        /* Seamless Full-Cell Attendance Matrix (No Inner Cramped Badges) */
        .att-cell-wrap {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            user-select: none;
            transition: filter 0.1s ease;
        }
        .att-cell-wrap:hover {
            filter: brightness(0.93);
            z-index: 2;
        }

        /* Instant Loading Feedback saat Klik Sel Presensi (Pola Jadwal Kerja) */
        .att-cell-wrap.is-loading {
            pointer-events: none !important;
            filter: brightness(0.88);
            position: relative;
        }
        .att-cell-wrap.is-loading > * {
            opacity: 0.12 !important;
        }
        .att-cell-wrap.is-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 14px;
            height: 14px;
            margin-top: -7px;
            margin-left: -7px;
            border: 2px solid rgba(11, 82, 170, 0.25);
            border-top-color: #0b52aa;
            border-radius: 50%;
            animation: att-cell-spin 0.55s linear infinite;
            z-index: 10;
            box-sizing: border-box;
        }
        @keyframes att-cell-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Table Loading Overlay & Dimming */
        .attendance-table-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(1.5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            pointer-events: none;
            transition: all 0.2s ease;
        }
        .attendance-table-loading-badge {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border-radius: 9999px;
            padding: 8px 20px;
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
        }
        .table-loading-dimmed {
            opacity: 0.5;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }
        .att-cell-present {
            background-color: #dcfce7 !important;
            color: #15803d !important;
        }
        .att-cell-present-manual {
            background-color: #dbeafe !important;
            color: #1d4ed8 !important;
        }
        .att-cell-late {
            background-color: #fef3c7 !important;
            color: #b45309 !important;
        }
        .att-cell-early {
            background-color: #fef3c7 !important;
            color: #b45309 !important;
        }
        .att-cell-cuti {
            background-color: #e0e7ff !important;
            color: #3730a3 !important;
        }
        .att-cell-sakit {
            background-color: #fef9c3 !important;
            color: #854d0e !important;
        }
        .att-cell-izin {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
        }
        .att-cell-alpa {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
        }
        .att-cell-ot-solo {
            background-color: #fffbeb !important;
            color: #b45309 !important;
        }

        /* Top-Right Overtime Ribbon Tag (Seamless in cell) */
        .att-ot-ribbon {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #f59e0b;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: 800;
            line-height: 1;
            padding: 2px 2.5px;
            border-bottom-left-radius: 3px;
            letter-spacing: -0.2px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.12);
        }
        .cell-roster-off .att-ot-ribbon {
            border-left: 1px solid rgba(255, 255, 255, 0.4);
            border-bottom: 1px solid rgba(255, 255, 255, 0.4);
        }

        .att-cell-letter {
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
        }
        .att-cell-check {
            font-size: 12px;
            line-height: 1;
        }
        .att-chip-holiday {
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            font-weight: 700;
            font-size: 10.5px;
            padding: 2.5px 6px;
            border-radius: 5px;
            display: inline-block;
        }
        .summary-badge-hadir {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            border-radius: 5px;
            padding: 2.5px 7px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Custom HTML Fieldset & Legend untuk Modal Presensi */
        fieldset.custom-modal-fieldset {
            border: 1px solid #e4e6fc !important;
            border-radius: 8px !important;
            padding: 12px 14px 14px 14px !important;
            margin-bottom: 16px !important;
            background-color: #ffffff !important;
        }
        legend.custom-modal-legend {
            width: auto !important;
            max-width: none !important;
            padding: 0 8px !important;
            margin-bottom: 0 !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            border-bottom: none !important;
            float: none !important;
            line-height: 1 !important;
        }

        /* Seamless Input Group Clear Buttons matching standard #e4e6fc border */
        #manualAttendanceModal .input-group-append .btn {
            border: 1px solid #e4e6fc !important;
            background-color: #ffffff !important;
            transition: all 0.15s ease-in-out;
        }
        #manualAttendanceModal .input-group-append .btn:hover {
            border-color: #fca5a5 !important;
            background-color: #fef2f2 !important;
        }
    </style>

    <!-- MODAL 1: IMPORT EXCEL FINGERPRINT -->
    <div wire:ignore.self class="modal fade" id="importAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="importAttendanceModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;" role="document">
            <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                <div class="modal-header bg-white border-bottom py-3 px-4">
                    <h5 class="modal-title text-dark font-weight-600 d-flex align-items-center" id="importAttendanceModalLabel" style="font-size: 15.5px; color: #0f172a;">
                        <i class="fas fa-file-excel text-success mr-2" style="font-size: 18px;"></i> Import Laporan Fingerprint
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #475569; opacity: 0.8; font-size: 24px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form wire:submit.prevent="importFingerprint"
                      x-data="{ isUploading: false, progress: 0 }"
                      x-init="$watch('$wire.excelFile', val => { if (val) { isUploading = false; progress = 0; } })"
                      x-on:livewire-upload-start="isUploading = true; progress = 0"
                      x-on:livewire-upload-finish="isUploading = false"
                      x-on:livewire-upload-error="isUploading = false; progress = 0"
                      x-on:livewire-upload-progress="progress = $event.detail.progress">
                    <div class="modal-body py-4 px-4 bg-white">
                        <!-- Petunjuk Format Berkas (.bd-callout) -->
                        <div class="bd-callout bd-callout-info mb-3" role="alert" style="padding: 12px 16px !important; margin-bottom: 16px !important; border-radius: 8px;">
                            <div class="d-flex align-items-start">
                                <div class="bd-callout-icon mr-2.5 mt-0.5 text-primary" style="font-size: 18px;">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="bd-callout-body flex-grow-1">
                                    <div class="bd-callout-title" style="font-size: 13px; font-weight: 600; color: #0b52aa; margin-bottom: 2px;">
                                        Format Berkas Mesin Absensi
                                    </div>
                                    <div class="bd-callout-desc" style="font-size: 12px; color: #334155; line-height: 1.5; font-weight: 400;">
                                        Mendukung berkas <code style="color: #0b52aa; background: #eff6ff; border: 1px solid #bfdbfe; padding: 1px 5px; border-radius: 4px; font-weight: 600;">.xls</code> atau <code style="color: #0b52aa; background: #eff6ff; border: 1px solid #bfdbfe; padding: 1px 5px; border-radius: 4px; font-weight: 600;">.xlsx</code> standar biometric (misal <strong style="color: #0f172a;">1_StandardReport.xls</strong> dengan tab <em>Kartu Laporan</em> maupun <em>Lap. Log Absen</em>).
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dropzone Area Interaktif -->
                        <div class="form-group mb-0">
                            <label class="font-weight-600 mb-1.5" style="font-size: 13px; color: #0f172a;">
                                Berkas Laporan Excel <span class="text-danger">*</span>
                            </label>

                            <!-- Input File Permanen di DOM agar Event Lifecycle Livewire Stabil -->
                            <input type="file" id="excelFileInput" wire:model="excelFile" class="d-none" accept=".xls,.xlsx">

                            @if (! $excelFile)
                                <!-- State 1: Belum Ada Berkas Dipilih -->
                                <div class="upload-dropzone text-center p-4 position-relative"
                                     style="border: 2px dashed #94a3b8; border-radius: 12px; background-color: #fafbfc; transition: all 0.2s ease; cursor: pointer;"
                                     onclick="document.getElementById('excelFileInput').click();"
                                     onmouseover="this.style.borderColor='#0b52aa'; this.style.backgroundColor='#f0f6ff';"
                                     onmouseout="this.style.borderColor='#94a3b8'; this.style.backgroundColor='#fafbfc';">
                                    
                                    <div class="d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px; border-radius: 50%; background-color: #ecfdf5; border: 1px solid #a7f3d0;">
                                        <i class="fas fa-file-excel" style="font-size: 22px; color: #10b981;"></i>
                                    </div>
                                    
                                    <div class="font-weight-600" style="font-size: 14px; color: #0f172a;">
                                        Pilih Berkas Laporan Excel
                                    </div>
                                    <div class="mt-1" style="font-size: 12px; color: #334155; font-weight: 500;">
                                        Klik untuk memilih berkas (.xls, .xlsx maks. 10 MB)
                                    </div>
                                </div>
                            @else
                                <!-- State 2: Berkas Sudah Terpilih -->
                                <div class="p-3 position-relative" style="border: 1.5px solid #86efac; border-radius: 12px; background-color: #f0fdf4;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center min-w-0 mr-2">
                                            <div class="d-inline-flex align-items-center justify-content-center flex-shrink-0 mr-3" style="width: 44px; height: 44px; border-radius: 10px; background-color: #ffffff; border: 1px solid #86efac; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                                <i class="fas fa-file-excel text-success" style="font-size: 22px;"></i>
                                            </div>
                                            <div class="min-w-0" style="line-height: 1.35;">
                                                <div class="font-weight-600 text-truncate" style="font-size: 13.5px; color: #0f172a;" title="{{ $excelFile->getClientOriginalName() }}">
                                                    {{ $excelFile->getClientOriginalName() }}
                                                </div>
                                                <div class="mt-0.5" style="font-size: 12px; color: #334155; font-weight: 500;">
                                                    <span>{{ number_format($excelFile->getSize() / 1024, 1) }} KB</span>
                                                    <span class="mx-1.5" style="color: #64748b;">&bull;</span>
                                                    <span class="text-success font-weight-600"><i class="fas fa-check-circle mr-1"></i>Siap diimpor</span>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" wire:click="$set('excelFile', null)" class="btn btn-sm btn-danger flex-shrink-0 font-weight-500 shadow-sm" style="background-color: #dc2626; border-color: #dc2626; border-radius: 6px; font-size: 11.5px; padding: 5px 12px; color: #ffffff;" title="Ganti Berkas">
                                            <i class="fas fa-times mr-1"></i> Ganti Berkas
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @error('excelFile')
                                <div class="text-danger mt-1.5 d-flex align-items-center" style="font-size: 12px; font-weight: 500;">
                                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Indikator Progress Upload Berkas & Pemrosesan Server -->
                        <div x-show="isUploading && ! $wire.excelFile" class="mt-3 p-3" style="border: 1px solid #bfdbfe; border-radius: 10px; background-color: #f8fafc;" x-cloak>
                            <!-- Sub-Tahap 1A: Pengunggahan Berkas dari Browser (0% - 99%) -->
                            <template x-if="progress < 100">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 12.5px; font-weight: 500; color: #334155;">
                                        <span><i class="fas fa-cloud-upload-alt mr-1.5 text-primary"></i> Mengunggah berkas ke server...</span>
                                        <span class="font-weight-600 text-primary" x-text="`${progress}%`"></span>
                                    </div>
                                    <div class="progress" style="height: 6px; border-radius: 6px; background-color: #e2e8f0; overflow: hidden;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                             role="progressbar"
                                             :style="`width: ${progress}%; transition: width 0.2s ease;`">
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Sub-Tahap 1B: Unggahan 100% Selesai, Server Sedang Memproses & Menyiapkan Berkas -->
                            <template x-if="progress >= 100">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 12.5px; font-weight: 500; color: #0b52aa;">
                                        <span><i class="fas fa-spinner fa-spin mr-1.5 text-primary"></i> Memproses &amp; menyiapkan berkas di server...</span>
                                        <span class="badge badge-primary font-weight-500" style="font-size: 10.5px; background-color: #0b52aa;">100% Selesai</span>
                                    </div>
                                    <div class="progress mb-1.5" style="height: 6px; border-radius: 6px; background-color: #e2e8f0; overflow: hidden;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                             role="progressbar"
                                             style="width: 100%;">
                                        </div>
                                    </div>
                                    <div class="text-center" style="font-size: 11.5px; color: #475569; font-weight: 400;">
                                        Unggahan selesai 100%. Sedang membaca metadata berkas...
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Indikator Progress Tahap 2: Pemrosesan Data & Pencocokan PIN Log Absensi -->
                        <div wire:loading wire:target="importFingerprint" class="mt-3 p-3" style="border: 1px solid #bfdbfe; border-radius: 10px; background-color: #f8fafc;">
                            <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 12.5px; font-weight: 500; color: #334155;">
                                <span><i class="fas fa-cog fa-spin mr-1.5 text-primary"></i> Membaca sheet &amp; memetakan log presensi...</span>
                                <span class="badge badge-primary font-weight-500" style="font-size: 10.5px; background-color: #0b52aa;">Sedang Proses</span>
                            </div>
                            <div class="progress mb-2" style="height: 6px; border-radius: 6px; background-color: #e2e8f0; overflow: hidden;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                     role="progressbar"
                                     style="width: 100%;">
                                </div>
                            </div>
                            <div class="text-center" style="font-size: 11.5px; color: #475569; font-weight: 400;">
                                Mohon tunggu, sistem sedang memetakan data absensi &amp; jam lembur karyawan...
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary font-weight-500 shadow-sm" wire:loading.attr="disabled" wire:target="importFingerprint,excelFile" @if (! $excelFile) disabled @endif :disabled="isUploading" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; font-size: 13.5px; padding: 9px 24px; color: #ffffff;">
                            <span wire:loading.remove wire:target="importFingerprint">
                                <i class="fas fa-upload mr-1.5"></i> Proses &amp; Simpan Log
                            </span>
                            <span wire:loading wire:target="importFingerprint">
                                <i class="fas fa-spinner fa-spin mr-1.5"></i> Memproses Data...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: EDIT PRESENSI & AUDIT FOTO / GPS -->
    <div wire:ignore.self class="modal fade" id="manualAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="manualAttendanceModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered attendance-dual-modal-dialog" role="document">
            <div class="attendance-mobile-slide-track">
            <div class="modal-content attendance-edit-modal-content">
                <form wire:submit.prevent="saveManualAttendance" class="attendance-edit-modal-form">
                    <div class="modal-header attendance-edit-modal-header">
                        <div class="d-flex align-items-center min-width-0">
                            <div class="attendance-modal-icon mr-3">
                                <i class="fas fa-edit" aria-hidden="true"></i>
                            </div>
                            <div class="min-width-0">
                                <h5 class="modal-title attendance-edit-modal-title" id="manualAttendanceModalLabel">
                                    {{ $editingAttendanceId ? 'Edit Catatan Presensi' : 'Input Presensi Manual' }}
                                </h5>
                                <div class="attendance-edit-modal-subtitle">Periksa detail presensi sebelum menyimpan perubahan.</div>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body attendance-edit-modal-body">

                                    @if ($editingAttendanceId && $editingAttendance)
                                        <!-- Ringkasan Karyawan -->
                                        <div class="attendance-employee-summary">
                                            <div class="attendance-employee-avatar">
                                                {{ $editingAttendance->employee?->initials ?? 'K' }}
                                            </div>
                                            <div class="attendance-employee-details">
                                                <div class="attendance-employee-name">
                                                    {{ $editingAttendance->employee?->full_name }}
                                                </div>
                                                <div class="attendance-employee-role">
                                                    {{ $editingAttendance->employee?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $editingAttendance->employee?->position?->name ?? 'Staff' }}
                                                </div>
                                                <div class="attendance-employee-meta">
                                                    Tanggal Presensi: {{ $editingAttendance->date?->format('d M Y') }}
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-block attendance-mobile-proof-open" data-attendance-mobile-proof-open>
                                            <i class="fas fa-map-marked-alt mr-1" aria-hidden="true"></i> Bukti Foto &amp; Lokasi GPS
                                        </button>
                                    @endif

                                    @unless ($editingAttendanceId)
                                        <!-- Pilih Karyawan (Select2 Searchable dengan optgroup status kerja) -->
                                        <div class="form-group mb-3">
                                            <label for="manualEmployeeId" class="font-weight-bold" style="font-size: 12.5px; color: #0f172a;">Karyawan <span class="text-danger">*</span></label>
                                            <div wire:ignore>
                                                <select id="manualEmployeeId" class="form-control select2 @error('manualEmployeeId') is-invalid @enderror" style="width: 100%;" {{ $lockDateAndEmployee ? 'disabled' : '' }}>
                                                    <option value="">-- Pilih Karyawan --</option>
                                                    @foreach ($allEmployees->groupBy(fn($e) => $e->employment_status instanceof \App\Enums\EmploymentStatus ? $e->employment_status->label() : (\App\Enums\EmploymentStatus::tryFrom((string) $e->employment_status)?->label() ?? 'Lainnya')) as $statusLabel => $groupEmps)
                                                        <optgroup label="{{ $statusLabel }}">
                                                            @foreach ($groupEmps as $emp)
                                                                <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('manualEmployeeId')
                                                <div class="text-danger text-xs mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Tanggal Presensi -->
                                        <div class="form-group mb-3">
                                            <label for="manualDate" class="font-weight-bold" style="font-size: 12.5px; color: #0f172a;">Tanggal Presensi <span class="text-danger">*</span></label>
                                            <input type="date" id="manualDate" wire:model.live="manualDate" class="form-control @error('manualDate') is-invalid @enderror" {{ $lockDateAndEmployee ? 'disabled' : '' }} style="border-radius: 8px; border-color: #e2e8f0;">
                                            @error('manualDate')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endunless

                                    @if ($manualStatus === 'libur')
                                        <!-- Informasi Hari Libur / Off (Pure White) -->
                                        <div class="p-3 mb-3 bg-white border" style="border-left: 4px solid #0b52aa !important; border-color: #e2e8f0; border-radius: 8px;">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar-day mr-2 text-primary" style="font-size: 15px;"></i>
                                                <span style="font-size: 13px; font-weight: 700; color: #0f172a;">Jadwal Hari Libur / Off</span>
                                            </div>
                                            <div class="mt-1" style="font-size: 12px; color: #475569; line-height: 1.4;">
                                                Tanggal ini merupakan hari libur. Secara default karyawan libur. Centang checklist di bawah jika ada penugasan lembur.
                                            </div>
                                        </div>

                                        <!-- Fieldset Checklist: Apakah Lembur Seharian? -->
                                        <fieldset class="custom-modal-fieldset bg-white mb-3" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                                            <legend class="custom-modal-legend" style="background: #ffffff; padding: 0 8px; font-size: 12.5px; font-weight: 500; color: #0f172a;">
                                                <div class="custom-control custom-checkbox d-inline-block">
                                                    <input type="checkbox" class="custom-control-input" id="hasFullDayOvertimeCheck" wire:model.live="hasFullDayOvertime">
                                                    <label class="custom-control-label font-weight-bold mb-0" for="hasFullDayOvertimeCheck" style="cursor: pointer; font-size: 12.5px; color: #0f172a;">
                                                        Apakah Lembur Seharian?
                                                    </label>
                                                </div>
                                            </legend>

                                            @if ($hasFullDayOvertime)
                                                <div class="row attendance-time-range-row">
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualClockIn" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Jam Masuk</label>
                                                                @if ($manualClockIn)
                                                                    <a href="javascript:void(0)" wire:click="clearClockIn" wire:loading.attr="disabled" wire:target="clearClockIn" class="text-danger font-weight-bold" style="font-size: 11px;">
                                                                        <span wire:loading.remove wire:target="clearClockIn">Hapus</span>
                                                                        <span wire:loading wire:target="clearClockIn"><i class="fas fa-spinner fa-spin"></i></span>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                            <div class="input-group">
                                                                <input type="text" id="manualClockIn" wire:model="manualClockIn" placeholder="00:00" class="form-control timepicker-24 @error('manualClockIn') is-invalid @enderror" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text bg-white" style="border-color: #e2e8f0; cursor: pointer;" onclick="this.closest('.input-group').querySelector('input')._flatpickr?.open()">
                                                                        <i class="far fa-clock text-primary"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualClockOut" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Jam Keluar</label>
                                                                @if ($manualClockOut)
                                                                    <a href="javascript:void(0)" wire:click="clearClockOut" wire:loading.attr="disabled" wire:target="clearClockOut" class="text-danger font-weight-bold" style="font-size: 11px;">
                                                                        <span wire:loading.remove wire:target="clearClockOut">Hapus</span>
                                                                        <span wire:loading wire:target="clearClockOut"><i class="fas fa-spinner fa-spin"></i></span>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                            <div class="input-group">
                                                                <input type="text" id="manualClockOut" wire:model="manualClockOut" placeholder="00:00" class="form-control timepicker-24 @error('manualClockOut') is-invalid @enderror" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text bg-white" style="border-color: #e2e8f0; cursor: pointer;" onclick="this.closest('.input-group').querySelector('input')._flatpickr?.open()">
                                                                        <i class="far fa-clock text-primary"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </fieldset>

                                        <!-- Fieldset Checklist: Apakah Lembur Per Jam? -->
                                        <fieldset class="custom-modal-fieldset bg-white mb-3" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                                            <legend class="custom-modal-legend" style="background: #ffffff; padding: 0 8px; font-size: 12.5px; font-weight: 500; color: #0f172a;">
                                                <div class="custom-control custom-checkbox d-inline-block">
                                                    <input type="checkbox" class="custom-control-input" id="hasOvertimeCheck" wire:model.live="hasOvertime">
                                                    <label class="custom-control-label mb-0" for="hasOvertimeCheck" style="cursor: pointer; font-size: 12.5px; font-weight: 500; color: #0f172a;">
                                                        Apakah Lembur Per Jam?
                                                    </label>
                                                </div>
                                            </legend>

                                            @if ($hasOvertime)
                                                <div class="row attendance-time-range-row">
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualOvertimeIn" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Lembur Mulai</label>
                                                                @if ($manualOvertimeIn)
                                                                    <a href="javascript:void(0)" wire:click="clearOvertimeIn" wire:loading.attr="disabled" wire:target="clearOvertimeIn" class="text-danger font-weight-bold" style="font-size: 11px;">
                                                                        <span wire:loading.remove wire:target="clearOvertimeIn">Hapus</span>
                                                                        <span wire:loading wire:target="clearOvertimeIn"><i class="fas fa-spinner fa-spin"></i></span>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                            <div class="input-group">
                                                                <input type="text" id="manualOvertimeIn" wire:model="manualOvertimeIn" placeholder="00:00" class="form-control timepicker-24 @error('manualOvertimeIn') is-invalid @enderror" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text bg-white" style="border-color: #e2e8f0; cursor: pointer;" onclick="this.closest('.input-group').querySelector('input')._flatpickr?.open()">
                                                                        <i class="far fa-clock text-primary"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualOvertimeOut" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Lembur Selesai</label>
                                                                @if ($manualOvertimeOut)
                                                                    <a href="javascript:void(0)" wire:click="clearOvertimeOut" wire:loading.attr="disabled" wire:target="clearOvertimeOut" class="text-danger font-weight-bold" style="font-size: 11px;">
                                                                        <span wire:loading.remove wire:target="clearOvertimeOut">Hapus</span>
                                                                        <span wire:loading wire:target="clearOvertimeOut"><i class="fas fa-spinner fa-spin"></i></span>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                            <div class="input-group">
                                                                <input type="text" id="manualOvertimeOut" wire:model="manualOvertimeOut" placeholder="00:00" class="form-control timepicker-24 @error('manualOvertimeOut') is-invalid @enderror" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text bg-white" style="border-color: #e2e8f0; cursor: pointer;" onclick="this.closest('.input-group').querySelector('input')._flatpickr?.open()">
                                                                        <i class="far fa-clock text-primary"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </fieldset>

                                    @else
                                        <!-- TAMPILAN HARI KERJA BIASA -->
                                        <!-- Fieldset Status Kehadiran -->
                                        <fieldset class="custom-modal-fieldset bg-white mb-3" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                                            <legend class="custom-modal-legend" style="background: #ffffff; padding: 0 8px; font-size: 12.5px; font-weight: 700; color: #0f172a;">
                                                Status Kehadiran <span class="text-danger">*</span>
                                            </legend>

                                            <!-- Select Option Status Kehadiran -->
                                            <div class="form-group mb-2.5">
                                                <select id="manualStatus" wire:model.live="manualStatus" class="form-control @error('manualStatus') is-invalid @enderror" style="border-radius: 8px; border-color: #e2e8f0;">
                                                    <option value="hadir">Hadir (H)</option>
                                                    <option value="cuti">Cuti (C)</option>
                                                    <option value="sakit">Sakit (S)</option>
                                                    <option value="izin">Izin (I)</option>
                                                    <option value="absen">Alpa / Mangkir (A)</option>
                                                    <option value="libur">Hari Libur</option>
                                                </select>
                                                @error('manualStatus')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Input Jam Masuk & Jam Keluar -->
                                            @if ($manualStatus === 'hadir')
                                                <div class="row attendance-time-range-row">
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualClockIn" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">
                                                                    Jam Masuk <span class="text-danger">*</span>
                                                                </label>
                                                                @if ($manualClockIn)
                                                                    <a href="javascript:void(0)" wire:click="clearClockIn" wire:loading.attr="disabled" wire:target="clearClockIn" class="text-danger font-weight-bold" style="font-size: 11px;">
                                                                        <span wire:loading.remove wire:target="clearClockIn">Hapus</span>
                                                                        <span wire:loading wire:target="clearClockIn"><i class="fas fa-spinner fa-spin"></i></span>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                            <div class="input-group">
                                                                <input type="text" id="manualClockIn" wire:model="manualClockIn" placeholder="00:00" class="form-control timepicker-24 @error('manualClockIn') is-invalid @enderror" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text bg-white" style="border-color: #e2e8f0; cursor: pointer;" onclick="this.closest('.input-group').querySelector('input')._flatpickr?.open()">
                                                                        <i class="far fa-clock text-primary"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualClockOut" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Jam Keluar</label>
                                                                @if ($manualClockOut)
                                                                    <a href="javascript:void(0)" wire:click="clearClockOut" wire:loading.attr="disabled" wire:target="clearClockOut" class="text-danger font-weight-bold" style="font-size: 11px;">
                                                                        <span wire:loading.remove wire:target="clearClockOut">Hapus</span>
                                                                        <span wire:loading wire:target="clearClockOut"><i class="fas fa-spinner fa-spin"></i></span>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                            <div class="input-group">
                                                                <input type="text" id="manualClockOut" wire:model="manualClockOut" placeholder="00:00" class="form-control timepicker-24 @error('manualClockOut') is-invalid @enderror" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text bg-white" style="border-color: #e2e8f0; cursor: pointer;" onclick="this.closest('.input-group').querySelector('input')._flatpickr?.open()">
                                                                        <i class="far fa-clock text-primary"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </fieldset>

                                        <!-- Fieldset Lembur -->
                                        <fieldset class="custom-modal-fieldset bg-white mb-3" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                                            <legend class="custom-modal-legend" style="background: #ffffff; padding: 0 8px; font-size: 12.5px; font-weight: 700; color: #0f172a;">
                                                <div class="custom-control custom-checkbox d-inline-block">
                                                    <input type="checkbox" class="custom-control-input" id="hasOvertimeCheck" wire:model.live="hasOvertime">
                                                    <label class="custom-control-label font-weight-bold mb-0" for="hasOvertimeCheck" style="cursor: pointer; font-size: 12.5px; color: #0f172a;">
                                                        Apakah Karyawan Lembur?
                                                    </label>
                                                </div>
                                            </legend>

                                            @if ($hasOvertime)
                                                <div class="row attendance-time-range-row">
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualOvertimeIn" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Lembur Mulai</label>
                                                                @if ($manualOvertimeIn)
                                                                    <a href="javascript:void(0)" wire:click="clearOvertimeIn" wire:loading.attr="disabled" wire:target="clearOvertimeIn" class="text-danger font-weight-bold" style="font-size: 11px;">
                                                                        <span wire:loading.remove wire:target="clearOvertimeIn">Hapus</span>
                                                                        <span wire:loading wire:target="clearOvertimeIn"><i class="fas fa-spinner fa-spin"></i></span>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                            <div class="input-group">
                                                                <input type="text" id="manualOvertimeIn" wire:model="manualOvertimeIn" placeholder="00:00" class="form-control timepicker-24 @error('manualOvertimeIn') is-invalid @enderror" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text bg-white" style="border-color: #e2e8f0; cursor: pointer;" onclick="this.closest('.input-group').querySelector('input')._flatpickr?.open()">
                                                                        <i class="far fa-clock text-primary"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualOvertimeOut" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Lembur Selesai</label>
                                                                @if ($manualOvertimeOut)
                                                                    <a href="javascript:void(0)" wire:click="clearOvertimeOut" wire:loading.attr="disabled" wire:target="clearOvertimeOut" class="text-danger font-weight-bold" style="font-size: 11px;">
                                                                        <span wire:loading.remove wire:target="clearOvertimeOut">Hapus</span>
                                                                        <span wire:loading wire:target="clearOvertimeOut"><i class="fas fa-spinner fa-spin"></i></span>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                            <div class="input-group">
                                                                <input type="text" id="manualOvertimeOut" wire:model="manualOvertimeOut" placeholder="00:00" class="form-control timepicker-24 @error('manualOvertimeOut') is-invalid @enderror" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text bg-white" style="border-color: #e2e8f0; cursor: pointer;" onclick="this.closest('.input-group').querySelector('input')._flatpickr?.open()">
                                                                        <i class="far fa-clock text-primary"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </fieldset>
                                    @endif

                                    <!-- Catatan / Keterangan -->
                                    <div class="form-group mb-2">
                                        <label for="manualNotes" class="font-weight-bold" style="font-size: 12px; color: #0f172a;">Catatan / Keterangan</label>
                                        <textarea id="manualNotes" wire:model="manualNotes" class="form-control" rows="2" placeholder="Alasan koreksi presensi..." style="border-radius: 8px; border-color: #e2e8f0;"></textarea>
                                    </div>
                                </div>

                                <div class="modal-footer attendance-edit-modal-footer">
                                    @if ($editingAttendanceId)
                                        <div class="attendance-edit-modal-actions">
                                            <button type="button" wire:click="confirmDeleteAttendance({{ $editingAttendanceId }})" wire:loading.attr="disabled" wire:target="confirmDeleteAttendance,deleteAttendance,saveManualAttendance" class="btn btn-danger font-weight-600">
                                                    <span wire:loading.remove wire:target="confirmDeleteAttendance,deleteAttendance"><i class="fas fa-trash-alt mr-1"></i> Hapus Presensi</span>
                                                    <span wire:loading wire:target="confirmDeleteAttendance,deleteAttendance"><i class="fas fa-spinner fa-spin mr-1"></i> Menghapus...</span>
                                            </button>
                                            <button type="submit" wire:loading.attr="disabled" wire:target="saveManualAttendance,confirmDeleteAttendance,deleteAttendance" class="btn btn-primary font-weight-600">
                                                    <span wire:loading.remove wire:target="saveManualAttendance"><i class="fas fa-save mr-1"></i> Simpan Perubahan</span>
                                                    <span wire:loading wire:target="saveManualAttendance"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                                            </button>
                                        </div>
                                    @else
                                        <button type="submit" wire:loading.attr="disabled" wire:target="saveManualAttendance" class="btn btn-primary btn-block font-weight-600">
                                            <span wire:loading.remove wire:target="saveManualAttendance"><i class="fas fa-save mr-1"></i> Simpan Presensi</span>
                                            <span wire:loading wire:target="saveManualAttendance"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                                        </button>
                                    @endif
                                </div>
                </form>
            </div>

                    <!-- PANEL KANAN (col-lg-5): Floating Bukti Foto & Lokasi GPS -->
                    <div class="card attendance-proof-window">
                        <div class="card-header attendance-proof-header">
                            <div class="d-flex align-items-center min-width-0">
                                <div class="attendance-proof-icon attendance-proof-map-icon mr-3">
                                    <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
                                </div>
                                <div class="min-width-0">
                                    <h5 class="card-title attendance-proof-title">Bukti Foto & Lokasi GPS</h5>
                                    <div class="attendance-proof-subtitle">Verifikasi bukti dan titik presensi.</div>
                                </div>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="card-body attendance-proof-body">
                                @if ($editingAttendanceId && $editingAttendance)
                                    <div class="attendance-employee-summary attendance-mobile-proof-identity">
                                        <div class="attendance-employee-avatar">
                                            {{ $editingAttendance->employee?->initials ?? 'K' }}
                                        </div>
                                        <div class="attendance-employee-details">
                                            <div class="attendance-employee-name">{{ $editingAttendance->employee?->full_name }}</div>
                                            <div class="attendance-employee-role">{{ $editingAttendance->employee?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $editingAttendance->employee?->position?->name ?? 'Staff' }}</div>
                                            <div class="attendance-employee-meta">Tanggal Presensi: {{ $editingAttendance->date?->format('d M Y') }}</div>
                                        </div>
                                    </div>
                                @endif
                                @if ($auditAttendance)
                                    <ul class="nav nav-pills nav-justified attendance-audit-tabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button type="button" id="attendanceClockTab" class="nav-link {{ in_array($auditTab, ['clock_in', 'clock_out'], true) ? 'active' : '' }}" data-toggle="pill" data-target="#attendanceClockPanel" wire:click="setAuditTab('clock_in')" role="tab" aria-controls="attendanceClockPanel" aria-selected="{{ in_array($auditTab, ['clock_in', 'clock_out'], true) ? 'true' : 'false' }}">
                                                <i class="fas fa-camera mr-1" aria-hidden="true"></i>Masuk &amp; Pulang
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button type="button" id="attendanceOvertimeTab" class="nav-link {{ $auditTab === 'overtime' ? 'active' : '' }}" data-toggle="pill" data-target="#attendanceOvertimePanel" wire:click="setAuditTab('overtime')" role="tab" aria-controls="attendanceOvertimePanel" aria-selected="{{ $auditTab === 'overtime' ? 'true' : 'false' }}">
                                                <i class="fas fa-stopwatch mr-1" aria-hidden="true"></i>Lembur
                                            </button>
                                        </li>
                                    </ul>

                                    @if ($auditAttendance->isPendingReview())
                                        <!-- Review Alert Box (Pure White) -->
                                        <div class="p-3 mb-3 bg-white border" style="border-radius: 12px; border-color: #fde68a !important; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                                            <div class="d-flex align-items-center mb-1 font-weight-700" style="font-size: 12px; color: #b45309;">
                                                <i class="fas fa-exclamation-triangle mr-1.5"></i> {{ $auditAttendance->employee?->employment_status === \App\Enums\EmploymentStatus::HARIAN ? 'Menunggu Persetujuan Admin' : 'Perlu Review Lokasi GPS' }}
                                            </div>
                                            @if ($auditAttendance->employee?->employment_status === \App\Enums\EmploymentStatus::HARIAN)
                                                <div class="mb-2" style="font-size: 11.5px; color: #1e293b; line-height: 1.4;">
                                                    Pilihan karyawan: <strong>{{ $auditAttendance->casual_work_type === 'hourly' ? 'Kerja per jam' : 'Kerja harian' }}</strong>
                                                    @if ($auditAttendance->casual_work_type === 'daily' && $auditAttendance->early_departure_reason)
                                                        <br>Pulang lebih awal: {{ $auditAttendance->early_departure_reason }}
                                                    @endif
                                                    @if ($auditAttendance->casual_work_type === 'hourly' || $auditConfirmedCasualWorkType === 'hourly')
                                                        <br>Durasi tercatat: {{ intdiv((int) $auditAttendance->duration_minutes, 60) }} jam {{ (int) $auditAttendance->duration_minutes % 60 }} menit
                                                        <br>Pembayaran dihitung otomatis mengikuti pembulatan di Profil Perusahaan.
                                                    @endif
                                                </div>
                                                <div class="form-group mb-2">
                                                    <label for="auditConfirmedCasualWorkType">Jenis kerja yang disetujui Admin <span class="text-danger">*</span></label>
                                                    <select id="auditConfirmedCasualWorkType" class="form-control @error('auditConfirmedCasualWorkType') is-invalid @enderror" wire:model.live="auditConfirmedCasualWorkType">
                                                        <option value="">Pilih jenis kerja</option>
                                                        <option value="daily">Kerja harian</option>
                                                        <option value="hourly">Kerja per jam</option>
                                                    </select>
                                                    @error('auditConfirmedCasualWorkType') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    <small class="form-text text-muted">Periksa pilihan karyawan. Ubah jika jenis kerja sebenarnya berbeda.</small>
                                                </div>
                                                @if ($auditConfirmedCasualWorkType === 'daily')
                                                    <div class="form-group mb-2">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" id="auditCasualPayOverrideEnabled" class="custom-control-input" wire:model.live="auditCasualPayOverrideEnabled">
                                                            <label class="custom-control-label" for="auditCasualPayOverrideEnabled">Sesuaikan nilai pembayaran</label>
                                                        </div>
                                                    </div>
                                                    @if ($auditCasualPayOverrideEnabled)
                                                        <div class="form-group mb-2">
                                                            <label for="auditCasualPayOverride">Nilai yang dibayarkan (Rp) <span class="text-danger">*</span></label>
                                                            <input type="number" min="0" step="1000" id="auditCasualPayOverride" class="form-control @error('auditCasualPayOverride') is-invalid @enderror" wire:model="auditCasualPayOverride" placeholder="Masukkan nominal pembayaran">
                                                            @error('auditCasualPayOverride') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                        </div>
                                                        <div class="form-group mb-2">
                                                            <label for="auditCasualPayReason">Alasan penyesuaian <span class="text-danger">*</span></label>
                                                            <textarea id="auditCasualPayReason" rows="2" maxlength="1000" class="form-control @error('auditCasualPayReason') is-invalid @enderror" wire:model="auditCasualPayReason" placeholder="Contoh: pulang lebih awal karena keadaan darurat keluarga"></textarea>
                                                            @error('auditCasualPayReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                        </div>
                                                    @endif
                                                @endif
                                            @endif
                                            @if ($auditAttendance->notes)
                                                <div class="mb-2" style="font-size: 11.5px; color: #1e293b; line-height: 1.4;">
                                                    <strong>Keterangan Karyawan:</strong> {{ $auditAttendance->notes }}
                                                </div>
                                            @endif
                                            <div class="d-flex" style="gap: 8px;">
                                                <button type="button" wire:click="approveAudit" wire:loading.attr="disabled" wire:target="approveAudit,rejectAudit" class="btn btn-sm btn-success flex-fill font-weight-600" style="border-radius: 6px; font-size: 11.5px; padding: 6px;">
                                                    <span wire:loading.remove wire:target="approveAudit"><i class="fas fa-check mr-1"></i> Setujui Valid</span>
                                                    <span wire:loading wire:target="approveAudit"><i class="fas fa-spinner fa-spin mr-1"></i> Memproses...</span>
                                                </button>
                                                <button type="button" wire:click="rejectAudit" wire:loading.attr="disabled" wire:target="approveAudit,rejectAudit" class="btn btn-sm btn-danger flex-fill font-weight-600 attendance-audit-reject-button" style="border-radius: 6px; font-size: 11.5px; padding: 6px;">
                                                    <span wire:loading.remove wire:target="rejectAudit"><i class="fas fa-times mr-1"></i> Tolak (Alpa)</span>
                                                    <span wire:loading wire:target="rejectAudit"><i class="fas fa-spinner fa-spin mr-1"></i> Memproses...</span>
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="tab-content attendance-audit-tab-content">
                                        <!-- TAB 1: MASUK & PULANG -->
                                        <div class="tab-pane fade attendance-audit-tab-pane {{ $auditTab !== 'overtime' ? 'show active' : '' }}" id="attendanceClockPanel" role="tabpanel" aria-labelledby="attendanceClockTab">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="attendance-audit-section-heading">
                                                        <span>Foto Masuk</span>
                                                        <span class="badge badge-light attendance-audit-time">{{ $auditAttendance->clock_in ? substr($auditAttendance->clock_in, 0, 5) . ' WIB' : '-' }}</span>
                                                    </div>
                                                    <div class="position-relative attendance-audit-photo-frame attendance-audit-photo-frame-small">
                                                        @if ($auditAttendance->clock_in_photo_url)
                                                            <a href="{{ $auditAttendance->clock_in_photo_url }}" target="_blank" title="Klik untuk memperbesar" class="d-block w-100 h-100">
                                                                <img src="{{ $auditAttendance->clock_in_photo_url }}" alt="Selfie Masuk" class="attendance-audit-photo-image" onerror="this.parentElement.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                            </a>
                                                            <div class="attendance-audit-photo-distance">
                                                                <i class="fas fa-map-marker-alt text-warning mr-1"></i> {{ $auditAttendance->clock_in_distance_meters ?? 0 }}m dari kantor
                                                            </div>
                                                            <div class="photo-error-fallback attendance-audit-photo-empty">
                                                                <i class="fas fa-image" aria-hidden="true"></i>
                                                                <strong>Foto tidak dapat dimuat</strong>
                                                                <span>Periksa kembali berkas bukti presensi.</span>
                                                            </div>
                                                        @else
                                                            <div class="attendance-audit-photo-empty">
                                                                <i class="fas fa-camera-slash" aria-hidden="true"></i>
                                                                <strong>Foto masuk tidak tersedia</strong>
                                                                <span>Tidak ada foto selfie pada catatan ini.</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="attendance-audit-section-heading">
                                                        <span>Foto Pulang</span>
                                                        <span class="badge badge-light attendance-audit-time">{{ $auditAttendance->clock_out ? substr($auditAttendance->clock_out, 0, 5) . ' WIB' : '-' }}</span>
                                                    </div>
                                                    <div class="position-relative attendance-audit-photo-frame attendance-audit-photo-frame-small">
                                                        @if ($auditAttendance->clock_out_photo_url)
                                                            <a href="{{ $auditAttendance->clock_out_photo_url }}" target="_blank" title="Klik untuk memperbesar" class="d-block w-100 h-100">
                                                                <img src="{{ $auditAttendance->clock_out_photo_url }}" alt="Selfie Pulang" class="attendance-audit-photo-image" onerror="this.parentElement.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                            </a>
                                                            <div class="attendance-audit-photo-distance">
                                                                <i class="fas fa-map-marker-alt text-warning mr-1"></i> {{ $auditAttendance->clock_out_distance_meters ?? 0 }}m dari kantor
                                                            </div>
                                                            <div class="photo-error-fallback attendance-audit-photo-empty">
                                                                <i class="fas fa-image" aria-hidden="true"></i>
                                                                <strong>Foto tidak dapat dimuat</strong>
                                                                <span>Periksa kembali berkas bukti presensi.</span>
                                                            </div>
                                                        @else
                                                            <div class="attendance-audit-photo-empty">
                                                                <i class="fas fa-camera-slash" aria-hidden="true"></i>
                                                                <strong>Foto pulang tidak tersedia</strong>
                                                                <span>Tidak ada foto selfie pada catatan ini.</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB 3: LEMBUR -->
                                        <div class="tab-pane fade attendance-audit-tab-pane {{ $auditTab === 'overtime' ? 'show active' : '' }}" id="attendanceOvertimePanel" role="tabpanel" aria-labelledby="attendanceOvertimeTab">
                                            <div class="attendance-overtime-summary">
                                                <div>
                                                    <span class="attendance-overtime-label">Jam lembur</span>
                                                    <strong class="attendance-overtime-value">
                                                        {{ $auditAttendance->overtime_in ? substr($auditAttendance->overtime_in, 0, 5) : '-' }} WIB &mdash; {{ $auditAttendance->overtime_out ? substr($auditAttendance->overtime_out, 0, 5) : '-' }} WIB
                                                    </strong>
                                                </div>
                                                <div class="text-right">
                                                    <span class="attendance-overtime-label">Total durasi</span>
                                                    <strong class="attendance-overtime-duration">
                                                        {{ $auditAttendance->overtime_minutes ? intdiv($auditAttendance->overtime_minutes, 60).'j '.($auditAttendance->overtime_minutes % 60).'m' : '-' }}
                                                    </strong>
                                                </div>
                                            </div>

                                            <div class="row attendance-audit-overtime-photos">
                                                <div class="col-6">
                                                    <div class="attendance-audit-overtime-photo-title">Mulai lembur</div>
                                                    <div class="position-relative attendance-audit-photo-frame attendance-audit-photo-frame-small">
                                                        @if ($auditAttendance->overtime_in_photo_url)
                                                            <a href="{{ $auditAttendance->overtime_in_photo_url }}" target="_blank" class="d-block w-100 h-100">
                                                                <img src="{{ $auditAttendance->overtime_in_photo_url }}" alt="Mulai Lembur" class="attendance-audit-photo-image" onerror="this.parentElement.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                            </a>
                                                            <div class="photo-error-fallback attendance-audit-photo-empty">
                                                                <i class="fas fa-image" aria-hidden="true"></i>
                                                                <strong>Foto tidak dapat dimuat</strong>
                                                            </div>
                                                        @else
                                                            <div class="attendance-audit-photo-empty">
                                                                <i class="fas fa-camera-slash" aria-hidden="true"></i>
                                                                <strong>Foto tidak tersedia</strong>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="attendance-audit-overtime-photo-title">Selesai lembur</div>
                                                    <div class="position-relative attendance-audit-photo-frame attendance-audit-photo-frame-small">
                                                        @if ($auditAttendance->overtime_out_photo_url)
                                                            <a href="{{ $auditAttendance->overtime_out_photo_url }}" target="_blank" class="d-block w-100 h-100">
                                                                <img src="{{ $auditAttendance->overtime_out_photo_url }}" alt="Selesai Lembur" class="attendance-audit-photo-image" onerror="this.parentElement.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                            </a>
                                                            <div class="photo-error-fallback attendance-audit-photo-empty">
                                                                <i class="fas fa-image" aria-hidden="true"></i>
                                                                <strong>Foto tidak dapat dimuat</strong>
                                                            </div>
                                                        @else
                                                            <div class="attendance-audit-photo-empty">
                                                                <i class="fas fa-camera-slash" aria-hidden="true"></i>
                                                                <strong>Foto tidak tersedia</strong>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div wire:ignore>
                                        <!-- Interactive Leaflet Map -->
                                        <div class="attendance-audit-map-section">
                                            <div class="attendance-audit-section-heading">
                                                <span>Lokasi presensi</span>
                                                <span id="auditMapStatusBadge" class="badge badge-light attendance-audit-time">Memuat GPS...</span>
                                            </div>
                                            <div id="auditLeafletMap" class="attendance-audit-map"></div>
                                        </div>

                                        <div class="attendance-audit-location-list">
                                            <div class="attendance-audit-location-row">
                                                <span class="attendance-audit-location-label">Lokasi acuan</span>
                                                <span id="auditMapOfficeLabel" class="attendance-audit-location-value">-</span>
                                            </div>
                                            <div class="attendance-audit-location-row">
                                                <span class="attendance-audit-location-label">Jarak dari kantor</span>
                                                <span id="auditMapDistanceText" class="attendance-audit-location-value text-primary">-</span>
                                            </div>
                                            <div class="attendance-audit-location-row">
                                                <span class="attendance-audit-location-label">Koordinat presensi</span>
                                                <span id="auditMapCoordinatesText" class="attendance-audit-location-value" title="Koordinat presensi">-</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="attendance-proof-empty-state d-flex flex-column align-items-center justify-content-center h-100 text-center">
                                        <div class="attendance-proof-empty-icon mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fas fa-map-marked-alt" style="font-size: 22px;"></i>
                                        </div>
                                        <div class="attendance-proof-empty-title">
                                            Input Presensi Baru
                                        </div>
                                        <div class="attendance-proof-empty-copy">
                                            Bukti foto selfie dan koordinat GPS akan tercatat otomatis saat presensi dilakukan via portal mandiri karyawan.
                                        </div>
                                    </div>
                                @endif
                        </div>
                        <div class="modal-footer attendance-proof-mobile-footer">
                            <button type="button" class="btn btn-primary btn-block attendance-proof-return-button" data-attendance-mobile-proof-back>
                                <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Kembali ke Form
                            </button>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: AUDIT PRESENSI (SELFIE & PETA GPS) -->
    @include('livewire.attendances.audit-modal')
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/leaflet/leaflet.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/leaflet/leaflet.js') }}"></script>
<script>
    (function () {
        function initAttendanceScripts() {
            // Izinkan focus Select2 di dalam modal Bootstrap
            if ($.fn.modal && $.fn.modal.Constructor) {
                $.fn.modal.Constructor.prototype._enforceFocus = function () {};
            }

            const initSelect2 = () => {
                const $select = $('#manualEmployeeId');
                if ($select.length) {
                    $select.select2({
                        dropdownParent: $('#manualAttendanceModal'),
                        width: '100%',
                        placeholder: '-- Pilih Karyawan --',
                        allowClear: false
                    }).off('change.manualEmployee').on('change.manualEmployee', function () {
                        const val = $(this).val();
                        @this.set('manualEmployeeId', val ? parseInt(val) : null);
                    });
                }
            };

            initSelect2();

            const syncMobileSlideHeight = () => {
                const modal = document.getElementById('manualAttendanceModal');
                const track = modal?.querySelector('.attendance-mobile-slide-track');
                const isMobile = window.matchMedia('(max-width: 575.98px)').matches;

                if (!modal || !track || !isMobile) {
                    return;
                }

                const activePanelSelector = modal.classList.contains('attendance-mobile-proof-active')
                    ? '.attendance-proof-window'
                    : '.attendance-edit-modal-content';
                const activePanel = modal.querySelector(activePanelSelector);

                if (!activePanel) {
                    return;
                }

                window.requestAnimationFrame(() => {
                    const activePanelHeight = Math.ceil(activePanel.getBoundingClientRect().height);
                    if (activePanelHeight > 0) {
                        track.style.height = `${activePanelHeight}px`;
                    }
                });
            };

            if (!window.attendanceMobileProofNavigationBound) {
                document.addEventListener('click', (event) => {
                    const openButton = event.target.closest('[data-attendance-mobile-proof-open]');
                    const backButton = event.target.closest('[data-attendance-mobile-proof-back]');
                    const isMobile = window.matchMedia('(max-width: 575.98px)').matches;

                    if (openButton && isMobile) {
                        const modal = openButton.closest('#manualAttendanceModal');
                        modal?.classList.add('attendance-mobile-proof-active');
                        syncMobileSlideHeight();
                    } else if (backButton && isMobile) {
                        const modal = backButton.closest('#manualAttendanceModal');
                        modal?.classList.remove('attendance-mobile-proof-active');
                        syncMobileSlideHeight();
                    }
                });

                window.addEventListener('resize', () => {
                    if (!window.matchMedia('(max-width: 575.98px)').matches) {
                        const modal = document.getElementById('manualAttendanceModal');
                        modal?.classList.remove('attendance-mobile-proof-active');
                        modal?.querySelector('.attendance-mobile-slide-track')?.style.removeProperty('height');
                    } else {
                        syncMobileSlideHeight();
                    }
                });

                window.attendanceMobileProofNavigationBound = true;
            }

            // Auto focus search field saat dropdown Select2 dibuka
            $(document).on('select2:open', () => {
                setTimeout(() => {
                    const searchField = document.querySelector('.select2-container--open .select2-search__field');
                    if (searchField) {
                        searchField.focus();
                    }
                }, 10);
            });

            Livewire.on('open-modal', (event) => {
                resetLoadingCells();
                const data = Array.isArray(event) ? event[0] : event;
                const modalId = typeof data === 'object' && data !== null ? data.id : data;
                if (modalId === 'manualAttendanceModal') {
                    document.getElementById('manualAttendanceModal')?.classList.remove('attendance-mobile-proof-active');
                    setTimeout(() => {
                        initSelect2();
                        const empVal = @this.get('manualEmployeeId');
                        const isLocked = Boolean(@this.get('lockDateAndEmployee') || @this.get('editingAttendanceId'));
                        $('#manualEmployeeId').val(empVal || '').trigger('change.select2');
                        $('#manualEmployeeId').prop('disabled', isLocked);
                    }, 50);
                }
            });

            // Animasi spinner instan & lock saat klik sel matriks presensi
            const resetLoadingCells = function() {
                document.querySelectorAll('.att-cell-wrap.is-loading').forEach(el => el.classList.remove('is-loading'));
            };

            if (typeof Livewire !== 'undefined' && Livewire.hook) {
                Livewire.hook('commit', ({ succeed }) => {
                    succeed(() => {
                        resetLoadingCells();
                        syncMobileSlideHeight();
                    });
                });
            }
            window.addEventListener('livewire:error', resetLoadingCells);
            document.addEventListener('livewire:navigated', resetLoadingCells);

            $('#manualAttendanceModal').on('hidden.bs.modal', function () {
                this.classList.remove('attendance-mobile-proof-active');
                this.querySelector('.attendance-mobile-slide-track')?.style.removeProperty('height');
                $('#manualEmployeeId').val('').trigger('change.select2');
                resetLoadingCells();
            });

            $('#manualAttendanceModal').on('shown.bs.modal', syncMobileSlideHeight);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAttendanceScripts);
        } else {
            initAttendanceScripts();
        }
    })();
</script>
@endpush
