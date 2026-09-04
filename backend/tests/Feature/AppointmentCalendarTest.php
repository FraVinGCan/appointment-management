<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated users cannot access the calendar endpoint', function () {
    $this->getJson('/api/appointments/calendar?start=2026-09-01&end=2026-09-30')
        ->assertUnauthorized();
});

test('clients cannot access the calendar endpoint', function () {
    $client = \App\Models\Client::factory()->create();

    $this->actingAs($client->user)
        ->getJson('/api/appointments/calendar?start=2026-09-01&end=2026-09-30')
        ->assertForbidden();
});

test('admins can fetch calendar events for a date range', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Appointment::factory()->create([
        'appointment_date' => '2026-09-10',
        'start_time' => '09:00',
        'end_time' => '09:30',
        'status' => AppointmentStatus::Confirmed,
    ]);

    Appointment::factory()->create([
        'appointment_date' => '2026-09-15',
        'start_time' => '14:00',
        'end_time' => '15:00',
        'status' => AppointmentStatus::Requested,
    ]);

    Appointment::factory()->create([
        'appointment_date' => '2026-10-01',
        'start_time' => '10:00',
        'end_time' => '10:30',
        'status' => AppointmentStatus::Completed,
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/appointments/calendar?start=2026-09-01&end=2026-09-30');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.start', '2026-09-10T09:00')
        ->assertJsonPath('data.1.start', '2026-09-15T14:00');
});

test('calendar endpoint returns correct event structure', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $appointment = Appointment::factory()->create([
        'appointment_date' => '2026-09-10',
        'start_time' => '09:00',
        'end_time' => '09:30',
        'status' => AppointmentStatus::Confirmed,
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/appointments/calendar?start=2026-09-01&end=2026-09-30');

    $response->assertOk()
        ->assertJsonPath('data.0.id', $appointment->id)
        ->assertJsonPath('data.0.title', fn (string $title) => str_contains($title, '—'))
        ->assertJsonPath('data.0.start', '2026-09-10T09:00')
        ->assertJsonPath('data.0.end', '2026-09-10T09:30')
        ->assertJsonPath('data.0.color', '#3b82f6')
        ->assertJsonPath('data.0.extendedProps.status', 'Confirmed');
});

test('calendar endpoint validates required date parameters', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->getJson('/api/appointments/calendar')
        ->assertUnprocessable();

    $this->actingAs($admin)
        ->getJson('/api/appointments/calendar?start=2026-09-01')
        ->assertUnprocessable();

    $this->actingAs($admin)
        ->getJson('/api/appointments/calendar?end=2026-09-30')
        ->assertUnprocessable();
});

test('calendar endpoint caps range at 90 days', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Appointment::factory()->create([
        'appointment_date' => '2026-12-01',
        'start_time' => '09:00',
        'end_time' => '09:30',
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/appointments/calendar?start=2026-09-01&end=2027-03-01');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('calendar endpoint returns empty data for no appointments in range', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Appointment::factory()->create([
        'appointment_date' => '2026-09-10',
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/appointments/calendar?start=2026-10-01&end=2026-10-31');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('calendar endpoint maps status colors correctly', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $cases = [
        [AppointmentStatus::Requested, '#f59e0b'],
        [AppointmentStatus::Confirmed, '#3b82f6'],
        [AppointmentStatus::Completed, '#22c55e'],
        [AppointmentStatus::Cancelled, '#6b7280'],
    ];

    foreach ($cases as [$status, $expectedColor]) {
        $appointment = Appointment::factory()->create([
            'status' => $status,
            'appointment_date' => '2026-09-10',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/appointments/calendar?start=2026-09-01&end=2026-09-30');

        $response->assertOk()
            ->assertJsonPath("data.0.color", $expectedColor);

        $appointment->delete();
    }
});
