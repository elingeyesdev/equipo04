/**
 * Smart Flood Visual Layer — mapa de calor rasterizado (Canvas + ImageOverlay)
 *
 * El COLOR representa la intensidad de la inundación (baja/media/alta). Degradado
 * continuo desde epicentros hacia bordes; bordes suavizados con blur. Estable al
 * zoom (sin Leaflet.heat ni repintados en cada notch).
 *
 * options:
 *   mode: 'auto' | 'geometric' | 'topographic'
 *   ttlHours: number (default 3)
 *   targetLayer: L.layerGroup
 *   pane: string (default 'overlayPane')
 */
window.SMART_HEATMAP_TIER_COLORS = {
    baja:  '#7dd3fc',
    media: '#0ea5e9',
    alta:  '#1e3a8a',
};

window.SMART_FLOOD_FILL = {
    edgeMin: { baja: 0.92, media: 0.96, alta: 1.0 },
    coreMax: { baja: 1.0, media: 1.0, alta: 1.0 },
    blurPx: 2,
    sampleStepM: 10,
    edgeFeatherM: 10,
    coreFactorFloor: 0.78,
    edgeFactorFloor: 0.72,
    chaikinIterations: 2,
    geometricRadiusM: { baja: 55, media: 90, alta: 130 },
    maxCanvasPx: 512,
};

/** @deprecated Mantener alias para compatibilidad con código legacy */
window.SMART_HEATMAP_OPACITY = window.SMART_FLOOD_FILL;

const SMART_HEATMAP_TIER_ORDER = ['baja', 'media', 'alta'];

function normalizeTier(value) {
    if (value === 'alta' || value === 'media' || value === 'baja') return value;
    return 'baja';
}

function tierRadiusM(tier) {
    const radii = window.SMART_FLOOD_FILL.geometricRadiusM || {};
    return radii[tier] != null ? radii[tier] : 55;
}

function hexToRgb(hex) {
    const h = hex.replace('#', '');
    return [
        parseInt(h.substring(0, 2), 16),
        parseInt(h.substring(2, 4), 16),
        parseInt(h.substring(4, 6), 16),
    ];
}

function chaikinSmoothRing(ring, iterations) {
    if (window.chaikinSmoothRing) {
        return window.chaikinSmoothRing(ring, iterations);
    }

    iterations = iterations || 2;
    let pts = ring.slice();
    if (pts.length < 3) return pts;

    if (pts[0][0] === pts[pts.length - 1][0] && pts[0][1] === pts[pts.length - 1][1]) {
        pts = pts.slice(0, -1);
    }

    for (let iter = 0; iter < iterations; iter++) {
        const next = [];
        const n = pts.length;
        for (let i = 0; i < n; i++) {
            const p0 = pts[i];
            const p1 = pts[(i + 1) % n];
            next.push([0.75 * p0[0] + 0.25 * p1[0], 0.75 * p0[1] + 0.25 * p1[1]]);
            next.push([0.25 * p0[0] + 0.75 * p1[0], 0.25 * p0[1] + 0.75 * p1[1]]);
        }
        pts = next;
    }
    return pts;
}

function ringBounds(ring, marginM) {
    marginM = marginM || 0;
    let minLat = Infinity;
    let maxLat = -Infinity;
    let minLng = Infinity;
    let maxLng = -Infinity;

    ring.forEach(function (p) {
        minLat = Math.min(minLat, p[0]);
        maxLat = Math.max(maxLat, p[0]);
        minLng = Math.min(minLng, p[1]);
        maxLng = Math.max(maxLng, p[1]);
    });

    const centerLat = (minLat + maxLat) / 2;
    const cosLat = Math.max(Math.cos(centerLat * Math.PI / 180), 0.01);
    const dLat = marginM / 111320;
    const dLng = marginM / (111320 * cosLat);

    return {
        minLat: minLat - dLat,
        maxLat: maxLat + dLat,
        minLng: minLng - dLng,
        maxLng: maxLng + dLng,
        centerLat: centerLat,
    };
}

function distPointToSegmentM(lat, lng, aLat, aLng, bLat, bLng) {
    const cosLat = Math.cos(lat * Math.PI / 180);
    const mPerDegLat = 111320;
    const mPerDegLng = 111320 * Math.max(cosLat, 0.01);

    const px = lng * mPerDegLng;
    const py = lat * mPerDegLat;
    const ax = aLng * mPerDegLng;
    const ay = aLat * mPerDegLat;
    const bx = bLng * mPerDegLng;
    const by = bLat * mPerDegLat;

    const dx = bx - ax;
    const dy = by - ay;
    const lenSq = dx * dx + dy * dy;

    if (lenSq === 0) {
        return Math.hypot(px - ax, py - ay);
    }

    let t = ((px - ax) * dx + (py - ay) * dy) / lenSq;
    t = Math.max(0, Math.min(1, t));

    const cx = ax + t * dx;
    const cy = ay + t * dy;
    return Math.hypot(px - cx, py - cy);
}

function distanceToPolygonBorder(lat, lng, ring) {
    let minDist = Infinity;

    for (let i = 0; i < ring.length; i++) {
        const j = (i + 1) % ring.length;
        minDist = Math.min(
            minDist,
            distPointToSegmentM(lat, lng, ring[i][0], ring[i][1], ring[j][0], ring[j][1])
        );
    }

    return minDist;
}

function nearestEpicenterDist(lat, lng, epicenters) {
    let minDist = Infinity;

    epicenters.forEach(function (ep) {
        minDist = Math.min(minDist, haversineM(lat, lng, ep.lat, ep.lng));
    });

    return minDist;
}

function maxEpicenterSpread(ring, epicenters) {
    let maxD = 20;

    epicenters.forEach(function (ep) {
        ring.forEach(function (v) {
            maxD = Math.max(maxD, haversineM(ep.lat, ep.lng, v[0], v[1]));
        });
    });

    return maxD;
}

function computeHeatAlpha(lat, lng, tier, ttl, fillCfg, epicenters, ring, maxSpread) {
    const edgeMin = (fillCfg.edgeMin[tier] || 0.18) * ttl;
    const coreMax = (fillCfg.coreMax[tier] || 0.34) * ttl;
    const featherM = fillCfg.edgeFeatherM || 10;
    const coreFloor = fillCfg.coreFactorFloor || 0.78;
    const edgeFloor = fillCfg.edgeFactorFloor || 0.72;

    const distEp = nearestEpicenterDist(lat, lng, epicenters);
    const coreFactor = Math.max(coreFloor, 1 - 0.55 * (distEp / maxSpread));
    let alpha = edgeMin + coreFactor * (coreMax - edgeMin);

    if (ring) {
        const distBorder = distanceToPolygonBorder(lat, lng, ring);
        const edgeFactor = Math.min(1, Math.max(edgeFloor, distBorder / Math.max(featherM, 6)));
        alpha *= edgeFactor;
    }

    return Math.max(0, Math.min(1, alpha));
}

function applyCanvasBlur(sourceCanvas, blurPx) {
    if (!blurPx || blurPx <= 0) {
        return sourceCanvas;
    }

    const pad = Math.ceil(blurPx * 2);
    const w = sourceCanvas.width + pad * 2;
    const h = sourceCanvas.height + pad * 2;
    const out = document.createElement('canvas');
    out.width = w;
    out.height = h;
    const ctx = out.getContext('2d');
    ctx.filter = 'blur(' + blurPx + 'px)';
    ctx.drawImage(sourceCanvas, pad, pad);
    ctx.filter = 'none';

    const cropped = document.createElement('canvas');
    cropped.width = sourceCanvas.width;
    cropped.height = sourceCanvas.height;
    const cropCtx = cropped.getContext('2d');
    cropCtx.drawImage(out, pad, pad, sourceCanvas.width, sourceCanvas.height, 0, 0, sourceCanvas.width, sourceCanvas.height);
    return cropped;
}

function buildHeatRaster(ring, epicenters, tier, ttl, fillCfg) {
    const smoothRing = chaikinSmoothRing(ring, fillCfg.chaikinIterations || 2);
    const marginM = (fillCfg.edgeFeatherM || 30) + (fillCfg.blurPx || 7) * 2;
    const bounds = ringBounds(smoothRing, marginM);
    const maxSpread = maxEpicenterSpread(smoothRing, epicenters);
    const rgb = hexToRgb(window.SMART_HEATMAP_TIER_COLORS[tier]);

    const latSpanM = haversineM(bounds.minLat, bounds.minLng, bounds.maxLat, bounds.minLng);
    const lngSpanM = haversineM(bounds.centerLat, bounds.minLng, bounds.centerLat, bounds.maxLng);
    const stepM = fillCfg.sampleStepM || 10;
    const maxPx = fillCfg.maxCanvasPx || 512;

    let cols = Math.max(16, Math.ceil(lngSpanM / stepM));
    let rows = Math.max(16, Math.ceil(latSpanM / stepM));
    const scale = Math.min(1, maxPx / Math.max(cols, rows));

    cols = Math.max(16, Math.round(cols * scale));
    rows = Math.max(16, Math.round(rows * scale));

    const canvas = document.createElement('canvas');
    canvas.width = cols;
    canvas.height = rows;
    const ctx = canvas.getContext('2d');
    const imageData = ctx.createImageData(cols, rows);
    const data = imageData.data;

    for (let row = 0; row < rows; row++) {
        const lat = bounds.maxLat - (row / Math.max(rows - 1, 1)) * (bounds.maxLat - bounds.minLat);

        for (let col = 0; col < cols; col++) {
            const lng = bounds.minLng + (col / Math.max(cols - 1, 1)) * (bounds.maxLng - bounds.minLng);

            if (!pointInPolygon(lat, lng, smoothRing)) continue;

            const alpha = computeHeatAlpha(lat, lng, tier, ttl, fillCfg, epicenters, smoothRing, maxSpread);
            if (alpha <= 0.004) continue;

            const idx = (row * cols + col) * 4;
            data[idx] = rgb[0];
            data[idx + 1] = rgb[1];
            data[idx + 2] = rgb[2];
            data[idx + 3] = Math.round(alpha * 255);
        }
    }

    ctx.putImageData(imageData, 0, 0);
    const blurred = applyCanvasBlur(canvas, fillCfg.blurPx || 7);

    return {
        dataUrl: blurred.toDataURL('image/png'),
        bounds: [[bounds.minLat, bounds.minLng], [bounds.maxLat, bounds.maxLng]],
    };
}

function buildRadialHeatRaster(lat, lng, tier, ttl, fillCfg) {
    const radiusM = tierRadiusM(tier);
    const marginM = radiusM * 0.15 + (fillCfg.blurPx || 7) * 2;
    const cosLat = Math.cos(lat * Math.PI / 180);
    const dLat = (radiusM + marginM) / 111320;
    const dLng = (radiusM + marginM) / (111320 * Math.max(cosLat, 0.01));

    const bounds = {
        minLat: lat - dLat,
        maxLat: lat + dLat,
        minLng: lng - dLng,
        maxLng: lng + dLng,
        centerLat: lat,
    };

    const rgb = hexToRgb(window.SMART_HEATMAP_TIER_COLORS[tier]);
    const edgeMin = (fillCfg.edgeMin[tier] || 0.18) * ttl;
    const coreMax = (fillCfg.coreMax[tier] || 0.34) * ttl;
    const featherM = fillCfg.edgeFeatherM || 30;
    const stepM = fillCfg.sampleStepM || 10;
    const maxPx = fillCfg.maxCanvasPx || 512;

    let cols = Math.max(16, Math.ceil((radiusM + marginM) * 2 / stepM));
    let rows = cols;
    const scale = Math.min(1, maxPx / cols);
    cols = Math.max(16, Math.round(cols * scale));
    rows = cols;

    const canvas = document.createElement('canvas');
    canvas.width = cols;
    canvas.height = rows;
    const ctx = canvas.getContext('2d');
    const imageData = ctx.createImageData(cols, rows);
    const data = imageData.data;

    for (let row = 0; row < rows; row++) {
        const pLat = bounds.maxLat - (row / Math.max(rows - 1, 1)) * (bounds.maxLat - bounds.minLat);

        for (let col = 0; col < cols; col++) {
            const pLng = bounds.minLng + (col / Math.max(cols - 1, 1)) * (bounds.maxLng - bounds.minLng);
            const dist = haversineM(lat, lng, pLat, pLng);

            if (dist > radiusM + marginM * 0.5) continue;

            const coreFloor = fillCfg.coreFactorFloor || 0.78;
            const edgeFloor = fillCfg.edgeFactorFloor || 0.72;
            const coreFactor = Math.max(coreFloor, 1 - 0.55 * (dist / radiusM));
            const edgeFactor = dist <= radiusM
                ? Math.min(1, Math.max(edgeFloor, (radiusM - dist) / featherM + edgeFloor))
                : Math.max(edgeFloor * 0.5, 1 - (dist - radiusM) / marginM);

            let alpha = (edgeMin + coreFactor * (coreMax - edgeMin)) * Math.min(1, edgeFactor);
            if (alpha <= 0.004) continue;

            const idx = (row * cols + col) * 4;
            data[idx] = rgb[0];
            data[idx + 1] = rgb[1];
            data[idx + 2] = rgb[2];
            data[idx + 3] = Math.round(alpha * 255);
        }
    }

    ctx.putImageData(imageData, 0, 0);
    const blurred = applyCanvasBlur(canvas, fillCfg.blurPx || 7);

    return {
        dataUrl: blurred.toDataURL('image/png'),
        bounds: [[bounds.minLat, bounds.minLng], [bounds.maxLat, bounds.maxLng]],
    };
}

function createHeatOverlay(map, raster, options) {
    return L.imageOverlay(raster.dataUrl, raster.bounds, {
        pane: options.pane || 'overlayPane',
        interactive: false,
        opacity: 1,
        className: 'flood-heat-overlay',
    });
}

window.createSmartHeatmap = function (map, reports, options = {}) {
    if (!map || !window.L) return null;

    const mode = options.mode || 'auto';
    const ttlHours = options.ttlHours || 3;
    const fillCfg = window.SMART_FLOOD_FILL || {};

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
    const layersByTier = { baja: [], media: [], alta: [] };

    parsedReports.forEach(function (rep) {
        const ttl = ttlFactor(rep.updatedAt, ttlHours);
        if (ttl <= 0) return;

        if (useTopographic && rep.polygonRings.length > 0) {
            rep.polygonRings.forEach(function (ring) {
                const ringRaster = buildHeatRaster(ring, rep.epicenters, rep.tier, ttl, fillCfg);
                if (ringRaster) {
                    layersByTier[rep.tier].push(createHeatOverlay(map, ringRaster, options));
                }
            });
        } else {
            const radialRaster = buildRadialHeatRaster(rep.lat, rep.lng, rep.tier, ttl, fillCfg);
            if (radialRaster) {
                layersByTier[rep.tier].push(createHeatOverlay(map, radialRaster, options));
            }
        }
    });

    const tiersPresent = SMART_HEATMAP_TIER_ORDER.filter(function (tier) {
        return layersByTier[tier].length > 0;
    });

    if (tiersPresent.length === 0) return null;

    const debugOpacity = {};
    tiersPresent.forEach(function (tier) {
        debugOpacity[tier] = {
            edge: fillCfg.edgeMin[tier] || 0.18,
            core: fillCfg.coreMax[tier] || 0.34,
        };
    });

    const tierGroups = [];

    tiersPresent.forEach(function (tier) {
        const group = L.layerGroup(layersByTier[tier]);

        if (options.targetLayer) {
            options.targetLayer.addLayer(group);
        } else {
            group.addTo(map);
        }

        tierGroups.push(group);
    });

    return {
        layers: tierGroups,
        tiers: tiersPresent,
        mode: useTopographic ? 'topographic' : 'geometric',
        debugOpacity: debugOpacity,
        get lastRadius() { return null; },
        get lastMax() { return null; },
        remove: function () {
            tierGroups.forEach(function (group) {
                if (options.targetLayer) {
                    options.targetLayer.removeLayer(group);
                } else {
                    map.removeLayer(group);
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

window.normalizePolygonRings = normalizePolygonRings;
window.isMultiPolygon = isMultiPolygon;
