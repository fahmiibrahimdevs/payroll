<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Larashiz') }} &mdash; SHIZUEFI</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/larashiz-logo.jpg') }}">

    <!-- General CSS Files -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-tailwind.compiled.css') }}">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style-tailwind.compiled.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components-tailwind.compiled.css') }}">

    @livewireStyles
    <style>
        /* Global Balanced Card Shadows (Clean, Crisp & Modern Depth) */
        .card,
        .card.card-statistic-1,
        .card.card-statistic-2 {
            box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.06), 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
            border: 1px solid #e9edf2 !important;
            margin-bottom: 24px;
        }

        .card.card-statistic-1 .card-icon,
        .card.card-statistic-2 .card-icon {
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.08) !important;
        }

        .shadow-sm {
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.06) !important;
        }

        .shadow,
        .shadow-md {
            box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.08), 0 2px 4px 0 rgba(0, 0, 0, 0.04) !important;
        }

        /* Mobile Responsive Spacing (Clean & Compact) */
        @media (max-width: 767.98px) {
            .card,
            .card.card-statistic-1,
            .card.card-statistic-2 {
                margin-bottom: 14px !important;
            }

            .row > [class*="col-"] {
                margin-bottom: 0 !important;
            }
        }

        /* ================= GLOBAL SELECT2 OPTGROUP & INDENTATION STYLING ================= */
        .select2-container .select2-results__group {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 7px 14px !important;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            margin-top: 4px;
            display: block;
            cursor: default;
        }
        .select2-container .select2-results__optgroup:first-child .select2-results__group {
            border-top: none;
            margin-top: 0;
        }
        .select2-container .select2-results__options--nested .select2-results__option {
            padding-left: 24px !important; /* Menjorok/tab ke depan */
            font-size: 12.5px !important;
            color: #334155;
            border-bottom: 1px solid #f8fafc;
        }
        .select2-container .select2-results__options--nested .select2-results__option--highlighted[aria-selected],
        .select2-container .select2-results__options--nested .select2-results__option--highlighted {
            background-color: #0b52aa !important;
            color: #ffffff !important;
        }
    </style>
    @stack('styles')
    {{ $styles ?? '' }}
</head>

<body>
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <div class="navbar-bg"></div>
            @include('layouts.shizuefi.navbar')
            @include('layouts.shizuefi.sidebar')

            <!-- Main Content -->
            <div class="main-content">
                <section class="section custom-section">
                    @if (isset($header))
                        <div class="section-header">
                            {{ $header }}
                        </div>
                    @endif

                    <div class="section-body">
                        {{ $slot ?? '' }}
                        @yield('content')
                    </div>
                </section>
            </div>

            @include('layouts.shizuefi.footer')
        </div>
    </div>

    <!-- General JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
    <script src="{{ asset('assets/js/stisla.js') }}"></script>

    <!-- Template JS Scripts -->
    <script src="{{ asset('assets/js/scripts.js') }}"></script>
    <script src="{{ asset('assets/js/custom.js') }}"></script>

    @livewireScripts

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Global Livewire & SweetAlert2 Bridge -->
    <script>
        document.addEventListener('livewire:init', () => {
            const getPayload = (event) => Array.isArray(event) ? event[0] : event;

            // 1. Toast Notification (Success, Info, Warning, Error)
            Livewire.on('swal:toast', (event) => {
                const data = getPayload(event);
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });

                Toast.fire({
                    icon: data.type || 'info',
                    title: data.message || ''
                });
            });

            // 2. Alert Modal Dialog (Success, Info, Warning, Error)
            Livewire.on('swal:alert', (event) => {
                const data = getPayload(event);
                const swalConfig = {
                    icon: data.type || 'info',
                    title: data.title || 'Pemberitahuan',
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#0b52aa'
                };
                if (data.html) {
                    swalConfig.html = data.html;
                } else {
                    swalConfig.text = data.message || data.text || '';
                }
                Swal.fire(swalConfig);
            });

            // 3. Generic Confirmation Dialog (Livewire & SweetAlert2)
            Livewire.on('swal:confirm', (event) => {
                const data = getPayload(event);
                Swal.fire({
                    title: data.title || 'Apakah Anda Yakin?',
                    text: data.text || 'Tindakan ini akan diproses!',
                    icon: data.icon || 'warning',
                    showCancelButton: true,
                    confirmButtonColor: data.confirmButtonColor || '#0b52aa',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: data.confirmButtonText || 'Ya, Lanjutkan!',
                    cancelButtonText: data.cancelButtonText || 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed && data.action) {
                        Livewire.dispatch(data.action, data.params || { id: data.id });
                    }
                });
            });

            // 4. Delete Confirmation Dialog
            Livewire.on('swal:confirm-delete', (event) => {
                const data = getPayload(event);
                const action = data.action || 'delete-post';
                Swal.fire({
                    title: data.title ? `Hapus "${data.title}"?` : 'Apakah Anda Yakin?',
                    text: data.text || (data.title ? `Data "${data.title}" akan dihapus permanen!` : 'Data ini akan dihapus permanen!'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#fc544b',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch(action, data.params || { id: data.id });
                    }
                });
            });

            // 4. Bootstrap Modal Handler
            Livewire.on('open-modal', (event) => {
                const data = getPayload(event);
                const modalId = data.id || 'postModal';
                $('#' + modalId).modal('show');
            });

            Livewire.on('close-modal', (event) => {
                const data = getPayload(event);
                const modalId = data.id || 'postModal';
                $('#' + modalId).modal('hide');
            });
        });

        // Global Chart.js Custom Floating HTML Tooltip (No Clipping, Auto-Fade Center Overlay)
        window.customChartTooltip = function(context) {
            const chart = context.chart;
            const tooltip = context.tooltip;
            const parent = chart && chart.canvas ? chart.canvas.parentNode : null;
            if (!parent) return;

            let tooltipEl = parent.querySelector('.custom-chartjs-tooltip');
            if (!tooltipEl) {
                tooltipEl = document.createElement('div');
                tooltipEl.className = 'custom-chartjs-tooltip';
                tooltipEl.style.position = 'absolute';
                tooltipEl.style.background = '#0f172a';
                tooltipEl.style.color = '#ffffff';
                tooltipEl.style.borderRadius = '8px';
                tooltipEl.style.padding = '8px 12px';
                tooltipEl.style.fontSize = '11.5px';
                tooltipEl.style.lineHeight = '1.35';
                tooltipEl.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3)';
                tooltipEl.style.pointerEvents = 'none';
                tooltipEl.style.zIndex = '50';
                tooltipEl.style.whiteSpace = 'nowrap';
                tooltipEl.style.opacity = '0';
                tooltipEl.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
                parent.appendChild(tooltipEl);
            }

            const centerEl = parent.querySelector('.chart-center-overlay');

            if (tooltip.opacity === 0) {
                tooltipEl.style.opacity = '0';
                if (centerEl) centerEl.style.opacity = '1';
                return;
            }

            if (centerEl) centerEl.style.opacity = '0';

            if (tooltip.body) {
                const titleLines = tooltip.title || [];
                const bodyLines = tooltip.body.map(function(b) { return b.lines; }).flat();

                let innerHtml = '';
                titleLines.forEach(function(title) {
                    innerHtml += '<div style="font-weight: 700; color: #ffffff; margin-bottom: 2px;">' + title + '</div>';
                });
                bodyLines.forEach(function(body) {
                    innerHtml += '<div style="font-weight: 500; color: #e2e8f0;">' + body + '</div>';
                });
                tooltipEl.innerHTML = innerHtml;
            }

            const canvas = chart.canvas;
            const parentWidth = parent.offsetWidth;
            const targetX = canvas.offsetLeft + tooltip.caretX;
            const targetY = canvas.offsetTop + tooltip.caretY;

            tooltipEl.style.opacity = '1';
            const tipWidth = tooltipEl.offsetWidth || 140;
            const tipHeight = tooltipEl.offsetHeight || 50;

            const halfWidth = tipWidth / 2;
            const margin = 8;
            const clampedX = Math.max(halfWidth + margin, Math.min(parentWidth - halfWidth - margin, targetX));

            if (targetY - tipHeight - 12 < 0) {
                tooltipEl.style.transform = 'translate(-50%, 0)';
                tooltipEl.style.top = (targetY + 12) + 'px';
            } else {
                tooltipEl.style.transform = 'translate(-50%, -100%)';
                tooltipEl.style.top = (targetY - 10) + 'px';
            }

            tooltipEl.style.left = clampedX + 'px';
        };
    </script>

    @stack('scripts')
    {{ $scripts ?? '' }}
</body>

</html>
