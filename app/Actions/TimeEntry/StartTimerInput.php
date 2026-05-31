<?php

namespace App\Actions\TimeEntry;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;

/**
 * Opens a running timer. The start time defaults to now and the entry stays
 * open (`ended_at` null) until stopped.
 */
final class StartTimerInput extends ActionInput
{
    public function __construct(
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
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}
