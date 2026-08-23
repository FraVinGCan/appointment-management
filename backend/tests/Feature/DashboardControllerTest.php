<?php

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashboardAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

test('admins receive dashboard stats', function () {
    $admin = dashboardAdmin();
    $client = Client::factory()->create(['active' => true]);
    $service = Service::factory()->create(['active' => true]);

    Appointment::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'status' => AppointmentStatus::Requested,
        'priority' => AppointmentPriority::High,
        'appointment_date' => now()->subDays(2)->format('Y-m-d'),
        'start_time' => '09:00',
        'end_time' => '09:30',
    ]);

    Appointment::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'status' => AppointmentStatus::Confirmed,
        'priority' => AppointmentPriority::Medium,
        'appointment_date' => now()->subDay()->format('Y-m-d'),
        'start_time' => '10:00',
        'end_time' => '10:30',
    ]);

    $response = $this->actingAs($admin)->getJson('/api/dashboard/stats');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'totals' => ['appointments', 'activeClients', 'activeServices', 'pending', 'urgent'],
                'statusDistribution' => ['labels', 'series', 'colors'],
                'priorityDistribution' => ['labels', 'series', 'colors'],
                'appointmentsOverTime' => ['labels', 'series'],
                'topServices' => ['labels', 'series'],
                'upcoming' => [],
            ],
        ])
        ->assertJsonPath('data.totals.appointments', 2)
        ->assertJsonPath('data.totals.pending', 1)
        ->assertJsonPath('data.totals.urgent', 1)
        ->assertJsonPath('data.statusDistribution.labels', [
            AppointmentStatus::Requested->value,
            AppointmentStatus::Confirmed->value,
            AppointmentStatus::Completed->value,
            AppointmentStatus::Cancelled->value,
        ]);
});

test('clients cannot access dashboard stats', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)->getJson('/api/dashboard/stats')->assertForbidden();
});

test('unauthenticated users cannot access dashboard stats', function () {
    $this->getJson('/api/dashboard/stats')->assertUnauthorized();
});
