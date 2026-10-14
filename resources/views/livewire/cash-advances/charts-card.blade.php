<!-- ================= 1 CARD BERISI 3 ANALISIS KASBON (DESAIN EKSEKUTIF SEPERTI HALAMAN PENGGAJIAN) ================= -->
<div class="card shadow-sm border-0 mb-0" style="border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e9edf2;">
    <!-- 1. Header Card Bersih & Konsisten -->
    <div class="border-bottom d-flex align-items-center justify-content-between" style="background: #ffffff; padding: 14px 20px;">
        <div>
            <h4 class="mb-0" style="font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.35;">
                Analisis Kasbon
            </h4>
            <div style="font-size: 11.5px; font-weight: 500; color: #64748b; margin-top: 2px;">
                Tren & sebaran pinjaman aktif
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- 2. Seksi 1: Kasbon Diberikan vs Dibayar (Bar Chart) -->
        <div style="padding: 16px 20px 14px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div style="font-size: 12px; font-weight: 700; color: #334155;">
                    Diberikan vs Dibayar
                </div>
                <span class="badge border font-weight-600 px-2 py-0.5" style="font-size: 10px; border-radius: 6px; background-color: #f8fafc; color: #64748b; border-color: #e2e8f0;">
                    6 bulan terakhir
                </span>
            </div>

            <!-- Bar Chart Container -->
            <div wire:key="ca-monthly-bar-chart"
                 x-data="{
                     chartInstance: null,
                     initChart() {
                         const labels = @js($chartData['monthly']['labels'] ?? []);
                         const disbursed = @js($chartData['monthly']['disbursed'] ?? []);
                         const paid = @js($chartData['monthly']['paid'] ?? []);

                         const render = () => {
                             const canvas = this.$refs.canvas;
                             if (!canvas) return;
                             if (typeof Chart === 'undefined') {
                                 setTimeout(render, 100);
                                 return;
                             }
                             const ctx = canvas.getContext('2d');
                             if (!ctx) return;

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
                                             label: 'Diberikan',
                                             data: disbursed,
                                             backgroundColor: '#0b52aa',
                                             borderRadius: 4,
                                             barPercentage: 0.65,
                                             categoryPercentage: 0.75,
                                         },
                                         {
                                             label: 'Dibayar',
                                             data: paid,
                                             backgroundColor: '#10b981',
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
                                             enabled: false,
                                             external: function(ctx) {
                                                 if (typeof window.customChartTooltip === 'function') {
                                                     window.customChartTooltip(ctx);
                                                 }
                                             },
                                             callbacks: {
                                                 title: function(items) {
                                                     return items && items.length ? items[0].label : '';
                                                 },
                                                 label: function(context) {
                                                     const dsLabel = (context.dataset && context.dataset.label) ? context.dataset.label + ': ' : '';
                                                     const val = context.raw || 0;
                                                     return dsLabel + 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                                 }
                                             }
                                         }
                                     },
                                     scales: {
                                         x: {
                                             grid: { display: false },
                                             ticks: { font: { size: 9 }, color: '#64748b' }
                                         },
                                         y: {
                                             grid: { color: '#f8fafc' },
                                             border: { dash: [3, 3] },
                                             beginAtZero: true,
                                             ticks: {
                                                 font: { size: 8.5 },
                                                 color: '#94a3b8',
                                                 maxTicksLimit: 4,
                                                 callback: function(v) {
                                                     if (v >= 1000000) return (v / 1000000).toFixed(v % 1000000 === 0 ? 0 : 1) + ' jt';
                                                     if (v >= 1000) return (v / 1000).toFixed(0) + ' rb';
                                                     return v;
                                                 }
                                             }
                                         }
                                     }
                                 }
                             });
                         };

                         this.$nextTick(() => { render(); });
                         if (typeof this.$cleanup === 'function') {
                             this.$cleanup(() => {
                                 if (this.chartInstance) {
                                     try { this.chartInstance.destroy(); } catch (e) {}
                                     this.chartInstance = null;
                                 }
                                 const tip = this.$el ? this.$el.querySelector('.custom-chartjs-tooltip') : null;
                                 if (tip) tip.remove();
                             });
                         }
                     }
                 }"
                 x-init="initChart()"
                 class="position-relative w-100"
                 style="height: 120px;">
                <canvas x-ref="canvas"></canvas>
            </div>

            <!-- Legend Bulat Minimalis -->
            <div class="d-flex justify-content-center align-items-center mt-2" style="gap: 16px; font-size: 11px; color: #64748b;">
                <span class="d-flex align-items-center">
                    <span style="width: 7px; height: 7px; border-radius: 2px; background-color: #0b52aa; margin-right: 6px; display: inline-block;"></span>
                    Diberikan
                </span>
                <span class="d-flex align-items-center">
                    <span style="width: 7px; height: 7px; border-radius: 2px; background-color: #10b981; margin-right: 6px; display: inline-block;"></span>
                    Dibayar
                </span>
            </div>
        </div>

        <!-- 3. Seksi 2: Top 5 Kasbon Terbesar (Donut Chart dengan Center Text Overlay) -->
        <div style="border-top: 1px solid #f1f5f9; padding: 16px 20px 14px;">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <div style="font-size: 12px; font-weight: 700; color: #334155;">
                    Top 5 Kasbon Terbesar
                </div>
                <span class="badge border font-weight-600 px-2 py-0.5" style="font-size: 10px; border-radius: 6px; background-color: #f8fafc; color: #64748b; border-color: #e2e8f0;">
                    Sisa saldo
                </span>
            </div>

            <!-- Donut Chart Container -->
            <div wire:key="ca-top-debtors-donut"
                 x-data="{
                     chartInstance: null,
                     initChart() {
                         const hasData = @js($chartData['top_debtors']['has_data'] ?? false);
                         const labels = hasData ? @js($chartData['top_debtors']['labels'] ?? []) : ['Belum ada kasbon'];
                         const dataVals = hasData ? @js($chartData['top_debtors']['data'] ?? []) : [1];
                         const colors = hasData ? @js($chartData['top_debtors']['colors'] ?? []) : ['#e2e8f0'];

                         const render = () => {
                             const canvas = this.$refs.canvas;
                             if (!canvas) return;
                             if (typeof Chart === 'undefined') {
                                 setTimeout(render, 100);
                                 return;
                             }
                             const ctx = canvas.getContext('2d');
                             if (!ctx) return;

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
                                         backgroundColor: colors,
                                         hoverBackgroundColor: colors,
                                         borderWidth: hasData ? 2 : 0,
                                         borderColor: '#ffffff',
                                         hoverOffset: hasData ? 4 : 0
                                     }]
                                 },
                                 options: {
                                     responsive: true,
                                     maintainAspectRatio: false,
                                     cutout: '72%',
                                     animation: { duration: 400 },
                                     plugins: {
                                         legend: { display: false },
                                         tooltip: {
                                             enabled: false,
                                             external: function(ctx) {
                                                 if (typeof window.customChartTooltip === 'function') {
                                                     window.customChartTooltip(ctx);
                                                 }
                                             },
                                             callbacks: {
                                                 title: function(items) {
                                                     return items && items.length ? items[0].label : '';
                                                 },
                                                 label: function(context) {
                                                     const val = Number(context.raw) || 0;
                                                     const dataset = context.dataset || {};
                                                     const total = (dataset.data || []).reduce((a, b) => a + (Number(b) || 0), 0);
                                                     const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                                     return 'Sisa: Rp ' + new Intl.NumberFormat('id-ID').format(val) + ' (' + pct + '%)';
                                                 }
                                             }
                                         }
                                     }
                                 }
                             });
                         };

                         this.$nextTick(() => { render(); });
                         if (typeof this.$cleanup === 'function') {
                             this.$cleanup(() => {
                                 if (this.chartInstance) {
                                     try { this.chartInstance.destroy(); } catch (e) {}
                                     this.chartInstance = null;
                                 }
                                 const tip = this.$el ? this.$el.querySelector('.custom-chartjs-tooltip') : null;
                                 if (tip) tip.remove();
                             });
                         }
                     }
                 }"
                 x-init="initChart()"
                 class="position-relative d-flex justify-content-center align-items-center my-2"
                 style="height: 140px; width: 100%;">

                <canvas x-ref="canvas" style="max-height: 140px; max-width: 140px;"></canvas>

                <!-- Center Text Overlay -->
                <div class="chart-center-overlay position-absolute text-center d-flex flex-column justify-content-center align-items-center" style="pointer-events: none; max-width: 105px; transition: opacity 0.2s ease;">
                    <span style="font-size: 9.5px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                        TOTAL SISA
                    </span>
                    <span style="font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1.25; margin-top: 2px;">
                        {{ $companyStats['compact_total_remaining_balance'] ?? ($companyStats['formatted_total_remaining_balance'] ?? 'Rp 0') }}
                    </span>
                </div>
            </div>

            <!-- Breakdown List: Top 5 Karyawan -->
            <div class="d-flex flex-column mt-2" style="gap: 4px;">
                @if (!empty($chartData['top_debtors']['labels']) && ($chartData['top_debtors']['has_data'] ?? false))
                    @php
                        $topTotal = array_sum($chartData['top_debtors']['data']);
                    @endphp
                    @foreach ($chartData['top_debtors']['labels'] as $idx => $debtorLabel)
                        @php
                            $dVal = $chartData['top_debtors']['data'][$idx] ?? 0;
                            $dPct = $topTotal > 0 ? round(($dVal / $topTotal) * 100, 1) : 0;
                            $dColor = $chartData['top_debtors']['colors'][$idx] ?? '#0b52aa';
                        @endphp
                        <div class="d-flex align-items-center justify-content-between py-1 px-1 rounded" style="font-size: 11.5px; line-height: 1.4; transition: background 0.15s ease;">
                            <div class="d-flex align-items-center text-truncate mr-2">
                                <span style="width: 8px; height: 8px; border-radius: 2px; background-color: {{ $dColor }}; margin-right: 7px; flex-shrink: 0;"></span>
                                <span class="text-truncate" style="color: #475569; font-weight: 500;" title="{{ $debtorLabel }}">{{ $debtorLabel }}</span>
                            </div>
                            <span style="font-weight: 600; color: #0f172a; flex-shrink: 0; font-variant-numeric: tabular-nums;">
                                Rp {{ number_format($dVal, 0, ',', '.') }} · {{ $dPct }}%
                            </span>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-2 text-muted" style="font-size: 11px;">
                        Belum ada kasbon aktif
                    </div>
                @endif
            </div>
        </div>

        <!-- 4. Seksi 3: Distribusi per Departemen (Donut Chart dengan Center Text Overlay) -->
        <div style="border-top: 1px solid #f1f5f9; padding: 16px 20px 18px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div style="font-size: 12px; font-weight: 700; color: #334155;">
                    Distribusi Departemen
                </div>
                <span class="badge border font-weight-600 px-2 py-0.5" style="font-size: 10px; border-radius: 6px; background-color: #f8fafc; color: #64748b; border-color: #e2e8f0;">
                    Kasbon aktif
                </span>
            </div>

            <!-- Donut Chart Container -->
            <div wire:key="ca-dept-donut"
                 x-data="{
                     chartInstance: null,
                     initChart() {
                         const hasData = @js($chartData['departments']['has_data'] ?? false);
                         const labels = hasData ? @js($chartData['departments']['labels'] ?? []) : ['Belum ada kasbon'];
                         const dataVals = hasData ? @js($chartData['departments']['data'] ?? []) : [1];
                         const colors = hasData ? @js($chartData['departments']['colors'] ?? []) : ['#e2e8f0'];

                         const render = () => {
                             const canvas = this.$refs.canvas;
                             if (!canvas) return;
                             if (typeof Chart === 'undefined') {
                                 setTimeout(render, 100);
                                 return;
                             }
                             const ctx = canvas.getContext('2d');
                             if (!ctx) return;

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
                                         backgroundColor: colors,
                                         hoverBackgroundColor: colors,
                                         borderWidth: hasData ? 2 : 0,
                                         borderColor: '#ffffff',
                                         hoverOffset: hasData ? 4 : 0
                                     }]
                                 },
                                 options: {
                                     responsive: true,
                                     maintainAspectRatio: false,
                                     cutout: '72%',
                                     animation: { duration: 400 },
                                     plugins: {
                                         legend: { display: false },
                                         tooltip: {
                                             enabled: false,
                                             external: function(ctx) {
                                                 if (typeof window.customChartTooltip === 'function') {
                                                     window.customChartTooltip(ctx);
                                                 }
                                             },
                                             callbacks: {
                                                 title: function(items) {
                                                     return items && items.length ? items[0].label : '';
                                                 },
                                                 label: function(context) {
                                                     const val = Number(context.raw) || 0;
                                                     const dataset = context.dataset || {};
                                                     const total = (dataset.data || []).reduce((a, b) => a + (Number(b) || 0), 0);
                                                     const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                                     return 'Total: Rp ' + new Intl.NumberFormat('id-ID').format(val) + ' (' + pct + '%)';
                                                 }
                                             }
                                         }
                                     }
                                 }
                             });
                         };

                         this.$nextTick(() => { render(); });
                         if (typeof this.$cleanup === 'function') {
                             this.$cleanup(() => {
                                 if (this.chartInstance) {
                                     try { this.chartInstance.destroy(); } catch (e) {}
                                     this.chartInstance = null;
                                 }
                                 const tip = this.$el ? this.$el.querySelector('.custom-chartjs-tooltip') : null;
                                 if (tip) tip.remove();
                             });
                         }
                     }
                 }"
                 x-init="initChart()"
                 class="position-relative d-flex justify-content-center align-items-center my-2"
                 style="height: 135px; width: 100%;">

                <canvas x-ref="canvas" style="max-height: 135px; max-width: 135px;"></canvas>

                <!-- Center Text Overlay -->
                <div class="chart-center-overlay position-absolute text-center d-flex flex-column justify-content-center align-items-center" style="pointer-events: none; max-width: 95px; transition: opacity 0.2s ease;">
                    <span style="font-size: 9.5px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                        DEPARTEMEN
                    </span>
                    <span style="font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.25; margin-top: 2px;">
                        {{ count($chartData['departments']['labels'] ?? []) }} Dept
                    </span>
                </div>
            </div>

            <!-- Breakdown List: Departemen -->
            <div class="d-flex flex-column mt-2" style="gap: 4px;">
                @if (!empty($chartData['departments']['labels']) && ($chartData['departments']['has_data'] ?? false))
                    @php
                        $deptTotal = array_sum($chartData['departments']['data']);
                    @endphp
                    @foreach ($chartData['departments']['labels'] as $idx => $deptLabel)
                        @php
                            $deptVal = $chartData['departments']['data'][$idx] ?? 0;
                            $deptPct = $deptTotal > 0 ? round(($deptVal / $deptTotal) * 100, 1) : 0;
                            $deptColor = $chartData['departments']['colors'][$idx] ?? '#0b52aa';
                        @endphp
                        <div class="d-flex align-items-center justify-content-between py-1 px-1 rounded" style="font-size: 11.5px; line-height: 1.4; transition: background 0.15s ease;">
                            <div class="d-flex align-items-center text-truncate mr-2">
                                <span style="width: 8px; height: 8px; border-radius: 2px; background-color: {{ $deptColor }}; margin-right: 7px; flex-shrink: 0;"></span>
                                <span class="text-truncate" style="color: #475569; font-weight: 500;" title="{{ $deptLabel }}">{{ $deptLabel }}</span>
                            </div>
                            <span style="font-weight: 600; color: #0f172a; flex-shrink: 0; font-variant-numeric: tabular-nums;">
                                Rp {{ number_format($deptVal, 0, ',', '.') }} · {{ $deptPct }}%
                            </span>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-2 text-muted" style="font-size: 11px;">
                        Belum ada kasbon aktif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
