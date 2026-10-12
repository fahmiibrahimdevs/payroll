<!-- Modal Input Pembayaran / Setoran Kasbon -->
<div wire:ignore.self class="modal fade" id="cashAdvancePaymentModal" role="dialog" aria-labelledby="cashAdvancePaymentModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
            <div class="modal-header bg-white border-bottom py-3">
                <h5 class="modal-title text-dark" id="cashAdvancePaymentModalLabel" style="font-weight: 500;">
                    Catat Pembayaran Kasbon
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            @if (auth()->user()?->hasRole('admin'))
                <form wire:submit="storePayment">
                <div class="modal-body py-3 px-4 bg-white">
                    @if ($selectedCashAdvance)
                        <!-- Card Info Kasbon & Sisa Kasbon -->
                        <div class="card border mb-3 bg-white" style="border-color: #eaedf2 !important;">
                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">No. Transaksi Kasbon:</div>
                                    <div class="text-dark" style="font-size: 1rem; font-weight: 600;">{{ $selectedCashAdvance->reference_number }}</div>
                                    <div class="text-muted small mt-1">{{ $selectedCashAdvance->employee?->full_name }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-muted small">Sisa Kasbon:</div>
                                    <div class="text-dark" style="font-size: 1.15rem; font-weight: 600;">{{ $selectedCashAdvance->formatted_remaining_amount }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Pilihan Target Pembayaran / Pelunasan -->
                        <div class="form-group mb-3">
                            <label class="d-block mb-2">Pilihan Pembayaran / Pelunasan <span class="text-danger">*</span></label>
                            <div class="card border mb-0 bg-white" style="border-color: #eaedf2 !important;">
                                <div class="card-body p-3">
                                    <!-- Opsi Pelunasan Penuh -->
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" id="target_full" name="payment_target_option" class="custom-control-input" value="full"
                                            wire:click="selectPaymentTarget('full')"
                                            {{ $paymentForm->payment_target === 'full' ? 'checked' : '' }}>
                                        <label class="custom-control-label text-dark" for="target_full" style="font-weight: 500;">
                                            @if ($selectedCashAdvance->repayment_scheme === \App\Enums\RepaymentScheme::SALARY_SINGLE)
                                                Pelunasan Penuh Lebih Awal (Sebelum Jadwal Potong Gaji)
                                            @else
                                                Pelunasan Penuh Seluruh Sisa (Lunas Lebih Awal)
                                            @endif
                                            <span class="text-primary ml-1" style="font-weight: 600;">({{ $selectedCashAdvance->formatted_remaining_amount }})</span>
                                        </label>
                                    </div>

                                    <!-- Opsi Cicilan Tertentu jika skema cicilan -->
                                    @if ($selectedCashAdvance->unpaidInstallments && $selectedCashAdvance->unpaidInstallments->isNotEmpty())
                                        @foreach ($selectedCashAdvance->unpaidInstallments as $inst)
                                            <div class="custom-control custom-radio mb-2" wire:key="target-inst-{{ $inst->id }}">
                                                <input type="radio" id="target_inst_{{ $inst->id }}" name="payment_target_option" class="custom-control-input" value="inst_{{ $inst->id }}"
                                                    wire:click="selectPaymentTarget('installment', {{ $inst->id }})"
                                                    {{ $paymentForm->payment_target === 'installment' && (int) $paymentForm->cash_advance_installment_id === (int) $inst->id ? 'checked' : '' }}>
                                                <label class="custom-control-label text-dark" for="target_inst_{{ $inst->id }}" style="font-weight: 500;">
                                                    Cicilan Ke-{{ $inst->installment_number }}
                                                    @if ($inst->due_date)
                                                        <span class="text-muted small">({{ $inst->due_date->format('M Y') }})</span>
                                                    @endif
                                                    <span class="text-primary ml-1" style="font-weight: 600;">({{ $inst->formatted_remaining_amount }})</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    @endif

                                    <!-- Opsi Nominal Kustom -->
                                    <div class="custom-control custom-radio mb-0">
                                        <input type="radio" id="target_custom" name="payment_target_option" class="custom-control-input" value="custom"
                                            wire:click="selectPaymentTarget('custom')"
                                            {{ $paymentForm->payment_target === 'custom' ? 'checked' : '' }}>
                                        <label class="custom-control-label text-dark" for="target_custom" style="font-weight: 500;">
                                            Nominal Sebagian / Kustom
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Nominal Pembayaran -->
                    <div class="form-group mb-3">
                        <label for="pay_amount">Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group" x-data>
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-500">Rp</span>
                            </div>
                            <input type="text"
                                   id="pay_amount"
                                   inputmode="numeric"
                                   x-on:input="
                                       let raw = $el.value.replace(/\D/g, '');
                                       $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                   "
                                   x-on:input.debounce.350ms="$wire.set('paymentForm.amount', $el.value.replace(/\D/g, '') ? parseFloat($el.value.replace(/\D/g, '')) : '')"
                                   x-on:change="$wire.set('paymentForm.amount', $el.value.replace(/\D/g, '') ? parseFloat($el.value.replace(/\D/g, '')) : '')"
                                   x-on:blur="$wire.set('paymentForm.amount', $el.value.replace(/\D/g, '') ? parseFloat($el.value.replace(/\D/g, '')) : '')"
                                   value="{{ !empty($paymentForm->amount) ? number_format((float) $paymentForm->amount, 0, ',', '.') : '' }}"
                                   class="form-control text-right @error('paymentForm.amount') is-invalid @enderror"
                                   placeholder="0"
                                   autocomplete="off">
                        </div>
                        @error('paymentForm.amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tanggal Pembayaran -->
                    <div class="form-group mb-3">
                        <label for="pay_date">Tanggal Pembayaran <span class="text-danger">*</span></label>
                        <input type="date" id="pay_date" wire:model="paymentForm.payment_date" class="form-control @error('paymentForm.payment_date') is-invalid @enderror">
                        @error('paymentForm.payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="form-group mb-3">
                        <label for="pay_method">Metode Pembayaran <span class="text-danger">*</span></label>
                        <div wire:ignore>
                            <select id="pay_method" class="form-control select2 @error('paymentForm.payment_method') is-invalid @enderror">
                                <option value="cash">Tunai / Cash</option>
                                <option value="bank_transfer">Transfer Bank</option>
                                <option value="salary_deduction">Potong Gaji</option>
                            </select>
                        </div>
                        @error('paymentForm.payment_method')
                            <div class="text-danger text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Upload Bukti Pembayaran -->
                    <div class="form-group mb-3">
                        <label for="pay_proof_document">Upload Bukti Pembayaran <small class="text-muted">(JPG, PNG, PDF maks. 5MB - Opsional)</small></label>
                        <div class="custom-file">
                            <input type="file" id="pay_proof_document" wire:model="paymentForm.proof_document" class="custom-file-input @error('paymentForm.proof_document') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                            <label class="custom-file-label text-truncate" for="pay_proof_document">
                                @if ($paymentForm->proof_document)
                                    <i class="fas fa-check-circle text-success mr-1"></i> {{ is_object($paymentForm->proof_document) ? $paymentForm->proof_document->getClientOriginalName() : 'File Terpilih' }}
                                @else
                                    <i class="fas fa-file-upload text-muted mr-1"></i> Pilih file bukti transfer / pembayaran...
                                @endif
                            </label>
                        </div>
                        <div wire:loading wire:target="paymentForm.proof_document" class="text-primary small mt-1">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah file bukti...
                        </div>
                        @if ($paymentForm->proof_document && is_object($paymentForm->proof_document))
                            <div class="d-flex align-items-center justify-content-between mt-1 px-1">
                                <span class="text-muted" style="font-size: 11px;">
                                    <i class="fas fa-info-circle mr-1"></i> {{ method_exists($paymentForm->proof_document, 'getSize') ? number_format($paymentForm->proof_document->getSize() / 1024, 0) . ' KB' : '' }}
                                </span>
                                <button type="button" class="btn btn-link text-danger p-0 text-decoration-none" wire:click="$set('paymentForm.proof_document', null)" style="font-size: 11px;">
                                    <i class="fas fa-times-circle mr-1"></i> Hapus file terpilih
                                </button>
                            </div>
                        @endif
                        @error('paymentForm.proof_document')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Catatan -->
                    <div class="form-group mb-0">
                        <label for="pay_notes">Catatan / No. Referensi <small class="text-muted">(Opsional)</small></label>
                        <textarea id="pay_notes" wire:model="paymentForm.notes" rows="2" placeholder="Catatan transaksi..." class="form-control @error('paymentForm.notes') is-invalid @enderror"></textarea>
                        @error('paymentForm.notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Info Approval Direktur -->
                    <div class="alert alert-light border mt-3 mb-0 d-flex align-items-center" style="font-size: 12px; color: #475569; border-color: #e2e8f0 !important; background-color: #f8fafc;">
                        <i class="fas fa-info-circle text-primary mr-2" style="font-size: 14px;"></i>
                        <span>Pembayaran manual ini akan berstatus <strong>Menunggu Approval Direktur</strong> sebelum saldo kasbon dan cicilan berkurang.</span>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Batal</button>
                    <button type="submit" class="btn btn-primary shadow-none" wire:loading.attr="disabled" wire:target="storePayment" style="font-weight: 500;">
                        <span wire:loading wire:target="storePayment"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                        <span wire:loading.remove wire:target="storePayment"><i class="fas fa-save mr-1"></i> Simpan Pembayaran</span>
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
