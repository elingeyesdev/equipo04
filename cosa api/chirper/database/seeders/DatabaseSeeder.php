<?php

namespace Database\Seeders;

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
        // User::factory(10)->create();

        // Demo citizen user
        User::updateOrCreate(
            ['carnet' => '12345678'],
            [
                'name' => 'Test User',
                'phone' => '555-0000',
                'address' => 'Test Address',
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => User::ROLE_CITIZEN,
            ],
        );

        // Demo authority/admin user
        User::updateOrCreate(
            ['carnet' => '99999999'],
            [
                'name' => 'Authority User',
                'phone' => '555-9999',
                'address' => 'Authority Address',
                'email' => 'authority@example.com',
                'password' => 'password123',
                'role' => User::ROLE_AUTHORITY,
            ],
        );
    }
}
