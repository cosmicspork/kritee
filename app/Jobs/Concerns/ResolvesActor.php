<?php

namespace App\Jobs\Concerns;

use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Models\User;

/**
 * Reconstructs the originating {@see Actor} when a queued job runs.
 *
 * A job persists only a scalar `user_id` in its payload — Eloquent models do not
 * survive serialization intact across the queue boundary — and rebuilds a
 * {@see UserActor} from it on handle, falling back to {@see SystemActor} for
 * userless work such as cron-dispatched jobs.
 */
trait ResolvesActor
{
    protected ?string $actorUserId = null;

    protected function forActor(Actor $actor): static
    {
        $this->actorUserId = $actor->isUser() ? $actor->id() : null;

        return $this;
    }

    protected function resolveActor(): Actor
    {
        $actor = $this->actorUserId === null
            ? new SystemActor
            : new UserActor(User::findOrFail($this->actorUserId));

        app()->instance(Actor::class, $actor);

        return $actor;
    }
}
