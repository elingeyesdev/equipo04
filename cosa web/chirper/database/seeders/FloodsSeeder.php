<?php

namespace Database\Seeders;

use App\Models\Inundacion;
use App\Models\Provincia;
use App\Models\Municipio;
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

        DB::table('inundaciones')->insert([
            [
                'latitud' => -17.7432000,
                'longitud' => -63.1675000,
                'estado' => 'activa',
                'municipio_id' => $municipio->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'latitud' => -17.8349000,
                'longitud' => -63.1389000,
                'estado' => 'activa',
                'municipio_id' => $municipio->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
