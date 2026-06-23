<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FloodApiClient;
use App\Services\FloodApiExceptions\ApiRequestException;
use App\Services\FloodApiExceptions\ApiValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

final class AuthorityController
{
    public function __construct(private readonly FloodApiClient $api)
    {
    }

    public function create(): View
    {
        return view('authorities.create');
    }

    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $token = (string) $request->session()->get('api_token', '');

        try {
            $citizens = $this->api->searchCitizens($token, $query);
            return response()->json($citizens);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error searching citizens: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'carnet' => ['required', 'string', 'max:20'],
        ]);

        $carnet = (string) $request->input('carnet');
        $token = (string) $request->session()->get('api_token', '');

        try {
            $this->api->promoteAuthority($token, $carnet);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiRequestException $e) {
            return back()->withInput()->withErrors([
                'carnet' => [$e->getMessage()],
            ]);
        }

        return redirect()->route('authorities.create')->with('status', 'Usuario promovido a autoridad con éxito.');
    }
}
