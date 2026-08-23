<?php

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentWorkflowException;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use App\Services\AppointmentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function workflowAppointment(AppointmentStatus $status): Appointment
{
    return Appointment::factory()->create([
        'status' => $status,
        'appointment_date' => '2026-10-01',
        'start_time' => '09:00',
        'end_time' => '09:30',
    ]);
}

test('admins can perform every valid appointment transition', function (string $action, AppointmentStatus $initialStatus, AppointmentStatus $expectedStatus) {
    $appointment = workflowAppointment($initialStatus);
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->postJson('/api/appointments/'.$appointment->id.'/'.$action)
        ->assertOk()
        ->assertJsonPath('data.status', $expectedStatus->value);

    expect($appointment->fresh()->status)->toBe($expectedStatus);
})->with([
    'confirm request' => ['confirm', AppointmentStatus::Requested, AppointmentStatus::Confirmed],
    'cancel request' => ['cancel', AppointmentStatus::Requested, AppointmentStatus::Cancelled],
    'complete confirmation' => ['complete', AppointmentStatus::Confirmed, AppointmentStatus::Completed],
    'cancel confirmation' => ['cancel', AppointmentStatus::Confirmed, AppointmentStatus::Cancelled],
]);

test('invalid appointment transitions return conflict and preserve status', function (string $action, AppointmentStatus $status) {
    $appointment = workflowAppointment($status);
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->postJson('/api/appointments/'.$appointment->id.'/'.$action)
        ->assertConflict()
        ->assertJsonPath('message', sprintf(
            'Appointment cannot be %s because its current status is %s.',
            ['confirm' => 'confirmed', 'complete' => 'completed', 'cancel' => 'cancelled'][$action],
            $status->value,
        ));

    expect($appointment->fresh()->status)->toBe($status);
})->with([
    'complete requested' => ['complete', AppointmentStatus::Requested],
    'confirm confirmed' => ['confirm', AppointmentStatus::Confirmed],
    'confirm completed' => ['confirm', AppointmentStatus::Completed],
    'complete completed' => ['complete', AppointmentStatus::Completed],
    'cancel completed' => ['cancel', AppointmentStatus::Completed],
    'confirm cancelled' => ['confirm', AppointmentStatus::Cancelled],
    'complete cancelled' => ['complete', AppointmentStatus::Cancelled],
    'cancel cancelled' => ['cancel', AppointmentStatus::Cancelled],
]);

test('client cancellation uses the workflow and remains ownership scoped', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $appointment = Appointment::factory()->create(['client_id' => $client->id]);
    $otherAppointment = Appointment::factory()->create([
        'client_id' => $otherClient->id,
        'appointment_date' => '2026-11-01',
    ]);

    $this->actingAs($client->user)->patchJson('/api/client/appointments/'.$appointment->id.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', AppointmentStatus::Cancelled->value);

    $this->actingAs($client->user)->patchJson('/api/client/appointments/'.$otherAppointment->id.'/cancel')
        ->assertNotFound();

    $this->actingAs($client->user)->patchJson('/api/client/appointments/'.$appointment->id.'/cancel')
        ->assertConflict()
        ->assertJsonPath('message', 'Appointment cannot be cancelled because its current status is Cancelled.');
});

test('workflow endpoints are restricted to admins', function () {
    $appointment = workflowAppointment(AppointmentStatus::Requested);
    $client = Client::factory()->create();

    $this->postJson('/api/appointments/'.$appointment->id.'/confirm')->assertUnauthorized();
    $this->actingAs($client->user)->postJson('/api/appointments/'.$appointment->id.'/confirm')->assertForbidden();
});

test('workflow service rejects invalid transitions directly', function () {
    $appointment = workflowAppointment(AppointmentStatus::Completed);

    expect(fn () => app(AppointmentWorkflowService::class)->confirm($appointment))
        ->toThrow(AppointmentWorkflowException::class);
});
