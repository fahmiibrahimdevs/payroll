@php
    $ctx = $filterContext ?? 'desktop';
@endphp

<!-- Filter Departemen (Select2) -->
<div class="form-group mb-3">
    <label for="filterDepartment_{{ $ctx }}">Departemen</label>
    <div wire:ignore>
        <select id="filterDepartment_{{ $ctx }}" class="form-control form-control-sm select2 select2-filter-dept">
            <option value="">Semua Departemen</option>
            @foreach ($departments as $dept)
                <option value="{{ $dept->id }}" @selected($filterDepartment == $dept->id)>{{ $dept->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Filter Jabatan (Select2) -->
<div class="form-group mb-3">
    <label for="filterPosition_{{ $ctx }}">Jabatan</label>
    <div wire:ignore>
        <select id="filterPosition_{{ $ctx }}" class="form-control form-control-sm select2 select2-filter-pos">
            <option value="">Semua Jabatan</option>
            @foreach ($positions as $pos)
                <option value="{{ $pos->id }}" @selected($filterPosition == $pos->id)>{{ $pos->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Filter Status Kepegawaian (Select2) -->
<div class="form-group mb-3">
    <label for="filterEmploymentStatus_{{ $ctx }}">Status Kepegawaian</label>
    <div wire:ignore>
        <select id="filterEmploymentStatus_{{ $ctx }}" class="form-control form-control-sm select2 select2-filter-emp-status">
            <option value="">Semua Status Kerja</option>
            @foreach ($employmentStatuses as $empStatus)
                <option value="{{ $empStatus->value }}" @selected($filterEmploymentStatus == $empStatus->value)>{{ $empStatus->label() }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Filter Status Akun (Select2) -->
<div class="form-group mb-0">
    <label for="filterStatus_{{ $ctx }}">Status Akun</label>
    <div wire:ignore>
        <select id="filterStatus_{{ $ctx }}" class="form-control form-control-sm select2 select2-filter-status">
            <option value="">Semua Status Akun</option>
            @foreach ($statuses as $stat)
                <option value="{{ $stat->value }}" @selected($filterStatus == $stat->value)>{{ $stat->label() }}</option>
            @endforeach
        </select>
    </div>
</div>

@if ($search || $filterDepartment || $filterPosition || $filterStatus || $filterEmploymentStatus)
    <div class="mt-3 pt-3 border-top">
        <button type="button" wire:click="resetFilters" class="btn btn-outline-danger btn-sm btn-block"><i class="fas fa-undo mr-1"></i> Reset Semua Filter</button>
    </div>
@endif
