@extends('layouts.app')

@section('content')
<!-- Leaflet CSS & JS for Maps -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="command-center-container bg-slate-900 text-slate-100 min-h-screen -mt-6 -mx-4 p-4 lg:p-6 flex flex-col font-sans">
    
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Actualizar Uso de Donación #{{ $donacion->id }}
            </h1>
            <p class="mt-1 text-sm text-slate-400">Entrega esta donación vinculándola a una inundación en el mapa interactivo.</p>
        </div>
        <a href="{{ route('donaciones.index') }}" class="text-sm font-medium text-slate-400 hover:text-white transition-colors bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg border border-slate-700">
            &larr; Volver
        </a>
    </div>

    <!-- Alert for validation errors -->
    @if ($errors->any())
    <div class="mb-6 bg-red-900/50 border border-red-500/50 text-red-200 px-4 py-3 rounded-xl flex flex-col gap-1 text-sm">
        <div class="font-bold flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Error al guardar
        </div>
        <ul class="list-disc list-inside ml-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6 flex-1">
        
        <!-- Panel Izquierdo: Info de Donación -->
        <div class="w-full lg:w-1/3 bg-slate-800 rounded-xl shadow-xl border border-slate-700 p-5 flex flex-col h-fit">
            <h2 class="text-lg font-semibold text-slate-200 mb-4 border-b border-slate-700 pb-2">Información Original</h2>
            
            <div class="space-y-4 text-sm text-slate-300">
                <div>
                    <span class="block text-xs text-slate-500 uppercase font-bold mb-1">Descripción</span>
                    <p class="text-white bg-slate-900/50 p-3 rounded-lg border border-slate-700/50">{{ $donacion->items_description }}</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="block text-xs text-slate-500 uppercase font-bold mb-1">Estado Previo</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider bg-slate-700 text-slate-300 border border-slate-600">
                            {{ str_replace('_', ' ', $donacion->status) }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs text-slate-500 uppercase font-bold mb-1">Fecha Registro</span>
                        <p class="text-slate-300">{{ $donacion->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <div>
                    <span class="block text-xs text-slate-500 uppercase font-bold mb-1">Centro de Acopio</span>
                    <p class="text-slate-300 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        {{ $donacion->centro->nombre ?? 'Desconocido' }}
                    </p>
                </div>
                
                <div>
                    <span class="block text-xs text-slate-500 uppercase font-bold mb-1">Donante</span>
                    <p class="text-slate-300 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        @if($donacion->is_anonymous)
                            <span class="italic text-slate-400">Anónimo</span>
                        @else
                            {{ $donacion->donor->name ?? 'Desconocido' }} ({{ $donacion->donor_carnet }})
                        @endif
                    </p>
                </div>

                @if($donacion->photo_path)
                <div>
                    <span class="block text-xs text-slate-500 uppercase font-bold mb-1">Evidencia Previa</span>
                    <img src="{{ asset('storage/' . $donacion->photo_path) }}" alt="Foto comprobante" class="w-full h-32 object-cover rounded-lg border border-slate-700 opacity-80 hover:opacity-100 transition-opacity">
                </div>
                @endif
            </div>
        </div>

        <!-- Panel Derecho: Formulario de Actualización -->
        <div class="w-full lg:w-2/3 bg-slate-800 rounded-xl shadow-xl border border-slate-700 p-6 flex flex-col">
            <h2 class="text-lg font-semibold text-slate-200 mb-6 border-b border-slate-700 pb-2">Destino de la Donación (Entregar)</h2>

            <form action="{{ route('donaciones.update', $donacion->id) }}" method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col">
                @csrf
                @method('PATCH')
                
                <!-- Siempre entregado -->
                <input type="hidden" name="status" value="entregado">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="space-y-5">
                        
                        <!-- Flood Picker (Inundación) -->
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Inundación Vinculada en el Mapa (Obligatoria)</label>
                            <input type="hidden" name="inundacion_id" id="update_inundacion_id" value="{{ old('inundacion_id', $donacion->inundacion_id) }}">
                            
                            <button type="button" onclick="openFloodDrawer()" class="w-full flex items-center justify-between bg-slate-900 hover:bg-slate-800 border border-slate-600 text-slate-300 rounded-lg p-3 transition-colors group shadow-inner">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center group-hover:bg-blue-900/50 transition-colors border border-slate-700 group-hover:border-blue-500/30 overflow-hidden relative">
                                        <div id="selected-flood-minimap" class="absolute inset-0 z-0"></div>
                                        <svg id="selected-flood-icon" class="w-5 h-5 text-blue-400 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <div class="text-left flex-1">
                                        <span id="update_inundacion_preview" class="block text-sm font-bold text-white truncate max-w-[200px]">
                                            @if($donacion->inundacion)
                                                Inundación #{{ $donacion->inundacion_id }}
                                            @else
                                                Seleccionar en el Mapa...
                                            @endif
                                        </span>
                                        <span class="block text-[10px] text-slate-400 mt-0.5">Abre el explorador geográfico</span>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                        
                        <!-- Víctima -->
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Víctima Específica (Opcional)</label>
                            <select name="victima_id" id="update_victima_id" class="w-full bg-slate-800 border border-slate-600 text-white rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500 shadow-inner">
                                <option value="">Ninguna víctima en específico...</option>
                                @foreach($victimas as $v)
                                    <option value="{{ $v->id }}" {{ old('victima_id', $donacion->victima_id) == $v->id ? 'selected' : '' }}>
                                        {{ $v->nombre_completo }} ({{ $v->carnet }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Detalles -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Detalles de la entrega</label>
                            <textarea name="usage_details" id="update-details" rows="3" placeholder="Explica detalladamente a quién o qué zona se entregó esta donación..." class="w-full bg-slate-900 border border-slate-600 text-white rounded-lg p-3 text-sm focus:ring-blue-500 focus:border-blue-500 shadow-inner resize-none custom-scrollbar">{{ old('usage_details', $donacion->usage_details) }}</textarea>
                        </div>
                    </div>

                    <div class="space-y-5">
                        
                        <!-- Mapa Principal Interactivo -->
                        <div class="bg-slate-900/50 p-3 rounded-xl border border-slate-700 h-full flex flex-col">
                            <label class="block text-xs font-medium text-slate-400 mb-2">Vista Satelital del Destino</label>
                            <div id="main-interactive-map" class="w-full flex-1 min-h-[200px] rounded-lg border border-slate-600 bg-slate-800 z-[1000]"></div>
                            <p class="text-[10px] text-slate-500 mt-2 text-center">El mapa se actualizará al seleccionar una inundación.</p>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Foto obligatoria -->
                <div class="mb-6 bg-slate-900/50 p-4 rounded-xl border border-slate-700">
                    <label class="block text-xs font-medium text-slate-300 mb-2">
                        Foto de Evidencia de Entrega 
                        @if($donacion->status !== 'entregado')
                            <span class="text-red-400 font-bold">(Obligatoria)</span>
                        @else
                            <span class="text-slate-500 font-normal">(Opcional si no desea cambiarla)</span>
                        @endif
                    </label>
                    <input type="file" name="photo" id="update_photo" accept="image/*" class="w-full text-sm text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 border border-slate-600 rounded-lg p-1.5 cursor-pointer bg-slate-800 shadow-inner transition-colors">
                </div>

                <div class="mt-auto pt-4 border-t border-slate-700 flex justify-end gap-3">
                    <a href="{{ route('donaciones.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700 hover:bg-slate-600 rounded-lg transition-colors">Cancelar</a>
                    <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow-lg hover:shadow-blue-500/25 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Registrar Entrega
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Drawer (Flood Picker Dark Mode con Mapas) -->
<div id="fp-drawer-backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[2500] hidden transition-opacity duration-300 opacity-0" onclick="closeFloodDrawer()"></div>
<div id="fp-drawer" class="fixed inset-y-0 right-0 w-full max-w-sm bg-slate-800 border-l border-slate-700 shadow-[0_0_40px_rgba(0,0,0,0.5)] z-[2501] flex flex-col translate-x-full transition-transform duration-300 ease-in-out font-sans">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-700 bg-slate-900/50">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            Mapa de Inundaciones
        </h3>
        <button type="button" onclick="closeFloodDrawer()" class="text-slate-400 hover:text-white transition-colors bg-slate-800 hover:bg-slate-700 p-1.5 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
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

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
    .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #64748b; }
</style>

<script>
    // Map Icons
    const redIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<svg class="w-6 h-6 text-red-500 drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>`,
        iconSize: [24, 24],
        iconAnchor: [12, 24]
    });

    const blueIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<svg class="w-8 h-8 text-blue-500 drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>`,
        iconSize: [32, 32],
        iconAnchor: [16, 32]
    });

    // Flood Data
    const activasData = @json($inundacionesActivas);
    const terminadasData = @json($inundacionesTerminadas);
    const allData = [...activasData, ...terminadasData];
    
    let fpExpandedId = null;
    let leafMaps = [];
    let mainMap = null;
    let allMarkers = {};
    let thumbMap = null;

    document.addEventListener('DOMContentLoaded', () => {
        populateLocationDropdowns();
        
        // Setup main map
        mainMap = L.map('main-interactive-map').setView([-17.7833, -63.1821], 6);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB', maxZoom: 19
        }).addTo(mainMap);
        
        renderAllMapMarkers();

        // Initial setup for the selected flood name if applicable
        const currentFloodId = document.getElementById('update_inundacion_id').value;
        if(currentFloodId) {
            const flood = allData.find(f => f.id == currentFloodId);
            if(flood) {
                const name = flood.municipio ? `Inundación en ${flood.municipio.nombre}` : `Inundación #${flood.id}`;
                document.getElementById('update_inundacion_preview').innerText = name;
                document.getElementById('selected-flood-icon').style.display = 'none';
                
                showMainMapLocation(flood.id, parseFloat(flood.latitud), parseFloat(flood.longitud));
                setupThumbMap(parseFloat(flood.latitud), parseFloat(flood.longitud));
            }
        }
    });

    function renderAllMapMarkers() {
        allData.forEach(f => {
            const lat = parseFloat(f.latitud);
            const lon = parseFloat(f.longitud);
            if (!isNaN(lat) && !isNaN(lon)) {
                const marker = L.marker([lat, lon], {icon: redIcon}).addTo(mainMap);
                marker.on('click', () => {
                    confirmSelection(f.id, f.municipio?.nombre || 'Desconocida', lat, lon);
                });
                allMarkers[f.id] = marker;
            }
        });
    }

    function showMainMapLocation(id, lat, lon) {
        if (!isNaN(lat) && !isNaN(lon)) {
            // Reset all markers to red
            Object.values(allMarkers).forEach(m => m.setIcon(redIcon));
            
            // Set selected marker to blue
            if(allMarkers[id]) {
                allMarkers[id].setIcon(blueIcon);
            }
            
            mainMap.setView([lat, lon], 14);
            setTimeout(() => mainMap.invalidateSize(true), 100);
        }
    }

    function setupThumbMap(lat, lon) {
        if (!isNaN(lat) && !isNaN(lon)) {
            if(thumbMap) {
                thumbMap.remove();
            }
            thumbMap = L.map('selected-flood-minimap', {
                zoomControl: false, attributionControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false, boxZoom: false, keyboard: false
            }).setView([lat, lon], 12);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(thumbMap);
            L.marker([lat, lon], {icon: redIcon}).addTo(thumbMap);
        }
    }

    function initMiniMaps() {
        // Destroy old maps in drawer
        leafMaps.forEach(item => {
            if(item.map) item.map.remove();
        });
        leafMaps = [];

        document.querySelectorAll('.mini-map-container').forEach(el => {
            const lat = parseFloat(el.dataset.lat);
            const lon = parseFloat(el.dataset.lon);
            const mapId = el.id;
            
            if (!isNaN(lat) && !isNaN(lon)) {
                const map = L.map(mapId, {
                    zoomControl: false, attributionControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false, boxZoom: false, keyboard: false
                }).setView([lat, lon], 12);
                
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(map);
                L.marker([lat, lon], {icon: redIcon}).addTo(map);
                
                leafMaps.push({ map: map, lat: lat, lon: lon });
            }
        });
        
        setTimeout(() => {
            leafMaps.forEach(item => {
                item.map.invalidateSize(true);
            });
        }, 100);
    }

    function populateLocationDropdowns() {
        const provs = [...new Set(allData.map(d => d.municipio?.provincia).filter(Boolean))].sort();
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
            filteredData = allData.filter(d => d.municipio?.provincia === selProv);
        }
        const muns = [...new Set(filteredData.map(d => d.municipio?.nombre).filter(Boolean))].sort();
        muns.forEach(m => {
            munSelect.innerHTML += `<option value="${m}">${m}</option>`;
        });
    }

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
            if (prov && f.municipio?.provincia !== prov) return false;
            if (mun && f.municipio?.nombre !== mun) return false;
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
            
            div.className = `bg-slate-800 border ${isExp ? 'border-blue-500 bg-slate-700/50 shadow-md' : 'border-slate-700'} hover:border-slate-500 rounded-lg p-3 cursor-pointer transition-all overflow-hidden flex items-start`;
            
            // Thumbnail container
            const thumbHtml = `<div id="drawer-map-${f.id}" data-lat="${f.latitud}" data-lon="${f.longitud}" class="mini-map-container w-14 h-14 shrink-0 bg-slate-900 rounded-lg border border-slate-600 mr-3 pointer-events-none"></div>`;
            
            const infoHtml = `
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start mb-1">
                        <div class="truncate pr-2">
                            <span class="font-bold text-white text-sm">#${f.id}</span>
                            <span class="text-xs text-slate-400 ml-1 truncate">· ${f.municipio?.nombre || 'Desconocido'}</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <div class="text-[11px] text-slate-400">Estado: <span class="${f.estado === 'activa' ? 'text-emerald-400' : 'text-slate-500'} font-medium uppercase">${f.estado}</span></div>
                        <span class="text-[10px] text-slate-500">${dateStr}</span>
                    </div>
                    
                    ${isExp ? `
                        <div class="mt-3 pt-3 border-t border-slate-600">
                            <button type="button" onclick="confirmSelection(${f.id}, '${f.municipio?.nombre || 'Desconocida'}', ${f.latitud}, ${f.longitud}); event.stopPropagation();" class="w-full bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold py-2 rounded transition-colors text-center shadow-lg">
                                Vincular a esta Inundación
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
            
            div.innerHTML = thumbHtml + infoHtml;
            
            div.onclick = () => {
                fpExpandedId = isExp ? null : f.id;
                renderFloodList();
            };
            resultsEl.appendChild(div);
        });
        
        // Initialize the mini maps that were just injected
        initMiniMaps();
    }

    function confirmSelection(id, municipioName, lat, lon) {
        document.getElementById('update_inundacion_id').value = id;
        document.getElementById('update_inundacion_preview').innerText = `Inundación en ${municipioName}`;
        document.getElementById('selected-flood-icon').style.display = 'none';
        
        showMainMapLocation(id, lat, lon);
        setupThumbMap(lat, lon);
        
        closeFloodDrawer();
    }
</script>
@endsection
