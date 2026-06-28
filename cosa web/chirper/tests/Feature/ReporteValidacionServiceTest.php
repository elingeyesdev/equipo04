<?php

use App\Models\MotivoRechazo;
use App\Models\Reporte;
use App\Models\User;
use App\Services\ReporteValidacionService;

beforeEach(function () {
    if (config('database.default') !== 'sqlite') {
        throw new RuntimeException(
            'Los tests deben usar SQLite (:memory:). Nunca ejecutar RefreshDatabase contra PostgreSQL de desarrollo.'
        );
    }

    $this->seed(\Database\Seeders\MotivoRechazoSeeder::class);

    User::factory()->create([
        'carnet' => '90000001',
        'role'   => User::ROLE_CITIZEN,
    ]);

    User::factory()->create([
        'carnet' => '90000002',
        'role'   => User::ROLE_AUTHORITY,
    ]);
});

it('rechaza un reporte con motivo y registra historial', function () {
    $reporte = Reporte::create([
        'citizen_carnet'       => '90000001',
        'lat_reporte'          => -17.75,
        'long_reporte'         => -63.17,
        'intensidad_propuesta' => 'media',
        'peso'                 => 1,
        'estado_validacion'    => Reporte::VALIDACION_PENDIENTE,
        'distancia_gps_metros' => 25,
    ]);

    app(ReporteValidacionService::class)->rechazar(
        $reporte,
        '90000002',
        'sin_evidencia',
    );

    $reporte->refresh();

    expect($reporte->estado_validacion)->toBe(Reporte::VALIDACION_RECHAZADO)
        ->and($reporte->validador_id)->toBe('90000002')
        ->and($reporte->motivo_rechazo_codigo)->toBe('sin_evidencia')
        ->and($reporte->rechazado_at)->not->toBeNull()
        ->and($reporte->historialValidacion)->toHaveCount(1);
});

it('aprueba con ajuste de intensidad sin sobrescribir la propuesta', function () {
    $reporte = Reporte::create([
        'citizen_carnet'       => '90000001',
        'lat_reporte'          => -17.75,
        'long_reporte'         => -63.17,
        'intensidad_propuesta' => 'alta',
        'peso'                 => 3,
        'estado_validacion'    => Reporte::VALIDACION_PENDIENTE,
    ]);

    app(ReporteValidacionService::class)->crearInundacionDesdeReporte(
        $reporte,
        '90000002',
        'media',
        'Evidencia de charco menor, no calle anegada.',
    );

    $reporte->refresh();

    expect($reporte->intensidad_propuesta)->toBe('alta')
        ->and($reporte->intensidad_validada)->toBe('media')
        ->and($reporte->intensidadEfectiva())->toBe('media')
        ->and($reporte->fueAjustado())->toBeTrue();
});

it('exige nota cuando el motivo la requiere', function () {
    $reporte = Reporte::create([
        'citizen_carnet'       => '90000001',
        'lat_reporte'          => -17.75,
        'long_reporte'         => -63.17,
        'intensidad_propuesta' => 'media',
        'peso'                 => 1,
        'estado_validacion'    => Reporte::VALIDACION_PENDIENTE,
    ]);

    expect(fn () => app(ReporteValidacionService::class)->rechazar(
        $reporte,
        '90000002',
        'otro',
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('bloquea crear inundacion si el reporte cae dentro del contorno activo', function () {
    $ring = [
        [-17.779, -63.181],
        [-17.779, -63.179],
        [-17.781, -63.179],
        [-17.781, -63.181],
    ];

    $inundacion = \App\Models\Inundacion::create([
        'latitud'      => -17.7900,
        'longitud'     => -63.1900,
        'estado'       => \App\Models\Inundacion::ESTADO_ACTIVA,
        'validador_id' => '90000002',
    ]);

    Reporte::create([
        'inundacion_id'       => $inundacion->id,
        'citizen_carnet'      => '90000001',
        'lat_reporte'         => -17.7800,
        'long_reporte'        => -63.1800,
        'intensidad_propuesta'=> 'media',
        'peso'                => 2,
        'estado_validacion'   => Reporte::VALIDACION_ACEPTADO,
        'polygon_coords'      => $ring,
        'updated_at'          => now(),
    ]);

    $pendiente = Reporte::create([
        'citizen_carnet'       => '90000001',
        'lat_reporte'          => -17.7800,
        'long_reporte'         => -63.1800,
        'intensidad_propuesta' => 'baja',
        'peso'                 => 1,
        'estado_validacion'    => Reporte::VALIDACION_PENDIENTE,
    ]);

    expect(fn () => app(ReporteValidacionService::class)->crearInundacionDesdeReporte(
        $pendiente,
        '90000002',
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('permite vincular por contorno activo aunque supere 300 m del centroide', function () {
    $ring = [
        [-17.779, -63.181],
        [-17.779, -63.179],
        [-17.781, -63.179],
        [-17.781, -63.181],
    ];

    $inundacion = \App\Models\Inundacion::create([
        'latitud'      => -17.7900,
        'longitud'     => -63.1900,
        'estado'       => \App\Models\Inundacion::ESTADO_ACTIVA,
        'validador_id' => '90000002',
    ]);

    Reporte::create([
        'inundacion_id'       => $inundacion->id,
        'citizen_carnet'      => '90000001',
        'lat_reporte'         => -17.7800,
        'long_reporte'        => -63.1800,
        'intensidad_propuesta'=> 'media',
        'peso'                => 2,
        'estado_validacion'   => Reporte::VALIDACION_ACEPTADO,
        'polygon_coords'      => $ring,
        'updated_at'          => now(),
    ]);

    $pendiente = Reporte::create([
        'citizen_carnet'       => '90000001',
        'lat_reporte'          => -17.7800,
        'long_reporte'         => -63.1800,
        'intensidad_propuesta' => 'baja',
        'peso'                 => 1,
        'estado_validacion'    => Reporte::VALIDACION_PENDIENTE,
    ]);

    $result = app(ReporteValidacionService::class)->aceptarYVincular(
        $pendiente,
        $inundacion->id,
        '90000002',
    );

    $pendiente->refresh();

    expect($result->id)->toBe($inundacion->id)
        ->and($pendiente->estado_validacion)->toBe(Reporte::VALIDACION_ACEPTADO)
        ->and($pendiente->inundacion_id)->toBe($inundacion->id);
});
