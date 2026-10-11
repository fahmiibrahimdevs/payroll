<!-- Modal Rincian Tagihan Bulanan (Statement Detail) -->
<div wire:ignore.self class="modal fade" id="statementDetailModal" role="dialog" aria-labelledby="statementDetailModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content statement-modal-content">
            <div class="modal-header statement-modal-header">
                <h5 class="modal-title statement-modal-title" id="statementDetailModalLabel">
                    <i class="fas fa-file-invoice-dollar mr-1 text-primary"></i>
                    Rincian Tagihan {{ $selectedStatementDetail['month_name_full'] ?? '' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            @if ($selectedStatementDetail)
                <div class="modal-body statement-modal-body">
                    <!-- Status Header Banner Card -->
                    <div class="statement-status-banner">
                        <div class="statement-status-icon-wrap">
                            @if ($selectedStatementDetail['is_paid'] ?? false)
                                <div class="statement-status-icon statement-status-icon-paid">
                                    <i class="fas fa-check"></i>
                                </div>
                            @else
                                <div class="statement-status-icon statement-status-icon-unpaid">
                                    <i class="fas fa-clock"></i>
                                </div>
                            @endif
                        </div>
                        <div class="statement-status-title">
                            {{ ($selectedStatementDetail['is_paid'] ?? false) ? 'Tagihan Telah Dilunasi' : 'Tagihan Belum Lunas' }}
                        </div>
                        <div class="statement-status-period">
                            Periode {{ $selectedStatementDetail['month_name_full'] ?? '' }}
                        </div>
                        <div class="statement-status-due">
                            Jatuh Tempo: <span class="statement-status-due-val">{{ $selectedStatementDetail['formatted_due_date'] ?? '-' }}</span>
                        </div>
                    </div>

                    <!-- Ringkasan Angka Tagihan Card -->
                    <div class="statement-metrics-card">
                        <div class="statement-metric-col">
                            <div class="statement-metric-label">Total Tagihan</div>
                            <div class="statement-metric-value">
                                {{ $selectedStatementDetail['formatted_total_amount'] ?? 'Rp 0' }}
                            </div>
                        </div>
                        <div class="statement-metric-col">
                            <div class="statement-metric-label">Total Pembayaran</div>
                            <div class="statement-metric-value statement-metric-value-paid">
                                {{ $selectedStatementDetail['formatted_paid_amount'] ?? 'Rp 0' }}
                            </div>
                        </div>
                        <div class="statement-metric-col">
                            <div class="statement-metric-label">Sisa Tagihan</div>
                            <div class="statement-metric-value {{ ($selectedStatementDetail['is_paid'] ?? false) ? '' : 'statement-metric-value-remaining' }}">
                                {{ $selectedStatementDetail['formatted_remaining_amount'] ?? 'Rp 0' }}
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Rincian Cicilan di Bulan Ini -->
                    <div class="statement-section-header">
                        <span class="statement-section-title">
                            Rincian Transaksi Cicilan ({{ count($selectedStatementDetail['items'] ?? []) }})
                        </span>
                    </div>

                    <div class="statement-items-list">
                        @forelse ($selectedStatementDetail['items'] ?? [] as $item)
                            <div class="statement-item-card {{ $item['is_paid'] ? 'statement-item-card-paid' : 'statement-item-card-unpaid' }}" wire:key="statement-item-{{ $item['id'] }}">
                                <div class="statement-item-main">
                                    <div class="statement-item-icon {{ $item['is_paid'] ? 'statement-item-icon-paid' : 'statement-item-icon-unpaid' }}">
                                        <i class="fas {{ $item['is_paid'] ? 'fa-check' : 'fa-clock' }}"></i>
                                    </div>
                                    <div class="statement-item-info">
                                        <div class="statement-item-title">{{ $item['title'] }}</div>
                                        @if (!empty($item['paid_at']))
                                            <div class="statement-item-meta statement-item-meta-paid">
                                                Lunas: {{ $item['paid_at'] }}
                                            </div>
                                        @elseif ($item['is_paid'])
                                            <div class="statement-item-meta statement-item-meta-paid">
                                                Lunas
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="statement-item-side">
                                    <div class="statement-item-amount">{{ $item['formatted_amount'] }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="statement-items-empty">
                                Tidak ada rincian cicilan untuk bulan ini.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="modal-footer statement-modal-footer">
                    <button type="button" class="btn btn-secondary shadow-none statement-btn-close" data-dismiss="modal">Tutup</button>
                    @can('recordEarlySettlement', \App\Models\CashAdvance::class)
                        @if (! ($selectedStatementDetail['is_paid'] ?? false))
                            <button type="button" wire:click="openEarlyPaymentModal('{{ $selectedStatementDetail['month_key'] }}')" wire:loading.attr="disabled" wire:target="openEarlyPaymentModal" data-dismiss="modal" class="btn btn-primary shadow-none statement-btn-action">
                                <i wire:loading.remove wire:target="openEarlyPaymentModal" class="fas fa-hand-holding-usd mr-1"></i>
                                <i wire:loading wire:target="openEarlyPaymentModal" class="fas fa-spinner fa-spin mr-1"></i>
                                <span>Bayar Tagihan Bulan Ini</span>
                            </button>
                        @endif
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>
