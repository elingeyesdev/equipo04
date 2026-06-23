/**
 * Smart Heatmap - Lógica compartida para mapas de calor
 *
 * Modos:
 *   - geometric: puntos en epicentros + puentes térmicos (comportamiento original)
 *   - topographic: muestreo dentro de polygon_coords con peso por profundidad estimada
 *   - auto (default): topographic si algún reporte tiene polígono, si no geometric
 *
 * options:
 *   mode: 'auto' | 'geometric' | 'topographic'
 *   ttlHours: number (default 3)
 *   sampleStepM: number (default 20) — paso de grilla dentro del polígono
 *   targetLayer, heatOptions — sin cambios
 */
window.createSmartHeatmap = function (map, reports, options = {}) {
    if (!map || !window.L || !window.L.heatLayer) return null;

    const mode = options.mode || 'auto';
    const ttlHours = options.ttlHours || 3;
    const sampleStepM = options.sampleStepM || 20;

    const parsedReports = reports.map(function (r) {
        const lat = parseFloat(r.lat || r.lat_reporte || r.latitud);
        const lng = parseFloat(r.lng || r.long_reporte || r.longitud);
        const esFallback = r.polygon_es_fallback === true || r.polygon_es_fallback === 1;
        const polygon = esFallback ? null : normalizePolygon(r.polygon_coords);

        return {
            lat: lat,
            lng: lng,
            intensity: r.intensidad || r.intensidad_propuesta || r.intensidad_calculada || 'baja',
            polygon: polygon,
            esFallback: esFallback,
            updatedAt: r.updated_at || r.updatedAt || r.created_at || null,
        };
    }).filter(function (r) {
        return !isNaN(r.lat) && !isNaN(r.lng);
    });

    if (parsedReports.length === 0) return null;

    const hasPolygons = parsedReports.some(function (r) {
        return r.polygon && r.polygon.length >= 3;
    });

    const useTopographic = mode === 'topographic' || (mode === 'auto' && hasPolygons);

    let heatPoints = [];

    if (useTopographic) {
        parsedReports.forEach(function (rep) {
            if (rep.polygon && rep.polygon.length >= 3) {
                heatPoints = heatPoints.concat(
                    samplePolygonHeatPoints(rep, sampleStepM, ttlHours)
                );
            } else {
                heatPoints.push(buildCenterPoint(rep, ttlHours));
            }
        });

        // Puentes térmicos solo entre reportes sin polígono topográfico
        const geometricOnly = parsedReports.filter(function (r) {
            return !r.polygon || r.polygon.length < 3;
        });
        heatPoints = heatPoints.concat(buildThermalBridges(geometricOnly));
    } else {
        parsedReports.forEach(function (rep) {
            heatPoints.push(buildCenterPoint(rep, ttlHours));
        });
        heatPoints = heatPoints.concat(buildThermalBridges(parsedReports));
    }

    if (heatPoints.length === 0) return null;

    const avgTtl = parsedReports.reduce(function (sum, r) {
        return sum + ttlFactor(r.updatedAt, ttlHours);
    }, 0) / parsedReports.length;

    let initialZoom = map.getZoom();
    let initialRadius = Math.max(12, Math.round(35 * Math.pow(1.5, initialZoom - 16)));
    let initialBlur = Math.max(10, Math.round(initialRadius * 0.8));

    const defaultHeatOptions = {
        radius: initialRadius,
        blur: initialBlur,
        minOpacity: Math.max(0.2, Math.min(0.65, 0.25 + 0.4 * avgTtl)),
        maxZoom: 18,
        gradient: {
            0.2: '#38bdf8',
            0.5: '#2563eb',
            1.0: '#1e3a8a',
        },
    };

    const heatLayer = L.heatLayer(heatPoints, Object.assign(
        defaultHeatOptions,
        options.heatOptions || {}
    ));

    if (options.targetLayer) {
        options.targetLayer.addLayer(heatLayer);
    } else {
        heatLayer.addTo(map);
    }

    const zoomListener = function () {
        let zoom = map.getZoom();
        let newRadius = Math.max(12, Math.round(35 * Math.pow(1.5, zoom - 16)));
        let newBlur = Math.max(10, Math.round(newRadius * 0.8));
        heatLayer.setOptions({ radius: newRadius, blur: newBlur });
    };

    map.on('zoomend', zoomListener);

    return {
        layer: heatLayer,
        mode: useTopographic ? 'topographic' : 'geometric',
        remove: function () {
            if (options.targetLayer) {
                options.targetLayer.removeLayer(heatLayer);
            } else {
                map.removeLayer(heatLayer);
            }
            map.off('zoomend', zoomListener);
        },
    };
};

// ── Helpers ────────────────────────────────────────────────────────────────

function normalizePolygon(coords) {
    if (!coords || !Array.isArray(coords) || coords.length < 3) return null;

    return coords.map(function (p) {
        if (Array.isArray(p)) {
            return [parseFloat(p[0]), parseFloat(p[1])];
        }
        if (p && typeof p === 'object') {
            return [parseFloat(p.lat ?? p.latitude), parseFloat(p.lng ?? p.longitude)];
        }
        return null;
    }).filter(function (p) {
        return p && !isNaN(p[0]) && !isNaN(p[1]);
    });
}

function intensityWeight(intensity) {
    if (intensity === 'alta') return 1.0;
    if (intensity === 'media') return 0.6;
    return 0.3;
}

function ttlFactor(updatedAt, ttlHours) {
    if (!updatedAt) return 1;

    const updated = new Date(updatedAt);
    if (isNaN(updated.getTime())) return 1;

    const ttlMs = ttlHours * 3600000;
    const remaining = (updated.getTime() + ttlMs - Date.now()) / ttlMs;

    return Math.max(0.12, Math.min(1, remaining));
}

function haversineM(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
        * Math.sin(dLng / 2) * Math.sin(dLng / 2);

    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function pointInPolygon(lat, lng, polygon) {
    let inside = false;

    for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
        const yi = polygon[i][0];
        const xi = polygon[i][1];
        const yj = polygon[j][0];
        const xj = polygon[j][1];

        if (((yi > lat) !== (yj > lat))
            && (lng < (xj - xi) * (lat - yi) / (yj - yi) + xi)) {
            inside = !inside;
        }
    }

    return inside;
}

function buildCenterPoint(rep, ttlHours) {
    const weight = intensityWeight(rep.intensity) * ttlFactor(rep.updatedAt, ttlHours);
    return [rep.lat, rep.lng, weight];
}

function samplePolygonHeatPoints(rep, stepM, ttlHours) {
    const polygon = rep.polygon;
    const lats = polygon.map(function (p) { return p[0]; });
    const lngs = polygon.map(function (p) { return p[1]; });
    const minLat = Math.min.apply(null, lats);
    const maxLat = Math.max.apply(null, lats);
    const minLng = Math.min.apply(null, lngs);
    const maxLng = Math.max.apply(null, lngs);

    const cosLat = Math.cos(rep.lat * Math.PI / 180);
    const stepLat = stepM / 111320;
    const stepLng = stepM / (111320 * Math.max(cosLat, 0.01));

    const maxDist = Math.max.apply(null, polygon.map(function (p) {
        return haversineM(rep.lat, rep.lng, p[0], p[1]);
    }));

    const baseWeight = intensityWeight(rep.intensity);
    const ttl = ttlFactor(rep.updatedAt, ttlHours);
    const points = [];

    for (let lat = minLat; lat <= maxLat; lat += stepLat) {
        for (let lng = minLng; lng <= maxLng; lng += stepLng) {
            if (!pointInPolygon(lat, lng, polygon)) continue;

            const dist = haversineM(rep.lat, rep.lng, lat, lng);
            const depthFactor = Math.max(0.2, 1 - 0.75 * (dist / (maxDist || stepM)));
            const weight = baseWeight * depthFactor * ttl;

            if (weight > 0.04) {
                points.push([lat, lng, weight]);
            }
        }
    }

    // Epicentro con profundidad máxima estimada
    points.push([rep.lat, rep.lng, baseWeight * ttl]);

    return points;
}

function buildThermalBridges(reports) {
    const bridges = [];

    if (reports.length < 2) return bridges;

    for (let i = 0; i < reports.length; i++) {
        for (let j = i + 1; j < reports.length; j++) {
            const p1 = L.latLng(reports[i].lat, reports[i].lng);
            const p2 = L.latLng(reports[j].lat, reports[j].lng);
            const dist = p1.distanceTo(p2);

            if (dist > 10 && dist <= 250) {
                const steps = Math.floor(dist / 15);
                const bridgeWeight = 0.35 * Math.min(
                    ttlFactor(reports[i].updatedAt, 3),
                    ttlFactor(reports[j].updatedAt, 3)
                );

                for (let k = 1; k < steps; k++) {
                    const fraction = k / steps;
                    bridges.push([
                        p1.lat + (p2.lat - p1.lat) * fraction,
                        p1.lng + (p2.lng - p1.lng) * fraction,
                        bridgeWeight,
                    ]);
                }
            }
        }
    }

    return bridges;
}
