<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\LogisticsController;
use App\Http\Middleware\ApiAuthenticate;
use App\Http\Middleware\EnsureApiAuthority;
use App\Http\Middleware\RedirectIfApiAuthenticated;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return session()->has('api_token')
        ? redirect()->route('reports.index')
        : redirect()->route('login');
})->name('home');

Route::middleware(RedirectIfApiAuthenticated::class)->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(ApiAuthenticate::class)->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/maps', [MapController::class, 'index'])->name('maps.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');

    Route::middleware(EnsureApiAuthority::class)->group(function () {
        Route::post('/reports/{id}/responses', [ReportController::class, 'storeResponse'])->name('reports.responses.store');
        Route::post('/reports/{id}/status', [ReportController::class, 'updateStatus'])->name('reports.status.update');
    });

    // Rutas de Logística (Centros de Asistencia)
    Route::get('/logistica', [LogisticsController::class, 'index'])->name('logistica.index');
    Route::post('/logistica', [LogisticsController::class, 'store'])->name('logistica.store');
    Route::patch('/logistica/{id}', [LogisticsController::class, 'update'])->name('logistica.update');
});
