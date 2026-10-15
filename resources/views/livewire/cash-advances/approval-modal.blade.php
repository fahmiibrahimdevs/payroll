<!-- Modal Approval & Rejection Kasbon (Direktur / Admin) -->
<div wire:ignore.self class="modal fade" id="cashAdvanceApprovalModal" role="dialog" aria-labelledby="cashAdvanceApprovalModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header px-4 pt-4 pb-2 border-0">
                <h5 class="modal-title font-weight-bold text-dark" id="cashAdvanceApprovalModalLabel" style="font-size: 16.5px;">Persetujuan Kasbon</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup" style="font-size: 22px; font-weight: 400; color: #94a3b8; opacity: 0.8; line-height: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            @if ($selectedCashAdvance)
                @php
                    $empName = $selectedCashAdvance->employee?->full_name ?: 'Karyawan';
                    $cleanedWords = array_values(array_filter(
                        preg_split('/\s+/', trim($empName)),
                        fn($w) => preg_match('/[a-zA-Z0-9]/', $w)
                    ));
                    $initials = '';
                    if (!empty($cleanedWords)) {
                        $initials .= strtoupper(substr($cleanedWords[0], 0, 1));
                        if (isset($cleanedWords[1])) {
                            $initials .= strtoupper(substr($cleanedWords[1], 0, 1));
                        }
                    }
                    $initials = $initials ?: 'K';

                    $statusStyles = match ($selectedCashAdvance->status) {
                        \App\Enums\CashAdvanceStatus::PENDING => 'background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;',
                        \App\Enums\CashAdvanceStatus::APPROVED, \App\Enums\CashAdvanceStatus::ACTIVE => 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;',
                        \App\Enums\CashAdvanceStatus::PAID => 'background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;',
                        \App\Enums\CashAdvanceStatus::REJECTED, \App\Enums\CashAdvanceStatus::CANCELLED => 'background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;',
                        default => 'background-color: #f8fafc; color: #475569; border: 1px solid #e2e8f0;',
                    };
                @endphp

                <div class="modal-body px-4 py-2">
                    <!-- 1. Identitas Karyawan -->
                    <div class="text-center pt-1">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 font-weight-bold"
                             style="width: 64px; height: 64px; background-color: #eff6ff; color: #0284c7; border: 2px solid #bae6fd; font-size: 20px;">
                            {{ $initials }}
                        </div>
                        <div class="font-weight-bold" style="font-size: 17px; color: #0f172a; margin-bottom: 3px;">
                            {{ $empName }}
                        </div>
                        <div style="font-size: 13px; color: #64748b; font-weight: 500;">
                            {{ $selectedCashAdvance->employee?->department?->name ?? 'Tanpa Departemen' }} &bull;
                            {{ $selectedCashAdvance->employee?->position?->name ?? 'Staff' }}
                        </div>
                    </div>

                    <!-- Dashed Divider -->
                    <div style="border-top: 1px dashed #e2e8f0; margin: 16px 0;"></div>

                    <!-- 2. Nominal Pengajuan & Status Badge -->
                    <div class="text-center">
                        <div class="text-uppercase" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; color: #64748b; margin-bottom: 4px;">
                            NOMINAL PENGAJUAN
                        </div>
                        <div class="font-weight-bold" style="font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-bottom: 8px;">
                            {{ $selectedCashAdvance->formatted_amount }}
                        </div>
                        <div>
                            <span style="display: inline-block; padding: 4px 14px; font-size: 12.5px; font-weight: 600; border-radius: 6px; line-height: 1.4; {{ $statusStyles }}">
                                {{ $selectedCashAdvance->status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Dashed Divider -->
                    <div style="border-top: 1px dashed #e2e8f0; margin: 16px 0;"></div>

                    <!-- 3. Rincian Detail Box -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #ffffff; margin-bottom: 14px;">
                        <!-- No. Referensi -->
                        <div class="d-flex justify-content-between align-items-center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-hashtag mr-2" style="color: #0061f2; font-size: 13px; width: 16px; text-align: center;"></i>
                                <span style="color: #475569; font-size: 13px; font-weight: 500;">No. Referensi</span>
                            </div>
                            <span style="color: #0f172a; font-size: 13px; font-weight: 600;">{{ $selectedCashAdvance->reference_number }}</span>
                        </div>

                        <!-- Skema Pelunasan -->
                        <div class="d-flex justify-content-between align-items-center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-money-check-alt mr-2" style="color: #0061f2; font-size: 13px; width: 16px; text-align: center;"></i>
                                <span style="color: #475569; font-size: 13px; font-weight: 500;">Skema Pelunasan</span>
                            </div>
                            <span style="color: #0f172a; font-size: 13px; font-weight: 600;">
                                {{ $selectedCashAdvance->scheme_label }}
                                @if ($selectedCashAdvance->repayment_scheme === \App\Enums\RepaymentScheme::SALARY_INSTALLMENT)
                                    <span style="color: #64748b; font-weight: 400; font-size: 13px; margin-left: 2px;">({{ $selectedCashAdvance->installment_count }}x Cicilan)</span>
                                @endif
                            </span>
                        </div>

                        <!-- Tgl Pencairan -->
                        <div class="d-flex justify-content-between align-items-center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                            <div class="d-flex align-items-center">
                                <i class="far fa-calendar-alt mr-2" style="color: #0061f2; font-size: 13px; width: 16px; text-align: center;"></i>
                                <span style="color: #475569; font-size: 13px; font-weight: 500;">Tgl Pencairan</span>
                            </div>
                            <span style="color: #0f172a; font-size: 13px; font-weight: 600;">{{ $selectedCashAdvance->disbursement_date?->translatedFormat('d M Y') ?? '-' }}</span>
                        </div>

                        <!-- Diajukan Oleh -->
                        <div class="d-flex justify-content-between align-items-center" style="padding: 10px 14px;">
                            <div class="d-flex align-items-center">
                                <i class="far fa-user mr-2" style="color: #0061f2; font-size: 13px; width: 16px; text-align: center;"></i>
                                <span style="color: #475569; font-size: 13px; font-weight: 500;">Diajukan Oleh</span>
                            </div>
                            <span style="color: #0f172a; font-size: 13px; font-weight: 600;">
                                {{ $selectedCashAdvance->submitter?->name ?? 'System' }}
                                @if ($selectedCashAdvance->created_at)
                                    <span style="color: #64748b; font-weight: 400; font-size: 13px; margin-left: 2px;">({{ $selectedCashAdvance->created_at->translatedFormat('d M Y H:i') }})</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- 4. Alasan / Keperluan Box -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; background: #ffffff;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="far fa-comment-alt mr-2" style="color: #0061f2; font-size: 13px; width: 16px; text-align: center;"></i>
                            <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; color: #475569; text-transform: uppercase;">ALASAN / KEPERLUAN</span>
                        </div>
                        <div style="font-size: 13.5px; color: #0f172a; font-weight: 400; line-height: 1.5; margin-left: 1px;">
                            {{ $selectedCashAdvance->reason ?: '-' }}
                        </div>
                    </div>
                </div>

                <!-- 5. Action Buttons -->
                @can('approve', $selectedCashAdvance)
                    <div class="modal-footer border-0 px-4 pb-4 pt-3">
                        <div class="d-flex align-items-center w-100" style="gap: 12px;">
                            <button type="button" onclick="confirmRejectCashAdvance()" wire:loading.attr="disabled" wire:target="reject"
                                    class="btn btn-danger flex-fill m-0 d-inline-flex align-items-center justify-content-center"
                                    style="border-radius: 8px; font-weight: 700; font-size: 14px; padding: 10px 16px; box-shadow: none;">
                                <i wire:loading.remove wire:target="reject" class="fas fa-times mr-2" style="font-size: 13px;"></i>
                                <i wire:loading wire:target="reject" class="fas fa-spinner fa-spin mr-2" style="font-size: 13px;"></i>
                                Tolak Pengajuan
                            </button>
                            <button type="button" onclick="confirmApproveCashAdvance()" wire:loading.attr="disabled" wire:target="approve"
                                    class="btn btn-primary flex-fill m-0 d-inline-flex align-items-center justify-content-center"
                                    style="border-radius: 8px; font-weight: 700; font-size: 14px; padding: 10px 16px; box-shadow: none;">
                                <i wire:loading.remove wire:target="approve" class="fas fa-check mr-2" style="font-size: 13px;"></i>
                                <i wire:loading wire:target="approve" class="fas fa-spinner fa-spin mr-2" style="font-size: 13px;"></i>
                                Setujui Kasbon
                            </button>
                        </div>
                    </div>
                @endcan
            @endif
        </div>
    </div>
</div>
