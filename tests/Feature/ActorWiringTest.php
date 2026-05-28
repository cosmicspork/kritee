<?php

use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Console\Commands\Concerns\ResolvesActor as ResolvesCommandActor;
use App\Jobs\Concerns\ResolvesActor as ResolvesJobActor;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;

/**
 * Resolves and reports its actor so the binding can be asserted from the test.
 */
class ActorProbeCommand extends Command
{
    use ResolvesCommandActor;

    protected $signature = 'test:actor-probe';

    public function handle(): int
    {
        $actor = $this->resolveActor();

        $this->line($actor::class.'|'.($actor->id() ?? 'null'));

        return self::SUCCESS;
    }
}

/**
 * Captures the actor it rebuilds from its payload for inspection by the test.
 */
class ActorProbeJob
{
    use ResolvesJobActor;

    public ?Actor $resolved = null;

    public function carry(Actor $actor): static
    {
        return $this->forActor($actor);
    }

    public function handle(): void
    {
        $this->resolved = $this->resolveActor();
    }
}

beforeEach(function () {
    $this->app[Kernel::class]->registerCommand(new ActorProbeCommand);
});

test('a command without --user resolves a SystemActor', function () {
    $this->artisan('test:actor-probe')
        ->expectsOutput(SystemActor::class.'|null')
        ->assertExitCode(0);

    expect(app(Actor::class))->toBeInstanceOf(SystemActor::class);
});

test('a command with --user resolves a UserActor wrapping that user', function () {
    $user = User::factory()->create();

    $this->artisan('test:actor-probe', ['--user' => (string) $user->getKey()])
        ->expectsOutput(UserActor::class.'|'.$user->getKey())
        ->assertExitCode(0);

    $actor = app(Actor::class);

    expect($actor)->toBeInstanceOf(UserActor::class)
        ->and($actor->id())->toBe((string) $user->getKey())
        ->and($actor->user()->is($user))->toBeTrue();
});

test('the job helper rebuilds a UserActor from a carried user payload', function () {
    $user = User::factory()->create();

    $job = (new ActorProbeJob)->carry(new UserActor($user));
    $job->handle();

    expect($job->resolved)->toBeInstanceOf(UserActor::class)
        ->and($job->resolved->id())->toBe((string) $user->getKey())
        ->and(app(Actor::class))->toBeInstanceOf(UserActor::class);
});

test('the job helper falls back to a SystemActor when no user is carried', function () {
    $job = (new ActorProbeJob)->carry(new SystemActor);
    $job->handle();

    expect($job->resolved)->toBeInstanceOf(SystemActor::class)
        ->and($job->resolved->id())->toBeNull();
});
