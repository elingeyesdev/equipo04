/**
 * Smart Flood Visual Layer — mapa de calor rasterizado (Canvas + ImageOverlay)
 *
 * Una capa raster por inundación; opacidad estable al añadir reportes (max alpha, sin apilar).
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
const ALPHA_VISIBLE_THRESHOLD = 0.004;

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

function boundsFromBoxes(boxes) {
    let minLat = Infinity;
    let maxLat = -Infinity;
    let minLng = Infinity;
    let maxLng = -Infinity;

    boxes.forEach(function (b) {
        minLat = Math.min(minLat, b.minLat);
        maxLat = Math.max(maxLat, b.maxLat);
        minLng = Math.min(minLng, b.minLng);
        maxLng = Math.max(maxLng, b.maxLng);
    });

    return {
        minLat: minLat,
        maxLat: maxLat,
        minLng: minLng,
        maxLng: maxLng,
        centerLat: (minLat + maxLat) / 2,
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

/**
 * Opacidad dentro del polígono: solo feather hacia el borde (sin depender de epicentros).
 * Así añadir reportes no oscurece el interior existente.
 */
function computePolygonInteriorAlpha(lat, lng, tier, ttl, fillCfg, ring) {
    const edgeMin = (fillCfg.edgeMin[tier] || 0.18) * ttl;
    const coreMax = (fillCfg.coreMax[tier] || 0.34) * ttl;
    const featherM = fillCfg.edgeFeatherM || 10;
    const edgeFloor = fillCfg.edgeFactorFloor || 0.72;
    const distBorder = distanceToPolygonBorder(lat, lng, ring);
    const edgeFactor = Math.min(1, Math.max(edgeFloor, distBorder / Math.max(featherM, 6)));

    return Math.max(0, Math.min(1, edgeMin + edgeFactor * (coreMax - edgeMin)));
}

function epicenterRadialBounds(ep, tier, fillCfg) {
    const radiusM = tierRadiusM(tier);
    const marginM = radiusM * 0.15 + (fillCfg.blurPx || 7) * 2;
    const cosLat = Math.cos(ep.lat * Math.PI / 180);
    const dLat = (radiusM + marginM) / 111320;
    const dLng = (radiusM + marginM) / (111320 * Math.max(cosLat, 0.01));

    return {
        minLat: ep.lat - dLat,
        maxLat: ep.lat + dLat,
        minLng: ep.lng - dLng,
        maxLng: ep.lng + dLng,
        centerLat: ep.lat,
    };
}

function computeEpicenterRadialAlpha(lat, lng, tier, ttl, fillCfg, epicenters) {
    const radiusM = tierRadiusM(tier);
    const marginM = radiusM * 0.15 + (fillCfg.blurPx || 7) * 2;
    let alpha = 0;

    epicenters.forEach(function (ep) {
        const dist = haversineM(ep.lat, ep.lng, lat, lng);
        alpha = Math.max(alpha, computeRadialAlpha(dist, tier, ttl, fillCfg, radiusM, marginM));
    });

    return alpha;
}

function computeRadialAlpha(dist, tier, ttl, fillCfg, radiusM, marginM) {
    const edgeMin = (fillCfg.edgeMin[tier] || 0.18) * ttl;
    const coreMax = (fillCfg.coreMax[tier] || 0.34) * ttl;
    const featherM = fillCfg.edgeFeatherM || 30;
    const coreFloor = fillCfg.coreFactorFloor || 0.78;
    const edgeFloor = fillCfg.edgeFactorFloor || 0.72;

    if (dist > radiusM + marginM * 0.5) return 0;

    const coreFactor = Math.max(coreFloor, 1 - 0.55 * (dist / radiusM));
    const edgeFactor = dist <= radiusM
        ? Math.min(1, Math.max(edgeFloor, (radiusM - dist) / featherM + edgeFloor))
        : Math.max(edgeFloor * 0.5, 1 - (dist - radiusM) / marginM);

    return (edgeMin + coreFactor * (coreMax - edgeMin)) * Math.min(1, edgeFactor);
}

function canvasMaxAlpha(canvas) {
    const ctx = canvas.getContext('2d');
    const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
    let maxA = 0;

    for (let i = 3; i < data.length; i += 4) {
        maxA = Math.max(maxA, data[i]);
    }

    return maxA / 255;
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

function rasterFromCanvas(canvas, bounds, fillCfg) {
    const blurred = applyCanvasBlur(canvas, fillCfg.blurPx || 7);

    return {
        dataUrl: blurred.toDataURL('image/png'),
        bounds: [[bounds.minLat, bounds.minLng], [bounds.maxLat, bounds.maxLng]],
    };
}

function gridDimensions(bounds, stepM, maxPx) {
    const latSpanM = haversineM(bounds.minLat, bounds.minLng, bounds.maxLat, bounds.minLng);
    const lngSpanM = haversineM(bounds.centerLat, bounds.minLng, bounds.centerLat, bounds.maxLng);

    let cols = Math.max(16, Math.ceil(lngSpanM / stepM));
    let rows = Math.max(16, Math.ceil(latSpanM / stepM));
    const scale = Math.min(1, maxPx / Math.max(cols, rows));

    cols = Math.max(16, Math.round(cols * scale));
    rows = Math.max(16, Math.round(rows * scale));

    return { cols: cols, rows: rows };
}

/**
 * Un solo raster por inundación: polígono (interior estable) + radiales en epicentros (max alpha).
 */
function buildInundacionHeatRaster(polygonRings, epicenters, tier, ttl, fillCfg) {
    const validEps = (epicenters || []).filter(function (ep) {
        return ep && !isNaN(ep.lat) && !isNaN(ep.lng);
    });

    if (validEps.length === 0) return null;

    const smoothRings = (polygonRings || []).map(function (ring) {
        return chaikinSmoothRing(ring, fillCfg.chaikinIterations || 2);
    }).filter(function (ring) {
        return ring && ring.length >= 3;
    });

    const marginM = (fillCfg.edgeFeatherM || 30) + (fillCfg.blurPx || 7) * 2;
    const boxes = [];

    smoothRings.forEach(function (ring) {
        boxes.push(ringBounds(ring, marginM));
    });
    validEps.forEach(function (ep) {
        boxes.push(epicenterRadialBounds(ep, tier, fillCfg));
    });

    if (boxes.length === 0) return null;

    const bounds = boundsFromBoxes(boxes);
    bounds.centerLat = (bounds.minLat + bounds.maxLat) / 2;

    const rgb = hexToRgb(window.SMART_HEATMAP_TIER_COLORS[tier]);
    const stepM = fillCfg.sampleStepM || 10;
    const maxPx = fillCfg.maxCanvasPx || 512;
    const grid = gridDimensions(bounds, stepM, maxPx);

    const canvas = document.createElement('canvas');
    canvas.width = grid.cols;
    canvas.height = grid.rows;
    const ctx = canvas.getContext('2d');
    const imageData = ctx.createImageData(grid.cols, grid.rows);
    const data = imageData.data;

    for (let row = 0; row < grid.rows; row++) {
        const lat = bounds.maxLat - (row / Math.max(grid.rows - 1, 1)) * (bounds.maxLat - bounds.minLat);

        for (let col = 0; col < grid.cols; col++) {
            const lng = bounds.minLng + (col / Math.max(grid.cols - 1, 1)) * (bounds.maxLng - bounds.minLng);

            let alpha = 0;

            smoothRings.forEach(function (ring) {
                if (!pointInPolygon(lat, lng, ring)) return;
                alpha = Math.max(alpha, computePolygonInteriorAlpha(lat, lng, tier, ttl, fillCfg, ring));
            });

            alpha = Math.max(alpha, computeEpicenterRadialAlpha(lat, lng, tier, ttl, fillCfg, validEps));

            if (alpha <= ALPHA_VISIBLE_THRESHOLD) continue;

            const idx = (row * grid.cols + col) * 4;
            data[idx] = rgb[0];
            data[idx + 1] = rgb[1];
            data[idx + 2] = rgb[2];
            data[idx + 3] = Math.round(alpha * 255);
        }
    }

    ctx.putImageData(imageData, 0, 0);

    if (canvasMaxAlpha(canvas) <= ALPHA_VISIBLE_THRESHOLD) {
        return null;
    }

    return rasterFromCanvas(canvas, bounds, fillCfg);
}

function createHeatOverlay(map, raster, options) {
    return L.imageOverlay(raster.dataUrl, raster.bounds, {
        pane: options.pane || 'overlayPane',
        interactive: false,
        opacity: 1,
        className: 'flood-heat-overlay',
    });
}

window.redrawFloodHeatOverlays = function (map, instance) {
    if (!map || !instance || !Array.isArray(instance.overlays)) return;

    function resetOverlays() {
        instance.overlays.forEach(function (overlay) {
            if (overlay && typeof overlay._reset === 'function') {
                overlay._reset();
            }
        });
    }

    resetOverlays();
    requestAnimationFrame(function () {
        resetOverlays();
        map.fire('moveend');
    });
};

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

    const layersByTier = { baja: [], media: [], alta: [] };
    const allOverlays = [];

    parsedReports.forEach(function (rep) {
        const ttl = ttlFactor(rep.updatedAt, ttlHours);
        if (ttl <= 0) return;

        const usePolygons = (mode === 'topographic' || mode === 'auto') && rep.polygonRings.length > 0;
        const rings = usePolygons ? rep.polygonRings : [];
        const raster = buildInundacionHeatRaster(rings, rep.epicenters, rep.tier, ttl, fillCfg);

        if (raster) {
            const overlay = createHeatOverlay(map, raster, options);
            layersByTier[rep.tier].push(overlay);
            allOverlays.push(overlay);
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
        overlays: allOverlays,
        tiers: tiersPresent,
        mode: parsedReports.some(function (r) { return r.polygonRings.length > 0; }) ? 'topographic' : 'geometric',
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
