<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inundacion;
use App\Models\Reporte;
use Illuminate\Support\Facades\Cache;

/**
 * Construye GeoJSON, persiste en BD y cachea resultados topográficos.
 */
final class PoligonoTopografiaCacheService
{
    private const CACHE_TTL_HOURS = 24;

    /**
     * @param  array<int, array{0: float, 1: float}>  $polygonCoords
     * @return array<string, mixed>
     */
    public function construirGeoJson(
        array $polygonCoords,
        float $lat,
        float $lng,
        string $intensidad,
        bool $esFallback,
    ): array {
        $coordinates = array_map(
            static fn (array $point): array => [(float) $point[1], (float) $point[0]],
            $polygonCoords
        );

        if ($coordinates !== [] && $coordinates[0] !== $coordinates[count($coordinates) - 1]) {
            $coordinates[] = $coordinates[0];
        }

        return [
            'type' => 'Feature',
            'properties' => [
                'intensidad' => $intensidad,
                'es_fallback' => $esFallback,
                'fuente' => $esFallback ? 'geometric_fallback' : 'topographic',
                'centro' => ['lat' => $lat, 'lng' => $lng],
                'calculado_at' => now()->toIso8601String(),
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [$coordinates],
            ],
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
