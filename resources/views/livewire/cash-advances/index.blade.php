<div>
    <x-slot name="header">
        <h1>Kasbon</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Keuangan & Gaji</div>
            <div class="breadcrumb-item">Kasbon</div>
        </div>
    </x-slot>

        <h2 class="section-title">Kasbon Karyawan</h2>
        <p class="section-lead">
            Kelola pengajuan pinjaman karyawan, cek sisa kasbon berjalan, dan lakukan pencatatan pelunasan kasbon.
        </p>

    <!-- 4 Modern Summary Statistic Cards (Iconless & Compact on Mobile 2x2, Rich with Stat-Icon on Desktop 1x4) -->
    <div class="cash-advance-stat-grid mb-4">
        <!-- 1. Sisa Kasbon Aktif (Gambar 2: Sisa kasbon aktif, Rp 1.300.000, 3 karyawan · 32% sudah terbayar) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Sisa Kasbon Aktif">Sisa kasbon aktif</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $companyStats['formatted_total_remaining_balance'] ?? 'Rp 0' }}">
                            {{ $companyStats['formatted_total_remaining_balance'] ?? 'Rp 0' }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-chart-line"></i>
                        </span>
                    </div>
                    <div class="progress mt-1 mb-1" style="height: 4px; background-color: #e2e8f0; border-radius: 9999px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, max(0, $companyStats['active_paid_percentage'] ?? 0)) }}%; border-radius: 9999px;"></div>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $companyStats['total_employees_with_active_advances'] ?? 0 }} karyawan &bull; {{ $companyStats['active_paid_percentage'] ?? 0 }}% sudah terbayar
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. ACC Pengajuan Kasbon -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="ACC Pengajuan Kasbon">ACC Pengajuan Kasbon</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $companyStats['total_pending_count'] ?? 0 }} Pengajuan">
                            <span>{{ $companyStats['total_pending_count'] ?? 0 }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Pengajuan</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-hourglass-half"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $companyStats['formatted_pending_amount'] ?? 'Rp 0' }} &bull;
                        @if (($companyStats['total_pending_count'] ?? 0) > 0)
                            <a href="javascript:void(0)" onclick="$('#pendingReviewModal').modal('show')" class="text-primary font-weight-bold ml-1">Tinjau</a>
                        @else
                            <span class="text-muted ml-1">Tinjau</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. ACC Pelunasan Kasbon -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-money-check-alt"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="ACC Pelunasan Kasbon">ACC Pelunasan Kasbon</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $companyStats['total_pending_payments_count'] ?? 0 }} Pelunasan">
                            <span>{{ $companyStats['total_pending_payments_count'] ?? 0 }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Pelunasan</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-clipboard-check"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $companyStats['formatted_pending_payments_amount'] ?? 'Rp 0' }} &bull;
                        @if (($companyStats['total_pending_payments_count'] ?? 0) > 0)
                            <a href="javascript:void(0)" onclick="$('#pendingPaymentReviewModal').modal('show')" class="text-primary font-weight-bold ml-1">Tinjau</a>
                        @else
                            <span class="text-muted ml-1">Tinjau</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Perlu Perhatian (Gambar 2: Perlu perhatian, 1 karyawan, Cicilan terlewat bulan lalu) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Perlu Perhatian">Perlu perhatian</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $companyStats['attention_employees_count'] ?? 0 }} Karyawan">
                            <span>{{ $companyStats['attention_employees_count'] ?? 0 }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Karyawan</span>
                        </div>
                        <span class="cash-advance-stat-badge {{ ($companyStats['attention_employees_count'] ?? 0) > 0 ? 'bg-danger text-white' : 'cash-advance-stat-badge-amber' }}">
                            <i class="fas fa-bell"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $companyStats['attention_subtitle'] ?? 'Semua cicilan lancar' }}
                        @if (($companyStats['attention_employees_count'] ?? 0) > 0)
                            &bull; <a href="javascript:void(0)" onclick="$('#overdueReviewModal').modal('show')" class="text-danger font-weight-bold ml-1">Tinjau</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>


    @if (! $selectedEmployeeId)
        <!-- ================= MODE 1: REKAP KASBON SEMUA KARYAWAN & GRAFIK (SPLIT KIRI & KANAN) ================= -->
        <div class="row">
            <!-- Kolom Kiri: 1 Card Berisi 3 Grafik Langsung Tanpa Tab (col-12 col-lg-3) -->
            <div class="col-12 col-lg-3 mb-4">
                @include('livewire.cash-advances.charts-card')
            </div>

            <!-- Kolom Kanan: Card Tabel Semua Karyawan (col-12 col-lg-9) -->
            <div class="col-12 col-lg-9 mb-4">
                <div class="card cash-advance-main-card mb-0">
                    <!-- Row 1: Header & Controls Card (on mobile) -->
                    <div class="cash-advance-top-card">
                        <div class="card-header">
                            <h4>Kasbon - <span class="text-primary font-weight-bold">Semua Karyawan</span></h4>
                        </div>

                        <!-- Controls: Show Entries & Search Bar -->
                        <div class="dt-control-wrapper">
                            <div class="dt-show-entries">
                                Show
                                <select wire:model.live="perPage" class="dt-select-entries">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                Entries
                            </div>

                            <div class="dt-search-wrapper">
                                <span class="mr-1">Search:</span>
                                <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama, departemen, jabatan...">
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Full-Width Data Table -->
                    <div class="table-responsive">
                        <table class="table table-clean mb-0">
                            <thead>
                                <tr style="background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;">
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 50px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">NO</th>
                                    <th class="text-nowrap !px-3 !py-2.5" style="color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">KARYAWAN</th>
                                    <th class="text-right text-nowrap !px-3 !py-2.5" style="color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">TOTAL PINJAMAN</th>
                                    <th class="text-right text-nowrap !px-3 !py-2.5" style="color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">SUDAH DIBAYAR</th>
                                    <th class="text-right text-nowrap !px-3 !py-2.5" style="color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">SISA KASBON</th>
                                    <th class="text-right text-nowrap !px-3 !py-2.5" style="color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">POTONGAN BULAN INI</th>
                                    <th class="text-left text-nowrap !px-3 !py-2.5" style="min-width: 130px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;">PELUNASAN</th>
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 70px; color: #1e293b; font-size: 11.5px; font-weight: 800; letter-spacing: 0.5px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($recap && $recap->total() > 0)
                                    @php
                                        $rowNumber = 0;
                                        $groupedRecap = collect($recap->items())->groupBy(fn ($item) => $item['department'] ?: 'Tanpa Departemen');
                                        if ($groupedRecap->has('Tanpa Departemen')) {
                                            $unassigned = $groupedRecap->pull('Tanpa Departemen');
                                            $groupedRecap->put('Tanpa Departemen', $unassigned);
                                        }
                                    @endphp

                                    @foreach ($groupedRecap as $departmentName => $deptRows)
                                        @php
                                            $isOfficialDept = $departmentName !== 'Tanpa Departemen';
                                        @endphp
                                        <!-- Baris Sub-Header Group Departemen (Sesuai Desain Halaman Karyawan) -->
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
                                                            <span class="font-weight-bold" style="font-size: 13px; letter-spacing: 0.3px; color: #0f172a;">
                                                                DEPARTEMEN {{ strtoupper($departmentName) }}
                                                            </span>
                                                            <span class="badge ml-2 font-weight-700" style="font-size: 11px; padding: 2.5px 8px; border-radius: 12px; background-color: {{ $isOfficialDept ? '#dbeafe' : '#fef3c7' }}; color: {{ $isOfficialDept ? '#1e40af' : '#92400e' }}; border: 1px solid {{ $isOfficialDept ? '#bfdbfe' : '#fde68a' }};">
                                                                {{ $deptRows->count() }} Karyawan
                                                            </span>
                                                            @if (! $isOfficialDept)
                                                                <span class="badge ml-1 font-weight-700" style="font-size: 10.5px; padding: 2.5px 7px; border-radius: 12px; background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                                                    Perlu Dilengkapi
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        @foreach ($deptRows as $row)
                                            @php $rowNumber++; @endphp
                                            <tr wire:key="recap-row-{{ $row['id'] }}" class="payroll-table-row" style="border-bottom: 1px solid #f1f5f9;">
                                                <td class="text-center font-weight-700 align-middle !px-3 !py-2.5 text-nowrap" style="color: #334155; font-size: 12.5px;">
                                                    {{ $recap->firstItem() + $rowNumber - 1 }}
                                                </td>
                                                <td class="align-middle text-nowrap !px-3 !py-2.5">
                                                    <div class="d-flex align-items-center text-nowrap">
                                                        <img src="{{ $row['photo_url'] }}" alt="{{ $row['name'] }}" class="rounded-circle mr-2.5 flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover; border: 1.5px solid #cbd5e1;">
                                                        <div class="text-nowrap">
                                                            <div class="font-weight-700 leading-tight" style="color: #0f172a; font-size: 13.5px;">{{ $row['name'] }}</div>
                                                            <div class="leading-tight mt-1" style="font-size: 12px; color: #334155; font-weight: 600;">
                                                                {{ $row['department'] }} &bull; {{ $row['position'] }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-right align-middle text-nowrap font-weight-600 !px-3 !py-2.5" style="color: #334155; font-size: 13px;">
                                                    {{ $row['formatted_initial_amount'] }}
                                                </td>
                                                <td class="text-right align-middle text-nowrap font-weight-600 !px-3 !py-2.5" style="color: #059669; font-size: 13px;">
                                                    {{ $row['formatted_paid_amount'] }}
                                                </td>
                                                <td class="text-right align-middle text-nowrap font-weight-700 !px-3 !py-2.5" style="color: {{ $row['remaining_amount'] > 0 ? '#d97706' : '#64748b' }}; font-size: 13px;">
                                                    {{ $row['formatted_remaining_amount'] }}
                                                </td>
                                                <td class="text-right align-middle text-nowrap font-weight-600 !px-3 !py-2.5" style="color: {{ $row['monthly_deduction'] > 0 ? '#0b52aa' : '#334155' }}; font-size: 13px;">
                                                    {{ $row['formatted_monthly_deduction'] }}
                                                </td>
                                                <!-- Kolom Pelunasan dengan Progress Bar Sesuai Gambar 2 -->
                                                <td class="text-left align-middle text-nowrap !px-3 !py-2.5" style="min-width: 130px;">
                                                    @if ($row['has_active'])
                                                        <div class="progress mb-1" style="height: 5px; background-color: #e2e8f0; border-radius: 9999px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $row['repayment_percentage'] }}%; border-radius: 9999px;" aria-valuenow="{{ $row['repayment_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="text-muted" style="font-size: 11px; font-weight: 600;">
                                                            {{ $row['repayment_text'] }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted" style="font-size: 12px;">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                                    <button type="button" wire:click="selectEmployee('{{ $row['hashid'] ?? $row['id'] }}')" wire:loading.attr="disabled" wire:target="selectEmployee('{{ $row['hashid'] ?? $row['id'] }}')" class="btn btn-primary btn-action" title="Lihat Tagihan & Buku Besar {{ $row['name'] }}">
                                                        <i wire:loading.remove wire:target="selectEmployee('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-eye"></i>
                                                        <i wire:loading wire:target="selectEmployee('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-spinner fa-spin"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" class="p-0 border-0">
                                            <x-empty-state
                                                icon="fas fa-wallet"
                                                title="Tidak Ada Data Kasbon Karyawan"
                                                :subtitle="$search ? 'Tidak ada catatan kasbon karyawan yang cocok dengan pencarian \"' . $search . '\".' : 'Belum ada data pengajuan atau saldo kasbon aktif saat ini.'"
                                                :action-label="auth()->user()?->can('create', \App\Models\CashAdvance::class) ? 'Buat Pengajuan Baru' : null"
                                                action-wire-click="createPengajuan"
                                                action-icon="fas fa-plus"
                                            />
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer: Info & Pagination -->
                    @if ($recap && $recap->total() > 0)
                        <div class="dt-footer-wrapper">
                            <div class="dt-info">
                                Showing {{ $recap->firstItem() ?? 0 }} to {{ $recap->lastItem() ?? 0 }} of {{ $recap->total() }} entries
                            </div>
                            <div class="dt-pagination">
                                {{ $recap->links('livewire.custom-pagination') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    @else
        <!-- ================= MODE 2: KARYAWAN SPESIFIK (2 COLS) ================= -->
        <div class="row align-items-start">
            <!-- Left Side: Card Avatar Profile Karyawan (Flutter-Style ListTile UI) -->
            <div class="col-lg-3 col-md-4 col-12 mb-4 mb-md-0">
                <div class="card mb-3 mb-md-0">
                    <div class="card-body p-3 text-center">
                        <!-- Avatar Circle -->
                        <div class="d-flex justify-content-center mb-2">
                            <img src="{{ $currentEmployee?->photo_url }}" alt="{{ $currentEmployee?->full_name }}" class="emp-profile-avatar-center">
                        </div>

                        <!-- Name & Role -->
                        <div class="emp-profile-name text-center" title="{{ $currentEmployee?->full_name }}">
                            {{ $currentEmployee?->full_name ?? '-' }}
                        </div>
                        <div class="emp-profile-role text-center mb-2.5">
                            {{ $currentEmployee?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $currentEmployee?->position?->name ?? 'Tanpa Jabatan' }}
                        </div>

                        <!-- Status Badges -->
                        <div class="d-flex align-items-center justify-content-center flex-wrap gap-1 mb-3">
                            @if ($currentEmployee?->employment_status_label)
                                <span class="{{ $currentEmployee->employment_status_badge_class }} text-xs">
                                    {{ $currentEmployee->employment_status_label }}
                                </span>
                            @endif
                            @if ($currentEmployee?->status_label)
                                <span class="{{ $currentEmployee->status_badge_class }} text-xs">
                                    {{ $currentEmployee->status_label }}
                                </span>
                            @endif
                        </div>

                        <!-- Operational Info: Flutter-Style ListTile Group (Sisa Kasbon, Kasbon Aktif, Telepon, Bergabung) -->
                        <div class="emp-tile-group mb-0 text-left">
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-coins text-primary"></i></div>
                                    <span>Sisa Kasbon</span>
                                </div>
                                <div class="emp-tile-right font-weight-bold text-dark">
                                    {{ $summary['formatted_total_balance'] }}
                                </div>
                            </div>
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-file-invoice-dollar text-success"></i></div>
                                    <span>Kasbon Aktif</span>
                                </div>
                                <div class="emp-tile-right font-weight-600">
                                    {{ $summary['active_advances_count'] }} Kasbon
                                </div>
                            </div>
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-phone text-muted"></i></div>
                                    <span>Telepon</span>
                                </div>
                                <div class="emp-tile-right">
                                    {{ $currentEmployee?->detail?->phone ?: '-' }}
                                </div>
                            </div>
                            @if ($currentEmployee?->join_date)
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="far fa-calendar-alt text-muted"></i></div>
                                        <span>Bergabung</span>
                                    </div>
                                    <div class="emp-tile-right">
                                        {{ $currentEmployee->join_date->translatedFormat('d M Y') }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Mini Grafik Pie Chart: Rasio Pelunasan Kasbon -->
                        <div class="cash-advance-pie-box mt-3 pt-3 border-top text-left"
                             wire:key="pie-card-{{ $currentEmployee?->id ?? 0 }}-{{ (int)($summary['total_paid'] ?? 0) }}-{{ (int)($summary['total_balance'] ?? 0) }}"
                             x-data="{
                                 chartInstance: null,
                                 initChart() {
                                     const hasData = {{ $summary['has_advances'] ? 'true' : 'false' }};
                                     if (!hasData) return;

                                     const empId = {{ (int) ($currentEmployee?->id ?? 0) }};
                                     const paid = {{ (float) ($summary['total_paid'] ?? 0) }};
                                     const remaining = {{ (float) ($summary['total_balance'] ?? 0) }};

                                     const tryRender = () => {
                                         const canvas = document.getElementById('caPieCanvas-' + empId);
                                         if (!canvas) return;

                                         if (typeof Chart === 'undefined') {
                                             setTimeout(tryRender, 100);
                                             return;
                                         }

                                         if (this.chartInstance) {
                                             try { this.chartInstance.destroy(); } catch (e) {}
                                             this.chartInstance = null;
                                         }

                                         this.chartInstance = new Chart(canvas, {
                                             type: 'pie',
                                             data: {
                                                 labels: ['Sudah Dibayar', 'Sisa Tagihan'],
                                                 datasets: [{
                                                     data: [paid, remaining],
                                                     backgroundColor: ['#10b981', '#f59e0b'],
                                                     hoverBackgroundColor: ['#059669', '#d97706'],
                                                     borderWidth: 2,
                                                     borderColor: '#ffffff',
                                                     hoverOffset: 4
                                                 }]
                                             },
                                             options: {
                                                 responsive: true,
                                                 maintainAspectRatio: false,
                                                 animation: {
                                                     duration: 400
                                                 },
                                                 plugins: {
                                                     legend: {
                                                         display: false
                                                     },
                                                     tooltip: {
                                                         backgroundColor: '#1e293b',
                                                         titleColor: '#ffffff',
                                                         bodyColor: '#ffffff',
                                                         padding: 8,
                                                         cornerRadius: 6,
                                                         callbacks: {
                                                             label: function(context) {
                                                                 const val = context.raw || 0;
                                                                 const total = paid + remaining;
                                                                 const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                                                 return ' ' + context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val) + ' (' + pct + '%)';
                                                             }
                                                         }
                                                     }
                                                 }
                                             }
                                         });
                                     };

                                     this.$nextTick(() => {
                                         tryRender();
                                     });

                                     if (typeof this.$cleanup === 'function') {
                                         this.$cleanup(() => {
                                             if (this.chartInstance) {
                                                 try { this.chartInstance.destroy(); } catch (e) {}
                                                 this.chartInstance = null;
                                             }
                                         });
                                     }
                                 }
                             }"
                             x-init="initChart()">

                            <div class="d-flex align-items-center justify-content-between mb-0">
                                <h2 class="section-title mt-0 mb-0">Rasio Pelunasan</h2>
                                @if ($summary['has_advances'])
                                    <span class="badge {{ $summary['paid_percentage'] >= 100 ? 'badge-success' : ($summary['paid_percentage'] > 0 ? 'badge-info' : 'badge-warning') }}" style="font-size: 11px; font-weight: 600; border-radius: 6px; padding: 3px 7px;">
                                        {{ $summary['paid_percentage'] }}% Lunas
                                    </span>
                                @else
                                    <span class="badge badge-light border text-muted" style="font-size: 11px; border-radius: 6px; padding: 3px 7px;">
                                        0 Kasbon
                                    </span>
                                @endif
                            </div>
                            <p class="section-lead">Perbandingan total kasbon terbayar vs sisa tagihan.</p>

                            @if ($summary['has_advances'])
                                <div wire:ignore style="position: relative; height: 160px; width: 100%; display: flex; align-items: center; justify-content: center;">
                                    <canvas id="caPieCanvas-{{ $currentEmployee?->id ?? 0 }}"></canvas>
                                </div>

                                <div class="mt-2.5 pt-2 border-top d-flex flex-column" style="gap: 5px;">
                                    <div class="d-flex align-items-center justify-content-between" style="font-size: 11.5px;">
                                        <div class="d-flex align-items-center">
                                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #10b981; display: inline-block; margin-right: 6px;"></span>
                                            <span class="text-muted">Sudah Dibayar</span>
                                        </div>
                                        <span class="font-weight-600 text-dark">{{ $summary['formatted_total_paid'] }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between" style="font-size: 11.5px;">
                                        <div class="d-flex align-items-center">
                                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #f59e0b; display: inline-block; margin-right: 6px;"></span>
                                            <span class="text-muted">Sisa Tagihan</span>
                                        </div>
                                        <span class="font-weight-600 text-dark">{{ $summary['formatted_total_balance'] }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-3 px-2 bg-white mt-2" style="border: 1px dashed #cbd5e1; border-radius: 12px;">
                                    <i class="fas fa-check-circle text-success mb-1" style="font-size: 20px;"></i>
                                    <div class="font-weight-600 text-dark" style="font-size: 12.5px;">Tidak Ada Tanggungan</div>
                                    <div class="text-muted text-xs">Karyawan ini sedang tidak memiliki kasbon aktif.</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Main Content Card (Tagihan Bulanan & Riwayat Transaksi) (9 cols on desktop) -->
            <div class="col-lg-9 col-md-8 col-12">
                <div class="card cash-advance-main-card">
                    <!-- Row 1: Header + Tabs + Controls Card (on mobile) -->
                    <div class="cash-advance-top-card">
                        <!-- Card Header: Native Template Title with Back Navigation -->
                        <div class="card-header">
                            @if (! auth()->user()?->hasRole('karyawan'))
                                <div class="section-header-back">
                                    <button type="button" 
                                            wire:click="clearSelectedEmployee" 
                                            wire:loading.attr="disabled" 
                                            wire:target="clearSelectedEmployee" 
                                            class="btn btn-icon" 
                                            title="Kembali ke Rekap Semua Karyawan">
                                        <i wire:loading.remove wire:target="clearSelectedEmployee" class="fas fa-arrow-left"></i>
                                        <i wire:loading wire:target="clearSelectedEmployee" class="fas fa-spinner fa-spin"></i>
                                    </button>
                                </div>
                            @endif
                            <h4>Rincian Kasbon</h4>
                        </div>

                        <!-- Controls: Show Entries & Search Bar (Placed above Tabs) -->
                        <div class="dt-control-wrapper">
                            <div class="dt-show-entries">
                                Show
                                <select wire:model.live="perPage" class="dt-select-entries">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                Entries
                            </div>

                            <div class="dt-search-wrapper">
                                <span class="mr-1">Search:</span>
                                <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="{{ $activeMainTab === 'statements' ? 'Cari periode, ref, keterangan...' : 'Cari ref / keterangan...' }}">
                            </div>
                        </div>

                        <!-- Native Template Tabs (.nav-pills) Placed Below Controls -->
                        <div class="cash-advance-tabs-wrapper">
                            <ul class="nav nav-pills" id="kasbonMainTabs" role="tablist">
                                <li class="nav-item">
                                    <button type="button" 
                                            class="nav-link {{ $activeMainTab === 'statements' ? 'active' : '' }} font-weight-600 border-0 text-nowrap d-flex align-items-center justify-content-center" 
                                            wire:click="switchMainTab('statements')" 
                                            wire:loading.attr="disabled" 
                                            wire:target="switchMainTab">
                                        Tagihan Bulanan
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button type="button" 
                                            class="nav-link {{ $activeMainTab === 'ledger' ? 'active' : '' }} font-weight-600 border-0 text-nowrap d-flex align-items-center justify-content-center" 
                                            wire:click="switchMainTab('ledger')" 
                                            wire:loading.attr="disabled" 
                                            wire:target="switchMainTab">
                                        Riwayat Transaksi
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card-body p-0 bg-white">
                        @if ($activeMainTab === 'statements')
                            <!-- ================= TAB 1: TAGIHAN BULANAN ================= -->
                            @php
                                $paginatedStatements = $statements['paginated_statements'] ?? null;
                            @endphp

                            <!-- 1. DESKTOP VIEW: Data Table (Hidden on Mobile < 768px) -->
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-clean mb-0">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 45px;">NO</th>
                                            <th class="text-nowrap !px-3 !py-2.5">PERIODE TAGIHAN</th>
                                            <th class="text-nowrap !px-3 !py-2.5">JATUH TEMPO</th>
                                            <th class="text-center text-nowrap !px-3 !py-2.5">JUMLAH KASBON</th>
                                            <th class="text-right text-nowrap !px-3 !py-2.5">TOTAL TAGIHAN</th>
                                            <th class="text-right text-nowrap !px-3 !py-2.5">SUDAH DIBAYAR</th>
                                            <th class="text-right text-nowrap !px-3 !py-2.5">SISA TAGIHAN</th>
                                            <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 110px;">STATUS</th>
                                            <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 70px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($paginatedStatements && $paginatedStatements->total() > 0)
                                            @foreach ($paginatedStatements as $index => $st)
                                                @php
                                                    $isOverdue = ($st['is_overdue'] ?? false) || (! $st['is_paid'] && \Carbon\Carbon::parse($st['due_date'])->startOfDay()->isBefore(today()));
                                                @endphp
                                                <tr wire:key="statement-row-{{ $st['month_key'] }}" class="{{ $isOverdue ? 'table-row-overdue' : '' }}" style="{{ $isOverdue ? 'background-color: #fef2f2;' : '' }}">
                                                    <td class="text-center font-weight-600 text-muted align-middle !px-3 !py-2.5">
                                                        {{ $paginatedStatements->firstItem() + $index }}
                                                    </td>
                                                    <td class="align-middle text-nowrap !px-3 !py-2.5">
                                                        <div class="font-weight-600 {{ $isOverdue ? 'text-danger' : 'text-dark' }} leading-tight">
                                                            Tagihan {{ $st['month_name_full'] }}
                                                        </div>
                                                    </td>
                                                    <td class="align-middle text-nowrap !px-3 !py-2.5 font-weight-500 {{ $isOverdue ? 'text-danger' : 'text-dark' }}">
                                                        {{ $st['formatted_due_date'] }}
                                                    </td>
                                                    <td class="text-center align-middle text-nowrap !px-3 !py-2.5 text-muted font-weight-500">
                                                        {{ $st['items_count'] }} Transaksi
                                                    </td>
                                                    <td class="text-right align-middle text-nowrap font-weight-500 text-dark !px-3 !py-2.5">
                                                        {{ $st['formatted_total_amount'] }}
                                                    </td>
                                                    <td class="text-right align-middle text-nowrap font-weight-500 text-dark !px-3 !py-2.5">
                                                        {{ $st['formatted_paid_amount'] }}
                                                    </td>
                                                    <td class="text-right align-middle text-nowrap font-weight-bold {{ $isOverdue ? 'text-danger' : 'text-dark' }} !px-3 !py-2.5">
                                                        {{ $st['formatted_remaining_amount'] }}
                                                    </td>
                                                    <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                                        @if ($st['is_paid'])
                                                            <span class="badge badge-success font-weight-bold" style="background-color: #d1fae5; color: #065f46; font-size: 11px; padding: 4px 8px;">
                                                                Lunas
                                                            </span>
                                                        @elseif ($isOverdue)
                                                            <span class="badge font-weight-bold" style="background-color: #fee2e2; color: #991b1b; font-size: 11px; padding: 4px 8px;">
                                                                Belum Lunas
                                                            </span>
                                                        @else
                                                            <span class="badge badge-warning font-weight-bold" style="background-color: #fef3c7; color: #92400e; font-size: 11px; padding: 4px 8px;">
                                                                Belum Lunas
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                                        <button type="button" wire:click="showStatementDetail('{{ $st['month_key'] }}')" wire:loading.attr="disabled" wire:target="showStatementDetail('{{ $st['month_key'] }}')" class="btn btn-primary btn-action" title="Lihat Rincian Tagihan Bulanan">
                                                            <i wire:loading.remove wire:target="showStatementDetail('{{ $st['month_key'] }}')" class="fas fa-eye"></i>
                                                            <i wire:loading wire:target="showStatementDetail('{{ $st['month_key'] }}')" class="fas fa-spinner fa-spin"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="9" class="text-center py-5 text-muted">
                                                    <div class="empty-state py-4">
                                                        <div class="empty-state-icon bg-light text-muted mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                                            <i class="fas fa-file-invoice-dollar"></i>
                                                        </div>
                                                        <h6 class="text-dark mb-1 font-weight-bold">
                                                            @if ($search)
                                                                Tidak Ada Tagihan yang Cocok
                                                            @else
                                                                Belum Ada Tagihan Bulanan
                                                            @endif
                                                        </h6>
                                                        <p class="text-muted small mb-0">
                                                            @if ($search)
                                                                Tidak ditemukan tagihan yang sesuai dengan pencarian "{{ $search }}".
                                                            @else
                                                                Karyawan ini belum memiliki jadwal tagihan atau cicilan kasbon berjalan.
                                                            @endif
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- 2. MOBILE VIEW: Compact List Card with Thick Left Accent Border (Hidden on Desktop >= 768px) -->
                            <div class="d-md-none">
                                @if ($paginatedStatements && $paginatedStatements->total() > 0)
                                    <div class="statement-mobile-list">
                                        @foreach ($paginatedStatements as $index => $st)
                                            @php
                                                $isOverdue = ($st['is_overdue'] ?? false) || (! $st['is_paid'] && \Carbon\Carbon::parse($st['due_date'])->startOfDay()->isBefore(today()));
                                            @endphp
                                            <div class="card statement-mobile-card {{ $st['is_paid'] ? 'statement-card-paid !border-l-[#10b981]' : ($isOverdue ? '!border-l-[#ef4444] !bg-[#fef2f2]' : 'statement-card-unpaid !border-l-[#f59e0b]') }}" wire:key="statement-card-{{ $st['month_key'] }}">
                                                <div class="card-body">
                                                    <!-- Top Row: Title (Left) & Detail Button (Right) -->
                                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                                        <div class="d-flex align-items-center gap-1.5 min-w-0 pr-2">
                                                            <span class="font-bold {{ $isOverdue ? 'text-red-700' : 'text-slate-800' }} text-[13.5px] leading-tight truncate">
                                                                Tagihan {{ $st['month_name_full'] }}
                                                            </span>
                                                            @if ($isOverdue)
                                                                <span class="badge font-weight-bold shrink-0" style="background-color: #fee2e2; color: #991b1b; font-size: 10px; padding: 2px 6px;">
                                                                    Terlewat
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <button type="button" 
                                                                wire:click="showStatementDetail('{{ $st['month_key'] }}')" 
                                                                wire:loading.attr="disabled" 
                                                                wire:target="showStatementDetail('{{ $st['month_key'] }}')" 
                                                                class="btn btn-outline-primary btn-sm font-weight-600 px-2.5 py-1 text-[11.5px] shrink-0 d-inline-flex align-items-center" 
                                                                style="border-radius: 6px; line-height: 1.2;">
                                                            <i wire:loading.remove wire:target="showStatementDetail('{{ $st['month_key'] }}')" class="fas fa-eye mr-1"></i>
                                                            <i wire:loading wire:target="showStatementDetail('{{ $st['month_key'] }}')" class="fas fa-spinner fa-spin mr-1"></i>
                                                            Detail
                                                        </button>
                                                    </div>

                                                    <!-- Meta Subtext: Jatuh Tempo & Jumlah Kasbon -->
                                                    <div class="text-[12px] text-slate-500 mb-2.5 d-flex align-items-center flex-wrap gap-x-2">
                                                        <span><i class="far fa-calendar-alt mr-1 text-slate-400"></i>{{ $st['formatted_due_date'] }}</span>
                                                        <span class="text-slate-300">&bull;</span>
                                                        <span><i class="fas fa-file-invoice-dollar mr-1 text-slate-400"></i>{{ $st['items_count'] }} Transaksi</span>
                                                    </div>

                                                    <!-- 3-Column Compact Nominal Bar -->
                                                    <div class="bg-slate-50 border border-slate-200/70 rounded-[8px] py-2 px-1 grid grid-cols-3 text-center">
                                                        <div class="px-1">
                                                            <div class="text-[11px] text-slate-500 font-medium leading-normal">Total Tagihan</div>
                                                            <div class="text-[12.5px] font-bold text-slate-800 leading-tight mt-0.5">{{ $st['formatted_total_amount'] }}</div>
                                                        </div>
                                                        <div class="px-1 border-x border-slate-200/80">
                                                            <div class="text-[11px] text-slate-500 font-medium leading-normal">Sudah Dibayar</div>
                                                            <div class="text-[12.5px] font-bold text-emerald-600 leading-tight mt-0.5">{{ $st['formatted_paid_amount'] }}</div>
                                                        </div>
                                                        <div class="px-1">
                                                            <div class="text-[11px] text-slate-500 font-medium leading-normal">Sisa Tagihan</div>
                                                            <div class="text-[12.5px] font-bold {{ $st['is_paid'] ? 'text-emerald-600' : 'text-rose-600' }} leading-tight mt-0.5">{{ $st['formatted_remaining_amount'] }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="card p-4 text-center text-muted mb-3">
                                        <div class="empty-state py-2">
                                            <div class="empty-state-icon bg-light text-muted mx-auto mb-2" style="width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </div>
                                            <h6 class="text-dark mb-1 font-weight-bold text-sm">
                                                @if ($search)
                                                    Tidak Ada Tagihan yang Cocok
                                                @else
                                                    Belum Ada Tagihan Bulanan
                                                @endif
                                            </h6>
                                            <p class="text-muted text-xs mb-0">
                                                @if ($search)
                                                    Tidak ditemukan tagihan yang sesuai dengan pencarian "{{ $search }}".
                                                @else
                                                    Karyawan ini belum memiliki jadwal tagihan atau cicilan kasbon berjalan.
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Footer: Info & Pagination -->
                            @if (!empty($paginatedStatements) && $paginatedStatements->total() > 0)
                                <div class="dt-footer-wrapper" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                    <div class="dt-info">
                                        Showing {{ $paginatedStatements->firstItem() ?? 0 }} to {{ $paginatedStatements->lastItem() ?? 0 }} of {{ $paginatedStatements->total() }} entries
                                    </div>
                                    <div class="dt-pagination">
                                        {{ $paginatedStatements->links('livewire.custom-pagination') }}
                                    </div>
                                </div>
                            @endif
                        @else
                            <!-- ================= TAB 2: RIWAYAT TRANSAKSI (BUKU BESAR) ================= -->
                            <!-- Data Table -->
                            <div class="table-responsive">
                                <table class="table table-clean mb-0">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 45px;">NO</th>
                                            <th class="text-nowrap !px-3 !py-2.5">TANGGAL / DIBUAT OLEH</th>
                                            <th class="text-nowrap !px-3 !py-2.5">TRANSAKSI & KETERANGAN</th>
                                            <th class="text-right text-nowrap !px-3 !py-2.5">PENGAJUAN</th>
                                            <th class="text-right text-nowrap !px-3 !py-2.5">PELUNASAN</th>
                                            <th class="text-right text-nowrap !px-3 !py-2.5">SALDO AKHIR</th>
                                            <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 125px;">STATUS</th>
                                            <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 70px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($ledger && $ledger->total() > 0)
                                            @foreach ($ledger as $index => $row)
                                                @php
                                                    $isRowActive = ($row['type'] === 'payment' && (int) $highlightPaymentId === (int) $row['id'])
                                                        || ($row['type'] === 'advance' && ! $highlightPaymentId && (int) $selectedCashAdvanceId === (int) $row['id']);
                                                @endphp
                                                <tr wire:key="ledger-row-{{ $row['type'] }}-{{ $row['id'] }}" id="ledger-row-{{ $row['type'] }}-{{ $row['id'] }}" class="{{ $isRowActive ? 'table-row-active-highlight font-weight-500 !bg-blue-50/60 transition-colors' : '' }}">
                                                    <td class="text-center font-weight-600 text-muted align-middle !px-3 !py-2.5">
                                                        {{ $ledger->firstItem() + $index }}
                                                    </td>
                                                    <td class="align-middle text-nowrap !px-3 !py-2.5">
                                                        <div class="font-weight-500 text-dark leading-tight">{{ $row['formatted_date'] }}</div>
                                                        <div class="text-[11px] text-muted leading-tight mt-0.5"><i class="far fa-user mr-1 text-[10px]"></i>{{ $row['creator_name'] }}</div>
                                                    </td>
                                                    <td class="align-middle !px-3 !py-2.5">
                                                        <div class="font-weight-bold text-dark leading-tight text-nowrap">{{ $row['reference_number'] }}</div>
                                                        @if (!empty($row['description']))
                                                            <div class="text-[11.5px] text-slate-500 truncate max-w-[200px] lg:max-w-[240px] leading-tight mt-0.5" title="{{ $row['description'] }}">
                                                                {{ $row['description'] }}
                                                            </div>
                                                        @else
                                                            <div class="text-[11px] text-muted-light italic leading-tight mt-0.5">-</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-right align-middle text-nowrap font-weight-500 !px-3 !py-2.5">
                                                        {{ $row['formatted_advance_amount'] }}
                                                    </td>
                                                    <td class="text-right align-middle text-nowrap font-weight-500 !px-3 !py-2.5">
                                                        {{ $row['formatted_payment_amount'] }}
                                                    </td>
                                                    <td class="text-right align-middle text-nowrap font-weight-bold text-dark !px-3 !py-2.5">
                                                        {{ $row['formatted_saldo_akhir'] }}
                                                    </td>
                                                    <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                                        <span class="{{ $row['status_badge'] }}">
                                                            {{ $row['status_label'] }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                                        @if ($row['type'] === 'advance')
                                                            @if ($row['status'] === 'pending')
                                                                @can('approve', $row['model'])
                                                                    <button type="button" wire:click="openApproval('{{ $row['hashid'] ?? $row['id'] }}')" wire:loading.attr="disabled" wire:target="openApproval('{{ $row['hashid'] ?? $row['id'] }}')" class="btn btn-primary btn-action mr-1" title="Persetujuan Direktur">
                                                                        <i wire:loading.remove wire:target="openApproval('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-clipboard-check"></i>
                                                                        <i wire:loading wire:target="openApproval('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-spinner fa-spin"></i>
                                                                    </button>
                                                                @endcan
                                                                @can('update', $row['model'])
                                                                    <button type="button" wire:click="edit('{{ $row['hashid'] ?? $row['id'] }}')" wire:loading.attr="disabled" wire:target="edit('{{ $row['hashid'] ?? $row['id'] }}')" class="btn btn-warning btn-action mr-1" title="Edit Data">
                                                                        <i wire:loading.remove wire:target="edit('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-pencil-alt"></i>
                                                                        <i wire:loading wire:target="edit('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-spinner fa-spin"></i>
                                                                    </button>
                                                                @endcan
                                                                @can('delete', $row['model'])
                                                                    <button type="button" wire:click="confirmDelete('{{ $row['hashid'] ?? $row['id'] }}')" wire:loading.attr="disabled" wire:target="confirmDelete('{{ $row['hashid'] ?? $row['id'] }}')" class="btn btn-danger btn-action" title="Hapus Pengajuan">
                                                                        <i wire:loading.remove wire:target="confirmDelete('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-trash"></i>
                                                                        <i wire:loading wire:target="confirmDelete('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-spinner fa-spin"></i>
                                                                    </button>
                                                                @endcan
                                                            @else
                                                                <button type="button" wire:click="showDetail('{{ $row['hashid'] ?? $row['id'] }}')" wire:loading.attr="disabled" wire:target="showDetail('{{ $row['hashid'] ?? $row['id'] }}')" class="btn btn-primary btn-action" title="Lihat Rincian Kasbon">
                                                                    <i wire:loading.remove wire:target="showDetail('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-eye"></i>
                                                                    <i wire:loading wire:target="showDetail('{{ $row['hashid'] ?? $row['id'] }}')" class="fas fa-spinner fa-spin"></i>
                                                                </button>
                                                            @endif
                                                        @else
                                                            @if (!empty($row['model']->proof_document))
                                                                <a href="{{ $row['model']->proof_document_url }}" target="_blank" class="btn btn-secondary btn-action mr-1" title="Lihat Bukti Transfer">
                                                                    <i class="fas fa-file-image"></i>
                                                                </a>
                                                            @endif
                                                            @if ($row['status'] === 'pending' && auth()->user()?->hasRole('direktur'))
                                                                <button type="button" onclick="confirmApprovePayment({{ $row['id'] }})" class="btn btn-success btn-action mr-1" title="Setujui Pembayaran (ACC)">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button type="button" onclick="confirmRejectPayment({{ $row['id'] }})" class="btn btn-danger btn-action mr-1" title="Tolak Pembayaran">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            @endif
                                                            <button type="button" wire:click="showDetail('{{ $row['cash_advance_hashid'] ?? $row['cash_advance_id'] }}', {{ $row['id'] }})" wire:loading.attr="disabled" wire:target="showDetail('{{ $row['cash_advance_hashid'] ?? $row['cash_advance_id'] }}', {{ $row['id'] }})" class="btn btn-info btn-action" title="Lihat Transaksi Pelunasan ({{ $row['reference_number'] }})">
                                                                <i wire:loading.remove wire:target="showDetail('{{ $row['cash_advance_hashid'] ?? $row['cash_advance_id'] }}', {{ $row['id'] }})" class="fas fa-receipt"></i>
                                                                <i wire:loading wire:target="showDetail('{{ $row['cash_advance_hashid'] ?? $row['cash_advance_id'] }}', {{ $row['id'] }})" class="fas fa-spinner fa-spin"></i>
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">
                                                    <div class="empty-state py-4">
                                                        <div class="empty-state-icon bg-light text-muted mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                                            <i class="fas fa-receipt"></i>
                                                        </div>
                                                        <h5 class="font-weight-bold text-dark mb-1">Belum Ada Riwayat Kasbon</h5>
                                                        <p class="text-muted small mb-3">
                                                            @if ($search)
                                                                Tidak ada transaksi kasbon yang sesuai dengan pencarian "{{ $search }}".
                                                            @else
                                                                Belum ada riwayat pengajuan maupun pelunasan kasbon untuk karyawan ini.
                                                            @endif
                                                        </p>
                                                        @can('create', \App\Models\CashAdvance::class)
                                                            <button type="button" wire:click="createPengajuan" wire:loading.attr="disabled" wire:target="createPengajuan" class="btn btn-primary btn-sm font-weight-bold">
                                                                <i wire:loading.remove wire:target="createPengajuan" class="fas fa-plus mr-1"></i>
                                                                <i wire:loading wire:target="createPengajuan" class="fas fa-spinner fa-spin mr-1"></i>
                                                                Buat Pengajuan Baru
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Footer: Info & Pagination -->
                            @if ($ledger && $ledger->total() > 0)
                                <div class="dt-footer-wrapper" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                    <div class="dt-info">
                                        Showing {{ $ledger->firstItem() ?? 0 }} to {{ $ledger->lastItem() ?? 0 }} of {{ $ledger->total() }} entries
                                    </div>
                                    <div class="dt-pagination">
                                        {{ $ledger->links('livewire.custom-pagination') }}
                                    </div>
                                </div>
                            @endif
                        @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Floating Action Button (Pengajuan Kasbon) -->
    @can('create', \App\Models\CashAdvance::class)
        <button type="button" wire:click="createPengajuan" wire:loading.attr="disabled" wire:target="create, createPengajuan" class="btn-floating-add" title="Buat Pengajuan Kasbon" aria-label="Buat Pengajuan Kasbon"><i wire:loading wire:target="create, createPengajuan" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="create, createPengajuan" class="fas fa-plus"></i></button>
    @endcan

    <!-- Modals -->
    @include('livewire.cash-advances.form-modal')
    @include('livewire.cash-advances.detail-modal')
    @include('livewire.cash-advances.approval-modal')
    @include('livewire.cash-advances.payment-modal')
    @include('livewire.cash-advances.statement-modal')
    @include('livewire.cash-advances.early-payment-modal')
    @include('livewire.cash-advances.pending-review-modal')
    @include('livewire.cash-advances.overdue-review-modal')
    @include('livewire.cash-advances.pending-payment-review-modal')
</div>

@push('styles')
<style>
    /* Section Title & Lead inside Cash Advance Pie Box */
    .cash-advance-pie-box .section-title {
        font-size: 13.5px !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        line-height: 1.35 !important;
        position: relative;
        margin: 0 !important;
        display: inline-flex;
        align-items: center;
    }
    .cash-advance-pie-box .section-title:before {
        content: " ";
        border-radius: 4px;
        height: 4px !important;
        width: 14px !important;
        background-color: #0b52aa;
        display: inline-block;
        float: none !important;
        margin-top: 0 !important;
        margin-right: 7px !important;
        flex-shrink: 0;
    }
    .cash-advance-pie-box .section-lead {
        margin-top: 2px !important;
        margin-left: 21px !important;
        font-size: 11px !important;
        color: #64748b !important;
        line-height: 1.35 !important;
        margin-bottom: 10px !important;
        font-weight: 400 !important;
        display: block;
    }

    /* Table Group Header & Row Hover Styling */
    .table.table-clean tbody tr.table-group-header td {
        vertical-align: middle !important;
    }
    .table.table-clean tbody tr.payroll-table-row:hover td {
        background-color: #f8fafc !important;
    }

    /* Overdue Installment Row Styling (Soft Red) */
    .table.table-clean tbody tr.table-row-overdue td {
        background-color: #fef2f2 !important;
    }
    .table.table-clean tbody tr.table-row-overdue:hover td {
        background-color: #fee2e2 !important;
    }

    /* Active Highlighted Ledger Row */
    .table.table-clean tbody tr.table-row-active-highlight td {
        background-color: #eff6ff !important;
        border-top: 1px solid #bfdbfe !important;
        border-bottom: 1px solid #bfdbfe !important;
    }
    .table.table-clean tbody tr.table-row-active-highlight:hover td {
        background-color: #dbeafe !important;
    }

    @keyframes rowHighlightPulse {
        0% { background-color: #bfdbfe; }
        50% { background-color: #dbeafe; }
        100% { background-color: #eff6ff; }
    }
    .table.table-clean tbody tr.table-row-highlight-pulse td {
        animation: rowHighlightPulse 1.2s ease-in-out;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        function initCashAdvanceScripts() {
            // Izinkan focus Select2 di dalam modal Bootstrap
            if ($.fn.modal && $.fn.modal.Constructor) {
                $.fn.modal.Constructor.prototype._enforceFocus = function () {};
            }

            const initSelect2 = () => {
                // Info Sidebar: Select Karyawan
                if ($('#main_employee_selector').length) {
                    $('#main_employee_selector').select2({
                        width: '100%',
                        placeholder: '-- Semua Karyawan --',
                        allowClear: true
                    }).off('change').on('change', function () {
                        const val = $(this).val();
                        @this.set('selectedEmployeeId', val ? parseInt(val) : null);
                    });
                }

                // Modal Form: Select Karyawan
                if ($('#form_employee_id').length) {
                    $('#form_employee_id').select2({
                        dropdownParent: $('#cashAdvanceModal'),
                        width: '100%',
                        placeholder: '-- Pilih Karyawan --',
                        allowClear: true
                    }).off('change').on('change', function () {
                        const val = $(this).val();
                        @this.set('form.employee_id', val ? parseInt(val) : null);
                    });
                }

                // Modal Form: Skema Pelunasan
                if ($('#form_repayment_scheme').length) {
                    $('#form_repayment_scheme').select2({
                        dropdownParent: $('#cashAdvanceModal'),
                        width: '100%',
                        placeholder: '-- Pilih Skema Pelunasan --'
                    }).off('change').on('change', function () {
                        const val = $(this).val();
                        @this.set('form.repayment_scheme', val || 'salary_installment');
                    });
                }

                // Modal Payment: Metode Pembayaran
                if ($('#pay_method').length) {
                    $('#pay_method').select2({
                        dropdownParent: $('#cashAdvancePaymentModal'),
                        width: '100%',
                        placeholder: '-- Pilih Metode --'
                    }).off('change').on('change', function () {
                        const val = $(this).val();
                        @this.set('paymentForm.payment_method', val || 'cash');
                    });
                }
            };

            initSelect2();

            Livewire.on('employee-selected', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const empId = typeof data === 'object' && data !== null ? data.id : data;
                $('#main_employee_selector').val(empId || '').trigger('change.select2');
            });

            // Listen direct navigation to ledger transaction row
            Livewire.on('scroll-to-ledger-row', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const type = data.type;
                const id = data.id;
                setTimeout(() => {
                    const row = document.getElementById(`ledger-row-${type}-${id}`);
                    if (row) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        row.classList.add('table-row-highlight-pulse');
                        setTimeout(() => {
                            row.classList.remove('table-row-highlight-pulse');
                        }, 2500);
                    }
                }, 150);
            });

            // Listen open-modal event untuk sinkronisasi nilai Select2
            Livewire.on('open-modal', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const modalId = typeof data === 'object' ? data.id : data;
                $('#' + modalId).modal('show');

                if (modalId === 'cashAdvanceModal') {
                    setTimeout(() => {
                        const empVal = data && typeof data.employee_id !== 'undefined' ? data.employee_id : @this.get('form.employee_id');
                        $('#form_employee_id').val(empVal || '').trigger('change.select2');

                        const schemeVal = data && typeof data.repayment_scheme !== 'undefined' ? data.repayment_scheme : @this.get('form.repayment_scheme');
                        $('#form_repayment_scheme').val(schemeVal || 'salary_installment').trigger('change.select2');
                    }, 50);
                } else if (modalId === 'cashAdvancePaymentModal') {
                    setTimeout(() => {
                        const methodVal = @this.get('paymentForm.payment_method');
                        $('#pay_method').val(methodVal || 'cash').trigger('change.select2');
                    }, 50);
                } else if (modalId === 'cashAdvanceDetailModal') {
                    const targetTab = (typeof data === 'object' && data.tab) ? data.tab : 'installments';
                    setTimeout(() => {
                        if (targetTab === 'payments') {
                            $('#payments-tab').tab('show');
                            if (typeof data === 'object' && data.highlightPaymentId) {
                                setTimeout(() => {
                                    const target = document.getElementById('pay-card-' + data.highlightPaymentId);
                                    if (target) {
                                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    }
                                }, 150);
                            }
                        } else {
                            $('#installments-tab').tab('show');
                        }
                    }, 50);
                }
            });

            Livewire.on('close-modal', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const modalId = typeof data === 'object' ? data.id : data;
                $('#' + modalId).modal('hide');
            });

            $('#cashAdvanceModal').on('hidden.bs.modal', function () {
                @this.call('closeModal');
            });

            $('#cashAdvanceDetailModal').on('hidden.bs.modal', function () {
                @this.call('closeDetailModal');
            });

            $('#cashAdvanceApprovalModal').on('hidden.bs.modal', function () {
                @this.call('closeApprovalModal');
            });

            Livewire.on('swal:confirm-delete', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                Swal.fire({
                    title: 'Hapus Kasbon?',
                    text: `Apakah Anda yakin ingin menghapus "${data.title}"? Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('delete-cash-advance', { id: data.id });
                    }
                });
            });

            window.confirmApproveCashAdvance = function () {
                Swal.fire({
                    title: 'Setujui Kasbon?',
                    text: 'Pengajuan kasbon akan disetujui dan jadwal pemotongan gaji/cicilan akan dibuat secara otomatis.',
                    icon: 'question',
                    input: 'textarea',
                    inputLabel: 'Catatan Persetujuan (Opsional)',
                    inputPlaceholder: 'Tuliskan catatan bila ada...',
                    showCancelButton: true,
                    confirmButtonColor: '#0b52aa',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Setujui Kasbon',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('approve-cash-advance', { notes: result.value || null });
                    }
                });
            };

            window.confirmRejectCashAdvance = function () {
                Swal.fire({
                    title: 'Tolak Pengajuan Kasbon?',
                    text: 'Pengajuan kasbon ini akan ditolak.',
                    icon: 'warning',
                    input: 'textarea',
                    inputLabel: 'Alasan Penolakan (Wajib Diisi)',
                    inputPlaceholder: 'Tuliskan alasan penolakan kasbon...',
                    inputValidator: (value) => {
                        if (!value || value.trim().length < 3) {
                            return 'Alasan penolakan wajib diisi (minimal 3 karakter)!';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Tolak Pengajuan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('reject-cash-advance', { reason: result.value ? result.value.trim() : '' });
                    }
                });
            };

            window.confirmApproveCashAdvanceDirect = function (advanceId) {
                Swal.fire({
                    title: 'Setujui Pengajuan Kasbon?',
                    text: 'Pengajuan kasbon akan disetujui dan jadwal pemotongan gaji/cicilan akan dibuat secara otomatis.',
                    icon: 'question',
                    input: 'textarea',
                    inputLabel: 'Catatan Persetujuan (Opsional)',
                    inputPlaceholder: 'Tuliskan catatan bila ada...',
                    showCancelButton: true,
                    confirmButtonColor: '#0b52aa',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Setujui Kasbon',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('approve-cash-advance-direct', { advanceId: advanceId, notes: result.value || null });
                    }
                });
            };

            window.confirmRejectCashAdvanceDirect = function (advanceId) {
                Swal.fire({
                    title: 'Tolak Pengajuan Kasbon?',
                    text: 'Pengajuan kasbon ini akan ditolak.',
                    icon: 'warning',
                    input: 'textarea',
                    inputLabel: 'Alasan Penolakan (Wajib Diisi)',
                    inputPlaceholder: 'Tuliskan alasan penolakan kasbon...',
                    inputValidator: (value) => {
                        if (!value || value.trim().length < 3) {
                            return 'Alasan penolakan wajib diisi (minimal 3 karakter)!';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Tolak Pengajuan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('reject-cash-advance-direct', { advanceId: advanceId, reason: result.value ? result.value.trim() : '' });
                    }
                });
            };

            window.confirmApprovePayment = function (paymentId) {
                Swal.fire({
                    title: 'Setujui Pembayaran Kasbon?',
                    text: 'Pembayaran ini akan disahkan. Saldo kasbon dan cicilan karyawan akan berkurang secara otomatis.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0b52aa',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui Pembayaran',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('approve-cash-advance-payment', { paymentId: paymentId });
                    }
                });
            };

            window.confirmRejectPayment = function (paymentId) {
                Swal.fire({
                    title: 'Tolak Pembayaran Kasbon?',
                    text: 'Pembayaran kasbon ini akan ditolak dan saldo tidak akan berkurang.',
                    icon: 'warning',
                    input: 'textarea',
                    inputLabel: 'Alasan Penolakan (Wajib Diisi)',
                    inputPlaceholder: 'Tuliskan alasan penolakan pembayaran...',
                    inputValidator: (value) => {
                        if (!value || value.trim().length < 3) {
                            return 'Alasan penolakan wajib diisi (minimal 3 karakter)!';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Tolak Pembayaran',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('reject-cash-advance-payment', { paymentId: paymentId, reason: result.value ? result.value.trim() : '' });
                    }
                });
            };
        }

        if (typeof window.Livewire !== 'undefined') {
            initCashAdvanceScripts();
        } else {
            document.addEventListener('livewire:init', initCashAdvanceScripts);
        }
    })();
</script>
@endpush
