<?php

namespace App\Actions\TimeEntry;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Optional;

/**
 * Edits an existing entry. Every editable field is {@see Optional}: an omitted
 * field is left untouched, while a supplied null clears a nullable column.
 */
final class UpdateTimeEntryInput extends ActionInput
{
    public function __construct(
        #[Required, Exists('time_entries', 'id')]
        public readonly int $timeEntryId,
        #[Exists('tickets', 'id')]
        public readonly int|null|Optional $ticketId = new Optional,
        #[Exists('projects', 'id')]
        public readonly int|null|Optional $projectId = new Optional,
        #[Exists('clients', 'id')]
        public readonly int|null|Optional $clientId = new Optional,
        #[Max(2000)]
        public readonly string|null|Optional $description = new Optional,
        #[Min(1)]
        public readonly int|Optional $durationMinutes = new Optional,
        public readonly bool|Optional $isBillable = new Optional,
        public readonly string|null|Optional $startedAt = new Optional,
        public readonly string|null|Optional $endedAt = new Optional,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}
