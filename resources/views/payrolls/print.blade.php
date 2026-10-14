<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - {{ $item->payslip_number }} - {{ $item->employee?->full_name ?? 'Karyawan' }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333333;
        }

        body {
            background-color: #f4f6f9;
            padding: 24px;
        }

        .slip-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            padding: 36px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0b52aa;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .company-info {
            display: flex;
            align-items: center;
        }

        .company-logo {
            max-height: 60px;
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
            color: #666666;
            margin-top: 2px;
        }

        .doc-title {
            text-align: right;
        }

        .doc-title h2 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #222222;
        }

        .doc-title .slip-no {
            font-size: 13px;
            font-weight: 600;
            color: #0b52aa;
            margin-top: 2px;
        }

        .doc-title .period {
            font-size: 12px;
            color: #777777;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .meta-table td.label {
            color: #666666;
            width: 130px;
        }

        .meta-table td.value {
            font-weight: 600;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 10px;
            background-color: #f1f5f9;
            border-left: 3px solid #0b52aa;
            margin-bottom: 10px;
        }

        .components-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .comp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .comp-table th, .comp-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eeeeee;
        }

        .comp-table td.amount {
            text-align: right;
            font-weight: 600;
        }

        .comp-table tr.total-row td {
            border-top: 1px solid #cccccc;
            border-bottom: 2px solid #cccccc;
            font-weight: 700;
        }

        .take-home-pay-box {
            background-color: #f0f7ff;
            border: 1px solid #b9d9fc;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
            margin-bottom: 30px;
        }

        .take-home-pay-box .thp-label {
            font-size: 13px;
            font-weight: 700;
            color: #0b52aa;
            text-transform: uppercase;
        }

        .take-home-pay-box .thp-sub {
            font-size: 12px;
            color: #666666;
        }

        .take-home-pay-box .thp-value {
            font-size: 24px;
            font-weight: 800;
            color: #0b52aa;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
        }

        .signature-box {
            padding-top: 60px;
            border-bottom: 1px solid #333333;
            width: 200px;
            margin: 0 auto;
            font-weight: 600;
        }

        .signature-title {
            margin-top: 4px;
            color: #666666;
        }

        .print-actions {
            max-width: 800px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: #0b52aa;
            color: #ffffff;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: #ffffff;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .slip-container {
                border: none;
                box-shadow: none;
                padding: 0;
            }
            .print-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="print-actions">
        <a href="javascript:window.close()" class="btn btn-secondary">Tutup</a>
        <button onclick="window.print()" class="btn btn-primary">Cetak / Simpan PDF</button>
    </div>

    <div class="slip-container">
        <!-- Header / Kop -->
        <div class="header">
            <div class="company-info">
                <img src="{{ $company->logo_url }}" alt="Logo" class="company-logo">
                <div>
                    <div class="company-name">{{ $company->name }}</div>
                    <div class="company-meta">{{ $company->address ?? 'Indonesia' }}</div>
                    <div class="company-meta">Telp: {{ $company->phone ?? '-' }} | Email: {{ $company->email ?? '-' }}</div>
                </div>
            </div>
            <div class="doc-title">
                <h2>SLIP GAJI</h2>
                <div class="slip-no">{{ $item->payslip_number }}</div>
                <div class="period">{{ $period->period_label }}</div>
            </div>
        </div>

        <!-- Metadata Karyawan -->
        <div class="meta-grid">
            <table class="meta-table">
                <tr>
                    <td class="label">Nama Karyawan</td>
                    <td class="value">: {{ $item->employee?->full_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">NIK Internal</td>
                    <td class="value">: {{ $item->employee?->detail?->employee_code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Departemen</td>
                    <td class="value">: {{ $item->employee?->department?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Jabatan</td>
                    <td class="value">: {{ $item->employee?->position?->name ?? '-' }}</td>
                </tr>
                @if ($company->enable_pph21 && $item->employee?->detail)
                    <tr>
                        <td class="label">Status Pajak</td>
                        <td class="value">: {{ $item->tax_method?->label() ?? 'Gross' }} / PTKP {{ $item->ptkp_status?->value ?? 'TK/0' }} ({{ ($period->month ?? $item->payrollPeriod?->month) == 12 ? 'Pasal 17 UU HPP' : ($item->ter_category ? 'TER ' . $item->ter_category : 'TER A') }})</td>
                    </tr>
                @endif
            </table>

            <table class="meta-table">
                <tr>
                    <td class="label">Status Kerja</td>
                    <td class="value">: {{ $item->employee?->employment_status_label ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Rekening Transfer</td>
                    <td class="value">: {{ $item->bank_name ?? '-' }} ({{ $item->bank_account_number ?? '-' }})</td>
                </tr>
                <tr>
                    <td class="label">Atas Nama</td>
                    <td class="value">: {{ $item->bank_account_name ?? ($item->employee?->full_name ?? '-') }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Bayar</td>
                    <td class="value">: {{ $period->payment_date ? $period->payment_date->format('d/m/Y') : 'Dalam Proses' }}</td>
                </tr>
            </table>
        </div>

        <!-- Components Breakdown -->
        <div class="components-grid">
            <!-- Earnings -->
            <div>
                <div class="section-title">PENGHASILAN (EARNINGS)</div>
                <table class="comp-table">
                    <tr>
                        <td>
                            Gaji Pokok
                            @if ($item->notes)
                                <div style="font-size: 10px; color: #64748b; font-weight: normal; margin-top: 2px;">({{ $item->notes }})</div>
                            @endif
                        </td>
                        <td class="amount">{{ $item->formatted_basic_salary }}</td>
                    </tr>
                    @foreach ($item->allowanceLines as $line)
                        <tr>
                            <td>
                                {{ $line->name }}
                                @if ($line->category->value === 'tax')
                                    <span style="font-size: 11px; color: #0b52aa; font-weight: 600;">(Gross-Up)</span>
                                @endif
                            </td>
                            <td class="amount">+{{ $line->formatted_amount }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total Penghasilan (A)</td>
                        <td class="amount" style="color: #28a745;">
                            Rp {{ number_format((float) $item->basic_salary + (float) $item->total_allowances, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Deductions -->
            <div>
                <div class="section-title">PEMOTONGAN (DEDUCTIONS)</div>
                <table class="comp-table">
                    @forelse ($item->deductionLines->filter(fn ($l) => (float) $l->amount != 0.0) as $line)
                        @php
                            $lineAmt = (float) $line->amount;
                        @endphp
                        <tr>
                            <td>
                                {{ $line->name }}
                                @if ($line->category->value === 'cash_advance')
                                    <span style="font-size: 11px; color: #b7791f; font-weight: 600;">(Kasbon)</span>
                                @elseif ($line->category->value === 'tax')
                                    <span style="font-size: 11px; color: #0b52aa; font-weight: 600;">(PPh 21)</span>
                                @elseif ($line->category->value === 'bpjs')
                                    <span style="font-size: 11px; color: #166534; font-weight: 600;">(BPJS)</span>
                                @endif
                            </td>
                            <td class="amount" style="{{ $lineAmt < 0 ? 'color: #28a745;' : '' }}">
                                {{ $lineAmt < 0 ? '+Rp ' . number_format(abs($lineAmt), 0, ',', '.') : '-' . $line->formatted_amount }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: #888888; padding: 12px 0;">Tidak ada potongan</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td>Total Pemotongan (B)</td>
                        <td class="amount" style="{{ (float) $item->total_deductions < 0 ? 'color: #28a745;' : 'color: #dc3545;' }}">
                            {{ (float) $item->total_deductions < 0 ? '+Rp ' . number_format(abs((float) $item->total_deductions), 0, ',', '.') : '-' . $item->formatted_total_deductions }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Take Home Pay Box -->
        <div class="take-home-pay-box">
            <div>
                <div class="thp-label">Gaji Bersih / Take Home Pay (A - B)</div>
                <div class="thp-sub">Ditransfer melalui {{ $item->bank_name ?? 'Payroll' }}</div>
            </div>
            <div class="thp-value">{{ $item->formatted_net_salary }}</div>
        </div>

        @if ((float) $item->total_company_contributions > 0)
            <div style="margin-top: 15px; padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 11.5px; color: #475569;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: #0b52aa;">Tanggungan BPJS Perusahaan (Manfaat Non-Tunai):</strong>
                        <span style="color: #64748b; margin-left: 4px;">Dibayar penuh oleh kantor di luar gaji bersih.</span>
                    </div>
                    <div style="font-weight: 700; color: #0b52aa;">
                        {{ $item->formatted_total_company_contributions }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Tanda Tangan -->
        <div class="signature-grid">
            <div>
                <div style="margin-bottom: 8px;">Penerima,</div>
                <div class="signature-box">{{ $item->employee?->full_name ?? '-' }}</div>
                <div class="signature-title">Karyawan</div>
            </div>
            <div>
                <div style="margin-bottom: 8px;">Diverifikasi oleh,</div>
                <div class="signature-box">{{ $period->approver?->name ?? 'Management' }}</div>
                <div class="signature-title">Direktur / HRD</div>
            </div>
        </div>

        <div style="margin-top: 40px; font-size: 11px; color: #888888; text-align: center;">
            <em>Dokumen slip gaji ini resmi dicetak dari sistem Larashiz HRM pada {{ now()->format('d/m/Y H:i') }}.</em>
        </div>
    </div>

</body>
</html>
