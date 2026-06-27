<?php

namespace Database\Seeders;

use App\Models\MotivoRechazo;
use Illuminate\Database\Seeder;

class MotivoRechazoSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            [
                'codigo'          => 'sin_evidencia',
                'label_autoridad' => 'Sin evidencia de inundación',
                'label_ciudadano' => 'No se observó inundación en la zona reportada',
                'requiere_nota'   => false,
            ],
            [
                'codigo'          => 'ubicacion_incorrecta',
                'label_autoridad' => 'Ubicación incorrecta',
                'label_ciudadano' => 'La ubicación del reporte no coincide con el evento',
                'requiere_nota'   => false,
            ],
            [
                'codigo'          => 'reporte_propio_zona',
                'label_autoridad' => 'Ya contamos con un reporte suyo en esta zona',
                'label_ciudadano' => 'Ya tienes un reporte activo en esta zona',
                'requiere_nota'   => false,
            ],
            [
                'codigo'          => 'contenido_inapropiado',
                'label_autoridad' => 'Contenido inapropiado o spam',
                'label_ciudadano' => 'El reporte no cumple las normas de uso',
                'requiere_nota'   => true,
            ],
            [
                'codigo'          => 'clima_incompatible',
                'label_autoridad' => 'Clima incompatible con inundación',
                'label_ciudadano' => 'Condiciones meteorológicas no compatibles con inundación',
                'requiere_nota'   => false,
            ],
            [
                'codigo'          => 'otro',
                'label_autoridad' => 'Otro motivo',
                'label_ciudadano' => 'Tu reporte no pudo ser validado',
                'requiere_nota'   => true,
            ],
        ];

        foreach ($motivos as $motivo) {
            MotivoRechazo::query()->updateOrCreate(
                ['codigo' => $motivo['codigo']],
                $motivo + ['activo' => true]
            );
        }
    }
}
