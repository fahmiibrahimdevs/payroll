<!-- Modal Audit Presensi: Selfie & Peta Lokasi GPS (Design 1 - Tabbed Map) -->
<div wire:ignore.self class="modal fade" id="attendanceAuditModal" tabindex="-1" role="dialog" aria-labelledby="attendanceAuditModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 14px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="mr-2.5 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #0b52aa 0%, #1d4ed8 100%); color: #ffffff;">
                        <i class="fas fa-map-marked-alt" style="font-size: 16px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title text-dark mb-0" id="attendanceAuditModalLabel" style="font-weight: 600; font-size: 16px;">
                            Audit Presensi: Bukti Foto Selfie & Peta Lokasi GPS
                        </h5>
                        <div style="font-size: 11.5px; color: #475569;">
                            Verifikasi keaslian titik kehadiran, jarak geofencing, dan foto selfie karyawan.
                        </div>
                    </div>
                </div>
                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body py-3 px-4 bg-white">
                @if ($auditAttendance)
                    <div class="row">
                        <!-- Sisi Kiri: Profil, Status, Bukti Foto Selfie & Form Review (col-lg-5) -->
                        <div class="col-lg-5 col-12 border-lg-right pr-lg-4 mb-4 mb-lg-0" style="border-color: #f1f5f9 !important;">
                            <!-- Header Karyawan -->
                            <div class="d-flex align-items-center p-3 mb-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);">
                                <div class="mr-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; border-radius: 10px; background: #0b52aa; color: #ffffff; font-weight: 600; font-size: 16px;">
                                    {{ $auditAttendance->employee?->initials ?? 'K' }}
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="text-truncate font-weight-600 text-dark" style="font-size: 14px; color: #0f172a;">
                                        {{ $auditAttendance->employee?->full_name }}
                                    </div>
                                    <div class="text-truncate" style="font-size: 12px; color: #475569;">
                                        {{ $auditAttendance->employee?->department?->name ?? 'Tanpa Departemen' }} &bull; {{ $auditAttendance->employee?->position?->name ?? 'Staff' }}
                                    </div>
                                    <div style="font-size: 11px; color: #64748b;">
                                        ID Finger: {{ $auditAttendance->employee?->fingerprint_id ?? '-' }} &bull; Tanggal: {{ $auditAttendance->date?->format('d M Y') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Bar Status Kehadiran & Review Geofence -->
                            <div class="d-flex align-items-center justify-content-between p-3 mb-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);">
                                <div>
                                    <span style="font-size: 11.5px; color: #64748b; font-weight: 500;">Status Presensi:</span>
                                    <span class="{{ $auditAttendance->status_badge_class }} ml-1">{{ $auditAttendance->status_label }}</span>
                                </div>
                                <div>
                                    @if ($auditAttendance->isPendingReview())
                                        <span class="badge badge-warning text-dark px-2 py-1" style="font-size: 11px; font-weight: 500; background-color: #fef3c7; color: #92400e !important; border: 1px solid #fde68a;">
                                            <i class="fas fa-clock mr-1"></i> Perlu Review
                                        </span>
                                    @elseif ($auditAttendance->isApproved())
                                        <span class="badge badge-success px-2 py-1" style="font-size: 11px; font-weight: 500; background-color: #d1fae5; color: #065f46 !important; border: 1px solid #a7f3d0;">
                                            <i class="fas fa-check-circle mr-1"></i> Valid / Disetujui
                                        </span>
                                    @elseif ($auditAttendance->isRejected())
                                        <span class="badge badge-danger px-2 py-1" style="font-size: 11px; font-weight: 500; background-color: #fee2e2; color: #991b1b !important; border: 1px solid #fca5a5;">
                                            <i class="fas fa-times-circle mr-1"></i> Ditolak (Mangkir)
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Grid Foto Selfie Masuk & Pulang -->
                            <div class="row mb-3">
                                <!-- 1. Foto Selfie Masuk -->
                                <div class="col-6">
                                    <div class="border rounded-lg p-2 text-center" style="background: #ffffff; border-color: #e2e8f0 !important; border-radius: 10px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span style="font-size: 11.5px; font-weight: 600; color: #059669;">
                                                <i class="fas fa-sign-in-alt mr-0.5"></i> Masuk
                                            </span>
                                            <span style="font-size: 11px; color: #64748b; font-weight: 500;">
                                                {{ $auditAttendance->clock_in ? substr($auditAttendance->clock_in, 0, 5) . ' WIB' : '-' }}
                                            </span>
                                        </div>
                                        <div class="position-relative mb-1" style="width: 100%; aspect-ratio: 1 / 1; background: #0f172a; border-radius: 8px; overflow: hidden;">
                                            @if ($auditAttendance->clock_in_photo_url)
                                                <img src="{{ $auditAttendance->clock_in_photo_url }}" alt="Selfie Masuk" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                <div class="position-absolute" style="bottom: 4px; left: 4px; right: 4px; background: rgba(0,0,0,0.6); padding: 2px 6px; border-radius: 4px; font-size: 9.5px; color: #ffffff;">
                                                    <i class="fas fa-map-marker-alt text-warning"></i> {{ $auditAttendance->clock_in_distance_meters ?? 0 }}m dari kantor
                                                </div>
                                                <div class="photo-error-fallback flex-column align-items-center justify-content-center h-100 text-muted" style="display: none; font-size: 10.5px;">
                                                    <i class="fas fa-image mb-1" style="font-size: 18px; color: #94a3b8;"></i>
                                                    <span style="color: #cbd5e1;">File foto tidak ada</span>
                                                </div>
                                            @else
                                                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted" style="font-size: 11px;">
                                                    <i class="fas fa-camera-slash mb-1" style="font-size: 20px;"></i>
                                                    <span>Tanpa Foto</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-truncate text-muted" style="font-size: 10.5px;" title="{{ $auditAttendance->clockInLocation?->name ?? 'Kantor Resmi' }}">
                                            {{ $auditAttendance->clockInLocation?->name ?? 'Kantor Resmi' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Foto Selfie Pulang -->
                                <div class="col-6">
                                    <div class="border rounded-lg p-2 text-center" style="background: #ffffff; border-color: #e2e8f0 !important; border-radius: 10px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span style="font-size: 11.5px; font-weight: 600; color: #0b52aa;">
                                                <i class="fas fa-sign-out-alt mr-0.5"></i> Pulang
                                            </span>
                                            <span style="font-size: 11px; color: #64748b; font-weight: 500;">
                                                {{ $auditAttendance->clock_out ? substr($auditAttendance->clock_out, 0, 5) . ' WIB' : '-' }}
                                            </span>
                                        </div>
                                        <div class="position-relative mb-1" style="width: 100%; aspect-ratio: 1 / 1; background: #0f172a; border-radius: 8px; overflow: hidden;">
                                            @if ($auditAttendance->clock_out_photo_url)
                                                <img src="{{ $auditAttendance->clock_out_photo_url }}" alt="Selfie Pulang" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.closest('.position-relative').querySelector('.photo-error-fallback').style.display='flex';">
                                                <div class="position-absolute" style="bottom: 4px; left: 4px; right: 4px; background: rgba(0,0,0,0.6); padding: 2px 6px; border-radius: 4px; font-size: 9.5px; color: #ffffff;">
                                                    <i class="fas fa-map-marker-alt text-warning"></i> {{ $auditAttendance->clock_out_distance_meters ?? 0 }}m dari kantor
                                                </div>
                                                <div class="photo-error-fallback flex-column align-items-center justify-content-center h-100 text-muted" style="display: none; font-size: 10.5px;">
                                                    <i class="fas fa-image mb-1" style="font-size: 18px; color: #94a3b8;"></i>
                                                    <span style="color: #cbd5e1;">File foto tidak ada</span>
                                                </div>
                                            @else
                                                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted" style="font-size: 11px;">
                                                    <i class="fas fa-camera-slash mb-1" style="font-size: 20px;"></i>
                                                    <span>Tanpa Foto</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-truncate text-muted" style="font-size: 10.5px;" title="{{ $auditAttendance->clockOutLocation?->name ?? 'Kantor Resmi' }}">
                                            {{ $auditAttendance->clockOutLocation?->name ?? 'Kantor Resmi' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alasan / Catatan Karyawan jika di Luar Radius -->
                            @if (! empty($auditAttendance->notes))
                                <div class="p-3 mb-3 rounded-lg" style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px;">
                                    <div class="d-flex align-items-center mb-1" style="font-size: 11.5px; font-weight: 600; color: #b45309;">
                                        <i class="fas fa-comment-dots mr-1.5"></i> Keterangan / Alasan Karyawan:
                                    </div>
                                    <div style="font-size: 12.5px; color: #1e293b; line-height: 1.4;">
                                        {{ $auditAttendance->notes }}
                                    </div>
                                </div>
                            @endif

                            <!-- Info Perangkat & Histori Reviewer -->
                            @if ($auditAttendance->reviewer || $auditAttendance->device_info)
                                <div class="p-3 mb-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04); font-size: 11.5px; color: #334155;">
                                    @if ($auditAttendance->reviewer)
                                        <div>
                                            <i class="fas fa-user-check text-primary mr-1"></i> Ditinjau oleh: <strong>{{ $auditAttendance->reviewer?->name }}</strong>
                                            @if ($auditAttendance->reviewed_at)
                                                ({{ $auditAttendance->reviewed_at->format('d/m/Y H:i') }})
                                            @endif
                                        </div>
                                        @if ($auditAttendance->review_notes)
                                            <div class="mt-1 text-dark" style="font-size: 11.5px;">
                                                <em>"{{ $auditAttendance->review_notes }}"</em>
                                            </div>
                                        @endif
                                    @endif
                                    @if ($auditAttendance->device_info)
                                        <div class="mt-1 text-truncate" title="{{ $auditAttendance->device_info }}" style="font-size: 10.5px; color: #94a3b8;">
                                            <i class="fas fa-mobile-alt mr-1"></i> {{ $auditAttendance->device_info }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Aksi Approval Admin jika Status Pending Review -->
                            @if ($auditAttendance->isPendingReview())
                                <div class="p-3 rounded-lg border" style="background: #ffffff; border-color: #cbd5e1 !important; border-radius: 10px;">
                                    <label for="auditReviewNotes" style="font-size: 12px; font-weight: 600; color: #1e293b;" class="mb-1">
                                        Tindakan Review Admin:
                                    </label>
                                    <textarea id="auditReviewNotes" wire:model="auditReviewNotes" class="form-control mb-2.5" rows="2" placeholder="Catatan persetujuan / alasan penolakan (opsional)..." style="font-size: 12px; height: 60px;"></textarea>
                                    <div class="d-flex align-items-center" style="gap: 8px;">
                                        <button type="button" wire:click="approveAudit" class="btn btn-success btn-block d-inline-flex align-items-center justify-content-center shadow-sm" style="font-weight: 500; font-size: 12.5px; border-radius: 8px; padding: 8px 12px;">
                                            <i class="fas fa-check mr-1.5"></i> Setujui Presensi
                                        </button>
                                        <button type="button" wire:click="rejectAudit" class="btn btn-outline-danger btn-block d-inline-flex align-items-center justify-content-center" style="font-weight: 500; font-size: 12.5px; border-radius: 8px; padding: 8px 12px;">
                                            <i class="fas fa-times mr-1.5"></i> Tolak (Mangkir)
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Sisi Kanan: Design 1 (Peta Interaktif Tabbed Leaflet) (col-lg-7) -->
                        <div class="col-lg-7 col-12 pl-lg-4">
                            <!-- Tab Bar: Lokasi Masuk vs Lokasi Pulang -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    <button id="auditTabMasuk" type="button" wire:click="setAuditTab('clock_in')" class="btn btn-sm {{ $auditTab === 'clock_in' ? 'btn-primary active' : 'btn-outline-secondary' }}" style="border-radius: 8px 0 0 8px; font-weight: 500; font-size: 12px; {{ $auditTab === 'clock_in' ? 'background-color: #0b52aa; border-color: #0b52aa;' : '' }}">
                                        <i class="fas fa-sign-in-alt mr-1"></i> 🟢 Lokasi Masuk
                                        @if ($auditAttendance->clock_in_distance_meters !== null)
                                            <span class="badge badge-light ml-1" style="font-size: 10px;">{{ $auditAttendance->clock_in_distance_meters }}m</span>
                                        @endif
                                    </button>
                                    <button id="auditTabPulang" type="button" wire:click="setAuditTab('clock_out')" class="btn btn-sm {{ $auditTab === 'clock_out' ? 'btn-primary active' : 'btn-outline-secondary' }}" style="border-radius: 0 8px 8px 0; font-weight: 500; font-size: 12px; {{ $auditTab === 'clock_out' ? 'background-color: #0b52aa; border-color: #0b52aa;' : '' }}">
                                        <i class="fas fa-sign-out-alt mr-1"></i> 🔴 Lokasi Pulang
                                        @if ($auditAttendance->clock_out_distance_meters !== null)
                                            <span class="badge badge-light ml-1" style="font-size: 10px;">{{ $auditAttendance->clock_out_distance_meters }}m</span>
                                        @endif
                                    </button>
                                </div>
                                <span class="text-muted" style="font-size: 11.5px;">
                                    <i class="fas fa-info-circle mr-1"></i> Tab untuk melihat posisi GPS
                                </span>
                            </div>

                            <!-- Leaflet Map Container -->
                            <div class="position-relative" style="width: 100%; height: 420px; border-radius: 12px; overflow: hidden; border: 1px solid #cbd5e1; background: #e2e8f0;">
                                <div id="auditLeafletMap" wire:ignore style="width: 100%; height: 100%;"></div>

                                <!-- Floating Map Info Card Overlay -->
                                <div id="auditMapInfoOverlay" class="position-absolute" style="bottom: 12px; left: 12px; right: 12px; z-index: 1000; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(6px); border-radius: 10px; padding: 10px 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="min-width-0 mr-2">
                                            <div class="d-flex align-items-center">
                                                <span id="auditMapStatusBadge" class="badge badge-success mr-2" style="font-size: 11px; font-weight: 600;">
                                                    Memuat Data...
                                                </span>
                                                <span id="auditMapOfficeLabel" class="text-truncate font-weight-500 text-dark" style="font-size: 12px;">
                                                    Kantor Pusat - Percetakan Subang
                                                </span>
                                            </div>
                                            <div id="auditMapCoordinatesText" class="text-truncate text-muted mt-0.5" style="font-size: 11px;">
                                                Menyiapkan koordinat titik tap...
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <div id="auditMapDistanceText" style="font-size: 16px; font-weight: 700; color: #0f172a;">
                                                -- m
                                            </div>
                                            <div style="font-size: 10px; color: #64748b;">
                                                Jarak dari Pusat
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="modal-footer bg-white border-top px-4 py-3">
                <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500; border-radius: 8px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let auditMap = null;
    let officeMarker = null;
    let radiusCircle = null;
    let employeeMarker = null;
    let distanceLine = null;
    let latestMapData = null;

    function initOrUpdateAuditMap(data) {
        if (!data) return;
        latestMapData = data;

        const container = document.getElementById('auditLeafletMap');
        if (!container) return;

        // Leaflet library check
        if (typeof L === 'undefined') {
            console.warn('Leaflet is not loaded yet');
            return;
        }

        const officeLat = data.officeLat || -6.5715000;
        const officeLng = data.officeLng || 107.7587000;
        const radiusMeters = data.radiusMeters || 50;
        const empLat = data.empLat;
        const empLng = data.empLng;
        const distance = data.distanceMeters || 0;
        const isInside = data.isInsideRadius;
        const tab = data.tab || 'clock_in';
        const locationName = data.locationName || 'Kantor Resmi';

        // 1. Inisialisasi Map jika belum ada
        if (!auditMap) {
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: "{{ asset('assets/leaflet/images/marker-icon-2x.png') }}",
                iconUrl: "{{ asset('assets/leaflet/images/marker-icon.png') }}",
                shadowUrl: "{{ asset('assets/leaflet/images/marker-shadow.png') }}",
            });

            auditMap = L.map('auditLeafletMap', {
                center: [officeLat, officeLng],
                zoom: 17,
                zoomControl: true,
            });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(auditMap);
        }

        // 2. Bersihkan layer lama
        if (officeMarker) auditMap.removeLayer(officeMarker);
        if (radiusCircle) auditMap.removeLayer(radiusCircle);
        if (employeeMarker) auditMap.removeLayer(employeeMarker);
        if (distanceLine) auditMap.removeLayer(distanceLine);

        // 3. Tambahkan Marker Pusat Kantor & Radius Circle
        officeMarker = L.marker([officeLat, officeLng]).addTo(auditMap)
            .bindPopup(`<strong>${locationName}</strong><br>Radius diizinkan: ${radiusMeters} meter`);

        radiusCircle = L.circle([officeLat, officeLng], {
            radius: radiusMeters,
            color: '#0b52aa',
            fillColor: '#38bdf8',
            fillOpacity: 0.22,
            weight: 2
        }).addTo(auditMap);

        // 4. Update Floating Info Card
        const statusBadge = document.getElementById('auditMapStatusBadge');
        const officeLabel = document.getElementById('auditMapOfficeLabel');
        const coordsText = document.getElementById('auditMapCoordinatesText');
        const distText = document.getElementById('auditMapDistanceText');

        if (officeLabel) officeLabel.innerText = `${locationName} (Batas ${radiusMeters}m)`;
        if (distText) distText.innerText = `${distance} m`;

        if (empLat && empLng) {
            // Karyawan memiliki koordinat tap GPS
            if (coordsText) coordsText.innerText = `Titik Tap: ${empLat.toFixed(6)}, ${empLng.toFixed(6)}`;

            if (isInside) {
                if (statusBadge) {
                    statusBadge.className = 'badge badge-success mr-2';
                    statusBadge.style.backgroundColor = '#10b981';
                    statusBadge.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Di Dalam Radius';
                }
            } else {
                if (statusBadge) {
                    statusBadge.className = 'badge badge-warning text-dark mr-2';
                    statusBadge.style.backgroundColor = '#f59e0b';
                    statusBadge.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Di Luar Radius';
                }
            }

            // Tambahkan Marker Tap Karyawan
            const tabName = tab === 'clock_in' ? 'Masuk' : (tab === 'overtime' ? 'Lembur' : 'Pulang');
            employeeMarker = L.circleMarker([empLat, empLng], {
                radius: 9,
                color: isInside ? '#059669' : '#dc2626',
                fillColor: isInside ? '#10b981' : '#ef4444',
                fillOpacity: 0.9,
                weight: 3
            }).addTo(auditMap).bindPopup(`<strong>Titik Tap Karyawan (${tabName})</strong><br>Jarak: ${distance} meter`);

            // Garis penghubung titik kantor ke karyawan
            distanceLine = L.polyline([
                [officeLat, officeLng],
                [empLat, empLng]
            ], {
                color: isInside ? '#0b52aa' : '#dc2626',
                weight: 2,
                dashArray: '5, 8'
            }).addTo(auditMap);

            // Fit bounds mencakup kedua titik
            const bounds = L.latLngBounds([
                [officeLat, officeLng],
                [empLat, empLng]
            ]);
            auditMap.fitBounds(bounds, { padding: [50, 50], maxZoom: 18 });
        } else {
            // Tidak ada GPS dari karyawan
            if (coordsText) coordsText.innerText = 'Titik GPS karyawan tidak tercatat pada sesi ini.';
            if (statusBadge) {
                statusBadge.className = 'badge badge-secondary mr-2';
                statusBadge.innerText = 'Tanpa Titik GPS';
            }
            auditMap.setView([officeLat, officeLng], 17);
        }

        setTimeout(() => {
            if (auditMap) auditMap.invalidateSize();
        }, 200);
    }

    // Listener Livewire open audit modal & payload koordinat
    window.addEventListener('init-audit-map', function (event) {
        const payload = event.detail[0] || event.detail;
        setTimeout(() => {
            initOrUpdateAuditMap(payload);
        }, 150);
    });

    // Invalidate map size saat modal terbuka penuh
    $('#manualAttendanceModal, #attendanceAuditModal').on('shown.bs.modal', function () {
        if (auditMap) {
            auditMap.invalidateSize();
            if (latestMapData) {
                initOrUpdateAuditMap(latestMapData);
            }
        }
    });
});
</script>
@endpush
