<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A new expense was persisted. Carries the expense and the actor id behind the
 * write so downstream listeners (and the future agent audit log) can attribute
 * it without a follow-up query.
 */
final class ExpenseRecorded implements DomainEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Expense $expense,
        public readonly ?string $actorId = null,
    ) {}
}
