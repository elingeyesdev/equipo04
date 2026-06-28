/**
 * Smart Heatmap - Lógica compartida para mapas de calor
 *
 * El COLOR representa la intensidad de la inundación (baja/media/alta), calculada
 * en el backend por voto ponderado de peso. Cada nivel se pinta como una capa de
 * calor independiente con un azul fijo, así el tono no depende de la densidad de
 * puntos sino de la intensidad real. Inundaciones distintas con distinta intensidad
 * se ven de distinto color; reportes de una misma inundación forman una sola zona.
 *
 * Cada fuente puede declarar `tier` ('baja'|'media'|'alta'); si no, se infiere de
 * su intensidad. La opacidad dentro de la mancha refleja profundidad/TTL.
 *
 * options:
 *   mode: 'auto' | 'geometric' | 'topographic'
 *   ttlHours: number (default 3)
 *   sampleStepM: number (default 12) — paso de grilla dentro del polígono
 *   targetLayer, heatOptions — sin cambios
 */
window.SMART_HEATMAP_TIER_COLORS = {
    baja:  '#7dd3fc',
    media: '#0ea5e9',
    alta:  '#1e3a8a',
};

window.SMART_HEATMAP_OPACITY = {
    gradientPeak: 0.042,
    minFloor: 0.004,
    minCeiling: 0.014,
    heatMax: 5.2,
    tierPeakScale: {
        baja: 0.88,
        media: 0.92,
        alta: 0.28,
    },
    tierHeatMaxScale: {
        baja: 1.0,
        media: 1.0,
        alta: 1.1,
    },
};

const SMART_HEATMAP_TIER_ORDER = ['baja', 'media', 'alta'];

function normalizeTier(value) {
    if (value === 'alta' || value === 'media' || value === 'baja') return value;
    return 'baja';
}

function hexToRgba(hex, alpha) {
    const h = hex.replace('#', '');
    const r = parseInt(h.substring(0, 2), 16);
    const g = parseInt(h.substring(2, 4), 16);
    const b = parseInt(h.substring(4, 6), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
}

function tierPeakScaleFor(tier) {
    const OP = window.SMART_HEATMAP_OPACITY || {};
    const scales = OP.tierPeakScale || {};
    return scales[tier] != null ? scales[tier] : 1.0;
}

function tierHeatMaxScaleFor(tier) {
    const OP = window.SMART_HEATMAP_OPACITY || {};
    const scales = OP.tierHeatMaxScale || {};
    return scales[tier] != null ? scales[tier] : 1.0;
}

function tierGradient(hex, tier) {
    const OP = window.SMART_HEATMAP_OPACITY || {};
    const tierScale = tierPeakScaleFor(tier);
    const peak = (OP.gradientPeak || 0.10) * tierScale;
    return {
        0.0: hexToRgba(hex, 0),
        0.2: hexToRgba(hex, peak * 0.18),
        0.5: hexToRgba(hex, peak * 0.45),
        0.8: hexToRgba(hex, peak * 0.75),
        1.0: hexToRgba(hex, peak),
    };
}

window.createSmartHeatmap = function (map, reports, options = {}) {
    if (!map || !window.L || !window.L.heatLayer) return null;

    const mode = options.mode || 'auto';
    const ttlHours = options.ttlHours || 3;
    const sampleStepM = options.sampleStepM || 12;

    const parsedReports = reports.map(function (r) {
        const lat = parseFloat(r.lat || r.lat_reporte || r.latitud);
        const lng = parseFloat(r.lng || r.long_reporte || r.longitud);
        const esFallback = r.polygon_es_fallback === true || r.polygon_es_fallback === 1;
        const polygonRings = esFallback ? [] : normalizePolygonRings(r.polygon_coords);
        const intensity = r.intensidad || r.intensidad_propuesta || r.intensidad_calculada || 'baja';
        const tier = normalizeTier(r.tier || r.intensidad_calculada || intensity);

        const epicenters = Array.isArray(r.epicenters) && r.epicenters.length > 0
            ? r.epicenters.map(function (ep) {
                return {
                    lat: parseFloat(ep.lat || ep.lat_reporte),
                    lng: parseFloat(ep.lng || ep.long_reporte),
                    updatedAt: ep.updated_at || ep.updatedAt || null,
                };
            }).filter(function (ep) {
                return !isNaN(ep.lat) && !isNaN(ep.lng);
            })
            : [{
                lat: lat,
                lng: lng,
                updatedAt: r.updated_at || r.updatedAt || r.created_at || null,
            }];

        return {
            lat: lat,
            lng: lng,
            tier: tier,
            polygonRings: polygonRings,
            epicenters: epicenters,
            esFallback: esFallback,
            updatedAt: r.updated_at || r.updatedAt || r.created_at || null,
        };
    }).filter(function (r) {
        return !isNaN(r.lat) && !isNaN(r.lng);
    });

    if (parsedReports.length === 0) return null;

    const hasPolygons = parsedReports.some(function (r) {
        return r.polygonRings.length > 0;
    });

    const useTopographic = mode === 'topographic' || (mode === 'auto' && hasPolygons);

    // Puntos de calor agrupados por nivel de intensidad (tier).
    const pointsByTier = { baja: [], media: [], alta: [] };

    function addPoints(tier, pts) {
        if (pts.length > 0) {
            pointsByTier[tier] = pointsByTier[tier].concat(pts);
        }
    }

    if (useTopographic) {
        parsedReports.forEach(function (rep) {
            if (rep.polygonRings.length > 0) {
                rep.polygonRings.forEach(function (ring) {
                    addPoints(rep.tier, samplePolygonHeatPoints(ring, rep.epicenters, sampleStepM, ttlHours));
                });
            } else {
                addPoints(rep.tier, sampleGeometricHeatPoints(rep.lat, rep.lng, rep.tier, rep.updatedAt, ttlHours));
            }
            // Con anillo unificado (1 polígono) los corredores legacy extienden la mancha
            // más allá del contorno de selección; sólo aplicarlos si aún hay multiparte.
            if (rep.epicenters.length >= 2 && rep.polygonRings.length !== 1) {
                addPoints(rep.tier, buildEpicenterCorridors(rep.epicenters, sampleStepM, ttlHours, 400));
            }
        });
    } else {
        parsedReports.forEach(function (rep) {
            addPoints(rep.tier, sampleGeometricHeatPoints(rep.lat, rep.lng, rep.tier, rep.updatedAt, ttlHours));
        });
        SMART_HEATMAP_TIER_ORDER.forEach(function (tier) {
            const tierReports = parsedReports.filter(function (r) { return r.tier === tier; });
            addPoints(tier, buildThermalBridges(tierReports));
        });
    }

    const tiersPresent = SMART_HEATMAP_TIER_ORDER.filter(function (tier) {
        return pointsByTier[tier].length > 0;
    });

    if (tiersPresent.length === 0) return null;

    const avgTtl = parsedReports.reduce(function (sum, r) {
        return sum + ttlFactor(r.updatedAt, ttlHours);
    }, 0) / parsedReports.length;

    // Radio/blur fijos en píxeles: mismo color y opacidad en todo zoom.
    // Leaflet.heat escala el canvas con CSS durante el gesto; no tocar setOptions al zoom.
    // maxZoom bajo (8) anula el factor interno v en zooms urbanos (≥10) → intensidad estable.
    const HEAT_RADIUS_PX = 28;
    const HEAT_BLUR_PX = 22;
    const HEAT_FIXED_MAX_ZOOM = 8;

    const OP = window.SMART_HEATMAP_OPACITY || {
        minFloor: 0.004,
        minCeiling: 0.014,
        heatMax: 5.2,
    };

    function baseMinOpacity(tier) {
        const tierScale = tierPeakScaleFor(tier);
        return Math.max(OP.minFloor, Math.min(OP.minCeiling, 0.006 + 0.022 * avgTtl)) * tierScale;
    }

    function heatMaxForTier(tier) {
        return (OP.heatMax || 5.2) * tierHeatMaxScaleFor(tier);
    }

    const heatDebugState = {
        lastRadius: HEAT_RADIUS_PX,
        lastMax: 0,
    };

    // Una capa de calor por nivel; orden baja→media→alta (alta arriba).
    const heatLayers = [];

    tiersPresent.forEach(function (tier) {
        const color = window.SMART_HEATMAP_TIER_COLORS[tier];
        const tierMax = heatMaxForTier(tier);
        if (tierMax > heatDebugState.lastMax) heatDebugState.lastMax = tierMax;

        const layerOptions = Object.assign({
            radius: HEAT_RADIUS_PX,
            blur: HEAT_BLUR_PX,
            minOpacity: baseMinOpacity(tier),
            max: tierMax,
            maxZoom: HEAT_FIXED_MAX_ZOOM,
        }, options.heatOptions || {});
        // El gradiente por nivel manda; no permitir override del color.
        layerOptions.gradient = tierGradient(color, tier);

        const layer = L.heatLayer(pointsByTier[tier], layerOptions);

        if (options.targetLayer) {
            options.targetLayer.addLayer(layer);
        } else {
            layer.addTo(map);
        }

        heatLayers.push(layer);
    });

    return {
        layers: heatLayers,
        tiers: tiersPresent,
        mode: useTopographic ? 'topographic' : 'geometric',
        get lastRadius() { return heatDebugState.lastRadius; },
        get lastMax() { return heatDebugState.lastMax; },
        remove: function () {
            heatLayers.forEach(function (layer) {
                if (options.targetLayer) {
                    options.targetLayer.removeLayer(layer);
                } else {
                    map.removeLayer(layer);
                }
            });
        },
    };
};

// ── Helpers ────────────────────────────────────────────────────────────────

function isMultiPolygon(coords) {
    if (!coords || !Array.isArray(coords) || coords.length === 0) return false;
    return Array.isArray(coords[0]) && Array.isArray(coords[0][0]);
}

function normalizePolygonRings(coords) {
    if (!coords || !Array.isArray(coords) || coords.length === 0) return [];

    if (isMultiPolygon(coords)) {
        return coords.map(normalizePolygon).filter(function (ring) {
            return ring && ring.length >= 3;
        });
    }

    const ring = normalizePolygon(coords);
    return ring && ring.length >= 3 ? [ring] : [];
}

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

function ttlFactor(updatedAt, ttlHours) {
    if (!updatedAt) return 1;

    const updated = new Date(updatedAt);
    if (isNaN(updated.getTime())) return 1;

    const ttlMs = ttlHours * 3600000;
    const remaining = (updated.getTime() + ttlMs - Date.now()) / ttlMs;

    if (remaining <= 0) return 0;

    return Math.min(1, remaining);
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

function nearestEpicenter(lat, lng, epicenters) {
    let nearest = epicenters[0];
    let minDist = Infinity;

    epicenters.forEach(function (ep) {
        const dist = haversineM(lat, lng, ep.lat, ep.lng);
        if (dist < minDist) {
            minDist = dist;
            nearest = ep;
        }
    });

    return { epicenter: nearest, distance: minDist };
}

/**
 * Muestrea puntos dentro del polígono. El peso = profundidad estimada × TTL
 * (NO intensidad: el color lo define la capa por nivel). Centro más sólido,
 * bordes difuminados, para una mancha suave.
 */
function samplePolygonHeatPoints(polygon, epicenters, stepM, ttlHours) {
    const lats = polygon.map(function (p) { return p[0]; });
    const lngs = polygon.map(function (p) { return p[1]; });
    const minLat = Math.min.apply(null, lats);
    const maxLat = Math.max.apply(null, lats);
    const minLng = Math.min.apply(null, lngs);
    const maxLng = Math.max.apply(null, lngs);

    const centerLat = (minLat + maxLat) / 2;
    const cosLat = Math.cos(centerLat * Math.PI / 180);
    const stepLat = stepM / 111320;
    const stepLng = stepM / (111320 * Math.max(cosLat, 0.01));

    const maxDist = Math.max.apply(null, epicenters.map(function (ep) {
        return Math.max.apply(null, polygon.map(function (p) {
            return haversineM(ep.lat, ep.lng, p[0], p[1]);
        }));
    }));

    const points = [];

    for (let lat = minLat; lat <= maxLat; lat += stepLat) {
        for (let lng = minLng; lng <= maxLng; lng += stepLng) {
            if (!pointInPolygon(lat, lng, polygon)) continue;

            const nearest = nearestEpicenter(lat, lng, epicenters);
            const ttl = ttlFactor(nearest.epicenter.updatedAt, ttlHours);
            const depthFactor = Math.max(0.25, 1 - 0.7 * (nearest.distance / (maxDist || stepM)));
            const weight = Math.min(0.28, depthFactor * ttl);

            if (weight > 0.02) {
                points.push([lat, lng, weight]);
            }
        }
    }

    return points;
}

/**
 * Mancha suave circular cuando no hay polígono topográfico (fallback geométrico).
 * El peso = profundidad × TTL; el color lo define la capa por nivel.
 */
function sampleGeometricHeatPoints(lat, lng, tier, updatedAt, ttlHours) {
    const radiusM = tier === 'alta' ? 130 : (tier === 'media' ? 90 : 55);
    const stepM = 10;
    const ttl = ttlFactor(updatedAt, ttlHours);
    const points = [];
    const cosLat = Math.cos(lat * Math.PI / 180);
    const stepLat = stepM / 111320;
    const stepLng = stepM / (111320 * Math.max(cosLat, 0.01));
    const latDelta = radiusM / 111320;
    const lngDelta = radiusM / (111320 * Math.max(cosLat, 0.01));

    for (let dLat = -latDelta; dLat <= latDelta; dLat += stepLat) {
        for (let dLng = -lngDelta; dLng <= lngDelta; dLng += stepLng) {
            const dist = haversineM(lat, lng, lat + dLat, lng + dLng);
            if (dist > radiusM) continue;

            const falloff = Math.max(0.2, 1 - Math.pow(dist / radiusM, 1.4));
            const weight = falloff * ttl;

            if (weight > 0.02) {
                points.push([lat + dLat, lng + dLng, weight]);
            }
        }
    }

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

/**
 * Corredores de calor entre epicentros (fallback visual para datos legacy multiparte).
 * Muestrea una cápsula suave entre pares de epicentros dentro de maxDistM.
 */
function buildEpicenterCorridors(epicenters, stepM, ttlHours, maxDistM) {
    const corridors = [];
    if (!Array.isArray(epicenters) || epicenters.length < 2) return corridors;

    const maxDist = maxDistM || 400;
    const corridorHalfWidthM = Math.max(stepM * 2, 20);

    for (let i = 0; i < epicenters.length; i++) {
        for (let j = i + 1; j < epicenters.length; j++) {
            const ep1 = epicenters[i];
            const ep2 = epicenters[j];
            const lat1 = parseFloat(ep1.lat);
            const lng1 = parseFloat(ep1.lng);
            const lat2 = parseFloat(ep2.lat);
            const lng2 = parseFloat(ep2.lng);

            if (isNaN(lat1) || isNaN(lng1) || isNaN(lat2) || isNaN(lng2)) continue;

            const dist = haversineM(lat1, lng1, lat2, lng2);
            if (dist <= 10 || dist > maxDist) continue;

            const ttl = Math.min(
                ttlFactor(ep1.updatedAt || ep1.updated_at, ttlHours),
                ttlFactor(ep2.updatedAt || ep2.updated_at, ttlHours)
            );
            if (ttl <= 0) continue;

            const steps = Math.max(3, Math.floor(dist / Math.max(8, stepM / 2)));
            const centerLat = (lat1 + lat2) / 2;
            const cosLat = Math.cos(centerLat * Math.PI / 180);
            const perpLat = -(lng2 - lng1);
            const perpLng = (lat2 - lat1);
            const perpLen = Math.sqrt(perpLat * perpLat + perpLng * perpLng) || 1;
            const offLat = (perpLat / perpLen) * (corridorHalfWidthM / 111320);
            const offLng = (perpLng / perpLen) * (corridorHalfWidthM / (111320 * Math.max(cosLat, 0.01)));

            for (let k = 0; k <= steps; k++) {
                const t = k / steps;
                const baseLat = lat1 + (lat2 - lat1) * t;
                const baseLng = lng1 + (lng2 - lng1) * t;
                const alongFactor = Math.max(0.35, 1 - Math.abs(t - 0.5) * 1.2);
                const weight = alongFactor * ttl * 0.55;

                if (weight > 0.02) {
                    corridors.push([baseLat, baseLng, weight]);
                    corridors.push([baseLat + offLat, baseLng + offLng, weight * 0.75]);
                    corridors.push([baseLat - offLat, baseLng - offLng, weight * 0.75]);
                }
            }
        }
    }

    return corridors;
}

// Export helpers for reports-map
window.normalizePolygonRings = normalizePolygonRings;
window.isMultiPolygon = isMultiPolygon;
