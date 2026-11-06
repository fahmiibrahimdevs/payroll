<x-app-layout>
    <x-slot name="header">
        <h1>Dashboard</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Utama</div>
        </div>
    </x-slot>

    @if (auth()->user()->hasRole('karyawan'))
        <!-- Top Row: 8 Metric Cards (Kiri 4x2) + Widget Presensi Mandiri (Kanan) | Pada Mobile Presensi Mandiri Tampil Paling Atas -->
        <div class="row align-items-start mb-4">
            <!-- Sisi Kiri (col-xl-9 col-lg-8): 8 Stat Cards dalam Grid 4x2 (Desktop order-lg-1, Mobile order-2) -->
            <div class="col-xl-9 col-lg-8 order-2 order-lg-1">
                <!-- Section Lead & Title untuk Ringkasan Karyawan -->
                <div class="dashboard-kpi-heading mb-3">
                    <h2 class="section-title mt-0 mb-1">Ringkasan Karyawan</h2>
                    <p class="section-lead mb-0">Selamat datang di portal mandiri karyawan Larashiz.</p>
                </div>

                <div class="dashboard-8kpi-grid">
                    <!-- 1. Jabatan & Unit -->
                    <div class="card cash-advance-stat-card mb-0 shadow-sm">
                        <div class="card-body cash-advance-stat-body">
                            <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="cash-advance-stat-content">
                                <div class="cash-advance-stat-title" title="Jabatan & Unit">
                                    Jabatan & Unit
                                </div>
                                <div class="cash-advance-stat-value-row">
                                    <div class="cash-advance-stat-value" title="{{ $employeeDashboard['employee']?->position?->name ?? 'Staff' }}">
                                        <span>{{ $employeeDashboard['employee']?->position?->name ?? 'Staff' }}</span>
                                    </div>
                                    <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                                        <i class="fas fa-building"></i>
                                    </span>
                                </div>
                                <div class="cash-advance-stat-subtext" title="{{ $employeeDashboard['employee']?->department?->name ?? 'Tanpa Departemen' }}">
                                    {{ $employeeDashboard['employee']?->department?->name ?? 'Tanpa Departemen' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Gaji Pokok Aktif -->
                    <div class="card cash-advance-stat-card mb-0 shadow-sm">
                        <div class="card-body cash-advance-stat-body">
                            <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="cash-advance-stat-content">
                                <div class="cash-advance-stat-title" title="Gaji Pokok Aktif">
                                    Gaji Pokok Aktif
                                </div>
                                <div class="cash-advance-stat-value-row">
                                    <div class="cash-advance-stat-value" title="{{ $employeeDashboard['employee']?->formatted_basic_salary ?? 'Rp 0' }}">
                                        <span>{{ $employeeDashboard['employee']?->formatted_basic_salary ?? 'Rp 0' }}</span>
                                    </div>
                                    <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                                        <i class="fas fa-wallet"></i>
                                    </span>
                                </div>
                                <div class="cash-advance-stat-subtext" title="{{ $employeeDashboard['employee']?->employment_status_label ?? 'Karyawan Tetap' }}">
                                    {{ $employeeDashboard['employee']?->employment_status_label ?? 'Karyawan Tetap' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Sisa Saldo Kasbon -->
                    <div class="card cash-advance-stat-card mb-0 shadow-sm">
                        <div class="card-body cash-advance-stat-body">
                            <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="cash-advance-stat-content">
                                <div class="cash-advance-stat-title" title="Sisa Saldo Kasbon">
                                    Sisa Kasbon
                                </div>
                                <div class="cash-advance-stat-value-row">
                                    <div class="cash-advance-stat-value" title="Rp {{ number_format($employeeDashboard['remaining_cash_advance'], 0, ',', '.') }}">
                                        <span>Rp {{ number_format($employeeDashboard['remaining_cash_advance'], 0, ',', '.') }}</span>
                                    </div>
                                    <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                                        <i class="fas fa-coins"></i>
                                    </span>
                                </div>
                                <div class="cash-advance-stat-subtext" title="{{ $employeeDashboard['active_cash_advance_count'] }} Pinjaman Aktif">
                                    {{ $employeeDashboard['active_cash_advance_count'] }} Pinjaman Aktif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Masa Kerja -->
                    <div class="card cash-advance-stat-card mb-0 shadow-sm">
                        <div class="card-body cash-advance-stat-body">
                            <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div class="cash-advance-stat-content">
                                <div class="cash-advance-stat-title" title="Masa Kerja">
                                    Masa Kerja
                                </div>
                                <div class="cash-advance-stat-value-row">
                                    <div class="cash-advance-stat-value" title="{{ $employeeDashboard['employee']?->tenure_label ?? '-' }}">
                                        <span>{{ $employeeDashboard['employee']?->tenure_label ?? '-' }}</span>
                                    </div>
                                    <span class="cash-advance-stat-badge cash-advance-stat-badge-purple">
                                        <i class="fas fa-award"></i>
                                    </span>
                                </div>
                                <div class="cash-advance-stat-subtext" title="Sejak {{ $employeeDashboard['employee']?->join_date?->format('d M Y') ?? '-' }}">
                                    Sejak {{ $employeeDashboard['employee']?->join_date?->format('d M Y') ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Hari Hadir Bulan Ini -->
                    <div class="card cash-advance-stat-card mb-0 shadow-sm">
                        <div class="card-body cash-advance-stat-body">
                            <div class="cash-advance-stat-icon cash-advance-stat-icon-cyan">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="cash-advance-stat-content">
                                <div class="cash-advance-stat-title" title="Hari Hadir Bulan Ini">
                                    Hadir Bulan Ini
                                </div>
                                <div class="cash-advance-stat-value-row">
                                    <div class="cash-advance-stat-value" title="{{ $employeeDashboard['monthly_attendance']['present_days'] }} Hari">
                                        <span>{{ $employeeDashboard['monthly_attendance']['present_days'] }}</span>
                                        <span class="cash-advance-stat-unit">Hari</span>
                                    </div>
                                    <span class="cash-advance-stat-badge cash-advance-stat-badge-cyan">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                </div>
                                <div class="cash-advance-stat-subtext" title="Periode {{ now()->translatedFormat('F Y') }}">
                                    Periode {{ now()->translatedFormat('F Y') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Izin & Sakit -->
                    <div class="card cash-advance-stat-card mb-0 shadow-sm">
                        <div class="card-body cash-advance-stat-body">
                            <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                                <i class="fas fa-umbrella"></i>
                            </div>
                            <div class="cash-advance-stat-content">
                                <div class="cash-advance-stat-title" title="Izin & Sakit Bulan Ini">
                                    Izin / Sakit
                                </div>
                                <div class="cash-advance-stat-value-row">
                                    <div class="cash-advance-stat-value" title="{{ $employeeDashboard['monthly_attendance']['leave_days'] }} Hari">
                                        <span>{{ $employeeDashboard['monthly_attendance']['leave_days'] }}</span>
                                        <span class="cash-advance-stat-unit">Hari</span>
                                    </div>
                                    <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                                        <i class="fas fa-clock"></i>
                                    </span>
                                </div>
                                <div class="cash-advance-stat-subtext" title="Izin, cuti, atau sakit">
                                    Izin, cuti, atau sakit
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Terlambat -->
                    <div class="card cash-advance-stat-card mb-0 shadow-sm">
                        <div class="card-body cash-advance-stat-body">
                            <div class="cash-advance-stat-icon cash-advance-stat-icon-red">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="cash-advance-stat-content">
                                <div class="cash-advance-stat-title" title="Keterlambatan Bulan Ini">
                                    Terlambat
                                </div>
                                <div class="cash-advance-stat-value-row">
                                    <div class="cash-advance-stat-value" title="{{ $employeeDashboard['monthly_attendance']['late_days'] }} Kali">
                                        <span>{{ $employeeDashboard['monthly_attendance']['late_days'] }}</span>
                                        <span class="cash-advance-stat-unit">Kali</span>
                                    </div>
                                    <span class="cash-advance-stat-badge cash-advance-stat-badge-red">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                </div>
                                <div class="cash-advance-stat-subtext" title="{{ $employeeDashboard['monthly_attendance']['late_minutes'] > 0 ? $employeeDashboard['monthly_attendance']['late_minutes'] . ' Menit keterlambatan' : 'Tepat waktu' }}">
                                    {{ $employeeDashboard['monthly_attendance']['late_minutes'] > 0 ? $employeeDashboard['monthly_attendance']['late_minutes'] . ' Menit' : 'Tepat waktu' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 8. Lembur Bulan Ini -->
                    <div class="card cash-advance-stat-card mb-0 shadow-sm">
                        <div class="card-body cash-advance-stat-body">
                            <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                                <i class="fas fa-business-time"></i>
                            </div>
                            <div class="cash-advance-stat-content">
                                <div class="cash-advance-stat-title" title="Lembur Bulan Ini">
                                    Total Lembur
                                </div>
                                <div class="cash-advance-stat-value-row">
                                    <div class="cash-advance-stat-value" title="{{ $employeeDashboard['monthly_attendance']['overtime_hours'] }} Jam">
                                        <span>{{ $employeeDashboard['monthly_attendance']['overtime_hours'] }}</span>
                                        <span class="cash-advance-stat-unit">Jam</span>
                                    </div>
                                    <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                                        <i class="fas fa-bolt"></i>
                                    </span>
                                </div>
                                <div class="cash-advance-stat-subtext" title="{{ $employeeDashboard['monthly_attendance']['overtime_days'] }} Hari lembur tercatat">
                                    {{ $employeeDashboard['monthly_attendance']['overtime_days'] }} Hari lembur
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sisi Presensi Mandiri (Desktop order-lg-2 di kanan, Mobile order-1 di paling atas) -->
            <div class="col-xl-3 col-lg-4 order-1 order-lg-2 mb-3 mb-lg-0">
                <livewire:attendances.dashboard-clock />
            </div>
        </div>

        <!-- Halaman Profil & Riwayat Slip Gaji Melebur Penuh ke Main Dashboard -->
        @if ($employeeDashboard['employee'])
            <livewire:employees.show :employee="$employeeDashboard['employee']" wire:key="dashboard-emp-show-{{ $employeeDashboard['employee']->id }}" />
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle mr-1"></i> Akun Anda belum terhubung dengan data profil karyawan. Silakan hubungi Administrator.
            </div>
        @endif

        <style>
            /* First Section Title & Lead on top (No extra top whitespace) */
            .dashboard-kpi-heading .section-title {
                margin-top: 0 !important;
                margin-bottom: 4px !important;
            }
            .dashboard-kpi-heading .section-lead {
                margin-top: 0 !important;
                margin-bottom: 12px !important;
                margin-left: 45px !important;
                font-size: 13px !important;
                color: #475569 !important;
            }

            /* Dashboard 8-KPI Grid (Identik dengan Admin Dashboard & Bebas Whitespace) */
            .dashboard-8kpi-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                grid-template-rows: auto auto;
                gap: 12px;
                height: auto;
            }
            .dashboard-8kpi-grid .card {
                border: 1px solid #eaedf2 !important;
                border-radius: 14px !important;
                background: #ffffff !important;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px 0 rgba(0, 0, 0, 0.02) !important;
                height: auto !important;
                margin: 0 !important;
                margin-bottom: 0 !important;
                transition: transform 0.15s ease, box-shadow 0.15s ease !important;
            }
            .dashboard-8kpi-grid .card:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.07) !important;
            }
            .dashboard-8kpi-grid .card-body {
                padding: 12px 14px !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                height: auto !important;
                text-align: left !important;
            }
            .dashboard-8kpi-grid .cash-advance-stat-icon {
                width: 44px !important;
                height: 44px !important;
                min-width: 44px !important;
                border-radius: 12px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 19px !important;
                color: #ffffff !important;
                flex-shrink: 0 !important;
                margin: 0 !important;
            }
            .dashboard-8kpi-grid .cash-advance-stat-content {
                margin-left: 10px !important;
                min-width: 0 !important;
                flex-grow: 1 !important;
            }
            .dashboard-8kpi-grid .cash-advance-stat-title {
                font-size: 12.5px !important;
                font-weight: 600 !important;
                color: #475569 !important;
                line-height: 1.35 !important;
                margin-bottom: 2px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            .dashboard-8kpi-grid .cash-advance-stat-value-row {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                margin: 4px 0 !important;
            }
            .dashboard-8kpi-grid .cash-advance-stat-value {
                font-size: 18.5px !important;
                font-weight: 700 !important;
                line-height: 1.2 !important;
                color: #0f172a !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            .dashboard-8kpi-grid .cash-advance-stat-unit {
                font-size: 12px !important;
                font-weight: 500 !important;
                color: #64748b !important;
                margin-left: 3px !important;
            }
            .dashboard-8kpi-grid .cash-advance-stat-badge {
                width: 20px !important;
                height: 20px !important;
                min-width: 20px !important;
                border-radius: 6px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 9.5px !important;
                margin-left: 6px !important;
                flex-shrink: 0 !important;
            }
            .dashboard-8kpi-grid .cash-advance-stat-subtext {
                font-size: 11.5px !important;
                font-weight: 500 !important;
                color: #64748b !important;
                line-height: 1.35 !important;
                margin-top: 2px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            @media (max-width: 991.98px) {
                .dashboard-8kpi-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
                    grid-template-rows: auto !important;
                    gap: 10px !important; /* Gap X dan Y konsisten 10px */
                }
                .dashboard-8kpi-grid .card {
                    margin: 0 !important;
                    margin-bottom: 0 !important;
                    border-radius: 12px !important;
                }
                .dashboard-8kpi-grid .card-body {
                    padding: 9px 8px !important;
                    min-height: auto !important;
                }
                .dashboard-8kpi-grid .cash-advance-stat-icon {
                    width: 36px !important;
                    height: 36px !important;
                    min-width: 36px !important;
                    font-size: 15px !important;
                    border-radius: 9px !important;
                }
                .dashboard-8kpi-grid .cash-advance-stat-content {
                    margin-left: 8px !important;
                }
                .dashboard-8kpi-grid .cash-advance-stat-title {
                    font-size: 11.5px !important;
                    margin-bottom: 1px !important;
                }
                .dashboard-8kpi-grid .cash-advance-stat-badge {
                    display: none !important; /* Icon kanan tidak ditampilkan pada mode mobile */
                }
                .dashboard-8kpi-grid .cash-advance-stat-value-row {
                    margin: 1px 0 !important;
                    justify-content: flex-start !important;
                }
                .dashboard-8kpi-grid .cash-advance-stat-value {
                    font-size: 14.5px !important;
                    line-height: 1.2 !important;
                }
                .dashboard-8kpi-grid .cash-advance-stat-unit {
                    font-size: 10.5px !important;
                    margin-left: 2px !important;
                }
                .dashboard-8kpi-grid .cash-advance-stat-subtext {
                    font-size: 10.5px !important;
                    margin-top: 1px !important;
                }
            }

            .cash-advance-stat-icon-blue {
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
                box-shadow: 0 4px 10px rgba(59, 130, 246, 0.28) !important;
                color: #ffffff !important;
            }
            .cash-advance-stat-icon-purple {
                background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%) !important;
                box-shadow: 0 4px 10px rgba(139, 92, 246, 0.28) !important;
                color: #ffffff !important;
            }
            .cash-advance-stat-icon-cyan {
                background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
                box-shadow: 0 4px 10px rgba(6, 182, 212, 0.28) !important;
                color: #ffffff !important;
            }
            .cash-advance-stat-icon-green {
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
                box-shadow: 0 4px 10px rgba(34, 197, 94, 0.28) !important;
                color: #ffffff !important;
            }
            .cash-advance-stat-icon-amber {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
                box-shadow: 0 4px 10px rgba(245, 158, 11, 0.28) !important;
                color: #ffffff !important;
            }
            .cash-advance-stat-icon-red {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
                box-shadow: 0 4px 10px rgba(220, 38, 38, 0.28) !important;
                color: #ffffff !important;
            }
            .cash-advance-stat-badge-blue {
                background-color: #eff6ff !important;
                color: #3b82f6 !important;
            }
            .cash-advance-stat-badge-green {
                background-color: #f0fdf4 !important;
                color: #16a34a !important;
            }
            .cash-advance-stat-badge-amber {
                background-color: #fffbeb !important;
                color: #d97706 !important;
            }
            .cash-advance-stat-badge-purple {
                background-color: #f5f3ff !important;
                color: #7c3aed !important;
            }
            .cash-advance-stat-badge-cyan {
                background-color: #ecfeff !important;
                color: #0891b2 !important;
            }
            .cash-advance-stat-badge-red {
                background-color: #fef2f2 !important;
                color: #dc2626 !important;
            }
        </style>
    @else
        <livewire:dashboard.admin-dashboard />
    @endif
</x-app-layout>