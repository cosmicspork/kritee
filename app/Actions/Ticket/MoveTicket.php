<?php

namespace App\Actions\Ticket;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Enums\TicketStatus;
use App\Events\TicketMoved;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MoveTicket implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        /** @var MoveTicketInput $input */
        $actor = $this->actor();

        $ticket = Ticket::findOrFail($input->ticketId);

        if ($actor instanceof UserActor && Gate::forUser($actor->user())->denies('update', $ticket)) {
            return ActionResult::failure(['authorization' => 'You are not allowed to move this ticket.']);
        }

        $from = $ticket->status;

        $result = $this->idempotently($input->idempotencyKey, function () use ($input, $ticket): ActionResult {
            DB::transaction(function () use ($input, $ticket): void {
                $ticket->status = $input->status;
                $ticket->save();

                $this->reorderColumn($input->status, $ticket, $input->sortOrder);
            });

            return ActionResult::success($ticket->fresh());
        });

        if ($result->success) {
            TicketMoved::dispatch($ticket->fresh() ?? $ticket, $from, $input->status);
        }

        return $result;
    }

    /**
     * Renumber the target column so $moved sits at $position and its siblings
     * keep a stable, gap-free order.
     */
    private function reorderColumn(TicketStatus $status, Ticket $moved, int $position): void
    {
        /** @var Collection<int, Ticket> $siblings */
        $siblings = Ticket::query()
            ->where('status', $status)
            ->whereKeyNot($moved->getKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $ordered = $siblings->all();
        array_splice($ordered, min($position, count($ordered)), 0, [$moved]);

        foreach ($ordered as $index => $ticket) {
            if ($ticket->sort_order !== $index) {
                $ticket->sort_order = $index;
                $ticket->save();
            }
        }
    }
}
