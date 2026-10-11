<div>
    <x-slot name="header">
        <h1>Penggajian</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Keuangan & Gaji</div>
            <div class="breadcrumb-item">Penggajian</div>
        </div>
    </x-slot>

    <h2 class="section-title">Periode Penggajian Karyawan</h2>
    <p class="section-lead">
        Kelola periode penggajian bulanan, perhitungan tunjangan, pemotongan cicilan kasbon otomatis, dan penerbitan slip gaji.
    </p>

    <!-- 4 Clean Statistic Cards (Template Bawaan Stisla/Shizuefi) -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Gaji Terbayar {{ date('Y') }}</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['formatted_total_paid_this_year'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Menunggu Approval</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['pending_approval_count'] }} <small class="text-muted">Periode</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Draf Berjalan</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['draft_count'] }} <small class="text-muted">Periode</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Karyawan Aktif</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['total_active_employees'] }} <small class="text-muted">Orang</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Data Table Card -->
    <div class="card">
        <div class="card-header">
            <h4>Daftar Periode Penggajian</h4>
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
                            <th class="text-center text-nowrap" style="width: 60px;">NO</th>
                            <th class="text-nowrap" style="width: 140px;">NOMOR PERIODE</th>
                            <th class="text-nowrap">NAMA PERIODE</th>
                            <th class="text-center text-nowrap" style="width: 120px;">PERIODE BULAN</th>
                            <th class="text-center text-nowrap" style="width: 100px;">KARYAWAN</th>
                            <th class="text-right text-nowrap" style="width: 160px;">TOTAL GAJI BERSIH</th>
                            <th class="text-center text-nowrap" style="width: 140px;">STATUS</th>
                            <th class="text-center text-nowrap" style="width: 110px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $index => $period)
                            <tr wire:key="period-{{ $period->id }}">
                                <td class="text-center font-weight-600 text-muted align-middle">
                                    {{ $periods->firstItem() + $index }}
                                </td>
                                <td class="font-weight-600 text-primary align-middle text-nowrap">
                                    <a href="{{ route('payrolls.show', $period) }}">
                                        {{ $period->reference_number }}
                                    </a>
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-600 text-dark">{{ $period->name }}</div>
                                    @if ($period->notes)
                                        <div class="text-muted small text-truncate" style="max-width: 280px;">{{ $period->notes }}</div>
                                    @endif
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <span class="badge badge-light border">{{ $period->period_label }}</span>
                                </td>
                                <td class="text-center align-middle font-weight-600">
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
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="fas fa-folder-open text-muted mb-2" style="font-size: 32px;"></i>
                                        <div class="font-weight-600 mt-2">Belum ada periode penggajian</div>
                                        <p class="small text-muted mb-3">Klik tombol "Buat Periode Baru" untuk memulai perhitungan gaji bulan ini.</p>
                                        @if (auth()->user()?->hasRole('admin'))
                                            <button type="button" wire:click="openCreateModal" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus mr-1"></i> Buat Periode Sekarang
                                            </button>
                                        @endif
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

    <!-- Floating Action Button (Buat Periode Baru) -->
    @if (auth()->user()?->hasRole('admin'))
        <button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" wire:target="openCreateModal" class="btn-floating-add" title="Buat Periode Baru" aria-label="Buat Periode Baru">
            <i wire:loading wire:target="openCreateModal" class="fas fa-spinner fa-spin"></i>
            <i wire:loading.remove wire:target="openCreateModal" class="fas fa-plus"></i>
        </button>
    @endif
</div>

@script
<script>
    $wire.on('delete-period-confirmed', (data) => {
        $wire.deletePeriod(data.id);
    });
</script>
@endscript
