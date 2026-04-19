<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFloodReportRequest;
use App\Http\Requests\Api\UpdateFloodReportRequest;
use App\Http\Resources\FloodReportResource;
use App\Models\FloodReport;
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

        $report->load(['citizen']);

        return response()->json([
            'data' => new FloodReportResource($report),
        ]);
    }
}
