<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Inundacion;
use App\Models\Reporte;
use App\Services\PoligonoTopografiaCacheService;
use App\Support\PolygonCoordsHelper;
use App\Support\PolygonSimplifier;
use Illuminate\Console\Command;

class SimplificarPoligonos extends Command
{
    protected $signature = 'topografia:simplificar-poligonos
                            {--solo-activas : Solo inundaciones activas y sus reportes}
                            {--force : Simplificar aunque el anillo ya sea pequeño}';

    protected $description = 'Reduce polygon_coords densos (p. ej. 10k puntos) a contornos livianos';

    public function handle(PoligonoTopografiaCacheService $cacheService): int
    {
        $force = (bool) $this->option('force');
        $soloActivas = (bool) $this->option('solo-activas');

        $reportesQuery = Reporte::query()->whereNotNull('polygon_coords');
        $inundacionesQuery = Inundacion::query()->whereNotNull('polygon_coords');

        if ($soloActivas) {
            $activaIds = Inundacion::activas()->pluck('id');
            $reportesQuery->whereIn('inundacion_id', $activaIds);
            $inundacionesQuery->whereIn('id', $activaIds);
        }

        $reportesActualizados = 0;
        $inundacionesActualizadas = 0;

        foreach ($reportesQuery->cursor() as $reporte) {
            $coords = (array) ($reporte->polygon_coords ?? []);
            if (! PolygonCoordsHelper::tieneGeometriaValida($coords)) {
                continue;
            }

            if (! $force && ! PolygonSimplifier::necesitaSimplificacion($coords)) {
                continue;
            }

            $antes = $this->contarVertices($coords);
            $despuesCoords = PolygonSimplifier::simplificarCoords($coords);
            $despues = $this->contarVertices($despuesCoords);

            if ($despues >= $antes && ! $force) {
                continue;
            }

            $geoJson = $cacheService->construirGeoJson(
                $despuesCoords,
                (float) $reporte->lat_reporte,
                (float) $reporte->long_reporte,
                (string) ($reporte->intensidad_propuesta ?? 'media'),
                (bool) $reporte->polygon_es_fallback,
            );

            $reporte->update([
                'polygon_coords' => $despuesCoords,
                'polygon_geojson' => $geoJson,
            ]);

            $cacheService->guardarEnCache('reporte', $reporte->id, $geoJson);
            $reportesActualizados++;
            $this->line("  Reporte #{$reporte->id}: {$antes} → {$despues} vértices");
        }

        foreach ($inundacionesQuery->cursor() as $inundacion) {
            if ($inundacion->polygon_editado_autoridad) {
                $this->line("  Inundación #{$inundacion->id}: omitida (editada por autoridad)");

                continue;
            }

            $coords = (array) ($inundacion->polygon_coords ?? []);
            if (! PolygonCoordsHelper::tieneGeometriaValida($coords)) {
                continue;
            }

            if (! $force && ! PolygonSimplifier::necesitaSimplificacion($coords)) {
                continue;
            }

            $antes = $this->contarVertices($coords);
            $despuesCoords = PolygonSimplifier::simplificarCoords($coords);
            $despues = $this->contarVertices($despuesCoords);

            if ($despues >= $antes && ! $force) {
                continue;
            }

            $geoJson = $cacheService->construirGeoJson(
                $despuesCoords,
                (float) $inundacion->latitud,
                (float) $inundacion->longitud,
                (string) ($inundacion->intensidadCalculada() ?? 'media'),
                (bool) $inundacion->polygon_es_fallback,
            );

            $inundacion->update([
                'polygon_coords' => $despuesCoords,
                'polygon_geojson' => $geoJson,
            ]);

            $cacheService->guardarEnCache('inundacion', $inundacion->id, $geoJson);
            $inundacionesActualizadas++;
            $this->info("Inundación #{$inundacion->id}: {$antes} → {$despues} vértices");
        }

        $this->newLine();
        $this->info("Listo: {$reportesActualizados} reporte(s) + {$inundacionesActualizadas} inundación(es) optimizados.");

        return self::SUCCESS;
    }

    /**
     * @param  array<mixed>  $coords
     */
    private function contarVertices(array $coords): int
    {
        return array_sum(array_map('count', PolygonCoordsHelper::normalizarAnillos($coords)));
    }
}
