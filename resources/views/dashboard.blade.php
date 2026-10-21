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

        <!-- Halaman Profil & Riwayat Slip Gaji Melebur Penuh ke Main Dashboard -->
        @if ($employeeDashboard['employee'])
            <livewire:employees.show :employee="$employeeDashboard['employee']" wire:key="dashboard-emp-show-{{ $employeeDashboard['employee']->id }}" />
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle mr-1"></i> Akun Anda belum terhubung dengan data profil karyawan. Silakan hubungi Administrator.
            </div>
        @endif

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