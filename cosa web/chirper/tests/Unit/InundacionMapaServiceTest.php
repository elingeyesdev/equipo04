<?php

declare(strict_types=1);

use App\Models\Inundacion;
use App\Models\Reporte;
use App\Services\InundacionMapaService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-06-27 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('reporte caducado no cuenta como vivo', function () {
    $reporte = new Reporte([
        'estado_validacion' => Reporte::VALIDACION_ACEPTADO,
    ]);
    $reporte->updated_at = Carbon::now()->subHours(4);

    expect(app(InundacionMapaService::class)->reporteEstaVivo($reporte))->toBeFalse();
});

test('reporte dentro del TTL cuenta como vivo', function () {
    $reporte = new Reporte([
        'estado_validacion' => Reporte::VALIDACION_ACEPTADO,
    ]);
    $reporte->updated_at = Carbon::now()->subHours(2);

    expect(app(InundacionMapaService::class)->reporteEstaVivo($reporte))->toBeTrue();
});

test('polygonCoordsParaMapa es null si no hay reportes vivos', function () {
    $inundacion = new Inundacion([
        'polygon_coords' => [[-17.78, -63.18], [-17.781, -63.18], [-17.781, -63.181]],
        'polygon_editado_autoridad' => false,
    ]);
    $inundacion->setRelation('reportesActivosTTL', collect());

    expect(app(InundacionMapaService::class)->polygonCoordsParaMapa($inundacion))->toBeNull();
});

test('inundaciones vinculables excluyen las sin TTL y respetan 300 m', function () {
    $mapa = app(InundacionMapaService::class);

    $reporte = new Reporte([
        'lat_reporte' => -17.7800,
        'long_reporte' => -63.1800,
    ]);

    $cercaViva = new Inundacion([
        'latitud' => -17.7801,
        'longitud' => -63.1801,
        'estado' => Inundacion::ESTADO_ACTIVA,
    ]);
    $cercaViva->id = 1;
    $cercaViva->setRelation('reportesActivosTTL', collect([new Reporte(['peso' => 1])]));

    $lejosViva = new Inundacion([
        'latitud' => -17.7900,
        'longitud' => -63.1900,
        'estado' => Inundacion::ESTADO_ACTIVA,
    ]);
    $lejosViva->id = 2;
    $lejosViva->setRelation('reportesActivosTTL', collect([new Reporte(['peso' => 1])]));

    $cercaCaducada = new Inundacion([
        'latitud' => -17.7801,
        'longitud' => -63.1801,
        'estado' => Inundacion::ESTADO_ACTIVA,
    ]);
    $cercaCaducada->id = 3;
    $cercaCaducada->setRelation('reportesActivosTTL', collect());

    $cercanas = $mapa->inundacionesVinculablesParaReporte($reporte, [$cercaViva, $lejosViva, $cercaCaducada]);

    expect($cercanas)->toHaveCount(1)
        ->and($cercanas[0]['id'])->toBe(1)
        ->and($cercanas[0]['distancia_metros'])->toBeLessThan(300);
});

test('inundacion vinculable por contorno activo aunque supere 300 m del centroide', function () {
    $mapa = app(InundacionMapaService::class);

    $ring = [
        [-17.779, -63.181],
        [-17.779, -63.179],
        [-17.781, -63.179],
        [-17.781, -63.181],
    ];

    $reporte = new Reporte([
        'lat_reporte' => -17.7800,
        'long_reporte' => -63.1800,
    ]);

    $inundacion = new Inundacion([
        'latitud' => -17.7900,
        'longitud' => -63.1900,
        'estado' => Inundacion::ESTADO_ACTIVA,
    ]);
    $inundacion->id = 10;
    $inundacion->setRelation('reportesActivosTTL', collect([
        new Reporte(['polygon_coords' => $ring]),
    ]));

    $cercanas = $mapa->inundacionesVinculablesParaReporte($reporte, [$inundacion]);

    expect($cercanas)->toHaveCount(1)
        ->and($cercanas[0]['dentro_contorno'])->toBeTrue()
        ->and($cercanas[0]['distancia_metros'])->toBeGreaterThan(300);
});

test('punto fuera del contorno y lejos del centroide no es vinculable', function () {
    $mapa = app(InundacionMapaService::class);

    $ring = [
        [-17.779, -63.181],
        [-17.779, -63.179],
        [-17.781, -63.179],
        [-17.781, -63.181],
    ];

    $reporte = new Reporte([
        'lat_reporte' => -17.8000,
        'long_reporte' => -63.2000,
    ]);

    $inundacion = new Inundacion([
        'latitud' => -17.7900,
        'longitud' => -63.1900,
        'estado' => Inundacion::ESTADO_ACTIVA,
    ]);
    $inundacion->id = 11;
    $inundacion->setRelation('reportesActivosTTL', collect([
        new Reporte(['polygon_coords' => $ring]),
    ]));

    expect($mapa->inundacionesVinculablesParaReporte($reporte, [$inundacion]))->toBe([]);
});

test('enrichPendientes marca solo_vincular cuando cae dentro del contorno', function () {
    $mapa = app(InundacionMapaService::class);

    $ring = [
        [-17.779, -63.181],
        [-17.779, -63.179],
        [-17.781, -63.179],
        [-17.781, -63.181],
    ];

    $pendiente = new Reporte([
        'lat_reporte' => -17.7800,
        'long_reporte' => -63.1800,
    ]);

    $inundacion = new Inundacion([
        'latitud' => -17.7900,
        'longitud' => -63.1900,
        'estado' => Inundacion::ESTADO_ACTIVA,
    ]);
    $inundacion->id = 12;
    $inundacion->setRelation('reportesActivosTTL', collect([
        new Reporte(['polygon_coords' => $ring]),
    ]));

    $coleccion = collect([$pendiente]);
    $mapa->enrichPendientesConCercanas($coleccion, [$inundacion]);

    expect($pendiente->solo_vincular)->toBeTrue()
        ->and($pendiente->dentro_contorno_activo)->toBeTrue()
        ->and($pendiente->cercanas)->toHaveCount(1);
});

test('polygonCoordsParaMapa devuelve un solo poligono de reporte vivo', function () {
    $mapa = app(InundacionMapaService::class);

    $ring = [[-17.78, -63.18], [-17.781, -63.18], [-17.781, -63.181]];
    $inundacion = new Inundacion(['polygon_editado_autoridad' => false]);
    $inundacion->setRelation('reportesActivosTTL', collect([
        new Reporte(['polygon_coords' => $ring]),
    ]));

    expect($mapa->polygonCoordsParaMapa($inundacion))->toBe($ring);
});
