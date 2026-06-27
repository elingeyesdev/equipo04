<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\Inundacion;
use App\Models\MotivoRechazo;
use App\Models\Reporte;
use App\Models\ReporteValidacionHistorial;
use App\Models\User;
use App\Services\FloodApiClient;
use App\Services\ReporteValidacionService;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsIndex extends Component
{
    use WithPagination;

    public $role = '';
    public $carnet = '';

    public $estadoValidacionUpdates = [];
    public $inundacionVincularIds = [];
    public $motivoRechazoUpdates = [];
    public $motivoTextoUpdates = [];
    public $reversionTextoUpdates = [];

    /** Filtros panel reportes rechazados (fase 3) */
    public $filtroRechazoMotivo = '';
    public $filtroRechazoValidador = '';
    public $filtroRechazoDesde = '';
    public $filtroRechazoHasta = '';

    /** Modal historial de validación */
    public ?int $historialReporteId = null;
    public array $historialEntradas = [];

    public function mount()
    {
        $user = (array) session()->get('api_user', []);
        $this->role = (string) ($user['role'] ?? '');
        $this->carnet = (string) ($user['carnet'] ?? '');

        if ($this->role === 'authority') {
            $rechazados = Reporte::where('estado_validacion', Reporte::VALIDACION_RECHAZADO)->get();
            foreach ($rechazados as $rep) {
                $this->estadoValidacionUpdates[$rep->id] = 'rechazado';
                $this->inundacionVincularIds[$rep->id] = $rep->inundacion_id ?? '';
                $this->motivoRechazoUpdates[$rep->id] = $rep->motivo_rechazo_codigo ?? '';
                $this->motivoTextoUpdates[$rep->id] = $rep->motivo_rechazo_texto ?? '';
                $this->reversionTextoUpdates[$rep->id] = '';
            }
        }
    }

    #[On('refreshReports')]
    #[On('echo:reportes,ReporteCreado')]
    #[On('echo:inundaciones,InundacionActualizada')]
    public function refreshData()
    {
    }

    public function desactivar(int $id)
    {
        if ($this->role !== 'authority') {
            return;
        }

        $api = app(FloodApiClient::class);
        $token = Session::get('api_token', '');
        try {
            $api->updateReport($token, $id, ['estado' => 'terminada']);
            session()->flash('success', "Inundación #{$id} marcada como terminada correctamente.");
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo desactivar la inundación: ' . $e->getMessage());
        }
    }

    public function renovarReporte(int $id)
    {
        $reporte = Reporte::findOrFail($id);
        $reporte->touch();

        if ($reporte->inundacion_id) {
            $inundacion = Inundacion::find($reporte->inundacion_id);
            if ($inundacion) {
                $inundacion->touch();
            }
        }

        session()->flash('success', "Reporte #{$id} renovado exitosamente. Su TTL se ha extendido 3 horas.");
        $this->dispatch('reporte-ttl-renovado', reporteId: $id);

        // Evita re-render completo del panel (minimapas, tablas) — era lento y parpadeaba.
        $this->skipRender();
    }

    public function verHistorial(int $id): void
    {
        if ($this->role !== 'authority') {
            return;
        }

        $reporte = Reporte::with(['historialValidacion.validador', 'historialValidacion.motivo'])
            ->findOrFail($id);

        $this->historialReporteId = $id;
        $this->historialEntradas = $reporte->historialValidacion->map(fn (ReporteValidacionHistorial $h) => [
            'fecha'              => $h->fecha_accion?->format('d/m/Y H:i'),
            'accion'             => $h->accion,
            'estado_anterior'    => $h->estado_anterior,
            'estado_nuevo'       => $h->estado_nuevo,
            'validador'          => $h->validador?->name ?? '—',
            'motivo'             => $h->motivo?->label_autoridad ?? $h->motivo_texto,
            'intensidad_propuesta' => $h->intensidad_propuesta_snapshot,
            'intensidad_validada'  => $h->intensidad_validada_snapshot,
        ])->all();

        $this->dispatch('historial-modal-open');
    }

    public function cerrarHistorial(): void
    {
        $this->historialReporteId = null;
        $this->historialEntradas = [];
        $this->dispatch('historial-modal-close');
    }

    public function limpiarFiltrosRechazados(): void
    {
        $this->filtroRechazoMotivo = '';
        $this->filtroRechazoValidador = '';
        $this->filtroRechazoDesde = '';
        $this->filtroRechazoHasta = '';
    }

    public function exportRechazadosCsv(): StreamedResponse
    {
        if ($this->role !== 'authority') {
            abort(403);
        }

        $reportes = $this->queryReportesRechazados()->get();

        return response()->streamDownload(function () use ($reportes) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'ID', 'Estado', 'Reportado por', 'Carnet', 'Intensidad propuesta', 'Intensidad validada',
                'Rechazado', 'Validador', 'Motivo', 'Nota', 'Dirección', 'Descripción',
            ]);

            foreach ($reportes as $rep) {
                fputcsv($out, [
                    $rep->id,
                    $rep->estado_validacion,
                    $rep->citizen?->name ?? 'Anónimo',
                    $rep->citizen_carnet ?? '',
                    $rep->intensidad_propuesta,
                    $rep->intensidad_validada ?? '',
                    optional($rep->rechazado_at ?? $rep->updated_at)?->format('Y-m-d H:i:s'),
                    $rep->validador?->name ?? '',
                    $rep->motivoRechazo?->label_autoridad ?? '',
                    $rep->motivo_rechazo_texto ?? '',
                    $rep->address ?? '',
                    $rep->description ?? '',
                ]);
            }

            fclose($out);
        }, 'reportes_rechazados_' . now()->format('Y-m-d_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function queryReportesRechazados()
    {
        $query = Reporte::where('estado_validacion', Reporte::VALIDACION_RECHAZADO)
            ->with(['citizen', 'validador', 'motivoRechazo']);

        if ($this->filtroRechazoMotivo !== '') {
            $query->where('motivo_rechazo_codigo', $this->filtroRechazoMotivo);
        }

        if ($this->filtroRechazoValidador !== '') {
            $query->where('validador_id', $this->filtroRechazoValidador);
        }

        if ($this->filtroRechazoDesde !== '') {
            $query->whereDate('rechazado_at', '>=', $this->filtroRechazoDesde);
        }

        if ($this->filtroRechazoHasta !== '') {
            $query->whereDate('rechazado_at', '<=', $this->filtroRechazoHasta);
        }

        return $query->latest('rechazado_at')->latest('updated_at');
    }

    public function updateEstadoValidacion(int $id)
    {
        if ($this->role !== 'authority' || $this->carnet === '') {
            session()->flash('error', 'No autorizado.');
            return;
        }

        $estadoValidacion = $this->estadoValidacionUpdates[$id] ?? '';
        $inundacionId = $this->inundacionVincularIds[$id] ?? null;
        $motivoCodigo = $this->motivoRechazoUpdates[$id] ?? '';
        $motivoTexto = $this->motivoTextoUpdates[$id] ?? null;
        $reversionTexto = $this->reversionTextoUpdates[$id] ?? null;

        if ($estadoValidacion === Reporte::VALIDACION_ACEPTADO && empty($inundacionId)) {
            session()->flash('error', 'Para marcar como aceptado debes seleccionar una inundación para vincular.');
            return;
        }

        $reporte = Reporte::findOrFail($id);
        $validacion = app(ReporteValidacionService::class);

        try {
            if ($estadoValidacion === Reporte::VALIDACION_ACEPTADO && ! empty($inundacionId)) {
                $validacion->aceptarYVincular($reporte, (int) $inundacionId, $this->carnet);
            } elseif ($estadoValidacion === Reporte::VALIDACION_RECHAZADO) {
                if ($motivoCodigo === '') {
                    session()->flash('error', 'Debes seleccionar un motivo de rechazo.');
                    return;
                }
                $validacion->rechazar($reporte, $this->carnet, (string) $motivoCodigo, $motivoTexto ?: null);
            } elseif ($estadoValidacion === Reporte::VALIDACION_PENDIENTE) {
                $validacion->revertirAPendiente($reporte, $this->carnet, $reversionTexto);
            } else {
                session()->flash('error', 'Estado de validación no reconocido.');
                return;
            }

            session()->flash('success', "Estado de validación del reporte #{$reporte->id} actualizado a \"{$estadoValidacion}\".");
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first() ?? 'Error de validación.');
        }
    }

    public function render()
    {
        $activasPaginator = Inundacion::activas()
            ->with(['reportesActivosTTL', 'reportes'])
            ->latest()
            ->paginate(15);

        $inundacionesActivas = collect($activasPaginator->items())
            ->map(fn (Inundacion $i) => $this->serializarActiva($i))
            ->all();

        $inundacionesTerminadas = Inundacion::terminadas()
            ->with('reportes')
            ->latest('updated_at')
            ->get()
            ->map(fn (Inundacion $i) => $this->serializarTerminada($i))
            ->all();

        $misReportes = [];
        if ($this->carnet !== '') {
            $misReportes = Reporte::where('citizen_carnet', $this->carnet)
                ->with('motivoRechazo')
                ->latest('updated_at')
                ->limit(20)
                ->get();
        }

        $reportesPendientes = [];
        $reportesRechazados = [];
        $inundacionesActivasParaVincular = collect();
        $motivosRechazo = collect();
        $validacion = app(ReporteValidacionService::class);

        if ($this->role === 'authority') {
            $motivosRechazo = MotivoRechazo::activos()->orderBy('codigo')->get();

            $reportesPendientes = Reporte::whereNull('inundacion_id')
                ->where('estado_validacion', Reporte::VALIDACION_PENDIENTE)
                ->with('citizen')
                ->latest()
                ->get();

            $reportesRechazados = $this->queryReportesRechazados()->get();

            $validadoresRechazo = User::query()
                ->where('role', User::ROLE_AUTHORITY)
                ->whereIn('carnet', Reporte::query()
                    ->where('estado_validacion', Reporte::VALIDACION_RECHAZADO)
                    ->whereNotNull('validador_id')
                    ->distinct()
                    ->pluck('validador_id'))
                ->orderBy('name')
                ->get(['carnet', 'name']);

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
                        $cercanas[] = [
                            'id'                   => $activa->id,
                            'latitud'              => $activa->latitud,
                            'longitud'             => $activa->longitud,
                            'intensidad_calculada' => $activa->intensidadCalculada(),
                        ];
                    }
                }
                $rep->cercanas = collect($cercanas);
                $rep->rechazos_previos = $validacion->contarRechazosCiudadano($rep->citizen_carnet);
            }
        }

        return view('livewire.reports-index', [
            'inundacionesActivas'             => $inundacionesActivas,
            'inundacionesTerminadas'          => $inundacionesTerminadas,
            'misReportes'                     => $misReportes,
            'reportesPendientes'              => $reportesPendientes,
            'reportesRechazados'              => $reportesRechazados,
            'inundacionesActivasParaVincular' => $inundacionesActivasParaVincular,
            'motivosRechazo'                  => $motivosRechazo,
            'validadoresRechazo'              => $validadoresRechazo ?? collect(),
            'ors_key'                         => config('services.openrouteservice.key'),
            'meta' => [
                'current_page' => $activasPaginator->currentPage(),
                'last_page'    => $activasPaginator->lastPage(),
            ],
        ])->layout('layouts.app');
    }

    private function serializarActiva(Inundacion $i): array
    {
        $i->loadMissing(['reportesActivosTTL', 'reportes']);

        $reportesActivos = $i->reportesActivosTTL->map(fn ($r) => [
            'id'                   => $r->id,
            'peso'                 => $r->peso,
            'intensidad_propuesta' => $r->intensidad_propuesta,
            'intensidad_validada'  => $r->intensidad_validada,
            'intensidad_efectiva'  => $r->intensidadEfectiva(),
            'lat_reporte'          => $r->lat_reporte,
            'long_reporte'         => $r->long_reporte,
            'foto_path'            => $r->foto_path,
            'estado_validacion'    => $r->estado_validacion,
            'polygon_coords'       => $r->polygon_coords,
            'polygon_es_fallback'  => (bool) $r->polygon_es_fallback,
            'updated_at'           => $r->updated_at,
            'created_at'           => $r->created_at,
            'created_at_human'     => $r->created_at?->diffForHumans(),
        ])->toArray();

        $idsActivos = $i->reportesActivosTTL->pluck('id')->all();

        $reportesInactivos = $i->reportes->filter(function ($r) use ($idsActivos) {
            if ($r->estado_validacion === Reporte::VALIDACION_RECHAZADO) {
                return false;
            }
            return ! in_array($r->id, $idsActivos, true);
        })->map(fn ($r) => [
            'id'                   => $r->id,
            'peso'                 => $r->peso,
            'intensidad_propuesta' => $r->intensidad_propuesta,
            'intensidad_validada'  => $r->intensidad_validada,
            'intensidad_efectiva'  => $r->intensidadEfectiva(),
            'lat_reporte'          => $r->lat_reporte,
            'long_reporte'         => $r->long_reporte,
            'foto_path'            => $r->foto_path,
            'estado_validacion'    => $r->estado_validacion,
            'polygon_coords'       => $r->polygon_coords,
            'polygon_es_fallback'  => (bool) $r->polygon_es_fallback,
            'updated_at'           => $r->updated_at,
            'created_at'           => $r->created_at,
            'created_at_human'     => $r->created_at?->diffForHumans(),
            'caducado_hace'        => ($r->updated_at ?? $r->created_at)?->diffForHumans(),
        ])->toArray();

        return [
            'id'                        => $i->id,
            'latitud'                   => $i->latitud,
            'longitud'                  => $i->longitud,
            'estado'                    => $i->estado,
            'created_at'                => $i->created_at,
            'updated_at'                => $i->updated_at,
            'address'                   => $i->reportes->first()?->address,
            'description'               => $i->reportes->first()?->description,
            'polygon_coords'            => $i->polygon_coords,
            'polygon_editado_autoridad' => $i->polygon_editado_autoridad,
            'polygon_es_fallback'       => (bool) $i->polygon_es_fallback,
            'quorum_total'              => $i->quorumTotal(),
            'intensidad_calculada'      => $i->intensidadCalculada(),
            'esta_confirmada'           => $i->estaConfirmada(),
            'desglose_puntos'           => $i->desgloseReportes($i->reportesActivosTTL),
            'reportes_activos'          => $reportesActivos,
            'reportes_inactivos'        => $reportesInactivos,
        ];
    }

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
            'intensidad_validada'  => $r->intensidad_validada,
            'intensidad_efectiva'  => $r->intensidadEfectiva(),
            'lat_reporte'          => $r->lat_reporte,
            'long_reporte'         => $r->long_reporte,
            'foto_path'            => $r->foto_path,
            'estado_validacion'    => $r->estado_validacion,
            'polygon_coords'       => $r->polygon_coords,
            'polygon_es_fallback'  => (bool) $r->polygon_es_fallback,
            'updated_at'           => $r->updated_at,
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
            'desglose_historico'   => $desglose,
            'quorum_historico'     => $totalHistorico,
            'reportes_vinculados'  => $reportesVinculados,
            'duracion_horas'       => $horas,
            'duracion_minutos'     => $minutos,
            'duracion_texto'       => "{$horas}h {$minutos}min",
            'fecha_inicio'         => $i->created_at,
        ];
    }
}
