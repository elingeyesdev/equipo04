<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthorityResponseController;
use App\Http\Controllers\Api\FloodReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('reports', [FloodReportController::class, 'index']);
    Route::post('reports', [FloodReportController::class, 'store']);
    Route::get('reports/{report}', [FloodReportController::class, 'show']);
    Route::patch('reports/{report}', [FloodReportController::class, 'update']);

    Route::get('reports/{report}/responses', [AuthorityResponseController::class, 'index']);
    Route::post('reports/{report}/responses', [AuthorityResponseController::class, 'store']);
});
