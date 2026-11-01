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
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
            <h4 class="mb-0">Hari Libur {{ $year }}</h4>
            <div class="d-flex align-items-center" style="gap: 6px;">
                <button type="button" wire:click="previousYear" class="btn btn-light border shadow-none d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px; border-radius: 8px; color: #334155;" title="Tahun sebelumnya">
                    <i class="fas fa-chevron-left" style="font-size: 11px;"></i>
                </button>
                <span class="font-weight-700" style="font-size: 15px; color: #0f172a; min-width: 52px; text-align: center;">{{ $year }}</span>
                <button type="button" wire:click="nextYear" class="btn btn-light border shadow-none d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px; border-radius: 8px; color: #334155;" title="Tahun berikutnya">
                    <i class="fas fa-chevron-right" style="font-size: 11px;"></i>
                </button>
            </div>
        </div>

        <div class="card-body p-3">
            <!-- Controls: Search (+ Show Entries hanya di tab tabel) -->
            <div class="dt-control-wrapper">
                <div class="dt-show-entries">
                    @if ($viewTab === 'table')
                        Show
                        <select wire:model.live="perPage" class="dt-select-entries">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        Entries
                    @endif
                </div>

                <div class="dt-search-wrapper">
                    <span class="mr-1">Search:</span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama hari libur...">
                </div>
            </div>

            <!-- Nav-pills: Tampilan Kalender / Tabel (di bawah controls) -->
            <ul class="nav nav-pills mb-2" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $viewTab === 'calendar' ? 'active' : '' }} font-weight-600 border-0 text-nowrap" wire:click="switchViewTab('calendar')">Kalender</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $viewTab === 'table' ? 'active' : '' }} font-weight-600 border-0 text-nowrap" wire:click="switchViewTab('table')">Tabel</button>
                </li>
            </ul>

            @php
                $isAdmin = auth()->user()?->hasRole('admin');
                $todayStr = now()->toDateString();
                $searchActive = trim($search) !== '';
                $weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
            @endphp

            @if ($viewTab === 'calendar')

            @if ($searchActive)
                <div class="px-1 pb-2" style="font-size: 12px; color: #334155;">
                    @if (count($matchingDates) > 0)
                        <i class="fas fa-search text-primary mr-1"></i>
                        {{ count($matchingDates) }} hari libur cocok dengan "<span class="font-weight-600">{{ $search }}</span>" — sorotan biru.
                    @else
                        <i class="fas fa-search text-muted mr-1"></i>
                        Tidak ada hari libur bernama "<span class="font-weight-600">{{ $search }}</span>" pada {{ $year }}.
                    @endif
                </div>
            @endif

            <div class="row">
                @for ($m = 1; $m <= 12; $m++)
                    @php
                        $first = \Carbon\Carbon::create($year, $m, 1);
                        $daysInMonth = $first->daysInMonth;
                        $leading = $first->dayOfWeekIso - 1; // Senin = 0
                        $rows = (int) ceil(($leading + $daysInMonth) / 7);
                        $monthCount = count(array_filter(array_keys($holidayInfo), fn ($d) => (int) substr($d, 5, 2) === $m));
                    @endphp
                    <div class="col-12 col-md-6 col-xl-4 mb-4">
                        <div class="h-100" x-data="{ sel: null, holidays: @js($holidayInfo) }" @click.outside="sel = null"
                             style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); padding: 14px 16px;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="font-weight-700" style="font-size: 13px; color: #0f172a;">
                                    {{ $first->locale('id')->translatedFormat('F Y') }}
                                </span>
                                @if ($monthCount > 0)
                                    <span class="badge font-weight-600" style="font-size: 10.5px; padding: 2.5px 10px; border-radius: 9999px; background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                                        {{ $monthCount }} Libur
                                    </span>
                                @endif
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px;">
                                @foreach ($weekdays as $wd)
                                    <div class="text-center" style="font-size: 10px; font-weight: 700; color: {{ $wd === 'Min' ? '#dc2626' : '#475569' }}; padding: 3px 0; text-transform: uppercase;">{{ $wd }}</div>
                                @endforeach

                                @for ($i = 0; $i < $rows * 7; $i++)
                                    @php
                                        $dayNum = $i - $leading + 1;
                                        $isValid = $dayNum >= 1 && $dayNum <= $daysInMonth;
                                        $dateStr = $isValid ? sprintf('%04d-%02d-%02d', $year, $m, $dayNum) : null;
                                        $holiday = $isValid ? ($holidayInfo[$dateStr] ?? null) : null;
                                        $isSunday = $isValid && ($i % 7) === 6;
                                        $isToday = $dateStr === $todayStr;
                                        $isDimmed = $holiday && $searchActive && ! in_array($dateStr, $matchingDates, true);
                                    @endphp
                                    @if (! $isValid)
                                        <div style="min-height: 46px;"></div>
                                    @elseif ($holiday)
                                        <button type="button"
                                                @click="sel = sel === '{{ $dateStr }}' ? null : '{{ $dateStr }}'"
                                                title="{{ $holiday['name'] }}"
                                                class="w-100 text-center"
                                                style="min-height: 46px; border-radius: 8px; cursor: pointer; border: 1px solid #fecaca; background-color: #fef2f2; padding: 3px 1px; transition: all 0.15s ease; opacity: {{ $isDimmed ? 0.4 : 1 }}; {{ $isToday ? 'outline: 2px solid #0b52aa; outline-offset: -2px;' : '' }}">
                                            <div style="font-size: 11.5px; font-weight: 700; color: #dc2626; line-height: 1.2;">{{ $dayNum }}</div>
                                            <div style="font-size: 8.5px; font-weight: 600; color: #b91c1c; line-height: 1.15; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word;">{{ $holiday['name'] }}</div>
                                        </button>
                                    @else
                                        <span class="w-100 h-100 d-flex align-items-center justify-content-center"
                                              style="min-height: 46px; font-size: 12px; color: {{ $isToday ? '#0b52aa' : ($isSunday ? '#dc2626' : '#334155') }}; font-weight: {{ $isToday || $isSunday ? '600' : '400' }}; {{ $isToday ? 'border: 2px solid #0b52aa; border-radius: 8px;' : '' }}">
                                            {{ $dayNum }}
                                        </span>
                                    @endif
                                @endfor
                            </div>

                            <!-- Popup info libur terpilih -->
                            <template x-if="sel && holidays[sel]">
                                <div class="mt-2 p-2.5 d-flex align-items-center justify-content-between flex-wrap" style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; gap: 8px;">
                                    <div style="min-width: 0;">
                                        <div class="font-weight-600" style="font-size: 12.5px; color: #991b1b;" x-text="holidays[sel].name"></div>
                                        <div style="font-size: 11px; color: #475569;" x-text="holidays[sel].label"></div>
                                    </div>
                                    @if ($isAdmin)
                                        <div class="d-flex flex-shrink-0" style="gap: 6px;">
                                            <button type="button" @click="$wire.edit(holidays[sel].id)" class="btn btn-primary btn-action" style="width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;" title="Edit Data">
                                                <i class="fas fa-pencil-alt" style="font-size: 11px;"></i>
                                            </button>
                                            <button type="button" @click="$wire.confirmDelete(holidays[sel].id)" class="btn btn-danger btn-action" style="width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;" title="Hapus Data">
                                                <i class="fas fa-trash" style="font-size: 11px;"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </template>
                        </div>
                    </div>
                @endfor
            </div>

            @if (count($holidayInfo) === 0 && ! $searchActive)
                <div class="text-center py-5">
                    <i class="far fa-calendar-times mb-2" style="font-size: 32px; color: #94a3b8;"></i>
                    <div class="font-weight-600" style="color: #334155;">Tidak ada hari libur terdaftar pada {{ $year }}</div>
                    <div style="font-size: 12.5px; color: #475569;">Gunakan tombol <span class="font-weight-600">+</span> di kanan bawah untuk menambahkan hari libur.</div>
                </div>
            @endif
            @else
                <!-- ================= TAMPILAN TABEL (paginasi standar) ================= -->
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
                                <tr wire:key="holiday-{{ $holiday->id }}" style="border-bottom: 1px solid #f1f5f9;">
                                    <td class="text-center font-weight-600 align-middle" style="color: #475569; font-size: 12.5px;">
                                        {{ $holidays->firstItem() + $index }}
                                    </td>
                                    <td class="align-middle">
                                        <div class="font-weight-600 text-nowrap" style="font-size: 13.5px; color: #0f172a;">
                                            {{ $holiday->date->translatedFormat('d M Y') }}
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <div style="font-size: 13.5px; color: #1e293b;">{{ $holiday->name }}</div>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge font-weight-600" style="font-size: 11px; padding: 2.5px 10px; border-radius: 9999px; background-color: #f1f5f9; color: #334155; border: 1px solid #e2e8f0;">
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
                                    <td colspan="5">
                                        <div class="text-center py-5">
                                            <i class="far fa-calendar-times mb-2" style="font-size: 32px; color: #94a3b8;"></i>
                                            <div class="font-weight-600" style="color: #334155;">Tidak ada data hari libur</div>
                                            <div style="font-size: 12.5px; color: #475569;">
                                                @if($search)
                                                    Tidak ada hari libur yang sesuai dengan kata kunci "{{ $search }}".
                                                @else
                                                    Belum ada hari libur yang terdaftar untuk tahun {{ $year }}.
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer: Info & Pagination -->
                <div class="dt-footer-wrapper">
                    <div class="dt-info">
                        Showing {{ $holidays->total() > 0 ? $holidays->firstItem() : 0 }} to {{ $holidays->total() > 0 ? $holidays->lastItem() : 0 }} of {{ $holidays->total() }} entries
                    </div>
                    <div>
                        {{ $holidays->links('livewire.custom-pagination') }}
                    </div>
                </div>
            @endif
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
