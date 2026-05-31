<?php

namespace App\Events;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a ticket changes status column or position on the kanban board.
 */
final class TicketMoved implements DomainEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketStatus $from,
        public readonly TicketStatus $to,
    ) {}
}
