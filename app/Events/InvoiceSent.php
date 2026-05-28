<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An invoice transitioned from draft to sent.
 */
final class InvoiceSent implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly Invoice $invoice) {}
}
