<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Admins may inspect every service; clients may inspect active services.
     */
    public function view(User $user, Service $service): bool
    {
        return $user->isAdmin() || (bool) $service->active;
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

    /**
     * Only admins may activate services.
     */
    public function activate(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }
}
