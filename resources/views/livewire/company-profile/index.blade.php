<div>
    <x-slot name="header">
        <h1>Profil Perusahaan</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Pengaturan</div>
            <div class="breadcrumb-item">Profil Perusahaan</div>
        </div>
    </x-slot>

    <h2 class="section-title">Profil & Identitas Perusahaan</h2>
    <p class="section-lead">
        Kelola identitas, kontak resmi, logo perusahaan, dan informasi rekening untuk kop surat slip gaji serta dokumen resmi lainnya.
    </p>

    <div class="row">
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>Logo Perusahaan</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="Preview Logo" class="img-fluid rounded border shadow-sm p-1" style="max-height: 140px; object-fit: contain;">
                            <div class="small text-muted mt-2">Preview Logo Baru</div>
                        @elseif ($currentLogoUrl)
                            <img src="{{ $currentLogoUrl }}" alt="Logo Perusahaan" class="img-fluid rounded border shadow-sm p-1" style="max-height: 140px; object-fit: contain;">
                            <div class="small text-muted mt-2">Logo Saat Ini</div>
                        @else
                            <div class="p-4 bg-light rounded text-muted">
                                <i class="fas fa-building fa-3x mb-2"></i>
                                <div>Belum ada logo</div>
                            </div>
                        @endif
                    </div>

                    <div class="form-group text-left mb-0">
                        <label class="font-weight-600">Unggah Logo Baru</label>
                        <input type="file" wire:model="logo" class="form-control-file @error('logo') is-invalid @enderror" accept="image/png, image/jpeg, image/jpg">
                        <small class="form-text text-muted">Format: PNG, JPG, JPEG (Maks. 2MB)</small>
                        @error('logo')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror

                        <div wire:loading wire:target="logo" class="text-primary small mt-1">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah preview...
                        </div>
                    </div>
                </div>
            </div>

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

        <div class="col-12 col-md-8">
            <form wire:submit="save">
                <div class="card">
                    <div class="card-header">
                        <h4>Data Identitas & Kontak</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="font-weight-600">Nama Perusahaan <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: PT Larashiz Sejahtera Indonesia">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-600">Kode / Inisial</label>
                                    <input type="text" wire:model="code" class="form-control @error('code') is-invalid @enderror" placeholder="Contoh: LARASHIZ">
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">NPWP Perusahaan</label>
                                    <input type="text" wire:model="tax_identification_number" class="form-control @error('tax_identification_number') is-invalid @enderror" placeholder="Contoh: 01.234.567.8-901.000">
                                    @error('tax_identification_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Nomor Telepon Kantor</label>
                                    <input type="text" wire:model="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="Contoh: 021-12345678">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Email Resmi</label>
                                    <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="Contoh: hrd@perusahaan.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Website</label>
                                    <input type="text" wire:model="website" class="form-control @error('website') is-invalid @enderror" placeholder="Contoh: https://perusahaan.com">
                                    @error('website')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="font-weight-600">Domain Email Karyawan</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light font-weight-600 text-primary">@</span>
                                        </div>
                                        <input type="text" wire:model="email_domain" class="form-control @error('email_domain') is-invalid @enderror" placeholder="Contoh: namaperusahaan.com">
                                    </div>
                                    <small class="form-text text-muted">
                                        Domain ini digunakan sebagai akhiran otomatis saat membuat email karyawan (contoh: <code>arif.fahmi.abdillah@{{ $email_domain ?: 'namaperusahaan.com' }}</code>).
                                    </small>
                                    @error('email_domain')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-600">Alamat Kantor Lengkap</label>
                            <textarea wire:model="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Nama jalan, gedung, nomor, RT/RW, kelurahan/kecamatan"></textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="font-weight-600">Kota / Kabupaten</label>
                                    <input type="text" wire:model="city" class="form-control @error('city') is-invalid @enderror" placeholder="Contoh: Jakarta Selatan">
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-600">Provinsi</label>
                                    <input type="text" wire:model="province" class="form-control @error('province') is-invalid @enderror" placeholder="Contoh: DKI Jakarta">
                                    @error('province')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-600">Kode Pos</label>
                                    <input type="text" wire:model="postal_code" class="form-control @error('postal_code') is-invalid @enderror" placeholder="Contoh: 12190">
                                    @error('postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-file-invoice-dollar mr-2"></i>Pengaturan Pajak PPh 21 & BPJS</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="custom-switch pl-0 cursor-pointer mt-1 mb-0" for="enable_pph21">
                                    <input type="checkbox" wire:model="enable_pph21" class="custom-switch-input" id="enable_pph21">
                                    <span class="custom-switch-indicator"></span>
                                    <span class="custom-switch-description font-weight-600 text-dark">Aktifkan Perhitungan PPh 21 Otomatis</span>
                                </label>
                                <small class="text-muted d-block mt-1">
                                    Menghitung PPh 21 karyawan secara otomatis berdasarkan Tarif Efektif Rata-Rata (TER PP 58/2023).
                                </small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="custom-switch pl-0 cursor-pointer mt-1 mb-0" for="include_bpjs_in_tax">
                                    <input type="checkbox" wire:model="include_bpjs_in_tax" class="custom-switch-input" id="include_bpjs_in_tax">
                                    <span class="custom-switch-indicator"></span>
                                    <span class="custom-switch-description font-weight-600 text-dark">Sertakan BPJS Perusahaan ke Bruto Pajak</span>
                                </label>
                                <small class="text-muted d-block mt-1">
                                    Premi JKK, JKM, dan BPJS Kesehatan perusahaan dihitung sebagai penambah penghasilan bruto kena pajak.
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-600">Tingkat Risiko BPJS JKK (Perusahaan)</label>
                                    <select wire:model="bpjs_jkk_risk_rate" class="form-control @error('bpjs_jkk_risk_rate') is-invalid @enderror">
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
                                    <label class="font-weight-600">Plafon Maksimal BPJS Pensiun (JP)</label>
                                    <div class="input-group" x-data>
                                        <div class="input-group-prepend"><span class="input-group-text font-weight-500">Rp</span></div>
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
                                    <small class="text-muted">Batas maksimal dasar upah JP tahun berjalan.</small>
                                    @error('bpjs_jp_cap')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-university mr-2"></i>Rekening Penggajian Operasional</h6>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-600">Nama Bank</label>
                                    <input type="text" wire:model="bank_name" class="form-control @error('bank_name') is-invalid @enderror" placeholder="Contoh: Bank Central Asia (BCA)">
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-600">Nomor Rekening</label>
                                    <input type="text" wire:model="bank_account_number" class="form-control @error('bank_account_number') is-invalid @enderror" placeholder="Contoh: 1234567890">
                                    @error('bank_account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-600">Atas Nama Rekening</label>
                                    <input type="text" wire:model="bank_account_name" class="form-control @error('bank_account_name') is-invalid @enderror" placeholder="Contoh: PT Larashiz Payroll">
                                    @error('bank_account_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-whitesmoke text-right">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
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
