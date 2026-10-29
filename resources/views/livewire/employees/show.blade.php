<div>
    <x-slot name="header">
        @if (auth()->user()->hasRole('karyawan'))
            <h1>Profil Saya</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item">Profil Saya</div>
            </div>
        @else
            <div class="section-header-back">
                <a href="{{ route('employees.index') }}" class="btn btn-icon" title="Kembali ke Daftar Karyawan">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
            <h1>Detail Profil Karyawan</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="{{ route('employees.index') }}">Karyawan</a></div>
                <div class="breadcrumb-item">{{ $employee->full_name }}</div>
            </div>
        @endif
    </x-slot>

    @if (auth()->user()->hasRole('karyawan'))
        <h2 class="section-title">Profil & Data Kepegawaian Saya</h2>
        <p class="section-lead mb-3">
            Informasi profil diri, status kepegawaian, pajak, serta rekapitulasi slip gaji resmi Anda.
        </p>
    @else
        <h2 class="section-title">Detail Profil & Kepegawaian</h2>
        <p class="section-lead mb-3">
            Informasi lengkap data karyawan, status kepegawaian, kompensasi gaji, hingga riwayat perkembangan gaji.
        </p>
    @endif

    {{-- Banner Callout Penyesuaian Gaji (.bd-callout) - Khusus Admin & Direktur --}}
    @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur'))
        @php
            $calloutClass = match ($progressionStatus['status']) {
                'stagnant' => 'bd-callout-warning',
                'recently_raised' => 'bd-callout-success',
                default => 'bd-callout-info',
            };
            $calloutIcon = match ($progressionStatus['status']) {
                'stagnant' => 'fa-exclamation-triangle',
                'recently_raised' => 'fa-check-circle',
                default => 'fa-info-circle',
            };
            $calloutTitle = match ($progressionStatus['status']) {
                'stagnant' => 'Perhatian: Karyawan Mengalami Stagnasi Gaji',
                'recently_raised' => 'Status Gaji Terkini: Penyesuaian Baru Tercatat',
                default => 'Status Riwayat & Evaluasi Gaji Karyawan',
            };
            $calloutDesc = match ($progressionStatus['status']) {
                'stagnant' => $progressionStatus['note'] . '. Pertimbangkan evaluasi berkala kompensasi untuk karyawan ini.',
                'recently_raised' => $progressionStatus['note'] . '. Gaji pokok karyawan telah diperbarui dalam sistem payroll.',
                default => $progressionStatus['note'] . '. Pantau perkembangan kompensasi dan catat penyesuaian gaji berkala karyawan bila diperlukan.',
            };
        @endphp

        <div class="bd-callout {{ $calloutClass }} mb-3" role="alert">
            <div class="callout-inner-flex">
                <div class="callout-content-group">
                    <div class="bd-callout-icon mr-3">
                        <i class="fas {{ $calloutIcon }}"></i>
                    </div>
                    <div class="callout-text-body">
                        <div class="bd-callout-title">
                            {{ $calloutTitle }}
                        </div>
                        <div class="bd-callout-desc">
                            {{ $calloutDesc }}
                        </div>
                    </div>
                </div>
                @if (auth()->user()->hasRole('admin'))
                    <div class="callout-action-wrapper">
                        <button type="button" wire:click="openAdjustmentModal" wire:loading.attr="disabled" class="btn btn-primary btn-callout-adjust">
                            <i class="fas fa-plus mr-1"></i> Sesuaikan Gaji
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="detail-page-wrapper">
        <div class="row align-items-start">
            <!-- Sisi Kiri: Profil, Info Kepegawaian, Data Pribadi, Pajak & BPJS, Riwayat Gaji (Dijabarkan Memanjang ke Bawah) -->
            <div class="col-lg-3 col-md-4 col-12 mb-3">
                <div class="card mb-3">
                    <div class="card-body p-3 p-md-4">
                        <!-- Profil Karyawan (Clean Tanpa Inner Card / Inner Border) -->
                        <div class="emp-profile-clean text-center">
                            <div class="d-flex justify-content-center mb-2">
                                <img src="{{ $employee->photo_url }}" alt="{{ $employee->full_name }}" class="emp-profile-avatar-clean">
                            </div>
                            <div class="emp-profile-name-clean">{{ $employee->full_name }}</div>
                            <div class="emp-profile-role-clean mb-2">
                                {{ $employee->department?->name ?: 'Tanpa Departemen' }} &bull; {{ $employee->position?->name ?: 'Tanpa Jabatan' }}
                            </div>
                            <div class="d-flex justify-content-center align-items-center flex-wrap" style="gap: 6px;">
                                <span class="{{ $employee->employment_status_badge_class }}">
                                    {{ $employee->employment_status_label }}
                                </span>
                                <span class="{{ $employee->status_badge_class }}">
                                    {{ $employee->status_label }}
                                </span>
                            </div>
                            @if (auth()->user()->hasRole('admin'))
                                <button type="button" wire:click="editEmployeeData" wire:loading.attr="disabled" class="btn btn-primary btn-block mt-3">
                                    <i class="fas fa-edit mr-1"></i> Edit Data
                                </button>
                            @endif
                        </div>

                        <!-- Ringkasan Cepat Info Pokok -->
                        <div class="emp-tile-group">
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-id-badge text-primary"></i></div>
                                    <span>ID / NIP</span>
                                </div>
                                <div class="emp-tile-right">
                                    {{ $employee->detail?->employee_code ?: '-' }}
                                </div>
                            </div>
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-user-clock text-info"></i></div>
                                    <span>Masa Kerja</span>
                                </div>
                                <div class="emp-tile-right">
                                    {{ $employee->tenure_label }}
                                </div>
                            </div>
                            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur') || auth()->user()->hasRole('karyawan'))
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-money-bill-wave text-success"></i></div>
                                        <span>{{ $employee->employment_status === \App\Enums\EmploymentStatus::HARIAN ? 'Upah Harian' : 'Gaji Pokok' }}</span>
                                    </div>
                                    <div class="emp-tile-right text-primary font-weight-500">
                                        {{ $employee->employment_status === \App\Enums\EmploymentStatus::HARIAN ? $employee->formatted_daily_rate . ' / hari' : $employee->formatted_basic_salary }}
                                    </div>
                                </div>
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-business-time text-info"></i></div>
                                        <span>Nominal Lembur</span>
                                    </div>
                                    <div class="emp-tile-right text-primary font-weight-500">
                                        {{ $employee->formatted_overtime_rate_per_hour }} / jam
                                        <span class="text-muted small">&bull;</span>
                                        {{ $employee->formatted_overtime_rate_per_day }} / hari
                                    </div>
                                </div>
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-utensils text-warning"></i></div>
                                        <span>Uang Makan Harian</span>
                                    </div>
                                    <div class="emp-tile-right text-primary font-weight-500">
                                        {{ $employee->formatted_meal_allowance_per_day }} / hari
                                        <span class="badge {{ $employee->meal_allowance_type === 'khusus' ? 'badge-info' : 'badge-light border' }} text-xs ml-1">
                                            {{ $employee->meal_allowance_type === 'khusus' ? 'Khusus' : 'Standar' }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur'))
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-chart-line text-warning"></i></div>
                                        <span>Status Gaji</span>
                                    </div>
                                    <div class="emp-tile-right">
                                        <span class="{{ $progressionStatus['badge_class'] }}">
                                            {{ $progressionStatus['label'] }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- 2 Tab Navigasi Segmented Control -->
                        <div class="emp-tab-nav">
                            <button type="button" 
                                    wire:click="setTab('kepegawaian')" 
                                    class="emp-tab-btn {{ in_array($activeTab, ['kepegawaian', 'pribadi']) ? 'active' : '' }}"
                                    title="Informasi Kepegawaian & Data Pribadi">
                                <i class="fas fa-user-tie mr-1"></i> Kepegawaian
                            </button>
                            <button type="button" 
                                    wire:click="setTab('riwayat-gaji')" 
                                    class="emp-tab-btn {{ in_array($activeTab, ['riwayat-gaji', 'pajak-bpjs']) ? 'active' : '' }}"
                                    title="{{ auth()->user()->hasRole('karyawan') ? 'Informasi Pajak & BPJS' : 'Informasi Pajak, BPJS & Riwayat Gaji' }}">
                                <i class="fas fa-file-invoice-dollar mr-1"></i> {{ auth()->user()->hasRole('karyawan') ? 'Pajak & BPJS' : 'Pajak & Gaji' }}
                            </button>
                        </div>

                        <!-- TAB 1: Bagian Informasi Kepegawaian dan Data Pribadi -->
                        @if (in_array($activeTab, ['kepegawaian', 'pribadi']))
                            <div class="emp-tab-pane">
                                <!-- 1. Informasi Kepegawaian & Rekening Bank -->
                                <h2 class="section-title emp-pane-first-title">Informasi Kepegawaian</h2>
                                <p class="section-lead">Rincian status kerja dan rekening bank payroll.</p>
                                <div class="emp-tile-group">
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-calendar-check text-primary"></i></div>
                                            <span>Tanggal Bergabung</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $employee->join_date ? $employee->join_date->format('d M Y') : '-' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-calendar-times text-warning"></i></div>
                                            <span>Akhir Kontrak</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $employee->contract_end_date ? $employee->contract_end_date->format('d M Y') : 'Karyawan Tetap' }}
                                        </div>
                                    </div>
                                    @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur') || auth()->user()->hasRole('karyawan'))
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-hand-holding-usd text-danger"></i></div>
                                                <span>Total Kasbon</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->formatted_total_cash_receipt }}
                                                @if ($employee->remaining_cash_advance > 0)
                                                    <span class="text-danger small d-block font-weight-normal">(Sisa: {{ $employee->formatted_remaining_cash_advance }})</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-university text-info"></i></div>
                                                <span>Nama Bank</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->bank_name ?: '-' }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-credit-card text-info"></i></div>
                                                <span>Nomor Rekening</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->bank_account_number ?: '-' }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-user-check text-info"></i></div>
                                                <span>Atas Nama</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->bank_account_name ?: '-' }}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- 2. Data Pribadi & Kontak -->
                                <h2 class="section-title">Data Pribadi & Kontak</h2>
                                <p class="section-lead">Informasi biodata diri, kontak, dan alamat domisili.</p>
                                <div class="emp-tile-group">
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-venus-mars text-primary"></i></div>
                                            <span>Jenis Kelamin</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $employee->detail?->gender_label ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-birthday-cake text-warning"></i></div>
                                            <span>Tempat & Tgl Lahir</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $employee->detail?->birth_place ?: '-' }}, {{ $employee->detail?->birth_date ? $employee->detail->birth_date->format('d M Y') : '-' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-phone text-success"></i></div>
                                            <span>No. Telepon / WhatsApp</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $employee->detail?->phone ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-envelope text-info"></i></div>
                                            <span>Alamat Email</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $employee->detail?->email ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-map-marker-alt text-danger"></i></div>
                                            <span>Alamat Domisili</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $employee->detail?->address ?: '-' }}
                                        </div>
                                    </div>
                                    @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur') || auth()->user()->hasRole('karyawan'))
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-file-pdf text-danger"></i></div>
                                                <span>Berkas CV</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                @if ($employee->detail?->curriculum_vitae)
                                                    <a href="{{ route('employees.documents.cv', $employee) }}" target="_blank" class="btn btn-sm btn-outline-primary text-nowrap"><i class="fas fa-download mr-1"></i> Unduh CV</a>
                                                @else
                                                    <span class="text-secondary font-italic">Belum diunggah</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- TAB 2: Bagian Informasi Pajak & BPJS dan Riwayat Gaji -->
                        @if (in_array($activeTab, ['riwayat-gaji', 'pajak-bpjs']))
                            <div class="emp-tab-pane">
                                <!-- 3. Informasi Pajak & BPJS -->
                                @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur') || auth()->user()->hasRole('karyawan'))
                                    <h2 class="section-title emp-pane-first-title">Informasi Pajak & BPJS</h2>
                                    <p class="section-lead">Status PTKP, metode PPh 21, dan nomor kepesertaan jaminan sosial.</p>
                                    <div class="emp-tile-group">
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-users text-primary"></i></div>
                                                <span>Status PTKP</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->ptkp_status?->value ?: 'TK/0' }} &bull; TER {{ $employee->detail?->ter_category ?: 'A' }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-calculator text-primary"></i></div>
                                                <span>Metode PPh 21</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                <span class="{{ $employee->detail?->tax_method?->badgeClass() ?: 'badge badge-primary' }}">
                                                    {{ $employee->detail?->tax_method?->shortLabel() ?: 'Gross' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-file-invoice-dollar text-primary"></i></div>
                                                <span>Jenis Pajak Pegawai</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->tax_recipient_type?->label() ?? 'Belum diklasifikasikan' }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-id-card text-primary"></i></div>
                                                <span>NIK KTP</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->nik ?: '-' }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-file-invoice text-primary"></i></div>
                                                <span>Nomor NPWP</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->npwp_number ?: ($employee->detail?->has_npwp ? 'Terdaftar' : 'Belum Ada NPWP') }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-heartbeat text-success"></i></div>
                                                <span>BPJS Kesehatan</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->bpjs_kesehatan_number ?: '-' }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row">
                                            <div class="emp-tile-left">
                                                <div class="emp-tile-icon"><i class="fas fa-shield-alt text-info"></i></div>
                                                <span>BPJS Ketenagakerjaan</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                {{ $employee->detail?->bpjs_ketenagakerjaan_number ?: '-' }}
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- 4. Riwayat Gaji & Riwayat Kenaikan - Khusus Admin & Direktur -->
                                @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur'))
                                    <h2 class="section-title">Riwayat Gaji (Riwayat Kenaikan & Penyesuaian)</h2>
                                    <p class="section-lead">Histori penyesuaian gaji berkala karyawan.</p>

                                    <!-- Ringkasan Metrik Gaji -->
                                    <div class="row salary-metrics-row">
                                        <div class="col-6">
                                            <div class="salary-metric-box text-left">
                                                <div class="metric-label">Gaji Saat Ini</div>
                                                <div class="metric-value text-primary">{{ $employee->formatted_basic_salary }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="salary-metric-box text-left">
                                                <div class="metric-label">Penyesuaian Terakhir</div>
                                                <div class="metric-value text-dark">{{ $employee->last_salary_change_date?->format('d M Y') ?: '-' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Timeline List Riwayat Kenaikan Gaji -->
                                    @if ($histories->isNotEmpty())
                                        <div class="salary-history-timeline">
                                            @foreach ($histories as $history)
                                                <div class="salary-history-item" wire:key="history-{{ $history->id }}">
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <div>
                                                            <span class="{{ $history->type_badge_class }} mr-1">{{ $history->type_label }}</span>
                                                            <span class="text-secondary text-small">{{ $history->effective_date ? $history->effective_date->format('d M Y') : '-' }}</span>
                                                        </div>
                                                        @if (auth()->user()->hasRole('admin'))
                                                            @if ($history->type !== \App\Enums\SalaryAdjustmentType::INITIAL)
                                                                <button type="button" wire:click="deleteSalaryHistory({{ $history->id }})" wire:loading.attr="disabled" wire:confirm="Apakah Anda yakin ingin menghapus catatan riwayat penyesuaian gaji ini?" class="btn btn-sm btn-outline-danger p-0 px-1.5" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    <div class="d-flex align-items-baseline justify-content-between mb-1">
                                                        <div class="font-weight-500 text-dark" style="font-size: 13.5px;">
                                                            {{ $history->formatted_new_salary }}
                                                        </div>
                                                        <div>
                                                            @if ($history->isIncrease())
                                                                <span class="badge badge-success text-nowrap" style="font-size: 11px; font-weight: 500;">
                                                                    +{{ $history->formatted_percentage_change }}
                                                                </span>
                                                            @elseif ($history->isDecrease())
                                                                <span class="badge badge-danger text-nowrap" style="font-size: 11px; font-weight: 500;">
                                                                    -{{ $history->formatted_percentage_change }}
                                                                </span>
                                                            @else
                                                                <span class="badge badge-light border text-nowrap" style="font-size: 11px; font-weight: 500;">Awal</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="text-secondary text-small d-flex justify-content-between" style="font-size: 11.5px;">
                                                        <span>{{ $history->reason ?: '-' }}</span>
                                                        <span class="text-muted font-italic">Oleh: {{ $history->changer?->name ?: 'Sistem' }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="salary-metric-box py-3 text-center">
                                            <i class="fas fa-history text-primary" style="font-size: 22px;"></i>
                                            <div class="mt-1 text-dark font-weight-500" style="font-size: 14px;">Belum Ada Riwayat Kenaikan</div>
                                            <p class="text-secondary text-small mb-0" style="font-size: 12px;">Klik tombol "Sesuaikan Gaji" di atas untuk mencatat penyesuaian gaji.</p>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sisi Kanan: Grafik Bar Gaji Bersih & Analitik Penggajian -->
            <div class="col-lg-9 col-md-8 col-12 mb-3">
                <!-- 1. Header Tren & Analisis Gaji Bersih (Section Title & Lead) -->
                <h2 class="section-title emp-pane-first-title">Tren & Analisis Gaji Bersih</h2>
                <p class="section-lead">Distribusi penerimaan gaji bersih per bulan serta rekap slip resmi tahun {{ $chartYear }}.</p>

                <!-- 2. Empat Card Statistik (UI Flutter Style - 2x2 di Mobile, 1x4 di Desktop) -->
                <div class="row flutter-kpi-row mb-3">
                    <div class="col-lg-3 col-6 mb-2">
                        <div class="flutter-stat-card card-blue">
                            <div class="stat-main-icon icon-bg-blue">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="stat-content-group">
                                <span class="stat-title">Total Diterima ({{ $chartYear }})</span>
                                <div class="stat-number text-blue">{{ $this->monthlySalaryData['formatted_total_net'] }}</div>
                                <span class="stat-caption">{{ $this->monthlySalaryData['count'] }} slip dibayarkan</span>
                            </div>
                            <div class="stat-accent-badge badge-blue">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6 mb-2">
                        <div class="flutter-stat-card card-amber">
                            <div class="stat-main-icon icon-bg-amber">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <div class="stat-content-group">
                                <span class="stat-title">Rata-Rata / Bulan</span>
                                <div class="stat-number text-amber">{{ $this->monthlySalaryData['formatted_avg_net'] }}</div>
                                <span class="stat-caption">Per bulan aktif</span>
                            </div>
                            <div class="stat-accent-badge badge-amber">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6 mb-2">
                        <div class="flutter-stat-card card-rose">
                            <div class="stat-main-icon icon-bg-rose">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content-group">
                                <span class="stat-title">Gaji Tertinggi</span>
                                <div class="stat-number text-rose">{{ $this->monthlySalaryData['formatted_max_net'] }}</div>
                                <span class="stat-caption">Bulan: {{ $this->monthlySalaryData['max_month'] }}</span>
                            </div>
                            <div class="stat-accent-badge badge-rose">
                                <i class="fas fa-award"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6 mb-2">
                        <div class="flutter-stat-card card-emerald">
                            <div class="stat-main-icon icon-bg-emerald">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="stat-content-group">
                                <span class="stat-title">Slip Gaji Terbit</span>
                                <div class="stat-number text-emerald">{{ $this->monthlySalaryData['count'] }} Slip</div>
                                <span class="stat-caption">Tahun {{ $chartYear }}</span>
                            </div>
                            <div class="stat-accent-badge badge-emerald">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Grafik Bar Penerimaan Gaji Bersih (Clean Box Tanpa Card) -->
                <div class="chart-box-clean mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="font-weight-600 text-dark" style="font-size: 14.5px;">
                            <i class="fas fa-chart-bar text-primary mr-1.5"></i> Grafik Bar Penerimaan Gaji Bersih (Jan – Des {{ $chartYear }})
                        </div>
                    </div>
                    @if ($this->monthlySalaryData['has_unapproved_items'] && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur')))
                        <div class="alert alert-light border d-flex align-items-center py-2 px-3 mb-2.5 text-muted" style="font-size: 12px; border-radius: 12px; background-color: #ffffff; border-color: #e2e8f0 !important; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
                            <i class="fas fa-info-circle text-warning mr-2" style="font-size: 14px;"></i>
                            <span>Penggajian yang masih berstatus <strong>Draf</strong> atau <strong>Menunggu Approval</strong> belum dimasukkan ke grafik penerimaan hingga disetujui Direktur.</span>
                        </div>
                    @endif
                    <div wire:ignore style="position: relative; height: 230px; width: 100%;">
                        <canvas id="salaryBarChart"></canvas>
                    </div>
                </div>

                <!-- 4. Header Rekapitulasi Slip Gaji Karyawan (Section Title & Lead) -->
                <h2 class="section-title">Rekapitulasi Slip Gaji Karyawan ({{ $chartYear }})</h2>
                <p class="section-lead">Rincian pendapatan kotor, potongan, dan nominal gaji bersih resmi.</p>

                <!-- 5. Tabel Slip Gaji (Di Dalam Card Bersih dengan Tombol Solid) -->
                <div class="card table-card-clean mb-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-clean table-hover text-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">Periode</th>
                                        <th class="text-nowrap">No. Slip</th>
                                        <th class="text-nowrap">Gaji Kotor</th>
                                        <th class="text-nowrap">Potongan</th>
                                        <th class="text-nowrap">Gaji Bersih</th>
                                        <th class="text-nowrap">Status</th>
                                        <th class="text-nowrap text-center" style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($this->monthlySalaryData['items'] as $item)
                                        <tr wire:key="slip-{{ $item->id }}">
                                            <td class="text-nowrap font-weight-500 text-dark">
                                                {{ $item->payrollPeriod?->month_name ?: '-' }} {{ $chartYear }}
                                            </td>
                                            <td class="text-nowrap">
                                                <span class="badge badge-light border text-secondary font-weight-500" style="border-radius: 6px;">
                                                    {{ $item->payslip_number ?: '-' }}
                                                </span>
                                            </td>
                                            <td class="text-nowrap text-dark">
                                                {{ $item->formatted_gross_salary }}
                                            </td>
                                            <td class="text-nowrap text-danger font-weight-500">
                                                {{ $item->formatted_total_deductions }}
                                            </td>
                                            <td class="text-nowrap font-weight-600 text-primary">
                                                {{ $item->formatted_net_salary }}
                                            </td>
                                            <td class="text-nowrap">
                                                <span class="{{ $item->payrollPeriod?->status_badge_class }}">
                                                    {{ $item->payrollPeriod?->status_label }}
                                                </span>
                                            </td>
                                            <td class="text-nowrap text-center">
                                                @if ($this->isApprovedItem($item) && $item->isPaid() && $item->payment_proof_path)
                                                    <a href="{{ route('payrolls.items.print', ['period' => $item->payroll_period_id, 'item' => $item->id]) }}" target="_blank" class="btn btn-sm btn-primary px-3 py-1 font-weight-600 shadow-sm" style="border-radius: 6px; font-size: 12px; background-color: #0b52aa; border-color: #0b52aa;" title="Buka / Cetak Slip Gaji Resmi">
                                                        <i class="fas fa-print mr-1"></i> Cetak Slip
                                                    </a>
                                                @elseif ($this->isApprovedItem($item))
                                                    <button type="button" class="btn btn-sm btn-secondary px-3 py-1 text-nowrap" disabled style="border-radius: 6px; font-size: 12px; opacity: 0.6; cursor: not-allowed;" title="Slip gaji belum dapat dicetak (menunggu pembayaran dan bukti transfer/kwitansi)">
                                                        <i class="fas fa-hourglass-half mr-1"></i> Menunggu Bayar
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-secondary px-3 py-1 text-nowrap" disabled style="border-radius: 6px; font-size: 12px; opacity: 0.6; cursor: not-allowed;" title="Slip gaji menunggu persetujuan Direktur">
                                                        <i class="fas fa-clock mr-1"></i> Menunggu ACC
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <div class="text-secondary mb-1"><i class="fas fa-file-invoice text-primary" style="font-size: 26px;"></i></div>
                                                <div class="text-dark font-weight-500">Belum Ada Slip Gaji di Tahun {{ $chartYear }}</div>
                                                <span class="text-small text-muted">Data payroll belum diproses atau karyawan belum terdaftar pada periode tahun ini.</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Sesuaikan Gaji -->
    @if (auth()->user()->hasRole('admin'))
        <div wire:ignore.self class="modal fade" id="salaryAdjustmentModal" role="dialog" aria-labelledby="salaryAdjustmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form wire:submit="saveSalaryAdjustment">
                        <div class="modal-header">
                            <h5 class="modal-title" id="salaryAdjustmentModalLabel">
                                <i class="fas fa-chart-line text-primary mr-1"></i> Sesuaikan Gaji Karyawan
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="closeAdjustmentModal"><span aria-hidden="true">&times;</span></button>
                        </div>

                        <div class="modal-body p-3 p-md-4">
                            <!-- Info Singkat Karyawan (Clean White Card) -->
                            <div class="p-2.5 d-flex align-items-center mb-3" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
                                <div class="mr-2.5">
                                    <img src="{{ $employee->photo_url }}" alt="{{ $employee->full_name }}" class="rounded-circle" style="width: 42px; height: 42px; object-fit: cover; border: 2px solid #e2e8f0;">
                                </div>
                                <div>
                                    <div class="font-weight-600 text-dark" style="font-size: 14px; line-height: 1.3;">{{ $employee->full_name }}</div>
                                    <div style="font-size: 12.5px; color: #475569; margin-top: 1px;">
                                        {{ $employee->position?->name ?: 'Tanpa Jabatan' }} &bull; Gaji Pokok Saat Ini: <span class="text-primary font-weight-600">{{ $employee->formatted_basic_salary }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Gaji Baru -->
                            <div class="form-group mb-3">
                                <label for="adj_new_salary" class="font-weight-500 text-dark">Nominal Gaji Baru (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group"
                                     x-data="{
                                         amount: @entangle('adj_new_salary'),
                                         format(val) {
                                             if (val === null || val === undefined || val === '') return '';
                                             let clean = String(val).replace(/\D/g, '');
                                             let num = parseInt(clean, 10);
                                             return isNaN(num) ? '' : new Intl.NumberFormat('id-ID').format(num);
                                         }
                                     }"
                                     x-effect="$refs.input && ($refs.input.value = format(amount))">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-500">Rp</span>
                                    </div>
                                    <input type="text"
                                           x-ref="input"
                                           id="adj_new_salary"
                                           inputmode="numeric"
                                           x-on:input="
                                               let raw = $el.value.replace(/\D/g, '');
                                               $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                               amount = raw ? parseFloat(raw) : null;
                                           "
                                           value="{{ !empty($adj_new_salary) ? number_format((float) preg_replace('/[^0-9]/', '', (string) $adj_new_salary), 0, ',', '.') : '' }}"
                                           class="form-control text-right @error('adj_new_salary') is-invalid @enderror"
                                           placeholder="0"
                                           autocomplete="off"
                                           required>
                                </div>
                                @error('adj_new_salary')
                                    <div class="text-danger text-small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tanggal Berlaku -->
                            <div class="form-group mb-3">
                                <label for="adj_effective_date" class="font-weight-500 text-dark">Tanggal Berlaku <span class="text-danger">*</span></label>
                                <input type="date" id="adj_effective_date" wire:model="adj_effective_date" class="form-control @error('adj_effective_date') is-invalid @enderror" required>
                                @error('adj_effective_date')
                                    <div class="text-danger text-small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Jenis Penyesuaian (Gaji Awal excluded) -->
                            <div class="form-group mb-3">
                                <label for="adj_type" class="font-weight-500 text-dark">Jenis Penyesuaian <span class="text-danger">*</span></label>
                                <select id="adj_type" wire:model="adj_type" class="form-control @error('adj_type') is-invalid @enderror" required>
                                    @foreach ($salaryAdjustmentTypes as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                                @error('adj_type')
                                    <div class="text-danger text-small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Alasan / Catatan -->
                            <div class="form-group mb-0">
                                <label for="adj_reason" class="font-weight-500 text-dark">Alasan / Catatan Penyesuaian</label>
                                <textarea id="adj_reason" wire:model="adj_reason" class="form-control @error('adj_reason') is-invalid @enderror" rows="3" placeholder="Contoh: Kenaikan berkala tahunan / Hasil evaluasi KPI / Penyesuaian UMR"></textarea>
                                @error('adj_reason')
                                    <div class="text-danger text-small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer bg-white br border-top">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="closeAdjustmentModal"><i class="fas fa-times mr-1"></i> Batal</button>
                            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary"><i wire:loading.remove class="fas fa-save mr-1"></i><i wire:loading class="fas fa-spinner fa-spin mr-1"></i> Simpan Penyesuaian</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let salaryChartInstance = null;

    function initSalaryChart(labels, data) {
        const canvas = document.getElementById('salaryBarChart');
        if (!canvas) return;

        if (salaryChartInstance) {
            salaryChartInstance.destroy();
        }

        salaryChartInstance = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Gaji Bersih',
                    data: data,
                    backgroundColor: 'rgba(11, 82, 170, 0.85)',
                    hoverBackgroundColor: '#0b52aa',
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                return ' Gaji Bersih: Rp ' + new Intl.NumberFormat('id-ID').format(val);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 11,
                                weight: '500'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 11
                            },
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(value % 1000000 === 0 ? 0 : 1) + ' Jt';
                                }
                                if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + ' Rb';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initSalaryChart(@json($this->monthlySalaryData['labels']), @json($this->monthlySalaryData['net_salaries']));
    });

    document.addEventListener('livewire:initialized', () => {
        initSalaryChart(@json($this->monthlySalaryData['labels']), @json($this->monthlySalaryData['net_salaries']));

        window.addEventListener('salary-chart-updated', (event) => {
            const detail = event.detail[0] || event.detail;
            if (salaryChartInstance && detail) {
                salaryChartInstance.data.labels = detail.labels;
                salaryChartInstance.data.datasets[0].data = detail.data;
                salaryChartInstance.update();
            } else if (detail) {
                initSalaryChart(detail.labels, detail.data);
            }
        });

        window.addEventListener('open-modal', (event) => {
            const detail = event.detail[0] || event.detail;
            const modalId = detail.id || detail;
            if (modalId) {
                $('#' + modalId).modal('show');
            }
        });

        window.addEventListener('open-salary-modal', () => {
            $('#salaryAdjustmentModal').modal('show');
        });

        window.addEventListener('close-salary-modal', () => {
            $('#salaryAdjustmentModal').modal('hide');
        });
    });
</script>
@endpush

<style>
    /* Section Title & Lead inside Kolom Kiri (Tabs & Card Profile) */
    .detail-page-wrapper .col-lg-3 .section-title,
    .detail-page-wrapper .emp-tab-pane .section-title {
        font-size: 14px !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        line-height: 1.35 !important;
        position: relative;
        margin-top: 14px !important;
        margin-bottom: 3px !important;
        display: block;
    }
    .detail-page-wrapper .col-lg-3 .section-title:first-of-type,
    .detail-page-wrapper .emp-tab-pane .emp-pane-first-title {
        margin-top: 10px !important;
    }
    .detail-page-wrapper .col-lg-3 .section-title:before,
    .detail-page-wrapper .emp-tab-pane .section-title:before {
        content: " ";
        border-radius: 4px;
        height: 4px !important;
        width: 14px !important;
        background-color: #0b52aa;
        display: inline-block;
        float: left;
        margin-top: 5px !important;
        margin-right: 7px !important;
    }
    .detail-page-wrapper .col-lg-3 .section-title + .section-lead,
    .detail-page-wrapper .col-lg-3 .section-lead,
    .detail-page-wrapper .emp-tab-pane .section-lead {
        margin-top: 0 !important;
        margin-left: 21px !important;
        font-size: 11.5px !important;
        color: #64748b !important;
        line-height: 1.4 !important;
        margin-bottom: 9px !important;
        font-weight: 400 !important;
        display: block;
    }

    /* Section Title & Lead di Kolom Kanan (Tren & Analisis Gaji Bersih, Rekapitulasi Slip Gaji Karyawan)
       Identik dengan Section Title & Lead Profil & Data Kepegawaian Saya di Bagian Atas */
    .detail-page-wrapper .col-lg-9 > .section-title {
        font-size: 18px !important;
        font-weight: 600 !important;
        color: #191d21 !important;
        position: relative;
        display: block;
        margin-top: 30px !important;
        margin-bottom: 5px !important;
        line-height: 1.3 !important;
    }
    .detail-page-wrapper .col-lg-9 > .emp-pane-first-title,
    .detail-page-wrapper .col-lg-9 > .section-title:first-of-type {
        margin-top: 0 !important;
    }
    .detail-page-wrapper .col-lg-9 > .section-title:before {
        content: " ";
        border-radius: 5px !important;
        height: 8px !important;
        width: 30px !important;
        background-color: #0b52aa !important;
        display: inline-block !important;
        float: left !important;
        margin-top: 6px !important;
        margin-right: 15px !important;
    }
    .detail-page-wrapper .col-lg-9 > .section-title + .section-lead,
    .detail-page-wrapper .col-lg-9 > .section-lead {
        margin-top: 0 !important;
        margin-left: 45px !important;
        font-size: 14px !important;
        color: #6c757d !important;
        line-height: 1.5 !important;
        margin-bottom: 20px !important;
        font-weight: 400 !important;
        display: block;
    }

    /* Segmented Control 2 Tab di Kolom Kiri */
    .emp-tab-nav {
        background: #f1f5f9;
        padding: 3.5px;
        border-radius: 9px;
        display: flex;
        gap: 3.5px;
        border: 1px solid #e2e8f0;
        margin-top: 13px;
        margin-bottom: 5px;
    }
    .emp-tab-btn {
        flex: 1;
        border: none;
        background: transparent;
        padding: 6px 6px;
        border-radius: 7px;
        font-size: 11.5px;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        line-height: 1.3;
    }
    .emp-tab-btn:hover {
        color: #0f172a;
    }
    .emp-tab-btn.active {
        background: #ffffff !important;
        color: #0b52aa !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
    }
    @media (max-width: 480px) {
        .emp-tab-btn {
            font-size: 11.5px;
            padding: 6px 4px;
            white-space: normal;
        }
    }

    /* Clean Profile Header (Tanpa Box / Card Border) */
    .emp-profile-clean {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 3px 0 5px 0 !important;
        margin-bottom: 12px !important;
    }
    .emp-profile-avatar-clean {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06);
    }
    .emp-profile-name-clean {
        font-size: 16.5px;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.35;
        margin-top: 7px;
        margin-bottom: 2px;
    }
    .emp-profile-role-clean {
        font-size: 12.5px;
        color: #64748b;
        font-weight: 400;
        line-height: 1.35;
    }

    /* Emp Tile Group & Row Consistency (Compact, Clean & Light Weight) */
    .detail-page-wrapper .emp-tile-group {
        border-radius: 9px !important;
        margin-bottom: 0 !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #ffffff !important;
        overflow: hidden !important;
    }
    .detail-page-wrapper .emp-tile-row {
        padding: 8px 12px !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f5f9;
    }
    .detail-page-wrapper .emp-tile-row:last-child {
        border-bottom: none !important;
    }
    .detail-page-wrapper .emp-tile-icon {
        width: 26px !important;
        height: 26px !important;
        font-size: 11px !important;
        margin-right: 9px !important;
        border-radius: 6px;
        background-color: #f1f5f9;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .detail-page-wrapper .emp-tile-left {
        font-size: 12.5px !important;
        color: #475569 !important;
        font-weight: 400 !important;
        display: flex;
        align-items: center;
    }
    .detail-page-wrapper .emp-tile-right {
        font-size: 12.8px !important;
        color: #0f172a !important;
        font-weight: 500 !important;
        text-align: right;
    }

    /* Soft metric box in Left Column */
    .salary-metrics-row {
        margin-left: -5px !important;
        margin-right: -5px !important;
        margin-bottom: 9px !important;
    }
    .salary-metrics-row > [class*="col-"] {
        padding-left: 5px !important;
        padding-right: 5px !important;
    }
    .salary-metric-box {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: 8px 11px;
        text-align: center;
        box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.02);
    }

    .metric-label {
        font-size: 10.5px;
        font-weight: 500;
        color: #64748b;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        line-height: 1.25;
        margin-bottom: 2px;
    }
    .metric-value {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.3;
    }

    /* History Timeline Item in Left Column */
    .salary-history-item {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: 9px 12px !important;
        margin-bottom: 7px !important;
        transition: all 0.15s ease;
    }
    .salary-history-item:last-child {
        margin-bottom: 0 !important;
    }
    .salary-history-item:hover {
        border-color: #cbd5e1;
    }

    /* Flutter UI Style KPI Cards in Right Column */
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

    /* Mobile Style untuk KPI Card (2x2 Grid, Iconless, Ringkas Sesuai Halaman Kasbon) */
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
            margin-bottom: 2px !important;
            line-height: 1.3 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .flutter-stat-card .stat-number {
            font-size: 16px !important;
            font-weight: 700 !important;
            line-height: 1.25 !important;
            margin: 0 0 2px 0 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .flutter-stat-card .stat-caption {
            font-size: 11px !important;
            color: #94a3b8 !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
    }

    /* Chart Box Clean (Sesuai Shadow Card Halaman Lain) */
    .chart-box-clean {
        background-color: #ffffff;
        border: 1px solid #e9edf2 !important;
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.06), 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
        margin-bottom: 24px;
    }

    /* Tabel Slip Gaji Card (Sesuai Shadow Card Halaman Lain) */
    .table-card-clean {
        background-color: #ffffff !important;
        border: 1px solid #e9edf2 !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.06), 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
        margin-bottom: 24px !important;
    }
    .table-card-clean .table th {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #475569 !important;
        letter-spacing: 0.5px !important;
        text-transform: uppercase !important;
        padding: 12px 16px !important;
        background-color: #f8fafc !important;
        border-top: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        white-space: nowrap !important;
    }
    .table-card-clean .table td {
        font-size: 13.5px !important;
        color: #1e293b !important;
        padding: 12px 16px !important;
        line-height: 1.5 !important;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9 !important;
        white-space: nowrap !important;
    }

    /* Tombol Aksi Solid (Non-Outline, Flutter Button Style) */
    .btn-solid-slip {
        background-color: #0b52aa !important;
        border: 1px solid #0b52aa !important;
        color: #ffffff !important;
        font-size: 12.5px !important;
        font-weight: 600 !important;
        padding: 6px 13px !important;
        border-radius: 6px !important;
        box-shadow: 0 1px 3px rgba(11, 82, 170, 0.25) !important;
        transition: all 0.15s ease-in-out !important;
    }
    .btn-solid-slip:hover {
        background-color: #094289 !important;
        border-color: #094289 !important;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(11, 82, 170, 0.35) !important;
    }

    .btn-solid-print {
        background-color: #f1f5f9 !important;
        border: 1px solid #e2e8f0 !important;
        color: #334155 !important;
        font-size: 12.5px !important;
        font-weight: 600 !important;
        padding: 6px 11px !important;
        border-radius: 6px !important;
        transition: all 0.15s ease-in-out !important;
    }
    .btn-solid-print:hover {
        background-color: #e2e8f0 !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
        transform: translateY(-1px);
    }

    /* Bootstrap Callout (.bd-callout) Component */
    .bd-callout {
        padding: 1rem 1.35rem !important;
        margin-top: 0;
        margin-bottom: 1.35rem !important;
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-left-width: 4px !important;
        border-radius: 9px !important;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04) !important;
        transition: all 0.2s ease-in-out !important;
    }

    .bd-callout-success {
        border-left-color: #10b981 !important;
        background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, rgba(255, 255, 255, 0.96) 18%, #ffffff 100%) !important;
    }

    .bd-callout-warning {
        border-left-color: #f59e0b !important;
        background: linear-gradient(90deg, rgba(245, 158, 11, 0.05) 0%, rgba(255, 255, 255, 0.96) 18%, #ffffff 100%) !important;
    }

    .bd-callout-info {
        border-left-color: #0b52aa !important;
        background: linear-gradient(90deg, rgba(11, 82, 170, 0.05) 0%, rgba(255, 255, 255, 0.96) 18%, #ffffff 100%) !important;
    }

    .bd-callout .bd-callout-icon {
        font-size: 20px !important;
        line-height: 1 !important;
        flex-shrink: 0 !important;
    }

    .bd-callout-success .bd-callout-icon {
        color: #10b981 !important;
    }

    .bd-callout-warning .bd-callout-icon {
        color: #f59e0b !important;
    }

    .bd-callout-info .bd-callout-icon {
        color: #0b52aa !important;
    }

    .bd-callout .bd-callout-title {
        font-size: 15px !important;
        font-weight: 700 !important;
        line-height: 1.4 !important;
        margin-bottom: 4px !important;
        letter-spacing: -0.15px !important;
    }

    .bd-callout-success .bd-callout-title {
        color: #065f46 !important;
    }

    .bd-callout-warning .bd-callout-title {
        color: #92400e !important;
    }

    .bd-callout-info .bd-callout-title {
        color: #0b52aa !important;
    }

    .bd-callout .bd-callout-desc {
        font-size: 13.5px !important;
        color: #475569 !important;
        line-height: 1.55 !important;
        margin-bottom: 0 !important;
    }

    .callout-inner-flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .callout-content-group {
        display: flex;
        align-items: flex-start;
        flex: 1 1 auto;
        min-width: 0;
    }

    .callout-action-wrapper {
        flex-shrink: 0;
        margin-left: auto;
    }

    .btn-callout-adjust {
        font-size: 13.5px !important;
        font-weight: 600 !important;
        padding: 8px 18px !important;
        border-radius: 6px !important;
        white-space: nowrap !important;
        box-shadow: 0 2px 4px rgba(11, 82, 170, 0.18) !important;
        transition: all 0.2s ease !important;
    }

    .btn-callout-adjust:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(11, 82, 170, 0.24) !important;
    }

    /* Sembunyikan batang scroll horizontal pada tabel agar tampilan clean dan rapi */
    .detail-page-wrapper .table-responsive {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }
    .detail-page-wrapper .table-responsive::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }

    @media (max-width: 767.98px) {
        .callout-inner-flex {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 10px !important;
        }
        .callout-action-wrapper {
            width: 100% !important;
            margin-left: 0 !important;
        }
        .btn-callout-adjust {
            width: 100% !important;
            display: block !important;
            text-align: center !important;
            padding: 9px 16px !important;
        }
    }
</style>
