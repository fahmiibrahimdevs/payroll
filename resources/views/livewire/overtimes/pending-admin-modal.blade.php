<!-- Modal Tinjau Verifikasi Admin (Review Submissions Waiting for Admin Verification) -->
<div wire:ignore.self class="modal fade" id="pendingAdminReviewModal" role="dialog" aria-labelledby="pendingAdminReviewModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;" role="document">
        <div class="modal-content">
            <!-- 1. Header Modal -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title font-weight-600 text-dark" id="pendingAdminReviewModalLabel">Verifikasi Pengajuan Lembur</h5>
                    <div style="font-size: 11.5px; color: #475569; font-weight: 400; margin-top: 2px;">
                        Pengajuan lembur karyawan yang menunggu koordinasi / verifikasi admin
                    </div>
                </div>
                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- 2. Modal Body: Daftar Pengajuan Lembur Pending Admin -->
            <div class="modal-body p-3 p-md-3" style="max-height: 70vh; overflow-y: auto;">
                @if (isset($pendingAdminSubmissions) && $pendingAdminSubmissions->isNotEmpty())
                    <div class="d-flex flex-column" style="gap: 10px;">
                        @foreach ($pendingAdminSubmissions as $item)
                            <div class="p-2.5 p-sm-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);" wire:key="pending-admin-{{ $item->id }}">
                                <!-- Baris 1: Identitas Karyawan & Subtotal Upah -->
                                <div class="d-flex justify-content-between align-items-center mb-1.5" style="gap: 10px;">
                                    <div class="d-flex align-items-center min-w-0" style="gap: 10px;">
                                        @if ($item->employee && $item->employee->photo_url)
                                            <img src="{{ $item->employee->photo_url }}" alt="{{ $item->employee->full_name }}" class="rounded-circle flex-shrink-0" style="width: 34px; height: 34px; object-fit: cover; border: 1.5px solid #cbd5e1;">
                                        @else
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold" style="width: 34px; height: 34px; background-color: #d97706; font-size: 13px; flex-shrink: 0;">
                                                {{ strtoupper(substr($item->employee?->full_name ?? 'K', 0, 1)) }}
                                            </div>
                                        @endif
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
                                            {{ $item->formatted_subtotal_overtime }}
                                        </div>
                                        <div style="font-size: 11px; margin-top: 1px; color: #475569; font-weight: 500;">
                                            {{ $item->formatted_duration }} &bull; {{ $item->formatted_rate_label }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Baris 2: Alasan, Tanggal, Jam Kerja & Tombol Aksi -->
                                <div class="d-flex justify-content-between align-items-center pt-2" style="border-top: 1px solid #f1f5f9; gap: 12px;">
                                    <div class="min-w-0" style="flex-grow: 1;">
                                        <div style="font-size: 12px; line-height: 1.35;">
                                            <span class="font-weight-600 text-dark">Alasan:</span> <span style="font-weight: 400; color: #1e293b;">{{ $item->reason ?: 'Tidak ada keterangan alasan.' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center flex-wrap" style="font-size: 11px; margin-top: 3px; color: #64748b; font-weight: 400; gap: 4px 10px;">
                                            <span class="text-nowrap"><i class="far fa-calendar-alt mr-1 text-muted"></i>{{ $item->date ? $item->date->translatedFormat('d M Y') : '-' }} &bull; {{ $item->isDaily() ? 'Lembur Harian' : $item->formatted_work_hours }}</span>
                                            <span class="text-nowrap">Diajukan: <strong class="text-dark font-weight-600">{{ $item->submitter?->name ?? ($item->employee?->full_name ?? 'Karyawan') }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center flex-shrink-0" style="gap: 6px;">
                                        <!-- Direct navigate to row in table -->
                                        <button type="button" 
                                                onclick="$('#pendingAdminReviewModal').modal('hide')" 
                                                wire:click="directToSubmission({{ $item->id }})" 
                                                class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center shadow-xs" 
                                                style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" 
                                                title="Lihat di Tabel Lembur">
                                            <i class="fas fa-arrow-right" style="font-size: 11px;"></i>
                                        </button>

                                        @if (auth()->user()?->hasRole('admin'))
                                            <!-- Sesuaikan Jam / Verifikasi Admin -->
                                            <button type="button" 
                                                    onclick="$('#pendingAdminReviewModal').modal('hide')" 
                                                    wire:click="openAdjustModal({{ $item->id }})" 
                                                    class="btn btn-primary d-inline-flex align-items-center justify-content-center shadow-xs" 
                                                    style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" 
                                                    title="Koordinasi & Sesuaikan Jam Kerja">
                                                <i class="fas fa-user-edit" style="font-size: 12px;"></i>
                                            </button>

                                            <!-- Tolak Pengajuan -->
                                            <button type="button" 
                                                    onclick="$('#pendingAdminReviewModal').modal('hide'); confirmRejectOvertime({{ $item->id }}, '{{ $item->reference_number }}');" 
                                                    class="btn btn-danger d-inline-flex align-items-center justify-content-center shadow-xs" 
                                                    style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" 
                                                    title="Tolak Pengajuan">
                                                <i class="fas fa-times" style="font-size: 12px;"></i>
                                            </button>
                                        @else
                                            <span class="badge badge-warning" style="font-size: 11px; font-weight: 600;">Menunggu Admin</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 36px;"></i>
                        <div class="font-weight-600 mt-2 text-dark">Tidak Ada Pengajuan Menunggu</div>
                        <div class="small mt-1" style="font-size: 11.5px; color: #475569;">Semua pengajuan lembur telah diverifikasi admin.</div>
                    </div>
                @endif
            </div>

            <!-- 3. Modal Footer -->
            <div class="modal-footer d-flex justify-content-between align-items-center" style="background-color: #ffffff; border-top: 1px solid #f1f5f9; padding: 12px 20px;">
                <div style="font-size: 12.5px; color: #1e293b;">
                    <span>Total:</span> <span class="font-weight-600 text-dark">{{ isset($pendingAdminSubmissions) ? $pendingAdminSubmissions->count() : 0 }} pengajuan</span>
                    <span style="color: #0b52aa; font-weight: 600; margin-left: 4px;">({{ isset($pendingAdminSubmissions) ? 'Rp ' . number_format($pendingAdminSubmissions->sum(fn ($i) => (float) $i->subtotal_overtime), 0, ',', '.') : 'Rp 0' }})</span>
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-3" data-dismiss="modal" style="font-weight: 500; border-radius: 8px;">Tutup</button>
            </div>
        </div>
    </div>
</div>
