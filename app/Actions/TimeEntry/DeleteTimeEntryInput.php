<?php

namespace App\Actions\TimeEntry;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;

final class DeleteTimeEntryInput extends ActionInput
{
    public function __construct(
        #[Required, Exists('time_entries', 'id')]
        public readonly int $timeEntryId,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}
