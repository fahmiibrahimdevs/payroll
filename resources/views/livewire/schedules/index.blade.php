<div>
    <x-slot name="header">
        <h1>Jadwal Kerja (Roster)</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('attendances.index') }}">Presensi</a></div>
            <div class="breadcrumb-item">Jadwal Kerja</div>
        </div>
    </x-slot>

    <!-- Header Section (Section Title & Section Lead Standar Stisla) & Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div>
            <h2 class="section-title mt-0">Roster Jadwal Kerja &amp; Libur Bergilir</h2>
            <p class="section-lead mb-0">
                Kelola jadwal masuk dan rotasi hari libur (Off Day) bulanan sesuai jadwal buka toko di hari Minggu.
            </p>
        </div>

        <div class="mt-2 mt-md-0 flex-shrink-0 d-flex align-items-center flex-wrap" style="gap: 8px;">
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="btn font-weight-bold d-inline-flex align-items-center text-white" style="background: #10b981; border: none; border-radius: 8px; padding: 7px 14px; font-size: 12px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25); transition: all 0.15s;">
                <span wire:loading.remove wire:target="exportExcel"><i class="fas fa-file-excel mr-1.5"></i> Export Excel</span>
                <span wire:loading wire:target="exportExcel"><i class="fas fa-spinner fa-spin mr-1.5"></i> Mengunduh...</span>
            </button>

            @if (auth()->user()?->hasRole('admin'))
                <button type="button" wire:click="copyFromPreviousMonth" wire:confirm="Salin pola jadwal libur dari bulan sebelumnya ke bulan ini?" wire:loading.attr="disabled" wire:target="copyFromPreviousMonth" class="btn font-weight-bold d-inline-flex align-items-center text-white" style="background: #3b82f6; border: none; border-radius: 8px; padding: 7px 14px; font-size: 12px; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.25); transition: all 0.15s;">
                    <span wire:loading.remove wire:target="copyFromPreviousMonth"><i class="fas fa-copy mr-1.5"></i> Salin Bulan Lalu</span>
                    <span wire:loading wire:target="copyFromPreviousMonth"><i class="fas fa-spinner fa-spin mr-1.5"></i> Menyalin Jadwal...</span>
                </button>

                <button type="button" wire:click="openImportModal" wire:loading.attr="disabled" wire:target="openImportModal" class="btn font-weight-bold d-inline-flex align-items-center text-white" style="background: #1d4ed8; border: none; border-radius: 8px; padding: 7px 16px; font-size: 12px; box-shadow: 0 2px 6px rgba(29, 78, 216, 0.25); transition: all 0.15s;">
                    <span wire:loading.remove wire:target="openImportModal"><i class="fas fa-file-upload mr-1.5"></i> Import Excel</span>
                    <span wire:loading wire:target="openImportModal"><i class="fas fa-spinner fa-spin mr-1.5"></i> Membuka...</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Alert Status Mode Perusahaan -->
    @if (!$company->isRosterMode())
        <div class="alert alert-warning border-0 d-flex align-items-center mb-2 py-2 px-3" style="background: #fffbeb; color: #b45309; border-radius: 10px; font-size: 12px;">
            <i class="fas fa-exclamation-triangle mr-2 text-warning"></i>
            <div>
                <strong>Perhatian:</strong> Mode Penjadwalan saat ini masih diatur ke <em>Standar Kantor</em>.
                Ubah ke <strong>Roster Toko</strong> di menu <a href="{{ route('company-profile.index') }}" class="font-weight-bold text-dark text-underline">Profil Perusahaan &gt; Penggajian</a>.
            </div>
        </div>
    @endif

    <!-- Card Filter & Navigasi Bulan (Compact & Clean White) -->
    <div class="card border-0 mb-3 roster-toolbar-card">
        <div class="card-body p-2 px-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center flex-wrap" style="gap: 10px;">
                <!-- Navigasi Bulan & Tahun -->
                <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                    <div class="roster-toolbar-month">
                        <button type="button" wire:click="prevMonth" wire:loading.attr="disabled" wire:target="prevMonth, nextMonth, currentMonth" class="roster-toolbar-month-btn" title="Bulan Sebelumnya">
                            <span wire:loading.remove wire:target="prevMonth"><i class="fas fa-chevron-left"></i></span>
                            <span wire:loading wire:target="prevMonth"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                        <div class="roster-toolbar-month-text">
                            <span wire:loading.remove wire:target="prevMonth, nextMonth, currentMonth">
                                <i class="far fa-calendar-alt mr-2 text-primary"></i> {{ $monthName }}
                            </span>
                            <span wire:loading wire:target="prevMonth, nextMonth, currentMonth" class="text-primary font-weight-bold">
                                <i class="fas fa-spinner fa-spin mr-1.5"></i> Memuat...
                            </span>
                        </div>
                        <button type="button" wire:click="nextMonth" wire:loading.attr="disabled" wire:target="prevMonth, nextMonth, currentMonth" class="roster-toolbar-month-btn" title="Bulan Berikutnya">
                            <span wire:loading.remove wire:target="nextMonth"><i class="fas fa-chevron-right"></i></span>
                            <span wire:loading wire:target="nextMonth"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    </div>

                    <button type="button" wire:click="currentMonth" wire:loading.attr="disabled" wire:target="prevMonth, nextMonth, currentMonth" class="btn roster-toolbar-btn-current">
                        <span wire:loading.remove wire:target="currentMonth"><i class="fas fa-undo-alt mr-1.5" style="font-size: 11px;"></i> Bulan Ini</span>
                        <span wire:loading wire:target="currentMonth"><i class="fas fa-spinner fa-spin mr-1.5" style="font-size: 11px;"></i> Memuat...</span>
                    </button>
                </div>

                <!-- Filter & Sorting Karyawan -->
                <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                    <!-- Dropdown Sorting Karyawan -->
                    <div style="min-width: 195px; position: relative;">
                        <select wire:model.live="sortBy" wire:loading.attr="disabled" wire:target="sortBy" class="form-control roster-toolbar-select" title="Urutan Karyawan">
                            <option value="custom">&#8645; Urutan Kustom (Bisa di-Drag)</option>
                            <option value="name_asc">&#8595; Nama (A - Z)</option>
                            <option value="name_desc">&#8593; Nama (Z - A)</option>
                            <option value="position_asc">&#9881; Jobdesk / Posisi</option>
                            <option value="department_asc">&#127970; Departemen</option>
                        </select>
                        <div wire:loading wire:target="sortBy" style="position: absolute; right: 28px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <i class="fas fa-spinner fa-spin text-primary" style="font-size: 11px;"></i>
                        </div>
                    </div>

                    <!-- Filter Departemen -->
                    <div style="min-width: 165px; position: relative;">
                        <select wire:model.live="departmentId" wire:loading.attr="disabled" wire:target="departmentId" class="form-control roster-toolbar-select" title="Filter Departemen">
                            <option value="">Semua Departemen</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <div wire:loading wire:target="departmentId" style="position: absolute; right: 28px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <i class="fas fa-spinner fa-spin text-primary" style="font-size: 11px;"></i>
                        </div>
                    </div>

                    <!-- Search Box -->
                    <div style="min-width: 200px;">
                        <div class="input-group roster-toolbar-search">
                            <div class="input-group-prepend">
                                <span class="input-group-text roster-toolbar-search-icon">
                                    <span wire:loading.remove wire:target="search"><i class="fas fa-search" style="font-size: 11px;"></i></span>
                                    <span wire:loading wire:target="search"><i class="fas fa-spinner fa-spin text-primary" style="font-size: 11px;"></i></span>
                                </span>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control roster-toolbar-search-input" placeholder="Cari nama karyawan...">
                        </div>
                    </div>

                    <!-- Per Page -->
                    <div style="width: 85px;">
                        <select wire:model.live="perPage" class="form-control roster-toolbar-select" title="Jumlah baris karyawan">
                            <option value="0">Semua</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Roster Grid (Excel-like Full Cell Colors, Compact Row Height 26px, Drag & Drop) -->
    <div class="card border-0 mb-3" style="background: #ffffff; border-radius: 12px; box-shadow: 0 2px 14px -2px rgba(0, 0, 0, 0.06); overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 760px; overflow-x: auto;">
                <table class="table mb-0 text-center roster-table" style="border-collapse: collapse; background: #ffffff;">
                    <thead class="sticky-top" style="z-index: 10;">
                        <!-- Baris Nama Hari (Clean White & Kontras) -->
                        <tr style="background: #ffffff;">
                            <th class="sticky-col first-col align-middle text-center" rowspan="2" style="width: 46px; min-width: 46px; z-index: 20; color: #475569; font-weight: 700; font-size: 10px; border-bottom: 2px solid #cbd5e1; background: #ffffff !important;">
                                NO
                            </th>
                            <th class="sticky-col second-col align-middle text-left" rowspan="2" style="width: 175px; min-width: 175px; max-width: 175px; z-index: 20; font-weight: 700; font-size: 11px; letter-spacing: 0.04em; border-bottom: 2px solid #cbd5e1; background: #ffffff !important; color: #334155; padding-left: 8px !important;">
                                NAMA KARYAWAN
                            </th>
                            <th class="sticky-col third-col align-middle text-left" rowspan="2" style="width: 110px; min-width: 110px; max-width: 110px; z-index: 20; font-weight: 700; font-size: 11px; letter-spacing: 0.04em; border-bottom: 2px solid #cbd5e1; background: #ffffff !important; color: #334155; padding-left: 8px !important;">
                                JOBDESK
                            </th>

                            @foreach ($roster['dates'] as $day => $meta)
                                @if ($meta['is_sunday'])
                                    <th class="p-0 font-weight-bold text-nowrap sunday-header align-middle"
                                        style="width: 29px; min-width: 29px; max-width: 29px; font-size: 10px; background: #dc2626 !important; color: #ffffff !important; border: 1px solid #b91c1c; height: 20px;"
                                        title="Hari Minggu">
                                        {{ $meta['day_name'] }}
                                    </th>
                                @elseif ($meta['is_holiday'])
                                    <th class="p-0 font-weight-bold text-nowrap align-middle"
                                        style="width: 29px; min-width: 29px; max-width: 29px; font-size: 10px; background: #ef4444 !important; color: #ffffff !important; border: 1px solid #dc2626; height: 20px;"
                                        title="{{ $meta['holiday_name'] }}">
                                        {{ $meta['day_name'] }}
                                    </th>
                                @else
                                    <th class="p-0 font-weight-bold text-nowrap align-middle"
                                        style="width: 29px; min-width: 29px; max-width: 29px; font-size: 10px; background: #ffffff !important; color: #475569; border: 1px solid #e2e8f0; height: 20px;">
                                        {{ $meta['day_name'] }}
                                    </th>
                                @endif
                            @endforeach

                            <th class="align-middle p-0" rowspan="2" style="width: 48px; min-width: 48px; color: #047857; font-weight: 700; font-size: 10.5px; border-bottom: 2px solid #cbd5e1; background: #ffffff !important;">MASUK</th>
                            <th class="align-middle p-0" rowspan="2" style="width: 48px; min-width: 48px; color: #be123c; font-weight: 700; font-size: 10.5px; border-bottom: 2px solid #cbd5e1; background: #ffffff !important;">LIBUR</th>
                        </tr>

                        <!-- Baris Angka Tanggal (Biru Muda untuk Hari Biasa, Merah untuk Hari Minggu / Libur) -->
                        <tr style="background: #ffffff;">
                            @foreach ($roster['dates'] as $day => $meta)
                                @if ($meta['is_sunday'])
                                    <th class="p-0 font-weight-bold align-middle"
                                        style="font-size: 11px; background: #b91c1c !important; color: #ffffff !important; border: 1px solid #991b1b; height: 20px;"
                                        title="Hari Minggu (Tgl {{ $day }})">
                                        {{ $day }}
                                    </th>
                                @elseif ($meta['is_holiday'])
                                    <th class="p-0 font-weight-bold align-middle"
                                        style="font-size: 11px; background: #dc2626 !important; color: #ffffff !important; border: 1px solid #b91c1c; height: 20px;"
                                        title="{{ $meta['holiday_name'] }} (Tgl {{ $day }})">
                                        {{ $day }}
                                    </th>
                                @else
                                    <th class="p-0 font-weight-bold align-middle"
                                        style="font-size: 11px; background: #e0f2fe !important; color: #0369a1 !important; border: 1px solid #bae6fd; height: 20px;"
                                        title="Tanggal {{ $day }}">
                                        {{ $day }}
                                    </th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>

                    <tbody id="rosterTableBody">
                        @forelse ($roster['employees'] as $idx => $emp)
                            @php
                                $empColor = $emp['color'] ?? 'none';
                            @endphp
                            <tr wire:key="emp-row-{{ $emp['id'] }}"
                                class="roster-row"
                                data-emp-id="{{ $emp['id'] }}"
                                draggable="{{ auth()->user()?->hasRole('admin') ? 'true' : 'false' }}">

                                <!-- Kolom NO: Handle Drag (Garis Tiga) & Angka -->
                                <td class="sticky-col first-col align-middle text-center p-0" style="background: #ffffff; width: 46px; min-width: 46px;">
                                    <div class="d-flex align-items-center justify-content-center" style="gap: 4px; height: 100%;">
                                        @if (auth()->user()?->hasRole('admin'))
                                            <span class="drag-handle text-muted" title="Klik & geser baris ini untuk menata urutan" style="cursor: grab;">
                                                <i class="fas fa-bars" style="font-size: 11px;"></i>
                                            </span>
                                        @endif
                                        <span class="row-num font-weight-bold text-dark">{{ $idx + 1 }}</span>
                                    </div>
                                </td>

                                <!-- Kolom NAMA KARYAWAN (Dengan pemilih warna dasar individual & 1 warna dengan Jobdesk) -->
                                <td class="sticky-col second-col align-middle text-left emp-cell-{{ $emp['id'] }} emp-bg-{{ $empColor }}" style="padding: 0 6px 0 8px !important; width: 175px; min-width: 175px; max-width: 175px;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="font-weight-bold text-truncate" style="font-size: 11.5px; line-height: 1.2;" title="{{ $emp['name'] }}">
                                            {{ $emp['name'] }}
                                        </span>

                                        @if (auth()->user()?->hasRole('admin'))
                                            <!-- Tombol Pemilih Warna -->
                                            <button type="button"
                                                wire:click="openColorModal({{ $emp['id'] }}, '{{ addslashes($emp['name']) }}')"
                                                class="btn-color-dot flex-shrink-0 ml-1"
                                                title="Klik untuk memilih warna baris (Nama & Jobdesk)">
                                                <span class="color-dot-indicator emp-bg-{{ $empColor }}"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Kolom JOBDESK (Teks Polos & Warna Senada dengan Nama Karyawan) -->
                                <td class="sticky-col third-col align-middle text-left emp-cell-{{ $emp['id'] }} emp-bg-{{ $empColor }}" style="padding: 0 8px !important; width: 110px; min-width: 110px; max-width: 110px;">
                                    <div class="text-truncate font-weight-500" style="font-size: 11px; line-height: 1.2;" title="{{ $emp['jobdesk'] }}">
                                        {{ $emp['jobdesk'] }}
                                    </div>
                                </td>

                                <!-- Kolom Tanggal: Full Cell Warna Hijau (Masuk) & Merah (OFF) -->
                                @foreach ($roster['dates'] as $day => $meta)
                                    @php
                                        $dStr = $meta['date'];
                                        $sch = $emp['schedules'][$dStr] ?? null;
                                        $isOff = $sch && $sch['status'] === \App\Models\EmployeeSchedule::STATUS_OFF;
                                    @endphp
                                    <td wire:key="cell-{{ $emp['id'] }}-{{ $dStr }}"
                                        @if (auth()->user()?->hasRole('admin'))
                                            wire:click="toggleDay({{ $emp['id'] }}, '{{ $dStr }}')"
                                        @endif
                                        class="roster-cell align-middle text-center p-0 {{ $isOff ? 'cell-off' : 'cell-work' }}"
                                        @if (!auth()->user()?->hasRole('admin'))
                                            style="cursor: default;"
                                        @endif
                                        title="{{ $emp['name'] }} (Tgl {{ $day }}): {{ auth()->user()?->hasRole('admin') ? 'Klik untuk Masuk/Libur' : ($isOff ? 'Libur' : 'Masuk') }}">
                                        <div class="roster-cell-inner d-flex align-items-center justify-content-center w-100 h-100">
                                            @if ($isOff)
                                                <span class="off-text">OFF</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach

                                <!-- Kolom Total Masuk & Libur (Ringkas) -->
                                <td class="align-middle p-0 font-weight-bold text-success" style="background: #ffffff; border-left: 2px solid #e2e8f0; font-size: 11px;">
                                    {{ $emp['total_work_days'] }} hr
                                </td>
                                <td class="align-middle p-0 font-weight-bold text-danger" style="background: #ffffff; font-size: 11px;">
                                    {{ $emp['total_off_days'] }} hr
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($roster['dates']) + 5 }}" class="text-center py-4 text-muted" style="background: #ffffff;">
                                    <i class="fas fa-users-slash fa-lg mb-1 d-block text-muted" style="opacity: 0.5;"></i>
                                    Tidak ada data karyawan yang cocok dengan kriteria pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer: Legenda & Total Karyawan (Compact & Dominan Putih) -->
            <div class="px-3 py-2 border-top d-flex flex-column flex-md-row justify-content-between align-items-md-center" style="background: #ffffff; border-color: #f1f5f9 !important; gap: 8px; font-size: 12px;">
                <div class="d-flex align-items-center flex-wrap" style="gap: 16px;">
                    <div class="d-flex align-items-center">
                        <span class="d-inline-block rounded mr-1.5" style="width: 14px; height: 14px; background-color: #22c55e;"></span>
                        <span class="font-weight-600" style="color: #334155; font-size: 11.5px;">Masuk Kerja (Work)</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="d-inline-block rounded mr-1.5" style="width: 14px; height: 14px; background-color: #ef4444;"></span>
                        <span class="font-weight-600" style="color: #334155; font-size: 11.5px;">Libur Bergilir (Off Day)</span>
                    </div>
                    <div class="d-flex align-items-center text-muted" style="color: #64748b !important; font-size: 11px;">
                        <i class="fas fa-hand-pointer mr-1 text-primary"></i>
                        <span>Klik tanggal untuk ubah Masuk &harr; Libur. Tarik <i class="fas fa-bars mx-0.5"></i> untuk drag urutan baris.</span>
                    </div>
                </div>

                <div class="font-weight-600" style="color: #475569; font-size: 11.5px;">
                    Total Karyawan: <span class="badge px-2 py-1 ml-1" style="background: #f1f5f9; color: #0f172a; font-size: 11px; border-radius: 6px;">{{ count($roster['employees']) }} Orang</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pemilih Warna Karyawan (Clean Livewire Modal) -->
    @if ($selectedEmployeeForColor !== null)
        <div class="modal fade show d-block"
             tabindex="-1"
             role="dialog"
             style="background-color: rgba(15, 23, 42, 0.45); z-index: 1060; backdrop-filter: blur(2px);">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 340px;">
                <div class="modal-content border-0" style="border-radius: 14px; box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.25); overflow: hidden; background: #ffffff;">
                    <div class="modal-header border-0 px-3 pt-3 pb-1 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center mr-2 rounded-circle" style="width: 30px; height: 30px; background: #eff6ff; color: #2563eb;">
                                <i class="fas fa-palette" style="font-size: 13px;"></i>
                            </div>
                            <div>
                                <h6 class="modal-title font-weight-bold mb-0" style="font-size: 13.5px; color: #0f172a;">
                                    Pilih Warna Baris
                                </h6>
                                <small class="text-muted d-block font-weight-bold text-truncate" style="max-width: 220px; font-size: 11px; color: #2563eb !important;">
                                    {{ $selectedEmployeeName }}
                                </small>
                            </div>
                        </div>
                        <button type="button" class="close text-muted" wire:click="closeColorModal" aria-label="Close" style="font-size: 18px; outline: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body px-3 py-3">
                        <p class="text-muted mb-2.5" style="font-size: 12px; color: #64748b !important;">
                            Warna yang dipilih akan mewarnai kolom <strong>Nama Karyawan</strong> dan <strong>Jobdesk</strong>:
                        </p>

                        <div class="row no-gutters">
                            <!-- Putih / Polos -->
                            <div class="col-6 p-1">
                                <button type="button" wire:click="setEmployeeColor({{ $selectedEmployeeForColor }}, 'none')" wire:loading.attr="disabled" wire:target="setEmployeeColor" class="btn btn-block text-left d-flex align-items-center p-2 rounded border" style="font-size: 12px; font-weight: 600; color: #334155; background: #ffffff;">
                                    <span class="d-inline-block rounded mr-2 border" style="width: 18px; height: 18px; background: #ffffff;"></span>
                                    <span>Polos (Putih)</span>
                                    <span wire:loading wire:target="setEmployeeColor({{ $selectedEmployeeForColor }}, 'none')" class="spinner-border spinner-border-sm ml-auto text-primary" style="width: 13px; height: 13px; border-width: 2px;"></span>
                                </button>
                            </div>
                            <!-- Kuning Dasar -->
                            <div class="col-6 p-1">
                                <button type="button" wire:click="setEmployeeColor({{ $selectedEmployeeForColor }}, 'yellow')" wire:loading.attr="disabled" wire:target="setEmployeeColor" class="btn btn-block text-left d-flex align-items-center p-2 rounded border-0" style="font-size: 12px; font-weight: 600; color: #713f12; background: #ffeb3b;">
                                    <span class="d-inline-block rounded mr-2 border border-dark" style="width: 18px; height: 18px; background: #ffeb3b;"></span>
                                    <span>Kuning</span>
                                    <span wire:loading wire:target="setEmployeeColor({{ $selectedEmployeeForColor }}, 'yellow')" class="spinner-border spinner-border-sm ml-auto text-dark" style="width: 13px; height: 13px; border-width: 2px;"></span>
                                </button>
                            </div>
                            <!-- Biru Dasar -->
                            <div class="col-6 p-1">
                                <button type="button" wire:click="setEmployeeColor({{ $selectedEmployeeForColor }}, 'blue')" wire:loading.attr="disabled" wire:target="setEmployeeColor" class="btn btn-block text-left d-flex align-items-center p-2 rounded border-0 text-white" style="font-size: 12px; font-weight: 600; background: #38bdf8;">
                                    <span class="d-inline-block rounded mr-2 border border-white" style="width: 18px; height: 18px; background: #38bdf8;"></span>
                                    <span>Biru</span>
                                    <span wire:loading wire:target="setEmployeeColor({{ $selectedEmployeeForColor }}, 'blue')" class="spinner-border spinner-border-sm ml-auto text-white" style="width: 13px; height: 13px; border-width: 2px;"></span>
                                </button>
                            </div>
                            <!-- Hijau Dasar -->
                            <div class="col-6 p-1">
                                <button type="button" wire:click="setEmployeeColor({{ $selectedEmployeeForColor }}, 'green')" wire:loading.attr="disabled" wire:target="setEmployeeColor" class="btn btn-block text-left d-flex align-items-center p-2 rounded border-0 text-white" style="font-size: 12px; font-weight: 600; background: #4ade80;">
                                    <span class="d-inline-block rounded mr-2 border border-white" style="width: 18px; height: 18px; background: #4ade80;"></span>
                                    <span>Hijau</span>
                                    <span wire:loading wire:target="setEmployeeColor({{ $selectedEmployeeForColor }}, 'green')" class="spinner-border spinner-border-sm ml-auto text-white" style="width: 13px; height: 13px; border-width: 2px;"></span>
                                </button>
                            </div>
                            <!-- Oranye Dasar -->
                            <div class="col-6 p-1">
                                <button type="button" wire:click="setEmployeeColor({{ $selectedEmployeeForColor }}, 'orange')" wire:loading.attr="disabled" wire:target="setEmployeeColor" class="btn btn-block text-left d-flex align-items-center p-2 rounded border-0 text-white" style="font-size: 12px; font-weight: 600; background: #fb923c;">
                                    <span class="d-inline-block rounded mr-2 border border-white" style="width: 18px; height: 18px; background: #fb923c;"></span>
                                    <span>Oranye</span>
                                    <span wire:loading wire:target="setEmployeeColor({{ $selectedEmployeeForColor }}, 'orange')" class="spinner-border spinner-border-sm ml-auto text-white" style="width: 13px; height: 13px; border-width: 2px;"></span>
                                </button>
                            </div>
                            <!-- Pink Dasar -->
                            <div class="col-6 p-1">
                                <button type="button" wire:click="setEmployeeColor({{ $selectedEmployeeForColor }}, 'pink')" wire:loading.attr="disabled" wire:target="setEmployeeColor" class="btn btn-block text-left d-flex align-items-center p-2 rounded border-0 text-white" style="font-size: 12px; font-weight: 600; background: #f472b6;">
                                    <span class="d-inline-block rounded mr-2 border border-white" style="width: 18px; height: 18px; background: #f472b6;"></span>
                                    <span>Pink</span>
                                    <span wire:loading wire:target="setEmployeeColor({{ $selectedEmployeeForColor }}, 'pink')" class="spinner-border spinner-border-sm ml-auto text-white" style="width: 13px; height: 13px; border-width: 2px;"></span>
                                </button>
                            </div>
                            <!-- Ungu Dasar -->
                            <div class="col-6 p-1">
                                <button type="button" wire:click="setEmployeeColor({{ $selectedEmployeeForColor }}, 'purple')" wire:loading.attr="disabled" wire:target="setEmployeeColor" class="btn btn-block text-left d-flex align-items-center p-2 rounded border-0 text-white" style="font-size: 12px; font-weight: 600; background: #c084fc;">
                                    <span class="d-inline-block rounded mr-2 border border-white" style="width: 18px; height: 18px; background: #c084fc;"></span>
                                    <span>Ungu</span>
                                    <span wire:loading wire:target="setEmployeeColor({{ $selectedEmployeeForColor }}, 'purple')" class="spinner-border spinner-border-sm ml-auto text-white" style="width: 13px; height: 13px; border-width: 2px;"></span>
                                </button>
                            </div>
                            <!-- Abu-abu Dasar -->
                            <div class="col-6 p-1">
                                <button type="button" wire:click="setEmployeeColor({{ $selectedEmployeeForColor }}, 'gray')" wire:loading.attr="disabled" wire:target="setEmployeeColor" class="btn btn-block text-left d-flex align-items-center p-2 rounded border-0 text-dark" style="font-size: 12px; font-weight: 600; background: #cbd5e1;">
                                    <span class="d-inline-block rounded mr-2 border" style="width: 18px; height: 18px; background: #cbd5e1;"></span>
                                    <span>Abu-abu</span>
                                    <span wire:loading wire:target="setEmployeeColor({{ $selectedEmployeeForColor }}, 'gray')" class="spinner-border spinner-border-sm ml-auto text-dark" style="width: 13px; height: 13px; border-width: 2px;"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-3 pb-3 pt-0 d-flex justify-content-end">
                        <button type="button" wire:click="closeColorModal" class="btn font-weight-bold" style="background: #f1f5f9; color: #475569; border: none; border-radius: 8px; padding: 6px 14px; font-size: 12px;">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Import Excel (Compact Clean White Card) -->
    @if ($showImportModal ?? false)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(15, 23, 42, 0.45); z-index: 1050; backdrop-filter: blur(2px);">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0" style="border-radius: 14px; box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.25); overflow: hidden; background: #ffffff;">
                    <div class="modal-header border-0 px-4 pt-3 pb-1">
                        <div class="d-flex align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center mr-2 rounded-circle" style="width: 32px; height: 32px; background: #eff6ff; color: #1d4ed8;">
                                <i class="fas fa-file-upload"></i>
                            </div>
                            <h5 class="modal-title font-weight-bold mb-0" style="font-size: 15px; color: #0f172a;">
                                Import Jadwal Kerja Excel
                            </h5>
                        </div>
                        <button type="button" class="close text-muted" wire:click="closeImportModal" aria-label="Close" style="font-size: 18px; outline: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form wire:submit.prevent="processImport">
                        <div class="modal-body px-4 py-2">
                            <p class="text-muted mb-2" style="font-size: 12.5px; color: #64748b !important;">
                                Unggah spreadsheet (.xlsx / .xls) jadwal bulanan <strong>{{ $monthName }}</strong>.
                                Sistem membaca kolom tanggal 1 s/d {{ $roster['days_in_month'] }} serta sel merah atau teks <code>OFF</code> / <code>LIBUR</code>.
                            </p>

                            <div class="form-group mb-2">
                                <label class="font-weight-bold mb-1" style="font-size: 12px; color: #334155;">Pilih Berkas Excel:</label>
                                <div class="p-2.5 rounded" style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 10px;">
                                    <input type="file" wire:model="importFile" class="form-control-file @error('importFile') is-invalid @enderror" accept=".xlsx,.xls" style="font-size: 12px;">
                                </div>
                                @error('importFile')
                                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                @enderror

                                <div wire:loading wire:target="importFile" class="text-primary mt-1.5" style="font-size: 11.5px;">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah berkas...
                                </div>
                            </div>

                            <div class="alert border-0 py-2 px-2.5 mb-0" style="background: #eff6ff; color: #1d4ed8; font-size: 11.5px; border-radius: 8px;">
                                <i class="fas fa-info-circle mr-1"></i>
                                Format kolom sesuai dengan file yang diunduh dari tombol <strong>Export Excel</strong>.
                            </div>
                        </div>

                        <div class="modal-footer border-0 px-4 pb-3 pt-2 d-flex justify-content-end" style="gap: 8px;">
                            <button type="button" wire:click="closeImportModal" wire:loading.attr="disabled" wire:target="processImport" class="btn font-weight-bold" style="background: #f1f5f9; color: #475569; border: none; border-radius: 8px; padding: 7px 16px; font-size: 12.5px;">
                                Batal
                            </button>
                            <button type="submit" class="btn font-weight-bold text-white" style="background: #1d4ed8; border: none; border-radius: 8px; padding: 7px 18px; font-size: 12.5px; box-shadow: 0 2px 6px rgba(29, 78, 216, 0.25);" wire:loading.attr="disabled" wire:target="processImport">
                                <span wire:loading.remove wire:target="processImport"><i class="fas fa-check mr-1"></i> Proses Impor</span>
                                <span wire:loading wire:target="processImport"><i class="fas fa-spinner fa-spin mr-1"></i> Memproses...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <style>
        [x-cloak] { display: none !important; }

        .roster-table {
            border-collapse: collapse !important;
            border-spacing: 0;
            background: #ffffff;
            font-size: 11px;
            width: 100%;
        }
        .roster-table th,
        .roster-table td {
            border: 1px solid #e2e8f0;
            height: 26px !important;
            padding: 0 !important;
            vertical-align: middle;
        }

        .sticky-col {
            position: sticky;
            z-index: 5;
        }
        .first-col {
            left: 0;
            z-index: 5;
            background-color: #ffffff !important;
        }
        .second-col {
            left: 46px;
            z-index: 5;
        }
        .third-col {
            left: 221px;
            z-index: 5;
            box-shadow: 4px 0 8px -2px rgba(0, 0, 0, 0.06);
        }
        thead th.first-col,
        thead th.second-col,
        thead th.third-col {
            z-index: 25 !important;
        }

        /* Drag Handle & Row Numbers */
        .drag-handle {
            cursor: grab;
            color: #94a3b8;
            transition: color 0.15s;
        }
        .drag-handle:hover {
            color: #2563eb;
        }
        .row-num {
            font-size: 11px;
        }

        /* Drag & Drop Feedback Styling */
        tr.roster-row.is-dragging {
            opacity: 0.45;
            background-color: #f1f5f9 !important;
        }
        tr.roster-row.drag-over-top {
            border-top: 3px solid #2563eb !important;
        }
        tr.roster-row.drag-over-bottom {
            border-bottom: 3px solid #2563eb !important;
        }
        @keyframes soft-glow-move {
            0% { background-color: #dbeafe !important; }
            50% { background-color: #eff6ff !important; }
            100% { background-color: transparent !important; }
        }
        tr.roster-row.just-moved td {
            animation: soft-glow-move 1.6s ease-out;
        }

        /* Warna Dasar Individual per Karyawan (Nama & Jobdesk sama warna) */
        .emp-bg-none   { background-color: #ffffff !important; color: #0f172a !important; }
        .emp-bg-yellow { background-color: #ffeb3b !important; color: #1e293b !important; }
        .emp-bg-blue   { background-color: #38bdf8 !important; color: #0f172a !important; }
        .emp-bg-green  { background-color: #4ade80 !important; color: #0f172a !important; }
        .emp-bg-orange { background-color: #fb923c !important; color: #0f172a !important; }
        .emp-bg-pink   { background-color: #f472b6 !important; color: #0f172a !important; }
        .emp-bg-purple { background-color: #c084fc !important; color: #0f172a !important; }
        .emp-bg-gray   { background-color: #cbd5e1 !important; color: #0f172a !important; }

        /* Color Dot Button on Name Column */
        .btn-color-dot {
            background: transparent;
            border: none;
            padding: 0;
            cursor: pointer;
            outline: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
        }
        .color-dot-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 1px solid rgba(0,0,0,0.3);
            transition: transform 0.15s;
        }
        .btn-color-dot:hover .color-dot-indicator {
            transform: scale(1.3);
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        /* Kotak Kalender Roster Full Cell (Bukan Tombol, Mengisi Penuh 1 Sel) */
        .roster-cell {
            width: 29px !important;
            min-width: 29px !important;
            max-width: 29px !important;
            height: 26px !important;
            cursor: pointer;
            user-select: none;
            transition: filter 0.08s ease;
            position: relative;
        }
        .roster-cell-inner {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 26px;
        }
        .cell-work {
            background-color: #22c55e !important;
            color: #ffffff !important;
        }
        .cell-work:hover {
            filter: brightness(0.92);
        }
        .cell-off {
            background-color: #ef4444 !important;
            color: #ffffff !important;
        }
        .cell-off:hover {
            filter: brightness(0.92);
        }
        .off-text {
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: #ffffff;
            display: inline-block;
            line-height: 26px;
            transition: opacity 0.1s ease;
        }

        /* Animasi Spinner Loading Saat Klik Ubah Masuk/Libur */
        .roster-cell[data-loading="true"],
        .roster-cell.is-loading {
            pointer-events: none !important;
            filter: brightness(0.85);
        }
        .roster-cell[data-loading="true"] .roster-cell-inner::after,
        .roster-cell.is-loading .roster-cell-inner::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 12px;
            height: 12px;
            margin-top: -6px;
            margin-left: -6px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: roster-spin 0.55s linear infinite;
            z-index: 10;
            box-sizing: border-box;
        }
        .roster-cell[data-loading="true"] .off-text,
        .roster-cell.is-loading .off-text {
            opacity: 0 !important;
        }

        @keyframes roster-spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Toolbar & Filter Bar Styling (Crisp, Anti-Clipping, Perfectly Centered) */
        .roster-toolbar-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }
        .roster-toolbar-month {
            display: inline-flex;
            align-items: center;
            height: 36px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 2px;
        }
        .roster-toolbar-month-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 6px;
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            padding: 0;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .roster-toolbar-month-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .roster-toolbar-month-text {
            padding: 0 14px;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 155px;
            line-height: normal;
            user-select: none;
        }
        .roster-toolbar-btn-current {
            height: 36px !important;
            background: #eff6ff !important;
            color: #2563eb !important;
            border: 1px solid #bfdbfe !important;
            border-radius: 8px !important;
            padding: 0 14px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: normal !important;
            box-shadow: none !important;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .roster-toolbar-btn-current:hover {
            background: #dbeafe !important;
            color: #1d4ed8 !important;
            border-color: #93c5fd !important;
        }

        /* Prevent dropdown text clipping caused by default Stisla select padding */
        select.roster-toolbar-select,
        .roster-toolbar-select {
            height: 36px !important;
            min-height: 36px !important;
            max-height: 36px !important;
            padding: 0 28px 0 10px !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            line-height: normal !important;
            color: #334155 !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            cursor: pointer;
            vertical-align: middle;
        }
        select.roster-toolbar-select:focus,
        .roster-toolbar-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
            outline: none !important;
        }

        /* Search Input Group Alignment */
        .roster-toolbar-search {
            height: 36px !important;
        }
        .roster-toolbar-search-icon {
            height: 36px !important;
            padding: 0 12px !important;
            font-size: 11px !important;
            color: #94a3b8 !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-right: none !important;
            border-radius: 8px 0 0 8px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .roster-toolbar-search-input {
            height: 36px !important;
            padding: 0 12px !important;
            font-size: 12px !important;
            line-height: normal !important;
            color: #334155 !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-left: none !important;
            border-radius: 0 8px 8px 0 !important;
            box-shadow: none !important;
        }
        .roster-toolbar-search-input:focus {
            border-color: #3b82f6 !important;
            box-shadow: none !important;
            outline: none !important;
        }
        .roster-toolbar-search:focus-within .roster-toolbar-search-icon,
        .roster-toolbar-search:focus-within .roster-toolbar-search-input {
            border-color: #3b82f6 !important;
        }
    </style>

    <!-- Script Drag & Drop HTML5 Native untuk Baris Karyawan (Event Delegation Aman & Ringan) -->
    <script>
        (function() {
            let draggedRow = null;

            document.addEventListener('dragstart', function(e) {
                const row = e.target.closest('tr.roster-row');
                if (!row) return;

                draggedRow = row;
                row.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', row.getAttribute('data-emp-id'));
            });

            document.addEventListener('dragend', function(e) {
                if (draggedRow) draggedRow.classList.remove('is-dragging');
                document.querySelectorAll('tr.roster-row').forEach(r => {
                    r.classList.remove('drag-over-top', 'drag-over-bottom');
                });
                draggedRow = null;
            });

            document.addEventListener('dragover', function(e) {
                const row = e.target.closest('tr.roster-row');
                if (!row || !draggedRow || draggedRow === row) return;

                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';

                const rect = row.getBoundingClientRect();
                const relY = e.clientY - rect.top;

                document.querySelectorAll('tr.roster-row').forEach(r => {
                    if (r !== row) r.classList.remove('drag-over-top', 'drag-over-bottom');
                });

                if (relY < rect.height / 2) {
                    row.classList.add('drag-over-top');
                    row.classList.remove('drag-over-bottom');
                } else {
                    row.classList.add('drag-over-bottom');
                    row.classList.remove('drag-over-top');
                }
            });

            document.addEventListener('dragleave', function(e) {
                const row = e.target.closest('tr.roster-row');
                if (row && e.relatedTarget && !row.contains(e.relatedTarget)) {
                    row.classList.remove('drag-over-top', 'drag-over-bottom');
                }
            });

            document.addEventListener('drop', function(e) {
                const row = e.target.closest('tr.roster-row');
                if (!row || !draggedRow || draggedRow === row) return;

                e.preventDefault();
                row.classList.remove('drag-over-top', 'drag-over-bottom');

                const tableBody = document.getElementById('rosterTableBody');
                if (!tableBody) return;

                const rect = row.getBoundingClientRect();
                const relY = e.clientY - rect.top;
                if (relY < rect.height / 2) {
                    tableBody.insertBefore(draggedRow, row);
                } else {
                    tableBody.insertBefore(draggedRow, row.nextSibling);
                }

                // Beri efek highlight pada baris yang baru dipindah
                const movedRow = draggedRow;
                movedRow.classList.add('just-moved');
                setTimeout(() => {
                    movedRow.classList.remove('just-moved');
                }, 1600);

                // Ambil urutan ID baru
                const newOrder = Array.from(tableBody.querySelectorAll('tr.roster-row'))
                    .map(r => parseInt(r.getAttribute('data-emp-id'), 10))
                    .filter(id => !isNaN(id));

                if (window.Livewire) {
                    const componentEl = tableBody.closest('[wire\\:id]');
                    if (componentEl) {
                        const component = window.Livewire.find(componentEl.getAttribute('wire:id'));
                        if (component) {
                            component.call('reorderEmployees', newOrder);
                        }
                    }
                }
            });

            // Animasi spinner instan saat klik sel masuk/libur
            document.addEventListener('click', function(e) {
                const cell = e.target.closest('.roster-cell');
                if (cell && cell.hasAttribute('wire:click')) {
                    cell.classList.add('is-loading');
                }
            });

            const resetLoadingCells = function() {
                document.querySelectorAll('.roster-cell.is-loading').forEach(el => el.classList.remove('is-loading'));
            };
            document.addEventListener('livewire:navigated', resetLoadingCells);
            window.addEventListener('livewire:error', resetLoadingCells);
        })();
    </script>
</div>
