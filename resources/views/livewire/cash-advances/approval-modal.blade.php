<!-- Modal Approval & Rejection Kasbon (Direktur / Admin) -->
<div wire:ignore.self class="modal fade" id="cashAdvanceApprovalModal" role="dialog" aria-labelledby="cashAdvanceApprovalModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered cash-advance-approval-dialog" role="document">
        <div class="modal-content cash-advance-approval-modal">
            <div class="modal-header cash-advance-approval-header">
                <h5 class="cash-advance-approval-title" id="cashAdvanceApprovalModalLabel">
                    Persetujuan Kasbon
                </h5>
                <button type="button" class="close text-muted m-0 p-0" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            @if ($selectedCashAdvance)
                <div class="modal-body cash-advance-approval-body">
                    @php
                        $empName = $selectedCashAdvance->employee?->full_name ?: 'Karyawan';
                        $words = preg_split('/\s+/', trim($empName));
                        $initials = strtoupper(substr($words[0] ?? 'K', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                    @endphp

                    <!-- Centered Profile Header (Avatar, Name, Dept • Position) -->
                    <div class="cash-advance-approval-profile">
                        <div class="cash-advance-approval-avatar">
                            {{ $initials }}
                        </div>
                        <div class="cash-advance-approval-name">
                            {{ $empName }}
                        </div>
                        <div class="cash-advance-approval-role">
                            {{ $selectedCashAdvance->employee?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $selectedCashAdvance->employee?->position?->name ?? 'Karyawan' }}
                        </div>
                    </div>

                    <!-- Centered Nominal Highlight & Badge -->
                    <div class="cash-advance-approval-hero">
                        <div class="cash-advance-approval-hero-label">
                            NOMINAL PENGAJUAN
                        </div>
                        <div class="cash-advance-approval-hero-amount">
                            {{ $selectedCashAdvance->formatted_amount }}
                        </div>
                        <span class="{{ $selectedCashAdvance->status_badge_class }}">
                            {{ $selectedCashAdvance->status_label }}
                        </span>
                    </div>

                    <!-- Clean Key-Value Information Tiles -->
                    <div class="cash-advance-approval-tiles">
                        <div class="cash-advance-approval-tile-row">
                            <span class="cash-advance-approval-tile-label">
                                <i class="fas fa-hashtag cash-advance-approval-tile-icon"></i> No. Referensi
                            </span>
                            <span class="cash-advance-approval-tile-value font-mono">
                                {{ $selectedCashAdvance->reference_number }}
                            </span>
                        </div>

                        <div class="cash-advance-approval-tile-row">
                            <span class="cash-advance-approval-tile-label">
                                <i class="fas fa-money-check-alt cash-advance-approval-tile-icon"></i> Skema Pelunasan
                            </span>
                            <span class="cash-advance-approval-tile-value">
                                {{ $selectedCashAdvance->scheme_label }}
                                @if ($selectedCashAdvance->repayment_scheme === \App\Enums\RepaymentScheme::SALARY_INSTALLMENT)
                                    <span class="cash-advance-approval-subtext">({{ $selectedCashAdvance->installment_count }}x Cicilan)</span>
                                @endif
                            </span>
                        </div>

                        <div class="cash-advance-approval-tile-row">
                            <span class="cash-advance-approval-tile-label">
                                <i class="far fa-calendar-alt cash-advance-approval-tile-icon"></i> Tgl Pencairan
                            </span>
                            <span class="cash-advance-approval-tile-value">
                                {{ $selectedCashAdvance->disbursement_date?->translatedFormat('d M Y') ?? '-' }}
                            </span>
                        </div>

                        <div class="cash-advance-approval-tile-row">
                            <span class="cash-advance-approval-tile-label">
                                <i class="far fa-user cash-advance-approval-tile-icon"></i> Diajukan Oleh
                            </span>
                            <span class="cash-advance-approval-tile-value">
                                {{ $selectedCashAdvance->submitter?->name ?? 'System' }}
                                @if ($selectedCashAdvance->created_at)
                                    <span class="cash-advance-approval-subtext">({{ $selectedCashAdvance->created_at->translatedFormat('d M Y H:i') }})</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Alasan / Keperluan -->
                    <div class="cash-advance-approval-reason-box">
                        <div class="cash-advance-approval-reason-label">
                            <i class="far fa-comment-alt cash-advance-approval-tile-icon"></i> Alasan / Keperluan
                        </div>
                        <div class="cash-advance-approval-reason-text">
                            {{ $selectedCashAdvance->reason ?: '-' }}
                        </div>
                    </div>
                </div>

                <!-- Footer: 2 Action Buttons -->
                @can('approve', $selectedCashAdvance)
                    <div class="modal-footer cash-advance-approval-footer">
                        <button type="button" onclick="confirmRejectCashAdvance()" wire:loading.attr="disabled" wire:target="reject" class="btn btn-outline-danger cash-advance-approval-btn"><i wire:loading.remove wire:target="reject" class="fas fa-times mr-1"></i><i wire:loading wire:target="reject" class="fas fa-spinner fa-spin mr-1"></i>Tolak Pengajuan</button>
                        <button type="button" onclick="confirmApproveCashAdvance()" wire:loading.attr="disabled" wire:target="approve" class="btn btn-primary cash-advance-approval-btn"><i wire:loading.remove wire:target="approve" class="fas fa-check mr-1"></i><i wire:loading wire:target="approve" class="fas fa-spinner fa-spin mr-1"></i>Setujui Kasbon</button>
                    </div>
                @endcan
            @endif
        </div>
    </div>
</div>
