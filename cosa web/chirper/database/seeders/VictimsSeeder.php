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
                'foto_path' => 'hombre 1.jpg',
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
                'foto_path' => 'mujer 1.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inundacion_id' => $inundacion->id,
                'carnet' => '7777777',
                'nombre_completo' => 'Carlos Roca',
                'fecha_nacimiento' => '1975-12-10',
                'estado' => 'rescatado',
                'descripcion' => 'Atrapado en el techo de su casa, evacuado exitosamente.',
                'foto_path' => 'hombre 2.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inundacion_id' => $inundacion->id,
                'carnet' => '6666666',
                'nombre_completo' => 'Ana Salvatierra',
                'fecha_nacimiento' => '2000-03-22',
                'estado' => 'perdido',
                'descripcion' => 'Se separó de su familia durante la evacuación.',
                'foto_path' => 'mujer 2.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inundacion_id' => $inundacion->id,
                'carnet' => '5555555',
                'nombre_completo' => 'Pedro Jimenez',
                'fecha_nacimiento' => '1960-01-05',
                'estado' => 'fallecido',
                'descripcion' => 'No logró evacuar a tiempo, encontrado por rescatistas.',
                'foto_path' => 'hombre 3.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
