<?php

namespace App\Actors;

use App\Actors\Contracts\Actor;
use App\Models\User;

final class UserActor implements Actor
{
    public function __construct(public readonly User $user) {}

    public function id(): string
    {
        return (string) $this->user->getKey();
    }

    public function user(): User
    {
        return $this->user;
    }

    public function isUser(): bool
    {
        return true;
    }

    public function isSystem(): bool
    {
        return false;
    }

    public function isAgent(): bool
    {
        return false;
    }
}
