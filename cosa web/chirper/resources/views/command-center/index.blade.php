@extends('layouts.app')

@section('content')
<div class="command-center-container bg-slate-900 text-slate-100 min-h-screen -mt-6 -mx-4 p-4 lg:p-6 flex flex-col font-sans">
    
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                Centro de Comando (Análisis de Impacto)
            </h1>
            <p class="mt-1 text-sm text-slate-400">Visualización interactiva de daños materiales, víctimas y avance geográfico.</p>
<style>
    /* Custom Map Tooltips for Dark Mode */
    .dark-tooltip {
        background-color: #1e293b !important;
        color: #f8fafc !important;
        border: 1px solid #475569 !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5) !important;
        font-family: 'Inter', sans-serif !important;
        font-size: 0.75rem !important;
    }
    .dark-tooltip .leaflet-tooltip-tip {
        border-top-color: #1e293b !important;
    }
</style>
        </div>
        <div class="flex gap-3">
            @if(session('api_user') && (session('api_user')['role'] ?? '') === 'authority')
            <button onclick="openMergeModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow-lg flex items-center gap-2 text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                Fusión Manual
            </button>
            <button onclick="openDamageModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow-lg flex items-center gap-2 text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Registrar Daño Material
            </button>
            @endif
        </div>
    </div>

    <!-- Alert for Merge Recommendations -->
    <div id="merge-alerts" class="mb-4 hidden space-y-2"></div>

    <div class="flex flex-col lg:flex-row gap-4 flex-1 h-[calc(100vh-140px)]">
        <!-- Panel Izquierdo: Estadísticas -->
        <div class="w-full lg:w-1/4 bg-slate-800 rounded-xl shadow-xl border border-slate-700 p-4 flex flex-col overflow-hidden">
            <h2 class="text-lg font-semibold text-slate-200 mb-4 border-b border-slate-700 pb-2">Resumen de Impacto</h2>
            
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-slate-700 p-3 rounded-lg text-center">
                    <span class="block text-xs text-slate-400 uppercase tracking-wider">Inundaciones</span>
                    <span class="block text-2xl font-bold text-blue-400" id="stat-inundaciones">0</span>
                </div>
                <div class="bg-slate-700 p-3 rounded-lg text-center">
                    <span class="block text-xs text-slate-400 uppercase tracking-wider">Reportes</span>
                    <span class="block text-2xl font-bold text-sky-400" id="stat-reportes">0</span>
                </div>
                <div class="bg-slate-700 p-3 rounded-lg text-center">
                    <span class="block text-xs text-slate-400 uppercase tracking-wider">Víctimas</span>
                    <span class="block text-2xl font-bold text-red-400" id="stat-victimas">0</span>
                </div>
                <div class="bg-slate-700 p-3 rounded-lg text-center">
                    <span class="block text-xs text-slate-400 uppercase tracking-wider">Daños Mat.</span>
                    <span class="block text-2xl font-bold text-orange-400" id="stat-danos">0</span>
                </div>
            </div>

            <h3 class="text-sm font-semibold text-slate-300 mb-2">Detalle de Eventos (Seleccionado)</h3>
            <div id="detail-panel" class="flex-1 overflow-y-auto pr-2 custom-scrollbar text-sm text-slate-400">
                <p class="text-center mt-10">Haz clic en una inundación o reporte en el mapa para ver los detalles.</p>
            </div>
        </div>

        <!-- Panel Derecho: Mapa y Timeline -->
        <div class="w-full lg:w-3/4 flex flex-col gap-4 relative">
            <!-- Contenedor del Mapa -->
            <div class="flex-1 bg-slate-800 rounded-xl shadow-xl border border-slate-700 relative overflow-hidden z-0">
                <div id="cc-map" class="absolute inset-0 z-0"></div>
                
                <!-- Leyenda -->
                <div class="absolute top-4 right-4 bg-slate-800/90 backdrop-blur border border-slate-600 p-3 rounded-lg shadow-lg z-[1000] text-xs">
                    <div class="font-bold mb-2 text-slate-200">Convenciones</div>
                    <div class="flex items-center gap-2 mb-1"><div class="w-3 h-3 bg-blue-600 rounded"></div> <span class="text-slate-300">Inundación Alta</span></div>
                    <div class="flex items-center gap-2 mb-1"><div class="w-3 h-3 bg-sky-500 rounded"></div> <span class="text-slate-300">Inundación Media</span></div>
                    <div class="flex items-center gap-2 mb-1"><div class="w-3 h-3 bg-teal-500 rounded"></div> <span class="text-slate-300">Inundación Baja</span></div>
                    <div class="flex items-center gap-2 mb-1"><div class="w-3 h-3 border-2 border-red-500 rounded-full"></div> <span class="text-slate-300">Víctima</span></div>
                    <div class="flex items-center gap-2"><div class="w-3 h-3 border-2 border-orange-500 rounded-none transform rotate-45"></div> <span class="text-slate-300">Daño Material</span></div>
                </div>
            </div>

            <!-- Timeline Slider -->
            <div class="h-24 bg-slate-800 rounded-xl shadow-xl border border-slate-700 p-4 flex flex-col justify-center">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Línea de Tiempo del Desastre</span>
                    <span id="timeline-display" class="text-sm font-bold text-blue-400 bg-slate-700 px-3 py-1 rounded">Cargando fechas...</span>
                </div>
                <input type="range" id="timeline-slider" min="0" max="100" value="100" class="w-full h-2 bg-slate-600 rounded-lg appearance-none cursor-pointer">
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registrar Daño Material -->
<div id="modal-dano" class="hidden fixed inset-0 z-[2000] bg-black/60 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 w-full max-w-3xl shadow-2xl flex flex-col max-h-[90vh]">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Registrar Daño Material
        </h3>
        <form id="form-dano" onsubmit="submitDano(event)" class="flex flex-col flex-1 overflow-y-auto custom-scrollbar pr-2">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <!-- Columna Izquierda: Datos -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Inundación Asociada</label>
                        <input type="hidden" name="inundacion_id" id="dano_inundacion_id" required>
                        
                        <!-- Trigger del Drawer -->
                        <div id="dano_fp_trigger">
                            <button type="button" onclick="openFloodDrawer()" class="w-full flex items-center justify-between bg-slate-700 hover:bg-slate-600 border border-slate-500 border-dashed text-slate-300 rounded p-3 transition-colors group">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-slate-800 flex items-center justify-center group-hover:bg-blue-900 transition-colors">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="block text-sm font-medium text-white">Buscar inundación</span>
                                        <span class="block text-[10px] text-slate-400">Clic para explorar eventos registrados</span>
                                    </div>
                                </div>
                            </button>
                        </div>
                        
                        <!-- Selected Chip -->
                        <div id="dano_fp_selected" class="hidden rounded-lg border border-blue-500/50 bg-blue-900/20 overflow-hidden shadow-lg">
                            <div class="flex items-center justify-between p-3 border-b border-blue-500/20 bg-blue-900/30">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.8)]"></div>
                                    <span id="dano_fp_label" class="text-xs font-bold text-blue-200">#000</span>
                                </div>
                                <button type="button" onclick="openFloodDrawer()" class="text-[10px] uppercase font-bold text-blue-300 hover:text-white transition-colors">Cambiar</button>
                            </div>
                            <div id="dano_fp_chip_map" class="h-28 w-full bg-slate-900 z-0 border-b border-blue-500/20"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Tipo de Infraestructura</label>
                        <select id="dano_tipo" required class="w-full bg-slate-700 border border-slate-600 text-white rounded p-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="puente">Puente</option>
                            <option value="carretera">Carretera / Vía</option>
                            <option value="edificio">Edificio / Vivienda</option>
                            <option value="cultivo">Cultivo / Área Agrícola</option>
                            <option value="vehicular">Vehicular</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Estado</label>
                        <select id="dano_estado" required class="w-full bg-slate-700 border border-slate-600 text-white rounded p-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="dañado">Dañado</option>
                            <option value="destruido">Destruido</option>
                            <option value="bloqueado">Bloqueado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Descripción</label>
                        <textarea id="dano_desc" rows="3" class="w-full bg-slate-700 border border-slate-600 text-white rounded p-2 text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>

                <!-- Columna Derecha: Mapa -->
                <div class="flex flex-col">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Ubicación Exacta del Daño</label>
                    <p class="text-[10px] text-slate-400 mb-2">Mueve el marcador naranja en el minimapa para precisar la ubicación.</p>
                    <div id="mini-map" class="w-full flex-1 min-h-[250px] rounded-lg border border-slate-600 z-[2001] bg-slate-800"></div>
                    <input type="hidden" id="dano_lat" required>
                    <input type="hidden" id="dano_lng" required>
                    
                    <div class="flex items-center gap-2 mt-2 bg-slate-700 p-2 rounded text-xs text-slate-300">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span id="dano-coords-display">Lat: --, Lng: --</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-700 mt-2">
                <button type="button" onclick="closeDamageModal()" class="px-4 py-2 text-sm text-slate-300 hover:text-white transition-colors">Cancelar</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">Guardar Daño</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Lista de Daños Materiales -->
<div id="modal-lista-danos" class="hidden fixed inset-0 z-[2000] bg-black/60 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 w-full max-w-2xl shadow-2xl flex flex-col max-h-[90vh]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Daños Materiales Registrados
            </h3>
            <button type="button" onclick="closeDamagesListModal()" class="text-slate-400 hover:text-white transition-colors bg-slate-700 hover:bg-slate-600 p-1.5 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <p id="lista-danos-subtitle" class="text-xs text-slate-400 mb-4">Mostrando daños de la Inundación N°--</p>
        
        <div class="flex-1 overflow-y-auto custom-scrollbar pr-2">
            <div id="lista-danos-container" class="space-y-3">
                <!-- JS renderiza los daños aquí -->
            </div>
        </div>
        <div class="flex justify-end pt-4 border-t border-slate-700 mt-4">
            <button type="button" onclick="closeDamagesListModal()" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal: Fusión Manual -->
<div id="modal-merge" class="hidden fixed inset-0 z-[2000] bg-black/60 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Fusión Manual Asistida</h3>
        <p class="text-xs text-slate-400 mb-4">Une dos eventos de inundación. El evento de origen será absorbido (incluyendo sus reportes, víctimas y daños) por el evento destino.</p>
        <form id="form-merge" onsubmit="submitMerge(event)">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-medium text-slate-300 mb-1">Inundación Principal (Destino)</label>
                <select id="merge_destino_id" required class="w-full bg-slate-700 border border-slate-600 text-white rounded p-2 text-sm">
                    <option value="">Seleccione...</option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-xs font-medium text-slate-300 mb-1">Inundación a Absorber (Origen)</label>
                <select id="merge_origen_id" required class="w-full bg-slate-700 border border-slate-600 text-white rounded p-2 text-sm">
                    <option value="">Seleccione...</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeMergeModal()" class="px-4 py-2 text-sm text-slate-300 hover:text-white transition-colors">Cancelar</button>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">Fusionar Eventos</button>
            </div>
        </form>
    </div>
</div>

<!-- Estilos Custom (Slider y Scrollbar) -->
<style>
    .command-center-container {
        /* Para que ocupe toda la pantalla ocultando márgenes del layout por defecto si los hay */
    }
    input[type=range]::-webkit-slider-thumb {
        -webkit-appearance: none;
        height: 20px;
        width: 20px;
        border-radius: 50%;
        background: #3b82f6;
        cursor: pointer;
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
    }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #1e293b; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
    
    /* Leaflet Dark Tooltip/Popup */
    .leaflet-popup-content-wrapper, .leaflet-popup-tip {
        background: #1e293b;
        color: #f8fafc;
        border: 1px solid #334155;
    }
    .leaflet-popup-content { margin: 12px; }
    .leaflet-container a.leaflet-popup-close-button { color: #cbd5e1; }
</style>

<!-- Scripts de Mapas -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="{{ asset('js/smart-heatmap.js') }}"></script>


<!-- Drawer (Flood Picker Dark Mode) -->
<div id="fp-drawer-backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[2500] hidden transition-opacity duration-300 opacity-0" onclick="closeFloodDrawer()"></div>
<div id="fp-drawer" class="fixed inset-y-0 right-0 w-full max-w-sm bg-slate-800 border-l border-slate-700 shadow-[0_0_40px_rgba(0,0,0,0.5)] z-[2501] flex flex-col translate-x-full transition-transform duration-300 ease-in-out">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-700 bg-slate-900/50">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            Seleccionar Inundación
        </h3>
        <button type="button" onclick="closeFloodDrawer()" class="text-slate-400 hover:text-white transition-colors bg-slate-800 hover:bg-slate-700 p-1.5 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
    </div>
    <div class="p-4 border-b border-slate-700 space-y-3 bg-slate-800">
        <div class="relative">
            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="text" id="fp_search" placeholder="Buscar por ID..." class="w-full bg-slate-900 border border-slate-600 text-slate-200 text-sm rounded-lg pl-9 pr-3 py-2 focus:border-blue-500 focus:outline-none" oninput="renderFloodList()">
        </div>
        <div class="flex gap-2">
            <select id="fp_provincia" class="w-1/2 bg-slate-900 border border-slate-600 text-slate-300 text-xs rounded-lg px-2 py-1.5 focus:border-blue-500 focus:outline-none" onchange="updateMunicipiosDropdown(); renderFloodList()">
                <option value="">Cualquier Provincia</option>
            </select>
            <select id="fp_municipio" class="w-1/2 bg-slate-900 border border-slate-600 text-slate-300 text-xs rounded-lg px-2 py-1.5 focus:border-blue-500 focus:outline-none" onchange="renderFloodList()">
                <option value="">Cualquier Municipio</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <input type="date" id="fp_date_from" class="w-1/2 bg-slate-900 border border-slate-600 text-slate-400 text-xs rounded-lg px-2 py-1.5 focus:border-blue-500 focus:outline-none" onchange="renderFloodList()">
            <span class="text-slate-500 text-xs">-</span>
            <input type="date" id="fp_date_to" class="w-1/2 bg-slate-900 border border-slate-600 text-slate-400 text-xs rounded-lg px-2 py-1.5 focus:border-blue-500 focus:outline-none" onchange="renderFloodList()">
        </div>
    </div>
    <div id="fp_results" class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-3 bg-slate-900/30">
        <!-- JS Cards -->
    </div>
</div>

<script>
    let ccMap;
    let allData = [];
    let timeOrderedEvents = [];
    let layerGroup;
    let selectedFloodId = null;
    let miniMap = null;
    let miniMarker = null;
    
    // Configuración visual sólida
    const PALETTE = {
        alta:  { color: '#2563eb', fill: '#1d4ed8' },
        media: { color: '#0ea5e9', fill: '#0369a1' },
        baja:  { color: '#14b8a6', fill: '#0f766e' },
        null:  { color: '#64748b', fill: '#475569' }
    };

    function getConvexHull(points) {
        if (points.length <= 2) return points;
        points = points.slice().sort((a, b) => a.lat === b.lat ? a.lng - b.lng : a.lat - b.lat);
        const cross = (o, a, b) => (a.lat - o.lat) * (b.lng - o.lng) - (a.lng - o.lng) * (b.lat - o.lat);
        const lower = [];
        for (let i = 0; i < points.length; i++) {
            while (lower.length >= 2 && cross(lower[lower.length - 2], lower[lower.length - 1], points[i]) <= 0) lower.pop();
            lower.push(points[i]);
        }
        const upper = [];
        for (let i = points.length - 1; i >= 0; i--) {
            while (upper.length >= 2 && cross(upper[upper.length - 2], upper[upper.length - 1], points[i]) <= 0) upper.pop();
            upper.push(points[i]);
        }
        upper.pop(); lower.pop();
        return lower.concat(upper);
    }

    document.addEventListener("DOMContentLoaded", () => {
        initMap();
        fetchData();
        fetchMergeRecommendations();
        
        document.getElementById('timeline-slider').addEventListener('input', updateTimelineDisplay);
    });

    function initMap() {
        // CartoDB Dark Matter para un look "Centro de Comando"
        ccMap = L.map('cc-map', { preferCanvas: true }).setView([-17.7833, -63.1821], 12);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB',
            maxZoom: 19
        }).addTo(ccMap);
        
        layerGroup = L.layerGroup().addTo(ccMap);
    }

    function initMiniMap() {
        if (!miniMap) {
            miniMap = L.map('mini-map').setView([-17.7833, -63.1821], 12);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; CartoDB', maxZoom: 19
            }).addTo(miniMap);

            const icon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color:#f97316; width:16px; height:16px; border:2px solid white; transform: rotate(45deg); box-shadow:0 0 8px rgba(0,0,0,0.5);"></div>`,
                iconSize: [16,16], iconAnchor: [8,8]
            });
            
            miniMarker = L.marker([-17.7833, -63.1821], { draggable: true, icon: icon }).addTo(miniMap);
            
            miniMarker.on('dragend', function(e) {
                const pos = miniMarker.getLatLng();
                document.getElementById('dano_lat').value = pos.lat.toFixed(6);
                document.getElementById('dano_lng').value = pos.lng.toFixed(6);
                document.getElementById('dano-coords-display').innerText = `Lat: ${pos.lat.toFixed(6)}, Lng: ${pos.lng.toFixed(6)}`;
            });

            miniMap.on('click', function(e) {
                miniMarker.setLatLng(e.latlng);
                document.getElementById('dano_lat').value = e.latlng.lat.toFixed(6);
                document.getElementById('dano_lng').value = e.latlng.lng.toFixed(6);
                document.getElementById('dano-coords-display').innerText = `Lat: ${e.latlng.lat.toFixed(6)}, Lng: ${e.latlng.lng.toFixed(6)}`;
            });
        }
    }

    function fetchData() {
        fetch('/command-center/data', {
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer {{ session("api_token") }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            allData = data;
            processTimeline(data);
            populateSelects(data);
            populateLocationDropdowns();
            updateStats(data);
        })
        .catch(err => console.error("Error fetching data:", err));
    }

    function processTimeline(data) {
        timeOrderedEvents = [];
        data.forEach(inun => {
            // Recopilar todas las fechas
            if(inun.created_at) timeOrderedEvents.push({ type: 'inun', date: new Date(inun.created_at) });
            inun.reportes.forEach(r => timeOrderedEvents.push({ type: 'rep', date: new Date(r.created_at) }));
            inun.victimas.forEach(v => timeOrderedEvents.push({ type: 'vic', date: new Date(v.created_at) }));
            inun.danos_materiales.forEach(d => timeOrderedEvents.push({ type: 'dano', date: new Date(d.created_at) }));
        });

        timeOrderedEvents.sort((a, b) => a.date - b.date);
        
        if(timeOrderedEvents.length > 0) {
            const slider = document.getElementById('timeline-slider');
            slider.max = timeOrderedEvents.length - 1;
            slider.value = timeOrderedEvents.length - 1;
            updateTimelineDisplay();
        }
    }

    function updateTimelineDisplay() {
        if(timeOrderedEvents.length === 0) return;
        const index = document.getElementById('timeline-slider').value;
        const currentDate = timeOrderedEvents[index].date;
        document.getElementById('timeline-display').innerText = currentDate.toLocaleString();
        renderMapAtDate(currentDate);
    }

    function renderMapAtDate(maxDate) {
        layerGroup.clearLayers();
        if (window.activeHeatLayer) {
            ccMap.removeLayer(window.activeHeatLayer);
            window.activeHeatLayer = null;
        }

        let allActiveReports = [];
        
        allData.forEach(inun => {
            const inunDate = new Date(inun.created_at);
            if (inunDate > maxDate) return; // Aún no existe en esta fecha

            // Filtrar los reportes activos para la fecha seleccionada
            const activeReports = inun.reportes.filter(rep => new Date(rep.created_at) <= maxDate);
            if (activeReports.length > 0) {
                allActiveReports.push(...activeReports);
            }

            const isSelected = selectedFloodId === inun.id;
            const style = PALETTE[inun.intensidad_calculada || 'null'];

            // --- CONTORNO REAL DE LA INUNDACIÓN (CONVEX HULL) ---
            let hullPoints = activeReports.map(rep => ({ lat: parseFloat(rep.lat), lng: parseFloat(rep.lng) }));
            if (hullPoints.length >= 3) {
                const hull = getConvexHull(hullPoints);
                if (hull && hull.length >= 3) {
                    L.polygon(hull, {
                        color: isSelected ? '#ffffff' : style.color,
                        fillColor: isSelected ? '#ffffff' : '#ffffff',
                        fillOpacity: isSelected ? 0.15 : 0.01,
                        weight: isSelected ? 5 : 3,
                        dashArray: isSelected ? '' : '5,5',
                        interactive: true,
                        pane: 'markerPane'
                    }).on('click', () => selectFlood(inun)).addTo(layerGroup);
                }
            } else if (hullPoints.length > 0) {
                // Para 1 o 2 puntos, usamos un círculo como contorno
                hullPoints.forEach(pt => {
                    L.circle([pt.lat, pt.lng], {
                        radius: 300,
                        color: isSelected ? '#ffffff' : style.color,
                        fillColor: isSelected ? '#ffffff' : '#ffffff',
                        fillOpacity: isSelected ? 0.15 : 0.01,
                        weight: isSelected ? 5 : 3,
                        dashArray: isSelected ? '' : '5,5',
                        interactive: true,
                        pane: 'markerPane'
                    }).on('click', () => selectFlood(inun)).addTo(layerGroup);
                });
            }

            // Círculos invisibles sobre los reportes para hacer TODO el mapa de calor clickeable
            activeReports.forEach(rep => {
                L.circle([parseFloat(rep.lat), parseFloat(rep.lng)], {
                    radius: 200, 
                    color: 'transparent', 
                    fillColor: '#ffffff', 
                    fillOpacity: 0.01, 
                    interactive: true,
                    pane: 'markerPane'
                }).on('click', () => selectFlood(inun)).addTo(layerGroup);
            });

            // Dibujar marcador de centroide de inundación
            const iconCentroide = L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color:${style.fill}; width:18px; height:18px; border-radius:50%; border:3px solid ${isSelected ? '#facc15' : 'white'}; box-shadow:0 0 10px rgba(0,0,0,0.5);"></div>`,
                iconSize: [18,18], iconAnchor: [9,9]
            });
            L.marker([parseFloat(inun.centroide.lat), parseFloat(inun.centroide.lng)], {icon: iconCentroide})
                .on('click', () => selectFlood(inun))
                .addTo(layerGroup);

            // Daños materiales (Naranjas/Diamante virtual)
            inun.danos_materiales.forEach(dano => {
                const isDanoActive = new Date(dano.created_at) <= maxDate;
                if(isDanoActive) {
                    const iconDano = L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style="background-color:#f97316; width:14px; height:14px; border:2px solid white; transform: rotate(45deg); box-shadow:0 0 8px rgba(0,0,0,0.5);"></div>`,
                        iconSize: [14,14], iconAnchor: [7,7]
                    });
                    const marker = L.marker([parseFloat(dano.lat), parseFloat(dano.lng)], {icon: iconDano});
                    marker.bindTooltip(
                        `<b class="text-orange-400">${dano.tipo_infraestructura.toUpperCase()}</b><br>
                         <span class="text-slate-300">Estado: <span class="text-white">${dano.estado}</span></span>
                         ${dano.descripcion ? '<br><span class="text-slate-400 italic mt-1 block">"'+dano.descripcion+'"</span>' : ''}`, 
                        { direction: 'top', className: 'dark-tooltip' }
                    );
                    marker.on('click', (e) => {
                        L.DomEvent.stopPropagation(e); // Aísla el clic para que no seleccione la inundación detrás
                    });
                    marker.addTo(layerGroup);
                }
            });

            // Víctimas (Rojos)
            inun.victimas.forEach((v) => {
                if(new Date(v.created_at) > maxDate) return;
                const vLat = parseFloat(inun.centroide.lat) + (Math.random() - 0.5) * 0.005;
                const vLng = parseFloat(inun.centroide.lng) + (Math.random() - 0.5) * 0.005;
                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color:#ef4444; width:12px; height:12px; border-radius:50%; border:2px solid white;"></div>`,
                    iconSize: [12,12], iconAnchor: [6,6]
                });
                L.marker([vLat, vLng], {icon}).bindPopup(`<b>Víctima #${v.id}</b><br>Estado: ${v.estado}`).addTo(layerGroup);
            });
        });

        // Crear la capa de calor (Heatmap)
        if (allActiveReports.length > 0) {
            // Utilizamos el tamaño ampliado (radius: 75, blur: 45) que tenía el command center
            window.activeHeatLayer = window.createSmartHeatmap(ccMap, allActiveReports, {
                heatOptions: {
                    radius: 75,
                    blur: 45,
                    minOpacity: 0.4
                }
            }).layer;
        }
    }

    function populateSelects(data) {
        let html = '<option value="">Seleccione una inundación...</option>';
        data.forEach(d => {
            const dateStr = new Date(d.created_at).toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' });
            html += `<option value="${d.id}">Inundación #${d.id} · ${dateStr} · Intensidad ${d.intensidad_calculada}</option>`;
        });
        document.getElementById('merge_destino_id').innerHTML = html;
        document.getElementById('merge_origen_id').innerHTML = html;
    }

    function populateLocationDropdowns() {
        const provs = [...new Set(allData.map(d => d.provincia).filter(Boolean))].sort();
        const provSelect = document.getElementById('fp_provincia');
        provSelect.innerHTML = '<option value="">Cualquier Provincia</option>';
        provs.forEach(p => {
            provSelect.innerHTML += `<option value="${p}">${p}</option>`;
        });
        updateMunicipiosDropdown();
    }

    function updateMunicipiosDropdown() {
        const selProv = document.getElementById('fp_provincia').value;
        const munSelect = document.getElementById('fp_municipio');
        munSelect.innerHTML = '<option value="">Cualquier Municipio</option>';
        
        let filteredData = allData;
        if (selProv) {
            filteredData = allData.filter(d => d.provincia === selProv);
        }
        const muns = [...new Set(filteredData.map(d => d.municipio).filter(Boolean))].sort();
        muns.forEach(m => {
            munSelect.innerHTML += `<option value="${m}">${m}</option>`;
        });
    }

    function updateStats(data) {
        let totalReps = 0, totalVics = 0, totalDanos = 0;
        data.forEach(d => {
            totalReps += d.reportes.length;
            totalVics += d.victimas.length;
            totalDanos += d.danos_materiales.length;
        });
        document.getElementById('stat-inundaciones').innerText = data.length;
        document.getElementById('stat-reportes').innerText = totalReps;
        document.getElementById('stat-victimas').innerText = totalVics;
        document.getElementById('stat-danos').innerText = totalDanos;
    }

    function fetchMergeRecommendations() {
        fetch('/command-center/merge-recommendations', {
            headers: { 'Accept': 'application/json', 'Authorization': 'Bearer {{ session("api_token") }}' }
        }).then(res => res.json()).then(data => {
            const alertBox = document.getElementById('merge-alerts');
            if(data.length > 0) {
                alertBox.classList.remove('hidden');
                alertBox.innerHTML = '';
                data.forEach(rec => {
                    alertBox.innerHTML += `
                        <div class="bg-indigo-900/50 border border-indigo-500/50 text-indigo-200 px-4 py-2 rounded flex justify-between items-center text-sm">
                            <span>💡 ${rec.mensaje}</span>
                            <button onclick="prefillMerge(${rec.inundacionA_id}, ${rec.inundacionB_id})" class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1 rounded text-xs transition-colors">Ver Fusión</button>
                        </div>
                    `;
                });
            }
        }).catch(err => console.error(err));
    }

    function prefillMerge(idA, idB) {
        document.getElementById('merge_destino_id').value = idA;
        document.getElementById('merge_origen_id').value = idB;
        openMergeModal();
    }

    function selectFlood(inun) {
        selectedFloodId = inun.id;
        
        // Rerender map to apply selected styles
        const index = document.getElementById('timeline-slider').value;
        renderMapAtDate(timeOrderedEvents[index].date);

        const panel = document.getElementById('detail-panel');
        
        // Active reports count based on timeline
        const indexDate = timeOrderedEvents[index].date;
        const activeReps = inun.reportes.filter(r => new Date(r.created_at) <= indexDate).length;
        const activeVics = inun.victimas ? inun.victimas.filter(v => new Date(v.created_at) <= indexDate).length : 0;
        const activeDans = inun.danos_materiales ? inun.danos_materiales.filter(d => new Date(d.created_at) <= indexDate).length : 0;

        panel.innerHTML = `
            <div class="bg-slate-700/50 p-4 rounded-lg border border-slate-600 mb-4 animate-fade-in">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="text-white font-bold text-lg">Inundación #${inun.id}</h4>
                    <span class="bg-blue-900 text-blue-200 text-[10px] uppercase px-2 py-1 rounded font-bold">${inun.intensidad_calculada || 'Indeterminado'}</span>
                </div>
                <p class="text-xs text-slate-300 mb-3 line-clamp-3">${inun.reportes[0] ? (inun.reportes[0].description || 'Evento en progreso...') : 'Sin reportes.'}</p>
                
                <div class="grid grid-cols-3 gap-2 mb-3 text-center">
                    <div class="bg-slate-800 rounded p-2"><span class="block text-sky-400 font-bold">${activeReps}</span><span class="text-[9px] text-slate-400 uppercase">Rep</span></div>
                    <div class="bg-slate-800 rounded p-2"><span class="block text-red-400 font-bold">${activeVics}</span><span class="text-[9px] text-slate-400 uppercase">Vic</span></div>
                    <div class="bg-slate-800 rounded p-2"><span class="block text-orange-400 font-bold">${activeDans}</span><span class="text-[9px] text-slate-400 uppercase">Daños</span></div>
                </div>

                <div class="flex gap-2 mb-4">
                    <a href="/victimas?inundacion_id=${inun.id}" target="_blank" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-[10px] uppercase font-bold py-2 rounded transition-colors text-center border border-slate-600 flex flex-col items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Ver Víctimas
                    </a>
                    <button type="button" onclick="showDamagesForFlood(${inun.id})" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-[10px] uppercase font-bold py-2 rounded transition-colors text-center border border-slate-600 flex flex-col items-center justify-center gap-1">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Ver Daños
                    </button>
                </div>

                @if(session('api_user') && (session('api_user')['role'] ?? '') === 'authority')
                <button onclick="openDamageModalForFlood(${inun.id})" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2 rounded shadow transition-colors flex justify-center items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Añadir Daño Material
                </button>
                @endif
            </div>
            <button onclick="clearSelection()" class="w-full text-xs text-slate-400 hover:text-white underline text-center">Limpiar Selección</button>
        `;
    }

    function clearSelection() {
        selectedFloodId = null;
        document.getElementById('detail-panel').innerHTML = '<p class="text-center mt-10">Haz clic en una inundación o reporte en el mapa para ver los detalles.</p>';
        const index = document.getElementById('timeline-slider').value;
        renderMapAtDate(timeOrderedEvents[index].date);
    }

    // Modal Logic & Flood Picker Drawer
    let fpExpandedId = null;
    let fpMiniMaps = {};
    let fpChipMapInstance = null;

    function openFloodDrawer() {
        document.getElementById('fp-drawer-backdrop').classList.remove('hidden');
        requestAnimationFrame(() => {
            document.getElementById('fp-drawer-backdrop').classList.remove('opacity-0');
            document.getElementById('fp-drawer').classList.remove('translate-x-full');
        });
        renderFloodList();
    }

    function closeFloodDrawer() {
        document.getElementById('fp-drawer-backdrop').classList.add('opacity-0');
        document.getElementById('fp-drawer').classList.add('translate-x-full');
        setTimeout(() => document.getElementById('fp-drawer-backdrop').classList.add('hidden'), 300);
    }

    function renderFloodList() {
        const q = document.getElementById('fp_search').value.toLowerCase();
        const prov = document.getElementById('fp_provincia').value;
        const mun = document.getElementById('fp_municipio').value;
        const df = document.getElementById('fp_date_from').value;
        const dt = document.getElementById('fp_date_to').value;
        const resultsEl = document.getElementById('fp_results');
        resultsEl.innerHTML = '';

        const filtered = allData.filter(f => {
            if (q && f.id.toString() !== q) return false;
            if (prov && f.provincia !== prov) return false;
            if (mun && f.municipio !== mun) return false;
            const fDate = f.created_at.split('T')[0];
            if (df && fDate < df) return false;
            if (dt && fDate > dt) return false;
            return true;
        });

        if (filtered.length === 0) {
            resultsEl.innerHTML = '<div class="text-center py-6"><p class="text-xs text-slate-500">No se encontraron resultados.</p></div>';
            return;
        }

        filtered.forEach(f => {
            const dateStr = new Date(f.created_at).toLocaleDateString('es-BO');
            const div = document.createElement('div');
            const isExp = fpExpandedId === f.id;
            
            div.className = `bg-slate-800 border ${isExp ? 'border-blue-500 bg-slate-700/50 shadow-md' : 'border-slate-700'} hover:border-slate-500 rounded-lg p-3 cursor-pointer transition-all overflow-hidden`;
            div.innerHTML = `
                <div class="flex justify-between items-start mb-1">
                    <div>
                        <span class="font-bold text-white text-sm">#${f.id}</span>
                        <span class="text-[10px] text-slate-400 ml-2">${dateStr}</span>
                    </div>
                    <span class="text-[9px] uppercase text-blue-200 font-bold bg-blue-900/50 px-2 py-0.5 rounded">${f.intensidad_calculada || 'Indeterminado'}</span>
                </div>
                <div class="text-[10px] text-slate-500 flex gap-2">
                    <span>${f.reportes.length} Rep</span>
                    <span>|</span>
                    <span>${f.danos_materiales.length} Daños</span>
                </div>
                
                <div id="fp_map_container_${f.id}" class="transition-all duration-300 origin-top overflow-hidden" style="height: ${isExp ? '140px' : '0'}; opacity: ${isExp ? '1' : '0'}; margin-top: ${isExp ? '0.75rem' : '0'}">
                    <div id="fp_map_${f.id}" class="w-full h-[100px] rounded border border-slate-600 bg-slate-900 z-0"></div>
                    <button type="button" onclick="event.stopPropagation(); selectFloodForDamage(${f.id})" class="w-full mt-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium py-1.5 rounded transition-colors shadow">
                        Confirmar Selección
                    </button>
                </div>
            `;
            div.onclick = () => {
                if (fpExpandedId === f.id) return; // Ya está abierto
                const prevExpanded = fpExpandedId;
                fpExpandedId = f.id;
                renderFloodList(); // Rerenderiza la lista para animar aperturas/cierres
            };
            resultsEl.appendChild(div);

            if (isExp && !fpMiniMaps[f.id]) {
                setTimeout(() => {
                    const mDiv = document.getElementById(`fp_map_${f.id}`);
                    if (mDiv) {
                        const m = L.map(mDiv, { zoomControl: false, attributionControl: false, scrollWheelZoom: true, dragging: true }).setView([parseFloat(f.centroide.lat), parseFloat(f.centroide.lng)], 13);
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(m);
                        
                        if (f.reportes && f.reportes.length > 0) {
                            window.createSmartHeatmap(m, f.reportes);
                        }
                        fpMiniMaps[f.id] = m;
                    }
                }, 50);
            }
        });
    }

    function selectFloodForDamage(id) {
        document.getElementById('dano_inundacion_id').value = id;
        document.getElementById('dano_fp_trigger').classList.add('hidden');
        document.getElementById('dano_fp_selected').classList.remove('hidden');
        
        const inun = allData.find(f => f.id === id);
        document.getElementById('dano_fp_label').innerText = `#${id} · ${new Date(inun.created_at).toLocaleDateString('es-BO')}`;
        
        closeFloodDrawer();

        // Render Chip Map
        if (fpChipMapInstance) { fpChipMapInstance.remove(); }
        setTimeout(() => {
            const div = document.getElementById('dano_fp_chip_map');
            fpChipMapInstance = L.map(div, { zoomControl: false, attributionControl: false, scrollWheelZoom: false, dragging: false }).setView([parseFloat(inun.centroide.lat), parseFloat(inun.centroide.lng)], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(fpChipMapInstance);
            
            if (inun.reportes && inun.reportes.length > 0) {
                window.createSmartHeatmap(fpChipMapInstance, inun.reportes);
            }
            updateMiniMapForSelected(inun);
        }, 300);
    }

    function updateMiniMapForSelected(inun) {
        if (inun && inun.centroide && miniMap) {
            const lat = parseFloat(inun.centroide.lat);
            const lng = parseFloat(inun.centroide.lng);
            miniMap.setView([lat, lng], 14);
            miniMarker.setLatLng([lat, lng]);
            document.getElementById('dano_lat').value = lat.toFixed(6);
            document.getElementById('dano_lng').value = lng.toFixed(6);
            document.getElementById('dano-coords-display').innerText = `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
        }
    }

    function openDamageModal() { 
        document.getElementById('modal-dano').classList.remove('hidden'); 
        initMiniMap();
        setTimeout(() => {
            if(miniMap) miniMap.invalidateSize();
        }, 200);
    }
    
    function openDamageModalForFlood(id) { 
        openDamageModal();
        setTimeout(() => selectFloodForDamage(id), 300);
    }
    function closeDamageModal() { document.getElementById('modal-dano').classList.add('hidden'); }
    function openMergeModal() { document.getElementById('modal-merge').classList.remove('hidden'); }
    function closeMergeModal() { document.getElementById('modal-merge').classList.add('hidden'); }

    function showDamagesForFlood(id) {
        const inun = allData.find(f => f.id === id);
        if (!inun) return;
        
        document.getElementById('lista-danos-subtitle').innerText = `Mostrando daños de la Inundación N°${id}`;
        const container = document.getElementById('lista-danos-container');
        
        if (!inun.danos_materiales || inun.danos_materiales.length === 0) {
            container.innerHTML = '<p class="text-center text-sm text-slate-500 py-6">No hay daños materiales registrados para esta inundación.</p>';
        } else {
            container.innerHTML = inun.danos_materiales.map(dano => {
                const dateStr = new Date(dano.created_at).toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' });
                return `
                    <div class="bg-slate-700/50 border border-slate-600 rounded-lg p-3 flex flex-col gap-2">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-bold text-white uppercase flex items-center gap-2">
                                <div style="background-color:#f97316; width:10px; height:10px; border:1px solid white; transform: rotate(45deg);"></div>
                                ${dano.tipo}
                            </span>
                            <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-600">${dateStr}</span>
                        </div>
                        <div class="flex gap-4 items-center">
                            <span class="text-xs text-slate-400">Estado: <span class="font-bold text-slate-200 capitalize">${dano.estado}</span></span>
                            <span class="text-xs text-slate-400">Coords: <span class="text-slate-300">${parseFloat(dano.lat).toFixed(4)}, ${parseFloat(dano.lng).toFixed(4)}</span></span>
                        </div>
                        ${dano.descripcion ? `<p class="text-xs text-slate-300 italic mt-1 bg-slate-800 p-2 rounded">"${dano.descripcion}"</p>` : ''}
                    </div>
                `;
            }).join('');
        }
        
        document.getElementById('modal-lista-danos').classList.remove('hidden');
    }

    function closeDamagesListModal() {
        document.getElementById('modal-lista-danos').classList.add('hidden');
    }

    function submitDano(e) {
        e.preventDefault();
        const payload = {
            inundacion_id: document.getElementById('dano_inundacion_id').value,
            tipo: document.getElementById('dano_tipo').value,
            latitud: document.getElementById('dano_lat').value,
            longitud: document.getElementById('dano_lng').value,
            estado: document.getElementById('dano_estado').value,
            descripcion: document.getElementById('dano_desc').value,
            _token: '{{ csrf_token() }}'
        };

        fetch('/command-center/danos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        }).then(res => res.json()).then(data => {
            if(data.dano) {
                alert('Daño registrado');
                closeDamageModal();
                fetchData(); // Reload data
            } else {
                alert('Error al registrar daño');
            }
        });
    }

    function submitMerge(e) {
        e.preventDefault();
        const payload = {
            inundacion_destino_id: document.getElementById('merge_destino_id').value,
            inundacion_origen_id: document.getElementById('merge_origen_id').value,
            _token: '{{ csrf_token() }}'
        };

        fetch('/command-center/merge', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        }).then(res => res.json()).then(data => {
            if(data.message) {
                alert(data.message);
                closeMergeModal();
                fetchData(); // Reload data
                fetchMergeRecommendations(); // Reload alerts
            } else {
                alert('Error al fusionar: ' + (data.error || 'Desconocido'));
            }
        });
    }
</script>
@endsection
