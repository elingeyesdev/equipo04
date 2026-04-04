<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\FloodApiExceptions\ApiRequestException;
use App\Services\FloodApiExceptions\ApiUnauthorizedException;
use App\Services\FloodApiExceptions\ApiValidationException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

final class FloodApiClient
{
    private function baseUrl(): string
    {
        return rtrim((string) config('services.flood_api.base_url'), '/');
    }

    private function timeout(): int
    {
        return (int) config('services.flood_api.timeout', 10);
    }

    private function client(?string $token = null): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout());

        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    /**
     * @return array{token:string,user:array<string,mixed>}
     */
    public function register(array $payload): array
    {
        $response = $this->client()->post('/auth/register', $payload);

        $this->throwIfError($response);

        $json = (array) $response->json();

        return [
            'token' => (string) Arr::get($json, 'token'),
            'user' => (array) Arr::get($json, 'user', []),
        ];
    }

    /**
     * @return array{token:string,user:array<string,mixed>}
     */
    public function login(array $payload): array
    {
        $response = $this->client()->post('/auth/login', $payload);

        $this->throwIfError($response);

        $json = (array) $response->json();

        return [
            'token' => (string) Arr::get($json, 'token'),
            'user' => (array) Arr::get($json, 'user', []),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function me(string $token): array
    {
        $response = $this->client($token)->get('/auth/me');

        $this->throwIfError($response);

        $json = (array) $response->json();

        return (array) Arr::get($json, 'user', []);
    }

    public function logout(string $token): void
    {
        $response = $this->client($token)->post('/auth/logout');

        $this->throwIfError($response);
    }

    /**
     * @return array{data:array<int,mixed>,meta:array<string,mixed>,links:array<string,mixed>}
     */
    public function listReports(string $token, int $page = 1): array
    {
        $response = $this->client($token)->get('/reports', [
            'page' => $page,
        ]);

        $this->throwIfError($response);

        $json = (array) $response->json();

        return [
            'data' => (array) Arr::get($json, 'data', []),
            'meta' => (array) Arr::get($json, 'meta', []),
            'links' => (array) Arr::get($json, 'links', []),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function createReport(string $token, array $payload): array
    {
        $response = $this->client($token)->post('/reports', $payload);

        $this->throwIfError($response);

        $json = (array) $response->json();

        return (array) Arr::get($json, 'data', []);
    }

    /**
     * @return array<string,mixed>
     */
    public function getReport(string $token, int|string $reportId): array
    {
        $response = $this->client($token)->get('/reports/'.urlencode((string) $reportId));

        $this->throwIfError($response);

        $json = (array) $response->json();

        return (array) Arr::get($json, 'data', []);
    }

    /**
     * @return array<string,mixed>
     */
    public function updateReport(string $token, int|string $reportId, array $payload): array
    {
        $response = $this->client($token)->patch('/reports/'.urlencode((string) $reportId), $payload);

        $this->throwIfError($response);

        $json = (array) $response->json();

        return (array) Arr::get($json, 'data', []);
    }

    /**
     * @return array<string,mixed>
     */
    public function createResponse(string $token, int|string $reportId, array $payload): array
    {
        $response = $this->client($token)->post('/reports/'.urlencode((string) $reportId).'/responses', $payload);

        $this->throwIfError($response);

        $json = (array) $response->json();

        return (array) Arr::get($json, 'data', []);
    }

    private function throwIfError(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        if ($response->status() === 401) {
            throw new ApiUnauthorizedException('No autorizado por la API.');
        }

        $payload = (array) $response->json();

        if ($response->status() === 422) {
            $errors = (array) Arr::get($payload, 'errors', []);
            throw new ApiValidationException('Validación fallida.', $errors);
        }

        $message = (string) Arr::get($payload, 'message', $response->body());

        throw new ApiRequestException($message !== '' ? $message : 'Error al llamar la API.', $response->status(), $payload);
    }
}
