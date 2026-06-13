<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CentroAsistencia;
use App\Models\Inundacion;
use App\Models\Reporte;
use App\Services\FloodApiClient;
use App\Services\FloodApiExceptions\ApiRequestException;
use App\Services\FloodApiExceptions\ApiUnauthorizedException;
use App\Services\FloodApiExceptions\ApiValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ReportController
{
    public function __construct(private readonly FloodApiClient $api)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user   = (array) $request->session()->get('api_user', []);
        $role   = (string) ($user['role'] ?? '');
        $carnet = (string) ($user['carnet'] ?? '');
        $page   = max(1, (int) $request->query('page', '1'));

        // ── Inundaciones ACTIVAS (paginadas) ──────────────────────────────
        // Cargamos reportesActivosTTL para quórum dinámico y reportes para
        // address/description del primer reporte vinculado.
        $activasPaginator = Inundacion::activas()
            ->with(['reportesActivosTTL', 'reportes'])
            ->latest()
            ->paginate(15, ['*'], 'page', $page);

        // IMPORTANTE: data_get() en Blade accede a propiedades, NO llama métodos.
        // Por eso transformamos cada modelo Eloquent a un array plano con todos
        // los campos calculados ya resueltos.
        $inundacionesActivas = collect($activasPaginator->items())
            ->map(fn (Inundacion $i) => $this->serializarActiva($i))
            ->all();

        // ── Inundaciones TERMINADAS (historial completo, sin paginación) ───
        // Para terminadas usamos TODOS los reportes (sin filtro TTL) porque
        // el TTL aplica solo al mapa activo; aquí queremos el total histórico.
        $inundacionesTerminadas = Inundacion::terminadas()
            ->with('reportes')
            ->latest('updated_at')
            ->get()
            ->map(fn (Inundacion $i) => $this->serializarTerminada($i))
            ->all();

        // ── Reportes del ciudadano autenticado ────────────────────────────
        $misReportes = [];
        if ($carnet !== '') {
            $misReportes = Reporte::where('citizen_carnet', $carnet)
                ->latest('updated_at')
                ->limit(20)
                ->get();
        }

        // ── Paneles de autoridad (pendientes + rechazados) ─────────────────
        $reportesPendientes = [];
        $reportesRechazados = [];
        $inundacionesActivasParaVincular = collect();
        if ($role === 'authority') {
            $reportesPendientes = Reporte::whereNull('inundacion_id')
                ->where('estado_validacion', Reporte::VALIDACION_PENDIENTE)
                ->latest()
                ->get();

            $reportesRechazados = Reporte::where('estado_validacion', Reporte::VALIDACION_RECHAZADO)
                ->latest('updated_at')
                ->get();

            // Calcular inundaciones cercanas (radio 300 m) a cada reporte pendiente
            $activas = Inundacion::activas()->get();
            $inundacionesActivasParaVincular = $activas;
            foreach ($reportesPendientes as $rep) {
                $cercanas = [];
                foreach ($activas as $activa) {
                    $lat1 = deg2rad((float) $rep->lat_reporte);
                    $lon1 = deg2rad((float) $rep->long_reporte);
                    $lat2 = deg2rad((float) $activa->latitud);
                    $lon2 = deg2rad((float) $activa->longitud);
                    $dLat = $lat2 - $lat1;
                    $dLon = $lon2 - $lon1;
                    $a    = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;
                    $dist = 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
                    if ($dist <= 300) {
                        $cercanas[] = $activa;
                    }
                }
                $rep->cercanas = collect($cercanas);
            }
        }

        return view('reports.index', [
            'inundacionesActivas'    => $inundacionesActivas,
            'inundacionesTerminadas' => $inundacionesTerminadas,
            'misReportes'            => $misReportes,
            'reportesPendientes'     => $reportesPendientes,
            'reportesRechazados'     => $reportesRechazados,
            'inundacionesActivasParaVincular' => $inundacionesActivasParaVincular,
            'meta' => [
                'current_page' => $activasPaginator->currentPage(),
                'last_page'    => $activasPaginator->lastPage(),
            ],
            'role' => $role,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers de serialización (Eloquent → array plano para Blade)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Serializa una inundación ACTIVA con quórum dinámico (TTL 3h).
     */
    private function serializarActiva(Inundacion $i): array
    {
        $i->loadMissing(['reportesActivosTTL', 'reportes']);

        $reportesActivos = $i->reportesActivosTTL->map(fn ($r) => [
            'id'                   => $r->id,
            'peso'                 => $r->peso,
            'intensidad_propuesta' => $r->intensidad_propuesta,
            'lat_reporte'          => $r->lat_reporte,
            'long_reporte'         => $r->long_reporte,
            'foto_path'            => $r->foto_path,
            'estado_validacion'    => $r->estado_validacion,
            'created_at'           => $r->created_at,
            'created_at_human'     => $r->created_at?->diffForHumans(),
        ])->toArray();

        $ttlInicio = \Carbon\Carbon::now()->subHours(\App\Models\Inundacion::TTL_HORAS);
        
        // Reportes inactivos: Aceptados/Pendientes pero cuyo updated_at es anterior al TTL
        $reportesInactivos = $i->reportes->filter(function ($r) use ($ttlInicio) {
            if ($r->estado_validacion === \App\Models\Reporte::VALIDACION_RECHAZADO) {
                return false;
            }
            $fecha = $r->updated_at ?? $r->created_at;
            return $fecha && $fecha->lt($ttlInicio);
        })->map(fn ($r) => [
            'id'                   => $r->id,
            'peso'                 => $r->peso,
            'intensidad_propuesta' => $r->intensidad_propuesta,
            'lat_reporte'          => $r->lat_reporte,
            'long_reporte'         => $r->long_reporte,
            'foto_path'            => $r->foto_path,
            'estado_validacion'    => $r->estado_validacion,
            'created_at'           => $r->created_at,
            'created_at_human'     => $r->created_at?->diffForHumans(),
            'caducado_hace'        => ($r->updated_at ?? $r->created_at)?->diffForHumans(),
        ])->toArray();

        return [
            'id'                   => $i->id,
            'latitud'              => $i->latitud,
            'longitud'             => $i->longitud,
            'estado'               => $i->estado,
            'created_at'           => $i->created_at,
            'updated_at'           => $i->updated_at,
            'address'              => $i->reportes->first()?->address,
            'description'          => $i->reportes->first()?->description,
            // Quórum dinámico — solo reportes últimas 3h, excluyendo rechazados
            'quorum_total'         => $i->quorumTotal(),
            'intensidad_calculada' => $i->intensidadCalculada(),
            'esta_confirmada'      => $i->estaConfirmada(),
            'desglose_puntos'      => $i->desgloseReportes($i->reportesActivosTTL),
            'reportes_activos'     => $reportesActivos,
            'reportes_inactivos'   => $reportesInactivos,
        ];
    }

    /**
     * Serializa una inundación TERMINADA con desglose histórico completo.
     * La duración se calcula como: updated_at (cierre) − created_at (inicio).
     */
    private function serializarTerminada(Inundacion $i): array
    {
        $i->loadMissing('reportes');

        $diff    = $i->created_at->diff($i->updated_at);
        $horas   = ($diff->days * 24) + $diff->h;
        $minutos = $diff->i;

        $reportesVinculados = $i->reportes->map(fn ($r) => [
            'id'                   => $r->id,
            'peso'                 => $r->peso,
            'intensidad_propuesta' => $r->intensidad_propuesta,
            'lat_reporte'          => $r->lat_reporte,
            'long_reporte'         => $r->long_reporte,
            'foto_path'            => $r->foto_path,
            'estado_validacion'    => $r->estado_validacion,
            'created_at'           => $r->created_at,
            'created_at_human'     => $r->created_at?->diffForHumans(),
        ])->toArray();

        $desglose       = $i->desgloseReportes($i->reportes);
        $totalHistorico = array_sum($desglose);

        return [
            'id'                   => $i->id,
            'latitud'              => $i->latitud,
            'longitud'             => $i->longitud,
            'estado'               => $i->estado,
            'created_at'           => $i->created_at,
            'updated_at'           => $i->updated_at,
            'address'              => $i->reportes->first()?->address,
            'description'          => $i->reportes->first()?->description,
            // Desglose histórico (todos los reportes vinculados, sin TTL)
            'desglose_historico'   => $desglose,
            'quorum_historico'     => $totalHistorico,
            'reportes_vinculados'  => $reportesVinculados,
            // Duración de la inundación (inicio → cierre por autoridad)
            'duracion_horas'       => $horas,
            'duracion_minutos'     => $minutos,
            'duracion_texto'       => "{$horas}h {$minutos}min",
            'fecha_inicio'         => $i->created_at,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Resto de acciones del controlador (sin cambios de lógica)
    // ─────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('reports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        $data = $request->validate([
            'latitud'   => ['required', 'numeric', 'between:-90,90'],
            'longitud'  => ['required', 'numeric', 'between:-180,180'],
            'address'   => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'provincia' => ['required', 'string'],
            'municipio' => ['required', 'string'],
        ]);

        try {
            $report = $this->api->createReport($token, $data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return back()->withInput()->withErrors([
                'description' => [$e->getMessage()],
            ]);
        }

        $id = Arr::get($report, 'id');

        return $id !== null
            ? redirect()->route('reports.show', ['id' => $id])
            : redirect()->route('reports.index');
    }

    public function show(Request $request, int|string $id): View|RedirectResponse
    {
        $inundacion = Inundacion::with(['reportes', 'victimas'])->findOrFail($id);

        $reportArray = [
            'latitude' => $inundacion->latitud,
            'longitude' => $inundacion->longitud,
        ];

        return view('reports.show', [
            'inundacion' => $inundacion,
            'eta'        => $this->calculateEta($reportArray),
            'role'       => (string) ($request->session()->get('api_user')['role'] ?? ''),
        ]);
    }

    public function storeResponse(Request $request, int|string $id): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        try {
            $this->api->createResponse($token, $id, $data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return back()->withErrors([
                'message' => [$e->getMessage()],
            ]);
        }

        return redirect()->route('reports.show', ['id' => $id]);
    }

    public function updateestado(Request $request, int|string $id): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        $data = $request->validate([
            // Estados normalizados: 'activa' | 'terminada' | 'falsa'
            'estado' => ['required', 'string', 'in:activa,terminada,falsa'],
        ]);

        try {
            $this->api->updateReport($token, $id, $data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return back()->withErrors([
                'estado' => [$e->getMessage()],
            ]);
        }

        return redirect()->route('reports.show', ['id' => $id]);
    }

    /**
     * Desactiva (termina) una inundación directamente desde el listado.
     */
    public function desactivar(Request $request, int|string $id): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        try {
            $this->api->updateReport($token, $id, ['estado' => 'terminada']);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return redirect()->route('reports.index')->withErrors([
                'estado' => 'No se pudo desactivar la inundación: ' . $e->getMessage(),
            ]);
        }

        return redirect()->route('reports.index')
            ->with('success', "Inundación #{$id} marcada como terminada correctamente.");
    }

    /**
     * Renueva el tiempo de vida (TTL) de un reporte activo actualizando su updated_at.
     */
    public function renovarReporte(Request $request, int|string $id): RedirectResponse
    {
        $reporte = Reporte::findOrFail((int) $id);
        
        // Touch actualiza el 'updated_at' al instante actual
        $reporte->touch();

        if ($reporte->inundacion_id) {
            // También podemos hacer un touch a la inundación para que suba en el listado o extienda su vida general
            $inundacion = Inundacion::find($reporte->inundacion_id);
            if ($inundacion) {
                $inundacion->touch();
            }
        }

        return redirect()->route('reports.index')
            ->with('success', "Reporte #{$id} renovado exitosamente. Su TTL se ha extendido 3 horas.");
    }

    /**
     * Actualiza el estado de validación de un reporte (panel de rechazados).
     */
    public function updateEstadoValidacion(Request $request, int|string $id): RedirectResponse
    {
        $estadoValidacion = (string) $request->input('estado_validacion', '');
        $data = $request->validate([
            'estado_validacion' => ['required', 'string', 'in:pendiente,aceptado,rechazado'],
            'inundacion_id' => ['nullable', 'integer', 'exists:inundaciones,id'],
        ]);

        if ($estadoValidacion === Reporte::VALIDACION_ACEPTADO && empty($data['inundacion_id'])) {
            return redirect()->route('reports.index')
                ->with('error', 'Para marcar como aceptado debes seleccionar una inundación para vincular.');
        }

        $reporte = Reporte::findOrFail((int) $id);
        $nuevoEstado = (string) $data['estado_validacion'];
        $reporte->update([
            'estado_validacion' => $nuevoEstado,
            // Si se acepta, queda vinculado; en otro estado, se limpia vínculo.
            'inundacion_id' => $nuevoEstado === Reporte::VALIDACION_ACEPTADO
                ? (int) $data['inundacion_id']
                : null,
        ]);

        if ($nuevoEstado === Reporte::VALIDACION_ACEPTADO && !empty($data['inundacion_id'])) {
            $inundacion = Inundacion::find((int) $data['inundacion_id']);
            if ($inundacion) {
                $inundacion->recalcularCentroide();
            }
        }

        return redirect()->route('reports.index')
            ->with('success', "Estado de validación del reporte #{$reporte->id} actualizado a \"{$nuevoEstado}\".");
    }

    public function latestForNotifications(Request $request): JsonResponse
    {
        $latest = Inundacion::activas()->latest()->first();

        if (! $latest) {
            return response()->json(['data' => null], 200);
        }

        $latest->load('reportesActivosTTL');

        return response()->json([
            'data' => [
                'id'                   => (string) $latest->id,
                'intensidad_calculada' => $latest->intensidadCalculada(),
                'quorum_total'         => $latest->quorumTotal(),
                'esta_confirmada'      => $latest->estaConfirmada(),
            ],
        ]);
    }

    public function notificationsFeed(Request $request): JsonResponse
    {
        $user   = (array) $request->session()->get('api_user', []);
        $role   = (string) ($user['role'] ?? '');
        $carnet = (string) ($user['carnet'] ?? '');

        if ($role === 'authority') {
            $items = Reporte::query()
                ->whereNull('inundacion_id')
                ->where('estado_validacion', Reporte::VALIDACION_PENDIENTE)
                ->latest('updated_at')
                ->limit(10)
                ->get()
                ->map(function (Reporte $reporte): array {
                    $isUpdated = $reporte->updated_at !== null
                        && $reporte->created_at !== null
                        && $reporte->updated_at->gt($reporte->created_at);

                    $cursor = (((int) optional($reporte->updated_at)->timestamp) * 100000) + (int) $reporte->id;

                    return [
                        'id'         => 'authority-pending-' . $reporte->id . '-' . (int) optional($reporte->updated_at)->timestamp,
                        'cursor'     => $cursor,
                        'title'      => $isUpdated ? 'Reporte pendiente actualizado' : 'Nuevo reporte pendiente',
                        'message'    => $isUpdated
                            ? 'El reporte #' . $reporte->id . ' fue actualizado y sigue pendiente de validacion.'
                            : 'El reporte #' . $reporte->id . ' requiere validacion de autoridad.',
                        'created_at' => optional($reporte->updated_at)?->toIso8601String(),
                        'link'       => route('reports.index', [], false),
                    ];
                })
                ->values()
                ->all();

            return response()->json(['data' => $items]);
        }

        if ($role !== 'citizen' || $carnet === '') {
            return response()->json(['data' => []]);
        }

        $items   = [];
        $reportes = Reporte::query()
            ->with('inundacion')
            ->where('citizen_carnet', $carnet)
            ->latest('updated_at')
            ->limit(20)
            ->get();

        foreach ($reportes as $reporte) {
            if ($reporte->estado_validacion === Reporte::VALIDACION_RECHAZADO) {
                $items[] = [
                    'id'         => 'citizen-rejected-' . $reporte->id,
                    'cursor'     => (int) optional($reporte->updated_at)->timestamp,
                    'title'      => 'Reporte rechazado',
                    'message'    => 'Tu reporte #' . $reporte->id . ' fue rechazado por una autoridad.',
                    'created_at' => optional($reporte->updated_at)?->toIso8601String(),
                    'link'       => route('reports.index', [], false),
                ];
            }

            if ($reporte->estado_validacion === Reporte::VALIDACION_ACEPTADO && $reporte->inundacion_id !== null) {
                $items[] = [
                    'id'         => 'citizen-accepted-' . $reporte->id,
                    'cursor'     => (int) optional($reporte->updated_at)->timestamp,
                    'title'      => 'Reporte atendido',
                    'message'    => 'Tu reporte #' . $reporte->id . ' fue atendido y vinculado a una inundacion.',
                    'created_at' => optional($reporte->updated_at)?->toIso8601String(),
                    'link'       => route('reports.index', [], false),
                ];
            }

            if ($reporte->inundacion !== null && in_array($reporte->inundacion->estado, [Inundacion::ESTADO_TERMINADA, Inundacion::ESTADO_FALSA], true)) {
                $estado    = (string) $reporte->inundacion->estado;
                $changedAt = $reporte->inundacion->updated_at ?? $reporte->updated_at;
                $items[]   = [
                    'id'         => 'citizen-status-' . $reporte->id . '-' . $estado,
                    'cursor'     => (int) optional($changedAt)->timestamp,
                    'title'      => 'Estado de inundacion actualizado',
                    'message'    => 'La inundacion asociada a tu reporte #' . $reporte->id . ' cambio a estado "' . $estado . '".',
                    'created_at' => optional($changedAt)?->toIso8601String(),
                    'link'       => route('reports.index', [], false),
                ];
            }
        }

        usort($items, static fn (array $a, array $b): int => ($b['cursor'] <=> $a['cursor']));

        return response()->json([
            'data' => array_slice($items, 0, 10),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers privados de distancia y ETA
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $report
     * @return array<string, mixed>|null
     */
    private function calculateEta(array $report): ?array
    {
        $reportLat = $this->pickFirstFloat([
            $report['latitude'] ?? null,
            $report['latitud'] ?? null,
            $report['lat_reporte'] ?? null,
        ]);

        $reportLng = $this->pickFirstFloat([
            $report['longitude'] ?? null,
            $report['longitud'] ?? null,
            $report['long_reporte'] ?? null,
        ]);

        if ($reportLat === null || $reportLng === null) {
            return null;
        }

        $closest       = null;
        $minDistanceKm = INF;

        foreach (CentroAsistencia::query()->get(['id_centro', 'nombre', 'latitud', 'longitud']) as $centro) {
            $centerLat = is_numeric($centro->latitud) ? (float) $centro->latitud : null;
            $centerLng = is_numeric($centro->longitud) ? (float) $centro->longitud : null;

            if ($centerLat === null || $centerLng === null) {
                continue;
            }

            $distanceKm = $this->haversineKm($reportLat, $reportLng, $centerLat, $centerLng);
            if ($distanceKm < $minDistanceKm) {
                $minDistanceKm = $distanceKm;
                $closest       = $centro;
            }
        }

        if ($closest === null || ! is_finite($minDistanceKm)) {
            return null;
        }

        $speedKmH  = 35.0;
        $etaMinutes = (int) max(3, ceil(($minDistanceKm / $speedKmH) * 60));

        return [
            'name'         => (string) ($closest->nombre ?? 'Centro de asistencia'),
            'distance_km'  => round($minDistanceKm, 2),
            'eta_minutes'  => $etaMinutes,
        ];
    }

    /**
     * @param array<int, mixed> $candidates
     */
    private function pickFirstFloat(array $candidates): ?float
    {
        foreach ($candidates as $value) {
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
