<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInundacionRequest;
use App\Http\Requests\Api\UpdateInundacionRequest;
use App\Http\Resources\InundacionResource;
use App\Jobs\CalcularPoligonoInundacion;
use App\Models\Inundacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InundacionController extends Controller
{
    /**
     * Lista las inundaciones activas (las únicas visibles en el mapa).
     * Las inundaciones 'terminada' y 'falsa' quedan fuera del mapa activo.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Inundacion::activas()->latest();

        if ($request->filled('provincia')) {
            $query->whereHas('municipio.provincia', function ($q) use ($request) {
                $q->where('nombre', $request->provincia);
            });
        }

        if ($request->filled('municipio')) {
            $query->whereHas('municipio', function ($q) use ($request) {
                $q->where('nombre', $request->municipio);
            });
        }

        // Eager-load la relación filtrada por TTL para calcular quórum,
        // la relación validador, y la relación reportes para dirección/descripción
        // sin disparar N+1 queries al serializar el resource.
        $reports = $query
            ->with(['validador', 'reportes', 'reportesActivosTTL'])
            ->get();

        return InundacionResource::collection($reports);
    }

    /**
     * Crea una nueva inundación manualmente (solo autoridades).
     * La intensidad ya no se almacena: se calculará dinámicamente.
     */
    public function store(StoreInundacionRequest $request): JsonResponse
    {
        $this->authorize('create', Inundacion::class);

        $data = $request->validated();
        $user = $request->user();

        $muni = \App\Models\Municipio::where('nombre', $data['municipio'])
            ->whereHas('provincia', fn($q) => $q->where('nombre', $data['provincia']))
            ->first();

        $inundacion = Inundacion::create([
            'validador_id' => $user->isAuthority() ? $user->carnet : null,
            'citizen_carnet' => $user->isCitizen() ? $user->carnet : null,
            'latitud'      => $data['latitud'],
            'longitud'     => $data['longitud'],
            'municipio_id' => $muni?->id,
            'estado'       => Inundacion::ESTADO_ACTIVA,
        ]);

        // Si un ciudadano creó la inundación desde el formulario, también
        // registramos un `Reporte` vinculado para que aparezca en "Mis reportes"
        // y quede trazable en el historial.
        if ($user->isCitizen()) {
            $reporte = \App\Models\Reporte::create([
                'user_uuid' => null,
                'citizen_carnet' => $user->carnet,
                'inundacion_id' => $inundacion->id,
                'lat_gps' => $data['latitud'],
                'long_gps' => $data['longitud'],
                'lat_reporte' => $data['latitud'],
                'long_reporte' => $data['longitud'],
                'intensidad_propuesta' => $data['intensidad_actual'] ?? 'media',
                'peso' => \App\Models\Reporte::PESO_SIN_FOTO,
                'address' => $data['address'] ?? null,
                'description' => $data['description'] ?? null,
                'estado_validacion' => \App\Models\Reporte::VALIDACION_ACEPTADO,
            ]);

            CalcularPoligonoInundacion::dispatch($reporte->id);
        } else {
            CalcularPoligonoInundacion::dispatch($inundacion->id, 'inundacion');
        }

        return response()->json([
            'data' => new InundacionResource($inundacion),
        ], 201);
    }

    /**
     * Detalle de una inundación con quórum calculado al vuelo.
     */
    public function show(Request $request, Inundacion $report): JsonResponse
    {
        $this->authorize('view', $report);

        $report->load(['validador', 'reportesActivosTTL']);

        return response()->json([
            'data' => new InundacionResource($report),
        ]);
    }

    /**
     * Actualiza una inundación (estado, coordenadas, municipio).
     * Solo autoridades pueden cambiar el estado.
     */
    public function update(UpdateInundacionRequest $request, Inundacion $report): JsonResponse
    {
        $this->authorize('update', $report);

        $data = $request->validated();
        $user = $request->user();

        // Doble check: solo autoridades modifican el estado
        if (!$user->isAuthority() && array_key_exists('estado', $data)) {
            unset($data['estado']);
        }

        $report->fill($data);
        $report->save();

        // Si la autoridad marcó la inundación como 'falsa',
        // revisar el historial de reportes falsas de cada ciudadano vinculado.
        if ($user->isAuthority() && ($data['estado'] ?? null) === Inundacion::ESTADO_FALSA) {
            $report->load('reportes');
            foreach ($report->reportes as $rep) {
                if ($rep->citizen_carnet) {
                    $citizen = User::find($rep->citizen_carnet);
                    if ($citizen) {
                        $citizen->refreshBanStatus();
                    }
                }
            }
        }

        $report->load(['validador', 'reportesActivosTTL']);

        return response()->json([
            'data' => new InundacionResource($report),
        ]);
    }


}
