<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InundacionController;
use App\Http\Controllers\Api\CentroAsistenciaController;
use App\Http\Controllers\Api\ReporteController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// Rutas públicas de reportes rápidos
Route::post('reportes', [ReporteController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    // Autoridades: Búsqueda y Promoción
    Route::get('citizens/search', [\App\Http\Controllers\Api\AuthorityController::class, 'searchCitizens']);
    Route::post('authorities/promote', [\App\Http\Controllers\Api\AuthorityController::class, 'promote']);

    Route::get('reports', [InundacionController::class, 'index']);
    Route::post('reports', [InundacionController::class, 'store']);
    Route::get('reports/{report}', [InundacionController::class, 'show']);
    Route::patch('reports/{report}', [InundacionController::class, 'update']);

    // Nuevas rutas de validación de reportes rápidos (Autoridad)
    Route::get('reportes/pendientes', [ReporteController::class, 'pending']);
    Route::post('reportes/{id}/validar', [ReporteController::class, 'validateReport']);

    // Logística: Centros de Asistencia / Acopio
    Route::get('centros', [CentroAsistenciaController::class, 'index']);
    Route::post('centros', [CentroAsistenciaController::class, 'store']);
    Route::patch('centros/{centro_id}', [CentroAsistenciaController::class, 'update']);
    Route::delete('centros/{centro_id}', [CentroAsistenciaController::class, 'destroy']);

    // Rastreo de Vehículos (App Móvil)
    Route::post('tracking/ping', [\App\Http\Controllers\Api\VehicleTrackingController::class, 'ping']);
});
