<?php

namespace App\Console\Commands\Concerns;

use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Models\User;
use Symfony\Component\Console\Input\InputOption;

/**
 * Binds the command's {@see Actor} into the container: a {@see SystemActor} by
 * default, or a {@see UserActor} when invoked with `--user=<id>`.
 *
 * Commands that pull this in gain the `--user` option automatically; resolve the
 * actor once at the top of `handle()` before dispatching any action.
 */
trait ResolvesActor
{
    protected function resolveActor(): Actor
    {
        $actor = $this->buildActor();

        $this->laravel->instance(Actor::class, $actor);

        return $actor;
    }

    private function buildActor(): Actor
    {
        $id = $this->option('user');

        if ($id === null || $id === '') {
            return new SystemActor;
        }

        return new UserActor(User::findOrFail($id));
    }

    /**
     * Signature-based commands build their definition from the parsed signature
     * and never consult {@see getOptions()}, so the `--user` option is added to
     * the command definition directly — the one path Symfony always invokes.
     */
    protected function configure(): void
    {
        parent::configure();

        $this->getDefinition()->addOption(
            new InputOption('user', null, InputOption::VALUE_OPTIONAL, 'Run as the user with this id'),
        );
    }
}
