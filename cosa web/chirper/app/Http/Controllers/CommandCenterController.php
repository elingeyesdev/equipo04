<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inundacion;
use App\Models\Reporte;
use App\Models\Victima;
use App\Models\DanoMaterial;
use Illuminate\Support\Facades\DB;

class CommandCenterController extends Controller
{
    /**
     * Renderiza la vista principal del Centro de Comando.
     */
    public function index()
    {
        return view('command-center.index');
    }

    /**
     * Retorna los datos consolidados para el Timeline y Mapa interactivo.
     * Incluye inundaciones activas, sus reportes, víctimas y daños materiales.
     */
    public function getData()
    {
        // Traemos las inundaciones con toda la data relacionada, ordenada cronológicamente
        $inundaciones = Inundacion::with([
            'municipio.provincia',
            'reportes' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'victimas' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'danosMateriales' => function ($q) {
                $q->orderBy('created_at', 'asc');
            }
        ])->get();

        // Podríamos mapear la estructura para facilitar la vida al frontend
        $data = $inundaciones->map(function ($inundacion) {
            return [
                'id' => $inundacion->id,
                'estado' => $inundacion->estado,
                'municipio_id' => $inundacion->municipio_id,
                'municipio' => $inundacion->municipio->nombre ?? null,
                'provincia' => $inundacion->municipio->provincia->nombre ?? null,
                'centroide' => [
                    'lat' => $inundacion->latitud,
                    'lng' => $inundacion->longitud,
                ],
                'polygon_coords' => $inundacion->polygon_coords, // Puede ser null
                'created_at' => $inundacion->created_at,
                'updated_at' => $inundacion->updated_at,
                'intensidad_calculada' => $inundacion->intensidadCalculada(),
                'reportes' => $inundacion->reportes->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'lat' => $r->lat_reporte,
                        'lng' => $r->long_reporte,
                        'lat_reporte' => $r->lat_reporte,
                        'long_reporte' => $r->long_reporte,
                        'intensidad' => $r->intensidad_propuesta,
                        'intensidad_propuesta' => $r->intensidad_propuesta,
                        'polygon_coords' => $r->polygon_coords,
                        'updated_at' => $r->updated_at,
                        'created_at' => $r->created_at,
                    ];
                }),
                'victimas' => $inundacion->victimas->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'estado' => $v->estado,
                        'created_at' => $v->created_at
                    ];
                }),
                'danos_materiales' => $inundacion->danosMateriales->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'tipo' => $d->tipo,
                        'descripcion' => $d->descripcion,
                        'lat' => $d->latitud,
                        'lng' => $d->longitud,
                        'estado' => $d->estado,
                        'created_at' => $d->created_at
                    ];
                }),
            ];
        });

        return response()->json($data);
    }

    /**
     * Registra un daño material.
     * Solo la autoridad debería poder hacer esto (middleware aplicado en rutas).
     */
    public function registrarDano(Request $request)
    {
        $validated = $request->validate([
            'inundacion_id' => 'required|exists:inundaciones,id',
            'tipo' => 'required|string|max:50',
            'descripcion' => 'nullable|string',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'estado' => 'required|string|max:20',
        ]);

        // Asignar el registrador actual
        $validated['registrado_por'] = auth()->user()->carnet ?? null;

        $dano = DanoMaterial::create($validated);

        return response()->json(['message' => 'Daño material registrado correctamente.', 'dano' => $dano], 201);
    }

    /**
     * Realiza la fusión manual de dos inundaciones (Inundacion A absorbe Inundacion B).
     */
    public function mergeInundaciones(Request $request)
    {
        $validated = $request->validate([
            'inundacion_origen_id' => 'required|exists:inundaciones,id', // La que será absorbida
            'inundacion_destino_id' => 'required|exists:inundaciones,id|different:inundacion_origen_id', // La que permanece
        ]);

        $origen = Inundacion::findOrFail($validated['inundacion_origen_id']);
        $destino = Inundacion::findOrFail($validated['inundacion_destino_id']);

        DB::beginTransaction();
        try {
            // Mover reportes
            Reporte::where('inundacion_id', $origen->id)
                   ->update(['inundacion_id' => $destino->id]);

            // Mover víctimas
            Victima::where('inundacion_id', $origen->id)
                   ->update(['inundacion_id' => $destino->id]);

            // Mover daños materiales
            DanoMaterial::where('inundacion_id', $origen->id)
                        ->update(['inundacion_id' => $destino->id]);

            // Fusionar polígonos (simulación básica: recalcular al vuelo o simplemente conservar el de destino si es más grande)
            // En un sistema real PostGIS, aquí se haría un ST_Union().
            // Por simplicidad en este MVP, el destino recalculará su centroide y, en background, su polígono.
            
            $destino->recalcularCentroide();
            
            // Eliminar la inundación origen ya que fue absorbida
            $origen->delete();

            DB::commit();

            return response()->json(['message' => 'Inundaciones fusionadas correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al fusionar inundaciones.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para detectar y recomendar posibles fusiones.
     * Revisa si los "bounding boxes" o coordenadas de diferentes inundaciones se solapan.
     */
    public function getMergeRecommendations()
    {
        $activas = Inundacion::activas()->get();
        $recommendations = [];

        // Algoritmo ingenuo de distancia euclidiana para el MVP.
        // Si dos centroides están a menos de X grados (aprox Y km), recomendamos fusión.
        $UMBRAL_DISTANCIA = 0.01; // Aproximadamente 1km dependiendo de lat/lng

        for ($i = 0; $i < count($activas); $i++) {
            for ($j = $i + 1; $j < count($activas); $j++) {
                $inundacionA = $activas[$i];
                $inundacionB = $activas[$j];

                $distancia = sqrt(
                    pow((float)$inundacionA->latitud - (float)$inundacionB->latitud, 2) + 
                    pow((float)$inundacionA->longitud - (float)$inundacionB->longitud, 2)
                );

                if ($distancia < $UMBRAL_DISTANCIA) {
                    $recommendations[] = [
                        'inundacionA_id' => $inundacionA->id,
                        'inundacionB_id' => $inundacionB->id,
                        'distancia_grados' => $distancia,
                        'mensaje' => "Las inundaciones #{$inundacionA->id} y #{$inundacionB->id} están muy cerca. Se recomienda fusionar."
                    ];
                }
            }
        }

        return response()->json($recommendations);
    }
}
