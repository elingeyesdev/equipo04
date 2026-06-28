<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\InundacionPublicController;
use Illuminate\View\View;

class ReporteRapidoController extends Controller
{
    public function show(): View
    {
        return view('reports.rapido', [
            'inundacionesPublicas' => InundacionPublicController::serializarParaVista(),
        ]);
    }
}
