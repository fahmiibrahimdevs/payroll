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
                Tampilan matriks kehadiran harian seluruh karyawan lengkap dengan jam scan masuk dan scan pulang.
            </p>
        </div>

        @if (auth()->user()?->hasRole(['admin', 'direktur']))
            <div class="mt-3 mt-md-0 flex-shrink-0 d-flex align-items-center" style="gap: 8px;">
                <button type="button" onclick="$('#importAttendanceModal').modal('show')" class="btn btn-primary d-inline-flex align-items-center font-weight-normal shadow-sm" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 8px 16px; font-size: 13px;">
                    <i class="fas fa-file-excel mr-1.5"></i> Import Excel Mesin
                </button>
                <button type="button" wire:click="openCreateModal" class="btn btn-secondary d-inline-flex align-items-center font-weight-normal shadow-sm" style="background-color: #f8fafc; border-color: #cbd5e1; color: #1e293b; border-radius: 8px; padding: 8px 16px; font-size: 13px;">
                    <i class="fas fa-plus mr-1.5 text-primary"></i> Input Manual
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

    <!-- Keterangan Legenda Presensi (Clean Executive Single-Line Bar) -->
    <div class="card mb-3 shadow-none" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px;">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between" style="gap: 12px;">
            <!-- Kiri: Judul & Petunjuk Interaktif Ringkas -->
            <div class="d-flex align-items-center" style="font-size: 12.5px; color: #334155; font-weight: 500;">
                <span class="d-inline-flex align-items-center justify-content-center mr-2 flex-shrink-0" style="width: 22px; height: 22px; border-radius: 6px; background-color: #eff6ff; color: #0b52aa;">
                    <i class="fas fa-info-circle" style="font-size: 11px;"></i>
                </span>
                <span class="font-weight-600 text-dark mr-1.5" style="color: #0f172a;">Petunjuk Presensi &amp; Keterangan Warna:</span>
                <span style="color: #475569;">Klik kotak tanggal untuk melihat detail atau koreksi manual.</span>
            </div>

            <!-- Kanan: Pill Badges Legenda Presensi Ringkas & Elegan -->
            <div class="d-flex flex-wrap align-items-center flex-shrink-0" style="gap: 8px; font-size: 11.5px;">
                <span class="d-inline-flex align-items-center font-weight-500" style="padding: 3px 9px; border-radius: 6px; background-color: #ecfdf5; border: 1px solid #a7f3d0; color: #047857;">
                    <span style="display:inline-block; width:6px; height:6px; background-color:#10b981; border-radius:50%; margin-right: 6px;"></span>
                    Hadir (Mesin Fingerprint)
                </span>
                <span class="d-inline-flex align-items-center font-weight-500" style="padding: 3px 9px; border-radius: 6px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8;">
                    <span style="display:inline-block; width:6px; height:6px; background-color:#3b82f6; border-radius:50%; margin-right: 6px;"></span>
                    Koreksi / Input Manual
                </span>
                <span class="d-inline-flex align-items-center font-weight-500" style="padding: 3px 9px; border-radius: 6px; background-color: #fffbeb; border: 1px solid #fde68a; color: #b45309;">
                    <span style="display:inline-block; width:6px; height:6px; background-color:#f59e0b; border-radius:50%; margin-right: 6px;"></span>
                    Lembur (OT)
                </span>
                <span class="d-inline-flex align-items-center font-weight-500" style="padding: 3px 9px; border-radius: 6px; background-color: #fff1f2; border: 1px solid #fecdd3; color: #e11d48;">
                    <span style="display:inline-block; width:6px; height:6px; background-color:#f43f5e; border-radius:50%; margin-right: 6px;"></span>
                    Hari Libur / Minggu
                </span>
                <span class="d-inline-flex align-items-center font-weight-500" style="padding: 3px 9px; border-radius: 6px; background-color: #f8fafc; border: 1px solid #e2e8f0; color: #475569;">
                    <span style="display:inline-block; width:6px; height:6px; background-color:#94a3b8; border-radius:50%; margin-right: 6px;"></span>
                    Kosong
                </span>
            </div>
        </div>
    </div>

    <!-- Main Card (Modern SaaS Executive Attendance Matrix) -->
    <div class="card cash-advance-main-card mb-4">
        <div class="cash-advance-top-card">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center" style="gap: 12px; padding: 18px 24px; border-bottom: 1px solid #f1f5f9;">
                <!-- Title & Period Subtitle -->
                <div>
                    <h4 class="mb-1" style="font-size: 16px; font-weight: 600; color: #0f172a;">
                        Presensi &bull; <span class="text-primary font-weight-600">Matriks Kehadiran</span>
                    </h4>
                    <p class="text-muted mb-0" style="font-size: 12px; font-weight: 400;">
                        Periode: <strong class="text-dark font-weight-500">{{ $periodFullRangeLabel }}</strong> &bull; <span style="color: #64748b;">{{ count($matrixDates) }} Hari Presensi</span>
                    </p>
                </div>

                <!-- Modern Month Navigator Toolbar -->
                <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                    <div class="d-inline-flex align-items-center bg-white border px-1.5 py-1" style="border-radius: 9999px; border-color: #e2e8f0 !important; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                        <button type="button" wire:click="previousMonth" class="btn btn-sm btn-icon text-muted p-0 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; border-radius: 50%; border: none; background: transparent; transition: all 0.2s;" title="Bulan Sebelumnya">
                            <i class="fas fa-chevron-left" style="font-size: 11px;"></i>
                        </button>
                        <div class="px-2 font-weight-600 text-dark d-inline-flex align-items-center" style="font-size: 13px; min-width: 135px; justify-content: center; letter-spacing: 0.2px;">
                            <i class="far fa-calendar-alt text-primary mr-1.5" style="font-size: 12px;"></i>
                            <span>{{ $periodMonthLabel }}</span>
                        </div>
                        <button type="button" wire:click="nextMonth" class="btn btn-sm btn-icon text-muted p-0 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; border-radius: 50%; border: none; background: transparent; transition: all 0.2s;" title="Bulan Berikutnya">
                            <i class="fas fa-chevron-right" style="font-size: 11px;"></i>
                        </button>
                    </div>

                    @if (! $isCurrentMonth)
                        <button type="button" wire:click="goToCurrentMonth" class="btn btn-sm font-weight-500 shadow-xs" style="border-radius: 9999px; font-size: 12px; padding: 4px 12px; border: 1px solid #cbd5e1; background-color: #f8fafc; color: #0b52aa;">
                            <i class="fas fa-undo-alt mr-1" style="font-size: 10px;"></i> Bulan Ini
                        </button>
                    @endif
                </div>
            </div>

            <!-- Controls: Show Entries & Search Bar -->
            <div class="dt-control-wrapper" style="padding: 12px 24px; border-bottom: 1px solid #f1f5f9;">
                <div class="dt-show-entries">
                    Show
                    <select wire:model.live="perPage" class="dt-select-entries">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    Entries
                </div>

                <div class="dt-search-wrapper">
                    <span class="mr-1 text-muted" style="font-size: 12px;">Search:</span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama, PIN, jabatan...">
                </div>
            </div>
        </div>

        <!-- Modern Full-Width Data Table (Clean Executive Grid) & Callout Styles -->
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

            .modern-attendance-matrix {
                border-collapse: separate !important;
                border-spacing: 0 !important;
                width: 100%;
                background-color: #ffffff;
            }
            .modern-attendance-matrix thead th {
                background-color: #ffffff;
                border-top: none !important;
                border-bottom: 2px solid #e2e8f0 !important;
                padding: 7px 4px;
                vertical-align: middle;
            }
            .modern-attendance-matrix tbody tr {
                border-bottom: 1px solid #f1f5f9;
                transition: background-color 0.15s ease;
            }
            .modern-attendance-matrix tbody tr:hover {
                background-color: #f8fafc;
            }
            .modern-attendance-matrix tbody td {
                border-bottom: 1px solid #f1f5f9 !important;
                border-right: 1px solid #f8fafc !important;
                padding: 4px 3px;
                vertical-align: middle;
            }
            /* Sticky Employee Column */
            .sticky-emp-col {
                position: sticky !important;
                left: 0;
                background-color: #ffffff !important;
                z-index: 10;
                box-shadow: 4px 0 10px -3px rgba(0, 0, 0, 0.05);
                border-right: 1px solid #e2e8f0 !important;
                white-space: nowrap !important;
            }
            .modern-attendance-matrix tbody tr:hover .sticky-emp-col {
                background-color: #f8fafc !important;
            }
            /* Table Group Header */
            .modern-attendance-matrix tbody tr.table-group-header .sticky-emp-col,
            .modern-attendance-matrix tbody tr.table-group-header:hover .sticky-emp-col {
                background-color: #f0f6ff !important;
                z-index: 11 !important;
                white-space: nowrap !important;
            }
            .modern-attendance-matrix tbody tr.table-group-header:hover {
                background-color: #f0f6ff !important;
            }
            .modern-attendance-matrix tbody tr.table-group-header td {
                vertical-align: middle !important;
                white-space: nowrap !important;
            }
            /* Modern Attendance Micro-Chip (Selalu Hijau untuk Hadir) */
            .att-chip {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-width: 76px;
                padding: 2.5px 6px;
                border-radius: 6px;
                line-height: 1.25;
                cursor: pointer;
                transition: transform 0.12s ease, box-shadow 0.12s ease;
                user-select: none;
                white-space: nowrap;
            }
            .att-chip:hover {
                transform: translateY(-1px);
                box-shadow: 0 3px 6px -1px rgba(0, 0, 0, 0.08);
            }
            .att-chip-present {
                background-color: #ecfdf5;
                border: 1px solid #a7f3d0;
            }
            .att-chip-present .att-time-in,
            .att-chip-present .att-time-out {
                color: #047857 !important;
                font-weight: 600 !important;
                font-size: 11px !important;
            }
            .att-chip-manual {
                background-color: #eff6ff;
                border: 1px solid #bfdbfe;
            }
            .att-chip-manual .att-time-in,
            .att-chip-manual .att-time-out {
                color: #1d4ed8 !important;
                font-weight: 600 !important;
                font-size: 11px !important;
            }
            .att-chip-ot {
                background-color: #fffbeb;
                border: 1px solid #fde68a;
            }
            .att-chip-ot .att-time-ot-badge {
                color: #b45309;
                font-weight: 600;
                font-size: 11px;
            }
            .att-time-ot-sub {
                color: #b45309;
                font-weight: 600;
                font-size: 9.5px;
                line-height: 1;
                margin-top: 2px;
            }
            .att-chip-empty {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                border-radius: 6px;
                cursor: pointer;
                color: #cbd5e1;
                font-size: 14px;
                transition: background-color 0.15s ease, color 0.15s ease;
            }
            .att-chip-empty:hover {
                background-color: #f1f5f9;
                color: #0b52aa;
            }
            .att-chip-holiday {
                background-color: #fff1f2;
                border: 1px solid #fecdd3;
                color: #e11d48;
                font-weight: 500;
                font-size: 11px;
                padding: 3px 6px;
                border-radius: 6px;
                display: inline-block;
            }
            /* Summary Badges in Summary Column */
            .summary-badge-hadir {
                background-color: #ecfdf5;
                color: #047857;
                border: 1px solid #a7f3d0;
                border-radius: 6px;
                padding: 3.5px 9px;
                font-size: 12px;
                font-weight: 600;
                white-space: nowrap;
            }
            .summary-badge-alpa {
                background-color: #fef2f2;
                color: #b91c1c;
                border: 1px solid #fecaca;
                border-radius: 6px;
                padding: 3px 6px;
                font-size: 10.5px;
                font-weight: 500;
                white-space: nowrap;
            }
        </style>

        <div class="table-responsive">
            <table class="table modern-attendance-matrix mb-0">
                <thead>
                    <tr>
                        <!-- Kolom Karyawan (Sticky Left) -->
                        <th class="sticky-emp-col text-left !px-3.5 !py-2 text-nowrap" style="min-width: 280px; width: 280px; white-space: nowrap;">
                            <div class="d-flex align-items-center justify-content-between text-nowrap" style="white-space: nowrap;">
                                <span class="font-weight-600 text-dark text-nowrap" style="font-size: 12.5px; letter-spacing: 0.3px; text-transform: uppercase; white-space: nowrap;">
                                    KARYAWAN
                                </span>
                                <span class="text-muted font-weight-400 text-nowrap" style="font-size: 11.5px; white-space: nowrap;">
                                    {{ $matrixEmployees->total() }} Orang
                                </span>
                            </div>
                        </th>

                        <!-- Kolom Tanggal (1 s/d 31) -->
                        @foreach ($matrixDates as $d)
                            <th class="text-center !py-1.5 !px-1" style="min-width: 76px; background-color: {{ $d['is_sunday'] ? '#fff1f2' : '#ffffff' }}; border-bottom: 2px solid {{ $d['is_sunday'] ? '#fecdd3' : '#e2e8f0' }} !important;">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="font-weight-600" style="font-size: 13.5px; line-height: 1.1; color: {{ $d['is_sunday'] ? '#e11d48' : '#0f172a' }};">
                                        {{ $d['day'] }}
                                    </span>
                                    <span class="text-uppercase font-weight-500 mt-0.5" style="font-size: 10.5px; line-height: 1; color: {{ $d['is_sunday'] ? '#f43f5e' : '#64748b' }};">
                                        {{ $d['dow_name'] }}
                                    </span>
                                </div>
                            </th>
                        @endforeach

                        <!-- Kolom Rekapitulasi (Clean Solid Anchor) -->
                        <th class="text-left !px-3.5 !py-2" style="min-width: 190px; background-color: #f8fafc; border-left: 2px solid #e2e8f0; border-bottom: 2px solid #e2e8f0 !important;">
                            <div class="font-weight-600 text-dark" style="font-size: 12.5px; letter-spacing: 0.3px; text-transform: uppercase;">
                                REKAP KEHADIRAN
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody style="font-size: 12.5px;">
                    @php
                        $groupedEmployees = $matrixEmployees->getCollection()->groupBy(function ($emp) {
                            return $emp->employment_status instanceof \App\Enums\EmploymentStatus
                                ? $emp->employment_status->value
                                : (string) ($emp->employment_status ?: 'lainnya');
                        });
                    @endphp

                    @forelse ($groupedEmployees as $statusKey => $groupEmployees)
                        @php
                            $statusLabel = match ($statusKey) {
                                'tetap' => 'Karyawan Tetap',
                                'kontrak' => 'Kontrak',
                                'harian' => 'Harian Lepas',
                                'magang' => 'Magang',
                                default => ucfirst($statusKey),
                            };
                            $statusIcon = match ($statusKey) {
                                'tetap' => 'fas fa-id-badge',
                                'kontrak' => 'fas fa-file-contract',
                                'harian' => 'fas fa-user-clock',
                                'magang' => 'fas fa-user-graduate',
                                default => 'fas fa-users',
                            };
                        @endphp
                        <!-- Baris Sub-Header Group Status Kerja -->
                        <tr class="table-group-header" style="background-color: #f0f6ff; border-top: 1px solid #bfdbfe; border-bottom: 1px solid #dbeafe;">
                            <td class="sticky-emp-col text-nowrap !py-2 !px-3.5" style="border-left: 4px solid #0b52aa !important; background-color: #f0f6ff !important; white-space: nowrap !important;">
                                <div class="d-flex align-items-center justify-content-between text-nowrap" style="white-space: nowrap; gap: 10px;">
                                    <div class="d-flex align-items-center text-nowrap" style="white-space: nowrap;">
                                        <span class="d-inline-flex align-items-center justify-content-center mr-2 flex-shrink-0" style="width: 22px; height: 22px; border-radius: 5px; background-color: #dbeafe; font-size: 11px; flex-shrink: 0;">
                                            <i class="{{ $statusIcon }}" style="color: #0b52aa;"></i>
                                        </span>
                                        <span class="font-weight-600 text-nowrap" style="font-size: 12.5px; letter-spacing: 0.3px; color: #0f172a; text-transform: uppercase; white-space: nowrap;">
                                            STATUS: {{ $statusLabel }}
                                        </span>
                                    </div>
                                    <span class="badge font-weight-500 text-nowrap flex-shrink-0" style="font-size: 11px; padding: 2.5px 8px; border-radius: 9999px; background-color: #ffffff; color: #0b52aa; border: 1px solid #bfdbfe; white-space: nowrap;">
                                        {{ $groupEmployees->count() }} Orang
                                    </span>
                                </div>
                            </td>
                            <td colspan="{{ count($matrixDates) + 1 }}" style="background-color: #f0f6ff; border-top: 1px solid #bfdbfe; border-bottom: 1px solid #dbeafe;"></td>
                        </tr>

                        @foreach ($groupEmployees as $emp)
                            @php
                                $empAttCollection = $attendancesInRange->get($emp->id, collect())->keyBy(fn ($a) => is_string($a->date) ? substr($a->date, 0, 10) : ($a->date ? $a->date->toDateString() : ''));
                                $hadirCount = 0;
                                $manualCount = 0;
                                $cutiCount = 0;
                                $sakitCount = 0;
                                $izinCount = 0;
                                $absenCount = 0;
                                $deptPosParts = array_filter([$emp->department?->name, $emp->position?->name]);
                                $deptPosLabel = !empty($deptPosParts) ? implode(' • ', $deptPosParts) : '-';
                            @endphp
                        <tr wire:key="matrix-row-{{ $emp->id }}">
                            <!-- Karyawan: Avatar, Nama, Department & Jabatan (Sticky Left) -->
                            <td class="sticky-emp-col align-middle !px-3.5 !py-2 text-nowrap">
                                <div class="d-flex align-items-center text-nowrap">
                                    <img src="{{ $emp->photo_url }}" alt="{{ $emp->full_name }}" class="rounded-circle mr-2.5 flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover; border: 1.5px solid #e2e8f0;">
                                    <div class="text-nowrap" style="line-height: 1.25;">
                                        <div class="font-weight-600 text-dark" style="font-size: 13.5px; color: #0f172a;">{{ $emp->full_name }}</div>
                                        <div class="mt-0.5 text-nowrap" style="font-size: 11.5px; color: #64748b; font-weight: 400;">
                                            {{ $deptPosLabel }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Sel Tanggal 1 s/d 31 -->
                            @foreach ($matrixDates as $d)
                                @php
                                    $att = $empAttCollection->get($d['date']);
                                    $status = $att ? strtolower($att->status) : null;
                                    $hasAttendance = $att !== null && in_array($status, ['hadir', 'terlambat', 'pulang_cepat'], true);
                                    $isCuti = $status === 'cuti';
                                    $isSakit = $status === 'sakit';
                                    $isIzin = $status === 'izin';
                                    $isAbsen = $status === 'absen';

                                    if ($hasAttendance) {
                                        $hadirCount++;
                                        if (($att->source ?? '') === 'manual') {
                                            $manualCount++;
                                        }
                                    } elseif ($isCuti) {
                                        $cutiCount++;
                                    } elseif ($isSakit) {
                                        $sakitCount++;
                                    } elseif ($isIzin) {
                                        $izinCount++;
                                    } elseif ($isAbsen || (! $d['is_sunday'] && ! $att)) {
                                        $absenCount++;
                                    }
                                @endphp
                                <td class="text-center p-1 align-middle" style="background-color: {{ $d['is_sunday'] ? '#fff1f220' : 'transparent' }};">
                                    @php
                                        $isManual = ($att?->source === 'manual');
                                    @endphp
                                    @if ($hasAttendance && $att->clock_in)
                                        <div class="att-chip {{ $isManual ? 'att-chip-manual' : 'att-chip-present' }}"
                                             wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')"
                                             title="{{ $emp->full_name }} ({{ $d['date'] }}): Masuk {{ substr($att->clock_in, 0, 5) }} s/d Pulang {{ $att->clock_out ? substr($att->clock_out, 0, 5) : '--:--' }}{{ $att->overtime_in ? ' | Lembur Masuk ' . substr($att->overtime_in, 0, 5) . ' s/d Lembur Pulang ' . ($att->overtime_out ? substr($att->overtime_out, 0, 5) : '--:--') : '' }}{{ $isManual ? ' [Koreksi Manual Admin]' : ' [Mesin Fingerprint]' }} (Klik untuk ubah)">
                                            <div class="d-flex align-items-center justify-content-center text-nowrap" style="gap: 3px; font-variant-numeric: tabular-nums;">
                                                <span class="att-time-in" style="font-size: 11px; font-weight: 600;">{{ substr($att->clock_in, 0, 5) }}</span>
                                                <span style="color: #64748b; font-size: 9.5px; font-weight: 500;">s/d</span>
                                                <span class="att-time-out" style="font-size: 11px; font-weight: 600;">{{ $att->clock_out ? substr($att->clock_out, 0, 5) : '--:--' }}</span>
                                            </div>
                                            @if ($att->overtime_in)
                                                <div class="d-flex align-items-center justify-content-center text-nowrap mt-0.5" style="gap: 3px; font-variant-numeric: tabular-nums; font-size: 11px; color: #b45309;">
                                                    <span style="font-weight: 600; font-size: 11px; color: #b45309;">{{ substr($att->overtime_in, 0, 5) }}</span>
                                                    <span style="color: #64748b; font-size: 9.5px; font-weight: 500;">s/d</span>
                                                    <span style="font-weight: 600; font-size: 11px; color: #b45309;">{{ $att->overtime_out ? substr($att->overtime_out, 0, 5) : '--:--' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif ($att && $att->overtime_in)
                                        <div class="att-chip att-chip-ot"
                                             wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')"
                                             title="{{ $emp->full_name }} ({{ $d['date'] }}): Lembur Masuk {{ substr($att->overtime_in, 0, 5) }} s/d Lembur Pulang {{ $att->overtime_out ? substr($att->overtime_out, 0, 5) : '--:--' }} (Klik untuk ubah)">
                                            <div class="d-flex align-items-center justify-content-center text-nowrap" style="gap: 3px; font-variant-numeric: tabular-nums; font-size: 11px; color: #b45309;">
                                                <span style="font-weight: 600; font-size: 11px; color: #b45309;">{{ substr($att->overtime_in, 0, 5) }}</span>
                                                <span style="color: #64748b; font-size: 9.5px; font-weight: 500;">s/d</span>
                                                <span style="font-weight: 600; font-size: 11px; color: #b45309;">{{ $att->overtime_out ? substr($att->overtime_out, 0, 5) : '--:--' }}</span>
                                            </div>
                                        </div>
                                    @elseif ($isCuti)
                                        <span class="badge badge-primary font-weight-500" style="font-size: 11px; padding: 3px 6px; border-radius: 4px; cursor: pointer;" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')">Cuti</span>
                                    @elseif ($isSakit)
                                        <span class="badge badge-warning font-weight-500" style="font-size: 11px; padding: 3px 6px; border-radius: 4px; cursor: pointer;" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')">Sakit</span>
                                    @elseif ($isIzin)
                                        <span class="badge badge-info font-weight-500" style="font-size: 11px; padding: 3px 6px; border-radius: 4px; cursor: pointer;" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')">Izin</span>
                                    @elseif ($d['is_sunday'])
                                        <span class="att-chip-holiday" title="Hari Libur / Minggu">Libur</span>
                                    @else
                                        <span class="att-chip-empty" wire:click="openCellModal({{ $emp->id }}, '{{ $d['date'] }}')" title="Belum ada presensi (Klik untuk input jam)">&ndash;</span>
                                    @endif
                                </td>
                            @endforeach

                            <!-- Rekapitulasi Akhir (Modern Badges) -->
                            <td class="align-middle !px-3 !py-1.5" style="background-color: #f8fafc; border-left: 2px solid #e2e8f0;">
                                <div class="d-flex align-items-center flex-wrap" style="gap: 5px;">
                                    <span class="summary-badge-hadir" title="Total Hari Masuk Kerja">
                                        <i class="fas fa-check-circle mr-1" style="font-size: 11px;"></i>{{ $hadirCount }} Hari
                                    </span>
                                    @if ($cutiCount > 0)
                                        <span class="badge font-weight-500" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 11px; padding: 3px 7px;">
                                            {{ $cutiCount }} Cuti
                                        </span>
                                    @endif
                                    @if ($sakitCount > 0)
                                        <span class="badge font-weight-500" style="background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; border-radius: 6px; font-size: 11px; padding: 3px 7px;">
                                            {{ $sakitCount }} Sakit
                                        </span>
                                    @endif
                                    @if ($izinCount > 0)
                                        <span class="badge font-weight-500" style="background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 6px; font-size: 11px; padding: 3px 7px;">
                                            {{ $izinCount }} Izin
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ count($matrixDates) + 2 }}" class="p-0 border-0">
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

        <!-- Footer: Info & Pagination -->
        @if ($matrixEmployees && $matrixEmployees->total() > 0)
            <div class="dt-footer-wrapper">
                <div class="dt-info">
                    Showing {{ $matrixEmployees->firstItem() ?? 0 }} to {{ $matrixEmployees->lastItem() ?? 0 }} of {{ $matrixEmployees->total() }} entries
                </div>
                <div class="dt-pagination">
                    {{ $matrixEmployees->links('livewire.custom-pagination') }}
                </div>
            </div>
        @endif
    </div>

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
                        @if ($editingAttendanceId)
                            <div class="bd-callout {{ $editingAttendanceSource === 'manual' ? 'bd-callout-info' : 'bd-callout-success' }} mb-3" style="padding: 12px 16px !important; margin-bottom: 16px !important; border-radius: 9px;">
                                <div class="d-flex align-items-center justify-content-between flex-nowrap" style="gap: 14px;">
                                    <div class="d-flex align-items-center text-nowrap" style="gap: 12px;">
                                        <div class="bd-callout-icon {{ $editingAttendanceSource === 'manual' ? 'text-primary' : 'text-success' }}" style="font-size: 20px;">
                                            <i class="fas {{ $editingAttendanceSource === 'manual' ? 'fa-user-edit' : 'fa-fingerprint' }}"></i>
                                        </div>
                                        <div style="line-height: 1.4;">
                                            <div class="text-nowrap" style="font-size: 13.5px; color: #1e293b;">
                                                <span style="font-weight: 500; color: #334155;">Sumber:</span>
                                                <strong class="{{ $editingAttendanceSource === 'manual' ? 'text-primary' : 'text-success' }} font-weight-600">
                                                    {{ $editingAttendanceSource === 'manual' ? 'Koreksi Manual Admin' : 'Mesin Fingerprint' }}
                                                </strong>
                                            </div>
                                            @if ($editingAttendanceRawLog)
                                                <div class="text-nowrap mt-1" style="font-size: 12px; color: #334155; font-weight: 500;">
                                                    Log Mesin Asli: <code style="color: #0b52aa; background: #eff6ff; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 5px; font-weight: 600; font-size: 12px; margin-left: 4px;">{{ $editingAttendanceRawLog }}</code>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($editingAttendanceRawLog && $editingAttendanceSource === 'manual')
                                        <button type="button" wire:click="resetToFingerprint({{ $editingAttendanceId }})" wire:loading.attr="disabled" class="btn btn-sm btn-primary text-nowrap font-weight-500 flex-shrink-0" style="background-color: #0b52aa; border-color: #0b52aa; font-size: 12px; padding: 5px 14px; border-radius: 6px; box-shadow: 0 1px 3px rgba(11, 82, 170, 0.25);" title="Kembalikan jam masuk & pulang ke rekaman mesin fingerprint asli">
                                            <i class="fas fa-undo mr-1.5"></i> Pulihkan Log Mesin
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Pilih Karyawan (Select2 Searchable dengan optgroup status kerja) -->
                        <div class="form-group mb-3">
                            <label for="manualEmployeeId" class="font-weight-normal">Karyawan <span class="text-danger">*</span></label>
                            <div wire:ignore>
                                <select id="manualEmployeeId" class="form-control select2 @error('manualEmployeeId') is-invalid @enderror" style="width: 100%;" {{ $editingAttendanceId ? 'disabled' : '' }}>
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
                            <label for="manualDate" class="font-weight-normal">Tanggal Presensi <span class="text-danger">*</span></label>
                            <input type="date" id="manualDate" wire:model="manualDate" class="form-control @error('manualDate') is-invalid @enderror" {{ $editingAttendanceId ? 'disabled' : '' }}>
                            @error('manualDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Jam Masuk & Jam Pulang -->
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group mb-3">
                                    <label for="manualClockIn" class="font-weight-normal">Jam Masuk</label>
                                    <input type="time" id="manualClockIn" wire:model="manualClockIn" class="form-control @error('manualClockIn') is-invalid @enderror">
                                    @error('manualClockIn')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-3">
                                    <label for="manualClockOut" class="font-weight-normal">Jam Pulang</label>
                                    <input type="time" id="manualClockOut" wire:model="manualClockOut" class="form-control @error('manualClockOut') is-invalid @enderror">
                                    @error('manualClockOut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Jam Lembur Masuk & Lembur Keluar -->
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group mb-3">
                                    <label for="manualOvertimeIn" class="font-weight-normal">Lembur Masuk <span class="text-muted text-xs font-weight-normal">(Opsional)</span></label>
                                    <input type="time" id="manualOvertimeIn" wire:model="manualOvertimeIn" class="form-control @error('manualOvertimeIn') is-invalid @enderror">
                                    @error('manualOvertimeIn')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-3">
                                    <label for="manualOvertimeOut" class="font-weight-normal">Lembur Keluar <span class="text-muted text-xs font-weight-normal">(Opsional)</span></label>
                                    <input type="time" id="manualOvertimeOut" wire:model="manualOvertimeOut" class="form-control @error('manualOvertimeOut') is-invalid @enderror">
                                    @error('manualOvertimeOut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Status Kehadiran -->
                        <div class="form-group mb-3">
                            <label for="manualStatus" class="font-weight-normal">Status Kehadiran <span class="text-danger">*</span></label>
                            <select id="manualStatus" wire:model="manualStatus" class="form-control @error('manualStatus') is-invalid @enderror">
                                <option value="hadir">Hadir (H)</option>
                                <option value="terlambat">Terlambat</option>
                                <option value="pulang_cepat">Pulang Cepat</option>
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

                        <!-- Catatan -->
                        <div class="form-group mb-2">
                            <label for="manualNotes" class="font-weight-normal">Catatan / Keterangan</label>
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
                        const isEditing = @this.get('editingAttendanceId') !== null;
                        $('#manualEmployeeId').val(empVal || '').trigger('change.select2');
                        $('#manualEmployeeId').prop('disabled', isEditing);
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
