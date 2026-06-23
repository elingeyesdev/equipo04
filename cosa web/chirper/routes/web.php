<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\VictimaController;
use App\Http\Controllers\InventarioController;
use App\Http\Middleware\ApiAuthenticate;
use App\Http\Middleware\EnsureApiAuthority;
use App\Http\Middleware\RedirectIfApiAuthenticated;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SugerenciaController;
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

// ── Rutas Públicas de Información y Sugerencias ───────────────────────
Route::view('/faq', 'faq.index')->name('faq.index');
Route::view('/contacto', 'contact.index')->name('contact.index');
Route::get('/sugerencias', [SugerenciaController::class, 'index'])->name('sugerencias.index');
Route::post('/sugerencias', [SugerenciaController::class, 'store'])->name('sugerencias.store');
Route::post('/sugerencias/{sugerencia}/like', [SugerenciaController::class, 'incrementLike'])->name('sugerencias.like');

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

    // ── Perfil de Usuario ────────────────────────────────────────────────
    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

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

    // ── Módulo de Inventario ──────────────────────────────────────────────
    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
    Route::get('/inventario/item/{inventario}', [InventarioController::class, 'showItem'])->name('inventario.item.show');
    Route::get('/inventario/{centro}', [InventarioController::class, 'show'])->name('inventario.show');
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

        // ── Gestión de Autoridades ────────────────────────────────────────────
        Route::get('/authorities/create', [\App\Http\Controllers\AuthorityController::class, 'create'])->name('authorities.create');
        Route::get('/authorities/search', [\App\Http\Controllers\AuthorityController::class, 'search'])->name('authorities.search');
        Route::post('/authorities', [\App\Http\Controllers\AuthorityController::class, 'store'])->name('authorities.store');

        // ── Chat entre autoridades ────────────────────────────────────────────
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/authorities', [ChatController::class, 'authorities'])->name('chat.authorities');
        Route::get('/chat/history/{carnet}', [ChatController::class, 'history'])->name('chat.history');
        Route::post('/chat/message', [ChatController::class, 'store'])->name('chat.store');

        // ── Inventario (escritura) ────────────────────────────────────────────
        Route::post('/inventario/{centro}', [InventarioController::class, 'store'])->name('inventario.store');
        Route::post('/inventario/{centro}/bulk-update', [InventarioController::class, 'bulkUpdateStatus'])->name('inventario.bulkUpdateStatus');
    });
});

// Endpoint de autenticación de canales privados Reverb (sesión personalizada)
Route::post('/chat/auth', [ChatController::class, 'broadcastAuth'])
    ->middleware([ApiAuthenticate::class, EnsureApiAuthority::class])
    ->name('chat.broadcast.auth');
