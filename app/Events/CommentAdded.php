<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;

final class CommentAdded implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly Comment $comment) {}
}
