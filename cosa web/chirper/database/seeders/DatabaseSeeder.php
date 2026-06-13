<?php

namespace Database\Seeders;

use App\Models\CentroAsistencia;
use App\Models\Donacion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProvinciasMunicipiosSeeder::class,
        ]);

        User::query()->updateOrCreate(['carnet' => '10000001'], [
            'carnet' => '10000001',
            'name' => 'Juan Pérez',
            'phone' => '70000001',
            'address' => 'Zona Centro',
            'email' => 'test@example.com',
            'role' => User::ROLE_CITIZEN,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        // Autoridades extra para probar chat 1-a-1
        User::query()->updateOrCreate(['carnet' => '10000002'], [
            'carnet' => '10000002',
            'name' => 'Roberto Suárez',
            'phone' => '70000002',
            'address' => 'Central de Emergencias',
            'email' => 'emergencias@autoridad.gob',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '10000003'], [
            'carnet' => '10000003',
            'name' => 'María Vargas',
            'phone' => '70000003',
            'address' => 'Depósito Municipal',
            'email' => 'logistica@autoridad.gob',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '10000004'], [
            'carnet' => '10000004',
            'name' => 'Carlos Mamani',
            'phone' => '70000004',
            'address' => 'Torre de Comunicaciones',
            'email' => 'comunicaciones@autoridad.gob',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '10000005'], [
            'carnet' => '10000005',
            'name' => 'Lucía Fernández',
            'phone' => '70000005',
            'address' => 'Sala de Situación',
            'email' => 'coordinacion@autoridad.gob',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '20000001'], [
            'carnet' => '20000001',
            'name' => 'Andrés Choque',
            'phone' => '71111111',
            'address' => 'Distrito Norte',
            'email' => 'autoridad.norte@example.com',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '20000002'], [
            'carnet' => '20000002',
            'name' => 'Sofía López',
            'phone' => '72222222',
            'address' => 'Distrito Sur',
            'email' => 'autoridad.sur@example.com',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '20000003'], [
            'carnet' => '20000003',
            'name' => 'Luis Rojas',
            'phone' => '73333333',
            'address' => 'Distrito Centro',
            'email' => 'autoridad.centro@example.com',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        CentroAsistencia::query()->updateOrCreate(
            ['nombre' => 'Centro de Acopio Cristo Redentor'],
            [
                'direccion' => 'Av. Cristo Redentor, entre 4to y 5to anillo',
                'latitud' => -17.7432000,
                'longitud' => -63.1675000,
                'hora_apertura' => '08:00',
                'hora_cierre' => '19:00',
                'contacto' => '76000001',
                'encargado' => 'Brigada Norte',
                'ultima_actualizacion' => now(),
            ]
        );

        CentroAsistencia::query()->updateOrCreate(
            ['nombre' => 'Centro Municipal Parque Urbano'],
            [
                'direccion' => 'Parque Urbano Central, Santa Cruz',
                'latitud' => -17.7759000,
                'longitud' => -63.1840000,
                'hora_apertura' => '07:30',
                'hora_cierre' => '20:00',
                'contacto' => '76000002',
                'encargado' => 'Equipo Centro',
                'ultima_actualizacion' => now(),
            ]
        );

        CentroAsistencia::query()->updateOrCreate(
            ['nombre' => 'Punto Solidario Plan 3000'],
            [
                'direccion' => 'Av. Paurito, zona Plan 3000',
                'latitud' => -17.8349000,
                'longitud' => -63.1389000,
                'hora_apertura' => '08:00',
                'hora_cierre' => '18:30',
                'contacto' => '76000003',
                'encargado' => 'Brigada Sur',
                'ultima_actualizacion' => now(),
            ]
        );

        // --- Donaciones Seeders ---
        $centro1 = CentroAsistencia::where('nombre', 'Centro de Acopio Cristo Redentor')->first()->id_centro;
        $centro2 = CentroAsistencia::where('nombre', 'Centro Municipal Parque Urbano')->first()->id_centro;

        Donacion::query()->firstOrCreate(
            ['items_description' => '50 litros de agua embotellada, 20 latas de atún'],
            [
                'centro_id' => $centro1,
                'donor_carnet' => '10000001',
                'is_anonymous' => false,
                'status' => 'recibido',
            ]
        );

        Donacion::query()->firstOrCreate(
            ['items_description' => 'Ropa variada para adultos y niños (4 bolsas grandes)'],
            [
                'centro_id' => $centro2,
                'donor_carnet' => '10000001',
                'is_anonymous' => true,
                'status' => 'en_uso',
                'usage_details' => 'Se clasificó y entregó a 3 familias desplazadas del barrio central.',
            ]
        );

        Donacion::query()->firstOrCreate(
            ['items_description' => '15 colchonetas y 30 frazadas térmicas'],
            [
                'centro_id' => $centro1,
                'donor_carnet' => '10000002',
                'is_anonymous' => false,
                'status' => 'entregado',
                'usage_details' => 'Entregado íntegramente al refugio temporal de la avenida principal.',
            ]
        );
    }
}
