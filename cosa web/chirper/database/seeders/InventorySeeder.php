<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $centro = DB::table('centros_asistencia')->first();
        if (!$centro) {
            return;
        }
        
        $inundacion = DB::table('inundaciones')->first();
        $inundacion_id = $inundacion ? $inundacion->id : null;

        $id1 = DB::table('inventario')->insertGetId([
            'centro_id' => $centro->id_centro,
            'donor_carnet' => '10000001',
            'categoria' => 'comida',
            'descripcion' => '50 litros de agua embotellada, 20 latas de atún',
            'is_anonymous' => false,
            'status' => 'en_inventario',
            'inundacion_id' => $inundacion_id,
            'photo_path' => 'donacion 1.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('trazabilidad_inventario')->insert([
            'inventario_id' => $id1,
            'estado_anterior' => null,
            'estado_nuevo' => 'recibido',
            'observacion' => 'Donación inicial registrada.',
            'fecha_actualizacion' => now(),
        ]);
        DB::table('trazabilidad_inventario')->insert([
            'inventario_id' => $id1,
            'estado_anterior' => 'recibido',
            'estado_nuevo' => 'en_inventario',
            'observacion' => 'Asignado a inundación y clasificado en inventario.',
            'fecha_actualizacion' => now(),
        ]);

        $id2 = DB::table('inventario')->insertGetId([
            'centro_id' => $centro->id_centro,
            'donor_carnet' => '10000002',
            'categoria' => 'ropa',
            'descripcion' => 'Ropa variada para adultos y niños (4 bolsas grandes)',
            'is_anonymous' => true,
            'status' => 'entregado',
            'usage_details' => 'Se clasificó y entregó a 3 familias desplazadas.',
            'inundacion_id' => $inundacion_id,
            'photo_path' => 'donacion 2.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('trazabilidad_inventario')->insert([
            'inventario_id' => $id2,
            'estado_anterior' => 'recibido',
            'estado_nuevo' => 'entregado',
            'observacion' => 'Se clasificó y entregó a 3 familias desplazadas.',
            'fecha_actualizacion' => now(),
        ]);
        
        $id3 = DB::table('inventario')->insertGetId([
            'centro_id' => $centro->id_centro,
            'donor_carnet' => '10000001',
            'categoria' => 'medicina',
            'descripcion' => 'Kits de primeros auxilios y medicinas básicas.',
            'is_anonymous' => false,
            'status' => 'en_inventario',
            'inundacion_id' => $inundacion_id,
            'photo_path' => 'donacion 3.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('trazabilidad_inventario')->insert([
            'inventario_id' => $id3,
            'estado_anterior' => null,
            'estado_nuevo' => 'recibido',
            'observacion' => 'Kits médicos recibidos.',
            'fecha_actualizacion' => now(),
        ]);
        DB::table('trazabilidad_inventario')->insert([
            'inventario_id' => $id3,
            'estado_anterior' => 'recibido',
            'estado_nuevo' => 'en_inventario',
            'observacion' => 'Guardados en sección médica.',
            'fecha_actualizacion' => now(),
        ]);
    }
}
