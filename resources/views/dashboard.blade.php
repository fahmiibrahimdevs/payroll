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

        <!-- Stat Cards Karyawan -->
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Jabatan & Unit</h4>
                        </div>
                        <div class="card-body" style="font-size: 17px; line-height: 1.2;">
                            {{ $employeeDashboard['employee']?->position?->name ?? 'Staff' }}
                            <div class="text-muted" style="font-size: 11px; font-weight: normal; margin-top: 2px;">
                                {{ $employeeDashboard['employee']?->department?->name ?? 'Tanpa Departemen' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Gaji Pokok Aktif</h4>
                        </div>
                        <div class="card-body" style="font-size: 17px; line-height: 1.2;">
                            {{ $employeeDashboard['employee']?->formatted_basic_salary ?? 'Rp 0' }}
                            <div class="text-muted" style="font-size: 11px; font-weight: normal; margin-top: 2px;">
                                {{ $employeeDashboard['employee']?->employment_status_label ?? 'Karyawan' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Sisa Saldo Kasbon</h4>
                        </div>
                        <div class="card-body" style="font-size: 17px; line-height: 1.2;">
                            Rp {{ number_format($employeeDashboard['remaining_cash_advance'], 0, ',', '.') }}
                            <div class="text-muted" style="font-size: 11px; font-weight: normal; margin-top: 2px;">
                                {{ $employeeDashboard['active_cash_advance_count'] }} Pinjaman Aktif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-info">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Masa Kerja</h4>
                        </div>
                        <div class="card-body" style="font-size: 17px; line-height: 1.2;">
                            {{ $employeeDashboard['employee']?->tenure_label ?? '-' }}
                            <div class="text-muted" style="font-size: 11px; font-weight: normal; margin-top: 2px;">
                                Sejak {{ $employeeDashboard['employee']?->join_date?->format('d M Y') ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Hero & Info Karyawan -->
        <div class="row">
            <div class="col-12 col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Informasi Akun Karyawan</h4>
                    </div>
                    <div class="card-body">
                        <div class="hero bg-primary text-white rounded p-4">
                            <div class="hero-inner">
                                <h2>Halo, {{ $employeeDashboard['employee']?->full_name ?? auth()->user()->name }}! 👋</h2>
                                <p class="lead">
                                    NIK / ID Karyawan: <strong>{{ $employeeDashboard['employee']?->detail?->employee_code ?? '-' }}</strong> &bull; Email: <strong>{{ auth()->user()->email }}</strong>
                                </p>
                                <div class="mt-3">
                                    <span class="badge badge-light text-primary font-weight-bold p-2 mr-2">
                                        <i class="fas fa-id-badge"></i> Role: Karyawan
                                    </span>
                                    <span class="badge badge-success p-2 mr-2">
                                        <i class="fas fa-check-circle"></i> Status: {{ $employeeDashboard['employee']?->status_label ?? 'Aktif' }}
                                    </span>
                                    <span class="badge badge-info p-2">
                                        <i class="fas fa-building"></i> Divisi {{ $employeeDashboard['employee']?->department?->name ?? 'Umum' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Aksi Cepat</h4>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @if ($employeeDashboard['employee'])
                                <a href="{{ route('employees.show', $employeeDashboard['employee']->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user-circle text-primary mr-2"></i> Profil & Data Saya
                                    </div>
                                    <i class="fas fa-chevron-right text-muted text-xs"></i>
                                </a>
                                <a href="{{ route('employees.show', ['employee' => $employeeDashboard['employee']->id, 'tab' => 'riwayat-gaji']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file-invoice-dollar text-success mr-2"></i> Riwayat Gaji & Slip
                                    </div>
                                    <i class="fas fa-chevron-right text-muted text-xs"></i>
                                </a>
                            @endif
                            <a href="{{ route('cash-advances.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-hand-holding-usd text-warning mr-2"></i> Kasbon & Pengajuan
                                </div>
                                <i class="fas fa-chevron-right text-muted text-xs"></i>
                            </a>
                            <a href="{{ route('profile') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="far fa-user text-info mr-2"></i> Pengaturan Akun
                                </div>
                                <i class="fas fa-chevron-right text-muted text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <livewire:dashboard.admin-dashboard />
    @endif
</x-app-layout>
