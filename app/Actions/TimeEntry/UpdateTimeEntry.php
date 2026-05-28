<?php

namespace App\Actions\TimeEntry;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actions\TimeEntry\Concerns\ResolvesTimeEntryContext;
use App\Actors\UserActor;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

/**
 * Applies a partial edit to an entry. Only fields the caller supplied are
 * touched. Editing the relational context re-resolves `client_id` against the
 * post-edit ticket and project so attribution stays coherent.
 */
class UpdateTimeEntry implements Action
{
    use EnsuresIdempotency, ResolvesActor, ResolvesTimeEntryContext;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof UpdateTimeEntryInput) {
            return ActionResult::failure(['input' => 'Expected '.UpdateTimeEntryInput::class.'.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user actor is required to edit a time entry.']);
        }

        $entry = TimeEntry::find($input->timeEntryId);

        if ($entry === null) {
            return ActionResult::failure(['time_entry_id' => 'Time entry not found.']);
        }

        if ($actor->user()->cannot('update', $entry)) {
            return ActionResult::failure(['authorization' => 'Not authorized to edit this time entry.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $entry): ActionResult {
            $changes = $this->scalarChanges($input);
            $this->applyClientResolution($input, $entry, $changes);

            DB::transaction(function () use ($entry, $changes): void {
                $entry->update($changes);
            });

            return ActionResult::success($entry->refresh());
        });
    }

    /**
     * The directly-supplied column changes, excluding `client_id` which is
     * derived from the resolved ticket/project context.
     *
     * @return array<string, mixed>
     */
    private function scalarChanges(UpdateTimeEntryInput $input): array
    {
        $changes = [];

        if (! $input->ticketId instanceof Optional) {
            $changes['ticket_id'] = $input->ticketId;
        }

        if (! $input->projectId instanceof Optional) {
            $changes['project_id'] = $input->projectId;
        }

        if (! $input->description instanceof Optional) {
            $changes['description'] = $input->description;
        }

        if (! $input->durationMinutes instanceof Optional) {
            $changes['duration_minutes'] = $input->durationMinutes;
        }

        if (! $input->isBillable instanceof Optional) {
            $changes['is_billable'] = $input->isBillable;
        }

        if (! $input->startedAt instanceof Optional) {
            $changes['started_at'] = $input->startedAt !== null ? CarbonImmutable::parse($input->startedAt) : null;
        }

        if (! $input->endedAt instanceof Optional) {
            $changes['ended_at'] = $input->endedAt !== null ? CarbonImmutable::parse($input->endedAt) : null;
        }

        return $changes;
    }

    /**
     * Re-resolve `client_id` whenever the entry's client, ticket, or project is
     * part of the edit; otherwise the existing attribution stands.
     *
     * @param  array<string, mixed>  $changes
     */
    private function applyClientResolution(UpdateTimeEntryInput $input, TimeEntry $entry, array &$changes): void
    {
        $clientTouched = ! $input->clientId instanceof Optional;
        $contextTouched = ! $input->ticketId instanceof Optional || ! $input->projectId instanceof Optional;

        if (! $clientTouched && ! $contextTouched) {
            return;
        }

        $ticketId = array_key_exists('ticket_id', $changes) ? $changes['ticket_id'] : $entry->ticket_id;
        $projectId = array_key_exists('project_id', $changes) ? $changes['project_id'] : $entry->project_id;

        $explicitClientId = $input->clientId instanceof Optional ? null : $input->clientId;

        $ticket = $ticketId !== null ? Ticket::find($ticketId) : null;
        $project = $projectId !== null ? Project::find($projectId) : null;

        $changes['client_id'] = $this->resolveClientId($explicitClientId, $ticket, $project);
    }
}
