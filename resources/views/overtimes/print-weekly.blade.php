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
            font-size: 11px;
            padding: 6px 8px;
            border: 1px solid #1e293b;
            text-align: center;
        }

        .calc-table td {
            padding: 6px 8px;
            border: 1px solid #1e293b;
            font-size: 11px;
            vertical-align: middle;
        }

        .td-center {
            text-align: center;
        }

        .td-right {
            text-align: right;
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

            .unpaid-watermark {
                display: flex !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        /* Large Diagonal Status Watermark for Unpaid Slips */
        .unpaid-watermark {
            position: absolute;
            top: 48%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-28deg);
            font-size: 56px;
            font-weight: 900;
            letter-spacing: 5px;
            color: rgba(220, 38, 38, 0.14) !important;
            border: 5px dashed rgba(220, 38, 38, 0.25) !important;
            padding: 12px 36px;
            border-radius: 12px;
            text-transform: uppercase;
            pointer-events: none;
            z-index: 50;
            white-space: nowrap;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    @php
        if (!function_exists('terbilang_id')) {
            function terbilang_id($angka): string {
                $angka = abs((float) $angka);
                $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
                $hasil = '';

                if ($angka < 12) {
                    $hasil = ' ' . $baca[(int) $angka];
                } elseif ($angka < 20) {
                    $hasil = terbilang_id($angka - 10) . ' Belas';
                } elseif ($angka < 100) {
                    $hasil = terbilang_id(floor($angka / 10)) . ' Puluh ' . terbilang_id(fmod($angka, 10));
                } elseif ($angka < 200) {
                    $hasil = 'Seratus ' . terbilang_id($angka - 100);
                } elseif ($angka < 1000) {
                    $hasil = terbilang_id(floor($angka / 100)) . ' Ratus ' . terbilang_id(fmod($angka, 100));
                } elseif ($angka < 2000) {
                    $hasil = 'Seribu ' . terbilang_id($angka - 1000);
                } elseif ($angka < 1000000) {
                    $hasil = terbilang_id(floor($angka / 1000)) . ' Ribu ' . terbilang_id(fmod($angka, 1000));
                } elseif ($angka < 1000000000) {
                    $hasil = terbilang_id(floor($angka / 1000000)) . ' Juta ' . terbilang_id(fmod($angka, 1000000));
                } elseif ($angka < 1000000000000) {
                    $hasil = terbilang_id(floor($angka / 1000000000)) . ' Miliar ' . terbilang_id(fmod($angka, 1000000000));
                } else {
                    $hasil = terbilang_id(floor($angka / 1000000000000)) . ' Triliun ' . terbilang_id(fmod($angka, 1000000000000));
                }

                return trim($hasil);
            }
        }
    @endphp

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
            $attendance = $slip['attendance'] ?? null;
            $totalHours = $slip['total_hours'];
            $totalOvertimePay = $slip['total_overtime_pay'];
            $grandTotal = $slip['grand_total'];
            $isHarian = $emp->employment_status === \App\Enums\EmploymentStatus::HARIAN;

            $daysCount = $attendance ? (int) $attendance->attended_days : 0;
            $mealRate = $attendance ? (float) $attendance->meal_allowance_per_day : (float) $emp->getEffectiveMealAllowancePerDay();
            $dailyRate = $attendance && (float) $attendance->daily_rate > 0 ? (float) $attendance->daily_rate : (float) ($emp->daily_rate ?? 0);
            $totalMeal = $attendance ? (float) $attendance->total_meal_allowance : (float) ($daysCount * $mealRate);
            $totalDailyWage = $attendance ? (float) ($attendance->total_daily_wage ?? 0) : (float) ($daysCount * $dailyRate);

            $dayLabels = [
                'mon' => 'Sen',
                'tue' => 'Sel',
                'wed' => 'Rab',
                'thu' => 'Kam',
                'fri' => 'Jum',
                'sat' => 'Sab',
                'sun' => 'Min',
            ];
            $attendedDaysList = [];
            if ($attendance && is_array($attendance->attendance_days)) {
                foreach ($attendance->attendance_days as $d) {
                    $attendedDaysList[] = $dayLabels[strtolower($d)] ?? ucfirst($d);
                }
            }

            $isSlipPaid = ($period->status === \App\Enums\OvertimePeriodStatus::PAID) || ($attendance && $attendance->isPaid());
        @endphp

        <div class="slip-paper {{ $isHarian ? 'slip-theme-harian' : 'slip-theme-staff' }}">
            @if (! $isSlipPaid)
                <!-- Watermark Status Belum Lunas (User Request: Batch print tetap cetak semua namun diberi watermark) -->
                <div class="unpaid-watermark">
                    DRAFT &bull; BELUM LUNAS
                </div>
            @endif

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
                                    <strong>{{ $emp->formatted_overtime_rate_per_hour }}</strong> / jam
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

            <!-- 1. RINCIAN KEHADIRAN & UANG MAKAN MINGGUAN (TABEL RESMI) -->
            <div style="font-weight: 800; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; color: #1e293b;">
                1. RINCIAN KEHADIRAN &amp; {{ $isHarian ? 'UPAH HARIAN / UANG MAKAN' : 'UANG MAKAN MINGGUAN' }}
            </div>
            <table class="calc-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">NO</th>
                        <th>KOMPONEN</th>
                        <th style="width: 100px;">HARI MASUK</th>
                        <th>RINCIAN HARI HADIR</th>
                        <th style="width: 130px;">TARIF / HARI</th>
                        <th style="width: 150px;">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($isHarian)
                        <tr>
                            <td class="td-center">1</td>
                            <td><strong>Upah Kerja Harian</strong></td>
                            <td class="td-center"><strong>{{ $daysCount }} Hari</strong></td>
                            <td>{{ !empty($attendedDaysList) ? implode(', ', $attendedDaysList) : '-' }}</td>
                            <td class="td-right">Rp {{ number_format($dailyRate, 0, ',', '.') }}</td>
                            <td class="td-right"><strong>Rp {{ number_format($totalDailyWage, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <td class="td-center">2</td>
                            <td><strong>Uang Makan Harian</strong></td>
                            <td class="td-center"><strong>{{ $daysCount }} Hari</strong></td>
                            <td>{{ !empty($attendedDaysList) ? implode(', ', $attendedDaysList) : '-' }}</td>
                            <td class="td-right">Rp {{ number_format($mealRate, 0, ',', '.') }}</td>
                            <td class="td-right"><strong>Rp {{ number_format($totalMeal, 0, ',', '.') }}</strong></td>
                        </tr>
                    @else
                        <tr>
                            <td class="td-center">1</td>
                            <td><strong>Uang Makan Kehadiran Mingguan</strong></td>
                            <td class="td-center"><strong>{{ $daysCount }} Hari</strong></td>
                            <td>{{ !empty($attendedDaysList) ? implode(', ', $attendedDaysList) : '-' }}</td>
                            <td class="td-right">Rp {{ number_format($mealRate, 0, ',', '.') }}</td>
                            <td class="td-right"><strong>Rp {{ number_format($totalMeal, 0, ',', '.') }}</strong></td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="td-center">
                            <strong>TOTAL {{ $isHarian ? 'UPAH HARIAN & UANG MAKAN' : 'UANG MAKAN MINGGUAN' }}</strong>
                        </td>
                        <td class="td-center"><strong>{{ $daysCount }} Hari</strong></td>
                        <td class="td-right subtotal-theme">
                            <strong>Rp {{ number_format($isHarian ? ($totalDailyWage + $totalMeal) : $totalMeal, 0, ',', '.') }}</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- 2. TABEL RINCIAN LEMBUR KERJA -->
            <div style="font-weight: 800; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; color: #1e293b;">
                2. RINCIAN UPAH LEMBUR KERJA
            </div>
            <table class="calc-table">
                <thead>
                    <tr>
                        <th style="width: 30px;">NO</th>
                        <th style="width: 85px;">TANGGAL</th>
                        <th style="width: 100px;">JAM KERJA</th>
                        <th style="width: 70px;">DURASI</th>
                        <th style="width: 110px;">TARIF / SATUAN</th>
                        <th style="width: 130px;">SUBTOTAL LEMBUR</th>
                        <th>URAIAN / TUGAS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $idx => $sub)
                        <tr>
                            <td class="td-center">{{ $idx + 1 }}</td>
                            <td class="td-center">{{ $sub->date->locale('id')->translatedFormat('d/m/Y') }}</td>
                            <td class="td-center font-monospace">{{ $sub->formatted_work_hours }}</td>
                            <td class="td-center font-weight-bold">{{ $sub->formatted_duration }}</td>
                            <td class="td-right">{{ $sub->formatted_rate_label }}</td>
                            <td class="td-right font-weight-bold">{{ $sub->formatted_subtotal_overtime }}</td>
                            <td>{{ $sub->reason }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="td-center py-2 text-muted">Tidak ada lembur kerja pada minggu ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="td-center"><strong>TOTAL LEMBUR</strong></td>
                        <td class="td-center"><strong>{{ number_format($totalHours, 2) }} jam</strong></td>
                        <td></td>
                        <td class="td-right subtotal-theme"><strong>Rp {{ number_format($totalOvertimePay, 0, ',', '.') }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <!-- KOTAK TOTAL DITERIMA MINGGU INI -->
            <div class="gaji-bersih-box">
                <div class="gaji-bersih-left">
                    <div class="gaji-bersih-title">TOTAL DITERIMA MINGGU INI</div>
                    <div class="gaji-bersih-sub">({{ $isHarian ? 'UPAH HARIAN + UANG MAKAN + UPAH LEMBUR' : 'UANG MAKAN KEHADIRAN + UPAH LEMBUR' }})</div>
                </div>
                <div class="gaji-bersih-right">
                    <div class="gaji-bersih-amount">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
                    <div class="gaji-bersih-terbilang">({{ terbilang_id($grandTotal) }} Rupiah)</div>
                </div>
            </div>

            @php
                $isSlipPaid = ($period->status === \App\Enums\OvertimePeriodStatus::PAID) || ($attendance && $attendance->isPaid());
            @endphp

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
