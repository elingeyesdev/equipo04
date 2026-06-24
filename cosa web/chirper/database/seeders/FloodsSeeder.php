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

        DB::table('reportes')->insert([
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
                'peso' => 3,
                'estado_validacion' => 'aceptado',
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
                'foto_path' => 'inundacion 3.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
