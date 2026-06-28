<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Inundacion;
use App\Models\Reporte;
use App\Services\PoligonoTopografiaCacheService;
use App\Services\TopografiaInundacionService;
use App\Support\PolygonCoordsHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job: CalcularPoligonoInundacion
 *
 * Calcula el polígono de área de inundación mediante region growing
 * sobre una grilla de elevación (Open Topo Data / SRTM 30 m).
 * Para inundaciones, fusiona los polígonos de sus reportes vinculados.
 */
final class CalcularPoligonoInundacion implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private readonly int $entityId,
        private readonly string $entityType = 'reporte',
    ) {}

    public function handle(
        TopografiaInundacionService $topografia,
        PoligonoTopografiaCacheService $cacheService,
    ): void {
        if ($this->entityType === 'inundacion') {
            $this->calcularParaInundacion($topografia, $cacheService);

            return;
        }

        $this->calcularParaReporte($topografia, $cacheService);
    }

    private function calcularParaReporte(
        TopografiaInundacionService $topografia,
        PoligonoTopografiaCacheService $cacheService,
    ): void {
        $reporte = Reporte::find($this->entityId);

        if ($reporte === null) {
            Log::warning("CalcularPoligonoInundacion: Reporte #{$this->entityId} no encontrado.");

            return;
        }

        if (!empty($reporte->polygon_coords)) {
            Log::info("CalcularPoligonoInundacion: Reporte #{$this->entityId} ya tiene polígono topográfico.");
            $this->dispatchUnionInundacion($reporte->inundacion_id);

            return;
        }

        $lat = (float) $reporte->lat_reporte;
        $lng = (float) $reporte->long_reporte;

        if ($lat === 0.0 && $lng === 0.0) {
            return;
        }

        $intensidad = $reporte->intensidadEfectiva();

        Log::info("CalcularPoligonoInundacion: Region growing para Reporte #{$this->entityId} ({$intensidad}).");

        $resultado = $topografia->calcularResultado($lat, $lng, $intensidad);
        $geoJson = $cacheService->construirGeoJson(
            $resultado['polygon_coords'],
            $lat,
            $lng,
            $intensidad,
            $resultado['es_fallback'],
        );

        $cacheService->persistirReporte(
            $reporte,
            $resultado['polygon_coords'],
            $geoJson,
            $resultado['es_fallback'],
        );

        Log::info("CalcularPoligonoInundacion: Topografía guardada para Reporte #{$this->entityId} ({$resultado['fuente']}).");

        $this->dispatchUnionInundacion($reporte->fresh()?->inundacion_id);
    }

    private function calcularParaInundacion(
        TopografiaInundacionService $topografia,
        PoligonoTopografiaCacheService $cacheService,
    ): void {
        $inundacion = Inundacion::with('reportesActivosTTL')->find($this->entityId);

        if ($inundacion === null) {
            Log::warning("CalcularPoligonoInundacion: Inundación #{$this->entityId} no encontrada.");

            return;
        }

        if ($inundacion->polygon_editado_autoridad) {
            Log::info("CalcularPoligonoInundacion: Inundación #{$this->entityId} tiene polígono editado por autoridad.");

            return;
        }

        $lat = (float) $inundacion->latitud;
        $lng = (float) $inundacion->longitud;

        if ($lat === 0.0 && $lng === 0.0) {
            return;
        }

        $poligonosReportes = $inundacion->reportesActivosTTL
            ->filter(static fn (Reporte $r): bool => PolygonCoordsHelper::tieneGeometriaValida((array) ($r->polygon_coords ?? [])))
            ->map(static fn (Reporte $r): array => PolygonCoordsHelper::normalizarAnillos((array) $r->polygon_coords)[0])
            ->values()
            ->all();

        $intensidad = $inundacion->intensidadCalculada() ?? 'media';
        $esFallback = false;
        $fuente = 'union_reportes';

        if (count($poligonosReportes) >= 2) {
            Log::info('CalcularPoligonoInundacion: Unión adaptativa de '.count($poligonosReportes)." polígonos (TTL vigente) para Inundación #{$this->entityId}.");

            $epicentros = $inundacion->reportesActivosTTL
                ->map(static fn (Reporte $r): array => [
                    (float) $r->lat_reporte,
                    (float) $r->long_reporte,
                ])
                ->values()
                ->all();

            $polygonCoords = $topografia->unirPoligonosEnAnilloUnico($poligonosReportes, $epicentros);

            $esFallback = $inundacion->reportesActivosTTL->contains(
                static fn (Reporte $r): bool => (bool) $r->polygon_es_fallback
            );
        } elseif (count($poligonosReportes) === 1) {
            $polygonCoords = $poligonosReportes[0];
            $esFallback = (bool) $inundacion->reportesActivosTTL->first(
                static fn (Reporte $r): bool => PolygonCoordsHelper::tieneGeometriaValida((array) ($r->polygon_coords ?? []))
            )?->polygon_es_fallback;
            $fuente = 'reporte_unico';
        } else {
            Log::info("CalcularPoligonoInundacion: Sin polígonos de reportes, region growing para Inundación #{$this->entityId}.");

            $resultado = $topografia->calcularResultado($lat, $lng, $intensidad);
            $polygonCoords = $resultado['polygon_coords'];
            $esFallback = $resultado['es_fallback'];
            $fuente = $resultado['fuente'];
        }

        if (!PolygonCoordsHelper::tieneGeometriaValida($polygonCoords)) {
            Log::warning("CalcularPoligonoInundacion: Inundación #{$this->entityId} sin geometría válida tras cálculo.");

            return;
        }

        $geoJson = $cacheService->construirGeoJson(
            $polygonCoords,
            $lat,
            $lng,
            $intensidad,
            $esFallback,
        );

        $cacheService->persistirInundacion(
            $inundacion,
            $polygonCoords,
            $geoJson,
            $esFallback,
        );

        Log::info("CalcularPoligonoInundacion: Topografía guardada para Inundación #{$this->entityId} ({$fuente}).");
    }

    private function dispatchUnionInundacion(?int $inundacionId): void
    {
        if ($inundacionId === null) {
            return;
        }

        self::dispatch($inundacionId, 'inundacion');
    }
}
