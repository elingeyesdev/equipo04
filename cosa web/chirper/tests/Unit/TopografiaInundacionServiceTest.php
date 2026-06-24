<?php

declare(strict_types=1);

use App\Contracts\ElevationProvider;
use App\Services\PoligonoTopografiaCacheService;
use App\Services\TopografiaInundacionService;
use Illuminate\Support\Facades\Cache;

describe('PoligonoTopografiaCacheService', function () {
    it('construye un GeoJSON Polygon válido con anillo cerrado', function () {
        $service = new PoligonoTopografiaCacheService();

        $polygon = [
            [-17.78, -63.18],
            [-17.781, -63.18],
            [-17.781, -63.181],
        ];

        $geoJson = $service->construirGeoJson($polygon, -17.7805, -63.1805, 'media', false);

        expect($geoJson['type'])->toBe('Feature')
            ->and($geoJson['geometry']['type'])->toBe('Polygon')
            ->and($geoJson['properties']['fuente'])->toBe('topographic')
            ->and($geoJson['properties']['es_fallback'])->toBeFalse();

        $ring = $geoJson['geometry']['coordinates'][0];
        expect($ring[0])->toBe($ring[count($ring) - 1]);
        expect($ring[0][0])->toBe(-63.18);
        expect($ring[0][1])->toBe(-17.78);
    });

    it('marca fallback geométrico en propiedades del GeoJSON', function () {
        $service = new PoligonoTopografiaCacheService();

        $geoJson = $service->construirGeoJson(
            [[-17.78, -63.18], [-17.781, -63.18], [-17.781, -63.181]],
            -17.78,
            -63.18,
            'baja',
            true,
        );

        expect($geoJson['properties']['fuente'])->toBe('geometric_fallback')
            ->and($geoJson['properties']['es_fallback'])->toBeTrue();
    });

    it('guarda y recupera GeoJSON desde caché', function () {
        $service = new PoligonoTopografiaCacheService();
        $payload = ['type' => 'Feature', 'properties' => ['fuente' => 'topographic']];

        $service->guardarEnCache('reporte', 42, $payload);

        expect(Cache::get($service->cacheKey('reporte', 42)))->toBe($payload);
    });

    it('construye un GeoJSON MultiPolygon cuando hay varios anillos', function () {
        $service = new PoligonoTopografiaCacheService();

        $ring1 = [[-17.78, -63.18], [-17.781, -63.18], [-17.781, -63.181]];
        $ring2 = [[-17.79, -63.19], [-17.791, -63.19], [-17.791, -63.191]];

        $geoJson = $service->construirGeoJson([$ring1, $ring2], -17.785, -63.185, 'media', false);

        expect($geoJson['geometry']['type'])->toBe('MultiPolygon')
            ->and($geoJson['properties']['es_multipolygon'])->toBeTrue()
            ->and($geoJson['geometry']['coordinates'])->toHaveCount(2);
    });
});

describe('TopografiaInundacionService union', function () {
    it('une dos polígonos cercanos en un solo anillo', function () {
        $elevation = Mockery::mock(ElevationProvider::class);
        $service = new TopografiaInundacionService($elevation);

        $p1 = $service->buildCircularFallback(-17.78, -63.18, 40.0);
        $p2 = $service->buildCircularFallback(-17.7804, -63.1805, 40.0);

        $result = $service->unirPoligonosReportes([$p1, $p2], 100.0);

        expect($result['es_multipolygon'])->toBeFalse()
            ->and($result['rings'])->toHaveCount(1)
            ->and(count($result['polygon_coords']))->toBeGreaterThanOrEqual(3);
    });

    it('mantiene dos anillos cuando los polígonos están muy lejos', function () {
        $elevation = Mockery::mock(ElevationProvider::class);
        $service = new TopografiaInundacionService($elevation);

        $p1 = $service->buildCircularFallback(-17.78, -63.18, 30.0);
        $p2 = $service->buildCircularFallback(-17.79, -63.19, 30.0);

        $result = $service->unirPoligonosReportes([$p1, $p2], 100.0);

        expect($result['es_multipolygon'])->toBeTrue()
            ->and($result['rings'])->toHaveCount(2);
    });

    it('devuelve el polígono tal cual si solo hay uno', function () {
        $elevation = Mockery::mock(ElevationProvider::class);
        $service = new TopografiaInundacionService($elevation);

        $p1 = $service->buildCircularFallback(-17.78, -63.18, 30.0);
        $result = $service->unirPoligonosReportes([$p1]);

        expect($result['es_multipolygon'])->toBeFalse()
            ->and($result['polygon_coords'])->toBe($p1);
    });
});

describe('TopografiaInundacionService', function () {
    it('usa fallback geométrico cuando la API de elevación falla', function () {
        $elevation = Mockery::mock(ElevationProvider::class);
        $elevation->shouldReceive('fetchElevations')
            ->once()
            ->andThrow(new RuntimeException('API no disponible'));

        $service = new TopografiaInundacionService($elevation);
        $resultado = $service->calcularResultado(-17.7833, -63.1821, 'media');

        expect($resultado['es_fallback'])->toBeTrue()
            ->and($resultado['fuente'])->toBe('geometric_fallback')
            ->and($resultado['polygon_coords'])->toHaveCount(16);
    });

    it('calcula polígono topográfico con elevaciones simuladas', function () {
        $lat = -17.7833;
        $lng = -63.1821;

        $elevation = Mockery::mock(ElevationProvider::class);
        $elevation->shouldReceive('fetchElevations')
            ->once()
            ->andReturnUsing(function (array $points) use ($lat, $lng) {
                return array_map(function (string $raw) use ($lat, $lng) {
                    [$pLat, $pLng] = array_map('floatval', explode(',', $raw, 2));
                    $isCenter = abs($pLat - $lat) < 0.00001 && abs($pLng - $lng) < 0.00001;
                    $elevation = $isCenter ? 400.0 : 399.0;

                    return ['elevation' => $elevation, 'lat' => $pLat, 'lng' => $pLng];
                }, $points);
            });

        $service = new TopografiaInundacionService($elevation);
        $resultado = $service->calcularResultado($lat, $lng, 'baja');

        expect($resultado['es_fallback'])->toBeFalse()
            ->and($resultado['fuente'])->toBe('topographic')
            ->and(count($resultado['polygon_coords']))->toBeGreaterThanOrEqual(3);
    });

    it('buildCircularFallback genera un polígono cerrado de 16 vértices', function () {
        $elevation = Mockery::mock(ElevationProvider::class);
        $service = new TopografiaInundacionService($elevation);

        $polygon = $service->buildCircularFallback(-17.78, -63.18, 50.0);

        expect($polygon)->toHaveCount(16)
            ->and($polygon[0][0])->not->toBe($polygon[1][0]);
    });
});

afterEach(function () {
    Mockery::close();
});
