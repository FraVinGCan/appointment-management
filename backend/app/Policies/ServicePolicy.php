<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Only admins may inspect individual services.
     */
    public function view(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins may create services.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins may update services.
     */
    public function update(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins may deactivate services.
     */
    public function deactivate(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }
}
