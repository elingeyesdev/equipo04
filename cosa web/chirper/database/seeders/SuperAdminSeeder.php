<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password123');

        User::updateOrCreate(
            ['carnet' => '30000001'],
            [
                'name' => 'Santiago Mamani',
                'phone' => '70000001',
                'address' => 'Dirección Central',
                'email' => 'super1@example.com',
                'password' => $password,
                'role' => User::ROLE_SUPER_ADMIN,
                'is_banned' => false,
            ]
        );

        User::updateOrCreate(
            ['carnet' => '30000002'],
            [
                'name' => 'Fernanda Ortiz',
                'phone' => '70000002',
                'address' => 'Dirección Central',
                'email' => 'super2@example.com',
                'password' => $password,
                'role' => User::ROLE_SUPER_ADMIN,
                'is_banned' => false,
            ]
        );
    }
}
