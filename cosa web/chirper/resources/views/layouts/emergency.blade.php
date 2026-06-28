<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Reporte Rápido — {{ config('app.name', 'SGI Santa Cruz') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <x-reports.rapido-styles />

    @stack('head')
</head>
<body class="rapido-body font-[Outfit,sans-serif] bg-slate-50 text-slate-900 antialiased">
    <header class="rapido-header sticky top-0 z-[2000] bg-white/90 backdrop-blur-md border-b border-slate-200 px-4 py-3 flex items-center justify-between safe-top">
        <div class="flex items-center gap-2">
            <span class="text-xl" aria-hidden="true">🌊</span>
            <span class="font-bold text-slate-800 text-sm sm:text-base">Reporte Rápido</span>
        </div>
        <a href="{{ route('login', [], false) }}" class="text-xs sm:text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
            Iniciar sesión
        </a>
    </header>

    <main class="rapido-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
