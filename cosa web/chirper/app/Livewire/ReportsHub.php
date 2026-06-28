<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesReportValidation;
use App\Livewire\Concerns\SerializesInundaciones;
use App\Models\Inundacion;
use App\Models\Reporte;
use App\Services\FloodApiClient;
use App\Services\ReporteValidacionService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsHub extends Component
{
    use ManagesReportValidation;
    use SerializesInundaciones;
    use WithPagination;

    public $role = '';
    public $carnet = '';

    public function mount(): void
    {
        $user = (array) session()->get('api_user', []);
        $this->role = (string) ($user['role'] ?? '');
        $this->carnet = (string) ($user['carnet'] ?? '');
        $this->initRechazadosFormState();
    }

    #[On('refreshReports')]
    #[On('echo:reportes,ReporteCreado')]
    #[On('echo:inundaciones,InundacionActualizada')]
    public function refreshData(): void
    {
    }

    public function desactivar(int $id): void
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

    public function renovarReporte(int $id): void
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
        $this->skipRender();
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

        $misReportes = collect();
        $totalMisReportes = 0;

        if ($this->carnet !== '') {
            $misQuery = Reporte::where('citizen_carnet', $this->carnet)->with('motivoRechazo');
            $totalMisReportes = (clone $misQuery)->count();
            $misReportes = $misQuery->latest('updated_at')->take(2)->get();
        }

        $reportesPendientes = collect();
        $reportesRechazados = collect();
        $totalPendientes = 0;
        $totalRechazados = 0;
        $validacion = app(ReporteValidacionService::class);

        if ($this->role === 'authority') {
            $pendientesQuery = $this->queryReportesPendientes();
            $totalPendientes = (clone $pendientesQuery)->count();
            $reportesPendientes = $pendientesQuery->take(5)->get();

            $rechazadosQuery = $this->queryReportesRechazados();
            $totalRechazados = (clone $rechazadosQuery)->count();
            $reportesRechazados = $rechazadosQuery->take(5)->get();

            $activas = Inundacion::activas()->get();
            $this->enrichPendientesWithCercanas($reportesPendientes, $activas, $validacion);
        }

        return view('livewire.reports-hub', array_merge([
            'inundacionesActivas' => $inundacionesActivas,
            'misReportes'         => $misReportes,
            'totalMisReportes'    => $totalMisReportes,
            'reportesPendientes'  => $reportesPendientes,
            'reportesRechazados'  => $reportesRechazados,
            'totalPendientes'     => $totalPendientes,
            'totalRechazados'     => $totalRechazados,
            'ors_key'             => config('services.openrouteservice.key'),
            'meta'                => [
                'current_page' => $activasPaginator->currentPage(),
                'last_page'    => $activasPaginator->lastPage(),
            ],
        ], $this->validationViewData()))->layout('layouts.app');
    }
}
