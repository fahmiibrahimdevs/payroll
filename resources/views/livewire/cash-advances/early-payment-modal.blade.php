<!-- Modal Pelunasan Lebih Awal (Multi-Month Early Settlement) -->
<div wire:ignore.self class="modal fade" id="earlyPaymentModal" role="dialog" aria-labelledby="earlyPaymentModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content early-payment-modal-content">
            <div class="modal-header early-payment-modal-header">
                <h5 class="modal-title early-payment-modal-title" id="earlyPaymentModalLabel">
                    <i class="fas fa-hand-holding-usd mr-1 text-primary"></i>
                    Pelunasan Tagihan Lebih Awal
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            @can('recordEarlySettlement', \App\Models\CashAdvance::class)
                <form wire:submit="storeEarlyPayment">
                <div class="modal-body early-payment-modal-body">
                    <!-- Info Karyawan & Total Keseluruhan Sisa Kasbon -->
                    <div class="early-pay-info-card">
                        <div class="mr-2 text-truncate">
                            <div class="early-pay-info-label">Karyawan:</div>
                            <div class="early-pay-info-name text-truncate">{{ $currentEmployee?->full_name ?? '-' }}</div>
                        </div>
                        <div class="text-right text-nowrap">
                            <div class="early-pay-info-label">Total Sisa Kasbon:</div>
                            <div class="early-pay-info-amount">{{ $summary['formatted_total_balance'] ?? 'Rp 0' }}</div>
                        </div>
                    </div>

                    <!-- Pemilihan Bulan Pelunasan -->
                    <div class="form-group mb-3">
                        <div class="mb-1">
                            <div class="early-pay-section-label">Pilih Bulan Tagihan <span class="text-danger">*</span></div>
                            <div class="early-pay-section-sub">Pilih bulan tagihan yang ingin dilunasi</div>
                        </div>

                        @error('selectedMonthKeysForPayment')
                            <div class="text-danger small mb-2 font-weight-500">{{ $message }}</div>
                        @enderror

                        @php
                            $unpaidList = $statements['unpaid_statements'] ?? [];
                            $waterfall = $this->earlyPaymentWaterfall;
                            $allocations = $waterfall['allocations'] ?? [];
                            $selectedSum = 0.0;
                            foreach ($unpaidList as $st) {
                                if (in_array($st['month_key'], $selectedMonthKeysForPayment, true)) {
                                    $selectedSum += (float) ($st['remaining_amount'] ?? 0);
                                }
                            }
                        @endphp

                        <div class="early-pay-month-list">
                            @forelse ($unpaidList as $st)
                                @php
                                    $mKey = $st['month_key'];
                                    $isSelected = in_array($mKey, $selectedMonthKeysForPayment, true);
                                    $alloc = $allocations[$mKey] ?? null;
                                    $isCovered = $alloc && $alloc['is_covered'];
                                    $isPartial = $alloc && $alloc['is_partial'];
                                    $isFull = $alloc && $alloc['is_full'];

                                    $cardModifierClass = '';
                                    if ($isFull) {
                                        $cardModifierClass = 'early-pay-month-card-full';
                                    } elseif ($isPartial) {
                                        $cardModifierClass = 'early-pay-month-card-partial';
                                    } elseif ($isSelected) {
                                        $cardModifierClass = 'early-pay-month-card-selected';
                                    }
                                @endphp
                                <div class="early-pay-month-card {{ $cardModifierClass }}"
                                    wire:click="toggleMonthSelection('{{ $mKey }}')"
                                    wire:key="early-pay-month-{{ $mKey }}">
                                    <div class="early-pay-month-main">
                                        <input type="checkbox" class="early-pay-month-checkbox"
                                            wire:click.stop="toggleMonthSelection('{{ $mKey }}')"
                                            {{ $isSelected ? 'checked' : '' }}>
                                        <div class="min-w-0">
                                            <div class="early-pay-month-title text-truncate">
                                                <span class="d-none d-sm-inline">Tagihan {{ $st['month_name_full'] }}</span>
                                                <span class="d-inline d-sm-none">{{ $st['month_name_short'] }} {{ $st['year'] }}</span>
                                            </div>
                                            <div class="early-pay-month-due text-truncate">
                                                Tanggal jatuh tempo {{ $st['formatted_due_date'] }}
                                            </div>
                                            @if ($isPartial)
                                                <div class="early-pay-month-partial-note text-truncate">
                                                    Sisa Nanti: Rp {{ number_format($alloc['remaining_after'], 0, ',', '.') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right text-nowrap pl-2">
                                        <div class="early-pay-month-amount {{ $isSelected ? '' : 'early-pay-month-amount-unselected' }}">
                                            {{ $st['formatted_remaining_amount'] }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="statement-items-empty">
                                    Tidak ada jadwal tagihan yang belum lunas.
                                </div>
                            @endforelse
                        </div>

                        <!-- Tombol Pilih Semua Bulan (Full Width di bawah card tagihan) -->
                        @if (!empty($unpaidList))
                            <button type="button" wire:click="selectAllUnpaidMonths" class="btn btn-outline-primary shadow-none early-pay-btn-select-all">
                                <i class="fas fa-check-double mr-1"></i> Pilih Semua Bulan
                            </button>
                        @endif

                        <!-- Card Total Tagihan Yang Dipilih -->
                        <div class="early-pay-summary-card">
                            <div>
                                <div class="early-pay-summary-label">Total Tagihan Dipilih:</div>
                                <div class="early-pay-summary-value">
                                    Rp {{ number_format($selectedSum, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="text-right text-nowrap">
                                <span class="early-pay-summary-badge">
                                    {{ count($selectedMonthKeysForPayment) }} Bulan Dipilih
                                </span>
                            </div>
                        </div>

                        <!-- Nominal yang Ingin Dibayar -->
                        <div class="form-group mb-0">
                            <label for="early_pay_amount" class="early-pay-form-label">
                                Nominal yang Ingin Dibayar <span class="text-danger">*</span>
                            </label>
                            <div class="input-group" x-data
                                 x-on:early-payment-amount-refreshed.window="
                                     const amountInput = $el.querySelector('#early_pay_amount');
                                     const raw = String($event.detail.amount ?? '').replace(/\D/g, '');
                                     amountInput.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                 ">
                                <div class="input-group-prepend early-pay-input-group-prepend">
                                    <span class="input-group-text early-pay-input-addon">Rp</span>
                                </div>
                                <input type="text"
                                       id="early_pay_amount"
                                       inputmode="numeric"
                                       x-on:input="
                                           let raw = $el.value.replace(/\D/g, '');
                                           $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                       "
                                       x-on:change="$wire.$set('earlyPaymentAmount', $el.value.replace(/\D/g, '') ? parseFloat($el.value.replace(/\D/g, '')) : '', true)"
                                       value="{{ !empty($earlyPaymentAmount) ? number_format((float) $earlyPaymentAmount, 0, ',', '.') : '' }}"
                                       class="form-control text-right early-pay-form-control @error('earlyPaymentAmount') is-invalid @enderror"
                                       placeholder="0"
                                       autocomplete="off">
                            </div>
                            @error('earlyPaymentAmount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- Tanggal Pembayaran -->
                        <div class="col-md-6 col-12">
                            <div class="form-group mb-3">
                                <label for="early_pay_date" class="early-pay-form-label">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" id="early_pay_date" wire:model="earlyPaymentDate" class="form-control early-pay-form-control @error('earlyPaymentDate') is-invalid @enderror">
                                @error('earlyPaymentDate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="col-md-6 col-12">
                            <div class="form-group mb-3">
                                <label for="early_pay_method" class="early-pay-form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select id="early_pay_method" wire:model="earlyPaymentMethod" class="form-control early-pay-form-control @error('earlyPaymentMethod') is-invalid @enderror">
                                    <option value="cash">Kas / Tunai</option>
                                    <option value="bank_transfer">Transfer Bank</option>
                                    <option value="salary_deduction">Potong Gaji Manual</option>
                                </select>
                                @error('earlyPaymentMethod')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Bukti Transfer / Dokumen Pembayaran -->
                    <div class="form-group mb-3">
                        <label for="early_proof_document" class="early-pay-form-label">
                            Bukti Pembayaran / Transfer <small class="text-muted font-normal">(Opsional, JPG/PNG/PDF max 5MB)</small>
                        </label>
                        <div class="custom-file">
                            <input type="file" id="early_proof_document" wire:model="earlyProofDocument" class="custom-file-input @error('earlyProofDocument') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                            <label class="custom-file-label early-pay-file-label text-truncate" for="early_proof_document">
                                @if ($earlyProofDocument)
                                    <i class="fas fa-check-circle text-success mr-1"></i> {{ is_object($earlyProofDocument) ? $earlyProofDocument->getClientOriginalName() : 'File Terpilih' }}
                                @else
                                    <i class="fas fa-file-upload text-muted mr-1"></i> Pilih file bukti transfer / pembayaran...
                                @endif
                            </label>
                        </div>
                        <div wire:loading wire:target="earlyProofDocument" class="text-primary small mt-1">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah file bukti...
                        </div>
                        @if ($earlyProofDocument && is_object($earlyProofDocument))
                            <div class="d-flex align-items-center justify-content-between mt-1 px-1">
                                <span class="text-muted small">
                                    <i class="fas fa-info-circle mr-1"></i> {{ method_exists($earlyProofDocument, 'getSize') ? number_format($earlyProofDocument->getSize() / 1024, 0) . ' KB' : '' }}
                                </span>
                                <button type="button" class="btn btn-link text-danger p-0 text-decoration-none small" wire:click="$set('earlyProofDocument', null)">
                                    <i class="fas fa-times-circle mr-1"></i> Hapus file terpilih
                                </button>
                            </div>
                        @endif
                        @error('earlyProofDocument')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Catatan Pelunasan -->
                    <div class="form-group mb-0">
                        <label for="early_pay_notes" class="early-pay-form-label">Catatan / Keterangan</label>
                        <input type="text" id="early_pay_notes" wire:model="earlyPaymentNotes" placeholder="Contoh: Transfer Bank BCA..." class="form-control early-pay-form-control @error('earlyPaymentNotes') is-invalid @enderror">
                        @error('earlyPaymentNotes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Info Approval Direktur -->
                    <div class="alert alert-light border mt-3 mb-0 d-flex align-items-center" style="font-size: 12px; color: #475569; border-color: #e2e8f0 !important; background-color: #f8fafc;">
                        <i class="fas fa-info-circle text-primary mr-2" style="font-size: 14px;"></i>
                        <span>Pelunasan manual ini akan berstatus <strong>Menunggu Approval Direktur</strong> sebelum saldo kasbon dan cicilan berkurang.</span>
                    </div>
                </div>

                <div class="modal-footer early-payment-modal-footer">
                    <button type="button" class="btn btn-secondary shadow-none early-pay-btn-cancel" data-dismiss="modal">Batal</button>
                    @php
                        $inputVal = (float) $earlyPaymentAmount;
                        $isExceeded = $inputVal > $selectedSum;
                        $isInvalid = $isExceeded || $inputVal < 1000 || $selectedSum <= 0 || empty($selectedMonthKeysForPayment) || $errors->has('earlyPaymentAmount');
                    @endphp
                    <button type="submit" wire:loading.attr="disabled" wire:target="storeEarlyPayment,earlyProofDocument,earlyPaymentAmount,toggleMonthSelection,selectAllUnpaidMonths" class="btn btn-primary shadow-none early-pay-btn-submit" @if ($isInvalid) disabled @endif>
                        <i wire:loading wire:target="storeEarlyPayment" class="fas fa-spinner fa-spin mr-1"></i>
                        <span wire:loading.remove wire:target="storeEarlyPayment">
                            @if ($isExceeded)
                                Nominal Melebihi Total
                            @elseif ($earlyPaymentAmount && $inputVal > 0)
                                Bayar Rp {{ number_format($inputVal, 0, ',', '.') }}
                            @else
                                Bayar Lebih Awal
                            @endif
                        </span>
                        <span wire:loading wire:target="storeEarlyPayment">Menyimpan...</span>
                    </button>
                </div>
                </form>
            @endcan
        </div>
    </div>
</div>
