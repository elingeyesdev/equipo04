<?php



namespace App\Http\Resources;



use App\Models\Inundacion;

use App\Services\InundacionMapaService;

use App\Support\PolygonCoordsHelper;

use Illuminate\Http\Request;

use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Support\Carbon;



/**

 * Datos sanitizados de inundaciones activas para reporte rápido (sin PII).

 */

class InundacionPublicResource extends JsonResource

{

    private ?float $userLat = null;



    private ?float $userLng = null;



    public function withUserCoords(?float $lat, ?float $lng): self

    {

        $this->userLat = $lat;

        $this->userLng = $lng;



        return $this;

    }



    /**

     * @return array<string, mixed>

     */

    public function toArray(Request $request): array

    {

        /** @var Inundacion $this */

        $mapaService = app(InundacionMapaService::class);



        $intensidadCalc = $this->intensidadCalculada();

        $estaConfirmada = $this->estaConfirmada();



        $reportesActivos = $this->whenLoaded(

            'reportesActivosTTL',

            fn () => $this->reportesActivosTTL,

            collect(),

        );



        $reportesCount = $reportesActivos instanceof \Illuminate\Support\Collection

            ? $reportesActivos->count()

            : 0;



        $polygonMapa = $this->whenLoaded(

            'reportesActivosTTL',

            fn () => $mapaService->polygonCoordsParaMapa($this->resource),

            $this->polygon_coords,

        );



        $mostrarEnMapa = $this->whenLoaded(

            'reportesActivosTTL',

            fn () => $reportesActivos->isNotEmpty(),

            true,

        );



        $ultimaActividadAt = null;

        if ($reportesActivos instanceof \Illuminate\Support\Collection && $reportesActivos->isNotEmpty()) {

            $ultimaActividadAt = $reportesActivos->max('updated_at');

        }



        $distanciaMetros = null;

        $dentroContorno  = false;



        if ($this->userLat !== null && $this->userLng !== null) {

            $distanciaMetros = round($mapaService->distanciaMetros(

                $this->userLat,

                $this->userLng,

                (float) $this->latitud,

                (float) $this->longitud,

            ), 1);



            $dentroContorno = $mapaService->puntoDentroContornoActivo(

                $this->userLat,

                $this->userLng,

                $this->resource,

            );

        }



        $reportesActivosLite = $this->whenLoaded(

            'reportesActivosTTL',

            fn () => $this->reportesActivosTTL->map(static fn ($rep) => [

                'id'                  => $rep->id,

                'lat_reporte'         => (float) $rep->lat_reporte,

                'long_reporte'        => (float) $rep->long_reporte,

                'polygon_coords'      => $rep->polygon_coords,

                'polygon_es_fallback' => (bool) $rep->polygon_es_fallback,

                'updated_at'          => $rep->updated_at?->toISOString(),

            ])->values()->all(),

        );



        $polygonEsMultipolygon = $this->whenLoaded(

            'reportesActivosTTL',

            fn () => $polygonMapa !== null && PolygonCoordsHelper::esMultipolygon($polygonMapa),

            false,

        );



        return [

            'id'                     => $this->id,

            'latitud'                => $this->latitud,

            'longitud'               => $this->longitud,

            'intensidad_calculada'   => $intensidadCalc,

            'esta_confirmada'        => $estaConfirmada,

            'polygon_coords'         => $polygonMapa,

            'polygon_es_multipolygon'=> $polygonEsMultipolygon,

            'mostrar_en_mapa'        => $mostrarEnMapa,

            'reportes_activos_count' => $reportesCount,

            'reportes_activos'       => $reportesActivosLite,

            'ultima_actividad_at'    => $ultimaActividadAt?->toISOString(),

            'ultima_actividad_human' => $ultimaActividadAt

                ? Carbon::parse($ultimaActividadAt)->diffForHumans()

                : null,

            'distancia_metros'       => $distanciaMetros,

            'dentro_contorno'        => $dentroContorno,

            'updated_at'             => $this->updated_at,

        ];

    }

}


