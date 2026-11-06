<div>
    <!-- Top Row: 8 Metric Cards (Kiri 4x2) + Widget Presensi Mandiri (Kanan) | Pada Mobile Presensi Mandiri Tampil Paling Atas -->
    <div class="row align-items-start mb-4">
        <!-- Sisi Kiri (col-xl-9 col-lg-8): 8 Stat Cards dalam Grid 4x2 (Desktop order-lg-1, Mobile order-2) -->
        <div class="col-xl-9 col-lg-8 order-2 order-lg-1">
            <!-- Section Lead & Title untuk Ringkasan & Presensi Karyawan -->
            <div class="mb-3">
                <h2 class="section-title mt-0 mb-1">Ringkasan & Presensi Karyawan</h2>
                <p class="section-lead mb-0">Informasi kehadiran hari ini, status cuti, dan metrik operasional karyawan secara real-time.</p>
            </div>

            <div class="dashboard-8kpi-grid">
                <!-- 1. Karyawan Aktif -->
                <div class="card cash-advance-stat-card mb-0 shadow-sm">
                    <div class="card-body cash-advance-stat-body">
                        <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="cash-advance-stat-content">
                            <div class="cash-advance-stat-title" title="Karyawan Aktif">
                                Karyawan Aktif
                            </div>
                            <div class="cash-advance-stat-value-row">
                                <div class="cash-advance-stat-value" title="{{ number_format($stats['active_employees_count']) }} Orang">
                                    <span>{{ number_format($stats['active_employees_count']) }}</span>
                                    <span class="cash-advance-stat-unit">Orang</span>
                                </div>
                                <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                                    <i class="fas fa-user-check"></i>
                                </span>
                            </div>
                            <div class="cash-advance-stat-subtext" title="{{ $stats['permanent_count'] }} Tetap • {{ $stats['contract_count'] }} Kontrak">
                                {{ $stats['permanent_count'] }} Tetap • {{ $stats['contract_count'] }} Kontrak
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Departemen -->
                <div class="card cash-advance-stat-card mb-0 shadow-sm">
                    <div class="card-body cash-advance-stat-body">
                        <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="cash-advance-stat-content">
                            <div class="cash-advance-stat-title" title="Departemen">
                                Departemen
                            </div>
                            <div class="cash-advance-stat-value-row">
                                <div class="cash-advance-stat-value" title="{{ $stats['departments_count'] }} Divisi">
                                    <span>{{ $stats['departments_count'] }}</span>
                                    <span class="cash-advance-stat-unit">Divisi</span>
                                </div>
                                <span class="cash-advance-stat-badge cash-advance-stat-badge-purple">
                                    <i class="fas fa-sitemap"></i>
                                </span>
                            </div>
                            <div class="cash-advance-stat-subtext" title="Struktur unit kerja">
                                Struktur unit kerja
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Hari Libur -->
                <div class="card cash-advance-stat-card mb-0 shadow-sm">
                    <div class="card-body cash-advance-stat-body">
                        <div class="cash-advance-stat-icon cash-advance-stat-icon-cyan">
                            <i class="fas fa-umbrella"></i>
                        </div>
                        <div class="cash-advance-stat-content">
                            <div class="cash-advance-stat-title" title="Hari Libur">
                                Hari Libur
                            </div>
                            <div class="cash-advance-stat-value-row">
                                <div class="cash-advance-stat-value" title="{{ $stats['holidays_count'] }} Hari">
                                    <span>{{ $stats['holidays_count'] }}</span>
                                    <span class="cash-advance-stat-unit">Hari</span>
                                </div>
                                <span class="cash-advance-stat-badge cash-advance-stat-badge-cyan">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                            </div>
                            <div class="cash-advance-stat-subtext" title="Kalender tahun {{ $selectedYear }}">
                                Kalender tahun {{ $selectedYear }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Biaya Gaji Mingguan -->
                <div class="card cash-advance-stat-card mb-0 shadow-sm">
                    <div class="card-body cash-advance-stat-body">
                        <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="cash-advance-stat-content">
                            <div class="cash-advance-stat-title" title="Biaya Gaji Mingguan">
                                Biaya Gaji Mingguan
                            </div>
                            <div class="cash-advance-stat-value-row">
                                <div class="cash-advance-stat-value" title="{{ $stats['formatted_weekly_payroll_cost'] }}">
                                    <span>{{ $stats['formatted_weekly_payroll_cost'] }}</span>
                                </div>
                                <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                                    <i class="fas fa-calendar-week"></i>
                                </span>
                            </div>
                            <div class="cash-advance-stat-subtext" title="Periode: {{ $stats['weekly_period_label'] }}">
                                {{ $stats['weekly_period_label'] }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Lembur Minggu Ini -->
                <div class="card cash-advance-stat-card mb-0 shadow-sm" style="cursor: pointer;" data-toggle="modal" data-target="#weeklyOvertimeModal" title="Klik untuk meninjau daftar karyawan lembur minggu ini">
                    <div class="card-body cash-advance-stat-body">
                        <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                            <i class="fas fa-business-time"></i>
                        </div>
                        <div class="cash-advance-stat-content">
                            <div class="cash-advance-stat-title" title="Lembur Minggu Ini">
                                Lembur Minggu Ini
                            </div>
                            <div class="cash-advance-stat-value-row">
                                <div class="cash-advance-stat-value" title="{{ $stats['weekly_overtime_employees_count'] }} Orang">
                                    <span>{{ $stats['weekly_overtime_employees_count'] }}</span>
                                    <span class="cash-advance-stat-unit">Orang</span>
                                </div>
                                <span class="cash-advance-stat-badge cash-advance-stat-badge-purple">
                                    <i class="fas fa-external-link-alt"></i>
                                </span>
                            </div>
                            <div class="cash-advance-stat-subtext text-primary" title="Tinjau daftar lembur">
                                <i class="fas fa-list-ul mr-1"></i> Tinjau daftar lembur
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Lembur Hari Ini -->
                <div class="card cash-advance-stat-card mb-0 shadow-sm">
                    <div class="card-body cash-advance-stat-body">
                        <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="cash-advance-stat-content">
                            <div class="cash-advance-stat-title" title="Lembur Hari Ini">
                                Lembur Hari Ini
                            </div>
                            <div class="cash-advance-stat-value-row">
                                <div class="cash-advance-stat-value" title="{{ $stats['today_overtime_count'] }} Orang">
                                    <span>{{ $stats['today_overtime_count'] }}</span>
                                    <span class="cash-advance-stat-unit">Orang</span>
                                </div>
                                <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                                    <i class="fas fa-clock"></i>
                                </span>
                            </div>
                            <div class="cash-advance-stat-subtext" title="Karyawan lembur hari ini">
                                Karyawan lembur hari ini
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7. Sudah Masuk -->
                <div class="card cash-advance-stat-card mb-0 shadow-sm">
                    <div class="card-body cash-advance-stat-body">
                        <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <div class="cash-advance-stat-content">
                            <div class="cash-advance-stat-title" title="Sudah Masuk">
                                Sudah Masuk
                            </div>
                            <div class="cash-advance-stat-value-row">
                                <div class="cash-advance-stat-value" title="{{ $stats['today_check_in_count'] }} / {{ $stats['today_scheduled_work_count'] }} Orang">
                                    <span>{{ $stats['today_check_in_count'] }} / {{ $stats['today_scheduled_work_count'] }}</span>
                                    <span class="cash-advance-stat-unit">Orang</span>
                                </div>
                                <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                                    <i class="fas fa-check"></i>
                                </span>
                            </div>
                            <div class="cash-advance-stat-subtext" title="Presensi masuk hari ini">
                                Presensi masuk hari ini
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 8. Sudah Pulang -->
                <div class="card cash-advance-stat-card mb-0 shadow-sm">
                    <div class="card-body cash-advance-stat-body">
                        <div class="cash-advance-stat-icon cash-advance-stat-icon-cyan">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div class="cash-advance-stat-content">
                            <div class="cash-advance-stat-title" title="Sudah Pulang">
                                Sudah Pulang
                            </div>
                            <div class="cash-advance-stat-value-row">
                                <div class="cash-advance-stat-value" title="{{ $stats['today_check_out_count'] }} / {{ $stats['today_scheduled_work_count'] }} Orang">
                                    <span>{{ $stats['today_check_out_count'] }} / {{ $stats['today_scheduled_work_count'] }}</span>
                                    <span class="cash-advance-stat-unit">Orang</span>
                                </div>
                                <span class="cash-advance-stat-badge cash-advance-stat-badge-cyan">
                                    <i class="fas fa-door-open"></i>
                                </span>
                            </div>
                            <div class="cash-advance-stat-subtext" title="Presensi pulang selesai">
                                Presensi pulang selesai
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sisi Kanan (col-xl-3 col-lg-4): Widget Presensi Mandiri (Desktop order-lg-2, Mobile order-1 berada di paling atas) -->
        <div class="col-xl-3 col-lg-4 order-1 order-lg-2 mb-3 mb-lg-0">
            <livewire:attendances.dashboard-clock wire:key="admin-attendance-clock-widget" />
        </div>
    </div>

    <!-- Section Title & Filter: Dashboard Utama / Analitik Tren Penggajian & PPh 21 -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 mt-2" style="gap: 14px;">
        <div>
            <h2 class="section-title my-0">Dashboard Utama</h2>
            <p class="section-lead mb-0">Pantau metrik karyawan, operasional penggajian, dan kasbon secara real-time.</p>
        </div>

        <!-- Filter Periode (Clean Styled Dropdown Pill) -->
        <div class="d-flex align-items-center flex-wrap" style="gap: 8px; background: #ffffff; padding: 4px 10px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);">
            <div class="d-flex align-items-center" style="gap: 6px;">
                <i class="far fa-calendar-alt text-muted" style="font-size: 12px;"></i>
                <label for="filter-month" class="mb-0 text-muted font-weight-600" style="font-size: 11.5px;">Bulan:</label>
                <select id="filter-month" wire:model.live="selectedMonth" class="form-control form-control-sm border shadow-none font-weight-bold" style="font-size: 11.5px; border-radius: 8px; border-color: #e2e8f0; background-color: #f8fafc; color: #0f172a; height: 30px; padding: 2px 10px; cursor: pointer; width: 130px;">
                    @foreach ($monthsList as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center" style="gap: 6px;">
                <label for="filter-year" class="mb-0 text-muted font-weight-600" style="font-size: 11.5px;">Tahun:</label>
                <select id="filter-year" wire:model.live="selectedYear" class="form-control form-control-sm border shadow-none font-weight-bold" style="font-size: 11.5px; border-radius: 8px; border-color: #e2e8f0; background-color: #f8fafc; color: #0f172a; height: 30px; padding: 2px 10px; cursor: pointer; width: 90px;">
                    @foreach ($availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Row 2: Charts & Analytics (Gaya Eksekutif Kasbon & Penggajian) -->
    <div class="row align-items-stretch">
        <!-- Grafik Kiri (col-lg-8): Tren Beban Penggajian & PPh 21 -->
        <div class="col-lg-8 col-md-12 col-12 mb-4">
            <div class="card shadow-sm border-0 mb-0 d-flex flex-column" style="border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e9edf2 !important; height: 100%;">
                <div class="border-bottom d-flex align-items-center justify-content-between flex-wrap" style="background: #ffffff; padding: 14px 20px; gap: 8px;">
                    <div>
                        <h4 class="mb-0" style="font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.35;">
                            Tren Beban Penggajian & PPh 21
                        </h4>
                        <div style="font-size: 11.5px; font-weight: 500; color: #64748b; margin-top: 2px;">
                            Gaji kotor, gaji bersih, dan PPh 21 tercatat tahun {{ $selectedYear }} (termasuk draf)
                        </div>
                    </div>
                    <!-- Minimal Dot Legends -->
                    <div class="d-flex align-items-center flex-wrap" style="gap: 14px; font-size: 11px; font-weight: 600; color: #64748b;">
                        <span class="d-inline-flex align-items-center">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background-color: #0b52aa; margin-right: 5px;"></span> Gaji Bersih
                        </span>
                        <span class="d-inline-flex align-items-center">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background-color: #0ea5e9; margin-right: 5px;"></span> Gaji Bruto
                        </span>
                        <span class="d-inline-flex align-items-center">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background-color: #ef4444; margin-right: 5px;"></span> PPh 21
                        </span>
                    </div>
                </div>
                <div class="card-body p-4 flex-grow-1"
                     wire:key="admin-payroll-trend-{{ $selectedYear }}-{{ $selectedMonth }}-{{ md5(json_encode($stats['payroll_trend'])) }}"
                     x-data="{
                        chartInstance: null,
                        initChart() {
                            const labels = {{ Js::from($stats['payroll_trend']['labels']) }};
                            const netData = {{ Js::from($stats['payroll_trend']['net']) }};
                            const grossData = {{ Js::from($stats['payroll_trend']['gross']) }};
                            const taxData = {{ Js::from($stats['payroll_trend']['tax']) }};

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
                                    type: 'bar',
                                    data: {
                                        labels: labels,
                                        datasets: [
                                            {
                                                label: 'Gaji Bersih',
                                                data: netData,
                                                backgroundColor: '#0b52aa',
                                                borderRadius: 4,
                                                barPercentage: 0.65,
                                                categoryPercentage: 0.75,
                                            },
                                            {
                                                label: 'Gaji Bruto',
                                                data: grossData,
                                                backgroundColor: '#0ea5e9',
                                                borderRadius: 4,
                                                barPercentage: 0.65,
                                                categoryPercentage: 0.75,
                                            },
                                            {
                                                label: 'PPh 21',
                                                data: taxData,
                                                backgroundColor: '#ef4444',
                                                borderRadius: 4,
                                                barPercentage: 0.65,
                                                categoryPercentage: 0.75,
                                            }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        animation: { duration: 400 },
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: {
                                                backgroundColor: '#0f172a',
                                                titleColor: '#ffffff',
                                                bodyColor: '#e2e8f0',
                                                padding: 8,
                                                cornerRadius: 8,
                                                callbacks: {
                                                    label: function(context) {
                                                        const val = context.raw || 0;
                                                        return ' ' + context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                                    }
                                                }
                                            }
                                        },
                                        scales: {
                                            x: {
                                                grid: { display: false },
                                                ticks: { font: { size: 10.5, weight: '600' }, color: '#64748b' }
                                            },
                                            y: {
                                                grid: { color: '#f8fafc' },
                                                border: { dash: [3, 3] },
                                                beginAtZero: true,
                                                ticks: {
                                                    font: { size: 10 },
                                                    color: '#94a3b8',
                                                    maxTicksLimit: 5,
                                                    callback: function(value) {
                                                        if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(0) + 'jt';
                                                        if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                                                        return 'Rp ' + value;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            };

                            this.$nextTick(() => render());

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
                    <div style="height: 260px; position: relative;">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik Kanan (col-lg-4): Donut Sebaran Karyawan per Departemen -->
        <div class="col-lg-4 col-md-12 col-12 mb-4">
            <div class="card shadow-sm border-0 mb-0 d-flex flex-column" style="border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e9edf2 !important; height: 100%;">
                <div class="border-bottom d-flex align-items-center justify-content-between" style="background: #ffffff; padding: 14px 20px;">
                    <div>
                        <h4 class="mb-0" style="font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.35;">
                            Sebaran Departemen
                        </h4>
                        <div style="font-size: 11.5px; font-weight: 500; color: #64748b; margin-top: 2px;">
                            Distribusi headcount karyawan aktif
                        </div>
                    </div>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-between flex-grow-1"
                     wire:key="admin-dept-doughnut-{{ $selectedYear }}-{{ $selectedMonth }}-{{ md5(json_encode($stats['department_distribution'])) }}"
                     x-data="{
                        chartInstance: null,
                        initChart() {
                            const labels = {{ Js::from($stats['department_distribution']['labels']) }};
                            const counts = {{ Js::from($stats['department_distribution']['counts']) }};
                            const colors = {{ Js::from($stats['department_distribution']['colors']) }};
                            const total = {{ (int) $stats['department_distribution']['total'] }};

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
                                            data: counts,
                                            backgroundColor: colors,
                                            borderWidth: 2,
                                            borderColor: '#ffffff',
                                            hoverOffset: 4
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '74%',
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: {
                                                backgroundColor: '#0f172a',
                                                titleColor: '#ffffff',
                                                bodyColor: '#e2e8f0',
                                                padding: 8,
                                                cornerRadius: 6,
                                                callbacks: {
                                                    label: function(context) {
                                                        const val = context.raw || 0;
                                                        const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                                        return ' ' + context.label + ': ' + val + ' orang (' + pct + '%)';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            };

                            this.$nextTick(() => render());

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

                    <!-- Doughnut Canvas with Center Overlay -->
                    <div class="position-relative d-flex justify-content-center align-items-center my-2" style="height: 150px; width: 100%;">
                        <canvas x-ref="canvas" style="max-height: 150px; max-width: 150px;"></canvas>

                        <div class="position-absolute text-center d-flex flex-column justify-content-center align-items-center" style="pointer-events: none;">
                            <span style="font-size: 10px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                TOTAL
                            </span>
                            <span style="font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.15;">
                                {{ $stats['department_distribution']['total'] }}
                            </span>
                            <span style="font-size: 10px; font-weight: 500; color: #475569;">
                                Karyawan
                            </span>
                        </div>
                    </div>

                    <!-- Custom Clean Department Legend List -->
                    <div class="dept-legend-list mt-1 px-1" style="max-height: 110px; overflow-y: auto;">
                        @foreach ($this->departmentDistribution as $item)
                            <div class="d-flex align-items-center justify-content-between py-1 border-bottom" style="font-size: 11.5px; border-color: #f8fafc !important;">
                                <div class="d-flex align-items-center text-truncate mr-2">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background-color: {{ $item['color'] }}; flex-shrink: 0; margin-right: 7px;"></span>
                                    <span class="text-dark font-weight-600 text-truncate">{{ $item['label'] }}</span>
                                </div>
                                <div class="text-nowrap text-muted font-weight-500">
                                    <strong class="text-dark">{{ $item['count'] }}</strong> <span style="font-size: 10px;">({{ $item['percentage'] }}%)</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Operational Widgets (Status Penggajian Berjalan & Pengajuan Kasbon Terkini) -->
    <div class="row align-items-stretch">
        <!-- Widget Kiri: Periode Penggajian Berjalan -->
        <div class="col-lg-6 col-md-12 col-12 mb-4">
            <div class="card shadow-sm border-0 mb-0 d-flex flex-column" style="border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e9edf2 !important; height: 100%;">
                <!-- Header Card -->
                <div class="d-flex justify-content-between align-items-center" style="background: #ffffff; padding: 16px 20px; border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <h4 class="mb-0" style="font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.35;">
                            Status Penggajian
                        </h4>
                        <div style="font-size: 11.5px; font-weight: 500; color: #64748b; margin-top: 2px;">
                            Periode {{ $monthsList[$selectedMonth] }} {{ $selectedYear }} &bull; {{ $stats['payroll_total_items_count'] }} Karyawan
                        </div>
                    </div>
                    @if ($stats['current_payroll_period'])
                        @php $p = $stats['current_payroll_period']; @endphp
                        <span class="badge font-weight-600 px-3 py-1" style="font-size: 11px; border-radius: 9999px; {{ $p->status === \App\Enums\PayrollPeriodStatus::DRAFT || $p->status === \App\Enums\PayrollPeriodStatus::SUBMITTED ? 'background-color: #fefce8; color: #ca8a04; border: 1px solid #fef08a;' : ($p->status === \App\Enums\PayrollPeriodStatus::REJECTED ? 'background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;' : ($p->status === \App\Enums\PayrollPeriodStatus::APPROVED ? 'background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;' : 'background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;')) }}">
                            {{ $p->status->label() }}
                        </span>
                    @endif
                </div>

                <!-- Body Card -->
                <div class="card-body p-4 d-flex flex-column justify-content-between flex-grow-1">
                    @if ($stats['current_payroll_period'])
                        @php $p = $stats['current_payroll_period']; @endphp
                        <div>
                            <!-- Progress Verifikasi HRD (Airy & Modern) -->
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center" style="gap: 10px;">
                                        <div style="width: 32px; height: 32px; border-radius: 9px; background-color: #eff6ff; color: #0b52aa; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 12.5px; font-weight: 700; color: #0f172a; line-height: 1.2;">
                                                @if ($isDirector)
                                                    Status Verifikasi HRD
                                                @else
                                                    Progress Verifikasi HRD
                                                @endif
                                            </div>
                                            <div style="font-size: 11px; color: #64748b; line-height: 1.2; margin-top: 2px;">
                                                {{ $stats['payroll_verified_count'] }} dari {{ $stats['payroll_total_items_count'] }} slip gaji telah diverifikasi
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge font-weight-600 px-2.5 py-1" style="font-size: 11px; border-radius: 9999px; {{ $this->currentPeriodSummary['verified_percentage'] == 100 ? 'background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;' : 'background-color: #eff6ff; color: #0b52aa; border: 1px solid #bfdbfe;' }}">
                                        {{ $this->currentPeriodSummary['verified_percentage'] }}% Selesai
                                    </span>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 9999px; background-color: #f1f5f9; overflow: hidden; margin-top: 10px;">
                                    <div class="progress-bar {{ $this->currentPeriodSummary['verified_percentage'] == 100 ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $this->currentPeriodSummary['verified_percentage'] }}%; border-radius: 9999px; transition: width 0.4s ease;" aria-valuenow="{{ $this->currentPeriodSummary['verified_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>

                            <!-- 3 Mini Executive Stat Cards (Identik dengan Gambar ke-2) -->
                            <div class="row g-2" style="margin-left: -4px; margin-right: -4px;">
                                <!-- 1. Gaji Bruto -->
                                <div class="col-4" style="padding-left: 4px; padding-right: 4px;">
                                    <div class="card cash-advance-stat-card">
                                        <div class="card-body cash-advance-stat-body" style="padding: 10px 10px !important; display: flex !important; flex-direction: row !important; align-items: center !important;">
                                            <div class="cash-advance-stat-icon cash-advance-stat-icon-blue" style="display: flex !important; width: 38px !important; height: 38px !important; min-width: 38px !important; border-radius: 10px !important;">
                                                <i class="fas fa-building" style="font-size: 15px !important;"></i>
                                            </div>
                                            <div class="cash-advance-stat-content" style="margin-left: 8px !important; min-width: 0;">
                                                <div class="cash-advance-stat-title" style="font-size: 11px; margin-bottom: 1px;" title="Gaji Bruto">Gaji Bruto</div>
                                                <div class="cash-advance-stat-value-row">
                                                    <div class="cash-advance-stat-value text-primary" style="font-size: 13.5px; font-weight: 800;" title="{{ $p->formatted_total_gross_salary }}">
                                                        {{ $p->formatted_total_gross_salary }}
                                                    </div>
                                                </div>
                                                <div class="cash-advance-stat-subtext" style="font-size: 10px; color: #475569; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    {{ $stats['payroll_total_items_count'] }} penerima
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Potongan -->
                                <div class="col-4" style="padding-left: 4px; padding-right: 4px;">
                                    <div class="card cash-advance-stat-card">
                                        <div class="card-body cash-advance-stat-body" style="padding: 10px 10px !important; display: flex !important; flex-direction: row !important; align-items: center !important;">
                                            <div class="cash-advance-stat-icon cash-advance-stat-icon-red" style="display: flex !important; width: 38px !important; height: 38px !important; min-width: 38px !important; border-radius: 10px !important;">
                                                <i class="fas fa-receipt" style="font-size: 15px !important;"></i>
                                            </div>
                                            <div class="cash-advance-stat-content" style="margin-left: 8px !important; min-width: 0;">
                                                <div class="cash-advance-stat-title" style="font-size: 11px; margin-bottom: 1px;" title="Potongan">Potongan</div>
                                                <div class="cash-advance-stat-value-row">
                                                    <div class="cash-advance-stat-value text-danger" style="font-size: 13.5px; font-weight: 800;" title="-{{ $p->formatted_total_deductions }}">
                                                        -{{ $p->formatted_total_deductions }}
                                                    </div>
                                                </div>
                                                <div class="cash-advance-stat-subtext" style="font-size: 10px; color: #475569; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    PPh 21 & BPJS
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Gaji Bersih (THP) -->
                                <div class="col-4" style="padding-left: 4px; padding-right: 4px;">
                                    <div class="card cash-advance-stat-card">
                                        <div class="card-body cash-advance-stat-body" style="padding: 10px 10px !important; display: flex !important; flex-direction: row !important; align-items: center !important;">
                                            <div class="cash-advance-stat-icon cash-advance-stat-icon-green" style="display: flex !important; width: 38px !important; height: 38px !important; min-width: 38px !important; border-radius: 10px !important;">
                                                <i class="fas fa-wallet" style="font-size: 15px !important;"></i>
                                            </div>
                                            <div class="cash-advance-stat-content" style="margin-left: 8px !important; min-width: 0;">
                                                <div class="cash-advance-stat-title" style="font-size: 11px; margin-bottom: 1px;" title="Gaji Bersih (THP)">Gaji Bersih</div>
                                                <div class="cash-advance-stat-value-row">
                                                    <div class="cash-advance-stat-value text-success" style="font-size: 13.5px; font-weight: 800;" title="{{ $p->formatted_total_net_salary }}">
                                                        {{ $p->formatted_total_net_salary }}
                                                    </div>
                                                </div>
                                                <div class="cash-advance-stat-subtext" style="font-size: 10px; color: #475569; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    @if ($p->status === \App\Enums\PayrollPeriodStatus::DRAFT)
                                                        Masih dalam draf
                                                    @elseif ($p->status === \App\Enums\PayrollPeriodStatus::SUBMITTED)
                                                        Menunggu persetujuan Direktur
                                                    @elseif ($p->status === \App\Enums\PayrollPeriodStatus::APPROVED)
                                                        Siap diproses pembayaran
                                                    @elseif ($p->status === \App\Enums\PayrollPeriodStatus::REJECTED)
                                                        Perlu direvisi
                                                    @else
                                                        Sudah dibayar
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <div class="d-inline-flex align-items-center justify-content-center mb-2" style="width: 44px; height: 44px; border-radius: 12px; background-color: #f8fafc; color: #94a3b8;">
                                <i class="far fa-calendar-times" style="font-size: 20px;"></i>
                            </div>
                            <h6 class="font-weight-bold text-dark mb-1" style="font-size: 13.5px;">Periode Belum Dibuat</h6>
                            <p class="small text-muted mb-3" style="font-size: 11.5px;">Belum ada draf penggajian untuk bulan {{ $monthsList[$selectedMonth] }} {{ $selectedYear }}.</p>
                            <a href="{{ route('payrolls.index') }}" class="btn btn-outline-primary btn-sm font-weight-600 px-3 py-1" style="border-radius: 8px; font-size: 11.5px;">
                                @if ($isDirector)
                                    Lihat Daftar Periode
                                @else
                                    Kelola Daftar Periode
                                @endif
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Footer Card -->
                @if ($stats['current_payroll_period'])
                    <div style="border-top: 1px solid #f1f5f9; padding: 13px 20px; background-color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                        <div class="d-flex align-items-center text-muted" style="font-size: 11.5px;">
                            <i class="fas fa-file-invoice-dollar text-primary mr-1.5" style="font-size: 13px;"></i>
                            @if ($isDirector && $p->status === \App\Enums\PayrollPeriodStatus::SUBMITTED)
                                <span>Penggajian menunggu persetujuan Direktur</span>
                            @elseif ($isDirector && $p->status === \App\Enums\PayrollPeriodStatus::APPROVED)
                                <span>Penggajian sudah disetujui</span>
                            @elseif ($p->status === \App\Enums\PayrollPeriodStatus::APPROVED)
                                <span>Disetujui Direktur &bull; siap diproses pembayarannya</span>
                            @elseif ($p->status === \App\Enums\PayrollPeriodStatus::REJECTED)
                                <span>Perlu revisi sebelum diajukan kembali</span>
                            @else
                                <span>{{ $stats['payroll_total_items_count'] - $stats['payroll_verified_count'] > 0 ? ($stats['payroll_total_items_count'] - $stats['payroll_verified_count']) . ' slip belum diverifikasi' : 'Semua slip terverifikasi' }}</span>
                            @endif
                        </div>
                        <a href="{{ route('payrolls.show', $p->id) }}" class="btn btn-primary font-weight-600 px-3 py-1.5 shadow-xs d-inline-flex align-items-center" style="border-radius: 8px; font-size: 12px; background-color: #0b52aa; border-color: #0b52aa; gap: 6px;">
                            <span>
                                @if ($isDirector && $p->status === \App\Enums\PayrollPeriodStatus::SUBMITTED)
                                    Tinjau Penggajian
                                @elseif ($isDirector)
                                    Lihat Rincian
                                @elseif ($p->status === \App\Enums\PayrollPeriodStatus::DRAFT && $stats['payroll_total_items_count'] > $stats['payroll_verified_count'])
                                    Lanjutkan Verifikasi
                                @elseif ($p->status === \App\Enums\PayrollPeriodStatus::REJECTED)
                                    Periksa Revisi
                                @elseif ($p->status === \App\Enums\PayrollPeriodStatus::APPROVED)
                                    Proses Pembayaran
                                @else
                                    Buka Rincian Penggajian
                                @endif
                            </span>
                            <i class="fas fa-arrow-right" style="font-size: 10px;"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Widget Kanan: Pengajuan Kasbon Terkini -->
        <div class="col-lg-6 col-md-12 col-12 mb-4">
            <div class="card shadow-sm border-0 mb-0 d-flex flex-column" style="border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e9edf2 !important; height: 100%;">
                <div class="d-flex justify-content-between align-items-center" style="background: #ffffff; padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <h4 class="mb-0" style="font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.35;">
                            Pengajuan Kasbon Terkini
                        </h4>
                        <div style="font-size: 11.5px; font-weight: 500; color: #64748b; margin-top: 2px;">Sisa Kasbon Aktif: {{ $stats['formatted_remaining_cash_advance'] }} &bull; {{ $stats['active_cash_advance_borrowers_count'] }} peminjam</div>
                    </div>
                    <a href="{{ route('cash-advances.index') }}" class="text-primary font-weight-600 text-decoration-none" style="font-size: 11.5px;">
                        Lihat Semua <i class="fas fa-chevron-right ml-0.5" style="font-size: 9px;"></i>
                    </a>
                </div>
                <div class="card-body p-0 flex-grow-1">
                    @if ($stats['recent_cash_advances']->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-clean mb-0" style="font-size: 12px;">
                                <tbody>
                                    @foreach ($stats['recent_cash_advances'] as $ca)
                                        <tr style="border-bottom: 1px solid #f8fafc;">
                                            <td class="align-middle !py-2.5 !px-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="mr-2.5 text-center font-weight-bold" style="width: 32px; height: 32px; border-radius: 8px; background-color: #eff6ff; color: #0b52aa; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                                        <i class="fas fa-hand-holding-usd"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-weight-700 text-dark leading-tight" style="font-size: 12px;">{{ $ca->employee?->full_name ?? 'Karyawan' }}</div>
                                                        <div class="text-muted leading-tight mt-0.5" style="font-size: 10.5px;">
                                                            {{ $ca->employee?->department?->name ?? 'Staff' }} &bull; {{ $ca->created_at->format('d M Y') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle text-right !py-2.5 !px-3 font-weight-700 text-dark" style="font-size: 12px;">
                                                Rp {{ number_format((float) $ca->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="align-middle text-center !py-2.5 !px-3 text-nowrap">
                                                <span class="{{ $ca->status->badgeClass() }} py-0.5 px-2 font-weight-600" style="font-size: 10px; border-radius: 12px;">
                                                    {{ $ca->status->label() }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <div class="d-inline-flex align-items-center justify-content-center mb-2" style="width: 44px; height: 44px; border-radius: 12px; background-color: #f8fafc; color: #94a3b8;">
                                <i class="fas fa-inbox" style="font-size: 20px;"></i>
                            </div>
                            <h6 class="font-weight-bold text-dark mb-1" style="font-size: 13.5px;">Tidak Ada Kasbon</h6>
                            <p class="small text-muted mb-0" style="font-size: 11.5px;">Belum ada catatan pengajuan kasbon saat ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal List Karyawan Lembur Periode Minggu Ini (Sabtu s/d Jumat) -->
    <div wire:ignore.self class="modal fade" id="weeklyOvertimeModal" tabindex="-1" role="dialog" aria-labelledby="weeklyOvertimeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
                <div class="modal-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-2.5 text-center" style="width: 34px; height: 34px; border-radius: 8px; background-color: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                            <i class="fas fa-business-time"></i>
                        </div>
                        <div>
                            <h5 class="modal-title text-dark mb-0" id="weeklyOvertimeModalLabel" style="font-weight: 500; font-size: 15px;">
                                Daftar Lembur Karyawan (Periode Minggu Ini)
                            </h5>
                            <span class="text-muted" style="font-size: 11.5px;">
                                Siklus Sabtu s/d Jumat: {{ $stats['weekly_period_label'] }}
                            </span>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-3 px-4 bg-white" style="max-height: 70vh; overflow-y: auto;">
                    @if ($stats['weekly_overtime_grouped_by_date']->isNotEmpty())
                        @foreach ($stats['weekly_overtime_grouped_by_date'] as $dateLabel => $submissions)
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <i class="far fa-calendar-alt text-primary mr-2" style="font-size: 13px;"></i>
                                        <span class="text-dark" style="font-weight: 600; font-size: 13px;">{{ $dateLabel }}</span>
                                    </div>
                                    <span class="badge badge-light border text-dark" style="font-size: 11px; font-weight: 500;">
                                        {{ $submissions->count() }} Data Lembur ({{ $submissions->pluck('employee_id')->unique()->count() }} Orang)
                                    </span>
                                </div>
                                <div class="table-responsive mb-2">
                                    <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
                                        <thead>
                                            <tr style="color: #475569; background-color: #f8fafc;">
                                                <th class="py-2 px-3 border-top-0">Karyawan</th>
                                                <th class="py-2 px-3 border-top-0">Jam Lembur</th>
                                                <th class="py-2 px-3 border-top-0">Durasi</th>
                                                <th class="py-2 px-3 border-top-0">Alasan / Tugas</th>
                                                <th class="py-2 px-3 border-top-0 text-center">Status</th>
                                                <th class="py-2 px-3 border-top-0 text-right">Upah Lembur</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($submissions as $sub)
                                                <tr>
                                                    <td class="py-2.5 px-3 align-middle">
                                                        <div class="text-dark" style="font-weight: 600;">{{ $sub->employee?->full_name ?? 'Karyawan' }}</div>
                                                        <div class="text-muted" style="font-size: 10.5px;">
                                                            {{ $sub->employee?->position?->name ?? 'Staff' }} &bull; {{ $sub->employee?->department?->name ?? 'Umum' }}
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5 px-3 align-middle text-muted">
                                                        @if ($sub->start_time && $sub->end_time)
                                                            <i class="far fa-clock mr-1"></i> {{ substr($sub->start_time, 0, 5) }} - {{ substr($sub->end_time, 0, 5) }}
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-2.5 px-3 align-middle text-dark" style="font-weight: 500;">
                                                        {{ number_format((float) $sub->duration_hours, 1) }} Jam
                                                    </td>
                                                    <td class="py-2.5 px-3 align-middle text-dark" style="max-width: 200px;">
                                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $sub->reason ?? '-' }}">
                                                            {{ $sub->reason ?? '-' }}
                                                        </span>
                                                    </td>
                                                    <td class="py-2.5 px-3 align-middle text-center">
                                                        @if ($sub->status->value === 'approved')
                                                            <span class="badge badge-success" style="font-size: 10px; font-weight: 500;">Disetujui</span>
                                                        @elseif ($sub->status->value === 'pending_admin')
                                                            <span class="badge badge-warning" style="font-size: 10px; font-weight: 500;">Review Admin</span>
                                                        @elseif ($sub->status->value === 'pending_direktur')
                                                            <span class="badge badge-info" style="font-size: 10px; font-weight: 500;">Review Direktur</span>
                                                        @else
                                                            <span class="badge badge-secondary" style="font-size: 10px; font-weight: 500;">{{ $sub->status->value }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-2.5 px-3 align-middle text-right text-dark" style="font-weight: 600;">
                                                        Rp {{ number_format((float) $sub->total_amount, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <div class="d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px; border-radius: 12px; background-color: #f8fafc; color: #94a3b8;">
                                <i class="fas fa-business-time" style="font-size: 22px;"></i>
                            </div>
                            <h6 class="font-weight-bold text-dark mb-1" style="font-size: 13.5px;">Tidak Ada Catatan Lembur</h6>
                            <p class="text-muted mb-0" style="font-size: 12px;">Belum ada karyawan yang lembur pada periode minggu ini ({{ $stats['weekly_period_label'] }}).</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-white border-top px-4 py-2.5 d-flex justify-content-between align-items-center">
                    <div class="text-muted" style="font-size: 11.5px;">
                        Total Karyawan Lembur: <strong class="text-dark">{{ $stats['weekly_overtime_employees_count'] }} Orang</strong>
                    </div>
                    <button type="button" class="btn btn-secondary shadow-none btn-sm px-3" data-dismiss="modal" style="font-weight: 500;">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Standard Stisla Section Title & Lead Alignment (No Overlap) */
    .section .section-title {
        margin-top: 0 !important;
        margin-bottom: 4px !important;
    }
    .section .section-title + .section-lead,
    .section .section-lead {
        margin-top: 0 !important;
        margin-bottom: 12px !important;
        margin-left: 45px !important;
        font-size: 13px !important;
        color: #475569 !important;
    }

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
            gap: 10px !important;
        }
        .dashboard-8kpi-grid .card {
            margin: 0 !important;
            margin-bottom: 0 !important;
            border-radius: 12px !important;
        }
        .dashboard-8kpi-grid .card-body {
            padding: 9px 8px !important;
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
            display: none !important;
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

    /* Bootstrap Callout (.bd-callout) Component */
    .bd-callout {
        padding: 0.85rem 1rem !important;
        margin-top: 0 !important;
        margin-bottom: 1.25rem !important;
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-left-width: 4px !important;
        border-radius: 8px !important;
        box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.04) !important;
        transition: all 0.2s ease-in-out !important;
    }

    .bd-callout-warning {
        border-left-color: #f59e0b !important;
        background: linear-gradient(90deg, rgba(245, 158, 11, 0.08) 0%, rgba(255, 255, 255, 0.98) 20%, #ffffff 100%) !important;
    }

    .bd-callout .bd-callout-icon {
        font-size: 18px !important;
        line-height: 1 !important;
        flex-shrink: 0 !important;
    }

    .bd-callout-warning .bd-callout-icon {
        color: #d97706 !important;
    }

    .bd-callout .bd-callout-title {
        font-size: 13.5px !important;
        font-weight: 700 !important;
        line-height: 1.35 !important;
        margin-bottom: 2px !important;
        letter-spacing: -0.15px !important;
    }

    .bd-callout-warning .bd-callout-title {
        color: #b45309 !important;
    }

    .bd-callout .bd-callout-desc {
        font-size: 12px !important;
        color: #475569 !important;
        line-height: 1.45 !important;
        margin-bottom: 0 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
