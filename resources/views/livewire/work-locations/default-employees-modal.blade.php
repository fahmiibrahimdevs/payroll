<div wire:ignore.self class="modal fade" id="defaultEmployeesModal" role="dialog" aria-labelledby="defaultEmployeesModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title text-dark" id="defaultEmployeesModalLabel">Atur Karyawan Default</h5>
                    <small class="text-muted">Lokasi kerja: {{ $defaultEmployeeLocationName ?? '' }}</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>

            <form wire:submit="saveDefaultEmployees">
                <div class="modal-body">
                    <small class="text-muted d-block mb-2" role="note">
                        Pilih karyawan yang menjadikan lokasi ini sebagai salah satu lokasi default. Karyawan dapat memiliki beberapa lokasi default; penugasan site bertanggal tetap berlaku.
                    </small>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="defaultEmployeeSearch" class="mb-0">Karyawan aktif</label>
                        <span class="badge badge-primary px-2 py-1">{{ count($selectedDefaultEmployeeIds) }} dipilih</span>
                    </div>
                    <div class="form-group mb-3">
                        <input type="search" id="defaultEmployeeSearch" wire:model.live.debounce.300ms="defaultEmployeeSearch" class="form-control" placeholder="Cari nama, departemen, jabatan, atau lokasi default..." autocomplete="off">
                    </div>

                    @error('selectedDefaultEmployeeIds')
                        <div class="text-danger mb-2">{{ $message }}</div>
                    @enderror
                    @error('selectedDefaultEmployeeIds.*')
                        <div class="text-danger mb-2">{{ $message }}</div>
                    @enderror

                    @forelse ($defaultLocationEmployees as $employee)
                        <div @class(['card shadow-sm border mb-2 default-location-employee-card', 'is-selected' => in_array((string) $employee->id, $selectedDefaultEmployeeIds, true)]) wire:key="default-location-employee-{{ $employee->id }}">
                            <div class="card-body py-2 px-3">
                                <label class="d-flex align-items-center mb-0 w-100" for="defaultEmployee{{ $employee->id }}">
                                    <input type="checkbox" id="defaultEmployee{{ $employee->id }}" value="{{ $employee->id }}" wire:model.live="selectedDefaultEmployeeIds" class="mr-3 flex-shrink-0">
                                    <span class="avatar avatar-sm mr-3 flex-shrink-0">
                                        <img src="{{ $employee->photo_url }}" alt="">
                                    </span>
                                    <span class="d-flex flex-column flex-grow-1">
                                        <span class="font-weight-600 text-dark">{{ $employee->full_name }}</span>
                                        <small class="text-muted text-truncate">
                                            {{ $employee->department?->name ?? 'Tanpa departemen' }} &middot; {{ $employee->position?->name ?? 'Tanpa jabatan' }}
                                            @if ($employee->defaultWorkLocations->isNotEmpty())
                                                &middot; Default saat ini: {{ $employee->defaultWorkLocations->pluck('name')->implode(', ') }}
                                            @elseif ($employee->workLocation)
                                                &middot; Default saat ini: {{ $employee->workLocation->name }}
                                            @endif
                                        </small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @empty
                        <div class="card shadow-sm border mb-0">
                            <div class="card-body py-3 px-3 text-muted">
                                @if (filled($defaultEmployeeSearch))
                                    Tidak ada karyawan aktif yang cocok dengan pencarian ini.
                                @else
                                    Belum ada karyawan aktif.
                                @endif
                            </div>
                        </div>
                    @endforelse

                    @if ($defaultLocationEmployeesTotal > 0)
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mt-2">
                            <small class="text-muted mb-2 mb-sm-0">Menampilkan {{ $defaultLocationEmployees->count() }} dari {{ $defaultLocationEmployeesTotal }} karyawan</small>
                            @if ($hasMoreDefaultLocationEmployees)
                                <button type="button" class="btn btn-outline-primary" wire:click="loadMoreDefaultEmployees" wire:loading.attr="disabled" wire:target="loadMoreDefaultEmployees">
                                    <span wire:loading wire:target="loadMoreDefaultEmployees"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat...</span>
                                    <span wire:loading.remove wire:target="loadMoreDefaultEmployees">Muat 10 Lagi</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveDefaultEmployees">
                        <i wire:loading wire:target="saveDefaultEmployees" class="fas fa-spinner fa-spin mr-1"></i>
                        <span wire:loading.remove wire:target="saveDefaultEmployees"><i class="fas fa-save mr-1"></i> Simpan Karyawan</span>
                        <span wire:loading wire:target="saveDefaultEmployees">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .default-location-employee-card {
            border: 1px solid #e2e8f0 !important;
            transition: border-color 0.15s ease-in-out;
        }

        .default-location-employee-card.is-selected {
            border: 1px solid #0b52aa !important;
        }
    </style>
@endpush
