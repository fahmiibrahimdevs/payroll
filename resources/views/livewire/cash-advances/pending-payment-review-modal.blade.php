<!-- Modal Tinjau Pembayaran Kasbon (Pending Payments Review Modal) -->
<div wire:ignore.self class="modal fade" id="pendingPaymentReviewModal" role="dialog" aria-labelledby="pendingPaymentReviewModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;" role="document">
        <div class="modal-content">
            <!-- 1. Header Modal -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title font-weight-600 text-dark" id="pendingPaymentReviewModalLabel">ACC Pelunasan Kasbon</h5>
                    <div style="font-size: 11.5px; color: #475569; font-weight: 400; margin-top: 2px;">
                        Setoran pelunasan kasbon yang menunggu persetujuan (ACC)
                    </div>
                </div>
                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- 2. Modal Body: Daftar Setoran Pembayaran Kasbon -->
            <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                @if (isset($pendingReviewPayments) && $pendingReviewPayments->isNotEmpty())
                    <div class="d-flex flex-column" style="gap: 10px;">
                        @foreach ($pendingReviewPayments as $payment)
                            @php
                                $emp = $payment->cashAdvance?->employee;
                            @endphp
                            <div class="p-2.5 p-sm-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);" wire:key="pending-pay-review-{{ $payment->id }}">
                                <!-- Baris 1: Identitas Karyawan & Nominal Bayar -->
                                <div class="d-flex justify-content-between align-items-center mb-1.5" style="gap: 10px;">
                                    <div class="d-flex align-items-center min-w-0" style="gap: 10px;">
                                        <!-- Avatar Warna Primary dengan Inisial seperti Gambar ke-2 -->
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold" style="width: 34px; height: 34px; background-color: #0b52aa; font-size: 13px; flex-shrink: 0;">
                                            {{ strtoupper(substr($emp?->full_name ?? 'K', 0, 1)) }}
                                        </div>
                                        <div class="text-truncate">
                                            <div class="font-weight-600 text-dark text-truncate" style="font-size: 13.5px; line-height: 1.3;">
                                                {{ $emp?->full_name ?? 'Karyawan' }}
                                            </div>
                                            <div class="text-truncate" style="font-size: 11px; margin-top: 1px; color: #475569; font-weight: 400;">
                                                {{ $emp?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $emp?->position?->name ?? 'Tanpa Jabatan' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <div class="font-weight-600 text-primary" style="font-size: 14.5px; line-height: 1.3;">
                                            {{ $payment->formatted_amount }}
                                        </div>
                                        <div style="font-size: 11px; margin-top: 1px; color: #475569; font-weight: 500;">
                                            {{ $payment->method_label }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Baris 2: Catatan, Diinput, Bukti Transfer, Tanggal & Tombol ACC / Tolak -->
                                <div class="d-flex justify-content-between align-items-center pt-2" style="border-top: 1px solid #f1f5f9; gap: 12px;">
                                    <div class="min-w-0" style="flex-grow: 1;">
                                        <!-- Line 1: Catatan: ... (kalau engga ada '-') -->
                                        <div style="font-size: 12px; line-height: 1.35;">
                                            <span class="font-weight-600 text-dark">Catatan:</span>
                                            <span style="font-weight: 400; color: #1e293b;">{{ $payment->notes ?: '-' }}</span>
                                        </div>

                                        <!-- Line 2: 14 Oct 2026 • Diinput oleh: Fahmi Admin • Tidak ada Bukti -->
                                        <div class="d-flex align-items-center flex-wrap" style="font-size: 11px; margin-top: 2px; color: #475569; font-weight: 400; gap: 4px;">
                                            <span><i class="far fa-calendar-alt mr-1 text-muted"></i>{{ $payment->payment_date ? $payment->payment_date->translatedFormat('d M Y') : '-' }}</span>
                                            <span class="text-muted mx-1">&bull;</span>
                                            <span>Diinput oleh: <strong class="text-dark font-weight-600">{{ $payment->recorder?->name ?? 'Admin' }}</strong></span>
                                            <span class="text-muted mx-1">&bull;</span>
                                            @if ($payment->proof_document)
                                                <a href="{{ $payment->proof_document_url }}" target="_blank" class="text-primary font-weight-600 text-decoration-none d-inline-flex align-items-center" style="font-size: 11px;" title="Lihat Bukti Transfer">
                                                    <i class="fas fa-file-invoice mr-1" style="font-size: 10.5px;"></i>Lihat Bukti
                                                </a>
                                            @else
                                                <span class="text-muted d-inline-flex align-items-center" style="font-size: 11px;">
                                                    <i class="fas fa-file-alt mr-1 text-muted" style="font-size: 10.5px;"></i>Tidak ada Bukti
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center flex-shrink-0" style="gap: 6px;">
                                        <button type="button" 
                                                onclick="$('#pendingPaymentReviewModal').modal('hide')" 
                                                wire:click="directToTransaction({{ $payment->cashAdvance?->employee_id }}, 'payment', {{ $payment->id }})" 
                                                class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center shadow-xs" 
                                                style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" 
                                                title="Lihat di Riwayat Transaksi">
                                            <i class="fas fa-arrow-right" style="font-size: 11px;"></i>
                                        </button>
                                        @can('approve', $payment)
                                            <button type="button" onclick="confirmApprovePayment({{ $payment->id }})" class="btn btn-success d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" title="Setujui Pelunasan (ACC)">
                                                <i class="fas fa-check" style="font-size: 12px;"></i>
                                            </button>
                                            <button type="button" onclick="confirmRejectPayment({{ $payment->id }})" class="btn btn-danger d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" title="Tolak Pelunasan">
                                                <i class="fas fa-times" style="font-size: 12px;"></i>
                                            </button>
                                        @else
                                            <span class="badge badge-warning" style="font-size: 11px; font-weight: 600;">Menunggu Direktur</span>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 36px;"></i>
                        <div class="font-weight-600 mt-2 text-dark">Semua Pelunasan Telah Di-ACC</div>
                        <div class="small mt-1" style="font-size: 11.5px; color: #475569;">Tidak ada setoran pelunasan yang menunggu persetujuan.</div>
                    </div>
                @endif
            </div>

            <!-- 3. Modal Footer -->
            <div class="modal-footer d-flex justify-content-between align-items-center" style="background-color: #ffffff; border-top: 1px solid #f1f5f9; padding: 12px 20px;">
                <div style="font-size: 12.5px; color: #1e293b;">
                    <span>Total:</span> <span class="font-weight-600 text-dark">{{ isset($pendingReviewPayments) ? $pendingReviewPayments->count() : 0 }} pelunasan</span>
                    <span style="color: #0b52aa; font-weight: 600; margin-left: 4px;">({{ $companyStats['formatted_pending_payments_amount'] ?? 'Rp 0' }})</span>
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-3" data-dismiss="modal" style="font-weight: 500; border-radius: 8px;">Tutup</button>
            </div>
        </div>
    </div>
</div>
