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
        return $user->isStaff();
    }

    /**
     * Only staff may create services.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may update services.
     */
    public function update(User $user, Service $service): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may deactivate services.
     */
    public function deactivate(User $user, Service $service): bool
    {
        return $user->isStaff();
    }
}
