@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Mapa de Reportes</h1>
            <p class="mt-1 text-sm text-gray-600">Visualiza los reportes de inundación en tiempo real.</p>
        </div>
    </div>

    @if ($error)
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
            {{ $error }}
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden relative" style="height: 600px;">
        <div id="map" class="absolute inset-0 z-0"></div>
    </div>
</div>

<!-- LEAFLET CDN -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    window.floodReports = @json($reports);
    
    function initMap() { 
        const defaultLocation = [-17.783325, -63.182111]; // Centro de Santa Cruz de la Sierra, Bolivia 
        
        let centerLoc = defaultLocation;
        if (window.floodReports.length > 0) {
            for(let i=0; i<window.floodReports.length; i++) {
                 let lat = parseFloat(window.floodReports[i].latitude);
                 let lng = parseFloat(window.floodReports[i].longitude);
                 if(!isNaN(lat) && !isNaN(lng)) {
                     centerLoc = [lat, lng];
                     break;
                 }
            }
        }

        // 1. Inicializar Mapa de Leaflet
        const map = L.map('map').setView(centerLoc, 12);

        // 2. Cargar Capa de OpenStreetMap (Gratuita)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // 3. Pintar Reportes
        window.floodReports.forEach(report => {
            const lat = parseFloat(report.latitude);
            const lng = parseFloat(report.longitude);

            if (isNaN(lat) || isNaN(lng)) return;

            // Determinar color de Severity CSS
            let markerColor = "#4285F4"; // Default Blue
            if (report.severity === 'high') markerColor = "#EA4335"; // Red
            if (report.severity === 'medium') markerColor = "#FBBC05"; // Yellow
            if (report.severity === 'low') markerColor = "#34A853"; // Green

            // Crear un Icono HTML (DivIcon) de Leaflet usando CSS para pintar el círculo
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

            // Reemplazando marker.addListener con bindPopup estático
            const contentStr = `
                <div class="max-w-xs">
                    <p class="font-semibold text-sm mb-1">${report.description.substring(0, 100) + (report.description.length > 100 ? '...' : '')}</p>
                    <p class="text-xs text-gray-600 mb-2"><b>Severidad:</b> ${report.severity} | <b>Estado:</b> ${report.status}</p>
                    <a href="/reports/${report.id}" class="text-xs text-blue-600 hover:underline">Ver detalle completo →</a>
                </div>
            `;
            
            L.marker([lat, lng], { icon: customIcon })
             .bindPopup(contentStr, { minWidth: 200 })
             .addTo(map);
        });
    }

    // Leaflet init se dispara manual directo ya que no requiere URL callbacks
    document.addEventListener("DOMContentLoaded", initMap);

    if (navigator.geolocation && window.floodReports.length === 0) {
        // En un Leaflet real, podemos usar map.locate() pero para el plan se deja esto como placeholder
    }
</script>
@endsection
