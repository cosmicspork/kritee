<?php

namespace App\Actions\Ticket;

use App\Actions\Contracts\ActionInput;
use App\Enums\TicketPriority;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Optional;

final class UpdateTicketInput extends ActionInput
{
    /**
     * Only the supplied attributes are changed; an {@see Optional} field is left
     * untouched, distinguishing "set to null" from "do not change".
     *
     * @param  array<int, int>|Optional  $projectIds  Replaces the project associations when present.
     */
    public function __construct(
        #[Required, Exists('tickets', 'id')]
        public readonly int $ticketId,
        #[Max(255)]
        public readonly string|Optional $title = new Optional,
        public readonly string|null|Optional $description = new Optional,
        public readonly TicketPriority|Optional $priority = new Optional,
        #[Exists('clients', 'id')]
        public readonly int|null|Optional $clientId = new Optional,
        #[Exists('users', 'id')]
        public readonly int|null|Optional $assigneeId = new Optional,
        public readonly string|null|Optional $dueDate = new Optional,
        public readonly array|Optional $projectIds = new Optional,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}
