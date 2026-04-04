<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FloodApiClient;
use App\Services\FloodApiExceptions\ApiRequestException;
use App\Services\FloodApiExceptions\ApiUnauthorizedException;
use App\Services\FloodApiExceptions\ApiValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AuthController
{
    public function __construct(private readonly FloodApiClient $api)
    {
    }

    public function showLogin(Request $request): View
    {
        return view('auth.login', [
            'intended' => $request->session()->get('intended', ''),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'intended' => ['nullable', 'string'],
        ]);

        $intended = (string) ($data['intended'] ?? '');
        if ($intended === '') {
            $intended = (string) $request->session()->pull('intended', '');
        }

        try {
            $result = $this->api->login([
                'login' => $data['login'],
                'password' => $data['password'],
            ]);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            throw ValidationException::withMessages([
                'login' => ['Credenciales inválidas.'],
            ]);
        } catch (ApiRequestException $e) {
            return back()->withInput()->withErrors([
                'login' => [$e->getMessage()],
            ]);
        }

        $token = (string) Arr::get($result, 'token', '');
        $user = (array) Arr::get($result, 'user', []);

        if ($token === '') {
            return back()->withInput()->withErrors([
                'login' => ['La API no devolvió token.'],
            ]);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('api_token', $token);
        $request->session()->put('api_user', $user);

        return $intended !== ''
            ? redirect()->to($intended)
            : redirect()->route('reports.index');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'carnet' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        try {
            $result = $this->api->register($data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiRequestException $e) {
            return back()->withInput()->withErrors([
                'carnet' => [$e->getMessage()],
            ]);
        }

        $token = (string) Arr::get($result, 'token', '');
        $user = (array) Arr::get($result, 'user', []);

        if ($token === '') {
            return back()->withInput()->withErrors([
                'carnet' => ['La API no devolvió token.'],
            ]);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('api_token', $token);
        $request->session()->put('api_user', $user);

        return redirect()->route('reports.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        if ($token !== '') {
            try {
                $this->api->logout($token);
            } catch (ApiRequestException) {
                // ignore API errors on logout
            }
        }

        $request->session()->forget(['api_token', 'api_user', 'intended']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
