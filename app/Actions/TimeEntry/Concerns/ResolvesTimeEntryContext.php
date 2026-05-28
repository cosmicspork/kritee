<?php

namespace App\Actions\TimeEntry\Concerns;

use App\Models\Project;
use App\Models\Ticket;

/**
 * Resolves the client a piece of logged time is attributed to.
 */
trait ResolvesTimeEntryContext
{
    /**
     * Resolve the client an entry bills against, most specific source first:
     * an explicit id, then the ticket's client (directly or via its first
     * project), then the project's client. Internal work resolves to null.
     */
    protected function resolveClientId(?int $clientId, ?Ticket $ticket, ?Project $project): ?int
    {
        if ($clientId !== null) {
            return $clientId;
        }

        if ($ticket !== null) {
            $ticketClientId = $ticket->client_id ?? $ticket->projects()->first()?->client_id;

            if ($ticketClientId !== null) {
                return $ticketClientId;
            }
        }

        return $project?->client_id;
    }
}
