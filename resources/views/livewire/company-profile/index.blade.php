<div>
    <x-slot name="header">
        <h1>Profil Perusahaan</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Pengaturan</div>
            <div class="breadcrumb-item">Profil Perusahaan</div>
        </div>
    </x-slot>

    <h2 class="section-title">Profil &amp; Identitas Perusahaan</h2>
    <p class="section-lead">
        Kelola identitas, kontak resmi, logo perusahaan, kebijakan uang makan, dan informasi rekening untuk kop surat slip gaji serta laporan resmi.
    </p>

    <div class="row">
        <!-- Kolom Kiri: Logo & Info Tambahan -->
        <div class="col-12 col-md-4">
            <!-- 1. Card Logo Perusahaan -->
            <div class="card">
                <div class="card-header">
                    <h4>Logo Perusahaan</h4>
                </div>
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center align-items-center mb-2">
                        @if ($logo)
                            <div class="p-2 border rounded shadow-sm bg-white d-inline-flex justify-content-center align-items-center" style="min-height: 140px; min-width: 140px; max-width: 180px;">
                                <img src="{{ $logo->temporaryUrl() }}" alt="Preview Logo" class="img-fluid" style="max-height: 120px; max-width: 160px; object-fit: contain;">
                            </div>
                        @elseif ($currentLogoUrl)
                            <div class="p-2 border rounded shadow-sm bg-white d-inline-flex justify-content-center align-items-center" style="min-height: 140px; min-width: 140px; max-width: 180px;">
                                <img src="{{ $currentLogoUrl }}" alt="Logo Perusahaan" class="img-fluid" style="max-height: 120px; max-width: 160px; object-fit: contain;">
                            </div>
                        @else
                            <div class="p-4 bg-white text-muted text-center border shadow-sm" style="min-width: 140px; border-radius: 12px; border-color: #e2e8f0;">
                                <i class="fas fa-building fa-3x mb-2 text-muted"></i>
                                <div class="small">Belum ada logo</div>
                            </div>
                        @endif
                    </div>
                    <div class="small text-muted mb-3 font-weight-500">
                        {{ $logo ? 'Preview Logo Baru' : ($currentLogoUrl ? 'Logo Saat Ini' : 'Belum Ada Logo') }}
                    </div>

                    <div class="form-group text-left mb-0">
                        <label for="logo" class="d-block mb-2">Unggah Logo Baru</label>
                        <div class="custom-file">
                            <input type="file" id="logo" wire:model="logo" class="custom-file-input @error('logo') is-invalid @enderror" accept="image/png, image/jpeg, image/jpg">
                            <label class="custom-file-label text-truncate" for="logo">
                                {{ $logo ? $logo->getClientOriginalName() : 'Pilih File Logo...' }}
                            </label>
                        </div>
                        <small class="form-text text-muted mt-2">Format: PNG, JPG, JPEG (Maks. 2MB)</small>
                        @error('logo')
                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                        @enderror

                        <div wire:loading wire:target="logo" class="text-primary small mt-2">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah preview...
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Card Informasi Tambahan -->
            <div class="card">
                <div class="card-header">
                    <h4>Informasi Tambahan</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-0">
                        Informasi profil perusahaan ini akan otomatis tercantum pada bagian kop atas dokumen <strong>Slip Gaji</strong> dan laporan rekapitulasi penggajian karyawan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Pengaturan Utama dengan Nav Tabs -->
        <div class="col-12 col-md-8">
            <form wire:submit="save">
                <div class="card">
                    <div class="card-header">
                        <h4>Pengaturan Profil Perusahaan</h4>
                    </div>
                    <div class="card-body">
                        <!-- Navigation Pills -->
                        <ul class="nav nav-pills mb-4" id="companyProfilePills" role="tablist" style="gap: 6px;">
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'identitas' ? 'active' : '' }} font-weight-600"
                                   href="javascript:void(0)"
                                   wire:click="setTab('identitas')"
                                   role="tab">
                                    <i class="fas fa-building mr-1.5"></i> Identitas &amp; Kontak
                                    @if ($errors->hasAny(['name', 'code', 'email', 'phone', 'website', 'email_domain', 'address', 'city', 'province', 'postal_code', 'tax_identification_number']))
                                        <span class="badge badge-danger ml-1 px-1.5 py-0.5" style="font-size: 10px;">!</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'penggajian' ? 'active' : '' }} font-weight-600"
                                   href="javascript:void(0)"
                                   wire:click="setTab('penggajian')"
                                   role="tab">
                                    <i class="fas fa-calendar-alt mr-1.5"></i> Penggajian &amp; Uang Makan
                                    @if ($errors->hasAny(['work_days_per_week', 'payroll_cutoff_day', 'payroll_pay_day', 'default_meal_allowance_per_day', 'overtime_rounding_mode']))
                                        <span class="badge badge-danger ml-1 px-1.5 py-0.5" style="font-size: 10px;">!</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'pajak' ? 'active' : '' }} font-weight-600"
                                   href="javascript:void(0)"
                                   wire:click="setTab('pajak')"
                                   role="tab">
                                    <i class="fas fa-file-invoice-dollar mr-1.5"></i> Pajak &amp; BPJS
                                    @if ($errors->hasAny(['bpjs_jkk_risk_rate', 'bpjs_jp_cap']))
                                        <span class="badge badge-danger ml-1 px-1.5 py-0.5" style="font-size: 10px;">!</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'bank' ? 'active' : '' }} font-weight-600"
                                   href="javascript:void(0)"
                                   wire:click="setTab('bank')"
                                   role="tab">
                                    <i class="fas fa-university mr-1.5"></i> Rekening Bank
                                    @if ($errors->hasAny(['bank_name', 'bank_account_number', 'bank_account_name']))
                                        <span class="badge badge-danger ml-1 px-1.5 py-0.5" style="font-size: 10px;">!</span>
                                    @endif
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" id="companyProfileTabContent">
                            <!-- TAB 1: IDENTITAS & KONTAK -->
                            <div class="tab-pane fade {{ $activeTab === 'identitas' ? 'show active' : 'd-none' }}" id="tab-identitas" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="name">Nama Perusahaan <span class="text-danger">*</span></label>
                                            <input type="text" id="name" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: PT Larashiz Sejahtera Indonesia">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="code">Kode / Inisial</label>
                                            <input type="text" id="code" wire:model="code" class="form-control @error('code') is-invalid @enderror" placeholder="Contoh: LARASHIZ">
                                            @error('code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax_identification_number">NPWP Perusahaan</label>
                                            <input type="text" id="tax_identification_number" wire:model="tax_identification_number" class="form-control @error('tax_identification_number') is-invalid @enderror" placeholder="Contoh: 01.234.567.8-901.000">
                                            @error('tax_identification_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone">Nomor Telepon Kantor</label>
                                            <input type="text" id="phone" wire:model="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="Contoh: 021-12345678">
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email Resmi</label>
                                            <input type="email" id="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="Contoh: hrd@perusahaan.com">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="website">Website Resmi</label>
                                            <input type="text" id="website" wire:model="website" class="form-control @error('website') is-invalid @enderror" placeholder="Contoh: https://perusahaan.com">
                                            @error('website')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="email_domain">
                                                Domain Email Karyawan
                                                <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" data-placement="top" title="Domain ini digunakan sebagai akhiran otomatis saat membuat email karyawan baru via tombol Auto."></i>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text font-weight-bold">@</span>
                                                </div>
                                                <input type="text" id="email_domain" wire:model="email_domain" class="form-control @error('email_domain') is-invalid @enderror" placeholder="Contoh: namaperusahaan.com">
                                            </div>
                                            @error('email_domain')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="address">Alamat Kantor Lengkap</label>
                                    <textarea id="address" wire:model="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Nama jalan, gedung, nomor, RT/RW, kelurahan/kecamatan"></textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="city">Kota / Kabupaten</label>
                                            <input type="text" id="city" wire:model="city" class="form-control @error('city') is-invalid @enderror" placeholder="Contoh: Jakarta Selatan">
                                            @error('city')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="province">Provinsi</label>
                                            <input type="text" id="province" wire:model="province" class="form-control @error('province') is-invalid @enderror" placeholder="Contoh: DKI Jakarta">
                                            @error('province')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="postal_code">Kode Pos</label>
                                            <input type="text" id="postal_code" wire:model="postal_code" class="form-control @error('postal_code') is-invalid @enderror" placeholder="Contoh: 12190">
                                            @error('postal_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: PENGGAJIAN & UANG MAKAN -->
                            <div class="tab-pane fade {{ $activeTab === 'penggajian' ? 'show active' : 'd-none' }}" id="tab-penggajian" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-group mb-0">
                                            <label for="work_days_per_week">
                                                Skema Hari Kerja
                                                <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" data-placement="top" title="Standar hari kerja per minggu untuk pembagi perhitungan gaji prorata."></i>
                                            </label>
                                            <select id="work_days_per_week" wire:model="work_days_per_week" class="form-control @error('work_days_per_week') is-invalid @enderror">
                                                <option value="5">5 Hari Kerja (Senin - Jumat)</option>
                                                <option value="6">6 Hari Kerja (Senin - Sabtu)</option>
                                            </select>
                                            @error('work_days_per_week')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-group mb-0">
                                            <label for="payroll_cutoff_day">
                                                Tanggal Cut-Off
                                                <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" data-placement="top" title="Tanggal tutup buku rekap gaji (misal tgl 20: siklus kerja tgl 21 bulan lalu s/d tgl 20 bulan ini)."></i>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                                </div>
                                                <input type="number" id="payroll_cutoff_day" min="1" max="31" wire:model="payroll_cutoff_day" class="form-control @error('payroll_cutoff_day') is-invalid @enderror" placeholder="20">
                                            </div>
                                            @error('payroll_cutoff_day')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-group mb-0">
                                            <label for="payroll_pay_day">
                                                Tanggal Gajian Default
                                                <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" data-placement="top" title="Tanggal default transfer penggajian ke rekening karyawan (misal tgl 25)."></i>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                                </div>
                                                <input type="number" id="payroll_pay_day" min="1" max="31" wire:model="payroll_pay_day" class="form-control @error('payroll_pay_day') is-invalid @enderror" placeholder="25">
                                            </div>
                                            @error('payroll_pay_day')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="section-title mt-4 mb-2" style="font-size: 14px;">Kebijakan Uang Makan Harian</div>

                                <div class="card border shadow-sm mb-3" style="background-color: #ffffff; border-radius: 12px; border-color: #e9edf2;">
                                    <div class="card-body p-3">
                                        <div class="row align-items-center">
                                            <!-- Switch Toggle & Keterangan Mode -->
                                            <div class="col-md-7 mb-3 mb-md-0">
                                                <label class="custom-switch pl-0 cursor-pointer mb-1" for="is_uniform_meal_allowance">
                                                    <input type="checkbox" wire:model.live="is_uniform_meal_allowance" class="custom-switch-input" id="is_uniform_meal_allowance">
                                                    <span class="custom-switch-indicator"></span>
                                                    <span class="custom-switch-description">
                                                        Terapkan Nominal Standar untuk Seluruh Karyawan
                                                    </span>
                                                </label>
                                                <div class="d-flex align-items-center mt-2" style="font-size: 12px; line-height: 1.45; gap: 8px;">
                                                    @if ($is_uniform_meal_allowance)
                                                        <span class="badge badge-primary px-2 py-1 text-nowrap">
                                                            <i class="fas fa-lock mr-1"></i> Mode Standar Aktif
                                                        </span>
                                                        <span class="text-muted">Seluruh karyawan otomatis mengikuti nominal standar perusahaan.</span>
                                                    @else
                                                        <span class="badge badge-warning px-2 py-1 text-nowrap">
                                                            <i class="fas fa-unlock-alt mr-1"></i> Mode Fleksibel Aktif
                                                        </span>
                                                        <span class="text-muted">Nominal uang makan dapat disesuaikan per individu di form Data Karyawan.</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Input Nominal Standar -->
                                            <div class="col-md-5">
                                                <div class="form-group mb-0">
                                                    <label for="default_meal_allowance_per_day">
                                                        Nominal Standar Perusahaan <small class="text-muted">(Rp/hari)</small>
                                                    </label>
                                                    <div class="input-group" x-data>
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text font-weight-600">Rp</span>
                                                        </div>
                                                        <input type="text"
                                                               id="default_meal_allowance_per_day"
                                                               inputmode="numeric"
                                                               x-on:input="
                                                                   let raw = $el.value.replace(/\D/g, '');
                                                                   $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                                                   $wire.$set('default_meal_allowance_per_day', raw ? parseFloat(raw) : 0, false);
                                                               "
                                                               value="{{ !empty($default_meal_allowance_per_day) ? number_format((float) $default_meal_allowance_per_day, 0, ',', '.') : '25.000' }}"
                                                               class="form-control text-right @error('default_meal_allowance_per_day') is-invalid @enderror"
                                                               placeholder="25.000"
                                                               autocomplete="off">
                                                    </div>
                                                    @error('default_meal_allowance_per_day')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="section-title mt-4 mb-2" style="font-size: 14px;">Kebijakan Jam Lembur</div>

                                <div class="card border shadow-sm mb-3" style="background-color: #ffffff; border-radius: 12px; border-color: #e9edf2;">
                                    <div class="card-body p-3">
                                        <div class="row align-items-center {{ $overtime_rounding_mode === 'round_60_min' ? 'mb-3' : '' }}">
                                            <div class="col-md-7 mb-3 mb-md-0">
                                                <label for="overtime_rounding_mode" class="d-block font-weight-600 mb-1">
                                                    Aturan Pembulatan Durasi Lembur
                                                </label>
                                                <div class="text-muted" style="font-size: 12px; line-height: 1.45;">
                                                    Pilih metode pembulatan jam lembur saat pengajuan lembur dihitung ke total jam dan upah lembur.
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group mb-0">
                                                    <select id="overtime_rounding_mode" wire:model.live="overtime_rounding_mode" class="form-control @error('overtime_rounding_mode') is-invalid @enderror">
                                                        <option value="round_60_min">Kelipatan 1 Jam Penuh (Default / Rekomendasi)</option>
                                                        <option value="round_30_min">Kelipatan 30 Menit (0.5 Jam)</option>
                                                        <option value="floor_hour">Jam Penuh ke Bawah (Floor)</option>
                                                        <option value="exact">Menit Riil / Eksak (Tanpa Pembulatan)</option>
                                                    </select>
                                                    @error('overtime_rounding_mode')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        @if ($overtime_rounding_mode === 'round_60_min')
                                            <div class="row align-items-center pt-3 border-top" style="border-color: #f1f5f9 !important;">
                                                <div class="col-md-7 mb-3 mb-md-0">
                                                    <label for="overtime_rounding_threshold_minutes" class="d-block font-weight-600 mb-1">
                                                        Batas Menit Dianggap 1 Jam Penuh
                                                    </label>
                                                    <div class="text-muted" style="font-size: 12px; line-height: 1.45;">
                                                        Kelebihan menit yang mencapai batas ini otomatis dihitung 1 jam penuh berikutnya. Contoh: diatur <strong>10 menit</strong>, maka lembur 1 jam 10 menit otomatis dihitung 2 jam.
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="form-group mb-0">
                                                        <div class="input-group">
                                                            <input type="number"
                                                                   id="overtime_rounding_threshold_minutes"
                                                                   wire:model="overtime_rounding_threshold_minutes"
                                                                   min="1"
                                                                   max="60"
                                                                   class="form-control text-right @error('overtime_rounding_threshold_minutes') is-invalid @enderror"
                                                                   placeholder="10">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text bg-white" style="border-left: 0; color: #64748b; font-size: 13px;">Menit</span>
                                                            </div>
                                                        </div>
                                                        @error('overtime_rounding_threshold_minutes')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: PAJAK PPH 21 & BPJS -->
                            <div class="tab-pane fade {{ $activeTab === 'pajak' ? 'show active' : 'd-none' }}" id="tab-pajak" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="custom-switch pl-0 cursor-pointer mb-0" for="enable_pph21">
                                            <input type="checkbox" wire:model="enable_pph21" class="custom-switch-input" id="enable_pph21">
                                            <span class="custom-switch-indicator"></span>
                                            <span class="custom-switch-description">
                                                Aktifkan PPh 21 Otomatis
                                            </span>
                                            <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" data-placement="top" title="Menghitung PPh 21 karyawan secara otomatis berdasarkan Tarif Efektif Rata-Rata (TER PP 58/2023)."></i>
                                        </label>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="custom-switch pl-0 cursor-pointer mb-0" for="include_bpjs_in_tax">
                                            <input type="checkbox" wire:model="include_bpjs_in_tax" class="custom-switch-input" id="include_bpjs_in_tax">
                                            <span class="custom-switch-indicator"></span>
                                            <span class="custom-switch-description">
                                                Sertakan BPJS Perusahaan ke Bruto
                                            </span>
                                            <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" data-placement="top" title="Premi JKK, JKM, dan BPJS Kesehatan perusahaan dihitung sebagai penambah penghasilan bruto kena pajak."></i>
                                        </label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bpjs_jkk_risk_rate">Tingkat Risiko BPJS JKK (Perusahaan)</label>
                                            <select id="bpjs_jkk_risk_rate" wire:model="bpjs_jkk_risk_rate" class="form-control @error('bpjs_jkk_risk_rate') is-invalid @enderror">
                                                <option value="0.0024">Kelompok I: 0.24% (Sangat Rendah)</option>
                                                <option value="0.0054">Kelompok II: 0.54% (Rendah)</option>
                                                <option value="0.0089">Kelompok III: 0.89% (Sedang)</option>
                                                <option value="0.0127">Kelompok IV: 1.27% (Tinggi)</option>
                                                <option value="0.0174">Kelompok V: 1.74% (Sangat Tinggi)</option>
                                            </select>
                                            @error('bpjs_jkk_risk_rate')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bpjs_jp_cap">
                                                Plafon Maksimal BPJS Pensiun (JP)
                                                <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" data-placement="top" title="Batas maksimal dasar upah JP tahun berjalan (sesuaikan jika ada penyesuaian regulasi Kemnaker per tahun berjalan)."></i>
                                            </label>
                                            <div class="input-group" x-data>
                                                <div class="input-group-prepend"><span class="input-group-text font-weight-600">Rp</span></div>
                                                <input type="text"
                                                       id="bpjs_jp_cap"
                                                       inputmode="numeric"
                                                       x-on:input="
                                                           let raw = $el.value.replace(/\D/g, '');
                                                           $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                                           $wire.$set('bpjs_jp_cap', raw ? parseFloat(raw) : 0, false);
                                                       "
                                                       value="{{ !empty($bpjs_jp_cap) ? number_format((float) $bpjs_jp_cap, 0, ',', '.') : '' }}"
                                                       class="form-control text-right @error('bpjs_jp_cap') is-invalid @enderror"
                                                       placeholder="0"
                                                       autocomplete="off">
                                            </div>
                                            @error('bpjs_jp_cap')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 4: REKENING BANK -->
                            <div class="tab-pane fade {{ $activeTab === 'bank' ? 'show active' : 'd-none' }}" id="tab-bank" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="bank_name">Nama Bank</label>
                                            <input type="text" id="bank_name" wire:model="bank_name" class="form-control @error('bank_name') is-invalid @enderror" placeholder="Contoh: Bank Central Asia (BCA)">
                                            @error('bank_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="bank_account_number">Nomor Rekening</label>
                                            <input type="text" id="bank_account_number" wire:model="bank_account_number" class="form-control @error('bank_account_number') is-invalid @enderror" placeholder="Contoh: 1234567890">
                                            @error('bank_account_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="bank_account_name">Atas Nama Rekening</label>
                                            <input type="text" id="bank_account_name" wire:model="bank_account_name" class="form-control @error('bank_account_name') is-invalid @enderror" placeholder="Contoh: PT Larashiz Payroll">
                                            @error('bank_account_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer: Clean White Border-Top -->
                    <div class="card-footer bg-white border-top text-right">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                <i class="fas fa-save mr-1"></i> Simpan Perubahan
                            </span>
                            <span wire:loading wire:target="save">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('[data-toggle="tooltip"]').tooltip({
            boundary: 'window',
            trigger: 'hover focus'
        });
    });
</script>
