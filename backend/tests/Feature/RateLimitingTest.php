<?php

use App\Models\Client;
use App\Models\Service;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withHeader('Origin', 'http://localhost:5173');
    Cache::flush();
    config([
        'ratelimit.public.per_minute' => 100,
        'ratelimit.authenticated.per_minute' => 100,
        'ratelimit.login.ip_per_minute' => 100,
        'ratelimit.login.email_per_minute' => 100,
        'ratelimit.register.per_minute' => 100,
        'ratelimit.booking.per_minute' => 100,
    ]);
});

afterEach(function (): void {
    Cache::flush();
});

test('public API requests are limited per IP address', function (): void {
    config(['ratelimit.public.per_minute' => 2]);

    $this->getJson('/api/services')->assertOk();
    $this->getJson('/api/services')->assertOk();
    $this->getJson('/api/services')->assertTooManyRequests();
});

test('login attempts are limited per IP address', function (): void {
    config(['ratelimit.login.ip_per_minute' => 2]);

    $payload = ['email' => 'admin@example.com', 'password' => 'wrong-password'];

    $this->postJson('/api/login', $payload)->assertUnauthorized();
    $this->postJson('/api/login', $payload)->assertUnauthorized();

    $this->postJson('/api/login', $payload)
        ->assertTooManyRequests()
        ->assertHeader('Retry-After');
});

test('login attempts are limited per email address', function (): void {
    config(['ratelimit.login.email_per_minute' => 2]);

    $payload = ['email' => 'admin@example.com', 'password' => 'wrong-password'];

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->postJson('/api/login', $payload)
        ->assertUnauthorized();
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->postJson('/api/login', $payload)
        ->assertUnauthorized();

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])
        ->postJson('/api/login', $payload)
        ->assertTooManyRequests();
});

test('client registration is limited per IP address', function (): void {
    config(['ratelimit.register.per_minute' => 2]);

    foreach (range(1, 2) as $number) {
        $this->postJson('/api/client/register', [
            'name' => "Client {$number}",
            'email' => "client-{$number}@example.com",
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();
    }

    $this->postJson('/api/client/register', [
        'name' => 'Client 3',
        'email' => 'client-3@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertTooManyRequests();
});

test('authenticated API requests are limited per user', function (): void {
    config(['ratelimit.authenticated.per_minute' => 2]);

    $client = Client::factory()->create();

    $this->actingAs($client->user)->getJson('/api/client/dashboard')->assertOk();
    $this->actingAs($client->user)->getJson('/api/client/dashboard')->assertOk();
    $this->actingAs($client->user)->getJson('/api/client/dashboard')->assertTooManyRequests();
});

test('booking requests have an additional per-user limit', function (): void {
    config(['ratelimit.booking.per_minute' => 2]);

    $client = Client::factory()->create();
    $service = Service::factory()->create();

    foreach (range(1, 2) as $number) {
        $this->actingAs($client->user)->postJson('/api/booking-requests', [
            'serviceId' => $service->id,
            'appointmentDate' => "2026-09-0{$number}",
            'startTime' => '09:00',
            'endTime' => '09:30',
        ])->assertCreated();
    }

    $this->actingAs($client->user)->postJson('/api/booking-requests', [
        'serviceId' => $service->id,
        'appointmentDate' => '2026-09-03',
        'startTime' => '09:00',
        'endTime' => '09:30',
    ])->assertTooManyRequests();
});
