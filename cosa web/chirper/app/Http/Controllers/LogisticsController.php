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

final class LogisticsController
{
    public function __construct(private readonly FloodApiClient $api)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');
        
        try {
            // Este método en ApiClient devuelve el array puro
            $centros = $this->api->listCentros($token);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return view('logistics.index', [
                'centros' => [],
                'error' => 'No se pudieron cargar los centros de logística: ' . $e->getMessage(),
            ]);
        }

        return view('logistics.index', [
            'centros' => $centros,
            'error' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');
        
        $payload = $request->except('_token');
        
        try {
            $this->api->createCentro($token, $payload);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (\App\Services\FloodApiExceptions\ApiValidationException $e) {
            $validationMessages = [];
            foreach ($e->errors as $field => $messages) {
                 $validationMessages[$field] = is_array($messages) ? implode(' ', $messages) : $messages;
            }
            return back()->withInput()->withErrors($validationMessages);
        } catch (ApiRequestException $e) {
            return back()->withInput()->withErrors(['apiError' => $e->getMessage()]);
        }
        
        return redirect()->route('logistica.index')->with('status', 'Centro de asistencia creado exitosamente.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');
        
        $payload = $request->except(['_token', '_method']);
        
        try {
            $this->api->updateCentro($token, $id, $payload);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (\App\Services\FloodApiExceptions\ApiValidationException $e) {
            $validationMessages = [];
            foreach ($e->errors as $field => $messages) {
                 $validationMessages[$field] = is_array($messages) ? implode(' ', $messages) : $messages;
            }
            return back()->withInput()->withErrors($validationMessages);
        } catch (ApiRequestException $e) {
            return back()->withInput()->withErrors(['apiError' => 'Error al actualizar: ' . $e->getMessage()]);
        }
        
        return redirect()->route('logistica.index')->with('status', 'Centro de asistencia actualizado correctamente.');
    }
}
