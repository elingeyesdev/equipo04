@props([
    'reports' => [],
    'pendingReports' => [],
    'showRouting' => false,
    'role' => null,
    'mapHeight' => '600px'
])

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="{{ asset('js/smart-heatmap.js') }}"></script>

<style>
/* ── Animación de pulso para zonas de alta intensidad ────────────────── */
@keyframes flood-pulse {
    0%   { fill-opacity: 0.55; stroke-opacity: 0.9; }
    50%  { fill-opacity: 0.30; stroke-opacity: 0.5; }
    100% { fill-opacity: 0.55; stroke-opacity: 0.9; }
}
.flood-polygon-alta path {
    animation: flood-pulse 2.5s ease-in-out infinite;
}

/* Leyenda flotante del mapa */
.map-legend-float {
    position: absolute;
    bottom: 24px;
    left: 16px;
    z-index: 1000;
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.13);
    border: 1px solid #e5e7eb;
    padding: 14px 16px;
    min-width: 190px;
    pointer-events: none;
    transition: opacity 0.3s;
}
.map-legend-float.hidden { opacity: 0; pointer-events: none; }
.map-legend-float h4 {
    font-size: 11px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.map-legend-float .legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 12px;
    color: #4b5563;
    font-weight: 500;
}
.map-legend-float .legend-swatch {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    flex-shrink: 0;
    border: 1.5px solid rgba(0,0,0,0.15);
}
.map-legend-float .legend-note {
    font-size: 10px;
    color: #9ca3af;
    text-align: center;
    margin-top: 8px;
    font-style: italic;
}
</style>

<div class="relative mb-10">
    <div id="map-container" class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden relative z-0" style="height: {{ $mapHeight }};" wire:ignore>
        <div id="map" class="absolute inset-0 z-0"></div>
        
        <!-- Botón Pantalla Completa -->
        <button id="btn-fullscreen-map" class="absolute top-[80px] left-[10px] z-[1000] bg-white text-gray-700 p-1.5 rounded-[4px] shadow-[0_1px_5px_rgba(0,0,0,0.65)] hover:bg-gray-100 transition-colors" title="Pantalla Completa" onclick="toggleMapFullscreen()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
        </button>
        
        <!-- LEYENDA DEL RADAR (UI/UX) -->
        <div id="radar-legend" class="hidden absolute bottom-6 left-6 bg-white/95 backdrop-blur p-4 rounded-xl shadow-xl border border-gray-100 z-[1000] pointer-events-none transition-all duration-300">
            <h4 id="radar-legend-title" class="text-xs font-bold text-gray-800 mb-3 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                <span>Intensidad de Lluvia</span>
            </h4>
            <div id="radar-legend-rain-colors" class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-blue-400 opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Lluvia Débil / Llovizna</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-yellow-400 opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Lluvia Moderada</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-red-500 opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Tormenta Fuerte</span>
                </div>
            </div>
            <div id="radar-legend-cloud-colors" class="space-y-2 hidden">
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-gray-200 opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Pocas nubes</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-gray-400 opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Nublado</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-gray-600 opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Muy nublado</span>
                </div>
            </div>
        </div>
    </div>
    
    @if($showRouting)
        <!-- Panel de Rutas Seguras -->
        <x-routing-panel />
    @endif
</div>

<script>
window.floodReports = @json($reports);
window.pendingReports = @json($pendingReports);

function initMap() {
    const mapEl = document.getElementById('map');
    if (!mapEl || mapEl._leaflet_id) return;

    const defaultLocation = [-17.783325, -63.182111]; // Centro de Santa Cruz de la Sierra, Bolivia

    let centerLoc = defaultLocation;
    if (window.floodReports && window.floodReports.length > 0) {
        for (let i = 0; i < window.floodReports.length; i++) {
            let lat = parseFloat(window.floodReports[i].latitud);
            let lng = parseFloat(window.floodReports[i].longitud);
            if (!isNaN(lat) && !isNaN(lng)) {
                centerLoc = [lat, lng];
                break;
            }
        }
    }

    // ── 1. Inicializar Mapa ───────────────────────────────────────────────
    const map = L.map('map', { preferCanvas: true }).setView(centerLoc, 12);
    window.mapObj = map;

    // Bounding box del departamento de Santa Cruz (para limitar capas externas)
    const santaCruzBounds = [[-20.5, -64.8], [-13.5, -57.4]];

    // ── 2. Capas Base ─────────────────────────────────────────────────────
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        maxZoom: 19,
    });

    osmLayer.addTo(map);

    const baseMaps = {
        "Mapa Normal (OSM)": osmLayer,
        "Satelital (Esri)": satelliteLayer,
    };

    // ── 3. Overlays ───────────────────────────────────────────────────────
    const overlayMaps = {};
    const layerControl = L.control.layers(baseMaps, overlayMaps, { collapsed: true }).addTo(map);

    // ── 3a. Capas de Reportes ─────────────────────────────────────────────
    const markersLayer           = L.layerGroup().addTo(map); // Centroides principales de inundación
    const polygonLayer           = L.layerGroup().addTo(map); // Polígonos topográficos (activo por defecto)
    const individualReportsLayer = L.layerGroup().addTo(map); // Reportes atómicos (activo por defecto)

    layerControl.addOverlay(markersLayer,           "Centros de Inundación (Centroides)");
    layerControl.addOverlay(polygonLayer,           "Mapa de Calor Inteligente (Topográfico)");
    layerControl.addOverlay(individualReportsLayer, "Reportes Ciudadanos (Detalle)");

    // ── 3b. ESRI Shaded Relief — relieve topográfico superpuesto ─────────
    const reliefOverlay = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        opacity: 0.45,
        bounds: santaCruzBounds,
        minZoom: 5,
        maxZoom: 18,
        zIndex: 5,
    });
    layerControl.addOverlay(reliefOverlay, "Relieve del Terreno (ESRI)");

    // ── 3c. Capas Meteorológicas (OpenWeatherMap) ─────────────────────────
    const precipLayer = L.layerGroup();
    const cloudLayer  = L.layerGroup();
    layerControl.addOverlay(precipLayer, "Radar de Lluvia (OpenWeather)");
    layerControl.addOverlay(cloudLayer,  "Nubes (OpenWeather)");

    L.tileLayer('/weather/tiles/precipitation_new/{z}/{x}/{y}?v=2', {
        opacity: 0.85, attribution: '&copy; OpenWeatherMap',
        bounds: santaCruzBounds, minZoom: 5, maxNativeZoom: 8, maxZoom: 18,
        updateWhenIdle: true, zIndex: 10
    }).addTo(precipLayer);

    L.tileLayer('/weather/tiles/clouds_new/{z}/{x}/{y}?v=2', {
        opacity: 0.85, attribution: '&copy; OpenWeatherMap',
        bounds: santaCruzBounds, minZoom: 5, maxNativeZoom: 8, maxZoom: 18,
        updateWhenIdle: true, zIndex: 10
    }).addTo(cloudLayer);

    // ── 3d. Red Hídrica ───────────────────────────────────────────────────
    fetch('/red_hidrica_santa_cruz.json')
        .then(res => res.json())
        .then(data => {
            const hydroLayer = L.geoJSON(data, {
                style: { color: '#0ea5e9', weight: 1.5, opacity: 0.8 },
                interactive: false
            });
            layerControl.addOverlay(hydroLayer, "Red Hídrica");
        }).catch(e => console.warn("Error cargando red hídrica", e));

    // ── 3e. Fronteras Geográficas ─────────────────────────────────────────
    let provincesData       = null;
    let municipalitiesData  = null;
    let highlightLayer      = null;

    const provincesOverlay = L.geoJSON(null, {
        style: { color: '#F97316', weight: 1.5, opacity: 0.8, fillOpacity: 0.05 },
        interactive: false
    });
    const municipalitiesOverlay = L.geoJSON(null, {
        style: { color: '#EF4444', weight: 1.5, opacity: 0.8, fillOpacity: 0.05 },
        interactive: false
    });

    layerControl.addOverlay(provincesOverlay,     "Fronteras Provinciales");
    layerControl.addOverlay(municipalitiesOverlay, "Fronteras Municipales");

    fetch('/provinces.geojson').then(r => r.json()).then(data => {
        provincesData = data;
        provincesOverlay.addData(data);
    });
    fetch('/municipalities.geojson').then(r => r.json()).then(data => {
        municipalitiesData = data;
        municipalitiesOverlay.addData(data);
    });

    // ── 4. Lógica de Leyenda ──────────────────────────────────────────────
    const radarLegend = document.getElementById('radar-legend');

    map.on('overlayadd', function (e) {
        if (e.name === "Radar de Lluvia (OpenWeather)" || e.name === "Nubes (OpenWeather)") {
            if (radarLegend) radarLegend.classList.remove('hidden');
            const isCloud = e.name === "Nubes (OpenWeather)";
            document.getElementById('radar-legend-title').innerHTML = isCloud
                ? '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg><span>Densidad de Nubes</span>'
                : '<svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg><span>Intensidad de Lluvia</span>';
            if (document.getElementById('radar-legend-rain-colors')) document.getElementById('radar-legend-rain-colors').classList.toggle('hidden', isCloud);
            if (document.getElementById('radar-legend-cloud-colors')) document.getElementById('radar-legend-cloud-colors').classList.toggle('hidden', !isCloud);
        }
    });
    map.on('overlayremove', function (e) {
        if (e.name === "Radar de Lluvia (OpenWeather)" || e.name === "Nubes (OpenWeather)") {
            if (!map.hasLayer(precipLayer) && !map.hasLayer(cloudLayer)) {
                if (radarLegend) radarLegend.classList.add('hidden');
            } else if (map.hasLayer(cloudLayer)) {
                if (document.getElementById('radar-legend-rain-colors')) document.getElementById('radar-legend-rain-colors').classList.add('hidden');
                if (document.getElementById('radar-legend-cloud-colors')) document.getElementById('radar-legend-cloud-colors').classList.remove('hidden');
            } else {
                if (document.getElementById('radar-legend-rain-colors')) document.getElementById('radar-legend-rain-colors').classList.remove('hidden');
                if (document.getElementById('radar-legend-cloud-colors')) document.getElementById('radar-legend-cloud-colors').classList.add('hidden');
            }
        }
    });

    // ── 5. Renderizado Inteligente de Reportes ────────────────────────────
    const INTENSITY_PALETTE = {
        alta:  { fill: '#1e3a8a', stroke: '#172554', marker: '#2563eb' },
        media: { fill: '#0ea5e9', stroke: '#0369a1', marker: '#38bdf8' },
        baja:  { fill: '#2dd4bf', stroke: '#0f766e', marker: '#14b8a6' },
        null:  { fill: '#94a3b8', stroke: '#475569', marker: '#cbd5e1' },
    };

    function getPalette(intensidad) {
        return INTENSITY_PALETTE[intensidad] || INTENSITY_PALETTE['null'];
    }

    function renderReports(reportsData) {
        markersLayer.clearLayers();
        polygonLayer.clearLayers();
        individualReportsLayer.clearLayers();

        if (window.smartHeatmapInstance) {
            window.smartHeatmapInstance.remove();
        }

        let allActiveReports = [];

        reportsData.forEach(report => {
            const lat = parseFloat(report.latitud);
            const lng = parseFloat(report.longitud);
            if (isNaN(lat) || isNaN(lng)) return;

            const intensidad = report.intensidad_calculada || report.intensidad || 'baja';
            const palette    = getPalette(intensidad);

            const confirmadoBadge = report.esta_confirmada
                ? '<span class="inline-flex items-center gap-1 bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded">Confirmada</span>'
                : '<span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-800 text-[10px] font-bold px-2 py-0.5 rounded">En espera</span>';

            const intensidadBadgeColor = { alta: 'blue', media: 'sky', baja: 'teal' }[intensidad] || 'gray';
            const intensidadBadge = `<span class="inline-flex items-center bg-${intensidadBadgeColor}-100 text-${intensidadBadgeColor}-800 text-[10px] font-semibold px-2 py-0.5 rounded capitalize">${intensidad}</span>`;

            const desc        = report.description || 'Sin descripción del evento.';
            const shortDesc   = desc.length > 120 ? desc.substring(0, 120) + '…' : desc;
            const quorumStr   = report.quorum_total !== undefined ? `<b>Quórum Global:</b> ${report.quorum_total} pts` : '';
            
            let numReports = 0;
            if (report.reportes_activos && Array.isArray(report.reportes_activos)) {
                numReports = report.reportes_activos.length;
            } else if (report.reportes && Array.isArray(report.reportes)) {
                numReports = report.reportes.length;
            }

            const polygonNote = report.polygon_coords
                ? `<p class="text-[10px] text-blue-600 mt-1">Polígono expansivo recalculado (${numReports} reportes asociados)</p>`
                : '<p class="text-[10px] text-gray-500 mt-1">Calculando zona de impacto topográfica…</p>';

            const popupContent = `
                <div class="max-w-[240px] font-sans">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        ${intensidadBadge}
                        ${confirmadoBadge}
                    </div>
                    <h5 class="text-xs font-bold text-gray-800 mb-1">Evento de Inundación N°${report.id}</h5>
                    <p class="text-xs text-gray-700 mb-1 leading-snug">${shortDesc}</p>
                    <p class="text-xs text-gray-500">${quorumStr}</p>
                    ${polygonNote}
                    <a href="/reports/${report.id}" class="block mt-2 text-center text-xs text-blue-600 hover:underline font-medium">Ver detalles de Inundación →</a>
                </div>`;

            const customIcon = L.divIcon({
                className: 'custom-leaflet-marker',
                html: `<div style="background-color:${palette.marker};width:20px;height:20px;border-radius:50%;border:3px solid white;box-shadow:0 0 10px rgba(0,0,0,0.3);animation:pulse 2s infinite;"></div>`,
                iconSize: [20, 20], iconAnchor: [10, 10]
            });

            const marker = L.marker([lat, lng], { icon: customIcon })
                .bindPopup(popupContent, { minWidth: 220 })
                .on('click', () => map.flyTo([lat, lng], 15, { animate: true, duration: 0.8 }));

            markersLayer.addLayer(marker);

            let drawIndividualReports = true;
            if (report.polygon_coords && Array.isArray(report.polygon_coords) && report.polygon_coords.length >= 3) {
                drawIndividualReports = false;

                const authorityPolygon = L.polygon(report.polygon_coords, {
                    color:       '#1e3a8a', 
                    fillColor:   '#3b82f6', 
                    fillOpacity: 0.45,
                    weight:      3,
                    dashArray:   '10,5', 
                    smoothFactor: 1.0,
                });

                authorityPolygon.bindPopup(popupContent, { minWidth: 220 });
                polygonLayer.addLayer(authorityPolygon);
            }

            const activeReps = report.reportes_activos || report.reportes || [];
            if (activeReps && Array.isArray(activeReps)) {
                allActiveReports.push(...activeReps);
                
                activeReps.forEach(rep => {
                    const repLat = parseFloat(rep.lat_reporte || rep.latitud);
                    const repLng = parseFloat(rep.long_reporte || rep.longitud);
                    if (isNaN(repLat) || isNaN(repLng)) return;

                    if (drawIndividualReports && rep.polygon_coords && Array.isArray(rep.polygon_coords) && rep.polygon_coords.length >= 3) {
                        const repPolygon = L.polygon(rep.polygon_coords, {
                            color: 'transparent',
                            fillColor: '#38bdf8',
                            fillOpacity: 0.3,
                            interactive: false
                        });
                        polygonLayer.addLayer(repPolygon);
                    }

                    const repIcon = L.divIcon({
                        className: 'individual-report-dot',
                        html: `<div style="background-color:#60a5fa;width:10px;height:10px;border-radius:50%;border:1.5px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);"></div>`,
                        iconSize: [10, 10], iconAnchor: [5, 5]
                    });

                    const intensidadProp = rep.intensidad_propuesta || rep.intensidad || 'baja';
                    const repIntensityColor = { alta: 'blue', media: 'sky', baja: 'teal' }[intensidadProp] || 'gray';
                    const repIntensityBadge = `<span class="inline-flex items-center bg-${repIntensityColor}-50 text-${repIntensityColor}-700 text-[10px] font-medium px-1.5 py-0.25 rounded capitalize">Propuesta: ${intensidadProp}</span>`;

                    const pesoStr = rep.peso ? `<p class="text-gray-600 font-medium">Aportó <b>${rep.peso} pts</b> al quórum.</p>` : '';
                    
                    const repPopupContent = `
                        <div class="max-w-[200px] font-sans text-xs">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <span class="bg-gray-200 text-gray-800 text-[9px] font-bold px-1.5 py-0.5 rounded">Reporte N°${rep.id}</span>
                                ${repIntensityBadge}
                            </div>
                            ${pesoStr}
                            <p class="text-[10px] text-gray-400 mt-1">${rep.created_at_human || ''}</p>
                        </div>`;

                    const repMarker = L.marker([repLat, repLng], { icon: repIcon })
                        .bindPopup(repPopupContent, { minWidth: 160 });

                    individualReportsLayer.addLayer(repMarker);
                });
            }
        });

        if (allActiveReports.length > 0) {
            if (window.createSmartHeatmap) {
                window.smartHeatmapInstance = window.createSmartHeatmap(map, allActiveReports, {
                    targetLayer: polygonLayer
                });
            }
        }
    }

    renderReports(window.floodReports);

    // Filter handling
    window.addEventListener('locationFilterChanged', function (e) {
        const { idPrefix, region, provincia, municipio } = e.detail;

        if (idPrefix === 'filter') {
            const filtered = window.floodReports.filter(r => {
                if (region && window.geographicData && window.geographicData.regiones) {
                    const regData = window.geographicData.regiones.find(rg => rg.nombre === region);
                    if (regData && r.municipio && !regData.municipios.includes(r.municipio)) return false;
                }
                if (provincia && r.provincia && r.provincia !== provincia) return false;
                if (municipio && r.municipio && r.municipio !== municipio) return false;
                return true;
            });
            renderReports(filtered);
        }

        if (highlightLayer) { map.removeLayer(highlightLayer); highlightLayer = null; }

        if (municipio && municipalitiesData) {
            const feature = municipalitiesData.features.find(f =>
                (window.normalizeMuniName ? window.normalizeMuniName(f.properties.name) : f.properties.name.toLowerCase()) === municipio.toLowerCase()
            );
            if (feature) {
                highlightLayer = L.geoJSON(feature, {
                    style: { color: '#EF4444', weight: 3, opacity: 0.9, fillOpacity: 0.08 },
                    interactive: false
                }).addTo(map);
                map.fitBounds(highlightLayer.getBounds());
            }
        } else if (provincia && provincesData) {
            const feature = provincesData.features.find(f =>
                (window.normalizeProvName ? window.normalizeProvName(f.properties.name) : f.properties.name.toLowerCase()) === provincia.toLowerCase()
            );
            if (feature) {
                highlightLayer = L.geoJSON(feature, {
                    style: { color: '#F97316', weight: 3, opacity: 0.9, fillOpacity: 0.08 },
                    interactive: false
                }).addTo(map);
                map.fitBounds(highlightLayer.getBounds());
            }
        } else if (region && window.geographicData && municipalitiesData) {
            const regData = window.geographicData.regiones.find(rg => rg.nombre === region);
            if (regData && regData.municipios) {
                const features = municipalitiesData.features.filter(f =>
                    regData.municipios.some(rm => rm.toLowerCase() === (window.normalizeMuniName ? window.normalizeMuniName(f.properties.name) : f.properties.name.toLowerCase()))
                );
                if (features.length > 0) {
                    highlightLayer = L.geoJSON(features, {
                        style: { color: '#8B5CF6', weight: 3, opacity: 0.9, fillOpacity: 0.08 },
                        interactive: false
                    }).addTo(map);
                    map.fitBounds(highlightLayer.getBounds());
                }
            }
        } else if (idPrefix === 'filter') {
            map.setView([-17.783325, -63.182111], 12);
        }
    });
}

function toggleMapFullscreen() {
    const container = document.getElementById('map-container');
    if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
        if (container.requestFullscreen) {
            container.requestFullscreen();
        } else if (container.msRequestFullscreen) {
            container.msRequestFullscreen();
        } else if (container.mozRequestFullScreen) {
            container.mozRequestFullScreen();
        } else if (container.webkitRequestFullscreen) {
            container.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
    }
}

document.addEventListener("DOMContentLoaded", initMap);
document.addEventListener("livewire:navigated", initMap);
</script>
