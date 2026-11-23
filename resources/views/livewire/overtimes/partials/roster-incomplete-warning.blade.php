<div class="roster-warning-content text-left">
    <p class="roster-warning-intro">
        Roster belum lengkap. Lengkapi status Kerja atau Libur di menu Jadwal Kerja:
    </p>

    <div class="roster-warning-list">
        @foreach ($employees as $index => $employee)
            <article class="roster-warning-card">
                <div class="roster-warning-person">
                    <span class="roster-warning-index">{{ $index + 1 }}</span>
                    <span class="roster-warning-avatar" aria-hidden="true"><i class="fas fa-user"></i></span>
                    <span class="roster-warning-identity">
                        <span class="roster-warning-name" title="{{ $employee['name'] }}">{{ $employee['name'] }}</span>
                        <span class="roster-warning-subtitle">Perlu melengkapi roster</span>
                    </span>
                </div>

                <div class="roster-warning-dates" role="list" aria-label="Tanggal roster belum diatur">
                    @foreach ($employee['dates'] as $date)
                        <span class="roster-warning-date" role="listitem">
                            <strong>{{ $date['date'] }}</strong>
                            <small>{{ $date['weekday'] }}</small>
                        </span>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>

    @if ($remainingEmployeeCount > 0)
        <p class="roster-warning-more">Dan {{ $remainingEmployeeCount }} karyawan lainnya juga memiliki tanggal roster yang belum diatur.</p>
    @endif
</div>
