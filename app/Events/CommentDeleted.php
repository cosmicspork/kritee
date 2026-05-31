<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CommentDeleted implements DomainEvent
{
    use Dispatchable;

    public function __construct(
        public readonly int $commentId,
        public readonly int $ticketId,
    ) {}
}
