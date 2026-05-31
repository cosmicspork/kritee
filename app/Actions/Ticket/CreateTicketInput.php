<?php

namespace App\Actions\Ticket;

use App\Actions\Contracts\ActionInput;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;

final class CreateTicketInput extends ActionInput
{
    /**
     * @param  array<int, int>|null  $projectIds  Projects to associate the ticket with.
     */
    public function __construct(
        #[Required, Max(255)]
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly TicketStatus $status = TicketStatus::Open,
        public readonly TicketPriority $priority = TicketPriority::Medium,
        #[Exists('clients', 'id')]
        public readonly ?int $clientId = null,
        #[Exists('users', 'id')]
        public readonly ?int $assigneeId = null,
        public readonly ?string $dueDate = null,
        public readonly ?array $projectIds = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}
