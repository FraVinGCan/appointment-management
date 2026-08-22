<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['auth:sanctum', 'staff'])->get('/api/staff-test', fn () => response()->json(['ok' => true]));
    Route::middleware(['auth:sanctum', 'client'])->get('/api/client-test', fn () => response()->json(['ok' => true]));
});

test('staff middleware allows staff and rejects clients', function () {
    $staff = User::factory()->create(['is_staff' => true]);
    $client = Client::factory()->create()->user;

    $this->actingAs($staff)->getJson('/api/staff-test')->assertOk();
    $this->actingAs($client)->getJson('/api/staff-test')->assertForbidden();
});

test('client middleware allows clients and rejects staff', function () {
    $client = Client::factory()->create()->user;
    $staff = User::factory()->create(['is_staff' => true]);

    $this->actingAs($client)->getJson('/api/client-test')->assertOk();
    $this->actingAs($staff)->getJson('/api/client-test')->assertForbidden();
});

test('boundary middleware rejects unauthenticated requests', function () {
    $this->getJson('/api/staff-test')->assertUnauthorized();
    $this->getJson('/api/client-test')->assertUnauthorized();
});
