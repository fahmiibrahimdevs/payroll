<div>
    <x-slot name="header">
        <h1>Lembur</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Kepegawaian</div>
            <div class="breadcrumb-item">Lembur</div>
        </div>
    </x-slot>

    <!-- Header Section + Action Button (Modern style matching Payrolls & Kasbon) -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2 mb-md-3">
        <div class="mr-md-3">
            <h2 class="section-title mt-0">Kelola Lembur &amp; Uang Makan</h2>
            <p class="section-lead mb-0">
                Kelola pengajuan lembur karyawan, koordinasi &amp; verifikasi jam kerja, persetujuan direktur, serta slip lembur mingguan.
            </p>
        </div>

        <div class="mt-3 mt-md-0 flex-shrink-0 d-flex align-items-center" style="gap: 8px;">
            @if ($isKaryawan)
                <button type="button" wire:click="openCreateModal" class="btn btn-primary d-inline-flex align-items-center font-weight-bold shadow-sm" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 9px 16px; font-size: 13px;">
                    <i class="fas fa-plus mr-1.5"></i> Ajukan Lembur Saya
                </button>
            @else
                @if (auth()->user()->hasRole('admin'))
                    <button type="button" wire:click="openCreateModal" class="btn btn-primary d-inline-flex align-items-center font-weight-bold shadow-sm" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 9px 16px; font-size: 13px;">
                        <i class="fas fa-plus mr-1.5"></i> Tambah Pengajuan
                    </button>
                @endif
            @endif
        </div>
    </div>

    <!-- 4 Modern Summary Statistic Cards (.cash-advance-stat-grid standard) -->
    <div class="cash-advance-stat-grid mb-4">
        <!-- 1. Total Pengajuan Bulan Ini (Gradient Blue) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-business-time"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Pengajuan Bulan Ini">Total pengajuan bulan ini</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $stats['total_this_month'] }} Pengajuan">
                            <span>{{ $stats['total_this_month'] }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Pengajuan</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-calendar-alt text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext" title="{{ $stats['total_hours_month'] }} jam kerja lembur tercatat">
                        {{ $stats['total_hours_month'] }} jam kerja lembur tercatat
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Verifikasi Admin (Gradient Amber) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Verifikasi Admin">Verifikasi admin</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $stats['pending_admin'] }} Menunggu">
                            <span>{{ $stats['pending_admin'] }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Menunggu</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-hourglass-half text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        <span>Menunggu koordinasi</span>
                        <span class="mx-1" style="color: #cbd5e1;">&bull;</span>
                        @if ($stats['pending_admin'] > 0)
                            <a href="javascript:void(0)" onclick="$('#pendingAdminReviewModal').modal('show')" class="text-warning font-weight-bold">Tinjau</a>
                        @else
                            <span class="text-muted">Tinjau</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Approval Direktur (Gradient Purple) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Approval Direktur">Approval direktur</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $stats['pending_direktur'] }} Pengajuan">
                            <span>{{ $stats['pending_direktur'] }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Pengajuan</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-stamp text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        <span>Telah lolos verif admin</span>
                        <span class="mx-1" style="color: #cbd5e1;">&bull;</span>
                        @if ($stats['pending_direktur'] > 0)
                            <a href="javascript:void(0)" onclick="$('#pendingDirectorReviewModal').modal('show')" class="text-primary font-weight-bold">Tinjau</a>
                        @else
                            <span class="text-muted">Tinjau</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Disetujui Bulan Ini (Gradient Green) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Disetujui Bulan Ini">Disetujui bulan ini</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-gray-800" style="color: #1f2937;" title="{{ $stats['approved_this_month'] }} Disetujui">
                            <span>{{ $stats['approved_this_month'] }}</span>
                            <span class="cash-advance-stat-unit text-gray-800" style="color: #1f2937;">Disetujui</span>
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-wallet text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext" title="{{ $stats['formatted_approved_amount'] }} siap dibayar">
                        {{ $stats['formatted_approved_amount'] }} siap dibayar
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN 1 ROW WITH 2 CARDS: SPLIT KIRI (ANALISIS & GRAFIK) & KANAN (TABEL DATA UTAMA) -->
    <div class="row align-items-start">
        <!-- Kolom Kiri: 1 Card Berisi 2 Grafik & Analisis Lembur (col-12 col-lg-3 col-md-4) -->
        <div class="col-12 col-lg-3 col-md-4 mb-4">
            @include('livewire.overtimes.charts-card')
        </div>

        <!-- Kolom Kanan: Card Tabel Data Utama (col-12 col-lg-9 col-md-8) -->
        <div class="col-12 col-lg-9 col-md-8 mb-4">
            <div class="card cash-advance-main-card shadow-sm mb-0" style="border-radius: 12px; border: 1px solid #e9edf2;">
                <div class="cash-advance-top-card">
                    <!-- Card Header Format Konsisten Kasbon & Penggajian -->
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Daftar Pengajuan Lembur &bull; Lembur - <span class="text-primary font-weight-bold">{{ $isKaryawan ? 'Pengajuan Saya' : 'Semua Karyawan' }}</span></h4>
                    </div>

                    <!-- Controls: Show Entries & Search Bar -->
                    <div class="dt-control-wrapper">
                        <div class="dt-show-entries">
                            Show
                            <select wire:model.live="perPage" class="dt-select-entries">
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            Entries
                        </div>

                        <div class="dt-search-wrapper">
                            <span class="mr-1">Search:</span>
                            <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama, ref, alasan...">
                        </div>
                    </div>
                </div>

                <div class="card-body p-0 bg-white">

        <!-- ================= TAB 1: DAFTAR PENGAJUAN LEMBUR ================= -->
        @if ($activeTab === 'submissions')
            <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th class="text-center text-nowrap !px-2.5 !py-2" style="width: 45px; color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">NO</th>
                            @if (! $isKaryawan)
                                <th class="text-nowrap !px-2.5 !py-2" style="color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">KARYAWAN</th>
                            @endif
                            <th class="text-nowrap !px-2.5 !py-2" style="color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">JAM KERJA</th>
                            <th class="text-center text-nowrap !px-2.5 !py-2" style="width: 75px; color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">DURASI</th>
                            <th class="text-right text-nowrap !px-2.5 !py-2" style="color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">UPAH / SATUAN</th>
                            <th class="text-right text-nowrap !px-2.5 !py-2" style="color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">TOTAL UPAH</th>
                            <th class="text-center text-nowrap !px-2.5 !py-2" style="width: 130px; color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">STATUS</th>
                            <th class="text-center text-nowrap !px-2.5 !py-2" style="width: 80px; color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($submissions->total() > 0)
                            @php
                                $rowNumber = 0;
                                $groupedSubmissions = collect($submissions->items())->groupBy(function ($item) {
                                    return $item->date ? $item->date->format('Y-m-d') : 'unknown';
                                });
                            @endphp

                            @foreach ($groupedSubmissions as $dateKey => $groupItems)
                                @php
                                    $firstItem = $groupItems->first();
                                    $formattedDateGroup = $firstItem && $firstItem->date
                                        ? $firstItem->date->locale('id')->translatedFormat('l, d M Y')
                                        : 'Tanggal Lainnya';
                                @endphp
                                <!-- Baris Sub-Header Group Tanggal (Format: Senin, 27 Okt 2026) -->
                                <tr class="table-group-header" style="background-color: #f0f6ff; border-top: 1px solid #bfdbfe; border-bottom: 1px solid #dbeafe;">
                                    <td colspan="{{ $isKaryawan ? 7 : 8 }}" class="!py-2 !px-3" style="border-left: 4px solid #0b52aa !important;">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 8px;">
                                            <div class="d-flex align-items-center">
                                                <span class="d-inline-flex align-items-center justify-content-center mr-2" style="width: 24px; height: 24px; border-radius: 6px; background-color: #dbeafe; font-size: 11px; flex-shrink: 0;">
                                                    <i class="far fa-calendar-alt" style="color: #0b52aa;"></i>
                                                </span>
                                                <span class="font-weight-bold" style="font-size: 13px; letter-spacing: 0.2px; color: #0f172a;">
                                                    {{ $formattedDateGroup }}
                                                </span>
                                                <span class="badge ml-2 font-weight-600" style="font-size: 11px; padding: 2px 8px; border-radius: 10px; background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;">
                                                    {{ $groupItems->count() }} Pengajuan
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                @foreach ($groupItems as $sub)
                                    @php
                                        $rowNumber++;
                                        $emp = $sub->employee;
                                        $empName = $emp?->full_name ?: 'Karyawan';
                                        $cleanedWords = array_values(array_filter(
                                            preg_split('/\s+/', trim($empName)),
                                            fn($w) => preg_match('/[a-zA-Z0-9]/', $w)
                                        ));
                                        $initials = '';
                                        if (!empty($cleanedWords)) {
                                            $initials .= strtoupper(substr($cleanedWords[0], 0, 1));
                                            if (isset($cleanedWords[1])) {
                                                $initials .= strtoupper(substr($cleanedWords[1], 0, 1));
                                            }
                                        }
                                        $initials = $initials ?: 'K';
                                        $dur = (float) $sub->duration_hours;
                                        $durDisplay = ($dur == (int) $dur) ? (int) $dur : number_format($dur, 1, ',', '.');
                                    @endphp
                                    <tr id="submission-row-{{ $sub->id }}" wire:key="submission-row-{{ $sub->id }}" class="payroll-table-row" style="border-bottom: 1px solid #f1f5f9;">
                                        <td class="text-center align-middle !px-2.5 !py-2 text-nowrap" style="color: #475569; font-size: 12.5px; font-weight: 600;">
                                            {{ $submissions->firstItem() + $rowNumber - 1 }}
                                        </td>

                                        <!-- Identitas Karyawan (Admin/Direktur View) -->
                                        @if (! $isKaryawan)
                                            <td class="align-middle text-nowrap !px-2.5 !py-2">
                                                <div class="d-flex align-items-center text-nowrap">
                                                    @if ($emp && $emp->photo_url)
                                                        <img src="{{ $emp->photo_url }}" alt="{{ $empName }}" class="rounded-circle mr-2 flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover; border: 1.5px solid #cbd5e1;">
                                                    @else
                                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mr-2 flex-shrink-0 font-weight-600"
                                                             style="width: 32px; height: 32px; background-color: #eff6ff; color: #0284c7; border: 1.5px solid #bae6fd; font-size: 11.5px;">
                                                            {{ $initials }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div style="color: #0f172a; font-size: 13px; font-weight: 600; line-height: 1.3;">
                                                            {{ $empName }}
                                                        </div>
                                                        <div class="mt-0.5" style="font-size: 11.5px; color: #64748b; font-weight: 500; line-height: 1.25;">
                                                            {{ $emp?->department?->name ?? 'Tanpa Dept' }} &bull; {{ $emp?->position?->name ?? 'Staff' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        @endif

                                        <!-- Jam Kerja (Lembur Harian atau Jam Mulai - Jam Selesai) -->
                                        <td class="align-middle text-nowrap !px-2.5 !py-2" style="color: #334155; font-size: 12.5px; font-weight: 500;">
                                            @if ($sub->isDaily())
                                                Lembur Harian
                                            @else
                                                {{ $sub->formatted_work_hours }}
                                            @endif
                                        </td>

                                        <!-- Kolom Khusus: Durasi -->
                                        <td class="text-center align-middle text-nowrap !px-2.5 !py-2">
                                            <span class="badge px-2.5 py-1" style="font-size: 12px; font-weight: 600; background-color: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; border-radius: 6px;">
                                                {{ $sub->formatted_duration }}
                                            </span>
                                        </td>

                                        <!-- Kolom Khusus: Upah per Jam / per Hari -->
                                        <td class="text-right align-middle text-nowrap !px-2.5 !py-2" style="color: #475569; font-size: 12.5px; font-weight: 500; font-variant-numeric: tabular-nums;">
                                            {{ $sub->formatted_rate_label }}
                                        </td>

                                        <!-- Kolom Khusus: Total Upah Lembur -->
                                        <td class="text-right align-middle text-nowrap !px-2.5 !py-2" style="color: #0b52aa; font-size: 13.5px; font-weight: 700; font-variant-numeric: tabular-nums;">
                                            {{ $sub->formatted_subtotal_overtime }}
                                        </td>

                                        <!-- Status Badge dengan Icon Pending & Font Nyaman Dibaca -->
                                        <td class="text-center align-middle text-nowrap !px-2.5 !py-2">
                                            <span style="display: inline-flex; align-items: center; padding: 3.5px 11px; font-size: 12px; font-weight: 600; border-radius: 6px; {{ $sub->status->badgeStyle() }}">
                                                <i class="{{ $sub->status->icon() }} mr-1.5" style="font-size: 10.5px;"></i>
                                                {{ $sub->status->label() }}
                                            </span>
                                        </td>

                                        <!-- Tombol Aksi Standar Kasbon / Stisla .btn-action -->
                                        <td class="text-center align-middle text-nowrap !px-2.5 !py-2">
                                            <!-- LIHAT DETAIL -->
                                            <button type="button" class="btn btn-info btn-action mr-1" wire:click="openDetailModal('{{ $sub->hashid }}')" title="Rincian Detail Pengajuan">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <!-- AKSI ADMIN: VERIFIKASI JAM / TOLAK -->
                                            @if (auth()->user()->hasRole('admin') && $sub->status === \App\Enums\OvertimeStatus::PENDING_ADMIN)
                                                <button type="button" class="btn btn-primary btn-action mr-1" wire:click="openAdjustModal('{{ $sub->hashid }}')" title="Koordinasi & Sesuaikan Jam Kerja">
                                                    <i class="fas fa-user-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-action mr-1" wire:click="openRejectModal('{{ $sub->hashid }}')" title="Tolak Pengajuan Lembur">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif

                                            <!-- HAPUS UNTUK ADMIN JIKA DIINPUT ADMIN & BELUM MASUK PERIODE -->
                                            @if (auth()->user()->hasRole('admin') && $sub->submission_source === \App\Enums\OvertimeSubmissionSource::ADMIN && is_null($sub->overtime_period_id))
                                                <button type="button" class="btn btn-danger btn-action" wire:click="confirmDelete('{{ $sub->hashid }}')" title="Hapus Pengajuan Lembur">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif

                                            <!-- AKSI DIREKTUR: APPROVE / TOLAK DENGAN SWEETALERT2 -->
                                            @if (auth()->user()->hasRole('direktur') && $sub->status === \App\Enums\OvertimeStatus::PENDING_DIREKTUR)
                                                <button type="button" class="btn btn-success btn-action mr-1" onclick="confirmApproveOvertime({{ $sub->id }}, '{{ $sub->reference_number }}')" title="Setujui Lembur">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-action" onclick="confirmRejectOvertime({{ $sub->id }}, '{{ $sub->reference_number }}')" title="Tolak Pengajuan Lembur">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif

                                            <!-- AJUKAN ULANG UNTUK KARYAWAN JIKA DITOLAK -->
                                            @if ($isKaryawan && in_array($sub->status, [\App\Enums\OvertimeStatus::REJECTED_ADMIN, \App\Enums\OvertimeStatus::REJECTED_DIREKTUR], true))
                                                <button type="button" class="btn btn-warning btn-action" wire:click="reSubmit('{{ $sub->hashid }}')" title="Ajukan Ulang / Perbaiki Data">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ $isKaryawan ? 7 : 8 }}" class="p-0 border-0">
                                    <x-empty-state
                                        icon="fas fa-business-time"
                                        :title="($search || $statusFilter !== 'all') ? 'Tidak Ada Pengajuan Lembur yang Cocok' : 'Belum Ada Pengajuan Lembur'"
                                        :subtitle="($search || $statusFilter !== 'all')
                                            ? 'Tidak ditemukan data pengajuan lembur yang sesuai dengan kriteria filter pencarian.'
                                            : 'Belum ada tiket pengajuan lembur yang diajukan pada periode ini.'"
                                        :action-label="($isKaryawan || auth()->user()?->hasRole('admin')) ? 'Tambah Pengajuan Lembur' : null"
                                        action-wire-click="openCreateModal"
                                        action-icon="fas fa-plus"
                                    />
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            @if ($submissions->hasPages())
                <div class="card-footer bg-white border-top text-right" style="padding: 12px 20px;">
                    {{ $submissions->links() }}
                </div>
            @endif
        @endif

        <!-- ================= TAB 2: PERIODE LEMBUR MINGGUAN ================= -->
        @if ($activeTab === 'periods' && ! $isKaryawan)
            <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th class="text-center text-nowrap !px-2.5 !py-2" style="width: 45px; color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">NO</th>
                            <th class="text-nowrap !px-2.5 !py-2" style="color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">PERIODE</th>
                            <th class="text-center text-nowrap !px-2.5 !py-2" style="width: 110px; color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">KARYAWAN</th>
                            <th class="text-right text-nowrap !px-2.5 !py-2" style="color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">UPAH LEMBUR</th>
                            <th class="text-right text-nowrap !px-2.5 !py-2" style="color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">UPAH MAKAN</th>
                            <th class="text-right text-nowrap !px-2.5 !py-2" style="color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">TOTAL BIAYA</th>
                            <th class="text-center text-nowrap !px-2.5 !py-2" style="width: 140px; color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;">STATUS</th>
                            <th class="text-center text-nowrap !px-2.5 !py-2" style="width: 110px; color: #334155; font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $idx => $per)
                            @php
                                $cleanName = preg_replace('/\s*\([^)]*\)\s*$/', '', $per->name);
                            @endphp
                            <tr wire:key="period-row-{{ $per->id }}" class="payroll-table-row" style="border-bottom: 1px solid #f1f5f9;">
                                <!-- NO -->
                                <td class="text-center align-middle !px-2.5 !py-2 text-nowrap" style="color: #475569; font-size: 12.5px; font-weight: 600;">
                                    {{ $periods->firstItem() + $idx }}
                                </td>

                                <!-- PERIODE (Nama & Rentang Tanggal) -->
                                <td class="align-middle text-nowrap !px-2.5 !py-2">
                                    <div class="d-flex align-items-center text-nowrap">
                                        <span class="d-inline-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 32px; height: 32px; border-radius: 8px; background-color: #eff6ff; border: 1.5px solid #bfdbfe;">
                                            <i class="far fa-calendar-alt" style="color: #0b52aa; font-size: 13px;"></i>
                                        </span>
                                        <div>
                                            <div style="color: #0f172a; font-size: 13px; font-weight: 600; line-height: 1.3;">
                                                {{ $cleanName ?: $per->name }}
                                            </div>
                                            <div class="mt-0.5" style="font-size: 11.5px; color: #64748b; font-weight: 400; line-height: 1.25;">
                                                {{ $per->start_date->locale('id')->translatedFormat('d M') }} - {{ $per->end_date->locale('id')->translatedFormat('d M Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- KARYAWAN -->
                                <td class="text-center align-middle text-nowrap !px-2.5 !py-2">
                                    <span class="badge px-2.5 py-1" style="font-size: 12px; font-weight: 600; background-color: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; border-radius: 6px;">
                                        {{ $per->employee_count }} Orang
                                    </span>
                                </td>

                                <!-- UPAH LEMBUR -->
                                <td class="text-right align-middle text-nowrap !px-2.5 !py-2" style="color: #475569; font-size: 12.5px; font-weight: 500; font-variant-numeric: tabular-nums;">
                                    {{ $per->formatted_total_overtime_pay }}
                                </td>

                                <!-- UPAH MAKAN -->
                                <td class="text-right align-middle text-nowrap !px-2.5 !py-2" style="color: #0284c7; font-size: 12.5px; font-weight: 500; font-variant-numeric: tabular-nums;">
                                    {{ $per->formatted_total_meal_allowance }}
                                </td>

                                <!-- TOTAL BIAYA (GRAND TOTAL) -->
                                <td class="text-right align-middle text-nowrap !px-2.5 !py-2" style="color: #0b52aa; font-size: 13.5px; font-weight: 700; font-variant-numeric: tabular-nums;">
                                    {{ $per->formatted_grand_total }}
                                </td>

                                <!-- STATUS -->
                                <td class="text-center align-middle text-nowrap !px-2.5 !py-2">
                                    <span style="display: inline-flex; align-items: center; padding: 3.5px 11px; font-size: 12px; font-weight: 600; border-radius: 6px; {{ $per->status->badgeStyle() }}">
                                        {{ $per->status->label() }}
                                    </span>
                                    @if ($per->paid_at)
                                        <div class="text-muted" style="font-size: 10.5px; margin-top: 2px;">
                                            {{ $per->paid_at->locale('id')->translatedFormat('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                </td>

                                <!-- AKSI STANDAR KASBON / STISLA .btn-action -->
                                <td class="text-center align-middle text-nowrap !px-2.5 !py-2">
                                    <!-- ATUR KEHADIRAN & UANG MAKAN (HALAMAN DEDIKASI) -->
                                    @if (auth()->user()->hasRole('admin'))
                                        <a href="{{ route('overtimes.periods.attendances', $per->id) }}" class="btn btn-warning btn-action mr-1" title="Atur Hari Kehadiran & Uang Makan Seluruh Karyawan">
                                            <i class="fas fa-calendar-check"></i>
                                        </a>
                                    @endif

                                    <!-- CETAK SLIP MINGGUAN -->
                                    <a href="{{ route('overtimes.periods.print', $per->id) }}" target="_blank" class="btn btn-info btn-action mr-1" title="Cetak Semua Slip Periode Ini">
                                        <i class="fas fa-print"></i>
                                    </a>

                                    <!-- TANDAI SUDAH DIBAYAR -->
                                    @if (auth()->user()->hasRole('admin') && $per->status === \App\Enums\OvertimePeriodStatus::DRAFT)
                                        <button type="button" class="btn btn-success btn-action" wire:click="markPeriodPaid({{ $per->id }})" wire:confirm="Tandai periode lembur ini telah selesai dibayar ke seluruh karyawan?" title="Tandai Selesai Dibayar">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="empty-state py-4">
                                        <div class="empty-state-icon bg-light text-muted mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                            <i class="fas fa-calendar-times"></i>
                                        </div>
                                        <h6 class="text-dark mb-1 font-weight-bold">
                                            Belum Ada Periode Lembur Mingguan
                                        </h6>
                                        <p class="text-muted small mb-0">
                                            Admin dapat membuat periode mingguan untuk mengelompokkan lembur yang telah disetujui Direktur.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($periods->hasPages())
                <div class="card-footer bg-white border-top text-right" style="padding: 12px 20px;">
                    {{ $periods->links() }}
                </div>
            @endif
        @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL 1: FORM PENGAJUAN LEMBUR ================= -->
    <div wire:ignore.self class="modal fade" id="overtimeSubmissionModal" role="dialog" aria-labelledby="submissionModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
                <div class="modal-header bg-white border-bottom py-3">
                    <h5 class="modal-title text-dark" id="submissionModalLabel" style="font-weight: 500;">
                        Form Pengajuan Lembur
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form wire:submit.prevent="storeSubmission">
                    <div class="modal-body py-3 px-4 bg-white">
                        @if (! $isKaryawan)
                            <!-- PILIH KARYAWAN UNTUK ADMIN (Select2 Searchable Sesuai Pola Kasbon) -->
                            <div class="form-group mb-3">
                                <label for="form_employee_id">Karyawan <span class="text-danger">*</span></label>
                                <div wire:ignore>
                                    @php
                                        $groupedEligible = $eligibleEmployees->groupBy(fn ($emp) => $emp->department?->name ?? 'Tanpa Departemen')->sortKeys();
                                    @endphp
                                    <select id="form_employee_id" class="form-control select2 @error('form.employee_id') is-invalid @enderror">
                                        <option value="">-- Pilih Karyawan Berhak Lembur --</option>
                                        @foreach ($groupedEligible as $deptName => $deptEmployees)
                                            <optgroup label="{{ $deptName }}">
                                                @foreach ($deptEmployees as $emp)
                                                    @php
                                                        $hourlyRate = (float) $emp->overtime_rate_per_hour;
                                                        $dailyRate = (float) $emp->overtime_rate_per_day;
                                                        $hourlyStr = $hourlyRate >= 1000 ? rtrim(rtrim(number_format($hourlyRate / 1000, 1, ',', '.'), '0'), ',') . 'rb/jam' : 'Rp ' . number_format($hourlyRate, 0, ',', '.') . '/jam';
                                                        $dailyStr = $dailyRate >= 1000 ? rtrim(rtrim(number_format($dailyRate / 1000, 1, ',', '.'), '0'), ',') . 'rb/hari' : 'Rp ' . number_format($dailyRate, 0, ',', '.') . '/hari';
                                                    @endphp
                                                    <option value="{{ $emp->id }}">
                                                        {{ $emp->full_name }} · {{ $hourlyStr }} · {{ $dailyStr }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                @error('form.employee_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <!-- 2. Tipe Lembur (Badge Tab Tanpa Ikon) -->
                        <div class="form-group mb-3">
                            <label class="d-block mb-2 font-weight-500 text-dark">Tipe Lembur <span class="text-danger">*</span></label>
                            <div class="d-flex" style="gap: 10px;">
                                <button type="button"
                                        wire:click="setOvertimeType('hourly')"
                                        class="btn flex-fill py-2 px-3 {{ $form->overtime_type === 'hourly' ? 'btn-primary text-white shadow-sm font-weight-600' : 'bg-white border text-dark font-weight-500' }}"
                                        style="border-radius: 10px; font-size: 13px; transition: all 0.15s ease; border-color: {{ $form->overtime_type === 'hourly' ? '#0b52aa' : '#e2e8f0' }} !important; {{ $form->overtime_type === 'hourly' ? 'background-color: #0b52aa !important;' : 'background-color: #ffffff !important;' }}">
                                    Lembur Per Jam
                                </button>
                                <button type="button"
                                        wire:click="setOvertimeType('daily')"
                                        class="btn flex-fill py-2 px-3 {{ $form->overtime_type === 'daily' ? 'btn-primary text-white shadow-sm font-weight-600' : 'bg-white border text-dark font-weight-500' }}"
                                        style="border-radius: 10px; font-size: 13px; transition: all 0.15s ease; border-color: {{ $form->overtime_type === 'daily' ? '#0b52aa' : '#e2e8f0' }} !important; {{ $form->overtime_type === 'daily' ? 'background-color: #0b52aa !important;' : 'background-color: #ffffff !important;' }}">
                                    Lembur Seharian (Harian)
                                </button>
                            </div>
                            @error('form.overtime_type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($form->overtime_type === 'hourly')
                            <!-- FORM LEMBUR PER JAM -->
                            <div class="row">
                                <!-- TANGGAL LEMBUR -->
                                <div class="col-md-4 col-12">
                                    <div class="form-group">
                                        <label for="form_date">Tanggal Lembur <span class="text-danger">*</span></label>
                                        <input type="date" id="form_date" wire:model="form.date" class="form-control @error('form.date') is-invalid @enderror">
                                        @error('form.date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- JAM MULAI -->
                                <div class="col-md-4 col-12">
                                    <div class="form-group">
                                        <label for="form_start_time">Jam Mulai <span class="text-danger">*</span></label>
                                        <input type="time" id="form_start_time" wire:model.live="form.start_time" class="form-control @error('form.start_time') is-invalid @enderror">
                                        @error('form.start_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- JAM SELESAI -->
                                <div class="col-md-4 col-12">
                                    <div class="form-group">
                                        <label for="form_end_time">Jam Selesai <span class="text-danger">*</span></label>
                                        <input type="time" id="form_end_time" wire:model.live="form.end_time" class="form-control @error('form.end_time') is-invalid @enderror">
                                        @error('form.end_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- ESTIMASI DURASI (Clean White Card dengan Rounded 12px & Shadow) -->
                            @if ($form->calculateEstimatedHours() > 0)
                                <div class="alert alert-light border small text-muted mb-3" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
                                    <div class="d-flex align-items-center text-dark font-weight-500 mb-1" style="font-size: 12px;">
                                        <i class="far fa-clock text-primary mr-1.5"></i>
                                        <span>Estimasi Durasi Kerja Lembur: <span class="text-primary font-weight-600">{{ $form->calculateEstimatedHours() }} jam</span></span>
                                    </div>
                                    <div style="font-size: 11px; line-height: 1.45; color: #64748b;">
                                        Durasi dihitung otomatis berdasarkan pembulatan yang berlaku di perusahaan.
                                    </div>
                                </div>
                            @endif
                        @else
                            <!-- 3. Form Lembur Harian (Clean White Card dengan Rounded 12px & Shadow) -->
                            <div class="form-group">
                                <label for="form_date_daily">Tanggal Lembur <span class="text-danger">*</span></label>
                                <input type="date" id="form_date_daily" wire:model="form.date" class="form-control @error('form.date') is-invalid @enderror">
                                @error('form.date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-light border small text-muted mb-3" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
                                <div class="d-flex align-items-center text-dark font-weight-500 mb-1" style="font-size: 12px;">
                                    <i class="fas fa-sun text-warning mr-1.5"></i>
                                    <span>Lembur Seharian Penuh</span>
                                </div>
                                <div style="font-size: 11px; line-height: 1.45; color: #64748b;">
                                    Durasi dihitung 1 hari penuh otomatis menggunakan nominal lembur harian karyawan. Range jam kerja tidak diperlukan.
                                </div>
                            </div>
                        @endif

                        <!-- KEPERLUAN / ALASAN LEMBUR -->
                        <div class="form-group">
                            <label for="form_reason">Uraian / Pekerjaan yang Dikerjakan <span class="text-danger">*</span></label>
                            <textarea id="form_reason" wire:model="form.reason" rows="3" placeholder="Tuliskan rincian tugas atau pekerjaan lembur..." class="form-control @error('form.reason') is-invalid @enderror"></textarea>
                            @error('form.reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if (! $isKaryawan)
                            <!-- CATATAN ADMIN -->
                            <div class="form-group mb-0">
                                <label for="form_admin_notes">Catatan Admin <small class="text-muted">(Opsional)</small></label>
                                <input type="text" id="form_admin_notes" wire:model="form.admin_notes" class="form-control" placeholder="Catatan tambahan bila diinput oleh admin...">
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer bg-white border-top px-4 py-3">
                        <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Batal</button>
                        <button type="submit" class="btn btn-primary shadow-none" wire:loading.attr="disabled" wire:target="storeSubmission" style="font-weight: 500;">
                            <span wire:loading wire:target="storeSubmission"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                            <span wire:loading.remove wire:target="storeSubmission"><i class="fas fa-paper-plane mr-1"></i> Kirim Pengajuan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL 2: VERIFIKASI & SESUAIKAN JAM ADMIN ================= -->
    <div wire:ignore.self class="modal fade" id="overtimeAdjustModal" tabindex="-1" role="dialog" aria-labelledby="adjustModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
                <div class="modal-header bg-white border-bottom py-3">
                    <h5 class="modal-title text-dark" id="adjustModalLabel" style="font-weight: 500;">
                        Verifikasi &amp; Sesuaikan Lembur
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form wire:submit.prevent="submitAdjustment">
                    <div class="modal-body py-3 px-4 bg-white">
                        @if ($adjustingSubmission)
                            @php
                                $adjEmp = $adjustingSubmission->employee;
                                $adjEmpName = $adjEmp?->full_name ?: 'Karyawan';
                            @endphp
                            <!-- Identitas & Data Asli Pengajuan (Clean White Card dengan Rounded 12px & Shadow) -->
                            <div class="alert alert-light border small text-muted mb-3" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="text-dark font-weight-600" style="font-size: 14px;">{{ $adjEmpName }}</div>
                                        <div style="font-size: 11.5px; color: #64748b;">
                                            {{ $adjEmp?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $adjEmp?->position?->name ?? 'Staff' }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-light border font-weight-600" style="font-size: 11px;">
                                            {{ $adjustingSubmission->date->locale('id')->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color: #e2e8f0; font-size: 12px;">
                                    <div>
                                        <span class="text-muted">Diajukan:</span> {{ $adjustingSubmission->isDaily() ? 'Lembur Harian (1 Hari)' : $adjustingSubmission->formatted_work_hours . ' (' . number_format($adjustingSubmission->duration_hours, 2) . ' jam)' }}
                                    </div>
                                    <div>
                                        <span class="text-muted">Nominal:</span> {{ $adjustingSubmission->formatted_rate_label }}
                                    </div>
                                </div>
                                @if ($adjustingSubmission->reason)
                                    <div class="mt-2 pt-2 border-top text-xs text-muted" style="border-color: #e2e8f0;">
                                        <span class="font-weight-500">Keperluan:</span> {{ $adjustingSubmission->reason }}
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($adjustingSubmission && $adjustingSubmission->isDaily())
                            <div class="alert alert-light border small text-muted mb-3" style="background-color: #ffffff; border: 1px solid #bbf7d0; border-left: 3px solid #16a34a; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
                                <div class="d-flex align-items-center text-success font-weight-600 mb-1" style="font-size: 13px;">
                                    <i class="fas fa-sun mr-2 text-warning"></i>
                                    <span>Lembur Harian (1 Hari Penuh)</span>
                                </div>
                                <div style="font-size: 12px; color: #166534; line-height: 1.45;">
                                    Durasi lembur dihitung 1 hari penuh otomatis menggunakan nominal lembur harian karyawan ({{ $adjustingSubmission->formatted_rate_label }}). Range jam kerja tidak perlu disesuaikan.
                                </div>
                            </div>
                        @else
                        <div class="row">
                            <!-- JAM MULAI ADJUST -->
                            <div class="col-md-4 col-12">
                                <div class="form-group">
                                    <label for="adjust_start_time">Jam Mulai <span class="text-danger">*</span></label>
                                    <input type="time" id="adjust_start_time" wire:model.live="adjust_start_time" class="form-control @error('adjust_start_time') is-invalid @enderror">
                                    @error('adjust_start_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- JAM SELESAI ADJUST -->
                            <div class="col-md-4 col-12">
                                <div class="form-group">
                                    <label for="adjust_end_time">Jam Selesai <span class="text-danger">*</span></label>
                                    <input type="time" id="adjust_end_time" wire:model.live="adjust_end_time" class="form-control @error('adjust_end_time') is-invalid @enderror">
                                    @error('adjust_end_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4 col-12">
                                <div class="form-group">
                                    <label for="adjust_duration_hours">Total Durasi (Jam) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.25" min="0.25" id="adjust_duration_hours" wire:model="adjust_duration_hours" class="form-control @error('adjust_duration_hours') is-invalid @enderror">
                                    @error('adjust_duration_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- CATATAN ADMIN -->
                        <div class="form-group mb-0">
                            <label for="adjust_admin_notes">Catatan Hasil Koordinasi <small class="text-muted">(Opsional)</small></label>
                            <textarea id="adjust_admin_notes" wire:model="adjust_admin_notes" class="form-control @error('adjust_admin_notes') is-invalid @enderror" rows="2" placeholder="Contoh: Jam disesuaikan dari 4 jam menjadi 3 jam setelah verifikasi log kerja..."></textarea>
                            @error('adjust_admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer bg-white border-top px-4 py-3">
                        <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Batal</button>
                        <button type="submit" class="btn btn-primary shadow-none" wire:loading.attr="disabled" wire:target="submitAdjustment" style="font-weight: 500;">
                            <span wire:loading wire:target="submitAdjustment"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                            <span wire:loading.remove wire:target="submitAdjustment"><i class="fas fa-paper-plane mr-1"></i> Verifikasi &amp; Teruskan ke Direktur</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL 3: PENOLAKAN LEMBUR ================= -->
    <div wire:ignore.self class="modal fade" id="overtimeRejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
                <div class="modal-header bg-white border-bottom py-3">
                    <h5 class="modal-title text-danger" id="rejectModalLabel" style="font-weight: 500;">
                        Tolak Pengajuan Lembur
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form wire:submit.prevent="submitRejection">
                    <div class="modal-body py-3 px-4 bg-white">
                        <div class="form-group mb-0">
                            <label for="rejectionReason">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea id="rejectionReason" wire:model="rejectionReason" class="form-control @error('rejectionReason') is-invalid @enderror" rows="3" placeholder="Tuliskan alasan penolakan secara jelas..."></textarea>
                            @error('rejectionReason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer bg-white border-top px-4 py-3">
                        <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Batal</button>
                        <button type="submit" class="btn btn-danger shadow-none" wire:loading.attr="disabled" wire:target="submitRejection" style="font-weight: 500;">
                            <span wire:loading wire:target="submitRejection"><i class="fas fa-spinner fa-spin mr-1"></i> Memproses...</span>
                            <span wire:loading.remove wire:target="submitRejection"><i class="fas fa-times mr-1"></i> Konfirmasi Tolak</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL 4: DETAIL PENGAJUAN LEMBUR (KASBON STYLE) ================= -->
    <div wire:ignore.self class="modal fade" id="overtimeDetailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
                <div class="modal-header bg-white border-bottom py-3">
                    <h5 class="modal-title text-dark" id="detailModalLabel" style="font-weight: 500;">
                        Rincian Pengajuan Lembur
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body px-4 py-3 bg-white">
                    @if ($detailSubmission)
                        @php
                            $dEmp = $detailSubmission->employee;
                            $dEmpName = $dEmp?->full_name ?: 'Karyawan';
                            $cleanedWords = array_values(array_filter(
                                preg_split('/\s+/', trim($dEmpName)),
                                fn($w) => preg_match('/[a-zA-Z0-9]/', $w)
                            ));
                            $dInitials = '';
                            if (!empty($cleanedWords)) {
                                $dInitials .= strtoupper(substr($cleanedWords[0], 0, 1));
                                if (isset($cleanedWords[1])) {
                                    $dInitials .= strtoupper(substr($cleanedWords[1], 0, 1));
                                }
                            }
                            $dInitials = $dInitials ?: 'K';
                        @endphp

                        <!-- 1. Identitas Karyawan -->
                        <div class="text-center pt-1">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 font-weight-bold"
                                 style="width: 60px; height: 60px; background-color: #eff6ff; color: #0284c7; border: 2px solid #bae6fd; font-size: 19px;">
                                {{ $dInitials }}
                            </div>
                            <div class="font-weight-bold" style="font-size: 17px; color: #0f172a; margin-bottom: 2px;">
                                {{ $dEmpName }}
                            </div>
                            <div style="font-size: 12.5px; color: #64748b; font-weight: 500;">
                                {{ $dEmp?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $dEmp?->position?->name ?? 'Staff' }}
                            </div>
                        </div>

                        <!-- Dashed Divider -->
                        <div style="border-top: 1px dashed #e2e8f0; margin: 14px 0;"></div>

                        <!-- 2. Nominal Upah Lembur & Status Badge -->
                        <div class="text-center">
                            <div class="text-uppercase" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; color: #64748b; margin-bottom: 3px;">
                                TOTAL UPAH LEMBUR
                            </div>
                            <div class="font-weight-bold" style="font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-bottom: 6px;">
                                {{ $detailSubmission->formatted_subtotal_overtime }}
                            </div>
                            <div>
                                <span style="display: inline-flex; align-items: center; padding: 4px 12px; font-size: 11.5px; font-weight: 500; border-radius: 6px; {{ $detailSubmission->status->badgeStyle() }}">
                                    <i class="{{ $detailSubmission->status->icon() }} mr-1.5" style="font-size: 10px;"></i>
                                    {{ $detailSubmission->status->label() }}
                                </span>
                            </div>
                        </div>

                        <!-- Dashed Divider -->
                        <div style="border-top: 1px dashed #e2e8f0; margin: 14px 0;"></div>

                        <!-- 3. Rincian Detail Box (Clean White Card dengan Rounded 12px & Shadow) -->
                        <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #ffffff; margin-bottom: 14px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
                            <!-- No. Referensi -->
                            <div class="d-flex justify-content-between align-items-center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-hashtag mr-2" style="color: #0b52aa; font-size: 13px; width: 16px; text-align: center;"></i>
                                    <span style="color: #475569; font-size: 12.5px; font-weight: 500;">No. Referensi</span>
                                </div>
                                <span style="color: #0f172a; font-size: 12.5px; font-weight: 500; font-family: monospace;">{{ $detailSubmission->reference_number }}</span>
                            </div>

                            <!-- Tanggal Lembur -->
                            <div class="d-flex justify-content-between align-items-center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                                <div class="d-flex align-items-center">
                                    <i class="far fa-calendar-alt mr-2" style="color: #0b52aa; font-size: 13px; width: 16px; text-align: center;"></i>
                                    <span style="color: #475569; font-size: 12.5px; font-weight: 500;">Tanggal Lembur</span>
                                </div>
                                <span style="color: #0f172a; font-size: 12.5px; font-weight: 500;">{{ $detailSubmission->date->locale('id')->translatedFormat('d F Y') }}</span>
                            </div>

                            <!-- Jam Kerja & Durasi -->
                            <div class="d-flex justify-content-between align-items-center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                                <div class="d-flex align-items-center">
                                    <i class="far fa-clock mr-2" style="color: #0b52aa; font-size: 13px; width: 16px; text-align: center;"></i>
                                    <span style="color: #475569; font-size: 12.5px; font-weight: 500;">Jam Kerja &amp; Durasi</span>
                                </div>
                                <span style="color: #0f172a; font-size: 12.5px; font-weight: 500;">
                                    @if ($detailSubmission->isDaily())
                                        <span class="badge px-2 py-1 mr-1 font-weight-600" style="font-size: 11px; background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 6px;">
                                            <i class="fas fa-sun mr-1 text-warning"></i> Lembur Harian
                                        </span>
                                        <span class="badge badge-light border font-weight-600">1 Hari Penuh</span>
                                    @else
                                        {{ $detailSubmission->formatted_work_hours }}
                                        <span class="badge badge-light border ml-1 font-weight-600">({{ (float) $detailSubmission->duration_hours == (int) $detailSubmission->duration_hours ? (int) $detailSubmission->duration_hours : number_format($detailSubmission->duration_hours, 1) }} jam)</span>
                                    @endif
                                </span>
                            </div>

                            <!-- Nominal Lembur -->
                            <div class="d-flex justify-content-between align-items-center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-coins mr-2" style="color: #0b52aa; font-size: 13px; width: 16px; text-align: center;"></i>
                                    <span style="color: #475569; font-size: 12.5px; font-weight: 500;">Nominal Lembur</span>
                                </div>
                                <span style="color: #0f172a; font-size: 12.5px; font-weight: 500;">{{ $detailSubmission->formatted_rate_label }}</span>
                            </div>

                            <!-- Asal Pengajuan -->
                            <div class="d-flex justify-content-between align-items-center" style="padding: 10px 14px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-tag mr-2" style="color: #0b52aa; font-size: 13px; width: 16px; text-align: center;"></i>
                                    <span style="color: #475569; font-size: 12.5px; font-weight: 500;">Asal Pengajuan</span>
                                </div>
                                <span class="font-weight-600" style="font-size: 11px; padding: 2px 8px; border-radius: 6px; {{ $detailSubmission->submission_source->badgeStyle() }}">
                                    {{ $detailSubmission->submission_source->label() }}
                                </span>
                            </div>
                        </div>

                        <!-- Uraian Pekerjaan (Clean White Card dengan Rounded & Shadow) -->
                        <div class="mb-3">
                            <label class="mb-1" style="font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; color: #475569;">Alasan / Uraian Pekerjaan:</label>
                            <div class="p-3 text-dark" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); font-size: 12.5px; line-height: 1.5; font-weight: 400;">
                                {{ $detailSubmission->reason }}
                            </div>
                        </div>

                        @if ($detailSubmission->admin_notes)
                            <div class="mb-3">
                                <label class="text-info mb-1" style="font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Catatan Verifikasi Admin:</label>
                                <div class="p-3 text-dark" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-left: 3px solid #0b52aa; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); font-size: 12.5px; font-weight: 400;">
                                    <div class="text-primary font-weight-500 mb-1" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-info-circle mr-1"></i> Catatan Admin
                                    </div>
                                    {{ $detailSubmission->admin_notes }}
                                </div>
                            </div>
                        @endif

                        @if ($detailSubmission->rejection_reason)
                            <div class="mb-3">
                                <label class="text-danger mb-1" style="font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Alasan Penolakan:</label>
                                <div class="p-3 text-dark" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-left: 3px solid #dc3545; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); font-size: 12.5px; font-weight: 400;">
                                    <div class="text-danger font-weight-500 mb-1" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Alasan Penolakan
                                    </div>
                                    {{ $detailSubmission->rejection_reason }}
                                </div>
                            </div>
                        @endif

                        @if ($detailSubmission->overtimePeriod)
                            <div class="p-3 mb-0 d-flex align-items-center" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); font-size: 12px;">
                                <i class="fas fa-calendar-check mr-2 text-success"></i>
                                <span>
                                    Masuk dalam Periode Mingguan: <strong>{{ $detailSubmission->overtimePeriod->name }}</strong> ({{ $detailSubmission->overtimePeriod->reference_number }})
                                </span>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between align-items-center">
                    @if ($detailSubmission && auth()->user()->hasRole('admin') && $detailSubmission->submission_source === \App\Enums\OvertimeSubmissionSource::ADMIN && is_null($detailSubmission->overtime_period_id))
                        <button type="button" class="btn btn-danger btn-sm" wire:click="confirmDelete('{{ $detailSubmission->hashid }}')" style="font-weight: 500; border-radius: 8px;">
                            <i class="fas fa-trash-alt mr-1"></i> Hapus Pengajuan
                        </button>
                    @elseif ($detailSubmission && auth()->user()->hasRole('direktur') && $detailSubmission->status === \App\Enums\OvertimeStatus::PENDING_DIREKTUR)
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            <button type="button" class="btn btn-danger btn-sm" onclick="$('#overtimeDetailModal').modal('hide'); confirmRejectOvertime({{ $detailSubmission->id }}, '{{ $detailSubmission->reference_number }}');" style="font-weight: 500; border-radius: 8px;">
                                <i class="fas fa-times mr-1"></i> Tolak Pengajuan
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="$('#overtimeDetailModal').modal('hide'); confirmApproveOvertime({{ $detailSubmission->id }}, '{{ $detailSubmission->reference_number }}');" style="font-weight: 500; border-radius: 8px;">
                                <i class="fas fa-check mr-1"></i> Setujui Lembur
                            </button>
                        </div>
                    @else
                        <div></div>
                    @endif
                    <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL 5: BUAT PERIODE LEMBUR MINGGUAN (ADMIN) ================= -->
    @if (! $isKaryawan)
        <div wire:ignore.self class="modal fade" id="overtimePeriodModal" tabindex="-1" role="dialog" aria-labelledby="periodModalLabel" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
                    <div class="modal-header bg-white border-bottom py-3">
                        <h5 class="modal-title text-dark" id="periodModalLabel" style="font-weight: 500;">
                            Buat Periode Lembur Mingguan
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form wire:submit.prevent="storePeriod">
                        <div class="modal-body py-3 px-4 bg-white">
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="period_start_date">Tanggal Mulai <span class="text-danger">*</span></label>
                                        <input type="date" id="period_start_date" wire:model.live="period_start_date" class="form-control @error('period_start_date') is-invalid @enderror">
                                        @error('period_start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="period_end_date">Tanggal Akhir <span class="text-danger">*</span></label>
                                        <input type="date" id="period_end_date" wire:model.live="period_end_date" class="form-control @error('period_end_date') is-invalid @enderror">
                                        @error('period_end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-light border small text-muted mb-3" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
                                <div class="d-flex align-items-center mb-1 text-dark font-weight-500" style="font-size: 12px;">
                                    <i class="fas fa-calendar-check text-primary mr-1.5"></i>
                                    <span>Lembur Disetujui: <strong class="text-primary">{{ $pendingPeriodCount }} pengajuan</strong></span>
                                </div>
                                <div style="font-size: 11px; line-height: 1.45; color: #64748b;">
                                    @if ($pendingPeriodCount > 0)
                                        Terdapat {{ $pendingPeriodCount }} lembur berstatus disetujui direktur pada rentang tanggal di atas yang siap dikelompokkan ke periode ini bersama uang makan seluruh karyawan.
                                    @else
                                        Periode mingguan akan dibuat untuk merekap kehadiran kerja &amp; uang makan seluruh karyawan aktif.
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="period_name">Nama Periode <span class="text-danger">*</span></label>
                                <input type="text" id="period_name" wire:model="period_name" class="form-control @error('period_name') is-invalid @enderror" placeholder="Contoh: Lembur Minggu 43">
                                @error('period_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label for="period_notes">Catatan <small class="text-muted">(Opsional)</small></label>
                                <textarea id="period_notes" wire:model="period_notes" class="form-control" rows="2" placeholder="Catatan opsional mengenai periode lembur ini..."></textarea>
                            </div>
                        </div>

                        <div class="modal-footer bg-white border-top px-4 py-3">
                            <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Batal</button>
                            <button type="submit" class="btn btn-primary shadow-none" wire:loading.attr="disabled" wire:target="storePeriod" style="font-weight: 500;">
                                <span wire:loading wire:target="storePeriod"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                                <span wire:loading.remove wire:target="storePeriod"><i class="fas fa-save mr-1"></i> Simpan Periode</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- ================= MODAL TINJAU: VERIFIKASI ADMIN & APPROVAL DIREKTUR ================= -->
    @include('livewire.overtimes.pending-admin-modal')
    @include('livewire.overtimes.pending-director-modal')
</div>

@push('styles')
<style>
    /* Table Responsive Clean Scrollbar (Hide horizontal scrollbar while keeping UX scrollable if needed) */
    .table-responsive {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }
    .table-responsive::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }

    /* Direct Jump Row Pulse Animation */
    @keyframes rowHighlightPulse {
        0% { background-color: #bfdbfe; }
        50% { background-color: #dbeafe; }
        100% { background-color: #eff6ff; }
    }
    .table.table-clean tbody tr.table-row-highlight-pulse td,
    tr.table-row-highlight-pulse td {
        animation: rowHighlightPulse 1.2s ease-in-out;
    }

    /* Select2 Optgroup Header & Tab Indentation Styling */
    .select2-container .select2-results__group {
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 7px 14px !important;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        margin-top: 4px;
        display: block;
        cursor: default;
    }
    .select2-container .select2-results__optgroup:first-child .select2-results__group {
        border-top: none;
        margin-top: 0;
    }
    .select2-container .select2-results__options--nested .select2-results__option {
        padding-left: 24px !important; /* Menjorok/tab ke depan */
        font-size: 12.5px !important;
        color: #334155;
        border-bottom: 1px solid #f8fafc;
    }
    .select2-container .select2-results__options--nested .select2-results__option--highlighted[aria-selected],
    .select2-container .select2-results__options--nested .select2-results__option--highlighted {
        background-color: #0b52aa !important;
        color: #ffffff !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initSelect2 = () => {
            if ($('#form_employee_id').length) {
                $('#form_employee_id').select2({
                    dropdownParent: $('#overtimeSubmissionModal'),
                    width: '100%',
                    placeholder: '-- Pilih Karyawan Berhak Lembur --',
                    allowClear: true
                }).off('change').on('change', function () {
                    const val = $(this).val();
                    @this.set('form.employee_id', val ? parseInt(val) : null);
                });
            }
        };

        initSelect2();

        // Auto focus search field saat dropdown Select2 dibuka
        $(document).on('select2:open', () => {
            setTimeout(() => {
                const searchField = document.querySelector('.select2-container--open .select2-search__field');
                if (searchField) {
                    searchField.focus();
                }
            }, 10);
        });

        Livewire.on('open-modal', (event) => {
            const data = Array.isArray(event) ? event[0] : event;
            const modalId = typeof data === 'object' ? data.id : data;
            if (modalId === 'overtimeSubmissionModal') {
                setTimeout(() => {
                    const empVal = @this.get('form.employee_id');
                    $('#form_employee_id').val(empVal || '').trigger('change.select2');
                }, 50);
            }
        });

        $('#overtimeSubmissionModal').on('hidden.bs.modal', function () {
            $('#form_employee_id').val('').trigger('change.select2');
        });

        // SweetAlert2 Confirmation Dialogs untuk Direktur (Approve & Reject)
        window.confirmApproveOvertime = function (id, ref) {
            const titleText = ref ? `Setujui Lembur "${ref}"?` : 'Setujui Pengajuan Lembur?';
            Swal.fire({
                title: titleText,
                text: 'Pengajuan lembur ini akan disetujui Direktur dan siap dimasukkan ke dalam periode lembur mingguan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check mr-1"></i> Ya, Setujui',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('approve-overtime-submission', { id: id });
                }
            });
        };

        window.confirmRejectOvertime = function (id, ref) {
            const titleText = ref ? `Tolak Lembur "${ref}"?` : 'Tolak Pengajuan Lembur?';
            Swal.fire({
                title: titleText,
                text: 'Pengajuan lembur ini akan ditolak. Mohon tuliskan alasan penolakan secara jelas.',
                icon: 'warning',
                input: 'textarea',
                inputLabel: 'Alasan Penolakan (Wajib Diisi)',
                inputPlaceholder: 'Tuliskan alasan penolakan (minimal 5 karakter)...',
                inputValidator: (value) => {
                    if (!value || value.trim().length < 5) {
                        return 'Alasan penolakan wajib diisi (minimal 5 karakter)!';
                    }
                },
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-times mr-1"></i> Tolak Pengajuan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Livewire.dispatch('reject-overtime-submission', { id: id, reason: result.value.trim() });
                }
            });
        };

        // Listen direct navigation from review modals to overtime submission row
        Livewire.on('scroll-to-submission-row', (event) => {
            const data = Array.isArray(event) ? event[0] : event;
            const id = typeof data === 'object' && data !== null ? data.id : data;
            setTimeout(() => {
                const row = document.getElementById(`submission-row-${id}`);
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('table-row-highlight-pulse');
                    setTimeout(() => {
                        row.classList.remove('table-row-highlight-pulse');
                    }, 2500);
                }
            }, 150);
        });
    });
</script>
@endpush
