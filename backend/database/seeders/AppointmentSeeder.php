<?php

namespace Database\Seeders;

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appointments = [
            [1, 1, 'Initial discussion about the requested service.', AppointmentStatus::Requested, AppointmentPriority::High, '2026-08-18', '09:00', '09:30'],
            [2, 2, 'Follow-up after the previous appointment.', AppointmentStatus::Confirmed, AppointmentPriority::Medium, '2026-08-18', '10:00', '10:30'],
            [3, 3, "Routine review of the client's request.", AppointmentStatus::Completed, AppointmentPriority::Low, '2026-08-12', '08:30', '09:00'],
            [4, 1, 'Scheduled service requiring a dedicated time slot.', AppointmentStatus::Confirmed, AppointmentPriority::High, '2026-08-19', '11:00', '12:00'],
            [5, 2, "Consultation regarding the client's requirements.", AppointmentStatus::Cancelled, AppointmentPriority::Medium, '2026-08-14', '13:00', '13:30'],
            [6, 3, 'Review progress following the previous appointment.', AppointmentStatus::Requested, AppointmentPriority::High, '2026-08-20', '09:30', '10:00'],
            [7, 1, 'Routine follow-up and requirements review.', AppointmentStatus::Confirmed, AppointmentPriority::Medium, '2026-08-20', '14:00', '14:30'],
            [8, 2, 'Consultation about the requested service.', AppointmentStatus::Requested, AppointmentPriority::Low, '2026-08-21', '08:00', '08:30'],
            [9, 3, 'Scheduled service with standard preparation.', AppointmentStatus::Completed, AppointmentPriority::High, '2026-08-10', '15:00', '16:00'],
            [10, 1, 'Follow-up to review the previous appointment outcome.', AppointmentStatus::Confirmed, AppointmentPriority::Medium, '2026-08-22', '10:30', '11:00'],
            [11, 2, 'Routine review and general consultation.', AppointmentStatus::Requested, AppointmentPriority::High, '2026-08-22', '13:30', '14:00'],
            [12, 3, "Initial consultation about the client's requirements.", AppointmentStatus::Cancelled, AppointmentPriority::Low, '2026-08-16', '16:00', '16:30'],
        ];

        foreach ($appointments as [$clientNumber, $serviceNumber, $notes, $status, $priority, $date, $start, $end]) {
            Appointment::updateOrCreate(
                ['client_id' => $clientNumber, 'service_id' => $serviceNumber, 'appointment_date' => $date, 'start_time' => $start],
                ['notes' => $notes, 'status' => $status, 'priority' => $priority, 'end_time' => $end],
            );
        }
    }
}
