<?php

namespace App\Actions\Concerns;

use App\Actors\Contracts\Actor;

/**
 * Resolves the {@see Actor} an action runs as.
 *
 * Resolution goes through a declared `Actor` return type rather than a bare
 * `app(Actor::class)` call so the type stays the interface: the concrete actor
 * is chosen by the caller (HTTP middleware, CLI, jobs, agents), and an action
 * narrows it with `instanceof` to enforce who may run it.
 */
trait ResolvesActor
{
    protected function actor(): Actor
    {
        return app(Actor::class);
    }
}
