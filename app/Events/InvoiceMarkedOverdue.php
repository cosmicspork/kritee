<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An issued invoice passed its due date and was moved to overdue.
 */
final class InvoiceMarkedOverdue implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly Invoice $invoice) {}
}
