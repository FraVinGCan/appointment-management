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
        $today = today();
        $appointments = [
            [1, 1, 'Discuss business goals and recommend practical next steps.', AppointmentStatus::Requested, AppointmentPriority::High, 1, '09:00', '09:30'],
            [2, 2, 'Review submitted documents and identify recommended updates.', AppointmentStatus::Confirmed, AppointmentPriority::Medium, 1, '10:00', '10:30'],
            [3, 3, 'Review the project scope and proposed delivery plan.', AppointmentStatus::Completed, AppointmentPriority::Low, -5, '08:30', '09:00'],
            [4, 1, 'Develop a practical strategy for the requested business goals.', AppointmentStatus::Confirmed, AppointmentPriority::High, 2, '11:00', '12:00'],
            [5, 2, 'Discuss required revisions to the submitted documents.', AppointmentStatus::Cancelled, AppointmentPriority::Medium, -3, '13:00', '13:30'],
            [6, 3, 'Map project priorities and confirm the next planning steps.', AppointmentStatus::Requested, AppointmentPriority::High, 3, '09:30', '10:00'],
            [7, 1, 'Review business objectives and refine the recommended approach.', AppointmentStatus::Confirmed, AppointmentPriority::Medium, 3, '14:00', '14:30'],
            [8, 2, 'Review document requirements and provide improvement notes.', AppointmentStatus::Requested, AppointmentPriority::Low, 4, '08:00', '08:30'],
            [9, 3, 'Confirm the project timeline and standard preparation tasks.', AppointmentStatus::Completed, AppointmentPriority::High, -7, '15:00', '16:00'],
            [10, 1, 'Evaluate the strategy outcomes and agree on adjustments.', AppointmentStatus::Confirmed, AppointmentPriority::Medium, 5, '10:30', '11:00'],
            [11, 2, 'Complete a general document review and recommendations.', AppointmentStatus::Requested, AppointmentPriority::High, 5, '13:30', '14:00'],
            [12, 3, 'Discuss project requirements and the proposed work plan.', AppointmentStatus::Cancelled, AppointmentPriority::Low, -1, '16:00', '16:30'],
        ];

        foreach ($appointments as [$clientNumber, $serviceNumber, $notes, $status, $priority, $dateOffset, $start, $end]) {
            $date = $today->copy()->addDays($dateOffset)->format('Y-m-d');

            Appointment::updateOrCreate(
                ['client_id' => $clientNumber, 'service_id' => $serviceNumber, 'notes' => $notes],
                ['status' => $status, 'priority' => $priority, 'appointment_date' => $date, 'start_time' => $start, 'end_time' => $end],
            );
        }
    }
}
