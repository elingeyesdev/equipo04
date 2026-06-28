<?php

namespace Database\Seeders;

use App\Models\CentroAsistencia;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            MotivoRechazoSeeder::class,
            ProvinciasMunicipiosSeeder::class,
            SuperAdminSeeder::class,
        ]);

        // Ciudadanos (1xxxxxxx)
        $ciudadanos = [
            ['10000001', 'Juan Pérez', '70000001', 'Zona Centro', 'juan@example.com'],
            ['10000002', 'María Gómez', '70000002', 'Zona Sur', 'maria@example.com'],
            ['10000003', 'Carlos Ruiz', '70000003', 'Zona Norte', 'carlos@example.com'],
            ['10000004', 'Ana Silva', '70000004', 'Plan 3000', 'ana@example.com'],
            ['10000005', 'Luis Morales', '70000005', 'Villa Primero de Mayo', 'luis@example.com'],
            ['10000006', 'Sofia Castro', '70000006', 'Equipetrol', 'sofia@example.com'],
            ['10000007', 'Pedro Soto', '70000007', 'Urubo', 'pedro@example.com'],
            ['10000008', 'Lucia Mendez', '70000008', 'Radial 26', 'lucia@example.com'],
            ['10000009', 'Jorge Vargas', '70000009', 'Avenida Busch', 'jorge@example.com'],
            ['10000010', 'Elena Rojas', '70000010', 'Barrio Lindo', 'elena@example.com'],
        ];

        foreach ($ciudadanos as $c) {
            User::query()->updateOrCreate(['carnet' => $c[0]], [
                'name' => $c[1],
                'phone' => $c[2],
                'address' => $c[3],
                'email' => $c[4],
                'role' => User::ROLE_CITIZEN,
                'password' => 'password123',
                'is_banned' => false,
            ]);
        }

        // Autoridades (2xxxxxxx)
        $autoridades = [
            ['20000001', 'Luisa Mamani', '71111111', 'Distrito Norte', 'autoridad.norte@example.com'],
            ['20000002', 'Diego Flores', '72222222', 'Distrito Sur', 'autoridad.sur@example.com'],
            ['20000003', 'Andrea Castro', '73333333', 'Distrito Centro', 'autoridad.centro@example.com'],
            ['20000004', 'Fernando Vargas', '74444444', 'Plan 3000', 'autoridad.plan3000@example.com'],
            ['20000005', 'Camila Ortiz', '75555555', 'Villa 1ro de Mayo', 'autoridad.villa@example.com'],
        ];

        foreach ($autoridades as $a) {
            User::query()->updateOrCreate(['carnet' => $a[0]], [
                'name' => $a[1],
                'phone' => $a[2],
                'address' => $a[3],
                'email' => $a[4],
                'role' => User::ROLE_AUTHORITY,
                'password' => 'password123',
                'is_banned' => false,
            ]);
        }

        // Centros de Asistencia
        $centros = [
            ['Centro de Acopio Cristo Redentor', 'Av. Cristo Redentor, entre 4to y 5to anillo', -17.7432000, -63.1675000, '08:00', '19:00', '76000001', 'Brigada Norte'],
            ['Centro Municipal Parque Urbano', 'Parque Urbano Central, Santa Cruz', -17.7759000, -63.1840000, '07:30', '20:00', '76000002', 'Equipo Centro'],
            ['Punto Solidario Plan 3000', 'Av. Paurito, zona Plan 3000', -17.8349000, -63.1389000, '08:00', '18:30', '76000003', 'Brigada Sur'],
            ['Base de Ayuda Villa 1ro de Mayo', 'Plaza Principal Villa 1ro de Mayo', -17.8033000, -63.1298000, '08:30', '19:30', '76000004', 'Brigada Este'],
            ['Centro Solidario Los Lotes', 'Av. Santos Dumont, Los Lotes', -17.8421000, -63.1777000, '07:00', '18:00', '76000005', 'Brigada Sur-Este'],
        ];

        foreach ($centros as $c) {
            CentroAsistencia::query()->updateOrCreate(
                ['nombre' => $c[0]],
                [
                    'direccion' => $c[1],
                    'latitud' => $c[2],
                    'longitud' => $c[3],
                    'hora_apertura' => $c[4],
                    'hora_cierre' => $c[5],
                    'contacto' => $c[6],
                    'encargado' => $c[7],
                    'ultima_actualizacion' => now(),
                ]
            );
        }

        $this->call([
            FloodsSeeder::class,
            VictimsSeeder::class,
            InventorySeeder::class,
        ]);
    }
}
