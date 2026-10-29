<div wire:ignore.self class="modal fade" id="postModal" role="dialog" aria-labelledby="postModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="postModalLabel">
                    {{ $isEditMode ? 'Edit Data' : 'Tambah Data' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="$set('form.post', null)"><span aria-hidden="true">&times;</span></button>
            </div>

            <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}">
                <div class="modal-body">
                    <div class="row">
                        <!-- Judul Post -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="title">Judul Post <span class="text-danger">*</span></label>
                                <input type="text" id="title" wire:model="form.title" class="form-control @error('form.title') is-invalid @enderror" placeholder="Masukkan judul post..." autocomplete="off">
                                @error('form.title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Kategori -->
                        <div class="col-md-6">
                            <div class="form-group" wire:ignore>
                                <label for="category">Kategori <span class="text-danger">*</span></label>
                                <select id="category" class="form-control select2">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Technology">Technology</option>
                                    <option value="Lifestyle">Lifestyle</option>
                                    <option value="Business">Business</option>
                                    <option value="Education">Education</option>
                                    <option value="Health">Health</option>
                                </select>
                            </div>
                            @error('form.category')
                                <div class="text-danger small mt-n3 mb-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <div class="form-group" wire:ignore>
                                <label for="status">Status Publikasi <span class="text-danger">*</span></label>
                                <select id="status" class="form-control select2">
                                    <option value="draft">Draft (Konsep)</option>
                                    <option value="published">Published (Diterbitkan)</option>
                                </select>
                            </div>
                            @error('form.status')
                                <div class="text-danger small mt-n3 mb-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Konten -->
                        <div class="col-md-12">
                            <div class="form-group mb-0">
                                <label for="content">Konten <span class="text-danger">*</span></label>
                                <textarea id="content" wire:model="form.content" rows="5" class="form-control @error('form.content') is-invalid @enderror" placeholder="Tuliskan isi konten post di sini..." style="height: 140px;"></textarea>
                                @error('form.content')
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
