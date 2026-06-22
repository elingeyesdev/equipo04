<?php

namespace Database\Seeders;

use App\Models\CentroAsistencia;

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
            'name' => 'Test User',
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
            'name' => 'Jefe Emergencias',
            'phone' => '70000002',
            'address' => 'Central de Emergencias',
            'email' => 'emergencias@autoridad.gob',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '10000003'], [
            'carnet' => '10000003',
            'name' => 'Coord. Logística',
            'phone' => '70000003',
            'address' => 'Depósito Municipal',
            'email' => 'logistica@autoridad.gob',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '10000004'], [
            'carnet' => '10000004',
            'name' => 'Resp. Comunicaciones',
            'phone' => '70000004',
            'address' => 'Torre de Comunicaciones',
            'email' => 'comunicaciones@autoridad.gob',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '10000005'], [
            'carnet' => '10000005',
            'name' => 'Dir. Coordinación',
            'phone' => '70000005',
            'address' => 'Sala de Situación',
            'email' => 'coordinacion@autoridad.gob',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '20000001'], [
            'carnet' => '20000001',
            'name' => 'Autoridad Norte',
            'phone' => '71111111',
            'address' => 'Distrito Norte',
            'email' => 'autoridad.norte@example.com',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '20000002'], [
            'carnet' => '20000002',
            'name' => 'Autoridad Sur',
            'phone' => '72222222',
            'address' => 'Distrito Sur',
            'email' => 'autoridad.sur@example.com',
            'role' => User::ROLE_AUTHORITY,
            'password' => 'password123',
            'is_banned' => false,
        ]);

        User::query()->updateOrCreate(['carnet' => '20000003'], [
            'carnet' => '20000003',
            'name' => 'Autoridad Centro',
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

        $this->call([
            FloodsSeeder::class,
            VictimsSeeder::class,
            InventorySeeder::class,
        ]);
    }
}
