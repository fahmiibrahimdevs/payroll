<div>
    <!-- ================= 1. SECTION HEADER (NATIVE STISLA) ================= -->
    <x-slot name="header">
        <div class="section-header-back">
            <a href="{{ route('payrolls.weekly') }}" class="btn btn-icon" title="Kembali ke Gaji Mingguan">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
        <h1>Presensi &amp; Uang Makan Mingguan</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('payrolls.weekly') }}">Gaji Mingguan</a></div>
            <div class="breadcrumb-item">Presensi Mingguan</div>
        </div>
    </x-slot>

    <!-- Native Section Title & Lead -->
    <h2 class="section-title mt-0">Presensi &amp; Uang Makan: {{ $period->name }}</h2>
    <p class="section-lead">
        Kelola hari masuk kerja, upah harian, dan uang makan mingguan seluruh karyawan dengan siklus mingguan Jum'at s/d Kamis.
    </p>

    <!-- ================= 2. 4 MODERN STAT CARDS (REKAP BIAYA MINGGUAN) ================= -->
    <div class="cash-advance-stat-grid mb-4">
        <!-- 1. Total Biaya Mingguan -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Biaya Mingguan">Total Biaya Mingguan</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            Rp {{ number_format($grandTotal, 0, ',', '.') }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-chart-line text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Dari {{ $totalEmployees }} karyawan gajian
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Upah Harian -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Upah Harian">Total Upah Harian</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            Rp {{ number_format($totalDailyWage, 0, ',', '.') }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-coins text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Pegawai harian lepas
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Uang Makan -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Uang Makan Mingguan">Total Uang Makan</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            Rp {{ number_format($totalMealAllowance, 0, ',', '.') }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-receipt text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Uang makan staf &amp; harian
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Total Upah Lembur -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                    <i class="fas fa-business-time"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Upah Lembur Mingguan">Total Upah Lembur</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;">
                            Rp {{ number_format($totalOvertimePay, 0, ',', '.') }}
                        </div>
                        <span class="cash-advance-stat-badge" style="background-color: #f3e8ff; color: #9333ea;">
                            <i class="fas fa-clock text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        {{ $totalOvertimeHours }} Jam lembur mingguan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= 3. CARD UTAMA TABEL KEHADIRAN (STANDARD STISLA) ================= -->
    <div class="card shadow-sm" style="border-radius: 12px; border: 1px solid #e9edf2;">
        <!-- Card Header: Title di kiri, Actions Solid Buttons di kanan (Tanpa Redundansi & Zero Outline) -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
            <h4 class="mb-0">Daftar Kehadiran Karyawan</h4>

            <div class="d-flex align-items-center" style="gap: 8px;">
                <!-- Tarik Data dari Mesin Absensi (Solid Primary) -->
                <button type="button"
                        wire:click="syncFromAttendance"
                        wire:loading.attr="disabled"
                        wire:target="syncFromAttendance"
                        class="btn btn-primary btn-sm d-inline-flex align-items-center font-weight-bold shadow-sm"
                        style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 7px 15px; font-size: 13px;"
                        title="Tarik data kehadiran otomatis dari mesin absensi">
                    <span wire:loading.remove wire:target="syncFromAttendance">
                        <i class="fas fa-fingerprint mr-1.5"></i> Tarik dari Absensi Mesin
                    </span>
                    <span wire:loading wire:target="syncFromAttendance">
                        <i class="fas fa-spinner fa-spin mr-1.5"></i> Sinkronisasi...
                    </span>
                </button>

                <!-- Simpan Perubahan (Solid Success) -->
                <button type="button"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="btn btn-success btn-sm d-inline-flex align-items-center font-weight-bold shadow-sm"
                        style="border-radius: 8px; padding: 7px 15px; font-size: 13px;">
                    <span wire:loading.remove wire:target="save"><i class="fas fa-save mr-1.5"></i> Simpan Perubahan</span>
                    <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin mr-1.5"></i> Menyimpan...</span>
                </button>

                <!-- Cetak Seluruh Slip Mingguan (Solid Info) -->
                <a href="{{ route('overtimes.periods.print', $period->id) }}"
                   target="_blank"
                   class="btn btn-info btn-sm d-inline-flex align-items-center font-weight-bold shadow-sm"
                   style="border-radius: 8px; padding: 7px 15px; font-size: 13px;"
                   title="Cetak Seluruh Slip Mingguan Periode Ini">
                    <i class="fas fa-print mr-1.5"></i> Cetak Slip
                </a>
            </div>
        </div>

        <!-- Controls: Search Bar (Format Standar dt-control-wrapper Tanpa Filter Departemen) -->
        <div class="dt-control-wrapper" style="padding: 12px 20px; border-bottom: 1px solid #f1f5f9;">
            <div class="d-flex align-items-center">
                <span style="font-size: 12px; font-weight: 500; color: #475569;">
                    <i class="fas fa-calendar-week mr-1 text-primary"></i> Siklus: {{ $period->start_date->locale('id')->translatedFormat('d M') }} &ndash; {{ $period->end_date->locale('id')->translatedFormat('d M Y') }} (7 Hari)
                </span>
            </div>

            <div class="dt-search-wrapper">
                <span class="mr-1" style="font-size: 12px; color: #475569;">Search:</span>
                <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama karyawan...">
            </div>
        </div>

        @php
            $cycleDays = [];
            $currDate = $period->start_date->copy();
            $endDate = $period->end_date->copy();
            $dayKeyMap = [
                5 => 'fri',
                6 => 'sat',
                0 => 'sun',
                1 => 'mon',
                2 => 'tue',
                3 => 'wed',
                4 => 'thu',
            ];
            $dayNameMap = [
                'fri' => 'Jum',
                'sat' => 'Sab',
                'sun' => 'Min',
                'mon' => 'Sen',
                'tue' => 'Sel',
                'wed' => 'Rab',
                'thu' => 'Kam',
            ];

            while ($currDate->lte($endDate)) {
                $dow = $currDate->dayOfWeek;
                $dKey = $dayKeyMap[$dow] ?? 'fri';
                $cycleDays[] = [
                    'key' => $dKey,
                    'day_name' => $dayNameMap[$dKey] ?? 'Hari',
                    'date_formatted' => $currDate->locale('id')->translatedFormat('d M'),
                ];
                $currDate->addDay();
            }
        @endphp

        <div class="card-body p-0">
            <!-- Table Responsive -->
            <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <tr>
                            <th class="text-center text-nowrap !px-3.5 !py-3" style="width: 50px; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">NO</th>
                            <th class="text-nowrap !px-3.5 !py-3" style="min-width: 190px; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">KARYAWAN</th>
                            <th class="text-nowrap !px-3.5 !py-3" style="min-width: 320px; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">KEHADIRAN (JUM'AT &ndash; KAMIS)</th>
                            <th class="text-right text-nowrap !px-3.5 !py-3" style="min-width: 130px; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">UPAH HARIAN</th>
                            <th class="text-right text-nowrap !px-3.5 !py-3" style="min-width: 130px; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">UANG MAKAN</th>
                            <th class="text-right text-nowrap !px-3.5 !py-3" style="min-width: 140px; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">LEMBUR MINGGUAN</th>
                            <th class="text-right text-nowrap !px-3.5 !py-3" style="min-width: 140px; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">TOTAL DITERIMA</th>
                            <th class="text-center text-nowrap !px-3.5 !py-3" style="min-width: 115px; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $globalIdx = 1;
                        @endphp
                        @forelse ($groupedAttendances as $statusKey => $groupItems)
                            @php
                                $statusLabel = match ($statusKey) {
                                    'tetap' => 'KARYAWAN TETAP',
                                    'kontrak' => 'KONTRAK',
                                    'harian' => 'HARIAN LEPAS',
                                    'magang' => 'MAGANG',
                                    default => strtoupper($statusKey),
                                };
                                $statusIcon = match ($statusKey) {
                                    'tetap' => 'fas fa-id-badge',
                                    'kontrak' => 'fas fa-file-contract',
                                    'harian' => 'fas fa-user-clock',
                                    'magang' => 'fas fa-user-graduate',
                                    default => 'fas fa-users',
                                };
                            @endphp

                            <!-- Baris Sub-Header Group Status Kerja -->
                            <tr class="table-group-header" style="background-color: #f0f6ff; border-top: 1px solid #bfdbfe; border-bottom: 1px solid #dbeafe;">
                                <td colspan="8" class="text-nowrap !py-2 !px-3.5" style="border-left: 4px solid #0b52aa !important; background-color: #f0f6ff !important;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <span class="d-inline-flex align-items-center justify-content-center mr-2 flex-shrink-0" style="width: 22px; height: 22px; border-radius: 5px; background-color: #dbeafe; font-size: 11px;">
                                                <i class="{{ $statusIcon }}" style="color: #0b52aa;"></i>
                                            </span>
                                            <span class="font-weight-600" style="font-size: 12px; letter-spacing: 0.3px; color: #0f172a; text-transform: uppercase;">
                                                STATUS: {{ $statusLabel }}
                                            </span>
                                        </div>
                                        <span class="badge font-weight-500" style="font-size: 11px; padding: 2px 8px; border-radius: 9999px; background-color: #ffffff; color: #0b52aa; border: 1px solid #bfdbfe;">
                                            {{ count($groupItems) }} Orang
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            @foreach ($groupItems as $itemKey => $attData)
                                @php
                                    $empId = $attData['employee_id'] ?? $itemKey;
                                @endphp
                                <tr wire:key="att-row-{{ $empId }}" style="border-bottom: 1px solid #f1f5f9;">
                                    <!-- NO -->
                                    <td class="text-center align-middle !px-3.5 !py-2.5 text-nowrap" style="color: #475569; font-size: 12.5px; font-weight: 600;">
                                        {{ $globalIdx++ }}
                                    </td>

                                    <!-- Identitas Karyawan -->
                                    <td class="align-middle text-nowrap !px-3.5 !py-2.5">
                                        <div class="d-flex align-items-center text-nowrap">
                                            @if ($attData['photo_url'])
                                                <img src="{{ $attData['photo_url'] }}" alt="{{ $attData['employee_name'] }}" class="rounded-circle mr-2.5 flex-shrink-0" style="width: 34px; height: 34px; object-fit: cover; border: 1.5px solid #cbd5e1;">
                                            @else
                                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mr-2.5 flex-shrink-0 font-weight-600"
                                                     style="width: 34px; height: 34px; background-color: #eff6ff; color: #0284c7; border: 1.5px solid #bae6fd; font-size: 12.5px;">
                                                    {{ strtoupper(substr($attData['employee_name'], 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div style="color: #0f172a; font-size: 13px; font-weight: 600; line-height: 1.3;">
                                                    {{ $attData['employee_name'] }}
                                                </div>
                                                <div class="mt-0.5" style="font-size: 11px; color: #64748b; font-weight: 400; line-height: 1.25;">
                                                    {{ $attData['department_name'] }} &bull; {{ $attData['position_name'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kehadiran Siklus Jum'at s/d Kamis (Read-Only Tanggal & Hari) -->
                                    <td class="align-middle !px-3.5 !py-2.5 text-nowrap">
                                        <div class="d-flex align-items-center flex-wrap" style="gap: 5px;">
                                            @foreach ($cycleDays as $cDay)
                                                @php
                                                    $isAttended = in_array($cDay['key'], $attData['attendance_days'] ?? [], true);
                                                @endphp
                                                <div class="d-inline-flex flex-column align-items-center justify-content-center text-center px-2 py-1"
                                                     style="min-width: 44px; border-radius: 7px; line-height: 1.15; {{ $isAttended ? 'background-color: #0b52aa; border: 1px solid #0b52aa; color: #ffffff;' : 'background-color: #ffffff; border: 1px solid #cbd5e1; color: #475569;' }}"
                                                     title="{{ $cDay['day_name'] }}, {{ $cDay['date_formatted'] }}: {{ $isAttended ? 'Hadir' : 'Tidak Hadir' }}">
                                                    <span style="font-size: 10px; font-weight: 500; {{ $isAttended ? 'color: rgba(255, 255, 255, 0.9);' : 'color: #64748b;' }}">
                                                        {{ $cDay['date_formatted'] }}
                                                    </span>
                                                    <span style="font-size: 11.5px; font-weight: 700; margin-top: 2px;">
                                                        {{ $cDay['day_name'] }}
                                                    </span>
                                                </div>
                                            @endforeach

                                            <span class="badge badge-light border text-dark font-weight-700 ml-1.5 px-2.5 py-1.5" style="border-radius: 8px; font-size: 11px;">
                                                {{ $attData['attended_days'] ?? 0 }} Hari
                                            </span>
                                        </div>
                                    </td>

                                    <!-- 1. Upah Harian -->
                                    <td class="text-right align-middle !px-3.5 !py-2.5 text-nowrap" style="font-variant-numeric: tabular-nums;">
                                        @if ($attData['is_harian'])
                                            <div style="color: #047857; font-size: 12.5px; font-weight: 600;">
                                                Rp {{ number_format($attData['total_daily_wage'], 0, ',', '.') }}
                                            </div>
                                            <small class="d-block font-weight-normal" style="font-size: 10.5px; color: #475569;">
                                                @ Rp {{ number_format($attData['daily_rate'], 0, ',', '.') }}
                                            </small>
                                        @else
                                            <span class="badge badge-light border font-weight-normal" style="font-size: 10px; padding: 2px 6px; color: #475569;">-</span>
                                            <small class="d-block" style="font-size: 10.5px; color: #475569;">Gaji Bulanan</small>
                                        @endif
                                    </td>

                                    <!-- 2. Uang Makan (Teks Biasa Bersih Tanpa Input Form) -->
                                    <td class="text-right align-middle !px-3.5 !py-2.5 text-nowrap" style="font-variant-numeric: tabular-nums;">
                                        <div style="color: #d97706; font-size: 12.5px; font-weight: 600;">
                                            Rp {{ number_format($attData['total_meal_allowance'], 0, ',', '.') }}
                                        </div>
                                        <small class="d-block font-weight-normal" style="font-size: 10.5px; color: #475569;">
                                            @ Rp {{ number_format($attData['meal_allowance_per_day'], 0, ',', '.') }}
                                        </small>
                                    </td>

                                    <!-- 3. Upah Lembur -->
                                    <td class="text-right align-middle !px-3.5 !py-2.5 text-nowrap" style="font-variant-numeric: tabular-nums;">
                                        @if (($attData['overtime_pay'] ?? 0) > 0 || ($attData['overtime_hours'] ?? 0) > 0)
                                            <div style="color: #1e293b; font-size: 12.5px; font-weight: 600;">
                                                Rp {{ number_format($attData['overtime_pay'], 0, ',', '.') }}
                                            </div>
                                            <span class="badge badge-light border text-primary font-weight-600 mt-0.5" style="font-size: 9.5px; padding: 1px 5px;">
                                                <i class="far fa-clock mr-1"></i>{{ $attData['overtime_hours'] }} Jam
                                            </span>
                                        @else
                                            <div style="color: #475569; font-size: 12px; font-weight: 500;">
                                                Rp 0
                                            </div>
                                            <small class="d-block" style="font-size: 10.5px; color: #475569;">0 Jam</small>
                                        @endif
                                    </td>

                                    <!-- 4. Total Diterima (Grand Total) -->
                                    <td class="text-right align-middle !px-3.5 !py-2.5 text-nowrap" style="font-variant-numeric: tabular-nums;">
                                        <div style="color: #0b52aa; font-size: 13.5px; font-weight: 700;">
                                            Rp {{ number_format($attData['grand_total'], 0, ',', '.') }}
                                        </div>
                                        @if (($attData['payment_status'] ?? 'unpaid') === 'paid')
                                            <span class="badge badge-success px-2 py-0.5 mt-0.5" style="font-size: 9.5px; border-radius: 6px; font-weight: 600;">
                                                <i class="fas fa-check-circle mr-1"></i> Dibayar
                                            </span>
                                        @else
                                            <span class="badge badge-light border px-2 py-0.5 mt-0.5 font-weight-normal" style="font-size: 9.5px; border-radius: 6px; color: #475569;">
                                                Belum Dibayar
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 5. Tombol Aksi Karyawan (Cetak Slip & Pembayaran) -->
                                    <td class="text-center align-middle text-nowrap !px-3.5 !py-2.5">
                                        <div class="d-inline-flex align-items-center" style="gap: 6px;">
                                            @if (($attData['payment_status'] ?? 'unpaid') === 'paid')
                                                <!-- Cetak Slip Gaji Karyawan (Hanya Aktif Jika Sudah Dibayar) -->
                                                <a href="{{ route('overtimes.periods.print', ['period' => $period->id, 'employee' => $empId]) }}"
                                                   target="_blank"
                                                   class="btn btn-warning btn-action"
                                                   data-toggle="tooltip"
                                                   title="Cetak Slip Gaji Resmi">
                                                    <i class="fas fa-print"></i>
                                                </a>

                                                <!-- Detail & Bukti Pembayaran -->
                                                <button type="button"
                                                        wire:click="openPaymentDetailModal({{ $empId }})"
                                                        class="btn btn-info btn-action"
                                                        data-toggle="tooltip"
                                                        title="Lihat Bukti & Detail Pembayaran">
                                                    <i class="fas fa-receipt"></i>
                                                </button>
                                            @else
                                                <!-- Tombol Bayar Gaji (Hijau) -->
                                                <button type="button"
                                                        wire:click="openPayEmployeeModal({{ $empId }})"
                                                        class="btn btn-success btn-action"
                                                        data-toggle="tooltip"
                                                        title="Bayar Gaji Karyawan">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </button>

                                                <!-- Tombol Cetak Disabled (Harus Konfirmasi Pembayaran Dahulu) -->
                                                <button type="button"
                                                        class="btn btn-light border text-muted btn-action"
                                                        style="cursor: not-allowed; opacity: 0.55;"
                                                        data-toggle="tooltip"
                                                        title="Slip hanya dapat dicetak setelah konfirmasi pembayaran">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="8" class="p-0 border-0">
                                    <x-empty-state
                                        icon="fas fa-calendar-check"
                                        title="Tidak Ada Karyawan dengan Kehadiran / Lembur"
                                        subtitle="Hanya karyawan dengan kehadiran kerja (&gt; 0 hari) atau jam lembur yang ditampilkan pada periode mingguan ini."
                                        action-label="Tarik dari Absensi Mesin"
                                        action-wire-click="syncFromAttendance"
                                        action-icon="fas fa-fingerprint"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer: Summary Info Only (Tanpa Tombol Duplikat) -->
            <div class="dt-footer-wrapper" style="padding: 12px 20px;">
                <div style="font-size: 12.5px; color: #475569;">
                    Menampilkan <strong>{{ count($filteredAttendances) }}</strong> karyawan gajian dari <strong>{{ $totalEmployees }}</strong> total karyawan
                </div>
                <div style="font-size: 13px; color: #0f172a;">
                    Total Biaya Mingguan: <strong style="color: #0b52aa; font-size: 14.5px;">Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong>
                </div>
            </div>
            </div>
        </div>

    <!-- ================= MODAL DETAIL PEMBAYARAN GAJI (IMAGE 0) ================= -->
    <div wire:ignore.self class="modal fade" id="paymentDetailModal" role="dialog" aria-labelledby="paymentDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);">
                <div class="modal-header bg-white" style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">
                    <h5 class="modal-title font-weight-700 text-dark" id="paymentDetailModalLabel" style="font-size: 15px; letter-spacing: -0.2px;">
                        Detail Pembayaran Gaji
                    </h5>
                    <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-3 px-4 bg-white">
                    @if ($selectedAttendance)
                        <!-- Mobile / Flutter Receipt Hero: Dashed Ring Icon + Heading + Subtitle + Amount -->
                        <div class="text-center mb-3 pt-1">
                            <div class="receipt-orbit-wrapper">
                                <div class="receipt-orbit-dot"></div>
                                <div class="receipt-solid-circle">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <h5 class="receipt-title">
                                Sudah Dibayar
                            </h5>
                            <p class="receipt-subtitle">
                                Gaji karyawan telah berhasil dibayarkan
                            </p>
                            <div class="receipt-amount mb-3">
                                Rp {{ number_format($selectedEmployeeData['grand_total'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>

                        <!-- Card List Informasi Pembayaran (Mobile Flutter ListTile Style) -->
                        <div class="emp-tile-group mb-0">
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-user"></i></div>
                                    <span>Karyawan</span>
                                </div>
                                <div class="emp-tile-right text-dark">
                                    {{ $selectedAttendance->employee?->full_name ?? '-' }}
                                </div>
                            </div>
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-receipt"></i></div>
                                    <span>No. Slip Gaji</span>
                                </div>
                                <div class="emp-tile-right text-dark" style="letter-spacing: 0.3px;">
                                    SLP-{{ $period->reference_number }}-{{ str_pad($selectedAttendance->employee_id, 4, '0', STR_PAD_LEFT) }}
                                </div>
                            </div>
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-calendar-check"></i></div>
                                    <span>Waktu Pembayaran</span>
                                </div>
                                <div class="emp-tile-right text-dark">
                                    {{ $selectedAttendance->paid_at ? $selectedAttendance->paid_at->format('d M Y, H:i') : '-' }}
                                </div>
                            </div>
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-wallet"></i></div>
                                    <span>Metode Pembayaran</span>
                                </div>
                                <div class="emp-tile-right text-dark">
                                    {{ $selectedAttendance->paymentMethodLabel() }}
                                </div>
                            </div>
                            @if ($selectedAttendance->bank_name || $selectedAttendance->bank_account_number)
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-university"></i></div>
                                        <span>Nama Bank / E-Wallet</span>
                                    </div>
                                    <div class="emp-tile-right text-dark">
                                        {{ $selectedAttendance->bank_name ?: '-' }}
                                    </div>
                                </div>
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-credit-card"></i></div>
                                        <span>Nomor Rekening / No. HP</span>
                                    </div>
                                    <div class="emp-tile-right text-dark" style="letter-spacing: 0.4px;">
                                        {{ $selectedAttendance->bank_account_number ?: '-' }}
                                    </div>
                                </div>
                                @if ($selectedAttendance->bank_account_name)
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-user-check"></i></div>
                                            <span>Atas Nama</span>
                                        </div>
                                        <div class="emp-tile-right text-dark">
                                            {{ $selectedAttendance->bank_account_name }}
                                        </div>
                                    </div>
                                @endif
                            @endif
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-user-shield"></i></div>
                                    <span>Dikonfirmasi Oleh</span>
                                </div>
                                <div class="emp-tile-right text-dark">
                                    {{ $selectedAttendance->payer?->name ?? 'Admin Demo' }}
                                </div>
                            </div>
                            @if ($selectedAttendance->payment_notes)
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-sticky-note"></i></div>
                                        <span>Catatan</span>
                                    </div>
                                    <div class="emp-tile-right text-truncate" style="max-width: 220px; color: #475569;" title="{{ $selectedAttendance->payment_notes }}">
                                        {{ $selectedAttendance->payment_notes }}
                                    </div>
                                </div>
                            @endif

                            <!-- Bukti Pembayaran / Foto Kwitansi -->
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon">
                                        @if ($selectedAttendance->payment_proof_path && \Illuminate\Support\Str::endsWith(strtolower($selectedAttendance->payment_proof_path), '.pdf'))
                                            <i class="fas fa-file-pdf text-danger"></i>
                                        @else
                                            <i class="fas fa-paperclip text-primary"></i>
                                        @endif
                                    </div>
                                    <span>Bukti Pembayaran</span>
                                </div>
                                <div class="emp-tile-right text-dark">
                                    @if ($selectedAttendance->payment_proof_path)
                                        @php
                                            $isPdfProof = \Illuminate\Support\Str::endsWith(strtolower($selectedAttendance->payment_proof_path), '.pdf');
                                        @endphp
                                        @if ($isPdfProof)
                                            <a href="{{ asset('storage/' . $selectedAttendance->payment_proof_path) }}" target="_blank" class="badge badge-light border text-danger font-weight-600 px-2.5 py-1" style="font-size: 11px; border-radius: 6px; border-color: #fca5a5 !important; background-color: #fef2f2 !important; text-decoration: none;" title="Buka lampiran bukti transfer (PDF)">
                                                <i class="fas fa-file-pdf mr-1 text-danger"></i> Buka Dokumen Bukti (PDF) <i class="fas fa-external-link-alt ml-1" style="font-size: 9px;"></i>
                                            </a>
                                        @else
                                            <a href="{{ asset('storage/' . $selectedAttendance->payment_proof_path) }}" target="_blank" class="badge badge-light border text-primary font-weight-600 px-2.5 py-1" style="font-size: 11px; border-radius: 6px; border-color: #cbd5e1 !important; text-decoration: none;" title="Buka lampiran foto bukti transfer / kwitansi">
                                                <i class="fas fa-file-image mr-1 text-success"></i> Buka Foto Bukti <i class="fas fa-external-link-alt ml-1" style="font-size: 9px;"></i>
                                            </a>
                                        @endif
                                    @else
                                        <span class="badge badge-light border text-danger font-weight-500 px-2 py-0.5" style="font-size: 10.5px;">
                                            Belum Diunggah
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                @if ($selectedAttendance)
                    <div class="modal-footer bg-white border-top p-3">
                        <div class="d-flex w-100" style="gap: 10px;">
                            <!-- KIRI: EDIT -->
                            <button type="button" wire:click="openEditPaymentModal({{ $selectedAttendance->employee_id }})" class="btn btn-primary flex-fill font-weight-600 shadow-sm" style="border-radius: 8px; font-size: 13.5px; padding: 10px 14px; background-color: #0b52aa; border-color: #0b52aa;">
                                <i class="fas fa-edit mr-1.5"></i> Edit
                            </button>

                            <!-- KANAN: CETAK SLIP (SUCCESS GREEN) -->
                            <a href="{{ route('overtimes.periods.print', ['period' => $period->id, 'employee' => $selectedAttendance->employee_id]) }}"
                               target="_blank"
                               class="btn btn-success flex-fill font-weight-600 shadow-sm text-center d-inline-flex align-items-center justify-content-center"
                               style="border-radius: 8px; font-size: 13.5px; padding: 10px 14px;">
                                <i class="fas fa-print mr-1.5"></i> Cetak Slip
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ================= MODAL FORM / EDIT PEMBAYARAN GAJI (IMAGE 1) ================= -->
    <div wire:ignore.self class="modal fade" id="payEmployeeModal" role="dialog" aria-labelledby="payEmployeeModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;" role="document">
            <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);">
                <div class="modal-header bg-white" style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9;">
                    <h5 class="modal-title font-weight-700 text-dark" id="payEmployeeModalLabel" style="font-size: 15px;">
                        {{ $isEditingPayment ? 'Edit Pembayaran Gaji Karyawan' : 'Form Pembayaran Gaji Karyawan' }}
                    </h5>
                    <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form wire:submit.prevent="processEmployeePayment">
                    <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
                        <!-- Card Info Karyawan & Gaji Bersih -->
                        @if ($selectedAttendance)
                            <div class="card mb-3.5" style="border-radius: 12px; border: 1px solid #e2e8f0; border-left: 4px solid #0b52aa; background: #ffffff; box-shadow: 0 2px 6px -1px rgba(15, 23, 42, 0.05);">
                                <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap" style="gap: 12px;">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $selectedAttendance->employee?->photo_url ?? asset('assets/img/avatar/avatar-1.png') }}"
                                             alt="{{ $selectedAttendance->employee?->full_name }}"
                                             class="rounded-circle mr-3 flex-shrink-0"
                                             style="width: 42px; height: 42px; object-fit: cover; border: 1.5px solid #cbd5e1;">
                                        <div>
                                            <div class="font-weight-600" style="font-size: 14.5px; color: #0f172a; line-height: 1.3;">
                                                {{ $selectedAttendance->employee?->full_name }}
                                            </div>
                                            <div class="mt-0.5" style="font-size: 12px; color: #475569; font-weight: 400; line-height: 1.3;">
                                                @if ($selectedAttendance->employee?->department)
                                                    {{ $selectedAttendance->employee->department->name }} &bull;
                                                @endif
                                                {{ $selectedAttendance->employee?->position?->name ?? 'Karyawan' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 text-right ml-auto">
                                        <div class="px-3 py-1.5" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;">
                                            <div style="font-size: 10px; font-weight: 600; color: #15803d; text-transform: uppercase; letter-spacing: 0.5px;">TOTAL DITERIMA</div>
                                            <div class="font-weight-700" style="font-size: 16.5px; color: #166534; font-variant-numeric: tabular-nums; line-height: 1.25;">
                                                Rp {{ number_format($selectedEmployeeData['grand_total'] ?? 0, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="form-row">
                            <!-- Tanggal Pembayaran -->
                            <div class="form-group col-md-6 mb-3">
                                <label for="weeklyPaymentDate" class="font-weight-500" style="color: #334155; font-size: 12.5px;">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" id="weeklyPaymentDate" wire:model="itemPaymentDate" class="form-control @error('itemPaymentDate') is-invalid @enderror" style="border-radius: 8px;">
                                @error('itemPaymentDate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Dikonfirmasi Oleh -->
                            <div class="form-group col-md-6 mb-3">
                                <label class="font-weight-500" style="color: #334155; font-size: 12.5px;">Dikonfirmasi Oleh</label>
                                <input type="text" value="{{ auth()->user()->name }}" class="form-control" style="background-color: #f8fafc; border-radius: 8px; color: #475569;" disabled>
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="form-group mb-3">
                            <label for="weeklyPaymentMethod" class="font-weight-500" style="color: #334155; font-size: 12.5px;">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select id="weeklyPaymentMethod" wire:model.live="itemPaymentMethod" class="form-control @error('itemPaymentMethod') is-invalid @enderror" style="border-radius: 8px;">
                                <option value="bank_transfer">Transfer Bank / E-Wallet</option>
                                <option value="cash">Tunai (Cash)</option>
                            </select>
                            @error('itemPaymentMethod')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Input Spesifik Bank Transfer -->
                        @if ($itemPaymentMethod === 'bank_transfer')
                            <div class="form-row">
                                <div class="form-group col-md-6 mb-3">
                                    <label for="weeklyBankName" class="font-weight-500" style="color: #334155; font-size: 12.5px;">Nama Bank / E-Wallet <span class="text-danger">*</span></label>
                                    <input type="text" id="weeklyBankName" wire:model="itemBankName" placeholder="Contoh: BCA, Mandiri, BRI, GoPay" class="form-control @error('itemBankName') is-invalid @enderror" style="border-radius: 8px;">
                                    @error('itemBankName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6 mb-3">
                                    <label for="weeklyBankAccountNumber" class="font-weight-500" style="color: #334155; font-size: 12.5px;">Nomor Rekening / No. HP <span class="text-danger">*</span></label>
                                    <input type="text" id="weeklyBankAccountNumber" wire:model="itemBankAccountNumber" placeholder="No. Rekening atau No. HP E-Wallet" class="form-control @error('itemBankAccountNumber') is-invalid @enderror" style="border-radius: 8px;">
                                    @error('itemBankAccountNumber')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="weeklyBankAccountName" class="font-weight-500" style="color: #334155; font-size: 12.5px;">Atas Nama Rekening / Akun</label>
                                <input type="text" id="weeklyBankAccountName" wire:model="itemBankAccountName" placeholder="Nama Pemilik Rekening / Akun" class="form-control" style="border-radius: 8px;">
                            </div>
                        @endif

                        <!-- Upload Bukti Pembayaran -->
                        <div class="form-group mb-3">
                            <label for="weekly_payment_proof" class="font-weight-500" style="color: #334155; font-size: 12.5px;">
                                Bukti Pembayaran / Foto Kwitansi <span class="text-danger">*</span>
                                <small style="color: #64748b;">(Wajib difotokan baik tunai maupun transfer &bull; JPG, PNG, PDF maks. 3MB)</small>
                            </label>
                            <div class="custom-file">
                                <input type="file" id="weekly_payment_proof" wire:model="itemPaymentProof" class="custom-file-input @error('itemPaymentProof') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                                <label class="custom-file-label text-truncate" for="weekly_payment_proof" style="border-radius: 8px;">
                                    @if ($itemPaymentProof && is_object($itemPaymentProof))
                                        @php
                                            $isNewPdf = \Illuminate\Support\Str::endsWith(strtolower($itemPaymentProof->getClientOriginalName()), '.pdf');
                                        @endphp
                                        @if ($isNewPdf)
                                            <i class="fas fa-file-pdf text-danger mr-1"></i> {{ $itemPaymentProof->getClientOriginalName() }}
                                        @else
                                            <i class="fas fa-file-image text-success mr-1"></i> {{ $itemPaymentProof->getClientOriginalName() }}
                                        @endif
                                    @elseif ($selectedAttendance?->payment_proof_path)
                                        <i class="fas fa-check-circle text-primary mr-1"></i> File terlampir (Klik jika ingin mengganti)
                                    @else
                                        <i class="fas fa-camera mr-1" style="color: #64748b;"></i> Ambil foto bukti transfer / kwitansi tanda terima (JPG, PNG, PDF)...
                                    @endif
                                </label>
                            </div>
                            <div wire:loading wire:target="itemPaymentProof" class="text-primary small mt-1">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah file bukti...
                            </div>
                            @if ($itemPaymentProof && is_object($itemPaymentProof))
                                <div class="d-flex align-items-center justify-content-between mt-1 px-1">
                                    <span class="small" style="color: #475569;">
                                        <i class="fas fa-info-circle mr-1"></i> {{ method_exists($itemPaymentProof, 'getSize') ? number_format($itemPaymentProof->getSize() / 1024, 0) . ' KB' : '' }}
                                    </span>
                                    <button type="button" class="btn btn-link text-danger p-0 text-decoration-none small" wire:click="$set('itemPaymentProof', null)">
                                        <i class="fas fa-times-circle mr-1"></i> Hapus file terpilih
                                    </button>
                                </div>
                            @endif
                            @error('itemPaymentProof')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Catatan -->
                        <div class="form-group mb-0">
                            <label for="weeklyPaymentNotes" class="font-weight-500" style="color: #334155; font-size: 12.5px;">Catatan / Keterangan <small style="color: #64748b;">(Opsional)</small></label>
                            <textarea id="weeklyPaymentNotes" wire:model="itemPaymentNotes" rows="2" placeholder="Nomor referensi transfer atau catatan..." class="form-control" style="border-radius: 8px;"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-end" style="gap: 8px;">
                        <button type="button" class="btn btn-secondary px-3" data-dismiss="modal" style="border-radius: 8px; font-weight: 500;">Batal</button>
                        <button type="submit" class="btn btn-primary px-3 shadow-sm" wire:loading.attr="disabled" wire:target="processEmployeePayment" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; font-weight: 600;">
                            <span wire:loading wire:target="processEmployeePayment"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                            <span wire:loading.remove wire:target="processEmployeePayment"><i class="fas fa-check mr-1"></i> {{ $isEditingPayment ? 'Simpan Perubahan' : 'Konfirmasi Pembayaran' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Payment Detail Modal (Mobile / Flutter Receipt Style) */
    @media (min-width: 576px) {
        #paymentDetailModal .modal-dialog {
            max-width: 440px !important;
        }
    }
    #paymentDetailModal .receipt-orbit-wrapper {
        position: relative;
        width: 68px;
        height: 68px;
        border-radius: 50%;
        border: 1.5px dashed #86efac;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }
    #paymentDetailModal .receipt-orbit-dot {
        position: absolute;
        width: 6px;
        height: 6px;
        background-color: #10b981;
        border-radius: 50%;
        right: -1px;
        top: 50%;
        transform: translateY(-50%);
    }
    #paymentDetailModal .receipt-solid-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 14px rgba(16, 185, 129, 0.32);
        color: #ffffff;
        font-size: 20px;
    }
    #paymentDetailModal .receipt-title {
        font-size: 17px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.3px;
        line-height: 1.25;
        margin-bottom: 3px;
    }
    #paymentDetailModal .receipt-subtitle {
        font-size: 12px;
        font-weight: 400;
        color: #64748b;
        line-height: 1.4;
        margin-bottom: 8px;
    }
    #paymentDetailModal .receipt-amount {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.6px;
        line-height: 1.15;
        margin-bottom: 14px;
    }
    #paymentDetailModal .emp-tile-group {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        margin-bottom: 0;
    }
    #paymentDetailModal .emp-tile-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9.5px 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    #paymentDetailModal .emp-tile-row:last-child {
        border-bottom: none;
    }
    #paymentDetailModal .emp-tile-left {
        display: flex;
        align-items: center;
        font-size: 12.5px;
        font-weight: 500;
        color: #64748b;
        line-height: 1.3;
    }
    #paymentDetailModal .emp-tile-icon {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        background: #f8fafc;
        border: 1px solid #edf2f7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        margin-right: 10px;
        flex-shrink: 0;
    }
    #paymentDetailModal .emp-tile-right {
        font-size: 12.5px;
        font-weight: 500;
        color: #0f172a;
        text-align: right;
        line-height: 1.35;
    }
</style>
@endpush
