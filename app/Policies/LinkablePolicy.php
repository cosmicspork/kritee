<?php

namespace App\Policies;

use App\Models\Linkable;
use App\Models\User;

class LinkablePolicy
{
    /**
     * Any authenticated member may relate records to one another.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * A link may be unlinked by any member; removing an edge is a low-stakes,
     * fully reversible operation, unlike destroying the linked records.
     */
    public function delete(User $user, Linkable $link): bool
    {
        return true;
    }
}
