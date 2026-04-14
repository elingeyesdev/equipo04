<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CentroAsistencia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CentroAsistenciaController extends Controller
{
    /**
     * Retorna la lista de centros de asistencia.
     * Si fuera a crecer mucho, consideraríamos paginación, pero para visualización de mapas solemos retornar un batch completo.
     */
    public function index(): JsonResponse
    {
        $centros = CentroAsistencia::all();
        
        return response()->json([
            'data' => $centros
        ]);
    }

    /**
     * Almacena un nuevo centro de asistencia en la base de datos.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string|in:Acopio,Donación,Mixto',
            'direccion' => 'nullable|string|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'estado' => 'nullable|string|in:Abierto,Lleno,Cerrado',
            'contacto_emergencia' => 'nullable|string|max:255',
            'encargado' => 'nullable|string|max:255',
            'insumos_necesarios' => 'nullable|string',
        ]);

        // Si no se proveyó estado explícito, forzamos "Abierto"
        if (!isset($validated['estado'])) {
            $validated['estado'] = 'Abierto';
        }

        // Guardamos
        $centro = CentroAsistencia::create($validated);

        return response()->json([
            'data' => $centro
        ], 201);
    }

    /**
     * Actualiza un centro de asistencia existente.
     */
    public function update(Request $request, $id_centro): JsonResponse
    {
        $centro = CentroAsistencia::findOrFail($id_centro);

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'tipo' => 'sometimes|required|string|in:Acopio,Donación,Mixto',
            'direccion' => 'nullable|string|max:255',
            'latitud' => 'sometimes|required|numeric',
            'longitud' => 'sometimes|required|numeric',
            'estado' => 'sometimes|required|string|in:Abierto,Lleno,Cerrado',
            'contacto_emergencia' => 'nullable|string|max:255',
            'encargado' => 'nullable|string|max:255',
            'insumos_necesarios' => 'nullable|string',
        ]);

        $centro->fill($validated);
        
        // Actualizar manualmente la hora ya que desactivamos timestamps
        $centro->ultima_actualizacion = now();

        $centro->save();

        return response()->json([
            'data' => $centro
        ]);
    }
}
