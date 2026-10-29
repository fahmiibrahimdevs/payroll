<!-- Modal Tinjau Kontrak Segera Berakhir (Expiring Contracts Review Modal) -->
<div wire:ignore.self class="modal fade" id="expiringContractsModal" role="dialog" aria-labelledby="expiringContractsModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;" role="document">
        <div class="modal-content">
            <!-- 1. Header Modal -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title font-weight-600 text-dark" id="expiringContractsModalLabel">Tinjau Kontrak Karyawan</h5>
                    <div style="font-size: 11.5px; color: #64748b; font-weight: 400; margin-top: 2px;">
                        Karyawan dengan kontrak kerja yang akan atau sudah berakhir
                    </div>
                </div>
                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- 2. Modal Body: Daftar Karyawan Kontrak Segera Berakhir -->
            <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                @if (isset($expiringEmployees) && $expiringEmployees->total() > 0)
                    <div class="d-flex flex-column" style="gap: 10px;">
                        @foreach ($expiringEmployees as $emp)
                            @php
                                $isOverdue = $emp->contract_end_date && now()->startOfDay()->gt($emp->contract_end_date->startOfDay());
                                $isToday = $emp->contract_end_date && now()->startOfDay()->equalTo($emp->contract_end_date->startOfDay());
                            @endphp
                            <div class="p-2.5 p-sm-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);" wire:key="expiring-emp-{{ $emp->id }}">
                                <!-- Baris 1: Identitas Karyawan & Status Durasi Kontrak -->
                                <div class="d-flex justify-content-between align-items-center mb-1.5" style="gap: 10px;">
                                    <div class="d-flex align-items-center min-w-0" style="gap: 10px;">
                                        <img src="{{ $emp->photo_url }}" alt="{{ $emp->full_name }}" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover; border: 1.5px solid #cbd5e1; flex-shrink: 0;">
                                        <div class="text-truncate">
                                            <div class="font-weight-600 text-dark text-truncate" style="font-size: 13.5px; line-height: 1.3;">
                                                {{ $emp->full_name }}
                                            </div>
                                            <div class="text-truncate" style="font-size: 11px; margin-top: 1px; color: #475569; font-weight: 400;">
                                                {{ $emp->department?->name ?: 'Tanpa Departemen' }} &bull; {{ $emp->position?->name ?: 'Tanpa Jabatan' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="{{ $emp->employment_status_badge_class }} text-xs font-weight-500">
                                            {{ $emp->employment_status_label }}
                                        </span>
                                        <div style="font-size: 11px; margin-top: 3px; color: {{ $isOverdue || $isToday ? '#dc2626' : '#d97706' }}; font-weight: 600;">
                                            {{ $emp->remaining_contract_label }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Baris 2: Tanggal Berakhir Kontrak & Tombol Aksi -->
                                <div class="d-flex justify-content-between align-items-center pt-2" style="border-top: 1px solid #f1f5f9; gap: 12px;">
                                    <div class="min-w-0" style="flex-grow: 1;">
                                        <div style="font-size: 12px; line-height: 1.35;">
                                            <span class="font-weight-500 text-dark">Berakhir:</span>
                                            <span style="font-weight: 500; color: {{ $isOverdue || $isToday ? '#dc2626' : '#1e293b' }};">
                                                {{ $emp->contract_end_date ? $emp->contract_end_date->format('d M Y') : '-' }}
                                            </span>
                                        </div>
                                        <div style="font-size: 11px; margin-top: 2px; color: #64748b; font-weight: 400;">
                                            Kode: {{ $emp->detail?->employee_code ?: '-' }} &bull; No: {{ $emp->detail?->phone ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center flex-shrink-0" style="gap: 6px;">
                                        <a href="{{ route('employees.show', $emp) }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center" style="font-size: 11.5px; border-radius: 8px; font-weight: 600; padding: 4px 10px; gap: 4px;" title="Lihat Profil Lengkap">
                                            <span>Profil</span>
                                            <i class="fas fa-eye" style="font-size: 10px;"></i>
                                        </a>
                                        @if (auth()->user()->hasRole('admin'))
                                            <button type="button" onclick="$('#expiringContractsModal').modal('hide')" wire:click="edit('{{ $emp->hashid }}')" wire:loading.attr="disabled" wire:target="edit('{{ $emp->hashid }}')" class="btn btn-primary btn-sm d-inline-flex align-items-center" style="font-size: 11.5px; border-radius: 8px; font-weight: 600; padding: 4px 10px; gap: 4px;" title="Perbarui / Edit Kontrak">
                                                <i wire:loading.remove wire:target="edit('{{ $emp->hashid }}')" class="fas fa-pencil-alt" style="font-size: 10px;"></i>
                                                <i wire:loading wire:target="edit('{{ $emp->hashid }}')" class="fas fa-spinner fa-spin" style="font-size: 10px;"></i>
                                                <span>Perbarui</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($expiringEmployees->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $expiringEmployees->links('livewire.custom-pagination') }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 36px;"></i>
                        <div class="font-weight-600 mt-2 text-dark">Semua Kontrak Aman</div>
                        <div class="small mt-1" style="font-size: 11.5px; color: #475569;">Tidak ada kontrak karyawan yang berakhir dalam 30 hari ke depan.</div>
                    </div>
                @endif
            </div>

            <!-- 3. Modal Footer -->
            <div class="modal-footer d-flex justify-content-between align-items-center" style="background-color: #ffffff; border-top: 1px solid #f1f5f9; padding: 12px 20px;">
                <div style="font-size: 12.5px; color: #1e293b;">
                    <span>Total:</span> <span class="font-weight-600 text-dark">{{ isset($expiringEmployees) ? $expiringEmployees->total() : 0 }} Karyawan</span>
                    @if (isset($expiringEmployees) && $expiringEmployees->total() > 0)
                        <span class="text-danger font-weight-500 ml-1">perlu ditindaklanjuti</span>
                    @endif
                </div>
                <button type="button" class="btn btn-secondary btn-sm font-weight-600" data-dismiss="modal" style="border-radius: 8px; padding: 5px 16px;">Tutup</button>
            </div>
        </div>
    </div>
</div>
