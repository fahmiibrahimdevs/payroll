<div wire:ignore.self class="modal fade" id="departmentModal" role="dialog" aria-labelledby="departmentModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalLabel">
                    {{ $isEditMode ? 'Edit Department' : 'Tambah Department' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="$set('form.department', null)"><span aria-hidden="true">&times;</span></button>
            </div>

            <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}">
                <div class="modal-body">
                    <div class="row">
                        <!-- Nama Department -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="name">Nama Department <span class="text-danger">*</span></label>
                                <input type="text" id="name" wire:model="form.name" class="form-control @error('form.name') is-invalid @enderror" placeholder="Masukkan nama department..." autocomplete="off">
                                @error('form.name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div class="col-md-12">
                            <div class="form-group mb-0">
                                <label for="description">Deskripsi</label>
                                <textarea id="description" wire:model="form.description" rows="4" class="form-control @error('form.description') is-invalid @enderror" placeholder="Tuliskan deskripsi department (opsional)..." style="height: 100px;"></textarea>
                                @error('form.description')
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
