<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Reporte Rápido — {{ config('app.name', 'SGI Santa Cruz') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <x-reports.rapido-styles />

    @stack('head')
</head>
<body class="rapido-body antialiased">
    <header class="rapido-header safe-top">
        <a href="{{ route('login', [], false) }}" class="rapido-brand">
            <span class="rapido-brand-accent">ISCZ</span> Portal
        </a>
        <a href="{{ route('login', [], false) }}" class="rapido-header-link">
            Iniciar sesión
        </a>
    </header>

    <main class="rapido-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
