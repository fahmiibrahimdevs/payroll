<div>
    <!-- Top Bar: Title & Clean Filter Controls -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4" style="gap: 14px;">
        <div>
            <h2 class="section-title my-0" style="font-size: 19px; font-weight: 700; color: #0f172a; letter-spacing: -0.3px;">
                Dashboard Utama
            </h2>
            <p class="section-lead mb-0 text-muted" style="font-size: 13px; margin-top: 2px;">
                Pantau metrik karyawan, operasional penggajian, dan kasbon secara real-time.
            </p>
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

    <!-- 4 Modern Stat Cards (Desain Clean & Identik Gambar ke-1) -->
    <div class="payroll-stat-grid mb-4">
        <!-- 1. Total Karyawan Aktif -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Karyawan Aktif">Total karyawan aktif</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-primary" title="{{ $stats['active_employees_count'] }} Orang">
                            {{ number_format($stats['active_employees_count']) }} <span style="font-size: 13px; font-weight: 600; color: #64748b;">Orang</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-user-check text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $stats['permanent_count'] }} Tetap &bull; {{ $stats['contract_count'] }} Kontrak &bull; {{ $stats['intern_count'] }} Magang
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Gaji Bersih (THP) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Gaji Bersih">Total gaji bersih</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-success" title="{{ $stats['formatted_net_salary'] }}">
                            {{ $stats['formatted_net_salary'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-money-bill-wave text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        @if ($stats['current_payroll_period'])
                            {{ $monthsList[$selectedMonth] }} {{ $selectedYear }} &bull; {{ $stats['current_payroll_period']->status->label() }} ({{ $stats['payroll_verified_count'] }}/{{ $stats['payroll_total_items_count'] }} Verif)
                        @else
                            {{ $monthsList[$selectedMonth] }} {{ $selectedYear }} &bull; Belum ada periode
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Setoran Pajak PPh 21 -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-red">
                    <i class="fas fa-landmark"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Setoran Pajak PPh 21">Total setoran PPh 21</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-danger" title="{{ $stats['formatted_pph21'] }}">
                            {{ $stats['formatted_pph21'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-red">
                            <i class="fas fa-file-invoice-dollar text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        @if ($stats['current_payroll_period'])
                            Kewajiban setor &bull; {{ $stats['pph21_employee_count'] }} karyawan
                        @else
                            PPh 21 bulan {{ $monthsList[$selectedMonth] }} &bull; Rp 0
                        @endif
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
                    <div class="cash-advance-stat-title" title="Sisa Kasbon Belum Lunas">Sisa kasbon aktif</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-warning" title="{{ $stats['formatted_remaining_cash_advance'] }}">
                            {{ $stats['formatted_remaining_cash_advance'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-receipt text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Belum lunas &bull; {{ $stats['active_cash_advance_borrowers_count'] }} orang peminjam
                    </div>
                </div>
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
                            Perbandingan Gaji Kotor, Gaji Bersih, dan PPh 21 tahun {{ $selectedYear }}
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
                     x-data="{
                        chartInstance: null,
                        initChart() {
                            const labels = {{ Js::from($stats['payroll_trend']['labels']) }};
                            const netData = {{ Js::from($stats['payroll_trend']['net']) }};
                            const grossData = {{ Js::from($stats['payroll_trend']['gross']) }};
                            const taxData = {{ Js::from($stats['payroll_trend']['tax']) }};

                            const render = () => {
                                const canvas = document.getElementById('adminPayrollTrendCanvas-{{ $selectedYear }}');
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
                        <canvas id="adminPayrollTrendCanvas-{{ $selectedYear }}"></canvas>
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
                     x-data="{
                        chartInstance: null,
                        initChart() {
                            const labels = {{ Js::from($stats['department_distribution']['labels']) }};
                            const counts = {{ Js::from($stats['department_distribution']['counts']) }};
                            const colors = {{ Js::from($stats['department_distribution']['colors']) }};
                            const total = {{ (int) $stats['department_distribution']['total'] }};

                            const render = () => {
                                const canvas = document.getElementById('adminDeptDoughnutCanvas');
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
                        <canvas id="adminDeptDoughnutCanvas" style="max-height: 150px; max-width: 150px;"></canvas>

                        <div class="position-absolute text-center d-flex flex-column justify-content-center align-items-center" style="pointer-events: none;">
                            <span style="font-size: 10px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                TOTAL
                            </span>
                            <span style="font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.15;">
                                {{ $stats['department_distribution']['total'] }}
                            </span>
                            <span style="font-size: 10px; font-weight: 500; color: #94a3b8;">
                                Karyawan
                            </span>
                        </div>
                    </div>

                    <!-- Custom Clean Department Legend List -->
                    <div class="dept-legend-list mt-1 px-1" style="max-height: 110px; overflow-y: auto;">
                        @foreach ($stats['department_distribution']['labels'] as $idx => $deptName)
                            @php
                                $c = $stats['department_distribution']['counts'][$idx] ?? 0;
                                $color = $stats['department_distribution']['colors'][$idx] ?? '#0b52aa';
                                $pct = $stats['department_distribution']['total'] > 0 ? round(($c / $stats['department_distribution']['total']) * 100, 1) : 0;
                            @endphp
                            <div class="d-flex align-items-center justify-content-between py-1 border-bottom" style="font-size: 11.5px; border-color: #f8fafc !important;">
                                <div class="d-flex align-items-center text-truncate mr-2">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background-color: {{ $color }}; flex-shrink: 0; margin-right: 7px;"></span>
                                    <span class="text-dark font-weight-600 text-truncate">{{ $deptName }}</span>
                                </div>
                                <div class="text-nowrap text-muted font-weight-500">
                                    <strong class="text-dark">{{ $c }}</strong> <span style="font-size: 10px;">({{ $pct }}%)</span>
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
                        <span class="badge font-weight-600 px-3 py-1" style="font-size: 11px; border-radius: 9999px; {{ $p->status->isEditable() ? 'background-color: #fefce8; color: #ca8a04; border: 1px solid #fef08a;' : ($p->status === \App\Enums\PayrollPeriodStatus::APPROVED ? 'background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;' : 'background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;') }}">
                            {{ $p->status->label() }}
                        </span>
                    @endif
                </div>

                <!-- Body Card -->
                <div class="card-body p-4 d-flex flex-column justify-content-between flex-grow-1">
                    @if ($stats['current_payroll_period'])
                        @php
                            $p = $stats['current_payroll_period'];
                            $pctVerified = $stats['payroll_total_items_count'] > 0 ? round(($stats['payroll_verified_count'] / $stats['payroll_total_items_count']) * 100) : 0;
                        @endphp
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
                                                Progress Verifikasi HRD
                                            </div>
                                            <div style="font-size: 11px; color: #64748b; line-height: 1.2; margin-top: 2px;">
                                                {{ $stats['payroll_verified_count'] }} dari {{ $stats['payroll_total_items_count'] }} slip gaji telah diverifikasi
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge font-weight-600 px-2.5 py-1" style="font-size: 11px; border-radius: 9999px; {{ $pctVerified == 100 ? 'background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;' : 'background-color: #eff6ff; color: #0b52aa; border: 1px solid #bfdbfe;' }}">
                                        {{ $pctVerified }}% Selesai
                                    </span>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 9999px; background-color: #f1f5f9; overflow: hidden; margin-top: 10px;">
                                    <div class="progress-bar {{ $pctVerified == 100 ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $pctVerified }}%; border-radius: 9999px; transition: width 0.4s ease;" aria-valuenow="{{ $pctVerified }}" aria-valuemin="0" aria-valuemax="100"></div>
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
                                                <div class="cash-advance-stat-subtext" style="font-size: 10px; color: #94a3b8; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
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
                                                <div class="cash-advance-stat-subtext" style="font-size: 10px; color: #94a3b8; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
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
                                                <div class="cash-advance-stat-subtext" style="font-size: 10px; color: #94a3b8; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    Siap ditransfer
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
                                Kelola Daftar Periode
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Footer Card -->
                @if ($stats['current_payroll_period'])
                    <div style="border-top: 1px solid #f1f5f9; padding: 13px 20px; background-color: #fafbfc; display: flex; align-items: center; justify-content: space-between;">
                        <div class="d-flex align-items-center text-muted" style="font-size: 11.5px;">
                            <i class="fas fa-file-invoice-dollar text-primary mr-1.5" style="font-size: 13px;"></i>
                            <span>{{ $stats['payroll_total_items_count'] - $stats['payroll_verified_count'] > 0 ? ($stats['payroll_total_items_count'] - $stats['payroll_verified_count']) . ' slip belum diverifikasi' : 'Semua slip terverifikasi' }}</span>
                        </div>
                        <a href="{{ route('payrolls.show', $p->id) }}" class="btn btn-primary font-weight-600 px-3 py-1.5 shadow-xs d-inline-flex align-items-center" style="border-radius: 8px; font-size: 12px; background-color: #0b52aa; border-color: #0b52aa; gap: 6px;">
                            <span>Buka Rincian Penggajian</span>
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
                        <div style="font-size: 11.5px; font-weight: 500; color: #64748b; margin-top: 2px;">
                            Catatan pengajuan pinjaman karyawan
                        </div>
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
</div>

@push('styles')
<style>
    .payroll-stat-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .payroll-stat-grid .card {
        margin-bottom: 0 !important;
    }
    @media (min-width: 576px) {
        .payroll-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
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
