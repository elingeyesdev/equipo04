/**
 * Reporte Rápido — flujo ciudadano anónimo (SGI)
 */
(function () {
    'use strict';

    const cfg = window.RAPIDO_CONFIG || {};
    const MAX_GPS_DISTANCE_M = 500;
    const CAROUSEL_SCROLL_PX = 280;
    const DRAG_THRESHOLD_PX = 5;
    const INTENSITY_LABELS = {
        baja: 'Baja',
        media: 'Media',
        alta: 'Alta',
    };

    const state = {
        floods: Array.isArray(cfg.initialFloods) ? cfg.initialFloods.slice() : [],
        gpsLat: null,
        gpsLng: null,
        reportLat: null,
        reportLng: null,
        intensity: 'baja',
        mode: 'nuevo',
        apoyarFloodId: null,
        map: null,
        marker: null,
        circle: null,
        heatLayer: null,
        reportDotsLayer: null,
        refreshTimer: null,
        heatPulseTimer: null,
        submitting: false,
        carouselDrag: null,
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

    function formatDistance(m) {
        if (m == null || isNaN(m)) return '—';
        if (m < 1000) return Math.round(m) + ' m';
        return (m / 1000).toFixed(1) + ' km';
    }

    function formatRelativeTime(iso) {
        if (!iso) return '';
        const then = new Date(iso).getTime();
        if (isNaN(then)) return '';
        const diffMin = Math.round((Date.now() - then) / 60000);
        if (diffMin < 1) return 'Actualizada hace un momento';
        if (diffMin < 60) return 'Actualizada hace ' + diffMin + ' min';
        const diffH = Math.round(diffMin / 60);
        if (diffH < 24) return 'Actualizada hace ' + diffH + ' h';
        return 'Actualizada hace ' + Math.round(diffH / 24) + ' d';
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

    function reportCountLabel(count) {
        const n = parseInt(count, 10) || 0;
        if (n === 1) return '1 reporte activo';
        return n + ' reportes activos';
    }

    // ── DOM refs ─────────────────────────────────────────────────────────

    const el = {
        gpsDot: document.getElementById('rapidoGpsDot'),
        gpsStatus: document.getElementById('rapidoGpsStatus'),
        btnRefreshGps: document.getElementById('rapidoBtnRefreshGps'),
        alertBanner: document.getElementById('rapidoAlertBanner'),
        modeChip: document.getElementById('rapidoModeChip'),
        modeChipText: document.getElementById('rapidoModeChipText'),
        modeChipDismiss: document.getElementById('rapidoModeChipDismiss'),
        carouselWrap: document.getElementById('rapidoCarouselWrap'),
        carousel: document.getElementById('rapidoCarousel'),
        carouselPrev: document.getElementById('rapidoCarouselPrev'),
        carouselNext: document.getElementById('rapidoCarouselNext'),
        carouselDots: document.getElementById('rapidoCarouselDots'),
        carouselEmpty: document.getElementById('rapidoCarouselEmpty'),
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

    function setGpsStatus(kind, text) {
        if (!el.gpsDot || !el.gpsStatus) return;
        el.gpsDot.className = 'rapido-gps-dot rapido-gps-dot--' + kind;
        el.gpsStatus.textContent = text;
    }

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

        if (state.mode === 'nuevo' || state.reportLat == null) {
            state.reportLat = lat;
            state.reportLng = lng;
        }

        setGpsStatus('active', 'GPS activo · ' + lat.toFixed(5) + ', ' + lng.toFixed(5));
        initOrUpdateMap();
        fetchContext();
        updateAlertBanner();
        validateMarkerDistance();
    }

    function requestGps() {
        if (!navigator.geolocation) {
            setGpsStatus('error', 'GPS no disponible en este dispositivo');
            return;
        }

        setGpsStatus('pending', 'Obteniendo ubicación…');

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                applyGpsPosition(pos.coords.latitude, pos.coords.longitude);
            },
            function () {
                setGpsStatus('error', 'No se pudo obtener GPS. Activa los permisos.');
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
            renderCarousel();
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

    // ── Carousel navigation ──────────────────────────────────────────────

    function getCarouselCards() {
        if (!el.carousel) return [];
        return Array.from(el.carousel.querySelectorAll('.rapido-flood-card'));
    }

    function scrollCarouselBy(delta) {
        if (!el.carousel) return;
        el.carousel.scrollBy({ left: delta, behavior: 'smooth' });
        setTimeout(updateCarouselDots, 350);
    }

    function scrollToCarouselIndex(index) {
        const cards = getCarouselCards();
        if (!cards[index]) return;
        cards[index].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        setTimeout(updateCarouselDots, 350);
    }

    function updateCarouselDots() {
        if (!el.carousel || !el.carouselDots) return;

        const cards = getCarouselCards();
        if (cards.length === 0) {
            el.carouselDots.classList.add('hidden');
            return;
        }

        el.carouselDots.classList.remove('hidden');
        el.carouselDots.innerHTML = '';

        const scrollLeft = el.carousel.scrollLeft;
        let activeIndex = 0;
        let minDist = Infinity;

        cards.forEach(function (card, i) {
            const dist = Math.abs(card.offsetLeft - scrollLeft - el.carousel.clientWidth / 2 + card.offsetWidth / 2);
            if (dist < minDist) {
                minDist = dist;
                activeIndex = i;
            }

            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'rapido-carousel-dot' + (i === activeIndex ? ' is-active' : '');
            dot.setAttribute('aria-label', 'Ir a inundación ' + (i + 1));
            dot.addEventListener('click', function () {
                scrollToCarouselIndex(i);
            });
            el.carouselDots.appendChild(dot);
        });
    }

    function initCarouselNav() {
        if (!el.carousel) return;

        if (el.carouselPrev) {
            el.carouselPrev.addEventListener('click', function () {
                scrollCarouselBy(-CAROUSEL_SCROLL_PX);
            });
        }
        if (el.carouselNext) {
            el.carouselNext.addEventListener('click', function () {
                scrollCarouselBy(CAROUSEL_SCROLL_PX);
            });
        }

        el.carousel.addEventListener('scroll', function () {
            window.requestAnimationFrame(updateCarouselDots);
        });

        el.carousel.addEventListener('pointerdown', function (e) {
            if (e.button !== 0) return;
            state.carouselDrag = {
                startX: e.clientX,
                scrollLeft: el.carousel.scrollLeft,
                dragging: false,
                pointerId: e.pointerId,
            };
        });

        el.carousel.addEventListener('pointermove', function (e) {
            if (!state.carouselDrag || state.carouselDrag.pointerId !== e.pointerId) return;
            const dx = e.clientX - state.carouselDrag.startX;
            if (!state.carouselDrag.dragging && Math.abs(dx) > DRAG_THRESHOLD_PX) {
                state.carouselDrag.dragging = true;
                el.carousel.classList.add('is-dragging');
                el.carousel.setPointerCapture(e.pointerId);
            }
            if (state.carouselDrag.dragging) {
                el.carousel.scrollLeft = state.carouselDrag.scrollLeft - dx;
            }
        });

        function endCarouselDrag(e) {
            if (!state.carouselDrag || state.carouselDrag.pointerId !== e.pointerId) return;
            const wasDragging = state.carouselDrag.dragging;
            state.carouselDrag = null;
            el.carousel.classList.remove('is-dragging');
            if (wasDragging) {
                updateCarouselDots();
            }
        }

        el.carousel.addEventListener('pointerup', endCarouselDrag);
        el.carousel.addEventListener('pointercancel', endCarouselDrag);
    }

    // ── Carousel render ──────────────────────────────────────────────────

    function renderCarousel() {
        if (!el.carousel || !el.carouselEmpty) return;

        const floods = state.floods.filter(function (f) {
            return f.mostrar_en_mapa !== false;
        });

        el.carousel.innerHTML = '';

        if (floods.length === 0) {
            if (el.carouselWrap) el.carouselWrap.classList.add('hidden');
            if (el.carouselDots) el.carouselDots.classList.add('hidden');
            el.carouselEmpty.classList.remove('hidden');
            if (state.gpsLat != null) {
                el.carouselEmpty.textContent = 'No hay inundaciones activas cerca de tu ubicación.';
            } else {
                el.carouselEmpty.textContent = 'Activa el GPS para ver inundaciones cercanas.';
            }
            return;
        }

        if (el.carouselWrap) el.carouselWrap.classList.remove('hidden');
        el.carouselEmpty.classList.add('hidden');

        floods.forEach(function (flood) {
            const intensity = normalizeIntensity(flood.intensidad_calculada);
            const isSelected = state.apoyarFloodId === flood.id;
            const card = document.createElement('button');
            card.type = 'button';
            card.className =
                'rapido-flood-card is-intensity-' +
                intensity +
                (isSelected ? ' is-selected' : '');
            card.dataset.floodId = String(flood.id);

            const distText =
                flood.dentro_contorno === true
                    ? 'Dentro de la zona'
                    : formatDistance(flood.distancia_metros);

            const count = flood.reportes_activos_count ?? (flood.reportes_activos || []).length;
            const statusBadge = flood.esta_confirmada
                ? '<span class="rapido-status-badge rapido-status-badge--confirmada">Confirmada</span>'
                : '<span class="rapido-status-badge rapido-status-badge--validacion">En validación</span>';

            const activityText =
                flood.ultima_actividad_human ||
                formatRelativeTime(flood.ultima_actividad_at) ||
                '';

            card.innerHTML =
                '<div class="flex items-center justify-between gap-2 mb-2">' +
                '<span class="rapido-intensity-badge rapido-intensity-badge--' +
                intensity +
                '">' +
                INTENSITY_LABELS[intensity] +
                '</span>' +
                '<span class="text-xs font-bold text-slate-500">' +
                distText +
                '</span>' +
                '</div>' +
                '<p class="text-sm font-semibold text-slate-800 mb-1">Inundación activa</p>' +
                '<p class="text-xs text-slate-600 mb-1">' +
                reportCountLabel(count) +
                ' · ' +
                statusBadge +
                '</p>' +
                (activityText
                    ? '<p class="text-xs text-slate-400 mb-2">' + activityText + '</p>'
                    : '<p class="text-xs mb-2"></p>') +
                '<span class="text-xs font-bold text-indigo-600">' +
                (isSelected ? 'Cancelar apoyo' : 'Apoyar →') +
                '</span>';

            card.addEventListener('click', function (e) {
                if (state.carouselDrag && state.carouselDrag.dragging) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                if (state.mode === 'apoyar' && state.apoyarFloodId === flood.id) {
                    exitApoyarMode();
                    return;
                }
                enterApoyarMode(flood);
            });

            el.carousel.appendChild(card);
        });

        updateCarouselDots();
    }

    function enterApoyarMode(flood) {
        state.mode = 'apoyar';
        state.apoyarFloodId = flood.id;
        setIntensity(normalizeIntensity(flood.intensidad_calculada));

        if (state.gpsLat != null) {
            state.reportLat = state.gpsLat;
            state.reportLng = state.gpsLng;
            if (state.marker) state.marker.setLatLng([state.reportLat, state.reportLng]);
        }

        if (el.modeChip) {
            el.modeChip.classList.remove('hidden');
            el.modeChipText.textContent = 'Apoyando esta inundación · pendiente de validación';
        }

        renderCarousel();
        validateMarkerDistance();
        updateSubmitButton();
    }

    function exitApoyarMode() {
        state.mode = 'nuevo';
        state.apoyarFloodId = null;
        if (el.modeChip) el.modeChip.classList.add('hidden');
        setIntensity('baja');

        if (state.gpsLat != null) {
            state.reportLat = state.gpsLat;
            state.reportLng = state.gpsLng;
            if (state.marker) state.marker.setLatLng([state.reportLat, state.reportLng]);
        }

        renderCarousel();
        validateMarkerDistance();
        updateSubmitButton();
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

    function updateHeatLegend(heatSources) {
        if (!el.heatLegend) return;
        const hasHeat = Array.isArray(heatSources) && heatSources.length > 0;
        el.heatLegend.classList.toggle('hidden', !hasHeat);
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

        updateHeatLegend(heatSources);

        if (heatSources.length > 0 && window.createSmartHeatmap) {
            window.smartHeatmapInstance = window.createSmartHeatmap(state.map, heatSources, {
                targetLayer: state.heatLayer,
                mode: 'auto',
                ttlHours: 3,
                pane: 'floodFillPane',
            });
            scheduleHeatRedraw();
        }

        renderReportDots();
        state.map.invalidateSize();
    }

    function initOrUpdateMap() {
        const lat = state.reportLat ?? state.gpsLat ?? -17.7833;
        const lng = state.reportLng ?? state.gpsLng ?? -63.1821;

        if (!state.map) {
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

            startHeatPulseTimer();
            renderMapLayers();

            setTimeout(function () {
                if (!state.map) return;
                state.map.invalidateSize();
                scheduleHeatRedraw();
            }, 250);
        } else {
            if (state.gpsLat != null && state.circle) {
                state.circle.setLatLng([state.gpsLat, state.gpsLng]);
            }
            if (state.marker) {
                state.marker.setLatLng([state.reportLat, state.reportLng]);
            }
            renderMapLayers();
        }
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
                state.mode === 'apoyar'
                    ? 'Tu apoyo quedó registrado. Las autoridades lo vincularán en breve.'
                    : 'Reporte enviado correctamente. Gracias por tu colaboración.';
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

        if (el.modeChipDismiss) {
            el.modeChipDismiss.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                exitApoyarMode();
            });
        }

        if (el.submitBtn) el.submitBtn.addEventListener('click', submitReport);

        el.intensityBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setIntensity(btn.dataset.intensity);
            });
        });

        initCarouselNav();
    }

    function init() {
        bindEvents();
        updateSubmitButton();
        renderCarousel();

        if (state.floods.length > 0 && state.gpsLat == null) {
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
