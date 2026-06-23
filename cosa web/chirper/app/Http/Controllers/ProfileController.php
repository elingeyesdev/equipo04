<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        $apiUser = session('api_user', []);
        
        if (empty($apiUser)) {
            return redirect()->route('login')->withErrors('Debes iniciar sesión para ver tu perfil.');
        }

        $user = User::where('carnet', $apiUser['carnet'])->first();

        // Obtener inventario donado por este usuario
        $aportes = Inventario::with('centro', 'trazabilidad')
            ->where('donor_carnet', $apiUser['carnet'])
            ->latest()
            ->get();

        return view('profile.show', compact('user', 'aportes'));
    }

    public function update(Request $request)
    {
        $apiUser = session('api_user', []);
        
        if (empty($apiUser)) {
            return redirect()->route('login');
        }

        $user = User::where('carnet', $apiUser['carnet'])->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->carnet . ',carnet',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Actualizar sesión si es necesario
        $apiUser['name'] = $user->name;
        session(['api_user' => $apiUser]);

        return redirect()->route('profile.show')->with('status', 'Perfil actualizado correctamente.');
    }
}
