/**
 * Contorno de selección: unión de TODOS los polígonos de reportes de una inundación
 * en un solo borde suavizado (raster + cierre morfológico + Chaikin).
 */
(function () {
    const GRID_CELL_M = 10;

    function haversineM(lat1, lng1, lat2, lng2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
            * Math.sin(dLng / 2) ** 2;
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

    function maxCentroidDistance(reports) {
        const points = reports.map(function (r) {
            return [
                parseFloat(r.lat_reporte || r.lat),
                parseFloat(r.long_reporte || r.lng),
            ];
        }).filter(function (p) {
            return !isNaN(p[0]) && !isNaN(p[1]);
        });

        let maxDist = 0;
        for (let i = 0; i < points.length; i++) {
            for (let j = i + 1; j < points.length; j++) {
                maxDist = Math.max(maxDist, haversineM(points[i][0], points[i][1], points[j][0], points[j][1]));
            }
        }
        return maxDist;
    }

    function boundingBoxOfPolygons(polygons, marginM) {
        let minLat = Infinity;
        let maxLat = -Infinity;
        let minLng = Infinity;
        let maxLng = -Infinity;

        polygons.forEach(function (polygon) {
            polygon.forEach(function (p) {
                minLat = Math.min(minLat, p[0]);
                maxLat = Math.max(maxLat, p[0]);
                minLng = Math.min(minLng, p[1]);
                maxLng = Math.max(maxLng, p[1]);
            });
        });

        const centerLat = (minLat + maxLat) / 2;
        const cosLat = Math.max(Math.cos(centerLat * Math.PI / 180), 0.01);
        const marginDeg = marginM / 111320;
        const marginLng = marginM / (111320 * cosLat);

        return {
            min_lat: minLat - marginDeg,
            max_lat: maxLat + marginDeg,
            min_lng: minLng - marginLng,
            max_lng: maxLng + marginLng,
            center_lat: centerLat,
            center_lng: (minLng + maxLng) / 2,
        };
    }

    function offsetByNorthEast(lat, lng, northM, eastM) {
        const cosLat = Math.max(Math.cos(lat * Math.PI / 180), 0.01);
        return [
            lat + northM / 111320,
            lng + eastM / (111320 * cosLat),
        ];
    }

    function buildUnionGrid(bounds) {
        const cellM = GRID_CELL_M;
        const centerLat = bounds.center_lat;
        const centerLng = bounds.center_lng;
        const cosLat = Math.max(Math.cos(centerLat * Math.PI / 180), 0.01);

        const northExtentM = (bounds.max_lat - bounds.min_lat) / 2 * 111320;
        const eastExtentM = (bounds.max_lng - bounds.min_lng) / 2 * 111320 * cosLat;

        const radiusRows = Math.max(1, Math.ceil(northExtentM / cellM));
        const radiusCols = Math.max(1, Math.ceil(eastExtentM / cellM));
        const rows = radiusRows * 2 + 1;
        const cols = radiusCols * 2 + 1;
        const centerRow = radiusRows;
        const centerCol = radiusCols;

        const halfNorthDeg = (cellM / 2) / 111320;
        const halfEastDeg = (cellM / 2) / (111320 * cosLat);

        const cells = [];
        for (let row = 0; row < rows; row++) {
            cells[row] = [];
            for (let col = 0; col < cols; col++) {
                const northM = (centerRow - row) * cellM;
                const eastM = (col - centerCol) * cellM;
                const pt = offsetByNorthEast(centerLat, centerLng, northM, eastM);
                cells[row][col] = { lat: pt[0], lng: pt[1] };
            }
        }

        return { rows, cols, cells, half_north_deg: halfNorthDeg, half_east_deg: halfEastDeg };
    }

    function rasterizePolygonsOnGrid(polygons, grid) {
        const rows = grid.rows;
        const cols = grid.cols;
        const cells = grid.cells;
        const filled = Array.from({ length: rows }, () => Array(cols).fill(false));

        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                const lat = cells[row][col].lat;
                const lng = cells[row][col].lng;
                for (let p = 0; p < polygons.length; p++) {
                    if (pointInPolygon(lat, lng, polygons[p])) {
                        filled[row][col] = true;
                        break;
                    }
                }
            }
        }
        return filled;
    }

    function dilateGrid(grid, radius) {
        const rows = grid.length;
        const cols = grid[0].length;
        const result = Array.from({ length: rows }, () => Array(cols).fill(false));
        const radiusSq = radius * radius;

        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                if (!grid[row][col]) continue;
                for (let dr = -radius; dr <= radius; dr++) {
                    for (let dc = -radius; dc <= radius; dc++) {
                        if (dr * dr + dc * dc > radiusSq) continue;
                        const nr = row + dr;
                        const nc = col + dc;
                        if (nr >= 0 && nr < rows && nc >= 0 && nc < cols) {
                            result[nr][nc] = true;
                        }
                    }
                }
            }
        }
        return result;
    }

    function erodeGrid(grid, radius) {
        const rows = grid.length;
        const cols = grid[0].length;
        const result = Array.from({ length: rows }, () => Array(cols).fill(false));
        const radiusSq = radius * radius;

        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                if (!grid[row][col]) continue;
                let keep = true;
                for (let dr = -radius; dr <= radius && keep; dr++) {
                    for (let dc = -radius; dc <= radius; dc++) {
                        if (dr * dr + dc * dc > radiusSq) continue;
                        const nr = row + dr;
                        const nc = col + dc;
                        if (nr < 0 || nr >= rows || nc < 0 || nc >= cols || !grid[nr][nc]) {
                            keep = false;
                            break;
                        }
                    }
                }
                result[row][col] = keep;
            }
        }
        return result;
    }

    function morphologicalClose(grid, radius) {
        return erodeGrid(dilateGrid(grid, radius), radius);
    }

    function findConnectedComponents(grid) {
        const rows = grid.length;
        const cols = grid[0].length;
        const visited = {};
        const components = [];

        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                const key = row + ',' + col;
                if (!grid[row][col] || visited[key]) continue;

                const component = [];
                const queue = [[row, col]];
                visited[key] = true;

                while (queue.length > 0) {
                    const [r, c] = queue.shift();
                    component.push([r, c]);
                    [[-1, 0], [1, 0], [0, -1], [0, 1]].forEach(function (d) {
                        const nr = r + d[0];
                        const nc = c + d[1];
                        const nk = nr + ',' + nc;
                        if (nr < 0 || nr >= rows || nc < 0 || nc >= cols) return;
                        if (!grid[nr][nc] || visited[nk]) return;
                        visited[nk] = true;
                        queue.push([nr, nc]);
                    });
                }
                if (component.length > 0) components.push(component);
            }
        }
        return components;
    }

    function pointKey(lat, lng) {
        return lat.toFixed(6) + ',' + lng.toFixed(6);
    }

    function parsePointKey(key) {
        const parts = key.split(',');
        return [parseFloat(parts[0]), parseFloat(parts[1])];
    }

    function cellCorners(lat, lng, halfNorthDeg, halfEastDeg) {
        const halfNorthM = halfNorthDeg * 111320;
        const halfEastM = halfEastDeg * 111320 * Math.max(Math.cos(lat * Math.PI / 180), 0.01);
        return {
            nw: offsetByNorthEast(lat, lng, halfNorthM, -halfEastM),
            ne: offsetByNorthEast(lat, lng, halfNorthM, halfEastM),
            se: offsetByNorthEast(lat, lng, -halfNorthM, halfEastM),
            sw: offsetByNorthEast(lat, lng, -halfNorthM, -halfEastM),
        };
    }

    function floodedCellsToPolygon(flooded, grid) {
        const floodedSet = {};
        flooded.forEach(function (rc) {
            floodedSet[rc[0] + ',' + rc[1]] = true;
        });

        const cells = grid.cells;
        const halfNorthDeg = grid.half_north_deg;
        const halfEastDeg = grid.half_east_deg;
        const adjacency = {};

        flooded.forEach(function (rc) {
            const row = rc[0];
            const col = rc[1];
            const center = cells[row][col];
            const corners = cellCorners(center.lat, center.lng, halfNorthDeg, halfEastDeg);

            if (!floodedSet[(row - 1) + ',' + col]) {
                addEdge(adjacency, corners.nw, corners.ne);
            }
            if (!floodedSet[(row + 1) + ',' + col]) {
                addEdge(adjacency, corners.sw, corners.se);
            }
            if (!floodedSet[row + ',' + (col - 1)]) {
                addEdge(adjacency, corners.sw, corners.nw);
            }
            if (!floodedSet[row + ',' + (col + 1)]) {
                addEdge(adjacency, corners.ne, corners.se);
            }
        });

        return chainBoundaryEdges(adjacency);
    }

    function addEdge(adjacency, from, to) {
        const fromKey = pointKey(from[0], from[1]);
        const toKey = pointKey(to[0], to[1]);
        if (!adjacency[fromKey]) adjacency[fromKey] = [];
        if (!adjacency[toKey]) adjacency[toKey] = [];
        adjacency[fromKey].push(to);
        adjacency[toKey].push(from);
    }

    function chainBoundaryEdges(adjacency) {
        const keys = Object.keys(adjacency);
        if (keys.length === 0) return [];

        const startKey = keys[0];
        const polygon = [];
        let currentKey = startKey;
        let previousKey = null;
        let guard = 0;

        do {
            polygon.push(parsePointKey(currentKey));
            let nextKey = null;
            (adjacency[currentKey] || []).forEach(function (neighbor) {
                const nk = pointKey(neighbor[0], neighbor[1]);
                if (nk !== previousKey && nextKey === null) nextKey = nk;
            });
            if (nextKey === null) break;
            previousKey = currentKey;
            currentKey = nextKey;
            guard++;
        } while (currentKey !== startKey && guard < 10000);

        return dedupeVertices(polygon);
    }

    function dedupeVertices(polygon) {
        if (polygon.length < 2) return polygon;
        const out = [polygon[0]];
        for (let i = 1; i < polygon.length; i++) {
            const prev = out[out.length - 1];
            const curr = polygon[i];
            if (Math.abs(prev[0] - curr[0]) > 1e-7 || Math.abs(prev[1] - curr[1]) > 1e-7) {
                out.push(curr);
            }
        }
        return out;
    }

    function chaikinSmooth(ring, iterations) {
        iterations = iterations || 3;
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
                next.push([
                    0.75 * p0[0] + 0.25 * p1[0],
                    0.75 * p0[1] + 0.25 * p1[1],
                ]);
                next.push([
                    0.25 * p0[0] + 0.75 * p1[0],
                    0.25 * p0[1] + 0.75 * p1[1],
                ]);
            }
            pts = next;
        }
        return pts;
    }

    function convexHull(points) {
        if (points.length < 3) return points.slice();
        const sorted = points.slice().sort(function (a, b) {
            return a[1] === b[1] ? a[0] - b[0] : a[1] - b[1];
        });

        function cross(o, a, b) {
            return (a[1] - o[1]) * (b[0] - o[0]) - (a[0] - o[0]) * (b[1] - o[1]);
        }

        const lower = [];
        sorted.forEach(function (p) {
            while (lower.length >= 2 && cross(lower[lower.length - 2], lower[lower.length - 1], p) <= 0) {
                lower.pop();
            }
            lower.push(p);
        });

        const upper = [];
        for (let i = sorted.length - 1; i >= 0; i--) {
            const p = sorted[i];
            while (upper.length >= 2 && cross(upper[upper.length - 2], upper[upper.length - 1], p) <= 0) {
                upper.pop();
            }
            upper.push(p);
        }

        upper.pop();
        lower.pop();
        return lower.concat(upper);
    }

    function unionPolygonsToSingleRing(polygons, bridgeMeters) {
        if (polygons.length === 0) return null;
        if (polygons.length === 1) return polygons[0].slice();

        const marginM = bridgeMeters + GRID_CELL_M;
        const bounds = boundingBoxOfPolygons(polygons, marginM);
        const grid = buildUnionGrid(bounds);
        const filled = rasterizePolygonsOnGrid(polygons, grid);
        const radius = Math.max(1, Math.ceil(bridgeMeters / (2 * GRID_CELL_M)));
        const closed = morphologicalClose(filled, radius);
        const components = findConnectedComponents(closed);

        if (components.length !== 1) return null;

        return floodedCellsToPolygon(components[0], grid);
    }

    /**
     * Un solo anillo suavizado que abarca TODOS los reportes de la inundación.
     * @returns {number[][]|null} ring [[lat,lng],...]
     */
    window.computeInundacionSelectionOutline = function (inundacion) {
        const activeReps = inundacion.reportes_activos || [];
        const polygons = [];

        // Incluimos también polígonos fallback (círculos): unir círculos sigue
        // dando una zona unificada coherente cuando no hay datos topográficos.
        activeReps.forEach(function (rep) {
            if (!rep.polygon_coords) return;
            if (!window.normalizePolygonRings) return;
            window.normalizePolygonRings(rep.polygon_coords).forEach(function (ring) {
                if (ring.length >= 3) polygons.push(ring);
            });
        });

        if (polygons.length === 0) return null;

        if (polygons.length === 1) {
            return chaikinSmooth(polygons[0], 3);
        }

        const maxDist = maxCentroidDistance(activeReps);
        let bridge = maxDist + 120;
        let ring = null;

        for (let attempt = 0; attempt < 8; attempt++) {
            ring = unionPolygonsToSingleRing(polygons, bridge);
            if (ring && ring.length >= 3) break;
            bridge += 80;
        }

        if (!ring || ring.length < 3) {
            const allPts = [];
            polygons.forEach(function (p) { p.forEach(function (v) { allPts.push(v); }); });
            activeReps.forEach(function (rep) {
                const lat = parseFloat(rep.lat_reporte || rep.lat);
                const lng = parseFloat(rep.long_reporte || rep.lng);
                if (!isNaN(lat) && !isNaN(lng)) allPts.push([lat, lng]);
            });
            ring = convexHull(allPts);
        }

        return chaikinSmooth(ring, 3);
    };

    /**
     * Anillo único para mapa de calor: prioriza API (1 anillo), luego outline adaptativo.
     * @returns {number[][]|null}
     */
    window.resolveUnifiedHeatRing = function (inundacion) {
        if (!inundacion) return null;

        if (window.normalizePolygonRings && inundacion.polygon_coords) {
            const fromApi = window.normalizePolygonRings(inundacion.polygon_coords);
            if (fromApi.length === 1 && fromApi[0].length >= 3) {
                return fromApi[0];
            }
            if (inundacion.polygon_es_multipolygon === false && fromApi.length >= 1 && fromApi[0].length >= 3) {
                return fromApi[0];
            }
        }

        if (window.computeInundacionSelectionOutline) {
            const outline = window.computeInundacionSelectionOutline(inundacion);
            if (outline && outline.length >= 3) {
                return outline;
            }
        }

        if (window.normalizePolygonRings && inundacion.polygon_coords) {
            const rings = window.normalizePolygonRings(inundacion.polygon_coords);
            if (rings.length >= 1 && rings[0].length >= 3) {
                return rings[0];
            }
        }

        return null;
    };

    window.chaikinSmoothRing = chaikinSmooth;
})();
