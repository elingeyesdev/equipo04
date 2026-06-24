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
                document.getElementById('successMessage').classList.remove('hidden');
                setTimeout(() => {
                    window.location.href = '/reports';
                }, 2000);
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
