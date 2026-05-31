<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;

/**
 * Admins and members may manage clients. The role check is explicit rather than
 * "any authenticated user" so a future read-only or restricted role is denied by
 * default instead of inheriting write access.
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manages($user);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->manages($user);
    }

    public function create(User $user): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->manages($user);
    }

    public function archive(User $user, Client $client): bool
    {
        return $this->manages($user);
    }

    private function manages(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Member], true);
    }
}
