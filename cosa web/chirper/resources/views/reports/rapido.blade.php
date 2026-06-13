@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Reporte Rápido de Inundación</h1>

        <form id="rapidReportForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Tu Ubicación (GPS)</label>
                <div class="mt-1 flex items-center justify-between">
                    <span id="gpsStatus" class="text-sm text-yellow-600">Obteniendo ubicación...</span>
                    <button type="button" id="btnGetLocation" class="text-sm text-blue-600 hover:text-blue-800">Actualizar GPS</button>
                </div>
                
                <div id="nearbyAnnouncement" class="mt-3 p-3 bg-blue-50 text-blue-800 rounded-md hidden border border-blue-200">
                    <p id="nearbyText" class="text-sm font-medium"></p>
                    <div class="mt-2 text-sm">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" id="chkReportClosest" class="rounded text-blue-600 focus:ring-blue-500" checked>
                            <span>Sí, quiero reportar y actualizar esta inundación cercana</span>
                        </label>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ubicación del Reporte (Máx. 500m del GPS)</label>
                <div id="map" class="h-64 bg-gray-100 rounded-lg border border-gray-300"></div>
                <p id="distanceWarning" class="mt-2 text-sm text-red-600 hidden">El marcador está demasiado lejos de tu ubicación real (máximo 500m).</p>
            </div>

            <div>
                <label for="intensidad" class="block text-sm font-medium text-gray-700">Intensidad Propuesta</label>
                <select id="intensidad" name="intensidad_propuesta" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="baja" selected>Baja</option>
                    <option value="media">Media</option>
                    <option value="alta">Alta</option>
                </select>
            </div>

            <button type="submit" id="btnSubmit" disabled class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                Enviar Reporte
            </button>
        </form>
        
        <div id="successMessage" class="mt-4 p-4 bg-green-100 text-green-800 rounded-md hidden">
            Reporte enviado o actualizado correctamente. Gracias por tu colaboración.
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<script>
document.addEventListener('DOMContentLoaded', function() {
    let map, marker, circle;
    let gpsLat, gpsLng;
    let reportLat, reportLng;
    const activas = @json($inundacionesActivas ?? []);
    let closestFlood = null;
    
    // UUID Generation or Retrieval
    function getUUID() {
        let uuid = localStorage.getItem('user_uuid');
        if (!uuid) {
            uuid = crypto.randomUUID();
            localStorage.setItem('user_uuid', uuid);
        }
        return uuid;
    }
    const userUuid = getUUID();

    // Haversine formula (JS)
    function getDistanceFromLatLonInM(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Radius of the earth in m
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

    function initMap(lat, lng) {
        if (!map) {
            map = L.map('map').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            // Círculo de 500m
            circle = L.circle([lat, lng], {
                color: 'blue',
                fillColor: '#3b82f6',
                fillOpacity: 0.1,
                radius: 500
            }).addTo(map);

            // Marcador movible
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
                }
            });
        } else {
            map.setView([lat, lng], 16);
            circle.setLatLng([lat, lng]);
            marker.setLatLng([lat, lng]);
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
                    
                    let closestDist = Infinity;
                    let nearbyCount = 0;
                    
                    activas.forEach(f => {
                        const d = getDistanceFromLatLonInM(gpsLat, gpsLng, f.latitud, f.longitud);
                        if (d <= 1000) nearbyCount++;
                        if (d < closestDist) {
                            closestDist = d;
                            closestFlood = f;
                        }
                    });
                    
                    const ann = document.getElementById('nearbyAnnouncement');
                    const txt = document.getElementById('nearbyText');
                    const chk = document.getElementById('chkReportClosest');
                    const intSelect = document.getElementById('intensidad');
                    
                    if (closestFlood && closestDist <= 500) {
                        ann.classList.remove('hidden');
                        txt.textContent = `Hemos encontrado ${nearbyCount} inundación(es) activa(s) cerca. La más cercana está a ${Math.round(closestDist)}m. El marcador se ha centrado allí automáticamente para actualizarla.`;
                        
                        reportLat = parseFloat(closestFlood.latitud);
                        reportLng = parseFloat(closestFlood.longitud);
                        
                        intSelect.value = closestFlood.intensidad_actual || 'baja';
                        chk.checked = true;
                        
                        chk.onchange = function() {
                            if (this.checked) {
                                reportLat = parseFloat(closestFlood.latitud);
                                reportLng = parseFloat(closestFlood.longitud);
                                intSelect.value = closestFlood.intensidad_actual || 'baja';
                            } else {
                                reportLat = gpsLat;
                                reportLng = gpsLng;
                                intSelect.value = 'baja';
                            }
                            if (marker && map) {
                                marker.setLatLng([reportLat, reportLng]);
                                map.setView([reportLat, reportLng]);
                            }
                        };
                    } else {
                        ann.classList.add('hidden');
                        reportLat = gpsLat;
                        reportLng = gpsLng;
                        intSelect.value = 'baja';
                    }
                    
                    status.textContent = 'Ubicación GPS obtenida correctamente';
                    status.className = 'text-sm text-green-600';
                    document.getElementById('btnSubmit').disabled = false;
                    
                    initMap(reportLat, reportLng);
                    
                    // Asegurarnos de que el círculo muestre el rango desde el GPS real
                    if (circle) circle.setLatLng([gpsLat, gpsLng]);
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

    document.getElementById('rapidReportForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.textContent = 'Enviando...';
        
        try {
            const response = await fetch('/api/reportes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    user_uuid: userUuid,
                    lat_gps: gpsLat,
                    long_gps: gpsLng,
                    lat_reporte: reportLat,
                    long_reporte: reportLng,
                    intensidad_propuesta: document.getElementById('intensidad').value
                })
            });
            
            const result = await response.json();
            
            if (response.ok) {
                document.getElementById('successMessage').classList.remove('hidden');
                setTimeout(() => {
                    window.location.href = '/login';
                }, 2000);
            } else {
                alert('Error: ' + (result.message || 'Error desconocido'));
                btn.disabled = false;
            }
        } catch (err) {
            alert('Error de conexión');
            btn.disabled = false;
        }
        btn.textContent = 'Enviar Reporte';
    });
});
</script>
@endsection
