<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

/**
 * Authorizes invoice writes. Called from the action layer, not controllers,
 * so the same boundary holds for HTTP, CLI, jobs, and agents.
 */
class InvoicePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Line items may only be composed onto an invoice that is still a draft.
     */
    public function addLineItems(User $user, Invoice $invoice): bool
    {
        return $invoice->status === InvoiceStatus::Draft;
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $invoice->status === InvoiceStatus::Draft;
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Viewed, InvoiceStatus::Overdue], true);
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $invoice->status !== InvoiceStatus::Paid;
    }
}
