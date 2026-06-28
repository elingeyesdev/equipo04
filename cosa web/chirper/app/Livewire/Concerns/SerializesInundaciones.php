<?php

namespace App\Livewire\Concerns;

use App\Models\Inundacion;
use App\Models\Reporte;
use App\Services\InundacionMapaService;

trait SerializesInundaciones
{
    protected function serializarActiva(Inundacion $i): array
    {
        $i->loadMissing(['reportesActivosTTL', 'reportes']);

        $reportesActivos = $i->reportesActivosTTL->map(fn ($r) => [
            'id'                   => $r->id,
            'peso'                 => $r->peso,
            'intensidad_propuesta' => $r->intensidad_propuesta,
            'intensidad_validada'  => $r->intensidad_validada,
            'intensidad_efectiva'  => $r->intensidadEfectiva(),
            'lat_reporte'          => $r->lat_reporte,
            'long_reporte'         => $r->long_reporte,
            'foto_path'            => $r->foto_path,
            'estado_validacion'    => $r->estado_validacion,
            'polygon_coords'       => $r->polygon_coords,
            'polygon_es_fallback'  => (bool) $r->polygon_es_fallback,
            'updated_at'           => $r->updated_at,
            'created_at'           => $r->created_at,
            'created_at_human'     => $r->created_at?->diffForHumans(),
        ])->toArray();

        $idsActivos = $i->reportesActivosTTL->pluck('id')->all();

        $reportesInactivos = $i->reportes->filter(function ($r) use ($idsActivos) {
            if ($r->estado_validacion === Reporte::VALIDACION_RECHAZADO) {
                return false;
            }

            return ! in_array($r->id, $idsActivos, true);
        })->map(fn ($r) => [
            'id'                   => $r->id,
            'peso'                 => $r->peso,
            'intensidad_propuesta' => $r->intensidad_propuesta,
            'intensidad_validada'  => $r->intensidad_validada,
            'intensidad_efectiva'  => $r->intensidadEfectiva(),
            'lat_reporte'          => $r->lat_reporte,
            'long_reporte'         => $r->long_reporte,
            'foto_path'            => $r->foto_path,
            'estado_validacion'    => $r->estado_validacion,
            'polygon_coords'       => $r->polygon_coords,
            'polygon_es_fallback'  => (bool) $r->polygon_es_fallback,
            'updated_at'           => $r->updated_at,
            'created_at'           => $r->created_at,
            'created_at_human'     => $r->created_at?->diffForHumans(),
            'caducado_hace'        => ($r->updated_at ?? $r->created_at)?->diffForHumans(),
        ])->toArray();

        $mapaService = app(InundacionMapaService::class);

        return [
            'id'                        => $i->id,
            'latitud'                   => $i->latitud,
            'longitud'                  => $i->longitud,
            'estado'                    => $i->estado,
            'created_at'                => $i->created_at,
            'updated_at'                => $i->updated_at,
            'address'                   => $i->reportes->first()?->address,
            'description'               => $i->reportes->first()?->description,
            'polygon_coords'            => $mapaService->polygonCoordsParaMapa($i),
            'mostrar_en_mapa'           => $i->reportesActivosTTL->isNotEmpty(),
            'polygon_editado_autoridad' => $i->polygon_editado_autoridad,
            'polygon_es_fallback'       => (bool) $i->polygon_es_fallback,
            'quorum_total'              => $i->quorumTotal(),
            'intensidad_calculada'      => $i->intensidadCalculada(),
            'esta_confirmada'           => $i->estaConfirmada(),
            'desglose_puntos'           => $i->desgloseReportes($i->reportesActivosTTL),
            'reportes_activos'          => $reportesActivos,
            'reportes_inactivos'        => $reportesInactivos,
        ];
    }

    protected function serializarTerminada(Inundacion $i): array
    {
        $i->loadMissing('reportes');

        $diff    = $i->created_at->diff($i->updated_at);
        $horas   = ($diff->days * 24) + $diff->h;
        $minutos = $diff->i;

        $reportesVinculados = $i->reportes->map(fn ($r) => [
            'id'                   => $r->id,
            'peso'                 => $r->peso,
            'intensidad_propuesta' => $r->intensidad_propuesta,
            'intensidad_validada'  => $r->intensidad_validada,
            'intensidad_efectiva'  => $r->intensidadEfectiva(),
            'lat_reporte'          => $r->lat_reporte,
            'long_reporte'         => $r->long_reporte,
            'foto_path'            => $r->foto_path,
            'estado_validacion'    => $r->estado_validacion,
            'polygon_coords'       => $r->polygon_coords,
            'polygon_es_fallback'  => (bool) $r->polygon_es_fallback,
            'updated_at'           => $r->updated_at,
            'created_at'           => $r->created_at,
            'created_at_human'     => $r->created_at?->diffForHumans(),
        ])->toArray();

        $desglose       = $i->desgloseReportes($i->reportes);
        $totalHistorico = array_sum($desglose);

        return [
            'id'                  => $i->id,
            'latitud'             => $i->latitud,
            'longitud'            => $i->longitud,
            'estado'              => $i->estado,
            'created_at'          => $i->created_at,
            'updated_at'          => $i->updated_at,
            'address'             => $i->reportes->first()?->address,
            'description'         => $i->reportes->first()?->description,
            'desglose_historico'  => $desglose,
            'quorum_historico'    => $totalHistorico,
            'reportes_vinculados' => $reportesVinculados,
            'duracion_horas'      => $horas,
            'duracion_minutos'    => $minutos,
            'duracion_texto'      => "{$horas}h {$minutos}min",
            'fecha_inicio'        => $i->created_at,
        ];
    }
}
