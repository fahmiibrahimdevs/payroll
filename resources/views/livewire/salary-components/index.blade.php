<div>
    <x-slot name="header">
        <h1>Kelola Komponen Gaji</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Master Data</div>
            <div class="breadcrumb-item">Komponen Gaji</div>
        </div>
    </x-slot>

    <h2 class="section-title">Master Komponen Gaji</h2>
    <p class="section-lead mb-3">
        Kelola komponen tunjangan dan potongan gaji untuk formula perhitungan penggajian karyawan.
    </p>

    <!-- Main Content Row: 3 cols Filter Sidebar + 9 cols Data Table -->
    <div class="row align-items-start">
        <!-- Left Sidebar: Filter Panel (Desktop: 3 cols / col-lg-3 col-md-4 d-none d-md-block) -->
        <div class="col-lg-3 col-md-4 d-none d-md-block">
            <div class="card">
                <div class="card-header">
                    <h4>Filter Komponen</h4>
                    @if ($search || $filterCategory !== '' || $filterType !== '' || $filterStatus !== '')
                        <div class="card-header-action">
                            <button type="button" wire:click="resetFilters" class="btn btn-sm btn-link text-danger text-decoration-none p-0"><i class="fas fa-undo mr-1"></i> Reset</button>
                        </div>
                    @endif
                </div>

                <div class="card-body">
                    @include('livewire.salary-components.filter-content', ['filterContext' => 'desktop'])
                </div>
            </div>
        </div>

        <!-- Right Side: Salary Component Data (Desktop: 9 cols / col-lg-9 col-md-8 col-12) -->
        <div class="col-lg-9 col-md-8 col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Daftar Komponen Gaji</h4>
                </div>
                <div class="card-body p-0">
                    <!-- Controls: Show Entries & Search Bar -->
                    <div class="dt-control-wrapper">
                        <div class="dt-show-entries">
                            Show
                            <select wire:model.live="perPage" class="dt-select-entries">
                                <option value="20">20</option>
                                <option value="40">40</option>
                                <option value="80">80</option>
                                <option value="160">160</option>
                                <option value="200">200</option>
                            </select>
                            Entries
                        </div>

                        <div class="dt-search-wrapper">
                            <span class="mr-1">Search:</span>
                            <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Nama, kode, keterangan...">
                        </div>

                        <!-- Mobile Filter Button -->
                        <div class="d-md-none w-100">
                            <button type="button" class="btn btn-primary btn-block shadow-none" data-toggle="modal" data-target="#salaryComponentFilterMobileModal"><i class="fas fa-filter mr-1.5"></i> Filter Komponen @if ($filterCategory !== '' || $filterType !== '' || $filterStatus !== '')<span class="badge badge-light text-primary ml-1 py-0.5 px-1.5 text-xs">Aktif</span>@endif</button>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-responsive">
                        <table class="table table-clean">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-center text-nowrap" style="width: 50px;">NO</th>
                                    <th class="text-nowrap" style="width: 140px;">KODE</th>
                                    <th class="text-nowrap">NAMA KOMPONEN</th>
                                    <th class="text-nowrap" style="width: 180px;">METODE HITUNG</th>
                                    <th class="text-center text-nowrap" style="width: 80px;">PAJAK</th>
                                    <th class="text-center text-nowrap" style="width: 100px;">STATUS</th>
                                    <th class="text-center text-nowrap" style="width: 100px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salaryComponents->groupBy(fn($item) => $item->category_label) as $groupLabel => $items)
                                    <!-- Row Group Header -->
                                    <tr class="bg-gray-100/70" wire:key="group-header-{{ Str::slug($groupLabel) }}">
                                        <td colspan="7" class="py-2.5 px-4 text-xs text-uppercase letter-spacing-1 border-top border-bottom bg-gray-100">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="text-dark font-weight-600 uppercase"><i class="fas fa-layer-group text-primary mr-1.5"></i> {{ $groupLabel }}</span>
                                                <span class="badge badge-light border text-xs py-0.5 px-2">{{ $items->count() }} Komponen</span>
                                            </div>
                                        </td>
                                    </tr>

                                    @foreach ($items as $item)
                                        <tr wire:key="salary-comp-{{ $item->id }}">
                                            <td class="text-center text-muted align-middle">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td class="align-middle text-nowrap">
                                                @if ($item->code)
                                                    <span class="badge badge-light border">{{ $item->code }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="text-dark font-weight-600">{{ $item->name }}</span>
                                                    @if ($item->is_system)
                                                        <span class="badge badge-secondary ml-1.5 py-0.5 px-1.5 text-xs" title="Komponen Bawaan Sistem"><i class="fas fa-lock mr-0.5 text-xs"></i> Sistem</span>
                                                    @endif
                                                </div>
                                                @if ($item->description)
                                                    <small class="text-muted d-block mt-0.5">{{ Str::limit($item->description, 60) }}</small>
                                                @endif
                                            </td>
                                            <td class="align-middle text-nowrap">
                                                <div class="text-dark">{{ $item->calculation_type_label }}</div>
                                                @if ($item->default_value !== null && $item->default_value !== '')
                                                    <small class="text-muted">Def: {{ $item->default_value_formatted }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle text-nowrap">
                                                <label class="custom-switch pl-0 m-0 cursor-pointer" title="{{ $item->is_system && $item->code === 'GAJI_POKOK' ? 'Gaji Pokok wajib dikenakan pajak' : ($item->is_taxable ? 'Pajak: Kena Pajak (Klik untuk ubah)' : 'Pajak: Bebas Pajak (Klik untuk ubah)') }}">
                                                    <input type="checkbox"
                                                           wire:click="toggleTaxable({{ $item->id }})"
                                                           wire:loading.attr="disabled"
                                                           class="custom-switch-input"
                                                           {{ $item->is_taxable ? 'checked' : '' }}
                                                           {{ $item->is_system && $item->code === 'GAJI_POKOK' ? 'disabled' : '' }}>
                                                    <span class="custom-switch-indicator"></span>
                                                </label>
                                            </td>
                                            <td class="text-center align-middle text-nowrap">
                                                <label class="custom-switch pl-0 m-0 cursor-pointer" title="{{ $item->is_system && $item->code === 'GAJI_POKOK' ? 'Gaji Pokok wajib selalu aktif' : ($item->is_active ? 'Status: Aktif (Klik untuk nonaktifkan)' : 'Status: Nonaktif (Klik untuk aktifkan)') }}">
                                                    <input type="checkbox"
                                                           wire:click="toggleStatus({{ $item->id }})"
                                                           wire:loading.attr="disabled"
                                                           class="custom-switch-input"
                                                           {{ $item->is_active ? 'checked' : '' }}
                                                           {{ $item->is_system && $item->code === 'GAJI_POKOK' ? 'disabled' : '' }}>
                                                    <span class="custom-switch-indicator"></span>
                                                </label>
                                            </td>
                                            <td class="text-center align-middle text-nowrap">
                                                <button type="button" wire:click="edit({{ $item->id }})" wire:loading.attr="disabled" wire:target="edit({{ $item->id }})" class="btn btn-primary btn-action mr-1" title="Edit Komponen"><i wire:loading wire:target="edit({{ $item->id }})" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="edit({{ $item->id }})" class="fas fa-pencil-alt"></i></button>
                                                @if (! $item->is_system)
                                                    <button type="button" wire:click="confirmDelete({{ $item->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $item->id }})" class="btn btn-danger btn-action" title="Hapus Komponen"><i wire:loading wire:target="confirmDelete({{ $item->id }})" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="confirmDelete({{ $item->id }})" class="fas fa-trash"></i></button>
                                                @else
                                                    <button type="button" class="btn btn-secondary btn-action cursor-not-allowed" disabled title="Komponen sistem tidak dapat dihapus"><i class="fas fa-lock text-muted"></i></button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <div class="py-4">
                                                <i class="fas fa-folder-open text-muted mb-2" style="font-size: 32px;"></i>
                                                <div class="mt-2 text-dark">Tidak ada data komponen gaji</div>
                                                <small class="text-muted">
                                                    @if($search || $filterCategory || $filterType || $filterStatus)
                                                        Tidak ada komponen gaji yang sesuai dengan kriteria filter pencarian.
                                                    @else
                                                        Belum ada master komponen gaji yang ditambahkan.
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
                            Showing {{ $salaryComponents->total() > 0 ? $salaryComponents->firstItem() : 0 }} to {{ $salaryComponents->total() > 0 ? $salaryComponents->lastItem() : 0 }} of {{ $salaryComponents->total() }} entries
                        </div>
                        <div>
                            {{ $salaryComponents->links('livewire.custom-pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button (Tambah Data) -->
    <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn-floating-add" title="Tambah Komponen Gaji" aria-label="Tambah Komponen Gaji"><i wire:loading wire:target="create" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="create" class="fas fa-plus"></i></button>

    <!-- Modal Form (Add / Edit) -->
    @include('livewire.salary-components.form-modal')

    <!-- Modal Filter (Mobile) -->
    @include('livewire.salary-components.filter-modal')

    @push('scripts')
        <script>
            (function () {
                function initSalaryComponentScripts() {
                    if ($.fn.modal && $.fn.modal.Constructor) {
                        $.fn.modal.Constructor.prototype._enforceFocus = function () {};
                    }

                    const initSelect2 = () => {
                        // Desktop: Filter Kategori
                        if ($('#filterCategory_desktop').length) {
                            $('#filterCategory_desktop').select2({
                                width: '100%',
                                placeholder: 'Semua Kategori',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterCategory', val || '');
                                if ($('#filterCategory_mobile').length && $('#filterCategory_mobile').val() !== val) {
                                    $('#filterCategory_mobile').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Desktop: Filter Status
                        if ($('#filterStatus_desktop').length) {
                            $('#filterStatus_desktop').select2({
                                width: '100%',
                                placeholder: 'Semua Status',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterStatus', val || '');
                                if ($('#filterStatus_mobile').length && $('#filterStatus_mobile').val() !== val) {
                                    $('#filterStatus_mobile').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Mobile: Filter Kategori
                        if ($('#filterCategory_mobile').length) {
                            $('#filterCategory_mobile').select2({
                                dropdownParent: $('#salaryComponentFilterMobileModal'),
                                width: '100%',
                                placeholder: 'Semua Kategori',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterCategory', val || '');
                                if ($('#filterCategory_desktop').length && $('#filterCategory_desktop').val() !== val) {
                                    $('#filterCategory_desktop').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Mobile: Filter Status
                        if ($('#filterStatus_mobile').length) {
                            $('#filterStatus_mobile').select2({
                                dropdownParent: $('#salaryComponentFilterMobileModal'),
                                width: '100%',
                                placeholder: 'Semua Status',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterStatus', val || '');
                                if ($('#filterStatus_desktop').length && $('#filterStatus_desktop').val() !== val) {
                                    $('#filterStatus_desktop').val(val || '').trigger('change.select2');
                                }
                            });
                        }
                    };

                    initSelect2();

                    // Listen reset-salary-component-filters event
                    Livewire.on('reset-salary-component-filters', () => {
                        $('#filterCategory_desktop, #filterCategory_mobile').val('').trigger('change.select2');
                        $('#filterStatus_desktop, #filterStatus_mobile').val('').trigger('change.select2');
                    });
                }

                if (typeof window.Livewire !== 'undefined') {
                    initSalaryComponentScripts();
                } else {
                    document.addEventListener('livewire:init', initSalaryComponentScripts);
                }
            })();
        </script>
    @endpush
</div>
