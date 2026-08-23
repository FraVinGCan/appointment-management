<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Only staff may inspect individual services.
     */
    public function view(User $user, Service $service): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Only staff may create services.
     */
    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Only staff may update services.
     */
    public function update(User $user, Service $service): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Only staff may deactivate services.
     */
    public function deactivate(User $user, Service $service): bool
    {
        return $this->isStaff($user);
    }

    private function isStaff(User $user): bool
    {
        return $user->is_staff;
    }
}
