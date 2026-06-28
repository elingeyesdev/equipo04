@props([
    'reports' => [],
    'pendingReports' => [],
    'showRouting' => false,
    'role' => null,
    'fetchPending' => false,
    'mapHeight' => '600px'
])

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="{{ asset('js/smart-heatmap.js') }}?v=20260629g"></script>
<script src="{{ asset('js/flood-outline.js') }}?v=20260627c"></script>

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
.flood-polygon-alta.flood-selected-outline path {
    animation: flood-pulse 2.5s ease-in-out infinite;
}
.flood-selected-outline path {
    stroke-width: 3.5px !important;
    filter: drop-shadow(0 0 6px rgba(37, 99, 235, 0.55));
}
.flood-marker-selected > div {
    box-shadow: 0 0 0 4px rgba(255,255,255,0.95), 0 0 14px rgba(37,99,235,0.75) !important;
    transform: scale(1.15);
}
.flood-report-selected > div {
    width: 14px !important;
    height: 14px !important;
    box-shadow: 0 0 0 3px #fff, 0 0 10px rgba(37,99,235,0.8) !important;
}

/* Tooltip de intensidad sobre el centroide */
.heat-tier-tooltip {
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0;
}
.heat-tier-tooltip::before { display: none; }
.heat-tier-tip {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.02em;
    box-shadow: 0 1px 4px rgba(0,0,0,0.3);
    white-space: nowrap;
}
.heat-tier-tip.heat-tier-alta  { background: #1e3a8a; }
.heat-tier-tip.heat-tier-media { background: #0ea5e9; }
.heat-tier-tip.heat-tier-baja  { background: #38bdf8; color: #0c4a6e; }

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
/* HUD de zoom/opacidad — solo para pruebas de calibración del mapa de calor (oculto) */
#map-zoom-debug {
    display: none !important;
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 1000;
    pointer-events: none;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 10px;
    line-height: 1.2;
    color: rgba(55, 65, 81, 0.85);
    background: rgba(255, 255, 255, 0.72);
    padding: 3px 6px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    user-select: none;
}
#map-container.routing-pick-mode,
#map-container.routing-pick-mode .leaflet-container {
    cursor: crosshair !important;
}
#routing-map-hint {
    animation: routing-hint-pulse 2s ease-in-out infinite;
}
@keyframes routing-hint-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.85; }
}
</style>

<div class="relative mb-10">
    <div id="map-container" class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden relative z-0" style="height: {{ $mapHeight }};" wire:ignore>
        <div id="map" class="absolute inset-0 z-0"></div>

        @if($showRouting)
        <div id="routing-map-hint" class="hidden absolute top-3 left-1/2 -translate-x-1/2 z-[1100] pointer-events-none bg-emerald-600/95 text-white text-xs font-bold px-4 py-2 rounded-full shadow-lg backdrop-blur-sm max-w-[90%] text-center">
            Haz clic en el mapa para marcar un punto
        </div>
        @endif

        {{-- HUD debug: nivel de zoom y opacidad por tier — solo pruebas, oculto --}}
        <div id="map-zoom-debug" class="hidden" aria-hidden="true">Zoom: —</div>
        
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

        <!-- LEYENDA DE INTENSIDAD DEL MAPA DE CALOR -->
        <div id="heat-intensity-legend" class="hidden absolute bottom-6 right-6 bg-white/95 backdrop-blur p-3.5 rounded-xl shadow-xl border border-gray-100 z-[1000] pointer-events-none transition-all duration-300">
            <h4 class="text-[11px] font-bold text-gray-800 mb-2.5 uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 016 6c0 4-6 10-6 10S4 12 4 8a6 6 0 016-6z"/></svg>
                <span>Intensidad de Inundación</span>
            </h4>
            <div class="space-y-1.5">
                <div class="flex items-center gap-2.5" data-heat-tier="alta">
                    <span class="w-5 h-5 rounded-md shadow-inner border border-black/10" style="background:#1e3a8a"></span>
                    <span class="text-xs text-gray-700 font-semibold">Alta</span>
                </div>
                <div class="flex items-center gap-2.5" data-heat-tier="media">
                    <span class="w-5 h-5 rounded-md shadow-inner border border-black/10" style="background:#0ea5e9"></span>
                    <span class="text-xs text-gray-700 font-semibold">Media</span>
                </div>
                <div class="flex items-center gap-2.5" data-heat-tier="baja">
                    <span class="w-5 h-5 rounded-md shadow-inner border border-black/10" style="background:#7dd3fc"></span>
                    <span class="text-xs text-gray-700 font-semibold">Baja</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-400 mt-2.5 italic max-w-[170px] leading-tight">El color refleja la intensidad calculada por peso de los reportes.</p>
        </div>
    </div>
    
    @if($showRouting)
        <!-- Panel de Rutas Seguras -->
        <x-routing-panel />
    @endif
</div>

<script>
window.SGI_MAP_CONFIG = {
    apiToken: @json(session('api_token')),
    fetchPending: @json($fetchPending),
};
@php
    $mapaService = app(\App\Services\InundacionMapaService::class);
    $pendingForMap = collect($pendingReports)->map(function ($rep) use ($mapaService) {
        if ($rep instanceof \App\Models\Reporte) {
            return $mapaService->serializarPendiente($rep);
        }
        return $rep;
    })->values()->all();
@endphp
window.floodReports = @json($reports);
window.pendingReports = @json($pendingForMap);
window.reportsMapFilter = null;

function bindReportsMapLivewireRefresh() {
    if (typeof Livewire === 'undefined' || window.__reportsMapLivewireBound) return;
    window.__reportsMapLivewireBound = true;

    Livewire.on('refreshReports', () => {
        if (typeof window.refreshReportsMap === 'function') {
            window.refreshReportsMap();
        }
    });
    Livewire.on('reporte-ttl-renovado', () => {
        if (typeof window.refreshReportsMap === 'function') {
            window.refreshReportsMap();
        }
    });
}

document.addEventListener('livewire:initialized', bindReportsMapLivewireRefresh);
document.addEventListener('DOMContentLoaded', bindReportsMapLivewireRefresh);

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

    // HUD debug (solo pruebas): zoom actual + edge/core por tier del heatmap.
    // Oculto en UI (#map-zoom-debug). Descomentar listeners para recalibrar opacidad.
    const zoomDebugEl = document.getElementById('map-zoom-debug');
    function updateMapZoomDebug() {
        if (!zoomDebugEl) return;
        const zoom = map.getZoom().toFixed(1);
        const inst = window.smartHeatmapInstance;
        if (inst && inst.debugOpacity) {
            const op = inst.debugOpacity;
            const fmt = function (tier) {
                if (!op[tier]) return '—';
                return op[tier].edge.toFixed(2) + '/' + op[tier].core.toFixed(2);
            };
            zoomDebugEl.textContent = 'Zoom: ' + zoom
                + ' · b:' + fmt('baja')
                + ' m:' + fmt('media')
                + ' a:' + fmt('alta');
        } else {
            zoomDebugEl.textContent = 'Zoom: ' + zoom;
        }
    }
    // map.on('zoom zoomend moveend', updateMapZoomDebug);
    // updateMapZoomDebug();

    // Pane de relleno SVG (debajo del contorno de selección).
    if (!map.getPane('floodFillPane')) {
        map.createPane('floodFillPane');
        map.getPane('floodFillPane').style.zIndex = 380;
    }

    // Pane dedicado: el relleno no debe tapar el contorno al seleccionar.
    if (!map.getPane('floodSelectionPane')) {
        map.createPane('floodSelectionPane');
        map.getPane('floodSelectionPane').style.zIndex = 550;
        map.getPane('floodSelectionPane').style.pointerEvents = 'none';
    }

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
    const markersLayer           = L.layerGroup().addTo(map);
    const polygonLayer           = L.layerGroup().addTo(map);
    const selectionBorderLayer   = L.layerGroup().addTo(map);
    const individualReportsLayer = L.layerGroup().addTo(map);
    const pendingReportsLayer    = L.layerGroup().addTo(map);

    window.selectedInundacionId = null;

    layerControl.addOverlay(markersLayer,           "Centros de Inundación (Centroides)");
    layerControl.addOverlay(polygonLayer,           "Zona de Inundación");
    layerControl.addOverlay(selectionBorderLayer,   "Contorno de Inundación Seleccionada");
    layerControl.addOverlay(individualReportsLayer, "Reportes Ciudadanos (Detalle)");
    layerControl.addOverlay(pendingReportsLayer,    "Reportes Pendientes (Validación)");

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

    function getInundacionOutlineRings(inundacion) {
        if (window.resolveUnifiedHeatRing) {
            const unified = window.resolveUnifiedHeatRing(inundacion);
            if (unified && unified.length >= 3) {
                return [unified];
            }
        }

        if (window.computeInundacionSelectionOutline) {
            const unified = window.computeInundacionSelectionOutline(inundacion);
            if (unified && unified.length >= 3) {
                return [unified];
            }
        }

        const activeReps = inundacion.reportes_activos || [];
        const rings = [];

        activeReps.forEach(function (rep) {
            if (rep.polygon_es_fallback || !rep.polygon_coords) return;
            if (!window.normalizePolygonRings) return;
            window.normalizePolygonRings(rep.polygon_coords).forEach(function (ring) {
                rings.push(ring);
            });
        });

        return rings;
    }

    function getInundacionHeatGeometry(inundacion) {
        if (window.resolveUnifiedHeatRing) {
            const unified = window.resolveUnifiedHeatRing(inundacion);
            if (unified && unified.length >= 3) {
                return { polygon_coords: unified, polygon_es_fallback: false };
            }
        }

        if (window.computeInundacionSelectionOutline) {
            const outline = window.computeInundacionSelectionOutline(inundacion);
            if (outline && outline.length >= 3) {
                return { polygon_coords: outline, polygon_es_fallback: false };
            }
        }

        return null;
    }

    function buildCentroidIcon(palette, selected) {
        const cls = selected ? 'custom-leaflet-marker flood-marker-selected' : 'custom-leaflet-marker';
        const border = selected ? '4px solid #fbbf24' : '3px solid white';
        return L.divIcon({
            className: cls,
            html: `<div style="background-color:${palette.marker};width:20px;height:20px;border-radius:50%;border:${border};box-shadow:0 0 10px rgba(0,0,0,0.3);transition:transform 0.2s;"></div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10],
        });
    }

    function buildReportIcon(selected) {
        const cls = selected ? 'individual-report-dot flood-report-selected' : 'individual-report-dot';
        const size = selected ? 14 : 10;
        return L.divIcon({
            className: cls,
            html: `<div style="background-color:#60a5fa;width:${size}px;height:${size}px;border-radius:50%;border:1.5px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);transition:all 0.2s;"></div>`,
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
        });
    }

    function updateSelectionMarkerStyles() {
        markersLayer.eachLayer(function (marker) {
            if (!marker._inundacionMeta) return;
            const selected = marker._inundacionMeta.id === window.selectedInundacionId;
            marker.setIcon(buildCentroidIcon(marker._inundacionMeta.palette, selected));
        });

        individualReportsLayer.eachLayer(function (marker) {
            if (!marker._inundacionId) return;
            const selected = marker._inundacionId === window.selectedInundacionId;
            marker.setIcon(buildReportIcon(selected));
        });
    }

    function clearInundacionSelection() {
        window.selectedInundacionId = null;
        window._lastSelectedInundacion = null;
        selectionBorderLayer.clearLayers();
        updateSelectionMarkerStyles();
    }

    function restoreInundacionSelectionAfterMapPaint() {
        if (!window.selectedInundacionId) return;

        const fromCache = window._lastSelectedInundacion
            && window._lastSelectedInundacion.id === window.selectedInundacionId
            ? window._lastSelectedInundacion
            : null;
        const fromReports = Array.isArray(window.floodReports)
            ? window.floodReports.find(function (r) { return r.id === window.selectedInundacionId; })
            : null;
        const inundacion = fromReports || fromCache;

        if (inundacion) {
            selectInundacion(inundacion, { fly: false });
        }
    }

    function selectInundacion(inundacion, options) {
        options = options || { fly: true };
        const lat = parseFloat(inundacion.latitud);
        const lng = parseFloat(inundacion.longitud);
        const intensidad = inundacion.intensidad_calculada || inundacion.intensidad || 'baja';
        const palette = getPalette(intensidad);

        window.selectedInundacionId = inundacion.id;
        window._lastSelectedInundacion = inundacion;
        selectionBorderLayer.clearLayers();

        const rings = getInundacionOutlineRings(inundacion);
        const outlineLayers = [];

        rings.forEach(function (ring) {
            const poly = L.polygon(ring, {
                color: palette.stroke,
                weight: 3,
                opacity: 0.95,
                fillColor: palette.fill,
                fillOpacity: 0.08,
                smoothFactor: 2.5,
                interactive: false,
                pane: 'floodSelectionPane',
                className: intensidad === 'alta'
                    ? 'flood-polygon-alta flood-selected-outline'
                    : 'flood-selected-outline',
            });
            poly.addTo(selectionBorderLayer);
            outlineLayers.push(poly);
        });

        updateSelectionMarkerStyles();

        if (options.fly) {
            if (outlineLayers.length > 0) {
                const group = L.featureGroup(outlineLayers);
                map.fitBounds(group.getBounds().pad(0.12), { maxZoom: 16, animate: true, duration: 0.8 });
            } else if (!isNaN(lat) && !isNaN(lng)) {
                map.flyTo([lat, lng], 15, { animate: true, duration: 0.8 });
            }
        }
    }

    map.on('click', function () {
        if (window.SGI_ROUTING?.isPicking?.()) return;
        clearInundacionSelection();
    });

    function renderReports(reportsData) {
        if (window.smartHeatmapInstance) {
            window.smartHeatmapInstance.remove();
            window.smartHeatmapInstance = null;
        }

        markersLayer.clearLayers();
        polygonLayer.clearLayers();
        selectionBorderLayer.clearLayers();
        individualReportsLayer.clearLayers();

        let heatSources = [];

        reportsData.forEach(report => {
            const lat = parseFloat(report.latitud);
            const lng = parseFloat(report.longitud);
            if (isNaN(lat) || isNaN(lng)) return;

            const activeReps = Array.isArray(report.reportes_activos) ? report.reportes_activos : [];
            const mostrarEnMapa = report.mostrar_en_mapa !== false && activeReps.length > 0;
            if (!mostrarEnMapa) return;

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
            
            let numReports = activeReps.length;

            const polygonNote = report.polygon_coords
                ? `<p class="text-[10px] text-blue-600 mt-1">Zona de impacto en mapa de calor (${numReports} reportes)</p>`
                : '<p class="text-[10px] text-gray-500 mt-1">Calculando zona de impacto…</p>';

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

            const customIcon = buildCentroidIcon(palette, window.selectedInundacionId === report.id);

            const marker = L.marker([lat, lng], { icon: customIcon })
                .bindPopup(popupContent, { minWidth: 220 });

            const intensidadLabel = intensidad.charAt(0).toUpperCase() + intensidad.slice(1);
            const quorumLabel = report.quorum_total !== undefined ? ` · ${report.quorum_total} pts` : '';
            marker.bindTooltip(
                `<span class="heat-tier-tip heat-tier-${intensidad}">${intensidadLabel}${quorumLabel}</span>`,
                { permanent: true, direction: 'top', offset: [0, -14], className: 'heat-tier-tooltip', opacity: 1 }
            );

            marker._inundacionMeta = { id: report.id, palette: palette };

            marker.on('click', function (e) {
                L.DomEvent.stopPropagation(e);
                if (window.selectedInundacionId === report.id) {
                    clearInundacionSelection();
                } else {
                    selectInundacion(report, { fly: true });
                }
            });

            markersLayer.addLayer(marker);

            // Epicentros = ubicación de cada reporte vivo, para ponderar la profundidad
            // dentro de la zona unificada (centro más intenso, bordes difuminados).
            const epicenters = activeReps.map(function (rep) {
                return {
                    lat: parseFloat(rep.lat_reporte || rep.latitud),
                    lng: parseFloat(rep.long_reporte || rep.longitud),
                    updated_at: rep.updated_at || rep.created_at,
                };
            }).filter(function (ep) { return !isNaN(ep.lat) && !isNaN(ep.lng); });

            const heatGeometry = getInundacionHeatGeometry(report);
            const allRepsHavePolygon = activeReps.length > 0 && activeReps.every(function (rep) {
                if (rep.polygon_es_fallback || !rep.polygon_coords) return false;
                if (!window.normalizePolygonRings) return false;
                return window.normalizePolygonRings(rep.polygon_coords).length > 0;
            });

            // Un solo heatSource por inundación (un raster / un ImageOverlay).
            // Si algún reporte aún no tiene polígono, omitimos el anillo parcial y pintamos radiales en todos los epicentros.
            heatSources.push({
                lat: lat,
                lng: lng,
                polygon_coords: (heatGeometry && allRepsHavePolygon) ? heatGeometry.polygon_coords : null,
                polygon_es_fallback: heatGeometry ? heatGeometry.polygon_es_fallback : false,
                tier: intensidad,
                updated_at: report.updated_at,
                epicenters: epicenters.length > 0 ? epicenters : undefined,
            });

            if (activeReps.length > 0) {
                activeReps.forEach(rep => {
                    const repLat = parseFloat(rep.lat_reporte || rep.latitud);
                    const repLng = parseFloat(rep.long_reporte || rep.longitud);
                    if (isNaN(repLat) || isNaN(repLng)) return;

                    const repSelected = window.selectedInundacionId === report.id;
                    const repIcon = buildReportIcon(repSelected);

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

                    repMarker._inundacionId = report.id;
                    repMarker.on('click', function (e) {
                        L.DomEvent.stopPropagation(e);
                        selectInundacion(report, { fly: false });
                    });

                    individualReportsLayer.addLayer(repMarker);
                });
            }
        });

        const heatLegend = document.getElementById('heat-intensity-legend');
        if (heatSources.length > 0 && window.createSmartHeatmap) {
            window.smartHeatmapInstance = window.createSmartHeatmap(map, heatSources, {
                targetLayer: polygonLayer,
                mode: 'auto',
                ttlHours: 3,
                pane: 'floodFillPane',
            });

            if (window.smartHeatmapInstance && typeof window.redrawFloodHeatOverlays === 'function') {
                window.redrawFloodHeatOverlays(map, window.smartHeatmapInstance);
            }

            if (heatLegend && window.smartHeatmapInstance && window.smartHeatmapInstance.tiers) {
                const tiers = window.smartHeatmapInstance.tiers;
                heatLegend.classList.remove('hidden');
                heatLegend.querySelectorAll('[data-heat-tier]').forEach(function (row) {
                    row.style.display = tiers.indexOf(row.getAttribute('data-heat-tier')) !== -1 ? '' : 'none';
                });
            }
            // updateMapZoomDebug(); // pruebas heatmap — HUD oculto
        } else if (heatLegend) {
            heatLegend.classList.add('hidden');
        }

        if (window.selectedInundacionId) {
            const selected = reportsData.find(function (r) {
                return r.id === window.selectedInundacionId;
            });
            if (selected) {
                selectInundacion(selected, { fly: false });
            } else {
                clearInundacionSelection();
            }
        }
    }

    function applyActiveMapFilter(reports) {
        const source = Array.isArray(reports) ? reports : [];
        const filter = window.reportsMapFilter;
        if (!filter || filter.idPrefix !== 'filter') {
            return source;
        }

        return source.filter(function (r) {
            if (filter.region && window.geographicData && window.geographicData.regiones) {
                const regData = window.geographicData.regiones.find(function (rg) {
                    return rg.nombre === filter.region;
                });
                if (regData && r.municipio && !regData.municipios.includes(r.municipio)) {
                    return false;
                }
            }
            if (filter.provincia && r.provincia && r.provincia !== filter.provincia) {
                return false;
            }
            if (filter.municipio && r.municipio && r.municipio !== filter.municipio) {
                return false;
            }
            return true;
        });
    }

    function renderPendingReportsOnMap(pendingData) {
        pendingReportsLayer.clearLayers();
        if (!Array.isArray(pendingData)) return;

        pendingData.forEach(function (report) {
            if (report.inundacion_id) return;

            const lat = parseFloat(report.lat_reporte);
            const lng = parseFloat(report.long_reporte);
            if (isNaN(lat) || isNaN(lng)) return;

            const customIcon = L.divIcon({
                className: 'custom-leaflet-marker',
                html: '<div style="background-color:#F59E0B;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 0 4px rgba(0,0,0,0.5);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8],
            });

            const popupHtml = typeof window.buildPendingValidationPopupHtml === 'function'
                ? window.buildPendingValidationPopupHtml(report)
                : ('<div class="max-w-xs font-sans">'
                    + '<p class="font-semibold text-sm mb-1 text-orange-600">Reporte Pendiente N°' + report.id + '</p>'
                    + '<p class="text-xs text-gray-600 mb-2">Intensidad: ' + (report.intensidad_propuesta || 'media') + '</p>'
                    + '</div>');

            const marker = L.marker([lat, lng], { icon: customIcon })
                .bindPopup(popupHtml, { minWidth: 200 });

            pendingReportsLayer.addLayer(marker);
        });
    }

    window.renderReportsMap = renderReports;
    window.renderPendingReportsMap = renderPendingReportsOnMap;
    window.restoreInundacionSelectionAfterMapPaint = restoreInundacionSelectionAfterMapPaint;

    window.refreshReportsMap = async function refreshReportsMap() {
        const cfg = window.SGI_MAP_CONFIG || {};
        if (!cfg.apiToken || typeof window.renderReportsMap !== 'function') {
            return;
        }

        window.__reportsMapFetchGen = (window.__reportsMapFetchGen || 0) + 1;
        const fetchGen = window.__reportsMapFetchGen;

        const headers = {
            Accept: 'application/json',
            Authorization: 'Bearer ' + cfg.apiToken,
        };

        try {
            const reportsRes = await fetch('/api/reports', {
                headers: headers,
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (reportsRes.ok && fetchGen === window.__reportsMapFetchGen) {
                const payload = await reportsRes.json();
                const reports = Array.isArray(payload.data) ? payload.data : [];
                window.floodReports = reports;
                window.renderReportsMap(applyActiveMapFilter(reports));

                if (window.smartHeatmapInstance && typeof window.redrawFloodHeatOverlays === 'function') {
                    window.redrawFloodHeatOverlays(window.mapObj, window.smartHeatmapInstance);
                    setTimeout(function () {
                        if (fetchGen !== window.__reportsMapFetchGen || !window.smartHeatmapInstance) return;
                        window.redrawFloodHeatOverlays(window.mapObj, window.smartHeatmapInstance);
                    }, 120);
                }

                setTimeout(function () {
                    if (fetchGen !== window.__reportsMapFetchGen) return;
                    restoreInundacionSelectionAfterMapPaint();
                }, 50);
            }

            if (cfg.fetchPending && fetchGen === window.__reportsMapFetchGen) {
                const pendingRes = await fetch('/api/reportes/pendientes', {
                    headers: headers,
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (pendingRes.ok) {
                    const pending = await pendingRes.json();
                    const queue = Array.isArray(pending)
                        ? pending.filter(function (r) { return !r.inundacion_id; })
                        : [];
                    window.pendingReports = queue;
                    renderPendingReportsOnMap(queue);
                }
            }

            if (fetchGen === window.__reportsMapFetchGen) {
                document.dispatchEvent(new CustomEvent('reportsMapRefreshed'));
            }
        } catch (err) {
            console.warn('No se pudo refrescar el mapa de reportes:', err);
        }
    };

    renderReports(applyActiveMapFilter(window.floodReports));
    renderPendingReportsOnMap(
        Array.isArray(window.pendingReports)
            ? window.pendingReports.filter(function (r) { return !r.inundacion_id; })
            : []
    );

    // Filter handling
    window.addEventListener('locationFilterChanged', function (e) {
        const { idPrefix, region, provincia, municipio } = e.detail;

        if (idPrefix === 'filter') {
            window.reportsMapFilter = e.detail;
            renderReports(applyActiveMapFilter(window.floodReports));
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
