/**
 * Reporte Rápido — flujo ciudadano anónimo (SGI)
 */
(function () {
    'use strict';

    const cfg = window.RAPIDO_CONFIG || {};
    const MAX_GPS_DISTANCE_M = 500;

    const state = {
        floods: Array.isArray(cfg.initialFloods) ? cfg.initialFloods.slice() : [],
        gpsLat: null,
        gpsLng: null,
        reportLat: null,
        reportLng: null,
        intensity: 'baja',
        map: null,
        marker: null,
        circle: null,
        heatLayer: null,
        reportDotsLayer: null,
        refreshTimer: null,
        heatPulseTimer: null,
        mapResizeObserver: null,
        shouldFitBounds: false,
        submitting: false,
    };

    // ── Geo helpers ──────────────────────────────────────────────────────

    function deg2rad(deg) {
        return deg * (Math.PI / 180);
    }

    function haversineM(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = deg2rad(lat2 - lat1);
        const dLon = deg2rad(lon2 - lon1);
        const a =
            Math.sin(dLat / 2) ** 2 +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.sin(dLon / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function normalizeIntensity(value) {
        return ['baja', 'media', 'alta'].includes(value) ? value : 'baja';
    }

    function normalizeRings(coords) {
        if (!coords || !window.normalizePolygonRings) return [];
        return window.normalizePolygonRings(coords);
    }

    function pointInRing(lat, lng, ring) {
        if (!ring || ring.length < 3) return false;
        let inside = false;
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const yi = ring[i][0];
            const xi = ring[i][1];
            const yj = ring[j][0];
            const xj = ring[j][1];
            const intersect =
                yi > lat !== yj > lat &&
                lng < ((xj - xi) * (lat - yi)) / (yj - yi + 1e-12) + xi;
            if (intersect) inside = !inside;
        }
        return inside;
    }

    function isInsideAnyAltaFlood(lat, lng) {
        return state.floods.some(function (f) {
            if (normalizeIntensity(f.intensidad_calculada) !== 'alta') return false;
            const rings = normalizeRings(f.polygon_coords);
            return rings.some(function (ring) {
                return pointInRing(lat, lng, ring);
            });
        });
    }

    // ── DOM refs ─────────────────────────────────────────────────────────

    const el = {
        btnRefreshGps: document.getElementById('rapidoBtnRefreshGps'),
        alertBanner: document.getElementById('rapidoAlertBanner'),
        heatLegend: document.getElementById('rapidoHeatLegend'),
        distanceWarn: document.getElementById('rapidoDistanceWarn'),
        intensityBtns: document.querySelectorAll('.rapido-intensity-btn'),
        submitBtn: document.getElementById('rapidoSubmitBtn'),
        submitHint: document.getElementById('rapidoSubmitHint'),
        formSection: document.getElementById('rapidoFormSection'),
        successPanel: document.getElementById('rapidoSuccessPanel'),
        successMessage: document.getElementById('rapidoSuccessMessage'),
        successReportId: document.getElementById('rapidoSuccessReportId'),
        successEta: document.getElementById('rapidoSuccessEta'),
    };

    // ── GPS ──────────────────────────────────────────────────────────────

    function validateMarkerDistance() {
        if (state.gpsLat == null || state.reportLat == null) return false;
        const dist = haversineM(state.gpsLat, state.gpsLng, state.reportLat, state.reportLng);
        const ok = dist <= MAX_GPS_DISTANCE_M;
        if (el.distanceWarn) el.distanceWarn.classList.toggle('hidden', ok);
        if (el.submitBtn) el.submitBtn.disabled = !ok || state.submitting || state.gpsLat == null;
        return ok;
    }

    function applyGpsPosition(lat, lng) {
        state.gpsLat = lat;
        state.gpsLng = lng;
        state.reportLat = lat;
        state.reportLng = lng;
        state.shouldFitBounds = true;

        initOrUpdateMap();
        fetchContext();
        updateAlertBanner();
        validateMarkerDistance();
    }

    function requestGps() {
        if (!navigator.geolocation) {
            alert('GPS no disponible en este dispositivo');
            if (el.submitBtn) el.submitBtn.disabled = true;
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                applyGpsPosition(pos.coords.latitude, pos.coords.longitude);
            },
            function () {
                alert('No se pudo obtener GPS. Activa los permisos de ubicación.');
                if (el.submitBtn) el.submitBtn.disabled = true;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 },
        );
    }

    // ── Context API ──────────────────────────────────────────────────────

    async function fetchContext() {
        if (state.gpsLat == null || !cfg.apiContext) return;

        try {
            const url =
                cfg.apiContext +
                '?lat=' +
                encodeURIComponent(state.gpsLat) +
                '&lng=' +
                encodeURIComponent(state.gpsLng);
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const json = await res.json();
            state.floods = Array.isArray(json.data) ? json.data : [];
            renderMapLayers();
            updateAlertBanner();
        } catch (_e) {
            /* silent refresh failure */
        }
    }

    function startRefreshTimer() {
        if (state.refreshTimer) clearInterval(state.refreshTimer);
        state.refreshTimer = setInterval(fetchContext, cfg.refreshIntervalMs || 60000);
    }

    // ── Intensity & submit UI ────────────────────────────────────────────

    function setIntensity(value) {
        state.intensity = normalizeIntensity(value);
        el.intensityBtns.forEach(function (btn) {
            btn.classList.toggle('is-active', btn.dataset.intensity === state.intensity);
        });
        updateSubmitButton();
    }

    function updateSubmitButton() {
        if (!el.submitBtn) return;

        el.submitBtn.className =
            'rapido-submit-btn rapido-submit-btn--' + state.intensity;

        if (state.intensity === 'alta') {
            el.submitBtn.textContent = 'Reportar emergencia';
            if (el.submitHint) {
                el.submitHint.textContent =
                    'Prioridad alta. Tu reporte quedará pendiente de validación por las autoridades.';
            }
        } else {
            el.submitBtn.textContent = 'Enviar reporte';
            if (el.submitHint) {
                el.submitHint.textContent =
                    'Tu reporte quedará pendiente de validación por las autoridades.';
            }
        }
    }

    function updateAlertBanner() {
        if (!el.alertBanner || state.gpsLat == null) return;
        el.alertBanner.classList.toggle('hidden', !isInsideAnyAltaFlood(state.gpsLat, state.gpsLng));
    }

    function updateHeatLegend() {
        if (!el.heatLegend) return;

        const instance = window.smartHeatmapInstance;
        if (!instance || !Array.isArray(instance.tiers) || instance.tiers.length === 0) {
            el.heatLegend.classList.add('hidden');
            return;
        }

        el.heatLegend.classList.remove('hidden');
        el.heatLegend.querySelectorAll('[data-heat-tier]').forEach(function (row) {
            const tier = row.getAttribute('data-heat-tier');
            row.style.display = instance.tiers.indexOf(tier) !== -1 ? '' : 'none';
        });
    }

    // ── Map ──────────────────────────────────────────────────────────────

    function buildUserIcon() {
        return L.divIcon({
            className: 'rapido-user-pin-wrap',
            html: '<div class="rapido-user-pin"></div>',
            iconSize: [18, 18],
            iconAnchor: [9, 9],
        });
    }

    function scheduleHeatRedraw() {
        if (!state.map || !window.smartHeatmapInstance) return;
        if (typeof window.redrawFloodHeatOverlays !== 'function') return;

        window.redrawFloodHeatOverlays(state.map, window.smartHeatmapInstance);
        setTimeout(function () {
            if (!state.map || !window.smartHeatmapInstance) return;
            window.redrawFloodHeatOverlays(state.map, window.smartHeatmapInstance);
        }, 120);
    }

    function startHeatPulseTimer() {
        if (state.heatPulseTimer) return;
        const fillCfg = window.SMART_FLOOD_FILL || {};
        const pulseMs = fillCfg.pulseIntervalMs || 600;
        state.heatPulseTimer = setInterval(function () {
            if (typeof window.tickFloodHeatTtlPulse === 'function') {
                window.tickFloodHeatTtlPulse();
            }
        }, pulseMs);
    }

    function fitMapToFloods() {
        if (!state.map) return;

        const points = [];

        if (state.gpsLat != null && state.gpsLng != null) {
            points.push([state.gpsLat, state.gpsLng]);
        }
        if (state.reportLat != null && state.reportLng != null) {
            points.push([state.reportLat, state.reportLng]);
        }

        state.floods.forEach(function (f) {
            const lat = parseFloat(f.latitud);
            const lng = parseFloat(f.longitud);
            if (!isNaN(lat) && !isNaN(lng)) {
                points.push([lat, lng]);
            }
            (f.reportes_activos || []).forEach(function (r) {
                const rl = parseFloat(r.lat_reporte);
                const rg = parseFloat(r.long_reporte);
                if (!isNaN(rl) && !isNaN(rg)) {
                    points.push([rl, rg]);
                }
            });
        });

        if (points.length >= 2) {
            state.map.fitBounds(points, { padding: [40, 40], maxZoom: 16, animate: true });
        } else if (points.length === 1) {
            state.map.setView(points[0], 16, { animate: true });
        }
    }

    function renderReportDots() {
        if (!state.reportDotsLayer) return;
        state.reportDotsLayer.clearLayers();

        state.floods.forEach(function (f) {
            const reps = f.reportes_activos || [];
            reps.forEach(function (r) {
                const lat = parseFloat(r.lat_reporte);
                const lng = parseFloat(r.long_reporte);
                if (isNaN(lat) || isNaN(lng)) return;
                L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: '',
                        html: '<div class="rapido-report-dot"></div>',
                        iconSize: [8, 8],
                        iconAnchor: [4, 4],
                    }),
                    interactive: false,
                }).addTo(state.reportDotsLayer);
            });
        });
    }

    function renderMapLayers() {
        if (!state.map || !state.heatLayer) return;

        if (window.smartHeatmapInstance && typeof window.smartHeatmapInstance.remove === 'function') {
            window.smartHeatmapInstance.remove();
            window.smartHeatmapInstance = null;
        }

        state.heatLayer.clearLayers();

        const heatSources = window.buildHeatSourcesFromInundaciones
            ? window.buildHeatSourcesFromInundaciones(state.floods)
            : [];

        if (heatSources.length > 0 && window.createSmartHeatmap) {
            window.smartHeatmapInstance = window.createSmartHeatmap(state.map, heatSources, {
                targetLayer: state.heatLayer,
                mode: 'auto',
                ttlHours: 3,
                pane: 'floodFillPane',
            });

            if (window.smartHeatmapInstance) {
                scheduleHeatRedraw();
                if (typeof window.tickFloodHeatTtlPulse === 'function') {
                    window.tickFloodHeatTtlPulse();
                }
            }
        }

        updateHeatLegend();
        renderReportDots();
        state.map.invalidateSize();

        if (state.shouldFitBounds) {
            fitMapToFloods();
            state.shouldFitBounds = false;
        }
    }

    function setupMapResizeObserver() {
        if (state.mapResizeObserver) return;

        const mapWrap = document.querySelector('.rapido-map-wrap');
        if (!mapWrap || !window.ResizeObserver) return;

        state.mapResizeObserver = new ResizeObserver(function () {
            if (!state.map) return;
            state.map.invalidateSize();
            scheduleHeatRedraw();
        });
        state.mapResizeObserver.observe(mapWrap);
    }

    function createMapInstance(lat, lng) {
        state.map = L.map('rapidoMap', { zoomControl: true }).setView([lat, lng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19,
        }).addTo(state.map);

        if (!state.map.getPane('floodFillPane')) {
            state.map.createPane('floodFillPane');
            state.map.getPane('floodFillPane').style.zIndex = 450;
        }

        state.heatLayer = L.layerGroup().addTo(state.map);
        state.reportDotsLayer = L.layerGroup().addTo(state.map);

        const circleLat = state.gpsLat ?? lat;
        const circleLng = state.gpsLng ?? lng;
        state.circle = L.circle([circleLat, circleLng], {
            color: '#3b82f6',
            fillColor: '#3b82f6',
            fillOpacity: 0.08,
            weight: 2,
            radius: MAX_GPS_DISTANCE_M,
        }).addTo(state.map);

        state.marker = L.marker([lat, lng], {
            draggable: true,
            icon: buildUserIcon(),
            zIndexOffset: 1000,
        }).addTo(state.map);

        state.marker.on('drag', function () {
            const pos = state.marker.getLatLng();
            state.reportLat = pos.lat;
            state.reportLng = pos.lng;
            validateMarkerDistance();
        });

        state.map.on('zoomend moveend', function () {
            if (window.smartHeatmapInstance) {
                scheduleHeatRedraw();
            }
        });

        setupMapResizeObserver();
        startHeatPulseTimer();

        state.map.whenReady(function () {
            renderMapLayers();
            setTimeout(function () {
                if (!state.map) return;
                state.map.invalidateSize();
                scheduleHeatRedraw();
            }, 250);
        });
    }

    function initOrUpdateMap() {
        const lat = state.reportLat ?? state.gpsLat ?? -17.7833;
        const lng = state.reportLng ?? state.gpsLng ?? -63.1821;

        if (!state.map) {
            createMapInstance(lat, lng);
            return;
        }

        if (state.gpsLat != null && state.circle) {
            state.circle.setLatLng([state.gpsLat, state.gpsLng]);
        }
        if (state.marker) {
            state.marker.setLatLng([state.reportLat, state.reportLng]);
        }
        renderMapLayers();
    }

    // ── Submit ───────────────────────────────────────────────────────────

    async function submitReport() {
        if (state.submitting || !validateMarkerDistance()) return;

        state.submitting = true;
        if (el.submitBtn) {
            el.submitBtn.disabled = true;
            el.submitBtn.textContent = 'Enviando…';
        }

        try {
            const res = await fetch(cfg.apiStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    user_uuid: cfg.userUuid,
                    lat_gps: state.gpsLat,
                    long_gps: state.gpsLng,
                    lat_reporte: state.reportLat,
                    long_reporte: state.reportLng,
                    intensidad_propuesta: state.intensity,
                }),
            });

            const result = await res.json();

            if (!res.ok) {
                alert('Error: ' + (result.message || 'No se pudo enviar el reporte'));
                state.submitting = false;
                validateMarkerDistance();
                updateSubmitButton();
                return;
            }

            showSuccess(result);
        } catch (_e) {
            alert('Error de conexión. Intenta de nuevo.');
            state.submitting = false;
            validateMarkerDistance();
            updateSubmitButton();
        }
    }

    function showSuccess(result) {
        const reporte = result.reporte || {};
        const eta = result.eta;

        if (el.formSection) el.formSection.classList.add('hidden');
        if (el.successPanel) el.successPanel.classList.remove('hidden');

        if (el.successMessage) {
            el.successMessage.textContent =
                'Reporte enviado correctamente. Gracias por tu colaboración.';
        }

        if (el.successReportId && reporte.id) {
            el.successReportId.textContent = 'Referencia: Reporte #' + reporte.id;
        }

        if (el.successEta && eta && eta.eta_minutes) {
            el.successEta.textContent =
                'Ayuda estimada desde ' +
                (eta.name || 'centro cercano') +
                ': ~' +
                eta.eta_minutes +
                ' min (' +
                (eta.distance_km || '?') +
                ' km)';
            el.successEta.classList.remove('hidden');
        }
    }

    // ── Init ─────────────────────────────────────────────────────────────

    function bindEvents() {
        if (el.btnRefreshGps) el.btnRefreshGps.addEventListener('click', requestGps);
        if (el.submitBtn) el.submitBtn.addEventListener('click', submitReport);

        el.intensityBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setIntensity(btn.dataset.intensity);
            });
        });
    }

    function init() {
        bindEvents();
        updateSubmitButton();

        if (state.floods.length > 0 && state.gpsLat == null) {
            state.shouldFitBounds = true;
            initOrUpdateMap();
        }

        requestGps();
        startRefreshTimer();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
