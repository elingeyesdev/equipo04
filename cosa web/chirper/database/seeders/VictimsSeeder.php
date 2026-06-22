<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VictimsSeeder extends Seeder
{
    public function run(): void
    {
        $inundacion = DB::table('inundaciones')->first();
        if (!$inundacion) {
            return;
        }

        DB::table('victimas')->insert([
            [
                'inundacion_id' => $inundacion->id,
                'carnet' => '8888888',
                'nombre_completo' => 'Juan Perez',
                'fecha_nacimiento' => '1990-05-15',
                'estado' => 'perdido',
                'descripcion' => 'Visto por última vez cerca del río.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inundacion_id' => $inundacion->id,
                'carnet' => '9999999',
                'nombre_completo' => 'Maria Gomez',
                'fecha_nacimiento' => '1985-08-20',
                'estado' => 'herido',
                'descripcion' => 'Rescatada con lesiones leves.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
