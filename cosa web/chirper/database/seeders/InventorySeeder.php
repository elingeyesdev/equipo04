<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventario;
use App\Models\TrazabilidadInventario;
use App\Models\CentroAsistencia;
use App\Models\User;
use App\Models\Inundacion;
use App\Models\Victima;
use Carbon\Carbon;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $centros = CentroAsistencia::pluck('id_centro')->toArray();
        if (empty($centros)) return;
        
        $inundaciones = Inundacion::pluck('id')->toArray();
        $victimas = Victima::pluck('id')->toArray();
        
        $ciudadanos = User::where('role', User::ROLE_CITIZEN)->pluck('carnet')->toArray();
        $autoridades = User::where('role', User::ROLE_AUTHORITY)->pluck('carnet')->toArray();
        
        if (empty($ciudadanos) || empty($autoridades)) return;

        $categorias = ['comida', 'bebida', 'ropa', 'medicamentos', 'dinero', 'otros'];
        $unidades = [
            'comida' => ['kg', 'raciones', 'latas'],
            'bebida' => ['litros', 'botellas'],
            'ropa' => ['prendas', 'bolsas'],
            'medicamentos' => ['cajas', 'unidades'],
            'dinero' => ['bs', 'usd'],
            'otros' => ['unidades']
        ];
        
        $descripciones = [
            'comida' => ['Arroz y fideos', 'Latas de atún y sardina', 'Raciones secas', 'Aceite y azúcar'],
            'bebida' => ['Agua embotellada', 'Jugos en caja', 'Sachet de agua'],
            'ropa' => ['Ropa de invierno', 'Ropa para niños', 'Zapatos y botas'],
            'medicamentos' => ['Kits de primeros auxilios', 'Paracetamol e Ibuprofeno', 'Alcohol y gasas'],
            'dinero' => ['Aporte voluntario', 'Colecta barrial'],
            'otros' => ['Frazadas', 'Carpas', 'Colchones', 'Linternas y pilas']
        ];

        for ($i = 0; $i < 60; $i++) {
            $centroId = $centros[array_rand($centros)];
            $donorCarnet = $ciudadanos[array_rand($ciudadanos)];
            $registrador = $autoridades[array_rand($autoridades)];
            $cat = $categorias[array_rand($categorias)];
            $unidad = $unidades[$cat][array_rand($unidades[$cat])];
            $desc = $descripciones[$cat][array_rand($descripciones[$cat])];
            $cantidad = rand(10, 200);
            
            $fechaInicial = Carbon::now()->subDays(rand(5, 30))->subHours(rand(0, 24));
            
            $estadosPosibles = ['recibido_centro', 'almacenado', 'en_transito', 'entregado', 'desechado'];
            
            if (in_array($cat, ['ropa', 'dinero'])) {
                $estadosPosibles = ['recibido_centro', 'almacenado', 'en_transito', 'entregado'];
            }
            
            $statusFinal = $estadosPosibles[array_rand($estadosPosibles)];
            
            $inundacionAsignada = (in_array($statusFinal, ['en_transito', 'entregado']) && !empty($inundaciones)) ? $inundaciones[array_rand($inundaciones)] : null;
            $victimaAsignada = ($statusFinal === 'entregado' && !empty($victimas)) ? $victimas[array_rand($victimas)] : null;

            $inventario = Inventario::create([
                'centro_id' => $centroId,
                'donor_carnet' => $donorCarnet,
                'registrado_por' => $registrador,
                'categoria' => $cat,
                'cantidad' => $cantidad,
                'unidad_medida' => $unidad,
                'descripcion' => $desc,
                'is_anonymous' => rand(0, 1) == 1,
                'status' => $statusFinal,
                'inundacion_id' => $inundacionAsignada,
                'victima_id' => $victimaAsignada,
                'created_at' => $fechaInicial,
                'updated_at' => $fechaInicial,
            ]);

            $trazabilidad = [];
            $fechaActual = clone $fechaInicial;

            $trazabilidad[] = [
                'inventario_id' => $inventario->id,
                'estado_anterior' => null,
                'estado_nuevo' => 'recibido_centro',
                'observacion' => 'Donación registrada inicialmente en el centro.',
                'fecha_actualizacion' => $fechaActual,
                'registrado_por' => $registrador,
                'photo_path' => null,
            ];

            if ($statusFinal !== 'recibido_centro') {
                $fechaActual = (clone $fechaActual)->addHours(rand(1, 24));
                
                if ($statusFinal === 'desechado') {
                    $trazabilidad[] = [
                        'inventario_id' => $inventario->id,
                        'estado_anterior' => 'recibido_centro',
                        'estado_nuevo' => 'desechado',
                        'observacion' => 'Producto expirado o en mal estado.',
                        'fecha_actualizacion' => $fechaActual,
                        'registrado_por' => $autoridades[array_rand($autoridades)],
                        'photo_path' => null,
                    ];
                } else {
                    $trazabilidad[] = [
                        'inventario_id' => $inventario->id,
                        'estado_anterior' => 'recibido_centro',
                        'estado_nuevo' => 'almacenado',
                        'observacion' => 'Donación clasificada y guardada en almacén.',
                        'fecha_actualizacion' => $fechaActual,
                        'registrado_por' => $autoridades[array_rand($autoridades)],
                        'photo_path' => null,
                    ];

                    if ($statusFinal === 'en_transito' || $statusFinal === 'entregado') {
                        $fechaActual = (clone $fechaActual)->addHours(rand(2, 48));
                        $trazabilidad[] = [
                            'inventario_id' => $inventario->id,
                            'estado_anterior' => 'almacenado',
                            'estado_nuevo' => 'en_transito',
                            'observacion' => 'Cargado al vehículo para su distribución.',
                            'fecha_actualizacion' => $fechaActual,
                            'registrado_por' => $autoridades[array_rand($autoridades)],
                            'photo_path' => null,
                        ];

                        if ($statusFinal === 'entregado') {
                            $fechaActual = (clone $fechaActual)->addHours(rand(1, 6));
                            $trazabilidad[] = [
                                'inventario_id' => $inventario->id,
                                'estado_anterior' => 'en_transito',
                                'estado_nuevo' => 'entregado',
                                'observacion' => 'Entregado exitosamente a los beneficiarios.',
                                'fecha_actualizacion' => $fechaActual,
                                'registrado_por' => $autoridades[array_rand($autoridades)],
                                'photo_path' => null,
                            ];
                        }
                    }
                }
            }

            foreach ($trazabilidad as $t) {
                TrazabilidadInventario::create($t);
            }
            
            $inventario->update(['updated_at' => $fechaActual]);
        }
    }
}
