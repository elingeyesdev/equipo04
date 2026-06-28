<?php

namespace App\Livewire\Concerns;

use App\Models\Inundacion;
use App\Models\MotivoRechazo;
use App\Models\Reporte;
use App\Models\ReporteValidacionHistorial;
use App\Models\User;
use App\Services\InundacionMapaService;
use App\Services\ReporteValidacionService;
use Illuminate\Validation\ValidationException;

trait ManagesReportValidation
{
    public $estadoValidacionUpdates = [];
    public $inundacionVincularIds = [];
    public $motivoRechazoUpdates = [];
    public $motivoTextoUpdates = [];
    public $reversionTextoUpdates = [];

    public $filtroRechazoMotivo = '';
    public $filtroRechazoValidador = '';
    public $filtroRechazoDesde = '';
    public $filtroRechazoHasta = '';

    public ?int $historialReporteId = null;
    public array $historialEntradas = [];

    public ?int $modificarReporteId = null;

    protected function initRechazadosFormState(): void
    {
        if ($this->role !== 'authority') {
            return;
        }

        $rechazados = Reporte::where('estado_validacion', Reporte::VALIDACION_RECHAZADO)->get();
        foreach ($rechazados as $rep) {
            $this->estadoValidacionUpdates[$rep->id] = 'rechazado';
            $this->inundacionVincularIds[$rep->id] = $rep->inundacion_id ?? '';
            $this->motivoRechazoUpdates[$rep->id] = $rep->motivo_rechazo_codigo ?? '';
            $this->motivoTextoUpdates[$rep->id] = $rep->motivo_rechazo_texto ?? '';
            $this->reversionTextoUpdates[$rep->id] = '';
        }
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
            'fecha'                => $h->fecha_accion?->format('d/m/Y H:i'),
            'accion'               => $h->accion,
            'estado_anterior'      => $h->estado_anterior,
            'estado_nuevo'         => $h->estado_nuevo,
            'validador'            => $h->validador?->name ?? '—',
            'motivo'               => $h->motivo?->label_autoridad ?? $h->motivo_texto,
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

    public function abrirModificarRechazado(int $id): void
    {
        if ($this->role !== 'authority') {
            return;
        }

        $rep = Reporte::findOrFail($id);
        $this->modificarReporteId = $id;
        $this->estadoValidacionUpdates[$id] = $this->estadoValidacionUpdates[$id] ?? 'rechazado';
        $this->inundacionVincularIds[$id] = $this->inundacionVincularIds[$id] ?? ($rep->inundacion_id ?? '');
        $this->motivoRechazoUpdates[$id] = $this->motivoRechazoUpdates[$id] ?? ($rep->motivo_rechazo_codigo ?? '');
        $this->motivoTextoUpdates[$id] = $this->motivoTextoUpdates[$id] ?? ($rep->motivo_rechazo_texto ?? '');
        $this->reversionTextoUpdates[$id] = $this->reversionTextoUpdates[$id] ?? '';
    }

    public function cerrarModificarRechazado(): void
    {
        $this->modificarReporteId = null;
    }

    public function limpiarFiltrosRechazados(): void
    {
        $this->filtroRechazoMotivo = '';
        $this->filtroRechazoValidador = '';
        $this->filtroRechazoDesde = '';
        $this->filtroRechazoHasta = '';
        $this->resetPage();
    }

    protected function queryReportesRechazados()
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

    protected function queryReportesPendientes()
    {
        return Reporte::whereNull('inundacion_id')
            ->where('estado_validacion', Reporte::VALIDACION_PENDIENTE)
            ->with('citizen')
            ->latest();
    }

    protected function enrichPendientesWithCercanas($reportesPendientes, $activas, ReporteValidacionService $validacion): void
    {
        app(InundacionMapaService::class)->enrichPendientesConCercanas($reportesPendientes, $activas);

        foreach ($reportesPendientes as $rep) {
            $rep->rechazos_previos = $validacion->contarRechazosCiudadano($rep->citizen_carnet);
        }
    }

    protected function validadoresRechazoList()
    {
        return User::query()
            ->where('role', User::ROLE_AUTHORITY)
            ->whereIn('carnet', Reporte::query()
                ->where('estado_validacion', Reporte::VALIDACION_RECHAZADO)
                ->whereNotNull('validador_id')
                ->distinct()
                ->pluck('validador_id'))
            ->orderBy('name')
            ->get(['carnet', 'name']);
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

            $this->modificarReporteId = null;
            session()->flash('success', "Estado de validación del reporte #{$reporte->id} actualizado a \"{$estadoValidacion}\".");
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first() ?? 'Error de validación.');
        }
    }

    protected function validationViewData(): array
    {
        $motivosRechazo = collect();
        $inundacionesActivasParaVincular = collect();
        $validadoresRechazo = collect();

        if ($this->role === 'authority') {
            $motivosRechazo = MotivoRechazo::activos()->orderBy('codigo')->get();
            $inundacionesActivasParaVincular = Inundacion::activas()->with('reportesActivosTTL')->get()
                ->filter(fn (Inundacion $i) => app(InundacionMapaService::class)->inundacionTieneReportesVivos($i));
            $validadoresRechazo = $this->validadoresRechazoList();
        }

        return [
            'motivosRechazo'                  => $motivosRechazo,
            'inundacionesActivasParaVincular' => $inundacionesActivasParaVincular,
            'validadoresRechazo'              => $validadoresRechazo,
        ];
    }
}
