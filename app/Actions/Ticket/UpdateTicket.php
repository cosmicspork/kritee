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
use Spatie\LaravelData\Optional;

class UpdateTicket implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        /** @var UpdateTicketInput $input */
        $actor = $this->actor();

        $ticket = Ticket::findOrFail($input->ticketId);

        if ($actor instanceof UserActor && Gate::forUser($actor->user())->denies('update', $ticket)) {
            return ActionResult::failure(['authorization' => 'You are not allowed to update this ticket.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $ticket): ActionResult {
            DB::transaction(function () use ($input, $ticket): void {
                $attributes = array_filter([
                    'title' => $input->title,
                    'description' => $input->description,
                    'priority' => $input->priority,
                    'client_id' => $input->clientId,
                    'assignee_id' => $input->assigneeId,
                    'due_date' => $input->dueDate,
                ], fn (mixed $value): bool => ! $value instanceof Optional);

                if ($attributes !== []) {
                    $ticket->fill($attributes)->save();
                }

                if (! $input->projectIds instanceof Optional) {
                    $ticket->projects()->sync($input->projectIds);
                }
            });

            return ActionResult::success($ticket->fresh());
        });
    }
}
