<div>
    <x-slot name="header">
        <h1>Hari Libur Nasional</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Master Data</div>
            <div class="breadcrumb-item">Hari Libur</div>
        </div>
    </x-slot>

    <h2 class="section-title">Hari Libur Nasional</h2>
    <p class="section-lead mb-3">
        Kelola kalender hari libur nasional. Hari libur dikecualikan dari hitungan hari kerja saat perhitungan gaji prorata dan gaji mingguan.
    </p>

    <div class="card">
        <div class="card-header">
            <h4>Tabel Hari Libur</h4>
        </div>

        <div class="card-body p-0">
            <!-- Controls: Year & Search -->
            <div class="dt-control-wrapper">
                <div class="dt-show-entries">
                    Tahun
                    <select wire:model.live="year" class="dt-select-entries">
                        @foreach ($yearOptions as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dt-search-wrapper">
                    <span class="mr-1">Search:</span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama hari libur...">
                </div>
            </div>

            <!-- Full-Width Data Table -->
            <div class="table-responsive">
                <table class="table table-clean">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-center text-nowrap" style="width: 60px;">NO</th>
                            <th class="text-nowrap" style="width: 200px;">TANGGAL</th>
                            <th class="text-nowrap">NAMA HARI LIBUR</th>
                            <th class="text-nowrap text-center" style="width: 140px;">HARI</th>
                            <th class="text-center text-nowrap" style="width: 110px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($holidays as $index => $holiday)
                            <tr wire:key="holiday-{{ $holiday->id }}">
                                <td class="text-center font-weight-600 text-[#64748b] align-middle">
                                    {{ $holidays->firstItem() + $index }}
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-[#1e293b] text-[14px] text-nowrap">
                                        {{ $holiday->date->translatedFormat('d M Y') }}
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="text-[#1e293b] text-[14px]">
                                        {{ $holiday->name }}
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-light border text-dark text-nowrap">
                                        {{ $holiday->date->locale('id')->dayName }}
                                    </span>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <button type="button" wire:click="edit({{ $holiday->id }})" wire:loading.attr="disabled" wire:target="edit({{ $holiday->id }})" class="btn btn-primary btn-action mr-1" title="Edit Data">
                                        <i wire:loading wire:target="edit({{ $holiday->id }})" class="fas fa-spinner fa-spin"></i>
                                        <i wire:loading.remove wire:target="edit({{ $holiday->id }})" class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $holiday->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $holiday->id }})" class="btn btn-danger btn-action" title="Hapus Data">
                                        <i wire:loading wire:target="confirmDelete({{ $holiday->id }})" class="fas fa-spinner fa-spin"></i>
                                        <i wire:loading.remove wire:target="confirmDelete({{ $holiday->id }})" class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="far fa-calendar-times text-muted mb-2" style="font-size: 32px;"></i>
                                        <div class="font-weight-600 mt-2 text-dark">Tidak ada data hari libur</div>
                                        <small class="text-muted">
                                            @if($search)
                                                Tidak ada hari libur yang sesuai dengan kata kunci "{{ $search }}".
                                            @else
                                                Belum ada hari libur yang terdaftar untuk tahun {{ $year }}.
                                            @endif
                                        </small>
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
                    Showing {{ $holidays->total() > 0 ? $holidays->firstItem() : 0 }} to {{ $holidays->total() > 0 ? $holidays->lastItem() : 0 }} of {{ $holidays->total() }} entries
                </div>
                <div>
                    {{ $holidays->links('livewire.custom-pagination') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button (Tambah Data, admin saja) -->
    @if (auth()->user()?->hasRole('admin'))
        <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn-floating-add" title="Tambah Data" aria-label="Tambah Data">
            <i wire:loading wire:target="create" class="fas fa-spinner fa-spin"></i>
            <i wire:loading.remove wire:target="create" class="fas fa-plus"></i>
        </button>
    @endif

    <!-- Modal Form (Add / Edit) -->
    @include('livewire.holidays.form-modal')
</div>
