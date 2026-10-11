<div>
    <x-slot name="header">
        <h1>Monitoring Log Aktivitas</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Monitoring Log</div>
        </div>
    </x-slot>

    <!-- Section Title & Lead -->
    <h2 class="section-title">Log Aktivitas</h2>
    <p class="section-lead mb-3">
        Monitoring dan lacak seluruh rekaman aktivitas sistem dan interaksi pengguna secara real-time.
    </p>

    <!-- Top Summary Statistic Cards (Bawaan Template Stisla) -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Aktivitas</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($statistics['total']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Log Info</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($statistics['info']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Log Warning</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($statistics['warning']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Log Error</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($statistics['error']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row: 3 cols Filter Sidebar + 9 cols Activity Log Data -->
    <div class="row align-items-start">
        <!-- Left Sidebar: Filter Panel (Desktop: 3 cols / col-lg-3 col-md-4 d-none d-md-block) -->
        <div class="col-lg-3 col-md-4 d-none d-md-block">
            <div class="card">
                <div class="card-header">
                    <h4>Filter Log</h4>
                    @if ($search || $activePreset !== 'today' || $filterLevel || $filterModule)
                        <div class="card-header-action">
                            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters" class="btn btn-sm btn-link text-danger font-weight-bold text-decoration-none p-0"><i wire:loading wire:target="resetFilters" class="fas fa-spinner fa-spin mr-1"></i><i wire:loading.remove wire:target="resetFilters" class="fas fa-undo-alt mr-1"></i> Reset</button>
                        </div>
                    @endif
                </div>

                <div class="card-body">
                    @include('livewire.logs.filter-content', ['filterContext' => 'desktop'])
                </div>
            </div>
        </div>

        <!-- Right Side: Activity Log Data (Desktop: 9 cols / col-lg-9 col-md-8 col-12) -->
        <div class="col-lg-9 col-md-8 col-12">
            <!-- Row 1: Header & Controls Card (Riwayat Aktivitas, Show Entries & Search Bar) -->
            <div class="card mb-3">
                <div class="card-header">
                    <h4>Riwayat Aktivitas</h4>
                </div>
                <div class="card-body p-0">
                    <div class="dt-control-wrapper">
                        <div class="dt-show-entries">
                            Show
                            <select wire:model.live="perPage" class="dt-select-entries">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            Entries
                        </div>

                        <div class="dt-search-wrapper">
                            <span class="mr-1">Search:</span>
                            <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari pesan, user, IP, aksi...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Activities Timeline (Stisla Template Style) -->
            <div class="activities mb-3">
                @forelse ($logs as $log)
                    @php
                        $level = strtolower($log->level ?? 'info');
                        $action = strtolower($log->action ?? '');

                        $iconBg = match ($level) {
                            'error', 'critical' => 'bg-danger text-white shadow-danger',
                            'warning', 'warn' => 'bg-warning text-white shadow-warning',
                            'debug' => 'bg-dark text-white',
                            default => match ($action) {
                                'create', 'store' => 'bg-success text-white shadow-success',
                                'delete', 'destroy' => 'bg-danger text-white shadow-danger',
                                'login', 'logout' => 'bg-info text-white shadow-info',
                                default => 'bg-primary text-white shadow-primary',
                            },
                        };

                        $iconClass = match ($action) {
                            'create', 'store' => 'fas fa-plus',
                            'update', 'edit' => 'fas fa-pencil-alt',
                            'delete', 'destroy' => 'fas fa-trash',
                            'login' => 'fas fa-sign-in-alt',
                            'logout' => 'fas fa-sign-out-alt',
                            'view', 'show' => 'fas fa-eye',
                            default => match ($level) {
                                'error', 'critical' => 'fas fa-times-circle',
                                'warning', 'warn' => 'fas fa-exclamation-triangle',
                                default => 'fas fa-clipboard-list',
                            },
                        };
                    @endphp

                    <div class="activity mt-2" wire:key="activity-log-{{ $log->id }}">
                        <div class="activity-icon {{ $iconBg }}">
                            <i class="{{ $iconClass }}"></i>
                        </div>
                        <div class="activity-detail flex-1 min-w-0">
                            <!-- Top: Time, Badges, & Action Button -->
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <div class="d-flex align-items-center flex-wrap gap-1 min-w-0 flex-grow-1">
                                    <span class="text-job text-primary font-weight-bold">{{ $log->created_at->diffForHumans() }}</span>
                                    <span class="bullet"></span>
                                    <span class="text-job text-muted">{{ $log->created_at->format('d M Y, H:i:s') }}</span>
                                    <span class="bullet d-none d-sm-inline"></span>
                                    <span class="{{ $log->level_badge_class }} text-[11px] font-weight-600 px-2 py-0.5">
                                        {{ strtoupper($log->level) }}
                                    </span>
                                    <span class="badge badge-light border text-[11px] font-weight-600 px-2 py-0.5">
                                        {{ strtoupper($log->module) }}
                                    </span>
                                </div>

                                <div class="flex-shrink-0">
                                    <button type="button" wire:click="showDetail({{ $log->id }})" wire:loading.attr="disabled" wire:target="showDetail({{ $log->id }})" class="btn btn-primary btn-sm px-2.5 py-1 text-[12px] font-semibold d-inline-flex align-items-center shadow-none" title="Lihat Detail Context"><i wire:loading wire:target="showDetail({{ $log->id }})" class="fas fa-spinner fa-spin mr-1"></i><i wire:loading.remove wire:target="showDetail({{ $log->id }})" class="fas fa-eye mr-1"></i><span class="d-none d-sm-inline">Detail</span></button>
                                </div>
                            </div>

                            <!-- Middle: Message Headline -->
                            <p class="text-[#191d21] font-weight-600 text-[14px] leading-snug mb-2">
                                {{ $log->message }}
                            </p>

                            <!-- Bottom: User, IP Address & Action Tag -->
                            <div class="d-flex align-items-center flex-wrap gap-3 text-[12px] text-muted pt-2 border-top border-light">
                                <span class="d-inline-flex align-items-center">
                                    <i class="fas fa-user-circle mr-1.5 text-primary"></i>
                                    @if ($log->user)
                                        <strong class="text-[#34395e]">{{ $log->user->name }}</strong>
                                        <span class="text-muted ml-1 d-none d-md-inline">({{ $log->user->email }})</span>
                                    @else
                                        <em class="text-muted">Sistem / Tamu</em>
                                    @endif
                                </span>
                                <span class="d-inline-flex align-items-center font-mono">
                                    <i class="fas fa-network-wired mr-1.5 text-info"></i>{{ $log->ip_address ?: '-' }}
                                </span>
                                <span class="badge badge-secondary text-[11px] font-mono px-2 py-0.5 ml-auto">
                                    {{ $log->action }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="activity">
                        <div class="activity-icon bg-secondary text-white">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="activity-detail flex-1 min-w-0">
                            <div class="text-center py-4 text-muted">
                                <div class="font-weight-600 text-dark">Tidak ada data log aktivitas</div>
                                <small class="text-muted d-block mt-1">
                                    @if ($search || $startDate || $endDate || $filterLevel || $filterModule)
                                        Tidak ditemukan log aktivitas yang sesuai dengan kriteria filter.
                                    @else
                                        Belum ada aktivitas yang tercatat untuk hari ini.
                                    @endif
                                </small>
                                @if ($search || $activePreset !== 'today' || $filterLevel || $filterModule)
                                    <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters" class="btn btn-primary btn-sm mt-3 px-3"><i wire:loading wire:target="resetFilters" class="fas fa-spinner fa-spin mr-1"></i><i wire:loading.remove wire:target="resetFilters" class="fas fa-undo-alt mr-1"></i> Reset ke Hari Ini</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Row 3: Pagination Card -->
            <div class="card mt-1">
                <div class="card-body p-0">
                    <div class="dt-footer-wrapper">
                        <div>
                            Showing {{ $logs->total() > 0 ? $logs->firstItem() : 0 }} to {{ $logs->total() > 0 ? $logs->lastItem() : 0 }} of {{ $logs->total() }} entries
                        </div>
                        <div>
                            {{ $logs->links('livewire.custom-pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button (Filter Modal for Mobile) -->
    <button type="button" class="btn-floating-filter d-md-none" data-toggle="modal" data-target="#filterMobileModal" title="Buka Filter Log" aria-label="Filter Log"><i class="fas fa-filter"></i>@if ($search || $activePreset !== 'today' || $filterLevel || $filterModule)<span class="filter-badge-indicator"></span>@endif</button>

    <!-- Mobile Filter Modal (Terapkan Filter) -->
    @include('livewire.logs.filter-modal')

    <!-- Detail Context Modal -->
    @include('livewire.logs.detail-modal')

    @push('scripts')
        <script>
            (function () {
                function initLogScripts() {
                    // Izinkan focus Select2 di dalam modal Bootstrap
                    if ($.fn.modal && $.fn.modal.Constructor) {
                        $.fn.modal.Constructor.prototype._enforceFocus = function () {};
                    }

                    const initSelect2 = () => {
                        // Desktop: Filter Preset
                        if ($('#filterPreset_desktop').length) {
                            $('#filterPreset_desktop').select2({
                                width: '100%',
                                placeholder: 'Pilih Periode',
                                minimumResultsForSearch: Infinity
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.call('setPreset', val);
                                if ($('#filterPreset_mobile').length && $('#filterPreset_mobile').val() !== val) {
                                    $('#filterPreset_mobile').val(val || 'today').trigger('change.select2');
                                }
                            });
                        }

                        // Desktop: Filter Level
                        if ($('#filterLevel_desktop').length) {
                            $('#filterLevel_desktop').select2({
                                width: '100%',
                                placeholder: 'Semua Tingkat (Level)',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterLevel', val || '');
                                if ($('#filterLevel_mobile').length && $('#filterLevel_mobile').val() !== val) {
                                    $('#filterLevel_mobile').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Desktop: Filter Modul
                        if ($('#filterModule_desktop').length) {
                            $('#filterModule_desktop').select2({
                                width: '100%',
                                placeholder: 'Semua Modul',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterModule', val || '');
                                if ($('#filterModule_mobile').length && $('#filterModule_mobile').val() !== val) {
                                    $('#filterModule_mobile').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Mobile: Filter Preset
                        if ($('#filterPreset_mobile').length) {
                            $('#filterPreset_mobile').select2({
                                dropdownParent: $('#filterMobileModal'),
                                width: '100%',
                                placeholder: 'Pilih Periode',
                                minimumResultsForSearch: Infinity
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.call('setPreset', val);
                                if ($('#filterPreset_desktop').length && $('#filterPreset_desktop').val() !== val) {
                                    $('#filterPreset_desktop').val(val || 'today').trigger('change.select2');
                                }
                            });
                        }

                        // Mobile: Filter Level
                        if ($('#filterLevel_mobile').length) {
                            $('#filterLevel_mobile').select2({
                                dropdownParent: $('#filterMobileModal'),
                                width: '100%',
                                placeholder: 'Semua Tingkat (Level)',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterLevel', val || '');
                                if ($('#filterLevel_desktop').length && $('#filterLevel_desktop').val() !== val) {
                                    $('#filterLevel_desktop').val(val || '').trigger('change.select2');
                                }
                            });
                        }

                        // Mobile: Filter Modul
                        if ($('#filterModule_mobile').length) {
                            $('#filterModule_mobile').select2({
                                dropdownParent: $('#filterMobileModal'),
                                width: '100%',
                                placeholder: 'Semua Modul',
                                allowClear: true
                            }).off('change').on('change', function () {
                                const val = $(this).val();
                                @this.set('filterModule', val || '');
                                if ($('#filterModule_desktop').length && $('#filterModule_desktop').val() !== val) {
                                    $('#filterModule_desktop').val(val || '').trigger('change.select2');
                                }
                            });
                        }
                    };

                    initSelect2();

                    // Listen sync-log-preset event
                    Livewire.on('sync-log-preset', (event) => {
                        const data = Array.isArray(event) ? event[0] : event;
                        const preset = typeof data === 'object' ? data.preset : data;
                        if (preset) {
                            $('#filterPreset_desktop, #filterPreset_mobile').val(preset).trigger('change.select2');
                        }
                    });

                    // Listen reset-log-filters event
                    Livewire.on('reset-log-filters', () => {
                        $('#filterPreset_desktop, #filterPreset_mobile').val('today').trigger('change.select2');
                        $('#filterLevel_desktop, #filterLevel_mobile').val('').trigger('change.select2');
                        $('#filterModule_desktop, #filterModule_mobile').val('').trigger('change.select2');
                    });
                }

                if (typeof window.Livewire !== 'undefined') {
                    initLogScripts();
                } else {
                    document.addEventListener('livewire:init', initLogScripts);
                }
            })();
        </script>
    @endpush
</div>
