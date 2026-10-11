<div wire:ignore.self class="modal fade" id="salaryComponentModal" role="dialog" aria-labelledby="salaryComponentModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salaryComponentModalLabel">
                    {{ $isEditMode ? 'Edit Komponen Gaji' : 'Tambah Komponen Gaji Baru' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}">
                <div class="modal-body py-3 px-4">
                    <div class="row">
                        <!-- Nama Komponen -->
                        <div class="col-md-7">
                            <div class="form-group mb-3">
                                <label for="comp_name">Nama Komponen <span class="text-danger">*</span></label>
                                <input type="text" id="comp_name" wire:model="form.name" class="form-control @error('form.name') is-invalid @enderror" placeholder="Contoh: Tunjangan Makan, BPJS Kesehatan" autocomplete="off">
                                @error('form.name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Kode Komponen -->
                        <div class="col-md-5">
                            <div class="form-group mb-3">
                                <label for="comp_code">Kode Komponen</label>
                                <input type="text" id="comp_code" wire:model="form.code" class="form-control text-uppercase @error('form.code') is-invalid @enderror" placeholder="Contoh: TJ_MAKAN" autocomplete="off">
                                @error('form.code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Kategori Komponen -->
                    <div class="form-group mb-3">
                        <label class="d-block">Kategori Komponen <span class="text-danger">*</span></label>
                        <div class="selectgroup selectgroup-pills w-100 d-flex flex-nowrap">
                            <label class="selectgroup-item mr-1" style="flex: 1 1 33.33%;">
                                <input type="radio" wire:model.live="form.category" value="pendapatan_tetap" class="selectgroup-input">
                                <span class="selectgroup-button text-center text-nowrap px-1">Pendapatan Tetap</span>
                            </label>
                            <label class="selectgroup-item mr-1" style="flex: 1 1 33.33%;">
                                <input type="radio" wire:model.live="form.category" value="tunjangan_tidak_tetap" class="selectgroup-input">
                                <span class="selectgroup-button text-center text-nowrap px-1">Tunj. Tidak Tetap</span>
                            </label>
                            <label class="selectgroup-item mr-0" style="flex: 1 1 33.33%;">
                                <input type="radio" wire:model.live="form.category" value="potongan" class="selectgroup-input">
                                <span class="selectgroup-button text-center text-nowrap px-1">Potongan</span>
                            </label>
                        </div>
                        @error('form.category')
                            <div class="text-danger text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Metode Hitung & Nilai Default -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="comp_calc_type">Metode Perhitungan <span class="text-danger">*</span></label>
                                <select id="comp_calc_type" wire:model.live="form.calculation_type" class="form-control @error('form.calculation_type') is-invalid @enderror">
                                    <option value="nominal">Nominal (Rp)</option>
                                    <option value="persen_gaji_pokok">Persen Gaji Pokok (%)</option>
                                </select>
                                @error('form.calculation_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="comp_default_value">Nilai Default {{ $form->calculation_type === 'persen_gaji_pokok' ? '(%)' : '(Rp)' }}</label>
                                <input type="number" step="0.01" min="0" id="comp_default_value" wire:model="form.default_value" class="form-control @error('form.default_value') is-invalid @enderror" placeholder="{{ $form->calculation_type === 'persen_gaji_pokok' ? 'Contoh: 1 atau 2' : 'Contoh: 500000' }}">
                                @error('form.default_value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Pajak dan Status -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="d-block">Kena Pajak PPh 21</label>
                                <label class="custom-switch pl-0 cursor-pointer mt-1 mb-0">
                                    <input type="checkbox" wire:model.live="form.is_taxable" class="custom-switch-input">
                                    <span class="custom-switch-indicator"></span>
                                    <span class="custom-switch-description">{{ $form->is_taxable ? 'Kena Pajak' : 'Bebas Pajak' }}</span>
                                </label>
                                @error('form.is_taxable')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="d-block">Status Aktif</label>
                                <label class="custom-switch pl-0 cursor-pointer mt-1 mb-0">
                                    <input type="checkbox" wire:model.live="form.is_active" class="custom-switch-input">
                                    <span class="custom-switch-indicator"></span>
                                    <span class="custom-switch-description">{{ $form->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </label>
                                @error('form.is_active')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group mb-0">
                        <label for="comp_description">Keterangan</label>
                        <textarea id="comp_description" wire:model="form.description" class="form-control @error('form.description') is-invalid @enderror" rows="2" placeholder="Catatan peruntukan komponen gaji ini..."></textarea>
                        @error('form.description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer bg-whitesmoke br">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="{{ $isEditMode ? 'update' : 'store' }}"><i wire:loading wire:target="{{ $isEditMode ? 'update' : 'store' }}" class="fas fa-spinner fa-spin mr-1"></i><i wire:loading.remove wire:target="{{ $isEditMode ? 'update' : 'store' }}" class="fas fa-save mr-1"></i>{{ $isEditMode ? 'Simpan Perubahan' : 'Simpan Komponen' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
