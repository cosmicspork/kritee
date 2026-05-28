<?php

namespace App\Actions\Ticket;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Models\Ticket;
use App\Services\Tickets\TicketKeyGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateTicket implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function __construct(private readonly TicketKeyGenerator $keyGenerator) {}

    public function execute(ActionInput $input): ActionResult
    {
        /** @var CreateTicketInput $input */
        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A ticket must be created by a user.']);
        }

        if (Gate::forUser($actor->user())->denies('create', Ticket::class)) {
            return ActionResult::failure(['authorization' => 'You are not allowed to create tickets.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $actor): ActionResult {
            $ticket = DB::transaction(function () use ($input, $actor): Ticket {
                $ticket = Ticket::create([
                    'key' => $this->keyGenerator->next(),
                    'title' => $input->title,
                    'description' => $input->description,
                    'status' => $input->status,
                    'priority' => $input->priority,
                    'client_id' => $input->clientId,
                    'assignee_id' => $input->assigneeId,
                    'due_date' => $input->dueDate,
                    'creator_id' => $actor->user()->getKey(),
                ]);

                if ($input->projectIds !== null) {
                    $ticket->projects()->sync($input->projectIds);
                }

                return $ticket;
            });

            return ActionResult::success($ticket->fresh());
        });
    }
}
