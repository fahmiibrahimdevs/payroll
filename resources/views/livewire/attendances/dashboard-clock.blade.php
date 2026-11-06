<div id="attendanceClockRoot" class="d-flex flex-column h-100" wire:key="attendance-dashboard-clock-root">
<link rel="stylesheet" href="{{ asset('assets/leaflet/leaflet.css') }}">
<script src="{{ asset('assets/leaflet/leaflet.js') }}"></script>

<style>
    /* Mobile / Flutter Receipt Orbit Hero Style for GPS Geofence Verification */
    .receipt-orbit-wrapper {
        position: relative;
        width: 68px;
        height: 68px;
        border-radius: 50%;
        border: 1.5px dashed #86efac;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        transition: all 0.25s ease-in-out;
    }
    .receipt-orbit-dot {
        position: absolute;
        width: 6px;
        height: 6px;
        background-color: #10b981;
        border-radius: 50%;
        right: -1px;
        top: 50%;
        transform: translateY(-50%);
        transition: all 0.25s ease-in-out;
    }
    .receipt-solid-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 14px rgba(16, 185, 129, 0.32);
        color: #ffffff;
        font-size: 20px;
        transition: all 0.25s ease-in-out;
    }
    .receipt-orbit-wrapper.orbit-success {
        border-color: #86efac;
    }
    .receipt-orbit-wrapper.orbit-success .receipt-orbit-dot {
        background-color: #10b981;
    }
    .receipt-orbit-wrapper.orbit-success .receipt-solid-circle {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 6px 14px rgba(16, 185, 129, 0.32);
    }
    .receipt-orbit-wrapper.orbit-warning {
        border-color: #fcd34d;
    }
    .receipt-orbit-wrapper.orbit-warning .receipt-orbit-dot {
        background-color: #f59e0b;
    }
    .receipt-orbit-wrapper.orbit-warning .receipt-solid-circle {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 6px 14px rgba(245, 158, 11, 0.32);
    }
    .receipt-orbit-wrapper.orbit-info {
        border-color: #93c5fd;
    }
    .receipt-orbit-wrapper.orbit-info .receipt-orbit-dot {
        background-color: #3b82f6;
    }
    .receipt-orbit-wrapper.orbit-info .receipt-solid-circle {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        box-shadow: 0 6px 14px rgba(59, 130, 246, 0.32);
    }
    .receipt-orbit-wrapper.orbit-danger {
        border-color: #fca5a5;
    }
    .receipt-orbit-wrapper.orbit-danger .receipt-orbit-dot {
        background-color: #ef4444;
    }
    .receipt-orbit-wrapper.orbit-danger .receipt-solid-circle {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        box-shadow: 0 6px 14px rgba(239, 68, 68, 0.32);
    }
    .receipt-title {
        font-size: 17px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.3px;
        line-height: 1.25;
        margin-bottom: 3px;
    }
    .receipt-subtitle {
        font-size: 12px;
        font-weight: 400;
        color: #64748b;
        line-height: 1.45;
        margin-bottom: 0;
        max-width: 460px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Styling for Leaflet Map in Web Clock Modal */
    #webClockMap {
        cursor: crosshair;
    }
    .user-gps-marker {
        position: relative;
        width: 16px;
        height: 16px;
        background: #3b82f6;
        border: 2.5px solid #ffffff;
        border-radius: 50%;
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.9);
    }
    .user-gps-marker::after {
        content: '';
        position: absolute;
        top: -7px;
        left: -7px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: 2px solid #3b82f6;
        animation: userGpsPulse 2s infinite ease-out;
        opacity: 0;
    }
    @keyframes userGpsPulse {
        0% { transform: scale(0.5); opacity: 1; }
        100% { transform: scale(1.6); opacity: 0; }
    }
    .office-gps-marker {
        width: 26px;
        height: 26px;
        background: #10b981;
        color: #ffffff;
        border: 2px solid #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        font-size: 11px;
    }
    .leaflet-bar a {
        background-color: rgba(15, 23, 42, 0.85) !important;
        color: #ffffff !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
        width: 26px !important;
        height: 26px !important;
        line-height: 26px !important;
    }
    .leaflet-bar a:hover {
        background-color: #0b52aa !important;
    }
    .leaflet-control-zoom {
        border: none !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important;
        border-radius: 6px !important;
        overflow: hidden;
    }
    .leaflet-control-attribution {
        background: rgba(15, 23, 42, 0.75) !important;
        color: #cbd5e1 !important;
        font-size: 9px !important;
        padding: 0 5px !important;
    }
    .leaflet-control-attribution a {
        color: #93c5fd !important;
    }

    /* Mobile Full-Screen to Top for Web Clock Modal */
    @media (max-width: 767.98px) {
        #webClockModal.modal {
            position: fixed !important;
            inset: 0 !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            height: 100dvh !important;
            max-height: 100dvh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            z-index: 1050 !important;
        }
        #webClockModal.modal:not(.show) {
            display: none !important;
        }
        #webClockModal.modal.show {
            display: block !important;
            justify-content: flex-start !important;
            align-items: stretch !important;
        }
        #webClockModal .modal-dialog,
        #webClockModal.modal.show .modal-dialog,
        #webClockModal.modal.fade .modal-dialog {
            position: fixed !important;
            inset: 0 !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            height: 100% !important;
            height: 100dvh !important;
            max-height: 100dvh !important;
            min-height: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            transform: none !important;
            justify-content: flex-start !important;
        }
        #webClockModal .modal-dialog::before,
        #webClockModal .modal-dialog-centered::before,
        #webClockModal .modal-header::before {
            display: none !important;
            height: 0 !important;
            content: none !important;
        }
        #webClockModal .modal-content,
        #webClockModal.modal.show .modal-content {
            position: absolute !important;
            inset: 0 !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            height: 100% !important;
            height: 100dvh !important;
            max-height: 100dvh !important;
            border: none !important;
            border-radius: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            box-shadow: none !important;
            padding-bottom: 0 !important;
            background: #ffffff !important;
        }
        #webClockModal .modal-header {
            position: relative !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            flex-shrink: 0 !important;
            border-radius: 0 !important;
            background: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 14px 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            z-index: 1060 !important;
            box-sizing: border-box !important;
        }
        #webClockModal .modal-body {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
            padding: 16px !important;
            box-sizing: border-box !important;
        }
        #webClockModal .modal-footer {
            position: relative !important;
            flex-shrink: 0 !important;
            border-radius: 0 !important;
            background: #ffffff !important;
            border-top: 1px solid #e2e8f0 !important;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
            padding: 12px 16px !important;
            padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px)) !important;
            z-index: 1060 !important;
            box-sizing: border-box !important;
        }
    }
    /* Modern Interactive Overtime Toggle Card */
    .overtime-toggle-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        cursor: pointer;
        user-select: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 10px !important;
    }
    .overtime-toggle-card:hover {
        border-color: #cbd5e1 !important;
        background: #f8fafc;
    }
    .overtime-toggle-card.active {
        background: #f0f7ff !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.12);
    }
    .overtime-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    .overtime-toggle-card.active .overtime-icon-box {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
    }
    .overtime-toggle-title {
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
    }
    .overtime-toggle-desc {
        font-size: 10px;
        color: #64748b;
        margin-top: 1px;
    }
    .overtime-switch-container {
        position: relative;
        width: 38px;
        height: 22px;
    }
    .overtime-switch-input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        margin: 0;
        padding: 0;
        pointer-events: none;
    }
    .overtime-switch-slider {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        border-radius: 22px;
        transition: background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .overtime-switch-knob {
        position: absolute;
        height: 18px;
        width: 18px;
        left: 2px;
        top: 2px;
        background-color: #ffffff;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .overtime-switch-input:checked + .overtime-switch-slider {
        background-color: #2563eb;
    }
    .overtime-switch-input:checked + .overtime-switch-slider .overtime-switch-knob {
        transform: translateX(16px);
    }
</style>

    <!-- Executive Attendance Card (Identik Gambar Referensi & Sesuai Standar Executive White Card) -->
    <div id="attendanceClockWidget" class="card shadow-sm bg-white mb-0 h-100" style="border: 1px solid #eef2f6; border-radius: 16px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05); background: #ffffff;">
        <div class="card-body d-flex flex-column justify-content-between align-items-center text-center" style="padding: 20px 20px !important;">
            <!-- Header Row: Title & Today Pill -->
            <div class="d-flex justify-content-between align-items-center w-100 mb-2">
                <span style="font-size: 15px; font-weight: 700; color: #0f172a; letter-spacing: -0.2px;">
                    Presensi Mandiri
                </span>
                <span class="badge border" style="background: #ffffff; border-color: #e2e8f0 !important; color: #475569; font-size: 11px; font-weight: 500; border-radius: 20px; padding: 4px 10px;" title="Status jadwal kerja karyawan hari ini">
                    <span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background-color: {{ $todayScheduleStatus === 'work' ? '#10b981' : '#f59e0b' }}; margin-right: 5px;"></span>
                    {{ $todayScheduleStatus === 'work' ? 'Jadwal Kerja' : 'Jadwal Libur (OFF)' }}
                </span>
            </div>

            <!-- Center Clock: Interactive Analog Clock with Realtime Ticking Hands -->
            <div class="position-relative" style="width: 110px; height: 110px; margin-top: 10px; margin-bottom: 10px;">
                <svg viewBox="0 0 100 100" style="width: 100%; height: 100%;">
                    <!-- Outer Dial Ring with Cyan/Blue Accent (Identik Referensi) -->
                    <circle cx="50" cy="50" r="46" fill="#ffffff" stroke="#0ea5e9" stroke-width="3" style="filter: drop-shadow(0 4px 10px rgba(14, 165, 233, 0.22));" />
                    <!-- Watermark Clock Numerals -->
                    <text x="50" y="21" font-size="10" font-weight="700" fill="#94a3b8" text-anchor="middle" dominant-baseline="middle">12</text>
                    <text x="82" y="51" font-size="9" font-weight="600" fill="#cbd5e1" text-anchor="middle" dominant-baseline="middle">3</text>
                    <text x="50" y="82" font-size="9" font-weight="600" fill="#cbd5e1" text-anchor="middle" dominant-baseline="middle">6</text>
                    <text x="18" y="51" font-size="9" font-weight="600" fill="#cbd5e1" text-anchor="middle" dominant-baseline="middle">9</text>
                    <!-- Center Tick Axis -->
                    <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="2, 6" />
                    <!-- Hour Hand -->
                    <line id="analogHourHand" x1="50" y1="50" x2="50" y2="28" stroke="#0f172a" stroke-width="3.2" stroke-linecap="round" />
                    <!-- Minute Hand -->
                    <line id="analogMinuteHand" x1="50" y1="50" x2="50" y2="19" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" />
                    <!-- Second Hand -->
                    <line id="analogSecondHand" x1="50" y1="54" x2="50" y2="15" stroke="#10b981" stroke-width="1.2" stroke-linecap="round" />
                    <!-- Center Pivot -->
                    <circle cx="50" cy="50" r="3.5" fill="#0f172a" />
                    <circle cx="50" cy="50" r="1.5" fill="#ffffff" />
                </svg>
            </div>

            <!-- Date & Big Digital Clock -->
            <div class="text-center" style="margin-bottom: 12px;">
                <div class="font-weight-600" style="color: #0b52aa; font-size: 13.5px; letter-spacing: -0.2px;">
                    {{ now()->locale('id')->translatedFormat('l, d/m/Y') }}
                </div>
                <div id="liveDigitalClock" style="font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: 0.5px; line-height: 1.2; margin: 3px 0 2px 0;">
                    {{ now()->format('H:i:s') }}
                </div>
                <div class="text-truncate" style="font-size: 11px; color: #64748b; margin-top: 1px; max-width: 220px;" title="{{ $isFreeLocation ? 'Bebas Lokasi' : ($allowedLocations[0]['name'] ?? 'Kantor Resmi') }}">
                    <i class="fas fa-map-marker-alt text-primary mr-1"></i> {{ $isFreeLocation ? 'Bebas Lokasi' : ($allowedLocations[0]['name'] ?? 'Kantor') }}
                </div>
            </div>

            <!-- Toggle Switch: Apakah hari ini lembur? (Modern Interactive Executive Card) -->
            <label for="isOvertimeSwitch" class="overtime-toggle-card w-100 d-flex align-items-center justify-content-between px-3 py-2 mb-2.5 {{ $isOvertime ? 'active' : '' }}">
                <div class="d-flex align-items-center text-left min-width-0 mr-2">
                    <div class="overtime-icon-box mr-2.5 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="fas fa-business-time"></i>
                    </div>
                    <div class="min-width-0">
                        <div class="overtime-toggle-title">
                            Apakah hari ini lembur?
                        </div>
                        <div class="overtime-toggle-desc text-truncate">
                            @if ($isOvertime)
                                <span class="text-primary font-weight-600">Mode lembur aktif</span>
                            @else
                                {{ $isHolidayOrWeekend ? 'Lembur di Hari Libur / Off' : 'Lembur setelah shift normal' }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="overtime-switch-container flex-shrink-0">
                    <input type="checkbox" id="isOvertimeSwitch" wire:model.live="isOvertime" class="overtime-switch-input">
                    <div class="overtime-switch-slider">
                        <div class="overtime-switch-knob"></div>
                    </div>
                </div>
            </label>

            <!-- Action Buttons -->
            @if ($isOvertime)
                <!-- MODE LEMBUR DIAKTIFKAN -->
                @if (! $isHolidayOrWeekend && ! $hasClockedOut)
                    <!-- Hari kerja biasa: wajib absen pulang reguler dulu -->
                    <div class="d-flex align-items-center w-100 mb-2" style="gap: 10px;">
                        @if ($hasClockedIn)
                            <button type="button" wire:click="openClockModal('clock_in')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0b52aa 0%, #1e40af 100%); border: none; border-radius: 11px; padding: 10px 12px; font-weight: 600; font-size: 13px;">
                                <i class="fas fa-check-circle mr-1.5"></i> Detail Masuk
                            </button>
                            <button type="button" wire:click="openClockModal('clock_out')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%); border: none; border-radius: 11px; padding: 10px 12px; font-weight: 600; font-size: 13px;">
                                <i class="fas fa-sign-out-alt mr-1.5"></i> Absen Pulang
                            </button>
                        @else
                            <button type="button" wire:click="openClockModal('clock_in')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0b52aa 0%, #2563eb 100%); border: none; border-radius: 11px; padding: 10px 12px; font-weight: 600; font-size: 13px;">
                                <i class="fas fa-sign-in-alt mr-1.5"></i> Absen Masuk
                            </button>
                            <button type="button" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-muted" style="background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 11px; padding: 10px 12px; font-weight: 600; font-size: 13px; cursor: not-allowed; opacity: 0.7;" disabled>
                                <i class="fas fa-lock mr-1.5 text-muted"></i> Absen Pulang
                            </button>
                        @endif
                    </div>
                    <div class="w-100 mb-2.5 py-1 px-2 text-center border" style="background: #ffffff; border-color: #fde68a !important; border-radius: 8px; font-size: 11px; color: #b45309; font-weight: 500;">
                        <i class="fas fa-info-circle mr-1"></i> Wajib <strong>Absen Pulang</strong> kerja normal sebelum mulai lembur.
                    </div>
                @else
                    <!-- Sudah Absen Pulang normal ATAU Hari Libur/Weekend: Tombol Lembur Aktif -->
                    <div class="w-100 mb-2">
                        @if (! $hasOvertimeIn)
                            <button type="button" wire:click="openClockModal('overtime_in')" class="btn btn-sm w-100 d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0b52aa 0%, #1e40af 100%); border: none; border-radius: 11px; padding: 11px 14px; font-weight: 600; font-size: 13px;">
                                <i class="fas fa-stopwatch mr-1.5"></i> Mulai Lembur
                            </button>
                        @elseif (! $hasOvertimeOut)
                            <div class="d-flex align-items-center w-100" style="gap: 8px;">
                                <button type="button" wire:click="openClockModal('overtime_in')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0b52aa 0%, #1e40af 100%); border: none; border-radius: 11px; padding: 10px 8px; font-weight: 600; font-size: 12.5px;">
                                    <i class="fas fa-check-circle mr-1"></i> Detail Mulai
                                </button>
                                <button type="button" wire:click="openClockModal('overtime_out')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none; border-radius: 11px; padding: 10px 8px; font-weight: 600; font-size: 12.5px;">
                                    <i class="fas fa-flag-checkered mr-1"></i> Selesai Lembur
                                </button>
                            </div>
                        @else
                            <div class="d-flex align-items-center w-100" style="gap: 8px;">
                                <button type="button" wire:click="openClockModal('overtime_in')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0b52aa 0%, #1e40af 100%); border: none; border-radius: 11px; padding: 10px 8px; font-weight: 600; font-size: 12px;">
                                    <i class="fas fa-check-circle mr-1"></i> Mulai: {{ $overtimeInTime }}
                                </button>
                                <button type="button" wire:click="openClockModal('overtime_out')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none; border-radius: 11px; padding: 10px 8px; font-weight: 600; font-size: 12px;">
                                    <i class="fas fa-check-circle mr-1"></i> Selesai: {{ $overtimeOutTime }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            @else
                <!-- MODE NORMAL (BUKAN LEMBUR) -->
                <div class="d-flex align-items-center w-100 mb-2.5" style="gap: 10px;">
                    <!-- Tombol Check In -->
                    @if ($hasClockedIn)
                        <button type="button" wire:click="openClockModal('clock_in')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0b52aa 0%, #1e40af 100%); border: none; border-radius: 11px; padding: 10px 12px; font-weight: 600; font-size: 13px;" title="Klik untuk melihat foto dan lokasi presensi masuk">
                            <i class="fas fa-check-circle mr-1.5"></i> Detail Masuk
                        </button>
                    @else
                        <button type="button" wire:click="openClockModal('clock_in')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0b52aa 0%, #2563eb 100%); border: none; border-radius: 11px; padding: 10px 12px; font-weight: 600; font-size: 13px;">
                            <i class="fas fa-sign-in-alt mr-1.5"></i> Absen Masuk
                        </button>
                    @endif

                    <!-- Tombol Check Out -->
                    @if ($hasClockedOut)
                        <button type="button" wire:click="openClockModal('clock_out')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none; border-radius: 11px; padding: 10px 12px; font-weight: 600; font-size: 13px;" title="Klik untuk melihat foto dan lokasi presensi pulang">
                            <i class="fas fa-check-circle mr-1.5"></i> Detail Pulang
                        </button>
                    @elseif ($hasClockedIn)
                        <button type="button" wire:click="openClockModal('clock_out')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-white" style="background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%); border: none; border-radius: 11px; padding: 10px 12px; font-weight: 600; font-size: 13px;">
                            <i class="fas fa-sign-out-alt mr-1.5"></i> Absen Pulang
                        </button>
                    @else
                        <button type="button" wire:click="openClockModal('clock_out')" class="btn btn-sm flex-fill d-flex align-items-center justify-content-center shadow-sm text-muted" style="background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 11px; padding: 10px 12px; font-weight: 600; font-size: 13px; cursor: not-allowed; opacity: 0.7;" title="Lakukan absen masuk terlebih dahulu">
                            <i class="fas fa-lock mr-1.5 text-muted"></i> Absen Pulang
                        </button>
                    @endif
                </div>
            @endif

            <!-- Bottom Timestamp Boxes -->
            @if ($isOvertime)
                <div class="w-100" style="display: flex; flex-direction: column; gap: 8px;">
                    @if (! $isHolidayOrWeekend)
                        <!-- Row 1: Regular Jam Masuk & Jam Pulang -->
                        <div class="d-flex align-items-center w-100" style="gap: 8px;">
                            <div class="flex-fill bg-white text-center" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 7px 6px;">
                                <div style="font-size: 9px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                    JAM MASUK
                                </div>
                                <div style="font-size: 12.5px; font-weight: 700; color: #0b52aa; margin-top: 1px;">
                                    {{ $clockInTime }}
                                </div>
                            </div>
                            <div class="flex-fill bg-white text-center" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 7px 6px;">
                                <div style="font-size: 9px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                    JAM PULANG
                                </div>
                                <div style="font-size: 12.5px; font-weight: 700; color: #0284c7; margin-top: 1px;">
                                    {{ $clockOutTime }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Row 2: Overtime Jam Mulai & Jam Selesai -->
                    <div class="d-flex align-items-center w-100" style="gap: 8px;">
                        <div class="flex-fill bg-white text-center" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 7px 6px;">
                            <div style="font-size: 9px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                MULAI LEMBUR
                            </div>
                            <div style="font-size: 12.5px; font-weight: 700; color: #0b52aa; margin-top: 1px;">
                                {{ $overtimeInTime }}
                            </div>
                        </div>
                        <div class="flex-fill bg-white text-center" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 7px 6px;">
                            <div style="font-size: 9px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                SELESAI LEMBUR
                            </div>
                            <div style="font-size: 12.5px; font-weight: 700; color: #d97706; margin-top: 1px;">
                                {{ $overtimeOutTime }}
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="d-flex align-items-center w-100" style="gap: 10px;">
                    <div class="flex-fill bg-white text-center" style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 8px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);">
                        <div style="font-size: 9.5px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                            JAM MASUK
                        </div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #0b52aa; margin-top: 2px;">
                            {{ $clockInTime }}
                        </div>
                    </div>
                    <div class="flex-fill bg-white text-center" style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 8px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);">
                        <div style="font-size: 9.5px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                            JAM PULANG
                        </div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #0284c7; margin-top: 2px;">
                            {{ $clockOutTime }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Camera & GPS Geofence Attendance (Standard Stisla Bawaan Modal) -->
    <div wire:ignore class="modal fade" id="webClockModal" role="dialog" aria-labelledby="webClockModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
                <div class="modal-header bg-white border-bottom py-2.5 px-3 px-sm-4 w-100" style="display: flex !important; align-items: center !important; justify-content: space-between !important;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-camera text-primary mr-2" style="font-size: 15px;"></i>
                        <h5 class="modal-title text-dark m-0" id="webClockModalLabel" style="font-weight: 700; font-size: 15px; letter-spacing: -0.2px;">
                            {{ match($clockType) {
                                'clock_out' => 'Presensi Pulang (Selfie + GPS)',
                                'overtime_in' => 'Presensi Mulai Lembur (Selfie + GPS)',
                                'overtime_out' => 'Presensi Selesai Lembur (Selfie + GPS)',
                                default => 'Presensi Masuk (Selfie + GPS)',
                            } }}
                        </h5>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light text-muted" data-dismiss="modal" aria-label="Close" onclick="window.webClockCloseModal()" style="border-radius: 50%; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; border: 1px solid #e2e8f0; background: #f8fafc; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body py-3 px-4 bg-white" style="max-height: calc(100vh - 160px); overflow-y: auto;">
                    <!-- Centered Receipt Hero: Dashed Orbit Ring + Heading + Subtitle -->
                    <div id="gpsStatusContainer" class="text-center pt-1 mb-3">
                        <div class="receipt-orbit-wrapper orbit-info" id="gpsOrbitWrapper">
                            <div class="receipt-orbit-dot"></div>
                            <div class="receipt-solid-circle">
                                <i id="gpsStatusIcon" class="fas fa-satellite-dish"></i>
                            </div>
                        </div>
                        <h5 class="receipt-title" id="gpsStatusTitle">
                            Memverifikasi Titik Lokasi
                        </h5>
                        <p class="receipt-subtitle px-2" id="gpsDistanceInfo">
                            Menunggu pembacaan titik koordinat dari sensor browser/HP...
                        </p>
                    </div>

                    <!-- 1. Area Kamera Selfie Presensi -->
                    <div class="form-group mb-3 text-center">
                        <div class="mb-3 d-block text-left">
                            <h2 class="section-title mt-0 mb-1" id="selfieSectionTitle">Foto Selfie Karyawan</h2>
                            <p class="section-lead ml-7!" id="selfieSectionLead">Pastikan wajah terlihat jelas dan berada di dalam area frame kamera dengan pencahayaan yang cukup.</p>
                        </div>
                        <div class="position-relative mx-auto" style="width: 100%; max-width: 280px; aspect-ratio: 1 / 1; background: #0f172a; border-radius: 12px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                            <video id="webClockVideo" autoplay playsinline muted style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                            <canvas id="webClockCanvas" style="display: none;"></canvas>
                            <img id="webClockPhotoPreview" style="display: none; width: 100%; height: 100%; object-fit: cover;" alt="Selfie Preview" onerror="this.onerror=null; this.src='/assets/img/avatar/avatar-1.png';">

                            <!-- Watermark Overlay (Timemark Camera Style) -->
                            <div id="webClockWatermarkOverlay" class="position-absolute text-left" style="bottom: 0; left: 0; right: 0; top: 0; pointer-events: none; padding: 10px; display: flex; flex-direction: column; justify-content: flex-end; background: linear-gradient(180deg, rgba(0,0,0,0) 55%, rgba(0,0,0,0.35) 75%, rgba(0,0,0,0.8) 100%); z-index: 10;">
                                <!-- Pill Badge: [ABSEN MASUK / ABSEN PULANG] [Time] -->
                                <div class="d-inline-flex align-items-stretch shadow-sm mb-1" style="background: #ffffff; border-radius: 4px; overflow: hidden; align-self: flex-start;">
                                    <span id="overlayTypeBadge" style="background: #f59e0b; color: #000000; font-weight: 800; font-size: 11px; padding: 2px 8px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center;">{{ $clockType === 'clock_out' ? 'ABSEN PULANG' : 'ABSEN MASUK' }}</span>
                                    <span id="overlayTimeBadge" style="color: #0b52aa; font-weight: 800; font-size: 13px; padding: 2px 9px; display: inline-flex; align-items: center; letter-spacing: -0.2px;">{{ now()->format('H:i') }}</span>
                                </div>

                                <!-- Left Accent Border with Date & Address -->
                                <div style="border-left: 2.5px solid #f59e0b; padding-left: 6px; margin-bottom: 4px; line-height: 1.25;">
                                    <div id="overlayDateText" style="color: #ffffff; font-weight: 700; font-size: 11px; text-shadow: 0 1px 3px rgba(0,0,0,0.9);">
                                        {{ now()->locale('id')->isoFormat('ddd, DD MMM YYYY') }}
                                    </div>
                                    <div id="overlayAddressText" style="color: #ffffff; font-size: 9.5px; font-weight: 400; text-shadow: 0 1px 3px rgba(0,0,0,0.9); margin-top: 2px; max-width: 220px; word-break: break-word;">
                                        Mencari alamat lokasi GPS...
                                    </div>
                                </div>

                                <!-- Bottom Verified & Branding Row -->
                                <div class="d-flex justify-content-between align-items-end w-100">
                                    <div style="color: #ffffff; font-size: 8.5px; text-shadow: 0 1px 3px rgba(0,0,0,0.9); display: flex; align-items: center;">
                                        <i class="far fa-check-circle mr-1" style="font-size: 9px; color: #ffffff;"></i>
                                        <span>Waktu terverifikasi oleh Sintesa</span>
                                    </div>
                                    <div style="text-align: right; line-height: 1;">
                                        <div style="font-size: 7px; color: #ffffff; text-shadow: 0 1px 2px rgba(0,0,0,0.9);">Photo by</div>
                                        <div style="font-size: 11.5px; font-weight: 800; color: #f59e0b; text-shadow: 0 1px 3px rgba(0,0,0,0.9); letter-spacing: -0.3px;">Sintesa</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Kontrol Foto Selfie (Ambil Foto & Ulangi Foto) -->
                        <div id="webClockPhotoControls" class="justify-content-center align-items-center mt-2.5 mb-1" style="display: flex; gap: 10px;">
                            <!-- Tombol Ambil Foto -->
                            <button type="button" id="webClockCaptureBtn" onclick="window.webClockTriggerCapture()" class="btn btn-primary btn-sm px-3.5 py-1.5 shadow-sm" style="border-radius: 20px; font-weight: 600; font-size: 12.5px; min-width: 120px; background: linear-gradient(135deg, #0b52aa 0%, #1e40af 100%); border: none; transition: all 0.2s ease;">
                                <i class="fas fa-camera mr-1.5"></i> Ambil Foto
                            </button>

                            <!-- Tombol Ulangi Foto -->
                            <button type="button" id="webClockRetakeBtn" onclick="window.webClockRetakePhoto()" class="btn btn-outline-secondary btn-sm px-3.5 py-1.5 shadow-sm" style="border-radius: 20px; font-weight: 600; font-size: 12.5px; min-width: 120px; background: #f8fafc; border: 1.5px solid #e2e8f0; color: #94a3b8; opacity: 0.5; cursor: not-allowed; transition: all 0.2s ease;" disabled>
                                <i class="fas fa-redo-alt mr-1.5"></i> Ulangi Foto
                            </button>
                        </div>
                    </div>

                    <!-- 2. Area Lokasi & Geofence -->
                    <div class="form-group mb-2">
                        <!-- Peta Satelit Geofence -->
                        <div class="position-relative" style="width: 100%; height: 160px; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #0f172a;">
                            <div wire:ignore id="webClockMap" style="width: 100%; height: 100%;"></div>

                            <!-- Layer Switcher -->
                            <div class="position-absolute" style="top: 8px; right: 8px; z-index: 1000; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px); border-radius: 6px; padding: 3px 6px; display: flex; gap: 4px;">
                                <button type="button" id="btnMapSatellite" onclick="window.setMapLayer('satellite')" class="btn btn-xs py-0.5 px-2 text-white font-weight-600" style="font-size: 10px; border-radius: 4px; background: #0b52aa; border: none;">
                                    <i class="fas fa-satellite mr-1"></i> Satelit
                                </button>
                                <button type="button" id="btnMapStreet" onclick="window.setMapLayer('street')" class="btn btn-xs py-0.5 px-2 text-white font-weight-500" style="font-size: 10px; border-radius: 4px; background: transparent; border: none;">
                                    <i class="fas fa-map mr-1"></i> Jalan
                                </button>
                            </div>

                            <!-- Bottom Legend -->
                            <div class="position-absolute" style="bottom: 8px; left: 8px; z-index: 1000; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px); border-radius: 6px; padding: 3px 8px; color: #ffffff; font-size: 10px; pointer-events: none;">
                                <span class="mr-2"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#3b82f6; border:1px solid #fff; margin-right:4px;"></span> Posisi Anda</span>
                                <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981; border:1px solid #fff; margin-right:4px;"></span> Kantor / Site</span>
                            </div>
                        </div>
                    </div>

                    <!-- Input Alasan / Catatan jika di Luar Radius -->
                    <div id="outOfRadiusNotesGroup" style="display: none;" class="form-group mb-0">
                        <label for="webClockNotes" style="font-size: 12.5px; font-weight: 500; color: #1e293b;">
                            Alasan / Lokasi Kunjungan Site <span class="text-danger">*</span>
                        </label>
                        <textarea id="webClockNotes" class="form-control" rows="2" placeholder="Tuliskan keterangan (mis. Kunjungan maintenance mesin klien di PT ABC)..." style="font-size: 13px; height: 70px;"></textarea>
                        <small style="font-size: 11px; color: #475569;">
                            <i class="fas fa-info-circle text-warning mr-0.5"></i> Karena Anda berada di luar radius kantor resmi, presensi akan diteruskan ke Admin untuk peninjauan.
                        </small>
                    </div>

                    <!-- Tampilan Read-Only Catatan Alasan (Jika Sudah Tercatat) -->
                    <div id="readOnlyNotesGroup" style="display: none;" class="mt-2 p-2.5 bg-light rounded border text-left">
                        <div class="d-flex align-items-center mb-1" style="font-size: 12px; font-weight: 600; color: #334155;">
                            <i class="fas fa-comment-alt text-primary mr-1.5"></i> Catatan Alasan Kunjungan:
                        </div>
                        <div id="readOnlyNotesText" style="font-size: 12px; color: #475569; line-height: 1.45;"></div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top py-2.5 px-3 px-sm-4 w-100">
                    <!-- Tombol Simpan Foto & Absen (Mode Absen Baru) -->
                    <button type="button" id="webClockSubmitBtn" onclick="window.webClockProcessAttendance()" class="btn btn-primary btn-block w-100 py-2.5 shadow-sm" style="border-radius: 8px; font-weight: 600; font-size: 14px; opacity: 0.55; cursor: not-allowed; transition: all 0.2s ease;" disabled>
                        <span id="webClockSubmitBtnSpinner" style="display: none;"><i class="fas fa-spinner fa-spin mr-1"></i> Memproses...</span>
                        <span id="webClockSubmitBtnText"><i class="fas fa-check-circle mr-1.5"></i> Simpan Foto & Absen</span>
                    </button>

                    <!-- Tombol Tutup (Mode Read-Only) -->
                    <button type="button" id="webClockCloseBtnFooter" data-dismiss="modal" onclick="window.webClockCloseModal()" class="btn btn-secondary btn-block w-100 py-2.5 shadow-sm" style="display: none; border-radius: 8px; font-weight: 600; font-size: 14px; background: #64748b; border: none;">
                        <i class="fas fa-times mr-1.5"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // State Kamera & GPS
    let isModalReadOnly = false;
    let cameraStream = null;
    let currentPhotoBase64 = null;
    let currentLatitude = null;
    let currentLongitude = null;
    let currentAccuracy = null;
    let isInsideRadius = false;
    let currentClockType = 'clock_in';
    let currentAllowedLocations = [];
    let isFreeLocationEmployee = false;
    let geoWatchId = null;
    let isCameraReady = false;

    // 1. Live Analog Clock Ticker
    function updateAnalogClock() {
        const now = new Date();
        const sec = now.getSeconds();
        const min = now.getMinutes();
        const hr = now.getHours();

        const secDeg = (sec / 60) * 360;
        const minDeg = ((min + sec / 60) / 60) * 360;
        const hrDeg = (((hr % 12) + min / 60) / 12) * 360;

        const sHand = document.getElementById('analogSecondHand');
        const mHand = document.getElementById('analogMinuteHand');
        const hHand = document.getElementById('analogHourHand');

        if (sHand) sHand.setAttribute('transform', `rotate(${secDeg} 50 50)`);
        if (mHand) mHand.setAttribute('transform', `rotate(${minDeg} 50 50)`);
        if (hHand) hHand.setAttribute('transform', `rotate(${hrDeg} 50 50)`);

        const liveClockEl = document.getElementById('liveDigitalClock');
        if (liveClockEl) {
            const hStr = String(hr).padStart(2, '0');
            const mStr = String(min).padStart(2, '0');
            const sStr = String(sec).padStart(2, '0');
            liveClockEl.innerText = `${hStr}:${mStr}:${sStr}`;
        }

        if (!isModalReadOnly) {
            const overlay = document.getElementById('overlayTimeText');
            if (overlay) {
                const hStr = String(hr).padStart(2, '0');
                const mStr = String(min).padStart(2, '0');
                const sStr = String(sec).padStart(2, '0');
                overlay.innerText = `${hStr}:${mStr}:${sStr} WIB`;
            }
            const timeBadge = document.getElementById('overlayTimeBadge');
            if (timeBadge) {
                const hStr = String(hr).padStart(2, '0');
                const mStr = String(min).padStart(2, '0');
                timeBadge.innerText = `${hStr}:${mStr}`;
            }
        }
    }

    setInterval(updateAnalogClock, 1000);
    updateAnalogClock();

    // Leaflet Map Variables
    let clockMap = null;
    let userMarker = null;
    let officeMarker = null;
    let geofenceCircle = null;
    let activeTileLayer = null;
    let streetTileLayer = null;
    let satelliteTileLayer = null;

    // Kalkulasi Jarak Haversine (meter)
    function calculateHaversine(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return Math.round(R * c);
    }

    // Inisialisasi Map Leaflet Interaktif
    function initWebClockMap(lat, lng) {
        if (typeof L === 'undefined') {
            console.warn('Leaflet is not loaded.');
            return;
        }

        const mapContainer = document.getElementById('webClockMap');
        if (!mapContainer) return;

        // Validasi apakah Leaflet container masih utuh di DOM dan memiliki .leaflet-pane
        const hasValidPanes = mapContainer.querySelector('.leaflet-pane') !== null;
        if (clockMap && (clockMap.getContainer() !== mapContainer || !hasValidPanes)) {
            try {
                clockMap.remove();
            } catch (e) {
                console.warn('Error removing invalid map instance:', e);
            }
            clockMap = null;
            userMarker = null;
            officeMarker = null;
            geofenceCircle = null;
            activeTileLayer = null;
            delete mapContainer._leaflet_id;
        }

        if (!clockMap) {
            delete mapContainer._leaflet_id;

            satelliteTileLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                attribution: 'Google Maps Satellite'
            });

            streetTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: 'OpenStreetMap'
            });

            clockMap = L.map('webClockMap', {
                center: [lat, lng],
                zoom: 17,
                zoomControl: false
            });

            L.control.zoom({ position: 'topleft' }).addTo(clockMap);

            // Default layer: Satellite
            activeTileLayer = satelliteTileLayer;
            activeTileLayer.addTo(clockMap);
        } else {
            clockMap.setView([lat, lng], clockMap.getZoom());
        }

        // Custom divIcon untuk User Marker (pulsing blue dot #3b82f6 dengan border putih)
        const userIcon = L.divIcon({
            className: 'custom-div-user-marker',
            html: '<div class="user-gps-marker"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });

        if (currentLatitude !== null && currentLongitude !== null) {
            if (!userMarker) {
                userMarker = L.marker([currentLatitude, currentLongitude], { icon: userIcon, zIndexOffset: 1000 }).addTo(clockMap);
                userMarker.bindTooltip('Posisi Anda', { direction: 'top', offset: [0, -8] });
            } else {
                userMarker.setLatLng([currentLatitude, currentLongitude]);
                if (!clockMap.hasLayer(userMarker)) {
                    userMarker.addTo(clockMap);
                }
            }
        } else if (userMarker) {
            clockMap.removeLayer(userMarker);
            userMarker = null;
        }

        // Office Marker & Geofence Radius
        updateMapOfficeAndGeofence();
    }

    // Update marker kantor, geofence circle, dan visual bounds
    function updateMapOfficeAndGeofence() {
        if (!clockMap || typeof L === 'undefined') return;

        let officeLoc = null;
        if (currentAllowedLocations && currentAllowedLocations.length > 0) {
            if (currentLatitude !== null && currentLongitude !== null) {
                let minD = Infinity;
                currentAllowedLocations.forEach(loc => {
                    const d = calculateHaversine(currentLatitude, currentLongitude, loc.latitude, loc.longitude);
                    if (d < minD) {
                        minD = d;
                        officeLoc = loc;
                    }
                });
            }
            if (!officeLoc) {
                officeLoc = currentAllowedLocations[0];
            }
        }

        if (officeLoc) {
            const officeIcon = L.divIcon({
                className: 'custom-div-office-marker',
                html: '<div class="office-gps-marker"><i class="fas fa-building"></i></div>',
                iconSize: [26, 26],
                iconAnchor: [13, 13]
            });

            if (!officeMarker) {
                officeMarker = L.marker([officeLoc.latitude, officeLoc.longitude], { icon: officeIcon }).addTo(clockMap);
            } else {
                officeMarker.setLatLng([officeLoc.latitude, officeLoc.longitude]);
            }
            officeMarker.bindTooltip(officeLoc.name || 'Kantor / Site', { direction: 'top', offset: [0, -12] });

            const circleColor = isInsideRadius ? '#10b981' : '#f59e0b';
            const radius = officeLoc.radius_meters || 50;

            if (!geofenceCircle) {
                geofenceCircle = L.circle([officeLoc.latitude, officeLoc.longitude], {
                    radius: radius,
                    color: circleColor,
                    fillColor: circleColor,
                    fillOpacity: 0.22,
                    weight: 2
                }).addTo(clockMap);
            } else {
                geofenceCircle.setLatLng([officeLoc.latitude, officeLoc.longitude]);
                geofenceCircle.setRadius(radius);
                geofenceCircle.setStyle({
                    color: circleColor,
                    fillColor: circleColor
                });
            }

            if (currentLatitude !== null && currentLongitude !== null) {
                const latDiff = Math.abs(currentLatitude - officeLoc.latitude);
                const lngDiff = Math.abs(currentLongitude - officeLoc.longitude);
                if (latDiff < 0.0001 && lngDiff < 0.0001) {
                    clockMap.setView([currentLatitude, currentLongitude], 17);
                } else {
                    const bounds = L.latLngBounds([
                        [currentLatitude, currentLongitude],
                        [officeLoc.latitude, officeLoc.longitude]
                    ]);
                    clockMap.fitBounds(bounds, { padding: [35, 35], maxZoom: 18 });
                }
            }
        } else {
            if (officeMarker) {
                clockMap.removeLayer(officeMarker);
                officeMarker = null;
            }
            if (geofenceCircle) {
                clockMap.removeLayer(geofenceCircle);
                geofenceCircle = null;
            }
            if (currentLatitude !== null && currentLongitude !== null) {
                clockMap.setView([currentLatitude, currentLongitude], 17);
            }
        }
    }

    // Switcher Layer Peta Satelit / Peta Jalan
    window.setMapLayer = function (type) {
        if (!clockMap || typeof L === 'undefined') return;

        const btnSat = document.getElementById('btnMapSatellite');
        const btnStr = document.getElementById('btnMapStreet');

        if (type === 'satellite') {
            if (activeTileLayer && clockMap.hasLayer(activeTileLayer)) {
                clockMap.removeLayer(activeTileLayer);
            }
            activeTileLayer = satelliteTileLayer;
            activeTileLayer.addTo(clockMap);

            if (btnSat) {
                btnSat.style.background = '#0b52aa';
                btnSat.classList.remove('font-weight-500');
                btnSat.classList.add('font-weight-600');
            }
            if (btnStr) {
                btnStr.style.background = 'transparent';
                btnStr.classList.remove('font-weight-600');
                btnStr.classList.add('font-weight-500');
            }
        } else {
            if (activeTileLayer && clockMap.hasLayer(activeTileLayer)) {
                clockMap.removeLayer(activeTileLayer);
            }
            activeTileLayer = streetTileLayer;
            activeTileLayer.addTo(clockMap);

            if (btnSat) {
                btnSat.style.background = 'transparent';
                btnSat.classList.remove('font-weight-600');
                btnSat.classList.add('font-weight-500');
            }
            if (btnStr) {
                btnStr.style.background = '#0b52aa';
                btnStr.classList.remove('font-weight-500');
                btnStr.classList.add('font-weight-600');
            }
        }
    };

    // Start Camera Stream
    async function startCamera() {
        const video = document.getElementById('webClockVideo');
        const photoPreview = document.getElementById('webClockPhotoPreview');

        if (photoPreview) photoPreview.style.display = 'none';
        if (video) video.style.display = 'block';

        currentPhotoBase64 = null;
        isCameraReady = false;
        setPhotoControlsState(false);
        updateSubmitButtonState();

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 640 },
                    height: { ideal: 640 },
                    aspectRatio: { ideal: 1.0 }
                },
                audio: false
            });
            if (video) {
                video.srcObject = cameraStream;
                video.onloadedmetadata = function () {
                    video.play();
                    isCameraReady = true;
                    setPhotoControlsState(false);
                    updateSubmitButtonState();
                };
                video.onplay = function () {
                    isCameraReady = true;
                    setPhotoControlsState(false);
                    updateSubmitButtonState();
                };
            }
        } catch (err) {
            console.error('Camera access error:', err);
            isCameraReady = false;
            fallbackCameraFeed();
            updateSubmitButtonState();
        }
    }

    // State tombol kontrol foto (Ambil Foto vs Ulangi Foto)
    function setPhotoControlsState(hasPhoto) {
        const captureBtn = document.getElementById('webClockCaptureBtn');
        const retakeBtn = document.getElementById('webClockRetakeBtn');
        if (!captureBtn || !retakeBtn) return;

        if (hasPhoto) {
            captureBtn.disabled = true;
            captureBtn.style.opacity = '0.45';
            captureBtn.style.cursor = 'not-allowed';
            captureBtn.style.background = '#94a3b8';

            retakeBtn.disabled = false;
            retakeBtn.style.opacity = '1';
            retakeBtn.style.cursor = 'pointer';
            retakeBtn.style.background = '#ffffff';
            retakeBtn.style.borderColor = '#f59e0b';
            retakeBtn.style.color = '#d97706';
        } else {
            captureBtn.disabled = !isCameraReady;
            captureBtn.style.opacity = isCameraReady ? '1' : '0.6';
            captureBtn.style.cursor = isCameraReady ? 'pointer' : 'not-allowed';
            captureBtn.style.background = 'linear-gradient(135deg, #0b52aa 0%, #1e40af 100%)';

            retakeBtn.disabled = true;
            retakeBtn.style.opacity = '0.45';
            retakeBtn.style.cursor = 'not-allowed';
            retakeBtn.style.background = '#f8fafc';
            retakeBtn.style.borderColor = '#e2e8f0';
            retakeBtn.style.color = '#94a3b8';
        }
    }

    // Stop Camera Stream
    window.webClockStopCamera = function () {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        if (geoWatchId !== null) {
            navigator.geolocation.clearWatch(geoWatchId);
            geoWatchId = null;
        }
        isCameraReady = false;
        updateSubmitButtonState();
    };

    // Helper menggambar rounded rectangle di Canvas 2D
    function drawCanvasRoundRect(ctx, x, y, w, h, r) {
        if (w < 2 * r) r = w / 2;
        if (h < 2 * r) r = h / 2;
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    // Format alamat Indonesia yang presisi (Jalan, RT/RW, Kelurahan, Kecamatan, Kota, dsb.)
    function formatIndonesianAddress(addr, displayName) {
        if (!addr) {
            if (displayName) {
                const parts = displayName.split(',').map(s => s.trim()).filter(Boolean);
                return [parts.slice(0, 2).join(', '), parts.slice(2, 5).join(', ')];
            }
            return ['Lokasi Presensi', ''];
        }

        // 1. Line 1: Jalan / Bangunan / No / RT / RW
        const road = addr.road || addr.street || addr.pedestrian || addr.living_street || addr.highway || addr.path || '';
        const houseNo = addr.house_number ? 'No. ' + addr.house_number : '';
        const block = addr.city_block || addr.neighbourhood || addr.residential || addr.quarter || '';
        const building = addr.building || addr.amenity || addr.office || '';

        let l1 = [];
        if (building && !road.toLowerCase().includes(building.toLowerCase())) l1.push(building);
        if (road) l1.push(road);
        if (houseNo) l1.push(houseNo);
        if (block && block !== road) l1.push(block);
        let line1 = l1.join(', ');

        // 2. Line 2: Kelurahan, Kecamatan, Kota, Provinsi, Kode Pos
        const kelurahan = addr.village || addr.kelurahan || addr.hamlet || '';
        let kecamatan = addr.suburb || addr.municipality || addr.district || '';
        if (kecamatan && !kecamatan.toLowerCase().startsWith('kec.') && !kecamatan.toLowerCase().startsWith('kecamatan')) {
            kecamatan = 'Kec. ' + kecamatan;
        }

        let kota = addr.city_district || addr.city || addr.town || addr.county || '';
        if (kota.toLowerCase().includes('jakarta timur')) kota = 'Kota Jakarta Timur';
        else if (kota.toLowerCase().includes('jakarta barat')) kota = 'Kota Jakarta Barat';
        else if (kota.toLowerCase().includes('jakarta selatan')) kota = 'Kota Jakarta Selatan';
        else if (kota.toLowerCase().includes('jakarta pusat')) kota = 'Kota Jakarta Pusat';
        else if (kota.toLowerCase().includes('jakarta utara')) kota = 'Kota Jakarta Utara';
        else if (kota.toLowerCase().includes('subang') && !kota.toLowerCase().includes('kab.')) kota = 'Kab. Subang';
        else if (kota.toLowerCase().includes('bekasi') && !kota.toLowerCase().includes('kota') && !kota.toLowerCase().includes('kab.')) kota = 'Kota Bekasi';

        let provinsi = addr.state || '';
        if (provinsi.includes('Daerah Khusus Ibukota') || provinsi.includes('DKI')) {
            provinsi = 'DKI Jakarta';
        }

        const kodePos = addr.postcode || '';

        let l2 = [];
        if (kelurahan) l2.push(kelurahan);
        if (kecamatan && kecamatan !== kelurahan) l2.push(kecamatan);
        if (kota && kota !== kecamatan) l2.push(kota);
        if (provinsi && provinsi !== kota) l2.push(provinsi);
        if (kodePos) l2.push(kodePos);

        let line2 = l2.join(', ');

        if (!line1 && line2) {
            const p = line2.split(', ');
            line1 = p.slice(0, 2).join(', ');
            line2 = p.slice(2).join(', ');
        }

        return [line1, line2].filter(Boolean);
    }

    let currentAddressLines = ['Mencari alamat lokasi...'];
    let lastGeocodedLat = null;
    let lastGeocodedLng = null;

    async function reverseGeocodeAddress(lat, lng) {
        if (lastGeocodedLat !== null && Math.abs(lat - lastGeocodedLat) < 0.0001 && Math.abs(lng - lastGeocodedLng) < 0.0001) {
            return;
        }
        lastGeocodedLat = lat;
        lastGeocodedLng = lng;

        try {
            const resp = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                headers: { 'Accept-Language': 'id, en' }
            });
            if (resp.ok) {
                const data = await resp.json();
                if (data && (data.address || data.display_name)) {
                    const resLines = formatIndonesianAddress(data.address, data.display_name);
                    if (resLines.length > 0) {
                        currentAddressLines = resLines;
                    }
                }
            }
        } catch (e) {
            console.warn('Geocoding notice:', e);
        }

        if (!currentAddressLines || currentAddressLines.length === 0 || currentAddressLines[0] === 'Mencari alamat lokasi...') {
            let locName = 'Lokasi Karyawan';
            if (currentAllowedLocations && currentAllowedLocations.length > 0) {
                locName = currentAllowedLocations[0].name;
            }
            currentAddressLines = [locName, `GPS: ${lat.toFixed(5)}, ${lng.toFixed(5)}`];
        }

        const addrEl = document.getElementById('overlayAddressText');
        if (addrEl) {
            addrEl.innerHTML = currentAddressLines.join('<br>');
        }
    }

    // Menggambar watermark Sintesa secara presisi di atas canvas foto (skala 1:1 terhadap kontainer 280px)
    function drawTimemarkWatermarkOnCanvas(ctx, width, height, customTimeStr, customDateStr, customAddrLines) {
        // Skala referensi 280px (lebar frame selfie di layar) agar ukuran teks & badge konsisten dan tidak mengecil saat dijepret
        const s = width / 280;

        // 1. Gradasi bayangan gelap di bagian bawah foto
        const gradH = 135 * s;
        const grad = ctx.createLinearGradient(0, height - gradH, 0, height);
        grad.addColorStop(0, 'rgba(0, 0, 0, 0)');
        grad.addColorStop(0.35, 'rgba(0, 0, 0, 0.35)');
        grad.addColorStop(0.75, 'rgba(0, 0, 0, 0.7)');
        grad.addColorStop(1, 'rgba(0, 0, 0, 0.85)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, height - gradH, width, gradH);

        // Data waktu & tanggal
        const nowObj = new Date();
        const days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const dayName = days[nowObj.getDay()];
        const dayDate = String(nowObj.getDate()).padStart(2, '0');
        const monthName = months[nowObj.getMonth()];
        const yearNum = nowObj.getFullYear();

        const dateStr = customDateStr || `${dayName}, ${dayDate} ${monthName} ${yearNum}`;
        const timeHH = String(nowObj.getHours()).padStart(2, '0');
        const timeMM = String(nowObj.getMinutes()).padStart(2, '0');
        const timeStr = customTimeStr || `${timeHH}:${timeMM}`;

        // 2. Badge [ABSEN MASUK / ABSEN PULANG / LEMBUR MASUK / LEMBUR PULANG] [HH:mm] (Pill Badge)
        let typeLabel = 'ABSEN MASUK';
        if (currentClockType === 'clock_out') {
            typeLabel = 'ABSEN PULANG';
        } else if (currentClockType === 'overtime_in') {
            typeLabel = 'LEMBUR MASUK';
        } else if (currentClockType === 'overtime_out') {
            typeLabel = 'LEMBUR PULANG';
        }

        ctx.font = `800 ${11 * s}px sans-serif`;
        const typeTextW = ctx.measureText(typeLabel).width;
        const badgeW = typeTextW + (16 * s);

        ctx.font = `800 ${13 * s}px sans-serif`;
        const timeTextW = ctx.measureText(timeStr).width;
        const timePartW = timeTextW + (18 * s);

        const pillTotalW = badgeW + timePartW;
        const pillH = 22 * s;
        const pillX = 10 * s;
        const pillY = height - 104 * s;

        // Base putih pill
        ctx.fillStyle = '#ffffff';
        drawCanvasRoundRect(ctx, pillX, pillY, pillTotalW, pillH, 4 * s);
        ctx.fill();

        // Sisi kiri kuning ABSEN MASUK / ABSEN PULANG
        ctx.fillStyle = '#f59e0b';
        drawCanvasRoundRect(ctx, pillX, pillY, badgeW, pillH, 4 * s);
        ctx.fill();
        ctx.fillRect(pillX + badgeW - 4 * s, pillY, 4 * s, pillH);

        // Teks ABSEN MASUK / ABSEN PULANG
        ctx.fillStyle = '#000000';
        ctx.font = `800 ${11 * s}px sans-serif`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(typeLabel, pillX + (badgeW / 2), pillY + (pillH / 2));

        // Teks Waktu (misal: 02:32)
        ctx.fillStyle = '#0b52aa';
        ctx.font = `800 ${13 * s}px sans-serif`;
        ctx.fillText(timeStr, pillX + badgeW + (timePartW / 2), pillY + (pillH / 2));

        // 3. Garis Emas Vertikal
        const lineX = 10 * s;
        const lineY1 = height - 76 * s;
        const lineY2 = height - 32 * s;
        ctx.strokeStyle = '#f59e0b';
        ctx.lineWidth = 2.5 * s;
        ctx.beginPath();
        ctx.moveTo(lineX, lineY1);
        ctx.lineTo(lineX, lineY2);
        ctx.stroke();

        // 4. Tanggal
        ctx.save();
        ctx.shadowColor = 'rgba(0, 0, 0, 0.9)';
        ctx.shadowBlur = 3 * s;
        ctx.fillStyle = '#ffffff';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.font = `700 ${11 * s}px sans-serif`;
        ctx.fillText(dateStr, lineX + 6 * s, lineY1);

        // 5. Alamat Lokasi
        ctx.font = `400 ${9.5 * s}px sans-serif`;
        const addrLines = customAddrLines || currentAddressLines;
        const addrL1 = addrLines[0] || 'Lokasi Karyawan';
        const addrL2 = addrLines[1] || (currentLatitude ? `GPS: ${currentLatitude.toFixed(5)}, ${currentLongitude.toFixed(5)}` : '');
        ctx.fillText(addrL1, lineX + 6 * s, lineY1 + 14 * s);
        if (addrL2) {
            ctx.fillText(addrL2, lineX + 6 * s, lineY1 + 26 * s);
        }

        // 6. Verified Teks di Bawah (Bahasa Indonesia)
        ctx.font = `400 ${8.5 * s}px sans-serif`;
        ctx.textBaseline = 'alphabetic';
        ctx.fillText('✔ Waktu terverifikasi oleh Sintesa', 10 * s, height - 10 * s);

        // 7. Branding "Photo by Sintesa" di Kanan Bawah
        ctx.textAlign = 'right';
        ctx.font = `400 ${7 * s}px sans-serif`;
        ctx.fillText('Photo by', width - 10 * s, height - 21 * s);
        ctx.fillStyle = '#f59e0b';
        ctx.font = `800 ${11.5 * s}px sans-serif`;
        ctx.fillText('Sintesa', width - 10 * s, height - 10 * s);

        ctx.restore();
    }

    // Ambil Foto dari Video dengan Crop Persegi 1:1 dan Watermark Timemark Camera
    function capturePhoto() {
        const video = document.getElementById('webClockVideo');
        const canvas = document.getElementById('webClockCanvas');
        const photoPreview = document.getElementById('webClockPhotoPreview');
        const overlay = document.getElementById('webClockWatermarkOverlay');

        if (!video || !canvas || !photoPreview) return null;

        const vWidth = video.videoWidth || 640;
        const vHeight = video.videoHeight || 480;
        const squareDim = Math.min(vWidth, vHeight);

        // Center-crop koordinat sumber dari feed kamera video
        const sx = (vWidth - squareDim) / 2;
        const sy = (vHeight - squareDim) / 2;

        const outputDim = Math.max(720, squareDim);
        canvas.width = outputDim;
        canvas.height = outputDim;

        const ctx = canvas.getContext('2d');

        // Gambar video selfie mirror
        ctx.save();
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, sx, sy, squareDim, squareDim, 0, 0, canvas.width, canvas.height);
        ctx.restore();

        // Gambar watermark Timemark permanen di atas canvas foto
        drawTimemarkWatermarkOnCanvas(ctx, canvas.width, canvas.height);

        currentPhotoBase64 = canvas.toDataURL('image/jpeg', 0.88);

        photoPreview.src = currentPhotoBase64;
        photoPreview.style.display = 'block';
        video.style.display = 'none';
        if (overlay) overlay.style.display = 'none';

        setPhotoControlsState(true);
        updateSubmitButtonState();
        return currentPhotoBase64;
    }

    // Tombol Ambil Foto di bawah frame foto
    window.webClockTriggerCapture = function () {
        if (isModalReadOnly) return;
        const video = document.getElementById('webClockVideo');
        if (!isCameraReady || !video || video.readyState < 2) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'Menyiapkan Kamera',
                    text: 'Kamera sedang memuat, mohon tunggu sebentar lalu klik Ambil Foto.',
                    confirmButtonColor: '#0b52aa',
                    timer: 2000
                });
            }
            return;
        }

        capturePhoto();
    };

    // Tombol Ulangi Foto di bawah frame foto
    window.webClockRetakePhoto = function () {
        if (isModalReadOnly) return;
        currentPhotoBase64 = null;
        isCameraReady = false;
        const video = document.getElementById('webClockVideo');
        const photoPreview = document.getElementById('webClockPhotoPreview');
        const overlay = document.getElementById('webClockWatermarkOverlay');

        if (photoPreview) {
            photoPreview.src = '';
            photoPreview.style.display = 'none';
        }
        if (video) video.style.display = 'block';
        if (overlay) overlay.style.display = 'flex';

        setPhotoControlsState(false);
        updateSubmitButtonState();
        startCamera();
    };

    function fallbackCameraFeed() {
        const video = document.getElementById('webClockVideo');
        const canvas = document.getElementById('webClockCanvas');
        const photoPreview = document.getElementById('webClockPhotoPreview');
        const overlay = document.getElementById('webClockWatermarkOverlay');

        if (canvas) {
            canvas.width = 720;
            canvas.height = 720;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(0, 0, 720, 720);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 24px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Kamera Tidak Terdeteksi', 360, 330);
            ctx.font = '16px sans-serif';
            ctx.fillStyle = '#94a3b8';
            ctx.fillText('(Lingkungan Headless / Demo)', 360, 365);

            drawTimemarkWatermarkOnCanvas(ctx, 720, 720);

            currentPhotoBase64 = canvas.toDataURL('image/jpeg', 0.85);
            if (photoPreview) {
                photoPreview.src = currentPhotoBase64;
                photoPreview.style.display = 'block';
            }
            if (video) video.style.display = 'none';
            if (overlay) overlay.style.display = 'none';

            setPhotoControlsState(true);
            updateSubmitButtonState();
        }
    }

    // GPS Geolocation Handler
    function requestGeolocation() {
        const gpsOverlay = document.getElementById('overlayGpsText');
        const orbitWrapper = document.getElementById('gpsOrbitWrapper');
        const icon = document.getElementById('gpsStatusIcon');
        const title = document.getElementById('gpsStatusTitle');
        const info = document.getElementById('gpsDistanceInfo');
        const notesGroup = document.getElementById('outOfRadiusNotesGroup');

        if (!navigator.geolocation) {
            if (orbitWrapper) orbitWrapper.className = 'receipt-orbit-wrapper orbit-danger';
            if (icon) icon.className = 'fas fa-times';
            if (title) title.innerText = 'GPS Tidak Didukung';
            if (info) info.innerText = 'Perangkat Anda tidak mendukung fitur Geolocation GPS.';
            return;
        }

        if (orbitWrapper) orbitWrapper.className = 'receipt-orbit-wrapper orbit-info';
        if (icon) icon.className = 'fas fa-satellite-dish fa-spin';
        if (title) title.innerText = 'Mencari Titik GPS Anda...';
        if (info) info.innerText = 'Menunggu pembacaan titik koordinat dari sensor HP Anda...';

        if (geoWatchId !== null) {
            navigator.geolocation.clearWatch(geoWatchId);
            geoWatchId = null;
        }

        let hasGottenPosition = false;

        function handleSuccess(position) {
            hasGottenPosition = true;
            currentLatitude = position.coords.latitude;
            currentLongitude = position.coords.longitude;
            currentAccuracy = Math.round(position.coords.accuracy || 0);

            if (gpsOverlay) {
                gpsOverlay.innerText = `GPS: ${currentLatitude.toFixed(5)}, ${currentLongitude.toFixed(5)} (±${currentAccuracy}m)`;
            }

            reverseGeocodeAddress(currentLatitude, currentLongitude);

            evaluateGeofence();
            updateSubmitButtonState();
        }

        function handleFailure(error) {
            console.warn('Geolocation error (' + error.code + '):', error.message);
            updateSubmitButtonState();
            if (hasGottenPosition) return;

            let errMsg = 'Gagal membaca titik GPS.';
            let errTitle = 'GPS Belum Terbaca';

            if (error.code === 1) { // PERMISSION_DENIED
                errTitle = 'Izin Lokasi Ditolak';
                errMsg = 'Akses lokasi ditolak. Buka izin lokasi untuk browser ini di pengaturan HP Anda.';
            } else if (error.code === 2) { // POSITION_UNAVAILABLE
                errTitle = 'Sinyal GPS Tidak Aktif';
                errMsg = 'Sinyal lokasi tidak tersedia. Pastikan fitur Lokasi/GPS di HP Anda dalam posisi AKTIF.';
            } else if (error.code === 3) { // TIMEOUT
                errTitle = 'Sinyal GPS Lambat';
                errMsg = 'Pencarian satelit GPS memakan waktu lama. Pastikan Anda berada di area terbuka atau aktifkan Wi-Fi/data.';
            }

            if (orbitWrapper) orbitWrapper.className = 'receipt-orbit-wrapper orbit-danger';
            if (icon) icon.className = 'fas fa-exclamation-triangle';
            if (title) title.innerText = errTitle;
            if (info) {
                info.innerHTML = `${errMsg} <br><button type="button" onclick="window.retryGeolocation()" class="btn btn-sm btn-outline-primary mt-2" style="font-size: 11px; padding: 2px 10px; border-radius: 6px;"><i class="fas fa-redo-alt mr-1"></i> Coba Deteksi Lokasi Lagi</button>`;
            }
        }

        // 1. Coba ambil lokasi dengan akurasi tinggi (Hardware GPS)
        navigator.geolocation.getCurrentPosition(
            handleSuccess,
            function (err) {
                console.warn('High accuracy GPS timeout/failed, trying network geolocation...', err);
                // 2. Fallback cepat ke akurasi jaringan (Wi-Fi / Tower)
                navigator.geolocation.getCurrentPosition(
                    handleSuccess,
                    handleFailure,
                    { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
                );
            },
            { enableHighAccuracy: true, timeout: 7000, maximumAge: 30000 }
        );

        // 3. Pasang watchPosition agar otomatis update posisi saat GPS mengunci satelit
        try {
            geoWatchId = navigator.geolocation.watchPosition(
                handleSuccess,
                function (err) { console.warn('watchPosition notice:', err); },
                { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 }
            );
        } catch (e) {
            console.warn('watchPosition not available:', e);
        }
    }

    window.retryGeolocation = function () {
        requestGeolocation();
    };

    function evaluateGeofence() {
        const orbitWrapper = document.getElementById('gpsOrbitWrapper');
        const icon = document.getElementById('gpsStatusIcon');
        const title = document.getElementById('gpsStatusTitle');
        const info = document.getElementById('gpsDistanceInfo');
        const notesGroup = document.getElementById('outOfRadiusNotesGroup');

        if (isFreeLocationEmployee) {
            isInsideRadius = true;
            if (orbitWrapper) orbitWrapper.className = 'receipt-orbit-wrapper orbit-info';
            if (icon) icon.className = 'fas fa-globe';
            if (title) title.innerText = 'Bebas Lokasi (Tugas Luar)';
            if (info) {
                info.innerHTML = 'Karyawan bebas lokasi (tugas lapangan). Presensi diizinkan di mana saja.';
            }
            if (notesGroup) notesGroup.style.display = 'none';

            if (currentLatitude !== null && currentLongitude !== null) {
                initWebClockMap(currentLatitude, currentLongitude);
            }
            return;
        }

        if (!currentAllowedLocations || currentAllowedLocations.length === 0) {
            isInsideRadius = true;
            if (orbitWrapper) orbitWrapper.className = 'receipt-orbit-wrapper orbit-success';
            if (icon) icon.className = 'fas fa-check';
            if (title) title.innerText = 'Lokasi Terverifikasi';
            if (info) info.innerText = 'Tidak ada pembatasan lokasi khusus.';
            if (notesGroup) notesGroup.style.display = 'none';

            if (currentLatitude !== null && currentLongitude !== null) {
                initWebClockMap(currentLatitude, currentLongitude);
            }
            return;
        }

        let minDistance = Infinity;
        let matchedLocation = null;

        currentAllowedLocations.forEach(loc => {
            const d = calculateHaversine(currentLatitude, currentLongitude, loc.latitude, loc.longitude);
            if (d < minDistance) {
                minDistance = d;
                matchedLocation = loc;
            }
        });

        const allowedRadius = matchedLocation ? matchedLocation.radius_meters : 50;

        if (minDistance <= allowedRadius) {
            isInsideRadius = true;
            if (orbitWrapper) orbitWrapper.className = 'receipt-orbit-wrapper orbit-success';
            if (icon) icon.className = 'fas fa-check';
            if (title) title.innerText = 'Radius Kantor Terverifikasi';
            if (info) {
                info.innerHTML = `Posisi Anda valid berada di area <strong>${matchedLocation.name}</strong> (${minDistance}m dari pusat, batas radius ${allowedRadius}m).`;
            }
            if (notesGroup) notesGroup.style.display = 'none';
        } else {
            isInsideRadius = false;
            if (orbitWrapper) orbitWrapper.className = 'receipt-orbit-wrapper orbit-warning';
            if (icon) icon.className = 'fas fa-exclamation-triangle';
            if (title) title.innerText = 'Di Luar Radius Kantor Resmi';
            if (info) {
                info.innerHTML = `Peringatan: Jarak Anda <strong>${minDistance}m</strong> dari <strong>${matchedLocation.name}</strong> (Batas: ${allowedRadius}m). Wajib mengisi alasan kunjungan.`;
            }
            if (notesGroup) notesGroup.style.display = 'block';
        }

        if (currentLatitude !== null && currentLongitude !== null) {
            initWebClockMap(currentLatitude, currentLongitude);
        }
    }

    // Invalidate dan sesuaikan ulang ukuran canvas & bounds peta Leaflet saat modal tampil
    function refreshMapSizeAndBounds() {
        if (!clockMap) return;
        clockMap.invalidateSize();
        if (currentLatitude !== null && currentLongitude !== null) {
            updateMapOfficeAndGeofence();
        }
    }

    // Modal Events Listener & Invalidate Map Size
    if (typeof $ !== 'undefined') {
        $('#webClockModal').on('shown.bs.modal', function () {
            refreshMapSizeAndBounds();
            setTimeout(refreshMapSizeAndBounds, 150);
        });
        $('#webClockModal').on('hidden.bs.modal', function () {
            window.webClockStopCamera();
        });
    }

    const modalEl = document.getElementById('webClockModal');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            refreshMapSizeAndBounds();
            setTimeout(refreshMapSizeAndBounds, 150);
        });
        modalEl.addEventListener('hidden.bs.modal', function () {
            window.webClockStopCamera();
        });
    }

    // Update state tombol submit (Hanya aktif jika foto sudah diambil & lokasi GPS sudah terbaca)
    function updateSubmitButtonState() {
        const submitBtn = document.getElementById('webClockSubmitBtn');
        if (!submitBtn) return;

        const isPhotoReady = (currentPhotoBase64 !== null);
        const isGpsReady = (currentLatitude !== null && currentLongitude !== null);

        if (isPhotoReady && isGpsReady) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
            submitBtn.style.pointerEvents = 'auto';
        } else {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.55';
            submitBtn.style.cursor = 'not-allowed';
            submitBtn.style.pointerEvents = 'none';
        }
    }

    window.addEventListener('open-web-clock-modal', function (event) {
        const data = event.detail[0] || event.detail;
        currentClockType = data.type || 'clock_in';
        isFreeLocationEmployee = !!data.isFreeLocation;
        currentAllowedLocations = data.allowedLocations || [];
        const isReadOnly = !!data.isReadOnly;
        const readOnlyData = data.readOnlyData || null;
        isModalReadOnly = isReadOnly;

        let clockTypeLabel = 'Masuk';
        if (currentClockType === 'clock_out') {
            clockTypeLabel = 'Pulang';
        } else if (currentClockType === 'overtime_in') {
            clockTypeLabel = 'Mulai Lembur';
        } else if (currentClockType === 'overtime_out') {
            clockTypeLabel = 'Selesai Lembur';
        }
        const titleEl = document.getElementById('webClockModalLabel');
        const selfieTitleEl = document.getElementById('selfieSectionTitle');
        const selfieLeadEl = document.getElementById('selfieSectionLead');
        const videoEl = document.getElementById('webClockVideo');
        const previewEl = document.getElementById('webClockPhotoPreview');
        const retakeBtn = document.getElementById('webClockRetakeBtn');
        const submitBtn = document.getElementById('webClockSubmitBtn');
        const closeBtnFooter = document.getElementById('webClockCloseBtnFooter');
        const outOfRadiusNotes = document.getElementById('outOfRadiusNotesGroup');
        const readOnlyNotesGroup = document.getElementById('readOnlyNotesGroup');
        const readOnlyNotesText = document.getElementById('readOnlyNotesText');
        const orbitWrapper = document.getElementById('gpsOrbitWrapper');
        const statusIcon = document.getElementById('gpsStatusIcon');
        const statusTitle = document.getElementById('gpsStatusTitle');
        const distanceInfo = document.getElementById('gpsDistanceInfo');
        const overlayTime = document.getElementById('overlayTimeText');
        const overlayGps = document.getElementById('overlayGpsText');

        if (isReadOnly && readOnlyData) {
            // 1. Tampilan Header & Judul Read-Only
            if (titleEl) titleEl.innerText = `Detail Presensi ${clockTypeLabel} (Tercatat)`;
            if (selfieTitleEl) selfieTitleEl.innerText = `Foto Selfie Presensi ${clockTypeLabel} (Tercatat)`;
            if (selfieLeadEl) selfieLeadEl.innerText = 'Foto selfie yang telah tersimpan pada sistem saat Anda melakukan presensi.';

            // 2. Matikan kamera & GPS aktif
            window.webClockStopCamera();
            if (geoWatchId !== null) {
                navigator.geolocation.clearWatch(geoWatchId);
                geoWatchId = null;
            }

            // 3. Tampilkan Foto Selfie yang tersimpan
            if (videoEl) videoEl.style.display = 'none';
            if (previewEl) {
                previewEl.src = readOnlyData.photoUrl || '';
                previewEl.style.display = 'block';
            }
            const watermarkOverlay = document.getElementById('webClockWatermarkOverlay');
            if (watermarkOverlay) watermarkOverlay.style.display = 'none';
            // Sembunyikan kontrol foto (Ambil Foto & Ulangi Foto)
            const photoControls = document.getElementById('webClockPhotoControls');
            if (photoControls) {
                photoControls.classList.add('d-none');
                photoControls.style.setProperty('display', 'none', 'important');
            }
            const captureBtn = document.getElementById('webClockCaptureBtn');
            const retakeBtn = document.getElementById('webClockRetakeBtn');
            if (captureBtn) captureBtn.style.setProperty('display', 'none', 'important');
            if (retakeBtn) retakeBtn.style.setProperty('display', 'none', 'important');

            // 4. Set Watermark Timestamp & Koordinat Tersimpan
            if (overlayTime) overlayTime.innerText = readOnlyData.time;
            if (overlayGps) {
                const latStr = (typeof readOnlyData.latitude === 'number' && readOnlyData.latitude !== 0) ? readOnlyData.latitude.toFixed(5) : '-';
                const lngStr = (typeof readOnlyData.longitude === 'number' && readOnlyData.longitude !== 0) ? readOnlyData.longitude.toFixed(5) : '-';
                overlayGps.innerHTML = `<i class="fas fa-map-marker-alt mr-1"></i> GPS: ${latStr}, ${lngStr} (Tercatat)`;
            }

            // 5. Centered Hero Orbit Status
            if (orbitWrapper && statusIcon && statusTitle && distanceInfo) {
                if (readOnlyData.isInside) {
                    orbitWrapper.className = 'receipt-orbit-wrapper orbit-success';
                    statusIcon.className = 'fas fa-check';
                    statusTitle.innerText = `Presensi ${clockTypeLabel} Terverifikasi`;
                    distanceInfo.innerHTML = `Tercatat valid pada <strong>${readOnlyData.time}</strong> di <strong>${readOnlyData.locationName}</strong>${readOnlyData.distance ? ' (' + readOnlyData.distance + 'm)' : ''}.`;
                } else {
                    orbitWrapper.className = 'receipt-orbit-wrapper orbit-warning';
                    statusIcon.className = 'fas fa-exclamation-triangle';
                    statusTitle.innerText = `Presensi ${clockTypeLabel} (Di Luar Radius)`;
                    distanceInfo.innerHTML = `Tercatat pada <strong>${readOnlyData.time}</strong>. Jarak: <strong>${readOnlyData.distance ?? 0}m</strong> dari ${readOnlyData.locationName}. Status: Menunggu Review Atasan.`;
                }
            }

            // 6. Tampilkan Catatan jika ada
            if (outOfRadiusNotes) outOfRadiusNotes.style.display = 'none';
            if (readOnlyNotesGroup && readOnlyNotesText) {
                if (readOnlyData.notes) {
                    readOnlyNotesGroup.style.display = 'block';
                    readOnlyNotesText.innerText = readOnlyData.notes;
                } else {
                    readOnlyNotesGroup.style.display = 'none';
                }
            }

            // 7. Footer: Sembunyikan Submit, Tampilkan Tombol Tutup
            if (submitBtn) submitBtn.style.display = 'none';
            if (closeBtnFooter) closeBtnFooter.style.display = 'block';

            // 8. Peta Lokasi Tercatat
            currentLatitude = (typeof readOnlyData.latitude === 'number' && readOnlyData.latitude !== 0) ? readOnlyData.latitude : null;
            currentLongitude = (typeof readOnlyData.longitude === 'number' && readOnlyData.longitude !== 0) ? readOnlyData.longitude : null;
            if (currentLatitude !== null && currentLongitude !== null) {
                initWebClockMap(currentLatitude, currentLongitude);
            }
        } else {
            // Mode Aktif (Ambil Foto & Absen Baru)
            if (titleEl) titleEl.innerText = `Presensi ${clockTypeLabel} (Selfie + GPS)`;
            if (selfieTitleEl) selfieTitleEl.innerText = 'Foto Selfie Karyawan';
            if (selfieLeadEl) selfieLeadEl.innerText = 'Pastikan wajah terlihat jelas dan berada di dalam area frame kamera dengan pencahayaan yang cukup.';

            const typeBadge = document.getElementById('overlayTypeBadge');
            if (typeBadge) {
                if (currentClockType === 'clock_out') {
                    typeBadge.innerText = 'ABSEN PULANG';
                } else if (currentClockType === 'overtime_in') {
                    typeBadge.innerText = 'LEMBUR MASUK';
                } else if (currentClockType === 'overtime_out') {
                    typeBadge.innerText = 'LEMBUR PULANG';
                } else {
                    typeBadge.innerText = 'ABSEN MASUK';
                }
            }

            currentLatitude = null;
            currentLongitude = null;
            currentPhotoBase64 = null;
            isCameraReady = false;

            if (videoEl) videoEl.style.display = 'block';
            if (previewEl) {
                previewEl.src = '';
                previewEl.style.display = 'none';
            }
            const watermarkOverlay = document.getElementById('webClockWatermarkOverlay');
            if (watermarkOverlay) watermarkOverlay.style.display = 'flex';
            const photoControls = document.getElementById('webClockPhotoControls');
            if (photoControls) {
                photoControls.classList.remove('d-none');
                photoControls.style.setProperty('display', 'flex', 'important');
            }
            const captureBtn = document.getElementById('webClockCaptureBtn');
            const retakeBtn = document.getElementById('webClockRetakeBtn');
            if (captureBtn) captureBtn.style.removeProperty('display');
            if (retakeBtn) retakeBtn.style.removeProperty('display');
            setPhotoControlsState(false);

            if (readOnlyNotesGroup) readOnlyNotesGroup.style.display = 'none';
            if (outOfRadiusNotes) outOfRadiusNotes.style.display = 'none';
            const notesInput = document.getElementById('webClockNotes');
            if (notesInput) notesInput.value = '';

            if (submitBtn) submitBtn.style.display = 'block';
            if (closeBtnFooter) closeBtnFooter.style.display = 'none';
            updateSubmitButtonState();

            startCamera();

            const initialLat = (currentAllowedLocations.length > 0) ? currentAllowedLocations[0].latitude : -6.5715000;
            const initialLng = (currentAllowedLocations.length > 0) ? currentAllowedLocations[0].longitude : 107.7587000;
            initWebClockMap(initialLat, initialLng);

            requestGeolocation();
        }

        if (typeof $ !== 'undefined') {
            $('#webClockModal').modal('show');
        }

        setTimeout(refreshMapSizeAndBounds, 100);
        setTimeout(refreshMapSizeAndBounds, 300);
    });

    window.webClockCloseModal = function () {
        window.webClockStopCamera();
        if (geoWatchId !== null) {
            navigator.geolocation.clearWatch(geoWatchId);
            geoWatchId = null;
        }
        isModalReadOnly = false;
        const photoControls = document.getElementById('webClockPhotoControls');
        if (photoControls) {
            photoControls.classList.add('d-none');
            photoControls.style.setProperty('display', 'none', 'important');
        }
        if (typeof $ !== 'undefined') {
            $('#webClockModal').modal('hide');
        } else {
            const modal = document.getElementById('webClockModal');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        }
    };

    window.addEventListener('close-web-clock-modal', function () {
        window.webClockCloseModal();
    });

    // Proses Pengiriman Absensi ke Livewire
    window.webClockProcessAttendance = function () {
        if (isModalReadOnly) return;
        const submitBtn = document.getElementById('webClockSubmitBtn');
        const spinner = document.getElementById('webClockSubmitBtnSpinner');
        const btnText = document.getElementById('webClockSubmitBtnText');
        const notesInput = document.getElementById('webClockNotes');

        if (currentLatitude === null || currentLongitude === null) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Lokasi Belum Terbaca',
                    text: 'Sensor GPS belum mengunci titik lokasi Anda. Mohon tunggu beberapa saat.',
                    confirmButtonColor: '#0b52aa'
                });
            } else {
                alert('Sensor GPS belum mengunci titik lokasi Anda. Mohon tunggu...');
            }
            return;
        }

        if (!currentPhotoBase64) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Foto Belum Diambil',
                    text: 'Silakan klik tombol "Ambil Foto" terlebih dahulu sebelum menyimpan presensi.',
                    confirmButtonColor: '#0b52aa'
                });
            } else {
                alert('Silakan klik tombol "Ambil Foto" terlebih dahulu sebelum menyimpan presensi.');
            }
            return;
        }

        const notes = notesInput ? notesInput.value.trim() : '';
        if (!isInsideRadius && !notes) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Alasan Wajib Diisi',
                    text: 'Anda berada di luar radius kantor resmi. Mohon tuliskan alasan kunjungan site / tugas luar.',
                    confirmButtonColor: '#0b52aa'
                });
            } else {
                alert('Anda berada di luar radius kantor resmi. Mohon tuliskan alasan kunjungan site.');
            }
            if (notesInput) notesInput.focus();
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.55';
            submitBtn.style.cursor = 'not-allowed';
        }
        if (spinner) spinner.style.display = 'inline-block';
        if (btnText) btnText.style.display = 'none';

        const deviceInfo = navigator.userAgent || 'Web Browser';

        const rootEl = document.getElementById('attendanceClockRoot') || document.getElementById('webClockModal')?.closest('[wire\\:id]');
        if (window.Livewire && rootEl) {
            const comp = window.Livewire.find(rootEl.getAttribute('wire:id'));
            comp.call('recordClock', currentClockType, currentPhotoBase64, currentLatitude, currentLongitude, notes, deviceInfo)
                .finally(() => {
                    updateSubmitButtonState();
                    if (spinner) spinner.style.display = 'none';
                    if (btnText) btnText.style.display = 'inline-block';
                });
        }
    };
});
</script>
</div>
