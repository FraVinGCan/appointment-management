<?php

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['is_admin' => true]);
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

test('admins can create, view, update, search, paginate, and delete appointments', function () {
    $admin = adminUser();
    $client = Client::factory()->create(['name' => 'Maria Santos']);
    $service = Service::factory()->create(['name' => 'Initial Consultation']);

    $response = $this->actingAs($admin)->postJson('/api/appointments', appointmentPayload($client, $service));
    $response->assertCreated()->assertJsonPath('data.status', AppointmentStatus::Requested->value);
    $appointmentId = $response->json('data.id');

    $this->actingAs($admin)->getJson('/api/appointments/'.$appointmentId)
        ->assertOk()
        ->assertJsonPath('data.client.name', 'Maria Santos');

    $this->actingAs($admin)->putJson('/api/appointments/'.$appointmentId, appointmentPayload($client, $service, [
        'notes' => 'Updated notes',
        'priority' => AppointmentPriority::High->value,
    ]))->assertOk()->assertJsonPath('data.notes', 'Updated notes');

    $this->actingAs($admin)->getJson('/api/appointments?search=Maria&per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1);

    $this->actingAs($admin)->deleteJson('/api/appointments/'.$appointmentId)->assertNoContent();
    $this->assertDatabaseMissing('appointments', ['id' => $appointmentId]);
});

test('appointment validation rejects status, invalid times, and overlapping confirmed bookings', function () {
    $admin = adminUser();
    $client = Client::factory()->create();
    $service = Service::factory()->create();
    $existing = Appointment::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'status' => AppointmentStatus::Confirmed,
        'appointment_date' => '2026-09-01',
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $this->actingAs($admin)->postJson('/api/appointments', appointmentPayload($client, $service, [
        'status' => AppointmentStatus::Confirmed->value,
        'startTime' => '09:30',
        'endTime' => '10:30',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['status']);

    $this->actingAs($admin)->postJson('/api/appointments', appointmentPayload($client, $service, [
        'startTime' => '10:00',
        'endTime' => '09:00',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['endTime']);

    $this->actingAs($admin)->postJson('/api/appointments', appointmentPayload($client, $service, [
        'startTime' => '09:30',
        'endTime' => '10:30',
    ]))->assertConflict();

    $existing->update(['status' => AppointmentStatus::Requested]);
    $this->actingAs($admin)->postJson('/api/appointments', appointmentPayload($client, $service, [
        'startTime' => '09:30',
        'endTime' => '10:30',
    ]))->assertCreated();

    $existing->update(['status' => AppointmentStatus::Cancelled]);
    $this->actingAs($admin)->postJson('/api/appointments', appointmentPayload($client, $service, [
        'startTime' => '11:00',
        'endTime' => '11:30',
    ]))->assertCreated();
});

test('appointment listing supports status and priority filters', function () {
    $admin = adminUser();
    $requested = Appointment::factory()->create([
        'status' => AppointmentStatus::Requested,
        'priority' => AppointmentPriority::High,
    ]);
    Appointment::factory()->create([
        'status' => AppointmentStatus::Confirmed,
        'priority' => AppointmentPriority::Low,
        'appointment_date' => '2026-09-03',
    ]);

    $this->actingAs($admin)->getJson('/api/appointments?status=Requested&priority=High')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $requested->id);

    $this->actingAs($admin)->getJson('/api/appointments?status=Unknown')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

test('appointment listing supports client and service filters', function () {
    $admin = adminUser();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $service = Service::factory()->create();
    $otherService = Service::factory()->create();
    $matching = Appointment::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
    ]);
    Appointment::factory()->create([
        'client_id' => $otherClient->id,
        'service_id' => $otherService->id,
    ]);

    $this->actingAs($admin)
        ->getJson("/api/appointments?client_id={$client->id}&service_id={$service->id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matching->id);
});

test('admin listings support client and service status filters', function () {
    $admin = adminUser();
    $activeClient = Client::factory()->create(['active' => true]);
    $inactiveClient = Client::factory()->create(['active' => false]);

    $this->actingAs($admin)
        ->getJson('/api/clients?active=0')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inactiveClient->id);

    Service::factory()->create(['category' => 'Consulting', 'active' => true]);
    $inactiveService = Service::factory()->inactive()->create(['category' => 'Consulting']);
    Service::factory()->create(['category' => 'Other', 'active' => true]);

    $this->actingAs($admin)
        ->getJson('/api/management/services?category=Consulting&active=0')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inactiveService->id);
});

test('service listing supports category filtering for clients while guests see no filter controls', function () {
    $client = Client::factory()->create();
    $matching = Service::factory()->create(['category' => 'Consulting']);
    Service::factory()->create(['category' => 'Other']);
    $inactive = Service::factory()->inactive()->create(['category' => 'Consulting']);

    $this->actingAs($client->user)
        ->getJson('/api/services?category=Consulting')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matching->id);

    $this->getJson('/api/services/categories?active=1')
        ->assertSuccessful()
        ->assertJsonPath('data', ['Consulting', 'Other']);

    expect($inactive->active)->toBeFalse();
});

test('public services only include active services and clients can book them', function () {
    $activeService = Service::factory()->create(['active' => true]);
    $inactiveService = Service::factory()->inactive()->create();
    $client = Client::factory()->create();

    $this->getJson('/api/services')
        ->assertOk()
        ->assertJsonFragment(['id' => $activeService->id])
        ->assertJsonMissing(['id' => $inactiveService->id]);

    $this->actingAs(adminUser())->getJson('/api/management/services')
        ->assertOk()
        ->assertJsonFragment(['id' => $inactiveService->id]);

    $this->actingAs($client->user)->postJson('/api/booking-requests', [
        'serviceId' => $activeService->id,
        'priority' => AppointmentPriority::High->value,
        'appointmentDate' => '2026-09-02',
        'startTime' => '11:00',
        'endTime' => '11:30',
    ])->assertUnprocessable()->assertJsonValidationErrors(['priority']);

    $this->actingAs($client->user)->postJson('/api/booking-requests', [
        'serviceId' => $activeService->id,
        'appointmentDate' => '2026-09-02',
        'startTime' => '11:00',
        'endTime' => '11:30',
    ])->assertCreated()->assertJsonPath('data.clientId', $client->id)
        ->assertJsonMissingPath('data.priority');
    $this->assertDatabaseHas('appointments', [
        'client_id' => $client->id,
        'service_id' => $activeService->id,
        'start_time' => '11:00',
        'priority' => AppointmentPriority::Medium->value,
    ]);

    $this->actingAs($client->user)->postJson('/api/booking-requests', [
        'serviceId' => $inactiveService->id,
        'appointmentDate' => '2026-09-02',
        'startTime' => '12:00',
        'endTime' => '12:30',
    ])->assertUnprocessable()->assertJsonValidationErrors(['serviceId']);
});

test('clients can view active service details but not inactive services', function () {
    $activeService = Service::factory()->create(['description' => 'Helpful details']);
    $inactiveService = Service::factory()->inactive()->create();
    $client = Client::factory()->create();

    $this->actingAs($client->user)->getJson('/api/services/'.$activeService->id)
        ->assertOk()
        ->assertJsonPath('data.name', $activeService->name)
        ->assertJsonPath('data.description', 'Helpful details');

    $this->actingAs($client->user)->getJson('/api/services/'.$inactiveService->id)
        ->assertForbidden();
});

test('guests can view active service details but not inactive services', function () {
    $activeService = Service::factory()->create(['description' => 'Helpful details']);
    $inactiveService = Service::factory()->inactive()->create();

    $this->getJson('/api/services/'.$activeService->id)
        ->assertOk()
        ->assertJsonPath('data.name', $activeService->name)
        ->assertJsonPath('data.description', 'Helpful details')
        ->assertJsonMissingPath('data.appointments');

    $this->getJson('/api/services/'.$inactiveService->id)
        ->assertNotFound();
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
        ->assertJsonPath('data.0.id', $appointment->id)
        ->assertJsonMissingPath('data.0.priority');

    $this->actingAs($firstClient->user)->patchJson('/api/client/appointments/'.$otherAppointment->id.'/cancel')
        ->assertNotFound();

    $this->actingAs($firstClient->user)->patchJson('/api/client/appointments/'.$appointment->id.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', AppointmentStatus::Cancelled->value);
});

test('admins can manage and deactivate clients and services', function () {
    $admin = adminUser();
    $client = Client::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($admin)->putJson('/api/clients/'.$client->id, [
        'name' => 'Updated Client',
        'email' => $client->user->email,
        'phone' => '555-0100',
        'active' => true,
    ])->assertOk()->assertJsonPath('data.name', 'Updated Client');

    $this->actingAs($admin)->patchJson('/api/clients/'.$client->id.'/deactivate')
        ->assertOk()
        ->assertJsonPath('data.active', false);

    $this->actingAs($admin)->patchJson('/api/clients/'.$client->id.'/activate')
        ->assertOk()
        ->assertJsonPath('data.active', true);

    $this->actingAs($admin)->putJson('/api/services/'.$service->id, [
        'name' => 'Updated Service',
        'shortDescription' => 'A concise service summary.',
        'category' => 'Advisory',
        'description' => 'Updated description',
        'active' => true,
    ])->assertOk()
        ->assertJsonPath('data.name', 'Updated Service')
        ->assertJsonPath('data.shortDescription', 'A concise service summary.')
        ->assertJsonPath('data.category', 'Advisory');

    $this->actingAs($admin)->patchJson('/api/services/'.$service->id.'/deactivate')
        ->assertOk()
        ->assertJsonPath('data.active', false);
});

test('protected endpoints reject unauthenticated requests', function () {
    $this->getJson('/api/appointments')->assertUnauthorized();
    $this->postJson('/api/booking-requests')->assertUnauthorized();
    $this->getJson('/api/clients')->assertUnauthorized();
});
