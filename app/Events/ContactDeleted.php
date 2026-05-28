<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class ContactDeleted implements DomainEvent
{
    use Dispatchable;

    public function __construct(
        public readonly int $contactId,
        public readonly int $clientId,
    ) {}
}
