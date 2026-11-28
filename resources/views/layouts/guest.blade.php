<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'PT. SINTESA NIAGA KARYA - Login' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/sintesa-icon.png') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- General CSS Files -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-tailwind.compiled.css') }}?v={{ @filemtime(public_path('assets/css/bootstrap-tailwind.compiled.css')) }}">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style-tailwind.compiled.css') }}?v={{ @filemtime(public_path('assets/css/style-tailwind.compiled.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components-tailwind.compiled.css') }}?v={{ @filemtime(public_path('assets/css/components-tailwind.compiled.css')) }}">

    <style>
        html,
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            background-color: #f9fafb !important;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        .pnj-auth-shell {
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            box-sizing: border-box;
            background-color: #f9fafb;
        }

        @media (min-width: 640px) {
            .pnj-auth-shell {
                padding: 48px 24px;
            }
        }
    </style>

    @livewireStyles
    @stack('styles')
</head>

<body class="antialiased">
    <div id="app" class="pnj-auth-shell">
        {{ $slot }}
    </div>

    <!-- General JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/stisla.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Template JS File -->
    <script src="{{ asset('assets/js/scripts.js') }}"></script>
    <script src="{{ asset('assets/js/custom.js') }}"></script>

    @livewireScripts
    @stack('scripts')
</body>

</html>
