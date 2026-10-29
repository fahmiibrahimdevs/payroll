<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Seluruh Slip Gaji - {{ $period->period_label }} ({{ $items->count() }} Karyawan)</title>
    
    <!-- Google Fonts & FontAwesome -->
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

        /* Top Action Bar (Sticky, Hidden when printing) */
        .print-actions {
            max-width: 820px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            position: sticky;
            top: 12px;
            z-index: 100;
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
            background-color: #083e82;
            border-color: #083e82;
        }

        .btn-secondary {
            background-color: #f8fafc;
            color: #475569;
            border-color: #cbd5e1;
        }

        .btn-secondary:hover {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        /* SLIP PAGE & PAPER WRAPPER */
        .slip-page {
            max-width: 820px;
            margin: 0 auto 30px auto;
            page-break-after: always;
            break-after: page;
        }

        .slip-page:last-child {
            margin-bottom: 0;
            page-break-after: auto;
            break-after: auto;
        }

        .slip-paper {
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #cbd5e1;
            padding: 24px 28px;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
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

        .kop-container,
        .kop-divider,
        .data-karyawan-box,
        .tables-grid,
        .gaji-bersih-box,
        .signatures-grid,
        .doc-subtle-note {
            position: relative;
            z-index: 1;
        }

        /* KOP SLIP GAJI */
        .kop-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            gap: 16px;
        }

        .kop-left {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-grow: 1;
        }

        .kop-logo {
            max-width: 65px;
            max-height: 65px;
            object-fit: contain;
        }

        .kop-text {
            line-height: 1.25;
        }

        .slip-title {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #000000;
            margin-bottom: 2px;
        }

        .company-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0b52aa;
            margin-bottom: 3px;
        }

        .company-sub {
            font-size: 10px;
            color: #475569;
            line-height: 1.35;
        }

        .kop-right {
            text-align: right;
            flex-shrink: 0;
        }

        .periode-box {
            border: 1px solid #000000;
            border-radius: 6px;
            padding: 5px 12px;
            background: #ffffff;
            text-align: center;
            min-width: 140px;
        }

        .periode-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #475569;
        }

        .periode-month {
            font-size: 12px;
            font-weight: 800;
            color: #000000;
        }

        .kop-divider {
            height: 2px;
            background-color: #000000;
            margin-bottom: 12px;
        }

        /* DATA KARYAWAN BOX */
        .data-karyawan-box {
            border: 1px solid #000000;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
            background: #ffffff;
        }

        .box-header {
            background-color: #f1f5f9;
            border-bottom: 1px solid #000000;
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
        }

        .box-body {
            padding: 8px 12px;
            background: transparent;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }

        .data-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .data-table td.col-lbl {
            width: 110px;
            color: #000000;
            font-weight: 500;
        }

        .data-table td.col-sep {
            width: 14px;
            text-align: center;
            color: #000000;
            font-weight: 600;
        }

        .data-table td.col-val {
            color: #000000;
            padding-right: 20px;
        }

        /* TWO-COLUMN TABLES: PENDAPATAN & POTONGAN */
        .tables-grid {
            display: flex;
            gap: 12px;
            margin-bottom: 14px;
            align-items: stretch;
        }

        .table-col {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .calc-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #1e293b;
            font-size: 11.5px;
            height: 100%;
        }

        .calc-table th,
        .calc-table td {
            border: 1px solid #1e293b;
            padding: 5px 8px;
        }

        .calc-table thead th.th-title {
            background-color: #dbeafe;
            color: #0b52aa;
            text-align: center;
            font-weight: 800;
            font-size: 11.5px;
            letter-spacing: 0.5px;
        }

        .calc-table thead th.th-col {
            background-color: #f8fafc;
            color: #000000;
            font-weight: 700;
            font-size: 10px;
            text-align: center;
        }

        .calc-table tbody td.td-no {
            width: 32px;
            text-align: center;
            color: #000000;
            font-weight: 600;
        }

        .calc-table tbody td.td-desc {
            color: #000000;
            font-weight: 500;
        }

        .calc-table tbody td.td-amount {
            width: 120px;
            text-align: right;
            font-weight: 600;
            color: #000000;
        }

        .calc-table tfoot td.td-total-lbl {
            background-color: #f1f5f9;
            font-weight: 800;
            text-align: right;
            padding-right: 12px;
            color: #000000;
            font-size: 11px;
        }

        .calc-table tfoot td.td-total-val {
            background-color: #f1f5f9;
            font-weight: 800;
            text-align: right;
            color: #000000;
            font-size: 11.5px;
        }

        .money-cell {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .curr-symbol {
            color: #000000;
            font-size: 10px;
            font-weight: 500;
        }

        .curr-val {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
        }

        /* GAJI BERSIH (NET SALARY) BOX */
        .gaji-bersih-box {
            border: 2px solid #000000;
            border-radius: 6px;
            padding: 8px 14px;
            background-color: #f8fafc;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .gaji-bersih-left {
            line-height: 1.3;
        }

        .gaji-bersih-title {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #000000;
        }

        .gaji-bersih-sub {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
        }

        .gaji-bersih-right {
            text-align: right;
        }

        .gaji-bersih-amount {
            font-size: 16px;
            font-weight: 800;
            color: #0b52aa;
            font-family: 'JetBrains Mono', monospace;
        }

        .gaji-bersih-terbilang {
            font-size: 10.5px;
            color: #334155;
            font-style: italic;
            margin-top: 1px;
            font-weight: 500;
        }

        /* SIGNATURES GRID */
        .signatures-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 12px;
            padding: 0 20px;
        }

        .signature-col {
            text-align: center;
            min-width: 180px;
        }

        .signature-date {
            font-size: 11px;
            color: #000000;
            margin-bottom: 4px;
        }

        .signature-title {
            font-size: 11.5px;
            font-weight: 600;
            color: #000000;
        }

        .signature-space {
            height: 60px;
        }

        .signature-name {
            font-weight: 800;
            text-decoration: underline;
            color: #000000;
            font-size: 12.5px;
        }

        .signature-nip {
            font-size: 11px;
            color: #0f172a;
            margin-top: 3px;
            font-weight: 500;
        }

        .doc-subtle-note {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
            font-size: 9.5px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.4;
        }

        /* PRINT MEDIA STYLING */
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

            .slip-page {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .slip-page:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
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
        // Helper fungsi terbilang angka Indonesia
        if (!function_exists('terbilang_id')) {
            function terbilang_id($angka): string {
                $angka = abs((float) $angka);
                if ($angka == 0) return 'Nol';
                $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
                $hasil = '';

                if ($angka < 12) {
                    $hasil = $huruf[(int) $angka];
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

                return trim(preg_replace('/\s+/', ' ', $hasil));
            }
        }

        // Alamat & Lokasi Perusahaan (Dinamis dari Profil Perusahaan)
        $locationParts = [];
        if (!empty($company->address)) {
            $locationParts[] = $company->address;
        }
        if (!empty($company->city) && (!empty($company->address) ? !str_contains(strtolower((string) $company->address), strtolower((string) $company->city)) : true)) {
            $locationParts[] = $company->city;
        }
        if (!empty($company->province) && (!empty($company->address) ? !str_contains(strtolower((string) $company->address), strtolower((string) $company->province)) : true)) {
            $locationParts[] = $company->province;
        }
        if (!empty($company->postal_code)) {
            $locationParts[] = 'Kode Pos ' . $company->postal_code;
        }
        $companyFullAddress = implode(', ', $locationParts);

        // Kota untuk Pengesahan
        $city = !empty($company->city) ? trim($company->city) : null;

        // Tanggal Dokumen
        $docDate = $period->payment_date 
            ? $period->payment_date->locale('id')->translatedFormat('d F Y') 
            : ($period->end_date ? $period->end_date->locale('id')->translatedFormat('d F Y') : now()->locale('id')->translatedFormat('d F Y'));

        // Approver / Admin Pengesah
        $adminUser = null;
        if ($period->submitter && $period->submitter->hasRole('admin')) {
            $adminUser = $period->submitter;
        } elseif ($period->creator && $period->creator->hasRole('admin')) {
            $adminUser = $period->creator;
        } elseif ($period->approver && $period->approver->hasRole('admin')) {
            $adminUser = $period->approver;
        } else {
            $adminUser = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first() ?? $period->approver;
        }

        $adminName = $adminUser?->name ?? 'Admin Demo';
        $adminEmployee = $adminUser?->employee;
        if (! $adminEmployee && $adminUser) {
            $adminEmployee = \App\Models\Employee::where('user_id', $adminUser->id)->first()
                ?? \App\Models\Employee::whereHas('detail', fn ($q) => $q->where('email', $adminUser->email))->first();
        }

        $adminPosition = $adminEmployee?->position?->name;
        $adminJobTitle = $adminPosition ?: ($adminUser?->hasRole('admin') ? 'HR Manager' : 'Direktur');
    @endphp

    <!-- Top Action Bar (On-screen sticky controls) -->
    <div class="print-actions">
        <div>
            <a href="{{ route('payrolls.show', $period) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Periode
            </a>
        </div>
        <div style="font-weight: 700; font-size: 13.5px; color: #0f172a;">
            Cetak Seluruh Slip Gaji: <span style="color: #0b52aa;">{{ $period->period_label }}</span> ({{ $items->count() }} Karyawan)
        </div>
        <div class="print-actions-right">
            <button onclick="window.print()" class="btn btn-primary" title="Cetak seluruh slip atau simpan sebagai dokumen PDF">
                <i class="fas fa-print"></i> Cetak Semua ({{ $items->count() }} Slip)
            </button>
        </div>
    </div>

    <!-- Iterate each employee payslip -->
    @foreach ($items as $item)
        @php
            // Persiapan data Pendapatan
            $earningsList = [];
            $earningsList[] = [
                'name' => 'Gaji Pokok' . ($item->human_notes ? ' (' . $item->human_notes . ')' : ''),
                'amount' => (float) $item->basic_salary,
            ];
            foreach ($item->allowanceLines as $line) {
                $earningsList[] = [
                    'name' => $line->name . ($line->category?->value === 'tax' ? ' (Gross-Up)' : ''),
                    'amount' => (float) $line->amount,
                ];
            }

            // Persiapan data Potongan (hanya yang bernilai != 0)
            $deductionsList = [];
            foreach ($item->deductionLines as $line) {
                if ((float) $line->amount != 0.0) {
                    $deductionsList[] = [
                        'name' => $line->name,
                        'amount' => (float) $line->amount,
                    ];
                }
            }

            // Menyeimbangkan jumlah baris agar footer tabel selalu sejajar rapi
            $maxRows = max(count($earningsList), count($deductionsList), 4);
        @endphp

        <div class="slip-page">
            <div class="slip-paper">

                @if (! $item->isPaid())
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

                <!-- KOP SLIP GAJI -->
                <div class="kop-container">
                    <div class="kop-left">
                        @if (!empty($company->logo_url))
                            <img src="{{ $company->logo_url }}" alt="Logo" class="kop-logo">
                        @endif
                        <div class="kop-text">
                            <h1 class="slip-title">SLIP GAJI KARYAWAN</h1>
                            <h2 class="company-title">{{ strtoupper($company->name) }}</h2>
                            @if (!empty($companyFullAddress))
                                <div class="company-sub">{{ $companyFullAddress }}</div>
                            @endif
                            <div class="company-sub">
                                @if ($company->phone) Telp. {{ $company->phone }} @endif
                                @if ($company->phone && $company->email) &nbsp;|&nbsp; @endif
                                @if ($company->email) email: {{ $company->email }} @endif
                                @if (($company->phone || $company->email) && $company->website) &nbsp;|&nbsp; @endif
                                @if ($company->website) web: {{ preg_replace('#^https?://#', '', $company->website) }} @endif
                            </div>
                        </div>
                    </div>
                    <div class="kop-right">
                        <div class="periode-box">
                            <div class="periode-label">PERIODE GAJI</div>
                            <div class="periode-month">{{ strtoupper($period->month_name) }} {{ $period->year }}</div>
                        </div>
                    </div>
                </div>

                <!-- GARIS PEMISAH KOP SURAT -->
                <div class="kop-divider"></div>

                <!-- 1. DATA KARYAWAN -->
                <div class="data-karyawan-box">
                    <div class="box-header">DATA KARYAWAN</div>
                    <div class="box-body">
                        <table class="data-table">
                            <tr>
                                <td class="col-lbl">Nama</td>
                                <td class="col-sep">:</td>
                                <td class="col-val"><strong>{{ $item->employee?->full_name ?? '-' }}</strong></td>
                                <td class="col-lbl">Unit Kerja / Dept</td>
                                <td class="col-sep">:</td>
                                <td class="col-val">{{ $item->employee?->department?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="col-lbl">NIP / ID</td>
                                <td class="col-sep">:</td>
                                <td class="col-val">{{ $item->employee?->detail?->employee_code ?: ($item->employee?->nik ?: '-') }}</td>
                                <td class="col-lbl">Tgl. Bergabung</td>
                                <td class="col-sep">:</td>
                                <td class="col-val">{{ $item->employee?->join_date ? $item->employee->join_date->locale('id')->translatedFormat('d F Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="col-lbl">Jabatan</td>
                                <td class="col-sep">:</td>
                                <td class="col-val">{{ $item->employee?->position?->name ?? '-' }}</td>
                                <td class="col-lbl">Rekening</td>
                                <td class="col-sep">:</td>
                                <td class="col-val">{{ $item->bank_name ?? '-' }} ({{ $item->bank_account_number ?? '-' }})</td>
                            </tr>
                            <tr>
                                <td class="col-lbl">Status</td>
                                <td class="col-sep">:</td>
                                <td class="col-val">{{ $item->employee?->employment_status_label ?? '-' }}</td>
                                <td class="col-lbl">Status Pajak</td>
                                <td class="col-sep">:</td>
                                <td class="col-val">{{ $item->ptkp_status?->value ?? ($item->employee?->detail?->ptkp_status?->value ?? 'TK/0') }} {{ $item->tax_method ? '('.$item->tax_method->label().')' : '' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- 2. TABEL PENDAPATAN & POTONGAN BERSEBELAHAN -->
                <div class="tables-grid">
                    <!-- TABEL PENDAPATAN -->
                    <div class="table-col">
                        <table class="calc-table">
                            <thead>
                                <tr>
                                    <th colspan="3" class="th-title">PENDAPATAN</th>
                                </tr>
                                <tr>
                                    <th class="th-col" style="width: 32px;">NO</th>
                                    <th class="th-col">URAIAN</th>
                                    <th class="th-col" style="width: 120px;">JUMLAH (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($i = 0; $i < $maxRows; $i++)
                                    @if (isset($earningsList[$i]))
                                        @php
                                            $earnAmt = (float) $earningsList[$i]['amount'];
                                            $earnStr = $earnAmt == 0 ? '-' : number_format($earnAmt, 0, ',', '.');
                                        @endphp
                                        <tr>
                                            <td class="td-no">{{ $i + 1 }}</td>
                                            <td class="td-desc">{{ $earningsList[$i]['name'] }}</td>
                                            <td class="td-amount">
                                                <div class="money-cell">
                                                    <span class="curr-symbol">Rp.</span>
                                                    <span class="curr-val">{{ $earnStr }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td class="td-no">&nbsp;</td>
                                            <td class="td-desc">&nbsp;</td>
                                            <td class="td-amount">&nbsp;</td>
                                        </tr>
                                    @endif
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="td-total-lbl">TOTAL PENDAPATAN</td>
                                    <td class="td-total-val">
                                        <div class="money-cell">
                                            <span class="curr-symbol">Rp.</span>
                                            <span class="curr-val">{{ number_format((float) $item->basic_salary + (float) $item->total_allowances, 0, ',', '.') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- TABEL POTONGAN -->
                    <div class="table-col">
                        <table class="calc-table">
                            <thead>
                                <tr>
                                    <th colspan="3" class="th-title">POTONGAN</th>
                                </tr>
                                <tr>
                                    <th class="th-col" style="width: 32px;">NO</th>
                                    <th class="th-col">URAIAN</th>
                                    <th class="th-col" style="width: 120px;">JUMLAH (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($j = 0; $j < $maxRows; $j++)
                                    @if (isset($deductionsList[$j]))
                                        @php
                                            $amt = (float) $deductionsList[$j]['amount'];
                                            $amtStr = $amt == 0 ? '-' : ($amt < 0 ? '-' . number_format(abs($amt), 0, ',', '.') : number_format($amt, 0, ',', '.'));
                                        @endphp
                                        <tr>
                                            <td class="td-no">{{ $j + 1 }}</td>
                                            <td class="td-desc">{{ $deductionsList[$j]['name'] }}</td>
                                            <td class="td-amount">
                                                <div class="money-cell">
                                                    <span class="curr-symbol">Rp.</span>
                                                    <span class="curr-val">{{ $amtStr }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td class="td-no">&nbsp;</td>
                                            <td class="td-desc">&nbsp;</td>
                                            <td class="td-amount">&nbsp;</td>
                                        </tr>
                                    @endif
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="td-total-lbl">TOTAL POTONGAN</td>
                                    <td class="td-total-val">
                                        <div class="money-cell">
                                            <span class="curr-symbol">Rp.</span>
                                            <span class="curr-val">{{ number_format((float) $item->total_deductions, 0, ',', '.') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- 3. KOTAK GAJI BERSIH YANG DITERIMA (TAKE HOME PAY BULANAN) -->
                <div class="gaji-bersih-box">
                    <div class="gaji-bersih-left">
                        <div class="gaji-bersih-title">GAJI BERSIH YANG DITERIMA (THP)</div>
                        <div class="gaji-bersih-sub">(TOTAL PENDAPATAN – TOTAL POTONGAN)</div>
                    </div>
                    <div class="gaji-bersih-right">
                        <div class="gaji-bersih-amount">Rp {{ number_format((float) $item->net_salary, 0, ',', '.') }}</div>
                        <div class="gaji-bersih-terbilang">({{ terbilang_id($item->net_salary) }} Rupiah)</div>
                    </div>
                </div>

                <!-- 3B. INFORMASI TAMBAHAN: TELAH DITERIMA MINGGUAN (NON-THP & READ-ONLY) -->
                @php
                    $otSum = $overtimeSummaries[$item->id] ?? null;
                @endphp
                @if ($otSum && $otSum['grand_total'] > 0)
                    <div class="data-karyawan-box mb-3" style="border: 1.5px dashed #0284c7; background-color: #f0f9ff;">
                        <div class="box-header d-flex justify-content-between align-items-center" style="background-color: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 11px;">
                            <span><i class="fas fa-info-circle mr-1"></i> INFORMASI TAMBAHAN: TELAH DITERIMA MINGGUAN (NON-THP)</span>
                            <span style="font-weight: 500; font-size: 10px; text-transform: none; color: #0284c7;">Sudah Dicairkan Mingguan</span>
                        </div>
                        <div class="box-body" style="padding: 8px 12px;">
                            <table class="data-table" style="font-size: 11px;">
                                <tr>
                                    <td style="width: 28%; font-weight: 600; color: #1e293b;">Total Uang Makan Mingguan:</td>
                                    <td style="width: 22%; font-weight: 700; color: #0284c7;">
                                        Rp {{ number_format($otSum['total_meal_allowance'], 0, ',', '.') }}
                                    </td>
                                    <td style="width: 28%; font-weight: 600; color: #1e293b;">Total Upah Lembur Mingguan:</td>
                                    <td style="width: 22%; font-weight: 700; color: #0b52aa;">
                                        Rp {{ number_format($otSum['total_overtime_pay'], 0, ',', '.') }}
                                        @if ($otSum['total_hours'] > 0)
                                            <span style="font-size: 10px; font-weight: normal; color: #64748b;">({{ number_format($otSum['total_hours'], 1) }} jam)</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" style="padding-top: 4px; font-size: 10px; color: #64748b; font-style: italic;">
                                        * Catatan: Nominal uang makan dan upah lembur di atas telah diterima setiap minggu dan tidak diakumulasikan ke saldo transfer akhir bulan.
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- 4. BAGIAN TANDA TANGAN: DITERIMA OLEH & DISETUJUI OLEH -->
                <div class="signatures-grid">
                    <!-- KIRI: DITERIMA OLEH (KARYAWAN) -->
                    <div class="signature-col">
                        <div class="signature-date">&nbsp;</div>
                        <div class="signature-title">Diterima Oleh,</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $item->employee?->full_name ?? '-' }}</div>
                        <div class="signature-nip">Karyawan</div>
                    </div>

                    <!-- KANAN: DISETUJUI OLEH (ADMIN) -->
                    <div class="signature-col">
                        <div class="signature-date">{{ $city ? $city . ', ' : '' }}{{ $docDate }}</div>
                        <div class="signature-title">Disetujui Oleh,</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $adminName }}</div>
                        <div class="signature-nip">{{ $adminJobTitle }}</div>
                    </div>
                </div>

                <!-- Catatan Kaki Otomatis -->
                <div class="doc-subtle-note">
                    Dokumen resmi slip gaji ini diterbitkan otomatis melalui sistem <strong>{{ $company->name ?: config('app.name', 'HRM') }}</strong> pada {{ now()->locale('id')->translatedFormat('d/m/Y H:i') }} WIB dan sah tanpa tanda tangan basah.
                </div>

            </div>
        </div>
    @endforeach

</body>
</html>
