<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\User;

/**
 * Authorizes writes to {@see Contact} records. Invoked from the Contact actions
 * so the rules hold for every caller, not only controller-bound requests. The
 * role check is explicit so a future read-only role is denied by default rather
 * than inheriting write access.
 */
class ContactPolicy
{
    public function create(User $user): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, Contact $contact): bool
    {
        return $this->manages($user);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->role === UserRole::Admin;
    }

    private function manages(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Member], true);
    }
}
