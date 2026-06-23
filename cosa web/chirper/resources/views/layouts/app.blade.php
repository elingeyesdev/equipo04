<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php($apiUser = (array) session('api_user', []))
    @php($apiRole = (string) ($apiUser['role'] ?? ''))

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Gobierno Autónomo de Santa Cruz') }}</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
        }
        .no-scrollbar {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
    </style>

    <script>
        window.normalizeProvName = function(name) {
            let n = name.replace(/Provincia\s+/i, '').trim().toLowerCase();
            const dict = {
                'velasco':            'josé miguel de velasco',
                'warnes':             'ignacio warnes',
                'manuel m. caballero':'manuel maría caballero'
            };
            return dict[n] || n;
        };

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
        window.normalizeGeoName = window.normalizeMuniName;
    </script>
</head>

<body class="min-h-screen bg-gray-50 text-gray-900 antialiased flex flex-col font-sans">
    
    <!-- GOBIERNO TOP BAR (Branding institucional) -->
    <div class="bg-primary-900 text-white w-full py-2 px-4 shadow-md z-[100] relative">
        <div class="max-w-7xl mx-auto flex justify-between items-center text-xs sm:text-sm font-medium tracking-wide">
            <div class="flex items-center gap-3">
                <!-- Escudo/Logo Placeholder -->
                <div class="w-6 h-6 bg-white/20 rounded flex items-center justify-center">
                    <svg class="w-4 h-4 fill-current text-white" viewBox="0 0 512 512"><path d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zm0 464c-114.7 0-208-93.3-208-208S141.3 48 256 48s208 93.3 208 208s-93.3 208-208 208zm80-208H176c-13.3 0-24 10.7-24 24s10.7 24 24 24h160c13.3 0 24-10.7 24-24s-10.7-24-24-24z"/></svg>
                </div>
                <span>SISTEMA DE GESTIÓN DE DESASTRES</span>
            </div>
            <div class="hidden sm:block text-primary-100">Gobierno Autónomo Departamental de Santa Cruz</div>
        </div>
    </div>

    <!-- MAIN NAVIGATION BAR -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-[90] shadow-sm w-full">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            
            <div class="flex items-center gap-6">
                <a href="{{ route('reports.index', [], false) }}" class="font-bold text-xl text-primary-800 tracking-tight font-heading flex items-center gap-2">
                    <span class="text-primary-600">ISCZ</span> Portal
                </a>
                
                <!-- Main Nav Links -->
                @if (session()->has('api_token'))
                <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
                    <a href="{{ route('reports.index', [], false) }}" class="px-4 py-2 rounded-md transition-colors {{ request()->routeIs('reports.index') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">Reportes</a>
                    <a href="{{ route('logistica.index', [], false) }}" class="px-4 py-2 rounded-md transition-colors {{ request()->routeIs('logistica.index') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">Logística</a>
                    <a href="{{ route('inventario.index', [], false) }}" class="px-4 py-2 rounded-md transition-colors {{ request()->routeIs('inventario.*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">Inventario</a>
                    <a href="{{ route('victimas.index', [], false) }}" class="px-4 py-2 rounded-md transition-colors {{ request()->routeIs('victimas.index') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">Víctimas</a>
                </nav>
                @endif
            </div>

            <nav class="flex items-center gap-4 text-sm">
                @if (session()->has('api_token'))
                    <!-- User Info & Geolocation & Notifications -->
                    <div class="flex items-center gap-3">
                        <button id="btn-geolocate" class="p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-colors" title="Guardar mi ubicación">
                            <span id="geo-btn-icon" class="w-5 h-5 block">
                                <svg class="w-full h-full fill-current" viewBox="0 0 640 640"><path d="M541.9 139.5C546.4 127.7 543.6 114.3 534.7 105.4C525.8 96.5 512.4 93.6 500.6 98.2L84.6 258.2C71.9 263 63.7 275.2 64 288.7C64.3 302.2 73.1 314.1 85.9 318.3L262.7 377.2L321.6 554C325.9 566.8 337.7 575.6 351.2 575.9C364.7 576.2 376.9 568 381.8 555.4L541.8 139.4z"/></svg>
                            </span>
                        </button>

                        <div class="relative">
                            <button id="notifications-toggle" class="p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-colors relative" title="Notificaciones">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <span id="notifications-badge" class="hidden absolute top-0 right-0 min-w-[16px] h-[16px] rounded-full bg-red-600 text-[9px] font-bold text-white flex items-center justify-center border-2 border-white">0</span>
                            </button>
                            <!-- Notifs panel -->
                            <div id="notifications-panel" class="hidden absolute right-0 z-50 mt-2 w-80 rounded-lg border border-gray-200 bg-white shadow-lg overflow-hidden">
                                <div class="bg-gray-50 border-b border-gray-200 px-4 py-2.5 text-xs font-bold text-gray-700 uppercase">Notificaciones</div>
                                <div id="notifications-list" class="max-h-72 overflow-y-auto">
                                    <div class="px-4 py-5 text-sm text-center text-gray-500">Sin notificaciones por ahora.</div>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile Link -->
                        <div class="hidden sm:block ml-2">
                            <a href="{{ route('profile.show') }}" class="flex items-center gap-2 p-1 pr-3 rounded-full bg-gray-50 border border-gray-200 hover:bg-gray-100 transition-colors">
                                <div class="w-8 h-8 rounded-full bg-primary-800 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                    {{ strtoupper(substr($apiUser['name'] ?? 'U', 0, 1)) }}
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-800 leading-none text-xs">{{ (string) ($apiUser['name'] ?? '') }}</span>
                                    <span class="text-primary-600 text-[10px] font-medium uppercase mt-0.5">{{ $apiRole }}</span>
                                </div>
                            </a>
                        </div>

                        <!-- Logout Form -->
                        <form method="POST" action="{{ route('logout', [], false) }}" class="ml-2">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-red-600 hover:bg-red-50 px-3 py-1.5 rounded border border-red-200 transition-colors">Salir</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login', [], false) }}" class="font-medium text-gray-600 hover:text-primary-700 transition-colors">Acceso Autoridades</a>
                @endif
            </nav>

        </div>
    </header>

    <!-- MOBILE NAVIGATION (Visible only on small screens) -->
    @if (session()->has('api_token'))
    <nav class="md:hidden bg-white border-b border-gray-200 overflow-x-auto no-scrollbar flex items-center px-4 py-2 gap-2 shadow-inner">
        <a href="{{ route('reports.index', [], false) }}" class="px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap {{ request()->routeIs('reports.index') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">Reportes</a>
        <a href="{{ route('logistica.index', [], false) }}" class="px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap {{ request()->routeIs('logistica.index') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">Logística</a>
        <a href="{{ route('inventario.index', [], false) }}" class="px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap {{ request()->routeIs('inventario.*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">Inventario</a>
        <a href="{{ route('victimas.index', [], false) }}" class="px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap {{ request()->routeIs('victimas.index') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">Víctimas</a>
    </nav>
    @endif

    <!-- PAGE CONTENT -->
    <main class="w-full flex-1 max-w-7xl mx-auto px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border-l-4 border-green-500 bg-green-50 p-4 text-sm text-green-800 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-md border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-800 shadow-sm">
                <div class="font-bold mb-1">Se encontraron los siguientes errores:</div>
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

    <!-- FOOTER INSTITUCIONAL -->
    <footer class="bg-white border-t border-gray-200 mt-auto py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center md:items-start gap-6">
                <!-- Información Principal -->
                <div class="text-center md:text-left">
                    <p class="text-sm text-gray-500 font-medium">&copy; {{ date('Y') }} Gobierno Autónomo Departamental de Santa Cruz. Todos los derechos reservados.</p>
                    <p class="text-xs mt-1 text-gray-400">Sistema Integrado de Gestión y Transparencia de Desastres</p>
                </div>
                
                <!-- Enlaces Rápidos -->
                <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm">
                    <a href="{{ route('sugerencias.index') }}" class="text-gray-500 hover:text-primary-700 transition-colors font-medium">Foro de Sugerencias</a>
                    <a href="{{ route('faq.index') }}" class="text-gray-500 hover:text-primary-700 transition-colors font-medium">Preguntas Frecuentes (FAQ)</a>
                    <a href="{{ route('contact.index') }}" class="text-gray-500 hover:text-primary-700 transition-colors font-medium">Contáctanos</a>
                </nav>
            </div>
        </div>
    </footer>

    <!-- Global Image Modal -->
    <div id="global-image-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-gray-900/90 backdrop-blur-sm transition-opacity duration-300 opacity-0" style="display: none;">
        <button id="global-image-modal-close" class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300 transition-colors cursor-pointer" aria-label="Cerrar">&times;</button>
        <div class="relative max-w-5xl max-h-[90vh] p-4 flex items-center justify-center">
            <img id="global-image-modal-img" src="" alt="Vista ampliada" class="max-w-full max-h-[85vh] rounded shadow-2xl object-contain scale-95 transition-transform duration-300">
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
                    let src = trigger.tagName === 'IMG' ? trigger.getAttribute('src') : trigger.getAttribute('href');
                    if (trigger.tagName === 'A') e.preventDefault();
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
                modal.classList.add('opacity-0');
                modalImg.classList.remove('scale-100');
                modalImg.classList.add('scale-95');
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.classList.add('hidden');
                    modalImg.src = '';
                }, 300);
            };

            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal || e.target.closest('#global-image-modal-close')) closeModal();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
            });
        }
    });
    </script>

    @include('chat.widget')
</body>

<script>
(function () {
    const LAT_KEY = 'app_user_lat';
    const LNG_KEY = 'app_user_lng';

    window.getUserLocation = function () {
        const lat = parseFloat(localStorage.getItem(LAT_KEY));
        const lng = parseFloat(localStorage.getItem(LNG_KEY));
        if (isNaN(lat) || isNaN(lng)) return null;
        return { lat, lng };
    };

    const btn = document.getElementById('btn-geolocate');
    if (!btn) return;

    function updateBtnState() {
        const loc = window.getUserLocation();
        if (loc) {
            btn.title = 'Ubicación guardada ✓ — clic para actualizar';
            btn.classList.add('text-primary-600');
            btn.classList.remove('text-gray-500');
        } else {
            btn.title = 'Guardar mi ubicación';
            btn.classList.remove('text-primary-600');
            btn.classList.add('text-gray-500');
        }
    }

    updateBtnState();

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Tu navegador no soporta geolocalización.');
            return;
        }
        btn.disabled = true;

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