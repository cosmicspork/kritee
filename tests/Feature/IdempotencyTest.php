<?php

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\ActionResult;
use Illuminate\Support\Facades\Cache;

function idempotencyHarness(): object
{
    return new class
    {
        use EnsuresIdempotency;

        public function run(?string $key, Closure $work): ActionResult
        {
            return $this->idempotently($key, $work);
        }
    };
}

test('a null key always runs the work', function () {
    $runs = 0;

    $harness = idempotencyHarness();

    $result = $harness->run(null, function () use (&$runs) {
        $runs++;

        return ActionResult::success(['n' => $runs]);
    });

    expect($runs)->toBe(1)
        ->and($result->success)->toBeTrue()
        ->and($result->data)->toBe(['n' => 1]);
});

test('a repeated key returns the cached result without rerunning side effects', function () {
    $runs = 0;
    $key = 'invoice-send-42';

    $work = function () use (&$runs) {
        $runs++;

        return ActionResult::success(['run' => $runs]);
    };

    $harness = idempotencyHarness();

    $first = $harness->run($key, $work);
    $second = $harness->run($key, $work);

    expect($runs)->toBe(1)
        ->and($first->data)->toBe(['run' => 1])
        ->and($second->data)->toBe(['run' => 1])
        ->and($second)->toEqual($first);
});

test('distinct keys each run their own work', function () {
    $runs = 0;

    $work = function () use (&$runs) {
        $runs++;

        return ActionResult::success(['run' => $runs]);
    };

    $harness = idempotencyHarness();

    $harness->run('key-a', $work);
    $harness->run('key-b', $work);

    expect($runs)->toBe(2);
});

test('a key claimed but not yet completed reports an in-progress failure', function () {
    $key = 'half-finished';

    Cache::add('idempotency:'.$key, true, 60);

    $harness = idempotencyHarness();

    $result = $harness->run($key, fn () => ActionResult::success('should not run'));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('idempotency_key');
});
