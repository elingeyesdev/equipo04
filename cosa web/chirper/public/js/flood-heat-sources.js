/**
 * Construcción compartida de heatSources para smart-heatmap.js
 * (reports-map + reporte-rapido).
 */
(function () {
    'use strict';

    window.getInundacionHeatGeometry = function (inundacion) {
        if (!inundacion) return null;

        if (window.resolveUnifiedHeatRing) {
            const unified = window.resolveUnifiedHeatRing(inundacion);
            if (unified && unified.length >= 3) {
                return { polygon_coords: unified, polygon_es_fallback: false };
            }
        }

        if (window.computeInundacionSelectionOutline) {
            const outline = window.computeInundacionSelectionOutline(inundacion);
            if (outline && outline.length >= 3) {
                return { polygon_coords: outline, polygon_es_fallback: false };
            }
        }

        return null;
    };

    function resolveTtlReferenceAt(report) {
        const activeReps = Array.isArray(report.reportes_activos)
            ? report.reportes_activos
            : [];

        let ttlReferenceMs = 0;
        activeReps.forEach(function (rep) {
            const t = new Date(rep.updated_at || rep.created_at).getTime();
            if (!isNaN(t) && t > ttlReferenceMs) {
                ttlReferenceMs = t;
            }
        });

        if (ttlReferenceMs > 0) {
            return new Date(ttlReferenceMs).toISOString();
        }

        if (report.ultima_actividad_at) {
            const ultima = new Date(report.ultima_actividad_at).getTime();
            if (!isNaN(ultima)) {
                return new Date(ultima).toISOString();
            }
        }

        return report.updated_at || null;
    }

    window.buildHeatSourcesFromInundaciones = function (inundaciones) {
        const list = Array.isArray(inundaciones) ? inundaciones : [];
        const heatSources = [];

        list.forEach(function (report) {
            const lat = parseFloat(report.latitud);
            const lng = parseFloat(report.longitud);
            if (isNaN(lat) || isNaN(lng)) return;

            const activeReps = Array.isArray(report.reportes_activos)
                ? report.reportes_activos
                : [];
            const mostrarEnMapa = report.mostrar_en_mapa !== false && activeReps.length > 0;
            if (!mostrarEnMapa) return;

            const intensidad = report.intensidad_calculada || report.intensidad || 'baja';

            const epicenters = activeReps
                .map(function (rep) {
                    return {
                        lat: parseFloat(rep.lat_reporte || rep.latitud),
                        lng: parseFloat(rep.long_reporte || rep.longitud),
                        updated_at: rep.updated_at || rep.created_at,
                    };
                })
                .filter(function (ep) {
                    return !isNaN(ep.lat) && !isNaN(ep.lng);
                });

            const heatGeometry = window.getInundacionHeatGeometry(report);
            const allRepsHavePolygon =
                activeReps.length > 0 &&
                activeReps.every(function (rep) {
                    if (rep.polygon_es_fallback || !rep.polygon_coords) return false;
                    if (!window.normalizePolygonRings) return false;
                    return window.normalizePolygonRings(rep.polygon_coords).length > 0;
                });

            const useTopographicPolygon =
                heatGeometry &&
                heatGeometry.polygon_coords &&
                (allRepsHavePolygon || !heatGeometry.polygon_es_fallback);

            heatSources.push({
                lat: lat,
                lng: lng,
                polygon_coords: useTopographicPolygon ? heatGeometry.polygon_coords : null,
                polygon_es_fallback: heatGeometry ? heatGeometry.polygon_es_fallback : false,
                tier: intensidad,
                intensidad_calculada: intensidad,
                updated_at: resolveTtlReferenceAt(report),
                epicenters: epicenters.length > 0 ? epicenters : undefined,
            });
        });

        return heatSources;
    };
})();
