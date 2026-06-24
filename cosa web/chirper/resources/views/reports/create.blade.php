@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Crear Reporte Detallado</h1>

        <form id="detailedReportForm" class="space-y-6" enctype="multipart/form-data">
            @csrf

            <!-- Ubicación GPS y Mapa -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Tu Ubicación (GPS)</label>
                <div class="mt-1 flex items-center justify-between">
                    <span id="gpsStatus" class="text-sm text-yellow-600">Obteniendo ubicación...</span>
                    <button type="button" id="btnGetLocation" class="text-sm text-blue-600 hover:text-blue-800">Actualizar GPS</button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ubicación del Reporte (Máx. 500m del GPS)</label>
            <div id="map" class="h-64 bg-gray-100 rounded-lg border border-gray-300 relative z-0"></div>
                <p id="distanceWarning" class="mt-2 text-sm text-red-600 hidden">El marcador está demasiado lejos de tu ubicación real (máximo 500m).</p>
            </div>

            <!-- Selección de Región/Provincia/Municipio -->
            <div class="bg-white p-4 rounded-md border border-gray-200 shadow-sm hidden">
                <p class="text-sm font-medium text-gray-700 mb-3">Región y Municipio (Autocompletado por GPS)</p>
                <x-location-filter idPrefix="form" :requireFields="false" />
            </div>

            <!-- Address -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700">Dirección (opcional)</label>
                <input id="address" name="address" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-lg py-3 px-4">
            </div>

            <!-- Intensidad -->
            <div>
                <label for="intensidad" class="block text-sm font-medium text-gray-700">Intensidad Propuesta</label>
                <select id="intensidad" name="intensidad_propuesta" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-lg py-3 px-4">
                    <option value="baja" selected>Baja</option>
                    <option value="media">Media</option>
                    <option value="alta">Alta</option>
                </select>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Descripción (obligatorio)</label>
                <textarea id="description" name="description" rows="4" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
            </div>

            <!-- Foto -->
            <div>
                <label for="foto" class="block text-sm font-medium text-gray-700">Foto de Evidencia (opcional)</label>
                <input id="foto" name="foto" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500
                file:mr-4 file:py-2 file:px-4
                file:rounded-md file:border-0
                file:text-sm file:font-semibold
                file:bg-blue-50 file:text-blue-700
                hover:file:bg-blue-100">

                <!-- Vista previa de imagen -->
                <div id="foto-preview-wrapper" class="hidden mt-3">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs text-gray-500">Vista previa <span class="text-blue-500">(clic para ampliar)</span>:</p>
                        <button type="button" id="btn-remove-foto"
                            class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1 transition-colors font-medium">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            Quitar imagen
                        </button>
                    </div>
                    <img id="foto-preview-img"
                        src="" alt="Vista previa"
                        class="clickable-image max-h-40 rounded-lg border border-gray-200 shadow-sm cursor-zoom-in object-cover transition-transform hover:scale-105">
                </div>
            </div>

            <button type="submit" id="btnSubmit" disabled class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none disabled:opacity-50">
                Enviar Reporte
            </button>
        </form>

        <div id="successMessage" class="mt-4 p-4 bg-green-100 text-green-800 rounded-md hidden">
            Reporte creado exitosamente.
        </div>
        <div id="errorMessage" class="mt-4 p-4 bg-red-100 text-red-800 rounded-md hidden"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let map, marker, circle;
    let gpsLat, gpsLng;
    let reportLat, reportLng;
    
    const citizenCarnet = "{{ session('api_user')['carnet'] ?? '' }}";

    let santaCruzPolygon = null;
    let provincesData = null;
    let municipalitiesData = null;

    fetch('/santacruz_boundary.json').then(res => res.json()).then(geoJson => santaCruzPolygon = geoJson);
    fetch('/provinces.geojson').then(res => res.json()).then(data => provincesData = data);
    fetch('/municipalities.geojson').then(res => res.json()).then(data => municipalitiesData = data);



    function getDistanceFromLatLonInM(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = deg2rad(lat2-lat1);
        const dLon = deg2rad(lon2-lon1); 
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2); 
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
        return R * c; 
    }
    function deg2rad(deg) { return deg * (Math.PI/180); }

    window.normalizeProvName = function(name) {
        if (!name) return name;
        return name.replace(/^Provincia\s+/i, '').trim();
    };

    window.normalizeMuniName = function(name) {
        if (!name) return name;
        return name.replace(/^Municipio\s+(de\s+)?/i, '').trim();
    };

    function initMap(lat, lng) {
        if (!map) {
            map = L.map('map').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            circle = L.circle([lat, lng], {
                color: 'blue',
                fillColor: '#3b82f6',
                fillOpacity: 0.1,
                radius: 500
            }).addTo(map);

            marker = L.marker([lat, lng], {draggable: true}).addTo(map);
            
            marker.on('drag', function(e) {
                const pos = marker.getLatLng();
                const dist = getDistanceFromLatLonInM(gpsLat, gpsLng, pos.lat, pos.lng);
                
                const btn = document.getElementById('btnSubmit');
                const warn = document.getElementById('distanceWarning');
                
                if (dist > 500) {
                    btn.disabled = true;
                    warn.classList.remove('hidden');
                } else {
                    btn.disabled = false;
                    warn.classList.add('hidden');
                    reportLat = pos.lat;
                    reportLng = pos.lng;
                    updateLocationText(reportLat, reportLng);
                }
            });
        } else {
            map.setView([lat, lng], 16);
            circle.setLatLng([gpsLat, gpsLng]);
            marker.setLatLng([reportLat, reportLng]);
        }
    }

    function updateLocationText(lat, lng) {
        if (!santaCruzPolygon || !provincesData || !municipalitiesData || typeof turf === 'undefined') {
            setTimeout(() => updateLocationText(lat, lng), 500);
            return;
        }

        const pt = turf.point([lng, lat]);
        if (!turf.booleanPointInPolygon(pt, santaCruzPolygon)) {
            return;
        }

        let foundProv = null;
        let foundMuni = null;

        for (let feature of provincesData.features) {
            if (turf.booleanPointInPolygon(pt, feature)) {
                foundProv = window.normalizeProvName ? window.normalizeProvName(feature.properties.name) : feature.properties.name;
                break;
            }
        }
        for (let feature of municipalitiesData.features) {
            if (turf.booleanPointInPolygon(pt, feature)) {
                foundMuni = window.normalizeMuniName ? window.normalizeMuniName(feature.properties.name) : feature.properties.name;
                break;
            }
        }

            if (foundProv) {
                const provSelect = document.getElementById('form_provincia');
                if (provSelect) {
                    if (provSelect.options.length <= 1) {
                        setTimeout(() => updateLocationText(lat, lng), 500);
                        return;
                    }
                    const norm = str => str ? str.normalize('NFD').replace(/[\u0300-\u036f]/g, "").toLowerCase() : '';
                    const foundProvNorm = norm(foundProv);
                    
                    for (let opt of provSelect.options) {
                        if (opt.value && norm(opt.value) === foundProvNorm) {
                            provSelect.value = opt.value;
                            break;
                        }
                    }
                    provSelect.dispatchEvent(new Event('change'));
                    
                    if (foundMuni) {
                        setTimeout(() => {
                            const munSelect = document.getElementById('form_municipio');
                            if (munSelect) {
                                const foundMuniNorm = norm(foundMuni);
                                for (let opt of munSelect.options) {
                                    if (opt.value && norm(opt.value) === foundMuniNorm) {
                                        munSelect.value = opt.value;
                                        break;
                                    }
                                }
                                munSelect.dispatchEvent(new Event('change'));
                            }
                        }, 100);
                    }
            }
        }
    }

    function getLocation() {
        const status = document.getElementById('gpsStatus');
        status.textContent = 'Obteniendo ubicación...';
        status.className = 'text-sm text-yellow-600';
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    gpsLat = pos.coords.latitude;
                    gpsLng = pos.coords.longitude;
                    reportLat = gpsLat;
                    reportLng = gpsLng;
                    
                    status.textContent = 'Ubicación GPS obtenida correctamente';
                    status.className = 'text-sm text-green-600';
                    document.getElementById('btnSubmit').disabled = false;
                    
                    initMap(gpsLat, gpsLng);
                    updateLocationText(gpsLat, gpsLng);
                },
                (err) => {
                    status.textContent = 'Error al obtener GPS. Activa los permisos.';
                    status.className = 'text-sm text-red-600';
                },
                { enableHighAccuracy: true }
            );
        }
    }

    document.getElementById('btnGetLocation').addEventListener('click', getLocation);
    getLocation();

    // ── Vista previa de foto ──────────────────────────────────
    const fotoInput = document.getElementById('foto');
    const previewWrapper = document.getElementById('foto-preview-wrapper');
    const previewImg = document.getElementById('foto-preview-img');

    fotoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewWrapper.classList.remove('hidden');
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            previewWrapper.classList.add('hidden');
            previewImg.src = '';
        }
    });

    document.getElementById('btn-remove-foto').addEventListener('click', function() {
        fotoInput.value = '';
        previewImg.src = '';
        previewWrapper.classList.add('hidden');
    });

    document.getElementById('detailedReportForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.textContent = 'Enviando...';
        
        document.getElementById('errorMessage').classList.add('hidden');
        document.getElementById('successMessage').classList.add('hidden');

        const formData = new FormData();
        formData.append('citizen_carnet', citizenCarnet);
        formData.append('lat_gps', gpsLat);
        formData.append('long_gps', gpsLng);
        formData.append('lat_reporte', reportLat);
        formData.append('long_reporte', reportLng);
        formData.append('intensidad_propuesta', document.getElementById('intensidad').value);
        
        const address = document.getElementById('address').value;
        if(address) formData.append('address', address);
        
        const description = document.getElementById('description').value;
        if(description) formData.append('description', description);
        
        const fotoInput = document.getElementById('foto');
        if(fotoInput.files.length > 0) {
            formData.append('foto', fotoInput.files[0]);
        }
        
        try {
            const response = await fetch('/api/reportes', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (response.ok) {
                let etaMsg = '';
                if (result.eta) {
                    etaMsg = `<br><span class="font-bold text-sm">Tiempo estimado de ayuda: ~${result.eta.eta_minutes} min (desde ${result.eta.name}, a ${result.eta.distance_km}km).</span>`;
                }
                document.getElementById('successMessage').innerHTML = 'Reporte creado exitosamente.' + etaMsg;
                document.getElementById('successMessage').classList.remove('hidden');
                setTimeout(() => {
                    window.location.href = '/reports';
                }, 3500);
            } else {
                document.getElementById('errorMessage').textContent = 'Error: ' + (result.message || 'Error desconocido');
                document.getElementById('errorMessage').classList.remove('hidden');
                btn.disabled = false;
            }
        } catch (err) {
            document.getElementById('errorMessage').textContent = 'Error de conexión';
            document.getElementById('errorMessage').classList.remove('hidden');
            btn.disabled = false;
        }
        btn.textContent = 'Enviar Reporte';
    });
});
</script>
@endsection
