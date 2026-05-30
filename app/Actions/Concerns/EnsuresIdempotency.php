<?php

namespace App\Actions\Concerns;

use App\Actions\Contracts\ActionResult;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Guards an action's side effects behind an idempotency key so a retried call
 * returns the original result instead of running the work a second time.
 */
trait EnsuresIdempotency
{
    private const string IDEMPOTENCY_CACHE_PREFIX = 'idempotency:';

    private const int IDEMPOTENCY_TTL_SECONDS = 86400;

    /**
     * Run $work at most once per key. A null key is a one-off call and always
     * runs. The first caller to claim a key wins the slot atomically; later
     * callers replay the stored result without touching side effects.
     */
    protected function idempotently(?string $key, Closure $work): ActionResult
    {
        if ($key === null) {
            return $work();
        }

        $cacheKey = self::IDEMPOTENCY_CACHE_PREFIX.$key;

        if (! Cache::add($cacheKey, true, self::IDEMPOTENCY_TTL_SECONDS)) {
            $stored = Cache::get($cacheKey);

            if ($stored instanceof ActionResult) {
                return $stored;
            }

            return ActionResult::failure([
                'idempotency_key' => 'A request with this idempotency key is already in progress.',
            ]);
        }

        $result = $work();

        Cache::put($cacheKey, $result, self::IDEMPOTENCY_TTL_SECONDS);

        return $result;
    }
}
