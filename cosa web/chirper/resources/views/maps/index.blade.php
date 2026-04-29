@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Mapa de Reportes</h1>
            <p class="mt-1 text-sm text-gray-600">Visualiza los reportes de inundación en tiempo real.</p>
        </div>
    </div>

    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Buscar Reportes por Ubicación</h3>
        <x-location-filter formAction="{{ route('maps.index', [], false) }}" />
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

        let markersLayer = L.layerGroup().addTo(map);

        function renderReports(reportsData) {
            markersLayer.clearLayers();
            reportsData.forEach(report => {
                const lat = parseFloat(report.latitude);
                const lng = parseFloat(report.longitude);

                if (isNaN(lat) || isNaN(lng)) return;

                let markerColor = "#4285F4"; // Default Blue
                if (report.severity === 'high') markerColor = "#EA4335"; // Red
                if (report.severity === 'medium') markerColor = "#FBBC05"; // Yellow
                if (report.severity === 'low') markerColor = "#34A853"; // Green

                const customIcon = L.divIcon({
                    className: 'custom-leaflet-marker',
                    html: `<div style="background-color: ${markerColor}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>`,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });

                const contentStr = `
                    <div class="max-w-xs">
                        <p class="font-semibold text-sm mb-1">${report.description.substring(0, 100) + (report.description.length > 100 ? '...' : '')}</p>
                        <p class="text-xs text-gray-600 mb-2"><b>Severidad:</b> ${report.severity} | <b>Estado:</b> ${report.status}</p>
                        <a href="/reports/${report.id}" class="text-xs text-blue-600 hover:underline">Ver detalle completo →</a>
                    </div>
                `;
                
                const marker = L.marker([lat, lng], { icon: customIcon })
                 .bindPopup(contentStr, { minWidth: 200 })
                 .on('click', function() {
                     map.flyTo([lat, lng], 15, { animate: true, duration: 1 });
                 });
                 
                markersLayer.addLayer(marker);
            });
        }

        // 3. Pintar Reportes
        renderReports(window.floodReports);

        // ─────────────────────────────────────────────────────────────
        // Cargar geometrías GeoJSON para resaltado de fronteras.
        // NOTA: A diferencia de logistics/index, aquí NO hay clic para registrar;
        // estos datos solo se usan para el resaltado visual del filtro.
        // Las funciones de traducción (normalizeProvName, normalizeMuniName) están
        // definidas globalmente en layouts/app.blade.php y disponibles en toda la app.
        // ─────────────────────────────────────────────────────────────
        let provincesData = null;
        let municipalitiesData = null;
        let highlightLayer = null; // Capa activa de resaltado (naranja=provincia, rojo=municipio)

        fetch('/provinces.geojson').then(res => res.json()).then(data => provincesData = data);
        fetch('/municipalities.geojson').then(res => res.json()).then(data => municipalitiesData = data);

        // ─────────────────────────────────────────────────────────────
        // EVENTO CENTRAL DE FILTRADO: locationFilterChanged
        // ─────────────────────────────────────────────────────────────
        // Igual al de logistics, pero más simple: solo filtra los marcadores
        // de reportes y actualiza la capa de resaltado geográfico.
        // No hay filtro de "Estado" (abierto/cerrado) porque es de centros de acopio.
        window.addEventListener('locationFilterChanged', function(e) {
            const { idPrefix, provincia, municipio } = e.detail;
            
            // Filtrado local SPA para reportes de inundación
            if (idPrefix === 'filter') {
                const filtered = window.floodReports.filter(r => {
                    // Si el backend no envió provincia, no podemos filtrar perfecto localmente,
                    // pero asumiendo que FloodReport Resource sí lo expone:
                    if (provincia && r.provincia && r.provincia !== provincia) return false;
                    if (municipio && r.municipio && r.municipio !== municipio) return false;
                    return true;
                });
                renderReports(filtered);
            }

            if (highlightLayer) {
                map.removeLayer(highlightLayer);
                highlightLayer = null;
            }

            if (municipio && municipalitiesData) {
                // Buscar el polígono del municipio seleccionado (rojo #EF4444)
                // normalizeMuniName traduce el nombre crudo del GeoJSON ("Municipio Warnes")
                // al formato oficial limpio ("warnes") para compararlo con el valor del filtro.
                const feature = municipalitiesData.features.find(f => window.normalizeMuniName(f.properties.name) === municipio.toLowerCase());
                if (feature) {
                    highlightLayer = L.geoJSON(feature, {
                        style: { color: '#EF4444', weight: 3, opacity: 0.9, fillOpacity: 0.1 },
                        interactive: false
                    }).addTo(map);
                    map.fitBounds(highlightLayer.getBounds());
                }
            } else if (provincia && provincesData) {
                // Buscar el polígono de la provincia seleccionada (naranja #F97316)
                // normalizeProvName maneja aliases como "Velasco" → "José Miguel de Velasco"
                const feature = provincesData.features.find(f => window.normalizeProvName(f.properties.name) === provincia.toLowerCase());
                if (feature) {
                    highlightLayer = L.geoJSON(feature, {
                        style: { color: '#F97316', weight: 3, opacity: 0.9, fillOpacity: 0.1 },
                        interactive: false
                    }).addTo(map);
                    map.fitBounds(highlightLayer.getBounds());
                }
            } else if (idPrefix === 'filter') {
                map.setView([-17.783325, -63.182111], 12);
            }
        });
    }

    // Leaflet init se dispara manual directo ya que no requiere URL callbacks
    document.addEventListener("DOMContentLoaded", initMap);

    if (navigator.geolocation && window.floodReports.length === 0) {
        // En un Leaflet real, podemos usar map.locate() pero para el plan se deja esto como placeholder
    }
</script>
@endsection
