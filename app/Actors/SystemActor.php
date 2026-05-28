<?php

namespace App\Actors;

use App\Actors\Contracts\Actor;

/**
 * Cron, CLI, and queued work with no originating user.
 */
final class SystemActor implements Actor
{
    public function id(): ?string
    {
        return null;
    }

    public function isUser(): bool
    {
        return false;
    }

    public function isSystem(): bool
    {
        return true;
    }

    public function isAgent(): bool
    {
        return false;
    }
}
