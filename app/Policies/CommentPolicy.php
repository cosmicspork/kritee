<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;

class CommentPolicy
{
    /**
     * Any authenticated user may comment on a ticket.
     */
    public function create(User $user, Ticket $ticket): bool
    {
        return true;
    }

    /**
     * A comment may be removed by its author or an administrator.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->isAdmin() || (int) $comment->author_id === (int) $user->getKey();
    }
}
