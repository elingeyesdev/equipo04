<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Utilidades para polygon_coords: anillo único o MultiPolygon (array de anillos).
 */
final class PolygonCoordsHelper
{
    /**
     * @param  array<mixed>  $coords
     */
    public static function esMultipolygon(array $coords): bool
    {
        if ($coords === []) {
            return false;
        }

        return isset($coords[0][0]) && is_array($coords[0][0]);
    }

    /**
     * @param  array<mixed>  $coords
     * @return array<int, array<int, array{0: float, 1: float}>>
     */
    public static function normalizarAnillos(array $coords): array
    {
        if ($coords === []) {
            return [];
        }

        if (self::esMultipolygon($coords)) {
            return array_values(array_map(
                static fn (array $ring): array => array_values($ring),
                $coords
            ));
        }

        return [array_values($coords)];
    }

    /**
     * @param  array<mixed>  $coords
     */
    public static function tieneGeometriaValida(array $coords): bool
    {
        foreach (self::normalizarAnillos($coords) as $ring) {
            if (count($ring) >= 3) {
                return true;
            }
        }

        return false;
    }
}
