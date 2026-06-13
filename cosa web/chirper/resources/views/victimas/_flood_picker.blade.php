{{--
    FLOOD PICKER — Drawer deslizante para seleccionar inundación
    ─────────────────────────────────────────────────────────────
    Uso: @include('victimas._flood_picker', [
        'inundaciones' => $inundaciones,   // array de arrays (mapeado en controller)
        'selectedId'   => old('inundacion_id', $victima->inundacion_id ?? null)
    ])

    El componente gestiona:
      - Input hidden: #inundacion_id (el que va en el form)
      - Botón trigger para abrir el drawer
      - Chip de confirmación con la selección actual
      - Drawer con búsqueda libre, filtro de estado y tarjetas con mini-mapa
--}}
@php
    $selectedId  = $selectedId ?? old('inundacion_id');
    $selectedInun = null;
    if ($selectedId) {
        $selectedInun = collect($inundaciones)->firstWhere('id', (int)$selectedId);
    }
@endphp

{{-- Leaflet (si no está ya cargado) --}}
@once
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="{{ asset('js/smart-heatmap.js') }}"></script>
@endonce

{{-- ─── Input hidden real (va en el form) ───────────────────────────────── --}}
<input type="hidden" name="inundacion_id" id="inundacion_id"
       value="{{ $selectedId ?? '' }}" required>
@error('inundacion_id')
    <p id="flood-picker-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
@enderror

{{-- ─── Chip de confirmación + Botón abrir ─────────────────────────────── --}}
<div id="fp-trigger-zone">
    {{-- Estado: ninguna seleccionada --}}
    <div id="fp-empty-state" class="{{ $selectedId ? 'hidden' : '' }}">
        <button type="button" id="fp-open-btn"
                class="w-full flex items-center gap-3 rounded-xl border-2 border-dashed border-blue-300 bg-blue-50 hover:bg-blue-100 hover:border-blue-400 px-5 py-4 text-left transition-all group">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-100 group-hover:bg-blue-200 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-blue-700">Buscar inundación asociada</p>
                <p class="text-xs text-blue-500 mt-0.5">Filtra por municipio, provincia, fecha o estado</p>
            </div>
            <svg class="ml-auto w-5 h-5 text-blue-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    {{-- Estado: inundación seleccionada --}}
    <div id="fp-selected-chip" class="{{ $selectedId ? '' : 'hidden' }} rounded-xl border border-blue-200 bg-blue-50 overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-blue-500 font-medium">Inundación seleccionada</p>
                <p id="fp-chip-label" class="text-sm font-semibold text-blue-800 truncate">
                    @if($selectedInun)
                        N°{{ $selectedInun['id'] }} · {{ $selectedInun['municipio'] }} · {{ $selectedInun['created_at'] }}
                    @endif
                </p>
            </div>
            <button type="button" id="fp-change-btn"
                    class="flex-shrink-0 flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium px-2 py-1 rounded hover:bg-blue-100 transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Cambiar
            </button>
        </div>
        {{-- Mini-mapa embebido en el chip --}}
        <div id="fp-chip-map" class="h-36 w-full border-t border-blue-200 relative z-0"></div>
    </div>
</div>

{{-- ─── DRAWER ────────────────────────────────────────────────────────────── --}}
{{-- Backdrop --}}
<div id="fp-backdrop"
     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[1000] hidden transition-opacity duration-200 opacity-0"></div>

{{-- Panel --}}
<div id="fp-drawer"
     class="fixed inset-y-0 right-0 w-full max-w-lg bg-white shadow-2xl z-[1001] flex flex-col
            translate-x-full transition-transform duration-300 ease-in-out">

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 bg-gray-50 flex-shrink-0">
        <div>
            <h3 class="text-base font-bold text-gray-900">Seleccionar Inundación</h3>
            <p class="text-xs text-gray-500 mt-0.5">
                <span id="fp-count-label">{{ count($inundaciones) }}</span> evento(s) disponibles
            </p>
        </div>
        <button type="button" id="fp-close-btn"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Search + filters --}}
    <div class="px-4 py-3 border-b border-gray-100 flex-shrink-0 space-y-2">
        {{-- Search bar --}}
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" id="fp-search"
                   placeholder="Buscar por ID, municipio o provincia..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
        </div>

        {{-- Status chips + date range --}}
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-gray-500 font-medium">Estado:</span>
            <button type="button" data-filter="all"
                    class="fp-filter-btn active text-xs px-3 py-1 rounded-full border font-medium transition-colors
                           bg-gray-900 text-white border-gray-900">
                Todas
            </button>
            <button type="button" data-filter="activa"
                    class="fp-filter-btn text-xs px-3 py-1 rounded-full border font-medium transition-colors
                           border-gray-300 text-gray-600 hover:border-blue-400 hover:text-blue-700">
                Activas
            </button>
            <button type="button" data-filter="terminada"
                    class="fp-filter-btn text-xs px-3 py-1 rounded-full border font-medium transition-colors
                           border-gray-300 text-gray-600 hover:border-gray-500 hover:text-gray-800">
                Terminadas
            </button>

            <span class="text-xs text-gray-400 mx-1">|</span>

            <input type="date" id="fp-date-from"
                   class="text-xs border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Desde" title="Desde">
            <span class="text-xs text-gray-400">—</span>
            <input type="date" id="fp-date-to"
                   class="text-xs border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Hasta" title="Hasta">
        </div>
    </div>

    {{-- Results list --}}
    <div id="fp-results" class="flex-1 overflow-y-auto no-scrollbar divide-y divide-gray-100">
        {{-- Rendered by JS --}}
    </div>

    {{-- Empty state --}}
    <div id="fp-no-results" class="hidden flex-1 flex flex-col items-center justify-center text-gray-400 py-10">
        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm font-medium">Sin resultados</p>
        <p class="text-xs mt-1">Prueba con otros filtros</p>
    </div>
</div>

{{-- ─── JS ─────────────────────────────────────────────────────────────── --}}
<script>
(function () {
    'use strict';

    // ── Datos ────────────────────────────────────────────────────────────
    const FLOODS = @json($inundaciones);
    const SELECTED_ID = {{ $selectedId ? (int)$selectedId : 'null' }};

    // ── Elementos ────────────────────────────────────────────────────────
    const hiddenInput    = document.getElementById('inundacion_id');
    const drawer         = document.getElementById('fp-drawer');
    const backdrop       = document.getElementById('fp-backdrop');
    const openBtn        = document.getElementById('fp-open-btn');
    const changeBtn      = document.getElementById('fp-change-btn');
    const closeBtn       = document.getElementById('fp-close-btn');
    const searchInput    = document.getElementById('fp-search');
    const resultsEl      = document.getElementById('fp-results');
    const noResultsEl    = document.getElementById('fp-no-results');
    const countLabel     = document.getElementById('fp-count-label');
    const emptyState     = document.getElementById('fp-empty-state');
    const selectedChip   = document.getElementById('fp-selected-chip');
    const chipLabel      = document.getElementById('fp-chip-label');
    const chipMap        = document.getElementById('fp-chip-map');
    const filterBtns     = document.querySelectorAll('.fp-filter-btn');
    const dateFrom       = document.getElementById('fp-date-from');
    const dateTo         = document.getElementById('fp-date-to');

    let currentFilter    = 'all';
    let expandedId       = null;
    let miniMaps         = {};       // { floodId: leafletMap }
    let chipLeafletMap   = null;

    // ── Utilidades ───────────────────────────────────────────────────────
    function statusBadge(estado) {
        if (estado === 'activa')    return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-blue-100 text-blue-700">Activa</span>';
        if (estado === 'terminada') return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-gray-200 text-gray-600">Terminada</span>';
        return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-red-100 text-red-600">Falsa</span>';
    }

    function renderCard(f, isSelected) {
        const sel = isSelected ? 'border-blue-500 bg-blue-50' : 'border-transparent hover:bg-gray-50';
        return `
        <div class="fp-card px-4 py-3 cursor-pointer border-l-4 transition-all ${sel}"
             data-id="${f.id}" data-lat="${f.latitud}" data-lng="${f.longitud}">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-sm font-bold text-gray-800">#${f.id}</span>
              ${statusBadge(f.estado)}
            </div>
            <span class="text-xs text-gray-400 flex-shrink-0">${f.created_at}</span>
          </div>
          <div class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-600">
            <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            </svg>
            <span class="font-medium text-gray-700">${f.municipio}</span>
            <span class="text-gray-400">/</span>
            <span>${f.provincia}</span>
          </div>
          {{-- Mini-mapa inline (se muestra al expandir) --}}
          <div id="fp-map-${f.id}"
               class="fp-inline-map mt-2 rounded-lg overflow-hidden border border-blue-200 relative z-0 transition-all duration-200"
               style="height: 0; opacity: 0;"></div>
        </div>`;
    }

    // ── Filtrado ─────────────────────────────────────────────────────────
    function getFiltered() {
        const q    = searchInput.value.trim().toLowerCase();
        const from = dateFrom.value;
        const to   = dateTo.value;

        return FLOODS.filter(f => {
            if (currentFilter !== 'all' && f.estado !== currentFilter) return false;
            if (from && f.created_at_iso < from) return false;
            if (to   && f.created_at_iso > to)   return false;
            if (q) {
                const hay = `#${f.id} ${f.municipio} ${f.provincia}`.toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    }

    function renderResults() {
        const list    = getFiltered();
        const selId   = hiddenInput.value ? parseInt(hiddenInput.value) : null;
        countLabel.textContent = list.length;

        // Limpiar los mapas antes de re-renderizar para evitar referencias rotas al DOM (Error del minimapa congelado)
        for (let key in miniMaps) {
            if (miniMaps[key]) {
                miniMaps[key].remove();
            }
        }
        miniMaps = {};
        expandedId = null;

        if (list.length === 0) {
            resultsEl.innerHTML = '';
            resultsEl.classList.add('hidden');
            noResultsEl.classList.remove('hidden');
            return;
        }

        noResultsEl.classList.add('hidden');
        resultsEl.classList.remove('hidden');
        resultsEl.innerHTML = list.map(f => renderCard(f, f.id === selId)).join('');

        // Bind click handlers
        resultsEl.querySelectorAll('.fp-card').forEach(card => {
            card.addEventListener('click', () => onCardClick(card));
        });
    }

    // ── Mapa inline dentro del card ──────────────────────────────────────
    function openInlineMap(card) {
        const id  = parseInt(card.dataset.id);
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        const mapDiv = document.getElementById(`fp-map-${id}`);
        if (!mapDiv) return;

        // Animar apertura
        mapDiv.style.height = '140px';
        mapDiv.style.opacity = '1';

        if (!miniMaps[id] && lat && lng) {
            setTimeout(() => {
                const m = L.map(mapDiv, {
                    zoomControl: false,
                    attributionControl: false,
                    scrollWheelZoom: false,
                    dragging: false,
                }).setView([lat, lng], 13);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 20 }).addTo(m);
                const flood = FLOODS.find(f => f.id === id);
                let heatData = (flood && flood.reportes && flood.reportes.length > 0) 
                    ? flood.reportes 
                    : [{ lat: lat, lng: lng, intensidad: 'media' }];
                
                window.createSmartHeatmap(m, heatData, {
                    heatOptions: { radius: 25, blur: 15 } // Ajustado para minimapa
                });
                
                miniMaps[id] = m;
                // Forzar redibujo tras la transición CSS (duration-200)
                setTimeout(() => m.invalidateSize(), 250);
            }, 50);
        } else if (miniMaps[id]) {
            setTimeout(() => miniMaps[id].invalidateSize(), 250);
        }
    }

    function closeInlineMap(card) {
        const id  = parseInt(card.dataset.id);
        const mapDiv = document.getElementById(`fp-map-${id}`);
        if (!mapDiv) return;
        mapDiv.style.height = '0';
        mapDiv.style.opacity = '0';
    }

    function onCardClick(card) {
        const id  = parseInt(card.dataset.id);

        if (expandedId && expandedId !== id) {
            // Cerrar el anterior
            const prev = resultsEl.querySelector(`.fp-card[data-id="${expandedId}"]`);
            if (prev) closeInlineMap(prev);
        }

        if (expandedId === id) {
            // Segunda click = seleccionar
            selectFlood(card);
        } else {
            // Primer click = expandir mapa
            expandedId = id;
            openInlineMap(card);
        }
    }

    // ── Selección ────────────────────────────────────────────────────────
    function selectFlood(card) {
        const id  = parseInt(card.dataset.id);
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);

        const flood = FLOODS.find(f => f.id === id);
        if (!flood) return;

        hiddenInput.value = id;
        chipLabel.textContent = `#${flood.id} · ${flood.municipio} · ${flood.created_at}`;

        emptyState.classList.add('hidden');
        selectedChip.classList.remove('hidden');

        // Mini-mapa en el chip
        chipMap.innerHTML = '';
        if (chipLeafletMap) { chipLeafletMap.remove(); chipLeafletMap = null; }

        setTimeout(() => {
            chipLeafletMap = L.map(chipMap, {
                zoomControl: false, attributionControl: false,
                scrollWheelZoom: false,
            }).setView([lat, lng], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 20 }).addTo(chipLeafletMap);
            
            let heatData = (flood && flood.reportes && flood.reportes.length > 0) 
                ? flood.reportes 
                : [{ lat: lat, lng: lng, intensidad: 'media' }];
            
            window.createSmartHeatmap(chipLeafletMap, heatData, {
                heatOptions: { radius: 25, blur: 15 }
            });
        }, 100);

        closeDrawer();
    }

    // ── Drawer open/close ─────────────────────────────────────────────────
    function openDrawer() {
        backdrop.classList.remove('hidden');
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            drawer.classList.remove('translate-x-full');
        });
        renderResults();
    }

    function closeDrawer() {
        backdrop.classList.add('opacity-0');
        drawer.classList.add('translate-x-full');
        setTimeout(() => {
            backdrop.classList.add('hidden');
            expandedId = null;
        }, 300);
    }

    // ── Event listeners ───────────────────────────────────────────────────
    openBtn.addEventListener('click', openDrawer);
    changeBtn.addEventListener('click', openDrawer);
    closeBtn.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);

    searchInput.addEventListener('input', () => { expandedId = null; renderResults(); });
    dateFrom.addEventListener('change', renderResults);
    dateTo.addEventListener('change', renderResults);

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            currentFilter = btn.dataset.filter;
            filterBtns.forEach(b => {
                const isActive = b === btn;
                b.classList.toggle('bg-gray-900', isActive);
                b.classList.toggle('text-white', isActive);
                b.classList.toggle('border-gray-900', isActive);
                b.classList.toggle('active', isActive);
                b.classList.toggle('border-gray-300', !isActive);
                b.classList.toggle('text-gray-600', !isActive);
            });
            expandedId = null;
            renderResults();
        });
    });

    // ── Init: mostrar chip si hay valor preseleccionado ───────────────────
    if (SELECTED_ID) {
        const flood = FLOODS.find(f => f.id === SELECTED_ID);
        if (flood && flood.latitud && flood.longitud) {
            setTimeout(() => {
                chipMap.innerHTML = '';
                chipLeafletMap = L.map(chipMap, {
                    zoomControl: false, attributionControl: false,
                    scrollWheelZoom: false,
                }).setView([flood.latitud, flood.longitud], 13);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 20 }).addTo(chipLeafletMap);
                
                let heatData = (flood && flood.reportes && flood.reportes.length > 0) 
                    ? flood.reportes 
                    : [{ lat: flood.latitud, lng: flood.longitud, intensidad: 'media' }];
                
                window.createSmartHeatmap(chipLeafletMap, heatData, {
                    heatOptions: { radius: 25, blur: 15 }
                });
            }, 300);
        }
    }
})();
</script>
