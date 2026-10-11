<div>
    <x-slot name="header">
        <div class="section-header-back">
            <a href="{{ route('payrolls.show', $period) }}" class="btn btn-icon" title="Kembali ke Periode Penggajian">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
        <h1>Kelola Komponen Gaji</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('payrolls.index') }}">Penggajian</a></div>
            <div class="breadcrumb-item"><a href="{{ route('payrolls.show', $period) }}">{{ $period->period_label }}</a></div>
            <div class="breadcrumb-item">{{ $item->employee?->full_name ?? 'Karyawan' }}</div>
        </div>
    </x-slot>

    <!-- Template Native Section Title & Lead + Persetujuan Direktur -->
    <div>
        <div>
            <h2 class="section-title">Kelola Komponen Gaji: {{ $period->period_label }}</h2>
            <p class="section-lead">
                @if ($this->isEditable)
                    Atur rincian penghasilan (tunjangan) dan potongan gaji karyawan. Perubahan akan langsung memengaruhi total take home pay.
                @else
                    Rincian penghasilan dan potongan gaji karyawan (Mode Hanya Baca).
                @endif
            </p>
        </div>

        @if (auth()->user()?->hasRole('direktur'))
            <div class="d-flex align-items-center mt-3 mt-md-0 flex-shrink-0">
                @if ($item->isApproved())
                    <button type="button" 
                        onclick="openApprovalMenu(this)"
                        data-status="approved"
                        data-employee="{{ e($item->employee?->full_name ?? 'Karyawan') }}"
                        data-nik="{{ e($item->employee?->nik ?? '-') }}"
                        data-position="{{ e($item->employee?->position?->name ?? 'Tanpa Jabatan') }}"
                        data-department="{{ e($item->employee?->department?->name ?? 'Tanpa Departemen') }}"
                        data-net-salary="{{ e($item->formatted_net_salary) }}"
                        data-approver="{{ e($item->approver?->name ?? 'Direktur') }}"
                        data-approved-at="{{ e($item->approved_at?->format('d/m/Y H:i')) }}"
                        class="btn btn-icon icon-left btn-success shadow-sm btn-approval-action" 
                        title="Slip telah di-ACC. Klik untuk ubah status atau batalkan persetujuan.">
                        <i class="fas fa-check-circle"></i> Disetujui (ACC) <i class="fas fa-caret-down ml-1"></i>
                    </button>
                @elseif ($item->isRejected())
                    <button type="button" 
                        onclick="openApprovalMenu(this)"
                        data-status="rejected"
                        data-employee="{{ e($item->employee?->full_name ?? 'Karyawan') }}"
                        data-nik="{{ e($item->employee?->nik ?? '-') }}"
                        data-position="{{ e($item->employee?->position?->name ?? 'Tanpa Jabatan') }}"
                        data-department="{{ e($item->employee?->department?->name ?? 'Tanpa Departemen') }}"
                        data-net-salary="{{ e($item->formatted_net_salary) }}"
                        data-rejection-reason="{{ e($item->rejection_reason) }}"
                        class="btn btn-icon icon-left btn-danger shadow-sm btn-approval-action" 
                        title="Slip perlu revisi. Klik untuk kelola status persetujuan atau catatan revisi.">
                        <i class="fas fa-exclamation-triangle"></i> Perlu Revisi <i class="fas fa-caret-down ml-1"></i>
                    </button>
                @else
                    <button type="button" 
                        onclick="openApprovalMenu(this)"
                        data-status="pending"
                        data-employee="{{ e($item->employee?->full_name ?? 'Karyawan') }}"
                        data-nik="{{ e($item->employee?->nik ?? '-') }}"
                        data-position="{{ e($item->employee?->position?->name ?? 'Tanpa Jabatan') }}"
                        data-department="{{ e($item->employee?->department?->name ?? 'Tanpa Departemen') }}"
                        data-net-salary="{{ e($item->formatted_net_salary) }}"
                        class="btn btn-icon icon-left btn-primary shadow-sm btn-approval-action" 
                        title="Klik untuk memberikan persetujuan (ACC) atau tolak untuk revisi.">
                        <i class="fas fa-user-check"></i> Persetujuan Direktur <i class="fas fa-caret-down ml-1"></i>
                    </button>
                @endif
            </div>
        @endif
    </div>

    <!-- Status Banner: Catatan Revisi Direktur atau ACC -->
    <!-- Status Banner: Catatan Revisi Direktur atau ACC (Bootstrap bd-callout) -->
    @if ($item->isRejected())
        <div class="bd-callout bd-callout-danger mb-4" role="alert">
            <div class="d-flex align-items-start">
                <div class="bd-callout-icon mr-3 mt-0.5">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="bd-callout-title">
                        Catatan Revisi dari Direktur
                    </div>
                    <div class="bd-callout-desc">
                        Alasan: <strong class="text-dark">"{{ $item->rejection_reason }}"</strong> &bull; <span class="text-muted">Silakan sesuaikan rincian tunjangan / potongan karyawan, lalu simpan perubahan.</span>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($item->isApproved() || ($period->approved_by && $period->approver))
        @php
            $approverName = $item->approver?->name ?? $period->approver?->name ?? 'Direktur';
            $approvedTime = ($item->approved_at ?? $period->approved_at)?->locale('id')->translatedFormat('d F Y H:i');
        @endphp
        <div class="bd-callout bd-callout-success mb-4" role="alert">
            <div class="d-flex align-items-start">
                <div class="bd-callout-icon mr-3 mt-0.5">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="bd-callout-title">
                        Slip Gaji Telah Disetujui (ACC: {{ $approverName }})
                    </div>
                    <div class="bd-callout-desc">
                        Disetujui oleh <strong class="text-dark">{{ $approverName }}</strong> pada <strong class="text-dark">{{ $approvedTime }}</strong>. Komponen gaji berada dalam mode hanya baca (read-only) dan tidak dapat diubah kembali.
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (! $this->isEditable && ! $item->isApproved() && ! $item->isRejected())
        <div class="bd-callout bd-callout-warning mb-4" role="alert">
            <div class="d-flex align-items-start">
                <div class="bd-callout-icon mr-3 mt-0.5">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="bd-callout-title">
                        Mode Hanya Baca
                    </div>
                    <div class="bd-callout-desc">
                        Periode penggajian ini berstatus <strong class="text-dark">{{ $period->status->label() }}</strong> sehingga komponen gaji tidak dapat diubah kembali.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Layout: 1 Row (Card 1: col-lg-3, Card 2: col-lg-9) -->
    <div class="row align-items-start">
        <!-- ================= CARD 1: INFORMASI KARYAWAN & RINGKASAN SLIP GAJI (COL-3) ================= -->
        <div class="col-12 col-lg-3 mb-4">
            <div class="card mb-4 shadow-sm emp-summary-card">
                <div class="card-body p-3 text-center">
                    <!-- Avatar Circle -->
                    <div class="d-flex justify-content-center mb-2">
                        @if ($item->employee?->photo_path)
                            <img src="{{ $item->employee->photo_url }}" alt="{{ $item->employee->full_name }}" class="emp-profile-avatar-center" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;">
                        @else
                            <div class="d-flex align-items-center justify-content-center rounded-circle text-white font-weight-bold mx-auto shadow-sm" style="width: 64px; height: 64px; background: linear-gradient(135deg, #0b52aa 0%, #2563eb 100%); font-size: 20px; letter-spacing: 0.5px;">
                                {{ strtoupper(substr($item->employee?->full_name ?? 'NA', 0, 2)) }}
                            </div>
                        @endif
                    </div>

                    <!-- Name & Role -->
                    <div class="font-weight-bold text-dark text-center" style="font-size: 16px;" title="{{ $item->employee?->full_name ?? 'Karyawan' }}">
                        {{ $item->employee?->full_name ?? 'Karyawan' }}
                    </div>
                    <div class="text-center text-muted mb-2" style="font-size: 12.5px;">
                        {{ $item->employee?->position?->name ?? 'Tanpa Jabatan' }} &bull; {{ $item->employee?->department?->name ?? 'Tanpa Departemen' }}
                    </div>

                    <!-- Status Badges -->
                    <div class="d-flex align-items-center justify-content-center flex-wrap mb-3" style="gap: 6px;">
                        <span class="badge px-2.5 py-1" style="background-color: #e8f0fe; color: #0b52aa; border: 1px solid #d0e1fd; font-size: 11px; font-weight: 500;">
                            {{ $item->employee?->employment_status_label ?? '-' }}
                        </span>
                        <span class="badge px-2.5 py-1" style="background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-size: 11px; font-weight: 500;">
                            <i class="fas fa-id-badge mr-1 text-success"></i>{{ $item->employee?->detail?->employee_code ?? 'EMP' }}
                        </span>
                    </div>

                    <!-- 1. Quick Switcher Karyawan -->
                    <div class="form-group mb-3 text-left">
                        <label for="select_employee">Pilih Karyawan</label>
                        <div wire:ignore>
                            <select id="select_employee" class="form-control select2" style="width: 100%;" onchange="if (this.value && this.value != '{{ $item->id }}') { window.location.href = '/payrolls/{{ $period->id }}/items/' + this.value + '/edit'; }">
                                @foreach ($this->otherItems as $other)
                                    <option value="{{ $other->id }}" @selected($other->id === $item->id)>
                                        {{ $other->employee?->full_name ?? 'Karyawan' }} ({{ $other->employee?->detail?->employee_code ?? 'EMP' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 2. Transfer & Slip Tile (Clean Flutter-Style Tile matching Kasbon) -->
                    <div class="emp-tile-group mb-3 text-left">
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-university text-primary" style="color: #0b52aa !important;"></i></div>
                                <span>Rekening Bank</span>
                            </div>
                            <div class="emp-tile-right font-weight-600 text-dark">
                                {{ $item->bank_name ?? '-' }} {{ $item->bank_account_number ?? '-' }}
                            </div>
                        </div>
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-receipt text-primary" style="color: #0b52aa !important;"></i></div>
                                <span>No. Slip Gaji</span>
                            </div>
                            <div class="emp-tile-right font-weight-600 text-primary" style="color: #0b52aa !important;">
                                {{ $item->payslip_number }}
                            </div>
                        </div>
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-check-circle text-success"></i></div>
                                <span>Status Bayar</span>
                            </div>
                            <div class="emp-tile-right">
                                <span class="{{ $item->payment_status_badge_class }}" style="font-size: 11px; font-weight: 500;">
                                    {{ $item->payment_status_label }}
                                </span>
                            </div>
                        </div>

                        <!-- NPWP -->
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-id-card text-primary" style="color: #0b52aa !important;"></i></div>
                                <span>NPWP</span>
                            </div>
                            <div class="emp-tile-right font-weight-600 text-dark">
                                @php
                                    $detail = $item->employee?->detail;
                                    $hasNpwp = ! empty($detail?->npwp_number);
                                    $hasNik = ! empty($detail?->nik);
                                    $isNonNpwpPenalized = ! $hasNpwp && ! $hasNik;
                                @endphp
                                @if ($hasNpwp)
                                    {{ $detail->npwp_number }}
                                @elseif ($hasNik)
                                    <span class="badge badge-light border text-dark" style="font-weight: 500; font-size: 11px;" title="Bebas penalti 20% karena menggunakan NIK valid (PENG-6/PJ.09/2024)">Non-NPWP (NIK Valid)</span>
                                @else
                                    <span class="badge badge-warning border text-warning" style="background-color: #fffbeb; font-weight: 600; font-size: 11px;" title="Dikenakan tarif +20% karena tidak memiliki NPWP dan NIK valid (UU PPh Pasal 21 ayat 5a)">Non-NPWP (+20%)</span>
                                @endif
                            </div>
                        </div>

                        <!-- Status PTKP -->
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-users text-primary" style="color: #0b52aa !important;"></i></div>
                                <span>Status PTKP</span>
                            </div>
                            @php
                                $ptkp = ($this->isEditable ? $item->employee?->detail?->ptkp_status : null) ?? $item->ptkp_status ?? $item->employee?->detail?->ptkp_status;
                                if (! ($ptkp instanceof \App\Enums\PtkpStatus) && is_string($ptkp)) {
                                    $ptkp = \App\Enums\PtkpStatus::tryFrom($ptkp);
                                }
                                $ptkpVal = $ptkp instanceof \App\Enums\PtkpStatus ? $ptkp->value : (string) ($ptkp ?? 'TK/0');
                                $ptkpDesc = $ptkp instanceof \App\Enums\PtkpStatus ? $ptkp->description() : 'Tidak Kawin, 0 Tanggungan';
                            @endphp
                            <div class="emp-tile-right font-weight-600 text-dark">
                                {{ $ptkpVal }}
                                <span class="text-muted text-xs font-weight-normal">({{ $ptkpDesc }})</span>
                            </div>
                        </div>

                        <!-- Skema Tarif -->
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-percentage text-primary" style="color: #0b52aa !important;"></i></div>
                                <span>Skema Tarif</span>
                            </div>
                            <div class="emp-tile-right font-weight-600 text-dark text-right">
                                @php
                                    $taxMethod = ($this->isEditable ? $item->employee?->detail?->tax_method : null) ?? $item->tax_method ?? $item->employee?->detail?->tax_method ?? \App\Enums\TaxMethod::GROSS;
                                    $terCat = ($this->isEditable ? ($item->employee?->detail?->ter_category ?? $item->ter_category) : null) ?? $item->ter_category ?? ($ptkp instanceof \App\Enums\PtkpStatus ? $ptkp->terCategory() : ($item->employee?->detail?->ter_category ?? 'A'));
                                    $terRate = (float) ($item->ter_rate ?? 0);
                                    if ($terRate <= 0 && $this->grossSalary > 0) {
                                        $taxService = app(\App\Services\TaxCalculationService::class);
                                        $baseTer = $taxService->getTerRate($terCat, $this->grossSalary);
                                        $terRate = $isNonNpwpPenalized ? round($baseTer * 1.2, 6) : $baseTer;
                                    }
                                @endphp
                                @if ($item->payrollPeriod->month == 12)
                                    <span class="text-info font-weight-bold">Pasal 17 UU HPP</span>
                                    @if ($isNonNpwpPenalized)
                                        <span class="badge badge-warning ml-1 text-xs" style="font-size: 10px;" title="Termasuk penalti tarif 20% Non-NPWP">+20%</span>
                                    @endif
                                    <button type="button"
                                            wire:click="showCalculationDetail('ter_scheme')"
                                            class="btn btn-sm btn-light border text-primary ml-1 px-1.5 py-0.5"
                                            style="font-size: 10.5px; line-height: 1.2; border-radius: 6px; background-color: #f8fafc;"
                                            title="Lihat Mengapa Terkena Skema Tarif Ini">
                                        <i class="fas fa-info-circle mr-1"></i>Rincian
                                    </button>
                                @else
                                    <span>TER {{ $terCat }}</span>
                                    @if ($terRate > 0)
                                        <span class="text-primary font-weight-bold ml-1" style="color: #0b52aa !important;">({{ number_format($terRate * 100, 2) }}%)</span>
                                    @endif
                                    @if ($isNonNpwpPenalized)
                                        <span class="badge badge-warning ml-1 text-xs" style="font-size: 10px;" title="Termasuk penalti tarif 20% Non-NPWP">+20%</span>
                                    @endif
                                    <button type="button"
                                            wire:click="showCalculationDetail('ter_scheme')"
                                            class="btn btn-sm btn-light border text-primary ml-1 px-1.5 py-0.5"
                                            style="font-size: 10.5px; line-height: 1.2; border-radius: 6px; background-color: #f8fafc;"
                                            title="Lihat Mengapa Terkena Tarif Ini (Dasar PMK 168)">
                                        <i class="fas fa-info-circle mr-1"></i>Rincian
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Riwayat Data (Pembuat & Terakhir Diupdate) -->
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-history text-primary" style="color: #0b52aa !important;"></i></div>
                                <span>Riwayat Data</span>
                            </div>
                            <div class="emp-tile-right text-dark text-right font-weight-600" style="font-size: 12.5px;">
                                @php
                                    $creator = $period->creator ?? $period->submitter;
                                    $creatorName = $creator?->name ?? 'Admin';
                                    $updater = $item->updater ?? $period->updater;
                                    $updaterName = $updater?->name;
                                    $isDifferentUpdater = $updaterName && ($updater->id !== ($creator?->id ?? null));
                                @endphp
                                @if ($isDifferentUpdater)
                                    <span>{{ $updaterName }}</span>
                                    <span class="badge badge-light border text-info text-xs ml-1" style="font-size: 9.5px; font-weight: 500;" title="Dibuat oleh {{ $creatorName }}">Editor Terakhir</span>
                                    <span class="text-muted text-xs font-weight-normal ml-1">({{ $creatorName }})</span>
                                @else
                                    <span>{{ $creatorName }}</span>
                                    <span class="badge badge-light border text-muted text-xs ml-1" style="font-size: 9.5px; font-weight: 500;">Pembuat</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 3. Take Home Pay Card (FinTech Wallet Card matching Kasbon tile group) -->
                    <div class="emp-tile-group mb-3 text-left">
                        <div class="p-3" style="background-color: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    Ringkasan Slip Gaji
                                </div>
                                <span class="badge badge-light border text-primary" style="font-size: 10.5px; font-weight: 500;">Take Home Pay</span>
                            </div>
                            <div class="font-weight-bold mb-0 text-primary" style="font-size: 22px; color: #0b52aa !important;">
                                Rp {{ number_format($this->estimatedNetSalary, 0, ',', '.') }}
                            </div>
                        </div>

                        <!-- Gaji Pokok -->
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-wallet text-primary" style="color: #0b52aa !important;"></i></div>
                                <span>Gaji Pokok</span>
                            </div>
                            <div class="emp-tile-right font-weight-600 text-dark">
                                {{ $item->formatted_basic_salary }}
                            </div>
                        </div>

                        <!-- Total Tunjangan -->
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-plus-circle text-success"></i></div>
                                <span>Total Tunjangan</span>
                            </div>
                            <div class="emp-tile-right font-weight-600 text-success">
                                +Rp {{ number_format($this->totalAllowances, 0, ',', '.') }}
                            </div>
                        </div>

                        <!-- Gaji Kotor (Bruto) -->
                        <div class="emp-tile-row" style="background-color: #f8fafc; border-top: 1px dashed #e2e8f0; border-bottom: 1px dashed #e2e8f0;">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-coins" style="color: #0b52aa !important;"></i></div>
                                <span class="font-weight-600 text-dark">Gaji Kotor (Bruto)</span>
                            </div>
                            <div class="emp-tile-right font-weight-700 text-dark" style="font-size: 13.5px;">
                                Rp {{ number_format($this->grossSalary, 0, ',', '.') }}
                            </div>
                        </div>

                        <!-- Total Potongan -->
                        <div class="emp-tile-row">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-minus-circle text-danger"></i></div>
                                <span>Total Potongan</span>
                            </div>
                            <div class="emp-tile-right font-weight-600 text-danger">
                                -Rp {{ number_format($this->totalDeductions, 0, ',', '.') }}
                            </div>
                        </div>

                        <!-- Tanggungan Perusahaan -->
                        <div class="emp-tile-row" style="border-top: 1px solid #f1f5f9;">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-building text-info"></i></div>
                                <span>Tanggungan Perusahaan</span>
                            </div>
                            <div class="emp-tile-right font-weight-600" style="color: #0b52aa !important;">
                                Rp {{ number_format($this->totalCompanyContributions, 0, ',', '.') }}
                            </div>
                        </div>

                        <!-- Total Biaya Perusahaan (Cost to Company / CTC) -->
                        <div class="emp-tile-row" style="background-color: #f8fafc;">
                            <div class="emp-tile-left">
                                <div class="emp-tile-icon"><i class="fas fa-calculator text-secondary"></i></div>
                                <span class="font-weight-600 text-dark">Total Biaya Perusahaan</span>
                            </div>
                            <div class="emp-tile-right font-weight-700 text-dark" style="font-size: 13.5px;">
                                Rp {{ number_format($this->costToCompany, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    @if ($this->isEditable && (auth()->user()?->hasRole(['admin', 'direktur']) || auth()->user()?->can('update', $period)))
                        <div class="mt-3">
                            <button type="button" 
                                    wire:click="save" 
                                    wire:loading.attr="disabled" 
                                    class="btn btn-primary btn-block py-2.5 font-weight-bold shadow-sm" 
                                    style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px;">
                                <span wire:loading.remove wire:target="save">
                                    <i class="fas fa-save mr-1.5"></i> Simpan Komponen Gaji
                                </span>
                                <span wire:loading wire:target="save">
                                    <i class="fas fa-spinner fa-spin mr-1.5"></i> Menyimpan...
                                </span>
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        <!-- ================= CARD 2: PENGHASILAN & POTONGAN (COL-9) ================= -->
        <div class="col-12 col-lg-9 mb-4">
            <!-- ================= CARD 2A: PENGHASILAN (EARNINGS) ================= -->
            <div class="card card-payroll-table mb-4 shadow-sm" style="border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff;">
                <div class="card-header" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h4>Penghasilan</h4>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-clean mb-0">
                            <thead style="background-color: #f8fafc; border-bottom: 1px solid #edf2f7;">
                                <tr>
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 45px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">NO</th>
                                    <th class="text-nowrap !px-3 !py-2.5" style="font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">NAMA TUNJANGAN</th>
                                    <th class="text-right text-nowrap !px-3 !py-2.5" style="width: 340px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">NOMINAL (RP) / %</th>
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 60px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;"><i class="fas fa-cog" style="color: #64748b;" title="Aksi"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Group Header: Pendapatan Tetap -->
                                <tr class="payroll-group-header-row">
                                    <td colspan="4" class="!px-3 !py-1.5" style="background-color: #f8fafc; border-bottom: 1px solid #edf2f7;">
                                        <span class="payroll-group-header-title text-uppercase" style="font-size: 12px; font-weight: 600; color: #334155; letter-spacing: 0.4px;">
                                            <i class="fas fa-layer-group text-primary mr-1"></i> Pendapatan Tetap
                                        </span>
                                    </td>
                                </tr>

                                <!-- Baris Gaji Pokok (Nomor 1) -->
                                @php
                                    $empFullSalary = (float) ($item->employee?->basic_salary ?? 0);
                                    $isEmpProrated = $empFullSalary > 0 && (float) $item->basic_salary < $empFullSalary;
                                    $prorateNoteText = $item->notes;
                                    if ($isEmpProrated) {
                                        $fmtBasic = 'Rp ' . number_format((float) $item->basic_salary, 0, ',', '.');
                                        $fmtFull = 'Rp ' . number_format($empFullSalary, 0, ',', '.');
                                        if (empty($prorateNoteText)) {
                                            $prorateNoteText = "Gaji prorata {$fmtBasic} dari {$fmtFull}";
                                        } elseif (! str_contains($prorateNoteText, 'dari Rp')) {
                                            $prorateNoteText = preg_replace('/^Gaji prorata\s*/i', "Gaji prorata {$fmtBasic} dari {$fmtFull} &bull; ", $prorateNoteText);
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td class="text-center text-muted align-middle !px-3 !py-2.5" style="font-size: 12.5px;">1</td>
                                    <td class="align-middle !px-3 !py-2.5">
                                        <div class="text-dark" style="font-weight: 500; font-size: 13px;">Gaji Pokok Karyawan</div>
                                        <div class="text-muted text-xs">{!! $prorateNoteText ?: 'Komponen dasar dari data master karyawan (otomatis tersinkron)' !!}</div>
                                    </td>
                                    <td class="text-right align-middle text-dark !px-3 !py-2.5" style="font-weight: 600; font-size: 13px;">
                                        {{ $item->formatted_basic_salary }}
                                    </td>
                                    <td class="text-center align-middle text-muted !px-3 !py-2.5">-</td>
                                </tr>

                                @php $allowanceCounter = 1; @endphp
                                @if (($item->tax_method === \App\Enums\TaxMethod::GROSS_UP || $item->employee?->detail?->tax_method === \App\Enums\TaxMethod::GROSS_UP) && (float) ($item->tax_allowance ?? 0) > 0)
                                    @php $allowanceCounter++; @endphp
                                    <tr>
                                        <td class="text-center text-muted align-middle !px-3 !py-2.5" style="font-size: 12.5px;">{{ $allowanceCounter }}</td>
                                        <td class="align-middle !px-3 !py-2.5">
                                            <div class="text-dark d-flex align-items-center" style="font-weight: 500; font-size: 13px;">
                                                Tunjangan PPh 21 (Gross-Up)
                                                <span class="badge badge-light border text-primary ml-2 text-xs" style="font-weight: 500;">Gross-Up Otomatis</span>
                                            </div>
                                        </td>
                                        <td class="text-right align-middle text-success !px-3 !py-2.5" style="font-weight: 600; font-size: 13px;">
                                            +Rp {{ number_format((float) $item->tax_allowance, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center align-middle text-muted !px-3 !py-2.5">-</td>
                                    </tr>
                                @endif

                                <!-- Group Header: Tunjangan & Pendapatan Tidak Tetap -->
                                <tr class="payroll-group-header-row">
                                    <td colspan="4" class="!px-3 !py-1.5" style="background-color: #f8fafc; border-top: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7;">
                                        <span class="payroll-group-header-title text-uppercase" style="font-size: 12px; font-weight: 600; color: #334155; letter-spacing: 0.4px;">
                                            <i class="fas fa-plus-circle text-success mr-1"></i> Tunjangan & Pendapatan Tidak Tetap
                                        </span>
                                    </td>
                                </tr>

                                @foreach ($allowanceRows as $index => $row)
                                    @if (($row['category'] ?? '') !== 'tax' && strcasecmp(trim($row['name'] ?? ''), 'tunjangan pph 21 (gross-up)') !== 0)
                                        @if (! $this->isEditable && empty($row['name']) && empty($row['amount']))
                                            @continue
                                        @endif
                                        @php $allowanceCounter++; @endphp
                                        <tr wire:key="allowance-row-{{ $index }}">
                                            <td class="text-center text-muted align-middle !px-3 !py-2.5" style="font-size: 12.5px;">{{ $allowanceCounter }}</td>
                                            <td class="align-middle !px-3 !py-2.5">
                                                @if ($this->isEditable)
                                                    <div class="position-relative select2-like-wrapper"
                                                         x-data="{
                                                             open: false,
                                                             search: '',
                                                             highlightedIndex: -1,
                                                             items: {{ Js::from($this->masterAllowances->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code])->values()) }},
                                                             init() {
                                                                 this.search = this.$refs.input ? this.$refs.input.value : '';
                                                             },
                                                             get filteredItems() {
                                                                 const q = (this.search || '').toLowerCase().trim();
                                                                 if (!q) return this.items;
                                                                 return this.items.filter(item => 
                                                                     item.name.toLowerCase().includes(q) || 
                                                                     (item.code && item.code.toLowerCase().includes(q))
                                                                 );
                                                             },
                                                             select(item) {
                                                                 this.search = item.name;
                                                                 if (this.$refs.input) {
                                                                     this.$refs.input.value = item.name;
                                                                     this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
                                                                 }
                                                                 $wire.set('allowanceRows.{{ $index }}.name', item.name);
                                                                 this.open = false;
                                                                 this.highlightedIndex = -1;
                                                             },
                                                             onKeyDownDown() {
                                                                 if (!this.open) { this.open = true; this.highlightedIndex = 0; return; }
                                                                 if (this.highlightedIndex < this.filteredItems.length - 1) {
                                                                     this.highlightedIndex++;
                                                                     this.scrollToHighlighted();
                                                                 }
                                                             },
                                                             onKeyDownUp() {
                                                                 if (!this.open) return;
                                                                 if (this.highlightedIndex > 0) {
                                                                     this.highlightedIndex--;
                                                                     this.scrollToHighlighted();
                                                                 }
                                                             },
                                                             onEnter(e) {
                                                                 if (this.open && this.filteredItems.length > 0) {
                                                                     if (this.highlightedIndex >= 0 && this.filteredItems[this.highlightedIndex]) {
                                                                         e.preventDefault();
                                                                         this.select(this.filteredItems[this.highlightedIndex]);
                                                                     } else if (this.filteredItems.length === 1) {
                                                                         e.preventDefault();
                                                                         this.select(this.filteredItems[0]);
                                                                     }
                                                                 }
                                                             },
                                                             scrollToHighlighted() {
                                                                 this.$nextTick(() => {
                                                                     const list = this.$refs.list;
                                                                     if (list && list.children[this.highlightedIndex]) {
                                                                         list.children[this.highlightedIndex].scrollIntoView({ block: 'nearest' });
                                                                     }
                                                                 });
                                                             }
                                                         }"
                                                         @click.outside="open = false"
                                                    >
                                                        <div class="position-relative">
                                                            <input type="text"
                                                                   x-ref="input"
                                                                   wire:model.live.debounce.400ms="allowanceRows.{{ $index }}.name"
                                                                   @focus="search = $event.target.value; open = true; highlightedIndex = -1"
                                                                   @click="open = true"
                                                                   @input="search = $event.target.value; open = true; highlightedIndex = -1"
                                                                   @keydown.down.prevent="onKeyDownDown()"
                                                                   @keydown.up.prevent="onKeyDownUp()"
                                                                   @keydown.enter="onEnter($event)"
                                                                   @keydown.escape="open = false"
                                                                   class="form-control payroll-table-input pr-4"
                                                                   placeholder="Ketik nama tunjangan atau pilih master..."
                                                                   autocomplete="off">
                                                            <span class="position-absolute text-muted"
                                                                  @click.stop="open = !open; if (open) { search = ''; $refs.input.focus(); }"
                                                                  style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 11px;">
                                                                <i class="fas" :class="open ? 'fa-chevron-up text-primary' : 'fa-chevron-down'"></i>
                                                            </span>
                                                        </div>

                                                        <div x-show="open && filteredItems.length > 0"
                                                             x-transition:enter="transition ease-out duration-100"
                                                             x-transition:enter-start="opacity-0 transform -translate-y-1"
                                                             x-transition:enter-end="opacity-100 transform translate-y-0"
                                                             x-cloak
                                                             class="select2-like-dropdown position-absolute bg-white"
                                                             style="top: 100%; left: 0; right: 0; z-index: 1050; margin-top: 2px; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); overflow: hidden;"
                                                        >
                                                            <ul x-ref="list" class="list-unstyled mb-0 py-1" style="max-height: 200px; overflow-y: auto;">
                                                                <template x-for="(item, idx) in filteredItems" :key="item.id">
                                                                    <li @mouseenter="highlightedIndex = idx"
                                                                        @mousedown.prevent="select(item)"
                                                                        class="px-3 py-2 d-flex align-items-center justify-content-between"
                                                                        style="font-size: 13px; font-weight: 500; cursor: pointer; transition: background-color 0.1s ease;"
                                                                        :style="highlightedIndex === idx ? 'background-color: #0b52aa !important; color: #ffffff !important;' : 'background-color: #ffffff; color: #1e293b;'"
                                                                    >
                                                                        <span x-text="item.name"></span>
                                                                        <span x-show="item.code"
                                                                              x-text="'(' + item.code + ')'"
                                                                              style="font-size: 11.5px; font-weight: 400; margin-left: 8px;"
                                                                              :style="highlightedIndex === idx ? 'color: rgba(255,255,255,0.75) !important;' : 'color: #64748b !important;'"
                                                                        ></span>
                                                                    </li>
                                                                </template>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-dark font-weight-500" style="font-size: 13px;">{{ $row['name'] }}</div>
                                                @endif
                                            </td>
                                            <td class="align-middle !px-3 !py-2.5">
                                                @if ($this->isEditable)
                                                    <div class="d-flex align-items-center">
                                                        @if (($row['calc_mode'] ?? 'nominal') === 'percentage')
                                                            <div class="d-flex align-items-center flex-grow-1 mr-2">
                                                                 <div class="input-group payroll-input-group mr-1.5" style="width: 110px; flex-shrink: 0;" title="Persentase dari Gaji Pokok">
                                                                    <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="allowanceRows.{{ $index }}.percentage" class="form-control text-right payroll-input payroll-pct-input" placeholder="0" autocomplete="off">
                                                                    <div class="input-group-append">
                                                                        <span class="input-group-text payroll-addon">%</span>
                                                                    </div>
                                                                </div>
                                                                <div class="input-group payroll-input-group flex-grow-1" title="Nominal Dihitung Otomatis">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text payroll-addon">Rp</span>
                                                                    </div>
                                                                    <input type="text" value="{{ !empty($row['amount']) ? number_format((float) $row['amount'], 0, ',', '.') : '0' }}" class="form-control text-right bg-light payroll-input text-muted" placeholder="0" readonly>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="input-group payroll-input-group flex-grow-1 mr-2">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text payroll-addon">Rp</span>
                                                                </div>
                                                                <input type="text"
                                                                       inputmode="numeric"
                                                                       x-on:input="
                                                                           let raw = $el.value.replace(/\D/g, '');
                                                                           $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                                                       "
                                                                       x-on:input.debounce.350ms="$wire.set('allowanceRows.{{ $index }}.amount', $el.value.replace(/\D/g, '') ? parseFloat($el.value.replace(/\D/g, '')) : '')"
                                                                       x-on:blur="$wire.set('allowanceRows.{{ $index }}.amount', $el.value.replace(/\D/g, '') ? parseFloat($el.value.replace(/\D/g, '')) : '')"
                                                                       value="{{ !empty($row['amount']) ? number_format((float) $row['amount'], 0, ',', '.') : '' }}"
                                                                       class="form-control text-right payroll-input"
                                                                       placeholder="0"
                                                                       autocomplete="off">
                                                            </div>
                                                        @endif
                                                        <label class="custom-switch payroll-calc-toggle" title="Beralih mode nominal atau persentase (% Gaji Pokok)">
                                                            <input type="checkbox" wire:click="toggleCalcMode('allowance', {{ $index }})" @checked(($row['calc_mode'] ?? 'nominal') === 'percentage') class="custom-switch-input">
                                                            <span class="custom-switch-indicator"></span>
                                                            <span class="custom-switch-description text-xs font-weight-600 {{ ($row['calc_mode'] ?? 'nominal') === 'percentage' ? 'text-primary' : 'text-muted' }} ml-1.5">%</span>
                                                        </label>
                                                    </div>
                                                @else
                                                    <div class="text-right text-dark font-weight-600" style="font-size: 13px;">
                                                        Rp {{ number_format((float) ($row['amount'] ?? 0), 0, ',', '.') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                                @if ($this->isEditable && (!empty($row['name']) || !empty($row['amount']) || count($allowanceRows) > 1))
                                                    <button type="button" wire:click="removeAllowanceRow({{ $index }})" class="btn btn-danger btn-action" title="Hapus Baris"><i class="fas fa-trash"></i></button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end align-items-center">
                    <div style="color: #475569; font-size: 12.5px; font-weight: 400;">
                        Total Tunjangan: <span class="text-dark ml-1" style="font-weight: 600; font-size: 13.5px;">+Rp {{ number_format($this->totalAllowances, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- ================= CARD 2B: POTONGAN (DEDUCTIONS) ================= -->
            <div class="card card-payroll-table mb-4 shadow-sm" style="border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff;">
                <div class="card-header" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h4>Potongan</h4>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-clean mb-0">
                            <thead style="background-color: #f8fafc; border-bottom: 1px solid #edf2f7;">
                                <tr>
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 45px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">NO</th>
                                    <th class="text-nowrap !px-3 !py-2.5" style="font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">NAMA POTONGAN</th>
                                    <th class="text-right text-nowrap !px-3 !py-2.5" style="width: 340px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">NOMINAL (RP) / %</th>
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 60px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;"><i class="fas fa-calculator" style="color: #64748b;" title="Rincian Perhitungan"></i></th>
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 70px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">
                                        @if ($this->isEditable && $this->hasTaxOrBpjsRows)
                                            <label class="custom-switch payroll-status-toggle m-0"
                                                   wire:key="header-tax-bpjs-toggle-{{ $this->hasAnyTaxOrBpjsEnabled ? 'on' : 'off' }}"
                                                   title="{{ $this->hasAnyTaxOrBpjsEnabled ? 'Klik untuk mematikan semua PPh 21 & BPJS' : 'Klik untuk mengaktifkan semua PPh 21 & BPJS' }}">
                                                <input type="checkbox"
                                                       wire:click="toggleAllTaxAndBpjs"
                                                       @checked($this->hasAnyTaxOrBpjsEnabled)
                                                       class="custom-switch-input">
                                                <span class="custom-switch-indicator"></span>
                                            </label>
                                        @else
                                            <i class="fas fa-cog" style="color: #64748b;" title="Aksi"></i>
                                        @endif
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $deductionCounter = 0; @endphp

                                <!-- Group Header: Potongan Tetap -->
                                <tr class="payroll-group-header-row">
                                    <td colspan="5" class="!px-3 !py-1.5" style="background-color: #f8fafc; border-bottom: 1px solid #edf2f7;">
                                        <span class="payroll-group-header-title text-uppercase" style="font-size: 12px; font-weight: 600; color: #334155; letter-spacing: 0.4px;">
                                            <i class="fas fa-lock text-danger mr-1"></i> Potongan Tetap
                                        </span>
                                    </td>
                                </tr>

                                <!-- Potongan Kasbon Otomatis -->
                                @foreach ($deductionRows as $index => $row)
                                    @if (! empty($row['cash_advance_installment_id']))
                                        @php
                                            $deductionCounter++;
                                            $rowAmt = (float) ($row['amount'] ?? 0);
                                            $paidAmt = (float) ($row['installment_paid_amount'] ?? 0);
                                        @endphp
                                        <tr wire:key="deduction-row-kasbon-{{ $index }}">
                                            <td class="text-center text-muted align-middle !px-3 !py-2.5" style="font-size: 12.5px;">{{ $deductionCounter }}</td>
                                            <td class="align-middle !px-3 !py-2.5">
                                                <div class="text-dark d-flex align-items-center flex-wrap" style="font-weight: 500; font-size: 13px; gap: 4px;">
                                                    <span>{{ $row['name'] }}</span>
                                                    @if ($rowAmt <= 0)
                                                        <span class="badge badge-success text-xs" style="font-weight: 500;">
                                                            <i class="fas fa-check-circle mr-1"></i>Sudah Lunas Lebih Awal
                                                        </span>
                                                    @elseif ($paidAmt > 0)
                                                        <span class="badge badge-info text-xs" style="font-weight: 500;">
                                                            <i class="fas fa-info-circle mr-1"></i>Sudah Bayar Awal Rp {{ number_format($paidAmt, 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-right align-middle {{ $rowAmt > 0 ? 'text-danger' : 'text-muted' }} !px-3 !py-2.5" style="font-weight: 600; font-size: 13px;">
                                                -Rp {{ number_format($rowAmt, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center align-middle text-muted !px-3 !py-2.5">-</td>
                                            <td class="text-center align-middle text-muted !px-3 !py-2.5">-</td>
                                        </tr>
                                    @endif
                                @endforeach

                                <!-- Potongan PPh 21 Otomatis -->
                                @foreach ($deductionRows as $index => $row)
                                    @if ((($row['category'] ?? '') === 'tax' || strcasecmp(trim($row['name'] ?? ''), 'pph 21') === 0) && empty($row['cash_advance_installment_id']))
                                        @php
                                            $deductionCounter++;
                                            $isEnabled = $row['is_enabled'] ?? true;
                                        @endphp
                                        <tr wire:key="deduction-row-tax-{{ $index }}" class="{{ ! $isEnabled ? 'payroll-row-disabled' : '' }}">
                                            <td class="text-center {{ $isEnabled ? 'text-muted' : 'payroll-text-disabled' }} align-middle !px-3 !py-2.5" style="font-size: 12.5px;">{{ $deductionCounter }}</td>
                                            <td class="align-middle !px-3 !py-2.5">
                                                <div class="{{ $isEnabled ? 'text-dark' : 'payroll-text-disabled' }} d-flex align-items-center" style="font-weight: 500; font-size: 13px;">
                                                    {{ $row['name'] }}
                                                    @if ($isEnabled)
                                                        <span class="badge badge-light border text-danger ml-2 text-xs" style="font-weight: 500;">PPh 21 Otomatis</span>
                                                    @else
                                                        <span class="badge badge-light border text-muted ml-2 text-xs" style="font-weight: 500;">Nonaktif</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-right align-middle !px-3 !py-2.5 {{ $isEnabled ? 'text-danger' : 'payroll-amount-disabled' }}" style="font-weight: 600; font-size: 13px;">
                                                -Rp {{ number_format((float) ($row['amount'] ?? 0), 0, ',', '.') }}
                                            </td>
                                            <td class="text-center align-middle !px-3 !py-2.5">
                                                <button type="button"
                                                        wire:click="showCalculationDetail('pph21')"
                                                        class="btn btn-info btn-action"
                                                        title="Lihat Rincian Perhitungan PPh 21">
                                                    <i class="fas fa-calculator"></i>
                                                </button>
                                            </td>
                                            <td class="text-center align-middle !px-3 !py-2.5">
                                                @if ($this->isEditable)
                                                    <label class="custom-switch payroll-status-toggle m-0"
                                                           wire:key="deduction-tax-toggle-{{ $index }}-{{ $isEnabled ? '1' : '0' }}"
                                                           title="{{ $isEnabled ? 'Aktif' : 'Nonaktif' }}">
                                                        <input type="checkbox"
                                                               wire:click="toggleDeductionEnabled({{ $index }})"
                                                               @checked($isEnabled)
                                                               class="custom-switch-input">
                                                        <span class="custom-switch-indicator"></span>
                                                    </label>
                                                @else
                                                    @if ($isEnabled)
                                                        <span class="badge badge-light border text-success text-xs font-weight-600">Aktif</span>
                                                    @else
                                                        <span class="badge badge-light border text-muted text-xs">Nonaktif</span>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach

                                <!-- Potongan BPJS Karyawan Otomatis -->
                                @foreach ($deductionRows as $index => $row)
                                    @if ((($row['category'] ?? '') === 'bpjs' || (!empty($row['is_system']) && str_starts_with(strtoupper($row['name'] ?? ''), 'BPJS'))) && empty($row['cash_advance_installment_id']) && ($row['category'] ?? '') !== 'tax' && strcasecmp(trim($row['name'] ?? ''), 'pph 21') !== 0)
                                        @php
                                            $deductionCounter++;
                                            $isEnabled = $row['is_enabled'] ?? true;
                                            $rowLower = strtolower(trim($row['name'] ?? ''));
                                            $bpjsCalcKey = 'bpjs_kes_emp';
                                            if (str_contains($rowLower, 'jht')) {
                                                $bpjsCalcKey = 'bpjs_jht_emp';
                                            } elseif (str_contains($rowLower, 'pensiun') || str_contains($rowLower, 'jp')) {
                                                $bpjsCalcKey = 'bpjs_jp_emp';
                                            }
                                        @endphp
                                        <tr wire:key="deduction-row-bpjs-{{ $index }}" class="{{ ! $isEnabled ? 'payroll-row-disabled' : '' }}">
                                            <td class="text-center {{ $isEnabled ? 'text-muted' : 'payroll-text-disabled' }} align-middle !px-3 !py-2.5" style="font-size: 12.5px;">{{ $deductionCounter }}</td>
                                            <td class="align-middle !px-3 !py-2.5">
                                                <div class="{{ $isEnabled ? 'text-dark' : 'payroll-text-disabled' }} d-flex align-items-center" style="font-weight: 500; font-size: 13px;">
                                                    {{ $row['name'] }}
                                                    @if ($isEnabled)
                                                        <span class="badge badge-light border text-info ml-2 text-xs" style="font-weight: 500;">BPJS Otomatis</span>
                                                    @else
                                                        <span class="badge badge-light border text-muted ml-2 text-xs" style="font-weight: 500;">Nonaktif</span>
                                                    @endif
                                                </div>
                                                @if (! empty($row['notes']))
                                                    <div class="{{ $isEnabled ? 'text-muted' : 'payroll-text-disabled' }} text-xs">{{ $row['notes'] }}</div>
                                                @endif
                                            </td>
                                            <td class="text-right align-middle !px-3 !py-2.5 {{ $isEnabled ? 'text-danger' : 'payroll-amount-disabled' }}" style="font-weight: 600; font-size: 13px;">
                                                -Rp {{ number_format((float) ($row['amount'] ?? 0), 0, ',', '.') }}
                                            </td>
                                            <td class="text-center align-middle !px-3 !py-2.5">
                                                <button type="button"
                                                        wire:click="showCalculationDetail('{{ $bpjsCalcKey }}')"
                                                        class="btn btn-info btn-action"
                                                        title="Lihat Rincian Perhitungan {{ $row['name'] }}">
                                                    <i class="fas fa-calculator"></i>
                                                </button>
                                            </td>
                                            <td class="text-center align-middle !px-3 !py-2.5">
                                                @if ($this->isEditable)
                                                    <label class="custom-switch payroll-status-toggle m-0"
                                                           wire:key="deduction-bpjs-toggle-{{ $index }}-{{ $isEnabled ? '1' : '0' }}"
                                                           title="{{ $isEnabled ? 'Aktif' : 'Nonaktif' }}">
                                                        <input type="checkbox"
                                                               wire:click="toggleDeductionEnabled({{ $index }})"
                                                               @checked($isEnabled)
                                                               class="custom-switch-input">
                                                        <span class="custom-switch-indicator"></span>
                                                    </label>
                                                @else
                                                    @if ($isEnabled)
                                                        <span class="badge badge-light border text-success text-xs font-weight-600">Aktif</span>
                                                    @else
                                                        <span class="badge badge-light border text-muted text-xs">Nonaktif</span>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach

                                <!-- Group Header: Potongan Tidak Tetap / Lainnya -->
                                <tr class="payroll-group-header-row">
                                    <td colspan="5" class="!px-3 !py-1.5" style="background-color: #f8fafc; border-top: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7;">
                                        <span class="payroll-group-header-title text-uppercase" style="font-size: 12px; font-weight: 600; color: #334155; letter-spacing: 0.4px;">
                                            <i class="fas fa-minus-circle text-danger mr-1"></i> Potongan Tidak Tetap / Lainnya
                                        </span>
                                    </td>
                                </tr>

                                <!-- Editable Deduction Rows -->
                                @foreach ($deductionRows as $index => $row)
                                    @if (empty($row['is_system']) && empty($row['cash_advance_installment_id']) && ($row['category'] ?? '') !== 'tax' && ($row['category'] ?? '') !== 'bpjs' && strcasecmp(trim($row['name'] ?? ''), 'pph 21') !== 0 && ! str_starts_with(strtoupper($row['name'] ?? ''), 'BPJS'))
                                        @if (! $this->isEditable && empty($row['name']) && empty($row['amount']))
                                            @continue
                                        @endif
                                        @php $deductionCounter++; @endphp
                                        <tr wire:key="deduction-row-{{ $index }}">
                                            <td class="text-center text-muted align-middle !px-3 !py-2.5" style="font-size: 12.5px;">{{ $deductionCounter }}</td>
                                            <td class="align-middle !px-3 !py-2.5">
                                                @if ($this->isEditable)
                                                    <div class="position-relative select2-like-wrapper"
                                                         x-data="{
                                                             open: false,
                                                             search: '',
                                                             highlightedIndex: -1,
                                                             items: {{ Js::from($this->masterDeductions->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code])->values()) }},
                                                             init() {
                                                                 this.search = this.$refs.input ? this.$refs.input.value : '';
                                                             },
                                                             get filteredItems() {
                                                                 const q = (this.search || '').toLowerCase().trim();
                                                                 if (!q) return this.items;
                                                                 return this.items.filter(item => 
                                                                     item.name.toLowerCase().includes(q) || 
                                                                     (item.code && item.code.toLowerCase().includes(q))
                                                                 );
                                                             },
                                                             select(item) {
                                                                 this.search = item.name;
                                                                 if (this.$refs.input) {
                                                                     this.$refs.input.value = item.name;
                                                                     this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
                                                                 }
                                                                 $wire.set('deductionRows.{{ $index }}.name', item.name);
                                                                 this.open = false;
                                                                 this.highlightedIndex = -1;
                                                             },
                                                             onKeyDownDown() {
                                                                 if (!this.open) { this.open = true; this.highlightedIndex = 0; return; }
                                                                 if (this.highlightedIndex < this.filteredItems.length - 1) {
                                                                     this.highlightedIndex++;
                                                                     this.scrollToHighlighted();
                                                                 }
                                                             },
                                                             onKeyDownUp() {
                                                                 if (!this.open) return;
                                                                 if (this.highlightedIndex > 0) {
                                                                     this.highlightedIndex--;
                                                                     this.scrollToHighlighted();
                                                                 }
                                                             },
                                                             onEnter(e) {
                                                                 if (this.open && this.filteredItems.length > 0) {
                                                                     if (this.highlightedIndex >= 0 && this.filteredItems[this.highlightedIndex]) {
                                                                         e.preventDefault();
                                                                         this.select(this.filteredItems[this.highlightedIndex]);
                                                                     } else if (this.filteredItems.length === 1) {
                                                                         e.preventDefault();
                                                                         this.select(this.filteredItems[0]);
                                                                     }
                                                                 }
                                                             },
                                                             scrollToHighlighted() {
                                                                 this.$nextTick(() => {
                                                                     const list = this.$refs.list;
                                                                     if (list && list.children[this.highlightedIndex]) {
                                                                         list.children[this.highlightedIndex].scrollIntoView({ block: 'nearest' });
                                                                     }
                                                                 });
                                                             }
                                                         }"
                                                         @click.outside="open = false"
                                                    >
                                                        <div class="position-relative">
                                                            <input type="text"
                                                                   x-ref="input"
                                                                   wire:model.live.debounce.400ms="deductionRows.{{ $index }}.name"
                                                                   @focus="search = $event.target.value; open = true; highlightedIndex = -1"
                                                                   @click="open = true"
                                                                   @input="search = $event.target.value; open = true; highlightedIndex = -1"
                                                                   @keydown.down.prevent="onKeyDownDown()"
                                                                   @keydown.up.prevent="onKeyDownUp()"
                                                                   @keydown.enter="onEnter($event)"
                                                                   @keydown.escape="open = false"
                                                                   class="form-control payroll-table-input pr-4"
                                                                   placeholder="Ketik nama potongan atau pilih master..."
                                                                   autocomplete="off">
                                                            <span class="position-absolute text-muted"
                                                                  @click.stop="open = !open; if (open) { search = ''; $refs.input.focus(); }"
                                                                  style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 11px;">
                                                                <i class="fas" :class="open ? 'fa-chevron-up text-primary' : 'fa-chevron-down'"></i>
                                                            </span>
                                                        </div>

                                                        <div x-show="open && filteredItems.length > 0"
                                                             x-transition:enter="transition ease-out duration-100"
                                                             x-transition:enter-start="opacity-0 transform -translate-y-1"
                                                             x-transition:enter-end="opacity-100 transform translate-y-0"
                                                             x-cloak
                                                             class="select2-like-dropdown position-absolute bg-white"
                                                             style="top: 100%; left: 0; right: 0; z-index: 1050; margin-top: 2px; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); overflow: hidden;"
                                                        >
                                                            <ul x-ref="list" class="list-unstyled mb-0 py-1" style="max-height: 200px; overflow-y: auto;">
                                                                <template x-for="(item, idx) in filteredItems" :key="item.id">
                                                                    <li @mouseenter="highlightedIndex = idx"
                                                                        @mousedown.prevent="select(item)"
                                                                        class="px-3 py-2 d-flex align-items-center justify-content-between"
                                                                        style="font-size: 13px; font-weight: 500; cursor: pointer; transition: background-color 0.1s ease;"
                                                                        :style="highlightedIndex === idx ? 'background-color: #0b52aa !important; color: #ffffff !important;' : 'background-color: #ffffff; color: #1e293b;'"
                                                                    >
                                                                        <span x-text="item.name"></span>
                                                                        <span x-show="item.code"
                                                                              x-text="'(' + item.code + ')'"
                                                                              style="font-size: 11.5px; font-weight: 400; margin-left: 8px;"
                                                                              :style="highlightedIndex === idx ? 'color: rgba(255,255,255,0.75) !important;' : 'color: #64748b !important;'"
                                                                        ></span>
                                                                    </li>
                                                                </template>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-dark font-weight-500" style="font-size: 13px;">{{ $row['name'] }}</div>
                                                @endif
                                            </td>
                                            <td class="align-middle !px-3 !py-2.5">
                                                @if ($this->isEditable)
                                                    <div class="d-flex align-items-center">
                                                        @if (($row['calc_mode'] ?? 'nominal') === 'percentage')
                                                            <div class="d-flex align-items-center flex-grow-1 mr-2">
                                                                <div class="input-group payroll-input-group mr-1.5" style="width: 110px; flex-shrink: 0;" title="Persentase dari Gaji Pokok">
                                                                    <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="deductionRows.{{ $index }}.percentage" class="form-control text-right payroll-input payroll-pct-input" placeholder="0" autocomplete="off">
                                                                    <div class="input-group-append">
                                                                        <span class="input-group-text payroll-addon">%</span>
                                                                    </div>
                                                                </div>
                                                                <div class="input-group payroll-input-group flex-grow-1" title="Nominal Dihitung Otomatis">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text payroll-addon">Rp</span>
                                                                    </div>
                                                                    <input type="text" value="{{ !empty($row['amount']) ? number_format((float) $row['amount'], 0, ',', '.') : '0' }}" class="form-control text-right bg-light payroll-input text-muted" placeholder="0" readonly>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="input-group payroll-input-group flex-grow-1 mr-2">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text payroll-addon">Rp</span>
                                                                </div>
                                                                <input type="text"
                                                                       inputmode="numeric"
                                                                       x-on:input="
                                                                           let raw = $el.value.replace(/\D/g, '');
                                                                           $el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
                                                                       "
                                                                       x-on:input.debounce.350ms="$wire.set('deductionRows.{{ $index }}.amount', $el.value.replace(/\D/g, '') ? parseFloat($el.value.replace(/\D/g, '')) : '')"
                                                                       x-on:blur="$wire.set('deductionRows.{{ $index }}.amount', $el.value.replace(/\D/g, '') ? parseFloat($el.value.replace(/\D/g, '')) : '')"
                                                                       value="{{ !empty($row['amount']) ? number_format((float) $row['amount'], 0, ',', '.') : '' }}"
                                                                       class="form-control text-right payroll-input"
                                                                       placeholder="0"
                                                                       autocomplete="off">
                                                            </div>
                                                        @endif
                                                        <label class="custom-switch payroll-calc-toggle" title="Beralih mode nominal atau persentase (% Gaji Pokok)">
                                                            <input type="checkbox" wire:click="toggleCalcMode('deduction', {{ $index }})" @checked(($row['calc_mode'] ?? 'nominal') === 'percentage') class="custom-switch-input">
                                                            <span class="custom-switch-indicator"></span>
                                                            <span class="custom-switch-description text-xs font-weight-600 {{ ($row['calc_mode'] ?? 'nominal') === 'percentage' ? 'text-primary' : 'text-muted' }} ml-1.5">%</span>
                                                        </label>
                                                    </div>
                                                @else
                                                    <div class="text-right text-dark font-weight-600" style="font-size: 13px;">
                                                        Rp {{ number_format((float) ($row['amount'] ?? 0), 0, ',', '.') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle text-muted !px-3 !py-2.5">-</td>
                                            <td class="text-center align-middle text-nowrap !px-3 !py-2.5">
                                                @if ($this->isEditable && (!empty($row['name']) || !empty($row['amount']) || count($deductionRows) > 1))
                                                    <button type="button" wire:click="removeDeductionRow({{ $index }})" class="btn btn-danger btn-action" title="Hapus Baris"><i class="fas fa-trash"></i></button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end align-items-center">
                    <div style="color: #475569; font-size: 12.5px; font-weight: 400;">
                        Total Potongan: <span class="text-dark ml-1" style="font-weight: 600; font-size: 13.5px;">-Rp {{ number_format($this->totalDeductions, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- ================= CARD 2C: TANGGUNGAN PERUSAHAAN (COMPANY CONTRIBUTIONS) ================= -->
            <div class="card mb-4 shadow-sm" style="border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; background: #ffffff;">
                <div class="card-header">
                    <h4>Tanggungan Perusahaan</h4>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-clean mb-0">
                            <thead style="background-color: #f8fafc; border-bottom: 1px solid #edf2f7;">
                                <tr>
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 45px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">NO</th>
                                    <th class="text-nowrap !px-3 !py-2.5" style="font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">NAMA TANGGUNGAN</th>
                                    <th class="text-right text-nowrap !px-3 !py-2.5" style="width: 340px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">NOMINAL (RP)</th>
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 60px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;"><i class="fas fa-calculator" style="color: #64748b;" title="Rincian Perhitungan"></i></th>
                                    <th class="text-center text-nowrap !px-3 !py-2.5" style="width: 70px; font-weight: 600; color: #334155; font-size: 12.5px; letter-spacing: 0.3px;">
                                        @if ($this->isEditable && ($this->bpjsBreakdown['company']['total'] ?? 0) > 0)
                                            <label class="custom-switch payroll-status-toggle m-0"
                                                   wire:key="header-company-bpjs-toggle-{{ $this->hasAnyCompanyBpjsEnabled ? 'on' : 'off' }}"
                                                   title="{{ $this->hasAnyCompanyBpjsEnabled ? 'Klik untuk mematikan semua tanggungan BPJS perusahaan' : 'Klik untuk mengaktifkan semua tanggungan BPJS perusahaan' }}">
                                                <input type="checkbox"
                                                       wire:click="toggleAllCompanyBpjs"
                                                       @checked($this->hasAnyCompanyBpjsEnabled)
                                                       class="custom-switch-input">
                                                <span class="custom-switch-indicator"></span>
                                            </label>
                                        @else
                                            <i class="fas fa-cog" style="color: #64748b;" title="Aksi"></i>
                                        @endif
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (($this->bpjsBreakdown['company']['total'] ?? 0) > 0)
                                    <!-- Group Header: Tanggungan Tetap Perusahaan (BPJS) -->
                                    <tr class="payroll-group-header-row">
                                        <td colspan="5" class="!px-3 !py-1.5" style="background-color: #f8fafc; border-bottom: 1px solid #edf2f7;">
                                            <span class="payroll-group-header-title text-uppercase" style="font-size: 12px; font-weight: 600; color: #334155; letter-spacing: 0.4px;">
                                                <i class="fas fa-building text-primary mr-1"></i> Tanggungan Tetap Perusahaan (BPJS)
                                            </span>
                                        </td>
                                    </tr>

                                    @php $isKesActive = $companyBpjsStatus['bpjs_kes'] ?? true; @endphp
                                    <tr class="{{ ! $isKesActive ? 'payroll-row-disabled' : '' }}">
                                        <td class="text-center {{ $isKesActive ? 'text-muted' : 'payroll-text-disabled' }} align-middle !px-3 !py-2.5" style="font-size: 12.5px;">1</td>
                                        <td class="align-middle !px-3 !py-2.5">
                                            <div class="{{ $isKesActive ? 'text-dark' : 'payroll-text-disabled' }} d-flex align-items-center" style="font-weight: 500; font-size: 13px;">
                                                BPJS Kesehatan ({{ (float) ($company->bpjs_kes_comp_rate ?? 0.04) * 100 }}%)
                                                @if ($isKesActive)
                                                    <span class="badge badge-light border text-info ml-2 text-xs" style="font-weight: 500;">BPJS Otomatis</span>
                                                @else
                                                    <span class="badge badge-light border text-muted ml-2 text-xs" style="font-weight: 500;">Nonaktif</span>
                                                @endif
                                            </div>
                                            <div class="{{ $isKesActive ? 'text-muted' : 'payroll-text-disabled' }} text-xs">Iuran BPJS Kesehatan dibayar oleh perusahaan</div>
                                        </td>
                                        <td class="text-right align-middle font-weight-600 !px-3 !py-2.5 {{ $isKesActive ? '' : 'payroll-amount-disabled' }}" style="font-size: 13px; {{ $isKesActive ? 'color: #0b52aa;' : '' }}">
                                            Rp {{ number_format((float) ($this->bpjsBreakdown['company']['bpjs_kes'] ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            <button type="button"
                                                    wire:click="showCalculationDetail('company_bpjs_kes')"
                                                    class="btn btn-info btn-action"
                                                    title="Lihat Rincian Perhitungan BPJS Kesehatan (Perusahaan)">
                                                <i class="fas fa-calculator"></i>
                                            </button>
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            @if ($this->isEditable)
                                                <label class="custom-switch payroll-status-toggle m-0"
                                                       wire:key="company-bpjs-toggle-kes-{{ $isKesActive ? '1' : '0' }}"
                                                       title="{{ $isKesActive ? 'Aktif' : 'Nonaktif' }}">
                                                    <input type="checkbox"
                                                           wire:click="toggleCompanyBpjs('bpjs_kes')"
                                                           @checked($isKesActive)
                                                           class="custom-switch-input">
                                                    <span class="custom-switch-indicator"></span>
                                                </label>
                                            @else
                                                @if ($isKesActive)
                                                    <span class="badge badge-light border text-success text-xs font-weight-600">Aktif</span>
                                                @else
                                                    <span class="badge badge-light border text-muted text-xs">Nonaktif</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>

                                    @php $isJhtActive = $companyBpjsStatus['bpjs_jht'] ?? true; @endphp
                                    <tr class="{{ ! $isJhtActive ? 'payroll-row-disabled' : '' }}">
                                        <td class="text-center {{ $isJhtActive ? 'text-muted' : 'payroll-text-disabled' }} align-middle !px-3 !py-2.5" style="font-size: 12.5px;">2</td>
                                        <td class="align-middle !px-3 !py-2.5">
                                            <div class="{{ $isJhtActive ? 'text-dark' : 'payroll-text-disabled' }} d-flex align-items-center" style="font-weight: 500; font-size: 13px;">
                                                BPJS Ketenagakerjaan JHT ({{ (float) ($company->bpjs_jht_comp_rate ?? 0.037) * 100 }}%)
                                                @if ($isJhtActive)
                                                    <span class="badge badge-light border text-info ml-2 text-xs" style="font-weight: 500;">BPJS Otomatis</span>
                                                @else
                                                    <span class="badge badge-light border text-muted ml-2 text-xs" style="font-weight: 500;">Nonaktif</span>
                                                @endif
                                            </div>
                                            <div class="{{ $isJhtActive ? 'text-muted' : 'payroll-text-disabled' }} text-xs">Iuran JHT dibayar oleh perusahaan</div>
                                        </td>
                                        <td class="text-right align-middle font-weight-600 !px-3 !py-2.5 {{ $isJhtActive ? '' : 'payroll-amount-disabled' }}" style="font-size: 13px; {{ $isJhtActive ? 'color: #0b52aa;' : '' }}">
                                            Rp {{ number_format((float) ($this->bpjsBreakdown['company']['bpjs_jht'] ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            <button type="button"
                                                    wire:click="showCalculationDetail('company_bpjs_jht')"
                                                    class="btn btn-info btn-action"
                                                    title="Lihat Rincian Perhitungan BPJS JHT (Perusahaan)">
                                                <i class="fas fa-calculator"></i>
                                            </button>
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            @if ($this->isEditable)
                                                <label class="custom-switch payroll-status-toggle m-0"
                                                       wire:key="company-bpjs-toggle-jht-{{ $isJhtActive ? '1' : '0' }}"
                                                       title="{{ $isJhtActive ? 'Aktif' : 'Nonaktif' }}">
                                                    <input type="checkbox"
                                                           wire:click="toggleCompanyBpjs('bpjs_jht')"
                                                           @checked($isJhtActive)
                                                           class="custom-switch-input">
                                                    <span class="custom-switch-indicator"></span>
                                                </label>
                                            @else
                                                @if ($isJhtActive)
                                                    <span class="badge badge-light border text-success text-xs font-weight-600">Aktif</span>
                                                @else
                                                    <span class="badge badge-light border text-muted text-xs">Nonaktif</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>

                                    @php $isJpActive = $companyBpjsStatus['bpjs_jp'] ?? true; @endphp
                                    <tr class="{{ ! $isJpActive ? 'payroll-row-disabled' : '' }}">
                                        <td class="text-center {{ $isJpActive ? 'text-muted' : 'payroll-text-disabled' }} align-middle !px-3 !py-2.5" style="font-size: 12.5px;">3</td>
                                        <td class="align-middle !px-3 !py-2.5">
                                            <div class="{{ $isJpActive ? 'text-dark' : 'payroll-text-disabled' }} d-flex align-items-center" style="font-weight: 500; font-size: 13px;">
                                                BPJS Ketenagakerjaan JP ({{ (float) ($company->bpjs_jp_comp_rate ?? 0.02) * 100 }}%)
                                                @if ($isJpActive)
                                                    <span class="badge badge-light border text-info ml-2 text-xs" style="font-weight: 500;">BPJS Otomatis</span>
                                                @else
                                                    <span class="badge badge-light border text-muted ml-2 text-xs" style="font-weight: 500;">Nonaktif</span>
                                                @endif
                                            </div>
                                            <div class="{{ $isJpActive ? 'text-muted' : 'payroll-text-disabled' }} text-xs">Iuran Jaminan Pensiun dibayar oleh perusahaan</div>
                                        </td>
                                        <td class="text-right align-middle font-weight-600 !px-3 !py-2.5 {{ $isJpActive ? '' : 'payroll-amount-disabled' }}" style="font-size: 13px; {{ $isJpActive ? 'color: #0b52aa;' : '' }}">
                                            Rp {{ number_format((float) ($this->bpjsBreakdown['company']['bpjs_jp'] ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            <button type="button"
                                                    wire:click="showCalculationDetail('company_bpjs_jp')"
                                                    class="btn btn-info btn-action"
                                                    title="Lihat Rincian Perhitungan BPJS JP (Perusahaan)">
                                                <i class="fas fa-calculator"></i>
                                            </button>
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            @if ($this->isEditable)
                                                <label class="custom-switch payroll-status-toggle m-0"
                                                       wire:key="company-bpjs-toggle-jp-{{ $isJpActive ? '1' : '0' }}"
                                                       title="{{ $isJpActive ? 'Aktif' : 'Nonaktif' }}">
                                                    <input type="checkbox"
                                                           wire:click="toggleCompanyBpjs('bpjs_jp')"
                                                           @checked($isJpActive)
                                                           class="custom-switch-input">
                                                    <span class="custom-switch-indicator"></span>
                                                </label>
                                            @else
                                                @if ($isJpActive)
                                                    <span class="badge badge-light border text-success text-xs font-weight-600">Aktif</span>
                                                @else
                                                    <span class="badge badge-light border text-muted text-xs">Nonaktif</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>

                                    @php $isJkkActive = $companyBpjsStatus['bpjs_jkk'] ?? true; @endphp
                                    <tr class="{{ ! $isJkkActive ? 'payroll-row-disabled' : '' }}">
                                        <td class="text-center {{ $isJkkActive ? 'text-muted' : 'payroll-text-disabled' }} align-middle !px-3 !py-2.5" style="font-size: 12.5px;">4</td>
                                        <td class="align-middle !px-3 !py-2.5">
                                            <div class="{{ $isJkkActive ? 'text-dark' : 'payroll-text-disabled' }} d-flex align-items-center" style="font-weight: 500; font-size: 13px;">
                                                BPJS Ketenagakerjaan JKK ({{ round(((float) ($company->bpjs_jkk_risk_rate ?? 0.0024)) * 100, 3) }}%)
                                                @if ($isJkkActive)
                                                    <span class="badge badge-light border text-info ml-2 text-xs" style="font-weight: 500;">BPJS Otomatis</span>
                                                @else
                                                    <span class="badge badge-light border text-muted ml-2 text-xs" style="font-weight: 500;">Nonaktif</span>
                                                @endif
                                            </div>
                                            <div class="{{ $isJkkActive ? 'text-muted' : 'payroll-text-disabled' }} text-xs">Jaminan Kecelakaan Kerja dibayar oleh perusahaan</div>
                                        </td>
                                        <td class="text-right align-middle font-weight-600 !px-3 !py-2.5 {{ $isJkkActive ? '' : 'payroll-amount-disabled' }}" style="font-size: 13px; {{ $isJkkActive ? 'color: #0b52aa;' : '' }}">
                                            Rp {{ number_format((float) ($this->bpjsBreakdown['company']['bpjs_jkk'] ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            <button type="button"
                                                    wire:click="showCalculationDetail('company_bpjs_jkk')"
                                                    class="btn btn-info btn-action"
                                                    title="Lihat Rincian Perhitungan BPJS JKK (Perusahaan)">
                                                <i class="fas fa-calculator"></i>
                                            </button>
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            @if ($this->isEditable)
                                                <label class="custom-switch payroll-status-toggle m-0"
                                                       wire:key="company-bpjs-toggle-jkk-{{ $isJkkActive ? '1' : '0' }}"
                                                       title="{{ $isJkkActive ? 'Aktif' : 'Nonaktif' }}">
                                                    <input type="checkbox"
                                                           wire:click="toggleCompanyBpjs('bpjs_jkk')"
                                                           @checked($isJkkActive)
                                                           class="custom-switch-input">
                                                    <span class="custom-switch-indicator"></span>
                                                </label>
                                            @else
                                                @if ($isJkkActive)
                                                    <span class="badge badge-light border text-success text-xs font-weight-600">Aktif</span>
                                                @else
                                                    <span class="badge badge-light border text-muted text-xs">Nonaktif</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>

                                    @php $isJkmActive = $companyBpjsStatus['bpjs_jkm'] ?? true; @endphp
                                    <tr class="{{ ! $isJkmActive ? 'payroll-row-disabled' : '' }}">
                                        <td class="text-center {{ $isJkmActive ? 'text-muted' : 'payroll-text-disabled' }} align-middle !px-3 !py-2.5" style="font-size: 12.5px;">5</td>
                                        <td class="align-middle !px-3 !py-2.5">
                                            <div class="{{ $isJkmActive ? 'text-dark' : 'payroll-text-disabled' }} d-flex align-items-center" style="font-weight: 500; font-size: 13px;">
                                                BPJS Ketenagakerjaan JKM ({{ (float) ($company->bpjs_jkm_rate ?? 0.003) * 100 }}%)
                                                @if ($isJkmActive)
                                                    <span class="badge badge-light border text-info ml-2 text-xs" style="font-weight: 500;">BPJS Otomatis</span>
                                                @else
                                                    <span class="badge badge-light border text-muted ml-2 text-xs" style="font-weight: 500;">Nonaktif</span>
                                                @endif
                                            </div>
                                            <div class="{{ $isJkmActive ? 'text-muted' : 'payroll-text-disabled' }} text-xs">Jaminan Kematian dibayar oleh perusahaan</div>
                                        </td>
                                        <td class="text-right align-middle font-weight-600 !px-3 !py-2.5 {{ $isJkmActive ? '' : 'payroll-amount-disabled' }}" style="font-size: 13px; {{ $isJkmActive ? 'color: #0b52aa;' : '' }}">
                                            Rp {{ number_format((float) ($this->bpjsBreakdown['company']['bpjs_jkm'] ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            <button type="button"
                                                    wire:click="showCalculationDetail('company_bpjs_jkm')"
                                                    class="btn btn-info btn-action"
                                                    title="Lihat Rincian Perhitungan BPJS JKM (Perusahaan)">
                                                <i class="fas fa-calculator"></i>
                                            </button>
                                        </td>
                                        <td class="text-center align-middle !px-3 !py-2.5">
                                            @if ($this->isEditable)
                                                <label class="custom-switch payroll-status-toggle m-0"
                                                       wire:key="company-bpjs-toggle-jkm-{{ $isJkmActive ? '1' : '0' }}"
                                                       title="{{ $isJkmActive ? 'Aktif' : 'Nonaktif' }}">
                                                    <input type="checkbox"
                                                           wire:click="toggleCompanyBpjs('bpjs_jkm')"
                                                           @checked($isJkmActive)
                                                           class="custom-switch-input">
                                                    <span class="custom-switch-indicator"></span>
                                                </label>
                                            @else
                                                @if ($isJkmActive)
                                                    <span class="badge badge-light border text-success text-xs font-weight-600">Aktif</span>
                                                @else
                                                    <span class="badge badge-light border text-muted text-xs">Nonaktif</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4 font-italic" style="font-size: 12.5px;">
                                            Tidak ada tanggungan BPJS perusahaan untuk karyawan ini atau fitur dinonaktifkan di pengaturan perusahaan.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div style="color: #64748b; font-size: 11.5px;">
                        <i class="fas fa-info-circle mr-1" style="color: #0b52aa;"></i> Tanggungan perusahaan tidak memotong gaji karyawan (manfaat tambahan di luar gaji bersih).
                    </div>
                    <div style="color: #475569; font-size: 12.5px; font-weight: 400;">
                        Total Tanggungan Perusahaan: <span class="ml-1" style="font-weight: 600; font-size: 13.5px; color: #0b52aa !important;">Rp {{ number_format($this->totalCompanyContributions, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Master Components Lists for Reference & Autocomplete -->
    <datalist id="master-allowance-list">
        @foreach ($this->masterAllowances as $ma)
            <option value="{{ $ma->name }}">{{ $ma->name }}{{ $ma->code ? ' ('.$ma->code.')' : '' }}</option>
        @endforeach
    </datalist>

    <datalist id="master-deduction-list">
        @foreach ($this->masterDeductions as $md)
            <option value="{{ $md->name }}">{{ $md->name }}{{ $md->code ? ' ('.$md->code.')' : '' }}</option>
        @endforeach
    </datalist>

    <!-- ================= MODAL RINCIAN PERHITUNGAN (CALCULATION BREAKDOWN) ================= -->
    <div wire:ignore.self class="modal fade" id="calcDetailModal" role="dialog" aria-labelledby="calcDetailModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
                <div class="modal-header bg-white border-bottom py-3">
                    <h5 class="modal-title text-dark" id="calcDetailModalLabel" style="font-weight: 500;">
                        {{ $calculationDetail['title'] ?? 'Rincian Perhitungan' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body py-3 px-4 bg-white">
                    @if ($calculationDetail)
                        <!-- Calculation Detail Tile Group (Sesuai Desain Ringkasan Profil Karyawan) -->
                        <div class="emp-tile-group mb-0 text-left">
                            @foreach ($calculationDetail['rows'] ?? [] as $row)
                                @php
                                    $isHighlight = ! empty($row['is_bold']);
                                    $hasAmount = ! empty($row['amount']) && $row['amount'] !== '—';
                                    $displayValue = $hasAmount ? $row['amount'] : ($row['calc'] ?? '—');
                                    $formulaText = ($hasAmount && ! empty($row['calc']) && $row['calc'] !== '—') ? $row['calc'] : null;
                                    $icon = $row['icon'] ?? 'fas fa-receipt';
                                @endphp
                                <div class="emp-tile-row bg-white" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                                    <div class="emp-tile-left d-flex align-items-center min-w-0 pr-2">
                                        <div class="emp-tile-icon" style="background-color: #f0f7ff; color: #0b52aa !important; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 11.5px; margin-right: 10px; flex-shrink: 0;">
                                            <i class="{{ $icon }}"></i>
                                        </div>
                                        <div class="min-w-0 text-left">
                                            <div class="text-truncate" style="font-size: 13px; font-weight: 500; color: #0f172a; line-height: 1.35;" title="{{ $row['component'] }}">
                                                {{ $row['component'] }}
                                            </div>
                                            @if ($formulaText)
                                                <div class="text-truncate" style="font-size: 11.5px; font-weight: 400; color: #475569; line-height: 1.3; margin-top: 2px;" title="{{ $formulaText }}">
                                                    {{ $formulaText }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="emp-tile-right text-nowrap pl-2 d-flex align-items-center justify-content-end" style="flex-shrink: 0;">
                                        <span style="font-size: 13px; {{ $isHighlight ? 'font-weight: 600; color: #0b52aa !important;' : 'font-weight: 500; color: #0f172a;' }}">
                                            {{ $displayValue }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Kesimpulan / Penjelasan Perhitungan (.bd-callout) -->
                        @if (! empty($calculationDetail['summary_text']))
                            <div class="bd-callout bd-callout-info mt-3 mb-0" role="alert">
                                <div class="d-flex align-items-start">
                                    <div class="bd-callout-icon mr-3 mt-0.5">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="bd-callout-title">
                                            Kesimpulan Perhitungan
                                        </div>
                                        <div class="bd-callout-desc">
                                            {!! $calculationDetail['summary_text'] !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4 text-muted" style="font-size: 13px;">
                            <i class="fas fa-spinner fa-spin mr-1 text-primary" style="color: #0b52aa !important;"></i> Memuat rincian perhitungan...
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-white border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            [x-cloak] {
                display: none !important;
            }

            /* Responsive flexbox constraints & horizontal table scroll containment */
            .table-responsive {
                width: 100% !important;
                max-width: 100% !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }

            @media (max-width: 991.98px) {
                .row {
                    min-width: 0 !important;
                }
                .row > [class*="col-"] {
                    min-width: 0 !important;
                    max-width: 100% !important;
                }
                .card,
                .card.card-payroll-table {
                    min-width: 0 !important;
                    max-width: 100% !important;
                    overflow: hidden !important;
                }
            }

            @media (min-width: 992px) {
                /* On desktop, allow autocomplete dropdowns to float outside card cleanly */
                .card.card-payroll-table {
                    overflow: visible !important;
                }
                .card.card-payroll-table .table-responsive {
                    overflow: visible !important;
                }
            }

            /* Select2-like Autocomplete Dropdown */
            .select2-like-dropdown ul::-webkit-scrollbar {
                width: 6px;
            }
            .select2-like-dropdown ul::-webkit-scrollbar-track {
                background: #f8fafc;
            }
            .select2-like-dropdown ul::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 3px;
            }
            .select2-like-dropdown ul::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            /* Bootstrap Callout (.bd-callout) Component */
            .bd-callout {
                padding: 1rem 1.25rem !important;
                margin-top: 0 !important;
                margin-bottom: 1.5rem !important;
                background-color: #ffffff !important;
                border: 1px solid #e2e8f0 !important;
                border-left-width: 4px !important;
                border-radius: 8px !important;
                box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.04) !important;
                transition: all 0.2s ease-in-out !important;
            }

            .bd-callout-success {
                border-left-color: #10b981 !important;
                background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, rgba(255, 255, 255, 0.96) 18%, #ffffff 100%) !important;
            }

            .bd-callout-danger {
                border-left-color: #ef4444 !important;
                background: linear-gradient(90deg, rgba(239, 68, 68, 0.05) 0%, rgba(255, 255, 255, 0.96) 18%, #ffffff 100%) !important;
            }

            .bd-callout-warning {
                border-left-color: #f59e0b !important;
                background: linear-gradient(90deg, rgba(245, 158, 11, 0.05) 0%, rgba(255, 255, 255, 0.96) 18%, #ffffff 100%) !important;
            }

            .bd-callout-info {
                border-left-color: #0b52aa !important;
                background: linear-gradient(90deg, rgba(11, 82, 170, 0.05) 0%, rgba(255, 255, 255, 0.96) 18%, #ffffff 100%) !important;
            }

            .bd-callout .bd-callout-icon {
                font-size: 18px !important;
                line-height: 1 !important;
                flex-shrink: 0 !important;
            }

            .bd-callout-success .bd-callout-icon {
                color: #10b981 !important;
            }

            .bd-callout-danger .bd-callout-icon {
                color: #ef4444 !important;
            }

            .bd-callout-warning .bd-callout-icon {
                color: #f59e0b !important;
            }

            .bd-callout-info .bd-callout-icon {
                color: #0b52aa !important;
            }

            .bd-callout .bd-callout-title {
                font-size: 14px !important;
                font-weight: 700 !important;
                line-height: 1.35 !important;
                margin-bottom: 3px !important;
                letter-spacing: -0.15px !important;
            }

            .bd-callout-success .bd-callout-title {
                color: #065f46 !important;
            }

            .bd-callout-danger .bd-callout-title {
                color: #991b1b !important;
            }

            .bd-callout-warning .bd-callout-title {
                color: #92400e !important;
            }

            .bd-callout-info .bd-callout-title {
                color: #0b52aa !important;
            }

            .bd-callout .bd-callout-desc {
                font-size: 12.5px !important;
                color: #475569 !important;
                line-height: 1.55 !important;
                margin-bottom: 0 !important;
            }

            .bd-callout .bd-callout-desc strong {
                font-weight: 600 !important;
                color: #1e293b !important;
            }

            /* Payroll Table Inputs & Addons Exact Alignment */
            .payroll-table-input {
                height: 36px !important;
                min-height: 36px !important;
                max-height: 36px !important;
                font-size: 13px !important;
                border-radius: 6px !important;
                border: 1px solid #cbd5e1 !important;
                padding: 6px 12px !important;
                background-color: #ffffff !important;
                color: #1e293b !important;
                box-shadow: none !important;
                line-height: normal !important;
            }
            .payroll-table-input:focus {
                border-color: #0b52aa !important;
                box-shadow: 0 0 0 3px rgba(11, 82, 170, 0.12) !important;
            }

            .payroll-input-group {
                display: flex !important;
                align-items: stretch !important;
                height: 36px !important;
                min-height: 36px !important;
                max-height: 36px !important;
                position: relative !important;
                margin-bottom: 0 !important;
            }
            .payroll-input-group .input-group-prepend,
            .payroll-input-group .input-group-append {
                display: flex !important;
                align-items: stretch !important;
                margin: 0 !important;
            }
            .payroll-input-group .payroll-addon {
                height: 36px !important;
                min-height: 36px !important;
                max-height: 36px !important;
                line-height: 34px !important;
                padding: 0 10px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                background-color: #f8fafc !important;
                color: #64748b !important;
                border: 1px solid #cbd5e1 !important;
                box-sizing: border-box !important;
                white-space: nowrap !important;
            }
            .payroll-input-group > .input-group-prepend > .payroll-addon {
                border-top-left-radius: 6px !important;
                border-bottom-left-radius: 6px !important;
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
                border-right: 0 !important;
            }
            .payroll-input-group > .input-group-append > .payroll-addon {
                border-top-right-radius: 6px !important;
                border-bottom-right-radius: 6px !important;
                border-top-left-radius: 0 !important;
                border-bottom-left-radius: 0 !important;
                border-left: 0 !important;
            }
            .payroll-input-group > .payroll-input {
                height: 36px !important;
                min-height: 36px !important;
                max-height: 36px !important;
                line-height: normal !important;
                font-size: 13px !important;
                border: 1px solid #cbd5e1 !important;
                padding: 6px 10px !important;
                box-sizing: border-box !important;
                box-shadow: none !important;
                background-color: #ffffff !important;
                color: #1e293b !important;
            }
            .payroll-input-group > .input-group-prepend + .payroll-input {
                border-top-left-radius: 0 !important;
                border-bottom-left-radius: 0 !important;
                border-top-right-radius: 6px !important;
                border-bottom-right-radius: 6px !important;
            }
            .payroll-input-group > .payroll-input:first-child {
                border-top-left-radius: 6px !important;
                border-bottom-left-radius: 6px !important;
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
            }
            .payroll-input-group > .payroll-input:focus {
                border-color: #0b52aa !important;
                box-shadow: 0 0 0 3px rgba(11, 82, 170, 0.12) !important;
                z-index: 2 !important;
            }

            .payroll-pct-input {
                padding-left: 8px !important;
                padding-right: 8px !important;
                text-align: right !important;
            }
            .payroll-pct-input::-webkit-inner-spin-button,
            .payroll-pct-input::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .payroll-pct-input {
                -moz-appearance: textfield;
            }

            .payroll-action-btn {
                height: 36px !important;
                min-height: 36px !important;
                max-height: 36px !important;
                width: 36px !important;
                min-width: 36px !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 6px !important;
            }

            /* Table Clean Header Typography */
            .table.table-clean thead th,
            .table-clean thead th {
                font-size: 12.5px !important;
                font-weight: 600 !important;
                color: #334155 !important;
                letter-spacing: 0.3px !important;
                text-transform: uppercase !important;
                padding: 10px 14px !important;
                vertical-align: middle !important;
            }

            /* Group Header Rows */
            .payroll-group-header-row td {
                background-color: #f8fafc !important;
                border-top: 1px solid #edf2f7 !important;
                border-bottom: 1px solid #edf2f7 !important;
                padding: 7px 14px !important;
            }
            .payroll-group-header-title {
                font-size: 12px !important;
                font-weight: 600 !important;
                color: #334155 !important;
                letter-spacing: 0.4px !important;
                text-transform: uppercase !important;
            }

            /* Payroll Toggle Switches (Clean, Symmetric, Hardware-Accelerated & Non-Clipped) */
            .payroll-status-toggle,
            .payroll-calc-toggle {
                margin: 0 !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                cursor: pointer !important;
                vertical-align: middle !important;
                user-select: none !important;
            }
            .payroll-calc-toggle {
                height: 36px !important;
                flex-shrink: 0 !important;
            }

            .payroll-status-toggle .custom-switch-indicator,
            .payroll-calc-toggle .custom-switch-indicator {
                height: 22px !important;
                width: 38px !important;
                min-width: 38px !important;
                max-width: 38px !important;
                margin: 0 !important;
                background-color: #cbd5e1 !important;
                border: none !important;
                border-radius: 9999px !important;
                position: relative !important;
                cursor: pointer !important;
                box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.12) !important;
                transition: background-color 0.2s ease-in-out !important;
            }

            .payroll-status-toggle .custom-switch-indicator:before,
            .payroll-calc-toggle .custom-switch-indicator:before {
                content: '' !important;
                position: absolute !important;
                height: 16px !important;
                width: 16px !important;
                top: 3px !important;
                left: 3px !important;
                border-radius: 50% !important;
                background-color: #ffffff !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25), 0 1px 1px rgba(0, 0, 0, 0.1) !important;
                transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            .payroll-status-toggle .custom-switch-input:checked ~ .custom-switch-indicator,
            .payroll-calc-toggle .custom-switch-input:checked ~ .custom-switch-indicator {
                background-color: #0b52aa !important;
            }

            .payroll-status-toggle .custom-switch-input:checked ~ .custom-switch-indicator:before,
            .payroll-calc-toggle .custom-switch-input:checked ~ .custom-switch-indicator:before {
                transform: translateX(16px) !important;
                left: 3px !important;
            }

            .payroll-row-disabled {
                background-color: #f8fafc !important;
            }
            .payroll-row-disabled td {
                background-color: #f8fafc !important;
            }
            .payroll-row-disabled:hover td {
                background-color: #f1f5f9 !important;
            }
            .payroll-text-disabled {
                color: #94a3b8 !important;
            }
            .payroll-amount-disabled {
                color: #94a3b8 !important;
                text-decoration: line-through !important;
            }

            /* SweetAlert Clean ActionSheet Popup */
            .swal2-popup.swal-actionsheet-popup {
                border-radius: 16px !important;
                padding: 24px 22px 20px !important;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            }
            .swal2-popup.swal-actionsheet-popup .swal2-title {
                font-size: 20px !important;
                font-weight: 700 !important;
                color: #1e293b !important;
                padding-top: 4px !important;
                margin-bottom: 8px !important;
            }
            .swal2-popup.swal-actionsheet-popup .swal2-icon {
                margin: 8px auto 14px auto !important;
                transform: scale(0.9) !important;
            }

            /* Employee summary card desktop sticky */
            .emp-summary-card {
                border-radius: 12px;
                border: 1px solid #e2e8f0;
                overflow: hidden;
                background: #ffffff;
            }
            @media (min-width: 992px) {
                .emp-summary-card {
                    position: sticky;
                    top: 85px;
                    z-index: 10;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                $(document).on('change select2:select', '#select_employee', function () {
                    const val = $(this).val();
                    if (val && val != '{{ $item->id }}') {
                        window.location.href = '/payrolls/{{ $period->id }}/items/' + val + '/edit';
                    }
                });

                function initManageItemScripts() {
                    const $select = $('#select_employee');
                    if ($select.length) {
                        if (!$select.hasClass('select2-hidden-accessible')) {
                            $select.select2({
                                width: '100%',
                                placeholder: '-- Pilih Karyawan --',
                            });
                        }

                        $select.off('change.employeeSwitcher select2:select.employeeSwitcher')
                               .on('change.employeeSwitcher select2:select.employeeSwitcher', function () {
                                   const val = $(this).val();
                                   if (val && val != '{{ $item->id }}') {
                                       window.location.href = '/payrolls/{{ $period->id }}/items/' + val + '/edit';
                                   }
                               });
                    }
                }

                $(document).on('click', '.btn-approval-action', function (e) {
                    e.preventDefault();
                    window.openApprovalMenu(this);
                });

                window.openApprovalMenu = function (btn) {
                    if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                        return;
                    }

                    if (!btn || !btn.dataset) {
                        btn = document.querySelector('[data-status]');
                    }

                    const status = btn ? (btn.dataset.status || 'pending') : 'pending';
                    const employee = btn ? (btn.dataset.employee || 'Karyawan') : 'Karyawan';
                    const approver = btn ? (btn.dataset.approver || 'Direktur') : 'Direktur';
                    const approvedAt = btn ? (btn.dataset.approvedAt || '') : '';
                    const rejectionReason = btn ? (btn.dataset.rejectionReason || '') : '';

                    const safeEmployee = $('<div>').text(employee).html();
                    const safeApprover = $('<div>').text(approver).html();
                    const safeReason = $('<div>').text(rejectionReason || '-').html();

                    if (typeof Swal === 'undefined') {
                        if (status === 'approved') {
                            if (confirm('Batalkan persetujuan (ACC) slip gaji ini?')) {
                                @this.call('unapproveItem');
                            }
                        }
                        return;
                    }

                    if (status === 'approved') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status: Slip Disetujui (ACC)',
                            width: '490px',
                            showCloseButton: true,
                            showConfirmButton: false,
                            showCancelButton: false,
                            customClass: {
                                popup: 'swal-actionsheet-popup',
                            },
                            html: `
                                <div class="text-center mb-3">
                                    <p class="mb-2" style="font-size: 14px; font-weight: 500; color: #1e293b;">
                                        Disetujui oleh <strong style="color: #0f172a;">${safeApprover}</strong> ${approvedAt ? '<span class="d-inline-block text-nowrap font-weight-normal" style="color: #475569;">(' + approvedAt + ')</span>' : ''}
                                    </p>
                                    <div class="py-2 px-3 mb-3 text-center" style="background-color: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 500; color: #1e293b;">
                                        <i class="fas fa-lock text-primary mr-1"></i> Slip gaji terkunci dalam mode <strong>hanya baca (read-only)</strong>.
                                    </div>
                                    <p class="mb-0" style="font-size: 13.5px; font-weight: 600; color: #334155;">Pilih tindakan persetujuan:</p>
                                </div>
                                <div class="d-flex align-items-center justify-content-center flex-nowrap mt-3" style="gap: 10px;">
                                    <button type="button" id="btn-swal-reject" class="btn btn-icon icon-left btn-danger flex-fill py-2 text-nowrap" style="font-size: 13.5px; font-weight: 600;">
                                        <i class="fas fa-times-circle"></i> Tolak / Minta Revisi
                                    </button>
                                    <button type="button" id="btn-swal-unapprove" class="btn btn-icon icon-left btn-warning flex-fill py-2 text-nowrap" style="font-size: 13.5px; font-weight: 600;">
                                        <i class="fas fa-undo"></i> Batalkan ACC
                                    </button>
                                </div>
                            `,
                            didOpen: () => {
                                $('#btn-swal-reject').on('click', () => {
                                    Swal.close();
                                    window.promptRejectItem();
                                });
                                $('#btn-swal-unapprove').on('click', () => {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Batalkan Persetujuan?',
                                        text: 'Status slip gaji akan dikembalikan menjadi Menunggu ACC (tanpa catatan revisi).',
                                        showCancelButton: true,
                                        confirmButtonText: 'Ya, Batalkan ACC',
                                        confirmButtonColor: '#ffa426',
                                        cancelButtonText: 'Kembali',
                                        cancelButtonColor: '#6c757d',
                                        reverseButtons: true
                                    }).then((confirmRes) => {
                                        if (confirmRes.isConfirmed) {
                                            @this.call('unapproveItem');
                                        }
                                    });
                                });
                            }
                        });
                    } else if (status === 'rejected') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Status: Perlu Revisi',
                            width: '490px',
                            showCloseButton: true,
                            showConfirmButton: false,
                            showCancelButton: false,
                            customClass: {
                                popup: 'swal-actionsheet-popup',
                            },
                            html: `
                                <div class="text-center mb-3">
                                    <p class="mb-2 font-weight-bold" style="font-size: 14px; color: #1e293b;">
                                        <i class="fas fa-clipboard-list text-warning mr-1"></i> Catatan revisi dari Direktur:
                                    </p>
                                    <div class="p-3 mb-3 text-left" style="background: #fff5f5; border: 1px solid #fecaca; border-left: 4px solid #ef4444; border-radius: 8px; font-size: 14px; font-weight: 600; color: #0f172a; line-height: 1.5;">
                                        "${safeReason}"
                                    </div>
                                    <p class="mb-0" style="font-size: 13.5px; font-weight: 600; color: #334155;">Pilih tindakan untuk slip gaji ini:</p>
                                </div>
                                <div class="d-flex align-items-center justify-content-center flex-nowrap mt-3" style="gap: 10px;">
                                    <button type="button" id="btn-swal-edit-reject" class="btn btn-icon icon-left btn-danger flex-fill py-2 text-nowrap" style="font-size: 13.5px; font-weight: 600;">
                                        <i class="fas fa-edit"></i> Ubah Catatan Revisi
                                    </button>
                                    <button type="button" id="btn-swal-approve" class="btn btn-icon icon-left btn-success flex-fill py-2 text-nowrap" style="font-size: 13.5px; font-weight: 600;">
                                        <i class="fas fa-check-circle"></i> Setujui (ACC Sekarang)
                                    </button>
                                </div>
                            `,
                            didOpen: () => {
                                $('#btn-swal-approve').on('click', () => {
                                    Swal.close();
                                    @this.call('approveItem');
                                });
                                $('#btn-swal-edit-reject').on('click', () => {
                                    Swal.close();
                                    window.promptRejectItem(rejectionReason);
                                });
                            }
                        });
                    } else {
                        // Pending
                        Swal.fire({
                            icon: 'question',
                            title: 'Persetujuan Slip Gaji',
                            width: '490px',
                            showCloseButton: true,
                            showConfirmButton: false,
                            showCancelButton: false,
                            customClass: {
                                popup: 'swal-actionsheet-popup',
                            },
                            html: `
                                <div class="text-center mb-3">
                                    <p class="mb-2" style="font-size: 14px; font-weight: 500; color: #1e293b;">
                                        Status saat ini: <span class="badge badge-warning" style="font-size: 12px; font-weight: 600; padding: 4px 8px;">Menunggu ACC</span>
                                    </p>
                                    <p class="mb-0" style="font-size: 13.5px; font-weight: 600; color: #334155;">Silakan pilih tindakan persetujuan untuk slip gaji ini:</p>
                                </div>
                                <div class="d-flex align-items-center justify-content-center flex-nowrap mt-3" style="gap: 10px;">
                                    <button type="button" id="btn-swal-reject" class="btn btn-icon icon-left btn-danger flex-fill py-2 text-nowrap" style="font-size: 13.5px; font-weight: 600;">
                                        <i class="fas fa-times-circle"></i> Tolak & Minta Revisi
                                    </button>
                                    <button type="button" id="btn-swal-approve" class="btn btn-icon icon-left btn-success flex-fill py-2 text-nowrap" style="font-size: 13.5px; font-weight: 600;">
                                        <i class="fas fa-check-circle"></i> Setujui (ACC Slip)
                                    </button>
                                </div>
                            `,
                            didOpen: () => {
                                $('#btn-swal-approve').on('click', () => {
                                    Swal.close();
                                    @this.call('approveItem');
                                });
                                $('#btn-swal-reject').on('click', () => {
                                    Swal.close();
                                    window.promptRejectItem();
                                });
                            }
                        });
                    }
                };

                window.promptRejectItem = function (defaultReason = '') {
                    if (typeof Swal === 'undefined') {
                        const reason = prompt('Catatan revisi / alasan penolakan:', defaultReason || '');
                        if (reason && reason.trim().length >= 3) {
                            @this.call('confirmRejectItem', reason.trim());
                        }
                        return;
                    }

                    const isEdit = !!defaultReason;
                    Swal.fire({
                        icon: 'warning',
                        title: isEdit ? 'Ubah Catatan Revisi' : 'Tolak & Minta Revisi',
                        html: '<p class="mb-2" style="font-size: 14px; font-weight: 500; color: #334155;">Tuliskan catatan perbaikan untuk Admin / HRD:</p>',
                        input: 'textarea',
                        inputValue: defaultReason || '',
                        inputPlaceholder: 'Tuliskan catatan perbaikan...',
                        inputAttributes: {
                            'aria-label': 'Tuliskan catatan perbaikan untuk Admin/HRD',
                            'style': 'height: 100px; font-size: 13.5px; border-radius: 8px;'
                        },
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-paper-plane mr-1"></i> ' + (isEdit ? 'Simpan Catatan' : 'Kirim Catatan'),
                        confirmButtonColor: '#fc544b',
                        cancelButtonText: 'Batal',
                        cancelButtonColor: '#6c757d',
                        reverseButtons: true,
                        showLoaderOnConfirm: true,
                        inputValidator: (value) => {
                            if (!value || value.trim().length < 3) {
                                return 'Catatan revisi wajib diisi minimal 3 karakter!';
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
                            @this.call('confirmRejectItem', result.value.trim());
                        }
                    });
                };

                if (typeof window.Livewire !== 'undefined') {
                    initManageItemScripts();
                    Livewire.on('prompt-reject-item', () => window.promptRejectItem());
                } else {
                    document.addEventListener('livewire:init', () => {
                        initManageItemScripts();
                        Livewire.on('prompt-reject-item', () => window.promptRejectItem());
                    });
                }
            })();
        </script>
    @endpush
</div>
