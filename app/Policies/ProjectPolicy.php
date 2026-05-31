<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Authorizes writes to {@see Project} records. Resolved by Laravel's policy
 * auto-discovery and invoked from the Project actions, where it covers every
 * caller (HTTP, CLI, queue, agent) rather than only controller-bound requests.
 */
class ProjectPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return true;
    }

    public function archive(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }
}
