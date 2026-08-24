<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost:5173');
});

test('the session probe returns a null user for guests', function () {
    $this->getJson('/api/user')->assertOk()->assertJsonPath('user', null);
});

test('an admin can log in and retrieve their context', function () {
    $user = User::factory()->create(['email' => 'admin@example.com', 'password' => 'password', 'is_admin' => true]);

    $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);

    $response->assertOk()->assertJsonPath('user.email', $user->email)->assertJsonPath('user.isAdmin', true);
    $this->getJson('/api/user')->assertOk()->assertJsonPath('user.id', $user->id);
});

test('invalid credentials return unauthorized', function () {
    User::factory()->create(['email' => 'admin@example.com', 'password' => 'password']);

    $this->postJson('/api/login', ['email' => 'admin@example.com', 'password' => 'wrong-password'])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'The provided credentials are incorrect.');
});

test('a client can register with a linked profile and is logged in', function () {
    $response = $this->postJson('/api/client/register', [
        'name' => 'New Client',
        'email' => 'new-client@example.com',
        'phone' => '555-0199',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()->assertJsonPath('user.isAdmin', false)->assertJsonPath('user.client.name', 'New Client');
    $this->assertDatabaseHas('users', ['email' => 'new-client@example.com', 'is_admin' => false]);
    $this->assertDatabaseHas('clients', ['name' => 'New Client', 'phone' => '555-0199']);
    expect(Client::whereHas('user', fn ($query) => $query->where('email', 'new-client@example.com'))->count())->toBe(1);
});

test('client registration rejects duplicate email addresses', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/client/register', [
        'name' => 'New Client',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('authenticated users can log out', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
    $this->postJson('/api/logout')->assertOk();

    $this->assertGuest('web');
});
