<!-- Modal Form: Penugasan Karyawan ke Site / Lokasi Kerja -->
<div wire:ignore.self class="modal fade" id="locationAssignmentModal" role="dialog" aria-labelledby="locationAssignmentModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
            <div class="modal-header bg-white border-bottom py-3">
                <h5 class="modal-title text-dark" id="locationAssignmentModalLabel" style="font-weight: 500;">
                    Penugasan Karyawan ke Site / Lokasi
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form wire:submit="storeAssignment">
                <div class="modal-body py-3 px-4 bg-white">
                    <div class="row">
                        <!-- 1. Karyawan -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="assign_employee_id">Karyawan <span class="text-danger">*</span></label>
                                <div wire:ignore>
                                    <select id="assign_employee_id" class="form-control select2 @error('assignmentForm.employee_id') is-invalid @enderror">
                                        <option value="">-- Pilih Karyawan --</option>
                                        @foreach ($allActiveEmployees as $emp)
                                            <option value="{{ $emp->id }}">
                                                {{ $emp->full_name }} ({{ $emp->position?->name ?? 'Staff' }} - {{ $emp->department?->name ?? 'Umum' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('assignmentForm.employee_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 2. Lokasi Kerja / Site -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="assign_work_location_id">Lokasi Kerja / Site Tujuan <span class="text-danger">*</span></label>
                                <div wire:ignore>
                                    <select id="assign_work_location_id" class="form-control select2 @error('assignmentForm.work_location_id') is-invalid @enderror">
                                        <option value="">-- Pilih Lokasi Kerja / Site --</option>
                                        @foreach ($allActiveLocations as $loc)
                                            <option value="{{ $loc->id }}">
                                                {{ $loc->name }} (Radius: {{ $loc->radius_meters }}m)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('assignmentForm.work_location_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 3. Rentang Tanggal -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="assign_start_date">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" id="assign_start_date" wire:model="assignmentForm.start_date" class="form-control @error('assignmentForm.start_date') is-invalid @enderror">
                                @error('assignmentForm.start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="assign_end_date">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" id="assign_end_date" wire:model="assignmentForm.end_date" class="form-control @error('assignmentForm.end_date') is-invalid @enderror">
                                @error('assignmentForm.end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 4. Pilihan Cepat Durasi -->
                        <div class="col-12 mb-3">
                            <div class="d-flex flex-wrap align-items-center" style="gap: 6px;">
                                <span class="text-muted mr-1" style="font-size: 12px;">Pilihan cepat:</span>
                                <button type="button" wire:click="setAssignmentDuration(1)" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11px;">1 Hari</button>
                                <button type="button" wire:click="setAssignmentDuration(3)" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11px;">3 Hari</button>
                                <button type="button" wire:click="setAssignmentDuration(7)" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11px;">1 Minggu</button>
                                <button type="button" wire:click="setAssignmentDuration(14)" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11px;">2 Minggu</button>
                                <button type="button" wire:click="setAssignmentDuration(30)" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11px;">1 Bulan</button>
                            </div>
                        </div>

                        <!-- 5. Keterangan / Catatan -->
                        <div class="col-12">
                            <div class="form-group mb-0">
                                <label for="assign_notes">Keterangan <small class="text-muted">(Opsional)</small></label>
                                <textarea id="assign_notes" wire:model="assignmentForm.notes" rows="2" placeholder="Catatan atau alasan penugasan..." class="form-control @error('assignmentForm.notes') is-invalid @enderror"></textarea>
                                @error('assignmentForm.notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Batal</button>
                    <button type="submit" class="btn btn-primary shadow-none" wire:loading.attr="disabled" wire:target="storeAssignment" style="font-weight: 500;">
                        <span wire:loading wire:target="storeAssignment"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                        <span wire:loading.remove wire:target="storeAssignment"><i class="fas fa-save mr-1"></i> Simpan Penugasan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        // Izinkan focus Select2 di dalam modal Bootstrap
        if ($.fn.modal && $.fn.modal.Constructor) {
            $.fn.modal.Constructor.prototype._enforceFocus = function () {};
        }

        const initSelect2 = function () {
            if ($('#assign_employee_id').length && !$('#assign_employee_id').hasClass('select2-hidden-accessible')) {
                $('#assign_employee_id').select2({
                    dropdownParent: $('#locationAssignmentModal'),
                    width: '100%',
                    placeholder: '-- Pilih Karyawan --',
                    allowClear: true
                }).off('change').on('change', function () {
                    const val = $(this).val();
                    if (typeof @this !== 'undefined') {
                        @this.set('assignmentForm.employee_id', val ? String(val) : '');
                    }
                });
            }

            if ($('#assign_work_location_id').length && !$('#assign_work_location_id').hasClass('select2-hidden-accessible')) {
                $('#assign_work_location_id').select2({
                    dropdownParent: $('#locationAssignmentModal'),
                    width: '100%',
                    placeholder: '-- Pilih Lokasi Kerja / Site --',
                    allowClear: true
                }).off('change').on('change', function () {
                    const val = $(this).val();
                    if (typeof @this !== 'undefined') {
                        @this.set('assignmentForm.work_location_id', val ? String(val) : '');
                    }
                });
            }
        };

        $(document).ready(function () {
            initSelect2();
        });

        function registerLivewireEvents() {
            Livewire.on('open-modal', function (event) {
                const data = Array.isArray(event) ? event[0] : event;
                const modalId = typeof data === 'object' ? data?.id : data;

                if (modalId === 'locationAssignmentModal') {
                    setTimeout(function () {
                        initSelect2();
                        if (typeof @this !== 'undefined') {
                            const empVal = @this.get('assignmentForm.employee_id');
                            const locVal = @this.get('assignmentForm.work_location_id');

                            $('#assign_employee_id').val(empVal ? String(empVal) : '').trigger('change.select2');
                            $('#assign_work_location_id').val(locVal ? String(locVal) : '').trigger('change.select2');
                        }
                    }, 50);
                }
            });

            Livewire.on('close-modal', function (event) {
                const data = Array.isArray(event) ? event[0] : event;
                const modalId = typeof data === 'object' ? data?.id : data;

                if (modalId === 'locationAssignmentModal') {
                    $('#assign_employee_id').val('').trigger('change.select2');
                    $('#assign_work_location_id').val('').trigger('change.select2');
                }
            });
        }

        if (typeof Livewire !== 'undefined') {
            registerLivewireEvents();
        } else {
            document.addEventListener('livewire:init', registerLivewireEvents);
        }

        $('#locationAssignmentModal').on('hidden.bs.modal', function () {
            $('#assign_employee_id').val('').trigger('change.select2');
            $('#assign_work_location_id').val('').trigger('change.select2');
        });
    })();
</script>
@endpush
