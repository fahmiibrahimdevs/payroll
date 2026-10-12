<!-- Modal Detail & Riwayat Kasbon -->
<div wire:ignore.self class="modal fade" id="cashAdvanceDetailModal" role="dialog" aria-labelledby="cashAdvanceDetailModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content cash-advance-detail-modal">
            <div class="modal-header cash-advance-detail-header">
                <h5 class="modal-title cash-advance-detail-title" id="cashAdvanceDetailModalLabel">
                    <span>Rincian Kasbon</span>
                    <span class="cash-advance-detail-reference">{{ $selectedCashAdvance?->reference_number }}</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            @if ($selectedCashAdvance)
                <div class="modal-body cash-advance-detail-body">
                    <!-- Single Elevated Info Card (Mobile / Flutter Style) -->
                    <div class="cash-advance-card">
                        <!-- Top Row: Karyawan (Avatar + Nama + Dept/Jabatan) & Badges (Status + Skema) -->
                        <div class="cash-advance-card-top">
                            <div class="cash-advance-card-employee">
                                @php
                                    $empName = $selectedCashAdvance->employee?->full_name ?: 'Karyawan';
                                    $words = preg_split('/\s+/', trim($empName));
                                    $initials = strtoupper(substr($words[0] ?? 'K', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                @endphp
                                <div class="cash-advance-card-avatar !bg-slate-100 !text-slate-700 !border-slate-300" aria-hidden="true">
                                    {{ $initials }}
                                </div>
                                <div class="cash-advance-card-employee-details">
                                    <div class="cash-advance-card-employee-name">{{ $empName }}</div>
                                    <div class="cash-advance-card-employee-dept">
                                        {{ $selectedCashAdvance->employee?->department?->name ?? 'Tanpa Departemen' }}
                                        <span class="cash-advance-card-dot">&bull;</span>
                                        {{ $selectedCashAdvance->employee?->position?->name ?? 'Karyawan' }}
                                    </div>
                                </div>
                            </div>

                            <div class="cash-advance-card-badges">
                                <span class="{{ $selectedCashAdvance->status_badge_class }}">{{ $selectedCashAdvance->status_label }}</span>
                                <span class="{{ $selectedCashAdvance->scheme_badge_class }}">{{ $selectedCashAdvance->scheme_short_label }}</span>
                            </div>
                        </div>

                        <!-- Metadata Row: Tgl Pencairan & Diajukan Oleh -->
                        <div class="cash-advance-card-meta-row !text-[12px]">
                            <div class="cash-advance-card-meta-item">
                                <span class="cash-advance-card-meta-label !text-[12px]">Tgl Pencairan:</span>
                                <span class="cash-advance-card-meta-value !text-[12.5px]">{{ $selectedCashAdvance->disbursement_date ? $selectedCashAdvance->disbursement_date->translatedFormat('d M Y') : '-' }}</span>
                            </div>
                            <div class="cash-advance-card-meta-item">
                                <span class="cash-advance-card-meta-label !text-[12px]">Diajukan Oleh:</span>
                                <span class="cash-advance-card-meta-value !text-[12.5px]">{{ $selectedCashAdvance->submitter?->name ?? 'System' }} <small class="text-muted">({{ $selectedCashAdvance->created_at?->translatedFormat('d M Y H:i') }})</small></span>
                            </div>
                        </div>

                        <!-- 3-Column Financial Metrics (Centered) -->
                        <div class="cash-advance-card-metrics">
                            <div class="cash-advance-card-metric-col">
                                <div class="cash-advance-card-metric-title !text-[10px] sm:!text-[11px] text-primary">TOTAL KASBON</div>
                                <div class="cash-advance-card-metric-amount !text-slate-900 !text-[13px] sm:!text-[14.5px] font-bold whitespace-nowrap">
                                    {{ $selectedCashAdvance->formatted_amount }}
                                </div>
                            </div>
                            <div class="cash-advance-card-metric-col">
                                <div class="cash-advance-card-metric-title !text-[10px] sm:!text-[11px] text-primary">TERBAYAR</div>
                                <div class="cash-advance-card-metric-amount !text-slate-900 !text-[13px] sm:!text-[14.5px] font-bold whitespace-nowrap">
                                    {{ $selectedCashAdvance->formatted_paid_amount }}
                                </div>
                            </div>
                            <div class="cash-advance-card-metric-col">
                                <div class="cash-advance-card-metric-title !text-[10px] sm:!text-[11px] text-primary">SISA KASBON</div>
                                <div class="cash-advance-card-metric-amount !text-slate-900 !text-[13px] sm:!text-[14.5px] font-bold whitespace-nowrap">
                                    {{ $selectedCashAdvance->formatted_remaining_amount }}
                                </div>
                            </div>
                        </div>

                        @php
                            $progress = (float) $selectedCashAdvance->progress_percentage;
                            if ($progress >= 100) {
                                $progressFillClass = 'bg-success';
                                $progressTextClass = 'text-success';
                            } elseif ($progress >= 75) {
                                $progressFillClass = 'bg-info';
                                $progressTextClass = 'text-info';
                            } elseif ($progress >= 40) {
                                $progressFillClass = 'bg-warning';
                                $progressTextClass = 'text-warning';
                            } else {
                                $progressFillClass = 'bg-danger';
                                $progressTextClass = 'text-danger';
                            }
                        @endphp
                        <!-- Mini Progress Bar Pelunasan -->
                        <div class="cash-advance-card-progress-wrap">
                            <div class="cash-advance-card-progress-label-row !text-[12px]">
                                <span class="cash-advance-card-progress-title !text-[12px]">Progress Pelunasan</span>
                                <span class="cash-advance-card-progress-percentage {{ $progressTextClass }} !text-[13px]">{{ $selectedCashAdvance->progress_percentage }}%</span>
                            </div>
                            <div class="cash-advance-card-progress-track !h-[7px]">
                                <div class="cash-advance-card-progress-fill {{ $progressFillClass }}" style="width: {{ min(100, max(0, $progress)) }}%;"></div>
                            </div>
                        </div>

                        <!-- Keperluan / Alasan -->
                        @if ($selectedCashAdvance->reason)
                            <div class="cash-advance-card-reason !text-[12.5px]">
                                <div class="cash-advance-card-reason-content">
                                    <span class="cash-advance-card-reason-title !text-[12.5px]">Keperluan:</span>
                                    <span class="cash-advance-card-reason-text !text-[12.5px]">{{ $selectedCashAdvance->reason }}</span>
                                </div>
                            </div>
                        @endif

                        @if ($selectedCashAdvance->rejection_reason)
                            <div class="cash-advance-card-reason cash-advance-card-reason-rejected !text-[12.5px]">
                                <div class="cash-advance-card-reason-content">
                                    <span class="cash-advance-card-reason-title text-danger !text-[12.5px]">Alasan Penolakan:</span>
                                    <span class="cash-advance-card-reason-text text-danger !text-[12.5px]">{{ $selectedCashAdvance->rejection_reason }}</span>
                                </div>
                            </div>
                        @endif

                    </div>

                    <!-- Nav Tabs & Content (Hidden if status is REJECTED) -->
                    @if ($selectedCashAdvance->status !== \App\Enums\CashAdvanceStatus::REJECTED && $selectedCashAdvance->status !== 'rejected')
                        <ul class="nav nav-pills cash-advance-detail-tabs mt-3" id="cashAdvanceDetailTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link {{ $detailModalTab === 'installments' ? 'active' : '' }}" id="installments-tab" data-toggle="tab" href="#installments-pane" role="tab" aria-controls="installments-pane" aria-selected="{{ $detailModalTab === 'installments' ? 'true' : 'false' }}">
                                    Jadwal Cicilan ({{ $selectedCashAdvance->installments->count() }})
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $detailModalTab === 'payments' ? 'active' : '' }}" id="payments-tab" data-toggle="tab" href="#payments-pane" role="tab" aria-controls="payments-pane" aria-selected="{{ $detailModalTab === 'payments' ? 'true' : 'false' }}">
                                    Riwayat Pembayaran ({{ $selectedCashAdvance->payments->count() }})
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content -mt-2 lg:-mt-3!" id="cashAdvanceDetailTabContent">
                            <!-- Pane 1: Jadwal Cicilan (Auto width, NO scroll) -->
                            <div class="tab-pane fade {{ $detailModalTab === 'installments' ? 'show active' : '' }}" id="installments-pane" role="tabpanel" aria-labelledby="installments-tab">
                                <div class="-mt-5 mb-3">
                                    <h2 class="section-title">Jadwal Angsuran Cicilan</h2>
                                    <p class="section-lead">Rencana jatuh tempo dan status pelunasan cicilan kasbon per bulan.</p>
                                </div>

                                <div class="cash-advance-detail-table-wrap">
                                    <table class="table table-sm cash-advance-detail-table cash-advance-detail-table-installments mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-center text-nowrap">CICILAN</th>
                                                <th class="text-nowrap">JATUH TEMPO</th>
                                                <th class="text-right text-nowrap">TAGIHAN</th>
                                                <th class="text-right text-nowrap">TERBAYAR</th>
                                                <th class="text-right text-nowrap">SISA</th>
                                                <th class="text-center text-nowrap">STATUS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($selectedCashAdvance->installments as $inst)
                                                <tr wire:key="inst-{{ $inst->id }}">
                                                    <td class="text-center align-middle text-muted font-weight-600" data-label="Cicilan">Ke-{{ $inst->installment_number }}</td>
                                                    <td class="align-middle text-nowrap" data-label="Jatuh tempo">{{ $inst->due_date?->translatedFormat('d M Y') }}</td>
                                                    <td class="text-right align-middle text-nowrap text-dark cash-advance-detail-amount" data-label="Tagihan">{{ $inst->formatted_amount }}</td>
                                                    <td class="text-right align-middle text-nowrap text-dark cash-advance-detail-amount" data-label="Terbayar">{{ $inst->formatted_paid_amount }}</td>
                                                    <td class="text-right align-middle text-nowrap text-dark cash-advance-detail-amount" data-label="Sisa">{{ $inst->formatted_remaining_amount }}</td>
                                                    <td class="text-center align-middle text-nowrap" data-label="Status"><span class="{{ $inst->status_badge_class }}">{{ $inst->status_label }}</span></td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-muted"><em>Jadwal cicilan belum digenerate.</em></td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pane 2: Riwayat Pembayaran (Clean 3-Line Cards) -->
                            <div class="tab-pane fade {{ $detailModalTab === 'payments' ? 'show active' : '' }}" id="payments-pane" role="tabpanel" aria-labelledby="payments-tab">
                                <div class="-mt-5 mb-3">
                                    <h2 class="section-title">Riwayat Transaksi Pembayaran</h2>
                                    <p class="section-lead">Catatan setoran atau pemotongan gaji yang telah dicatat untuk kasbon ini.</p>
                                </div>
                                <div class="space-y-2">
                                    @forelse ($selectedCashAdvance->payments as $pay)
                                        @php
                                            $isHighlighted = ((int) $highlightPaymentId === (int) $pay->id);
                                        @endphp
                                        <div id="pay-card-{{ $pay->id }}" class="border rounded-lg p-2.5 transition-all duration-200 {{ $isHighlighted ? 'bg-blue-50/70 border-blue-500 ring-2 ring-blue-200 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300' }}" wire:key="pay-card-{{ $pay->id }}">
                                            <!-- Line 1: NO REFERENCE, STATUS & JUMLAH -->
                                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                    <span class="font-bold text-slate-900 text-[13px] tracking-tight">
                                                        {{ $pay->payment_number }}
                                                    </span>
                                                    <span class="{{ $pay->status_badge_class }} text-xs">
                                                        {{ $pay->status_label }}
                                                    </span>
                                                    <span class="{{ $pay->method_badge_class }} text-xs">
                                                        {{ $pay->method_label }}
                                                    </span>
                                                </div>
                                                <span class="font-bold text-slate-900 text-[13.5px] whitespace-nowrap">
                                                    {{ $pay->formatted_amount }}
                                                </span>
                                            </div>

                                            <!-- Line 2: KETERANGAN (Full text, no truncation) -->
                                            <div class="text-[12px] text-slate-600 font-medium my-1 leading-normal break-words">
                                                {{ $pay->notes ?: '-' }}
                                            </div>

                                            @if ($pay->isRejected() && $pay->rejection_reason)
                                                <div class="text-[11.5px] text-danger font-medium mt-1 mb-1 bg-danger-light p-1.5 rounded">
                                                    <i class="fas fa-info-circle mr-1"></i>Alasan Ditolak: {{ $pay->rejection_reason }}
                                                </div>
                                            @endif

                                            <!-- Line 3: TANGGAL WAKTU & LIHAT BUKTI -->
                                            <div class="flex items-center justify-between gap-2 text-[11.5px] text-slate-500">
                                                <span>
                                                    {{ $pay->created_at ? $pay->created_at->translatedFormat('d M Y H:i') : $pay->payment_date?->translatedFormat('d M Y') }}
                                                    @if ($pay->recorder)
                                                        &bull; Oleh {{ $pay->recorder->name }}
                                                    @endif
                                                </span>
                                                @if ($pay->proof_document)
                                                    <a href="{{ $pay->proof_document_url }}" target="_blank" class="inline-flex items-center gap-1 text-[#0b52aa] hover:text-blue-800 font-semibold text-[11.5px] whitespace-nowrap">
                                                        <i class="fas fa-file-alt text-[10.5px]"></i> Lihat Bukti
                                                    </a>
                                                @endif
                                            </div>

                                            <!-- Direktur Approval Actions for Pending Payment -->
                                            @if ($pay->isPending() && auth()->user()?->hasRole('direktur'))
                                                <div class="d-flex align-items-center justify-content-between gap-2 mt-2 pt-2 border-top">
                                                    <span class="text-xs text-muted"><i class="fas fa-shield-alt mr-1"></i>Persetujuan Pembayaran:</span>
                                                    <div class="d-flex align-items-center gap-1.5">
                                                        <button type="button" onclick="confirmRejectPayment({{ $pay->id }})" class="btn btn-sm btn-outline-danger py-1 px-2.5" style="font-size: 11.5px;">
                                                            <i class="fas fa-times mr-1"></i> Tolak
                                                        </button>
                                                        <button type="button" onclick="confirmApprovePayment({{ $pay->id }})" class="btn btn-sm btn-primary py-1 px-2.5" style="background-color: #0b52aa; border-color: #0b52aa; font-size: 11.5px;">
                                                            <i class="fas fa-check mr-1"></i> Setujui (ACC)
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-center py-6 px-3 border border-dashed border-slate-200 rounded-lg bg-slate-50/50 text-slate-500">
                                            <div class="text-[13px] font-medium text-slate-700">Belum ada riwayat pembayaran</div>
                                            <div class="text-[11.5px] text-slate-400 mt-0.5">Belum ada transaksi pembayaran yang dicatat untuk kasbon ini.</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="modal-footer cash-advance-detail-footer">
                <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
