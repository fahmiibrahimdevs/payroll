<div wire:ignore.self class="modal fade" id="holidayModal" role="dialog" aria-labelledby="holidayModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="holidayModalLabel">
                    {{ $isEditMode ? 'Edit Hari Libur' : 'Tambah Hari Libur' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="$set('form.holiday', null)"><span aria-hidden="true">&times;</span></button>
            </div>

            <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}">
                <div class="modal-body">
                    <div class="row">
                        <!-- Tanggal Libur -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date">Tanggal Libur <span class="text-danger">*</span></label>
                                <input type="date" id="date" wire:model="form.date" class="form-control @error('form.date') is-invalid @enderror">
                                @error('form.date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Nama Hari Libur -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nama Hari Libur <span class="text-danger">*</span></label>
                                <input type="text" id="name" wire:model="form.name" class="form-control @error('form.name') is-invalid @enderror" placeholder="Contoh: Kemerdekaan RI" autocomplete="off">
                                @error('form.name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Batal</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="update, store"><span wire:loading wire:target="update, store"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span><span wire:loading.remove wire:target="update, store"><i class="fas fa-save mr-1"></i> {{ $isEditMode ? 'Simpan Perubahan' : 'Tambah Data' }}</span></button>
                </div>
            </form>
        </div>
    </div>
</div>
