<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FloodsSeeder extends Seeder
{
    public function run(): void
    {
        $municipio = DB::table('municipios')->first();
        if (!$municipio) {
            return;
        }

        $id1 = DB::table('inundaciones')->insertGetId([
            'latitud' => -17.7432000,
            'longitud' => -63.1675000,
            'estado' => 'activa',
            'municipio_id' => $municipio->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id2 = DB::table('inundaciones')->insertGetId([
            'latitud' => -17.8349000,
            'longitud' => -63.1389000,
            'estado' => 'activa',
            'municipio_id' => $municipio->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reportes = [
            [
                'inundacion_id' => $id1,
                'citizen_carnet' => '10000001',
                'lat_reporte' => -17.7432000,
                'long_reporte' => -63.1675000,
                'address' => 'Av. Cristo Redentor, Calle 1',
                'description' => 'Inundación severa cerca de la rotonda.',
                'intensidad_propuesta' => 'alta',
                'peso' => 3,
                'estado_validacion' => 'aceptado',
                'validador_id' => '10000002',
                'validado_at' => now(),
                'foto_path' => 'inundacion 1.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inundacion_id' => $id1,
                'citizen_carnet' => '10000001',
                'lat_reporte' => -17.7442000,
                'long_reporte' => -63.1685000,
                'address' => 'Barrio Sirari',
                'description' => 'Agua entrando a las casas.',
                'intensidad_propuesta' => 'alta',
                'intensidad_validada' => 'media',
                'ajuste_comentario' => 'Charco superficial, no anegación de calle completa.',
                'peso' => 3,
                'estado_validacion' => 'aceptado',
                'validador_id' => '10000002',
                'validado_at' => now(),
                'foto_path' => 'inundacion 2.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inundacion_id' => $id2,
                'citizen_carnet' => '10000001',
                'lat_reporte' => -17.8349000,
                'long_reporte' => -63.1389000,
                'address' => 'Plan 3000',
                'description' => 'Avenidas principales anegadas.',
                'intensidad_propuesta' => 'media',
                'peso' => 3,
                'estado_validacion' => 'aceptado',
                'validador_id' => '10000002',
                'validado_at' => now(),
                'foto_path' => 'inundacion 3.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inundacion_id' => null,
                'citizen_carnet' => '10000001',
                'lat_gps' => -17.7500000,
                'long_gps' => -63.1700000,
                'lat_reporte' => -17.7501000,
                'long_reporte' => -63.1701000,
                'address' => 'Zona Equipetrol',
                'description' => 'Reporte pendiente de validación para pruebas.',
                'intensidad_propuesta' => 'media',
                'peso' => 1,
                'estado_validacion' => 'pendiente',
                'distancia_gps_metros' => 14.5,
                'datos_clima_json' => json_encode(['current' => ['precipitation' => 2.4]]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inundacion_id' => null,
                'citizen_carnet' => '10000001',
                'lat_gps' => -17.7600000,
                'long_gps' => -63.1800000,
                'lat_reporte' => -17.7600000,
                'long_reporte' => -63.1800000,
                'address' => 'Barrio Las Palmas',
                'description' => 'Reporte rechazado de ejemplo.',
                'intensidad_propuesta' => 'alta',
                'peso' => 1,
                'estado_validacion' => 'rechazado',
                'validador_id' => '10000002',
                'motivo_rechazo_codigo' => 'sin_evidencia',
                'motivo_rechazo_texto' => null,
                'rechazado_at' => now()->subDay(),
                'distancia_gps_metros' => 0,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDay(),
            ],
        ];

        foreach ($reportes as $reporte) {
            DB::table('reportes')->insert($reporte);
        }
    }
}
