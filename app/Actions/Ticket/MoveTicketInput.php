<?php

namespace App\Actions\Ticket;

use App\Actions\Contracts\ActionInput;
use App\Enums\TicketStatus;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;

final class MoveTicketInput extends ActionInput
{
    public function __construct(
        #[Required, Exists('tickets', 'id')]
        public readonly int $ticketId,
        #[Required]
        public readonly TicketStatus $status,
        #[Min(0)]
        public readonly int $sortOrder = 0,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}
