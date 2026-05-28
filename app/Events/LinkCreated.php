<?php

namespace App\Events;

use App\Models\Linkable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a link is established between two records.
 *
 * The inverse link auto-created for asymmetric relationship types is supplied
 * so listeners observe the full unit of work, not just the primary edge.
 */
final class LinkCreated implements DomainEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Linkable $link,
        public readonly ?Linkable $inverse = null,
    ) {}
}
