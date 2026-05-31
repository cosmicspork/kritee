<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a link is removed.
 *
 * The link records are already deleted by dispatch time, so their identifying
 * coordinates travel on the event for listeners that need to react.
 *
 * @phpstan-type LinkCoordinates array{
 *     source_type: string,
 *     source_id: int|string,
 *     target_type: string,
 *     target_id: int|string,
 *     relationship_type: string,
 * }
 */
final class LinkRemoved implements DomainEvent
{
    use Dispatchable;

    /**
     * @param  LinkCoordinates  $link
     * @param  LinkCoordinates|null  $inverse
     */
    public function __construct(
        public readonly array $link,
        public readonly ?array $inverse = null,
    ) {}
}
