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

        $id1 = DB::table('inventario')->insertGetId([
            'centro_id' => $centro->id_centro,
            'donor_carnet' => '10000001',
            'categoria' => 'comida',
            'descripcion' => '50 litros de agua embotellada, 20 latas de atún',
            'is_anonymous' => false,
            'status' => 'recibido',
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

        $id2 = DB::table('inventario')->insertGetId([
            'centro_id' => $centro->id_centro,
            'donor_carnet' => '10000002',
            'categoria' => 'ropa',
            'descripcion' => 'Ropa variada para adultos y niños (4 bolsas grandes)',
            'is_anonymous' => true,
            'status' => 'entregado',
            'usage_details' => 'Se clasificó y entregó a 3 familias desplazadas.',
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
    }
}
