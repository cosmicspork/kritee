<?php

namespace App\Policies;

use App\Models\Roadmap;
use App\Models\User;

/**
 * Authorizes writes to {@see Roadmap} records and their items. Resolved by
 * Laravel's policy auto-discovery and invoked from the Roadmap actions, where it
 * covers every caller (HTTP, CLI, queue, agent) rather than only controller-bound
 * requests.
 */
class RoadmapPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Roadmap $roadmap): bool
    {
        return true;
    }

    /**
     * Archiving retires a roadmap from circulation; reserve it for admins so a
     * member cannot pull a shared roadmap out from under others.
     */
    public function archive(User $user, Roadmap $roadmap): bool
    {
        return $user->isAdmin();
    }

    public function manageItems(User $user, Roadmap $roadmap): bool
    {
        return true;
    }
}
