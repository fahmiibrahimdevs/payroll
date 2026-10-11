@php
    $ctx = $filterContext ?? 'desktop';
@endphp

<!-- Filter Kategori Komponen (Select2) -->
<div class="form-group mb-3">
    <label for="filterCategory_{{ $ctx }}">Kategori Komponen</label>
    <div wire:ignore>
        <select id="filterCategory_{{ $ctx }}" class="form-control form-control-sm select2 select2-filter-cat">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->value }}" @selected($filterCategory == $cat->value)>{{ $cat->label() }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Filter Status (Select2) -->
<div class="form-group mb-0">
    <label for="filterStatus_{{ $ctx }}">Status</label>
    <div wire:ignore>
        <select id="filterStatus_{{ $ctx }}" class="form-control form-control-sm select2 select2-filter-status">
            <option value="">Semua Status</option>
            <option value="active" @selected($filterStatus === 'active')>Aktif</option>
            <option value="inactive" @selected($filterStatus === 'inactive')>Nonaktif</option>
        </select>
    </div>
</div>
