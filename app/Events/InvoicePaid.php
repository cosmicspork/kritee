<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An invoice was marked paid.
 */
final class InvoicePaid implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly Invoice $invoice) {}
}
