<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\Inundacion;
use App\Models\Reporte;
use App\Services\FloodApiClient;
use Illuminate\Support\Facades\Session;

class ReportsIndex extends Component
{
    use WithPagination;

    public $role = '';
    public $carnet = '';

    // Estado para el panel de rechazados
    public $estadoValidacionUpdates = [];
    public $inundacionVincularIds = [];

    public function mount()
    {
        $user = (array) session()->get('api_user', []);
        $this->role = (string) ($user['role'] ?? '');
        $this->carnet = (string) ($user['carnet'] ?? '');

        // Inicializamos los estados para los selectores del panel de rechazados
        if ($this->role === 'authority') {
            $rechazados = Reporte::where('estado_validacion', Reporte::VALIDACION_RECHAZADO)->get();
            foreach ($rechazados as $rep) {
                $this->estadoValidacionUpdates[$rep->id] = 'rechazado';
                $this->inundacionVincularIds[$rep->id] = $rep->inundacion_id ?? '';
            }
        }
    }

    #[On('refreshReports')]
    #[On('echo:reportes,ReporteCreado')]
    #[On('echo:inundaciones,InundacionActualizada')]
    public function refreshData()
    {
        // Vacío intencionalmente, solo fuerza el re-render de Livewire
    }

    public function desactivar(int $id)
    {
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
    }

    public function updateEstadoValidacion(int $id)
    {
        $estadoValidacion = $this->estadoValidacionUpdates[$id] ?? '';
        $inundacionId = $this->inundacionVincularIds[$id] ?? null;

        if ($estadoValidacion === Reporte::VALIDACION_ACEPTADO && empty($inundacionId)) {
            session()->flash('error', 'Para marcar como aceptado debes seleccionar una inundación para vincular.');
            return;
        }

        $reporte = Reporte::findOrFail($id);
        $reporte->update([
            'estado_validacion' => $estadoValidacion,
            'inundacion_id' => $estadoValidacion === Reporte::VALIDACION_ACEPTADO
                ? (int) $inundacionId
                : null,
        ]);

        if ($estadoValidacion === Reporte::VALIDACION_ACEPTADO && !empty($inundacionId)) {
            $inundacion = Inundacion::find((int) $inundacionId);
            if ($inundacion) {
                $inundacion->recalcularCentroide();
            }
        }

        session()->flash('success', "Estado de validación del reporte #{$reporte->id} actualizado a \"{$estadoValidacion}\".");
    }

    public function render()
    {
        // Lógica replicada desde ReportController@index
        // ── Inundaciones ACTIVAS ──────────────────────────────
        $activasPaginator = Inundacion::activas()
            ->with(['reportesActivosTTL', 'reportes'])
            ->latest()
            ->paginate(15);

        $inundacionesActivas = collect($activasPaginator->items())
            ->map(fn (Inundacion $i) => $this->serializarActiva($i))
            ->all();

        // ── Inundaciones TERMINADAS ───
        $inundacionesTerminadas = Inundacion::terminadas()
            ->with('reportes')
            ->latest('updated_at')
            ->get()
            ->map(fn (Inundacion $i) => $this->serializarTerminada($i))
            ->all();

        // ── Reportes del ciudadano autenticado ────────────────────────────
        $misReportes = [];
        if ($this->carnet !== '') {
            $misReportes = Reporte::where('citizen_carnet', $this->carnet)
                ->latest('updated_at')
                ->limit(20)
                ->get();
        }

        // ── Paneles de autoridad (pendientes + rechazados) ─────────────────
        $reportesPendientes = [];
        $reportesRechazados = [];
        $inundacionesActivasParaVincular = collect();

        if ($this->role === 'authority') {
            $reportesPendientes = Reporte::whereNull('inundacion_id')
                ->where('estado_validacion', Reporte::VALIDACION_PENDIENTE)
                ->latest()
                ->get();

            $reportesRechazados = Reporte::where('estado_validacion', Reporte::VALIDACION_RECHAZADO)
                ->latest('updated_at')
                ->get();

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

        return view('livewire.reports-index', [
            'inundacionesActivas'    => $inundacionesActivas,
            'inundacionesTerminadas' => $inundacionesTerminadas,
            'misReportes'            => $misReportes,
            'reportesPendientes'     => $reportesPendientes,
            'reportesRechazados'     => $reportesRechazados,
            'inundacionesActivasParaVincular' => $inundacionesActivasParaVincular,
            'ors_key' => config('services.openrouteservice.key'),
            'meta' => [
                'current_page' => $activasPaginator->currentPage(),
                'last_page'    => $activasPaginator->lastPage(),
            ]
        ])->layout('layouts.app');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers de serialización (Eloquent → array plano para Blade)
    // ─────────────────────────────────────────────────────────────────────

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
            'quorum_total'         => $i->quorumTotal(),
            'intensidad_calculada' => $i->intensidadCalculada(),
            'esta_confirmada'      => $i->estaConfirmada(),
            'desglose_puntos'      => $i->desgloseReportes($i->reportesActivosTTL),
            'reportes_activos'     => $reportesActivos,
            'reportes_inactivos'   => $reportesInactivos,
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
