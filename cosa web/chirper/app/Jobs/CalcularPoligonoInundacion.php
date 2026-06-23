<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Inundacion;
use App\Models\Reporte;
use App\Services\TopografiaInundacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job: CalcularPoligonoInundacion
 *
 * Calcula el polígono de área de inundación mediante region growing
 * sobre una grilla de elevación (Open Topo Data / SRTM 30 m).
 *
 * Puede operar sobre un reporte individual (polygon_coords en reportes)
 * o sobre una inundación creada manualmente por autoridad.
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

    public function handle(TopografiaInundacionService $topografia): void
    {
        if ($this->entityType === 'inundacion') {
            $this->calcularParaInundacion($topografia);

            return;
        }

        $this->calcularParaReporte($topografia);
    }

    private function calcularParaReporte(TopografiaInundacionService $topografia): void
    {
        $reporte = Reporte::find($this->entityId);

        if ($reporte === null) {
            Log::warning("CalcularPoligonoInundacion: Reporte #{$this->entityId} no encontrado.");

            return;
        }

        if (!empty($reporte->polygon_coords)) {
            Log::info("CalcularPoligonoInundacion: Reporte #{$this->entityId} ya tiene polígono topográfico.");

            return;
        }

        $lat = (float) $reporte->lat_reporte;
        $lng = (float) $reporte->long_reporte;

        if ($lat === 0.0 && $lng === 0.0) {
            return;
        }

        $intensidad = $reporte->intensidad_propuesta ?? 'media';

        Log::info("CalcularPoligonoInundacion: Region growing para Reporte #{$this->entityId} ({$intensidad}).");

        $polygon = $topografia->calcularPoligono($lat, $lng, $intensidad);

        $reporte->update(['polygon_coords' => $polygon]);

        Log::info("CalcularPoligonoInundacion: Topografía guardada para Reporte #{$this->entityId}.");
    }

    private function calcularParaInundacion(TopografiaInundacionService $topografia): void
    {
        $inundacion = Inundacion::find($this->entityId);

        if ($inundacion === null) {
            Log::warning("CalcularPoligonoInundacion: Inundación #{$this->entityId} no encontrada.");

            return;
        }

        if ($inundacion->polygon_editado_autoridad || !empty($inundacion->polygon_coords)) {
            Log::info("CalcularPoligonoInundacion: Inundación #{$this->entityId} ya tiene polígono.");

            return;
        }

        $lat = (float) $inundacion->latitud;
        $lng = (float) $inundacion->longitud;

        if ($lat === 0.0 && $lng === 0.0) {
            return;
        }

        Log::info("CalcularPoligonoInundacion: Region growing para Inundación #{$this->entityId}.");

        $polygon = $topografia->calcularPoligono($lat, $lng, 'media');

        $inundacion->update([
            'polygon_coords' => $polygon,
            'polygon_calculado_at' => now(),
        ]);

        Log::info("CalcularPoligonoInundacion: Topografía guardada para Inundación #{$this->entityId}.");
    }
}
