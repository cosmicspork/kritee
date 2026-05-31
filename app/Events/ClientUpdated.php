<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Foundation\Events\Dispatchable;

final class ClientUpdated implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly Client $client) {}
}
