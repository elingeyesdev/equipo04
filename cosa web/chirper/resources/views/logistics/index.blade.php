@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Logística de Ayuda</h1>
                <p class="mt-1 text-sm text-gray-600">Registra y localiza puntos de donación y centros de acopio.</p>
            </div>
        </div>

        <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-800 mb-3">Buscar Centros por Ubicación</h3>
            <x-location-filter formAction="{{ route('logistica.index', [], false) }}" :showEstado="true"
                :showSearch="true" />
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($error ?? null)
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-600">
                {{ $error }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-600">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @if($isAdmin)
                <!-- Panel Izquierdo: Formulario -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 h-fit">
                    <div class="flex justify-between items-center mb-4">
                        <h2 id="form_title" class="text-lg font-semibold text-gray-800">Registrar Nuevo Centro</h2>
                        <button type="button" id="cancel_edit_btn"
                            class="hidden text-xs text-red-600 hover:underline font-medium">Cancelar Edición</button>
                    </div>

                    <form id="logistics_form" action="{{ route('logistica.store', [], false) }}" method="POST">
                        @csrf
                        <div id="method_field"></div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Centro *</label>
                            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div class="mb-4 bg-gray-50 p-3 rounded-md border border-gray-200">
                            <x-location-filter idPrefix="form" />
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Apertura *</label>
                                <input type="time" id="hora_apertura" name="hora_apertura"
                                    value="{{ old('hora_apertura', '08:00') }}" required
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hora de Cierre *</label>
                                <input type="time" id="hora_cierre" name="hora_cierre" value="{{ old('hora_cierre', '18:00') }}"
                                    required
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Referencial</label>
                            <input type="text" id="direccion" name="direccion" value="{{ old('direccion') }}"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Latitud *</label>
                                <input type="text" name="latitud" id="lat" value="{{ old('latitud') }}" readonly required
                                    class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm placeholder:text-gray-400"
                                    placeholder="Haz clic en el mapa">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Longitud *</label>
                                <input type="text" name="longitud" id="lng" value="{{ old('longitud') }}" readonly required
                                    class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm placeholder:text-gray-400"
                                    placeholder="Haz clic en el mapa">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de Contacto</label>
                            <input type="text" id="contacto" name="contacto" value="{{ old('contacto') }}"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Encargado Responsable</label>
                            <input type="text" id="encargado" name="encargado" value="{{ old('encargado') }}"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <button type="submit" id="submit_btn"
                            class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors">
                            Registrar Centro
                        </button>
                    </form>
                </div>
            @endif

            <!-- Panel Derecho: Mapa -->
            <div class="{{ $isAdmin ? 'lg:col-span-2' : 'lg:col-span-3' }}">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden relative"
                    style="height: 700px;">
                    <div id="logistics_map" class="absolute inset-0 z-0"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2 text-right">💡 Haz clic sobre el mapa para autocompletar las
                    coordenadas del formulario.</p>
            </div>
        </div>

        <!-- Panel Inferior: Tabla de Registros -->
        <div class="mt-10 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Directorio de Centros Registrados</h2>
                <span
                    class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                    Total: {{ count($centros) }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Horario</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contacto</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Dirección</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Encargado</th>
                            @if($isAdmin)
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($centros as $centro)
                            <tr id="tr-centro-{{ $centro['id_centro'] }}" class="hover:bg-gray-50 transition-colors center-row"
                                data-provincia="{{ $centro['provincia'] ?? '' }}"
                                data-municipio="{{ $centro['municipio'] ?? '' }}" data-nombre="{{ $centro['nombre'] ?? '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $centro['nombre'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs text-gray-600">{{ $centro['hora_apertura'] ?? '08:00' }} -
                                        {{ $centro['hora_cierre'] ?? '18:00' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs text-gray-600">{{ $centro['contacto'] ?? 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500 max-w-xs truncate"
                                        title="{{ $centro['direccion'] ?? 'N/A' }}">{{ $centro['direccion'] ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $centro['encargado'] ?? 'N/A' }}
                                </td>
                                @if($isAdmin)
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick='editCentro(@json($centro))'
                                            class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-2 rounded transition-colors mr-1 inline-flex items-center"
                                            title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('logistica.destroy', ['id' => $centro['id_centro']], false) }}"
                                            method="POST" class="inline-block" onsubmit="deleteCentroAjax(event, this.action)">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-2 rounded transition-colors inline-flex items-center"
                                                title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                @endif
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
        let markersLayer = null;

        function initLogisticsMap() {
            const defaultLocation = [-17.783325, -63.182111]; // Santa Cruz, Bolivia

            map = L.map('logistics_map').setView(defaultLocation, 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            markersLayer = L.layerGroup().addTo(map);

            renderMarkers(window.centros);

            // 2. Cargar geometrías

            function renderMarkers(centrosData) {
                markersLayer.clearLayers();
                centrosData.forEach(centro => {
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
                        if (currentMs >= apMs || currentMs <= ciMs) isOpen = true;
                    } else {
                        if (currentMs >= apMs && currentMs <= ciMs) isOpen = true;
                    }
                    centro.is_open = isOpen; // cache the status

                    // Actualizamos la fila de la tabla con el estado
                    const tr = document.getElementById(`tr-centro-${centro.id_centro}`);
                    if (tr) {
                        tr.dataset.estado = isOpen ? 'abierto' : 'cerrado';
                    }

                    let markerColor = isOpen ? "#34A853" : "#EA4335";
                    const customIcon = L.divIcon({
                        className: 'custom-leaflet-marker',
                        html: `<div style="background-color: ${markerColor}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    });

                    const statusBadge = isOpen ? `<span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded">ABIERTO AHORA</span>` : `<span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded">CERRADO</span>`;

                    const contentStr = `<div class="max-w-[200px] p-1">
                    <div class="flex items-center justify-between mb-2">
                       <h3 class="font-bold text-sm m-0">${centro.nombre}</h3>
                    </div>
                    <p class="text-xs text-gray-700 mb-1"><b>Estado:</b> ${statusBadge}</p>
                    <p class="text-xs text-gray-700 mb-1"><b>Horario:</b> ${horaAperturaStr} a ${horaCierreStr}</p>
                    ${centro.contacto ? `<p class="text-xs text-gray-700 mb-1"><b>Cel:</b> ${centro.contacto}</p>` : ''}
                    ${centro.direccion ? `<p class="text-xs text-gray-700 mb-1"><b>Dir:</b> ${centro.direccion}</p>` : ''}
                        @if($isAdmin)
                            <button onclick='editCentro(${JSON.stringify(centro).replace(/'/g, "&apos;")})' class="mt-2 w-full py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-semibold rounded border border-gray-300 transition-colors">✏️ Editar Centro</button>
                        @endif
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank" rel="noopener noreferrer" class="mt-2 block w-full text-center py-2 bg-blue-600 hover:bg-blue-700 !text-white !no-underline text-xs font-bold rounded shadow-sm transition-all">📍 Ver ruta en Maps</a>
                </div>`;

                    const marker = L.marker([lat, lng], { icon: customIcon })
                        .bindPopup(contentStr)
                        .on('click', function () {
                            map.flyTo([lat, lng], 15, { animate: true, duration: 1 });
                        });

                    markersLayer.addLayer(marker);
                });
            }
            // Exponer globalmente para que el handler AJAX (fuera de initLogisticsMap)
            // pueda llamarla después de crear/editar un centro sin recargar la página.
            // La declaración "function renderMarkers" sigue siendo hoisteada normalmente.
            window.renderMarkers = renderMarkers;

            // ─────────────────────────────────────────────────────────────
            // 2. Cargar geometrías GeoJSON
            // Estos dos archivos contienen los polígonos de los límites
            // geográficos del departamento de Santa Cruz. Se cargan de forma
            // asíncrona al iniciar el mapa y se usan para:
            //   a) Resaltar la provincia/municipio seleccionado en el filtro
            //   b) Detectar automáticamente en qué provincia/municipio
            //      cayó el clic del admin al registrar un nuevo centro
            // ─────────────────────────────────────────────────────────────
            let provincesData = null;
            let municipalitiesData = null;
            let highlightLayer = null; // Capa activa de resaltado (naranja=provincia, rojo=municipio)

            fetch('/provinces.geojson').then(res => res.json()).then(data => provincesData = data);
            fetch('/municipalities.geojson').then(res => res.json()).then(data => municipalitiesData = data);

            @if($isAdmin)
                let santaCruzPolygon = null;

                // Cargar frontera
                fetch('/santacruz_boundary.json')
                    .then(res => res.json())
                    .then(geoJson => {
                        santaCruzPolygon = geoJson;
                        L.geoJSON(santaCruzPolygon, {
                            style: { color: '#3B82F6', weight: 2, opacity: 0.6, fillOpacity: 0.05 },
                            interactive: false
                        }).addTo(map);
                    });
                // Auto-seleccionar y validar en click
                map.on('click', function (e) {
                    if (!santaCruzPolygon || typeof turf === 'undefined' || !provincesData || !municipalitiesData) {
                        alert("Cargando fronteras geográficas, por favor espera un momento.");
                        return;
                    }

                    const pt = turf.point([e.latlng.lng, e.latlng.lat]);

                    // Validar si está en Santa Cruz
                    if (!turf.booleanPointInPolygon(pt, santaCruzPolygon)) {
                        alert("¡Fuera de límite! Por favor seleccione una ubicación dentro del departamento de Santa Cruz.");
                        return;
                    }

                    document.getElementById('lat').value = e.latlng.lat.toFixed(7);
                    document.getElementById('lng').value = e.latlng.lng.toFixed(7);

                    if (mapMarker) {
                        mapMarker.setLatLng(e.latlng);
                    } else {
                        mapMarker = L.marker(e.latlng).addTo(map);
                    }

                    // ─── DETECCIÓN DE PROVINCIA ───────────────────────────────────
                    // Iteramos cada feature de provinces.geojson y verificamos si
                    // el punto clickeado (pt) está dentro del polígono (turf).
                    // IMPORTANTE: Los nombres en el GeoJSON son distintos a los nombres
                    // oficiales usados en el select. Ej: GeoJSON tiene "Provincia Warnes"
                    // pero el select tiene "Ignacio Warnes". Por eso usamos
                    // window.normalizeProvName() — definido en layouts/app.blade.php —
                    // para traducir antes de buscar la opción correcta.
                    for (let feature of provincesData.features) {
                        if (turf.booleanPointInPolygon(pt, feature)) {
                            // Traducir nombre del GeoJSON al nombre oficial
                            const geoNorm = window.normalizeProvName(feature.properties.name);
                            const provSelect = document.getElementById('form_provincia');
                            if (provSelect) {
                                for (let opt of provSelect.options) {
                                    if (opt.value && opt.value.toLowerCase() === geoNorm) {
                                        foundProv = opt.value; // guardamos el valor exacto del select
                                        break;
                                    }
                                }
                            }
                            if (!foundProv) foundProv = feature.properties.name; // fallback al nombre crudo
                            break;
                        }
                    }

                    // ─── DETECCIÓN DE MUNICIPIO ───────────────────────────────────
                    // Igual que el anterior, pero usando window.normalizeMuniName().
                    // Nota crítica: normalizeMuniName y normalizeProvName son DISTINTAS
                    // porque un mismo nombre puede significar cosas diferentes:
                    // Ej: "Warnes" en GeoJSON de provincias = "Ignacio Warnes" (provincia)
                    //     "Warnes" en GeoJSON de municipios = "Warnes" (municipio, igual)
                    // Si usáramos una sola función para ambos, el municipio "Warnes"
                    // se convertiría incorrectamente al nombre de la provincia.
                    for (let feature of municipalitiesData.features) {
                        if (turf.booleanPointInPolygon(pt, feature)) {
                            // Traducir nombre del GeoJSON al nombre oficial
                            const geoNorm = window.normalizeMuniName(feature.properties.name);
                            const munSelectTmp = document.getElementById('form_municipio');
                            // Guardamos normalizado; el setTimeout hará la comparación
                            foundMuni = geoNorm;
                            break;
                        }
                    }

                    // Actualizar select de Formulario
                    if (foundProv) {
                        const provSelect = document.getElementById('form_provincia');
                        if (provSelect) {
                            provSelect.value = foundProv;
                            provSelect.dispatchEvent(new Event('change'));

                            // Esperar a que carguen las opciones del municipio y seleccionar
                            if (foundMuni) {
                                setTimeout(() => {
                                    const munSelect = document.getElementById('form_municipio');
                                    if (munSelect) {
                                        for (let opt of munSelect.options) {
                                            if (opt.value && opt.value.toLowerCase() === foundMuni) {
                                                munSelect.value = opt.value;
                                                break;
                                            }
                                        }
                                        munSelect.dispatchEvent(new Event('change'));
                                    }
                                }, 150);
                            }
                        }
                    }
                });
            @endif

            // ─────────────────────────────────────────────────────────────
            // EVENTO CENTRAL DE FILTRADO: locationFilterChanged
            // ─────────────────────────────────────────────────────────────
            // Este evento es despachado por el componente location-filter.blade.php
            // cada vez que el usuario cambia un selector (provincia, municipio,
            // estado o nombre). No recarga la página (SPA).
            //
            // El evento lleva en e.detail:
            //   - idPrefix:  "filter" (filtro principal) o "form" (formulario de registro)
            //   - provincia: nombre oficial seleccionado en el select
            //   - municipio: nombre oficial seleccionado en el select
            //   - estado:    "abierto" | "cerrado" | "" (todos)
            //   - nombre:    texto libre de búsqueda
            //
            // Al recibirlo aquí hacemos DOS cosas:
            //   1) Filtrar los marcadores del mapa y las filas de la tabla
            //   2) Dibujar/actualizar la capa de resaltado geográfico
            //      (naranja para provincia, rojo para municipio)
            // ─────────────────────────────────────────────────────────────
            window.addEventListener('locationFilterChanged', function (e) {
                const { idPrefix, provincia, municipio, estado, nombre } = e.detail;
                let filteredCentros = window.centros;

                // Normalización para comparación: minúsculas y sin espacios extra
                const provF  = provincia  ? provincia.toLowerCase().trim()  : '';
                const muniF  = municipio  ? municipio.toLowerCase().trim()  : '';
                const nombreF = nombre    ? nombre.toLowerCase().trim()     : '';

                // Si el evento viene del filtro principal, aplicamos filtrado local a mapa y tabla
                if (idPrefix === 'filter') {
                    filteredCentros = window.centros.filter(c => {
                        // Comparación case-insensitive: el valor del dropdown y el de la BD pueden tener distinta capitalización
                        if (provF  && (c.provincia  || '').toLowerCase().trim() !== provF)  return false;
                        if (muniF  && (c.municipio  || '').toLowerCase().trim() !== muniF)  return false;
                        if (estado && estado === 'abierto' && c.is_open === false) return false;
                        if (estado && estado === 'cerrado' && c.is_open === true)  return false;
                        if (nombreF && !(c.nombre || '').toLowerCase().includes(nombreF))    return false;
                        return true;
                    });

                    renderMarkers(filteredCentros);

                    // Filtrar tabla con la misma lógica case-insensitive
                    document.querySelectorAll('.center-row').forEach(tr => {
                        const dProv = (tr.dataset.provincia || '').toLowerCase().trim();
                        const dMun  = (tr.dataset.municipio  || '').toLowerCase().trim();
                        const dEst  = tr.dataset.estado;
                        const dNom  = (tr.dataset.nombre || '').toLowerCase();
                        let show = true;
                        if (provF  && dProv !== provF)  show = false;
                        if (muniF  && dMun  !== muniF)  show = false;
                        if (estado && estado !== dEst)  show = false;
                        if (nombreF && !dNom.includes(nombreF)) show = false;
                        tr.style.display = show ? '' : 'none';
                    });
                }

                // ─── RESALTADO GEOGRÁFICO ─────────────────────────────────────
                // Limpiamos la capa anterior y buscamos el polígono que corresponde
                // al filtro activo. La búsqueda usa normalizeProvName / normalizeMuniName
                // (definidas en layouts/app.blade.php) para convertir los nombres crudos
                // del GeoJSON ("Provincia Ichilo", "Municipio Warnes") al formato oficial
                // limpio ("ichilo", "warnes") y compararlo con el valor del filtro.
                // Prioridad: municipio > provincia > reset de vista

                // Eliminar la capa de resaltado anterior antes de dibujar la nueva
                if (highlightLayer) {
                    map.removeLayer(highlightLayer);
                    highlightLayer = null;
                }

                if (municipio && municipalitiesData) {
                    // Buscar el polígono del municipio seleccionado (rojo)
                    const feature = municipalitiesData.features.find(f => window.normalizeMuniName(f.properties.name) === municipio.toLowerCase());
                    if (feature) {
                        highlightLayer = L.geoJSON(feature, {
                            style: { color: '#EF4444', weight: 3, opacity: 0.9, fillOpacity: 0.1 },
                            interactive: false
                        }).addTo(map);
                        map.fitBounds(highlightLayer.getBounds());
                    }
                } else if (provincia && provincesData) {
                    // Buscar el polígono de la provincia seleccionada (naranja)
                    const feature = provincesData.features.find(f => window.normalizeProvName(f.properties.name) === provincia.toLowerCase());
                    if (feature) {
                        highlightLayer = L.geoJSON(feature, {
                            style: { color: '#F97316', weight: 3, opacity: 0.9, fillOpacity: 0.1 },
                            interactive: false
                        }).addTo(map);
                        map.fitBounds(highlightLayer.getBounds());
                    }
                } else if (idPrefix === 'filter' && filteredCentros && filteredCentros.length > 0) {
                    // Si limpiaron todo, restablecer vista
                    map.setView([-17.783325, -63.182111], 12);
                }
            });
        }

        document.addEventListener("DOMContentLoaded", initLogisticsMap);

        // Lógica para cambiar dinámicamente entre Registrar / Editar
        window.editCentro = function (centro) {
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

        document.getElementById('cancel_edit_btn').addEventListener('click', function () {
            document.getElementById('form_title').innerText = "Registrar Nuevo Centro";
            document.getElementById('submit_btn').innerText = "Registrar Centro";
            document.getElementById('submit_btn').classList.replace("bg-indigo-600", "bg-blue-600");
            document.getElementById('submit_btn').classList.replace("hover:bg-indigo-500", "hover:bg-blue-500");

            document.getElementById('cancel_edit_btn').classList.add("hidden");

            document.getElementById('method_field').innerHTML = '';
            document.getElementById('logistics_form').action = `{{ route('logistica.store', [], false) }}`;
            document.getElementById('logistics_form').reset();
        });

        // --- FORMULARIO AJAX ---
        const form = document.getElementById('logistics_form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const submitBtn = document.getElementById('submit_btn');
                const originalText = submitBtn.innerText;
                submitBtn.innerText = "Procesando...";
                submitBtn.disabled = true;

                const formData = new FormData(this);
                const action = this.action;

                fetch(action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(async response => {
                        if (!response.ok) {
                            const data = await response.json();
                            let msg = "Error al guardar el centro.";
                            if (data.errors) {
                                msg = Object.values(data.errors).map(arr => Array.isArray(arr) ? arr.join(' ') : arr).join('\n');
                            } else if (data.error) {
                                msg = data.error;
                            }
                            throw new Error(msg);
                        }
                        return response.json();
                    })
                    .then(data => {
                        alert(data.message || "Guardado exitosamente.");

                        // --- Limpiar el formulario ---
                        document.getElementById('logistics_form').reset();
                        document.getElementById('method_field').innerHTML = '';
                        document.getElementById('logistics_form').action = `{{ route('logistica.store', [], false) }}`;
                        document.getElementById('form_title').innerText = "Registrar Nuevo Centro";
                        submitBtn.innerText = "Registrar Centro";
                        submitBtn.classList.replace("bg-indigo-600", "bg-blue-600");
                        submitBtn.classList.replace("hover:bg-indigo-500", "hover:bg-blue-500");
                        document.getElementById('cancel_edit_btn').classList.add("hidden");

                        // --- Eliminar marcador temporal del mapa ---
                        if (mapMarker) {
                            map.removeLayer(mapMarker);
                            mapMarker = null;
                        }

                        // --- Re-fetch SPA: actualizar mapa + tabla sin recargar la página ---
                        // Pedimos la página al servidor, extraemos el JSON de centros del HTML
                        // y llamamos renderMarkers() para refrescar los pines del mapa.
                        fetch("{{ route('logistica.index', [], false) }}", {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                            .then(res => res.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');

                                // 1. Extraer el array window.centros del script del HTML recibido
                                const scripts = doc.querySelectorAll('script');
                                for (const s of scripts) {
                                    const match = s.textContent.match(/window\.centros\s*=\s*(\[[\s\S]*?\]);/);
                                    if (match) {
                                        try {
                                            window.centros = JSON.parse(match[1]);
                                            window.renderMarkers(window.centros); // Refrescar pines del mapa
                                        } catch(parseErr) {
                                            console.warn('No se pudo parsear window.centros:', parseErr);
                                        }
                                        break;
                                    }
                                }

                                // 2. Actualizar la tabla de registros
                                const newTable = doc.querySelector('.mt-10.bg-white.rounded-lg');
                                const oldTable = document.querySelector('.mt-10.bg-white.rounded-lg');
                                if (newTable && oldTable) {
                                    oldTable.innerHTML = newTable.innerHTML;
                                }
                            })
                            .catch(err => console.warn('Error al refrescar centros:', err))
                            .finally(() => { submitBtn.disabled = false; });
                    })
                    .catch(error => {
                        alert(error.message);
                        submitBtn.innerText = originalText;
                        submitBtn.disabled = false;
                    });
            });
        }

        // --- ELIMINAR AJAX ---
        function deleteCentroAjax(event, url) {
            event.preventDefault();
            if (!confirm('¿Estás seguro de eliminar este centro? Esta acción es irreversible.')) return;

            fetch(url, {
                method: 'POST',
                body: new URLSearchParams({
                    '_token': '{{ csrf_token() }}',
                    '_method': 'DELETE'
                }),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(async response => {
                    if (!response.ok) {
                        const data = await response.json();
                        throw new Error(data.error || "Error al eliminar");
                    }
                    return response.json();
                })
                .then(data => {
                    alert(data.message || "Eliminado correctamente");
                    event.target.closest('tr').remove();
                })
                .catch(error => alert(error.message));
        }
    </script>
    <!-- LEAFLET CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- TURF.JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

@endsection