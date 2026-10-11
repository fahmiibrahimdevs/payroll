<div>
    <x-slot name="header">
        <h1>Kelola Jabatan</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Master Data</div>
            <div class="breadcrumb-item">Jabatan</div>
        </div>
    </x-slot>

    <h2 class="section-title">Jabatan</h2>
    <p class="section-lead mb-3">
        Kelola data posisi dan struktur jabatan kepegawaian dalam organisasi perusahaan.
    </p>

    <div class="card">
        <div class="card-header">
            <h4>Tabel Jabatan</h4>
        </div>

        <div class="card-body p-0">
            <!-- Controls: Show Entries & Search Bar -->
            <div class="dt-control-wrapper">
                <div class="dt-show-entries">
                    Show
                    <select wire:model.live="perPage" class="dt-select-entries">
                        <option value="5">5</option>
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

            <!-- Full-Width Data Table -->
            <div class="table-responsive">
                <table class="table table-clean">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-center text-nowrap" style="width: 60px;">NO</th>
                            <th class="text-nowrap" style="width: 240px;">NAMA JABATAN</th>
                            <th class="text-nowrap">DESKRIPSI</th>
                            <th class="text-center text-nowrap" style="width: 110px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positions as $index => $position)
                            <tr wire:key="position-{{ $position->id }}">
                                <td class="text-center font-weight-600 text-[#64748b] align-middle">
                                    {{ $positions->firstItem() + $index }}
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-[#1e293b] text-[14px]">
                                        {{ $position->name }}
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="text-[#64748b] text-[13px] leading-relaxed">
                                        {{ $position->description ?: '-' }}
                                    </div>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <button type="button" wire:click="edit({{ $position->id }})" wire:loading.attr="disabled" wire:target="edit({{ $position->id }})" class="btn btn-primary btn-action mr-1" title="Edit Data">
                                        <i wire:loading wire:target="edit({{ $position->id }})" class="fas fa-spinner fa-spin"></i>
                                        <i wire:loading.remove wire:target="edit({{ $position->id }})" class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $position->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $position->id }})" class="btn btn-danger btn-action" title="Hapus Data">
                                        <i wire:loading wire:target="confirmDelete({{ $position->id }})" class="fas fa-spinner fa-spin"></i>
                                        <i wire:loading.remove wire:target="confirmDelete({{ $position->id }})" class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="fas fa-folder-open text-muted mb-2" style="font-size: 32px;"></i>
                                        <div class="font-weight-600 mt-2">Tidak ada data ditemukan</div>
                                        <small class="text-muted">
                                            @if($search)
                                                Tidak ada jabatan yang sesuai dengan kata kunci "{{ $search }}".
                                            @else
                                                Belum ada data jabatan yang tersedia.
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
                    Showing {{ $positions->total() > 0 ? $positions->firstItem() : 0 }} to {{ $positions->total() > 0 ? $positions->lastItem() : 0 }} of {{ $positions->total() }} entries
                </div>
                <div>
                    {{ $positions->links('livewire.custom-pagination') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button (Tambah Data) -->
    <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn-floating-add" title="Tambah Data" aria-label="Tambah Data">
        <i wire:loading wire:target="create" class="fas fa-spinner fa-spin"></i>
        <i wire:loading.remove wire:target="create" class="fas fa-plus"></i>
    </button>

    <!-- Modal Form (Add / Edit) -->
    @include('livewire.positions.form-modal')
</div>
