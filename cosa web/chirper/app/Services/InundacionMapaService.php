<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inundacion;
use App\Models\Reporte;
use App\Support\PolygonCoordsHelper;
use App\Support\PolygonSimplifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reglas de visualización del mapa de calor y vinculación geográfica.
 * Toda la lógica de TTL, polígonos visibles y distancia de vinculación vive aquí.
 */
final class InundacionMapaService
{
    /** Distancia máxima (Haversine) entre el reporte pendiente y el centroide de la inundación para vincular. */
    public const RADIO_VINCULACION_METROS = 300;

    public function __construct(
        private readonly TopografiaInundacionService $topografia,
    ) {}

    public function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);
        $dLat = $lat2Rad - $lat1Rad;
        $dLng = $lng2Rad - $lng1Rad;
        $a = sin($dLat / 2) ** 2 + cos($lat1Rad) * cos($lat2Rad) * sin($dLng / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function reporteEstaVivo(Reporte $reporte): bool
    {
        if (in_array($reporte->estado_validacion, [Reporte::VALIDACION_RECHAZADO, 'rechazada'], true)) {
            return false;
        }

        $referencia = $reporte->updated_at ?? $reporte->created_at;
        if ($referencia === null) {
            return false;
        }

        $ttlInicio = Carbon::now()->subHours(Inundacion::TTL_HORAS);

        return $referencia->greaterThanOrEqualTo($ttlInicio);
    }

    public function inundacionTieneReportesVivos(Inundacion $inundacion): bool
    {
        if ($inundacion->relationLoaded('reportesActivosTTL')) {
            return $inundacion->reportesActivosTTL->isNotEmpty();
        }

        return $inundacion->reportesActivosTTL()->exists();
    }

    /**
     * Polígono unificado solo con reportes dentro del TTL.
     * Null si no hay reportes vivos (no debe pintarse mancha en el mapa).
     *
     * @return array<int, mixed>|null
     */
    public function polygonCoordsParaMapa(Inundacion $inundacion): ?array
    {
        if (! $this->inundacionTieneReportesVivos($inundacion)) {
            return null;
        }

        if ($inundacion->polygon_editado_autoridad && PolygonCoordsHelper::tieneGeometriaValida((array) ($inundacion->polygon_coords ?? []))) {
            return $inundacion->polygon_coords;
        }

        $reportesVivos = $inundacion->relationLoaded('reportesActivosTTL')
            ? $inundacion->reportesActivosTTL
            : $inundacion->reportesActivosTTL()->get();

        $poligonosReportes = $reportesVivos
            ->filter(static fn (Reporte $r): bool => PolygonCoordsHelper::tieneGeometriaValida((array) ($r->polygon_coords ?? [])))
            ->map(static fn (Reporte $r): array => PolygonCoordsHelper::normalizarAnillos((array) $r->polygon_coords)[0])
            ->values()
            ->all();

        if (count($poligonosReportes) >= 2) {
            $epicentros = $reportesVivos
                ->map(static fn (Reporte $r): array => [
                    (float) $r->lat_reporte,
                    (float) $r->long_reporte,
                ])
                ->values()
                ->all();

            return $this->topografia->unirPoligonosEnAnilloUnico($poligonosReportes, $epicentros);
        }

        if (count($poligonosReportes) === 1) {
            return PolygonSimplifier::simplificarCoords($poligonosReportes[0]);
        }

        return null;
    }

    /**
     * @return list<array<int, array{0: float, 1: float}>>
     */
    private function anillosContornoActivo(Inundacion $inundacion): array
    {
        if (! $this->inundacionTieneReportesVivos($inundacion)) {
            return [];
        }

        $coords = $this->polygonCoordsParaMapa($inundacion);
        if ($coords !== null && PolygonCoordsHelper::tieneGeometriaValida($coords)) {
            return PolygonCoordsHelper::normalizarAnillos($coords);
        }

        $reportesVivos = $inundacion->relationLoaded('reportesActivosTTL')
            ? $inundacion->reportesActivosTTL
            : $inundacion->reportesActivosTTL()->get();

        $anillos = [];
        foreach ($reportesVivos as $reporte) {
            if (! PolygonCoordsHelper::tieneGeometriaValida((array) ($reporte->polygon_coords ?? []))) {
                continue;
            }
            foreach (PolygonCoordsHelper::normalizarAnillos((array) $reporte->polygon_coords) as $ring) {
                if (count($ring) >= 3) {
                    $anillos[] = $ring;
                }
            }
        }

        return $anillos;
    }

    public function puntoDentroContornoActivo(float $lat, float $lng, Inundacion $inundacion): bool
    {
        if ($lat === 0.0 && $lng === 0.0) {
            return false;
        }

        foreach ($this->anillosContornoActivo($inundacion) as $ring) {
            if ($this->topografia->pointInPolygon($lat, $lng, $ring)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inundaciones activas vinculables: dentro del contorno activo OR ≤ {@see RADIO_VINCULACION_METROS} m del centroide.
     *
     * @param  iterable<int, Inundacion>  $candidatas
     * @return list<array{id: int, latitud: mixed, longitud: mixed, intensidad_calculada: string|null, distancia_metros: float, dentro_contorno: bool}>
     */
    public function inundacionesVinculablesParaReporte(Reporte $reporte, iterable $candidatas): array
    {
        $latRep = (float) $reporte->lat_reporte;
        $lngRep = (float) $reporte->long_reporte;

        if ($latRep === 0.0 && $lngRep === 0.0) {
            return [];
        }

        $cercanas = [];

        foreach ($candidatas as $inundacion) {
            if (! $this->inundacionTieneReportesVivos($inundacion)) {
                continue;
            }

            $dist = $this->distanciaMetros(
                $latRep,
                $lngRep,
                (float) $inundacion->latitud,
                (float) $inundacion->longitud,
            );

            $dentroContorno = $this->puntoDentroContornoActivo($latRep, $lngRep, $inundacion);

            if (! $dentroContorno && $dist > self::RADIO_VINCULACION_METROS) {
                continue;
            }

            $cercanas[] = [
                'id'                   => $inundacion->id,
                'latitud'              => $inundacion->latitud,
                'longitud'             => $inundacion->longitud,
                'intensidad_calculada' => $inundacion->intensidadCalculada(),
                'distancia_metros'     => round($dist, 1),
                'dentro_contorno'      => $dentroContorno,
            ];
        }

        usort($cercanas, static function (array $a, array $b): int {
            if ($a['dentro_contorno'] !== $b['dentro_contorno']) {
                return $b['dentro_contorno'] <=> $a['dentro_contorno'];
            }

            return $a['distancia_metros'] <=> $b['distancia_metros'];
        });

        return $cercanas;
    }

    /**
     * @deprecated Use inundacionesVinculablesParaReporte()
     *
     * @param  iterable<int, Inundacion>  $candidatas
     * @return list<array{id: int, latitud: mixed, longitud: mixed, intensidad_calculada: string|null, distancia_metros: float}>
     */
    public function inundacionesVinculablesCerca(Reporte $reporte, iterable $candidatas): array
    {
        return array_map(
            static fn (array $item): array => array_diff_key($item, ['dentro_contorno' => true]),
            $this->inundacionesVinculablesParaReporte($reporte, $candidatas),
        );
    }

    /**
     * @param  Collection<int, Reporte>  $pendientes
     * @param  iterable<int, Inundacion>  $inundacionesActivas
     */
    public function enrichPendientesConCercanas(Collection $pendientes, iterable $inundacionesActivas): void
    {
        foreach ($pendientes as $rep) {
            $cercanas = $this->inundacionesVinculablesParaReporte($rep, $inundacionesActivas);
            $rep->cercanas = collect($cercanas);
            $rep->dentro_contorno_activo = collect($cercanas)->contains(
                static fn (array $c): bool => ($c['dentro_contorno'] ?? false) === true,
            );
            $rep->solo_vincular = (bool) $rep->dentro_contorno_activo;
        }
    }

    /**
     * Serializa un reporte pendiente enriquecido para JSON (API / mapa).
     *
     * @return array<string, mixed>
     */
    public function serializarPendiente(Reporte $rep): array
    {
        $cercanas = collect($rep->cercanas ?? [])->values()->all();
        $reporterName = $rep->relationLoaded('citizen') && $rep->citizen
            ? $rep->citizen->name
            : ($rep->citizen_carnet ? 'Carnet '.$rep->citizen_carnet : 'Ciudadano anónimo');

        return [
            'id'                     => $rep->id,
            'citizen_carnet'         => $rep->citizen_carnet,
            'reporter_name'          => $reporterName,
            'lat_reporte'            => $rep->lat_reporte,
            'long_reporte'           => $rep->long_reporte,
            'lat_gps'                => $rep->lat_gps,
            'long_gps'               => $rep->long_gps,
            'intensidad_propuesta'   => $rep->intensidad_propuesta,
            'description'            => $rep->description,
            'address'                => $rep->address,
            'peso'                   => $rep->peso,
            'distancia_gps_metros'   => $rep->distancia_gps_metros,
            'estado_validacion'      => $rep->estado_validacion,
            'inundacion_id'          => $rep->inundacion_id,
            'created_at'             => $rep->created_at?->toIso8601String(),
            'cercanas'               => $cercanas,
            'solo_vincular'          => (bool) ($rep->solo_vincular ?? false),
            'dentro_contorno_activo' => (bool) ($rep->dentro_contorno_activo ?? false),
            'rechazos_previos'       => $rep->rechazos_previos ?? 0,
        ];
    }

    /**
     * @param  iterable<int, Inundacion>  $inundacionesActivas
     */
    public function reporteDentroInundacionActiva(Reporte $reporte, iterable $inundacionesActivas): bool
    {
        $latRep = (float) $reporte->lat_reporte;
        $lngRep = (float) $reporte->long_reporte;

        foreach ($inundacionesActivas as $inundacion) {
            if (! $this->inundacionTieneReportesVivos($inundacion)) {
                continue;
            }

            if ($this->puntoDentroContornoActivo($latRep, $lngRep, $inundacion)) {
                return true;
            }
        }

        return false;
    }
}
