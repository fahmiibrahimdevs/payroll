<!-- Modal Form: Tambah / Edit Master Lokasi Kerja -->
<div wire:ignore.self class="modal fade" id="workLocationModal" role="dialog" aria-labelledby="workLocationModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <h5 class="modal-title text-dark" id="workLocationModalLabel" style="font-weight: 600; font-size: 16px;">
                    <i class="fas fa-map-marked-alt mr-2 text-primary"></i>
                    {{ $isEditMode ? 'Edit Lokasi Kerja' : 'Tambah Lokasi Kerja Baru' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="resetForm">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}">
                <div class="modal-body py-3 px-4 bg-white">
                    <div class="row">
                        <!-- 1. Nama Lokasi & Status Aktif Switch -->
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label for="name" style="font-weight: 500; color: #1e293b;">
                                    Nama Lokasi Kerja <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" wire:model="form.name" class="form-control @error('form.name') is-invalid @enderror" placeholder="Contoh: Kantor Pusat, Workshop Subang, Site Cisalak..." autocomplete="off">
                                @error('form.name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label style="font-weight: 500; color: #1e293b;">Status Lokasi</label>
                                <div class="pt-2">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" wire:model="form.is_active" value="1">
                                        <label class="custom-control-label" for="is_active" style="cursor: pointer; font-weight: 500; color: #334155;">
                                            {{ $form->is_active ? 'Lokasi Aktif' : 'Nonaktif' }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Alamat Lengkap -->
                        <div class="col-12">
                            <div class="form-group mb-3">
                                <label for="address" style="font-weight: 500; color: #1e293b;">
                                    Alamat Lengkap <small class="text-muted">(Opsional)</small>
                                </label>
                                <textarea id="address" wire:model="form.address" rows="2" class="form-control @error('form.address') is-invalid @enderror" placeholder="Tuliskan nama jalan, no, kelurahan/kecamatan lokasi..." style="height: 68px;"></textarea>
                                @error('form.address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 3. Radius Geofence (Meter) -->
                        <div class="col-12">
                            <div class="form-group mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="radius_meters" style="font-weight: 500; color: #1e293b;">
                                        Radius Geofencing (Meter) <span class="text-danger">*</span>
                                    </label>
                                    <span class="badge" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 500; font-size: 11px;">
                                        Lingkaran Biru di Peta
                                    </span>
                                </div>
                                <div class="input-group">
                                    <input type="number" id="radius_meters" wire:model="form.radius_meters" min="5" max="10000" class="form-control @error('form.radius_meters') is-invalid @enderror" placeholder="50">
                                    <div class="input-group-append">
                                        <span class="input-group-text bg-white" style="color: #475569; font-weight: 500;">Meter</span>
                                    </div>
                                </div>
                                <small class="form-text mt-1" style="color: #475569; font-size: 11.5px;">
                                    Batas radius toleransi jarak absensi karyawan dari titik koordinat pusat (standar: 50 m, proyek luar: 100 - 500 m).
                                </small>
                                @error('form.radius_meters')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 4. Interactive Leaflet Map Picker -->
                        <div class="col-12">
                            <div class="form-group mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="mb-0" style="font-weight: 600; color: #1e293b; font-size: 13px;">
                                        <i class="fas fa-crosshairs text-primary mr-1"></i> Penentuan Titik Koordinat Peta
                                    </label>
                                    <button type="button" id="btnUseCurrentLocation" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center" style="font-size: 11.5px; padding: 4px 10px; border-radius: 6px;">
                                        <i class="fas fa-location-arrow mr-1.5"></i> Gunakan GPS Saya Saat Ini
                                    </button>
                                </div>

                                <!-- Leaflet Container with wire:ignore -->
                                <div wire:ignore id="locationPickerMap" style="height: 280px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; z-index: 1;"></div>

                                <div class="d-flex justify-content-between align-items-center mt-1.5">
                                    <small style="color: #475569; font-size: 11.5px;">
                                        <i class="fas fa-info-circle mr-1 text-primary"></i> Klik pada peta atau geser marker pin untuk mengatur posisi koordinat.
                                    </small>
                                    <small class="text-right" style="color: #64748b; font-size: 11px;">
                                        Leaflet OSM &bull; Subang Area
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Inputs Latitude & Longitude -->
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label for="latitude" style="font-weight: 500; color: #1e293b;">
                                    Latitude <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="latitude" wire:model="form.latitude" class="form-control font-mono @error('form.latitude') is-invalid @enderror" placeholder="-6.5715000">
                                @error('form.latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label for="longitude" style="font-weight: 500; color: #1e293b;">
                                    Longitude <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="longitude" wire:model="form.longitude" class="form-control font-mono @error('form.longitude') is-invalid @enderror" placeholder="107.7587000">
                                @error('form.longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-end" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-secondary shadow-none mr-2" data-dismiss="modal" wire:click="resetForm" style="font-weight: 500; font-size: 13px; padding: 8px 16px; border-radius: 6px;">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary shadow-sm" wire:loading.attr="disabled" wire:target="update, store" style="background-color: #0b52aa; border-color: #0b52aa; font-weight: 500; font-size: 13px; padding: 8px 18px; border-radius: 6px;">
                        <span wire:loading wire:target="update, store">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...
                        </span>
                        <span wire:loading.remove wire:target="update, store">
                            <i class="fas fa-save mr-1"></i> {{ $isEditMode ? 'Simpan Perubahan' : 'Tambah Lokasi' }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/leaflet/leaflet.css') }}">
<style>
    /* Styling leaflet container in modal */
    #locationPickerMap {
        cursor: crosshair;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/leaflet/leaflet.js') }}"></script>
<script>
    (function () {
        let locationMap = null;
        let locationMarker = null;
        let locationCircle = null;

        function updateInputs(lat, lng) {
            const latVal = Number(lat).toFixed(7);
            const lngVal = Number(lng).toFixed(7);

            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');

            if (latInput) latInput.value = latVal;
            if (lngInput) lngInput.value = lngVal;
        }

        function syncToLivewire(lat, lng) {
            const latVal = Number(lat).toFixed(7);
            const lngVal = Number(lng).toFixed(7);

            updateInputs(lat, lng);

            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');

            if (latInput) {
                latInput.dispatchEvent(new Event('input', { bubbles: true }));
                latInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (lngInput) {
                lngInput.dispatchEvent(new Event('input', { bubbles: true }));
                lngInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (typeof @this !== 'undefined') {
                @this.set('form.latitude', latVal);
                @this.set('form.longitude', lngVal);
            }
        }

        function setMapLocation(lat, lng, radius, panTo = true) {
            lat = parseFloat(lat);
            lng = parseFloat(lng);
            radius = parseInt(radius) || 50;

            if (isNaN(lat) || isNaN(lng)) {
                lat = -6.5715000;
                lng = 107.7587000;
            }

            if (!locationMap) {
                initLeafletMap(lat, lng, radius);
                return;
            }

            const latLng = L.latLng(lat, lng);
            if (locationMarker) {
                locationMarker.setLatLng(latLng);
            }
            if (locationCircle) {
                locationCircle.setLatLng(latLng);
                locationCircle.setRadius(radius);
            }
            if (panTo) {
                locationMap.setView(latLng, Math.max(locationMap.getZoom(), 16));
            }
        }

        function initLeafletMap(initialLat, initialLng, initialRadius) {
            const mapEl = document.getElementById('locationPickerMap');
            if (!mapEl) return;

            initialLat = parseFloat(initialLat);
            initialLng = parseFloat(initialLng);
            initialRadius = parseInt(initialRadius) || 50;

            if (isNaN(initialLat) || isNaN(initialLng)) {
                initialLat = -6.5715000;
                initialLng = 107.7587000;
            }

            if (locationMap) {
                setMapLocation(initialLat, initialLng, initialRadius, true);
                locationMap.invalidateSize();
                return;
            }

            // Fix leaflet marker asset paths
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: '{{ asset("assets/leaflet/images/marker-icon-2x.png") }}',
                iconUrl: '{{ asset("assets/leaflet/images/marker-icon.png") }}',
                shadowUrl: '{{ asset("assets/leaflet/images/marker-shadow.png") }}',
            });

            locationMap = L.map('locationPickerMap', {
                center: [initialLat, initialLng],
                zoom: 16,
                scrollWheelZoom: 'center'
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(locationMap);

            locationMarker = L.marker([initialLat, initialLng], {
                draggable: true,
                autoPan: true
            }).addTo(locationMap);

            locationCircle = L.circle([initialLat, initialLng], {
                radius: initialRadius,
                color: '#0b52aa',
                fillColor: '#0b52aa',
                fillOpacity: 0.15,
                weight: 2
            }).addTo(locationMap);

            locationMarker.on('drag', function (e) {
                const pos = e.target.getLatLng();
                locationCircle.setLatLng(pos);
                updateInputs(pos.lat, pos.lng);
            });

            locationMarker.on('dragend', function (e) {
                const pos = e.target.getLatLng();
                locationCircle.setLatLng(pos);
                syncToLivewire(pos.lat, pos.lng);
            });

            locationMap.on('click', function (e) {
                const pos = e.latlng;
                locationMarker.setLatLng(pos);
                locationCircle.setLatLng(pos);
                syncToLivewire(pos.lat, pos.lng);
            });
        }

        // Initialize when modal is shown
        $(document).on('shown.bs.modal', '#workLocationModal', function () {
            const lat = parseFloat($('#latitude').val()) || -6.5715000;
            const lng = parseFloat($('#longitude').val()) || 107.7587000;
            const radius = parseInt($('#radius_meters').val()) || 50;

            initLeafletMap(lat, lng, radius);

            setTimeout(function () {
                if (locationMap) {
                    locationMap.invalidateSize();
                    setMapLocation(lat, lng, radius, true);
                }
            }, 250);
        });

        // Livewire event listener
        document.addEventListener('livewire:init', function () {
            Livewire.on('work-location-init-map', function (data) {
                const payload = Array.isArray(data) ? data[0] : (data?.params || data);
                const lat = payload?.lat ?? parseFloat($('#latitude').val()) ?? -6.5715000;
                const lng = payload?.lng ?? parseFloat($('#longitude').val()) ?? 107.7587000;
                const radius = payload?.radius ?? parseInt($('#radius_meters').val()) ?? 50;

                initLeafletMap(lat, lng, radius);
                setTimeout(function () {
                    if (locationMap) {
                        locationMap.invalidateSize();
                        setMapLocation(lat, lng, radius, true);
                    }
                }, 200);
            });
        });

        // Radius input listener
        $(document).on('input change', '#radius_meters', function () {
            const rad = parseInt(this.value) || 50;
            if (locationCircle) {
                locationCircle.setRadius(rad);
            }
        });

        // Manual coordinate change listener
        $(document).on('change', '#latitude, #longitude', function () {
            const lat = parseFloat($('#latitude').val());
            const lng = parseFloat($('#longitude').val());
            const radius = parseInt($('#radius_meters').val()) || 50;
            if (!isNaN(lat) && !isNaN(lng)) {
                setMapLocation(lat, lng, radius, true);
            }
        });

        // Use Current GPS Location button
        $(document).on('click', '#btnUseCurrentLocation', function () {
            const btn = $(this);
            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung layanan Geolocation.');
                return;
            }

            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mendeteksi GPS...');

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    btn.prop('disabled', false).html(originalHtml);
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const radius = parseInt($('#radius_meters').val()) || 50;

                    setMapLocation(lat, lng, radius, true);
                    syncToLivewire(lat, lng);
                },
                function (err) {
                    btn.prop('disabled', false).html(originalHtml);
                    let msg = 'Gagal mendeteksi koordinat GPS.';
                    if (err.code === 1) {
                        msg = 'Izin akses lokasi ditolak oleh browser/pengguna.';
                    } else if (err.code === 2) {
                        msg = 'Sinyal lokasi atau GPS tidak dapat diakses.';
                    } else if (err.code === 3) {
                        msg = 'Permintaan lokasi GPS timed out.';
                    }
                    alert(msg);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    })();
</script>
@endpush
