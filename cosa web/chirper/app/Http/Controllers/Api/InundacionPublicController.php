<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InundacionPublicResource;
use App\Models\Inundacion;
use App\Services\InundacionMapaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InundacionPublicController extends Controller
{
    public const RADIO_CONTEXTO_METROS = 2000;

    public function __construct(
        private readonly InundacionMapaService $mapaService,
    ) {}

    /**
     * Contexto público de inundaciones activas para reporte rápido (sin auth).
     */
    public function contexto(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $userLat = isset($validated['lat']) ? (float) $validated['lat'] : null;
        $userLng = isset($validated['lng']) ? (float) $validated['lng'] : null;

        $inundaciones = Inundacion::activas()
            ->with('reportesActivosTTL')
            ->get()
            ->filter(fn (Inundacion $i): bool => $this->mapaService->inundacionTieneReportesVivos($i));

        if ($userLat !== null && $userLng !== null) {
            $inundaciones = $inundaciones
                ->map(function (Inundacion $inundacion) use ($userLat, $userLng) {
                    $dist = $this->mapaService->distanciaMetros(
                        $userLat,
                        $userLng,
                        (float) $inundacion->latitud,
                        (float) $inundacion->longitud,
                    );
                    $inundacion->setAttribute('_distancia_ctx', $dist);

                    return $inundacion;
                })
                ->filter(function (Inundacion $inundacion) use ($userLat, $userLng): bool {
                    $dist = (float) $inundacion->getAttribute('_distancia_ctx');

                    if ($dist <= self::RADIO_CONTEXTO_METROS) {
                        return true;
                    }

                    return $this->mapaService->puntoDentroContornoActivo(
                        $userLat,
                        $userLng,
                        $inundacion,
                    );
                })
                ->sort(function (Inundacion $a, Inundacion $b) use ($userLat, $userLng): int {
                    $dentroA = $this->mapaService->puntoDentroContornoActivo($userLat, $userLng, $a);
                    $dentroB = $this->mapaService->puntoDentroContornoActivo($userLat, $userLng, $b);

                    if ($dentroA !== $dentroB) {
                        return $dentroB <=> $dentroA;
                    }

                    return ((float) $a->getAttribute('_distancia_ctx')) <=> ((float) $b->getAttribute('_distancia_ctx'));
                })
                ->values();
        }

        return InundacionPublicResource::collection(
            $inundaciones->map(function (Inundacion $inundacion) use ($userLat, $userLng) {
                return (new InundacionPublicResource($inundacion))->withUserCoords($userLat, $userLng);
            }),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function serializarParaVista(?float $lat = null, ?float $lng = null): array
    {
        $controller = app(self::class);
        $request    = Request::create('/api/inundaciones/contexto', 'GET', array_filter([
            'lat' => $lat,
            'lng' => $lng,
        ], static fn ($v) => $v !== null));

        $collection = $controller->contexto($request);
        $resolved   = $collection->toArray($request);

        return $resolved['data'] ?? [];
    }
}
