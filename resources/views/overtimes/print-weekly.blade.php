<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Lembur Mingguan - {{ $period->reference_number }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" crossorigin="anonymous">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #0f172a;
            padding: 24px 16px;
            font-size: 12px;
            line-height: 1.45;
        }

        .print-actions {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .print-actions-right {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease-in-out;
        }

        .btn i {
            margin-right: 6px;
        }

        .btn-primary {
            background-color: #0b52aa;
            color: #ffffff;
            border-color: #0b52aa;
            box-shadow: 0 2px 4px rgba(11, 82, 170, 0.2);
        }

        .btn-primary:hover {
            background-color: #09458f;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #475569;
            border-color: #cbd5e1;
        }

        .slip-paper {
            position: relative;
            overflow: hidden;
            max-width: 820px;
            min-height: 1000px;
            margin: 0 auto 32px auto;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            padding: 28px 32px;
            display: flex;
            flex-direction: column;
            page-break-after: always;
        }

        .slip-paper:last-child {
            page-break-after: auto;
        }

        /* Full-Page Repeating Diagonal Watermark (45 Degrees) */
        .slip-watermark-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            user-select: none;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .slip-watermark-pattern image {
            filter: grayscale(100%);
        }

        /* Content containers positioned above watermark */
        .kop-container,
        .kop-divider,
        .data-karyawan-box,
        .calc-table,
        .gaji-bersih-box,
        .signatures-grid,
        .doc-subtle-note {
            position: relative;
            z-index: 1;
        }

        /* ================= COLOR THEMES (KARYAWAN VS HARIAN) ================= */
        /* 1. STAF / KARYAWAN (CORPORATE BLUE) */
        .slip-theme-staff .kop-doc-title {
            color: #0b52aa;
        }
        .slip-theme-staff .periode-box {
            border-color: #0b52aa;
        }
        .slip-theme-staff .periode-label {
            color: #0b52aa;
        }
        .slip-theme-staff .box-header {
            background-color: #dbeafe;
            color: #0b52aa;
            border-bottom: 1px solid #93c5fd;
        }
        .slip-theme-staff .calc-table th {
            background-color: #dbeafe;
            color: #0f172a;
        }
        .slip-theme-staff .calc-table tfoot td {
            background-color: #dbeafe;
            color: #0f172a;
        }
        .slip-theme-staff .gaji-bersih-box {
            border-color: #93c5fd;
        }
        .slip-theme-staff .gaji-bersih-left {
            background-color: #dbeafe;
            border-right: 1px solid #93c5fd;
        }
        .slip-theme-staff .gaji-bersih-title {
            color: #0b52aa;
        }
        .slip-theme-staff .gaji-bersih-amount {
            color: #0b52aa;
        }
        .slip-theme-staff .subtotal-theme {
            color: #0b52aa;
        }

        /* 2. PEGAWAI HARIAN LEPAS (EMERALD GREEN) */
        .slip-theme-harian .kop-doc-title {
            color: #059669;
        }
        .slip-theme-harian .periode-box {
            border-color: #059669;
        }
        .slip-theme-harian .periode-label {
            color: #059669;
        }
        .slip-theme-harian .box-header {
            background-color: #d1fae5;
            color: #065f46;
            border-bottom: 1px solid #6ee7b7;
        }
        .slip-theme-harian .calc-table th {
            background-color: #d1fae5;
            color: #0f172a;
        }
        .slip-theme-harian .calc-table tfoot td {
            background-color: #d1fae5;
            color: #0f172a;
        }
        .slip-theme-harian .gaji-bersih-box {
            border-color: #6ee7b7;
        }
        .slip-theme-harian .gaji-bersih-left {
            background-color: #d1fae5;
            border-right: 1px solid #6ee7b7;
        }
        .slip-theme-harian .gaji-bersih-title {
            color: #059669;
        }
        .slip-theme-harian .gaji-bersih-amount {
            color: #059669;
        }
        .slip-theme-harian .subtotal-theme {
            color: #059669;
        }

        /* HEADER KOP */
        .kop-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .kop-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .kop-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .kop-company-name {
            font-size: 17px;
            font-weight: 800;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: -0.2px;
        }

        .kop-company-address {
            font-size: 11px;
            color: #334155;
            margin-top: 2px;
            max-width: 440px;
            line-height: 1.35;
        }

        .kop-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
        }

        .kop-doc-title {
            font-size: 15px;
            font-weight: 800;
            color: #000000;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .periode-box {
            border: 1.5px solid #1e293b;
            padding: 6px 14px;
            text-align: center;
            min-width: 175px;
            background: transparent;
        }

        .periode-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e293b;
        }

        .periode-month {
            font-size: 12.5px;
            font-weight: 800;
            color: #000000;
            margin-top: 2px;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }

        .kop-divider {
            height: 2px;
            background-color: #000000;
            margin-bottom: 14px;
        }

        /* DATA KARYAWAN BOX */
        .data-karyawan-box {
            border: 1px solid #1e293b;
            margin-bottom: 14px;
        }

        .box-header {
            background-color: #dbeafe;
            color: #000000;
            font-weight: 800;
            font-size: 11.5px;
            padding: 5px 12px;
            letter-spacing: 0.4px;
            border-bottom: 1px solid #1e293b;
        }

        .box-body {
            padding: 8px 12px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table td {
            padding: 3px 4px;
            font-size: 11.5px;
            vertical-align: top;
        }

        .col-lbl {
            width: 15%;
            color: #334155;
            font-weight: 500;
        }

        .col-sep {
            width: 2%;
            text-align: center;
            font-weight: 600;
        }

        .col-val {
            width: 33%;
            color: #000000;
        }

        /* DETAIL TABLE */
        .calc-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #1e293b;
            margin-bottom: 14px;
        }

        .calc-table th {
            background-color: #dbeafe;
            color: #000000;
            font-weight: 800;
            font-size: 10.5px;
            padding: 7px 8px;
            border: 1px solid #1e293b;
            text-align: center;
            white-space: nowrap !important;
            text-wrap: nowrap !important;
        }

        .calc-table td {
            padding: 6px 8px;
            border: 1px solid #1e293b;
            font-size: 11px;
            vertical-align: middle;
            white-space: nowrap !important;
            text-wrap: nowrap !important;
        }

        .td-center {
            text-align: center;
        }

        .td-right {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .currency-split {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 6px;
            font-variant-numeric: tabular-nums;
        }

        .calc-table tfoot td {
            background-color: #dbeafe;
            font-weight: 800;
            font-size: 11.5px;
            color: #000000;
            border: 1px solid #1e293b;
        }

        /* GRAND TOTAL BOX */
        .gaji-bersih-box {
            display: flex;
            border: 1px solid #1e293b;
            margin-bottom: 16px;
            align-items: stretch;
        }

        .gaji-bersih-left {
            width: 50%;
            background-color: #dbeafe;
            border-right: 1px solid #1e293b;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .gaji-bersih-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            color: #000000;
            letter-spacing: 0.3px;
        }

        .gaji-bersih-sub {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e293b;
            margin-top: 3px;
        }

        .gaji-bersih-right {
            width: 50%;
            background-color: transparent;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .gaji-bersih-amount {
            font-size: 20px;
            font-weight: 800;
            color: #000000;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .gaji-bersih-terbilang {
            font-size: 11px;
            font-style: italic;
            color: #1e293b;
            margin-top: 4px;
            text-align: center;
        }

        /* SIGNATURES */
        .signatures-grid {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 24px;
            gap: 40px;
        }

        .signature-col {
            flex: 1;
            text-align: center;
            font-size: 11.5px;
            color: #000000;
        }

        .signature-title {
            font-weight: 800;
            font-size: 12px;
            color: #000000;
        }

        .signature-space {
            height: 60px;
        }

        .signature-name {
            font-weight: 800;
            text-decoration: underline;
            color: #000000;
            font-size: 12px;
        }

        .signature-nip {
            font-size: 11px;
            color: #0f172a;
            margin-top: 3px;
            font-weight: 500;
        }

        .doc-subtle-note {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.4;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 15mm;
            }

            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .print-actions {
                display: none !important;
            }

            .slip-theme-staff,
            .slip-theme-harian {
                border-top: none !important;
            }

            .slip-paper {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                position: relative;
                overflow: hidden;
                box-sizing: border-box;
                min-height: 270mm !important;
                display: flex !important;
                flex-direction: column !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .signatures-grid {
                margin-top: auto !important;
                padding-top: 20px !important;
            }

            .doc-subtle-note {
                margin-top: 15px !important;
                padding-bottom: 5px !important;
            }

            .slip-watermark-pattern {
                display: block !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- ACTION BUTTONS -->
    <div class="print-actions">
        <a href="{{ route('overtimes.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Menu Lembur
        </a>
        <div class="print-actions-right">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    @forelse ($slips as $slip)
        @php
            $emp = $slip['employee'];
            $submissions = $slip['submissions'];
            $attendance = $slip['attendance'];
            $totalHours = $slip['total_hours'];
            $totalOvertimePay = $slip['total_overtime_pay'];
            $overtimeMealAllowance = $slip['overtime_meal_allowance'];
            $grandTotal = $slip['grand_total'];
            $grandTotalInWords = $slip['grand_total_in_words'];
            $isHarian = $slip['is_harian'];
            $daysCount = $slip['days_count'];
            $mealRate = $slip['meal_rate'];
            $dailyRate = $slip['daily_rate'];
            $totalMeal = $slip['total_meal'];
            $totalDailyWage = $slip['total_daily_wage'];
            $isSlipPaid = $slip['is_paid'];
            $days = $slip['days'];
            $totals = $slip['totals'];
            $unitPriceFormatted = $slip['unit_price_formatted'];
        @endphp
        <div class="slip-paper {{ $isHarian ? 'slip-theme-harian' : 'slip-theme-staff' }}">
            <!-- Full-Page Repeating Diagonal Watermark Pattern (45 Derajat) -->
            <svg class="slip-watermark-pattern" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100%" height="100%">
                <defs>
                    <pattern id="wmPattern-{{ $loop->index ?? 0 }}" width="150" height="150" patternUnits="userSpaceOnUse" patternTransform="rotate(-45)">
                        <image href="{{ $company->logo_url ?? asset('assets/img/larashiz-logo.jpg') }}"
                               xlink:href="{{ $company->logo_url ?? asset('assets/img/larashiz-logo.jpg') }}"
                               x="40" y="40" width="70" height="70"
                               opacity="0.07"
                               preserveAspectRatio="xMidYMid meet" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#wmPattern-{{ $loop->index ?? 0 }})" />
            </svg>

            <!-- HEADER KOP -->
            <div class="kop-container">
                <div class="kop-left">
                    <img src="{{ $company->logo_url ?? asset('assets/img/larashiz-logo.jpg') }}" alt="Logo" class="kop-logo">
                    <div>
                        <div class="kop-company-name">{{ $company->name ?: 'PERUSAHAAN' }}</div>
                        <div class="kop-company-address">{{ $company->address ?: 'Alamat Perusahaan' }}</div>
                    </div>
                </div>
                <div class="kop-right">
                    <div class="kop-doc-title">SLIP MINGGUAN ({{ $isHarian ? 'GAJI & UANG MAKAN' : 'UANG MAKAN & LEMBUR' }})</div>
                    <div class="periode-box">
                        <div class="periode-label">PERIODE MINGGUAN</div>
                        <div class="periode-month">
                            {{ $period->start_date->locale('id')->translatedFormat('d M') }} s/d {{ $period->end_date->locale('id')->translatedFormat('d M Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="kop-divider"></div>

            <!-- DATA KARYAWAN -->
            <div class="data-karyawan-box">
                <div class="box-header">DATA KARYAWAN</div>
                <div class="box-body">
                    <table class="data-table">
                        <tr>
                            <td class="col-lbl">Nama</td>
                            <td class="col-sep">:</td>
                            <td class="col-val"><strong>{{ $emp->full_name }}</strong></td>
                            <td class="col-lbl">Unit Kerja / Dept</td>
                            <td class="col-sep">:</td>
                            <td class="col-val">{{ $emp->department?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="col-lbl">Status Kerja</td>
                            <td class="col-sep">:</td>
                            <td class="col-val">{{ $emp->employment_status?->label() ?: 'Karyawan Tetap' }}</td>
                            <td class="col-lbl">Jabatan</td>
                            <td class="col-sep">:</td>
                            <td class="col-val">{{ $emp->position?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="col-lbl">{{ $isHarian ? 'Upah Harian' : 'Uang Lembur' }}</td>
                            <td class="col-sep">:</td>
                            <td class="col-val">
                                @if ($isHarian)
                                    <strong>Rp {{ number_format($dailyRate, 0, ',', '.') }}</strong> / hari
                                @else
                                    <strong>{{ $slip['unit_price_formatted'] }}</strong> / jam
                                @endif
                            </td>
                            <td class="col-lbl">Uang Makan</td>
                            <td class="col-sep">:</td>
                            <td class="col-val">
                                <strong>Rp {{ number_format($mealRate, 0, ',', '.') }}</strong> / hari
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- TABEL RINCIAN PRESENSI, LEMBUR & UANG MAKAN MINGGUAN (FORMAT MATRIX KONSISTEN) -->
            <div style="font-weight: 800; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; color: #1e293b;">
                RINCIAN PRESENSI, LEMBUR &amp; {{ $isHarian ? 'UPAH HARIAN / UANG MAKAN' : 'UANG MAKAN MINGGUAN' }}
            </div>
            <table class="calc-table matrix-print-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="text-align: left; padding-left: 10px; width: 130px; white-space: nowrap !important;">HARI</th>
                        <th rowspan="2" style="width: 50px; text-align: center; white-space: nowrap !important;">JAM</th>
                        <th rowspan="2" style="width: 90px; text-align: center; white-space: nowrap !important;">HARI LIBUR</th>
                        <th rowspan="2" style="width: 105px; text-align: center; white-space: nowrap !important;">UNIT PRICE</th>
                        <th colspan="2" style="text-align: center; white-space: nowrap !important;">UANG MAKAN</th>
                        <th rowspan="2" style="width: 130px; text-align: center; white-space: nowrap !important;">UPAH DITERIMA</th>
                    </tr>
                    <tr>
                        <th style="width: 115px; text-align: right; padding-right: 10px; white-space: nowrap !important;">MAKAN SIANG</th>
                        <th style="width: 115px; text-align: right; padding-right: 10px; white-space: nowrap !important;">MAKAN MALAM</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $daysMatrixCount = count($days);
                        $firstDay = $days[0] ?? null;
                    @endphp
                    @if ($firstDay)
                        <tr>
                            <!-- HARI -->
                            <td style="text-align: left; padding-left: 10px; {{ $firstDay['is_holiday'] ? 'color: #dc2626; font-weight: 600;' : 'color: #1e293b;' }}">
                                {{ $firstDay['day_name'] }}, {{ $firstDay['date_formatted'] }}
                            </td>
                            <!-- JAM -->
                            <td style="text-align: center; font-weight: 600; color: #0f172a;">
                                {{ !empty($firstDay['ot_hours_formatted']) ? $firstDay['ot_hours_formatted'] : '-' }}
                            </td>
                            <!-- HARI LIBUR -->
                            <td style="padding: 6px 8px;">
                                @if ($firstDay['holiday_ot'] > 0)
                                    <div class="currency-split">
                                        <span>Rp</span>
                                        <span>{{ number_format($firstDay['holiday_ot'], 0, ',', '.') }}</span>
                                    </div>
                                @else
                                    <div style="text-align: center; color: #cbd5e1;">-</div>
                                @endif
                            </td>
                            <!-- UNIT PRICE (rowspan) -->
                            <td rowspan="{{ $daysMatrixCount }}" style="vertical-align: middle; padding: 6px 8px; font-weight: 600; font-size: 11.5px; background-color: #ffffff;">
                                {{ $unitPriceFormatted }}
                            </td>
                            <!-- MAKAN SIANG -->
                            <td style="padding: 6px 8px;">
                                @if ($firstDay['lunch'] > 0)
                                    <div class="currency-split">
                                        <span>Rp</span>
                                        <span>{{ number_format($firstDay['lunch'], 0, ',', '.') }}</span>
                                    </div>
                                @else
                                    <div style="text-align: center; color: #cbd5e1;">-</div>
                                @endif
                            </td>
                            <!-- MAKAN MALAM -->
                            <td style="padding: 6px 8px;">
                                @if ($firstDay['dinner'] > 0)
                                    <div class="currency-split">
                                        <span>Rp</span>
                                        <span>{{ number_format($firstDay['dinner'], 0, ',', '.') }}</span>
                                    </div>
                                @else
                                    <div style="text-align: center; color: #cbd5e1;">-</div>
                                @endif
                            </td>
                            <!-- UPAH DITERIMA (rowspan clean white) -->
                            <td rowspan="{{ $daysMatrixCount }}" style="background-color: #ffffff; text-align: left; padding: 8px; vertical-align: middle;">
                                @if ($isHarian)
                                    <div style="font-size: 10px; color: #64748b;">Upah harian</div>
                                    <div style="font-weight: 700; margin-bottom: 8px;">Rp {{ number_format($totals['total_daily_wage'], 0, ',', '.') }}</div>
                                    <div style="font-size: 10px; color: #64748b;">Kerja per jam</div>
                                    <div style="font-weight: 700;">Rp {{ number_format($totals['total_hourly_work_pay'], 0, ',', '.') }}</div>
                                @endif
                            </td>
                        </tr>

                        @for ($d = 1; $d < $daysMatrixCount; $d++)
                            @php $day = $days[$d]; @endphp
                            <tr>
                                <!-- HARI -->
                                <td style="text-align: left; padding-left: 10px; {{ $day['is_holiday'] ? 'color: #dc2626; font-weight: 600;' : 'color: #1e293b;' }}">
                                    {{ $day['day_name'] }}, {{ $day['date_formatted'] }}
                                </td>
                                <!-- JAM -->
                                <td style="text-align: center; font-weight: 600; color: #0f172a;">
                                    {{ !empty($day['ot_hours_formatted']) ? $day['ot_hours_formatted'] : '-' }}
                                </td>
                                <!-- HARI LIBUR -->
                                <td style="padding: 6px 8px;">
                                    @if ($day['holiday_ot'] > 0)
                                        <div class="currency-split">
                                            <span>Rp</span>
                                            <span>{{ number_format($day['holiday_ot'], 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <div style="text-align: center; color: #cbd5e1;">-</div>
                                    @endif
                                </td>
                                <!-- MAKAN SIANG -->
                                <td style="padding: 6px 8px;">
                                    @if ($day['lunch'] > 0)
                                        <div class="currency-split">
                                            <span>Rp</span>
                                            <span>{{ number_format($day['lunch'], 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <div style="text-align: center; color: #cbd5e1;">-</div>
                                    @endif
                                </td>
                                <!-- MAKAN MALAM -->
                                <td style="padding: 6px 8px;">
                                    @if ($day['dinner'] > 0)
                                        <div class="currency-split">
                                            <span>Rp</span>
                                            <span>{{ number_format($day['dinner'], 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <div style="text-align: center; color: #cbd5e1;">-</div>
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    @endif
                </tbody>
                <tfoot>
                    <tr class="tfoot-total-row">
                        <td style="text-align: center; font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;">
                            <strong>TOTAL LEMBUR</strong>
                        </td>
                        <td style="text-align: center; font-size: 11.5px; color: #0f172a;">
                            <strong>{{ $totals['total_ot_hours_formatted'] }}</strong>
                        </td>
                        <td style="padding: 6px 8px;">
                            @if ($totals['total_holiday_ot'] > 0)
                                <div class="currency-split">
                                    <span>Rp</span>
                                    <span>{{ number_format($totals['total_holiday_ot'], 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div style="text-align: center; color: #cbd5e1;">-</div>
                            @endif
                        </td>
                        <td style="padding: 6px 8px; font-size: 11.5px;">
                            @if ($totals['hourly_ot_pay'] > 0)
                                <div class="currency-split">
                                    <span>Rp</span>
                                    <span>{{ number_format($totals['hourly_ot_pay'], 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div style="text-align: center; color: #cbd5e1;">-</div>
                            @endif
                        </td>
                        <td style="padding: 6px 8px;">
                            @if ($totals['total_lunch'] > 0)
                                <div class="currency-split">
                                    <span>Rp</span>
                                    <span>{{ number_format($totals['total_lunch'], 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div style="text-align: center; color: #cbd5e1;">-</div>
                            @endif
                        </td>
                        <td style="padding: 6px 8px;">
                            @if ($totals['total_dinner'] > 0)
                                <div class="currency-split">
                                    <span>Rp</span>
                                    <span>{{ number_format($totals['total_dinner'], 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div style="text-align: center; color: #cbd5e1;">-</div>
                            @endif
                        </td>
                        <td style="padding: 6px 8px; font-size: 12px; font-weight: 800;" class="subtotal-theme">
                            @if ($totals['grand_total'] > 0)
                                <div class="currency-split">
                                    <span>Rp</span>
                                    <span>{{ number_format($totals['grand_total'], 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div style="text-align: center; color: #cbd5e1;">-</div>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- KOTAK TOTAL DITERIMA MINGGU INI -->
            <div class="gaji-bersih-box">
                <div class="gaji-bersih-left">
                    <div class="gaji-bersih-title">TOTAL DITERIMA MINGGU INI</div>
                    <div class="gaji-bersih-sub">
                        @if ($isHarian)
                            (UPAH HARIAN / KERJA PER JAM + UANG MAKAN KEHADIRAN + LEMBUR{{ $overtimeMealAllowance > 0 ? ' & MAKAN LEMBUR' : '' }})
                        @else
                            (UANG MAKAN KEHADIRAN + UPAH LEMBUR{{ $overtimeMealAllowance > 0 ? ' + UANG MAKAN LEMBUR' : '' }})
                        @endif
                    </div>
                </div>
                <div class="gaji-bersih-right">
                    <div class="gaji-bersih-amount">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
                    <div class="gaji-bersih-terbilang">({{ $grandTotalInWords }} Rupiah)</div>
                </div>
            </div>

            @if ($isSlipPaid)
                <!-- INFORMASI PEMBAYARAN RESMI (LUNAS DIBAYARKAN) -->
                <div class="payment-confirmed-banner" style="margin-top: 10px; margin-bottom: 14px; padding: 8px 14px; background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 6px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-block; padding: 2px 8px; background-color: #16a34a; color: #ffffff; font-size: 11px; font-weight: 800; border-radius: 4px; letter-spacing: 0.5px;">LUNAS DIBAYARKAN</span>
                        <span style="font-size: 11px; color: #166534; font-weight: 600;">
                            Waktu: {{ $attendance?->paid_at ? $attendance->paid_at->locale('id')->translatedFormat('d F Y, H:i') : ($period->paid_at ? $period->paid_at->locale('id')->translatedFormat('d F Y, H:i') : now()->locale('id')->translatedFormat('d F Y')) }} WIB
                        </span>
                    </div>
                    <div style="font-size: 11px; color: #166534; font-weight: 600;">
                        Metode: {{ $attendance?->paymentMethodLabel() ?? 'Tunai (Cash)' }}
                        @if ($attendance?->bank_name)
                            &bull; {{ $attendance->bank_name }} ({{ $attendance->bank_account_number }})
                        @endif
                    </div>
                </div>
            @else
                <!-- PERINGATAN ESTIMASI (BELUM DIBAYAR) -->
                <div class="payment-pending-banner" style="margin-top: 10px; margin-bottom: 14px; padding: 7px 12px; background-color: #fffbeb; border: 1px dashed #f59e0b; border-radius: 6px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; color: #b45309; font-weight: 700;">
                        STATUS: DRAF / BELUM DIBAYARKAN
                    </span>
                    <span style="font-size: 10.5px; color: #b45309;">
                        Slip ini sah sebagai bukti pembayaran setelah status ditandai lunas dibayar.
                    </span>
                </div>
            @endif

            <!-- TANDA TANGAN -->
            <div class="signatures-grid">
                <div class="signature-col">
                    <div class="signature-title">Diterima Oleh,</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $emp->full_name }}</div>
                    <div class="signature-nip">Karyawan</div>
                </div>
                <div class="signature-col">
                    <div class="signature-title">Disetujui Oleh,</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $period->creator?->name ?? 'Admin / HRD' }}</div>
                    <div class="signature-nip">HRD / Manajemen</div>
                </div>
            </div>

            <div class="doc-subtle-note">
                Dokumen resmi slip lembur mingguan diterbitkan otomatis melalui sistem <strong>{{ $company->name ?: config('app.name', 'HRM') }}</strong> pada {{ now()->locale('id')->translatedFormat('d/m/Y H:i') }} WIB dan sah tanpa tanda tangan basah.
            </div>
        </div>
    @empty
        <div class="slip-paper" style="text-align: center; padding: 60px 20px; border-radius: 8px;">
            <i class="fas fa-file-invoice" style="font-size: 48px; color: #94a3b8; margin-bottom: 16px;"></i>
            <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Tidak Ada Slip yang Dicetak</h3>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 0;">Tidak ada data karyawan dengan kehadiran kerja aktif (&gt; 0 hari) atau uang lembur/harian pada periode ini.</p>
        </div>
    @endforelse

</body>
</html>
