<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inundacion;
use App\Support\PolygonCoordsHelper;

foreach (Inundacion::where('estado', 'activa')->with('reportes')->get() as $i) {
    $rings = PolygonCoordsHelper::normalizarAnillos((array) ($i->polygon_coords ?? []));
    $reps = $i->reportes->filter(fn ($r) => PolygonCoordsHelper::tieneGeometriaValida((array) ($r->polygon_coords ?? [])))->count();
    echo sprintf(
        "Inundacion #%d: %d anillo(s), %d reportes con poligono, fallback=%s\n",
        $i->id,
        count($rings),
        $reps,
        $i->polygon_es_fallback ? 'si' : 'no'
    );

    $reportes = $i->reportes->all();
    for ($a = 0; $a < count($reportes); $a++) {
        for ($b = $a + 1; $b < count($reportes); $b++) {
            $r1 = $reportes[$a];
            $r2 = $reportes[$b];
            $dist = haversine(
                (float) $r1->lat_reporte,
                (float) $r1->long_reporte,
                (float) $r2->lat_reporte,
                (float) $r2->long_reporte
            );
            echo sprintf("  dist reporte #%d ↔ #%d: %.0f m\n", $r1->id, $r2->id, $dist);
        }
    }
}

function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6_371_000.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}
