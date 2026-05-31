<?php

namespace App\Actions\TimeEntry;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;

/**
 * Closes a running timer. `endedAt` defaults to now; duration is derived from
 * the entry's `started_at`.
 */
final class StopTimerInput extends ActionInput
{
    public function __construct(
        #[Required, Exists('time_entries', 'id')]
        public readonly int $timeEntryId,
        #[Nullable]
        public readonly ?string $endedAt = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}
