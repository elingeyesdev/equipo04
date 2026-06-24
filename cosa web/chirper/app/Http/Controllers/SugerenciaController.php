<?php

namespace App\Http\Controllers;

use App\Models\Sugerencia;
use Illuminate\Http\Request;

class SugerenciaController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->query('sort', 'likes'); // por defecto ordenamos por likes

        if ($sort === 'recientes') {
            $sugerencias = Sugerencia::latest()->get();
        } else {
            $sugerencias = Sugerencia::orderBy('likes', 'desc')->latest()->get();
        }

        return view('sugerencias.index', compact('sugerencias', 'sort'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);

        $apiUser = session('api_user', []);
        
        if (empty($apiUser)) {
            return redirect()->back()->withErrors('Debes estar autenticado para crear una sugerencia.');
        }

        Sugerencia::create([
            'titulo' => $request->titulo,
            'contenido' => $request->contenido,
            'autor_nombre' => $apiUser['name'] ?? 'Usuario',
            'likes' => 0,
        ]);

        return redirect()->route('sugerencias.index')->with('status', 'Sugerencia publicada correctamente.');
    }

    public function incrementLike(Sugerencia $sugerencia)
    {
        $sugerencia->increment('likes');
        return redirect()->back()->with('status', '¡Gracias por tu voto!');
    }
}
