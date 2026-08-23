<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['auth:sanctum', 'admin'])->get('/api/admin-test', fn () => response()->json(['ok' => true]));
    Route::middleware(['auth:sanctum', 'client'])->get('/api/client-test', fn () => response()->json(['ok' => true]));
});

test('admin middleware allows admins and rejects clients', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $client = Client::factory()->create()->user;

    $this->actingAs($admin)->getJson('/api/admin-test')->assertOk();
    $this->actingAs($client)->getJson('/api/admin-test')->assertForbidden();
});

test('client middleware allows clients and rejects admins', function () {
    $client = Client::factory()->create()->user;
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($client)->getJson('/api/client-test')->assertOk();
    $this->actingAs($admin)->getJson('/api/client-test')->assertForbidden();
});

test('boundary middleware rejects unauthenticated requests', function () {
    $this->getJson('/api/admin-test')->assertUnauthorized();
    $this->getJson('/api/client-test')->assertUnauthorized();
});
