@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Logística de Ayuda</h1>
            <p class="mt-1 text-sm text-gray-600">Registra y localiza puntos de donación y centros de acopio.</p>
        </div>
    </div>

    @if ($error ?? null)
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
            {{ $error }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Panel Izquierdo: Formulario -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 h-fit">
            <div class="flex justify-between items-center mb-4">
                <h2 id="form_title" class="text-lg font-semibold text-gray-800">Registrar Nuevo Centro</h2>
                <button type="button" id="cancel_edit_btn" class="hidden text-xs text-red-600 hover:underline font-medium">Cancelar Edición</button>
            </div>
            
            <form id="logistics_form" action="{{ route('logistica.store') }}" method="POST">
                @csrf
                <div id="method_field"></div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Centro *</label>
                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Apertura *</label>
                        <input type="time" id="hora_apertura" name="hora_apertura" value="{{ old('hora_apertura', '08:00') }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Cierre *</label>
                        <input type="time" id="hora_cierre" name="hora_cierre" value="{{ old('hora_cierre', '18:00') }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Referencial</label>
                    <input type="text" id="direccion" name="direccion" value="{{ old('direccion') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Latitud *</label>
                        <input type="text" name="latitud" id="lat" value="{{ old('latitud') }}" readonly required class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm placeholder:text-gray-400" placeholder="Haz clic en el mapa">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Longitud *</label>
                        <input type="text" name="longitud" id="lng" value="{{ old('longitud') }}" readonly required class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm placeholder:text-gray-400" placeholder="Haz clic en el mapa">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de Contacto</label>
                    <input type="text" id="contacto" name="contacto" value="{{ old('contacto') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Encargado Responsable</label>
                    <input type="text" id="encargado" name="encargado" value="{{ old('encargado') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <button type="submit" id="submit_btn" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors">
                    Registrar Centro
                </button>
            </form>
        </div>

        <!-- Panel Derecho: Mapa -->
        <div class="lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden relative" style="height: 700px;">
                <div id="logistics_map" class="absolute inset-0 z-0"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2 text-right">💡 Haz clic sobre el mapa para autocompletar las coordenadas del formulario.</p>
        </div>
    </div>

    <!-- Panel Inferior: Tabla de Registros -->
    <div class="mt-10 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Directorio de Centros Registrados</h2>
            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                Total: {{ count($centros) }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dirección</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Encargado</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($centros as $centro)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $centro['nombre'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs text-gray-600">{{ $centro['hora_apertura'] ?? '08:00' }} - {{ $centro['hora_cierre'] ?? '18:00' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs text-gray-600">{{ $centro['contacto'] ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500 max-w-xs truncate" title="{{ $centro['direccion'] ?? 'N/A' }}">{{ $centro['direccion'] ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $centro['encargado'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick='editCentro(@json($centro))' class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded transition-colors">Editar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 text-sm">
                                No se encontraron centros registrados. Empieza registrando uno arriba.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.centros = @json($centros ?? []);
    let mapMarker = null;
    let map = null;
    
    function initLogisticsMap() { 
        const defaultLocation = [-17.783325, -63.182111]; // Santa Cruz, Bolivia
        
        map = L.map('logistics_map').setView(defaultLocation, 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // 1. Mostrar puntos registrados
        window.centros.forEach(centro => {
            const lat = parseFloat(centro.latitud);
            const lng = parseFloat(centro.longitud);

            if (isNaN(lat) || isNaN(lng)) return;

            // Lógica de Estado Abierto / Cerrado basado en horario
            const horaAperturaStr = centro.hora_apertura || '08:00';
            const horaCierreStr = centro.hora_cierre || '18:00';
            
            // Obtener MS desde medianoche
            const now = new Date();
            const currentMs = now.getHours() * 3600000 + now.getMinutes() * 60000;
            
            const apParts = horaAperturaStr.split(':');
            const ciParts = horaCierreStr.split(':');
            
            const apMs = parseInt(apParts[0] || 0) * 3600000 + parseInt(apParts[1] || 0) * 60000;
            const ciMs = parseInt(ciParts[0] || 0) * 3600000 + parseInt(ciParts[1] || 0) * 60000;
            
            let isOpen = false;
            
            if (ciMs < apMs) {
                // El centro cierra al día siguiente (ej. 20:00 a 02:00)
                if (currentMs >= apMs || currentMs <= ciMs) isOpen = true;
            } else {
                if (currentMs >= apMs && currentMs <= ciMs) isOpen = true;
            }
            
            let markerColor = isOpen ? "#34A853" : "#EA4335"; // Verde o Rojo
            
            const customIcon = L.divIcon({
                className: 'custom-leaflet-marker',
                html: `<div style="
                    background-color: ${markerColor};
                    width: 20px;
                    height: 20px;
                    border-radius: 50%;
                    border: 2px solid white;
                    box-shadow: 0 0 4px rgba(0,0,0,0.5);
                "></div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            const statusBadge = isOpen 
                ? `<span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded">ABIERTO AHORA</span>` 
                : `<span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded">CERRADO</span>`;

            const contentStr = `
                <div class="max-w-[200px] p-1">
                    <div class="flex items-center justify-between mb-2">
                       <h3 class="font-bold text-sm m-0">${centro.nombre}</h3>
                    </div>
                    <p class="text-xs text-gray-700 mb-1"><b>Estado:</b> ${statusBadge}</p>
                    <p class="text-xs text-gray-700 mb-1"><b>Horario:</b> ${horaAperturaStr} a ${horaCierreStr}</p>
                    ${centro.contacto ? `<p class="text-xs text-gray-700 mb-1"><b>Cel:</b> ${centro.contacto}</p>` : ''}
                    ${centro.direccion ? `<p class="text-xs text-gray-700 mb-1"><b>Dir:</b> ${centro.direccion}</p>` : ''}
                    <button onclick='editCentro(${JSON.stringify(centro).replace(/'/g, "&apos;")})' class="mt-2 w-full py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-semibold rounded border border-gray-300 transition-colors">
                        ✏️ Editar Centro
                    </button>
                </div>
            `;
            
            L.marker([lat, lng], { icon: customIcon })
             .bindPopup(contentStr)
             .addTo(map);
        });

        // 2. Click en el mapa para el Formulario
        map.on('click', function(e) {
            document.getElementById('lat').value = e.latlng.lat.toFixed(7);
            document.getElementById('lng').value = e.latlng.lng.toFixed(7);

            // Mover el pin temporal
            if (mapMarker) {
                mapMarker.setLatLng(e.latlng);
            } else {
                mapMarker = L.marker(e.latlng).addTo(map);
            }
        });
    }

    document.addEventListener("DOMContentLoaded", initLogisticsMap);

    // Lógica para cambiar dinámicamente entre Registrar / Editar
    window.editCentro = function(centro) {
        document.getElementById('form_title').innerText = "Editando Centro";
        document.getElementById('submit_btn').innerText = "Guardar Cambios";
        document.getElementById('submit_btn').classList.replace("bg-blue-600", "bg-indigo-600");
        document.getElementById('submit_btn').classList.replace("hover:bg-blue-500", "hover:bg-indigo-500");
        
        document.getElementById('cancel_edit_btn').classList.remove("hidden");
        
        // Rellenar ocultos/URL
        document.getElementById('method_field').innerHTML = '<input type="hidden" name="_method" value="PATCH">';
        document.getElementById('logistics_form').action = `/logistica/${centro.id_centro}`;
        
        // Rellenar campos existenes
        document.getElementById('nombre').value = centro.nombre || '';
        document.getElementById('hora_apertura').value = (centro.hora_apertura || '08:00:00').substring(0, 5);
        document.getElementById('hora_cierre').value = (centro.hora_cierre || '18:00:00').substring(0, 5);
        document.getElementById('direccion').value = centro.direccion || '';
        document.getElementById('lat').value = centro.latitud;
        document.getElementById('lng').value = centro.longitud;
        document.getElementById('contacto').value = centro.contacto || '';
        document.getElementById('encargado').value = centro.encargado || '';
    };

    document.getElementById('cancel_edit_btn').addEventListener('click', function() {
        document.getElementById('form_title').innerText = "Registrar Nuevo Centro";
        document.getElementById('submit_btn').innerText = "Registrar Centro";
        document.getElementById('submit_btn').classList.replace("bg-indigo-600", "bg-blue-600");
        document.getElementById('submit_btn').classList.replace("hover:bg-indigo-500", "hover:bg-blue-500");
        
        document.getElementById('cancel_edit_btn').classList.add("hidden");
        
        document.getElementById('method_field').innerHTML = '';
        document.getElementById('logistics_form').action = `{{ route('logistica.store') }}`;
        document.getElementById('logistics_form').reset();
    });

</script>
<!-- LEAFLET CDN -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

@endsection
