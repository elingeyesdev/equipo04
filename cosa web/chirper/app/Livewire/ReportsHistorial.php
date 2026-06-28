<?php

namespace App\Livewire;

use App\Livewire\Concerns\SerializesInundaciones;
use App\Models\Inundacion;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsHistorial extends Component
{
    use SerializesInundaciones;
    use WithPagination;

    public $role = '';

    public function mount(): void
    {
        $user = (array) session()->get('api_user', []);
        $this->role = (string) ($user['role'] ?? '');
    }

    public function render()
    {
        $terminadasPaginator = Inundacion::terminadas()
            ->with('reportes')
            ->latest('updated_at')
            ->paginate(15);

        $inundacionesTerminadas = collect($terminadasPaginator->items())
            ->map(fn (Inundacion $i) => $this->serializarTerminada($i))
            ->all();

        return view('livewire.reports-historial', [
            'inundacionesTerminadas' => $inundacionesTerminadas,
            'terminadasPaginator'    => $terminadasPaginator,
        ])->layout('layouts.app');
    }
}
