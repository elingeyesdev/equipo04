<?php

namespace App\Http\Controllers;

use App\Models\CentroAsistencia;
use App\Models\Inventario;
use App\Models\TrazabilidadInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    public function index()
    {
        $centros = CentroAsistencia::with('inventario')->get();
        return view('inventario.index', compact('centros'));
    }

    public function show(CentroAsistencia $centro)
    {
        $inventario = Inventario::with(['donor', 'trazabilidad'])
            ->where('centro_id', $centro->id_centro)
            ->latest()
            ->get();

        return view('inventario.show', compact('centro', 'inventario'));
    }

    public function store(Request $request, CentroAsistencia $centro)
    {
        $validated = $request->validate([
            'categoria' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'is_anonymous' => 'boolean',
        ]);

        $apiUser = session('api_user', []);
        $donorCarnet = $validated['is_anonymous'] ? null : ($apiUser['carnet'] ?? null);

        DB::transaction(function () use ($validated, $centro, $donorCarnet) {
            $item = Inventario::create([
                'centro_id' => $centro->id_centro,
                'donor_carnet' => $donorCarnet,
                'categoria' => $validated['categoria'],
                'descripcion' => $validated['descripcion'],
                'is_anonymous' => $validated['is_anonymous'] ?? false,
                'status' => 'recibido',
            ]);

            TrazabilidadInventario::create([
                'inventario_id' => $item->id,
                'estado_nuevo' => 'recibido',
                'observacion' => 'Ingreso inicial al inventario.',
                'fecha_actualizacion' => now(),
            ]);
        });

        return redirect()->route('inventario.show', $centro->id_centro)
            ->with('status', 'Ítem registrado en inventario.');
    }

    public function updateStatus(Request $request, Inventario $inventario)
    {
        $validated = $request->validate([
            'status' => 'required|in:recibido,en_inventario,entregado',
            'usage_details' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $inventario) {
            $oldStatus = $inventario->status;
            
            $inventario->update([
                'status' => $validated['status'],
                'usage_details' => $validated['usage_details'] ?? $inventario->usage_details,
            ]);

            TrazabilidadInventario::create([
                'inventario_id' => $inventario->id,
                'estado_anterior' => $oldStatus,
                'estado_nuevo' => $validated['status'],
                'observacion' => $validated['usage_details'] ?? 'Actualización de estado.',
                'fecha_actualizacion' => now(),
            ]);
        });

        return redirect()->back()->with('status', 'Estado del inventario actualizado correctamente.');
    }
}
