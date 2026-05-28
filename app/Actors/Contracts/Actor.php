<?php

namespace App\Actors\Contracts;

/**
 * The entity on whose behalf an action runs.
 */
interface Actor
{
    public function id(): ?string;

    public function isUser(): bool;

    public function isSystem(): bool;

    public function isAgent(): bool;
}
