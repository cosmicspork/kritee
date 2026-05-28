<?php

namespace App\Actions\TimeEntry;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actions\TimeEntry\Concerns\ResolvesTimeEntryContext;
use App\Actors\UserActor;
use App\Events\TimeEntryRecorded;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Opens a running time entry: `started_at` set, `ended_at` and
 * `duration_minutes` left empty until {@see StopTimer} closes it.
 */
class StartTimer implements Action
{
    use EnsuresIdempotency, ResolvesActor, ResolvesTimeEntryContext;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof StartTimerInput) {
            return ActionResult::failure(['input' => 'Expected '.StartTimerInput::class.'.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user actor is required to start a timer.']);
        }

        if ($actor->user()->cannot('create', TimeEntry::class)) {
            return ActionResult::failure(['authorization' => 'Not authorized to log time.']);
        }

        $userId = $actor->user()->getKey();

        return $this->idempotently($input->idempotencyKey, function () use ($input, $userId): ActionResult {
            $ticket = $input->ticketId !== null ? Ticket::find($input->ticketId) : null;
            $project = $input->projectId !== null ? Project::find($input->projectId) : null;

            $entry = DB::transaction(function () use ($input, $userId, $ticket, $project): TimeEntry {
                return TimeEntry::create([
                    'user_id' => $userId,
                    'ticket_id' => $input->ticketId,
                    'project_id' => $input->projectId,
                    'client_id' => $this->resolveClientId($input->clientId, $ticket, $project),
                    'description' => $input->description,
                    'started_at' => $input->startedAt !== null
                        ? CarbonImmutable::parse($input->startedAt)
                        : CarbonImmutable::now(),
                    'ended_at' => null,
                    'duration_minutes' => 0,
                    'is_billable' => $input->isBillable,
                ]);
            });

            TimeEntryRecorded::dispatch($entry);

            return ActionResult::success($entry);
        });
    }
}
