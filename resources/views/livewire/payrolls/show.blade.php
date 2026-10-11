<div>
    <x-slot name="header">
        <div class="section-header-back">
            <a href="{{ route('payrolls.index') }}" class="btn btn-icon" title="Kembali ke Daftar Penggajian">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
        <h1>Detail Penggajian</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('payrolls.index') }}">Penggajian</a></div>
            <div class="breadcrumb-item">{{ $period->reference_number }}</div>
        </div>
    </x-slot>

    <!-- ================= 1. NATIVE SECTION TITLE & LEAD (RINGKAS & JELAS) ================= -->
    <h2 class="section-title">Detail Penggajian: {{ $period->period_label }}</h2>
    <p class="section-lead">
        Rincian slip gaji dan alur pemrosesan penggajian karyawan.
    </p>

    <!-- ================= 2. ALUR KERJA & RINGKASAN PENGGAJIAN ================= -->
    @php
        $statusVal = $period->status->value;
        $isRejected = ($statusVal === 'rejected');
        $step2Done = in_array($statusVal, ['submitted', 'approved', 'paid'], true);
        $step3Done = in_array($statusVal, ['approved', 'paid'], true);
        $step4Done = ($statusVal === 'paid');
    @endphp

    <!-- ================= 2. RINGKASAN STATISTIK FINANSIAL ================= -->
    <div class="payroll-stat-grid mb-4">
        <!-- 1. Total Gaji Pokok -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Gaji Pokok">Total Gaji Pokok</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-primary" title="{{ $period->formatted_total_basic_salary }}">
                            {{ $period->formatted_total_basic_salary }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-chart-line text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">Dasar gaji {{ $period->employee_count }} karyawan</div>
                </div>
            </div>
        </div>

        <!-- 2. BPJS Perusahaan (CTC) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                    <i class="fas fa-building"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="BPJS Perusahaan (CTC)">BPJS Perusahaan</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-warning" title="{{ (float) $period->total_company_contributions > 0 ? $period->formatted_total_company_contributions : 'Rp 0' }}">
                            {{ (float) $period->total_company_contributions > 0 ? $period->formatted_total_company_contributions : 'Rp 0' }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-chart-line text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">Tanggungan kantor (BPJS)</div>
                </div>
            </div>
        </div>

        <!-- 3. Total Tunjangan (+) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Tunjangan (+)">Total Tunjangan (+)</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-success" title="+{{ $period->formatted_total_allowances }}">
                            +{{ $period->formatted_total_allowances }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-chart-line text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">Tambahan & lembur</div>
                </div>
            </div>
        </div>

        <!-- 4. Total Potongan (-) -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-red">
                    <i class="fas fa-minus-circle"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Potongan (-)">Total Potongan (-)</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-danger" title="-{{ $period->formatted_total_deductions }}">
                            -{{ $period->formatted_total_deductions }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-red">
                            <i class="fas fa-chart-line text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">PPh 21, BPJS, Kasbon</div>
                </div>
            </div>
        </div>

        <!-- 5. Total Take Home Pay -->
        <div class="card cash-advance-stat-card payroll-stat-card-take-home">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Take Home Pay">Total Take Home Pay</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-dark" title="{{ $period->formatted_total_net_salary }}">
                            {{ $period->formatted_total_net_salary }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-purple">
                            <i class="fas fa-chart-line text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">Total gaji ditransfer</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= 3. ALUR PENGGAJIAN (COL 3) & TABEL RINCIAN GAJI (COL 9) ================= -->
    <div class="row">
        <!-- Kolom Kiri: Alur Penggajian (col-12 col-lg-3) -->
        <div class="col-12 col-lg-3 mb-4">
            <div class="payroll-sidebar-sticky">
                <!-- Header Alur Penggajian (Garis Vertikal Mirip Section Title & Lead) -->
                <div class="payroll-sidebar-header mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <span class="payroll-sidebar-indicator bg-primary"></span>
                        <h5 class="payroll-sidebar-title mb-0">Alur Penggajian</h5>
                    </div>
                    <p class="payroll-sidebar-lead mb-0">
                        Tahapan proses penggajian dari draf hingga transfer gaji ke karyawan.
                    </p>
                </div>

                <div class="activities">
                    <!-- Tahap 1: Draf Gaji -->
                    <div class="activity">
                        <div class="activity-icon bg-primary text-white shadow-primary">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                        <div class="activity-detail">
                            <div class="mb-1">
                                <span class="text-job text-primary font-weight-bold">Tahap 1</span>
                                <span class="bullet"></span>
                                <span class="text-job text-muted">{{ $period->created_at ? $period->created_at->format('d M Y') : '-' }}</span>
                            </div>
                            <p class="mb-0 text-dark">
                                Draf penggajian dibuat oleh <strong>{{ $period->creator?->name ?? 'Admin' }}</strong>.
                            </p>
                        </div>
                    </div>

                    <!-- Tahap 2: Pengajuan -->
                    <div class="activity">
                        @if ($isRejected)
                            <div class="activity-icon bg-danger text-white shadow-danger">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-1">
                                    <span class="text-job text-danger font-weight-bold">Tahap 2</span>
                                    <span class="bullet"></span>
                                    <span class="text-job text-danger">{{ $period->updated_at ? $period->updated_at->format('d M Y') : '-' }}</span>
                                </div>
                                <p class="mb-0 text-danger">
                                    Pengajuan ditolak oleh <strong>{{ $period->approver?->name ?? 'Direktur' }}</strong> untuk revisi.
                                </p>
                            </div>
                        @elseif ($step2Done)
                            <div class="activity-icon bg-primary text-white shadow-primary">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-1">
                                    <span class="text-job text-primary font-weight-bold">Tahap 2</span>
                                    <span class="bullet"></span>
                                    <span class="text-job text-muted">{{ $period->submitted_at ? $period->submitted_at->format('d M Y') : ($period->created_at ? $period->created_at->format('d M Y') : '-') }}</span>
                                </div>
                                <p class="mb-0 text-dark">
                                    Draf diajukan oleh <strong>{{ $period->submitter?->name ?? ($period->creator?->name ?? 'Admin') }}</strong> ke Direktur.
                                </p>
                            </div>
                        @else
                            <div class="activity-icon activity-icon-pending">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-1">
                                    <span class="text-job activity-text-pending font-weight-bold">Tahap 2</span>
                                    <span class="bullet"></span>
                                    <span class="text-job activity-text-pending">Belum Diajukan</span>
                                </div>
                                <p class="mb-0 activity-desc-pending">
                                    Menunggu pengajuan draf penggajian ke Direktur.
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Tahap 3: Persetujuan (ACC) -->
                    <div class="activity">
                        @if ($step3Done)
                            <div class="activity-icon bg-primary text-white shadow-primary">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-1">
                                    <span class="text-job text-primary font-weight-bold">Tahap 3</span>
                                    <span class="bullet"></span>
                                    <span class="text-job text-muted">{{ $period->approved_at ? $period->approved_at->format('d M Y') : '-' }}</span>
                                </div>
                                <p class="mb-0 text-dark">
                                    Penggajian disetujui (ACC) oleh <strong>{{ $period->approver?->name ?? 'Direktur' }}</strong>.
                                </p>
                            </div>
                        @elseif ($statusVal === 'submitted')
                            <div class="activity-icon bg-primary text-white shadow-primary">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-1">
                                    <span class="text-job text-primary font-weight-bold">Tahap 3</span>
                                    <span class="bullet"></span>
                                    <span class="text-job text-primary">Menunggu ACC</span>
                                </div>
                                <p class="mb-0 text-primary">
                                    Sedang ditinjau oleh Direktur untuk persetujuan (ACC).
                                </p>
                            </div>
                        @else
                            <div class="activity-icon activity-icon-pending">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-1">
                                    <span class="text-job activity-text-pending font-weight-bold">Tahap 3</span>
                                    <span class="bullet"></span>
                                    <span class="text-job activity-text-pending">Belum Diproses</span>
                                </div>
                                <p class="mb-0 activity-desc-pending">
                                    Menunggu draf diajukan sebelum persetujuan.
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Tahap 4: Pembayaran Gaji -->
                    <div class="activity">
                        @if ($step4Done)
                            <div class="activity-icon bg-primary text-white shadow-primary">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-1">
                                    <span class="text-job text-primary font-weight-bold">Tahap 4</span>
                                    <span class="bullet"></span>
                                    <span class="text-job text-muted">{{ $period->payment_date ? $period->payment_date->format('d M Y') : '-' }}</span>
                                </div>
                                <p class="mb-0 text-dark">
                                    Gaji telah berhasil ditransfer ke seluruh karyawan. Dikonfirmasi oleh <strong>{{ $period->updater?->name ?? ($period->items()->whereNotNull('paid_by')->first()?->payer?->name ?? 'Admin') }}</strong>.
                                </p>
                            </div>
                        @elseif ($statusVal === 'approved')
                            <div class="activity-icon text-white" style="background-color: #64748b; box-shadow: 0 4px 8px rgba(100, 116, 139, 0.25);">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-1">
                                    <span class="text-job font-weight-bold" style="color: #64748b;">Tahap 4</span>
                                    <span class="bullet"></span>
                                    <span class="text-job font-weight-bold" style="color: #64748b;">Siap Dibayar</span>
                                </div>
                                <p class="mb-0" style="color: #475569;">
                                    Penggajian disetujui, siap untuk ditransfer ke karyawan.
                                </p>
                            </div>
                        @else
                            <div class="activity-icon activity-icon-pending">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-1">
                                    <span class="text-job activity-text-pending font-weight-bold">Tahap 4</span>
                                    <span class="bullet"></span>
                                    <span class="text-job activity-text-pending">Belum Diproses</span>
                                </div>
                                <p class="mb-0 activity-desc-pending">
                                    Transfer gaji diproses setelah persetujuan ACC.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Catatan Penolakan (Hanya muncul jika status ditolak Direktur) -->
                @if ($period->status->value === 'rejected' && $period->rejection_reason)
                    <div class="alert alert-danger mt-3 mb-0">
                        <div class="alert-title">Catatan Revisi Direktur:</div>
                        {{ $period->rejection_reason }}
                    </div>
                @endif

                @php
                    $hasActions = ($period->status->isEditable() && auth()->user()?->hasRole('admin')) 
                        || ($period->status->value === 'submitted' && auth()->user()?->hasRole('direktur'))
                        || ($period->status->value === 'paid');
                @endphp

                @if ($hasActions)
                    <!-- Action Buttons -->
                    <div class="mt-3">
                        <!-- Admin: Recalculate Taxes & Submit to Director -->
                        @if ($period->status->isEditable() && auth()->user()?->hasRole('admin'))
                            <div class="payroll-sidebar-actions">
                                @if ($company->enable_pph21)
                                    <button type="button" 
                                        wire:click="recalculateAllTaxes" 
                                        wire:loading.attr="disabled"
                                        class="btn btn-info payroll-action-btn"
                                        title="Hitung ulang PPh 21 untuk seluruh karyawan">
                                        <span wire:loading.remove wire:target="recalculateAllTaxes"><i class="fas fa-calculator mr-1"></i> Hitung Ulang Pajak</span>
                                        <span wire:loading wire:target="recalculateAllTaxes"><i class="fas fa-spinner fa-spin mr-1"></i> Menghitung...</span>
                                    </button>
                                @endif

                                <button type="button" 
                                    wire:click="$dispatch('swal:confirm', {
                                        title: 'Ajukan ke Direktur?',
                                        text: 'Draf penggajian {{ $period->reference_number }} akan diajukan ke Direktur untuk mendapatkan persetujuan.',
                                        action: 'submit-period-confirmed'
                                        })" 
                                    class="btn btn-primary payroll-action-btn">
                                    <i class="fas fa-paper-plane mr-1"></i> Ajukan ke Direktur
                                </button>
                            </div>
                        @endif

                        <!-- Direktur: Approve or Reject -->
                        @if ($period->status->value === 'submitted' && auth()->user()?->hasRole('direktur'))
                            <div class="payroll-sidebar-actions">
                                <button type="button" 
                                    wire:click="$dispatch('swal:confirm', {
                                        title: 'Setujui Penggajian?',
                                        text: 'Apakah Anda menyetujui total penggajian {{ $period->formatted_total_net_salary }} untuk periode {{ $period->period_label }}?',
                                        action: 'approve-period-confirmed',
                                        confirmButtonColor: '#28a745'
                                    })" 
                                    class="btn btn-success payroll-action-btn">
                                    <i class="fas fa-check mr-1"></i> Setujui Penggajian (ACC)
                                </button>
                                <button type="button" wire:click="openRejectModal" class="btn btn-danger payroll-action-btn">
                                    <i class="fas fa-times mr-1"></i> Tolak / Minta Revisi
                                </button>
                            </div>
                        @endif

                        {{-- Tombol konfirmasi pembayaran gaji di-comment dulu sesuai permintaan user --}}
                        {{--
                        @if ($period->status->value === 'approved' && auth()->user()?->hasRole('admin'))
                            <button type="button" wire:click="openPaymentModal" class="btn btn-block btn-success">
                                <i class="fas fa-hand-holding-usd mr-1"></i> Konfirmasi Pembayaran Gaji
                            </button>
                        @endif
                        --}}

                        @if ($period->status->value === 'paid')
                            <div class="text-center py-1">
                                <span class="badge badge-info px-3 py-2">
                                    <i class="fas fa-check-circle mr-1"></i> Telah Dibayar pada {{ $period->payment_date?->format('d/m/Y') }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Kolom Kanan: Tabel Rincian Gaji Karyawan (col-12 col-lg-9) -->
        <div class="col-12 col-lg-9 mb-4">
            <!-- Banner Karyawan Baru Belum Masuk Draf (.bd-callout) -->
            @if ($period->status->isEditable() && $this->missingEmployees->isNotEmpty() && auth()->user()?->hasRole('admin'))
                @php
                    $missingCount = $this->missingEmployees->count();
                @endphp
                <div class="bd-callout bd-callout-info mb-4" role="alert">
                    <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap: 12px;">
                        <div class="d-flex align-items-start flex-grow-1" style="min-width: 280px;">
                            <div class="bd-callout-icon mr-3 mt-0.5">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="bd-callout-title">
                                    Karyawan Baru Terdeteksi ({{ $missingCount }} Belum Masuk Draf)
                                </div>
                                <div class="bd-callout-desc">
                                    Terdapat karyawan berstatus aktif yang belum terdaftar di draf slip gaji periode ini:
                                    <ul class="mb-2 mt-1.5 pl-3 list-unstyled">
                                        @foreach ($this->missingEmployees as $emp)
                                            @php
                                                $prorate = app(\App\Services\PayrollService::class)->calculateProratedSalary($emp, $period);
                                            @endphp
                                            <li class="mb-1 d-flex align-items-center flex-wrap" style="gap: 6px;">
                                                <i class="fas fa-check text-primary" style="font-size: 11px;"></i>
                                                <strong class="text-dark">{{ $emp->full_name }}</strong>
                                                <span class="text-muted" style="font-size: 12px;">
                                                    ({{ $emp->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $emp->position?->name ?? 'Karyawan' }})
                                                </span>
                                                &mdash;
                                                @if ($prorate['is_prorated'])
                                                    <span class="badge badge-light text-primary border font-weight-600" style="font-size: 11.5px;">
                                                        Prorata ({{ $prorate['worked_days'] }} dari {{ $prorate['total_working_days'] }} hari kerja): Rp {{ number_format($prorate['basic_salary'], 0, ',', '.') }}
                                                    </span>
                                                    <small class="text-muted">(Gaji Normal: Rp {{ number_format($prorate['full_salary'], 0, ',', '.') }} &bull; Masuk: {{ $emp->join_date?->translatedFormat('d M Y') }})</small>
                                                @else
                                                    <span class="badge badge-light text-success border font-weight-600" style="font-size: 11.5px;">
                                                        Gaji Penuh: Rp {{ number_format($prorate['basic_salary'], 0, ',', '.') }}
                                                    </span>
                                                    <small class="text-muted">(Masuk: {{ $emp->join_date ? $emp->join_date->translatedFormat('d M Y') : '-' }})</small>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="text-muted mt-1" style="font-size: 11.5px;">
                                        <i class="fas fa-info-circle mr-1 text-primary"></i> Dihitung proporsional berdasarkan hari kerja (Senin–Sabtu). Klik tombol untuk membuat slip gaji otomatis.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="align-self-center mt-2 mt-sm-0">
                            <button type="button" 
                                wire:click="syncMissingEmployees" 
                                wire:loading.attr="disabled" 
                                class="btn btn-primary btn-sm px-3 py-2 font-weight-600 shadow-sm" 
                                style="border-radius: 8px; white-space: nowrap;">
                                <span wire:loading.remove wire:target="syncMissingEmployees">
                                    <i class="fas fa-user-plus mr-1.5"></i> {{ $missingCount > 1 ? 'Tambahkan Semua (' . $missingCount . ')' : 'Tambahkan ke Draf' }}
                                </span>
                                <span wire:loading wire:target="syncMissingEmployees">
                                    <i class="fas fa-spinner fa-spin mr-1.5"></i> Menambahkan...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card cash-advance-main-card mb-0">
        <!-- Row 1: Header & Controls Card (Matching Kasbon) -->
        <div class="cash-advance-top-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
                <h4 class="mb-0">Rincian Gaji - <span class="text-primary font-weight-bold">{{ $period->period_label }}</span></h4>
                @if (auth()->user()?->hasRole('direktur') && in_array($period->status->value, ['submitted', 'draft', 'rejected']))
                    <button type="button" 
                        wire:click="$dispatch('swal:confirm', {
                            title: 'Setujui Seluruh Gaji?',
                            text: 'Apakah Anda ingin menyetujui seluruh slip gaji ({{ $items->total() }} karyawan) untuk periode ini sekaligus?',
                            action: 'approve-all-items-confirmed',
                            confirmButtonColor: '#28a745'
                        })"
                        class="btn btn-sm btn-success font-weight-bold shadow-sm" style="border-radius: 8px;">
                        <i class="fas fa-check-double mr-1.5"></i> Setujui Semua
                    </button>
                @endif
            </div>

            <!-- Controls: Show Entries & Search Bar (Matching Kasbon) -->
            <div class="dt-control-wrapper">
                <div class="dt-show-entries">
                    Show
                    <select wire:model.live="perPage" class="dt-select-entries">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    Entries
                </div>

                <div class="dt-search-wrapper">
                    <span class="mr-1">Search:</span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama karyawan, no slip...">
                </div>
            </div>
        </div>

        <!-- Dynamic Bulk Action Bar for Direktur -->
        @if (auth()->user()?->hasRole('direktur') && count($selectedItems) > 0)
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap" style="background-color: #eff6ff !important; border-color: #bfdbfe !important; gap: 10px;">
                <div class="d-flex align-items-center">
                    <span class="badge badge-primary px-3 py-1.5 mr-2.5 font-weight-bold" style="font-size: 12.5px; border-radius: 20px;">
                        <i class="fas fa-check-square mr-1.5"></i> {{ count($selectedItems) }} Karyawan Dipilih
                    </span>
                    <span class="text-dark font-weight-600" style="font-size: 13px;">Persetujuan massal untuk slip yang dicentang:</span>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <button type="button" wire:click="approveSelected" class="btn btn-sm btn-success font-weight-bold shadow-sm px-3" style="border-radius: 8px;">
                        <i class="fas fa-check mr-1"></i> Setujui Terpilih (ACC)
                    </button>
                    <button type="button" wire:click="openRejectSelectedModal" class="btn btn-sm btn-outline-danger font-weight-bold px-3" style="border-radius: 8px;">
                        <i class="fas fa-times mr-1"></i> Tolak / Revisi Terpilih
                    </button>
                    <button type="button" wire:click="$set('selectedItems', [])" class="btn btn-sm btn-light border text-muted" style="border-radius: 8px;">
                        Batal
                    </button>
                </div>
            </div>
        @endif

        <!-- Row 2: Full-Width Data Table (Clean, Human-Centric) -->
        <div class="table-responsive">
            <table class="table table-clean mb-0">
                <thead class="bg-gray-100">
                    <tr>
                        @if (auth()->user()?->hasRole('direktur'))
                            <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 40px;">
                                <input type="checkbox" wire:model.live="selectAll" style="width: 16px; height: 16px; cursor: pointer;" title="Pilih Semua">
                            </th>
                        @endif
                        <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 45px;">NO</th>
                        <th class="text-nowrap !px-3 !py-2.5" style="width: 135px;">NO. SLIP</th>
                        <th class="text-nowrap !px-3 !py-2.5">KARYAWAN</th>
                        <th class="text-nowrap !px-3 !py-2.5">TANGGAL BERGABUNG</th>
                        <th class="text-nowrap !px-3 !py-2.5">LAMA KERJA</th>
                        <th class="text-right text-nowrap !px-3 !py-2.5">GAJI KOTOR</th>
                        <th class="text-right text-nowrap !px-3 !py-2.5">POTONGAN (-)</th>
                        <th class="text-right text-nowrap !px-3 !py-2.5">GAJI BERSIH</th>
                        <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 100px;">STATUS</th>
                        <th class="text-center text-nowrap !px-3 !py-2.5" style="width: {{ auth()->user()?->hasRole('direktur') ? '130px' : '105px' }};"><i class="fas fa-cog text-muted mr-1" title="Aksi"></i> AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $idx => $item)
                        <tr wire:key="item-{{ $item->id }}">
                            @if (auth()->user()?->hasRole('direktur'))
                                <td class="text-center align-middle !px-3 !py-2.5">
                                    <input type="checkbox" wire:model.live="selectedItems" value="{{ $item->id }}" style="width: 16px; height: 16px; cursor: pointer;">
                                </td>
                            @endif
                            <td class="text-center font-weight-600 text-muted align-middle !px-3 !py-2.5">
                                {{ $items->firstItem() + $idx }}
                            </td>
                            <td class="align-middle text-nowrap !px-3 !py-2.5">
                                <button type="button" wire:click="openPreviewSlip({{ $item->id }})" class="btn btn-link p-0 font-weight-600 text-primary text-decoration-none" style="font-size: 13px;" title="Lihat Slip Gaji">
                                    {{ $item->payslip_number }}
                                </button>
                            </td>
                            <td class="align-middle text-nowrap !px-3 !py-2.5">
                                <div class="d-flex align-items-center">
                                    @if ($item->employee?->photo_path)
                                        <img src="{{ $item->employee->photo_url }}" alt="{{ $item->employee->full_name }}" class="rounded-circle mr-2.5 flex-shrink-0" style="width: 36px; height: 36px; object-fit: cover; border: 1px solid #e2e8f0;">
                                    @else
                                        <div class="rounded-circle mr-2.5 flex-shrink-0 d-flex align-items-center justify-content-center text-white font-weight-bold" style="width: 36px; height: 36px; background: linear-gradient(135deg, #0b52aa 0%, #2563eb 100%); font-size: 12.5px;">
                                            {{ strtoupper(substr($item->employee?->full_name ?? 'NA', 0, 2)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-weight-600 text-dark leading-tight">
                                            {{ $item->employee?->full_name ?? 'Karyawan Tidak Diketahui' }}
                                            @if ($item->employee?->trashed())
                                                <span class="badge badge-light border text-danger ml-1 font-weight-600" style="font-size: 10px; padding: 2px 6px;" title="Karyawan telah dinonaktifkan/dihapus dari master">Dihapus</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-muted leading-tight mt-0.5">
                                            {{ $item->employee?->position?->name ?? 'Tanpa Jabatan' }} &bull; {{ $item->employee?->department?->name ?? 'Tanpa Departemen' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle text-nowrap !px-3 !py-2.5">
                                <span class="font-weight-500 text-dark">
                                    {{ $item->employee?->join_date ? $item->employee->join_date->format('d M Y') : '-' }}
                                </span>
                            </td>
                            <td class="align-middle text-nowrap !px-3 !py-2.5">
                                <span class="badge badge-light border text-muted font-weight-600 px-2.5 py-1" style="font-size: 11px; border-radius: 6px;">
                                    {{ $item->employee?->tenure_label ?? '-' }}
                                </span>
                            </td>
                            <td class="text-right align-middle text-nowrap font-weight-500 text-dark !px-3 !py-2.5">
                                <div>{{ $item->formatted_gross_salary }}</div>
                                @if ((float) $item->total_allowances > 0)
                                    <div class="text-[10.5px] text-success leading-tight mt-0.5" title="Termasuk tunjangan {{ $item->formatted_total_allowances }}">
                                        +{{ $item->formatted_total_allowances }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-right align-middle text-nowrap font-weight-500 {{ (float) $item->total_deductions > 0 ? 'text-danger' : 'text-muted' }} !px-3 !py-2.5">
                                {{ (float) $item->total_deductions > 0 ? '-' . $item->formatted_total_deductions : '-' }}
                            </td>
                            <td class="text-right align-middle text-nowrap font-weight-bold text-dark !px-3 !py-2.5" style="font-size: 13.5px;">
                                {{ $item->formatted_net_salary }}
                            </td>
                            <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                @if ($item->isRejected())
                                    <span class="badge badge-danger px-2.5 py-1" style="font-size: 11px; font-weight: 600; border-radius: 12px;" title="Catatan: {{ $item->rejection_reason }}">
                                        Ditolak
                                    </span>
                                @elseif (! $item->isApproved())
                                    <span class="badge badge-warning text-dark px-2.5 py-1" style="font-size: 11px; font-weight: 600; border-radius: 12px;">
                                        Menunggu
                                    </span>
                                @elseif ($item->isPaid())
                                    <span class="badge badge-info px-2.5 py-1" style="font-size: 11px; font-weight: 600; border-radius: 12px;" title="Dibayar pada {{ $item->paid_at?->format('d M Y, H:i') }} &bull; Dikonfirmasi oleh {{ $item->payer?->name ?? 'Admin' }}">
                                        Dibayar
                                    </span>
                                @else
                                    <span class="badge badge-success px-2.5 py-1" style="font-size: 11px; font-weight: 600; border-radius: 12px;" title="Disetujui oleh {{ $item->approver?->name ?? 'Direktur' }}">
                                        Disetujui
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                <div class="d-inline-flex align-items-center" style="gap: 6px;">
                                    <!-- Tombol Kelola Komponen Gaji -->
                                    <a href="{{ route('payrolls.items.edit', [$period, $item]) }}" class="btn btn-primary btn-action" data-toggle="tooltip" title="Kelola Komponen Gaji">
                                        <i class="fas fa-sliders-h"></i>
                                    </a>

                                    <!-- Tombol Hapus dari Draf (hanya jika periode draf/revisi & bukan direktur) -->
                                    @if ($period->status->isEditable() && ! auth()->user()?->hasRole('direktur'))
                                        <button type="button" wire:click="confirmRemoveItem({{ $item->id }})" class="btn btn-danger btn-action" data-toggle="tooltip" title="Hapus dari Draf">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @endif

                                    <!-- Tombol Bayar / Detail Bayar untuk HRD / Admin -->
                                    @if (! auth()->user()?->hasRole('direktur'))
                                        @if ($item->isApproved() && ! $item->isPaid())
                                            <button type="button" wire:click="openPayItemModal({{ $item->id }})" class="btn btn-success btn-action" data-toggle="tooltip" title="Bayar Gaji Karyawan">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                        @elseif ($item->isPaid())
                                            <button type="button" wire:click="openPaymentDetailModal({{ $item->id }})" class="btn btn-info btn-action" data-toggle="tooltip" title="Lihat Bukti & Detail Pembayaran">
                                                <i class="fas fa-receipt"></i>
                                            </button>
                                        @endif
                                    @endif

                                    <!-- Persetujuan Individual Per Karyawan untuk Direktur -->
                                    @if (auth()->user()?->hasRole('direktur'))
                                        @if (! $item->isApproved())
                                            <button type="button" wire:click="approveItem({{ $item->id }})" class="btn btn-success btn-action" data-toggle="tooltip" title="Setujui (ACC)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" wire:click="openRejectItemModal({{ $item->id }})" class="btn btn-danger btn-action" data-toggle="tooltip" title="Tolak">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @elseif ($item->isPaid())
                                            <button type="button" wire:click="openPaymentDetailModal({{ $item->id }})" class="btn btn-info btn-action" data-toggle="tooltip" title="Lihat Bukti & Detail Pembayaran">
                                                <i class="fas fa-receipt"></i>
                                            </button>
                                        @else
                                            <span class="badge badge-light border text-success font-weight-600" style="font-size: 11px; padding: 6px 8px; border-radius: 6px;" title="Disetujui oleh {{ $item->approver?->name ?? 'Direktur' }}">
                                                <i class="fas fa-check-double text-success"></i>
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->hasRole('direktur') ? 11 : 10 }}" class="text-center py-5 text-muted">
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon bg-light text-muted mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <h5 class="font-weight-bold text-dark mb-1">Tidak Ada Data Karyawan</h5>
                                    <p class="text-muted small mb-0">
                                        @if ($search)
                                            Tidak ada karyawan yang cocok dengan pencarian "{{ $search }}".
                                        @else
                                            Tidak ada data karyawan dalam periode penggajian ini.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer: Info & Pagination (Matching Kasbon) -->
        @if ($items && $items->total() > 0)
            <div class="dt-footer-wrapper">
                <div class="dt-info">
                    Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries
                </div>
                <div class="dt-pagination">
                    {{ $items->links('livewire.custom-pagination') }}
                </div>
            </div>
        @endif
            </div>
        </div>
    </div>

    <!-- ================= 4. MODAL PREVIEW SLIP GAJI (FLUTTER FINTECH DIGITAL RECEIPT) ================= -->
    <div wire:ignore.self class="modal fade" id="previewSlipModal" role="dialog" aria-labelledby="previewSlipModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 860px;">
            <div class="modal-content shadow-lg" style="border-radius: 18px; border: 1px solid #cbd5e1; overflow: hidden; background: #f8fafc;">
                <!-- Modal Top Header -->
                <div class="modal-header px-4 py-3 bg-white d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #edf2f7;">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center mr-2.5 rounded-circle text-white shadow-sm" style="width: 34px; height: 34px; background: linear-gradient(135deg, #0b52aa 0%, #2563eb 100%);">
                            <i class="fas fa-file-invoice" style="font-size: 14px;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-weight-bold text-dark mb-0" id="previewSlipModalLabel" style="font-size: 16px;">
                                Slip Gaji Karyawan
                            </h5>
                            <div class="text-muted text-xs">Pratinjau resmi rincian penghasilan dan pemotongan gaji</div>
                        </div>
                    </div>
                    <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close" style="opacity: 0.7; font-size: 24px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Modal Body: Digital Ticket Receipt -->
                <div class="modal-body p-4">
                    @if ($previewItem)
                        <!-- Elevated White Slip Sheet -->
                        <div class="slip-ticket-sheet bg-white p-4 shadow-sm" style="border-radius: 14px; border: 1px solid #e2e8f0;">
                            <!-- 1. Kop Surat Perusahaan -->
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center pb-3 mb-3" style="border-bottom: 2px dashed #e2e8f0;">
                                <div class="d-flex align-items-center mb-3 mb-sm-0">
                                    <div class="rounded-lg mr-3 p-1 d-flex align-items-center justify-content-center bg-white shrink-0" style="width: 58px; height: 58px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                                        <img src="{{ $company->logo_url }}" alt="Logo" style="max-height: 48px; max-width: 50px; object-fit: contain;">
                                    </div>
                                    <div>
                                        <h5 class="font-weight-bold text-dark mb-0" style="font-size: 16px; letter-spacing: -0.2px;">{{ $company->name }}</h5>
                                        <div class="text-muted text-xs mt-0.5"><i class="fas fa-map-marker-alt text-primary mr-1"></i>{{ $company->address ?? 'Indonesia' }}</div>
                                        <div class="text-muted text-xs"><i class="fas fa-phone-alt text-primary mr-1"></i>{{ $company->phone ?? '-' }} &bull; <i class="fas fa-envelope text-primary mr-1"></i>{{ $company->email ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="text-left text-sm-right w-100 w-sm-auto">
                                    <span class="badge px-2.5 py-1 mb-1 font-weight-700" style="background: #e8f0fe; color: #0b52aa; border: 1px solid #c7dcfc; font-size: 11px; border-radius: 20px; letter-spacing: 0.5px;">
                                        SLIP GAJI RESMI
                                    </span>
                                    <div class="font-weight-700 text-dark" style="font-size: 14.5px; letter-spacing: 0.2px;">{{ $previewItem->payslip_number }}</div>
                                    <div class="text-muted text-xs">{{ $previewItem->payrollPeriod->period_label }}</div>
                                </div>
                            </div>

                            <!-- 2. Data Karyawan (Flutter FinTech 4-Tile Grid) -->
                            <div class="row mb-3">
                                <!-- Tile 1: Identitas Karyawan -->
                                <div class="col-12 col-md-6 mb-2">
                                    <div class="emp-tile-group m-0 h-100" style="border-radius: 10px; border: 1px solid #edf2f7; background-color: #f8fafc;">
                                        <div class="emp-tile-row p-2.5 px-3">
                                            <div class="emp-tile-left" style="font-size: 12px;">
                                                <div class="emp-tile-icon" style="background-color: #e8f0fe; color: #0b52aa;"><i class="fas fa-user"></i></div>
                                                <span>Nama Karyawan</span>
                                            </div>
                                            <div class="emp-tile-right font-weight-700 text-dark" style="font-size: 12.5px;">
                                                {{ $previewItem->employee?->full_name ?? '-' }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row p-2.5 px-3">
                                            <div class="emp-tile-left" style="font-size: 12px;">
                                                <div class="emp-tile-icon" style="background-color: #f1f5f9; color: #475569;"><i class="fas fa-id-card"></i></div>
                                                <span>NIK / Jabatan</span>
                                            </div>
                                            <div class="emp-tile-right text-dark" style="font-size: 12px;">
                                                {{ $previewItem->employee?->detail?->employee_code ?? '-' }} &bull; {{ $previewItem->employee?->position?->name ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tile 2: Info Rekening Bank & Status -->
                                <div class="col-12 col-md-6 mb-2">
                                    <div class="emp-tile-group m-0 h-100" style="border-radius: 10px; border: 1px solid #edf2f7; background-color: #f8fafc;">
                                        <div class="emp-tile-row p-2.5 px-3">
                                            <div class="emp-tile-left" style="font-size: 12px;">
                                                <div class="emp-tile-icon" style="background-color: #e8f0fe; color: #0b52aa;"><i class="fas fa-university"></i></div>
                                                <span>Rekening Bank</span>
                                            </div>
                                            <div class="emp-tile-right font-weight-600 text-dark" style="font-size: 12.5px;">
                                                {{ $previewItem->bank_name ?? 'Bank' }} {{ $previewItem->bank_account_number ?? '-' }}
                                            </div>
                                        </div>
                                        <div class="emp-tile-row p-2.5 px-3">
                                            <div class="emp-tile-left" style="font-size: 12px;">
                                                <div class="emp-tile-icon" style="background-color: #f0fdf4; color: #16a34a;"><i class="fas fa-check-circle"></i></div>
                                                <span>Status Bayar</span>
                                            </div>
                                            <div class="emp-tile-right">
                                                <span class="{{ $previewItem->payment_status_badge_class }}" style="font-size: 11px; font-weight: 500; padding: 2px 7px; border-radius: 10px;">
                                                    {{ $previewItem->payment_status_label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tile 3: Status Kerja & Pajak PPh 21 -->
                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                    <div class="emp-tile-group m-0" style="border-radius: 10px; border: 1px solid #edf2f7; background-color: #f8fafc;">
                                        <div class="emp-tile-row p-2.5 px-3">
                                            <div class="emp-tile-left" style="font-size: 12px;">
                                                <div class="emp-tile-icon" style="background-color: #f1f5f9; color: #475569;"><i class="fas fa-briefcase"></i></div>
                                                <span>Status Kerja</span>
                                            </div>
                                            <div class="emp-tile-right text-dark font-weight-600" style="font-size: 12px;">
                                                {{ $previewItem->employee?->employment_status_label ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="emp-tile-group m-0" style="border-radius: 10px; border: 1px solid #edf2f7; background-color: #f8fafc;">
                                        <div class="emp-tile-row p-2.5 px-3">
                                            <div class="emp-tile-left" style="font-size: 12px;">
                                                <div class="emp-tile-icon" style="background-color: #e8f0fe; color: #0b52aa;"><i class="fas fa-percentage"></i></div>
                                                <span>Skema Pajak</span>
                                            </div>
                                            <div class="emp-tile-right text-right" style="font-size: 12px;">
                                                @php
                                                    $previewPtkp = ($period->status->isEditable() ? $previewItem->employee?->detail?->ptkp_status : null) ?? $previewItem->ptkp_status;
                                                    $previewPtkpVal = $previewPtkp instanceof \App\Enums\PtkpStatus ? $previewPtkp->value : (string) ($previewPtkp ?? 'TK/0');
                                                    $previewTerCat = ($period->status->isEditable() ? ($previewItem->employee?->detail?->ter_category ?? $previewItem->ter_category) : null) ?? $previewItem->ter_category ?? ($previewPtkp instanceof \App\Enums\PtkpStatus ? $previewPtkp->terCategory() : 'A');
                                                    $previewTaxMethod = ($period->status->isEditable() ? $previewItem->employee?->detail?->tax_method : null) ?? $previewItem->tax_method ?? \App\Enums\TaxMethod::GROSS;
                                                @endphp
                                                <span class="badge badge-light border text-primary" style="font-size: 11px; font-weight: 600;">
                                                    {{ $previewTaxMethod instanceof \App\Enums\TaxMethod ? $previewTaxMethod->label() : 'Gross' }} &bull; {{ $previewPtkpVal }} (TER {{ $previewTerCat }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Rincian Penghasilan & Pemotongan (Dual Modern Flutter Cards) -->
                            <div class="row mb-3">
                                <!-- Kolom Penghasilan -->
                                <div class="col-12 col-md-6 mb-3 mb-md-0">
                                    <div class="card m-0 shadow-none h-100" style="border-radius: 12px; border: 1px solid #dcfce7; overflow: hidden; background: #ffffff;">
                                        <div class="p-2.5 px-3 d-flex align-items-center justify-content-between" style="background-color: #f0fdf4; border-bottom: 1px solid #dcfce7;">
                                            <span class="font-weight-700 text-success text-xs text-uppercase" style="letter-spacing: 0.4px;">
                                                <i class="fas fa-plus-circle mr-1"></i> Penghasilan (Earnings)
                                            </span>
                                            <span class="badge px-2 py-0.5 text-xs font-weight-600" style="background-color: #dcfce7; color: #166534; font-size: 10.5px;">Pendapatan</span>
                                        </div>
                                        <div class="p-0">
                                            <table class="table table-sm table-clean mb-0">
                                                <tbody>
                                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                                        <td class="!px-3 !py-2.5 align-middle text-dark font-weight-500" style="font-size: 12.5px;">
                                                            <div>Gaji Pokok</div>
                                                            @php
                                                                $prevFullSalary = (float) ($previewItem->employee?->basic_salary ?? 0);
                                                                $prevIsProrated = $prevFullSalary > 0 && (float) $previewItem->basic_salary < $prevFullSalary;
                                                                $prevNoteText = $previewItem->notes;
                                                                if ($prevIsProrated) {
                                                                    $fmtPBasic = 'Rp ' . number_format((float) $previewItem->basic_salary, 0, ',', '.');
                                                                    $fmtPFull = 'Rp ' . number_format($prevFullSalary, 0, ',', '.');
                                                                    if (empty($prevNoteText)) {
                                                                        $prevNoteText = "Gaji prorata {$fmtPBasic} dari {$fmtPFull}";
                                                                    } elseif (! str_contains($prevNoteText, 'dari Rp')) {
                                                                        $prevNoteText = preg_replace('/^Gaji prorata\s*/i', "Gaji prorata {$fmtPBasic} dari {$fmtPFull} &bull; ", $prevNoteText);
                                                                    }
                                                                }
                                                            @endphp
                                                            @if ($prevNoteText)
                                                                <div class="text-muted font-normal" style="font-size: 11px;">{!! $prevNoteText !!}</div>
                                                            @endif
                                                        </td>
                                                        <td class="!px-3 !py-2.5 align-middle text-right font-weight-600 text-dark" style="font-size: 12.5px;">
                                                            {{ $previewItem->formatted_basic_salary }}
                                                        </td>
                                                    </tr>
                                                    @foreach ($previewItem->allowanceLines->filter(fn ($l) => (float) $l->amount > 0) as $line)
                                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                                            <td class="!px-3 !py-2 align-middle text-dark" style="font-size: 12.5px;">
                                                                {{ $line->name }}
                                                                @if ($line->category->value === 'tax')
                                                                    <span class="badge badge-light border text-primary ml-1 text-xs" style="font-size: 9.5px; font-weight: 500;">Gross-Up</span>
                                                                @endif
                                                            </td>
                                                            <td class="!px-3 !py-2 align-middle text-right font-weight-600 text-success" style="font-size: 12.5px;">+{{ $line->formatted_amount }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-auto p-2.5 px-3 d-flex align-items-center justify-content-between" style="background-color: #f8fafc; border-top: 1px solid #edf2f7;">
                                            <span class="text-muted font-weight-600 text-xs">Total Penghasilan</span>
                                            <span class="text-success font-weight-700" style="font-size: 13.5px;">
                                                +Rp {{ number_format((float) $previewItem->basic_salary + (float) $previewItem->total_allowances, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom Pemotongan -->
                                <div class="col-12 col-md-6">
                                    <div class="card m-0 shadow-none h-100" style="border-radius: 12px; border: 1px solid #fee2e2; overflow: hidden; background: #ffffff;">
                                        <div class="p-2.5 px-3 d-flex align-items-center justify-content-between" style="background-color: #fef2f2; border-bottom: 1px solid #fee2e2;">
                                            <span class="font-weight-700 text-danger text-xs text-uppercase" style="letter-spacing: 0.4px;">
                                                <i class="fas fa-minus-circle mr-1"></i> Pemotongan (Deductions)
                                            </span>
                                            <span class="badge px-2 py-0.5 text-xs font-weight-600" style="background-color: #fee2e2; color: #991b1b; font-size: 10.5px;">Potongan</span>
                                        </div>
                                        <div class="p-0">
                                            <table class="table table-sm table-clean mb-0">
                                                <tbody>
                                                    @forelse ($previewItem->deductionLines->filter(fn ($l) => (float) $l->amount > 0) as $line)
                                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                                            <td class="!px-3 !py-2 align-middle text-dark" style="font-size: 12.5px;">
                                                                {{ $line->name }}
                                                                @if ($line->category->value === 'cash_advance')
                                                                    <span class="badge badge-light border text-warning ml-1 text-xs" style="font-size: 9.5px; font-weight: 500;">Kasbon</span>
                                                                @elseif ($line->category->value === 'tax')
                                                                    <span class="badge badge-light border text-danger ml-1 text-xs" style="font-size: 9.5px; font-weight: 500;">PPh 21</span>
                                                                @elseif ($line->category->value === 'bpjs')
                                                                    <span class="badge badge-light border text-info ml-1 text-xs" style="font-size: 9.5px; font-weight: 500;">BPJS</span>
                                                                @endif
                                                            </td>
                                                            <td class="!px-3 !py-2 align-middle text-right font-weight-600 text-danger" style="font-size: 12.5px;">-{{ $line->formatted_amount }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="2" class="text-muted text-center py-3 text-xs">Tidak ada potongan gaji</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-auto p-2.5 px-3 d-flex align-items-center justify-content-between" style="background-color: #f8fafc; border-top: 1px solid #edf2f7;">
                                            <span class="text-muted font-weight-600 text-xs">Total Pemotongan</span>
                                            <span class="text-danger font-weight-700" style="font-size: 13.5px;">
                                                -{{ $previewItem->formatted_total_deductions }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Take Home Pay Hero Card (Flutter FinTech Wallet Card) -->
                            <div class="take-home-pay-card p-3 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 shadow-sm" style="background: linear-gradient(135deg, #0b52aa 0%, #1e40af 100%); border-radius: 14px; color: #ffffff;">
                                <div class="d-flex align-items-center mb-2 mb-sm-0">
                                    <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.18);">
                                        <i class="fas fa-wallet" style="font-size: 18px;"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase font-weight-700 text-xs" style="letter-spacing: 0.5px; opacity: 0.85;">Penerimaan Bersih (Take Home Pay)</div>
                                        <div class="text-xs" style="opacity: 0.9;">Ditransfer langsung ke rekening {{ $previewItem->bank_name ?? 'terdaftar' }}</div>
                                    </div>
                                </div>
                                <div class="font-weight-800" style="font-size: 22px; letter-spacing: -0.3px; color: #ffffff;">
                                    {{ $previewItem->formatted_net_salary }}
                                </div>
                            </div>

                            <!-- 5. Authenticity Footer Watermark -->
                            <div class="text-center text-muted" style="font-size: 11px; opacity: 0.85;">
                                <i class="fas fa-shield-alt text-success mr-1"></i> Dokumen ini diterbitkan secara resmi melalui sistem <strong>Larashiz HRM</strong> dan sah tanpa tanda tangan basah.
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Action Footer -->
                <div class="modal-footer px-4 py-3 bg-white d-flex justify-content-between align-items-center" style="border-top: 1px solid #edf2f7;">
                    <div>
                        @if ($previewItem)
                            <a href="{{ $this->getWhatsappLink($previewItem) }}" target="_blank" class="btn btn-success px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 8px;">
                                <i class="fab fa-whatsapp mr-1.5"></i> Kirim Slip via WA
                            </a>
                            <a href="{{ route('payrolls.items.print', [$period, $previewItem]) }}" target="_blank" class="btn btn-primary px-3 py-2 font-weight-bold shadow-sm" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px;">
                                <i class="fas fa-print mr-1.5"></i> Cetak / Simpan PDF
                            </a>
                        @endif
                    </div>
                    <button type="button" class="btn btn-secondary px-3 py-2 font-weight-500" data-dismiss="modal" style="border-radius: 8px;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= 5. MODAL TOLAK PENGGAJIAN (DIREKTUR) ================= -->
    <div wire:ignore.self class="modal fade" id="rejectModal" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg" style="border-radius: 16px; border: 1px solid #cbd5e1; overflow: hidden;">
                <div class="modal-header px-4 py-3 bg-white d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #edf2f7;">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center mr-2.5 rounded-circle text-danger bg-red-50" style="width: 32px; height: 32px; background-color: #fef2f2;">
                            <i class="fas fa-times-circle" style="font-size: 16px;"></i>
                        </div>
                        <h5 class="modal-title font-weight-bold text-danger mb-0" id="rejectModalLabel" style="font-size: 15px;">
                            Tolak Penggajian & Minta Revisi
                        </h5>
                    </div>
                    <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form wire:submit="rejectPeriod">
                    <div class="modal-body p-4 bg-white">
                        <div class="form-group mb-0">
                            <label class="font-weight-600 text-dark" style="font-size: 13px;">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea wire:model="rejectionReason" class="form-control @error('rejectionReason') is-invalid @enderror" rows="4" placeholder="Tuliskan catatan detail perbaikan untuk Admin/HRD..." style="border-radius: 8px; font-size: 13px;"></textarea>
                            @error('rejectionReason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted mt-2" style="font-size: 11.5px;">
                                <i class="fas fa-info-circle mr-1 text-primary"></i> Status periode akan dikembalikan ke <strong>Draf</strong> sehingga Admin dapat memperbaiki komponen gaji sesuai arahan Anda.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3 bg-light d-flex justify-content-end" style="border-top: 1px solid #edf2f7; gap: 8px;">
                        <button type="button" class="btn btn-secondary px-3 py-2 font-weight-500" data-dismiss="modal" style="border-radius: 8px;">Batal</button>
                        <button type="submit" class="btn btn-danger px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 8px;">
                            <i class="fas fa-times mr-1"></i> Tolak Penggajian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= 6. MODAL KONFIRMASI PEMBAYARAN GAJI ================= -->
    <div wire:ignore.self class="modal fade" id="paymentModal" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg" style="border-radius: 16px; border: 1px solid #cbd5e1; overflow: hidden;">
                <div class="modal-header px-4 py-3 bg-white d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #edf2f7;">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center mr-2.5 rounded-circle text-success bg-green-50" style="width: 32px; height: 32px; background-color: #f0fdf4;">
                            <i class="fas fa-hand-holding-usd" style="font-size: 16px;"></i>
                        </div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0" id="paymentModalLabel" style="font-size: 15px;">
                            Konfirmasi Pembayaran Gaji
                        </h5>
                    </div>
                    <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form wire:submit="processPayment">
                    <div class="modal-body p-4 bg-white">
                        <div class="alert alert-light border small text-muted mb-3" style="border-radius: 8px; background-color: #f8fafc;">
                            Total beban gaji bersih yang akan dibayarkan: <strong class="text-dark">{{ $period->formatted_total_net_salary }}</strong> untuk <strong>{{ $period->employee_count }} karyawan</strong>.
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="font-weight-600 text-dark" style="font-size: 13px;">Tanggal Realisasi Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" wire:model="paymentDate" class="form-control @error('paymentDate') is-invalid @enderror" style="border-radius: 8px; font-size: 13px;">
                                @error('paymentDate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label class="font-weight-600 text-dark" style="font-size: 13px;">Dikonfirmasi Oleh</label>
                                <input type="text" class="form-control" value="{{ auth()->user()?->name ?? '-' }}" readonly style="border-radius: 8px; font-size: 13px;">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-600 text-dark" style="font-size: 13px;">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select wire:model="paymentMethod" class="form-control @error('paymentMethod') is-invalid @enderror" style="border-radius: 8px; font-size: 13px;">
                                <option value="bank_transfer">Transfer Bank (Payroll Corporate)</option>
                                <option value="cash">Tunai / Cash</option>
                            </select>
                            @error('paymentMethod')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-600 text-dark" style="font-size: 13px;">Catatan Pembayaran (Opsional)</label>
                            <textarea wire:model="paymentNotes" class="form-control" rows="2" placeholder="Nomor referensi batch transfer bank atau keterangan lainnya..." style="border-radius: 8px; font-size: 13px;"></textarea>
                        </div>

                        <div class="alert alert-warning small mb-0" style="border-radius: 8px; font-size: 12px;">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <strong>PENTING:</strong> Menekan tombol konfirmasi akan mengubah status menjadi <strong>Dibayar</strong> dan otomatis mencatat pelunasan cicilan kasbon karyawan yang ada di periode ini.
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3 bg-light d-flex justify-content-end" style="border-top: 1px solid #edf2f7; gap: 8px;">
                        <button type="button" class="btn btn-secondary px-3 py-2 font-weight-500" data-dismiss="modal" style="border-radius: 8px;">Batal</button>
                        <button type="submit" class="btn btn-success px-3 py-2 font-weight-bold shadow-sm" wire:loading.attr="disabled" style="border-radius: 8px;">
                            <span wire:loading.remove wire:target="processPayment">
                                <i class="fas fa-check-circle mr-1.5"></i> Konfirmasi Telah Dibayar
                            </span>
                            <span wire:loading wire:target="processPayment">
                                <i class="fas fa-spinner fa-spin mr-1.5"></i> Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= 7. MODAL TOLAK / REVISI SLIP ITEM (DIREKTUR) ================= -->
    <div wire:ignore.self class="modal fade" id="rejectItemModal" role="dialog" aria-labelledby="rejectItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg" style="border-radius: 16px; border: 1px solid #cbd5e1; overflow: hidden;">
                <div class="modal-header px-4 py-3 bg-white d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #edf2f7;">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center mr-2.5 rounded-circle text-danger bg-red-50" style="width: 32px; height: 32px; background-color: #fef2f2;">
                            <i class="fas fa-times-circle" style="font-size: 16px;"></i>
                        </div>
                        <h5 class="modal-title font-weight-bold text-danger mb-0" id="rejectItemModalLabel" style="font-size: 15px;">
                            {{ $isBulkReject ? 'Tolak / Revisi Slip Karyawan Terpilih' : 'Tolak / Revisi Slip Karyawan' }}
                        </h5>
                    </div>
                    <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form wire:submit="confirmRejectItem">
                    <div class="modal-body p-4 bg-white">
                        <div class="form-group mb-0">
                            <label class="font-weight-600 text-dark" style="font-size: 13px;">Catatan Revisi / Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea wire:model="itemRejectionReason" class="form-control @error('itemRejectionReason') is-invalid @enderror" rows="3" placeholder="Tuliskan catatan perbaikan untuk Admin/HRD..." style="border-radius: 8px; font-size: 13px;"></textarea>
                            @error('itemRejectionReason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted mt-2" style="font-size: 11.5px;">
                                <i class="fas fa-info-circle mr-1 text-primary"></i> Catatan ini akan dicatat pada slip karyawan terkait agar Admin/HRD dapat memperbaikinya.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3 bg-light d-flex justify-content-end" style="border-top: 1px solid #edf2f7; gap: 8px;">
                        <button type="button" class="btn btn-secondary px-3 py-2 font-weight-500" data-dismiss="modal" style="border-radius: 8px;">Batal</button>
                        <button type="submit" class="btn btn-danger px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 8px;">
                            <i class="fas fa-times mr-1"></i> Kirim Revisi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= 8. MODAL PEMBAYARAN GAJI INDIVIDUAL KARYAWAN (HRD) ================= -->
    <div wire:ignore.self class="modal fade" id="payItemModal" role="dialog" aria-labelledby="payItemModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="payItemModalLabel">
                        {{ $isEditingPayment ? 'Edit Pembayaran Gaji Karyawan' : 'Form Pembayaran Gaji Karyawan' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form wire:submit="processItemPayment">
                    <div class="modal-body py-3 px-4">
                        @if ($payingItem)
                            <!-- Card Info Karyawan & Gaji Bersih (Bawaan Template) -->
                            <div class="card border mb-3">
                                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small">Penerima Gaji:</div>
                                        <div class="font-weight-bold text-dark" style="font-size: 15px;">{{ $payingItem->employee?->full_name ?? '-' }}</div>
                                        <div class="text-muted small">{{ $payingItem->payslip_number }} &bull; {{ $payingItem->employee?->position?->name ?? 'Tanpa Jabatan' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-muted small">Gaji Bersih:</div>
                                        <div class="font-weight-bold text-success" style="font-size: 18px;">{{ $payingItem->formatted_net_salary }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Tanggal Pembayaran & Dikonfirmasi Oleh -->
                        <div class="form-row">
                            <div class="form-group col-md-6 mb-3">
                                <label for="itemPaymentDate">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" id="itemPaymentDate" wire:model="itemPaymentDate" class="form-control @error('itemPaymentDate') is-invalid @enderror">
                                @error('itemPaymentDate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label for="itemConfirmedBy">Dikonfirmasi Oleh</label>
                                <input type="text" id="itemConfirmedBy" class="form-control" value="{{ auth()->user()?->name ?? '-' }}" readonly>
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="form-group mb-3">
                            <label for="itemPaymentMethod">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select id="itemPaymentMethod" wire:model.live="itemPaymentMethod" class="form-control @error('itemPaymentMethod') is-invalid @enderror">
                                <option value="bank_transfer">Transfer Bank / E-Wallet</option>
                                <option value="cash">Tunai / Cash</option>
                            </select>
                            @error('itemPaymentMethod')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if ($itemPaymentMethod === 'cash')
                                <small class="form-text text-muted">
                                    Pembayaran diserahkan langsung secara tunai (cash) dalam amplop kepada karyawan.
                                </small>
                            @endif
                        </div>

                        <!-- Input Spesifik Sesuai Metode -->
                        @if ($itemPaymentMethod === 'bank_transfer')
                            <div class="form-row">
                                <div class="form-group col-md-6 mb-3">
                                    <label for="itemBankName">Nama Bank / E-Wallet <span class="text-danger">*</span></label>
                                    <input type="text" id="itemBankName" wire:model="itemBankName" placeholder="Contoh: BCA, Mandiri, GoPay, DANA" class="form-control @error('itemBankName') is-invalid @enderror">
                                    @error('itemBankName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6 mb-3">
                                    <label for="itemBankAccountNumber">Nomor Rekening / No. HP <span class="text-danger">*</span></label>
                                    <input type="text" id="itemBankAccountNumber" wire:model="itemBankAccountNumber" placeholder="No. Rekening atau No. HP E-Wallet" class="form-control @error('itemBankAccountNumber') is-invalid @enderror">
                                    @error('itemBankAccountNumber')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="itemBankAccountName">Atas Nama Rekening / Akun</label>
                                <input type="text" id="itemBankAccountName" wire:model="itemBankAccountName" placeholder="Nama Pemilik Rekening / Akun" class="form-control">
                            </div>
                        @endif

                        <!-- Upload Bukti Pembayaran (Custom File Bootstrap) -->
                        <div class="form-group mb-3">
                            <label for="item_payment_proof">Bukti Pembayaran <small class="text-muted">(JPG, PNG, PDF maks. 3MB - Opsional)</small></label>
                            <div class="custom-file">
                                <input type="file" id="item_payment_proof" wire:model="itemPaymentProof" class="custom-file-input @error('itemPaymentProof') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                                <label class="custom-file-label text-truncate" for="item_payment_proof">
                                    @if ($itemPaymentProof && is_object($itemPaymentProof))
                                        <i class="fas fa-check-circle text-success mr-1"></i> {{ $itemPaymentProof->getClientOriginalName() }}
                                    @else
                                        <i class="fas fa-file-upload text-muted mr-1"></i> Pilih file bukti transfer / kwitansi...
                                    @endif
                                </label>
                            </div>
                            <div wire:loading wire:target="itemPaymentProof" class="text-primary small mt-1">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah file bukti...
                            </div>
                            @if ($itemPaymentProof && is_object($itemPaymentProof))
                                <div class="d-flex align-items-center justify-content-between mt-1 px-1">
                                    <span class="text-muted small">
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
                            <label for="itemPaymentNotes">Catatan / Keterangan <small class="text-muted">(Opsional)</small></label>
                            <textarea id="itemPaymentNotes" wire:model="itemPaymentNotes" rows="2" placeholder="Nomor referensi transfer atau catatan..." class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-whitesmoke br">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="processItemPayment">
                            <span wire:loading wire:target="processItemPayment"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                            <span wire:loading.remove wire:target="processItemPayment"><i class="fas fa-check mr-1"></i> {{ $isEditingPayment ? 'Simpan Perubahan' : 'Konfirmasi Pembayaran' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= 9. MODAL DETAIL & BUKTI PEMBAYARAN GAJI ================= -->
    <div wire:ignore.self class="modal fade" id="paymentDetailModal" role="dialog" aria-labelledby="paymentDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentDetailModalLabel">
                        Detail Pembayaran Gaji
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-3 px-4">
                    @if ($paidDetailItem)
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
                                {{ $paidDetailItem->formatted_net_salary }}
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
                                    {{ $paidDetailItem->employee?->full_name ?? '-' }}
                                </div>
                            </div>
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-receipt"></i></div>
                                    <span>No. Slip Gaji</span>
                                </div>
                                <div class="emp-tile-right text-dark" style="letter-spacing: 0.3px;">
                                    {{ $paidDetailItem->payslip_number }}
                                </div>
                            </div>
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-calendar-check"></i></div>
                                    <span>Waktu Pembayaran</span>
                                </div>
                                <div class="emp-tile-right text-dark">
                                    {{ $paidDetailItem->paid_at ? $paidDetailItem->paid_at->format('d M Y, H:i') : '-' }}
                                </div>
                            </div>
                            <div class="emp-tile-row">
                                <div class="emp-tile-left">
                                    <div class="emp-tile-icon"><i class="fas fa-wallet"></i></div>
                                    <span>Metode Pembayaran</span>
                                </div>
                                <div class="emp-tile-right text-dark">
                                    {{ $paidDetailItem->payment_method === 'cash' ? 'Tunai (Cash)' : $paidDetailItem->payment_method_label }}
                                </div>
                            </div>
                            @if ($paidDetailItem->bank_name || $paidDetailItem->bank_account_number)
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-university"></i></div>
                                        <span>Nama Bank / E-Wallet</span>
                                    </div>
                                    <div class="emp-tile-right text-dark">
                                        {{ $paidDetailItem->bank_name ?: '-' }}
                                    </div>
                                </div>
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-credit-card"></i></div>
                                        <span>Nomor Rekening / No. HP</span>
                                    </div>
                                    <div class="emp-tile-right text-dark" style="letter-spacing: 0.4px;">
                                        {{ $paidDetailItem->bank_account_number ?: '-' }}
                                    </div>
                                </div>
                                @if ($paidDetailItem->bank_account_name)
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-user-check"></i></div>
                                            <span>Atas Nama</span>
                                        </div>
                                        <div class="emp-tile-right text-dark">
                                            {{ $paidDetailItem->bank_account_name }}
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
                                    {{ $paidDetailItem->payer?->name ?? ($paidDetailItem->updater?->name ?? 'Admin') }}
                                </div>
                            </div>
                            @if ($paidDetailItem->payment_notes)
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-sticky-note"></i></div>
                                        <span>Catatan</span>
                                    </div>
                                    <div class="emp-tile-right text-muted text-truncate" style="max-width: 220px;" title="{{ $paidDetailItem->payment_notes }}">
                                        {{ $paidDetailItem->payment_notes }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                @if ($paidDetailItem)
                    <div class="modal-footer bg-whitesmoke br p-3">
                        <div class="d-flex w-100" style="gap: 10px;">
                            @if ($paidDetailItem->payment_proof_path)
                                <button type="button" wire:click="openEditItemPaymentModal({{ $paidDetailItem->id }})" class="btn btn-light border text-dark flex-fill font-weight-600 shadow-sm" style="border-radius: 8px; font-size: 13px; padding: 9px 12px;">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                                <a href="{{ asset('storage/' . $paidDetailItem->payment_proof_path) }}" target="_blank" class="btn btn-primary flex-fill font-weight-600 shadow-sm text-center" style="border-radius: 8px; font-size: 13px; padding: 9px 12px;">
                                    <i class="fas fa-file-download mr-1"></i> Download Bukti
                                </a>
                            @else
                                <button type="button" wire:click="openEditItemPaymentModal({{ $paidDetailItem->id }})" class="btn btn-primary flex-fill font-weight-600 shadow-sm" style="border-radius: 8px; font-size: 13px; padding: 9px 12px;">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                                <button type="button" class="btn btn-secondary flex-fill font-weight-600" data-dismiss="modal" style="border-radius: 8px; font-size: 13px; padding: 9px 12px;">
                                    <i class="fas fa-times mr-1"></i> Tutup
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            /* 5 Kartu Statistik: Modern CSS Grid (Equal gap-x & gap-y di mobile/tablet, 5 Kolom sejajar di desktop) */
            .payroll-stat-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 20px;
            }

            .payroll-stat-grid .card {
                margin-bottom: 0 !important;
            }

            .payroll-stat-grid .payroll-stat-card-take-home {
                grid-column: span 2 / span 2;
            }

            @media (min-width: 576px) {
                .payroll-stat-grid {
                    gap: 12px;
                }
            }

            @media (min-width: 992px) {
                .payroll-stat-grid {
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: 14px;
                    margin-bottom: 24px;
                }
                .payroll-stat-grid .payroll-stat-card-take-home {
                    grid-column: span 1 / span 1;
                }
                .payroll-sidebar-sticky {
                    position: sticky;
                    top: 80px;
                    z-index: 5;
                }
            }

            @media (min-width: 1200px) {
                .payroll-stat-grid {
                    gap: 16px;
                }
            }

            /* Header Alur Penggajian (Mirip Section Title & Section Lead) */
            .payroll-sidebar-header .payroll-sidebar-indicator {
                display: inline-block;
                width: 4px;
                height: 18px;
                background-color: var(--primary, #0b52aa) !important;
                border-radius: 2.5px;
                margin-right: 8px;
                flex-shrink: 0;
            }
            .payroll-sidebar-header .payroll-sidebar-title {
                font-family: 'Nunito', 'Segoe UI', arial, sans-serif;
                font-size: 16px;
                font-weight: 600;
                color: #191d21;
                letter-spacing: -0.2px;
                line-height: 1.3;
            }
            .payroll-sidebar-header .payroll-sidebar-lead {
                font-family: 'Nunito', 'Segoe UI', arial, sans-serif;
                font-size: 12.5px;
                font-weight: 500;
                line-height: 1.6;
                color: #64748b;
                margin-top: 4px;
            }

            /* Compact Activities Timeline (Sesuai proporsi bawaan template Shizuefi) */
            .activities .activity .activity-detail {
                padding: 10px 14px !important;
                margin-bottom: 14px !important;
            }
            .activities .activity .activity-detail:before {
                top: 14px !important;
            }
            .activities .activity:before {
                background-color: #e2e8f0 !important;
            }
            .activities .activity .activity-icon.activity-icon-pending {
                background-color: #f1f5f9 !important;
                border: 1px solid #cbd5e1 !important;
                color: #64748b !important;
                box-shadow: none !important;
            }
            .activities .activity .activity-text-pending {
                color: #64748b !important;
            }
            .activities .activity .activity-desc-pending {
                color: #64748b !important;
                font-size: 13px !important;
                line-height: 1.5 !important;
            }
            .activities .activity .activity-detail .bullet {
                color: #94a3b8 !important;
            }

            /* Payroll Sidebar Action Buttons (1 Row 2 Buttons on Desktop & Mobile, No Outline) */
            .payroll-sidebar-actions {
                display: flex !important;
                flex-direction: row !important;
                align-items: stretch !important;
                gap: 8px !important;
                width: 100% !important;
            }
            .payroll-sidebar-actions .payroll-action-btn {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                padding: 9px 8px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                line-height: 1.3 !important;
                border-radius: 8px !important;
                margin: 0 !important;
                white-space: normal !important;
                word-break: break-word !important;
            }
            .payroll-sidebar-actions .payroll-action-btn i {
                font-size: 12px !important;
                flex-shrink: 0;
            }

            /* Custom Stat Icon & Badges (Matching Kasbon) */
            .cash-advance-stat-icon-red {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
                box-shadow: 0 4px 10px rgba(220, 38, 38, 0.28) !important;
            }
            .cash-advance-stat-badge-red {
                background-color: #fef2f2 !important;
                color: #dc2626 !important;
            }
            .cash-advance-stat-badge-purple {
                background-color: #f5f3ff !important;
                color: #8b5cf6 !important;
            }

            /* Table Row Hover */
            .payroll-table-row:hover {
                background-color: #f8fafc !important;
            }

            /* Sembunyikan batang scroll horizontal pada tabel */
            .cash-advance-main-card .table-responsive {
                scrollbar-width: none !important; /* Firefox */
                -ms-overflow-style: none !important; /* IE/Edge */
            }
            .cash-advance-main-card .table-responsive::-webkit-scrollbar {
                display: none !important; /* Chrome, Safari, Edge */
                width: 0 !important;
                height: 0 !important;
            }

            /* Responsive Adjustments for Receipt Modal */
            @media (min-width: 992px) {
                #previewSlipModal .modal-lg {
                    max-width: 860px !important;
                }
            }

            /* Payment Detail Modal (Mobile / Flutter Receipt Style) */
            @media (min-width: 576px) {
                #paymentDetailModal .modal-dialog {
                    max-width: 440px !important;
                }
            }
            #paymentDetailModal .modal-header {
                padding: 14px 20px;
                border-bottom: 1px solid #f1f5f9;
            }
            #paymentDetailModal .modal-title {
                font-size: 15px;
                font-weight: 700;
                color: #1e293b;
                letter-spacing: -0.2px;
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

            /* Bootstrap Callout (.bd-callout) Component */
            .bd-callout {
                padding: 1rem 1.25rem !important;
                margin-top: 0 !important;
                margin-bottom: 1.5rem !important;
                background-color: #ffffff !important;
                border: 1px solid #e2e8f0 !important;
                border-left-width: 4px !important;
                border-radius: 8px !important;
                box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.04) !important;
                transition: all 0.2s ease-in-out !important;
            }

            .bd-callout-info {
                border-left-color: #0b52aa !important;
                background: linear-gradient(90deg, rgba(11, 82, 170, 0.05) 0%, rgba(255, 255, 255, 0.96) 18%, #ffffff 100%) !important;
            }

            .bd-callout .bd-callout-icon {
                font-size: 18px !important;
                line-height: 1 !important;
                flex-shrink: 0 !important;
            }

            .bd-callout-info .bd-callout-icon {
                color: #0b52aa !important;
            }

            .bd-callout .bd-callout-title {
                font-size: 14px !important;
                font-weight: 700 !important;
                line-height: 1.35 !important;
                margin-bottom: 3px !important;
                letter-spacing: -0.15px !important;
            }

            .bd-callout-info .bd-callout-title {
                color: #0b52aa !important;
            }

            .bd-callout .bd-callout-desc {
                font-size: 12.5px !important;
                color: #475569 !important;
                line-height: 1.55 !important;
                margin-bottom: 0 !important;
            }

            .bd-callout .bd-callout-desc strong {
                font-weight: 600 !important;
                color: #1e293b !important;
            }
        </style>
    @endpush
</div>

@script
<script>
    $wire.on('submit-period-confirmed', () => {
        $wire.submitForApproval();
    });

    $wire.on('approve-period-confirmed', () => {
        $wire.approvePeriod();
    });

    $wire.on('approve-all-items-confirmed', () => {
        $wire.approveAll();
    });
</script>
@endscript
