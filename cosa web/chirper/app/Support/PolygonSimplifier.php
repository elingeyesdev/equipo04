<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Reduce polygon_coords a un contorno liviano (Douglas-Peucker + tope de vértices).
 * polygon_coords debe ser contorno, no grilla de muestreo.
 */
final class PolygonSimplifier
{
    /** Máximo de vértices por anillo tras simplificar. */
    public const MAX_VERTICES = 150;

    /** Tolerancia inicial Douglas-Peucker en metros. */
    public const DEFAULT_TOLERANCE_M = 2.5;

    /**
     * @param  array<mixed>  $coords  Anillo único o MultiPolygon
     * @return array<mixed>
     */
    public static function simplificarCoords(
        array $coords,
        ?float $toleranceM = null,
        ?int $maxVertices = null,
    ): array {
        $toleranceM ??= self::DEFAULT_TOLERANCE_M;
        $maxVertices ??= self::MAX_VERTICES;

        if (! PolygonCoordsHelper::tieneGeometriaValida($coords)) {
            return $coords;
        }

        $rings = PolygonCoordsHelper::normalizarAnillos($coords);
        $out = [];

        foreach ($rings as $ring) {
            $simplified = self::simplificarAnillo($ring, $toleranceM, $maxVertices);
            if (count($simplified) >= 3) {
                $out[] = $simplified;
            }
        }

        if ($out === []) {
            return $coords;
        }

        return count($out) === 1 ? $out[0] : $out;
    }

    /**
     * @param  array<mixed>  $coords
     */
    public static function necesitaSimplificacion(array $coords, int $maxVertices = self::MAX_VERTICES): bool
    {
        foreach (PolygonCoordsHelper::normalizarAnillos($coords) as $ring) {
            if (count($ring) > $maxVertices) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{0: float|int, 1: float|int}>  $ring
     * @return array<int, array{0: float, 1: float}>
     */
    public static function simplificarAnillo(array $ring, float $toleranceM, int $maxVertices): array
    {
        $ring = self::dedupeConsecutive($ring);

        if (count($ring) <= $maxVertices && count($ring) <= 80) {
            return $ring;
        }

        // Anillos enormes (p. ej. 10k por contorno fallido): envolvente convexa primero.
        if (count($ring) > 200) {
            $ring = self::convexHullLatLng($ring);
        }

        if (count($ring) <= $maxVertices && count($ring) <= 80) {
            return $ring;
        }

        $tolerance = $toleranceM;
        $simplified = self::douglasPeucker($ring, $tolerance);

        for ($attempt = 0; $attempt < 8 && count($simplified) > $maxVertices; $attempt++) {
            $tolerance *= 1.75;
            $simplified = self::douglasPeucker($ring, $tolerance);
        }

        if (count($simplified) > $maxVertices) {
            $simplified = self::convexHullLatLng($ring);
        }

        if (count($simplified) > $maxVertices) {
            $simplified = self::decimateUniform($simplified, $maxVertices);
        }

        if (count($simplified) < 3 && count($ring) >= 3) {
            $simplified = self::decimateUniform($ring, min($maxVertices, count($ring)));
        }

        return self::dedupeConsecutive($simplified);
    }

    /**
     * @param  array<int, array{0: float|int, 1: float|int}>  $points
     * @return array<int, array{0: float, 1: float}>
     */
    public static function convexHullLatLng(array $points): array
    {
        $pts = [];
        foreach ($points as $p) {
            if (! is_array($p) || count($p) < 2) {
                continue;
            }
            $pts[] = [(float) $p[0], (float) $p[1]];
        }

        if (count($pts) < 3) {
            return $pts;
        }

        usort($pts, static fn (array $a, array $b): int => ($a[1] <=> $b[1]) ?: ($a[0] <=> $b[0]));

        $cross = static fn (array $o, array $a, array $b): float => ($a[1] - $o[1]) * ($b[0] - $o[0]) - ($a[0] - $o[0]) * ($b[1] - $o[1]);

        $lower = [];
        foreach ($pts as $p) {
            while (count($lower) >= 2 && $cross($lower[count($lower) - 2], $lower[count($lower) - 1], $p) <= 0) {
                array_pop($lower);
            }
            $lower[] = $p;
        }

        $upper = [];
        for ($i = count($pts) - 1; $i >= 0; $i--) {
            $p = $pts[$i];
            while (count($upper) >= 2 && $cross($upper[count($upper) - 2], $upper[count($upper) - 1], $p) <= 0) {
                array_pop($upper);
            }
            $upper[] = $p;
        }

        array_pop($lower);
        array_pop($upper);

        return array_merge($lower, $upper);
    }

    /**
     * @param  array<int, array{0: float|int, 1: float|int}>  $ring
     * @return array<int, array{0: float, 1: float}>
     */
    private static function douglasPeucker(array $ring, float $toleranceM): array
    {
        $n = count($ring);
        if ($n <= 2) {
            return self::dedupeConsecutive($ring);
        }

        $originLat = (float) $ring[0][0];
        $originLng = (float) $ring[0][1];
        $flat = array_map(
            static fn (array $p): array => self::toLocalMeters((float) $p[0], (float) $p[1], $originLat, $originLng),
            $ring
        );

        $keep = array_fill(0, $n, false);
        $keep[0] = true;
        $keep[$n - 1] = true;

        $stack = [[0, $n - 1]];

        while ($stack !== []) {
            [$start, $end] = array_pop($stack);

            if ($end <= $start + 1) {
                continue;
            }

            $maxDist = 0.0;
            $index = $start;

            for ($i = $start + 1; $i < $end; $i++) {
                $dist = self::perpendicularDistanceM($flat[$i], $flat[$start], $flat[$end]);
                if ($dist > $maxDist) {
                    $maxDist = $dist;
                    $index = $i;
                }
            }

            if ($maxDist > $toleranceM) {
                $keep[$index] = true;
                $stack[] = [$start, $index];
                $stack[] = [$index, $end];
            }
        }

        $result = [];
        foreach ($ring as $i => $p) {
            if ($keep[$i]) {
                $result[] = [(float) $p[0], (float) $p[1]];
            }
        }

        return $result;
    }

    /**
     * @param  array{0: float, 1: float}  $point
     * @param  array{0: float, 1: float}  $lineStart
     * @param  array{0: float, 1: float}  $lineEnd
     */
    private static function perpendicularDistanceM(array $point, array $lineStart, array $lineEnd): float
    {
        $dx = $lineEnd[0] - $lineStart[0];
        $dy = $lineEnd[1] - $lineStart[1];

        if ($dx === 0.0 && $dy === 0.0) {
            return hypot($point[0] - $lineStart[0], $point[1] - $lineStart[1]);
        }

        $t = (($point[0] - $lineStart[0]) * $dx + ($point[1] - $lineStart[1]) * $dy) / ($dx * $dx + $dy * $dy);
        $t = max(0.0, min(1.0, $t));

        $projX = $lineStart[0] + $t * $dx;
        $projY = $lineStart[1] + $t * $dy;

        return hypot($point[0] - $projX, $point[1] - $projY);
    }

    /**
     * @return array{0: float, 1: float} [eastM, northM]
     */
    private static function toLocalMeters(float $lat, float $lng, float $originLat, float $originLng): array
    {
        $cosLat = max(cos(deg2rad($originLat)), 0.01);

        return [
            ($lng - $originLng) * 111_320.0 * $cosLat,
            ($lat - $originLat) * 111_320.0,
        ];
    }

    /**
     * @param  array<int, array{0: float|int, 1: float|int}>  $ring
     * @return array<int, array{0: float, 1: float}>
     */
    private static function dedupeConsecutive(array $ring): array
    {
        if ($ring === []) {
            return [];
        }

        $out = [[(float) $ring[0][0], (float) $ring[0][1]]];

        for ($i = 1, $n = count($ring); $i < $n; $i++) {
            $prev = $out[count($out) - 1];
            $lat = (float) $ring[$i][0];
            $lng = (float) $ring[$i][1];

            if (abs($prev[0] - $lat) > 1e-7 || abs($prev[1] - $lng) > 1e-7) {
                $out[] = [$lat, $lng];
            }
        }

        return $out;
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring
     * @return array<int, array{0: float, 1: float}>
     */
    private static function decimateUniform(array $ring, int $maxVertices): array
    {
        $n = count($ring);
        if ($n <= $maxVertices) {
            return $ring;
        }

        $step = ($n - 1) / ($maxVertices - 1);
        $out = [];

        for ($i = 0; $i < $maxVertices; $i++) {
            $idx = (int) round($i * $step);
            $idx = min($idx, $n - 1);
            $out[] = $ring[$idx];
        }

        return self::dedupeConsecutive($out);
    }
}
