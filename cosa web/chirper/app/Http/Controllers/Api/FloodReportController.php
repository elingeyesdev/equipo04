<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFloodReportRequest;
use App\Http\Requests\Api\UpdateFloodReportRequest;
use App\Http\Resources\FloodReportResource;
use App\Models\FloodReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FloodReportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = FloodReport::query()->latest();

        if (! $user->isAuthority()) {
            $query->where('citizen_carnet', $user->carnet);
        }

        if ($request->filled('provincia')) {
            $query->where('provincia', $request->provincia);
        }

        if ($request->filled('municipio')) {
            $query->where('municipio', $request->municipio);
        }

        $reports = $query->paginate(15);

        return FloodReportResource::collection($reports);
    }

    public function store(StoreFloodReportRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('create', FloodReport::class);

        $data = $request->validated();

        $report = FloodReport::create([
            'citizen_carnet' => $user->carnet,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'provincia' => $data['provincia'],
            'municipio' => $data['municipio'],
            'address' => $data['address'] ?? null,
            'description' => $data['description'],
            'severity' => $data['severity'],
            'status' => 'open',
        ]);

        return response()->json([
            'data' => new FloodReportResource($report),
        ], 201);
    }

    public function show(Request $request, FloodReport $report): JsonResponse
    {
        $this->authorize('view', $report);

        $report->load(['citizen', 'responses.authority']);

        return response()->json([
            'data' => new FloodReportResource($report),
        ]);
    }

    public function update(UpdateFloodReportRequest $request, FloodReport $report): JsonResponse
    {
        $this->authorize('update', $report);

        $data = $request->validated();

        $user = $request->user();

        if (! $user->isAuthority() && array_key_exists('status', $data)) {
            unset($data['status']);
        }

        $report->fill($data);
        $report->save();

        if ($user->isAuthority()) {
            $this->refreshCitizenBanStatus((string) $report->citizen_carnet);
        }

        $report->load(['citizen']);

        return response()->json([
            'data' => new FloodReportResource($report),
        ]);
    }

    private function refreshCitizenBanStatus(string $citizenCarnet): void
    {
        $falseReportsCount = FloodReport::query()
            ->where('citizen_carnet', $citizenCarnet)
            ->where('status', 'false_report')
            ->count();

        User::query()
            ->where('carnet', $citizenCarnet)
            ->update([
                'is_banned' => $falseReportsCount >= 2,
            ]);
    }
}
