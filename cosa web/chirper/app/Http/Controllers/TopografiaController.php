<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inundacion;
use App\Models\Reporte;
use App\Services\PoligonoTopografiaCacheService;
use Illuminate\Http\JsonResponse;

/**
 * Sirve GeoJSON precalculado de polígonos topográficos (caché + BD).
 */
final class TopografiaController extends Controller
{
    public function showReporte(int $id, PoligonoTopografiaCacheService $cache): JsonResponse
    {
        $reporte = Reporte::find($id);

        if ($reporte === null) {
            return response()->json(['error' => 'Reporte no encontrado.'], 404);
        }

        $geoJson = $cache->obtenerGeoJson('reporte', $id);

        if ($geoJson === null) {
            return response()->json([
                'error' => 'GeoJSON no disponible. El polígono aún se está calculando.',
                'polygon_coords' => $reporte->polygon_coords,
                'polygon_es_fallback' => (bool) $reporte->polygon_es_fallback,
            ], 404);
        }

        return response()->json($geoJson);
    }

    public function showInundacion(int $id, PoligonoTopografiaCacheService $cache): JsonResponse
    {
        $inundacion = Inundacion::find($id);

        if ($inundacion === null) {
            return response()->json(['error' => 'Inundación no encontrada.'], 404);
        }

        $geoJson = $cache->obtenerGeoJson('inundacion', $id);

        if ($geoJson === null) {
            return response()->json([
                'error' => 'GeoJSON no disponible. El polígono aún se está calculando.',
                'polygon_coords' => $inundacion->polygon_coords,
                'polygon_es_fallback' => (bool) $inundacion->polygon_es_fallback,
            ], 404);
        }

        return response()->json($geoJson);
    }
}
