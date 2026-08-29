<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->clientUser = Client::factory()->create()->user;
});

test('appointment policy grants admins every ability', function () {
    $appointment = Appointment::factory()->create();

    foreach (['viewAny', 'create'] as $ability) {
        expect(Gate::forUser($this->admin)->allows($ability, Appointment::class))->toBeTrue();
    }

    foreach (['view', 'update', 'delete', 'confirm', 'complete', 'cancel'] as $ability) {
        expect(Gate::forUser($this->admin)->allows($ability, $appointment))->toBeTrue();
    }
});

test('appointment policy lets clients view and cancel only their own appointments', function () {
    $own = Appointment::factory()->create(['client_id' => $this->clientUser->client->id]);
    $foreign = Appointment::factory()->status(AppointmentStatus::Confirmed)->create();

    expect(Gate::forUser($this->clientUser)->allows('view', $own))->toBeTrue()
        ->and(Gate::forUser($this->clientUser)->allows('cancel', $own))->toBeTrue()
        ->and(Gate::forUser($this->clientUser)->allows('view', $foreign))->toBeFalse()
        ->and(Gate::forUser($this->clientUser)->allows('cancel', $foreign))->toBeFalse();

    foreach (['update', 'delete', 'confirm', 'complete'] as $ability) {
        expect(Gate::forUser($this->clientUser)->allows($ability, $own))->toBeFalse();
    }
});

test('appointment policy denies booking without an active client profile', function () {
    $inactive = Client::factory()->create(['active' => false])->user;

    expect(Gate::forUser($this->admin)->allows('create', Appointment::class))->toBeTrue()
        ->and(Gate::forUser($this->clientUser)->allows('create', Appointment::class))->toBeTrue()
        ->and(Gate::forUser($inactive)->allows('create', Appointment::class))->toBeFalse();
});

test('client policy is admin only', function () {
    $client = Client::factory()->create();

    foreach (['viewAny', 'create'] as $ability) {
        expect(Gate::forUser($this->admin)->allows($ability, Client::class))->toBeTrue()
            ->and(Gate::forUser($this->clientUser)->allows($ability, Client::class))->toBeFalse();
    }

    foreach (['view', 'update', 'activate', 'deactivate'] as $ability) {
        expect(Gate::forUser($this->admin)->allows($ability, $client))->toBeTrue()
            ->and(Gate::forUser($this->clientUser)->allows($ability, $client))->toBeFalse();
    }
});

test('service policy lets clients view active services only', function () {
    $service = Service::factory()->create();
    $inactiveService = Service::factory()->inactive()->create();

    expect(Gate::forUser($this->admin)->allows('create', Service::class))->toBeTrue()
        ->and(Gate::forUser($this->clientUser)->allows('create', Service::class))->toBeFalse();

    expect(Gate::forUser($this->admin)->allows('view', $service))->toBeTrue()
        ->and(Gate::forUser($this->clientUser)->allows('view', $service))->toBeTrue()
        ->and(Gate::forUser($this->clientUser)->allows('view', $inactiveService))->toBeFalse();

    foreach (['update', 'deactivate'] as $ability) {
        expect(Gate::forUser($this->admin)->allows($ability, $service))->toBeTrue()
            ->and(Gate::forUser($this->clientUser)->allows($ability, $service))->toBeFalse();
    }
});

test('clients can cancel their own appointment and get a 404 for foreign appointments', function () {
    $own = Appointment::factory()->create(['client_id' => $this->clientUser->client->id]);
    $foreign = Appointment::factory()->status(AppointmentStatus::Confirmed)->create();

    $this->actingAs($this->clientUser)
        ->patchJson("/api/client/appointments/{$own->id}/cancel")
        ->assertOk();

    $this->actingAs($this->clientUser)
        ->patchJson("/api/client/appointments/{$foreign->id}/cancel")
        ->assertNotFound()
        ->assertJson(['message' => 'Appointment not found.']);
});

test('admin workflow transitions stay authorized through policies', function () {
    $appointment = Appointment::factory()->create();

    $this->actingAs($this->admin)
        ->postJson("/api/appointments/{$appointment->id}/confirm")
        ->assertOk();

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);
});
