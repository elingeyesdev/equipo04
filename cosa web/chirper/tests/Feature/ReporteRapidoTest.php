<?php



use App\Models\Inundacion;

use App\Models\Reporte;

use App\Services\InundacionMapaService;



it('expone contexto público de inundaciones sin autenticación', function () {

    $inundacion = Inundacion::create([

        'latitud'  => -17.7833,

        'longitud' => -63.1821,

        'estado'   => Inundacion::ESTADO_ACTIVA,

    ]);



    Reporte::create([

        'inundacion_id'        => $inundacion->id,

        'lat_reporte'          => -17.7833,

        'long_reporte'         => -63.1821,

        'intensidad_propuesta' => 'media',

        'peso'                 => 3,

        'estado_validacion'    => Reporte::VALIDACION_ACEPTADO,

        'distancia_gps_metros' => 10,

    ]);



    $response = $this->getJson('/api/inundaciones/contexto?lat=-17.7833&lng=-63.1821');



    $response->assertOk()

        ->assertJsonStructure([

            'data' => [

                [

                    'id',

                    'latitud',

                    'longitud',

                    'intensidad_calculada',

                    'esta_confirmada',

                    'polygon_coords',

                    'polygon_es_multipolygon',

                    'mostrar_en_mapa',

                    'reportes_activos_count',

                    'reportes_activos',

                    'ultima_actividad_at',

                    'ultima_actividad_human',

                    'distancia_metros',

                    'dentro_contorno',

                ],

            ],

        ])

        ->assertJsonMissingPath('data.0.validador')

        ->assertJsonMissingPath('data.0.citizen_carnet')

        ->assertJsonMissingPath('data.0.quorum_total');



    expect($response->json('data.0.id'))->toBe($inundacion->id)

        ->and($response->json('data.0.distancia_metros'))->toBeLessThan(50)

        ->and($response->json('data.0.reportes_activos_count'))->toBe(1)

        ->and($response->json('data.0.reportes_activos'))->toHaveCount(1);

});



it('filtra inundaciones fuera del radio de contexto', function () {

    $cerca = Inundacion::create([

        'latitud'  => -17.7833,

        'longitud' => -63.1821,

        'estado'   => Inundacion::ESTADO_ACTIVA,

    ]);



    $lejos = Inundacion::create([

        'latitud'  => -17.9000,

        'longitud' => -63.3000,

        'estado'   => Inundacion::ESTADO_ACTIVA,

    ]);



    foreach ([$cerca, $lejos] as $inundacion) {

        Reporte::create([

            'inundacion_id'        => $inundacion->id,

            'lat_reporte'          => (float) $inundacion->latitud,

            'long_reporte'         => (float) $inundacion->longitud,

            'intensidad_propuesta' => 'baja',

            'peso'                 => 1,

            'estado_validacion'    => Reporte::VALIDACION_ACEPTADO,

        ]);

    }



    $response = $this->getJson('/api/inundaciones/contexto?lat=-17.7833&lng=-63.1821');



    $response->assertOk();



    $ids = collect($response->json('data'))->pluck('id')->all();



    expect($ids)->toContain($cerca->id)

        ->not->toContain($lejos->id);

});



it('crea reporte rápido anónimo y lo detecta como vinculable cerca de inundación', function () {

    $inundacion = Inundacion::create([

        'latitud'  => -17.7833,

        'longitud' => -63.1821,

        'estado'   => Inundacion::ESTADO_ACTIVA,

    ]);



    Reporte::create([

        'inundacion_id'        => $inundacion->id,

        'lat_reporte'          => -17.7833,

        'long_reporte'         => -63.1821,

        'intensidad_propuesta' => 'alta',

        'peso'                 => 3,

        'estado_validacion'    => Reporte::VALIDACION_ACEPTADO,

    ]);



    $uuid = '550e8400-e29b-41d4-a716-446655440000';



    $response = $this->postJson('/api/reportes', [

        'user_uuid'            => $uuid,

        'lat_gps'              => -17.78335,

        'long_gps'             => -63.18215,

        'lat_reporte'          => -17.78335,

        'long_reporte'         => -63.18215,

        'intensidad_propuesta' => 'alta',

    ]);



    $response->assertCreated();



    $reporte = Reporte::where('user_uuid', $uuid)->first();



    expect($reporte)->not->toBeNull()

        ->and($reporte->estado_validacion)->toBe(Reporte::VALIDACION_PENDIENTE)

        ->and($reporte->inundacion_id)->toBeNull();



    $activas = Inundacion::activas()->with('reportesActivosTTL')->get();

    $mapa    = app(InundacionMapaService::class);

    $cercanas = $mapa->inundacionesVinculablesParaReporte($reporte, $activas);



    expect($cercanas)->not->toBeEmpty()

        ->and($cercanas[0]['id'])->toBe($inundacion->id);

});



it('renderiza la página de reporte rápido', function () {

    $response = $this->get('/reporte-rapido');



    $response->assertOk()

        ->assertSee('Reporte Rápido', false)

        ->assertSee('rapidoMap', false)

        ->assertSee('flood-heat-sources.js', false)

        ->assertSee('reporte-rapido.js', false)

        ->assertDontSee('rapidoShowReportDots', false);

});


