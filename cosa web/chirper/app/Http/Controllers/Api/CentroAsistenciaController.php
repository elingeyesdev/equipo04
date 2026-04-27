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
    public function index(Request $request): JsonResponse
    {
        $query = CentroAsistencia::query();

        if ($request->filled('provincia')) {
            $query->where('provincia', $request->provincia);
        }

        if ($request->filled('municipio')) {
            $query->where('municipio', $request->municipio);
        }

        return response()->json([
            'data' => $query->get()
        ]);
    }

    /**
     * Almacena un nuevo centro de asistencia en la base de datos.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->isAuthority()) {
            abort(403, 'Solo administradores pueden crear centros de asistencia.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'provincia' => 'required|string|max:255',
            'municipio' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'hora_apertura' => 'required|date_format:H:i',
            'hora_cierre' => 'required|date_format:H:i',
            'contacto' => 'nullable|string|max:255',
            'encargado' => 'nullable|string|max:255',
        ]);

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
        if (!$request->user()->isAuthority()) {
            abort(403, 'Solo administradores pueden modificar centros de asistencia.');
        }

        $centro = CentroAsistencia::findOrFail($id_centro);

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'provincia' => 'sometimes|required|string|max:255',
            'municipio' => 'sometimes|required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'latitud' => 'sometimes|required|numeric',
            'longitud' => 'sometimes|required|numeric',
            'hora_apertura' => 'sometimes|required|date_format:H:i',
            'hora_cierre' => 'sometimes|required|date_format:H:i',
            'contacto' => 'nullable|string|max:255',
            'encargado' => 'nullable|string|max:255',
        ]);

        $centro->fill($validated);
        
        // Actualizar manualmente la hora ya que desactivamos timestamps
        $centro->ultima_actualizacion = now();

        $centro->save();

        return response()->json([
            'data' => $centro
        ]);
    }

    /**
     * Elimina un centro de asistencia existente.
     */
    public function destroy(Request $request, $id_centro): JsonResponse
    {
        if (!$request->user()->isAuthority()) {
            abort(403, 'Solo administradores pueden eliminar centros de asistencia.');
        }

        $centro = CentroAsistencia::findOrFail($id_centro);
        $centro->delete();

        return response()->json([
            'message' => 'Centro eliminado correctamente'
        ]);
    }
}
