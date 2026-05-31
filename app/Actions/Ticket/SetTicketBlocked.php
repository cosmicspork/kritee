<?php

namespace App\Actions\Ticket;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SetTicketBlocked implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        /** @var SetTicketBlockedInput $input */
        $actor = $this->actor();

        $ticket = Ticket::findOrFail($input->ticketId);

        if ($actor instanceof UserActor && Gate::forUser($actor->user())->denies('update', $ticket)) {
            return ActionResult::failure(['authorization' => 'You are not allowed to change this ticket.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $ticket): ActionResult {
            DB::transaction(function () use ($input, $ticket): void {
                $ticket->is_blocked = $input->isBlocked;
                $ticket->save();
            });

            return ActionResult::success($ticket->fresh());
        });
    }
}
