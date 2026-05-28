<?php

namespace App\Events;

use App\Models\Contact;
use Illuminate\Foundation\Events\Dispatchable;

final class ContactCreated implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly Contact $contact) {}
}
