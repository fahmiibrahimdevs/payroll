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

            <form id="locationAssignmentForm">
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
                                            @php
                                                $defaultLocationIds = $emp->defaultWorkLocations->modelKeys();
                                                if ($emp->work_location_id !== null) {
                                                    $defaultLocationIds[] = (int) $emp->work_location_id;
                                                }
                                                $defaultLocationIds = array_values(array_unique(array_map('intval', $defaultLocationIds)));
                                                sort($defaultLocationIds);
                                            @endphp
                                            <option value="{{ $emp->id }}" data-default-location-ids="{{ implode(',', $defaultLocationIds) }}">
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

        const assignmentLocationOptionTemplates = $('#assign_work_location_id option').toArray().map(function (option) {
            const optionTemplate = option.cloneNode(true);
            optionTemplate.selected = false;

            return optionTemplate;
        });

        const initSelect2 = function () {
            const $employeeSelect = $('#assign_employee_id');
            if ($employeeSelect.length) {
                if (!$employeeSelect.hasClass('select2-hidden-accessible')) {
                    $employeeSelect.select2({
                        dropdownParent: $('#locationAssignmentModal'),
                        width: '100%',
                        placeholder: '-- Pilih Karyawan --',
                        allowClear: true
                    });
                }

                $employeeSelect.off('change.assignmentEmployee').on('change.assignmentEmployee', function () {
                    const val = $(this).val();
                    const employeeId = val ? String(val) : '';
                    const selectedEmployeeOption = $(this).find('option:selected');
                    const defaultLocationIds = String(selectedEmployeeOption.attr('data-default-location-ids') || '')
                        .split(',')
                        .filter(Boolean);

                    updateAssignmentLocationOptions(employeeId, defaultLocationIds);

                    if (typeof @this !== 'undefined') {
                        @this.call('selectAssignmentEmployee', val ? Number(val) : null)
                            .then(function (result) {
                                const data = result && typeof result === 'object' ? result : {};
                                const resolvedEmployeeId = data.employeeId ?? employeeId;

                                if (String($('#assign_employee_id').val() || '') !== String(resolvedEmployeeId || '')) {
                                    return;
                                }

                                updateAssignmentLocationOptions(resolvedEmployeeId, data.locationIds || []);
                            })
                            .catch(function () {
                                updateAssignmentLocationOptions(employeeId, defaultLocationIds);
                            });
                    }
                });
            }

            const $locationSelect = $('#assign_work_location_id');
            if ($locationSelect.length) {
                if (!$locationSelect.hasClass('select2-hidden-accessible')) {
                    $locationSelect.select2({
                        dropdownParent: $('#locationAssignmentModal'),
                        width: '100%',
                        placeholder: '-- Pilih Lokasi Kerja / Site --',
                        allowClear: true
                    });
                }

                $locationSelect.off('change.assignmentLocation').on('change.assignmentLocation', function () {
                    const val = $(this).val();
                    if (typeof @this !== 'undefined') {
                        @this.set('assignmentForm.work_location_id', val ? String(val) : '', false);
                    }
                });
            }
        };

        const updateAssignmentLocationOptions = function (employeeId, defaultLocationIds) {
            const selectedLocationId = String($('#assign_work_location_id').val() || '');
            const defaultLocationIdSet = new Set((defaultLocationIds || []).map(function (locationId) {
                return String(locationId);
            }));
            const shouldClearSelectedLocation = Boolean(employeeId) && defaultLocationIdSet.has(selectedLocationId);
            const $locationSelect = $('#assign_work_location_id');
            const availableOptions = assignmentLocationOptionTemplates.filter(function (optionTemplate) {
                const locationId = String(optionTemplate.value || '');

                return !employeeId || !locationId || !defaultLocationIdSet.has(locationId);
            });

            $locationSelect.empty();
            availableOptions.forEach(function (optionTemplate) {
                $locationSelect.append(optionTemplate.cloneNode(true));
            });

            if (shouldClearSelectedLocation) {
                if (typeof @this !== 'undefined') {
                    @this.set('assignmentForm.work_location_id', '', false);
                }
            }

            $locationSelect.val(shouldClearSelectedLocation ? '' : selectedLocationId);
            $locationSelect.trigger('change.select2');
        };

        const assignmentForm = document.getElementById('locationAssignmentForm');
        assignmentForm?.addEventListener('submit', function (event) {
            event.preventDefault();

            const employeeId = $('#assign_employee_id').val();
            const workLocationId = $('#assign_work_location_id').val();

            if (typeof @this !== 'undefined') {
                @this.set('assignmentForm.employee_id', employeeId ? String(employeeId) : '', false);
                @this.set('assignmentForm.work_location_id', workLocationId ? String(workLocationId) : '', false);
                @this.storeAssignment();
            }
        });

        $(document).ready(function () {
            initSelect2();
            updateAssignmentLocationOptions('', []);
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
                            updateAssignmentLocationOptions('', []);
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
                    updateAssignmentLocationOptions('');
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
            updateAssignmentLocationOptions('');
        });
    })();
</script>
@endpush
