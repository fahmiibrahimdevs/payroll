<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Penggajian Tahunan {{ $year }} - {{ $company->name ?? config('app.name') }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
        }

        body {
            background-color: #f8fafc;
            padding: 24px;
        }

        .summary-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 36px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0b52aa;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .company-info {
            display: flex;
            align-items: center;
        }

        .company-logo {
            max-height: 55px;
            max-width: 90px;
            object-fit: contain;
            margin-right: 16px;
        }

        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #0b52aa;
        }

        .company-meta {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .doc-title {
            text-align: right;
        }

        .doc-title h2 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
        }

        .doc-title p {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 28px;
        }

        .stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            background: #f8fafc;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 4px;
        }

        .stat-sub {
            font-size: 10.5px;
            color: #94a3b8;
            margin-top: 2px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
            border-left: 3px solid #0b52aa;
            padding-left: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 24px;
        }

        th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
            text-align: left;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
        }

        td {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            color: #334155;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
        }

        .sig-box {
            text-align: center;
            width: 220px;
        }

        .sig-space {
            height: 65px;
        }

        .sig-name {
            font-weight: 700;
            border-bottom: 1px solid #334155;
            padding-bottom: 2px;
            font-size: 13px;
        }

        .sig-title {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px;
        }

        .print-btn-bar {
            max-width: 900px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn-print {
            background-color: #0b52aa;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .summary-container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .print-btn-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-btn-bar">
        <button onclick="window.print()" class="btn-print">Cetak / Simpan PDF</button>
    </div>

    <div class="summary-container">
        <div class="header">
            <div class="company-info">
                @if ($company->logo_url)
                    <img src="{{ $company->logo_url }}" alt="Logo" class="company-logo">
                @endif
                <div>
                    <div class="company-name">{{ $company->name ?? config('app.name') }}</div>
                    <div class="company-meta">{{ $company->address ?? 'Indonesia' }}</div>
                    <div class="company-meta">Telp: {{ $company->phone ?? '-' }} · Email: {{ $company->email ?? '-' }}</div>
                </div>
            </div>
            <div class="doc-title">
                <h2>REKAPITULASI PAYROLL</h2>
                <p>Tahun Anggaran {{ $year }}</p>
                <p style="font-size: 11px; color: #94a3b8;">{{ $statistics['rekap_payroll_subtitle'] }}</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Biaya Perusahaan</div>
                <div class="stat-value">{{ $statistics['compact_yearly_company_cost'] }}</div>
                <div class="stat-sub">{{ $statistics['formatted_yearly_total_company_cost'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total THP Ditransfer</div>
                <div class="stat-value">{{ $statistics['compact_yearly_thp'] }}</div>
                <div class="stat-sub">{{ $statistics['formatted_yearly_total_thp'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Setoran Pajak & BPJS</div>
                <div class="stat-value">{{ $statistics['compact_yearly_setoran'] }}</div>
                <div class="stat-sub">{{ $statistics['formatted_yearly_total_setoran'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Sisa Kasbon Karyawan</div>
                <div class="stat-value">{{ $statistics['compact_remaining_kasbon'] }}</div>
                <div class="stat-sub">{{ $statistics['remaining_kasbon_subtitle'] }}</div>
            </div>
        </div>

        <div class="section-title">Alokasi Biaya Penggajian</div>
        <table>
            <thead>
                <tr>
                    <th>Komponen Pengeluaran</th>
                    <th class="text-right">Nominal</th>
                    <th class="text-center">Porsi (%)</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Take Home Pay (THP)</strong></td>
                    <td class="text-right font-weight-bold">{{ $statistics['formatted_yearly_total_thp'] }}</td>
                    <td class="text-center font-weight-bold">{{ $statistics['yearly_thp_pct'] }}%</td>
                    <td>Ditransfer langsung ke rekening karyawan</td>
                </tr>
                <tr>
                    <td><strong>BPJS (Karyawan + Perusahaan)</strong></td>
                    <td class="text-right">{{ $statistics['formatted_yearly_total_bpjs'] }}</td>
                    <td class="text-center">{{ $statistics['yearly_bpjs_pct'] }}%</td>
                    <td>Iuran BPJS Ketenagakerjaan & BPJS Kesehatan</td>
                </tr>
                <tr>
                    <td><strong>Pajak PPh 21</strong></td>
                    <td class="text-right">{{ $statistics['formatted_yearly_total_pph'] }}</td>
                    <td class="text-center">{{ $statistics['yearly_pph_pct'] }}%</td>
                    <td>Pajak penghasilan karyawan disetor ke kas negara (TER)</td>
                </tr>
                <tr style="background-color: #f8fafc; font-weight: bold;">
                    <td>TOTAL BIAYA PERUSAHAAN</td>
                    <td class="text-right">{{ $statistics['formatted_yearly_total_company_cost'] }}</td>
                    <td class="text-center">100%</td>
                    <td>Rata-rata: {{ $statistics['formatted_yearly_avg_cost_per_person'] }} / orang / bulan</td>
                </tr>
            </tbody>
        </table>

        @if (!empty($statistics['department_breakdown']))
            <div class="section-title">Rincian Biaya per Departemen</div>
            <table>
                <thead>
                    <tr>
                        <th>Departemen</th>
                        <th class="text-center">Jumlah SDM</th>
                        <th class="text-right">Total Biaya</th>
                        <th class="text-center">Porsi Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($statistics['department_breakdown'] as $dept)
                        <tr>
                            <td><strong>{{ $dept['name'] }}</strong></td>
                            <td class="text-center">{{ $dept['count'] }} orang</td>
                            <td class="text-right">{{ $dept['formatted_cost'] }}</td>
                            <td class="text-center">{{ $dept['bar_pct'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="footer-signatures">
            <div class="sig-box">
                <div class="sig-title">Dibuat oleh,</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ auth()->user()?->name ?? 'HR & Payroll Admin' }}</div>
                <div class="sig-title">HR & Finance Officer</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Mengetahui & Menyetujui,</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $company->director_name ?? 'Direktur Utama' }}</div>
                <div class="sig-title">Direktur</div>
            </div>
        </div>
    </div>
</body>
</html>
