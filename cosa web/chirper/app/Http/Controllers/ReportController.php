<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FloodReport;
use App\Services\FloodApiClient;
use App\Services\FloodApiExceptions\ApiRequestException;
use App\Services\FloodApiExceptions\ApiUnauthorizedException;
use App\Services\FloodApiExceptions\ApiValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ReportController
{
    public function __construct(private readonly FloodApiClient $api)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = (array) $request->session()->get('api_user', []);
        $role = (string) ($user['role'] ?? '');
        $carnet = (string) ($user['carnet'] ?? '');
        $page = max(1, (int) $request->query('page', '1'));

        $query = FloodReport::query()->latest();

        if ($role !== 'authority' && $carnet !== '') {
            $query->where('citizen_carnet', $carnet);
        }

        $reports = $query->paginate(15, ['*'], 'page', $page);

        return view('reports.index', [
            'reports' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
            ],
            'error' => null,
        ]);
    }

    public function create(): View
    {
        return view('reports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'severity' => ['required', 'string', 'in:low,medium,high'],
        ]);

        try {
            $report = $this->api->createReport($token, $data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return back()->withInput()->withErrors([
                'description' => [$e->getMessage()],
            ]);
        }

        $id = Arr::get($report, 'id');

        return $id !== null
            ? redirect()->route('reports.show', ['id' => $id])
            : redirect()->route('reports.index');
    }

    public function show(Request $request, int|string $id): View|RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        try {
            $report = $this->api->getReport($token, $id);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            abort($e->status, $e->getMessage());
        }

        return view('reports.show', [
            'report' => $report,
        ]);
    }

    public function storeResponse(Request $request, int|string $id): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        try {
            $this->api->createResponse($token, $id, $data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return back()->withErrors([
                'message' => [$e->getMessage()],
            ]);
        }

        return redirect()->route('reports.show', ['id' => $id]);
    }

    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        $data = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,resolved,closed'],
        ]);

        try {
            $this->api->updateReport($token, $id, $data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return back()->withErrors([
                'status' => [$e->getMessage()],
            ]);
        }

        return redirect()->route('reports.show', ['id' => $id]);
    }

    public function latestForNotifications(Request $request): JsonResponse
    {
        $user = (array) $request->session()->get('api_user', []);
        $role = (string) ($user['role'] ?? '');
        $carnet = (string) ($user['carnet'] ?? '');

        $query = FloodReport::query()->latest();

        if ($role !== 'authority' && $carnet !== '') {
            $query->where('citizen_carnet', $carnet);
        }

        $latest = $query->first();

        if (! $latest) {
            return response()->json(['data' => null], 200);
        }

        return response()->json([
            'data' => [
                'id' => (string) $latest->id,
                'severity' => (string) $latest->severity,
            ],
        ]);
    }
}
