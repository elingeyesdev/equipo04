<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\VictimaController;
use App\Http\Controllers\DonacionController;
use App\Http\Middleware\ApiAuthenticate;
use App\Http\Middleware\EnsureApiAuthority;
use App\Http\Middleware\RedirectIfApiAuthenticated;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return session()->has('api_token')
        ? redirect()->to(route('reports.index', [], false))
        : redirect()->to(route('login', [], false));
})->name('home');

Route::middleware(RedirectIfApiAuthenticated::class)->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/reporte-rapido', function () {
    // Solo id, latitud y longitud — intensidad_actual fue eliminada (cálculo dinámico).
    $activas = \App\Models\Inundacion::where('estado', 'activa')->get(['id', 'latitud', 'longitud']);
    return view('reports.rapido', ['inundacionesActivas' => $activas]);
})->name('reports.rapido');

Route::get('/weather/tiles/{layer}/{z}/{x}/{y}', [\App\Http\Controllers\WeatherController::class, 'getTile'])->name('weather.tiles');

// Proxy para Open Topo Data — datos de elevación del terreno
// Usado por el Job CalcularPoligonoInundacion y opcionalmente por el frontend
Route::get('/api/elevation', [\App\Http\Controllers\ElevationController::class, 'getElevation'])->name('elevation.get');

Route::middleware(ApiAuthenticate::class)->group(function () {
    Route::get('/reports', \App\Livewire\ReportsIndex::class)->name('reports.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/notifications/feed', [ReportController::class, 'notificationsFeed'])->name('reports.notifications.feed');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');

    Route::middleware(EnsureApiAuthority::class)->group(function () {
        Route::post('/reports/{id}/responses', [ReportController::class, 'storeResponse'])->name('reports.responses.store');
        Route::post('/reports/{id}/status', [ReportController::class, 'updateestado'])->name('reports.status.update');

        Route::get('/reports/notifications/latest', [ReportController::class, 'latestForNotifications'])->name('reports.notifications.latest');
    });

    // ── Logística (Centros de Asistencia) ────────────────────────────────
    Route::get('/logistica', [LogisticsController::class, 'index'])->name('logistica.index');

    // ── Módulo de Vehículos (Vistas de Mapa y Activos) ────────────────────
    Route::get('/vehiculos/mapa', [VehiculoController::class, 'mapa'])->name('vehiculos.mapa');
    Route::get('/vehiculos/activos', [VehiculoController::class, 'activos'])->name('vehiculos.activos');

    // ── Módulo de Víctimas ────────────────────────────────────────────────
    // Las rutas GET con segmentos estáticos deben ir ANTES de la ruta con {id}
    Route::get('/victimas', [VictimaController::class, 'index'])->name('victimas.index');
    Route::get('/victimas/create', [VictimaController::class, 'create'])->name('victimas.create');
    Route::get('/victimas/{id}', [VictimaController::class, 'show'])->name('victimas.show')->where('id', '[0-9]+');

    // ── Módulo de Donaciones ──────────────────────────────────────────────
    Route::get('/donaciones', [DonacionController::class, 'index'])->name('donaciones.index');
    // ── Centro de Comando (Timeline y Análisis) ───────────────────────────
    Route::get('/command-center', [\App\Http\Controllers\CommandCenterController::class, 'index'])->name('command-center.index');
    Route::get('/command-center/data', [\App\Http\Controllers\CommandCenterController::class, 'getData'])->name('command-center.data');

    // Operaciones de escritura — solo autoridad
    Route::middleware(EnsureApiAuthority::class)->group(function () {
        // Logística
        Route::post('/logistica', [LogisticsController::class, 'store'])->name('logistica.store');
        Route::patch('/logistica/{id}', [LogisticsController::class, 'update'])->name('logistica.update');
        Route::delete('/logistica/{id}', [LogisticsController::class, 'destroy'])->name('logistica.destroy');

        // Vehículos — Gestión
        Route::get('/vehiculos', [VehiculoController::class, 'index'])->name('vehiculos.index');
        Route::post('/vehiculos', [VehiculoController::class, 'store'])->name('vehiculos.store');

        // Víctimas — CRUD completo
        Route::post('/victimas', [VictimaController::class, 'store'])->name('victimas.store');
        Route::get('/victimas/{id}/edit', [VictimaController::class, 'edit'])->name('victimas.edit')->where('id', '[0-9]+');
        Route::put('/victimas/{id}', [VictimaController::class, 'update'])->name('victimas.update')->where('id', '[0-9]+');
        Route::delete('/victimas/{id}', [VictimaController::class, 'destroy'])->name('victimas.destroy')->where('id', '[0-9]+');

        // Centro de Comando — Operaciones Autoridad
        Route::post('/command-center/danos', [\App\Http\Controllers\CommandCenterController::class, 'registrarDano'])->name('command-center.danos.store');
        Route::post('/command-center/merge', [\App\Http\Controllers\CommandCenterController::class, 'mergeInundaciones'])->name('command-center.merge');
        Route::get('/command-center/merge-recommendations', [\App\Http\Controllers\CommandCenterController::class, 'getMergeRecommendations'])->name('command-center.merge.recommendations');

        // ── Chat entre autoridades ────────────────────────────────────────────
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/authorities', [ChatController::class, 'authorities'])->name('chat.authorities');
        Route::get('/chat/history/{carnet}', [ChatController::class, 'history'])->name('chat.history');
        Route::post('/chat/message', [ChatController::class, 'store'])->name('chat.store');

        // ── Donaciones (escritura) ────────────────────────────────────────────
        Route::post('/donaciones', [DonacionController::class, 'store'])->name('donaciones.store');
        Route::get('/donaciones/{id}/edit', [DonacionController::class, 'edit'])->name('donaciones.edit')->where('id', '[0-9]+');
        Route::patch('/donaciones/{id}', [DonacionController::class, 'update'])->name('donaciones.update');
    });
});

// Endpoint de autenticación de canales privados Reverb (sesión personalizada)
Route::post('/chat/auth', [ChatController::class, 'broadcastAuth'])
    ->middleware([ApiAuthenticate::class, EnsureApiAuthority::class])
    ->name('chat.broadcast.auth');
