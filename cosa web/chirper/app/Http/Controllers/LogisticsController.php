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
        $apiUser = (array) $request->session()->get('api_user', []);
        $isAdmin = (string) ($apiUser['role'] ?? '') === 'authority';
        
        try {
            // Este método en ApiClient devuelve el array puro
            $centros = $this->api->listCentros($token, 1, $request->query('provincia'), $request->query('municipio'));
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return view('logistics.index', [
                'centros' => [],
                'error' => 'No se pudieron cargar los centros de logística: ' . $e->getMessage(),
                'isAdmin' => false,
            ]);
        }

        return view('logistics.index', [
            'centros' => $centros,
            'error' => null,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
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
            return $request->wantsJson()
                ? response()->json(['errors' => $validationMessages], 422)
                : back()->withInput()->withErrors($validationMessages);
        } catch (ApiRequestException $e) {
            return $request->wantsJson()
                ? response()->json(['error' => $e->getMessage()], 400)
                : back()->withInput()->withErrors(['apiError' => $e->getMessage()]);
        }
        
        return $request->wantsJson()
            ? response()->json(['status' => 'success', 'message' => 'Centro de asistencia creado exitosamente.'])
            : redirect()->route('logistica.index')->with('status', 'Centro de asistencia creado exitosamente.');
    }

    public function update(Request $request, $id): RedirectResponse|\Illuminate\Http\JsonResponse
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
            return $request->wantsJson()
                ? response()->json(['errors' => $validationMessages], 422)
                : back()->withInput()->withErrors($validationMessages);
        } catch (ApiRequestException $e) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Error al actualizar: ' . $e->getMessage()], 400)
                : back()->withInput()->withErrors(['apiError' => 'Error al actualizar: ' . $e->getMessage()]);
        }
        
        return $request->wantsJson()
            ? response()->json(['status' => 'success', 'message' => 'Centro de asistencia actualizado correctamente.'])
            : redirect()->route('logistica.index')->with('status', 'Centro de asistencia actualizado correctamente.');
    }

    public function destroy(Request $request, $id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $token = (string) $request->session()->get('api_token', '');
        
        try {
            $this->api->deleteCentro($token, $id);
            return $request->wantsJson()
                ? response()->json(['status' => 'success', 'message' => 'Centro de asistencia eliminado correctamente.'])
                : redirect()->route('logistica.index')->with('status', 'Centro de asistencia eliminado correctamente.');
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return $request->wantsJson()
                ? response()->json(['error' => 'No autorizado'], 401)
                : redirect()->route('login');
        } catch (ApiRequestException $e) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Error al eliminar: ' . $e->getMessage()], 400)
                : back()->withErrors(['apiError' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    }
}
