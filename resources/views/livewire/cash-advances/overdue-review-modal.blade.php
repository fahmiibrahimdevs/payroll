<!-- Modal Tinjau Cicilan Kasbon Terlewat (Overdue Review Modal) -->
<div wire:ignore.self class="modal fade" id="overdueReviewModal" role="dialog" aria-labelledby="overdueReviewModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;" role="document">
        <div class="modal-content">
            <!-- 1. Header Modal -->
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-dark" id="overdueReviewModalLabel">Tinjau Cicilan Terlewat</h5>
                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- 2. Modal Body: Daftar Karyawan dengan Cicilan Overdue -->
            <div class="modal-body p-3 p-md-3" style="max-height: 70vh; overflow-y: auto;">
                @if (isset($overdueReviewAdvances) && $overdueReviewAdvances->isNotEmpty())
                    <div class="d-flex flex-column" style="gap: 10px;">
                        @foreach ($overdueReviewAdvances as $item)
                            <div class="p-2.5 p-sm-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);" wire:key="overdue-review-{{ $item['cash_advance_id'] }}">
                                <!-- Baris 1: Identitas Karyawan & Nominal Terlewat (Kompak) -->
                                <div class="d-flex justify-content-between align-items-center mb-1.5" style="gap: 10px;">
                                    <div class="d-flex align-items-center min-w-0" style="gap: 10px;">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold" style="width: 34px; height: 34px; background-color: #dc2626; font-size: 13px; flex-shrink: 0;">
                                            {{ strtoupper(substr($item['employee_name'] ?? 'K', 0, 1)) }}
                                        </div>
                                        <div class="text-truncate">
                                            <div class="font-weight-600 text-dark text-truncate" style="font-size: 13.5px; line-height: 1.3;">
                                                {{ $item['employee_name'] }}
                                            </div>
                                            <div class="text-truncate" style="font-size: 11px; margin-top: 1px; color: #475569; font-weight: 400;">
                                                {{ $item['department_name'] }} &bull; {{ $item['position_name'] }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <div class="font-weight-600 text-danger" style="font-size: 14.5px; line-height: 1.3;">
                                            {{ $item['formatted_overdue_amount'] }}
                                        </div>
                                        <div style="font-size: 11px; margin-top: 1px; color: #dc2626; font-weight: 500;">
                                            {{ $item['overdue_count'] }} cicilan terlewat
                                        </div>
                                    </div>
                                </div>

                                <!-- Baris 2: Jatuh Tempo, Total Sisa Kasbon & Tombol Aksi (Kompak & Elegan) -->
                                <div class="d-flex justify-content-between align-items-center pt-2" style="border-top: 1px solid #f1f5f9; gap: 12px;">
                                    <div class="min-w-0" style="flex-grow: 1;">
                                        <div style="font-size: 12px; line-height: 1.35;">
                                            <span class="font-weight-600 text-dark">Jatuh Tempo:</span> <span style="font-weight: 400; color: #1e293b;">{{ $item['formatted_due_date'] }}</span>
                                        </div>
                                        <div style="font-size: 11px; margin-top: 2px; color: #475569; font-weight: 400;">
                                            Sisa saldo kasbon: Rp {{ number_format($item['remaining_amount'], 0, ',', '.') }}
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center flex-shrink-0">
                                        <button type="button" onclick="$('#overdueReviewModal').modal('hide')" wire:click="selectEmployee({{ $item['employee_id'] }})" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center" style="font-size: 11.5px; border-radius: 8px; font-weight: 600; padding: 4px 12px; gap: 4px;" title="Lihat Rekap Kasbon Karyawan">
                                            <span>Tinjau</span>
                                            <i class="fas fa-arrow-right" style="font-size: 10px;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 36px;"></i>
                        <div class="font-weight-600 mt-2 text-dark">Semua Cicilan Lancar</div>
                        <div class="small mt-1" style="font-size: 11.5px; color: #475569;">Tidak ada karyawan yang memiliki cicilan kasbon terlewat jatuh tempo.</div>
                    </div>
                @endif
            </div>

            <!-- 3. Modal Footer: Kontras Jelas Tanpa Teks Abu-abu Pudar -->
            <div class="modal-footer d-flex justify-content-between align-items-center" style="background-color: #ffffff; border-top: 1px solid #f1f5f9; padding: 12px 20px;">
                <div style="font-size: 12.5px; color: #1e293b;">
                    <span>Total:</span> <span class="font-weight-600 text-dark">{{ isset($overdueReviewAdvances) ? $overdueReviewAdvances->count() : 0 }} Karyawan</span>
                    @if (isset($overdueReviewAdvances) && $overdueReviewAdvances->isNotEmpty())
                        <span style="color: #dc2626; font-weight: 600; margin-left: 4px;">(Total Terlewat: Rp {{ number_format($overdueReviewAdvances->sum('overdue_amount'), 0, ',', '.') }})</span>
                    @endif
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-3" data-dismiss="modal" style="font-weight: 500; border-radius: 8px;">Tutup</button>
            </div>
        </div>
    </div>
</div>
