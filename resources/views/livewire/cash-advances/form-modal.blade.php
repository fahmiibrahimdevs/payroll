<!-- Modal Form: Tambah / Edit Pengajuan Kasbon -->
<div wire:ignore.self class="modal fade" id="cashAdvanceModal" role="dialog" aria-labelledby="cashAdvanceModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
            <div class="modal-header bg-white border-bottom py-3">
                <h5 class="modal-title text-dark" id="cashAdvanceModalLabel" style="font-weight: 500;">
                    {{ $isEditMode ? 'Edit Data Kasbon' : 'Tambah Kasbon Baru' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="$set('form.cashAdvance', null)"><span aria-hidden="true">&times;</span></button>
            </div>

            <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}">
                <div class="modal-body py-3 px-4 bg-white">
                    <!-- Alert Peringatan jika Karyawan Masih Memiliki Kasbon Aktif -->
                    @if ($activeCashAdvanceAlert && $activeCashAdvanceAlert['has_active'] && ! $isEditMode)
                        <div class="alert alert-warning mb-3 d-flex align-items-center" style="border-left: 4px solid #ffc107; background-color: #fff8e1; border-color: #ffe082;">
                            <i class="fas fa-exclamation-triangle fa-2x mr-3 text-warning"></i>
                            <div>
                                <h6 class="font-weight-bold mb-1 text-dark">Perhatian: Karyawan Memiliki Kasbon Aktif</h6>
                                <p class="mb-0 small text-dark">
                                    Karyawan ini masih memiliki kasbon aktif (<strong>{{ $activeCashAdvanceAlert['advances'][0]['reference_number'] ?? 'Kasbon Aktif' }}</strong>) dengan sisa kasbon <strong>{{ $activeCashAdvanceAlert['formatted_total_remaining'] }}</strong>. Pengajuan kasbon baru tetap dapat diproses dan akan meminta konfirmasi saat disimpan.
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <!-- 1. Karyawan (1 Full Row / col-12) -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="form_employee_id">Karyawan <span class="text-danger">*</span></label>
                                <div wire:ignore>
                                    <select id="form_employee_id" class="form-control select2 @error('form.employee_id') is-invalid @enderror">
                                        <option value="">-- Pilih Karyawan --</option>
                                        @foreach ($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->department?->name ?? 'Tanpa Cabang' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('form.employee_id')
                                    <div class="text-danger text-xs mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 2. Tanggal Pencairan DULU baru Nominal Kasbon (col-md-6) -->
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label for="form_disbursement_date">Tanggal Pencairan <span class="text-danger">*</span></label>
                                <input type="date" id="form_disbursement_date" wire:model="form.disbursement_date" class="form-control @error('form.disbursement_date') is-invalid @enderror">
                                @error('form.disbursement_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label for="form_amount">Nominal Kasbon (Rp) <span class="text-danger">*</span></label>
                                <input type="number" id="form_amount" wire:model.live="form.amount" min="10000" step="1000" placeholder="Contoh: 1500000" class="form-control @error('form.amount') is-invalid @enderror">
                                @error('form.amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 3. Skema Pelunasan (2 Opsi: Cicilan Potong Gaji atau Potong Gaji 1x) -->
                        <div class="{{ $form->repayment_scheme === 'salary_installment' ? 'col-md-6' : 'col-12' }} col-12">
                            <div class="form-group">
                                <label for="form_repayment_scheme">Skema Pelunasan <span class="text-danger">*</span></label>
                                <div wire:ignore>
                                    <select id="form_repayment_scheme" class="form-control select2 @error('form.repayment_scheme') is-invalid @enderror">
                                        <option value="salary_installment">Cicilan Potong Gaji</option>
                                        <option value="salary_single">Potong Gaji 1x</option>
                                    </select>
                                </div>
                                @error('form.repayment_scheme')
                                    <div class="text-danger text-xs mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if ($form->repayment_scheme === 'salary_installment')
                            <!-- Tenor Stepper (+ / -) dengan Kotak Seamless Tanpa Rounded Dalam -->
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="form_installment_count">Tenor (Jumlah Bulan) <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center bg-white" style="border: 1px solid #ced4da; border-radius: 0.25rem; height: 42px; overflow: hidden;">
                                        <button type="button" class="stepper-action-btn" wire:click="decrementTenor" {{ (int) $form->installment_count <= 1 ? 'disabled' : '' }}>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" id="form_installment_count" wire:model.live="form.installment_count" min="1" max="60" class="text-center font-weight-bold" style="flex: 1; width: 100%; height: 100%; border: none !important; border-radius: 0 !important; outline: none !important; box-shadow: none !important; background: transparent; font-size: 1.15rem; color: #191d21;">
                                        <button type="button" class="stepper-action-btn" wire:click="incrementTenor" {{ (int) $form->installment_count >= 60 ? 'disabled' : '' }}>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    @error('form.installment_count')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Card Estimasi Cicilan Bersih (Border & Radius Identik Input Form) -->
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="p-3 bg-white" style="border: 1px solid #ced4da; border-radius: 0.25rem;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="d-block mb-1" style="font-size: 12px; font-weight: 600; color: #34395e; letter-spacing: 0.5px;">
                                                    Estimasi Cicilan per Bulan
                                                </label>
                                                <div class="font-weight-bold text-dark mt-1" style="font-size: 1.35rem; color: #191d21; line-height: 1.2;">
                                                    @if ((float) $form->amount > 0 && (int) $form->installment_count > 0)
                                                        Rp {{ number_format((float) $form->amount / max(1, (int) $form->installment_count), 0, ',', '.') }}
                                                        <span class="font-weight-normal text-muted" style="font-size: 12px;">/ bulan</span>
                                                    @else
                                                        Rp 0
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <span class="badge text-dark font-weight-bold px-2.5 py-1.5" style="font-size: 11.5px; background: #ffffff; border: 1px solid #ced4da; border-radius: 0.25rem;">
                                                    {{ $form->installment_count ?: 1 }}x Cicilan
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Card Keterangan Potong Gaji 1x -->
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="p-3 bg-white" style="border: 1px solid #ced4da; border-radius: 0.25rem;">
                                        <label class="d-block mb-1" style="font-size: 12px; font-weight: 600; color: #34395e; letter-spacing: 0.5px;">
                                            Total Pemotongan Gaji (1x Sekaligus)
                                        </label>
                                        <div class="font-weight-bold text-dark mt-1" style="font-size: 1.35rem; color: #191d21; line-height: 1.2;">
                                            @if ((float) $form->amount > 0)
                                                Rp {{ number_format((float) $form->amount, 0, ',', '.') }}
                                            @else
                                                Rp 0
                                            @endif
                                        </div>
                                        <div class="small text-muted mt-1">
                                            Nominal kasbon akan langsung dipotong penuh pada periode penggajian (payroll) berikutnya.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- 4. Alasan / Keperluan (col-12) -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="form_reason">Alasan / Keperluan <span class="text-danger">*</span></label>
                                <textarea id="form_reason" wire:model="form.reason" rows="2" placeholder="Tuliskan keperluan pengajuan kasbon..." class="form-control @error('form.reason') is-invalid @enderror"></textarea>
                                @error('form.reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 5. Catatan Tambahan (col-12) -->
                        <div class="col-12">
                            <div class="form-group mb-0">
                                <label for="form_notes">Catatan <small class="text-muted">(Opsional)</small></label>
                                <textarea id="form_notes" wire:model="form.notes" rows="2" placeholder="Catatan tambahan bila ada..." class="form-control @error('form.notes') is-invalid @enderror"></textarea>
                                @error('form.notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Batal</button>
                    <button type="submit" class="btn btn-primary shadow-none" wire:loading.attr="disabled" wire:target="update, store" style="font-weight: 500;">
                        <span wire:loading wire:target="update, store"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                        <span wire:loading.remove wire:target="update, store"><i class="fas fa-save mr-1"></i> {{ $isEditMode ? 'Simpan Perubahan' : 'Simpan Data' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .stepper-action-btn {
        width: 44px;
        height: 100%;
        border: none;
        background-color: #ffffff;
        color: #495057;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        outline: none !important;
        box-shadow: none !important;
        transition: background-color 0.15s ease-in-out;
    }
    .stepper-action-btn:first-child {
        border-right: 1px solid #ced4da;
    }
    .stepper-action-btn:last-child {
        border-left: 1px solid #ced4da;
    }
    .stepper-action-btn:hover:not(:disabled),
    .stepper-action-btn:focus:not(:disabled),
    .stepper-action-btn:active:not(:disabled) {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .stepper-action-btn:disabled {
        background-color: #f8fafc;
        color: #cbd5e1;
        cursor: not-allowed;
    }
</style>
