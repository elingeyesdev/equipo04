<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reporte;
use App\Models\Inundacion;
use App\Services\InundacionMapaService;
use App\Services\ReporteValidacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReporteController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────
    // Reporte rápido (ciudadano / anónimo)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Crea o actualiza un reporte rápido.
     * Acceso público (sin autenticación requerida).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_uuid'           => 'nullable|uuid',
            'citizen_carnet'      => 'nullable|string',
            'lat_gps'             => 'required|numeric',
            'long_gps'            => 'required|numeric',
            'lat_reporte'         => 'required|numeric',
            'long_reporte'        => 'required|numeric',
            'intensidad_propuesta'=> 'required|string|in:baja,media,alta',
            'foto'                => 'nullable|image|max:5120',
            'address'             => 'nullable|string|max:255',
            'description'         => 'nullable|string',
        ]);

        if (empty($data['user_uuid']) && empty($data['citizen_carnet'])) {
            return response()->json(['message' => 'Se requiere UUID o Carnet'], 400);
        }

        // Validación Haversine: máximo 500 m entre GPS y punto reportado
        $distanciaKm = $this->haversineKm(
            $data['lat_gps'], $data['long_gps'],
            $data['lat_reporte'], $data['long_reporte']
        );
        $distanciaMetros = round($distanciaKm * 1000, 2);
        if ($distanciaKm > 0.5) {
            return response()->json([
                'message' => 'El reporte no puede estar a más de 500 metros de tu ubicación actual.',
            ], 422);
        }

        // Procesar foto (si existe) y calcular peso ANTES de persistir
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('fotos', 'public');
        }

        $peso = Reporte::calcularPeso($fotoPath);

        // Identificador único del reportador (carnet tiene precedencia)
        $identifier       = $data['citizen_carnet'] ?? $data['user_uuid'];
        $identifierColumn = !empty($data['citizen_carnet']) ? 'citizen_carnet' : 'user_uuid';

        // Buscar si ya tiene un reporte pendiente (1 reporte por usuario activo)
        $reportePrevio = Reporte::where($identifierColumn, $identifier)
            ->where('estado_validacion', Reporte::VALIDACION_PENDIENTE)
            ->first();

        $weatherData = $this->fetchWeatherData($data['lat_reporte'], $data['long_reporte']);

        if ($reportePrevio) {
            $reportePrevio->update([
                'lat_gps'              => $data['lat_gps'],
                'long_gps'             => $data['long_gps'],
                'lat_reporte'          => $data['lat_reporte'],
                'long_reporte'         => $data['long_reporte'],
                'intensidad_propuesta' => $data['intensidad_propuesta'],
                'foto_path'            => $fotoPath ?? $reportePrevio->foto_path,
                // Recalcular peso: si ahora sube foto, sube de 1 a 3
                'peso'                 => $fotoPath !== null
                                            ? Reporte::PESO_CON_FOTO
                                            : $reportePrevio->peso,
                'address'              => $data['address'] ?? $reportePrevio->address,
                'description'          => $data['description'] ?? $reportePrevio->description,
                'datos_clima_json'     => $weatherData,
                'distancia_gps_metros' => $distanciaMetros,
            ]);

            return response()->json([
                'message' => 'Reporte actualizado correctamente',
                'reporte' => $reportePrevio->fresh(),
            ], 200);
        }

        $reporte = Reporte::create([
            'user_uuid'            => $data['user_uuid'] ?? null,
            'citizen_carnet'       => $data['citizen_carnet'] ?? null,
            'lat_gps'              => $data['lat_gps'],
            'long_gps'             => $data['long_gps'],
            'lat_reporte'          => $data['lat_reporte'],
            'long_reporte'         => $data['long_reporte'],
            'intensidad_propuesta' => $data['intensidad_propuesta'],
            'peso'                 => $peso,
            'foto_path'            => $fotoPath,
            'address'              => $data['address'] ?? null,
            'description'          => $data['description'] ?? null,
            'datos_clima_json'     => $weatherData,
            'distancia_gps_metros' => $distanciaMetros,
            'estado_validacion'    => Reporte::VALIDACION_PENDIENTE,
        ]);

        return response()->json([
            'message' => 'Reporte creado exitosamente',
            'reporte' => $reporte,
            'eta'     => $reporte->eta,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Validación por autoridad
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Lista los reportes pendientes de validación para la autoridad.
     */
    public function pending(): JsonResponse
    {
        $mapaService = app(InundacionMapaService::class);
        $validacion = app(ReporteValidacionService::class);

        $reportes = Reporte::where('estado_validacion', Reporte::VALIDACION_PENDIENTE)
            ->whereNull('inundacion_id')
            ->with('citizen')
            ->latest()
            ->get();

        $activas = Inundacion::activas()
            ->with('reportesActivosTTL')
            ->get()
            ->filter(static fn (Inundacion $i): bool => $mapaService->inundacionTieneReportesVivos($i));

        $mapaService->enrichPendientesConCercanas($reportes, $activas);

        foreach ($reportes as $rep) {
            $rep->rechazos_previos = $validacion->contarRechazosCiudadano($rep->citizen_carnet);
        }

        $payload = $reportes->map(
            static fn (Reporte $rep): array => $mapaService->serializarPendiente($rep),
        )->values();

        return response()->json($payload);
    }

    /**
     * Valida un reporte: lo rechaza, lo vincula a una inundación existente,
     * o crea una nueva inundación a partir de él.
     *
     * Con la lógica de Quórum Dinámico (Opción 2), ya NO se modifican
     * puntos_quorum, intensidad_actual ni expira_at en la inundación.
     * El quórum se recalcula al vuelo en cada consulta.
     */
    public function validateReport(Request $request, int $id): JsonResponse
    {
        if (! $request->user()?->isAuthority()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $reporte = Reporte::findOrFail($id);

        $data = $request->validate([
            'action'              => 'required|in:vincular,crear,rechazar',
            'inundacion_id'       => 'required_if:action,vincular|exists:inundaciones,id',
            'motivo_codigo'       => 'required_if:action,rechazar|exists:motivos_rechazo,codigo',
            'motivo_texto'        => 'nullable|string|max:2000',
            'intensidad_validada' => 'nullable|string|in:baja,media,alta',
            'ajuste_comentario'   => 'nullable|string|max:2000',
        ]);

        $validacion = app(ReporteValidacionService::class);
        $validadorCarnet = (string) $request->user()->carnet;

        try {
            if ($data['action'] === 'rechazar') {
                $validacion->rechazar(
                    $reporte,
                    $validadorCarnet,
                    (string) $data['motivo_codigo'],
                    $data['motivo_texto'] ?? null,
                );

                return response()->json(['message' => 'Reporte rechazado']);
            }

            if ($data['action'] === 'crear') {
                $inundacion = $validacion->crearInundacionDesdeReporte(
                    $reporte,
                    $validadorCarnet,
                    $data['intensidad_validada'] ?? null,
                    $data['ajuste_comentario'] ?? null,
                );

                $inundacion->load('reportesActivosTTL');

                return response()->json([
                    'message'    => 'Nueva inundación creada',
                    'inundacion' => $this->inundacionConQuorum($inundacion),
                ], 201);
            }

            if ($data['action'] === 'vincular') {
                $inundacion = $validacion->aceptarYVincular(
                    $reporte,
                    (int) $data['inundacion_id'],
                    $validadorCarnet,
                    $data['intensidad_validada'] ?? null,
                    $data['ajuste_comentario'] ?? null,
                );

                $inundacion->load('reportesActivosTTL');

                return response()->json([
                    'message'    => 'Reporte vinculado exitosamente',
                    'inundacion' => $this->inundacionConQuorum($inundacion),
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json(['message' => 'Acción no reconocida'], 422);
    }

    /**
     * Catálogo de motivos de rechazo activos.
     */
    public function motivosRechazo(): JsonResponse
    {
        $motivos = \App\Models\MotivoRechazo::query()
            ->activos()
            ->orderBy('codigo')
            ->get(['codigo', 'label_autoridad', 'label_ciudadano', 'requiere_nota']);

        return response()->json($motivos);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Devuelve un array con los datos de la inundación más el quórum calculado.
     */
    private function inundacionConQuorum(Inundacion $inundacion): array
    {
        return array_merge($inundacion->toArray(), [
            'quorum_total'         => $inundacion->quorumTotal(),
            'intensidad_calculada' => $inundacion->intensidadCalculada(),
            'esta_confirmada'      => $inundacion->estaConfirmada(),
        ]);
    }

    /**
     * Obtiene datos meteorológicos de Open-Meteo para las coordenadas dadas.
     * Falla silenciosamente si la API no responde en 5 segundos.
     */
    private function fetchWeatherData(float $lat, float $lng): ?array
    {
        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude'  => $lat,
                'longitude' => $lng,
                'current'   => 'precipitation',
                'timezone'  => 'auto',
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception) {
            // Fallamos silenciosamente; el reporte igual se crea
        }

        return null;
    }

    /**
     * Calcula la distancia entre dos puntos geográficos usando la fórmula
     * de Haversine. Devuelve el resultado en kilómetros.
     */
    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R    = 6371; // Radio de la Tierra en km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
