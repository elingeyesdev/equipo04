<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php($apiUser = (array) session('api_user', []))
    @php($apiRole = (string) ($apiUser['role'] ?? ''))

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Flood Reports') }}</title>
    <meta name="api-user-role" content="{{ $apiRole }}">
    @if ($apiRole === 'authority')
        <meta name="reports-notifications-endpoint" content="{{ route('reports.notifications.latest', [], false) }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

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

<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('reports.index', [], false) }}"
                class="font-semibold tracking-tight hover:underline underline-offset-4">
                {{ config('app.name', 'Flood Reports') }}
            </a>

            <nav class="flex items-center gap-1 text-sm">
                @if (session()->has('api_token'))
                    <a href="{{ route('reports.index', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        Reportes
                    </a>
                    <a href="{{ route('maps.index', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        Mapas
                    </a>
                    <a href="{{ route('logistica.index', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        Logística
                    </a>
                    <a href="{{ route('reports.create', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        Crear
                    </a>
                    <span class="hidden sm:inline-flex items-center gap-1 rounded-md px-3 py-2 text-gray-600">
                        <span class="truncate max-w-[14rem]">{{ (string) ($apiUser['name'] ?? '') }}</span>
                        @if ($apiRole !== '')
                            <span class="text-gray-400">·</span>
                            <span class="text-gray-500">{{ $apiRole }}</span>
                        @endif
                    </span>
                    {{-- Botón de geolocalización: pide la ubicación 1 sola vez y la guarda en localStorage --}}
                    {{-- Queda disponible para todos los módulos (Logística, Mapas, Reportes) via window.getUserLocation() --}}
                    <button id="btn-geolocate"
                        class="rounded-md px-2 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 flex items-center gap-1 text-sm"
                        title="Guardar mi ubicación para encontrar centros cercanos">
                        <span id="geo-btn-icon">📍</span>
                    </button>
                    <form method="POST" action="{{ route('logout', [], false) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                            Salir
                        </button>
                    </form>
                @else
                    <a href="{{ route('login', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">Login</a>
                    <a href="{{ route('register', [], false) }}"
                        class="rounded-md bg-gray-900 px-3 py-2 font-medium text-white hover:bg-gray-800">Registro</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
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

        @yield('content')
    </main>
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
            btn.querySelector('#geo-btn-icon').textContent = '✅';
        } else {
            btn.title    = 'Guardar mi ubicación para encontrar centros cercanos';
            btn.querySelector('#geo-btn-icon').textContent = '📍';
        }
    }

    updateBtnState(); // estado inicial al cargar la página

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Tu navegador no soporta geolocalización.');
            return;
        }
        btn.disabled = true;
        btn.querySelector('#geo-btn-icon').textContent = '⏳';

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