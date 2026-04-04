<?php

use App\Models\FloodReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('citizen sees only own reports, authority sees all', function () {
    $citizenA = User::factory()->create(['role' => User::ROLE_CITIZEN]);
    $citizenB = User::factory()->create(['role' => User::ROLE_CITIZEN]);

    FloodReport::create([
        'citizen_carnet' => $citizenA->carnet,
        'latitude' => 1,
        'longitude' => 1,
        'address' => null,
        'description' => 'A',
        'severity' => 'low',
        'status' => 'open',
    ]);

    FloodReport::create([
        'citizen_carnet' => $citizenB->carnet,
        'latitude' => 2,
        'longitude' => 2,
        'address' => null,
        'description' => 'B',
        'severity' => 'low',
        'status' => 'open',
    ]);

    Sanctum::actingAs($citizenA);
    $citizenList = $this->getJson('/api/reports');
    $citizenList
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $authority = User::factory()->create(['role' => User::ROLE_AUTHORITY]);
    Sanctum::actingAs($authority);
    $authorityList = $this->getJson('/api/reports');
    $authorityList
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
