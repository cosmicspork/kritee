<?php

namespace App\Actions\TimeEntry;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Events\TimeEntryRecorded;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Closes a running entry: stamps `ended_at` and derives `duration_minutes` from
 * the elapsed wall-clock time. Recording the duration is an edit, not a new
 * record, so no {@see TimeEntryRecorded} fires here.
 */
class StopTimer implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof StopTimerInput) {
            return ActionResult::failure(['input' => 'Expected '.StopTimerInput::class.'.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user actor is required to stop a timer.']);
        }

        $entry = TimeEntry::find($input->timeEntryId);

        if ($entry === null) {
            return ActionResult::failure(['time_entry_id' => 'Time entry not found.']);
        }

        if ($actor->user()->cannot('update', $entry)) {
            return ActionResult::failure(['authorization' => 'Not authorized to stop this timer.']);
        }

        if ($entry->started_at === null) {
            return ActionResult::failure(['started_at' => 'This entry has no start time to measure from.']);
        }

        if ($entry->ended_at !== null) {
            return ActionResult::failure(['ended_at' => 'This timer has already been stopped.']);
        }

        $startedAt = CarbonImmutable::parse($entry->started_at);
        $endedAt = $input->endedAt !== null ? CarbonImmutable::parse($input->endedAt) : CarbonImmutable::now();

        if ($endedAt->lessThan($startedAt)) {
            return ActionResult::failure(['ended_at' => 'The stop time cannot precede the start time.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($entry, $startedAt, $endedAt): ActionResult {
            DB::transaction(function () use ($entry, $startedAt, $endedAt): void {
                $entry->update([
                    'ended_at' => $endedAt,
                    'duration_minutes' => (int) $startedAt->diffInMinutes($endedAt),
                ]);
            });

            return ActionResult::success($entry->refresh());
        });
    }
}
