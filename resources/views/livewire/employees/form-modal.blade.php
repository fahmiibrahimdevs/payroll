<div wire:ignore.self class="modal fade" id="employeeModal" role="dialog" aria-labelledby="employeeModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalLabel">
                    {{ $isEditMode ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="closeModal"><span aria-hidden="true">&times;</span></button>
            </div>

            <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}">
                <div class="modal-body py-3 px-4">
                    <!-- Navigation Pills (3 Tabs Ringkas: Pas 1 Baris Tanpa Scrollbar) -->
                    <ul class="nav nav-pills mb-3" id="employeeFormPills" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-600 text-center" id="employment-tab" data-toggle="pill" href="#emp-pill-kepegawaian" role="tab" aria-controls="emp-pill-kepegawaian" aria-selected="true">
                                <i class="fas fa-briefcase mr-1"></i> Kepegawaian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-600 text-center" id="personal-tab" data-toggle="pill" href="#emp-pill-pribadi" role="tab" aria-controls="emp-pill-pribadi" aria-selected="false">
                                <i class="fas fa-user mr-1"></i> Pribadi & Akun
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-600 text-center" id="tax-tab" data-toggle="pill" href="#emp-pill-pajak" role="tab" aria-controls="emp-pill-pajak" aria-selected="false">
                                <i class="fas fa-file-invoice-dollar mr-1"></i> Pajak & BPJS
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="employeeFormPillsContent">
                        <!-- Tab 1: Kepegawaian (Mandatory / Wajib Diisi) -->
                        <div class="tab-pane fade show active" id="emp-pill-kepegawaian" role="tabpanel" aria-labelledby="employment-tab">
                            <div class="row">
                                <!-- Nama Lengkap -->
                                <div class="col-md-8">
                                    <div class="form-group mb-2">
                                        <label for="full_name">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" id="full_name" wire:model.live.debounce.400ms="form.full_name" class="form-control @error('form.full_name') is-invalid @enderror" placeholder="Contoh: Muhammad Fahmi Ibrahim" autocomplete="off">
                                        @error('form.full_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- ID Mesin Fingerprint -->
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label for="emp_fingerprint_id">ID Fingerprint / PIN</label>
                                        <input type="text" id="emp_fingerprint_id" wire:model="form.fingerprint_id" class="form-control @error('form.fingerprint_id') is-invalid @enderror" placeholder="Contoh: 17, 28" autocomplete="off">
                                        @error('form.fingerprint_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Departemen (Select2) -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="emp_department_id">Departemen</label>
                                        <div wire:ignore>
                                            <select id="emp_department_id" class="form-control select2">
                                                <option value="">-- Pilih Departemen --</option>
                                                @foreach ($departments as $dept)
                                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('form.department_id')
                                            <div class="text-danger text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Jabatan (Select2) -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="emp_position_id">Jabatan</label>
                                        <div wire:ignore>
                                            <select id="emp_position_id" class="form-control select2">
                                                <option value="">-- Pilih Jabatan --</option>
                                                @foreach ($positions as $pos)
                                                    <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('form.position_id')
                                            <div class="text-danger text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Status Kepegawaian (Native Clean Select) -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="employment_status">Status Kepegawaian <span class="text-danger">*</span></label>
                                        <select id="employment_status" wire:model.live="form.employment_status" class="form-control @error('form.employment_status') is-invalid @enderror">
                                            @foreach ($employmentStatuses as $empStat)
                                                <option value="{{ $empStat->value }}">{{ $empStat->label() }}</option>
                                            @endforeach
                                        </select>
                                        @error('form.employment_status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Status Karyawan (Native Clean Select) -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="status">Status Karyawan <span class="text-danger">*</span></label>
                                        <select id="status" wire:model="form.status" class="form-control @error('form.status') is-invalid @enderror">
                                            @foreach ($statuses as $st)
                                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                            @endforeach
                                        </select>
                                        @error('form.status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Tanggal Bergabung -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="join_date">Tanggal Bergabung <span class="text-danger">*</span></label>
                                        <input type="date" id="join_date" wire:model="form.join_date" class="form-control @error('form.join_date') is-invalid @enderror">
                                        @error('form.join_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Tanggal Akhir Kontrak -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="contract_end_date" class="d-flex align-items-center">
                                            <span>Tanggal Akhir Kontrak</span>
                                            <i class="fas fa-info-circle text-muted ml-1 cursor-pointer" data-toggle="tooltip" data-placement="top" title="Jika pekerjaan berakhir sebelum Desember, tandai masa pajak terakhir pada slip bulan berhenti."></i>
                                        </label>
                                        <input type="date" id="contract_end_date" wire:model="form.contract_end_date" class="form-control @error('form.contract_end_date') is-invalid @enderror">
                                        @error('form.contract_end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                @if ($form->employment_status === 'harian')
                                    <!-- Upah Harian Pegawai Harian -->
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="daily_rate">Upah Harian (Rp/hari) <span class="text-danger">*</span></label>
                                            <div class="input-group"
                                                 wire:key="emp-daily-rate-wrap-{{ $form->employee?->id ?? 'new' }}"
                                                 x-data="{
                                                     rawVal: @entangle('form.daily_rate'),
                                                     format(v) {
                                                         if (!v || isNaN(v) || v == 0) return '';
                                                         return new Intl.NumberFormat('id-ID').format(v);
                                                     }
                                                 }"
                                                 x-init="$refs.input && ($refs.input.value = format(rawVal))"
                                                 x-effect="if ($refs.input) { let f = format(rawVal); if ($refs.input.value !== f) { $refs.input.value = f; } }">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text font-weight-500">Rp</span>
                                                </div>
                                                <input type="text"
                                                       x-ref="input"
                                                       id="daily_rate"
                                                       inputmode="numeric"
                                                       x-on:input="
                                                           let raw = $el.value.replace(/\D/g, '');
                                                           $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                                           rawVal = raw ? parseFloat(raw) : 0;
                                                       "
                                                       class="form-control text-right @error('form.daily_rate') is-invalid @enderror"
                                                       placeholder="0"
                                                       autocomplete="off">
                                            </div>
                                            @error('form.daily_rate')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Pegawai harian dibayar mingguan dan tidak ada gaji pokok bulanan.</small>
                                        </div>
                                    </div>
                                @else
                                    <!-- Gaji Pokok -->
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="basic_salary">Gaji Pokok (Rp) <span class="text-danger">*</span></label>
                                            <div class="input-group"
                                                 wire:key="emp-basic-salary-wrap-{{ $form->employee?->id ?? 'new' }}"
                                                 x-data="{
                                                     rawVal: @entangle('form.basic_salary'),
                                                     format(v) {
                                                         if (!v || isNaN(v) || v == 0) return '';
                                                         return new Intl.NumberFormat('id-ID').format(v);
                                                     }
                                                 }"
                                                 x-init="$refs.input && ($refs.input.value = format(rawVal))"
                                                 x-effect="if ($refs.input) { let f = format(rawVal); if ($refs.input.value !== f) { $refs.input.value = f; } }">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text font-weight-500">Rp</span>
                                                </div>
                                                <input type="text"
                                                       x-ref="input"
                                                       id="basic_salary"
                                                       inputmode="numeric"
                                                       x-on:input="
                                                           let raw = $el.value.replace(/\D/g, '');
                                                           $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                                           rawVal = raw ? parseFloat(raw) : 0;
                                                       "
                                                       class="form-control text-right @error('form.basic_salary') is-invalid @enderror"
                                                       placeholder="0"
                                                       autocomplete="off">
                                            </div>
                                            @error('form.basic_salary')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                <!-- Nominal Lembur per Jam -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="overtime_rate_per_hour">Nominal Lembur per Jam (Rp)</label>
                                        <div class="input-group"
                                             wire:key="emp-ot-hour-wrap-{{ $form->employee?->id ?? 'new' }}"
                                             x-data="{
                                                 rawVal: @entangle('form.overtime_rate_per_hour'),
                                                 format(v) {
                                                     if (!v || isNaN(v) || v == 0) return '';
                                                     return new Intl.NumberFormat('id-ID').format(v);
                                                 }
                                             }"
                                             x-init="$refs.input && ($refs.input.value = format(rawVal))"
                                             x-effect="if ($refs.input) { let f = format(rawVal); if ($refs.input.value !== f) { $refs.input.value = f; } }">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text font-weight-500">Rp</span>
                                            </div>
                                            <input type="text"
                                                   x-ref="input"
                                                   id="overtime_rate_per_hour"
                                                   inputmode="numeric"
                                                   x-on:input="
                                                       let raw = $el.value.replace(/\D/g, '');
                                                       $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                                       rawVal = raw ? parseFloat(raw) : 0;
                                                   "
                                                   class="form-control text-right @error('form.overtime_rate_per_hour') is-invalid @enderror"
                                                   placeholder="0"
                                                   autocomplete="off">
                                        </div>
                                        @error('form.overtime_rate_per_hour')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Nominal Lembur Harian (Seharian Penuh) -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="overtime_rate_per_day">Nominal Lembur Harian (Rp)</label>
                                        <div class="input-group"
                                             wire:key="emp-ot-day-wrap-{{ $form->employee?->id ?? 'new' }}"
                                             x-data="{
                                                 rawVal: @entangle('form.overtime_rate_per_day'),
                                                 format(v) {
                                                     if (!v || isNaN(v) || v == 0) return '';
                                                     return new Intl.NumberFormat('id-ID').format(v);
                                                 }
                                             }"
                                             x-init="$refs.input && ($refs.input.value = format(rawVal))"
                                             x-effect="if ($refs.input) { let f = format(rawVal); if ($refs.input.value !== f) { $refs.input.value = f; } }">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text font-weight-500">Rp</span>
                                            </div>
                                            <input type="text"
                                                   x-ref="input"
                                                   id="overtime_rate_per_day"
                                                   inputmode="numeric"
                                                   x-on:input="
                                                       let raw = $el.value.replace(/\D/g, '');
                                                       $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                                       rawVal = raw ? parseFloat(raw) : 0;
                                                   "
                                                   class="form-control text-right @error('form.overtime_rate_per_day') is-invalid @enderror"
                                                   placeholder="0"
                                                   autocomplete="off">
                                        </div>
                                        @error('form.overtime_rate_per_day')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Pengaturan Uang Makan Harian -->
                                @php
                                    $companyProfile = \App\Models\CompanyProfile::getProfile();
                                    $isUniformMeal = (bool) ($companyProfile->is_uniform_meal_allowance ?? true);
                                    $defaultMealRate = (float) ($companyProfile->default_meal_allowance_per_day ?? 25000);
                                @endphp
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="meal_allowance_per_day" class="d-flex align-items-center">
                                            <span>Uang Makan Harian (Rp)</span>
                                            @if ($isUniformMeal)
                                                <i class="fas fa-lock text-muted ml-1 cursor-pointer" data-toggle="tooltip" data-placement="top" title="Terkunci: Mengikuti kebijakan standar profil perusahaan (Rp {{ number_format($defaultMealRate, 0, ',', '.') }}/hari). Ubah di menu Profil Perusahaan jika ingin mode nominal berbeda per karyawan."></i>
                                            @else
                                                <i class="fas fa-info-circle text-muted ml-1 cursor-pointer" data-toggle="tooltip" data-placement="top" title="Mode Fleksibel Aktif: Nominal uang makan dapat disesuaikan secara khusus untuk karyawan ini."></i>
                                            @endif
                                        </label>

                                        <div class="input-group"
                                             wire:key="emp-meal-rate-wrap-{{ $form->employee?->id ?? 'new' }}"
                                             x-data="{
                                                 rawVal: @entangle('form.meal_allowance_per_day'),
                                                 defaultRate: {{ $defaultMealRate }},
                                                 format(v) {
                                                     let num = (!v || isNaN(v) || v == 0) ? this.defaultRate : v;
                                                     return new Intl.NumberFormat('id-ID').format(num);
                                                 }
                                             }"
                                             @unless($isUniformMeal)
                                             x-init="$refs.input && ($refs.input.value = format(rawVal))"
                                             x-effect="if ($refs.input) { let f = format(rawVal); if ($refs.input.value !== f) { $refs.input.value = f; } }"
                                             @endunless>
                                            <div class="input-group-prepend">
                                                <span class="input-group-text font-weight-500">Rp</span>
                                            </div>
                                            @if ($isUniformMeal)
                                                <input type="text"
                                                       id="meal_allowance_per_day"
                                                       value="{{ number_format($defaultMealRate, 0, ',', '.') }}"
                                                       class="form-control text-right font-weight-500"
                                                       style="background-color: #f1f5f9; color: #475569;"
                                                       readonly
                                                       disabled
                                                       title="Mengikuti nominal standar perusahaan">
                                            @else
                                                <input type="text"
                                                       x-ref="input"
                                                       id="meal_allowance_per_day"
                                                       inputmode="numeric"
                                                       x-on:input="
                                                           let raw = $el.value.replace(/\D/g, '');
                                                           $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                                           rawVal = raw ? parseFloat(raw) : 0;
                                                       "
                                                       class="form-control text-right @error('form.meal_allowance_per_day') is-invalid @enderror"
                                                       placeholder="{{ number_format($defaultMealRate, 0, ',', '.') }}"
                                                       autocomplete="off">
                                            @endif
                                        </div>
                                        @error('form.meal_allowance_per_day')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Total Kasbon (Read-only / Terkalkulasi Otomatis) -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="total_cash_receipt">Total Kasbon</label>
                                        <input type="text" id="total_cash_receipt" value="{{ $form->formatted_total_cash_receipt }}" class="form-control font-medium" readonly disabled>
                                    </div>
                                </div>

                                <!-- Upload Foto (Template Custom-File Component) -->
                                <div class="col-md-12">
                                    <div class="form-group mb-1">
                                        <label for="photo" class="d-flex align-items-center">
                                            <span>Foto Karyawan</span>
                                            <small class="text-muted ml-2 font-weight-normal">(JPG/PNG, maks. 2MB - Opsional)</small>
                                        </label>
                                        <div class="d-flex align-items-center">
                                            @if ($form->photo)
                                                <img src="{{ $form->photo->temporaryUrl() }}" alt="Preview" width="42" height="42" class="rounded-circle mr-3 shadow-sm border" style="object-fit: cover;">
                                            @elseif ($form->employee?->photo_path)
                                                <img src="{{ $form->employee->photo_url }}" alt="Current Photo" width="42" height="42" class="rounded-circle mr-3 shadow-sm border" style="object-fit: cover;">
                                            @endif
                                            <div class="custom-file flex-grow-1">
                                                <input type="file" id="photo" wire:model="form.photo" accept="image/png,image/jpeg,image/jpg" class="custom-file-input @error('form.photo') is-invalid @enderror">
                                                <label class="custom-file-label text-truncate" for="photo">
                                                    {{ $form->photo ? $form->photo->getClientOriginalName() : ($form->employee?->photo_path ? basename($form->employee->photo_path) : 'Pilih Foto Karyawan...') }}
                                                </label>
                                            </div>
                                        </div>
                                        @error('form.photo')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                        <div wire:loading wire:target="form.photo" class="text-primary small mt-1">
                                            <i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah foto...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Detail Pribadi & Rekening Bank (Opsional) -->
                        <div class="tab-pane fade" id="emp-pill-pribadi" role="tabpanel" aria-labelledby="personal-tab">
                            <div class="row">
                                <!-- Kode Karyawan -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="employee_code">Kode Karyawan</label>
                                        <input type="text" id="employee_code" wire:model="form.employee_code" class="form-control @error('form.employee_code') is-invalid @enderror" placeholder="Otomatis jika dikosongkan" autocomplete="off">
                                        @error('form.employee_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Jenis Kelamin (Native Clean Select) -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="gender">Jenis Kelamin</label>
                                        <select id="gender" wire:model="form.gender" class="form-control @error('form.gender') is-invalid @enderror">
                                            @foreach ($genders as $g)
                                                <option value="{{ $g->value }}">{{ $g->label() }}</option>
                                            @endforeach
                                        </select>
                                        @error('form.gender')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Tempat Lahir -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="birth_place">Tempat Lahir</label>
                                        <input type="text" id="birth_place" wire:model="form.birth_place" class="form-control @error('form.birth_place') is-invalid @enderror" placeholder="Contoh: Jakarta" autocomplete="off">
                                        @error('form.birth_place')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Tanggal Lahir -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="birth_date">Tanggal Lahir</label>
                                        <input type="date" id="birth_date" wire:model="form.birth_date" class="form-control @error('form.birth_date') is-invalid @enderror">
                                        @error('form.birth_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- No Telepon -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="phone">No. Telepon / WhatsApp</label>
                                        <input type="text" id="phone" wire:model="form.phone" class="form-control @error('form.phone') is-invalid @enderror" placeholder="Contoh: 08123456789" autocomplete="off">
                                        @error('form.phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Email Akun Login (Global Company Domain) -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label for="email_username" class="mb-0 d-flex align-items-center">
                                                <span>Email Akun Login</span>
                                                <i class="fas fa-info-circle text-dark ml-1 cursor-pointer" data-toggle="tooltip" data-placement="top" title="Domain email perusahaan bersifat global. Username otomatis dibuat dari nama karyawan."></i>
                                            </label>
                                            <button type="button" wire:click="generateEmailFromName" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 11px;" title="Generate username email otomatis dari nama karyawan">
                                                <i class="fas fa-magic mr-1"></i> Auto
                                            </button>
                                        </div>
                                        <div class="input-group">
                                            <input type="text" id="email_username" wire:model.live.debounce.400ms="form.email_username" class="form-control @error('form.email') is-invalid @enderror @error('form.email_username') is-invalid @enderror" placeholder="nama.karyawan" autocomplete="off">
                                            <div class="input-group-append">
                                                <span class="input-group-text font-weight-500 text-dark">@{{ $companyEmailDomain }}</span>
                                            </div>
                                        </div>
                                        @error('form.email')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                        @error('form.email_username')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Role Akun Login -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="form_role">Role Akun Login <span class="text-danger">*</span></label>
                                        <select id="form_role" wire:model="form.role" class="form-control @error('form.role') is-invalid @enderror">
                                            <option value="karyawan">Karyawan</option>
                                            <option value="admin">Administrator</option>
                                            <option value="direktur">Direktur</option>
                                        </select>
                                        @error('form.role')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Password Akun -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="form_password">
                                            <span>Password Akun</span>
                                            <small class="text-dark ml-1 font-weight-normal">({{ $isEditMode ? 'Kosongkan jika tidak diubah' : 'Default: 1' }})</small>
                                        </label>
                                        <div class="input-group" x-data="{ showPass: false }">
                                            <input :type="showPass ? 'text' : 'password'" id="form_password" wire:model="form.password" class="form-control @error('form.password') is-invalid @enderror" placeholder="{{ $isEditMode ? 'Kosongkan jika tidak diubah' : '1' }}" autocomplete="new-password">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" @click="showPass = !showPass" tabindex="-1" title="Tampilkan/Sembunyikan Password">
                                                    <i class="fas" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                                </button>
                                            </div>
                                        </div>
                                        @error('form.password')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Alamat Domisili -->
                                <div class="col-md-12">
                                    <div class="form-group mb-2">
                                        <label for="address">Alamat Domisili</label>
                                        <textarea id="address" wire:model="form.address" rows="2" class="form-control @error('form.address') is-invalid @enderror" placeholder="Alamat lengkap tempat tinggal saat ini..." style="height: 55px;"></textarea>
                                        @error('form.address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Nama Bank -->
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label for="bank_name">Nama Bank</label>
                                        <input type="text" id="bank_name" wire:model="form.bank_name" class="form-control @error('form.bank_name') is-invalid @enderror" placeholder="BCA / Mandiri" autocomplete="off">
                                        @error('form.bank_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Nomor Rekening -->
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label for="bank_account_number">No. Rekening</label>
                                        <input type="text" id="bank_account_number" wire:model="form.bank_account_number" class="form-control @error('form.bank_account_number') is-invalid @enderror" placeholder="1234567890" autocomplete="off">
                                        @error('form.bank_account_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Atas Nama Rekening -->
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label for="bank_account_name">Atas Nama</label>
                                        <input type="text" id="bank_account_name" wire:model="form.bank_account_name" class="form-control @error('form.bank_account_name') is-invalid @enderror" placeholder="Sesuai buku tabungan" autocomplete="off">
                                        @error('form.bank_account_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Upload CV (Template Custom-File Component) -->
                                <div class="col-md-12">
                                    <div class="form-group mb-1">
                                        <label for="curriculum_vitae" class="d-flex align-items-center">
                                            <span>File CV / Resume</span>
                                            <small class="text-muted ml-2 font-weight-normal">(PDF/DOC/DOCX, maks. 5MB - Opsional)</small>
                                        </label>
                                        <div class="custom-file">
                                            <input type="file" id="curriculum_vitae" wire:model="form.curriculum_vitae" accept=".pdf,.doc,.docx" class="custom-file-input @error('form.curriculum_vitae') is-invalid @enderror">
                                            <label class="custom-file-label text-truncate" for="curriculum_vitae">
                                                {{ $form->curriculum_vitae ? $form->curriculum_vitae->getClientOriginalName() : ($form->employee?->detail?->curriculum_vitae ? basename($form->employee->detail->curriculum_vitae) : 'Pilih Berkas CV / Resume...') }}
                                            </label>
                                        </div>
                                        @error('form.curriculum_vitae')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                        <div wire:loading wire:target="form.curriculum_vitae" class="text-primary small mt-1">
                                            <i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah berkas CV...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Pajak & BPJS -->
                        <div class="tab-pane fade" id="emp-pill-pajak" role="tabpanel" aria-labelledby="tax-tab">
                            <div class="row">
                                <!-- Status Pernikahan -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="marital_status">Status Pernikahan <span class="text-danger">*</span></label>
                                        <select id="marital_status" wire:model="form.marital_status" class="form-control @error('form.marital_status') is-invalid @enderror">
                                            <option value="tk">Tidak Kawin (TK)</option>
                                            <option value="k">Kawin (K)</option>
                                            <option value="hb">Hidup Berpisah (HB)</option>
                                        </select>
                                        @error('form.marital_status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Jumlah Tanggungan -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="dependents_count">Jumlah Tanggungan</label>
                                        <select id="dependents_count" wire:model="form.dependents_count" class="form-control @error('form.dependents_count') is-invalid @enderror">
                                            <option value="0">0 Tanggungan</option>
                                            <option value="1">1 Tanggungan</option>
                                            <option value="2">2 Tanggungan</option>
                                            <option value="3">3 Tanggungan (Maksimal)</option>
                                        </select>
                                        @error('form.dependents_count')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="tax_recipient_type" class="d-flex align-items-center">
                                            <span>Jenis Penerima Penghasilan <span class="text-danger">*</span></span>
                                            <i class="fas fa-info-circle text-muted ml-1 cursor-pointer" data-toggle="tooltip" data-placement="top" title="Kontrak penuh waktu bergaji bulanan termasuk Pegawai Tetap untuk PPh 21. Pilih di luar cakupan untuk harian, borongan, atau magang."></i>
                                        </label>
                                        <select id="tax_recipient_type" wire:model="form.tax_recipient_type" class="form-control @error('form.tax_recipient_type') is-invalid @enderror">
                                            <option value="">-- Pilih jenis pajak --</option>
                                            <option value="monthly_employee">Pegawai Tetap PPh 21 (gaji bulanan, termasuk kontrak)</option>
                                            <option value="outside_scope">Di luar cakupan saat ini (harian, borongan, magang)</option>
                                        </select>
                                        @error('form.tax_recipient_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Metode Perhitungan Pajak -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="tax_method">Metode PPh 21 <span class="text-danger">*</span></label>
                                        <select id="tax_method" wire:model="form.tax_method" class="form-control @error('form.tax_method') is-invalid @enderror">
                                            <option value="gross">Gross (Dipotong dari Gaji Karyawan)</option>
                                            <option value="gross_up">Gross-Up (Ditanggung Perusahaan via Tunjangan Pajak)</option>
                                            <option value="net">Nett (Ditanggung Perusahaan Langsung)</option>
                                        </select>
                                        @error('form.tax_method')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- NIK KTP -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="nik">NIK KTP</label>
                                        <input type="text" id="nik" wire:model="form.nik" class="form-control @error('form.nik') is-invalid @enderror" placeholder="16 digit NIK KTP" autocomplete="off">
                                        @error('form.nik')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- NPWP -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="npwp_number" class="d-flex align-items-center">
                                            <span>Nomor NPWP</span>
                                            <i class="fas fa-info-circle text-muted ml-1 cursor-pointer" data-toggle="tooltip" data-placement="top" title="Jika belum memiliki kartu NPWP fisik, dapat menggunakan NIK 16 digit yang telah terintegrasi DJP."></i>
                                        </label>
                                        <input type="text" id="npwp_number" wire:model="form.npwp_number" class="form-control @error('form.npwp_number') is-invalid @enderror" placeholder="Contoh: 12.345.678.9-012.000" autocomplete="off">
                                        @error('form.npwp_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- BPJS Kesehatan -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="bpjs_kesehatan_number">BPJS Kesehatan</label>
                                        <input type="text" id="bpjs_kesehatan_number" wire:model="form.bpjs_kesehatan_number" class="form-control @error('form.bpjs_kesehatan_number') is-invalid @enderror" placeholder="Nomor kartu BPJS Kesehatan" autocomplete="off">
                                        @error('form.bpjs_kesehatan_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- BPJS Ketenagakerjaan -->
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="bpjs_ketenagakerjaan_number">BPJS Ketenagakerjaan</label>
                                        <input type="text" id="bpjs_ketenagakerjaan_number" wire:model="form.bpjs_ketenagakerjaan_number" class="form-control @error('form.bpjs_ketenagakerjaan_number') is-invalid @enderror" placeholder="Nomor KPJ BPJS TK" autocomplete="off">
                                        @error('form.bpjs_ketenagakerjaan_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="closeModal"><i class="fas fa-times mr-1"></i> Batal</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="update, store"><span wire:loading wire:target="update, store"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span><span wire:loading.remove wire:target="update, store"><i class="fas fa-save mr-1"></i> {{ $isEditMode ? 'Simpan Perubahan' : 'Tambah Data' }}</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* 3 Tabs Pas 1 Baris: Simetris & Bebas Scrollbar */
    #employeeFormPills {
        display: flex;
        flex-wrap: nowrap;
        gap: 6px;
        width: 100%;
        margin-bottom: 1rem;
    }
    #employeeFormPills .nav-item {
        flex: 1 1 0;
        min-width: 0;
        margin: 0;
    }
    #employeeFormPills .nav-link {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 4px;
        font-size: 13px;
        white-space: nowrap;
        border-radius: 4px;
    }
    @media (max-width: 575.98px) {
        #employeeFormPills .nav-link {
            font-size: 11px;
            padding: 7px 2px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#employeeModal').on('shown.bs.modal', function () {
            $(this).find('[data-toggle="tooltip"]').tooltip({
                boundary: 'window',
                trigger: 'hover focus'
            });
        });
    });
</script>
