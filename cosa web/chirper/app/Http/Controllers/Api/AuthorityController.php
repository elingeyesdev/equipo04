<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthorityController extends Controller
{
    public function searchCitizens(Request $request): JsonResponse
    {
        if (!$request->user()->isAuthority()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $query = $request->input('q', '');
        
        \Illuminate\Support\Facades\Log::info("Search request received for: " . $query);

        $citizens = User::where('role', User::ROLE_CITIZEN)
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'ilike', "%{$query}%")
                        ->orWhere('carnet', 'ilike', "%{$query}%");
                });
            })
            ->limit(10)
            ->get(['carnet', 'name']);

        return response()->json(['data' => $citizens]);
    }

    public function promote(Request $request): JsonResponse
    {
        if (!$request->user()->isAuthority()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'carnet' => ['required', 'string', 'max:20'],
        ]);

        $carnet = $request->input('carnet');
        $user = User::where('carnet', $carnet)->first();

        if (!$user) {
            throw ValidationException::withMessages(['carnet' => 'Usuario no encontrado.']);
        }

        if ($user->role === User::ROLE_AUTHORITY) {
            throw ValidationException::withMessages(['carnet' => 'El usuario ya es autoridad.']);
        }

        $user->update(['role' => User::ROLE_AUTHORITY]);

        return response()->json(['message' => 'Usuario promovido a autoridad con éxito.']);
    }
}
