<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesReportValidation;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsRechazados extends Component
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

        $this->initRechazadosFormState();
    }

    public function updatedFiltroRechazoMotivo(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroRechazoValidador(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroRechazoDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroRechazoHasta(): void
    {
        $this->resetPage();
    }

    #[On('refreshReports')]
    public function refreshData(): void
    {
    }

    public function render()
    {
        $reportesRechazados = $this->queryReportesRechazados()->paginate(15);

        return view('livewire.reports-rechazados', array_merge([
            'reportesRechazados' => $reportesRechazados,
        ], $this->validationViewData()))->layout('layouts.app');
    }
}
