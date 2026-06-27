<?php

declare(strict_types=1);

use App\Support\PolygonCoordsHelper;
use App\Support\PolygonSimplifier;

describe('PolygonSimplifier', function () {
    it('reduce un anillo de 10_000 puntos a menos de 150 vértices', function () {
        $ring = [];
        for ($i = 0; $i < 10_000; $i++) {
            $ring[] = [-17.818 + ($i % 100) * 0.00001, -63.186 + intdiv($i, 100) * 0.00001];
        }

        $simplified = PolygonSimplifier::simplificarCoords($ring);

        expect(PolygonCoordsHelper::tieneGeometriaValida($simplified))->toBeTrue()
            ->and(count(PolygonCoordsHelper::normalizarAnillos($simplified)[0]))
            ->toBeLessThanOrEqual(PolygonSimplifier::MAX_VERTICES);
    });

    it('simplifica cada anillo de un multipolígono por separado', function () {
        $ring1 = [];
        $ring2 = [];
        for ($i = 0; $i < 600; $i++) {
            $angle = ($i / 600) * 2 * M_PI;
            $ring1[] = [-17.78 + cos($angle) * 0.002, -63.18 + sin($angle) * 0.002];
            $ring2[] = [-17.79 + cos($angle) * 0.0015, -63.19 + sin($angle) * 0.0015];
        }

        $simplified = PolygonSimplifier::simplificarCoords([$ring1, $ring2]);

        expect(PolygonCoordsHelper::esMultipolygon($simplified))->toBeTrue();
        foreach (PolygonCoordsHelper::normalizarAnillos($simplified) as $ring) {
            expect(count($ring))->toBeLessThanOrEqual(PolygonSimplifier::MAX_VERTICES);
        }
    });

    it('no altera polígonos pequeños de fallback (16 vértices)', function () {
        $elevation = Mockery::mock(App\Contracts\ElevationProvider::class);
        $service = new App\Services\TopografiaInundacionService($elevation);
        $small = $service->buildCircularFallback(-17.78, -63.18, 50.0);

        $simplified = PolygonSimplifier::simplificarCoords($small);

        expect(count($simplified))->toBe(16);
    });
});

afterEach(function () {
    Mockery::close();
});
