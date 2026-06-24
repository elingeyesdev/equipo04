<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CalcularPoligonoInundacion;
use App\Models\Inundacion;
use App\Models\Reporte;
use App\Support\PolygonCoordsHelper;
use Illuminate\Console\Command;

class RecalcularPoligonosInundacion extends Command
{
    protected $signature = 'topografia:recalcular-inundaciones
                            {--solo-activas : Solo inundaciones en estado activa}
                            {--id= : Recalcular una inundación específica por ID}';

    protected $description = 'Invalida y recalcula polígonos unificados de inundaciones (y de sus reportes si faltan)';

    public function handle(): int
    {
        $query = Inundacion::query()->with('reportes');

        if ($id = $this->option('id')) {
            $query->where('id', (int) $id);
        } elseif ($this->option('solo-activas')) {
            $query->where('estado', Inundacion::ESTADO_ACTIVA);
        }

        $inundaciones = $query->get();

        if ($inundaciones->isEmpty()) {
            $this->warn('No se encontraron inundaciones para recalcular.');

            return self::SUCCESS;
        }

        $reportesEncolados = 0;
        $inundacionesEncoladas = 0;

        foreach ($inundaciones as $inundacion) {
            if ($inundacion->polygon_editado_autoridad) {
                $this->line("  Inundación #{$inundacion->id}: omitida (polígono editado por autoridad)");

                continue;
            }

            $repsConPoligono = 0;
            $repsSinPoligono = 0;

            foreach ($inundacion->reportes as $reporte) {
                if (PolygonCoordsHelper::tieneGeometriaValida((array) ($reporte->polygon_coords ?? []))) {
                    $repsConPoligono++;

                    continue;
                }

                CalcularPoligonoInundacion::dispatch($reporte->id);
                $reportesEncolados++;
                $repsSinPoligono++;
                $this->line("  Reporte #{$reporte->id}: job encolado (sin polígono)");
            }

            $inundacion->update([
                'polygon_coords' => null,
                'polygon_geojson' => null,
                'polygon_calculado_at' => null,
                'polygon_es_fallback' => false,
            ]);

            if ($repsSinPoligono === 0) {
                CalcularPoligonoInundacion::dispatch($inundacion->id, 'inundacion');
                $inundacionesEncoladas++;
                $this->info("Inundación #{$inundacion->id}: unión encolada ({$repsConPoligono}/{$inundacion->reportes->count()} reportes con polígono)");
            } else {
                $this->info("Inundación #{$inundacion->id}: unión pendiente ({$repsSinPoligono} reporte(s) calculándose primero)");
            }
        }

        $this->newLine();
        $this->info("Listo: {$reportesEncolados} job(s) de reporte + {$inundacionesEncoladas} job(s) de inundación encolados.");
        $this->comment('El queue_worker procesará en ~15–60 s por entidad (API de elevación).');
        $this->comment('Monitorea: docker compose logs -f queue_worker');

        return self::SUCCESS;
    }
}
