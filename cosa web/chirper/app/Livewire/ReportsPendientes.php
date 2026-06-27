<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesReportValidation;
use App\Models\Inundacion;
use App\Services\ReporteValidacionService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsPendientes extends Component
{
    use ManagesReportValidation;
    use WithPagination;

    public $role = '';
    public $carnet = '';

    public function mount(): void
    {
        $user = (array) session()->get('api_user', []);
        $this->role = (string) ($user['role'] ?? '');
        $this->carnet = (string) ($user['carnet'] ?? '');

        if ($this->role !== 'authority') {
            abort(403);
        }
    }

    #[On('refreshReports')]
    public function refreshData(): void
    {
    }

    public function render()
    {
        $validacion = app(ReporteValidacionService::class);
        $activas = Inundacion::activas()->get();

        $reportesPendientes = $this->queryReportesPendientes()
            ->paginate(10);

        $this->enrichPendientesWithCercanas(collect($reportesPendientes->items()), $activas, $validacion);

        return view('livewire.reports-pendientes', array_merge([
            'reportesPendientes' => $reportesPendientes,
            'ors_key'            => config('services.openrouteservice.key'),
        ], $this->validationViewData()))->layout('layouts.app');
    }
}
