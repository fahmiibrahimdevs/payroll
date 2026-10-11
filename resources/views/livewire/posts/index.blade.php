<div>
    <x-slot name="header">
        <h1>Kelola Posts</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Posts</div>
        </div>
    </x-slot>

    <h2 class="section-title">Postingan</h2>
    <p class="section-lead">
        Kelola seluruh postingan artikel, berita, dan publikasi konten Anda di halaman ini.
    </p>

    <div class="card">
        <!-- Header: Title (Bawaan Template) -->
        <div class="card-header">
            <h4>Tabel Postingan</h4>
        </div>

        <div class="card-body p-0">
            <!-- Controls: Show Entries & Search Bar (DataTables Style) -->
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
                            <th class="text-nowrap">JUDUL & KONTEN POST</th>
                            <th class="text-nowrap" style="width: 160px;">KATEGORI</th>
                            <th class="text-nowrap" style="width: 140px;">STATUS</th>
                            <th class="text-center text-nowrap" style="width: 110px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($posts as $index => $post)
                            <tr wire:key="post-{{ $post->id }}">
                                <td class="text-center font-weight-600 text-[#64748b] align-middle">
                                    {{ $posts->firstItem() + $index }}
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-[#1e293b] text-[14px] mb-1">
                                        {{ $post->title }}
                                    </div>
                                    <div class="text-[#64748b] text-[12px] leading-relaxed">
                                        {{ Str::limit($post->content, 150) }}
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-light border">
                                        {{ $post->category }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    @if ($post->status === 'published')
                                        <span class="badge badge-success">
                                            Published
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            Draft
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <button type="button" wire:click="edit({{ $post->id }})" wire:loading.attr="disabled" wire:target="edit({{ $post->id }})" class="btn btn-primary btn-action mr-1" title="Edit Data">
                                        <i wire:loading wire:target="edit({{ $post->id }})" class="fas fa-spinner fa-spin"></i>
                                        <i wire:loading.remove wire:target="edit({{ $post->id }})" class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $post->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $post->id }})" class="btn btn-danger btn-action" title="Hapus Data">
                                        <i wire:loading wire:target="confirmDelete({{ $post->id }})" class="fas fa-spinner fa-spin"></i>
                                        <i wire:loading.remove wire:target="confirmDelete({{ $post->id }})" class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="fas fa-folder-open text-muted mb-2" style="font-size: 32px;"></i>
                                        <div class="font-weight-600 mt-2">Tidak ada data ditemukan</div>
                                        <small class="text-muted">
                                            @if($search)
                                                Tidak ada postingan yang sesuai dengan kata kunci "{{ $search }}".
                                            @else
                                                Belum ada data postingan yang tersedia.
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
                    Showing {{ $posts->total() > 0 ? $posts->firstItem() : 0 }} to {{ $posts->total() > 0 ? $posts->lastItem() : 0 }} of {{ $posts->total() }} entries
                </div>
                <div>
                    {{ $posts->links('livewire.custom-pagination') }}
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
    @include('livewire.posts.form-modal')
</div>

@push('scripts')
<script>
    (function () {
        function initPostScripts() {
            // Izinkan focus Select2 di dalam modal Bootstrap
            if ($.fn.modal && $.fn.modal.Constructor) {
                $.fn.modal.Constructor.prototype._enforceFocus = function() {};
            }

            const initPostSelect2 = () => {
                // Select2 Kategori
                if ($('#category').length) {
                    $('#category').select2({
                        dropdownParent: $('#postModal'),
                        width: '100%',
                        placeholder: '-- Pilih Kategori --'
                    }).on('change', function () {
                        @this.set('form.category', $(this).val() || '');
                    });
                }

                // Select2 Status
                if ($('#status').length) {
                    $('#status').select2({
                        dropdownParent: $('#postModal'),
                        width: '100%'
                    }).on('change', function () {
                        @this.set('form.status', $(this).val() || 'draft');
                    });
                }
            };

            initPostSelect2();

            // Listen open-modal event untuk sinkronisasi nilai Select2
            Livewire.on('open-modal', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                if (data && data.id === 'postModal') {
                    setTimeout(() => {
                        $('#category').val(data.category || '').trigger('change.select2');
                        $('#status').val(data.status || 'draft').trigger('change.select2');
                    }, 50);
                }
            });
        }

        if (typeof window.Livewire !== 'undefined') {
            initPostScripts();
        } else {
            document.addEventListener('livewire:init', initPostScripts);
        }
    })();
</script>
@endpush
