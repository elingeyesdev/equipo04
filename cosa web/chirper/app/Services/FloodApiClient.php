<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\FloodApiExceptions\ApiRequestException;
use App\Services\FloodApiExceptions\ApiUnauthorizedException;
use App\Services\FloodApiExceptions\ApiValidationException;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

final class FloodApiClient
{
    public function __construct(private readonly Kernel $kernel)
    {
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.flood_api.base_url'), '/');
    }

    private function timeout(): int
    {
        return (int) config('services.flood_api.timeout', 10);
    }

    private function useInternalApi(): bool
    {
        $flag = env('FLOOD_API_INTERNAL', true);

        return filter_var($flag, FILTER_VALIDATE_BOOL) !== false;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{status:int,json:array<string,mixed>,body:string}
     */
    private function request(string $method, string $path, array $payload = [], ?string $token = null): array
    {
        return $this->useInternalApi()
            ? $this->requestInternal($method, $path, $payload, $token)
            : $this->requestHttp($method, $path, $payload, $token);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{status:int,json:array<string,mixed>,body:string}
     */
    private function requestHttp(string $method, string $path, array $payload = [], ?string $token = null): array
    {
        $request = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout());

        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        $path = '/'.ltrim($path, '/');

        $response = match (strtoupper($method)) {
            'GET' => $request->get($path, $payload),
            'PATCH' => $request->patch($path, $payload),
            'DELETE' => $request->delete($path, $payload),
            default => $request->post($path, $payload),
        };

        return [
            'status' => $response->status(),
            'json' => (array) $response->json(),
            'body' => $response->body(),
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{status:int,json:array<string,mixed>,body:string}
     */
    private function requestInternal(string $method, string $path, array $payload = [], ?string $token = null): array
    {
        $httpMethod = strtoupper($method);
        $uri = '/api/'.ltrim($path, '/');

        if ($httpMethod === 'GET' && $payload !== []) {
            $uri .= '?'.http_build_query($payload);
        }

        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];

        if ($token !== null && $token !== '') {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $content = null;
        $requestPayload = [];

        if ($httpMethod !== 'GET') {
            $requestPayload = $payload;
            $content = $payload !== [] ? (string) json_encode($payload, JSON_THROW_ON_ERROR) : '{}';
            $server['CONTENT_LENGTH'] = (string) strlen($content);
        }

        $request = Request::create(
            $uri,
            $httpMethod,
            $requestPayload,
            [],
            [],
            $server,
            $content
        );

        $response = $this->kernel->handle($request);
        $body = (string) $response->getContent();
        $decoded = json_decode($body, true);

        $this->kernel->terminate($request, $response);

        return [
            'status' => $response->getStatusCode(),
            'json' => is_array($decoded) ? $decoded : [],
            'body' => $body,
        ];
    }

    /**
     * @return array{token:string,user:array<string,mixed>}
     */
    public function register(array $payload): array
    {
        $response = $this->request('POST', '/auth/register', $payload);

        $this->throwIfError($response);

        $json = $response['json'];

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
        $response = $this->request('POST', '/auth/login', $payload);

        $this->throwIfError($response);

        $json = $response['json'];

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
        $response = $this->request('GET', '/auth/me', [], $token);

        $this->throwIfError($response);

        $json = $response['json'];

        return (array) Arr::get($json, 'user', []);
    }

    public function logout(string $token): void
    {
        $response = $this->request('POST', '/auth/logout', [], $token);

        $this->throwIfError($response);
    }

    /**
     * @return array{data:array<int,mixed>,meta:array<string,mixed>,links:array<string,mixed>}
     */
    public function listReports(string $token, int $page = 1): array
    {
        $response = $this->request('GET', '/reports', [
            'page' => $page,
        ], $token);

        $this->throwIfError($response);

        $json = $response['json'];

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
        $response = $this->request('POST', '/reports', $payload, $token);

        $this->throwIfError($response);

        $json = $response['json'];

        return (array) Arr::get($json, 'data', []);
    }

    /**
     * @return array<string,mixed>
     */
    public function getReport(string $token, int|string $reportId): array
    {
        $response = $this->request('GET', '/reports/'.urlencode((string) $reportId), [], $token);

        $this->throwIfError($response);

        $json = $response['json'];

        return (array) Arr::get($json, 'data', []);
    }

    /**
     * @return array<string,mixed>
     */
    public function updateReport(string $token, int|string $reportId, array $payload): array
    {
        $payload['_method'] = 'PATCH';
        $response = $this->request('POST', '/reports/'.urlencode((string) $reportId), $payload, $token);

        $this->throwIfError($response);

        $json = $response['json'];

        return (array) Arr::get($json, 'data', []);
    }

    /**
     * @return array<string,mixed>
     */
    public function createResponse(string $token, int|string $reportId, array $payload): array
    {
        $response = $this->request('POST', '/reports/'.urlencode((string) $reportId).'/responses', $payload, $token);

        $this->throwIfError($response);

        $json = $response['json'];

        return (array) Arr::get($json, 'data', []);
    }

    /**
     * @return array<int,mixed>
     */
    public function listCentros(string $token, int $page = 1): array
    {
        $response = $this->request('GET', '/centros', [
            'page' => $page,
        ], $token);

        $this->throwIfError($response);

        $json = $response['json'];

        // El backend retorna un array puro en 'data'
        return (array) Arr::get($json, 'data', []);
    }

    /**
     * @return array<string,mixed>
     */
    public function createCentro(string $token, array $payload): array
    {
        $response = $this->request('POST', '/centros', $payload, $token);

        $this->throwIfError($response);

        $json = $response['json'];

        return (array) Arr::get($json, 'data', []);
    }

    /**
     * @return array<string,mixed>
     */
    public function updateCentro(string $token, string|int $id, array $payload): array
    {
        // Usamos POST con _method=PATCH para evitar el Fatal Error de PHP 8.4 (request_parse_body) en el servidor de desarrollo
        $payload['_method'] = 'PATCH';
        $response = $this->request('POST', '/centros/' . urlencode((string) $id), $payload, $token);

        $this->throwIfError($response);

        $json = $response['json'];

        return (array) Arr::get($json, 'data', []);
    }

    public function deleteCentro(string $token, string|int $id): void
    {
        $response = $this->request('POST', '/centros/' . urlencode((string) $id), [
            '_method' => 'DELETE'
        ], $token);

        $this->throwIfError($response);
    }

    /**
     * @param  array{status:int,json:array<string,mixed>,body:string}  $response
     */
    private function throwIfError(array $response): void
    {
        if ($response['status'] >= 200 && $response['status'] < 300) {
            return;
        }

        if ($response['status'] === 401) {
            throw new ApiUnauthorizedException('No autorizado por la API.');
        }

        $payload = $response['json'];

        if ($response['status'] === 422) {
            $errors = (array) Arr::get($payload, 'errors', []);
            throw new ApiValidationException('Validación fallida.', $errors);
        }

        $message = (string) Arr::get($payload, 'message', $response['body']);

        throw new ApiRequestException($message !== '' ? $message : 'Error al llamar la API.', $response['status'], $payload);
    }
}
