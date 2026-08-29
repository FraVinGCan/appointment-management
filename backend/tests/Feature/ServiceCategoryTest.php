<?php

use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins can view unique service categories including inactive services', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Service::factory()->create(['category' => 'Category']);
    Service::factory()->inactive()->create(['category' => 'category']);
    Service::factory()->create(['category' => 'Other']);
    Service::factory()->create(['category' => null]);

    $this->actingAs($admin)
        ->getJson('/api/management/services/categories')
        ->assertOk()
        ->assertJsonPath('data', ['Category', 'Other']);
});

test('service categories are restricted to admins', function () {
    $client = Client::factory()->create();

    $this->getJson('/api/management/services/categories')->assertUnauthorized();
    $this->actingAs($client->user)->getJson('/api/management/services/categories')->assertForbidden();
});

test('service writes normalize categories and reuse existing case-insensitive categories', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Service::factory()->inactive()->create(['category' => 'Category']);

    $this->actingAs($admin)
        ->postJson('/api/services', [
            'name' => 'Existing Category Service',
            'category' => '  cAtEgOrY  ',
        ])
        ->assertCreated()
        ->assertJsonPath('data.category', 'Category');

    $this->actingAs($admin)
        ->postJson('/api/services', [
            'name' => 'New Category Service',
            'category' => '  New   Category  ',
        ])
        ->assertCreated()
        ->assertJsonPath('data.category', 'New Category');
});
