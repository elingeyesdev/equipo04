<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php($apiUser = (array) session('api_user', []))
    @php($apiRole = (string) ($apiUser['role'] ?? ''))

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Flood Reports') }}</title>
    <meta name="api-user-role" content="{{ $apiRole }}">
    @if (session()->has('api_token'))
        <meta name="reports-notifications-endpoint" content="{{ route('reports.notifications.feed', [], false) }}">
        <meta name="api-user-carnet" content="{{ (string) ($apiUser['carnet'] ?? '') }}">
        <meta name="reverb-app-key" content="{{ config('broadcasting.connections.reverb.key') }}">
        <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host', '127.0.0.1') }}">
        <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port', 8080) }}">
        <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme', 'http') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        /* Ocultar barra de desplazamiento en todos los navegadores para elementos con .no-scrollbar */
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
        }
        .no-scrollbar {
            -ms-overflow-style: none !important;  /* IE y Edge */
            scrollbar-width: none !important;  /* Firefox */
        }
    </style>

    <script>
        // Normaliza nombre de PROVINCIA desde el GeoJSON al nombre oficial
        window.normalizeProvName = function(name) {
            let n = name.replace(/Provincia\s+/i, '').trim().toLowerCase();
            const dict = {
                'velasco':            'josé miguel de velasco',
                'warnes':             'ignacio warnes',
                'manuel m. caballero':'manuel maría caballero'
            };
            return dict[n] || n;
        };

        // Normaliza nombre de MUNICIPIO desde el GeoJSON al nombre oficial
        window.normalizeMuniName = function(name) {
            let n = name.replace(/Municipio\s+/i, '').trim().toLowerCase();
            const dict = {
                'ascención de guarayos': 'ascensión de guarayos',
                'san antonio de lomerio':'san antonio de lomerío',
                'san rafael':            'san rafael de velasco',
                'charagua':              'charagua iyambae',
                'gutiérrez':             'kereimba iyaambae',
                'san juan':              'san juan de yapacaní',
                'pampa grande':          'pampagrande',
                'postrer valle':         'postrervalle',
                'pucará':                'pucara',
                'trigal':                'el trigal',
                'porongo (ayacucho)':    'porongo'
            };
            return dict[n] || n;
        };

        // Mantener compatibilidad con código antiguo que use normalizeGeoName
        window.normalizeGeoName = window.normalizeMuniName;
    </script>
</head>

<body class="min-h-screen bg-gray-50 text-gray-900 antialiased flex">
    @if (session()->has('api_token'))
        <!-- SIDEBAR INTERACTIVO -->
        <aside class="fixed inset-y-0 left-0 bg-[#0f172a] text-gray-300 w-16 hover:w-64 transition-all duration-300 ease-in-out z-[2000] overflow-x-hidden overflow-y-hidden flex flex-col group border-r border-gray-800 shadow-2xl no-scrollbar">
            <!-- Logo / Brand -->
            <div class="h-16 flex items-center px-4 border-b border-gray-800 shrink-0 bg-[#0b1120]">
                <div class="min-w-[32px] h-8 flex items-center justify-center bg-blue-600 text-white font-bold rounded-lg shadow-lg shadow-blue-500/30">
                    ISCZ
                </div>
                <span class="ml-4 font-bold text-lg text-white opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap tracking-tight">
                    Inundaciones SCZ
                </span>
            </div>
            
            <!-- Navigation Links -->
            <nav class="flex-1 py-6 flex flex-col gap-1 overflow-y-auto no-scrollbar">
                <a href="{{ route('reports.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('reports.index') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Reportes">
                    <svg class="w-5 h-5 min-w-[20px] fill-current" viewBox="0 0 640 640"><path d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM248 320C234.7 320 224 330.7 224 344C224 357.3 234.7 368 248 368L392 368C405.3 368 416 357.3 416 344C416 330.7 405.3 320 392 320L248 320zM248 416C234.7 416 224 426.7 224 440C224 453.3 234.7 464 248 464L392 464C405.3 464 416 453.3 416 440C416 426.7 405.3 416 392 416L248 416z"/></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Reportes</span>
                </a>
                

                
                <a href="{{ route('vehiculos.mapa', [], false) }}" class="hidden flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('vehiculos.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Rastreo de Vehículos">
                    <svg class="w-6 h-6 min-w-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Vehículos</span>
                </a>
                
                <a href="{{ route('logistica.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('logistica.index') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Logística">
                    <svg class="w-5 h-5 min-w-[20px] fill-current" viewBox="0 0 640 640"><path d="M64 128C64 92.7 92.7 64 128 64L384 64C419.3 64 448 92.7 448 128L448 272.7C412.3 275.6 379.5 288.3 352 308.1L352 304.1C352 295.3 344.8 288.1 336 288.1L304 288.1C295.2 288.1 288 295.3 288 304.1L288 336.1C288 344.9 295.2 352.1 304 352.1L308 352.1C294.2 371.3 283.9 393.1 277.9 416.6C276 416.2 274 416.1 272 416.1L240 416.1C222.3 416.1 208 430.4 208 448.1L208 528.1L282.9 528.1C289 545.4 297.5 561.5 308 576.1L128 576C92.7 576 64 547.3 64 512L64 128zM176 160C167.2 160 160 167.2 160 176L160 208C160 216.8 167.2 224 176 224L208 224C216.8 224 224 216.8 224 208L224 176C224 167.2 216.8 160 208 160L176 160zM288 176L288 208C288 216.8 295.2 224 304 224L336 224C344.8 224 352 216.8 352 208L352 176C352 167.2 344.8 160 336 160L304 160C295.2 160 288 167.2 288 176zM176 288C167.2 288 160 295.2 160 304L160 336C160 344.8 167.2 352 176 352L208 352C216.8 352 224 344.8 224 336L224 304C224 295.2 216.8 288 208 288L176 288zM320 464C320 384.5 384.5 320 464 320C543.5 320 608 384.5 608 464C608 543.5 543.5 608 464 608C384.5 608 320 543.5 320 464zM460.7 396.7C454.5 402.9 454.5 413.1 460.7 419.3L489.4 448L400 448C391.2 448 384 455.2 384 464C384 472.8 391.2 480 400 480L489.4 480L460.7 508.7C454.5 514.9 454.5 525.1 460.7 531.3C466.9 537.5 477.1 537.5 483.3 531.3L539.3 475.3C545.5 469.1 545.5 458.9 539.3 452.7L483.3 396.7C477.1 390.5 466.9 390.5 460.7 396.7z"/></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Logística</span>
                </a>
                
                <a href="{{ route('donaciones.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('donaciones.index') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Donaciones">
                    <svg class="w-5 h-5 min-w-[20px] fill-current" viewBox="0 0 640 640"><path d="M320 48C306.7 48 296 58.7 296 72L296 84L294.2 84C257.6 84 228 113.7 228 150.2C228 183.6 252.9 211.8 286 215.9L347 223.5C352.1 224.1 356 228.5 356 233.7C356 239.4 351.4 243.9 345.8 243.9L272 244C256.5 244 244 256.5 244 272C244 287.5 256.5 300 272 300L296 300L296 312C296 325.3 306.7 336 320 336C333.3 336 344 325.3 344 312L344 300L345.8 300C382.4 300 412 270.3 412 233.8C412 200.4 387.1 172.2 354 168.1L293 160.5C287.9 159.9 284 155.5 284 150.3C284 144.6 288.6 140.1 294.2 140.1L360 140C375.5 140 388 127.5 388 112C388 96.5 375.5 84 360 84L344 84L344 72C344 58.7 333.3 48 320 48zM141.3 405.5L98.7 448L64 448C46.3 448 32 462.3 32 480L32 544C32 561.7 46.3 576 64 576L384.5 576C413.5 576 441.8 566.7 465.2 549.5L591.8 456.2C609.6 443.1 613.4 418.1 600.3 400.3C587.2 382.5 562.2 378.7 544.4 391.8L424.6 480L312 480C298.7 480 288 469.3 288 456C288 442.7 298.7 432 312 432L384 432C401.7 432 416 417.7 416 400C416 382.3 401.7 368 384 368L231.8 368C197.9 368 165.3 381.5 141.3 405.5z"/></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Donaciones</span>
                </a>
                
                <a href="{{ route('victimas.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('victimas.index') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Víctimas">
                    <svg class="w-5 h-5 min-w-[20px] fill-current" viewBox="0 0 640 640"><path d="M280 88C280 57.1 254.9 32 224 32C193.1 32 168 57.1 168 88C168 118.9 193.1 144 224 144C254.9 144 280 118.9 280 88zM304 300.7L341 350.6C353.8 333.1 369.5 317.9 387.3 305.6L331.1 229.9C306 196 266.3 176 224 176C181.7 176 142 196 116.8 229.9L46.3 324.9C35.8 339.1 38.7 359.1 52.9 369.7C67.1 380.3 87.1 377.3 97.7 363.1L144 300.7L144 576C144 593.7 158.3 608 176 608C193.7 608 208 593.7 208 576L208 416C208 407.2 215.2 400 224 400C232.8 400 240 407.2 240 416L240 576C240 593.7 254.3 608 272 608C289.7 608 304 593.7 304 576L304 300.7zM496 608C575.5 608 640 543.5 640 464C640 384.5 575.5 320 496 320C416.5 320 352 384.5 352 464C352 543.5 416.5 608 496 608zM496 508C507 508 516 517 516 528C516 539 507 548 496 548C485 548 476 539 476 528C476 517 485 508 496 508zM496 368C504.8 368 512 375.2 512 384L512 464C512 472.8 504.8 480 496 480C487.2 480 480 472.8 480 464L480 384C480 375.2 487.2 368 496 368z"/></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Víctimas</span>
                </a>
                
                @if ($apiRole === 'authority')
                <a href="{{ route('command-center.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('command-center.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'hover:bg-gray-800 hover:text-white text-indigo-400' }}" title="Análisis de impacto">
                    <svg class="w-5 h-5 min-w-[20px] fill-current" viewBox="0 0 640 640"><path d="M256 144C256 117.5 277.5 96 304 96L336 96C362.5 96 384 117.5 384 144L384 496C384 522.5 362.5 544 336 544L304 544C277.5 544 256 522.5 256 496L256 144zM64 336C64 309.5 85.5 288 112 288L144 288C170.5 288 192 309.5 192 336L192 496C192 522.5 170.5 544 144 544L112 544C85.5 544 64 522.5 64 496L64 336zM496 160L528 160C554.5 160 576 181.5 576 208L576 496C576 522.5 554.5 544 528 544L496 544C469.5 544 448 522.5 448 496L448 208C448 181.5 469.5 160 496 160z"/></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Análisis de impacto</span>
                </a>
                @endif
            </nav>
            
            <!-- Bottom Actions -->
            <div class="p-3 border-t border-gray-800">
                <form method="POST" action="{{ route('logout', [], false) }}" class="w-full">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-3 text-red-400 hover:text-red-300 hover:bg-gray-800 rounded-lg transition-colors" title="Salir">
                        <svg class="w-6 h-6 min-w-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </aside>
    @endif

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-h-screen {{ session()->has('api_token') ? 'ml-16' : '' }} transition-all duration-300 w-full relative">
        
        <!-- HEADER TOP BAR -->
        <header class="border-b border-gray-200 bg-white/80 backdrop-blur-md sticky top-0 z-[90] shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between gap-4">
                
                <div class="flex items-center gap-4">
                    @if (!session()->has('api_token'))
                    <a href="{{ route('reports.index', [], false) }}" class="font-bold text-xl tracking-tight text-blue-600 hover:text-blue-700 transition-colors">
                        {{ config('app.name', 'Flood Reports') }}
                    </a>
                    @else
                    <span class="text-sm font-semibold text-gray-500 uppercase tracking-widest hidden sm:block">Panel de Control</span>
                    @endif
                </div>

                <nav class="flex items-center gap-2 sm:gap-4 text-sm">
                    @if (session()->has('api_token'))
                        
                        <!-- User Info -->
                        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-100 border border-gray-200">
                            <div class="w-6 h-6 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 font-bold text-xs">
                                {{ strtoupper(substr($apiUser['name'] ?? 'U', 0, 1)) }}
                            </div>
                            <span class="font-medium text-gray-700 max-w-[10rem] truncate">{{ (string) ($apiUser['name'] ?? '') }}</span>
                            @if ($apiRole !== '')
                                <span class="bg-white text-gray-500 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wide border border-gray-200">{{ $apiRole }}</span>
                            @endif
                        </div>

                        <!-- Botón Geolocalización -->
                        <button id="btn-geolocate" class="rounded-full w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors border border-transparent hover:border-blue-200" title="Guardar mi ubicación">
                            <span id="geo-btn-icon" class="w-5 h-5 flex items-center justify-center"><svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M541.9 139.5C546.4 127.7 543.6 114.3 534.7 105.4C525.8 96.5 512.4 93.6 500.6 98.2L84.6 258.2C71.9 263 63.7 275.2 64 288.7C64.3 302.2 73.1 314.1 85.9 318.3L262.7 377.2L321.6 554C325.9 566.8 337.7 575.6 351.2 575.9C364.7 576.2 376.9 568 381.8 555.4L541.8 139.4z"/></svg></span>
                        </button>

                        <!-- Notificaciones -->
                        <div class="relative">
                            <button id="notifications-toggle" class="rounded-full w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors border border-transparent hover:border-blue-200 relative" title="Notificaciones">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <span id="notifications-badge" class="hidden absolute top-0 right-0 min-w-[18px] h-[18px] rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center shadow-sm border-2 border-white">0</span>
                            </button>
                            <div id="notifications-panel" class="hidden absolute right-0 z-50 mt-2 w-80 rounded-xl border border-gray-200 bg-white shadow-xl overflow-hidden">
                                <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Notificaciones
                                </div>
                                <div id="notifications-list" class="max-h-80 overflow-y-auto">
                                    <div class="px-4 py-6 text-sm text-center text-gray-500">Sin notificaciones por ahora.</div>
                                </div>
                            </div>
                        </div>

                    @else
                        <a href="{{ route('login', [], false) }}" class="rounded-full px-4 py-2 text-gray-600 hover:bg-gray-100 font-medium transition-colors">Iniciar Sesión</a>
                        <a href="{{ route('register', [], false) }}" class="rounded-full bg-gray-900 px-5 py-2 font-medium text-white shadow hover:bg-gray-800 transition-colors">Registrarse</a>
                    @endif
                </nav>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="mx-auto w-full max-w-7xl px-4 py-8 flex-1">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
                <div class="font-medium mb-1">Revisá los errores:</div>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
        </main>
    </div>{{-- /content wrapper --}}

    <!-- Global Image Modal -->
    <div id="global-image-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black bg-opacity-85 backdrop-blur-sm transition-all duration-300" style="display: none;">
        <button id="global-image-modal-close" class="absolute top-4 right-4 text-white text-5xl font-light hover:text-gray-300 focus:outline-none transition-colors select-none cursor-pointer" aria-label="Cerrar">&times;</button>
        <div class="relative max-w-4xl max-h-[90vh] p-4 flex items-center justify-center">
            <img id="global-image-modal-img" src="" alt="Vista ampliada" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl border border-gray-800 transform scale-95 transition-all duration-300 object-contain">
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('global-image-modal');
        const modalImg = document.getElementById('global-image-modal-img');
        const modalClose = document.getElementById('global-image-modal-close');

        if (modal && modalImg && modalClose) {
            document.addEventListener('click', function(e) {
                const trigger = e.target.closest('.clickable-image');
                if (trigger) {
                    let src = '';
                    if (trigger.tagName === 'IMG') {
                        src = trigger.getAttribute('src');
                    } else if (trigger.tagName === 'A') {
                        e.preventDefault();
                        src = trigger.getAttribute('href');
                    }

                    if (src) {
                        modalImg.src = src;
                        modal.style.display = 'flex';
                        modal.classList.remove('hidden');
                        setTimeout(() => {
                            modal.classList.remove('opacity-0');
                            modalImg.classList.remove('scale-95');
                            modalImg.classList.add('scale-100');
                        }, 10);
                    }
                }
            });

            const closeModal = function() {
                modalImg.classList.remove('scale-100');
                modalImg.classList.add('scale-95');
                modal.classList.add('opacity-0');
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.classList.add('hidden');
                    modalImg.src = '';
                }, 300);
            };

            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal || e.target.closest('#global-image-modal-close')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'flex') {
                    closeModal();
                }
            });
        }
    });
    </script>

    @include('chat.widget')
</body>

<script>
// ─────────────────────────────────────────────────────────────
// Geolocalización global (layouts/app.blade.php)
// ─────────────────────────────────────────────────────────────
// La ubicación se pide UNA sola vez mediante el botón 📍 del nav.
// Se persiste en localStorage con las claves 'app_user_lat' y 'app_user_lng'
// para que cualquier módulo (Logística, Reportes, Mapas) la consuma
// sin volver a pedirla al usuario.
//
// API pública expuesta:
//   window.getUserLocation() → { lat, lng } | null
// ─────────────────────────────────────────────────────────────
(function () {
    const LAT_KEY = 'app_user_lat';
    const LNG_KEY = 'app_user_lng';

    // Lee la ubicación guardada; devuelve {lat, lng} o null si no existe
    window.getUserLocation = function () {
        const lat = parseFloat(localStorage.getItem(LAT_KEY));
        const lng = parseFloat(localStorage.getItem(LNG_KEY));
        if (isNaN(lat) || isNaN(lng)) return null;
        return { lat, lng };
    };

    const btn = document.getElementById('btn-geolocate');
    if (!btn) return; // no hay sesión activa

    function updateBtnState() {
        const loc = window.getUserLocation();
        if (loc) {
            btn.title    = 'Ubicación guardada ✓ — clic para actualizar';
            btn.querySelector('#geo-btn-icon').innerHTML = '<svg class="w-5 h-5 fill-current" viewBox="0 0 640 640"><path d="M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z"/></svg>';
        } else {
            btn.title    = 'Guardar mi ubicación para encontrar centros cercanos';
            btn.querySelector('#geo-btn-icon').innerHTML = '<svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M541.9 139.5C546.4 127.7 543.6 114.3 534.7 105.4C525.8 96.5 512.4 93.6 500.6 98.2L84.6 258.2C71.9 263 63.7 275.2 64 288.7C64.3 302.2 73.1 314.1 85.9 318.3L262.7 377.2L321.6 554C325.9 566.8 337.7 575.6 351.2 575.9C364.7 576.2 376.9 568 381.8 555.4L541.8 139.4z"/></svg>';
        }
    }

    updateBtnState(); // estado inicial al cargar la página

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Tu navegador no soporta geolocalización.');
            return;
        }
        btn.disabled = true;
        btn.querySelector('#geo-btn-icon').innerHTML = '<svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M128 96C128 78.3 142.3 64 160 64L480 64C497.7 64 512 78.3 512 96C512 113.7 497.7 128 480 128L480 139C480 181.4 463.1 222.1 433.1 252.1L365.2 320L433.1 387.9C463.1 417.9 480 458.6 480 501L480 512C497.7 512 512 526.3 512 544C512 561.7 497.7 576 480 576L160 576C142.3 576 128 561.7 128 544C128 526.3 142.3 512 160 512L160 501C160 458.6 176.9 417.9 206.9 387.9L274.8 320L206.9 252.1C176.9 222.1 160 181.4 160 139L160 128C142.3 128 128 113.7 128 96zM224 128L224 139C224 164.5 234.1 188.9 252.1 206.9L320 274.8L387.9 206.9C405.9 188.9 416 164.5 416 139L416 128L224 128zM224 512L416 512L416 501C416 475.5 405.9 451.1 387.9 433.1L320 365.2L252.1 433.1C234.1 451.1 224 475.5 224 501L224 512z"/></svg>';

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                localStorage.setItem(LAT_KEY, pos.coords.latitude);
                localStorage.setItem(LNG_KEY, pos.coords.longitude);
                btn.disabled = false;
                updateBtnState();
            },
            function (err) {
                alert('No se pudo obtener la ubicación: ' + err.message);
                btn.disabled = false;
                updateBtnState();
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
})();
</script>

</html>