<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Only staff may list clients.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may view client profiles.
     */
    public function view(User $user, Client $client): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may create client profiles.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may update client profiles.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may reactivate client accounts.
     */
    public function activate(User $user, Client $client): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may deactivate client accounts.
     */
    public function deactivate(User $user, Client $client): bool
    {
        return $user->isStaff();
    }
}
