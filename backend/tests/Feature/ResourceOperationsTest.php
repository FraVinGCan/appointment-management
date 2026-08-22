<?php

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function staffUser(): User
{
    return User::factory()->create(['is_staff' => true]);
}

function appointmentPayload(Client $client, Service $service, array $overrides = []): array
{
    return array_merge([
        'clientId' => $client->id,
        'serviceId' => $service->id,
        'notes' => 'Appointment notes',
        'priority' => AppointmentPriority::Medium->value,
        'appointmentDate' => '2026-09-01',
        'startTime' => '09:00',
        'endTime' => '09:30',
    ], $overrides);
}

test('staff can create, view, update, search, paginate, and delete appointments', function () {
    $staff = staffUser();
    $client = Client::factory()->create(['name' => 'Maria Santos']);
    $service = Service::factory()->create(['name' => 'Initial Consultation']);

    $response = $this->actingAs($staff)->postJson('/api/appointments', appointmentPayload($client, $service));
    $response->assertCreated()->assertJsonPath('data.status', AppointmentStatus::Requested->value);
    $appointmentId = $response->json('data.id');

    $this->actingAs($staff)->getJson('/api/appointments/'.$appointmentId)
        ->assertOk()
        ->assertJsonPath('data.client.name', 'Maria Santos');

    $this->actingAs($staff)->putJson('/api/appointments/'.$appointmentId, appointmentPayload($client, $service, [
        'notes' => 'Updated notes',
        'priority' => AppointmentPriority::High->value,
    ]))->assertOk()->assertJsonPath('data.notes', 'Updated notes');

    $this->actingAs($staff)->getJson('/api/appointments?search=Maria&per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1);

    $this->actingAs($staff)->deleteJson('/api/appointments/'.$appointmentId)->assertNoContent();
    $this->assertDatabaseMissing('appointments', ['id' => $appointmentId]);
});

test('appointment validation rejects status, invalid times, and overlapping active bookings', function () {
    $staff = staffUser();
    $client = Client::factory()->create();
    $service = Service::factory()->create();
    $existing = Appointment::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'appointment_date' => '2026-09-01',
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $this->actingAs($staff)->postJson('/api/appointments', appointmentPayload($client, $service, [
        'status' => AppointmentStatus::Confirmed->value,
        'startTime' => '09:30',
        'endTime' => '10:30',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['status']);

    $this->actingAs($staff)->postJson('/api/appointments', appointmentPayload($client, $service, [
        'startTime' => '10:00',
        'endTime' => '09:00',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['endTime']);

    $this->actingAs($staff)->postJson('/api/appointments', appointmentPayload($client, $service, [
        'startTime' => '09:30',
        'endTime' => '10:30',
    ]))->assertConflict();

    $existing->update(['status' => AppointmentStatus::Cancelled]);
    $this->actingAs($staff)->postJson('/api/appointments', appointmentPayload($client, $service, [
        'startTime' => '09:30',
        'endTime' => '10:30',
    ]))->assertCreated();
});

test('public services only include active services and clients can book them', function () {
    $activeService = Service::factory()->create(['active' => true]);
    $inactiveService = Service::factory()->inactive()->create();
    $client = Client::factory()->create();

    $this->getJson('/api/services')
        ->assertOk()
        ->assertJsonFragment(['id' => $activeService->id])
        ->assertJsonMissing(['id' => $inactiveService->id]);

    $this->actingAs(staffUser())->getJson('/api/management/services')
        ->assertOk()
        ->assertJsonFragment(['id' => $inactiveService->id]);

    $this->actingAs($client->user)->postJson('/api/booking-requests', [
        'serviceId' => $activeService->id,
        'priority' => AppointmentPriority::Low->value,
        'appointmentDate' => '2026-09-02',
        'startTime' => '11:00',
        'endTime' => '11:30',
    ])->assertCreated()->assertJsonPath('data.clientId', $client->id);

    $this->actingAs($client->user)->postJson('/api/booking-requests', [
        'serviceId' => $inactiveService->id,
        'priority' => AppointmentPriority::Low->value,
        'appointmentDate' => '2026-09-02',
        'startTime' => '12:00',
        'endTime' => '12:30',
    ])->assertUnprocessable()->assertJsonValidationErrors(['serviceId']);
});

test('clients can only view and cancel their own eligible appointments', function () {
    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();
    $service = Service::factory()->create();
    $appointment = Appointment::factory()->create(['client_id' => $firstClient->id, 'service_id' => $service->id]);
    $otherAppointment = Appointment::factory()->create(['client_id' => $secondClient->id, 'service_id' => $service->id, 'start_time' => '10:00', 'end_time' => '10:30']);

    $this->actingAs($firstClient->user)->getJson('/api/client/appointments')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $appointment->id);

    $this->actingAs($firstClient->user)->patchJson('/api/client/appointments/'.$otherAppointment->id.'/cancel')
        ->assertNotFound();

    $this->actingAs($firstClient->user)->patchJson('/api/client/appointments/'.$appointment->id.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', AppointmentStatus::Cancelled->value);
});

test('staff can manage and deactivate clients and services', function () {
    $staff = staffUser();
    $client = Client::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($staff)->putJson('/api/clients/'.$client->id, [
        'name' => 'Updated Client',
        'email' => $client->user->email,
        'phone' => '555-0100',
    ])->assertOk()->assertJsonPath('data.name', 'Updated Client');

    $this->actingAs($staff)->patchJson('/api/clients/'.$client->id.'/deactivate')
        ->assertOk()
        ->assertJsonPath('data.active', false);

    $this->actingAs($staff)->putJson('/api/services/'.$service->id, [
        'name' => 'Updated Service',
        'description' => 'Updated description',
        'durationMinutes' => 45,
        'active' => true,
    ])->assertOk()->assertJsonPath('data.durationMinutes', 45);

    $this->actingAs($staff)->patchJson('/api/services/'.$service->id.'/deactivate')
        ->assertOk()
        ->assertJsonPath('data.active', false);
});

test('protected phase 4 endpoints reject unauthenticated requests', function () {
    $this->getJson('/api/appointments')->assertUnauthorized();
    $this->postJson('/api/booking-requests')->assertUnauthorized();
    $this->getJson('/api/clients')->assertUnauthorized();
});
