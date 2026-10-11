@php
    $ctx = $filterContext ?? 'desktop';
@endphp

<!-- 1. Periode Waktu (Select2) -->
<div class="form-group mb-3">
    <label for="filterPreset_{{ $ctx }}">Periode Waktu</label>
    <div wire:ignore>
        <select id="filterPreset_{{ $ctx }}" class="form-control form-control-sm select2 select2-filter-preset">
            <option value="today" @selected($activePreset === 'today')>Hari Ini</option>
            <option value="yesterday" @selected($activePreset === 'yesterday')>Kemarin</option>
            <option value="7days" @selected($activePreset === '7days')>7 Hari Terakhir</option>
            <option value="this_month" @selected($activePreset === 'this_month')>Bulan Ini</option>
            <option value="all" @selected($activePreset === 'all')>Semua Periode</option>
            <option value="custom" @selected($activePreset === 'custom')>Kustom Tanggal</option>
        </select>
    </div>
</div>

<!-- Rentang Tanggal -->
<div class="form-group mb-3">
    <div class="row">
        <div class="col-6 pr-1">
            <label for="filterStartDate_{{ $ctx }}" class="text-[12px] text-[#475467] font-medium mb-1.5 d-block">Dari Tanggal</label>
            <input type="date" id="filterStartDate_{{ $ctx }}" wire:model.live="startDate" class="form-control form-control-sm">
        </div>
        <div class="col-6 pl-1">
            <label for="filterEndDate_{{ $ctx }}" class="text-[12px] text-[#475467] font-medium mb-1.5 d-block">Sampai Tanggal</label>
            <input type="date" id="filterEndDate_{{ $ctx }}" wire:model.live="endDate" class="form-control form-control-sm">
        </div>
    </div>
</div>

<!-- 2. Tingkat / Level (Select2) -->
<div class="form-group mb-3">
    <label for="filterLevel_{{ $ctx }}">Tingkat (Level)</label>
    <div wire:ignore>
        <select id="filterLevel_{{ $ctx }}" class="form-control form-control-sm select2 select2-filter-level">
            <option value="">Semua Tingkat (Level)</option>
            <option value="info" @selected($filterLevel === 'info')>Info</option>
            <option value="warning" @selected($filterLevel === 'warning')>Warning</option>
            <option value="error" @selected($filterLevel === 'error')>Error</option>
        </select>
    </div>
</div>

<!-- 3. Modul Sistem (Select2) -->
<div class="form-group mb-0">
    <label for="filterModule_{{ $ctx }}">Modul Sistem</label>
    <div wire:ignore>
        <select id="filterModule_{{ $ctx }}" class="form-control form-control-sm select2 select2-filter-module">
            <option value="">Semua Modul</option>
            @foreach ($modules as $module)
                <option value="{{ $module }}" @selected($filterModule === $module)>{{ ucfirst($module) }}</option>
            @endforeach
        </select>
    </div>
</div>
