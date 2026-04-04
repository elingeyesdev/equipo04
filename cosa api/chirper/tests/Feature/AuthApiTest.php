<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('citizen can register with carnet and optional email', function () {
    $payload = [
        'carnet' => '0000123400',
        'name' => 'Juan Perez',
        'phone' => '+59170000000',
        'address' => 'Av. Principal 123',
        'email' => 'juan@example.com',
        'password' => 'password123',
    ];

    $response = $this->postJson('/api/auth/register', $payload);

    $response
        ->assertCreated()
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('token')
                ->has('user', fn (AssertableJson $json) =>
                    $json->where('carnet', '0000123400')
                        ->where('role', User::ROLE_CITIZEN)
                        ->etc()
                )
        );

    $user = User::where('carnet', '0000123400')->first();
    expect($user)->not->toBeNull();
    expect($user->email)->toBe('juan@example.com');
});

test('user can login with carnet', function () {
    $user = User::factory()->create([
        'carnet' => '0000000001',
        'email' => 'a@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'login' => '0000000001',
        'password' => 'password123',
    ]);

    $response
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('token')
                ->has('user', fn (AssertableJson $json) =>
                    $json->where('carnet', $user->carnet)->etc()
                )
        );
});

test('user can login with email', function () {
    $user = User::factory()->create([
        'carnet' => '0000000002',
        'email' => 'b@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'login' => 'b@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('token')
                ->has('user', fn (AssertableJson $json) =>
                    $json->where('carnet', $user->carnet)->etc()
                )
        );
});
