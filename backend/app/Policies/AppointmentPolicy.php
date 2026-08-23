<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Only staff may list every appointment.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff may view any appointment; clients may view their own.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->isStaff() || $appointment->isOwnedBy($user);
    }

    /**
     * Staff create appointments on behalf of clients; active clients book their own.
     */
    public function create(User $user): bool
    {
        return $user->isStaff() || (bool) $user->client?->active;
    }

    /**
     * Only staff may edit appointment details.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may delete appointments.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may confirm requested appointments.
     */
    public function confirm(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may mark appointments as completed.
     */
    public function complete(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff may cancel any appointment; clients may cancel their own.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->isStaff() || $appointment->isOwnedBy($user);
    }
}
