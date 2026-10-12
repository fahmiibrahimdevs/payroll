<div>
    <x-slot name="header">
        <h1>Penggajian</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Keuangan & Gaji</div>
            <div class="breadcrumb-item">Penggajian</div>
        </div>
    </x-slot>

    <!-- Template Native Section Title & Lead + Tombol Buat Periode Baru -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2 mb-md-3">
        <div class="mr-md-3">
            <h2 class="section-title mt-0">Periode Penggajian Karyawan</h2>
            <p class="section-lead mb-0">
                Kelola periode penggajian bulanan, perhitungan tunjangan, pemotongan cicilan kasbon otomatis, dan penerbitan slip gaji.
            </p>
        </div>

        @if (auth()->user()?->hasRole('admin'))
            <button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" wire:target="openCreateModal" class="btn btn-primary d-none d-md-inline-flex align-items-center font-weight-bold shadow-sm mt-3 mt-md-0 flex-shrink-0" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 9px 16px; font-size: 13px;">
                <span wire:loading.remove wire:target="openCreateModal">
                    <i class="fas fa-plus mr-1.5"></i> Buat Periode Baru
                </span>
                <span wire:loading wire:target="openCreateModal">
                    <i class="fas fa-spinner fa-spin mr-1.5"></i> Membuka...
                </span>
            </button>
        @endif
    </div>

    <!-- 4 Modern Stat Cards (Rekap Tahunan HRD & Keuangan) -->
    <div class="payroll-stat-grid mb-4">
        <!-- 1. Total Biaya Perusahaan -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-building"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Biaya Perusahaan">Total biaya perusahaan</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-primary" title="{{ $statistics['formatted_yearly_total_company_cost'] }}">
                            {{ $statistics['compact_yearly_company_cost'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-chart-line text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $statistics['yearly_company_cost_subtitle'] }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total THP Ditransfer -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total THP Ditransfer">Total THP ditransfer</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-success" title="{{ $statistics['formatted_yearly_total_thp'] }}">
                            {{ $statistics['compact_yearly_thp'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-money-bill-wave text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $statistics['yearly_thp_subtitle'] }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Setoran -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-red">
                    <i class="fas fa-landmark"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Setoran PPh 21 & BPJS">Total setoran</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-danger" title="{{ $statistics['formatted_yearly_total_setoran'] }}">
                            {{ $statistics['compact_yearly_setoran'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-red">
                            <i class="fas fa-file-invoice-dollar text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $statistics['yearly_setoran_subtitle'] }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Sisa Kasbon -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Sisa Kasbon Belum Lunas">Sisa kasbon</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-warning" title="{{ $statistics['formatted_remaining_kasbon'] }}">
                            {{ $statistics['compact_remaining_kasbon'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-receipt text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $statistics['remaining_kasbon_subtitle'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Split Layout (3 Kolom Sorotan Periode Berjalan : 9 Kolom Riwayat Periode) -->
    <div class="row align-items-start">
        <!-- ================= KOLOM KIRI (COL-3): RINGKASAN PAYROLL EKSEKUTIF ================= -->
        <div class="col-12 col-lg-3 col-md-4 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03);">
                <!-- 1. Header Card -->
                <div class="border-bottom d-flex align-items-center justify-content-between" style="background: #ffffff; padding: 14px 20px;">
                    <div>
                        <h4 class="mb-0" style="font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.35;">
                            Rekap payroll
                        </h4>
                        <div style="font-size: 11.5px; font-weight: 500; color: #64748b; margin-top: 2px;">
                            {{ $statistics['rekap_payroll_subtitle'] }}
                        </div>
                    </div>

                    <!-- Dropdown Filter Tahun -->
                    <div>
                        <select wire:model.live="selectedYear" class="form-control form-control-sm border shadow-none font-weight-bold" style="font-size: 11.5px; border-radius: 8px; border-color: #e2e8f0; background-color: #f8fafc; color: #0f172a; height: 30px; padding: 2px 10px; cursor: pointer;">
                            @foreach ($availableYears as $yearOpt)
                                <option value="{{ $yearOpt }}">{{ $yearOpt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- 2. Hero Metric: Total Biaya Perusahaan (Donut Chart) -->
                    @php
                        $activeMonthsInYear = collect($statistics['twelve_months_data'])
                            ->filter(fn ($tm) => $tm['cost'] > 0)
                            ->values();

                        $hasChartData = $activeMonthsInYear->isNotEmpty();
                        $chartLabels = $hasChartData ? $activeMonthsInYear->pluck('month_name')->all() : ['Belum ada data'];
                        $chartValues = $hasChartData ? $activeMonthsInYear->pluck('cost')->all() : [1];

                        $donutPalette = ['#0b52aa', '#2563eb', '#3b82f6', '#0284c7', '#0ea5e9', '#06b6d4', '#10b981', '#14b8a6', '#6366f1', '#8b5cf6', '#f59e0b', '#ec4899'];
                        $chartColors = $hasChartData
                            ? ($activeMonthsInYear->count() === 1 ? ['#2563eb'] : array_slice($donutPalette, 0, $activeMonthsInYear->count()))
                            : ['#e2e8f0'];
                    @endphp

                    <div style="padding: 16px 20px 14px;" class="text-center">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div style="font-size: 12px; font-weight: 600; color: #64748b;">
                                Total biaya perusahaan
                            </div>
                            <span class="badge badge-light border text-muted font-weight-600 px-2 py-0.5" style="font-size: 10.5px; border-radius: 6px;">
                                Tahun {{ $selectedYear }}
                            </span>
                        </div>

                        <!-- Donut Chart Container -->
                        <div wire:key="payroll-doughnut-{{ $selectedYear }}-{{ $statistics['yearly_total_company_cost'] }}"
                             x-data="{
                                 chartInstance: null,
                                 initChart() {
                                     const year = {{ (int) $selectedYear }};
                                     const hasData = {{ $hasChartData ? 'true' : 'false' }};
                                     const labels = {{ \Illuminate\Support\Js::from($chartLabels) }};
                                     const dataVals = {{ \Illuminate\Support\Js::from($chartValues) }};
                                     const bgColors = {{ \Illuminate\Support\Js::from($chartColors) }};

                                     const render = () => {
                                         const canvas = document.getElementById('payrollDoughnutCanvas-' + year);
                                         if (!canvas) return;

                                         if (typeof Chart === 'undefined') {
                                             setTimeout(render, 100);
                                             return;
                                         }

                                         if (this.chartInstance) {
                                             try { this.chartInstance.destroy(); } catch (e) {}
                                             this.chartInstance = null;
                                         }

                                         this.chartInstance = new Chart(canvas, {
                                             type: 'doughnut',
                                             data: {
                                                 labels: labels,
                                                 datasets: [{
                                                     data: dataVals,
                                                     backgroundColor: bgColors,
                                                     hoverBackgroundColor: bgColors,
                                                     borderWidth: hasData ? 2 : 0,
                                                     borderColor: '#ffffff',
                                                     hoverOffset: hasData ? 4 : 0
                                                 }]
                                             },
                                             options: {
                                                 responsive: true,
                                                 maintainAspectRatio: false,
                                                 cutout: '74%',
                                                 animation: {
                                                     duration: 500
                                                 },
                                                 plugins: {
                                                     legend: {
                                                         display: false
                                                     },
                                                     tooltip: {
                                                         enabled: hasData,
                                                         backgroundColor: '#0f172a',
                                                         titleColor: '#ffffff',
                                                         bodyColor: '#ffffff',
                                                         padding: 10,
                                                         cornerRadius: 8,
                                                         displayColors: false,
                                                         callbacks: {
                                                             title: function(context) {
                                                                 return context[0].label + ' ' + year;
                                                             },
                                                             label: function(context) {
                                                                 const val = context.raw || 0;
                                                                 return 'Total Biaya: Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                                             }
                                                         }
                                                     }
                                                 }
                                             }
                                         });
                                     };

                                     this.$nextTick(() => {
                                         render();
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
                             x-init="initChart()"
                             class="position-relative d-flex justify-content-center align-items-center my-3"
                             style="height: 180px; width: 100%;">

                            <canvas id="payrollDoughnutCanvas-{{ $selectedYear }}" style="max-height: 180px; max-width: 180px;"></canvas>

                            <!-- Center Text Overlay -->
                            <div class="position-absolute text-center d-flex flex-column justify-content-center align-items-center" style="pointer-events: none; max-width: 125px;">
                                <span style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                    TOTAL {{ $selectedYear }}
                                </span>
                                <span style="font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.25; margin-top: 2px;" title="{{ $statistics['formatted_yearly_total_company_cost'] }}">
                                    {{ $statistics['compact_yearly_company_cost'] }}
                                </span>
                            </div>
                        </div>

                        <div style="font-size: 11.5px; font-weight: 400; color: #94a3b8; line-height: 1.35;">
                            Gaji bruto + BPJS porsi perusahaan
                        </div>
                    </div>

                    <!-- 3. Dual Metric Tiles: Rata-rata/bulan & Biaya/orang/bulan -->
                    <div style="padding: 0 20px 14px;">
                        <div class="row no-gutters" style="gap: 8px;">
                            <div class="col" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                                <div style="font-size: 11px; font-weight: 500; color: #64748b; line-height: 1.3;">
                                    Rata-rata / bulan
                                </div>
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.35; margin-top: 3px; word-break: break-all;">
                                    {{ $statistics['formatted_yearly_avg_cost_per_month'] }}
                                </div>
                            </div>
                            <div class="col" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                                <div style="font-size: 11px; font-weight: 500; color: #64748b; line-height: 1.3;">
                                    Biaya / orang / bulan
                                </div>
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.35; margin-top: 3px; word-break: break-all;">
                                    {{ $statistics['formatted_yearly_avg_cost_per_person'] }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Uang gaji dipakai untuk apa (Allocation Bar) -->
                    <div style="border-top: 1px solid #f1f5f9; padding: 14px 20px;">
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 9px;">
                            Uang gaji dipakai untuk apa
                        </div>

                        <!-- Multi-segment bar -->
                        <div class="d-flex w-100 overflow-hidden mb-2.5" style="height: 8px; border-radius: 4px; background-color: #f1f5f9; gap: 2px;">
                            <div style="width: {{ $statistics['yearly_thp_pct'] }}%; background-color: #0b52aa; border-radius: 3px 0 0 3px;" title="Take Home Pay: {{ $statistics['yearly_thp_pct'] }}%"></div>
                            <div style="width: {{ $statistics['yearly_bpjs_pct'] }}%; background-color: #f59e0b;" title="BPJS: {{ $statistics['yearly_bpjs_pct'] }}%"></div>
                            <div style="width: {{ $statistics['yearly_pph_pct'] }}%; background-color: #ef4444; border-radius: 0 3px 3px 0;" title="PPh 21: {{ $statistics['yearly_pph_pct'] }}%"></div>
                        </div>

                        <!-- Breakdown List -->
                        <div class="d-flex flex-column" style="gap: 6px;">
                            <div class="d-flex align-items-center justify-content-between" style="font-size: 11.5px; line-height: 1.4;">
                                <div class="d-flex align-items-center">
                                    <span style="width: 8px; height: 8px; border-radius: 2px; background-color: #0b52aa; margin-right: 7px; flex-shrink: 0;"></span>
                                    <span style="color: #475569; font-weight: 500;">Take home pay</span>
                                </div>
                                <span style="font-weight: 600; color: #0f172a;">
                                    {{ $statistics['formatted_yearly_total_thp'] }} · {{ $statistics['yearly_thp_pct'] }}%
                                </span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between" style="font-size: 11.5px; line-height: 1.4;">
                                <div class="d-flex align-items-center">
                                    <span style="width: 8px; height: 8px; border-radius: 2px; background-color: #f59e0b; margin-right: 7px; flex-shrink: 0;"></span>
                                    <span style="color: #475569; font-weight: 500;">BPJS (setor)</span>
                                </div>
                                <span style="font-weight: 600; color: #0f172a;">
                                    {{ $statistics['formatted_yearly_total_bpjs'] }} · {{ $statistics['yearly_bpjs_pct'] }}%
                                </span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between" style="font-size: 11.5px; line-height: 1.4;">
                                <div class="d-flex align-items-center">
                                    <span style="width: 8px; height: 8px; border-radius: 2px; background-color: #ef4444; margin-right: 7px; flex-shrink: 0;"></span>
                                    <span style="color: #475569; font-weight: 500;">PPh 21 (setor)</span>
                                </div>
                                <span style="font-weight: 600; color: #0f172a;">
                                    {{ $statistics['formatted_yearly_total_pph'] }} · {{ $statistics['yearly_pph_pct'] }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Biaya per departemen -->
                    <div style="border-top: 1px solid #f1f5f9; padding: 14px 20px;">
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
                            Biaya per departemen
                        </div>
                        <div class="d-flex flex-column" style="gap: 10px;">
                            @forelse ($statistics['department_breakdown'] as $dept)
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 11.5px; line-height: 1.35;">
                                        <span style="font-weight: 500; color: #334155;">
                                            {{ $dept['name'] }} · {{ $dept['count'] }} orang
                                        </span>
                                        <span style="font-weight: 700; color: #0f172a;">
                                            {{ $dept['compact_cost'] }}
                                        </span>
                                    </div>
                                    <div class="w-100" style="height: 5px; background-color: #f1f5f9; border-radius: 9999px; overflow: hidden;">
                                        <div style="width: {{ $dept['bar_pct'] }}%; height: 100%; background-color: #0b52aa; border-radius: 9999px;"></div>
                                    </div>
                                </div>
                            @empty
                                <div style="font-size: 11.5px; color: #94a3b8;">Belum ada rincian departemen</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- 6. Tombol Action: Export Rekap Tahunan (PDF) -->
                    <div class="border-top" style="background-color: #ffffff; padding: 14px 20px;">
                        <a href="{{ route('payrolls.yearly-summary.print', $selectedYear) }}" target="_blank"
                           class="btn btn-block d-flex align-items-center justify-content-center font-weight-bold shadow-none"
                           style="border-radius: 10px; border: 1px solid #cbd5e1; color: #0f172a; background-color: #f8fafc; padding: 8px 14px; font-size: 12px; transition: all 0.15s ease;">
                            <i class="fas fa-file-pdf mr-1.5" style="color: #ef4444; font-size: 13px;"></i>
                            <span>Export rekap tahunan (PDF)</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= KOLOM KANAN (COL-9): DAFTAR RIWAYAT PERIODE ================= -->
        <div class="col-12 col-lg-9 col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4>Daftar Riwayat Periode</h4>
                </div>

                <div class="card-body p-0">
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
                            <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Search here...">
                        </div>
                    </div>

                    <!-- Table Responsive -->
                    <div class="table-responsive">
                        <table class="table table-clean">
                            <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <tr>
                                    <th class="text-nowrap !py-3 !px-3.5" style="min-width: 180px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">PERIODE</th>
                                    <th class="text-center text-nowrap !py-3 !px-3.5" style="width: 110px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">KARYAWAN</th>
                                    <th class="text-right text-nowrap !py-3 !px-3.5" style="min-width: 140px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">TOTAL BIAYA</th>
                                    <th class="text-right text-nowrap !py-3 !px-3.5" style="min-width: 140px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">THP DITRANSFER</th>
                                    <th class="text-center text-nowrap !py-3 !px-3.5" style="width: 125px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">STATUS</th>
                                    <th class="text-nowrap !py-3 !px-3.5" style="min-width: 135px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">DISETUJUI</th>
                                    <th class="text-center text-nowrap !py-3 !px-3.5" style="width: 90px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($periods as $period)
                                    <tr wire:key="period-{{ $period->id }}" style="border-bottom: 1px solid #f1f5f9;">
                                        <td class="align-middle text-nowrap !py-3 !px-3.5">
                                            <div class="d-flex align-items-center text-nowrap">
                                                <span class="d-inline-flex align-items-center justify-content-center mr-2.5" style="width: 32px; height: 32px; border-radius: 8px; background-color: #eff6ff; border: 1px solid #bfdbfe; flex-shrink: 0;">
                                                    <i class="far fa-calendar-alt" style="color: #0b52aa; font-size: 13px;"></i>
                                                </span>
                                                <a href="{{ route('payrolls.show', $period) }}" class="font-weight-700 text-decoration-none" style="font-size: 13.5px; color: #0f172a !important;" title="{{ $period->reference_number }}">
                                                    {{ $period->period_label }}
                                                </a>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle font-weight-600 text-nowrap !py-3 !px-3.5">
                                            <span class="badge font-weight-700 px-2.5 py-1" style="font-size: 11.5px; border-radius: 12px; background-color: #f8fafc; color: #334155; border: 1px solid #e2e8f0;">
                                                <i class="fas fa-users mr-1" style="color: #64748b; font-size: 10px;"></i>{{ $period->employee_count }} orang
                                            </span>
                                        </td>
                                        <td class="text-right align-middle text-nowrap !py-3 !px-3.5">
                                            <span class="font-weight-700" style="color: #0f172a; font-size: 13px;">
                                                {{ $period->formatted_total_cost_to_company }}
                                            </span>
                                        </td>
                                        <td class="text-right align-middle text-nowrap !py-3 !px-3.5">
                                            <span class="font-weight-700" style="color: #059669; font-size: 13px;">
                                                {{ $period->formatted_total_net_salary }}
                                            </span>
                                        </td>
                                        <td class="text-center align-middle text-nowrap !py-3 !px-3.5">
                                            <span class="{{ $period->status_badge_class }} font-weight-600 px-2.5 py-1" style="font-size: 11px; border-radius: 12px;">
                                                {{ $period->status_label }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-nowrap !py-3 !px-3.5">
                                            @if ($period->approved_by && $period->approved_at)
                                                <div class="d-inline-flex align-items-center text-nowrap">
                                                    <i class="fas fa-check-circle text-success mr-1.5" style="font-size: 12px;"></i>
                                                    <span class="font-weight-600" style="color: #1e293b; font-size: 12px;">
                                                        {{ $period->approver?->name ?? 'Direktur' }}, {{ $period->approved_at->locale('id')->translatedFormat('d M') }}
                                                    </span>
                                                </div>
                                            @elseif ($period->status === \App\Enums\PayrollPeriodStatus::REJECTED)
                                                <div class="d-inline-flex align-items-center text-nowrap">
                                                    <i class="fas fa-times-circle text-danger mr-1.5" style="font-size: 12px;"></i>
                                                    <span class="text-danger font-weight-600" style="font-size: 12px;" title="{{ $period->rejection_reason }}">
                                                        {{ $period->approver?->name ?? 'Direktur' }} (Revisi)
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-muted font-weight-600" style="font-size: 13px;">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle text-nowrap !py-3 !px-3.5">
                                            <div class="d-inline-flex align-items-center text-nowrap">
                                                <a href="{{ route('payrolls.show', $period) }}" class="btn btn-info btn-action mr-1" title="Lihat">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if ($period->status->isEditable() && auth()->user()?->hasRole('admin'))
                                                    <button type="button" 
                                                        wire:click="confirmDelete({{ $period->id }})" 
                                                        wire:loading.attr="disabled"
                                                        wire:target="confirmDelete({{ $period->id }})"
                                                        class="btn btn-danger btn-action" 
                                                        title="Hapus">
                                                        <i wire:loading wire:target="confirmDelete({{ $period->id }})" class="fas fa-spinner fa-spin"></i>
                                                        <i wire:loading.remove wire:target="confirmDelete({{ $period->id }})" class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <div class="py-4">
                                                <i class="fas fa-folder-open text-muted mb-2" style="font-size: 32px;"></i>
                                                <div class="font-weight-600 mt-2">Belum ada periode penggajian</div>
                                                <p class="small text-muted mb-0">Gunakan tombol "+ Buat Periode Baru" di atas untuk memulai perhitungan gaji bulan ini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer: Info & Pagination -->
                    <div class="dt-footer-wrapper">
                        <div>
                            Showing {{ $periods->total() > 0 ? $periods->firstItem() : 0 }} to {{ $periods->total() > 0 ? $periods->lastItem() : 0 }} of {{ $periods->total() }} entries
                        </div>
                        <div>
                            {{ $periods->links('livewire.custom-pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Buat Periode Baru -->
    <div wire:ignore.self class="modal fade" id="createPeriodModal" role="dialog" aria-labelledby="createPeriodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPeriodModalLabel">Buat Periode Penggajian Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form wire:submit="storePeriod">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Bulan Penggajian <span class="text-danger">*</span></label>
                                    <select wire:model.live="newMonth" class="form-control @error('newMonth') is-invalid @enderror">
                                        @php
                                            $monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                                        @endphp
                                        @for ($m = 1; $m <= 12; $m++)
                                            @php
                                                $isTaken = in_array($m, $existingMonthsForNewYear ?? [], true);
                                            @endphp
                                            <option value="{{ $m }}" {{ $isTaken ? 'disabled' : '' }}>
                                                {{ $monthNames[$m] }} {{ $isTaken ? '(Sudah Ada)' : '' }}
                                            </option>
                                        @endfor
                                    </select>
                                    @error('newMonth')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if (in_array($newMonth, $existingMonthsForNewYear ?? [], true))
                                        <div class="text-danger small mt-1 font-weight-600">
                                            <i class="fas fa-exclamation-circle mr-1"></i> Periode bulan ini sudah pernah dibuat. Silakan pilih bulan lain.
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Tahun <span class="text-danger">*</span></label>
                                    <select wire:model.live="newYear" class="form-control @error('newYear') is-invalid @enderror">
                                        @foreach ($availableYears as $yr)
                                            <option value="{{ $yr }}">{{ $yr }}</option>
                                        @endforeach
                                    </select>
                                    @error('newYear')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-600">Nama Periode <span class="text-danger">*</span></label>
                            <input type="text" wire:model="newName" class="form-control @error('newName') is-invalid @enderror" placeholder="Contoh: Penggajian Periode Oktober 2026">
                            @error('newName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-600">Catatan Internal (Opsional)</label>
                            <textarea wire:model="newNotes" class="form-control @error('newNotes') is-invalid @enderror" rows="2" placeholder="Catatan tambahan mengenai periode ini"></textarea>
                            @error('newNotes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-light border small text-muted mb-0">
                            <i class="fas fa-info-circle text-primary mr-1"></i>
                            Sistem akan secara otomatis menarik seluruh karyawan berstatus <strong>Aktif</strong>, gaji pokok masing-masing, serta potongan cicilan kasbon yang jatuh tempo pada bulan tersebut.
                        </div>
                    </div>
                    <div class="modal-footer bg-whitesmoke br">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" {{ in_array($newMonth, $existingMonthsForNewYear ?? [], true) ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="storePeriod">
                                <i class="fas fa-check mr-1"></i> Proses & Buat Draf
                            </span>
                            <span wire:loading wire:target="storePeriod">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Floating Action Button (Buat Periode Baru - Khusus Mobile) -->
    @if (auth()->user()?->hasRole('admin'))
        <button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" wire:target="openCreateModal" class="btn-floating-add d-md-none" title="Buat Periode Baru" aria-label="Buat Periode Baru">
            <i wire:loading wire:target="openCreateModal" class="fas fa-spinner fa-spin"></i>
            <i wire:loading.remove wire:target="openCreateModal" class="fas fa-plus"></i>
        </button>
    @endif
</div>

@push('styles')
    <style>
        .payroll-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .payroll-stat-grid .card {
            margin-bottom: 0 !important;
        }
        @media (min-width: 576px) {
            .payroll-stat-grid {
                gap: 12px;
            }
        }
        @media (min-width: 992px) {
            .payroll-stat-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
                margin-bottom: 24px;
            }
        }
        @media (min-width: 1200px) {
            .payroll-stat-grid {
                gap: 16px;
            }
        }
        .cash-advance-stat-icon-red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.28) !important;
        }
        .cash-advance-stat-badge-red {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
        }


    </style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

