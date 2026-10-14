<!-- Modal Tinjau Pengajuan Kasbon (Review Pending Advances) -->
<div wire:ignore.self class="modal fade" id="pendingReviewModal" role="dialog" aria-labelledby="pendingReviewModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;" role="document">
        <div class="modal-content">
            <!-- 1. Header Modal -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title font-weight-600 text-dark" id="pendingReviewModalLabel">ACC Pengajuan Kasbon</h5>
                    <div style="font-size: 11.5px; color: #475569; font-weight: 400; margin-top: 2px;">
                        Pengajuan kasbon karyawan yang menunggu persetujuan (ACC)
                    </div>
                </div>
                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- 2. Modal Body: Daftar Pengajuan Kasbon Pending -->
            <div class="modal-body p-3 p-md-3" style="max-height: 70vh; overflow-y: auto;">
                @if (isset($pendingReviewAdvances) && $pendingReviewAdvances->isNotEmpty())
                    <div class="d-flex flex-column" style="gap: 10px;">
                        @foreach ($pendingReviewAdvances as $item)
                            <div class="p-2.5 p-sm-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);" wire:key="pending-review-{{ $item->id }}">
                                <!-- Baris 1: Identitas Karyawan & Nominal/Skema (Kompak) -->
                                <div class="d-flex justify-content-between align-items-center mb-1.5" style="gap: 10px;">
                                    <div class="d-flex align-items-center min-w-0" style="gap: 10px;">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold" style="width: 34px; height: 34px; background-color: #0b52aa; font-size: 13px; flex-shrink: 0;">
                                            {{ strtoupper(substr($item->employee?->full_name ?? 'K', 0, 1)) }}
                                        </div>
                                        <div class="text-truncate">
                                            <div class="font-weight-600 text-dark text-truncate" style="font-size: 13.5px; line-height: 1.3;">
                                                {{ $item->employee?->full_name ?? '-' }}
                                            </div>
                                            <div class="text-truncate" style="font-size: 11px; margin-top: 1px; color: #475569; font-weight: 400;">
                                                {{ $item->employee?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $item->employee?->position?->name ?? 'Karyawan' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <div class="font-weight-600 text-primary" style="font-size: 14.5px; line-height: 1.3;">
                                            {{ $item->formatted_amount }}
                                        </div>
                                        <div style="font-size: 11px; margin-top: 1px; color: #475569; font-weight: 500;">
                                            {{ $item->scheme_label }}@if ($item->repayment_scheme === \App\Enums\RepaymentScheme::SALARY_INSTALLMENT) ({{ $item->installment_count }}x)@endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Baris 2: Alasan, Tanggal & Tombol Aksi (Kompak & Elegan) -->
                                <div class="d-flex justify-content-between align-items-center pt-2" style="border-top: 1px solid #f1f5f9; gap: 12px;">
                                    <div class="min-w-0" style="flex-grow: 1;">
                                        <div style="font-size: 12px; line-height: 1.35;">
                                            <span class="font-weight-600 text-dark">Alasan:</span> <span style="font-weight: 400; color: #1e293b;">{{ $item->reason ?: 'Tidak ada keterangan alasan.' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center flex-wrap" style="font-size: 11px; margin-top: 2px; color: #475569; font-weight: 400; gap: 4px;">
                                            <span><i class="far fa-calendar-alt mr-1 text-muted"></i>{{ $item->created_at?->translatedFormat('d M Y') ?? '-' }}</span>
                                            <span class="text-muted mx-1">&bull;</span>
                                            <span>Diajukan oleh: <strong class="text-dark font-weight-600">{{ $item->submitter?->name ?? ($item->employee?->full_name ?? 'Karyawan') }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center flex-shrink-0" style="gap: 6px;">
                                        <button type="button" 
                                                onclick="$('#pendingReviewModal').modal('hide')" 
                                                wire:click="directToTransaction({{ $item->employee_id }}, 'advance', {{ $item->id }})" 
                                                class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center shadow-xs" 
                                                style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" 
                                                title="Lihat di Riwayat Transaksi">
                                            <i class="fas fa-arrow-right" style="font-size: 11px;"></i>
                                        </button>
                                        @can('approve', $item)
                                            <button type="button" onclick="confirmApproveCashAdvanceDirect({{ $item->id }})" wire:loading.attr="disabled" wire:target="approveDirect({{ $item->id }})" class="btn btn-success d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" title="Setujui Kasbon">
                                                <i wire:loading.remove wire:target="approveDirect({{ $item->id }})" class="fas fa-check" style="font-size: 12px;"></i>
                                                <i wire:loading wire:target="approveDirect({{ $item->id }})" class="fas fa-spinner fa-spin" style="font-size: 12px;"></i>
                                            </button>
                                            <button type="button" onclick="confirmRejectCashAdvanceDirect({{ $item->id }})" wire:loading.attr="disabled" wire:target="rejectDirect({{ $item->id }})" class="btn btn-danger d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" title="Tolak Pengajuan">
                                                <i wire:loading.remove wire:target="rejectDirect({{ $item->id }})" class="fas fa-times" style="font-size: 12px;"></i>
                                                <i wire:loading wire:target="rejectDirect({{ $item->id }})" class="fas fa-spinner fa-spin" style="font-size: 12px;"></i>
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
                        <div class="font-weight-600 mt-2 text-dark">Tidak Ada Pengajuan Menunggu</div>
                        <div class="small mt-1" style="font-size: 11.5px; color: #475569;">Semua pengajuan kasbon telah ditinjau dan diproses.</div>
                    </div>
                @endif
            </div>

            <!-- 3. Modal Footer: Kontras Jelas Tanpa Teks Abu-abu Pudar -->
            <div class="modal-footer d-flex justify-content-between align-items-center" style="background-color: #ffffff; border-top: 1px solid #f1f5f9; padding: 12px 20px;">
                <div style="font-size: 12.5px; color: #1e293b;">
                    <span>Total:</span> <span class="font-weight-600 text-dark">{{ isset($pendingReviewAdvances) ? $pendingReviewAdvances->count() : 0 }} pengajuan</span>
                    <span style="color: #0b52aa; font-weight: 600; margin-left: 4px;">({{ $companyStats['formatted_pending_amount'] ?? 'Rp 0' }})</span>
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-3" data-dismiss="modal" style="font-weight: 500; border-radius: 8px;">Tutup</button>
            </div>
        </div>
    </div>
</div>
