<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - {{ $item->payslip_number }} - {{ $item->employee?->full_name ?? 'Karyawan' }}</title>
    
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

        /* Top Action Buttons (Hidden when printing) */
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

        .btn-secondary:hover {
            background-color: #f8fafc;
            color: #1e293b;
        }

        .btn-whatsapp {
            background-color: #10b981;
            color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        .btn-whatsapp:hover {
            background-color: #059669;
        }

        /* Slip Paper Sheet */
        .slip-paper {
            position: relative;
            overflow: hidden;
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 30px 36px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            color: #000000;
        }

        /* Subtle Watermark Logo */
        .slip-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 360px;
            height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 0;
            opacity: 0.06;
            user-select: none;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .slip-watermark img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: grayscale(100%);
        }

        /* Content containers positioned above watermark */
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

        /* Header / Kop */
        .kop-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .kop-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-grow: 1;
        }

        .kop-logo {
            max-height: 70px;
            max-width: 95px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .kop-text {
            text-align: left;
        }

        .slip-title {
            font-size: 19px;
            font-weight: 800;
            color: #002b66;
            letter-spacing: 0.5px;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .company-title {
            font-size: 16px;
            font-weight: 800;
            color: #002b66;
            letter-spacing: 0.3px;
            line-height: 1.3;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .company-sub {
            font-size: 11px;
            color: #1e293b;
            margin-top: 2px;
            line-height: 1.35;
        }

        /* Periode Gaji Box */
        .kop-right {
            flex-shrink: 0;
        }

        .periode-box {
            border: 1.5px solid #1e293b;
            padding: 10px 18px;
            text-align: center;
            min-width: 170px;
            background: transparent;
        }

        .periode-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
        }

        .periode-month {
            font-size: 15px;
            font-weight: 800;
            color: #000000;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Kop Divider Bar */
        .kop-divider {
            height: 3px;
            background-color: #002b66;
            margin-top: 12px;
            margin-bottom: 16px;
        }

        /* DATA KARYAWAN BOX */
        .data-karyawan-box {
            border: 1px solid #1e293b;
            margin-bottom: 14px;
        }

        .box-header {
            background-color: #dbeafe;
            border-bottom: 1px solid #1e293b;
            padding: 5px 12px;
            font-size: 12px;
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
            text-align: center;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
            padding: 6px;
        }

        .calc-table thead th.th-col {
            background-color: transparent;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            padding: 5px;
            color: #000000;
        }

        .calc-table tbody td {
            vertical-align: middle;
            background-color: transparent;
        }

        .calc-table tbody td.td-no {
            text-align: center;
            width: 32px;
            font-weight: 600;
        }

        .calc-table tbody td.td-desc {
            text-align: left;
            line-height: 1.35;
        }

        .calc-table tbody td.td-amount {
            width: 120px;
            white-space: nowrap;
            font-weight: 500;
            padding: 5px 8px;
        }

        .calc-table tfoot td {
            background-color: #dbeafe;
            font-weight: 800;
            font-size: 12px;
            padding: 6px 8px;
            color: #000000;
        }

        .calc-table tfoot td.td-total-lbl {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .calc-table tfoot td.td-total-val {
            width: 120px;
            white-space: nowrap;
            padding: 6px 8px;
        }

        /* Accounting Format: Rp. on far left, Number on far right */
        .money-cell {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .curr-symbol {
            text-align: left;
            font-weight: 500;
            color: #000000;
            white-space: nowrap;
        }

        .curr-val {
            text-align: right;
            font-weight: 500;
            color: #000000;
            white-space: nowrap;
            margin-left: auto;
            font-variant-numeric: tabular-nums;
        }

        .td-total-val .curr-symbol,
        .td-total-val .curr-val {
            font-weight: 800;
        }

        /* GAJI BERSIH BANNER (SPLIT BOX) */
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
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .gaji-bersih-title {
            font-size: 13.5px;
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
            font-size: 21px;
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
            line-height: 1.35;
        }

        /* TANDA TANGAN (DITERIMA OLEH & DISETUJUI OLEH) */
        .signatures-grid {
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
            gap: 40px;
        }

        .signature-col {
            flex: 1;
            text-align: center;
            font-size: 11.5px;
            color: #000000;
        }

        .signature-date {
            font-size: 11.5px;
            color: #000000;
            margin-bottom: 4px;
            min-height: 17px;
        }

        .signature-title {
            font-weight: 800;
            font-size: 12.5px;
            color: #000000;
        }

        .signature-space {
            height: 70px;
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
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px dashed #cbd5e1;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.4;
        }

        /* PRINT MEDIA STYLING */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
                margin: 0;
            }

            .print-actions {
                display: none !important;
            }

            .slip-paper {
                box-shadow: none;
                border: none;
                padding: 10px 15px;
                max-width: 100%;
                margin: 0;
                position: relative;
                overflow: hidden;
            }

            .slip-watermark {
                opacity: 0.07 !important;
                display: flex !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
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
                $bilangan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
                $hasil = '';

                if ($angka < 12) {
                    $hasil = $bilangan[(int)$angka];
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

        // WhatsApp direct link generator
        $phone = $item->employee?->detail?->phone ?? '';
        $cleanedPhone = preg_replace('/[^0-9]/', '', (string) $phone);
        if (str_starts_with($cleanedPhone, '0')) {
            $cleanedPhone = '62' . substr($cleanedPhone, 1);
        }
        $periodLabel = $period->period_label ?? 'Bulan Ini';
        $waMsg = "Halo {$item->employee?->full_name},\n\n";
        $waMsg .= "Berikut adalah ringkasan slip gaji Anda untuk {$periodLabel} dari {$company->name}:\n";
        $waMsg .= "• No Slip: {$item->payslip_number}\n";
        $waMsg .= "• Gaji Pokok: {$item->formatted_basic_salary}\n";
        $waMsg .= "• Total Pendapatan (+): Rp " . number_format((float) $item->basic_salary + (float) $item->total_allowances, 0, ',', '.') . "\n";
        $waMsg .= "• Total Potongan (-): {$item->formatted_total_deductions}\n";
        $waMsg .= "• Gaji Bersih Diterima: {$item->formatted_net_salary}\n\n";
        $waMsg .= "Status Pembayaran: " . ($item->payment_status === 'paid' ? 'LUNAS / DIBAYAR' : 'PROSES') . "\n";
        $waMsg .= "Terima kasih atas dedikasi dan kerja keras Anda!";
        $waUrl = !empty($cleanedPhone) ? "https://wa.me/{$cleanedPhone}?text=" . rawurlencode($waMsg) : null;

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

        // Kota untuk Pengesahan (Dinamis dari Profil Perusahaan)
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

        // Jabatan Admin Pengesah (hanya nama jabatan saja)
        $adminEmployee = $adminUser?->employee;
        if (! $adminEmployee && $adminUser) {
            $adminEmployee = \App\Models\Employee::where('user_id', $adminUser->id)->first()
                ?? \App\Models\Employee::whereHas('detail', fn ($q) => $q->where('email', $adminUser->email))->first();
        }

        $adminPosition = $adminEmployee?->position?->name;
        if ($adminPosition) {
            $adminJobTitle = $adminPosition;
        } else {
            $adminJobTitle = $adminUser?->hasRole('admin') ? 'HR Manager' : 'Direktur';
        }
    @endphp

    <!-- Top Actions Header (On-screen) -->
    <div class="print-actions">
        <div>
            <a href="javascript:window.close()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Tutup
            </a>
        </div>
        <div class="print-actions-right">
            @if ($waUrl)
                <a href="{{ $waUrl }}" target="_blank" class="btn btn-whatsapp" title="Kirim ringkasan slip ke nomor WhatsApp karyawan">
                    <i class="fab fa-whatsapp"></i> Kirim ke WhatsApp
                </a>
            @endif
            <button onclick="window.print()" class="btn btn-primary" title="Cetak slip atau simpan sebagai dokumen PDF">
                <i class="fas fa-print"></i> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <!-- Official Payslip Sheet (Standard Indonesian Corporate/School Template) -->
    <div class="slip-paper">

        @if (!empty($company->logo_url))
            <div class="slip-watermark">
                <img src="{{ $company->logo_url }}" alt="Watermark Logo">
            </div>
        @endif

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

        <!-- 3. KOTAK GAJI BERSIH YANG DITERIMA -->
        <div class="gaji-bersih-box">
            <div class="gaji-bersih-left">
                <div class="gaji-bersih-title">GAJI BERSIH YANG DITERIMA</div>
                <div class="gaji-bersih-sub">(TOTAL PENDAPATAN – TOTAL POTONGAN)</div>
            </div>
            <div class="gaji-bersih-right">
                <div class="gaji-bersih-amount">Rp {{ number_format((float) $item->net_salary, 0, ',', '.') }}</div>
                <div class="gaji-bersih-terbilang">({{ terbilang_id($item->net_salary) }} Rupiah)</div>
            </div>
        </div>

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

</body>
</html>
