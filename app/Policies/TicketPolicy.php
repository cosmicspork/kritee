<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $ticket->creator_id === $user->getKey() || $ticket->assignee_id === $user->getKey();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $ticket->creator_id === $user->getKey();
    }
}
