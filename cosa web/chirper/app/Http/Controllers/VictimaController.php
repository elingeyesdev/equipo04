<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreVictimaRequest;
use App\Http\Requests\UpdateVictimaRequest;
use App\Models\Inundacion;
use App\Models\Municipio;
use App\Models\Provincia;
use App\Models\Victima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * VictimaController — CRUD completo de víctimas.
 *
 * Permisos:
 *  - index, show → authority y citizen (requiere sesión)
 *  - create, store, edit, update, destroy → solo authority
 *    (protegido por middleware EnsureApiAuthority en las rutas)
 */
final class VictimaController extends Controller
{
    // ── Listado + Filtros ─────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $user = (array) $request->session()->get('api_user', []);
        $role = (string) ($user['role'] ?? '');

        // Cargamos todos los registros con eager loading.
        // El filtrado se realiza en el cliente (JS) sin recargar la página.
        $victimas = Victima::query()
            ->with(['inundacion.municipio.provincia'])
            ->latest()
            ->get();

        $inundaciones = Inundacion::with('municipio.provincia')->latest()->get();
        $provincias   = Provincia::orderBy('nombre')->get();
        $municipios   = Municipio::orderBy('nombre')->get();

        return view('victimas.index', compact('victimas', 'inundaciones', 'provincias', 'municipios', 'role'));
    }

    // ── Formulario de Creación ────────────────────────────────────────────

    public function create(): View
    {
        $inundaciones = Inundacion::with(['municipio.provincia', 'reportes'])
            ->latest()
            ->get()
            ->map(fn ($i) => [
                'id'       => $i->id,
                'estado'   => $i->estado,
                'latitud'  => (float) $i->latitud,
                'longitud' => (float) $i->longitud,
                'municipio'  => $i->municipio?->nombre ?? 'Sin municipio',
                'provincia'  => $i->municipio?->provincia?->nombre ?? 'Sin provincia',
                'created_at' => $i->created_at?->format('d/m/Y H:i') ?? '?',
                'created_at_iso' => $i->created_at?->toDateString() ?? '',
                'reportes' => $i->reportes->map(fn($r) => [
                    'lat' => (float) $r->lat_reporte,
                    'lng' => (float) $r->long_reporte,
                    'intensidad' => $r->intensidad_calculada ?? 'baja'
                ])->toArray(),
            ]);

        $estados      = Victima::ESTADOS;
        $estadoLabels = Victima::ESTADO_LABELS;

        return view('victimas.create', compact('inundaciones', 'estados', 'estadoLabels'));
    }

    // ── Guardar Nueva Víctima ─────────────────────────────────────────────

    public function store(StoreVictimaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('victimas', 'public');
        }

        unset($data['foto']);

        $user = (array) $request->session()->get('api_user', []);
        $data['registrado_por'] = (string) ($user['carnet'] ?? '');

        $victima = Victima::create($data);

        return redirect()
            ->route('victimas.show', ['id' => $victima->id])
            ->with('success', "Víctima «{$victima->nombre_completo}» registrada correctamente.");
    }

    // ── Detalle de Víctima ────────────────────────────────────────────────

    public function show(Request $request, int $id): View
    {
        $victima = Victima::with(['inundacion.municipio.provincia', 'registrador'])->findOrFail($id);
        $user    = (array) $request->session()->get('api_user', []);
        $role    = (string) ($user['role'] ?? '');

        return view('victimas.show', compact('victima', 'role'));
    }

    // ── Formulario de Edición ─────────────────────────────────────────────

    public function edit(int $id): View
    {
        $victima      = Victima::findOrFail($id);
        $inundaciones = Inundacion::with(['municipio.provincia', 'reportes'])
            ->latest()
            ->get()
            ->map(fn ($i) => [
                'id'       => $i->id,
                'estado'   => $i->estado,
                'latitud'  => (float) $i->latitud,
                'longitud' => (float) $i->longitud,
                'municipio'  => $i->municipio?->nombre ?? 'Sin municipio',
                'provincia'  => $i->municipio?->provincia?->nombre ?? 'Sin provincia',
                'created_at' => $i->created_at?->format('d/m/Y H:i') ?? '?',
                'created_at_iso' => $i->created_at?->toDateString() ?? '',
                'reportes' => $i->reportes->map(fn($r) => [
                    'lat' => (float) $r->latitud,
                    'lng' => (float) $r->longitud,
                    'intensidad' => $r->intensidad_calculada ?? 'baja'
                ])->toArray(),
            ]);

        $estados      = Victima::ESTADOS;
        $estadoLabels = Victima::ESTADO_LABELS;

        return view('victimas.edit', compact('victima', 'inundaciones', 'estados', 'estadoLabels'));
    }

    // ── Actualizar Víctima ────────────────────────────────────────────────

    public function update(UpdateVictimaRequest $request, int $id): RedirectResponse
    {
        $victima = Victima::findOrFail($id);
        $data    = $request->validated();

        // ── Gestión de la foto ────────────────────────────────────────────
        if ($request->boolean('eliminar_foto') && $victima->foto_path) {
            Storage::disk('public')->delete($victima->foto_path);
            $data['foto_path'] = null;
        }

        if ($request->hasFile('foto')) {
            // Eliminar foto anterior si existía
            if ($victima->foto_path) {
                Storage::disk('public')->delete($victima->foto_path);
            }
            $data['foto_path'] = $request->file('foto')->store('victimas', 'public');
        }

        // No persistir la clave virtual 'foto' ni 'eliminar_foto' en BD
        unset($data['foto'], $data['eliminar_foto']);

        $victima->update($data);

        return redirect()
            ->route('victimas.show', ['id' => $victima->id])
            ->with('success', "Víctima «{$victima->nombre_completo}» actualizada correctamente.");
    }

    // ── Eliminar Víctima ──────────────────────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        $victima = Victima::findOrFail($id);

        // Eliminar foto del disco antes de borrar el registro
        if ($victima->foto_path) {
            Storage::disk('public')->delete($victima->foto_path);
        }

        $nombre = $victima->nombre_completo;
        $victima->delete();

        return redirect()
            ->route('victimas.index')
            ->with('success', "Víctima «{$nombre}» eliminada correctamente.");
    }
}
