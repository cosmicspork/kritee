<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryPolicy
{
    /**
     * Any authenticated user may log their own time.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Time belongs to the user who logged it; admins may correct anyone's.
     * A billed entry is locked — corrections to it go through billing, not here.
     */
    public function update(User $user, TimeEntry $timeEntry): bool
    {
        if ($timeEntry->is_billed) {
            return false;
        }

        return $user->isAdmin() || $user->getKey() === $timeEntry->user_id;
    }

    public function delete(User $user, TimeEntry $timeEntry): bool
    {
        if ($timeEntry->is_billed) {
            return false;
        }

        return $user->isAdmin() || $user->getKey() === $timeEntry->user_id;
    }
}
