@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Crear reporte</h1>
                <p class="mt-1 text-sm text-gray-600">Ingresá ubicación y descripción del evento.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">Volver</a>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('reports.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="number" step="any" value="{{ old('latitude') }}" class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" readonly required>
                    @error('latitude')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="number" step="any" value="{{ old('longitude') }}" class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" readonly required>
                    @error('longitude')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="-mt-1">
                <button type="button" id="getLocationBtn" class="text-sm inline-flex items-center bg-gray-100 text-gray-700 border border-gray-300 px-3 py-1.5 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Obtener mi ubicación actual
                </button>
                <p id="locationStatus" class="inline-block mt-2 sm:mt-0 sm:ml-3 text-sm text-gray-600 block sm:inline"></p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="address">Dirección (opcional)</label>
                <input id="address" name="address" type="text" value="{{ old('address') }}" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900">
                @error('address')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="severity">Severidad</label>
                <select id="severity" name="severity" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" required>
                    @php($sev = old('severity', 'medium'))
                    <option value="low" @selected($sev === 'low')>low</option>
                    <option value="medium" @selected($sev === 'medium')>medium</option>
                    <option value="high" @selected($sev === 'high')>high</option>
                </select>
                @error('severity')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="description">Descripción</label>
                <textarea id="description" name="description" rows="5" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" required>{{ old('description') }}</textarea>
                @error('description')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                    Crear
                </button>
            </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const locBtn = document.getElementById('getLocationBtn');
            const locStatus = document.getElementById('locationStatus');

            function fetchLocation() {
                if (!navigator.geolocation) {
                    locStatus.textContent = "Tu navegador no soporta geolocalización.";
                    locStatus.classList.replace('text-gray-600', 'text-red-600');
                    enableInputs();
                    return;
                }

                locStatus.textContent = "Obteniendo ubicación...";
                locStatus.className = "inline-block mt-2 sm:mt-0 sm:ml-3 text-sm text-gray-600 block sm:inline";
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    latInput.value = position.coords.latitude;
                    lngInput.value = position.coords.longitude;
                    locStatus.textContent = "Ubicación obtenida exitosamente.";
                    locStatus.classList.replace('text-gray-600', 'text-green-600');
                }, function(error) {
                    locStatus.textContent = "Error al obtener ubicación. Puedes ingresarla manualmente.";
                    locStatus.classList.replace('text-gray-600', 'text-red-600');
                    enableInputs();
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000
                });
            }

            function enableInputs() {
                latInput.removeAttribute('readonly');
                latInput.classList.replace('bg-gray-50', 'bg-white');
                lngInput.removeAttribute('readonly');
                lngInput.classList.replace('bg-gray-50', 'bg-white');
            }

            locBtn.addEventListener('click', fetchLocation);

            if (!latInput.value || !lngInput.value) {
                fetchLocation();
            }
        });
    </script>
@endsection
