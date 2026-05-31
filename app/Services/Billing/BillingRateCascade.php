<?php

namespace App\Services\Billing;

use App\Models\BillingRate;
use App\Models\Client;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the hourly rate that applies to a time entry by walking the
 * rate hierarchy from most to least specific: ticket, then project, then
 * client, then the logging user's default. The first tier holding a rate
 * effective on the given date wins; the user's `default_hourly_rate` is the
 * terminal fallback.
 */
final class BillingRateCascade
{
    /**
     * Resolve the effective hourly rate for the entry on the given date, or
     * null when no tier yields one.
     */
    public function resolve(TimeEntry $timeEntry, ?CarbonImmutable $on = null): ?string
    {
        $on ??= CarbonImmutable::now();

        foreach ($this->rateables($timeEntry) as $rateable) {
            $amount = $this->effectiveRate($rateable, $on);

            if ($amount !== null) {
                return $amount;
            }
        }

        return $this->userDefault($timeEntry);
    }

    /**
     * The rate-bearing entities for the entry, ordered most specific first.
     *
     * @return iterable<Model>
     */
    private function rateables(TimeEntry $timeEntry): iterable
    {
        $ticket = $timeEntry->ticket;

        if ($ticket instanceof Ticket) {
            yield $ticket;
        }

        $project = $this->project($timeEntry, $ticket);

        if ($project instanceof Project) {
            yield $project;
        }

        $client = $this->client($timeEntry, $ticket, $project);

        if ($client instanceof Client) {
            yield $client;
        }
    }

    private function project(TimeEntry $timeEntry, ?Ticket $ticket): ?Project
    {
        if ($timeEntry->project instanceof Project) {
            return $timeEntry->project;
        }

        return $ticket?->projects()->first();
    }

    private function client(TimeEntry $timeEntry, ?Ticket $ticket, ?Project $project): ?Client
    {
        return $timeEntry->client
            ?? $ticket->client
            ?? $project?->client;
    }

    /**
     * The newest rate attached to the entity that is effective on the date,
     * or null when the entity carries no qualifying rate.
     */
    private function effectiveRate(Model $rateable, CarbonImmutable $on): ?string
    {
        $rate = BillingRate::query()
            ->where('rateable_type', $rateable->getMorphClass())
            ->where('rateable_id', $rateable->getKey())
            ->where(function ($query) use ($on): void {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $on->toDateString());
            })
            ->where(function ($query) use ($on): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $on->toDateString());
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        return $rate?->amount;
    }

    private function userDefault(TimeEntry $timeEntry): ?string
    {
        $user = $timeEntry->user;

        return $user instanceof User ? $user->default_hourly_rate : null;
    }
}
