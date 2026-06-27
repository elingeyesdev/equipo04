<?php

namespace App\Livewire;

use App\Models\Reporte;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsMisReportes extends Component
{
    use WithPagination;

    public $role = '';
    public $carnet = '';

    public function mount(): void
    {
        $user = (array) session()->get('api_user', []);
        $this->role = (string) ($user['role'] ?? '');
        $this->carnet = (string) ($user['carnet'] ?? '');
    }

    public function render()
    {
        $misReportes = collect();

        if ($this->carnet !== '') {
            $misReportes = Reporte::where('citizen_carnet', $this->carnet)
                ->with('motivoRechazo')
                ->latest('updated_at')
                ->paginate(15);
        }

        return view('livewire.reports-mis-reportes', [
            'misReportes' => $misReportes,
            'role'        => $this->role,
        ])->layout('layouts.app');
    }
}
