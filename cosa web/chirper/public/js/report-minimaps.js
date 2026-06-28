(function () {
    if (typeof L === 'undefined') return;

    const reportMinimaps = new Map();
    let minimapMorphTimer = null;
    let observer = null;

    function haversineMeters(lat1, lng1, lat2, lng2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function maxZoomForDistance(meters) {
        if (meters < 30) return 18;
        if (meters < 150) return 17;
        if (meters < 500) return 16;
        if (meters < 2000) return 15;
        return 14;
    }

    function destroyReportMinimap(id) {
        if (reportMinimaps.has(id)) {
            reportMinimaps.get(id).remove();
            reportMinimaps.delete(id);
        }
    }

    function pruneReportMinimaps() {
        reportMinimaps.forEach((map, id) => {
            const el = document.getElementById(id);
            if (!el || !el.isConnected) {
                map.remove();
                reportMinimaps.delete(id);
            }
        });
    }

    function bindWheelZoom(containerEl, map) {
        if (containerEl.dataset.wheelBound) return;
        containerEl.dataset.wheelBound = '1';
        containerEl.addEventListener('mouseenter', () => {
            const m = reportMinimaps.get(containerEl.id);
            if (m) m.scrollWheelZoom.enable();
        });
        containerEl.addEventListener('mouseleave', () => {
            const m = reportMinimaps.get(containerEl.id);
            if (m) m.scrollWheelZoom.disable();
        });
    }

    const tooltipOpts = {
        direction: 'top',
        className: 'text-[10px] font-bold',
        sticky: true,
        offset: [0, -4],
    };

    function initReportLocationMinimap(containerEl, coords, force) {
        const id = containerEl.id;
        if (!id) return;

        const existing = reportMinimaps.get(id);
        if (!force && existing && containerEl.querySelector('.leaflet-container') && containerEl.isConnected) {
            return existing;
        }

        destroyReportMinimap(id);

        const latGps = parseFloat(coords.latGps);
        const lngGps = parseFloat(coords.lngGps);
        const latRep = parseFloat(coords.latRep);
        const lngRep = parseFloat(coords.lngRep);

        const hasUser = !isNaN(latGps) && !isNaN(lngGps);
        const hasEvent = !isNaN(latRep) && !isNaN(lngRep);

        if (!hasUser && !hasEvent) {
            containerEl.innerHTML = '<div class="flex items-center justify-center h-full text-[10px] text-slate-400 font-medium px-2 text-center">Sin coordenadas</div>';
            return;
        }

        containerEl.innerHTML = '';

        const centerLat = hasEvent ? latRep : latGps;
        const centerLng = hasEvent ? lngRep : lngGps;
        let initialZoom = 16;

        if (hasUser && hasEvent) {
            const dist = haversineMeters(latGps, lngGps, latRep, lngRep);
            initialZoom = maxZoomForDistance(dist);
        }

        const map = L.map(containerEl, {
            zoomControl: false,
            attributionControl: false,
            dragging: true,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
            keyboard: false,
        }).setView([centerLat, centerLng], initialZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const bounds = L.latLngBounds();
        let distMeters = 0;

        if (hasUser) {
            const userIcon = L.divIcon({
                className: 'custom-leaflet-user',
                html: '<div style="background-color:#3b82f6;width:10px;height:10px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.4);"></div>',
                iconSize: [10, 10],
                iconAnchor: [5, 5],
            });
            L.marker([latGps, lngGps], { icon: userIcon })
                .bindTooltip('GPS', tooltipOpts)
                .addTo(map);
            bounds.extend([latGps, lngGps]);
        }

        if (hasEvent) {
            const eventIcon = L.divIcon({
                className: 'custom-leaflet-event',
                html: '<div style="background-color:#f43f5e;width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 0 8px rgba(0,0,0,0.5);"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6],
            });
            L.marker([latRep, lngRep], { icon: eventIcon })
                .bindTooltip('Evento', tooltipOpts)
                .addTo(map);
            bounds.extend([latRep, lngRep]);
        }

        if (hasUser && hasEvent && (latGps !== latRep || lngGps !== lngRep)) {
            distMeters = map.distance([latGps, lngGps], [latRep, lngRep]);
            L.polyline([[latGps, lngGps], [latRep, lngRep]], {
                color: '#94a3b8',
                weight: 2,
                dashArray: '4, 4',
            }).addTo(map);

            L.marker([(latGps + latRep) / 2, (lngGps + lngRep) / 2], {
                icon: L.divIcon({
                    className: 'dist-label',
                    html: '<div style="background:rgba(255,255,255,0.9);color:#64748b;font-size:8px;font-weight:bold;padding:1px 3px;border-radius:3px;border:1px solid #cbd5e1;">' + Math.round(distMeters) + 'm</div>',
                    iconSize: [36, 14],
                    iconAnchor: [18, 7],
                }),
            }).addTo(map);
        }

        if (bounds.isValid()) {
            const fitMaxZoom = hasUser && hasEvent ? maxZoomForDistance(distMeters || haversineMeters(latGps, lngGps, latRep, lngRep)) : 16;
            map.fitBounds(bounds, { padding: [12, 12], maxZoom: fitMaxZoom });
        }

        map.scrollWheelZoom.disable();
        bindWheelZoom(containerEl, map);

        reportMinimaps.set(id, map);
        containerEl.dataset.minimapReady = '1';
        setTimeout(() => map.invalidateSize(), 100);

        return map;
    }

    function initMinimapElement(el, force) {
        if (!el.id || el.dataset.minimapReady === '1' && el.querySelector('.leaflet-container') && !force) {
            return;
        }
        initReportLocationMinimap(el, {
            latGps: el.dataset.latGps,
            lngGps: el.dataset.lngGps,
            latRep: el.dataset.latRep,
            lngRep: el.dataset.lngRep,
        }, force);
    }

    function initAllReportMinimaps(force) {
        pruneReportMinimaps();
        document.querySelectorAll('.report-location-minimap').forEach((el) => {
            if (!el.id) return;
            if (!force && reportMinimaps.has(el.id) && el.querySelector('.leaflet-container')) {
                return;
            }
            if (force || !el.querySelector('.leaflet-container')) {
                initMinimapElement(el, force);
            }
        });
    }

    function scheduleMinimapInitForNewOnly() {
        clearTimeout(minimapMorphTimer);
        minimapMorphTimer = setTimeout(() => {
            pruneReportMinimaps();
            document.querySelectorAll('.report-location-minimap').forEach((el) => {
                if (!el.id || el.querySelector('.leaflet-container') || reportMinimaps.has(el.id)) {
                    return;
                }
                if (window.reportMinimapsLazy && observer) {
                    observer.observe(el);
                } else {
                    initMinimapElement(el, false);
                }
            });
        }, 200);
    }

    function setupLazyObserver() {
        if (observer || !('IntersectionObserver' in window)) return;

        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                observer.unobserve(el);
                initMinimapElement(el, false);
            });
        }, { rootMargin: '80px', threshold: 0.01 });

        document.querySelectorAll('.report-location-minimap').forEach((el) => {
            if (el.id && !el.querySelector('.leaflet-container')) {
                observer.observe(el);
            }
        });
    }

    window.initAllReportMinimaps = initAllReportMinimaps;
    window.reportMinimapsLazy = true;

    document.addEventListener('DOMContentLoaded', () => {
        if (window.reportMinimapsLazy) {
            setupLazyObserver();
        } else {
            initAllReportMinimaps(false);
        }
    });

    document.addEventListener('livewire:initialized', () => {
        if (window.reportMinimapsLazy) {
            setupLazyObserver();
        } else {
            initAllReportMinimaps(false);
        }

        if (typeof Livewire !== 'undefined') {
            Livewire.on('refreshReports', () => {
                setTimeout(() => initAllReportMinimaps(true), 100);
            });
            Livewire.on('reporte-ttl-renovado', ({ reporteId }) => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('¡Listo!', 'Reporte #' + reporteId + ' renovado. TTL extendido 3 horas.', 'success');
                }
            });
            Livewire.hook('morph.updated', () => {
                scheduleMinimapInitForNewOnly();
                if (window.reportMinimapsLazy) setupLazyObserver();
            });
        }
    });
})();
