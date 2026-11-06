<div>
    <x-slot name="header">
        <h1>Lokasi Kerja</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Master Data</div>
            <div class="breadcrumb-item">Lokasi Kerja</div>
        </div>
    </x-slot>

    <!-- Header Section & Action Button -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2 mb-md-3">
        <div class="mr-md-3">
            <h2 class="section-title mt-0">Lokasi Kerja & Geofencing</h2>
            <p class="section-lead mb-0">
                Kelola titik lokasi absensi, koordinat GPS, dan radius toleransi kehadiran (geofencing) karyawan.
            </p>
        </div>

        <!-- Tombol Aksi Utama di Kanan Atas -->
        <div class="mt-3 mt-md-0 flex-shrink-0 d-flex align-items-center" style="gap: 8px;">
            <button type="button" wire:click="openAssignmentModal" wire:loading.attr="disabled" class="btn btn-outline-primary d-inline-flex align-items-center font-weight-bold shadow-sm" style="border-radius: 8px; padding: 9px 16px; font-size: 13px;">
                <i class="fas fa-user-tag mr-1.5"></i> Penugasan Site
            </button>
            <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn btn-primary d-inline-flex align-items-center font-weight-bold shadow-sm" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 9px 16px; font-size: 13px;">
                <i wire:loading wire:target="create" class="fas fa-spinner fa-spin mr-1.5"></i>
                <i wire:loading.remove wire:target="create" class="fas fa-plus mr-1.5"></i> Tambah Lokasi
            </button>
        </div>
    </div>

    <!-- 4 Modern Executive Metric Cards -->
    <div class="cash-advance-stat-grid mb-4">
        <!-- 1. Total Lokasi (Biru) -->
        <div class="card cash-advance-stat-card shadow-sm" style="border-radius: 12px; border: 1px solid #eaedf2; background: #ffffff;">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Total Lokasi Terdaftar">Total Lokasi</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-dark">{{ $totalLocations }}</div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-map-pin"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Titik geofence terdaftar
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Lokasi Aktif (Hijau) -->
        <div class="card cash-advance-stat-card shadow-sm" style="border-radius: 12px; border: 1px solid #eaedf2; background: #ffffff;">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Lokasi Aktif">Lokasi Aktif</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-success">{{ $activeLocations }}</div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-green">
                            <i class="fas fa-check"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Siap validasi absensi
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Karyawan Terdistribusi (Amber) -->
        <div class="card cash-advance-stat-card shadow-sm" style="border-radius: 12px; border: 1px solid #eaedf2; background: #ffffff;">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                    <i class="fas fa-users"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Karyawan Terdistribusi">Karyawan Terdistribusi</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-warning">{{ $distributedEmployees }}</div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-amber">
                            <i class="fas fa-id-badge"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Karyawan memiliki lokasi
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Site / Proyek Luar (Ungu) -->
        <div class="card cash-advance-stat-card shadow-sm" style="border-radius: 12px; border: 1px solid #eaedf2; background: #ffffff;">
            <div class="card-body cash-advance-stat-body">
                <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                    <i class="fas fa-building"></i>
                </div>
                <div class="cash-advance-stat-content">
                    <div class="cash-advance-stat-title" title="Site / Proyek Luar">Site / Proyek Luar</div>
                    <div class="cash-advance-stat-value-row">
                        <div class="cash-advance-stat-value text-primary">{{ $externalSites }}</div>
                        <span class="cash-advance-stat-badge cash-advance-stat-badge-blue">
                            <i class="fas fa-hard-hat"></i>
                        </span>
                    </div>
                    <div class="cash-advance-stat-subtext">
                        Cabang, workshop & proyek
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Data Table Card -->
    <div class="card shadow-sm mb-4" style="background-color: #ffffff; border-radius: 12px; border: 1px solid #eaedf2;">
        <div class="card-header bg-white border-bottom py-3">
            <h4 class="text-dark mb-0" style="font-weight: 600; font-size: 16px;">
                Daftar Lokasi Kerja
            </h4>
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
                    <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama atau alamat...">
                </div>
            </div>

            <!-- Full-Width Data Table -->
            <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-center text-nowrap" style="width: 50px;">NO</th>
                            <th class="text-nowrap" style="min-width: 180px;">NAMA LOKASI</th>
                            <th class="text-nowrap" style="min-width: 220px;">ALAMAT</th>
                            <th class="text-nowrap" style="width: 170px;">KOORDINAT</th>
                            <th class="text-center text-nowrap" style="width: 90px;">RADIUS</th>
                            <th class="text-center text-nowrap" style="width: 100px;">KARYAWAN</th>
                            <th class="text-center text-nowrap" style="width: 100px;">STATUS</th>
                            <th class="text-center text-nowrap" style="width: 140px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workLocations as $index => $location)
                            <tr wire:key="work-location-{{ $location->id }}">
                                <td class="text-center font-weight-600 text-[#64748b] align-middle">
                                    {{ $workLocations->firstItem() + $index }}
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-600 text-[#1e293b]" style="font-size: 13.5px;">
                                        {{ $location->name }}
                                    </div>
                                    @if(str_contains(strtolower($location->name), 'pusat'))
                                        <span class="badge" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 10.5px; padding: 2px 6px;">Kantor Pusat</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="text-[#334155]" style="font-size: 13px; max-width: 280px; line-height: 1.4;">
                                        {{ $location->address ?: '-' }}
                                    </div>
                                </td>
                                <td class="align-middle text-nowrap">
                                    <div class="text-[#334155] font-mono" style="font-size: 12px;">
                                        <i class="fas fa-map-marker-alt text-danger mr-1"></i> {{ number_format($location->latitude, 5) }}, {{ number_format($location->longitude, 5) }}
                                    </div>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <span class="badge" style="background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-weight: 500; font-size: 11.5px; padding: 4px 8px;">
                                        {{ $location->radius_meters }} m
                                    </span>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <span class="badge" style="background-color: #eff6ff; color: #0284c7; border: 1px solid #bae6fd; font-weight: 600; font-size: 11.5px; padding: 4px 8px;">
                                        <i class="fas fa-users mr-1"></i> {{ $location->employees_count }}
                                    </span>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <button type="button" wire:click="toggleStatus({{ $location->id }})" wire:loading.attr="disabled" wire:target="toggleStatus({{ $location->id }})" class="btn btn-sm p-0 border-0 shadow-none" title="Klik untuk ubah status aktif/nonaktif">
                                        @if ($location->is_active)
                                            <span class="badge" style="background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-weight: 500; font-size: 11px; padding: 4px 8px; cursor: pointer;">
                                                <i class="fas fa-check-circle mr-1"></i> Aktif
                                            </span>
                                        @else
                                            <span class="badge" style="background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-weight: 500; font-size: 11px; padding: 4px 8px; cursor: pointer;">
                                                <i class="fas fa-times-circle mr-1"></i> Nonaktif
                                            </span>
                                        @endif
                                    </button>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <button type="button" wire:click="openAssignmentModal({{ $location->id }})" class="btn btn-warning btn-action mr-1" title="Tugaskan Karyawan ke Lokasi Ini" style="background-color: #f59e0b; border-color: #f59e0b; color: #ffffff;">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                    <a href="{{ $location->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-info btn-action mr-1" title="Buka di Google Maps" style="background-color: #0284c7; border-color: #0284c7;">
                                        <i class="fas fa-map-marked-alt"></i>
                                    </a>
                                    <button type="button" wire:click="edit({{ $location->id }})" wire:loading.attr="disabled" wire:target="edit({{ $location->id }})" class="btn btn-primary btn-action mr-1" title="Edit Data" style="background-color: #0b52aa; border-color: #0b52aa;">
                                        <i wire:loading wire:target="edit({{ $location->id }})" class="fas fa-spinner fa-spin"></i>
                                        <i wire:loading.remove wire:target="edit({{ $location->id }})" class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $location->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $location->id }})" class="btn btn-danger btn-action" title="Hapus Data">
                                        <i wire:loading wire:target="confirmDelete({{ $location->id }})" class="fas fa-spinner fa-spin"></i>
                                        <i wire:loading.remove wire:target="confirmDelete({{ $location->id }})" class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="fas fa-map-marked-alt text-muted mb-2" style="font-size: 32px;"></i>
                                        <div class="font-weight-600 mt-2 text-dark">Tidak ada lokasi kerja ditemukan</div>
                                        <small class="text-muted">
                                            @if($search)
                                                Tidak ada lokasi yang sesuai dengan kata kunci "{{ $search }}".
                                            @else
                                                Belum ada data lokasi kerja yang tersedia. Silakan klik tombol "Tambah Lokasi" untuk menambahkan titik pertama.
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
                    Showing {{ $workLocations->total() > 0 ? $workLocations->firstItem() : 0 }} to {{ $workLocations->total() > 0 ? $workLocations->lastItem() : 0 }} of {{ $workLocations->total() }} entries
                </div>
                <div>
                    {{ $workLocations->links('livewire.custom-pagination') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Card Daftar Penugasan Karyawan ke Site (Aktif & Mendatang) -->
    <div class="card shadow-sm mb-4" style="background-color: #ffffff; border-radius: 12px; border: 1px solid #eaedf2;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h4 class="text-dark mb-0" style="font-weight: 600; font-size: 16px;">
                <i class="fas fa-calendar-check text-primary mr-1.5"></i> Daftar Penugasan Site Aktif & Mendatang
            </h4>
            <span class="badge" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11.5px; padding: 4px 10px; border-radius: 20px;">
                {{ $activeAssignments->count() }} Penugasan Terjadwal
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-center text-nowrap" style="width: 50px;">NO</th>
                            <th class="text-nowrap" style="min-width: 200px;">KARYAWAN</th>
                            <th class="text-nowrap" style="min-width: 180px;">LOKASI / SITE</th>
                            <th class="text-nowrap" style="min-width: 190px;">PERIODE PENUGASAN</th>
                            <th class="text-center text-nowrap" style="width: 120px;">STATUS</th>
                            <th class="text-nowrap" style="min-width: 180px;">KETERANGAN</th>
                            <th class="text-center text-nowrap" style="width: 80px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activeAssignments as $idx => $assignment)
                            @php
                                $todayStr = now()->toDateString();
                                $startDateStr = $assignment->start_date->format('Y-m-d');
                                $endDateStr = $assignment->end_date->format('Y-m-d');
                                $isActiveToday = $todayStr >= $startDateStr && $todayStr <= $endDateStr;
                                $isUpcoming = $todayStr < $startDateStr;
                                $durationDays = $assignment->start_date->diffInDays($assignment->end_date) + 1;
                            @endphp
                            <tr wire:key="assignment-row-{{ $assignment->id }}">
                                <td class="text-center font-weight-600 text-[#64748b] align-middle">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-600 text-[#1e293b]" style="font-size: 13.5px;">
                                        {{ $assignment->employee?->full_name ?? 'Karyawan Tidak Ditemukan' }}
                                    </div>
                                    <div class="text-[#64748b]" style="font-size: 11.5px;">
                                        {{ $assignment->employee?->position?->name ?? 'Staff' }} &bull; {{ $assignment->employee?->department?->name ?? 'Umum' }}
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-600 text-[#1e293b]" style="font-size: 13px;">
                                        <i class="fas fa-map-marker-alt text-primary mr-1"></i>
                                        {{ $assignment->workLocation?->name ?? 'Lokasi Terhapus' }}
                                    </div>
                                    <small class="text-[#64748b]">
                                        Radius: {{ $assignment->workLocation?->radius_meters ?? 50 }}m
                                    </small>
                                </td>
                                <td class="align-middle text-nowrap">
                                    <div class="text-[#1e293b] font-weight-500" style="font-size: 12.5px;">
                                        <i class="far fa-calendar-alt text-muted mr-1"></i>
                                        {{ $assignment->start_date->translatedFormat('d M Y') }} - {{ $assignment->end_date->translatedFormat('d M Y') }}
                                    </div>
                                    <span class="badge" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 10.5px; padding: 2px 6px;">
                                        {{ $durationDays }} Hari Penugasan
                                    </span>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    @if ($isActiveToday)
                                        <span class="badge" style="background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-weight: 600; font-size: 11px; padding: 4px 8px;">
                                            <i class="fas fa-check-circle mr-1"></i> Aktif Hari Ini
                                        </span>
                                    @elseif ($isUpcoming)
                                        <span class="badge" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 500; font-size: 11px; padding: 4px 8px;">
                                            <i class="far fa-clock mr-1"></i> Mendatang
                                        </span>
                                    @else
                                        <span class="badge" style="background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; font-weight: 500; font-size: 11px; padding: 4px 8px;">
                                            Selesai
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="text-[#334155]" style="font-size: 12.5px; max-width: 250px; line-height: 1.35;">
                                        {{ $assignment->notes ?: '-' }}
                                    </div>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <button type="button" wire:click="confirmDeleteAssignment({{ $assignment->id }})" class="btn btn-danger btn-action" title="Batalkan / Hapus Penugasan">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <div class="py-2">
                                        <i class="fas fa-calendar-times text-muted mb-2" style="font-size: 26px;"></i>
                                        <div class="font-weight-600 text-dark" style="font-size: 13.5px;">Belum Ada Penugasan Site Aktif</div>
                                        <small class="text-muted">
                                            Gunakan tombol "Penugasan Site" di atas untuk menugaskan karyawan ke lokasi/proyek luar untuk rentang tanggal tertentu.
                                        </small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Floating Action Button (Tambah Lokasi) -->
    <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn-floating-add" title="Tambah Lokasi Kerja" aria-label="Tambah Lokasi Kerja">
        <i wire:loading wire:target="create" class="fas fa-spinner fa-spin"></i>
        <i wire:loading.remove wire:target="create" class="fas fa-plus"></i>
    </button>

    <!-- Modal Form (Add / Edit Lokasi) -->
    @include('livewire.work-locations.form-modal')

    <!-- Modal Form (Penugasan Site Karyawan) -->
    @include('livewire.work-locations.assignment-modal')
</div>
