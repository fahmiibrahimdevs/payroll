<div wire:ignore.self class="modal fade" id="employeeDetailModal" role="dialog" aria-labelledby="employeeDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeDetailModalLabel">
                    Detail Profil Karyawan
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="$set('selectedEmployee', null)"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body p-3 p-md-4">
                @if ($selectedEmployee)
                    <!-- Flutter Mobile-Style Centered Profile Card -->
                    <div class="emp-profile-header text-center">
                        <div class="d-flex justify-content-center mb-2">
                            <img src="{{ $selectedEmployee->photo_url }}" alt="{{ $selectedEmployee->full_name }}" class="emp-profile-avatar-center">
                        </div>
                        <div class="emp-profile-name text-center">{{ $selectedEmployee->full_name }}</div>
                        <div class="emp-profile-role text-center mb-3">
                            {{ $selectedEmployee->department?->name ?: 'Tanpa Departemen' }} &bull; {{ $selectedEmployee->position?->name ?: 'Tanpa Jabatan' }}
                        </div>
                        <div class="emp-profile-tags justify-content-center">
                            <span class="{{ $selectedEmployee->employment_status_badge_class }}">
                                {{ $selectedEmployee->employment_status_label }}
                            </span>
                            <span class="{{ $selectedEmployee->status_badge_class }}">
                                {{ $selectedEmployee->status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Navigation Pills: 2 Tab Ringkas (Kepegawaian vs Pribadi & Pajak) -->
                    <ul class="nav nav-pills" id="employeeDetailPills" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-600 text-center" id="detail-employment-tab" data-toggle="pill" href="#detail-pill-kepegawaian" role="tab" aria-controls="detail-pill-kepegawaian" aria-selected="true">
                                <i class="fas fa-briefcase mr-1"></i> Kepegawaian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-600 text-center" id="detail-personal-tab" data-toggle="pill" href="#detail-pill-pribadi" role="tab" aria-controls="detail-pill-pribadi" aria-selected="false">
                                <i class="fas fa-id-card mr-1"></i> Pribadi & Pajak
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="employeeDetailPillsContent">
                        <!-- Tab 1: Kepegawaian (Status Masa Kerja, Kompensasi & Rekening Bank) -->
                        <div class="tab-pane fade show active" id="detail-pill-kepegawaian" role="tabpanel" aria-labelledby="detail-employment-tab">
                            <h2 class="section-title">Informasi Kepegawaian</h2>
                            <p class="section-lead">Rincian status kerja{{ auth()->user()->hasRole('admin') ? ' dan kompensasi gaji pokok' : '' }}.</p>
                            <div class="emp-tile-group">
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-calendar-check"></i></div>
                                        <span>Tanggal Bergabung</span>
                                    </div>
                                    <div class="emp-tile-right">
                                        {{ $selectedEmployee->join_date ? $selectedEmployee->join_date->format('d M Y') : '-' }}
                                    </div>
                                </div>
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-calendar-times"></i></div>
                                        <span>Akhir Kontrak</span>
                                    </div>
                                    <div class="emp-tile-right">
                                        {{ $selectedEmployee->contract_end_date ? $selectedEmployee->contract_end_date->format('d M Y') : 'Karyawan Tetap' }}
                                    </div>
                                </div>
                                @if (auth()->user()->hasRole('admin'))
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-money-bill-wave"></i></div>
                                            <span>Gaji Pokok</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->formatted_basic_salary }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-hand-holding-usd"></i></div>
                                            <span>Total Kasbon</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->formatted_total_cash_receipt }}
                                            @if ($selectedEmployee->remaining_cash_advance > 0)
                                                <span class="text-danger small d-block font-weight-normal">(Sisa: {{ $selectedEmployee->formatted_remaining_cash_advance }})</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if (auth()->user()->hasRole('admin'))
                                <h2 class="section-title">Rekening Bank</h2>
                                <p class="section-lead">Informasi akun perbankan untuk pembayaran payroll.</p>
                                <div class="emp-tile-group mb-0">
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-university"></i></div>
                                            <span>Nama Bank</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->detail?->bank_name ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-credit-card"></i></div>
                                            <span>Nomor Rekening</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->detail?->bank_account_number ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-user-check"></i></div>
                                            <span>Atas Nama</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->detail?->bank_account_name ?: '-' }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Tab 2: Data Pribadi & Kontak + Informasi Pajak & BPJS -->
                        <div class="tab-pane fade" id="detail-pill-pribadi" role="tabpanel" aria-labelledby="detail-personal-tab">
                            <h2 class="section-title">Data Pribadi & Kontak</h2>
                            <p class="section-lead">Informasi biodata diri, kontak, dan alamat domisili.</p>
                            <div class="emp-tile-group">
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-venus-mars"></i></div>
                                        <span>Jenis Kelamin</span>
                                    </div>
                                    <div class="emp-tile-right">
                                        {{ $selectedEmployee->detail?->gender_label ?: '-' }}
                                    </div>
                                </div>
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-birthday-cake"></i></div>
                                        <span>Tempat & Tgl Lahir</span>
                                    </div>
                                    <div class="emp-tile-right">
                                        {{ $selectedEmployee->detail?->birth_place ?: '-' }}, {{ $selectedEmployee->detail?->birth_date ? $selectedEmployee->detail->birth_date->format('d M Y') : '-' }}
                                    </div>
                                </div>
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-phone"></i></div>
                                        <span>No. Telepon / WhatsApp</span>
                                    </div>
                                    <div class="emp-tile-right">
                                        {{ $selectedEmployee->detail?->phone ?: '-' }}
                                    </div>
                                </div>
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-envelope"></i></div>
                                        <span>Alamat Email</span>
                                    </div>
                                    <div class="emp-tile-right">
                                        {{ $selectedEmployee->detail?->email ?: '-' }}
                                    </div>
                                </div>
                                <div class="emp-tile-row">
                                    <div class="emp-tile-left">
                                        <div class="emp-tile-icon"><i class="fas fa-map-marker-alt"></i></div>
                                        <span>Alamat Domisili</span>
                                    </div>
                                    <div class="emp-tile-right">
                                        {{ $selectedEmployee->detail?->address ?: '-' }}
                                    </div>
                                </div>
                                @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur'))
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-file-pdf"></i></div>
                                            <span>Berkas CV</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            @if ($selectedEmployee->detail?->curriculum_vitae)
                                                <a href="{{ route('employees.documents.cv', $selectedEmployee) }}" target="_blank" class="btn btn-sm btn-outline-primary text-nowrap">
                                                    <i class="fas fa-download mr-1"></i> Unduh CV
                                                </a>
                                            @else
                                                <span class="text-muted font-italic">Belum diunggah</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur'))
                                <h2 class="section-title">Informasi Pajak & BPJS</h2>
                                <p class="section-lead">Status PTKP, metode PPh 21, dan nomor kepesertaan jaminan sosial.</p>
                                <div class="emp-tile-group mb-0">
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-users text-primary"></i></div>
                                            <span>Status PTKP</span>
                                        </div>
                                        <div class="emp-tile-right font-weight-bold text-dark">
                                            {{ $selectedEmployee->detail?->ptkp_status?->value ?: 'TK/0' }} &bull; TER {{ $selectedEmployee->detail?->ter_category ?: 'A' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-calculator text-primary"></i></div>
                                            <span>Metode PPh 21</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            <span class="{{ $selectedEmployee->detail?->tax_method?->badgeClass() ?: 'badge badge-primary' }}">
                                                {{ $selectedEmployee->detail?->tax_method?->shortLabel() ?: 'Gross' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-file-invoice-dollar text-primary"></i></div>
                                            <span>Jenis Pajak Pegawai</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->detail?->tax_recipient_type?->label() ?? 'Belum diklasifikasikan' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-id-card text-primary"></i></div>
                                            <span>NIK KTP</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->detail?->nik ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-file-invoice text-primary"></i></div>
                                            <span>Nomor NPWP</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->detail?->npwp_number ?: ($selectedEmployee->detail?->has_npwp ? 'Terdaftar' : 'Belum Ada NPWP') }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-heartbeat text-success"></i></div>
                                            <span>BPJS Kesehatan</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->detail?->bpjs_kesehatan_number ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="emp-tile-row">
                                        <div class="emp-tile-left">
                                            <div class="emp-tile-icon"><i class="fas fa-shield-alt text-info"></i></div>
                                            <span>BPJS Ketenagakerjaan</span>
                                        </div>
                                        <div class="emp-tile-right">
                                            {{ $selectedEmployee->detail?->bpjs_ketenagakerjaan_number ?: '-' }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data profil...
                    </div>
                @endif
            </div>

            <div class="modal-footer bg-whitesmoke br">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Detail Modal Nav-Pills: 50:50 di mobile, default Stisla di desktop */
    @media (max-width: 767.98px) {
        #employeeDetailPills {
            display: flex !important;
            flex-wrap: nowrap !important;
            width: 100% !important;
            gap: 8px;
        }
        #employeeDetailPills .nav-item {
            flex: 1 1 50% !important;
            min-width: 0 !important;
            margin: 0 !important;
        }
        #employeeDetailPills .nav-link {
            width: 100% !important;
            text-align: center !important;
            padding: 8px 6px !important;
            white-space: nowrap !important;
            font-size: 13px !important;
        }
    }
</style>
