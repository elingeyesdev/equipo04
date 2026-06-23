<?php

namespace App\Http\Controllers;

use App\Models\CentroAsistencia;
use App\Models\Inventario;
use App\Models\TrazabilidadInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventarioController extends Controller
{
    public function index()
    {
        $centros = CentroAsistencia::with('inventario')->get();
        return view('inventario.index', compact('centros'));
    }

    public function show(CentroAsistencia $centro)
    {
        $inventario = Inventario::with(['donor', 'trazabilidad', 'registrador'])
            ->where('centro_id', $centro->id_centro)
            ->latest()
            ->get();

        return view('inventario.show', compact('centro', 'inventario'));
    }

    public function store(Request $request, CentroAsistencia $centro)
    {
        $validated = $request->validate([
            'categoria' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'donor_type' => 'required|in:anonimo,autoridad,ciudadano',
            'donor_carnet_input' => 'nullable|string|max:20|required_if:donor_type,ciudadano',
            'cantidad' => 'nullable|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
        ]);

        $apiUser = session('api_user', []);
        $authorityCarnet = $apiUser['carnet'] ?? null;
        
        $isAnonymous = false;
        $donorCarnet = null;
        
        if ($validated['donor_type'] === 'anonimo') {
            $isAnonymous = true;
        } elseif ($validated['donor_type'] === 'autoridad') {
            $donorCarnet = $authorityCarnet;
        } elseif ($validated['donor_type'] === 'ciudadano') {
            $donorCarnet = $validated['donor_carnet_input'];
        }

        DB::transaction(function () use ($validated, $centro, $donorCarnet, $authorityCarnet, $isAnonymous) {
            $item = Inventario::create([
                'centro_id' => $centro->id_centro,
                'donor_carnet' => $donorCarnet,
                'categoria' => $validated['categoria'],
                'descripcion' => $validated['descripcion'] ?? '',
                'is_anonymous' => $isAnonymous,
                'status' => 'recibido_centro',
                'registrado_por' => $authorityCarnet,
                'cantidad' => $validated['cantidad'] ?? null,
                'unidad_medida' => $validated['unidad_medida'] ?? null,
            ]);

            TrazabilidadInventario::create([
                'inventario_id' => $item->id,
                'estado_nuevo' => 'recibido_centro',
                'observacion' => 'Ingreso inicial al inventario.',
                'fecha_actualizacion' => now(),
                'registrado_por' => $authorityCarnet,
            ]);
        });

        $statusMsg = 'Ítem registrado en inventario.';
        if ($validated['donor_type'] === 'ciudadano') {
            $userExists = \App\Models\User::where('carnet', $donorCarnet)->exists();
            if (!$userExists) {
                $statusMsg = 'La donación fue registrada, pero el ciudadano con carnet ' . $donorCarnet . ' no tiene cuenta. No recibirá notificaciones hasta que se registre en la plataforma.';
            }
        }

        return redirect()->route('inventario.show', $centro->id_centro)
            ->with('status', $statusMsg);
    }

    public function bulkUpdateStatus(Request $request, CentroAsistencia $centro)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => 'exists:inventario,id',
            'status' => 'required|in:recibido_centro,almacenado,retirado,en_transito,entregado',
            'usage_details' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
        ]);

        if ($validated['status'] === 'entregado' && !$request->hasFile('photo')) {
            return redirect()->back()->withErrors(['photo' => 'La foto es obligatoria al entregar.']);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('inventario_entregas', 'public');
        }

        $apiUser = session('api_user', []);
        $authorityCarnet = $apiUser['carnet'] ?? null;

        DB::transaction(function () use ($validated, $photoPath, $authorityCarnet) {
            $items = Inventario::whereIn('id', $validated['items'])->get();

            foreach ($items as $inventario) {
                $oldStatus = $inventario->status;

                $inventario->update([
                    'status' => $validated['status'],
                    'usage_details' => $validated['usage_details'] ?? $inventario->usage_details,
                ]);

                TrazabilidadInventario::create([
                    'inventario_id' => $inventario->id,
                    'estado_anterior' => $oldStatus,
                    'estado_nuevo' => $validated['status'],
                    'observacion' => $validated['usage_details'] ?? 'Actualización masiva de estado.',
                    'fecha_actualizacion' => now(),
                    'registrado_por' => $authorityCarnet,
                    'photo_path' => $photoPath,
                ]);
            }
        });

        return redirect()->back()->with('status', count($validated['items']) . ' ítems actualizados correctamente.');
    }

    public function showItem(Inventario $inventario)
    {
        $inventario->load(['donor', 'registrador', 'trazabilidad.registrador', 'centro']);
        return view('inventario.item.show', compact('inventario'));
    }
}
