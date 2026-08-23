<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentWorkflowException;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class AppointmentWorkflowService
{
    public function confirm(Appointment $appointment): Appointment
    {
        return $this->transition($appointment, 'confirmed', [AppointmentStatus::Requested], AppointmentStatus::Confirmed);
    }

    public function complete(Appointment $appointment): Appointment
    {
        return $this->transition($appointment, 'completed', [AppointmentStatus::Confirmed], AppointmentStatus::Completed);
    }

    public function cancel(Appointment $appointment): Appointment
    {
        return $this->transition($appointment, 'cancelled', [AppointmentStatus::Requested, AppointmentStatus::Confirmed], AppointmentStatus::Cancelled);
    }

    /**
     * @param  array<int, AppointmentStatus>  $allowedStatuses
     */
    private function transition(
        Appointment $appointment,
        string $action,
        array $allowedStatuses,
        AppointmentStatus $nextStatus,
    ): Appointment {
        return DB::transaction(function () use ($appointment, $action, $allowedStatuses, $nextStatus): Appointment {
            $lockedAppointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if (! in_array($lockedAppointment->status, $allowedStatuses, true)) {
                throw new AppointmentWorkflowException($action, $lockedAppointment->status);
            }

            $lockedAppointment->update(['status' => $nextStatus]);

            return $lockedAppointment->fresh()->loadDetails();
        });
    }
}
