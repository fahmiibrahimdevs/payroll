<div>
    <x-slot name="header">
        <h1>Absensi Karyawan</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Operasional</div>
            <div class="breadcrumb-item">Absensi Karyawan</div>
        </div>
    </x-slot>

    <!-- Header Section & Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div class="mr-md-3">
            <h2 class="section-title mt-0">Rekap Presensi &amp; Jam Kerja Mesin Fingerprint</h2>
            <p class="section-lead mb-0">
                Tampilan Matriks Kehadiran harian seluruh karyawan lengkap dengan jam scan masuk dan scan pulang.
            </p>
        </div>

        @if (auth()->user()?->hasRole(['admin', 'direktur']))
            <div class="mt-3 mt-md-0 flex-shrink-0 d-flex align-items-center" style="gap: 8px;">
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
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center flex-wrap" style="gap: 10px;">
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

                <!-- Filter & Search Karyawan -->
                <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
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

                    <!-- Search Box -->
                    <div style="min-width: 200px;">
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
                    <div style="width: 85px;">
                        <select wire:model.live="perPage" class="form-control roster-toolbar-select" title="Jumlah baris karyawan">
                            <option value="0">Semua</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Roster Grid (Style Jadwal: Sticky 2-Tier Header, 3 Sticky Columns, Clean White Grid) -->
    <div class="card border-0 mb-3" style="background: #ffffff; border-radius: 12px; box-shadow: 0 2px 14px -2px rgba(0, 0, 0, 0.06); overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 760px; overflow-x: auto;">
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
                    <tbody x-on:click="const cell = $event.target.closest('.att-cell-clickable'); if (cell) { $wire.openCellModal(cell.dataset.emp, cell.dataset.date); }">
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
                <div class="card-footer bg-white border-top py-2 px-3 d-flex flex-column flex-lg-row justify-content-between align-items-center" style="gap: 10px;">
                    <div class="d-flex align-items-center flex-wrap" style="color: #334155; font-size: 11.5px; gap: 14px;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-hand-pointer mr-1.5 text-primary"></i>
                            <span>Klik tanda sel untuk ubah data / koreksi presensi.</span>
                        </div>
                        <!-- Mini Legenda Full-Cell -->
                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                            <span class="d-inline-flex align-items-center" style="gap: 4px;">
                                <span class="d-inline-flex align-items-center justify-content-center" style="width: 20px; height: 18px; background: #dcfce7; color: #15803d; border-radius: 3px; font-size: 9px;"><i class="fas fa-check"></i></span> Hadir
                            </span>
                            <span class="d-inline-flex align-items-center" style="gap: 4px;">
                                <span class="d-inline-flex align-items-center justify-content-center position-relative" style="width: 24px; height: 18px; background: #dcfce7; color: #15803d; border-radius: 3px; font-size: 9px;"><i class="fas fa-check"></i><span style="position: absolute; top: 0; right: 0; background: #f59e0b; color: #fff; font-size: 6px; font-weight: 800; line-height: 1; padding: 1px 1.5px; border-bottom-left-radius: 2px;">OT</span></span> Hadir + Lembur
                            </span>
                            <span class="d-inline-flex align-items-center" style="gap: 4px;">
                                <span class="d-inline-flex align-items-center justify-content-center" style="width: 20px; height: 18px; background: #fffbeb; color: #b45309; border: 1px solid #f59e0b; border-radius: 3px; font-size: 8px; font-weight: 800;">OT</span> Lembur
                            </span>
                            <span class="d-inline-flex align-items-center" style="gap: 4px;">
                                <span class="d-inline-flex align-items-center justify-content-center" style="width: 18px; height: 18px; background: #fef3c7; color: #b45309; border-radius: 3px; font-size: 9px; font-weight: 800;">T</span> Terlambat
                            </span>
                            <span class="d-inline-flex align-items-center" style="gap: 4px;">
                                <span class="d-inline-flex align-items-center justify-content-center" style="width: 18px; height: 18px; background: #e0e7ff; color: #3730a3; border-radius: 3px; font-size: 9px; font-weight: 800;">C</span> Cuti
                            </span>
                            <span class="d-inline-flex align-items-center" style="gap: 4px;">
                                <span class="d-inline-flex align-items-center justify-content-center" style="width: 18px; height: 18px; background: #fef9c3; color: #854d0e; border-radius: 3px; font-size: 9px; font-weight: 800;">S</span> Sakit
                            </span>
                            <span class="d-inline-flex align-items-center" style="gap: 4px;">
                                <span class="d-inline-flex align-items-center justify-content-center" style="width: 18px; height: 18px; background: #e0f2fe; color: #0369a1; border-radius: 3px; font-size: 9px; font-weight: 800;">I</span> Izin
                            </span>
                            <span class="d-inline-flex align-items-center" style="gap: 4px;">
                                <span class="d-inline-flex align-items-center justify-content-center" style="width: 18px; height: 18px; background: #fee2e2; color: #b91c1c; border-radius: 3px; font-size: 9px; font-weight: 800;">A</span> Alpa
                            </span>
                        </div>
                    </div>
                    @if ($matrixEmployees->hasPages())
                        <div>
                            {{ $matrixEmployees->links('livewire.custom-pagination') }}
                        </div>
                    @endif
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

    <!-- MODAL 2: EDIT PRESENSI & AUDIT FOTO / GPS (DUAL FLOATING CARDS SIDE-BY-SIDE) -->
    <div wire:ignore.self class="modal fade" id="manualAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="manualAttendanceModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 1200px; width: 95%; margin: 1.5rem auto;" role="document">
            <div class="modal-content border-0 bg-transparent shadow-none" style="border: none !important; background: transparent !important; box-shadow: none !important;">
                <div class="row align-items-stretch" style="margin: 0 -8px;">

                    <!-- PANEL KIRI (col-lg-7): Form Edit Catatan Presensi -->
                    <div class="col-lg-7 col-12 px-2 mb-3 mb-lg-0">
                        <div class="card shadow-lg bg-white h-100 d-flex flex-column" style="border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08), 0 10px 10px -5px rgba(0,0,0,0.04);">
                            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center min-width-0">
                                    <div class="mr-2.5 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; border-radius: 8px; background: #0b52aa; color: #ffffff;">
                                        <i class="fas fa-edit" style="font-size: 14px;"></i>
                                    </div>
                                    <div class="min-width-0">
                                        <h5 class="card-title text-dark m-0 font-weight-700" id="manualAttendanceModalLabel" style="font-size: 15px; letter-spacing: -0.2px;">
                                            {{ $editingAttendanceId ? 'Edit Catatan Presensi' : 'Input Presensi Manual' }}
                                        </h5>
                                    </div>
                                </div>
                                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close" style="font-size: 20px; line-height: 1;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <form wire:submit.prevent="saveManualAttendance" class="d-flex flex-column flex-grow-1">
                                <div class="card-body py-3 px-4 bg-white flex-grow-1" style="overflow-y: auto; max-height: calc(85vh - 140px);">

                                    @if ($editingAttendance)
                                        <!-- Ringkasan Karyawan (Pure White Card) -->
                                        <div class="d-flex align-items-center p-3 mb-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);">
                                            <div class="mr-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; border-radius: 10px; background: #0b52aa; color: #ffffff; font-weight: 700; font-size: 15px;">
                                                {{ $editingAttendance->employee?->initials ?? 'K' }}
                                            </div>
                                            <div class="min-width-0 flex-grow-1">
                                                <div class="text-truncate font-weight-700 text-dark" style="font-size: 13.5px; color: #0f172a;">
                                                    {{ $editingAttendance->employee?->full_name }}
                                                </div>
                                                <div class="text-truncate" style="font-size: 11.5px; color: #475569;">
                                                    {{ $editingAttendance->employee?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $editingAttendance->employee?->position?->name ?? 'Staff' }}
                                                </div>
                                                <div style="font-size: 11px; color: #64748b;">
                                                    ID Finger: {{ $editingAttendance->employee?->fingerprint_id ?? '-' }} &bull; Tanggal: {{ $editingAttendance->date?->format('d M Y') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Pilih Karyawan (Select2 Searchable dengan optgroup status kerja) -->
                                    <div class="form-group mb-3">
                                        <label for="manualEmployeeId" class="font-weight-bold" style="font-size: 12.5px; color: #0f172a;">Karyawan <span class="text-danger">*</span></label>
                                        <div wire:ignore>
                                            <select id="manualEmployeeId" class="form-control select2 @error('manualEmployeeId') is-invalid @enderror" style="width: 100%;" {{ $lockDateAndEmployee ? 'disabled' : '' }}>
                                                <option value="">-- Pilih Karyawan --</option>
                                                @foreach ($allEmployees->groupBy(fn($e) => $e->employment_status instanceof \App\Enums\EmploymentStatus ? $e->employment_status->label() : (\App\Enums\EmploymentStatus::tryFrom((string) $e->employment_status)?->label() ?? 'Lainnya')) as $statusLabel => $groupEmps)
                                                    <optgroup label="{{ $statusLabel }}">
                                                        @foreach ($groupEmps as $emp)
                                                            <option value="{{ $emp->id }}">
                                                                {{ $emp->full_name }} - ID Finger: {{ $emp->fingerprint_id ?? '-' }}
                                                            </option>
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
                                            <legend class="custom-modal-legend" style="background: #ffffff; padding: 0 8px; font-size: 12.5px; font-weight: 700; color: #0f172a;">
                                                <div class="custom-control custom-checkbox d-inline-block">
                                                    <input type="checkbox" class="custom-control-input" id="hasFullDayOvertimeCheck" wire:model.live="hasFullDayOvertime">
                                                    <label class="custom-control-label font-weight-bold mb-0" for="hasFullDayOvertimeCheck" style="cursor: pointer; font-size: 12.5px; color: #0f172a;">
                                                        Apakah Lembur Seharian?
                                                    </label>
                                                </div>
                                            </legend>

                                            @if ($hasFullDayOvertime)
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualClockIn" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Jam Masuk</label>
                                                                @if ($manualClockIn)
                                                                    <a href="javascript:void(0)" wire:click="clearClockIn" class="text-danger font-weight-bold" style="font-size: 11px;">Hapus</a>
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
                                                                    <a href="javascript:void(0)" wire:click="clearClockOut" class="text-danger font-weight-bold" style="font-size: 11px;">Hapus</a>
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
                                            <legend class="custom-modal-legend" style="background: #ffffff; padding: 0 8px; font-size: 12.5px; font-weight: 700; color: #0f172a;">
                                                <div class="custom-control custom-checkbox d-inline-block">
                                                    <input type="checkbox" class="custom-control-input" id="hasOvertimeCheck" wire:model.live="hasOvertime">
                                                    <label class="custom-control-label font-weight-bold mb-0" for="hasOvertimeCheck" style="cursor: pointer; font-size: 12.5px; color: #0f172a;">
                                                        Apakah Lembur Per Jam?
                                                    </label>
                                                </div>
                                            </legend>

                                            @if ($hasOvertime)
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualOvertimeIn" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Lembur Mulai</label>
                                                                @if ($manualOvertimeIn)
                                                                    <a href="javascript:void(0)" wire:click="clearOvertimeIn" class="text-danger font-weight-bold" style="font-size: 11px;">Hapus</a>
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
                                                                    <a href="javascript:void(0)" wire:click="clearOvertimeOut" class="text-danger font-weight-bold" style="font-size: 11px;">Hapus</a>
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
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualClockIn" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">
                                                                    Jam Masuk <span class="text-danger">*</span>
                                                                </label>
                                                                @if ($manualClockIn)
                                                                    <a href="javascript:void(0)" wire:click="clearClockIn" class="text-danger font-weight-bold" style="font-size: 11px;">Hapus</a>
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
                                                                    <a href="javascript:void(0)" wire:click="clearClockOut" class="text-danger font-weight-bold" style="font-size: 11px;">Hapus</a>
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
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group mb-0">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <label for="manualOvertimeIn" class="font-weight-bold mb-0" style="font-size: 12px; color: #0f172a;">Lembur Mulai</label>
                                                                @if ($manualOvertimeIn)
                                                                    <a href="javascript:void(0)" wire:click="clearOvertimeIn" class="text-danger font-weight-bold" style="font-size: 11px;">Hapus</a>
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
                                                                    <a href="javascript:void(0)" wire:click="clearOvertimeOut" class="text-danger font-weight-bold" style="font-size: 11px;">Hapus</a>
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

                                <div class="card-footer bg-white border-top py-3 px-4">
                                    @if ($editingAttendanceId)
                                        <div class="row w-100 mx-0">
                                            <div class="col-6 pr-1 pl-0">
                                                <button type="button" wire:click="confirmDeleteAttendance({{ $editingAttendanceId }})" wire:loading.attr="disabled" class="btn btn-outline-danger btn-block font-weight-600" style="border-radius: 8px; font-size: 13px; padding: 9px 12px;">
                                                    <i class="fas fa-trash-alt mr-1"></i> Hapus Presensi
                                                </button>
                                            </div>
                                            <div class="col-6 pl-1 pr-0">
                                                <button type="submit" class="btn btn-primary btn-block font-weight-600 text-white" style="background: linear-gradient(135deg, #0b52aa 0%, #1e40af 100%); border: none; border-radius: 8px; font-size: 13px; padding: 9px 12px;">
                                                    <i class="fas fa-save mr-1"></i> Simpan Perubahan
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <button type="submit" class="btn btn-primary btn-block font-weight-600 text-white" style="background: linear-gradient(135deg, #0b52aa 0%, #1e40af 100%); border: none; border-radius: 8px; font-size: 13px; padding: 9px 14px;">
                                            <i class="fas fa-save mr-1"></i> Simpan Presensi
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- PANEL KANAN (col-lg-5): Floating Bukti Foto & Lokasi GPS -->
                    <div class="col-lg-5 col-12 px-2">
                        <div class="card shadow-lg bg-white h-100 d-flex flex-column" style="border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08), 0 10px 10px -5px rgba(0,0,0,0.04);">
                            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center min-width-0">
                                    <div class="mr-2.5 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; color: #0b52aa;">
                                        <i class="fas fa-map-marked-alt" style="font-size: 14px;"></i>
                                    </div>
                                    <div class="min-width-0">
                                        <h5 class="card-title text-dark m-0 font-weight-700" style="font-size: 15px; letter-spacing: -0.2px;">
                                            Bukti Foto & Lokasi GPS
                                        </h5>
                                        <div class="text-truncate" style="font-size: 11px; color: #64748b;">
                                            Audit visual kehadiran & koordinat
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close" style="font-size: 20px; line-height: 1;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="card-body p-3.5 bg-white flex-grow-1 d-flex flex-column" style="overflow-y: auto; max-height: calc(85vh - 140px);">
                                @if ($auditAttendance)
                                    <!-- Nav Tab Pills (Masuk, Pulang, Lembur) -->
                                    <div class="d-flex align-items-center mb-3 p-1 bg-white border" style="border-radius: 12px; border-color: #e2e8f0 !important; gap: 4px;">
                                        <button type="button" wire:click="setAuditTab('clock_in')" class="btn btn-sm flex-fill font-weight-600" style="border-radius: 8px; font-size: 12px; padding: 7px 10px; transition: all 0.15s ease; {{ $auditTab === 'clock_in' ? 'background: #0b52aa; color: #ffffff;' : 'background: #ffffff; color: #475569; border: 1px solid #e2e8f0;' }}">
                                            <i class="fas fa-camera mr-1"></i> Masuk
                                        </button>
                                        <button type="button" wire:click="setAuditTab('clock_out')" class="btn btn-sm flex-fill font-weight-600" style="border-radius: 8px; font-size: 12px; padding: 7px 10px; transition: all 0.15s ease; {{ $auditTab === 'clock_out' ? 'background: #0b52aa; color: #ffffff;' : 'background: #ffffff; color: #475569; border: 1px solid #e2e8f0;' }}">
                                            <i class="fas fa-door-open mr-1"></i> Pulang
                                        </button>
                                        <button type="button" wire:click="setAuditTab('overtime')" class="btn btn-sm flex-fill font-weight-600" style="border-radius: 8px; font-size: 12px; padding: 7px 10px; transition: all 0.15s ease; {{ $auditTab === 'overtime' ? 'background: #0b52aa; color: #ffffff;' : 'background: #ffffff; color: #475569; border: 1px solid #e2e8f0;' }}">
                                            <i class="fas fa-stopwatch mr-1"></i> Lembur
                                        </button>
                                    </div>

                                    @if ($auditAttendance->isPendingReview())
                                        <!-- Review Alert Box (Pure White) -->
                                        <div class="p-3 mb-3 bg-white border" style="border-radius: 12px; border-color: #fde68a !important; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                                            <div class="d-flex align-items-center mb-1 font-weight-700" style="font-size: 12px; color: #b45309;">
                                                <i class="fas fa-exclamation-triangle mr-1.5"></i> Perlu Review (Di Luar Radius Kantor)
                                            </div>
                                            @if ($auditAttendance->notes)
                                                <div class="mb-2" style="font-size: 11.5px; color: #1e293b; line-height: 1.4;">
                                                    <strong>Keterangan Karyawan:</strong> {{ $auditAttendance->notes }}
                                                </div>
                                            @endif
                                            <div class="d-flex" style="gap: 8px;">
                                                <button type="button" wire:click="approveAudit" class="btn btn-sm btn-success flex-fill font-weight-600" style="border-radius: 6px; font-size: 11.5px; padding: 6px;">
                                                    <i class="fas fa-check mr-1"></i> Setujui Valid
                                                </button>
                                                <button type="button" wire:click="rejectAudit" class="btn btn-sm btn-outline-danger flex-fill font-weight-600" style="border-radius: 6px; font-size: 11.5px; padding: 6px;">
                                                    <i class="fas fa-times mr-1"></i> Tolak (Alpa)
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($auditTab === 'clock_in')
                                        <!-- TAB 1: MASUK -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                                <span style="font-size: 12px; font-weight: 700; color: #0f172a;">Foto Selfie Masuk</span>
                                                <span style="font-size: 11px; color: #64748b; font-weight: 600;">{{ $auditAttendance->clock_in ? substr($auditAttendance->clock_in, 0, 5) . ' WIB' : '-' }}</span>
                                            </div>
                                            <div class="position-relative border text-center mx-auto" style="width: 100%; max-width: 280px; aspect-ratio: 1 / 1; border-radius: 12px; overflow: hidden; background: #0f172a; border-color: #e2e8f0 !important;">
                                                @if ($auditAttendance->clock_in_photo_url)
                                                    <a href="{{ $auditAttendance->clock_in_photo_url }}" target="_blank" title="Klik untuk memperbesar" class="d-block w-100 h-100">
                                                        <img src="{{ $auditAttendance->clock_in_photo_url }}" alt="Selfie Masuk" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                    </a>
                                                    <div class="position-absolute" style="top: 8px; right: 8px; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); padding: 3px 8px; border-radius: 6px; font-size: 10px; color: #ffffff; z-index: 2;">
                                                        <i class="fas fa-map-marker-alt text-warning mr-1"></i> {{ $auditAttendance->clock_in_distance_meters ?? 0 }}m dari kantor
                                                    </div>
                                                    <div class="photo-error-fallback flex-column align-items-center justify-content-center h-100 text-muted" style="display: none; font-size: 11.5px;">
                                                        <i class="fas fa-image mb-1.5" style="font-size: 24px; color: #94a3b8;"></i>
                                                        <span style="color: #cbd5e1;">File foto tidak ditemukan di server</span>
                                                    </div>
                                                @else
                                                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted" style="font-size: 12px;">
                                                        <i class="fas fa-camera-slash mb-1.5" style="font-size: 24px; color: #94a3b8;"></i>
                                                        <span style="color: #cbd5e1;">Tanpa Foto Masuk (Manual / Finger)</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                    @elseif ($auditTab === 'clock_out')
                                        <!-- TAB 2: PULANG -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                                <span style="font-size: 12px; font-weight: 700; color: #0f172a;">Foto Selfie Pulang</span>
                                                <span style="font-size: 11px; color: #64748b; font-weight: 600;">{{ $auditAttendance->clock_out ? substr($auditAttendance->clock_out, 0, 5) . ' WIB' : '-' }}</span>
                                            </div>
                                            <div class="position-relative border text-center mx-auto" style="width: 100%; max-width: 280px; aspect-ratio: 1 / 1; border-radius: 12px; overflow: hidden; background: #0f172a; border-color: #e2e8f0 !important;">
                                                @if ($auditAttendance->clock_out_photo_url)
                                                    <a href="{{ $auditAttendance->clock_out_photo_url }}" target="_blank" title="Klik untuk memperbesar" class="d-block w-100 h-100">
                                                        <img src="{{ $auditAttendance->clock_out_photo_url }}" alt="Selfie Pulang" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                    </a>
                                                    <div class="position-absolute" style="top: 8px; right: 8px; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); padding: 3px 8px; border-radius: 6px; font-size: 10px; color: #ffffff; z-index: 2;">
                                                        <i class="fas fa-map-marker-alt text-warning mr-1"></i> {{ $auditAttendance->clock_out_distance_meters ?? 0 }}m dari kantor
                                                    </div>
                                                    <div class="photo-error-fallback flex-column align-items-center justify-content-center h-100 text-muted" style="display: none; font-size: 11.5px;">
                                                        <i class="fas fa-image mb-1.5" style="font-size: 24px; color: #94a3b8;"></i>
                                                        <span style="color: #cbd5e1;">File foto tidak ditemukan di server</span>
                                                    </div>
                                                @else
                                                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted" style="font-size: 12px;">
                                                        <i class="fas fa-camera-slash mb-1.5" style="font-size: 24px; color: #94a3b8;"></i>
                                                        <span style="color: #cbd5e1;">Tanpa Foto Pulang</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                    @else
                                        <!-- TAB 3: LEMBUR -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2 p-2.5 bg-white border" style="border-radius: 10px; border-color: #e2e8f0 !important;">
                                                <div>
                                                    <div style="font-size: 11px; color: #64748b;">Jam Lembur:</div>
                                                    <div style="font-size: 12.5px; font-weight: 700; color: #0b52aa;">
                                                        {{ $auditAttendance->overtime_in ? substr($auditAttendance->overtime_in, 0, 5) : '-' }} WIB &mdash; {{ $auditAttendance->overtime_out ? substr($auditAttendance->overtime_out, 0, 5) : '-' }} WIB
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div style="font-size: 11px; color: #64748b;">Total Durasi:</div>
                                                    <div style="font-size: 12.5px; font-weight: 700; color: #059669;">
                                                        {{ $auditAttendance->overtime_minutes ? intdiv($auditAttendance->overtime_minutes, 60).'j '.($auditAttendance->overtime_minutes % 60).'m' : '-' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <div class="col-6 pr-1">
                                                    <div style="font-size: 11px; font-weight: 700; color: #0f172a; margin-bottom: 3px;">Mulai Lembur</div>
                                                    <div class="position-relative border text-center" style="width: 100%; aspect-ratio: 1 / 1; border-radius: 10px; overflow: hidden; background: #0f172a; border-color: #e2e8f0 !important;">
                                                        @if ($auditAttendance->overtime_in_photo_url)
                                                            <a href="{{ $auditAttendance->overtime_in_photo_url }}" target="_blank" class="d-block w-100 h-100">
                                                                <img src="{{ $auditAttendance->overtime_in_photo_url }}" alt="Mulai Lembur" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                            </a>
                                                            <div class="photo-error-fallback flex-column align-items-center justify-content-center h-100 text-muted" style="display: none; font-size: 10.5px;">
                                                                <i class="fas fa-image mb-1" style="font-size: 18px; color: #94a3b8;"></i>
                                                                <span style="color: #cbd5e1;">Foto tidak ada</span>
                                                            </div>
                                                        @else
                                                            <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted" style="font-size: 11px;">
                                                                <i class="fas fa-camera-slash mb-1" style="font-size: 18px; color: #94a3b8;"></i>
                                                                <span style="color: #cbd5e1;">Tanpa Foto</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-6 pl-1">
                                                    <div style="font-size: 11px; font-weight: 700; color: #0f172a; margin-bottom: 3px;">Selesai Lembur</div>
                                                    <div class="position-relative border text-center" style="width: 100%; aspect-ratio: 1 / 1; border-radius: 10px; overflow: hidden; background: #0f172a; border-color: #e2e8f0 !important;">
                                                        @if ($auditAttendance->overtime_out_photo_url)
                                                            <a href="{{ $auditAttendance->overtime_out_photo_url }}" target="_blank" class="d-block w-100 h-100">
                                                                <img src="{{ $auditAttendance->overtime_out_photo_url }}" alt="Selesai Lembur" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                            </a>
                                                            <div class="photo-error-fallback flex-column align-items-center justify-content-center h-100 text-muted" style="display: none; font-size: 10.5px;">
                                                                <i class="fas fa-image mb-1" style="font-size: 18px; color: #94a3b8;"></i>
                                                                <span style="color: #cbd5e1;">Foto tidak ada</span>
                                                            </div>
                                                        @else
                                                            <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted" style="font-size: 11px;">
                                                                <i class="fas fa-camera-slash mb-1" style="font-size: 18px; color: #94a3b8;"></i>
                                                                <span style="color: #cbd5e1;">Tanpa Foto</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Interactive Leaflet Map -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                                            <span style="font-size: 12px; font-weight: 700; color: #0f172a;">Peta Koordinat GPS</span>
                                            <span id="auditMapStatusBadge" class="badge badge-light border" style="font-size: 10.5px;">Memuat GPS...</span>
                                        </div>
                                        <div id="auditLeafletMap" wire:ignore style="width: 100%; height: 180px; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;"></div>
                                    </div>

                                    <!-- Location Info Box (Pure White) -->
                                    <div class="p-2.5 bg-white border" style="border-radius: 10px; border-color: #e2e8f0 !important; font-size: 11.5px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span style="color: #64748b;">Lokasi Acuan:</span>
                                            <span id="auditMapOfficeLabel" class="font-weight-600 text-dark">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span style="color: #64748b;">Jarak dari Kantor:</span>
                                            <span id="auditMapDistanceText" class="font-weight-700 text-primary">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span style="color: #64748b;">Koordinat Tap:</span>
                                            <span id="auditMapCoordinatesText" class="font-weight-500 text-muted text-truncate" style="max-width: 180px;">-</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-center py-5">
                                        <div class="mb-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; border-radius: 50%; background: #eff6ff; color: #0b52aa;">
                                            <i class="fas fa-map-marked-alt" style="font-size: 22px;"></i>
                                        </div>
                                        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
                                            Input Presensi Baru
                                        </div>
                                        <div style="font-size: 11.5px; color: #64748b; max-width: 260px;">
                                            Bukti foto selfie dan koordinat GPS akan tercatat otomatis saat presensi dilakukan via portal mandiri karyawan.
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
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
                const data = Array.isArray(event) ? event[0] : event;
                const modalId = typeof data === 'object' && data !== null ? data.id : data;
                if (modalId === 'manualAttendanceModal') {
                    setTimeout(() => {
                        initSelect2();
                        const empVal = @this.get('manualEmployeeId');
                        const isLocked = Boolean(@this.get('lockDateAndEmployee') || @this.get('editingAttendanceId'));
                        $('#manualEmployeeId').val(empVal || '').trigger('change.select2');
                        $('#manualEmployeeId').prop('disabled', isLocked);
                    }, 50);
                }
            });

            $('#manualAttendanceModal').on('hidden.bs.modal', function () {
                $('#manualEmployeeId').val('').trigger('change.select2');
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAttendanceScripts);
        } else {
            initAttendanceScripts();
        }
    })();
</script>
@endpush
