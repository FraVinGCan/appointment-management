<?php

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('domain relationships and enum casts are persisted', function () {
    $client = Client::factory()->create();
    $service = Service::factory()->create();
    $appointment = Appointment::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'status' => AppointmentStatus::Confirmed,
        'priority' => AppointmentPriority::High,
    ]);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->fresh()->priority)->toBe(AppointmentPriority::High)
        ->and($appointment->client->is($client))->toBeTrue()
        ->and($appointment->service->is($service))->toBeTrue()
        ->and($client->appointments->contains($appointment))->toBeTrue()
        ->and($service->appointments->contains($appointment))->toBeTrue();
});

test('seed data is ordered and includes inactive historical services', function () {
    $this->seed();

    expect(Client::count())->toBe(12)
        ->and(Service::count())->toBe(6)
        ->and(Appointment::count())->toBe(12)
        ->and(Appointment::query()->whereIn('status', AppointmentStatus::cases())->count())->toBe(12);

    $inactiveService = Service::where('active', false)->firstOrFail();

    expect($inactiveService->appointments)->not->toBeEmpty();
    $this->assertDatabaseHas('users', ['email' => 'admin@example.com', 'is_admin' => true]);
});

test('client profiles belong to exactly one user account', function () {
    $client = Client::factory()->create();

    expect($client->user->is_admin)->toBeFalse()
        ->and($client->user->client->is($client))->toBeTrue();
});
