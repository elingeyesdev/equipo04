<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ElevationProvider;
use Illuminate\Support\Facades\Log;

/**
 * Calcula polígonos de inundación mediante region growing sobre una grilla
 * de elevación (SRTM 30 m). El agua se expande por celdas contiguas cuya
 * elevación es menor o igual al epicentro + tolerancia.
 */
final class TopografiaInundacionService
{
    /** Tamaño de celda de la grilla en metros (≈ resolución SRTM). */
    private const GRID_CELL_M = 25.0;

    /** Margen de elevación para incluir aceras, canales y terreno plano. */
    private const ELEVATION_TOLERANCE_M = 0.5;

    /** Radio máximo de expansión según intensidad del reporte. */
    private const MAX_RADIUS_M = [
        'baja'  => 100.0,
        'media' => 200.0,
        'alta'  => 300.0,
    ];

    /** Puntos por request a Open Topo Data. */
    private const ELEVATION_BATCH_SIZE = 100;

    /** Pausa entre lotes (ms) para respetar rate limit ~1 req/s. */
    private const ELEVATION_BATCH_DELAY_MS = 1100;

    /** Celda de la grilla de unión de polígonos de reportes (metros). */
    private const UNION_GRID_CELL_M = 10.0;

    /** Distancia máxima entre bordes para fusionar polígonos de una misma inundación. */
    public const UNION_BRIDGE_M = 100.0;

    public function __construct(
        private readonly ElevationProvider $elevationService,
    ) {}

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    public function calcularPoligono(float $lat, float $lng, string $intensidad = 'media'): array
    {
        return $this->calcularResultado($lat, $lng, $intensidad)['polygon_coords'];
    }

    /**
     * @return array{
     *   polygon_coords: array<int, array{0: float, 1: float}>,
     *   es_fallback: bool,
     *   fuente: 'topographic'|'geometric_fallback'
     * }
     */
    public function calcularResultado(float $lat, float $lng, string $intensidad = 'media'): array
    {
        $maxRadius = self::MAX_RADIUS_M[$intensidad] ?? self::MAX_RADIUS_M['media'];
        $fallbackRadius = $maxRadius * 0.35;

        if ($lat === 0.0 && $lng === 0.0) {
            return $this->resultadoFallback($lat, $lng, $fallbackRadius);
        }

        $grid = $this->buildElevationGrid($lat, $lng, $maxRadius);

        if ($grid === null) {
            Log::warning('TopografiaInundacionService: sin datos de elevación, usando fallback geométrico.');

            return $this->resultadoFallback($lat, $lng, $fallbackRadius);
        }

        $flooded = $this->regionGrow($grid, $lat, $lng, $maxRadius);

        if (count($flooded) < 1) {
            return $this->resultadoFallback($lat, $lng, $fallbackRadius);
        }

        $polygon = $this->floodedCellsToPolygon($flooded, $grid);

        if (count($polygon) < 3) {
            return $this->resultadoFallback($lat, $lng, $fallbackRadius);
        }

        return [
            'polygon_coords' => $polygon,
            'es_fallback' => false,
            'fuente' => 'topographic',
        ];
    }

    /**
     * @return array{
     *   polygon_coords: array<int, array{0: float, 1: float}>,
     *   es_fallback: bool,
     *   fuente: 'geometric_fallback'
     * }
     */
    private function resultadoFallback(float $lat, float $lng, float $radiusMeters): array
    {
        return [
            'polygon_coords' => $this->buildCircularFallback($lat, $lng, $radiusMeters),
            'es_fallback' => true,
            'fuente' => 'geometric_fallback',
        ];
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    public function buildCircularFallback(float $centroLat, float $centroLng, float $radiusMeters): array
    {
        $points = [];
        $numPoints = 16;

        for ($i = 0; $i < $numPoints; $i++) {
            $angle = ($i / $numPoints) * 360.0;
            [$pLat, $pLng] = $this->offsetPoint($centroLat, $centroLng, $radiusMeters, $angle);
            $points[] = [$pLat, $pLng];
        }

        return $points;
    }

    /**
     * Fusiona polígonos de reportes mediante rasterización + cierre morfológico.
     * Rellena huecos entre manchas cercanas siguiendo la forma de los bordes.
     *
     * @param  array<int, array<int, array{0: float, 1: float}>>  $poligonos
     * @return array{
     *   rings: array<int, array<int, array{0: float, 1: float}>>,
     *   es_multipolygon: bool,
     *   polygon_coords: array<int, mixed>
     * }
     */
    public function unirPoligonosReportes(array $poligonos, float $bridgeMeters = self::UNION_BRIDGE_M): array
    {
        $poligonos = array_values(array_filter($poligonos, static fn (array $p): bool => count($p) >= 3));

        if ($poligonos === []) {
            return [
                'rings' => [],
                'es_multipolygon' => false,
                'polygon_coords' => [],
            ];
        }

        if (count($poligonos) === 1) {
            return [
                'rings' => [$poligonos[0]],
                'es_multipolygon' => false,
                'polygon_coords' => $poligonos[0],
            ];
        }

        $marginM = $bridgeMeters + self::UNION_GRID_CELL_M;
        $bounds = $this->boundingBoxOfPolygons($poligonos, $marginM);
        $grid = $this->buildUnionGrid($bounds);

        $filled = $this->rasterizePolygonsOnGrid($poligonos, $grid);
        $radius = max(1, (int) ceil($bridgeMeters / (2 * self::UNION_GRID_CELL_M)));
        $closed = $this->morphologicalClose($filled, $radius);
        $components = $this->findConnectedComponents($closed);

        $rings = [];
        foreach ($components as $component) {
            $ring = $this->floodedCellsToPolygon($component, $grid);
            if (count($ring) >= 3) {
                $rings[] = $ring;
            }
        }

        $esMultipolygon = count($rings) > 1;

        return [
            'rings' => $rings,
            'es_multipolygon' => $esMultipolygon,
            'polygon_coords' => $esMultipolygon ? $rings : ($rings[0] ?? []),
        ];
    }

    /**
     * @param  array<int, array<int, array{0: float, 1: float}>>  $poligonos
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float, center_lat: float, center_lng: float}
     */
    private function boundingBoxOfPolygons(array $poligonos, float $marginM): array
    {
        $minLat = INF;
        $maxLat = -INF;
        $minLng = INF;
        $maxLng = -INF;

        foreach ($poligonos as $polygon) {
            foreach ($polygon as [$lat, $lng]) {
                $minLat = min($minLat, $lat);
                $maxLat = max($maxLat, $lat);
                $minLng = min($minLng, $lng);
                $maxLng = max($maxLng, $lng);
            }
        }

        $marginDeg = $marginM / 111_320.0;
        $centerLat = ($minLat + $maxLat) / 2;
        $cosLat = max(cos(deg2rad($centerLat)), 0.01);
        $marginLng = $marginM / (111_320.0 * $cosLat);

        return [
            'min_lat' => $minLat - $marginDeg,
            'max_lat' => $maxLat + $marginDeg,
            'min_lng' => $minLng - $marginLng,
            'max_lng' => $maxLng + $marginLng,
            'center_lat' => $centerLat,
            'center_lng' => ($minLng + $maxLng) / 2,
        ];
    }

    /**
     * @param  array{min_lat: float, max_lat: float, min_lng: float, max_lng: float, center_lat: float, center_lng: float}  $bounds
     * @return array<string, mixed>
     */
    private function buildUnionGrid(array $bounds): array
    {
        $cellM = self::UNION_GRID_CELL_M;
        $centerLat = $bounds['center_lat'];
        $centerLng = $bounds['center_lng'];
        $cosLat = max(cos(deg2rad($centerLat)), 0.01);

        $northExtentM = ($bounds['max_lat'] - $bounds['min_lat']) / 2 * 111_320.0;
        $eastExtentM = ($bounds['max_lng'] - $bounds['min_lng']) / 2 * 111_320.0 * $cosLat;

        $radiusRows = max(1, (int) ceil($northExtentM / $cellM));
        $radiusCols = max(1, (int) ceil($eastExtentM / $cellM));
        $rows = $radiusRows * 2 + 1;
        $cols = $radiusCols * 2 + 1;
        $centerRow = $radiusRows;
        $centerCol = $radiusCols;

        $halfNorthDeg = ($cellM / 2) / 111_320.0;
        $halfEastDeg = ($cellM / 2) / (111_320.0 * $cosLat);

        $cells = [];
        for ($row = 0; $row < $rows; $row++) {
            $cells[$row] = [];
            for ($col = 0; $col < $cols; $col++) {
                $northM = ($centerRow - $row) * $cellM;
                $eastM = ($col - $centerCol) * $cellM;
                [$cellLat, $cellLng] = $this->offsetByNorthEast($centerLat, $centerLng, $northM, $eastM);
                $cells[$row][$col] = ['lat' => $cellLat, 'lng' => $cellLng];
            }
        }

        return [
            'rows' => $rows,
            'cols' => $cols,
            'cells' => $cells,
            'half_north_deg' => $halfNorthDeg,
            'half_east_deg' => $halfEastDeg,
        ];
    }

    /**
     * @param  array<int, array<int, array{0: float, 1: float}>>  $poligonos
     * @param  array<string, mixed>  $grid
     * @return array<int, array<int, bool>>
     */
    private function rasterizePolygonsOnGrid(array $poligonos, array $grid): array
    {
        $rows = $grid['rows'];
        $cols = $grid['cols'];
        $cells = $grid['cells'];
        $filled = array_fill(0, $rows, array_fill(0, $cols, false));

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $lat = $cells[$row][$col]['lat'];
                $lng = $cells[$row][$col]['lng'];

                foreach ($poligonos as $polygon) {
                    if ($this->pointInPolygon($lat, $lng, $polygon)) {
                        $filled[$row][$col] = true;
                        break;
                    }
                }
            }
        }

        return $filled;
    }

    /**
     * @param  array<int, array<int, bool>>  $grid
     * @return array<int, array<int, bool>>
     */
    private function morphologicalClose(array $grid, int $radius): array
    {
        return $this->erodeGrid($this->dilateGrid($grid, $radius), $radius);
    }

    /**
     * @param  array<int, array<int, bool>>  $grid
     * @return array<int, array<int, bool>>
     */
    private function dilateGrid(array $grid, int $radius): array
    {
        $rows = count($grid);
        $cols = count($grid[0] ?? []);
        $result = array_fill(0, $rows, array_fill(0, $cols, false));
        $radiusSq = $radius * $radius;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                if (!$grid[$row][$col]) {
                    continue;
                }

                for ($dr = -$radius; $dr <= $radius; $dr++) {
                    for ($dc = -$radius; $dc <= $radius; $dc++) {
                        if ($dr * $dr + $dc * $dc > $radiusSq) {
                            continue;
                        }

                        $nr = $row + $dr;
                        $nc = $col + $dc;

                        if ($nr >= 0 && $nr < $rows && $nc >= 0 && $nc < $cols) {
                            $result[$nr][$nc] = true;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array<int, bool>>  $grid
     * @return array<int, array<int, bool>>
     */
    private function erodeGrid(array $grid, int $radius): array
    {
        $rows = count($grid);
        $cols = count($grid[0] ?? []);
        $result = array_fill(0, $rows, array_fill(0, $cols, false));
        $radiusSq = $radius * $radius;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                if (!$grid[$row][$col]) {
                    continue;
                }

                $keep = true;
                for ($dr = -$radius; $dr <= $radius && $keep; $dr++) {
                    for ($dc = -$radius; $dc <= $radius; $dc++) {
                        if ($dr * $dr + $dc * $dc > $radiusSq) {
                            continue;
                        }

                        $nr = $row + $dr;
                        $nc = $col + $dc;

                        if ($nr < 0 || $nr >= $rows || $nc < 0 || $nc >= $cols || !$grid[$nr][$nc]) {
                            $keep = false;
                            break;
                        }
                    }
                }

                $result[$row][$col] = $keep;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array<int, bool>>  $grid
     * @return array<int, array<int, array{0: int, 1: int}>>
     */
    private function findConnectedComponents(array $grid): array
    {
        $rows = count($grid);
        $cols = count($grid[0] ?? []);
        $visited = [];
        $components = [];

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $key = "{$row},{$col}";

                if (!$grid[$row][$col] || isset($visited[$key])) {
                    continue;
                }

                $component = [];
                $queue = [[$row, $col]];
                $visited[$key] = true;

                while ($queue !== []) {
                    [$r, $c] = array_shift($queue);
                    $component[] = [$r, $c];

                    foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dr, $dc]) {
                        $nr = $r + $dr;
                        $nc = $c + $dc;
                        $nKey = "{$nr},{$nc}";

                        if ($nr < 0 || $nr >= $rows || $nc < 0 || $nc >= $cols) {
                            continue;
                        }

                        if (!$grid[$nr][$nc] || isset($visited[$nKey])) {
                            continue;
                        }

                        $visited[$nKey] = true;
                        $queue[] = [$nr, $nc];
                    }
                }

                if ($component !== []) {
                    $components[] = $component;
                }
            }
        }

        return $components;
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $polygon
     */
    public function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $polygon[$i][0];
            $xi = $polygon[$i][1];
            $yj = $polygon[$j][0];
            $xj = $polygon[$j][1];

            if ((($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * @return array{
     *   rows: int,
     *   cols: int,
     *   center_row: int,
     *   center_col: int,
     *   half_north_deg: float,
     *   half_east_deg: float,
     *   cells: array<int, array<int, array{lat: float, lng: float, elevation: float|null}>>
     * }|null
     */
    private function buildElevationGrid(float $centerLat, float $centerLng, float $maxRadiusM): ?array
    {
        $radiusCells = (int) ceil($maxRadiusM / self::GRID_CELL_M);
        $rows = $radiusCells * 2 + 1;
        $cols = $radiusCells * 2 + 1;
        $centerRow = $radiusCells;
        $centerCol = $radiusCells;

        $halfNorthM = self::GRID_CELL_M / 2.0;
        $halfEastM = self::GRID_CELL_M / 2.0;
        $cosLat = max(cos(deg2rad($centerLat)), 0.01);
        $halfNorthDeg = $halfNorthM / 111_320.0;
        $halfEastDeg = $halfEastM / (111_320.0 * $cosLat);

        $rawPoints = [];
        $cells = [];

        for ($row = 0; $row < $rows; $row++) {
            $cells[$row] = [];
            for ($col = 0; $col < $cols; $col++) {
                $northM = ($centerRow - $row) * self::GRID_CELL_M;
                $eastM = ($col - $centerCol) * self::GRID_CELL_M;
                [$cellLat, $cellLng] = $this->offsetByNorthEast($centerLat, $centerLng, $northM, $eastM);

                $cells[$row][$col] = [
                    'lat' => $cellLat,
                    'lng' => $cellLng,
                    'elevation' => null,
                ];
                $rawPoints[] = "{$cellLat},{$cellLng}";
            }
        }

        try {
            $elevations = $this->fetchElevationsInBatches($rawPoints);
        } catch (\Exception $e) {
            Log::error('TopografiaInundacionService: error al consultar elevaciones.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $idx = 0;
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $cells[$row][$col]['elevation'] = $elevations[$idx]['elevation'] ?? null;
                $idx++;
            }
        }

        $originElevation = $cells[$centerRow][$centerCol]['elevation'] ?? null;
        if ($originElevation === null) {
            return null;
        }

        return [
            'rows' => $rows,
            'cols' => $cols,
            'center_row' => $centerRow,
            'center_col' => $centerCol,
            'center_lat' => $centerLat,
            'center_lng' => $centerLng,
            'origin_elevation' => $originElevation,
            'max_radius_m' => $maxRadiusM,
            'half_north_deg' => $halfNorthDeg,
            'half_east_deg' => $halfEastDeg,
            'cells' => $cells,
        ];
    }

    /**
     * BFS: expansión por celdas contiguas con elevación ≤ origen + tolerancia.
     *
     * @param  array<string, mixed>  $grid
     * @return array<int, array{0: int, 1: int}>
     */
    private function regionGrow(array $grid, float $centerLat, float $centerLng, float $maxRadiusM): array
    {
        $rows = $grid['rows'];
        $cols = $grid['cols'];
        $centerRow = $grid['center_row'];
        $centerCol = $grid['center_col'];
        $originElevation = (float) $grid['origin_elevation'];
        $threshold = $originElevation + self::ELEVATION_TOLERANCE_M;
        $cells = $grid['cells'];

        $flooded = [];
        $visited = [];
        $queue = [[$centerRow, $centerCol]];
        $visited["{$centerRow},{$centerCol}"] = true;
        $flooded[] = [$centerRow, $centerCol];

        $neighbors = [[-1, 0], [1, 0], [0, -1], [0, 1]];

        while ($queue !== []) {
            [$row, $col] = array_shift($queue);

            foreach ($neighbors as [$dRow, $dCol]) {
                $nRow = $row + $dRow;
                $nCol = $col + $dCol;
                $key = "{$nRow},{$nCol}";

                if ($nRow < 0 || $nRow >= $rows || $nCol < 0 || $nCol >= $cols) {
                    continue;
                }

                if (isset($visited[$key])) {
                    continue;
                }

                $cell = $cells[$nRow][$nCol];
                $elevation = $cell['elevation'];

                if ($elevation === null || $elevation > $threshold) {
                    continue;
                }

                if ($this->distanceMeters($centerLat, $centerLng, $cell['lat'], $cell['lng']) > $maxRadiusM) {
                    continue;
                }

                $visited[$key] = true;
                $flooded[] = [$nRow, $nCol];
                $queue[] = [$nRow, $nCol];
            }
        }

        return $flooded;
    }

    /**
     * Traza el contorno de la región inundada y lo devuelve como polígono cerrado.
     *
     * @param  array<int, array{0: int, 1: int}>  $flooded
     * @param  array<string, mixed>  $grid
     * @return array<int, array{0: float, 1: float}>
     */
    private function floodedCellsToPolygon(array $flooded, array $grid): array
    {
        $floodedSet = [];
        foreach ($flooded as [$row, $col]) {
            $floodedSet["{$row},{$col}"] = true;
        }

        $cells = $grid['cells'];
        $halfNorthDeg = $grid['half_north_deg'];
        $halfEastDeg = $grid['half_east_deg'];
        $adjacency = [];

        foreach ($flooded as [$row, $col]) {
            $center = $cells[$row][$col];
            $corners = $this->cellCorners($center['lat'], $center['lng'], $halfNorthDeg, $halfEastDeg);

            if (!isset($floodedSet[($row - 1) . ",{$col}"])) {
                $this->addUndirectedEdge($adjacency, $corners['nw'], $corners['ne']);
            }
            if (!isset($floodedSet[($row + 1) . ",{$col}"])) {
                $this->addUndirectedEdge($adjacency, $corners['sw'], $corners['se']);
            }
            if (!isset($floodedSet["{$row}," . ($col - 1)])) {
                $this->addUndirectedEdge($adjacency, $corners['sw'], $corners['nw']);
            }
            if (!isset($floodedSet["{$row}," . ($col + 1)])) {
                $this->addUndirectedEdge($adjacency, $corners['ne'], $corners['se']);
            }
        }

        return $this->chainBoundaryEdges($adjacency);
    }

    /**
     * @param  array<string, array<int, array{0: float, 1: float}>>  $adjacency
     * @return array<int, array{0: float, 1: float}>
     */
    private function chainBoundaryEdges(array $adjacency): array
    {
        if ($adjacency === []) {
            return [];
        }

        $startKey = array_key_first($adjacency);
        $polygon = [];
        $currentKey = $startKey;
        $previousKey = null;
        $guard = 0;

        do {
            [$lat, $lng] = $this->parsePointKey($currentKey);
            $polygon[] = [$lat, $lng];

            $nextKey = null;
            foreach ($adjacency[$currentKey] ?? [] as $neighbor) {
                $neighborKey = $this->pointKey($neighbor[0], $neighbor[1]);
                if ($neighborKey !== $previousKey) {
                    $nextKey = $neighborKey;
                    break;
                }
            }

            if ($nextKey === null) {
                break;
            }

            $previousKey = $currentKey;
            $currentKey = $nextKey;
            $guard++;
        } while ($currentKey !== $startKey && $guard < 10_000);

        return $this->deduplicateConsecutiveVertices($polygon);
    }

    /**
     * @param  array<string, array<int, array{0: float, 1: float}>>  $adjacency
     * @param  array{0: float, 1: float}  $from
     * @param  array{0: float, 1: float}  $to
     */
    private function addUndirectedEdge(array &$adjacency, array $from, array $to): void
    {
        $fromKey = $this->pointKey($from[0], $from[1]);
        $toKey = $this->pointKey($to[0], $to[1]);

        $adjacency[$fromKey] ??= [];
        $adjacency[$toKey] ??= [];

        $adjacency[$fromKey][] = $to;
        $adjacency[$toKey][] = $from;
    }

    /**
     * @return array{nw: array{0: float, 1: float}, ne: array{0: float, 1: float}, se: array{0: float, 1: float}, sw: array{0: float, 1: float}}
     */
    private function cellCorners(float $lat, float $lng, float $halfNorthDeg, float $halfEastDeg): array
    {
        $halfNorthM = $halfNorthDeg * 111_320.0;
        $halfEastM = $halfEastDeg * 111_320.0 * max(cos(deg2rad($lat)), 0.01);

        return [
            'nw' => $this->offsetByNorthEast($lat, $lng, $halfNorthM, -$halfEastM),
            'ne' => $this->offsetByNorthEast($lat, $lng, $halfNorthM, $halfEastM),
            'se' => $this->offsetByNorthEast($lat, $lng, -$halfNorthM, $halfEastM),
            'sw' => $this->offsetByNorthEast($lat, $lng, -$halfNorthM, -$halfEastM),
        ];
    }

    /**
     * @param  string[]  $rawPoints
     * @return array<int, array{elevation: float|null, lat: float, lng: float}>
     */
    private function fetchElevationsInBatches(array $rawPoints): array
    {
        $results = [];
        $chunks = array_chunk($rawPoints, self::ELEVATION_BATCH_SIZE);

        foreach ($chunks as $index => $chunk) {
            if ($index > 0) {
                usleep(self::ELEVATION_BATCH_DELAY_MS * 1000);
            }

            $batch = $this->elevationService->fetchElevations($chunk);
            array_push($results, ...$batch);
        }

        return $results;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function offsetByNorthEast(float $lat, float $lng, float $northM, float $eastM): array
    {
        $cosLat = max(cos(deg2rad($lat)), 0.01);
        $newLat = $lat + ($northM / 111_320.0);
        $newLng = $lng + ($eastM / (111_320.0 * $cosLat));

        return [$newLat, $newLng];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function offsetPoint(float $lat, float $lng, float $distanceMeters, float $bearingDegrees): array
    {
        $rEarth = 6_378_137.0;
        $bearing = deg2rad($bearingDegrees);
        $lat1 = deg2rad($lat);
        $lon1 = deg2rad($lng);

        $lat2 = asin(
            sin($lat1) * cos($distanceMeters / $rEarth)
            + cos($lat1) * sin($distanceMeters / $rEarth) * cos($bearing)
        );
        $lon2 = $lon1 + atan2(
            sin($bearing) * sin($distanceMeters / $rEarth) * cos($lat1),
            cos($distanceMeters / $rEarth) - sin($lat1) * sin($lat2)
        );

        return [rad2deg($lat2), rad2deg($lon2)];
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $rEarth = 6_371_000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $rEarth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function pointKey(float $lat, float $lng): string
    {
        return round($lat, 6) . ',' . round($lng, 6);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function parsePointKey(string $key): array
    {
        $parts = explode(',', $key, 2);

        return [(float) $parts[0], (float) ($parts[1] ?? 0)];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $polygon
     * @return array<int, array{0: float, 1: float}>
     */
    private function deduplicateConsecutiveVertices(array $polygon): array
    {
        if (count($polygon) < 2) {
            return $polygon;
        }

        $deduped = [$polygon[0]];

        for ($i = 1, $n = count($polygon); $i < $n; $i++) {
            $prev = $deduped[count($deduped) - 1];
            $curr = $polygon[$i];
            if (abs($prev[0] - $curr[0]) > 1e-7 || abs($prev[1] - $curr[1]) > 1e-7) {
                $deduped[] = $curr;
            }
        }

        return $deduped;
    }
}
