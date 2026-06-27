<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inundacion;
use App\Models\Reporte;
use App\Support\PolygonCoordsHelper;
use App\Support\PolygonSimplifier;
use Illuminate\Support\Facades\Cache;

/**
 * Construye GeoJSON, persiste en BD y cachea resultados topográficos.
 */
final class PoligonoTopografiaCacheService
{
    private const CACHE_TTL_HOURS = 24;

    /**
     * @param  array<int, mixed>  $polygonCoords  Anillo único o array de anillos (MultiPolygon)
     * @return array<string, mixed>
     */
    public function construirGeoJson(
        array $polygonCoords,
        float $lat,
        float $lng,
        string $intensidad,
        bool $esFallback,
    ): array {
        $rings = PolygonCoordsHelper::normalizarAnillos($polygonCoords);
        $esMultipolygon = count($rings) > 1;

        $geoRings = array_map(
            static function (array $ring): array {
                $coordinates = array_map(
                    static fn (array $point): array => [(float) $point[1], (float) $point[0]],
                    $ring
                );

                if ($coordinates !== [] && $coordinates[0] !== $coordinates[count($coordinates) - 1]) {
                    $coordinates[] = $coordinates[0];
                }

                return $coordinates;
            },
            $rings
        );

        if ($esMultipolygon) {
            $geometry = [
                'type' => 'MultiPolygon',
                'coordinates' => array_map(static fn (array $ring): array => [$ring], $geoRings),
            ];
        } else {
            $geometry = [
                'type' => 'Polygon',
                'coordinates' => [$geoRings[0] ?? []],
            ];
        }

        return [
            'type' => 'Feature',
            'properties' => [
                'intensidad' => $intensidad,
                'es_fallback' => $esFallback,
                'es_multipolygon' => $esMultipolygon,
                'fuente' => $esFallback ? 'geometric_fallback' : 'topographic',
                'centro' => ['lat' => $lat, 'lng' => $lng],
                'calculado_at' => now()->toIso8601String(),
            ],
            'geometry' => $geometry,
        ];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $polygonCoords
     * @param  array<string, mixed>  $geoJson
     */
    public function persistirReporte(
        Reporte $reporte,
        array $polygonCoords,
        array $geoJson,
        bool $esFallback,
    ): void {
        $polygonCoords = PolygonSimplifier::simplificarCoords($polygonCoords);
        $geoJson = $this->construirGeoJson(
            $polygonCoords,
            (float) $reporte->lat_reporte,
            (float) $reporte->long_reporte,
            (string) ($reporte->intensidad_propuesta ?? 'media'),
            $esFallback,
        );

        $reporte->update([
            'polygon_coords' => $polygonCoords,
            'polygon_geojson' => $geoJson,
            'polygon_calculado_at' => now(),
            'polygon_es_fallback' => $esFallback,
        ]);

        $this->guardarEnCache('reporte', $reporte->id, $geoJson);
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $polygonCoords
     * @param  array<string, mixed>  $geoJson
     */
    public function persistirInundacion(
        Inundacion $inundacion,
        array $polygonCoords,
        array $geoJson,
        bool $esFallback,
    ): void {
        $polygonCoords = PolygonSimplifier::simplificarCoords($polygonCoords);
        $geoJson = $this->construirGeoJson(
            $polygonCoords,
            (float) $inundacion->latitud,
            (float) $inundacion->longitud,
            (string) ($inundacion->intensidadCalculada() ?? 'media'),
            $esFallback,
        );

        $inundacion->update([
            'polygon_coords' => $polygonCoords,
            'polygon_geojson' => $geoJson,
            'polygon_calculado_at' => now(),
            'polygon_es_fallback' => $esFallback,
        ]);

        $this->guardarEnCache('inundacion', $inundacion->id, $geoJson);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerGeoJson(string $entityType, int $entityId): ?array
    {
        $cached = Cache::get($this->cacheKey($entityType, $entityId));
        if (is_array($cached)) {
            return $cached;
        }

        if ($entityType === 'reporte') {
            $reporte = Reporte::find($entityId);

            return is_array($reporte?->polygon_geojson) ? $reporte->polygon_geojson : null;
        }

        $inundacion = Inundacion::find($entityId);

        return is_array($inundacion?->polygon_geojson) ? $inundacion->polygon_geojson : null;
    }

    /**
     * @param  array<string, mixed>  $geoJson
     */
    public function guardarEnCache(string $entityType, int $entityId, array $geoJson): void
    {
        Cache::put(
            $this->cacheKey($entityType, $entityId),
            $geoJson,
            now()->addHours(self::CACHE_TTL_HOURS)
        );
    }

    public function cacheKey(string $entityType, int $entityId): string
    {
        return "topografia.geojson.{$entityType}.{$entityId}";
    }
}
