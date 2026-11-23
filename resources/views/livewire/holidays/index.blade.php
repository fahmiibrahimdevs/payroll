<div class="holidays-index-page">
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

    <div class="card holiday-calendar-shell">
        <div class="card-header holiday-calendar-header d-flex align-items-center justify-content-between flex-wrap">
            <h4 class="holiday-calendar-page-title">Kalender Hari Libur</h4>
            <div class="holiday-year-controls d-flex align-items-center">
                <button type="button" wire:click="previousYear" class="btn btn-light border shadow-none holiday-year-button" title="Tahun sebelumnya" aria-label="Tahun sebelumnya">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="holiday-year-label">{{ $year }}</span>
                <button type="button" wire:click="nextYear" class="btn btn-light border shadow-none holiday-year-button" title="Tahun berikutnya" aria-label="Tahun berikutnya">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <div class="card-body p-3 holiday-calendar-body">
            <div class="dt-control-wrapper holiday-controls">
                <div class="dt-search-wrapper">
                    <span class="mr-1">Search:</span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama hari libur...">
                </div>
            </div>

            @php
                $isAdmin = auth()->user()?->hasRole('admin');
                $todayStr = now()->toDateString();
                $searchActive = trim($search) !== '';
                $weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
            @endphp

            @if ($searchActive)
                <div class="holiday-search-info">
                    @if (count($matchingDates) > 0)
                        <i class="fas fa-search text-primary mr-1"></i>
                        {{ count($matchingDates) }} hari libur cocok dengan "<span class="font-weight-600">{{ $search }}</span>" dan ditandai pada kalender.
                    @else
                        <i class="fas fa-search text-muted mr-1"></i>
                        Tidak ada hari libur bernama "<span class="font-weight-600">{{ $search }}</span>" pada {{ $year }}.
                    @endif
                </div>
            @endif

            <div class="row holiday-calendar-grid">
                @for ($m = 1; $m <= 12; $m++)
                    @php
                        $first = \Carbon\Carbon::create($year, $m, 1);
                        $daysInMonth = $first->daysInMonth;
                        $leading = $first->dayOfWeekIso - 1; // Senin = 0
                        $rows = (int) ceil(($leading + $daysInMonth) / 7);
                        $monthHolidays = $holidayMonths[$m] ?? [];
                        $monthCount = count($monthHolidays);
                    @endphp
                    <div class="col-12 col-md-6 col-xl-4 mb-4 holiday-month-column" wire:key="holiday-month-{{ $year }}-{{ $m }}">
                        <article class="holiday-month-card {{ $year === now()->year && $m === now()->month ? 'is-current-month' : '' }}">
                            <header class="holiday-month-header">
                                <h3 class="holiday-month-title">{{ $first->locale('id')->translatedFormat('F Y') }}</h3>
                                @if ($monthCount > 0)
                                    <span class="holiday-month-count">
                                        <i class="fas fa-calendar-day"></i> {{ $monthCount }} hari libur
                                    </span>
                                @else
                                    <span class="holiday-month-count is-empty">Tanpa hari libur</span>
                                @endif
                            </header>

                            <div class="holiday-month-calendar-grid">
                                @foreach ($weekdays as $wd)
                                    <div class="holiday-calendar-weekday {{ $wd === 'Min' ? 'is-sunday' : '' }}">{{ $wd }}</div>
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
                                        $isSearchMatch = $holiday && $searchActive && in_array($dateStr, $matchingDates, true);
                                    @endphp
                                    @if (! $isValid)
                                        <div class="holiday-calendar-day is-empty"></div>
                                    @else
                                        <div class="holiday-calendar-day {{ $isSunday ? 'is-sunday' : '' }} {{ $isToday ? 'is-today' : '' }} {{ $holiday ? 'is-holiday' : '' }} {{ $isDimmed ? 'is-dimmed' : '' }} {{ $isSearchMatch ? 'is-search-match' : '' }}">
                                            <span class="holiday-calendar-day-number">{{ $dayNum }}</span>
                                            @if ($holiday)
                                                <span class="holiday-calendar-day-dot"></span>
                                            @endif
                                        </div>
                                    @endif
                                @endfor
                            </div>

                            <div class="holiday-month-notes">
                                <div class="holiday-month-notes-title">Keterangan Hari Libur</div>
                                @forelse ($monthHolidays as $holiday)
                                    <div class="holiday-month-note {{ $searchActive ? ($holiday['matchesSearch'] ? 'is-search-match' : 'is-dimmed') : '' }}" wire:key="holiday-month-note-{{ $holiday['id'] }}">
                                        <span class="holiday-month-note-date">
                                            <strong>{{ $holiday['day'] }}</strong>
                                            <small>{{ $holiday['weekday'] }}</small>
                                        </span>
                                        <span class="holiday-month-note-name" title="{{ $holiday['name'] }}">{{ $holiday['name'] }}</span>
                                        @if ($isAdmin)
                                            <div class="holiday-month-note-actions">
                                                <button type="button" wire:click="edit({{ $holiday['id'] }})" wire:loading.attr="disabled" wire:target="edit({{ $holiday['id'] }})" class="btn btn-primary btn-action" title="Edit Data" aria-label="Edit {{ $holiday['name'] }}">
                                                    <i wire:loading wire:target="edit({{ $holiday['id'] }})" class="fas fa-spinner fa-spin"></i>
                                                    <i wire:loading.remove wire:target="edit({{ $holiday['id'] }})" class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button type="button" wire:click="confirmDelete({{ $holiday['id'] }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $holiday['id'] }})" class="btn btn-danger btn-action" title="Hapus Data" aria-label="Hapus {{ $holiday['name'] }}">
                                                    <i wire:loading wire:target="confirmDelete({{ $holiday['id'] }})" class="fas fa-spinner fa-spin"></i>
                                                    <i wire:loading.remove wire:target="confirmDelete({{ $holiday['id'] }})" class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="holiday-month-empty-note">Tidak ada hari libur bulan ini.</div>
                                @endforelse
                            </div>
                        </article>
                    </div>
                @endfor
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
