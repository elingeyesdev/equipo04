@extends('layouts.app')

@section('content')
<div class="h-[calc(100vh-80px)] flex flex-col md:flex-row relative">
    
    <!-- Panel Lateral con Lista de Vehículos Activos -->
    <div class="w-full md:w-80 bg-white/95 backdrop-blur shadow-xl border-r border-gray-200 z-[1000] flex flex-col absolute md:relative h-1/3 md:h-full bottom-0 md:bottom-auto transition-all duration-300 rounded-t-2xl md:rounded-none overflow-hidden">
        
        <div class="p-4 border-b border-gray-200 bg-gray-50/80 flex items-center justify-between sticky top-0">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                🚑 Unidades en Ruta
            </h2>
            <span id="active-count" class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full border border-green-200">
                0
            </span>
        </div>

        <div class="overflow-y-auto flex-1 p-2 space-y-2" id="vehicle-list">
            <p class="text-xs text-gray-500 text-center mt-4">Cargando unidades...</p>
        </div>
    </div>

    <!-- Contenedor del Mapa -->
    <div class="flex-1 relative z-0">
        <div id="tracking_map" class="absolute inset-0"></div>
        
        <div class="absolute top-4 right-4 z-[1000]">
            <a href="{{ route('vehiculos.index', [], false) }}" class="bg-white/90 backdrop-blur border border-gray-200 shadow-md rounded-md px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2">
                <span>⚙️ Gestionar</span>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Inicializar Mapa Leaflet
    const defaultLocation = [-17.783325, -63.182111]; // Santa Cruz
    const map = L.map('tracking_map', { zoomControl: false }).setView(defaultLocation, 12);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    // 2. Variables y Capas
    const markersLayer = L.layerGroup().addTo(map);
    let vehicleMarkers = {}; // Para guardar y actualizar marcadores existentes sin borrarlos

    // Iconos personalizados
    const icons = {
        'ambulancia': '🚑',
        'camion_rescate': '🚒',
        'camioneta': '🛻',
        'default': '<svg class="w-4 h-4 fill-current text-red-500 inline-block" viewBox="0 0 640 640"><path d="M541.9 139.5C546.4 127.7 543.6 114.3 534.7 105.4C525.8 96.5 512.4 93.6 500.6 98.2L84.6 258.2C71.9 263 63.7 275.2 64 288.7C64.3 302.2 73.1 314.1 85.9 318.3L262.7 377.2L321.6 554C325.9 566.8 337.7 575.6 351.2 575.9C364.7 576.2 376.9 568 381.8 555.4L541.8 139.4z"/></svg>'
    };

    function createIcon(tipo, isRoute) {
        const emoji = icons[tipo] || icons['default'];
        const color = isRoute ? 'bg-red-500' : 'bg-gray-500';
        const pulse = isRoute ? '<div class="absolute inset-0 rounded-full animate-ping bg-red-400 opacity-75"></div>' : '';
        
        return L.divIcon({
            className: 'custom-vehicle-marker',
            html: `
                <div class="relative flex items-center justify-center w-10 h-10 ${color} rounded-full text-lg shadow-lg border-2 border-white transition-transform duration-300 hover:scale-110">
                    ${pulse}
                    <span class="relative z-10">${emoji}</span>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });
    }

    // 3. Función Principal de Fetch (Polling)
    async function fetchActiveVehicles() {
        try {
            const response = await fetch("{{ route('vehiculos.activos', [], false) }}");
            if (!response.ok) throw new Error("Error en la petición");
            
            const vehicles = await response.json();
            updateMapAndList(vehicles);
        } catch (error) {
            console.error('Error fetching vehicles:', error);
        }
    }

    // 4. Actualizar Mapa y UI
    function updateMapAndList(vehicles) {
        // Actualizar Contador
        const enRuta = vehicles.filter(v => v.en_ruta).length;
        document.getElementById('active-count').innerText = enRuta;

        const listContainer = document.getElementById('vehicle-list');
        listContainer.innerHTML = '';

        if (vehicles.length === 0) {
            listContainer.innerHTML = '<p class="text-xs text-gray-500 text-center mt-4">No hay vehículos transmitiendo ubicación.</p>';
            markersLayer.clearLayers();
            vehicleMarkers = {};
            return;
        }

        const currentIds = vehicles.map(v => v.id);
        
        // Limpiar marcadores que ya no están
        Object.keys(vehicleMarkers).forEach(id => {
            if (!currentIds.includes(parseInt(id))) {
                markersLayer.removeLayer(vehicleMarkers[id]);
                delete vehicleMarkers[id];
            }
        });

        // Actualizar o crear
        vehicles.forEach(vehicle => {
            const lat = parseFloat(vehicle.latitud);
            const lng = parseFloat(vehicle.longitud);

            if (isNaN(lat) || isNaN(lng)) return;

            const icon = createIcon(vehicle.tipo, vehicle.en_ruta);
            
            // PopUp HTML
            const popupContent = `
                <div class="p-2">
                    <h3 class="font-bold text-sm mb-1 capitalize">${vehicle.tipo.replace('_', ' ')} - ${vehicle.placa}</h3>
                    <p class="text-xs text-gray-600"><b>Conductor:</b> ${vehicle.conductor}</p>
                    <p class="text-xs ${vehicle.en_ruta ? 'text-red-600 font-bold' : 'text-gray-500'}"><b>Estado:</b> ${vehicle.en_ruta ? '🚨 En Emergencia/Ruta' : 'Standby'}</p>
                    <p class="text-[10px] text-gray-400 mt-2">Última act: ${vehicle.ultima_ubicacion}</p>
                </div>
            `;

            if (vehicleMarkers[vehicle.id]) {
                // Si existe, mover suavemente
                const marker = vehicleMarkers[vehicle.id];
                marker.setLatLng([lat, lng]);
                marker.setIcon(icon);
                marker.setPopupContent(popupContent);
            } else {
                // Crear nuevo
                const marker = L.marker([lat, lng], { icon: icon }).bindPopup(popupContent);
                vehicleMarkers[vehicle.id] = marker;
                markersLayer.addLayer(marker);
            }

            // Agregar a la lista HTML
            const listItem = document.createElement('div');
            listItem.className = `p-3 rounded-lg border ${vehicle.en_ruta ? 'border-red-200 bg-red-50' : 'border-gray-100 bg-white'} shadow-sm cursor-pointer hover:shadow-md transition-shadow flex items-start gap-3`;
            listItem.onclick = () => {
                map.flyTo([lat, lng], 16, { animate: true, duration: 1.5 });
                setTimeout(() => vehicleMarkers[vehicle.id].openPopup(), 1500);
            };

            listItem.innerHTML = `
                <div class="text-2xl">${icons[vehicle.tipo] || icons['default']}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-900 truncate uppercase">${vehicle.placa}</p>
                    <p class="text-xs text-gray-600 truncate">${vehicle.conductor}</p>
                    <p class="text-[10px] text-gray-500 mt-1 flex items-center gap-1">
                        ⏱️ ${vehicle.ultima_ubicacion}
                    </p>
                </div>
                ${vehicle.en_ruta ? '<span class="flex w-3 h-3 bg-red-500 rounded-full animate-pulse mt-1"></span>' : ''}
            `;
            listContainer.appendChild(listItem);
        });
    }

    // Iniciar polling
    fetchActiveVehicles();
    setInterval(fetchActiveVehicles, 5000); // Poll cada 5 segundos
});
</script>
@endsection
