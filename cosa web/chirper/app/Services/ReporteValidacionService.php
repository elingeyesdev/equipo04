<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\CalcularPoligonoInundacion;
use App\Models\Inundacion;
use App\Models\Reporte;

/**
 * Centraliza la lógica de validación de reportes por autoridad
 * y el disparo del cálculo topográfico asociado.
 */
final class ReporteValidacionService
{
    public function crearInundacionDesdeReporte(Reporte $reporte, string $validadorCarnet): Inundacion
    {
        $inundacion = Inundacion::create([
            'latitud'      => $reporte->lat_reporte,
            'longitud'     => $reporte->long_reporte,
            'estado'       => Inundacion::ESTADO_ACTIVA,
            'validador_id' => $validadorCarnet,
        ]);

        $inundacion->resolverMunicipio();

        $reporte->update([
            'estado_validacion' => Reporte::VALIDACION_ACEPTADO,
            'inundacion_id'     => $inundacion->id,
        ]);

        $this->dispatchTopografia($reporte->fresh());

        return $inundacion->fresh();
    }

    public function aceptarYVincular(Reporte $reporte, int $inundacionId): Inundacion
    {
        $inundacion = Inundacion::findOrFail($inundacionId);

        $reporte->update([
            'estado_validacion' => Reporte::VALIDACION_ACEPTADO,
            'inundacion_id'     => $inundacion->id,
        ]);

        $inundacion->recalcularCentroide();

        $this->dispatchTopografia($reporte->fresh());

        return $inundacion->fresh();
    }

    public function rechazar(Reporte $reporte): void
    {
        $reporte->update([
            'estado_validacion' => Reporte::VALIDACION_RECHAZADO,
            'inundacion_id'     => null,
        ]);
    }

    private function dispatchTopografia(Reporte $reporte): void
    {
        CalcularPoligonoInundacion::dispatch($reporte->id);

        if ($reporte->inundacion_id !== null) {
            CalcularPoligonoInundacion::dispatch($reporte->inundacion_id, 'inundacion');
        }
    }
}
