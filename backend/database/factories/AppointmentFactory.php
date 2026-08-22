<?php

namespace Database\Factories;

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'service_id' => Service::factory(),
            'notes' => fake()->optional()->sentence(),
            'status' => AppointmentStatus::Requested,
            'priority' => AppointmentPriority::Medium,
            'appointment_date' => fake()->dateTimeBetween('today', '+30 days')->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '09:30',
        ];
    }

    public function status(AppointmentStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }
}
