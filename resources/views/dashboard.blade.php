<x-app-layout>
    <x-slot name="header">
        <h1>Dashboard</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Utama</div>
        </div>
    </x-slot>

    @if (auth()->user()->hasRole('karyawan'))
        <!-- Section Title & Lead -->
        <h2 class="section-title">Ringkasan Karyawan</h2>
        <p class="section-lead mb-3">
            Selamat datang di portal mandiri karyawan Larashiz.
        </p>

        <!-- 4 Card Statistik (UI Flutter Style - 2x2 di Mobile, 1x4 di Desktop) -->
        <div class="row flutter-kpi-row mb-3">
            <div class="col-lg-3 col-6 mb-2">
                <div class="flutter-stat-card card-blue">
                    <div class="stat-main-icon icon-bg-blue">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-content-group">
                        <span class="stat-title">Jabatan & Unit</span>
                        <div class="stat-number text-blue" title="{{ $employeeDashboard['employee']?->position?->name ?? 'Staff' }}">
                            {{ $employeeDashboard['employee']?->position?->name ?? 'Staff' }}
                        </div>
                        <span class="stat-caption" title="{{ $employeeDashboard['employee']?->department?->name ?? 'Tanpa Departemen' }}">
                            {{ $employeeDashboard['employee']?->department?->name ?? 'Tanpa Departemen' }}
                        </span>
                    </div>
                    <div class="stat-accent-badge badge-blue">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6 mb-2">
                <div class="flutter-stat-card card-emerald">
                    <div class="stat-main-icon icon-bg-emerald">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content-group">
                        <span class="stat-title">Gaji Pokok Aktif</span>
                        <div class="stat-number text-emerald">
                            {{ $employeeDashboard['employee']?->formatted_basic_salary ?? 'Rp 0' }}
                        </div>
                        <span class="stat-caption">
                            {{ $employeeDashboard['employee']?->employment_status_label ?? 'Karyawan' }}
                        </span>
                    </div>
                    <div class="stat-accent-badge badge-emerald">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6 mb-2">
                <div class="flutter-stat-card card-amber">
                    <div class="stat-main-icon icon-bg-amber">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="stat-content-group">
                        <span class="stat-title">Sisa Saldo Kasbon</span>
                        <div class="stat-number text-amber">
                            Rp {{ number_format($employeeDashboard['remaining_cash_advance'], 0, ',', '.') }}
                        </div>
                        <span class="stat-caption">
                            {{ $employeeDashboard['active_cash_advance_count'] }} Pinjaman Aktif
                        </span>
                    </div>
                    <div class="stat-accent-badge badge-amber">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6 mb-2">
                <div class="flutter-stat-card card-rose">
                    <div class="stat-main-icon icon-bg-rose">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-content-group">
                        <span class="stat-title">Masa Kerja</span>
                        <div class="stat-number text-rose">
                            {{ $employeeDashboard['employee']?->tenure_label ?? '-' }}
                        </div>
                        <span class="stat-caption">
                            Sejak {{ $employeeDashboard['employee']?->join_date?->format('d M Y') ?? '-' }}
                        </span>
                    </div>
                    <div class="stat-accent-badge badge-rose">
                        <i class="fas fa-award"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layout Utama: 2 Kolom (Profil & Slip Terakhir vs Kasbon & Aksi Cepat) -->
        <div class="row">
            <!-- Kolom Kiri: Profil Saya & Slip Gaji Terakhir -->
            <div class="col-lg-7 col-md-12 mb-3">
                <!-- 1. Card Profil Mandiri Karyawan -->
                <div class="card mb-3 shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-body p-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #ffffff;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center">
                                @if ($employeeDashboard['employee']?->photo_path)
                                    <img src="{{ $employeeDashboard['employee']->photo_url }}" alt="Foto" width="56" height="56" class="rounded-circle mr-3 shadow" style="object-fit: cover; border: 2px solid rgba(255,255,255,0.85);">
                                @else
                                    <div class="rounded-circle mr-3 shadow d-flex align-items-center justify-content-center text-primary font-weight-bold" style="width: 56px; height: 56px; background: #ffffff; font-size: 20px;">
                                        {{ strtoupper(substr($employeeDashboard['employee']?->full_name ?? auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <h4 class="mb-1 text-white font-weight-bold" style="font-size: 19px;">
                                        {{ $employeeDashboard['employee']?->full_name ?? auth()->user()->name }}
                                    </h4>
                                    <div class="text-white-50" style="font-size: 13px;">
                                        NIK: <span class="text-white font-weight-600">{{ $employeeDashboard['employee']?->detail?->employee_code ?? '-' }}</span> &bull; {{ auth()->user()->email }}
                                    </div>
                                </div>
                            </div>
                            @if ($employeeDashboard['employee'])
                                <a href="{{ route('employees.show', $employeeDashboard['employee']->id) }}" class="btn btn-light btn-sm font-weight-bold px-3 py-2 shadow-sm text-primary" style="border-radius: 8px;">
                                    <i class="fas fa-id-card mr-1"></i> Buka Profil Lengkap
                                </a>
                            @endif
                        </div>
                        <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center" style="border-color: rgba(255,255,255,0.2) !important;">
                            <span class="badge badge-light text-primary font-weight-bold py-1 px-2 mr-2 mb-1">
                                <i class="fas fa-id-badge"></i> Role: Karyawan
                            </span>
                            <span class="badge badge-success py-1 px-2 mr-2 mb-1">
                                <i class="fas fa-check-circle"></i> Status: {{ $employeeDashboard['employee']?->status_label ?? 'Aktif' }}
                            </span>
                            <span class="badge badge-info py-1 px-2 mr-2 mb-1">
                                <i class="fas fa-building"></i> Divisi {{ $employeeDashboard['employee']?->department?->name ?? 'Umum' }}
                            </span>
                            <span class="badge badge-warning text-dark py-1 px-2 mb-1">
                                <i class="fas fa-briefcase"></i> {{ $employeeDashboard['employee']?->position?->name ?? 'Staff' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 2. Widget Slip Gaji Terakhir -->
                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #f1f5f9; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                        <h4 class="mb-0 font-weight-bold" style="font-size: 16px; color: #1e293b;">
                            <i class="fas fa-file-invoice-dollar text-success mr-2"></i> Slip Gaji Terkini
                        </h4>
                        @if ($employeeDashboard['employee'])
                            <a href="{{ route('employees.show', ['employee' => $employeeDashboard['employee']->id, 'tab' => 'riwayat-gaji']) }}" class="btn btn-outline-primary btn-sm font-weight-bold" style="border-radius: 8px;">
                                Semua Riwayat Slip <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        @endif
                    </div>
                    <div class="card-body p-4">
                        @if ($employeeDashboard['latest_payroll_item'])
                            @php
                                $slip = $employeeDashboard['latest_payroll_item'];
                            @endphp
                            <div class="d-flex align-items-center justify-content-between flex-wrap pb-3 mb-3 border-bottom">
                                <div>
                                    <div class="text-muted small">Periode Penggajian:</div>
                                    <div class="font-weight-bold text-dark" style="font-size: 15px;">
                                        {{ $slip->payrollPeriod?->name ?? 'Periode Berjalan' }}
                                    </div>
                                    <div class="text-muted small mt-1">
                                        No. Slip: <span class="font-weight-600 text-dark">{{ $slip->payslip_number }}</span>
                                    </div>
                                </div>
                                <div class="text-right mt-2 mt-sm-0">
                                    <div class="text-muted small">Take Home Pay (Gaji Bersih):</div>
                                    <div class="text-success font-weight-bold" style="font-size: 22px; letter-spacing: -0.5px;">
                                        Rp {{ number_format((float) $slip->net_salary, 0, ',', '.') }}
                                    </div>
                                    <span class="badge badge-success px-2 py-1 font-weight-bold">
                                        <i class="fas fa-check-circle mr-1"></i> Resmi Disetujui
                                    </span>
                                </div>
                            </div>

                            <!-- Mini Summary Breakdown -->
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="p-2 rounded bg-light">
                                        <div class="text-muted text-xs">Gaji Pokok</div>
                                        <div class="font-weight-bold text-dark mt-1" style="font-size: 13px;">
                                            Rp {{ number_format((float) $slip->basic_salary, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-light">
                                        <div class="text-muted text-xs">Tunjangan</div>
                                        <div class="font-weight-bold text-primary mt-1" style="font-size: 13px;">
                                            Rp {{ number_format((float) $slip->total_allowances, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-light">
                                        <div class="text-muted text-xs">Potongan</div>
                                        <div class="font-weight-bold text-danger mt-1" style="font-size: 13px;">
                                            Rp {{ number_format((float) $slip->total_deductions, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2">
                                <a href="{{ route('payrolls.items.print', ['period' => $slip->payroll_period_id, 'item' => $slip->id]) }}" target="_blank" class="btn btn-success font-weight-bold shadow-sm" style="border-radius: 8px;">
                                    <i class="fas fa-print mr-1"></i> Cetak / Unduh Slip Resmi
                                </a>
                                @if ($employeeDashboard['employee'])
                                    <a href="{{ route('employees.show', ['employee' => $employeeDashboard['employee']->id, 'tab' => 'riwayat-gaji']) }}" class="btn btn-outline-secondary font-weight-bold" style="border-radius: 8px;">
                                        <i class="fas fa-history mr-1"></i> Buka Riwayat Gaji & Slip
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-file-invoice text-slate-300 fa-3x mb-3" style="color: #cbd5e1;"></i>
                                <p class="mb-2">Belum ada slip gaji resmi yang diterbitkan untuk akun Anda saat ini.</p>
                                @if ($employeeDashboard['employee'])
                                    <a href="{{ route('employees.show', ['employee' => $employeeDashboard['employee']->id, 'tab' => 'riwayat-gaji']) }}" class="btn btn-outline-primary btn-sm font-weight-bold" style="border-radius: 8px;">
                                        Lihat Riwayat Gaji
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Kasbon Berjalan & Navigasi Cepat -->
            <div class="col-lg-5 col-md-12 mb-3">
                <!-- 1. Card Status Kasbon Saya -->
                <div class="card mb-3 shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #f1f5f9; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                        <h4 class="mb-0 font-weight-bold" style="font-size: 16px; color: #1e293b;">
                            <i class="fas fa-hand-holding-usd text-warning mr-2"></i> Kasbon Berjalan
                        </h4>
                        <a href="{{ route('cash-advances.index') }}" class="btn btn-outline-warning btn-sm font-weight-bold" style="border-radius: 8px;">
                            Buka Kasbon Saya <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="card-body p-4">
                        @if ($employeeDashboard['remaining_cash_advance'] > 0 && $employeeDashboard['active_cash_advance'])
                            @php
                                $adv = $employeeDashboard['active_cash_advance'];
                            @endphp
                            <div class="p-3 rounded mb-3" style="background: #fffbeb; border: 1px solid #fef3c7;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="badge badge-warning font-weight-bold text-dark">
                                        {{ $adv->reference_number }}
                                    </span>
                                    <span class="badge badge-primary font-weight-bold">
                                        Sedang Berjalan
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div class="text-muted small">Sisa Pinjaman Kasbon:</div>
                                    <div class="font-weight-bold text-warning" style="font-size: 20px;">
                                        Rp {{ number_format((float) $adv->remaining_amount, 0, ',', '.') }}
                                    </div>
                                    <div class="text-muted small mt-1">
                                        Total Pinjaman: <strong>Rp {{ number_format((float) $adv->amount, 0, ',', '.') }}</strong>
                                        &bull; Cicilan: <strong>Rp {{ number_format((float) $adv->monthly_installment, 0, ',', '.') }}/bln</strong>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-3 rounded mb-3 bg-light text-center">
                                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                <div class="font-weight-bold text-dark">Tidak Ada Kasbon Aktif</div>
                                <div class="text-muted small mt-1">Anda saat ini tidak memiliki tanggungan cicilan atau pinjaman berjalan.</div>
                            </div>
                        @endif

                        <a href="{{ route('cash-advances.index') }}" class="btn btn-warning btn-block font-weight-bold shadow-sm" style="border-radius: 8px; color: #1e293b;">
                            <i class="fas fa-hand-holding-usd mr-1"></i> Ajukan Kasbon / Kelola Pinjaman
                        </a>
                    </div>
                </div>

                <!-- 2. Card Aksi Cepat / Navigasi Mandiri -->
                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3" style="border-bottom: 1px solid #f1f5f9; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                        <h4 class="mb-0 font-weight-bold" style="font-size: 16px; color: #1e293b;">
                            <i class="fas fa-th-large text-primary mr-2"></i> Aksi Cepat Karyawan
                        </h4>
                    </div>
                    <div class="card-body p-3">
                        <div class="list-group list-group-flush">
                            @if ($employeeDashboard['employee'])
                                <a href="{{ route('employees.show', $employeeDashboard['employee']->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-bottom rounded mb-1">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center text-primary" style="width: 36px; height: 36px; background: #eff6ff;">
                                            <i class="fas fa-user-circle"></i>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold text-dark" style="font-size: 13.5px;">Profil & Data Saya</div>
                                            <div class="text-muted" style="font-size: 11.5px;">Informasi data pribadi, bank payroll, & BPJS</div>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted text-xs"></i>
                                </a>

                                <a href="{{ route('employees.show', ['employee' => $employeeDashboard['employee']->id, 'tab' => 'riwayat-gaji']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-bottom rounded mb-1">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center text-success" style="width: 36px; height: 36px; background: #ecfdf5;">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold text-dark" style="font-size: 13.5px;">Riwayat Gaji & Slip</div>
                                            <div class="text-muted" style="font-size: 11.5px;">Arsip slip gaji resmi & analisis penerimaan</div>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted text-xs"></i>
                                </a>
                            @endif

                            <a href="{{ route('cash-advances.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-bottom rounded mb-1">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center text-warning" style="width: 36px; height: 36px; background: #fffbeb;">
                                        <i class="fas fa-hand-holding-usd"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold text-dark" style="font-size: 13.5px;">Kasbon Saya</div>
                                        <div class="text-muted" style="font-size: 11.5px;">Permohonan pinjaman baru & riwayat cicilan</div>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-muted text-xs"></i>
                            </a>

                            <a href="{{ route('profile') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 rounded">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center text-info" style="width: 36px; height: 36px; background: #f0fdfa;">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold text-dark" style="font-size: 13.5px;">Pengaturan Akun</div>
                                        <div class="text-muted" style="font-size: 11.5px;">Ubah password & informasi kredensial login</div>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-muted text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Flutter UI Style KPI Cards */
            .flutter-kpi-row {
                margin-left: -6px;
                margin-right: -6px;
                margin-bottom: 14px;
            }
            .flutter-kpi-row > [class*="col-"] {
                padding-left: 6px;
                padding-right: 6px;
            }

            .flutter-stat-card {
                background: #ffffff;
                border: 1px solid #e9edf2 !important;
                border-radius: 12px;
                padding: 12px 14px;
                display: flex;
                align-items: center;
                position: relative;
                overflow: hidden;
                box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.06), 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
                transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
                height: 100%;
                min-height: 78px;
            }
            .flutter-stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.08), 0 2px 4px 0 rgba(0, 0, 0, 0.04) !important;
                border-color: #cbd5e1 !important;
            }

            .stat-main-icon {
                width: 44px;
                height: 44px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 17px;
                flex-shrink: 0;
                margin-right: 12px;
            }
            .icon-bg-blue {
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                box-shadow: 0 3px 8px rgba(37, 99, 235, 0.32);
            }
            .icon-bg-amber {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                box-shadow: 0 3px 8px rgba(217, 119, 6, 0.32);
            }
            .icon-bg-rose {
                background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
                box-shadow: 0 3px 8px rgba(225, 29, 72, 0.32);
            }
            .icon-bg-emerald {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                box-shadow: 0 3px 8px rgba(5, 150, 105, 0.32);
            }

            .stat-content-group {
                flex: 1 1 auto;
                min-width: 0;
                padding-right: 18px;
            }
            .stat-title {
                font-size: 12px;
                font-weight: 500;
                color: #64748b;
                line-height: 1.3;
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .stat-number {
                font-size: 17.5px;
                font-weight: 700;
                line-height: 1.3;
                margin: 2px 0 1px 0;
                letter-spacing: -0.3px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .text-blue { color: #1d4ed8 !important; }
            .text-amber { color: #d97706 !important; }
            .text-rose { color: #e11d48 !important; }
            .text-emerald { color: #059669 !important; }

            .stat-caption {
                font-size: 11.5px;
                color: #94a3b8;
                line-height: 1.25;
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .stat-accent-badge {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                width: 22px;
                height: 22px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 10.5px;
            }
            .badge-blue { background: #eff6ff; color: #3b82f6; }
            .badge-amber { background: #fffbeb; color: #f59e0b; }
            .badge-rose { background: #fff1f2; color: #f43f5e; }
            .badge-emerald { background: #ecfdf5; color: #10b981; }

            /* Mobile Style untuk KPI Card (2x2 Grid) */
            @media (max-width: 991.98px) {
                .flutter-kpi-row {
                    margin-left: -4px !important;
                    margin-right: -4px !important;
                    margin-bottom: 12px !important;
                }
                .flutter-kpi-row > [class*="col-"] {
                    padding-left: 4px !important;
                    padding-right: 4px !important;
                    margin-bottom: 8px !important;
                }
                .flutter-stat-card {
                    padding: 10px 12px !important;
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    justify-content: center !important;
                    min-height: auto !important;
                    border-radius: 12px !important;
                }
                .flutter-stat-card .stat-main-icon {
                    display: none !important;
                }
                .flutter-stat-card .stat-accent-badge {
                    display: none !important;
                }
                .flutter-stat-card .stat-content-group {
                    padding-right: 0 !important;
                    width: 100% !important;
                    min-width: 0 !important;
                }
                .flutter-stat-card .stat-title {
                    font-size: 11.5px !important;
                    font-weight: 600 !important;
                    color: #475569 !important;
                }
                .flutter-stat-card .stat-number {
                    font-size: 14.5px !important;
                    font-weight: 700 !important;
                    margin: 2px 0 !important;
                }
                .flutter-stat-card .stat-caption {
                    font-size: 10.5px !important;
                    color: #64748b !important;
                }
            }
        </style>
    @else
        <livewire:dashboard.admin-dashboard />
    @endif
</x-app-layout>