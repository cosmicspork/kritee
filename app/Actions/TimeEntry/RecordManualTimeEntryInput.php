<?php

namespace App\Actions\TimeEntry;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;

/**
 * Logs a completed block of time with the duration supplied directly. Start and
 * end timestamps are optional context that does not drive the duration.
 */
final class RecordManualTimeEntryInput extends ActionInput
{
    public function __construct(
        #[Required, Min(1)]
        public readonly int $durationMinutes,
        #[Nullable, Exists('tickets', 'id')]
        public readonly ?int $ticketId = null,
        #[Nullable, Exists('projects', 'id')]
        public readonly ?int $projectId = null,
        #[Nullable, Exists('clients', 'id')]
        public readonly ?int $clientId = null,
        #[Nullable, Max(2000)]
        public readonly ?string $description = null,
        public readonly bool $isBillable = true,
        public readonly ?string $startedAt = null,
        public readonly ?string $endedAt = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}
