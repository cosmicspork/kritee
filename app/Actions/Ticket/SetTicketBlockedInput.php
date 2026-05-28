<?php

namespace App\Actions\Ticket;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;

final class SetTicketBlockedInput extends ActionInput
{
    public function __construct(
        #[Required, Exists('tickets', 'id')]
        public readonly int $ticketId,
        #[Required]
        public readonly bool $isBlocked,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}
