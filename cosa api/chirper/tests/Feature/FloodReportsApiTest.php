<?php

use App\Models\FloodReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('citizen can create and view own flood report', function () {
    $citizen = User::factory()->create([
        'role' => User::ROLE_CITIZEN,
        'password' => Hash::make('password123'),
    ]);

    Sanctum::actingAs($citizen);

    $create = $this->postJson('/api/reports', [
        'latitude' => -16.5000000,
        'longitude' => -68.1500000,
        'address' => 'Zona Centro',
        'description' => 'Hay una inundación fuerte en la calle.',
        'severity' => 'high',
    ]);

    $create->assertCreated();

    $reportId = $create->json('data.id');

    $show = $this->getJson('/api/reports/'.$reportId);
    $show->assertOk();
});

test('citizen cannot create authority responses', function () {
    $citizen = User::factory()->create(['role' => User::ROLE_CITIZEN]);
    Sanctum::actingAs($citizen);

    $report = FloodReport::create([
        'citizen_carnet' => $citizen->carnet,
        'latitude' => 0,
        'longitude' => 0,
        'address' => null,
        'description' => 'Test',
        'severity' => 'low',
        'status' => 'open',
    ]);

    $response = $this->postJson('/api/reports/'.$report->id.'/responses', [
        'message' => 'Respuesta no permitida',
    ]);

    $response->assertForbidden();
});

test('authority can respond and citizen can see the response', function () {
    $citizen = User::factory()->create(['role' => User::ROLE_CITIZEN]);

    $report = FloodReport::create([
        'citizen_carnet' => $citizen->carnet,
        'latitude' => 1,
        'longitude' => 1,
        'address' => 'Lugar X',
        'description' => 'Test',
        'severity' => 'medium',
        'status' => 'open',
    ]);

    $authority = User::factory()->create([
        'role' => User::ROLE_AUTHORITY,
        'email' => 'authority@example.com',
    ]);

    Sanctum::actingAs($authority);

    $createResponse = $this->postJson('/api/reports/'.$report->id.'/responses', [
        'message' => 'Estamos en camino.',
    ]);

    $createResponse->assertCreated();

    Sanctum::actingAs($citizen);

    $show = $this->getJson('/api/reports/'.$report->id);
    $show
        ->assertOk()
        ->assertJsonPath('data.responses.0.message', 'Estamos en camino.');
});

