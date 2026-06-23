<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ElevationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Proxy HTTP para consultas de elevación del terreno.
 */
final class ElevationController extends Controller
{
    private const MAX_POINTS = 100;

    public function __construct(
        private readonly ElevationService $elevationService,
    ) {}

    public function getElevation(Request $request): JsonResponse
    {
        $locationsStr = (string) $request->query('locations', '');

        if ($locationsStr === '') {
            return response()->json(['error' => 'El parámetro locations es requerido.'], 422);
        }

        $rawPoints = array_filter(array_map('trim', explode('|', $locationsStr)));

        if ($rawPoints === []) {
            return response()->json(['error' => 'No se proporcionaron puntos válidos.'], 422);
        }

        if (count($rawPoints) > self::MAX_POINTS) {
            return response()->json(['error' => 'Máximo ' . self::MAX_POINTS . ' puntos por request.'], 422);
        }

        try {
            $results = $this->elevationService->fetchElevations($rawPoints);

            return response()->json(['results' => $results]);
        } catch (\Exception $e) {
            Log::error('ElevationController: Error al consultar Open Topo Data.', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'No se pudo obtener la elevación del terreno.'], 503);
        }
    }
}
