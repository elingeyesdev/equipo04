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
    public function index(Request $request)
    {
        $search = $request->input('search');

        $centros = CentroAsistencia::with('inventario')
            ->when($search, function ($query) use ($search) {
                $query->where('nombre', 'ilike', '%' . $search . '%')
                      ->orWhere('direccion', 'ilike', '%' . $search . '%');
            })
            ->get();

        $stats = [
            'total_centros' => CentroAsistencia::count(),
            'total_items' => Inventario::count(),
            'items_hoy' => Inventario::whereDate('created_at', today())->count(),
        ];

        return view('inventario.index', compact('centros', 'stats', 'search'));
    }

    public function show(Request $request, CentroAsistencia $centro)
    {
        $status = $request->input('status');
        $categoria = $request->input('categoria');
        $recent = $request->input('recent');

        $query = Inventario::with(['donor', 'trazabilidad', 'registrador'])
            ->where('centro_id', $centro->id_centro);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        } elseif ($status !== 'all') {
            $query->whereNotIn('status', ['entregado', 'desechado']);
        }

        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        if ($recent === '30_days') {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $inventario = $query->latest()->get();

        $stats = [
            'total' => Inventario::where('centro_id', $centro->id_centro)->count(),
            'en_almacen' => Inventario::where('centro_id', $centro->id_centro)->whereIn('status', ['recibido_centro', 'almacenado'])->count(),
            'entregados' => Inventario::where('centro_id', $centro->id_centro)->where('status', 'entregado')->count(),
        ];

        $inundaciones = \App\Models\Inundacion::activas()->get();
        $victimas = \App\Models\Victima::all(); // Puedes optimizar esto a víctimas activas si tienes un scope

        return view('inventario.show', compact('centro', 'inventario', 'inundaciones', 'victimas', 'stats', 'status', 'categoria', 'recent'));
    }

    public function store(Request $request, CentroAsistencia $centro)
    {
        $validated = $request->validate([
            'categoria' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'donor_type' => 'required|in:anonimo,autoridad,ciudadano',
            'donor_carnet_input' => 'nullable|digits_between:6,9|required_if:donor_type,ciudadano',
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
            'status' => 'required|in:recibido_centro,almacenado,en_transito,entregado,desechado',
            'usage_details' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
            'inundacion_id' => 'nullable|exists:inundaciones,id',
            'victima_id' => 'nullable|exists:victimas,id',
        ]);

        if ($validated['status'] === 'entregado' && !$request->hasFile('photo')) {
            return redirect()->back()->withErrors(['photo' => 'La foto es obligatoria al entregar.']);
        }

        if ($validated['status'] === 'desechado' && empty($validated['usage_details'])) {
            return redirect()->back()->withErrors(['usage_details' => 'Debes justificar por qué se está desechando (Observación requerida).']);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('inventario_entregas', 'public');
        }

        $apiUser = session('api_user', []);
        $authorityCarnet = $apiUser['carnet'] ?? null;

        $flujo = [
            'recibido_centro' => 1,
            'almacenado' => 2,
            'en_inventario' => 2, // Alias por si hay datos viejos
            'retirado' => 3,      // Alias (fusionado con en_transito)
            'en_transito' => 3,
            'entregado' => 4,
        ];

        DB::transaction(function () use ($validated, $photoPath, $authorityCarnet, $flujo) {
            $items = Inventario::whereIn('id', $validated['items'])->get();

            // Verificamos que todos tengan el mismo status inicial
            $statusInicial = $items->first()->status;
            foreach ($items as $item) {
                if ($item->status !== $statusInicial) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'No puedes seleccionar ítems con diferentes estados para una actualización masiva.']);
                }
                if ($item->status === 'desechado') {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Los ítems desechados no pueden cambiar de estado.']);
                }
            }

            $nuevoStatus = $validated['status'];

            // Lógica para 'desechado'
            if ($nuevoStatus === 'desechado') {
                $permitidos = ['comida', 'bebida', 'medicamentos'];
                foreach ($items as $item) {
                    if (!in_array($item->categoria, $permitidos)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['status' => 'Solo se pueden desechar donaciones de comida, bebida o medicamentos.']);
                    }
                }
            } else {
                // Validación estricta del flujo normal
                if (!isset($flujo[$statusInicial]) || !isset($flujo[$nuevoStatus])) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['status' => 'Estado inválido.']);
                }
                $pasoActual = $flujo[$statusInicial];
                $pasoSiguiente = $flujo[$nuevoStatus];

                if ($pasoSiguiente !== $pasoActual + 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['status' => 'El estado seleccionado no corresponde al siguiente paso lógico del ciclo de vida.']);
                }
            }

            foreach ($items as $inventario) {
                $oldStatus = $inventario->status;

                $dataToUpdate = [
                    'status' => $nuevoStatus,
                    'usage_details' => $validated['usage_details'] ?? $inventario->usage_details,
                ];

                if (!empty($validated['inundacion_id'])) {
                    $dataToUpdate['inundacion_id'] = $validated['inundacion_id'];
                }
                
                if (!empty($validated['victima_id'])) {
                    $dataToUpdate['victima_id'] = $validated['victima_id'];
                }

                $inventario->update($dataToUpdate);

                TrazabilidadInventario::create([
                    'inventario_id' => $inventario->id,
                    'estado_anterior' => $oldStatus,
                    'estado_nuevo' => $nuevoStatus,
                    'observacion' => $validated['usage_details'] ?? 'No se proporcionaron comentarios u observaciones adicionales.',
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
