<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FloodApiClient;
use App\Services\FloodApiExceptions\ApiRequestException;
use App\Services\FloodApiExceptions\ApiUnauthorizedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

final class MapController
{
    public function __construct(private readonly FloodApiClient $api)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');
        
        $page = 1; 

        try {
            $result = $this->api->listReports($token, $page);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return view('maps.index', [
                'reports' => [],
                'error' => 'No se pudieron cargar los reportes para el mapa: ' . $e->getMessage(),
            ]);
        }

        return view('maps.index', [
            'reports' => (array) Arr::get($result, 'data', []),
            'error' => null,
        ]);
    }
}
