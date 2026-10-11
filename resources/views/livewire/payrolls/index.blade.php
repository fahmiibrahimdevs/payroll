<div>
    <x-slot name="header">
        <h1>Penggajian</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Keuangan & Gaji</div>
            <div class="breadcrumb-item">Penggajian</div>
        </div>
    </x-slot>

    <!-- Template Native Section Title & Lead + Tombol Buat Periode Baru -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2 mb-md-3">
        <div class="mr-md-3">
            <h2 class="section-title mt-0">Periode Penggajian Karyawan</h2>
            <p class="section-lead mb-0">
                Kelola periode penggajian bulanan, perhitungan tunjangan, pemotongan cicilan kasbon otomatis, dan penerbitan slip gaji.
            </p>
        </div>

        @if (auth()->user()?->hasRole('admin'))
            <button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" wire:target="openCreateModal" class="btn btn-primary d-none d-md-inline-flex align-items-center font-weight-bold shadow-sm mt-3 mt-md-0 flex-shrink-0" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 9px 16px; font-size: 13px;">
                <span wire:loading.remove wire:target="openCreateModal">
                    <i class="fas fa-plus mr-1.5"></i> Buat Periode Baru
                </span>
                <span wire:loading wire:target="openCreateModal">
                    <i class="fas fa-spinner fa-spin mr-1.5"></i> Membuka...
                </span>
            </button>
        @endif
    </div>

    <!-- 4 Modern Stat Cards (Kebutuhan Nyata HRD & Keuangan) -->
    <div class="payroll-stat-grid mb-4">
        <!-- 1. Take Home Pay Berjalan -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Take Home Pay Berjalan">Take Home Pay Berjalan</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-primary" title="{{ $statistics['formatted_active_net_salary'] }}">
                            {{ $statistics['formatted_active_net_salary'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-coins text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        @if ($activePeriod)
                            Estimasi transfer {{ $activePeriod->employee_count }} karyawan
                        @else
                            Belum ada draf aktif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Potongan Kasbon Terpotong -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Potongan Kasbon Terpotong">Potongan Kasbon</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-warning" title="{{ $statistics['formatted_active_kasbon'] }}">
                            {{ $statistics['formatted_active_kasbon'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-receipt text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        @if ($statistics['active_kasbon_count'] > 0)
                            {{ $statistics['active_kasbon_count'] }} cicilan kasbon terpotong
                        @else
                            Tidak ada potongan kasbon
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Pajak PPh 21 Masa -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-red">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Pajak PPh 21 Masa">Pajak PPh 21 Masa</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-danger" title="{{ $statistics['formatted_active_pph21'] }}">
                            {{ $statistics['formatted_active_pph21'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-red">
                            <i class="fas fa-university text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Setor kas negara (TER)
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Total Iuran BPJS -->
        <div class="card cash-advance-stat-card">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total BPJS (Karyawan + Kantor)">Total Iuran BPJS</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-success" title="{{ $statistics['formatted_active_total_bpjs'] }}">
                            {{ $statistics['formatted_active_total_bpjs'] }}
                        </div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-heartbeat text-[10px]"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Kesehatan & Ketenagakerjaan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Split Layout (4 Kolom Sorotan Periode Berjalan : 8 Kolom Riwayat Periode) -->
    <div class="row align-items-start">
        <!-- ================= KOLOM KIRI (COL-4): SOROTAN PERIODE BERJALAN ================= -->
        <div class="col-12 col-lg-4 mb-4">
            @if ($activePeriod)
                <div class="card shadow-sm payroll-focus-card">
                    <div class="card-header border-bottom-0 pb-0 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="payroll-sidebar-indicator mr-2"></span>
                            <h4 class="mb-0 font-weight-bold text-dark" style="font-size: 15px;">Periode Berjalan</h4>
                        </div>
                        <span class="{{ $activePeriod->status_badge_class }}">
                            {{ $activePeriod->status_label }}
                        </span>
                    </div>
                    <div class="card-body pt-3">
                        <!-- Nama & Info Periode -->
                        <div class="mb-3">
                            <div class="text-xs font-weight-bold text-uppercase text-muted letter-spacing-1 mb-1">
                                {{ $activePeriod->reference_number }} &bull; {{ $activePeriod->period_label }}
                            </div>
                            <h5 class="font-weight-bold text-dark mb-1" style="font-size: 16px; line-height: 1.35;">
                                {{ $activePeriod->name }}
                            </h5>
                            @if ($activePeriod->notes)
                                <p class="text-muted small mb-0">{{ $activePeriod->notes }}</p>
                            @endif
                        </div>

                        <!-- Financial Snapshot Box -->
                        <div class="payroll-financial-box p-3 rounded mb-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="d-flex justify-content-between align-items-center mb-1.5 small">
                                <span class="text-muted">Gaji Pokok:</span>
                                <span class="font-weight-600 text-dark">{{ $activePeriod->formatted_total_basic_salary }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1.5 small">
                                <span class="text-muted">Tunjangan (+):</span>
                                <span class="font-weight-600 text-success">+{{ $activePeriod->formatted_total_allowances }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-muted">Potongan (-):</span>
                                <span class="font-weight-600 text-danger">-{{ $activePeriod->formatted_total_deductions }}</span>
                            </div>
                            <div class="border-top pt-2 d-flex justify-content-between align-items-baseline">
                                <span class="font-weight-bold text-dark" style="font-size: 13px;">Take Home Pay:</span>
                                <span class="font-weight-bold text-primary" style="font-size: 17px;">{{ $activePeriod->formatted_total_net_salary }}</span>
                            </div>
                        </div>

                        <!-- Status Kesiapan Operasional -->
                        <div class="mb-4">
                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2.5">Status Kesiapan Operasional</div>
                            <div class="payroll-checklist-group">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 20px; height: 20px; font-size: 10px;">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="small text-dark font-weight-500">
                                        <strong>{{ $activePeriod->employee_count }} Karyawan</strong> terdata dalam slip
                                    </div>
                                </div>

                                <div class="d-flex align-items-center mb-2">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 20px; height: 20px; font-size: 10px;">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="small text-dark font-weight-500">
                                        Pajak PPh 21 &amp; iuran BPJS terhitung
                                    </div>
                                </div>

                                <div class="d-flex align-items-center mb-2">
                                    @if ($statistics['active_kasbon_count'] > 0)
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 20px; height: 20px; font-size: 10px;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="small text-dark font-weight-500">
                                            {{ $statistics['active_kasbon_count'] }} cicilan kasbon otomatis terpotong
                                        </div>
                                    @else
                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 20px; height: 20px; font-size: 10px;">
                                            <i class="fas fa-minus"></i>
                                        </div>
                                        <div class="small text-muted font-weight-500">
                                            Tidak ada potongan cicilan kasbon
                                        </div>
                                    @endif
                                </div>

                                <div class="d-flex align-items-center">
                                    @if ($activePeriod->status === \App\Enums\PayrollPeriodStatus::DRAFT)
                                        <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 20px; height: 20px; font-size: 10px;">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="small text-dark font-weight-500">
                                            Draf belum diajukan ke Direktur
                                        </div>
                                    @elseif ($activePeriod->status === \App\Enums\PayrollPeriodStatus::SUBMITTED)
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 20px; height: 20px; font-size: 10px;">
                                            <i class="fas fa-paper-plane"></i>
                                        </div>
                                        <div class="small text-dark font-weight-500">
                                            Menunggu persetujuan Direktur (ACC)
                                        </div>
                                    @elseif ($activePeriod->status === \App\Enums\PayrollPeriodStatus::APPROVED)
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 20px; height: 20px; font-size: 10px;">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                        <div class="small text-dark font-weight-500">
                                            Telah disetujui Direktur &bull; Siap ditransfer
                                        </div>
                                    @elseif ($activePeriod->status === \App\Enums\PayrollPeriodStatus::PAID)
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 20px; height: 20px; font-size: 10px;">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                        <div class="small text-dark font-weight-500">
                                            Selesai dibayar &amp; slip diterbitkan
                                        </div>
                                    @else
                                        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mr-2.5 flex-shrink-0" style="width: 20px; height: 20px; font-size: 10px;">
                                            <i class="fas fa-exclamation"></i>
                                        </div>
                                        <div class="small text-danger font-weight-500">
                                            Perlu revisi komponen gaji
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Single Action Button to Manage Current Period -->
                        <a href="{{ route('payrolls.show', $activePeriod) }}" class="btn btn-primary btn-block d-flex align-items-center justify-content-center font-weight-600 shadow-sm" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 10px 16px; font-size: 13.5px;">
                            <span>Kelola Periode Ini</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            @else
                <div class="card shadow-sm payroll-focus-card">
                    <div class="card-header border-bottom-0 pb-0">
                        <div class="d-flex align-items-center">
                            <span class="payroll-sidebar-indicator mr-2"></span>
                            <h4 class="mb-0 font-weight-bold text-dark" style="font-size: 15px;">Periode Berjalan</h4>
                        </div>
                    </div>
                    <div class="card-body text-center py-5">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 56px; height: 56px; background-color: #f1f5f9;">
                            <i class="fas fa-calendar-plus" style="font-size: 24px; color: #0b52aa;"></i>
                        </div>
                        <h5 class="font-weight-600 text-dark mb-1" style="font-size: 15px;">Belum Ada Periode Aktif</h5>
                        <p class="text-muted small mb-0 px-2">
                            Gunakan tombol "+ Buat Periode Baru" untuk memulai perhitungan gaji bulan ini.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- ================= KOLOM KANAN (COL-8): DAFTAR RIWAYAT PERIODE ================= -->
        <div class="col-12 col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4>Daftar Riwayat Periode</h4>
                </div>

                <div class="card-body p-0">
                    <!-- Controls: Show Entries & Search Bar -->
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
                            <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Search here...">
                        </div>
                    </div>

                    <!-- Table Responsive -->
                    <div class="table-responsive">
                        <table class="table table-clean">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-center text-nowrap" style="width: 50px;">NO</th>
                                    <th class="text-nowrap" style="width: 130px;">NO. PERIODE</th>
                                    <th class="text-nowrap">NAMA PERIODE</th>
                                    <th class="text-center text-nowrap" style="width: 95px;">KARYAWAN</th>
                                    <th class="text-right text-nowrap" style="width: 150px;">TOTAL GAJI BERSIH</th>
                                    <th class="text-center text-nowrap" style="width: 125px;">STATUS</th>
                                    <th class="text-center text-nowrap" style="width: 90px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($periods as $index => $period)
                                    <tr wire:key="period-{{ $period->id }}">
                                        <td class="text-center font-weight-600 text-muted align-middle">
                                            {{ $periods->firstItem() + $index }}
                                        </td>
                                        <td class="font-weight-600 align-middle text-nowrap">
                                            <a href="{{ route('payrolls.show', $period) }}" class="text-primary font-weight-600">
                                                {{ $period->reference_number }}
                                            </a>
                                            <div class="text-muted small">{{ $period->period_label }}</div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="font-weight-600 text-dark">{{ $period->name }}</div>
                                            @if ($period->notes)
                                                <div class="text-muted small text-truncate" style="max-width: 220px;">{{ $period->notes }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle font-weight-600 text-nowrap">
                                            {{ $period->employee_count }} <span class="text-muted small">orang</span>
                                        </td>
                                        <td class="text-right align-middle font-weight-bold text-dark text-nowrap">
                                            {{ $period->formatted_total_net_salary }}
                                        </td>
                                        <td class="text-center align-middle text-nowrap">
                                            <span class="{{ $period->status_badge_class }}">
                                                {{ $period->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-center align-middle text-nowrap">
                                            <a href="{{ route('payrolls.show', $period) }}" class="btn btn-info btn-action mr-1" title="Lihat & Kelola Rincian">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if ($period->status->isEditable() && auth()->user()?->hasRole('admin'))
                                                <button type="button" 
                                                    wire:click="$dispatch('swal:confirm', {
                                                        title: 'Hapus Periode?',
                                                        text: 'Draf periode {{ $period->reference_number }} dan seluruh rincian slip karyawan di dalamnya akan dihapus!',
                                                        action: 'delete-period-confirmed',
                                                        params: { id: {{ $period->id }} }
                                                    })" 
                                                    class="btn btn-danger btn-action" 
                                                    title="Hapus Periode">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <div class="py-4">
                                                <i class="fas fa-folder-open text-muted mb-2" style="font-size: 32px;"></i>
                                                <div class="font-weight-600 mt-2">Belum ada periode penggajian</div>
                                                <p class="small text-muted mb-0">Gunakan tombol "+ Buat Periode Baru" di atas untuk memulai perhitungan gaji bulan ini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer: Info & Pagination -->
                    <div class="dt-footer-wrapper">
                        <div>
                            Showing {{ $periods->total() > 0 ? $periods->firstItem() : 0 }} to {{ $periods->total() > 0 ? $periods->lastItem() : 0 }} of {{ $periods->total() }} entries
                        </div>
                        <div>
                            {{ $periods->links('livewire.custom-pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Buat Periode Baru -->
    <div wire:ignore.self class="modal fade" id="createPeriodModal" role="dialog" aria-labelledby="createPeriodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPeriodModalLabel">Buat Periode Penggajian Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form wire:submit="storePeriod">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Bulan Penggajian <span class="text-danger">*</span></label>
                                    <select wire:model.live="newMonth" class="form-control @error('newMonth') is-invalid @enderror">
                                        @for ($m = 1; $m <= 12; $m++)
                                            @php
                                                $monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                                            @endphp
                                            <option value="{{ $m }}">{{ $monthNames[$m] }}</option>
                                        @endfor
                                    </select>
                                    @error('newMonth')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Tahun <span class="text-danger">*</span></label>
                                    <select wire:model.live="newYear" class="form-control @error('newYear') is-invalid @enderror">
                                        @foreach ($availableYears as $yr)
                                            <option value="{{ $yr }}">{{ $yr }}</option>
                                        @endforeach
                                    </select>
                                    @error('newYear')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-600">Nama Periode <span class="text-danger">*</span></label>
                            <input type="text" wire:model="newName" class="form-control @error('newName') is-invalid @enderror" placeholder="Contoh: Penggajian Periode Oktober 2026">
                            @error('newName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-600">Catatan Internal (Opsional)</label>
                            <textarea wire:model="newNotes" class="form-control @error('newNotes') is-invalid @enderror" rows="2" placeholder="Catatan tambahan mengenai periode ini"></textarea>
                            @error('newNotes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-light border small text-muted mb-0">
                            <i class="fas fa-info-circle text-primary mr-1"></i>
                            Sistem akan secara otomatis menarik seluruh karyawan berstatus <strong>Aktif</strong>, gaji pokok masing-masing, serta potongan cicilan kasbon yang jatuh tempo pada bulan tersebut.
                        </div>
                    </div>
                    <div class="modal-footer bg-whitesmoke br">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="storePeriod">
                                <i class="fas fa-check mr-1"></i> Proses & Buat Draf
                            </span>
                            <span wire:loading wire:target="storePeriod">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Floating Action Button (Buat Periode Baru - Khusus Mobile) -->
    @if (auth()->user()?->hasRole('admin'))
        <button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" wire:target="openCreateModal" class="btn-floating-add d-md-none" title="Buat Periode Baru" aria-label="Buat Periode Baru">
            <i wire:loading wire:target="openCreateModal" class="fas fa-spinner fa-spin"></i>
            <i wire:loading.remove wire:target="openCreateModal" class="fas fa-plus"></i>
        </button>
    @endif
</div>

@push('styles')
    <style>
        .payroll-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .payroll-stat-grid .card {
            margin-bottom: 0 !important;
        }
        @media (min-width: 576px) {
            .payroll-stat-grid {
                gap: 12px;
            }
        }
        @media (min-width: 992px) {
            .payroll-stat-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
                margin-bottom: 24px;
            }
        }
        @media (min-width: 1200px) {
            .payroll-stat-grid {
                gap: 16px;
            }
        }
        .cash-advance-stat-icon-red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.28) !important;
        }
        .cash-advance-stat-badge-red {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
        }
        .payroll-sidebar-indicator {
            display: inline-block;
            width: 4px;
            height: 18px;
            background-color: #0b52aa !important;
            border-radius: 2.5px;
            flex-shrink: 0;
        }
        .payroll-focus-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .payroll-financial-box {
            border-radius: 10px;
        }
    </style>
@endpush

@script
<script>
    $wire.on('delete-period-confirmed', (data) => {
        $wire.deletePeriod(data.id);
    });
</script>
@endscript
