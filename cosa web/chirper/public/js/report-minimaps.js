(function () {
    if (typeof L === 'undefined') return;

    const reportMinimaps = new Map();
    let minimapMorphTimer = null;
    let observer = null;

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

    function initReportLocationMinimap(containerEl, coords, force) {
        const id = containerEl.id;
        if (!id) return;

        const existing = reportMinimaps.get(id);
        if (!force && existing && containerEl.querySelector('.leaflet-container') && containerEl.isConnected) {
            return existing;
        }

        destroyReportMinimap(id);

        const latRep = parseFloat(coords.latRep);
        const lngRep = parseFloat(coords.lngRep);

        if (isNaN(latRep) || isNaN(lngRep)) {
            containerEl.innerHTML = '<div class="flex items-center justify-center h-full text-[10px] text-slate-400 font-medium px-2 text-center">Sin coordenadas</div>';
            return;
        }

        containerEl.innerHTML = '';

        const map = L.map(containerEl, {
            zoomControl: false,
            attributionControl: false,
            dragging: true,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
            keyboard: false,
        }).setView([latRep, lngRep], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const eventIcon = L.divIcon({
            className: 'custom-leaflet-event',
            html: '<div style="background-color:#f43f5e;width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 0 8px rgba(0,0,0,0.5);"></div>',
            iconSize: [12, 12],
            iconAnchor: [6, 6],
        });
        L.marker([latRep, lngRep], { icon: eventIcon }).addTo(map);

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
    window.destroyReportMinimap = destroyReportMinimap;
    window.initReportLocationMinimap = initReportLocationMinimap;
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
