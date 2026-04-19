<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAuthorityResponseRequest;
use App\Http\Resources\AuthorityResponseResource;
use App\Models\AuthorityResponse;
use App\Models\FloodReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuthorityResponseController extends Controller
{
    public function index(Request $request, FloodReport $report): AnonymousResourceCollection
    {
        $this->authorize('view', $report);

        $responses = $report->responses()->with('authority')->latest()->paginate(15);

        return AuthorityResponseResource::collection($responses);
    }

    public function store(StoreAuthorityResponseRequest $request, FloodReport $report): JsonResponse
    {
        $this->authorize('create', [AuthorityResponse::class, $report]);

        $data = $request->validated();

        $response = $report->responses()->create([
            'authority_carnet' => $request->user()->carnet,
            'message' => $data['message'],
        ]);

        $response->load('authority');

        return response()->json([
            'data' => new AuthorityResponseResource($response),
        ], 201);
    }
}
