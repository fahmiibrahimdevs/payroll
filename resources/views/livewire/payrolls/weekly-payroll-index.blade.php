<div>
    <x-slot name="header">
        <h1>Gaji &amp; Slip Mingguan</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Penggajian</div>
            <div class="breadcrumb-item">Gaji Mingguan</div>
        </div>
    </x-slot>

    <!-- Template Native Section Title & Lead + Tombol Buat Periode Baru -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2 mb-md-3">
        <div class="mr-md-3">
            <h2 class="section-title mt-0">Rekap Gaji &amp; Slip Mingguan</h2>
            <p class="section-lead mb-0">
                Kalkulasi upah harian, uang makan mingguan, dan upah lembur seluruh karyawan dengan siklus mingguan Jum'at s/d Kamis.
            </p>
        </div>

        @if (auth()->user()?->hasRole(['admin', 'direktur']))
            <button type="button" wire:click="openCreatePeriodModal" class="btn btn-primary d-none d-md-inline-flex align-items-center font-weight-bold shadow-sm mt-3 mt-md-0 flex-shrink-0" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 9px 16px; font-size: 13px;">
                <i class="fas fa-plus mr-1.5"></i> Buat Periode Mingguan
            </button>
        @endif
    </div>

    <!-- 4 Modern Stat Cards (Rekap Eksekutif Mingguan) -->
    <div class="cash-advance-stat-grid mb-4">
        <!-- 1. Total Biaya Mingguan -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Biaya Mingguan">Total Biaya Mingguan</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            Rp {{ number_format($stats['total_grand_total'], 0, ',', '.') }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-chart-line text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Dari {{ $stats['total_periods'] }} periode mingguan
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Upah Harian -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Upah Harian">Total Upah Harian</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            Rp {{ number_format($stats['total_daily_wage'], 0, ',', '.') }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-coins text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Pegawai harian lepas
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Uang Makan -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Uang Makan Mingguan">Total Uang Makan</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            Rp {{ number_format($stats['total_meal_allowance'], 0, ',', '.') }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-receipt text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Uang makan staf &amp; harian
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Total Upah Lembur -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                    <i class="fas fa-business-time"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Upah Lembur Mingguan">Total Upah Lembur</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            Rp {{ number_format($stats['total_overtime_pay'], 0, ',', '.') }}
                        </div>
                        <span class="cash-advance-stat-badge" style="background-color: #f3e8ff; color: #9333ea;">
                            <i class="fas fa-clock text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Jam lembur mingguan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Split Layout (3 Kolom Ringkasan & Grafik : 9 Kolom Riwayat Periode) -->
    <div class="row align-items-start">
        <!-- ================= KOLOM KIRI (COL-3): RINGKASAN & DONUT CHART BIAYA MINGGUAN ================= -->
        <div class="col-12 col-lg-3 col-md-4 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03);">
                <!-- 1. Header Card -->
                <div class="border-bottom d-flex align-items-center justify-content-between" style="background: #ffffff; padding: 14px 20px;">
                    <div>
                        <h4 class="mb-0" style="font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.35;">
                            Rekap Gaji Mingguan
                        </h4>
                        <div style="font-size: 11.5px; font-weight: 500; color: #64748b; margin-top: 2px;">
                            Siklus Jum'at s/d Kamis
                        </div>
                    </div>
                    <span class="badge badge-light border text-primary font-weight-600 px-2 py-0.5" style="font-size: 10px; border-radius: 6px;">
                        7 Hari
                    </span>
                </div>

                <div class="card-body p-0">
                    <!-- 2. Hero Metric: Donut Chart Komposisi Biaya Mingguan -->
                    <div style="padding: 16px 20px 14px;" class="text-center">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div style="font-size: 12px; font-weight: 600; color: #64748b;">
                                Komposisi biaya mingguan
                            </div>
                            <span class="badge badge-light border text-muted font-weight-600 px-2 py-0.5" style="font-size: 10px; border-radius: 6px;">
                                Total Periode
                            </span>
                        </div>

                        <!-- Donut Chart Container -->
                        <div wire:key="weekly-doughnut-{{ md5(json_encode([$stats['total_grand_total'], $chartValues])) }}"
                             x-data="{
                                 chartInstance: null,
                                 initChart() {
                                     const hasData = {{ $hasChartData ? 'true' : 'false' }};
                                     const labels = {{ \Illuminate\Support\Js::from($chartLabels) }};
                                     const dataVals = {{ \Illuminate\Support\Js::from($chartValues) }};
                                     const bgColors = {{ \Illuminate\Support\Js::from($chartColors) }};

                                     const render = () => {
                                         const canvas = this.$refs.canvas;
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
                                                         callbacks: {
                                                             label: function(context) {
                                                                 const val = context.raw || 0;
                                                                 return context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val);
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

                            <canvas x-ref="canvas" style="max-height: 180px; max-width: 180px;"></canvas>

                            <!-- Center Text Overlay -->
                            <div class="chart-center-overlay position-absolute text-center d-flex flex-column justify-content-center align-items-center" style="pointer-events: none; max-width: 130px; transition: opacity 0.2s ease;">
                                <span style="font-size: 10.5px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                    TOTAL BIAYA
                                </span>
                                <span style="font-size: 17px; font-weight: 800; color: #0f172a; line-height: 1.25; margin-top: 2px;">
                                    @if ($stats['total_grand_total'] >= 1000000)
                                        Rp {{ number_format($stats['total_grand_total'] / 1000000, 1) }} Jt
                                    @else
                                        Rp {{ number_format($stats['total_grand_total'], 0, ',', '.') }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div style="font-size: 11.5px; font-weight: 400; color: #64748b; line-height: 1.35;">
                            Upah harian + uang makan + lembur
                        </div>
                    </div>

                    <!-- 3. Dual Metric Tiles: Total Periode & Rata-rata per Periode -->
                    <div style="padding: 0 20px 14px;">
                        <div class="row no-gutters" style="gap: 8px;">
                            <div class="col" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); padding: 10px 12px;">
                                <div style="font-size: 11px; font-weight: 500; color: #64748b; line-height: 1.3;">
                                    Total periode
                                </div>
                                <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; line-height: 1.35; margin-top: 3px;">
                                    {{ $stats['total_periods'] }} Periode
                                </div>
                            </div>
                            <div class="col" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); padding: 10px 12px;">
                                <div style="font-size: 11px; font-weight: 500; color: #64748b; line-height: 1.3;">
                                    Rata-rata / periode
                                </div>
                                <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; line-height: 1.35; margin-top: 3px;">
                                    @if ($stats['avg_per_period'] >= 1000000)
                                        Rp {{ number_format($stats['avg_per_period'] / 1000000, 1) }} Jt
                                    @else
                                        Rp {{ number_format($stats['avg_per_period'], 0, ',', '.') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Komposisi Alokasi Uang Mingguan (Allocation Bar) -->
                    <div style="border-top: 1px solid #f1f5f9; padding: 14px 20px;">
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 9px;">
                            Alokasi biaya mingguan
                        </div>

                        <!-- Multi-segment bar -->
                        <div class="d-flex w-100 overflow-hidden mb-2.5" style="height: 8px; border-radius: 4px; background-color: #f1f5f9; gap: 2px;">
                            <div style="width: {{ $pctDaily }}%; background-color: #10b981; border-radius: 3px 0 0 3px;" title="Upah Harian: {{ $pctDaily }}%"></div>
                            <div style="width: {{ $pctMeal }}%; background-color: #f59e0b;" title="Uang Makan: {{ $pctMeal }}%"></div>
                            <div style="width: {{ $pctOt }}%; background-color: #0b52aa; border-radius: 0 3px 3px 0;" title="Upah Lembur: {{ $pctOt }}%"></div>
                        </div>

                        <!-- Breakdown List -->
                        <div class="d-flex flex-column" style="gap: 6px;">
                            <div class="d-flex align-items-center justify-content-between" style="font-size: 11.5px; line-height: 1.4;">
                                <div class="d-flex align-items-center">
                                    <span style="width: 8px; height: 8px; border-radius: 2px; background-color: #10b981; margin-right: 7px; flex-shrink: 0;"></span>
                                    <span style="color: #475569; font-weight: 500;">Upah harian lepas</span>
                                </div>
                                <span style="font-weight: 600; color: #0f172a;">
                                    Rp {{ number_format($stats['total_daily_wage'], 0, ',', '.') }} · {{ $pctDaily }}%
                                </span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between" style="font-size: 11.5px; line-height: 1.4;">
                                <div class="d-flex align-items-center">
                                    <span style="width: 8px; height: 8px; border-radius: 2px; background-color: #f59e0b; margin-right: 7px; flex-shrink: 0;"></span>
                                    <span style="color: #475569; font-weight: 500;">Uang makan mingguan</span>
                                </div>
                                <span style="font-weight: 600; color: #0f172a;">
                                    Rp {{ number_format($stats['total_meal_allowance'], 0, ',', '.') }} · {{ $pctMeal }}%
                                </span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between" style="font-size: 11.5px; line-height: 1.4;">
                                <div class="d-flex align-items-center">
                                    <span style="width: 8px; height: 8px; border-radius: 2px; background-color: #0b52aa; margin-right: 7px; flex-shrink: 0;"></span>
                                    <span style="color: #475569; font-weight: 500;">Upah lembur mingguan</span>
                                </div>
                                <span style="font-weight: 600; color: #0f172a;">
                                    Rp {{ number_format($stats['total_overtime_pay'], 0, ',', '.') }} · {{ $pctOt }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= KOLOM KANAN (COL-9): DAFTAR RIWAYAT PERIODE MINGGUAN ================= -->
        <div class="col-12 col-lg-9 col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4>Daftar Riwayat Periode</h4>
                </div>

                <div class="card-body p-0">
                    <!-- Controls: Show Entries & Search Bar (Format Baku Gaji Bulanan Tanpa Filter Status) -->
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
                        <table class="table table-clean mb-0">
                            <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <tr>
                                    <th class="text-nowrap !py-3 !px-3.5" style="min-width: 190px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">PERIODE</th>
                                    <th class="text-center text-nowrap !py-3 !px-3.5" style="width: 105px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">KARYAWAN</th>
                                    <th class="text-right text-nowrap !py-3 !px-3.5" style="min-width: 130px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">UPAH HARIAN</th>
                                    <th class="text-right text-nowrap !py-3 !px-3.5" style="min-width: 130px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">UANG MAKAN</th>
                                    <th class="text-right text-nowrap !py-3 !px-3.5" style="min-width: 130px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">UPAH LEMBUR</th>
                                    <th class="text-right text-nowrap !py-3 !px-3.5" style="min-width: 140px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">TOTAL BIAYA</th>
                                    <th class="text-center text-nowrap !py-3 !px-3.5" style="min-width: 155px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;">STATUS</th>
                                    <th class="text-center text-nowrap !py-3 !px-3.5" style="min-width: 150px; font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.5px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($periods as $idx => $per)
                                    <tr wire:key="weekly-period-{{ $per->id }}" style="border-bottom: 1px solid #f1f5f9;">
                                        <!-- PERIODE -->
                                        <td class="align-middle text-nowrap !py-3 !px-3.5">
                                            <div class="d-flex align-items-center text-nowrap">
                                                <span class="d-inline-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 32px; height: 32px; border-radius: 8px; background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                                    <i class="far fa-calendar-alt" style="color: #0b52aa; font-size: 13px;"></i>
                                                </span>
                                                <div>
                                                    <a href="{{ route('overtimes.periods.attendances', $per->id) }}" class="font-weight-700 text-decoration-none" style="font-size: 13.5px; color: #0f172a !important;" title="{{ $per->reference_number }}">
                                                        {{ $per->name }}
                                                    </a>
                                                    <div class="mt-0.5" style="font-size: 11.5px; color: #64748b; font-weight: 400; line-height: 1.25;">
                                                        <span class="badge badge-light border text-dark font-weight-normal mr-1" style="font-size: 9.5px; padding: 2px 5px;">Jum'at - Kamis</span>
                                                        {{ $per->start_date->locale('id')->translatedFormat('d M') }} &ndash; {{ $per->end_date->locale('id')->translatedFormat('d M Y') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- KARYAWAN -->
                                        <td class="text-center align-middle font-weight-600 text-nowrap !py-3 !px-3.5">
                                            <span class="badge font-weight-700 px-2.5 py-1" style="font-size: 11.5px; border-radius: 12px; background-color: #f8fafc; color: #334155; border: 1px solid #e2e8f0;">
                                                {{ $per->employee_count }} Orang
                                            </span>
                                        </td>

                                        <!-- UPAH HARIAN -->
                                        <td class="text-right align-middle font-weight-600 text-nowrap !py-3 !px-3.5" style="color: #047857; font-size: 12.5px; font-variant-numeric: tabular-nums;">
                                            Rp {{ number_format((float) ($per->total_daily_wage ?? 0), 0, ',', '.') }}
                                        </td>

                                        <!-- UANG MAKAN -->
                                        <td class="text-right align-middle font-weight-600 text-nowrap !py-3 !px-3.5" style="color: #d97706; font-size: 12.5px; font-variant-numeric: tabular-nums;">
                                            {{ $per->formatted_total_meal_allowance }}
                                        </td>

                                        <!-- UPAH LEMBUR -->
                                        <td class="text-right align-middle font-weight-600 text-nowrap !py-3 !px-3.5" style="color: #475569; font-size: 12.5px; font-variant-numeric: tabular-nums;">
                                            {{ $per->formatted_total_overtime_pay }}
                                        </td>

                                        <!-- TOTAL BIAYA -->
                                        <td class="text-right align-middle font-weight-bold text-nowrap !py-3 !px-3.5" style="color: #0b52aa; font-size: 13.5px; font-variant-numeric: tabular-nums;">
                                            {{ $per->formatted_grand_total }}
                                        </td>

                                        <!-- STATUS / PROGRESS PEMBAYARAN -->
                                        @php
                                            $totalEmp = (int) ($per->attendances_count ?? $per->employee_count ?? 0);
                                            $paidEmp = (int) ($per->paid_attendances_count ?? 0);
                                            if ($per->status === \App\Enums\OvertimePeriodStatus::PAID && $paidEmp < $totalEmp) {
                                                $paidEmp = $totalEmp;
                                            }
                                            $pct = $totalEmp > 0 ? (int) round(($paidEmp / $totalEmp) * 100) : 0;
                                            $isFullyPaid = ($totalEmp > 0 && $paidEmp >= $totalEmp) || $per->status === \App\Enums\OvertimePeriodStatus::PAID;
                                            $isPartiallyPaid = ! $isFullyPaid && $paidEmp > 0;
                                        @endphp
                                        <td class="text-center align-middle text-nowrap !py-3 !px-3.5">
                                            <div class="d-inline-flex flex-column align-items-center" style="min-width: 135px;">
                                                @if ($isFullyPaid)
                                                    <span class="badge font-weight-600 px-2.5 py-0.5 mb-1" style="font-size: 10.5px; border-radius: 9999px; background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;">
                                                        <i class="fas fa-check-circle mr-1" style="font-size: 9.5px;"></i> Sudah Dibayar
                                                    </span>
                                                @elseif ($isPartiallyPaid)
                                                    <span class="badge font-weight-600 px-2.5 py-0.5 mb-1" style="font-size: 10.5px; border-radius: 9999px; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                                        <i class="fas fa-clock mr-1" style="font-size: 9.5px;"></i> {{ $pct }}% Selesai
                                                    </span>
                                                @else
                                                    <span class="badge font-weight-600 px-2.5 py-0.5 mb-1" style="font-size: 10.5px; border-radius: 9999px; background-color: #fef9c3; color: #854d0e; border: 1px solid #fde047;">
                                                        Belum Dibayar
                                                    </span>
                                                @endif

                                                <div class="w-100 mt-0.5" style="max-width: 130px;">
                                                    <div class="progress" style="height: 6px; border-radius: 9999px; background-color: #e2e8f0; overflow: hidden;" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                                        <div class="progress-bar" style="width: {{ $pct }}%; border-radius: 9999px; background-color: {{ $isFullyPaid ? '#10b981' : ($isPartiallyPaid ? '#0b52aa' : '#cbd5e1') }}; transition: width 0.3s ease;"></div>
                                                    </div>
                                                </div>

                                                <div class="mt-1 font-weight-500" style="font-size: 11px; color: #334155; line-height: 1.2;">
                                                    {{ $paidEmp }}/{{ $totalEmp }} orang sudah dibayar
                                                </div>
                                            </div>
                                        </td>

                                        <!-- AKSI -->
                                        <td class="text-center align-middle text-nowrap !py-3 !px-3.5">
                                            @if (auth()->user()?->hasRole(['admin', 'direktur']))
                                                <!-- ATUR PRESENSI & SYNC ABSENSI -->
                                                <a href="{{ route('overtimes.periods.attendances', $per->id) }}" class="btn btn-warning btn-action mr-1" title="Atur Presensi, Uang Makan & Tarik Data Absensi">
                                                    <i class="fas fa-calendar-check"></i>
                                                </a>

                                                <!-- CETAK SLIP GAJI MINGGUAN -->
                                                <a href="{{ route('overtimes.periods.print', $per->id) }}" target="_blank" class="btn btn-info btn-action mr-1" title="Cetak Seluruh Slip Mingguan">
                                                    <i class="fas fa-print"></i>
                                                </a>

                                                <!-- TANDAI LUNAS/SELESAI DIBAYAR -->
                                                @if ($per->status === \App\Enums\OvertimePeriodStatus::DRAFT)
                                                    <button type="button" class="btn btn-success btn-action mr-1" wire:click="confirmMarkPaid({{ $per->id }})" title="Tandai Selesai Dibayar">
                                                        <i class="fas fa-check-double"></i>
                                                    </button>

                                                    <!-- HAPUS PERIODE (ROLE DIREKTUR & ADMIN) -->
                                                    <button type="button" class="btn btn-danger btn-action" wire:click="confirmDelete({{ $per->id }})" title="Hapus Periode Mingguan">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @endif
                                            @else
                                                <a href="{{ route('overtimes.periods.print', $per->id) }}" target="_blank" class="btn btn-info btn-action" title="Lihat Slip Mingguan Saya">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-0 border-0">
                                            <x-empty-state
                                                icon="fas fa-calendar-alt"
                                                title="Belum Ada Periode Gaji Mingguan"
                                                subtitle="Kelompokkan upah harian, uang makan, dan lembur mingguan dengan siklus Jum'at s/d Kamis."
                                                :action-label="auth()->user()?->hasRole(['admin', 'direktur']) ? 'Buat Periode Mingguan' : null"
                                                action-wire-click="openCreatePeriodModal"
                                                action-icon="fas fa-plus"
                                            />
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

    <!-- MODAL: BUAT PERIODE GAJI MINGGUAN (Standard Clean Stisla Modal) -->
    <div wire:ignore.self class="modal fade" id="weeklyPeriodModal" role="dialog" aria-labelledby="weeklyPeriodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="weeklyPeriodModalLabel">Buat Periode Gaji Mingguan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form wire:submit="storePeriod">
                    <div class="modal-body">
                        <!-- Petunjuk Siklus Mingguan (.bd-callout) -->
                        <div class="bd-callout bd-callout-info mb-3" role="alert">
                            <div class="d-flex align-items-start">
                                <div class="bd-callout-icon mr-2.5 mt-0.5">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="bd-callout-body flex-grow-1">
                                    <div class="bd-callout-title">
                                        Siklus Standar: Jum'at s/d Kamis (7 Hari)
                                    </div>
                                    <div class="bd-callout-desc">
                                        Rentang tanggal otomatis dihitung 7 hari tanpa overlap. Sistem otomatis menarik absensi, upah harian lepas, serta uang makan &amp; lembur mingguan staf.
                                    </div>
                                    <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between" style="border-color: rgba(0, 0, 0, 0.06) !important;">
                                        <span style="font-size: 11.5px; color: #334155; font-weight: 500;">
                                            Karyawan Mingguan Terdeteksi:
                                        </span>
                                        @if ($this->eligibleCandidatesCount > 0)
                                            <span class="badge badge-success font-weight-600" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">
                                                <i class="fas fa-users mr-1"></i> {{ $this->eligibleCandidatesCount }} Orang
                                            </span>
                                        @else
                                            <span class="badge badge-danger font-weight-600" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">
                                                <i class="fas fa-user-slash mr-1"></i> 0 Orang
                                            </span>
                                        @endif
                                    </div>
                                    @if ($this->eligibleCandidatesCount === 0)
                                        <div class="mt-2 p-2 bg-white rounded border border-danger text-danger" style="font-size: 11px; line-height: 1.4;">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Tidak ada karyawan harian lepas, catatan presensi hadir, atau pengajuan lembur yang disetujui pada siklus ini (minimal 1 orang). Silakan kelola di menu <a href="{{ route('employees.index') }}" class="font-weight-600 text-danger" style="text-decoration: underline;">Kelola Karyawan</a>.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Rentang Tanggal Siklus 7 Hari (2 Kolom Sejajar) -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Tanggal Mulai (Jum'at) <span class="text-danger">*</span></label>
                                    <input type="date" id="period_start_date" wire:model.live="period_start_date" class="form-control @error('period_start_date') is-invalid @enderror">
                                    <small class="form-text" style="color: #475569; font-size: 11.5px; margin-top: 4px;">
                                        <i class="far fa-calendar-check text-primary mr-1"></i>Pilih hari Jum'at (otomatis disesuaikan jika memilih hari lain)
                                    </small>
                                    @error('period_start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Tanggal Akhir (Kamis) <span class="text-danger">*</span></label>
                                    <input type="date" id="period_end_date" wire:model="period_end_date" readonly class="form-control @error('period_end_date') is-invalid @enderror" style="background-color: #f8fafc; cursor: not-allowed; color: #1e293b;">
                                    <small class="form-text" style="color: #475569; font-size: 11.5px; margin-top: 4px;">
                                        <i class="fas fa-lock text-muted mr-1"></i>Terkunci otomatis: 7 hari siklus Jum'at s/d Kamis
                                    </small>
                                    @error('period_end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Nama Periode -->
                        <div class="form-group">
                            <label class="font-weight-600">Nama Periode <span class="text-danger">*</span></label>
                            <input type="text" id="period_name" wire:model="period_name" class="form-control @error('period_name') is-invalid @enderror" placeholder="Contoh: Gaji Mingguan W44">
                            @error('period_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Catatan Internal -->
                        <div class="form-group mb-0">
                            <label class="font-weight-600">Catatan Internal (Opsional)</label>
                            <textarea id="period_notes" wire:model="period_notes" class="form-control @error('period_notes') is-invalid @enderror" rows="2" placeholder="Catatan tambahan mengenai periode mingguan ini..."></textarea>
                            @error('period_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer bg-white border-top px-4 py-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="storePeriod" {{ $this->eligibleCandidatesCount === 0 ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="storePeriod">
                                <i class="fas fa-check mr-1"></i> Simpan &amp; Buat Periode
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
    @if (auth()->user()?->hasRole(['admin', 'direktur']))
        <button type="button" wire:click="openCreatePeriodModal" wire:loading.attr="disabled" wire:target="openCreatePeriodModal" class="btn-floating-add d-md-none" title="Buat Periode Mingguan" aria-label="Buat Periode Mingguan">
            <i wire:loading wire:target="openCreatePeriodModal" class="fas fa-spinner fa-spin"></i>
            <i wire:loading.remove wire:target="openCreatePeriodModal" class="fas fa-plus"></i>
        </button>
    @endif
</div>

@push('styles')
    <style>
        /* Bootstrap Callout (.bd-callout) Component */
        .bd-callout {
            padding: 0.85rem 1.15rem !important;
            margin-top: 0;
            margin-bottom: 1.25rem !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-left-width: 4px !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04) !important;
            transition: all 0.2s ease-in-out !important;
        }
        .bd-callout-info {
            border-left-color: #0b52aa !important;
            background: linear-gradient(90deg, rgba(11, 82, 170, 0.05) 0%, rgba(255, 255, 255, 0.96) 18%, #ffffff 100%) !important;
        }
        .bd-callout .bd-callout-icon {
            font-size: 18px !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            color: #0b52aa !important;
        }
        .bd-callout .bd-callout-title {
            font-size: 13px !important;
            font-weight: 600 !important;
            line-height: 1.4 !important;
            margin-bottom: 3px !important;
            color: #0b52aa !important;
        }
        .bd-callout .bd-callout-desc {
            font-size: 12px !important;
            color: #334155 !important;
            line-height: 1.45 !important;
            margin-bottom: 0 !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
