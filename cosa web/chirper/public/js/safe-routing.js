document.addEventListener('DOMContentLoaded', function () {
    if (!window.ORS_API_KEY) {
        console.warn("OpenRouteService API Key no configurada.");
        return;
    }

    const panel = document.getElementById('routing-panel');
    if (!panel) return;

    let routeStartMarker = null;
    let routeEndMarker = null;
    let routeLayer = null;
    let routingFlowState = 'idle';
    let currentTransportMode = 'driving-car';
    let hadFallbackRoute = false;

    const STEP_IDS = {
        idle: 'routing-step-idle',
        transport: 'routing-step-transport',
        pick_origin: 'routing-step-pick-origin',
        pick_origin_map: 'routing-step-pick-origin-map',
        pick_dest: 'routing-step-pick-dest',
        pick_dest_map: 'routing-step-pick-dest-map',
        review: 'routing-step-review',
        results: 'routing-step-results',
    };

    const PROGRESS_BY_STATE = {
        transport: { step: 1, percent: 25 },
        pick_origin: { step: 2, percent: 50 },
        pick_origin_map: { step: 2, percent: 50 },
        pick_dest: { step: 3, percent: 75 },
        pick_dest_map: { step: 3, percent: 75 },
        review: { step: 4, percent: 100 },
    };

    const TRANSPORT_LABELS = {
        'driving-car': 'Auto',
        'cycling-regular': 'Bicicleta',
        'foot-walking': 'A pie',
    };

    const TRANSPORT_HINTS = {
        'driving-car': 'Usa calles; evita polígonos inundados.',
        'cycling-regular': 'Puede sugerir desvíos extra en zonas críticas.',
        'foot-walking': 'Puede sugerir desvíos extra en zonas críticas.',
    };

    const inputStart = document.getElementById('route-start-input');
    const inputEnd = document.getElementById('route-end-input');
    const inputEndManual = document.getElementById('route-end-input-manual');
    const btnCalc = document.getElementById('btn-calculate-route');
    const loadingDiv = document.getElementById('routing-loading');
    const progressEl = document.getElementById('routing-progress');
    const progressLabel = document.getElementById('routing-progress-label');
    const progressPercent = document.getElementById('routing-progress-percent');
    const progressBar = document.getElementById('routing-progress-bar');
    const mapHint = document.getElementById('routing-map-hint');
    const mapContainer = document.getElementById('map-container');

    const btnFocusStart = document.getElementById('btn-focus-start');
    const btnClearStart = document.getElementById('btn-clear-start');
    const btnFocusEnd = document.getElementById('btn-focus-end');
    const btnClearEnd = document.getElementById('btn-clear-end');

    function isPicking() {
        return routingFlowState === 'pick_origin_map' || routingFlowState === 'pick_dest_map';
    }

    function isWizardActive() {
        return routingFlowState !== 'idle' && routingFlowState !== 'results';
    }

    window.SGI_ROUTING = {
        getState: () => routingFlowState,
        isPicking,
        isWizardActive,
        cancel: () => cancelWizard(false),
    };

    function updateFloodCount() {
        const count = (window.floodReports || []).length;
        const el = document.getElementById('routing-flood-count');
        if (el) {
            el.textContent = count === 1
                ? '1 inundación activa en el mapa'
                : `${count} inundaciones activas en el mapa`;
        }
    }

    function formatCoords(lat, lng) {
        return `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    }

    function haversineKm(lat1, lng1, lat2, lng2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function updateOriginSummaries() {
        const text = routeStartMarker
            ? formatCoords(routeStartMarker.getLatLng().lat, routeStartMarker.getLatLng().lng)
            : '—';
        ['routing-origin-summary', 'routing-origin-summary-map'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = text;
        });
    }

    function updateReviewSummary() {
        if (!routeStartMarker || !routeEndMarker) return;

        const s = routeStartMarker.getLatLng();
        const e = routeEndMarker.getLatLng();

        document.getElementById('review-origin-coords').textContent = formatCoords(s.lat, s.lng);
        document.getElementById('review-dest-coords').textContent = formatCoords(e.lat, e.lng);

        const dist = haversineKm(s.lat, s.lng, e.lat, e.lng);
        document.getElementById('review-straight-distance').textContent =
            `~${dist < 1 ? (dist * 1000).toFixed(0) + ' m' : dist.toFixed(2) + ' km'} en línea recta`;

        document.getElementById('review-transport-label').innerHTML =
            `Transporte: <span class="font-bold text-gray-800">${TRANSPORT_LABELS[currentTransportMode]}</span>`;

        const hazards = getAllHazardPoints().length;
        document.getElementById('review-hazard-chip').textContent =
            hazards === 1 ? 'Evitando 1 zona de inundación' : `Evitando ${hazards} zonas de inundación`;
    }

    function updateMapHint() {
        if (!mapHint) return;

        if (routingFlowState === 'pick_origin_map') {
            mapHint.textContent = 'Haz clic en el mapa para marcar tu punto de partida';
            mapHint.classList.remove('hidden');
        } else if (routingFlowState === 'pick_dest_map') {
            mapHint.textContent = 'Haz clic en el mapa para marcar tu destino';
            mapHint.classList.remove('hidden');
        } else {
            mapHint.classList.add('hidden');
        }
    }

    function updatePickModeCursor() {
        if (!mapContainer) return;
        if (isPicking()) {
            mapContainer.classList.add('routing-pick-mode');
        } else {
            mapContainer.classList.remove('routing-pick-mode');
        }
    }

    function updateProgress() {
        const info = PROGRESS_BY_STATE[routingFlowState];
        if (!info) {
            progressEl.classList.add('hidden');
            return;
        }
        progressEl.classList.remove('hidden');
        progressLabel.textContent = `Paso ${info.step} de 4`;
        progressPercent.textContent = `${info.percent}%`;
        progressBar.style.width = `${info.percent}%`;
    }

    function setFlowState(next) {
        routingFlowState = next;

        Object.entries(STEP_IDS).forEach(([state, id]) => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('hidden', state !== next);
        });

        updateProgress();
        updateMapHint();
        updatePickModeCursor();

        if (next === 'idle') {
            updateFloodCount();
        }
        if (next === 'pick_dest' || next === 'pick_dest_map') {
            updateOriginSummaries();
        }
        if (next === 'review') {
            updateReviewSummary();
        }
        if (next === 'results') {
            syncResultsTransportButtons();
        }
    }

    function syncTransportButtons(selector) {
        document.querySelectorAll(selector).forEach(btn => {
            const mode = btn.getAttribute('data-mode');
            const active = mode === currentTransportMode;
            btn.classList.toggle('text-emerald-700', active);
            btn.classList.toggle('bg-white', active);
            btn.classList.toggle('shadow-sm', active);
            btn.classList.toggle('text-gray-500', !active);
            btn.classList.toggle('hover:text-gray-700', !active);
        });
    }

    function syncResultsTransportButtons() {
        syncTransportButtons('.transport-btn-results');
    }

    function setTransportMode(mode) {
        currentTransportMode = mode;
        syncTransportButtons('.transport-btn');
        syncTransportButtons('.transport-btn-results');

        const hint = document.getElementById('routing-transport-hint');
        if (hint) hint.textContent = TRANSPORT_HINTS[mode] || '';
    }

    function clearEntireRoute() {
        if (routeLayer && window.mapObj) window.mapObj.removeLayer(routeLayer);
        routeLayer = null;
        hadFallbackRoute = false;
        const fallback = document.getElementById('routing-fallback-warning');
        if (fallback) fallback.classList.add('hidden');
    }

    function clearMarkers() {
        if (routeStartMarker && window.mapObj) window.mapObj.removeLayer(routeStartMarker);
        if (routeEndMarker && window.mapObj) window.mapObj.removeLayer(routeEndMarker);
        routeStartMarker = null;
        routeEndMarker = null;

        if (inputStart) inputStart.value = '';
        if (inputEnd) inputEnd.value = '';
        if (inputEndManual) inputEndManual.value = '';

        btnFocusStart?.classList.add('hidden');
        btnClearStart?.classList.add('hidden');
        btnFocusEnd?.classList.add('hidden');
        btnClearEnd?.classList.add('hidden');

        document.getElementById('btn-origin-map-confirm')?.setAttribute('disabled', 'disabled');
        document.getElementById('btn-dest-map-confirm')?.setAttribute('disabled', 'disabled');
    }

    function resetAll() {
        clearEntireRoute();
        clearMarkers();
        setFlowState('idle');
    }

    function cancelWizard(confirmFirst) {
        if (!confirmFirst || !isWizardActive()) {
            resetAll();
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Cancelar planificación de ruta?',
                text: 'Se borrarán los puntos marcados en el mapa.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Continuar',
                confirmButtonColor: '#dc2626',
            }).then(result => {
                if (result.isConfirmed) resetAll();
            });
        } else if (confirm('¿Cancelar planificación de ruta?')) {
            resetAll();
        }
    }

    function startWizard() {
        clearEntireRoute();
        clearMarkers();
        setTransportMode(currentTransportMode);
        setFlowState('transport');
    }

    function confirmOriginMap() {
        if (!routeStartMarker) return;
        setFlowState('pick_dest');
    }

    function confirmDestMap() {
        if (!routeEndMarker) return;
        setFlowState('review');
    }

    function setMarker(type, lat, lng) {
        if (!window.mapObj) return;

        const iconColor = type === 'start' ? '#10b981' : '#ef4444';
        const labelText = type === 'start' ? 'Punto de Partida' : 'Destino';
        const svgPin = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${iconColor}" width="36" height="36" stroke="white" stroke-width="1.5"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>`;

        const customIcon = L.divIcon({
            className: 'custom-routing-marker',
            html: `<div style="filter: drop-shadow(0px 3px 3px rgba(0,0,0,0.4)); transform: translateY(-4px);">${svgPin}</div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36],
            tooltipAnchor: [18, -18],
        });

        if (type === 'start') {
            if (routeStartMarker) window.mapObj.removeLayer(routeStartMarker);
            routeStartMarker = L.marker([lat, lng], { icon: customIcon, draggable: true }).addTo(window.mapObj);
            routeStartMarker.bindTooltip(labelText, { direction: 'right', className: 'font-bold text-gray-700' });
            routeStartMarker.on('dragend', (e) => {
                const ll = e.target.getLatLng();
                inputStart.value = formatCoords(ll.lat, ll.lng);
                updateOriginSummaries();
                if (routingFlowState === 'review') updateReviewSummary();
                if (routeLayer) clearEntireRoute();
            });
            inputStart.value = formatCoords(lat, lng);
            btnFocusStart.classList.remove('hidden');
            btnClearStart.classList.remove('hidden');
            document.getElementById('btn-origin-map-confirm')?.removeAttribute('disabled');
            updateOriginSummaries();
        } else {
            if (routeEndMarker) window.mapObj.removeLayer(routeEndMarker);
            routeEndMarker = L.marker([lat, lng], { icon: customIcon, draggable: true }).addTo(window.mapObj);
            routeEndMarker.bindTooltip(labelText, { direction: 'right', className: 'font-bold text-gray-700' });
            routeEndMarker.on('dragend', (e) => {
                const ll = e.target.getLatLng();
                inputEnd.value = formatCoords(ll.lat, ll.lng);
                if (routingFlowState === 'review') updateReviewSummary();
                if (routeLayer) clearEntireRoute();
            });
            inputEnd.value = formatCoords(lat, lng);
            btnFocusEnd.classList.remove('hidden');
            btnClearEnd.classList.remove('hidden');
            document.getElementById('btn-dest-map-confirm')?.removeAttribute('disabled');
        }

        if (routeLayer) clearEntireRoute();
    }

    function requestGpsOrigin() {
        const gpsError = document.getElementById('routing-gps-error');
        const gpsSub = document.getElementById('btn-origin-gps-sub');

        if (!navigator.geolocation) {
            if (gpsError) {
                gpsError.textContent = 'Tu navegador no soporta geolocalización. Elige en el mapa.';
                gpsError.classList.remove('hidden');
            }
            return;
        }

        if (gpsError) gpsError.classList.add('hidden');
        if (gpsSub) gpsSub.textContent = 'Obteniendo ubicación…';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                if (gpsSub) gpsSub.textContent = 'Usar GPS del dispositivo';
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                setMarker('start', lat, lng);
                window.mapObj?.setView([lat, lng], 15);
                setFlowState('pick_dest');
            },
            (error) => {
                if (gpsSub) gpsSub.textContent = 'Usar GPS del dispositivo';
                if (gpsError) {
                    gpsError.textContent = `No se pudo obtener la ubicación: ${error.message}. Prueba "Elegir en el mapa".`;
                    gpsError.classList.remove('hidden');
                }
            }
        );
    }

    function parseManualDest() {
        const val = inputEndManual?.value || '';
        const parts = val.split(',').map(s => parseFloat(s.trim()));
        if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
            setMarker('end', parts[0], parts[1]);
            window.mapObj?.setView([parts[0], parts[1]], 15);
            setFlowState('review');
        }
    }

    // ── Transport buttons ──
    document.querySelectorAll('.transport-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            setTransportMode(e.currentTarget.getAttribute('data-mode'));
        });
    });

    document.querySelectorAll('.transport-btn-results').forEach(btn => {
        btn.addEventListener('click', (e) => {
            setTransportMode(e.currentTarget.getAttribute('data-mode'));
            if (routeStartMarker && routeEndMarker && routingFlowState === 'results') {
                btnCalc?.click();
            }
        });
    });

    // ── Wizard navigation ──
    document.getElementById('btn-start-safe-route')?.addEventListener('click', startWizard);
    document.getElementById('btn-toggle-how-it-works')?.addEventListener('click', () => {
        document.getElementById('routing-how-it-works')?.classList.toggle('hidden');
    });
    document.getElementById('btn-transport-continue')?.addEventListener('click', () => setFlowState('pick_origin'));
    document.getElementById('btn-transport-cancel')?.addEventListener('click', () => cancelWizard(false));

    document.getElementById('btn-origin-gps')?.addEventListener('click', requestGpsOrigin);
    document.getElementById('btn-origin-map')?.addEventListener('click', () => setFlowState('pick_origin_map'));
    document.getElementById('btn-pick-origin-back')?.addEventListener('click', () => setFlowState('transport'));
    document.getElementById('btn-origin-map-back')?.addEventListener('click', () => setFlowState('pick_origin'));
    document.getElementById('btn-origin-map-confirm')?.addEventListener('click', confirmOriginMap);

    document.getElementById('btn-dest-map')?.addEventListener('click', () => setFlowState('pick_dest_map'));
    document.getElementById('btn-change-origin')?.addEventListener('click', () => setFlowState('pick_origin'));
    document.getElementById('btn-pick-dest-back')?.addEventListener('click', () => setFlowState('pick_origin'));
    document.getElementById('btn-dest-map-back')?.addEventListener('click', () => setFlowState('pick_dest'));
    document.getElementById('btn-dest-map-confirm')?.addEventListener('click', confirmDestMap);
    document.getElementById('btn-dest-manual-apply')?.addEventListener('click', parseManualDest);

    document.getElementById('btn-review-edit-origin')?.addEventListener('click', () => setFlowState('pick_origin'));
    document.getElementById('btn-review-edit-dest')?.addEventListener('click', () => setFlowState('pick_dest'));
    document.getElementById('btn-review-cancel')?.addEventListener('click', () => cancelWizard(true));

    document.getElementById('btn-view-route-map')?.addEventListener('click', () => {
        if (routeLayer && window.mapObj) {
            window.mapObj.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
        }
    });
    document.getElementById('btn-new-route')?.addEventListener('click', () => {
        clearEntireRoute();
        clearMarkers();
        startWizard();
    });
    document.getElementById('btn-finish-route')?.addEventListener('click', () => resetAll());

    // ── Focus / clear individual markers ──
    btnFocusStart?.addEventListener('click', () => {
        if (routeStartMarker) window.mapObj.flyTo(routeStartMarker.getLatLng(), 15);
    });
    btnFocusEnd?.addEventListener('click', () => {
        if (routeEndMarker) window.mapObj.flyTo(routeEndMarker.getLatLng(), 15);
    });

    btnClearStart?.addEventListener('click', () => {
        if (routeStartMarker) window.mapObj.removeLayer(routeStartMarker);
        routeStartMarker = null;
        inputStart.value = '';
        btnFocusStart.classList.add('hidden');
        btnClearStart.classList.add('hidden');
        document.getElementById('btn-origin-map-confirm')?.setAttribute('disabled', 'disabled');
        if (routeLayer) clearEntireRoute();
    });

    btnClearEnd?.addEventListener('click', () => {
        if (routeEndMarker) window.mapObj.removeLayer(routeEndMarker);
        routeEndMarker = null;
        inputEnd.value = '';
        btnFocusEnd.classList.add('hidden');
        btnClearEnd.classList.add('hidden');
        document.getElementById('btn-dest-map-confirm')?.setAttribute('disabled', 'disabled');
        if (routeLayer) clearEntireRoute();
    });

    // ── Panel open/close ──
    document.getElementById('toggle-routing-panel')?.addEventListener('click', () => {
        if (isWizardActive()) {
            cancelWizard(true);
            return;
        }
        hidePanel();
    });

    function hidePanel() {
        panel.classList.add('-translate-x-96', 'opacity-0');
        setTimeout(() => {
            panel.classList.add('hidden');
            document.getElementById('btn-open-routing')?.classList.remove('hidden');
        }, 300);
    }

    document.getElementById('btn-open-routing')?.addEventListener('click', (e) => {
        e.currentTarget.classList.add('hidden');
        panel.classList.remove('hidden');
        setTimeout(() => panel.classList.remove('-translate-x-96', 'opacity-0'), 10);
    });

    // ── Map click (gated) ──
    let mapInterval = setInterval(() => {
        if (window.mapObj) {
            clearInterval(mapInterval);

            window.mapObj.on('click', function (e) {
                if (routingFlowState === 'pick_origin_map') {
                    if (e.originalEvent) {
                        L.DomEvent.stopPropagation(e.originalEvent);
                    }
                    setMarker('start', e.latlng.lat, e.latlng.lng);
                    return;
                }
                if (routingFlowState === 'pick_dest_map') {
                    if (e.originalEvent) {
                        L.DomEvent.stopPropagation(e.originalEvent);
                    }
                    setMarker('end', e.latlng.lat, e.latlng.lng);
                    return;
                }
            });
        }
    }, 500);

    // ── Hazard / ORS logic (unchanged) ──
    function createCirclePolygon(lat, lng, radiusInMeters, points = 16) {
        const R = 6378137;
        const latRad = lat * Math.PI / 180;
        const poly = [];
        for (let i = 0; i <= points; i++) {
            const theta = (i / points) * (2 * Math.PI);
            const dx = radiusInMeters * Math.cos(theta);
            const dy = radiusInMeters * Math.sin(theta);
            const pLat = lat + (dy / R) * (180 / Math.PI);
            const pLng = lng + (dx / (R * Math.cos(latRad))) * (180 / Math.PI);
            poly.push([pLng, pLat]);
        }
        return [poly];
    }

    function getAllHazardPoints() {
        const points = [];
        const allReports = (window.floodReports || []).concat(window.pendingReports || []);

        allReports.forEach(report => {
            const lat = parseFloat(report.latitud || report.lat_reporte);
            const lng = parseFloat(report.longitud || report.long_reporte);

            if (!isNaN(lat) && !isNaN(lng)) {
                let radius = 15;
                let intensidad = report.intensidad_calculada || report.intensidad_propuesta;
                if (intensidad === 'alta') radius = 35;
                else if (intensidad === 'media') radius = 25;

                points.push({ lat, lng, radius, polygon_coords: report.polygon_coords || null });
            }

            if (report.reportes_activos && Array.isArray(report.reportes_activos)) {
                report.reportes_activos.forEach(rep => {
                    const rLat = parseFloat(rep.lat_reporte);
                    const rLng = parseFloat(rep.long_reporte);
                    if (!isNaN(rLat) && !isNaN(rLng)) {
                        let rRadius = 15;
                        if (rep.intensidad_propuesta === 'alta') rRadius = 35;
                        else if (rep.intensidad_propuesta === 'media') rRadius = 25;

                        points.push({
                            lat: rLat, lng: rLng, radius: rRadius,
                            polygon_coords: rep.polygon_coords || null,
                        });
                    }
                });

                let activeReps = report.reportes_activos;
                if (activeReps.length > 1) {
                    for (let i = 0; i < activeReps.length; i++) {
                        for (let j = i + 1; j < activeReps.length; j++) {
                            const r1Lat = parseFloat(activeReps[i].lat_reporte);
                            const r1Lng = parseFloat(activeReps[i].long_reporte);
                            const r2Lat = parseFloat(activeReps[j].lat_reporte);
                            const r2Lng = parseFloat(activeReps[j].long_reporte);

                            if (isNaN(r1Lat) || isNaN(r1Lng) || isNaN(r2Lat) || isNaN(r2Lng)) continue;

                            const latToMeters = 111320;
                            const lngToMeters = 111320 * Math.cos(r1Lat * Math.PI / 180);
                            const dx = (r2Lng - r1Lng) * lngToMeters;
                            const dy = (r2Lat - r1Lat) * latToMeters;
                            const dist = Math.sqrt(dx * dx + dy * dy);

                            if (dist > 10 && dist <= 250) {
                                let steps = Math.floor(dist / 15);
                                if (steps < 2) steps = 2;
                                for (let k = 1; k < steps; k++) {
                                    let fraction = k / steps;
                                    points.push({
                                        lat: r1Lat + (r2Lat - r1Lat) * fraction,
                                        lng: r1Lng + (r2Lng - r1Lng) * fraction,
                                        radius: 15,
                                        polygon_coords: null,
                                    });
                                }
                            }
                        }
                    }
                }
            }
        });
        return points;
    }

    function getAvoidPolygons(hazards) {
        const polygons = [];

        hazards.forEach(hazard => {
            if (hazard.polygon_coords && Array.isArray(hazard.polygon_coords) && hazard.polygon_coords.length > 2) {
                const ring = hazard.polygon_coords.map(coord => [parseFloat(coord[1]), parseFloat(coord[0])]);
                if (ring[0][0] !== ring[ring.length - 1][0] || ring[0][1] !== ring[ring.length - 1][1]) {
                    ring.push([...ring[0]]);
                }
                polygons.push([ring]);
            } else {
                polygons.push(createCirclePolygon(hazard.lat, hazard.lng, hazard.radius));
            }
        });

        return polygons;
    }

    btnCalc?.addEventListener('click', async () => {
        if (!routeStartMarker || !routeEndMarker) return;

        loadingDiv.classList.remove('hidden');
        loadingDiv.classList.add('flex');
        btnCalc.disabled = true;
        hadFallbackRoute = false;

        const startLat = routeStartMarker.getLatLng().lat;
        const startLng = routeStartMarker.getLatLng().lng;
        const endLat = routeEndMarker.getLatLng().lat;
        const endLng = routeEndMarker.getLatLng().lng;

        let coordinatesArray = [[startLng, startLat], [endLng, endLat]];
        const allHazards = getAllHazardPoints();

        if (currentTransportMode !== 'driving-car' && allHazards.length > 0) {
            const latToMeters = 111320;
            const lngToMeters = 111320 * Math.cos(startLat * Math.PI / 180);
            const ex = (endLng - startLng) * lngToMeters;
            const ey = (endLat - startLat) * latToMeters;
            const routeLength = Math.sqrt(ex * ex + ey * ey);

            if (routeLength > 0) {
                let worstFlood = null;
                let maxInterference = 0;

                allHazards.forEach(hazard => {
                    const fx = (hazard.lng - startLng) * lngToMeters;
                    const fy = (hazard.lat - startLat) * latToMeters;
                    const dot = (fx * ex + fy * ey) / routeLength;

                    if (dot > 0 && dot < routeLength) {
                        const projX = (dot / routeLength) * ex;
                        const projY = (dot / routeLength) * ey;
                        const distToLine = Math.sqrt((fx - projX) ** 2 + (fy - projY) ** 2);

                        if (distToLine < hazard.radius) {
                            const interference = hazard.radius - distToLine;
                            if (interference > maxInterference) {
                                maxInterference = interference;
                                worstFlood = { fx, fy, radius: hazard.radius, projX, projY };
                            }
                        }
                    }
                });

                if (worstFlood) {
                    let dirX = worstFlood.fx - worstFlood.projX;
                    let dirY = worstFlood.fy - worstFlood.projY;
                    let dirLen = Math.sqrt(dirX * dirX + dirY * dirY);

                    if (dirLen < 1) {
                        dirX = -ey / routeLength;
                        dirY = ex / routeLength;
                        dirLen = 1;
                    }

                    let pushDist = Math.max(worstFlood.radius * 1.5, 100);
                    let waypointLng, waypointLat;
                    let validWaypoint = false;

                    for (let attempt = 0; attempt < 8; attempt++) {
                        const waypointX = worstFlood.fx + (dirX / dirLen) * pushDist;
                        const waypointY = worstFlood.fy + (dirY / dirLen) * pushDist;
                        waypointLng = startLng + (waypointX / lngToMeters);
                        waypointLat = startLat + (waypointY / latToMeters);

                        let inHazard = false;
                        for (let h of allHazards) {
                            const hx = (h.lng - startLng) * lngToMeters;
                            const hy = (h.lat - startLat) * latToMeters;
                            const dist = Math.sqrt((waypointX - hx) ** 2 + (waypointY - hy) ** 2);
                            if (dist <= h.radius) {
                                inHazard = true;
                                break;
                            }
                        }

                        if (!inHazard) {
                            validWaypoint = true;
                            break;
                        }
                        pushDist += 50;
                    }

                    if (validWaypoint) {
                        coordinatesArray.splice(1, 0, [waypointLng, waypointLat]);
                    }
                }
            }
        }

        const avoidPolys = getAvoidPolygons(allHazards);

        async function fetchRoute(coords, polys) {
            let body = { coordinates: coords, elevation: false, instructions: false, units: 'm' };
            if (coords.length === 3) {
                body.radiuses = [-1, 1000, -1];
            }
            if (polys && polys.length > 0) {
                body.options = { avoid_polygons: { type: 'MultiPolygon', coordinates: polys } };
            }
            const response = await fetch(`https://api.openrouteservice.org/v2/directions/${currentTransportMode}/geojson`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
                    'Content-Type': 'application/json',
                    'Authorization': window.ORS_API_KEY,
                },
                body: JSON.stringify(body),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error ? data.error.message : 'Error calculando ruta');
            return data;
        }

        try {
            let data;
            const originalCoords = [[startLng, startLat], [endLng, endLat]];

            try {
                data = await fetchRoute(coordinatesArray, avoidPolys);
            } catch (err1) {
                console.warn('Intento 1 (Ruta Evasiva Óptima) falló:', err1.message);
                try {
                    data = await fetchRoute(originalCoords, avoidPolys);
                } catch (err2) {
                    console.warn('Intento 2 (Evasión Directa) falló:', err2.message);
                    data = await fetchRoute(originalCoords, null);
                    hadFallbackRoute = true;
                }
            }

            if (routeLayer) window.mapObj.removeLayer(routeLayer);

            routeLayer = L.geoJSON(data, {
                style: function () {
                    return { color: '#059669', weight: 6, opacity: 0.8, lineCap: 'round', lineJoin: 'round' };
                },
            }).addTo(window.mapObj);

            window.mapObj.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });

            const summary = data.features[0].properties.summary;
            document.getElementById('route-distance').innerText = (summary.distance / 1000).toFixed(2) + ' km';
            document.getElementById('route-duration').innerText = Math.round(summary.duration / 60) + ' min';

            const fallbackEl = document.getElementById('routing-fallback-warning');
            if (fallbackEl) {
                fallbackEl.classList.toggle('hidden', !hadFallbackRoute);
            }

            setFlowState('results');

        } catch (error) {
            console.error(error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'No se pudo calcular la ruta', text: error.message, confirmButtonColor: '#059669' });
            } else {
                alert('No se pudo calcular la ruta: ' + error.message);
            }
        } finally {
            loadingDiv.classList.add('hidden');
            loadingDiv.classList.remove('flex');
            btnCalc.disabled = false;
        }
    });

    // ── Panel drag ──
    const panelHeader = document.getElementById('routing-panel-header');
    let isDragging = false;
    let offsetX = 0, offsetY = 0;

    panelHeader?.addEventListener('mousedown', (e) => {
        if (e.target.closest('button')) return;
        isDragging = true;
        offsetX = e.clientX - panel.offsetLeft;
        offsetY = e.clientY - panel.offsetTop;
        panel.style.transition = 'none';
        panel.style.right = 'auto';
        panel.style.bottom = 'auto';
        panel.style.left = panel.offsetLeft + 'px';
        panel.style.top = panel.offsetTop + 'px';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        let newX = e.clientX - offsetX;
        let newY = e.clientY - offsetY;
        const container = panel.parentElement;
        newX = Math.max(0, Math.min(newX, container.offsetWidth - panel.offsetWidth));
        newY = Math.max(0, Math.min(newY, container.offsetHeight - panel.offsetHeight));
        panel.style.left = newX + 'px';
        panel.style.top = newY + 'px';
    });

    document.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            panel.style.transition = 'opacity 0.3s';
            document.body.style.userSelect = '';
        }
    });

    // ── Init ──
    setFlowState('idle');
    updateFloodCount();
    document.addEventListener('reportsMapRefreshed', updateFloodCount);
});
