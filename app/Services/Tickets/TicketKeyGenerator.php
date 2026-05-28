<?php

namespace App\Services\Tickets;

use App\Models\Ticket;

/**
 * Produces the next sequential ticket key in the `TK-<n>` form. The sequence
 * derives from the highest numeric suffix already in use rather than the row
 * id, so it stays correct when keys are assigned out of band or rows are
 * deleted.
 */
final class TicketKeyGenerator
{
    private const PREFIX = 'TK-';

    public function next(): string
    {
        $highest = Ticket::query()
            ->where('key', 'like', self::PREFIX.'%')
            ->get(['key'])
            ->map(fn (Ticket $ticket): int => $this->sequenceOf($ticket->key))
            ->max() ?? 0;

        return self::PREFIX.($highest + 1);
    }

    /**
     * Extract the trailing integer from a key, treating a malformed suffix as
     * zero so it never advances the sequence.
     */
    private function sequenceOf(string $key): int
    {
        return (int) substr($key, strlen(self::PREFIX));
    }
}
