<?php

namespace App\Http\Controllers;

use App\Models\Donacion;
use App\Models\CentroAsistencia;
use App\Models\Inundacion;
use App\Models\Victima;
use Illuminate\Http\Request;

class DonacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Donacion::with(['centro', 'donor', 'inundacion.municipio', 'victima'])->latest();

        // Filtro por Centro de Asistencia
        if ($request->filled('centro_id')) {
            $query->where('centro_id', $request->centro_id);
        }

        // Filtro por Estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro "Mis Donaciones" (si es ciudadano)
        if ($request->boolean('mine')) {
            $apiUser = (array) $request->session()->get('api_user', []);
            $myCarnet = $apiUser['carnet'] ?? null;
            if ($myCarnet) {
                $query->where('donor_carnet', $myCarnet);
            }
        }

        $donaciones = $query->paginate(15)->withQueryString();
        $centros = CentroAsistencia::orderBy('nombre')->get(['id_centro', 'nombre']);
        $inundacionesActivas = Inundacion::activas()->with('municipio')->latest()->get();
        $inundacionesTerminadas = Inundacion::terminadas()->with('municipio')->latest()->get();
        $victimas = Victima::all();

        return view('donaciones.index', [
            'donaciones' => $donaciones,
            'centros'    => $centros,
            'inundacionesActivas' => $inundacionesActivas,
            'inundacionesTerminadas' => $inundacionesTerminadas,
            'victimas'   => $victimas,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'centro_id'         => 'required|exists:centros_asistencia,id_centro',
            'donor_carnet'      => 'nullable|string|min:6|max:8|regex:/^[0-9]+$/|exists:users,carnet',
            'items_description' => 'required|string',
            'is_anonymous'      => 'boolean',
            'inundacion_id'     => 'nullable|exists:inundaciones,id',
            'victima_id'        => 'nullable|exists:victimas,id',
        ]);

        if (!$request->boolean('is_anonymous') && empty($validated['donor_carnet'])) {
            return back()->withErrors(['donor_carnet' => 'El carnet del donante es obligatorio si no es anónimo.'])->withInput();
        }

        $validated['is_anonymous'] = $request->boolean('is_anonymous');
        $validated['status'] = 'en_inventario';
        
        Donacion::create($validated);

        return redirect()->route('donaciones.index')->with('success', 'Donación registrada exitosamente.');
    }

    public function edit($id)
    {
        $donacion = Donacion::with(['centro', 'donor', 'inundacion.municipio', 'victima'])->findOrFail($id);
        
        $inundacionesActivas = Inundacion::activas()->with('municipio')->latest()->get();
        $inundacionesTerminadas = Inundacion::terminadas()->with('municipio')->latest()->get();
        $victimas = Victima::all();

        return view('donaciones.edit', [
            'donacion' => $donacion,
            'inundacionesActivas' => $inundacionesActivas,
            'inundacionesTerminadas' => $inundacionesTerminadas,
            'victimas' => $victimas,
        ]);
    }

    public function update(Request $request, $id)
    {
        $donacion = Donacion::findOrFail($id);

        if ($donacion->edit_count >= 2) {
            return redirect()->route('donaciones.index')->withErrors(['error' => 'No se puede editar esta donación, ya alcanzó el límite de ediciones.']);
        }

        $rules = [
            'status'        => 'required|string|in:en_inventario,entregado',
            'usage_details' => 'nullable|string',
            'inundacion_id' => 'required_if:status,entregado|nullable|exists:inundaciones,id',
            'victima_id'    => 'nullable|exists:victimas,id',
        ];

        if ($request->status !== $donacion->status) {
            $rules['photo'] = 'required|image|max:2048';
        } else {
            $rules['photo'] = 'nullable|image|max:2048';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('donaciones', 'public');
        }

        // Registrar cambios
        $changes = [];
        foreach (['status', 'usage_details', 'inundacion_id', 'victima_id'] as $field) {
            if (array_key_exists($field, $validated) && $donacion->$field != $validated[$field]) {
                $changes[$field] = [
                    'from' => $donacion->$field,
                    'to' => $validated[$field]
                ];
            }
        }
        
        if ($request->hasFile('photo')) {
            $changes['photo_path'] = ['from' => 'Old Photo', 'to' => 'New Photo'];
        }

        if (!empty($changes)) {
            $currentLog = is_array($donacion->edit_log) ? $donacion->edit_log : [];
            $currentLog[] = [
                'date' => now()->toDateTimeString(),
                'changes' => $changes
            ];
            $validated['edit_log'] = $currentLog;
            $validated['edit_count'] = $donacion->edit_count + 1;
        }

        $donacion->update($validated);

        return redirect()->route('donaciones.index')->with('success', 'Uso de la donación actualizado exitosamente.');
    }
}
