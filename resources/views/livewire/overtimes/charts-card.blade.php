<!-- ================= 1 CARD BERISI ANALISIS LEMBUR (DESAIN EKSEKUTIF SEPERTI KASBON & PENGGAJIAN) ================= -->
<div class="card shadow-sm border-0 mb-0" style="border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e9edf2;">
    <!-- 1. Header Card Bersih & Konsisten -->
    <div class="border-bottom d-flex align-items-center justify-content-between" style="background: #ffffff; padding: 14px 20px;">
        <div>
            <h4 class="mb-0" style="font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.35;">
                Analisis Lembur
            </h4>
            <div style="font-size: 11.5px; font-weight: 500; color: #64748b; margin-top: 2px;">
                Tren &amp; distribusi kerja lembur
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- 2. Seksi 1: Tren Jam Lembur Bulanan (Bar Chart) -->
        <div style="padding: 16px 20px 14px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div style="font-size: 12px; font-weight: 700; color: #334155;">
                    Tren Jam Lembur
                </div>
                <span class="badge border font-weight-600 px-2 py-0.5" style="font-size: 10px; border-radius: 6px; background-color: #ffffff; color: #64748b; border-color: #e2e8f0;">
                    6 bulan terakhir
                </span>
            </div>

            <!-- Bar Chart Container (Alpine + Chart.js) -->
            <div wire:ignore
                 wire:key="ot-monthly-bar-chart-{{ md5(json_encode($chartData['monthly'] ?? [])) }}"
                 x-data="{
                     chartInstance: null,
                     initChart() {
                         const labels = @js($chartData['monthly']['labels'] ?? []);
                         const hours = @js($chartData['monthly']['hours'] ?? []);

                         const render = () => {
                             const canvas = this.$refs.canvas;
                             if (!canvas) return;
                             if (typeof Chart === 'undefined') {
                                 if (typeof window.ensureChartJs === 'function') {
                                     window.ensureChartJs(() => render());
                                 } else {
                                     setTimeout(render, 100);
                                 }
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
                                     labels: labels && labels.length ? labels : ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'],
                                     datasets: [{
                                         label: 'Total Jam',
                                         data: hours && hours.length ? hours : [0, 0, 0, 0, 0, 0],
                                         backgroundColor: '#0b52aa',
                                         borderRadius: 4,
                                         barPercentage: 0.65,
                                         categoryPercentage: 0.75,
                                     }]
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
                                                     const val = context.raw || 0;
                                                     return 'Durasi: ' + val + ' jam lembur';
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
                                             suggestedMax: 5,
                                             ticks: {
                                                 font: { size: 8.5 },
                                                 color: '#94a3b8',
                                                 maxTicksLimit: 4,
                                                 callback: function(v) { return v + ' j'; }
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
            <div class="d-flex justify-content-center align-items-center mt-2" style="font-size: 11px; color: #64748b;">
                <span class="d-flex align-items-center">
                    <span style="width: 7px; height: 7px; border-radius: 2px; background-color: #0b52aa; margin-right: 6px; display: inline-block;"></span>
                    Total Jam Lembur Disetujui
                </span>
            </div>
        </div>

        <!-- 3. Seksi 2: Distribusi Lembur per Departemen (Donut Chart dengan Center Text Overlay) -->
        <div style="border-top: 1px solid #f1f5f9; padding: 16px 20px 14px;">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <div style="font-size: 12px; font-weight: 700; color: #334155;">
                    Sebaran per Departemen
                </div>
                <span class="badge border font-weight-600 px-2 py-0.5" style="font-size: 10px; border-radius: 6px; background-color: #ffffff; color: #64748b; border-color: #e2e8f0;">
                    Upah lembur
                </span>
            </div>

            <!-- Donut Chart Container -->
            <div wire:ignore
                 wire:key="ot-dept-donut-{{ md5(json_encode($chartData['department_chart'] ?? [])) }}"
                 x-data="{
                     chartInstance: null,
                     initChart() {
                         const hasData = @js($chartData['department_chart']['has_data'] ?? false);
                         const labels = hasData ? @js($chartData['department_chart']['labels'] ?? []) : ['Belum ada data'];
                         const dataVals = hasData ? @js($chartData['department_chart']['data'] ?? []) : [1];
                         const colors = hasData ? @js($chartData['department_chart']['colors'] ?? []) : ['#e2e8f0'];

                         const render = () => {
                             const canvas = this.$refs.canvas;
                             if (!canvas) return;
                             if (typeof Chart === 'undefined') {
                                 if (typeof window.ensureChartJs === 'function') {
                                     window.ensureChartJs(() => render());
                                 } else {
                                     setTimeout(render, 100);
                                 }
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
                                             enabled: hasData,
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
                                                     return 'Upah: Rp ' + new Intl.NumberFormat('id-ID').format(val) + ' (' + pct + '%)';
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
                <div class="chart-center-overlay position-absolute text-center d-flex flex-column justify-content-center align-items-center" style="pointer-events: none; max-width: 90px; transition: opacity 0.2s ease;">
                    <span style="font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                        TOTAL 6 BLN
                    </span>
                    <span style="font-size: 13.5px; font-weight: 800; color: #0f172a; line-height: 1.25; margin-top: 1px;">
                        {{ $chartData['department_chart']['compact_total'] ?? 'Rp 0' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 4. Seksi 3: Dual Metric Tiles (Rata-rata Jam & Biaya/Orang) -->
        <div style="padding: 0 20px 14px;">
            <div class="row no-gutters" style="gap: 8px;">
                <div class="col" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); padding: 10px 12px;">
                    <div style="font-size: 10.5px; font-weight: 500; color: #64748b; line-height: 1.3;">
                        Rata-rata / orang
                    </div>
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.35; margin-top: 2px;">
                        {{ $chartData['metrics']['avg_hours_per_person'] ?? 0 }} jam
                    </div>
                </div>
                <div class="col" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); padding: 10px 12px;">
                    <div style="font-size: 10.5px; font-weight: 500; color: #64748b; line-height: 1.3;">
                        Rata-rata upah
                    </div>
                    <div style="font-size: 13px; font-weight: 700; color: #0b52aa; line-height: 1.35; margin-top: 2px; word-break: break-all;">
                        {{ $chartData['metrics']['formatted_avg_cost'] ?? 'Rp 0' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Seksi 4: Rincian Departemen dengan Mini Progress Bar -->
        @if (!empty($chartData['department_breakdown']))
            <div style="border-top: 1px solid #f1f5f9; padding: 14px 20px 16px;">
                <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
                    Biaya per Departemen
                </div>
                <div class="d-flex flex-column" style="gap: 10px;">
                    @foreach ($chartData['department_breakdown'] as $dept)
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 11px; line-height: 1.35;">
                                <span style="font-weight: 500; color: #334155;">
                                    {{ $dept['name'] }} · {{ $dept['hours'] }} jam
                                </span>
                                <span style="font-weight: 700; color: #0f172a;">
                                    {{ $dept['compact_cost'] }}
                                </span>
                            </div>
                            <div class="w-100" style="height: 5px; background-color: #f1f5f9; border-radius: 9999px; overflow: hidden;">
                                <div style="width: {{ $dept['bar_pct'] }}%; height: 100%; background-color: #0b52aa; border-radius: 9999px;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    if (typeof window.ensureChartJs !== 'function') {
        window.ensureChartJs = function(callback) {
            if (typeof Chart !== 'undefined') {
                if (typeof callback === 'function') callback();
                return;
            }
            if (window._chartJsLoading) {
                window._chartJsCallbacks = window._chartJsCallbacks || [];
                if (typeof callback === 'function') window._chartJsCallbacks.push(callback);
                return;
            }
            window._chartJsLoading = true;
            window._chartJsCallbacks = typeof callback === 'function' ? [callback] : [];
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = function() {
                window._chartJsLoading = false;
                const cbs = window._chartJsCallbacks || [];
                window._chartJsCallbacks = [];
                cbs.forEach(function(cb) { try { cb(); } catch(e){} });
            };
            script.onerror = function() {
                window._chartJsLoading = false;
            };
            document.head.appendChild(script);
        };
    }
</script>
