<?php

namespace App\Actors;

use App\Actors\Contracts\Actor;
use App\Models\AgentExecution;

final class AgentActor implements Actor
{
    public function __construct(public readonly AgentExecution $execution) {}

    public function id(): string
    {
        return (string) $this->execution->getKey();
    }

    public function execution(): AgentExecution
    {
        return $this->execution;
    }

    public function isUser(): bool
    {
        return false;
    }

    public function isSystem(): bool
    {
        return false;
    }

    public function isAgent(): bool
    {
        return true;
    }
}
