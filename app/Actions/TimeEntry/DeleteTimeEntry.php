<?php

namespace App\Actions\TimeEntry;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\DB;

class DeleteTimeEntry implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof DeleteTimeEntryInput) {
            return ActionResult::failure(['input' => 'Expected '.DeleteTimeEntryInput::class.'.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user actor is required to delete a time entry.']);
        }

        // The lookup, authorization, and delete all sit inside the idempotency
        // claim so a retry replays the cached success rather than re-querying a
        // row the first call already removed.
        return $this->idempotently($input->idempotencyKey, function () use ($input, $actor): ActionResult {
            $entry = TimeEntry::find($input->timeEntryId);

            if ($entry === null) {
                return ActionResult::failure(['time_entry_id' => 'Time entry not found.']);
            }

            if ($actor->user()->cannot('delete', $entry)) {
                return ActionResult::failure(['authorization' => 'Not authorized to delete this time entry.']);
            }

            $id = $entry->getKey();

            DB::transaction(function () use ($entry): void {
                $entry->delete();
            });

            return ActionResult::success(['id' => $id]);
        });
    }
}
