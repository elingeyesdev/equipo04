<?php

namespace App\Http\Resources;

use App\Models\Inundacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * InundacionResource — Quórum Dinámico (Opción 2)
 *
 * Expone los datos calculados al vuelo (quórum, intensidad, confirmación)
 * a partir de los reportes activos dentro del TTL (3 horas).
 * Requiere que 'reportesActivosTTL' esté eager-loaded para evitar N+1.
 */
class InundacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Inundacion $this */

        // ── Quórum dinámico ──────────────────────────────────────────────
        // Si la relación ya fue eager-loaded usamos la colección en memoria;
        // si no, se lanzará una query adicional (evitar en listados).
        $quorumTotal        = $this->quorumTotal();
        $intensidadCalc     = $this->intensidadCalculada();
        $estaConfirmada     = $this->estaConfirmada();

        // ── Reportes activos (dentro del TTL) para el panel lateral ──────
        $reportesActivosTTL = $this->whenLoaded(
            'reportesActivosTTL',
            fn () => $this->reportesActivosTTL->map(fn ($rep) => [
                'id'                   => $rep->id,
                'peso'                 => $rep->peso,
                'intensidad_propuesta' => $rep->intensidad_propuesta,
                'lat_reporte'          => $rep->lat_reporte,
                'long_reporte'         => $rep->long_reporte,
                'foto_path'            => $rep->foto_path,
                'datos_clima_json'     => $rep->datos_clima_json,
                'estado_validacion'    => $rep->estado_validacion,
                'polygon_coords'       => $rep->polygon_coords,
                'polygon_es_fallback'  => (bool) $rep->polygon_es_fallback,
                'updated_at'           => $rep->updated_at,
                'created_at'           => $rep->created_at,
                'created_at_human'     => Carbon::parse($rep->created_at)->diffForHumans(),
            ])
        );

        // ── Desglose de puntos por categoría (para panel lateral) ────────
        $desglosePuntos = $this->whenLoaded('reportesActivosTTL', function () {
            $desglose = ['baja' => 0, 'media' => 0, 'alta' => 0];
            foreach ($this->reportesActivosTTL as $rep) {
                $cat = $rep->intensidad_propuesta;
                if (array_key_exists($cat, $desglose)) {
                    $desglose[$cat] += $rep->peso;
                }
            }
            return $desglose;
        });

        return [
            // ── Datos estáticos ──────────────────────────────────────────
            'id'         => $this->id,
            'latitud'    => $this->latitud,
            'longitud'   => $this->longitud,
            'estado'     => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Dirección/descripción tomada del primer reporte vinculado
            'address'     => $this->reportes->first()?->address,
            'description' => $this->reportes->first()?->description,

            // Validador (autoridad que creó o última que modificó)
            'validador' => $this->whenLoaded(
                'validador',
                fn () => [
                    'carnet' => $this->validador?->carnet,
                    'name'   => $this->validador?->name,
                ]
            ),

            // ── Datos calculados dinámicamente ───────────────────────────
            'quorum_total'         => $quorumTotal,
            'intensidad_calculada' => $intensidadCalc,
            'esta_confirmada'      => $estaConfirmada,
            'desglose_puntos'      => $desglosePuntos,

            // ── Polígono de área de inundación (calculado por CalcularPoligonoInundacion Job) ──
            // Array de [lat, lng] pares. Null si aún no fue calculado.
            'polygon_coords'            => $this->polygon_coords,
            'polygon_calculado_at'      => $this->polygon_calculado_at?->toISOString(),
            'polygon_editado_autoridad' => (bool) $this->polygon_editado_autoridad,
            'polygon_es_fallback'       => (bool) $this->polygon_es_fallback,

            // ── Detalle de reportes para el panel lateral del mapa ───────
            'reportes_activos' => $reportesActivosTTL,
        ];
    }
}
