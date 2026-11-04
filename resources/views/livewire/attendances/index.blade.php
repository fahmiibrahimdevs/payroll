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

                            @foreach ($matrixDates as $d)
                                @if ($d['is_sunday'])
                                    <th class="p-0 font-weight-bold text-nowrap align-middle date-col"
                                        style="width: 30px; min-width: 28px; max-width: 32px; font-size: 9.5px; background: #dc2626 !important; color: #ffffff !important; border: 1px solid #b91c1c; height: 20px;"
                                        title="Hari Minggu">
                                        {{ $d['dow_name'] }}
                                    </th>
                                @elseif ($d['is_holiday'])
                                    <th class="p-0 font-weight-bold text-nowrap align-middle date-col"
                                        style="width: 30px; min-width: 28px; max-width: 32px; font-size: 9.5px; background: #ef4444 !important; color: #ffffff !important; border: 1px solid #dc2626; height: 20px;"
                                        title="{{ $d['holiday_name'] }}">
                                        {{ $d['dow_name'] }}
                                    </th>
                                @else
                                    <th class="p-0 font-weight-bold text-nowrap align-middle date-col"
                                        style="width: 30px; min-width: 28px; max-width: 32px; font-size: 9.5px; background: #ffffff !important; color: #0f172a !important; border: 1px solid #cbd5e1; height: 20px;">
                                        {{ $d['dow_name'] }}
                                    </th>
                                @endif
                            @endforeach

                            <th colspan="2" class="align-middle text-center p-0" style="border-left: 2px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; font-weight: 700; font-size: 10px; background: #ffffff !important; color: #334155; height: 20px;">
                                REKAP KEHADIRAN
                            </th>
                        </tr>

                        <!-- Baris 2: Angka Tanggal (Biru Muda untuk Hari Biasa, Merah untuk Hari Minggu / Libur) dan Sub-Header Rekap -->
                        <tr style="background: #ffffff;">
                            @foreach ($matrixDates as $d)
                                @if ($d['is_sunday'])
                                    <th class="p-0 font-weight-bold align-middle date-col"
                                        style="width: 30px; min-width: 28px; max-width: 32px; font-size: 10.5px; background: #b91c1c !important; color: #ffffff !important; border: 1px solid #991b1b; height: 20px;"
                                        title="Hari Minggu (Tgl {{ $d['day'] }})">
                                        {{ $d['day'] }}
                                    </th>
                                @elseif ($d['is_holiday'])
                                    <th class="p-0 font-weight-bold align-middle date-col"
                                        style="width: 30px; min-width: 28px; max-width: 32px; font-size: 10.5px; background: #dc2626 !important; color: #ffffff !important; border: 1px solid #b91c1c; height: 20px;"
                                        title="{{ $d['holiday_name'] }} (Tgl {{ $d['day'] }})">
                                        {{ $d['day'] }}
                                    </th>
                                @else
                                    <th class="p-0 font-weight-bold align-middle date-col"
                                        style="width: 30px; min-width: 28px; max-width: 32px; font-size: 10.5px; background: #e0f2fe !important; color: #0369a1 !important; border: 1px solid #bae6fd; height: 20px;"
                                        title="Tanggal {{ $d['day'] }}">
                                        {{ $d['day'] }}
                                    </th>
                                @endif
                            @endforeach

                            <th class="align-middle text-center p-0 font-weight-bold" style="width: 44px; min-width: 44px; max-width: 44px; color: #047857; font-size: 9.5px; border-left: 2px solid #cbd5e1; border-bottom: 2px solid #cbd5e1; background: #ffffff !important; height: 20px;" title="Total Hari Masuk Kerja">HADIR</th>
                            <th class="align-middle text-center p-0 font-weight-bold" style="width: 44px; min-width: 44px; max-width: 44px; color: #be123c; font-size: 9.5px; border-bottom: 2px solid #cbd5e1; background: #ffffff !important; height: 20px;" title="Total Hari Libur">LIBUR</th>
                        </tr>
                    </thead>
                    <tbody>
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
                                    @foreach ($matrixDates as $d)
                                        @php
                                            $att = $empRow['att']->get($d['date']);
                                            $sch = $empRow['schedules']->get($d['date']);
                                            $isOff = $sch ? ($sch->status === \App\Models\EmployeeSchedule::STATUS_OFF) : ($d['is_sunday'] || $d['is_holiday']);
                                            $status = $att ? strtolower($att->status) : null;
                                            $hasOt = $att && ($att->overtime_in || ($att->overtime_duration_minutes ?? 0) > 0);
                                            $hasWorkHours = $att && (!empty($att->clock_in) || !empty($att->clock_out));
                                            $isRegularWork = $att !== null && in_array($status, ['hadir', 'terlambat', 'pulang_cepat'], true) && $hasWorkHours;
                                        @endphp
                                        <td class="text-center p-0 align-middle date-col {{ $isOff ? 'cell-roster-off' : '' }}" style="width: 30px; min-width: 28px; max-width: 32px;">
                                            @if ($isRegularWork)
                                                @php
                                                    $tooltipText = "{$emp->full_name} ({$d['date']}): " . ucfirst($status) . ($att->clock_in ? ' Masuk ' . substr($att->clock_in, 0, 5) : '') . ($att->clock_out ? ' s/d Pulang ' . substr($att->clock_out, 0, 5) : '') . ($hasOt ? ' | Lembur ' . substr($att->overtime_in, 0, 5) . ($att->overtime_out ? '-' . substr($att->overtime_out, 0, 5) : '') : '') . ($att->source === 'manual' ? ' [Manual]' : ' [Fingerprint]');
                                                @endphp
                                                @if ($status === 'hadir')
                                                    <div class="att-cell-wrap att-cell-present att-chip-present {{ $att?->source === 'manual' ? 'att-chip-manual' : '' }}"
                                                         wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')"
                                                         title="{{ $tooltipText }} (Klik untuk ubah)">
                                                        <i class="fas fa-check att-cell-check"></i>
                                                        @if ($hasOt)
                                                            <span class="att-ot-ribbon" title="Ada Lembur">OT</span>
                                                        @endif
                                                    </div>
                                                @elseif ($status === 'terlambat')
                                                    <div class="att-cell-wrap att-cell-late"
                                                         wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')"
                                                         title="{{ $tooltipText }} (Klik untuk ubah)">
                                                        <span class="att-cell-letter">T</span>
                                                        @if ($hasOt)
                                                            <span class="att-ot-ribbon" title="Ada Lembur">OT</span>
                                                        @endif
                                                    </div>
                                                @elseif ($status === 'pulang_cepat')
                                                    <div class="att-cell-wrap att-cell-early"
                                                         wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')"
                                                         title="{{ $tooltipText }} (Klik untuk ubah)">
                                                        <span class="att-cell-letter" style="font-size: 9.5px;">PC</span>
                                                        @if ($hasOt)
                                                            <span class="att-ot-ribbon" title="Ada Lembur">OT</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @elseif ($status === 'libur' && $hasWorkHours)
                                                @php
                                                    $holidayTooltip = "{$emp->full_name} ({$d['date']}): Lembur Hari Libur" . ($att->clock_in ? ' Masuk ' . substr($att->clock_in, 0, 5) : '') . ($att->clock_out ? ' s/d Pulang ' . substr($att->clock_out, 0, 5) : '') . ($hasOt ? ' | Lembur ' . substr($att->overtime_in, 0, 5) . ($att->overtime_out ? '-' . substr($att->overtime_out, 0, 5) : '') : '') . ($att->source === 'manual' ? ' [Manual]' : ' [Fingerprint]');
                                                @endphp
                                                <div class="att-cell-wrap {{ $att?->source === 'manual' ? 'att-chip-manual' : '' }}"
                                                     wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')"
                                                     title="{{ $holidayTooltip }} (Klik untuk ubah)">
                                                    <i class="fas fa-check att-cell-check text-white"></i>
                                                    @if ($hasOt)
                                                        <span class="att-ot-ribbon" title="Ada Lembur">OT</span>
                                                    @endif
                                                </div>
                                            @elseif ($hasOt)
                                                <div class="att-cell-wrap {{ ! $isOff ? 'att-cell-ot-solo' : '' }}"
                                                     wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')"
                                                     title="{{ $emp->full_name }} ({{ $d['date'] }}): Lembur {{ substr($att->overtime_in, 0, 5) }}{{ $att->overtime_out ? ' - ' . substr($att->overtime_out, 0, 5) : '' }} (Klik untuk ubah)">
                                                    @if ($isOff)
                                                        <span class="att-cell-letter text-white" style="font-size: 10.5px; font-weight: 800;">OT</span>
                                                    @else
                                                        <span class="att-cell-letter" style="color: #b45309; font-size: 10px;">OT</span>
                                                    @endif
                                                </div>
                                            @elseif ($status === 'cuti')
                                                <div class="att-cell-wrap att-cell-cuti" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')" title="{{ $emp->full_name }} ({{ $d['date'] }}): Cuti (Klik untuk ubah)">
                                                    <span class="att-cell-letter">C</span>
                                                </div>
                                            @elseif ($status === 'sakit')
                                                <div class="att-cell-wrap att-cell-sakit" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')" title="{{ $emp->full_name }} ({{ $d['date'] }}): Sakit (Klik untuk ubah)">
                                                    <span class="att-cell-letter">S</span>
                                                </div>
                                            @elseif ($status === 'izin')
                                                <div class="att-cell-wrap att-cell-izin" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')" title="{{ $emp->full_name }} ({{ $d['date'] }}): Izin (Klik untuk ubah)">
                                                    <span class="att-cell-letter">I</span>
                                                </div>
                                            @elseif ($status === 'absen')
                                                <div class="att-cell-wrap att-cell-alpa" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')" title="{{ $emp->full_name }} ({{ $d['date'] }}): Alpa / Mangkir (Klik untuk ubah)">
                                                    <span class="att-cell-letter">A</span>
                                                </div>
                                            @elseif ($isOff)
                                                <div class="att-cell-wrap" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')" title="{{ $emp->full_name }} ({{ $d['date'] }}): Jadwal Libur / Off (Klik untuk input jam)">
                                                    &nbsp;
                                                </div>
                                            @else
                                                <div class="att-cell-wrap text-muted" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')" title="{{ $emp->full_name }} ({{ $d['date'] }}): Belum ada presensi (Klik untuk input jam)">
                                                    <span style="color: #cbd5e1; font-size: 13px;">&ndash;</span>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach

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

    <!-- MODAL 2: MANUAL ATTENDANCE (CREATE & EDIT) -->
    <div wire:ignore.self class="modal fade" id="manualAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="manualAttendanceModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;" role="document">
            <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                <div class="modal-header bg-white border-bottom py-3 px-4">
                    <h5 class="modal-title text-dark font-weight-normal" id="manualAttendanceModalLabel" style="font-size: 16px;">
                        {{ $editingAttendanceId ? 'Edit Catatan Presensi' : 'Input Presensi Manual' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form wire:submit.prevent="saveManualAttendance">
                    <div class="modal-body py-3 px-4 bg-white">


                        <!-- Pilih Karyawan (Select2 Searchable dengan optgroup status kerja) -->
                        <div class="form-group mb-3">
                            <label for="manualEmployeeId" class="font-weight-normal">Karyawan <span class="text-danger">*</span></label>
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

                        <!-- Tanggal -->
                        <div class="form-group mb-3">
                            <label for="manualDate" class="font-weight-bold" style="font-size: 13px; color: #0f172a;">Tanggal Presensi <span class="text-danger">*</span></label>
                            <input type="date" id="manualDate" wire:model.live="manualDate" class="form-control @error('manualDate') is-invalid @enderror" {{ $lockDateAndEmployee ? 'disabled' : '' }}>
                            @error('manualDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($manualStatus === 'libur')
                            <!-- 1. BD Callout: Informasi Hari Libur / Off (Kontras Tinggi) -->
                            <div class="bd-callout bd-callout-info py-2.5 px-3 mb-3" style="border-left: 4px solid #0b52aa; background-color: #f0f7ff; border-radius: 8px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-day mr-2" style="font-size: 16px; color: #0b52aa;"></i>
                                    <span style="font-size: 13.5px; font-weight: 700; color: #0f172a;">Jadwal Hari Libur / Off</span>
                                </div>
                                <div class="mt-1" style="font-size: 12.5px; color: #1e293b; font-weight: 500; line-height: 1.4;">
                                    Tanggal ini merupakan hari libur. Secara default karyawan libur. Centang checklist di bawah jika ada penugasan lembur.
                                </div>
                            </div>

                            <!-- 2. Fieldset Checklist: Apakah Lembur Seharian? -->
                            <fieldset class="custom-modal-fieldset">
                                <legend class="custom-modal-legend">
                                    <div class="custom-control custom-checkbox d-inline-block">
                                        <input type="checkbox" class="custom-control-input" id="hasFullDayOvertimeCheck" wire:model.live="hasFullDayOvertime">
                                        <label class="custom-control-label font-weight-bold mb-0" for="hasFullDayOvertimeCheck" style="cursor: pointer; font-size: 13px; color: #0f172a;">
                                            Apakah Lembur Seharian?
                                        </label>
                                    </div>
                                </legend>

                                @if ($hasFullDayOvertime)
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <label for="manualClockIn" class="font-weight-bold mb-0" style="font-size: 12.5px; color: #0f172a;">Jam Masuk</label>
                                                    @if ($manualClockIn)
                                                        <a href="javascript:void(0)" wire:click="clearClockIn" class="text-danger font-weight-bold" style="font-size: 11.5px;" title="Kosongkan jam masuk">
                                                            Hapus Jam
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="input-group">
                                                    <input type="time" id="manualClockIn" wire:model.live="manualClockIn" class="form-control @error('manualClockIn') is-invalid @enderror">
                                                    @if ($manualClockIn)
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary" wire:click="clearClockIn" title="Hapus jam masuk">
                                                                <i class="fas fa-times text-danger"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('manualClockIn')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <label for="manualClockOut" class="font-weight-bold mb-0" style="font-size: 12.5px; color: #0f172a;">Jam Keluar</label>
                                                    @if ($manualClockOut)
                                                        <a href="javascript:void(0)" wire:click="clearClockOut" class="text-danger font-weight-bold" style="font-size: 11.5px;" title="Kosongkan jam keluar">
                                                            Hapus Jam
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="input-group">
                                                    <input type="time" id="manualClockOut" wire:model.live="manualClockOut" class="form-control @error('manualClockOut') is-invalid @enderror">
                                                    @if ($manualClockOut)
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary" wire:click="clearClockOut" title="Hapus jam keluar">
                                                                <i class="fas fa-times text-danger"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('manualClockOut')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="py-1" style="font-size: 12.5px; color: #1e293b; font-weight: 500;">
                                        <i class="fas fa-info-circle text-primary mr-1"></i> Centang checklist <strong>Apakah Lembur Seharian?</strong> jika karyawan bekerja penuh satu hari libur.
                                    </div>
                                @endif
                            </fieldset>

                            <!-- 3. Fieldset Checklist: Apakah Lembur Per Jam? -->
                            <fieldset class="custom-modal-fieldset">
                                <legend class="custom-modal-legend">
                                    <div class="custom-control custom-checkbox d-inline-block">
                                        <input type="checkbox" class="custom-control-input" id="hasOvertimeCheck" wire:model.live="hasOvertime">
                                        <label class="custom-control-label font-weight-bold mb-0" for="hasOvertimeCheck" style="cursor: pointer; font-size: 13px; color: #0f172a;">
                                            Apakah Lembur Per Jam?
                                        </label>
                                    </div>
                                </legend>

                                @if ($hasOvertime)
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <label for="manualOvertimeIn" class="font-weight-bold mb-0" style="font-size: 12.5px; color: #0f172a;">Lembur Mulai</label>
                                                    @if ($manualOvertimeIn)
                                                        <a href="javascript:void(0)" wire:click="clearOvertimeIn" class="text-danger font-weight-bold" style="font-size: 11.5px;" title="Kosongkan jam lembur mulai">
                                                            Hapus Jam
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="input-group">
                                                    <input type="time" id="manualOvertimeIn" wire:model.live="manualOvertimeIn" class="form-control @error('manualOvertimeIn') is-invalid @enderror">
                                                    @if ($manualOvertimeIn)
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary" wire:click="clearOvertimeIn" title="Hapus lembur mulai">
                                                                <i class="fas fa-times text-danger"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('manualOvertimeIn')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <label for="manualOvertimeOut" class="font-weight-bold mb-0" style="font-size: 12.5px; color: #0f172a;">Lembur Selesai</label>
                                                    @if ($manualOvertimeOut)
                                                        <a href="javascript:void(0)" wire:click="clearOvertimeOut" class="text-danger font-weight-bold" style="font-size: 11.5px;" title="Kosongkan jam lembur selesai">
                                                            Hapus Jam
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="input-group">
                                                    <input type="time" id="manualOvertimeOut" wire:model.live="manualOvertimeOut" class="form-control @error('manualOvertimeOut') is-invalid @enderror">
                                                    @if ($manualOvertimeOut)
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary" wire:click="clearOvertimeOut" title="Hapus lembur selesai">
                                                                <i class="fas fa-times text-danger"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('manualOvertimeOut')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="py-1" style="font-size: 12.5px; color: #1e293b; font-weight: 500;">
                                        <i class="fas fa-info-circle text-primary mr-1"></i> Centang checklist <strong>Apakah Lembur Per Jam?</strong> jika ada perhitungan jam lembur.
                                    </div>
                                @endif
                            </fieldset>

                        @else
                            <!-- TAMPILAN HARI KERJA BIASA -->
                            <!-- 1. Fieldset Status Kehadiran (Meliputi Select Option dan Jam Masuk / Jam Keluar) -->
                            <fieldset class="custom-modal-fieldset">
                                <legend class="custom-modal-legend">
                                    Status Kehadiran <span class="text-danger">*</span>
                                </legend>

                                <!-- Select Option Status Kehadiran -->
                                <div class="form-group mb-2.5">
                                    <select id="manualStatus" wire:model.live="manualStatus" class="form-control @error('manualStatus') is-invalid @enderror">
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
                                                    <label for="manualClockIn" class="font-weight-bold mb-0" style="font-size: 12.5px; color: #0f172a;">
                                                        Jam Masuk <span class="text-danger">*</span>
                                                    </label>
                                                    @if ($manualClockIn)
                                                        <a href="javascript:void(0)" wire:click="clearClockIn" class="text-danger font-weight-bold" style="font-size: 11.5px;" title="Kosongkan jam masuk">
                                                            Hapus Jam
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="input-group">
                                                    <input type="time" id="manualClockIn" wire:model.live="manualClockIn" class="form-control @error('manualClockIn') is-invalid @enderror">
                                                    @if ($manualClockIn)
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary" wire:click="clearClockIn" title="Hapus jam masuk">
                                                                <i class="fas fa-times text-danger"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('manualClockIn')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <label for="manualClockOut" class="font-weight-bold mb-0" style="font-size: 12.5px; color: #0f172a;">
                                                        Jam Keluar
                                                    </label>
                                                    @if ($manualClockOut)
                                                        <a href="javascript:void(0)" wire:click="clearClockOut" class="text-danger font-weight-bold" style="font-size: 11.5px;" title="Kosongkan jam keluar">
                                                            Hapus Jam
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="input-group">
                                                    <input type="time" id="manualClockOut" wire:model.live="manualClockOut" class="form-control @error('manualClockOut') is-invalid @enderror">
                                                    @if ($manualClockOut)
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary" wire:click="clearClockOut" title="Hapus jam keluar">
                                                                <i class="fas fa-times text-danger"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('manualClockOut')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="py-1" style="font-size: 12.5px; color: #1e293b; font-weight: 500;">
                                        <i class="fas fa-info-circle text-primary mr-1"></i> Status <strong>{{ ucfirst($manualStatus) }}</strong> tidak memerlukan input jam masuk & jam keluar.
                                    </div>
                                @endif
                            </fieldset>

                            <!-- 2. Fieldset Lembur (Meliputi Input Lembur Mulai dan Lembur Selesai) -->
                            <fieldset class="custom-modal-fieldset">
                                <legend class="custom-modal-legend">
                                    <div class="custom-control custom-checkbox d-inline-block">
                                        <input type="checkbox" class="custom-control-input" id="hasOvertimeCheck" wire:model.live="hasOvertime">
                                        <label class="custom-control-label font-weight-bold mb-0" for="hasOvertimeCheck" style="cursor: pointer; font-size: 13px; color: #0f172a;">
                                            Apakah Karyawan Lembur?
                                        </label>
                                    </div>
                                </legend>

                                @if ($hasOvertime)
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <label for="manualOvertimeIn" class="font-weight-bold mb-0" style="font-size: 12.5px; color: #0f172a;">Lembur Mulai</label>
                                                    @if ($manualOvertimeIn)
                                                        <a href="javascript:void(0)" wire:click="clearOvertimeIn" class="text-danger font-weight-bold" style="font-size: 11.5px;" title="Kosongkan jam lembur mulai">
                                                            Hapus Jam
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="input-group">
                                                    <input type="time" id="manualOvertimeIn" wire:model.live="manualOvertimeIn" class="form-control @error('manualOvertimeIn') is-invalid @enderror">
                                                    @if ($manualOvertimeIn)
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary" wire:click="clearOvertimeIn" title="Hapus lembur mulai">
                                                                <i class="fas fa-times text-danger"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('manualOvertimeIn')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <label for="manualOvertimeOut" class="font-weight-bold mb-0" style="font-size: 12.5px; color: #0f172a;">Lembur Selesai</label>
                                                    @if ($manualOvertimeOut)
                                                        <a href="javascript:void(0)" wire:click="clearOvertimeOut" class="text-danger font-weight-bold" style="font-size: 11.5px;" title="Kosongkan jam lembur selesai">
                                                            Hapus Jam
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="input-group">
                                                    <input type="time" id="manualOvertimeOut" wire:model.live="manualOvertimeOut" class="form-control @error('manualOvertimeOut') is-invalid @enderror">
                                                    @if ($manualOvertimeOut)
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary" wire:click="clearOvertimeOut" title="Hapus lembur selesai">
                                                                <i class="fas fa-times text-danger"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('manualOvertimeOut')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="py-1" style="font-size: 12.5px; color: #1e293b; font-weight: 500;">
                                        <i class="fas fa-info-circle text-primary mr-1"></i> Centang checklist <strong>Apakah Karyawan Lembur?</strong> di atas untuk mengaktifkan input jam lembur.
                                    </div>
                                @endif
                            </fieldset>
                        @endif

                        <!-- Catatan -->
                        <div class="form-group mb-2">
                            <label for="manualNotes" class="font-weight-bold" style="font-size: 12.5px; color: #0f172a;">Catatan / Keterangan</label>
                            <textarea id="manualNotes" wire:model="manualNotes" class="form-control" rows="2" placeholder="Alasan koreksi manual..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-white border-top py-3 px-4">
                        @if ($editingAttendanceId)
                            <div class="row w-100 mx-0">
                                <div class="col-6 pr-1.5 pl-0">
                                    <button type="button" wire:click="confirmDeleteAttendance({{ $editingAttendanceId }})" wire:loading.attr="disabled" class="btn btn-danger btn-block text-nowrap font-weight-500" style="border-radius: 8px; font-size: 13.5px; padding: 9px 14px;">
                                        <i class="fas fa-trash-alt mr-1.5"></i> Hapus Presensi
                                    </button>
                                </div>
                                <div class="col-6 pl-1.5 pr-0">
                                    <button type="submit" class="btn btn-primary btn-block text-nowrap font-weight-500" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; font-size: 13.5px; padding: 9px 14px;">
                                        <i class="fas fa-save mr-1.5"></i> Simpan Presensi
                                    </button>
                                </div>
                            </div>
                        @else
                            <button type="submit" class="btn btn-primary btn-block text-nowrap font-weight-500" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; font-size: 13.5px; padding: 9px 14px;">
                                <i class="fas fa-save mr-1.5"></i> Simpan Presensi
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
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
