<?php

namespace App\Events;

use App\Models\TimeEntry;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a time entry first comes into existence — whether started as an
 * open timer or recorded with a duration up front. Billing and reporting attach
 * here; stopping or editing an existing entry does not re-fire it.
 */
final class TimeEntryRecorded implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly TimeEntry $timeEntry) {}
}
