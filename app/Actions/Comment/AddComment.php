<?php

namespace App\Actions\Comment;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\CommentAdded;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AddComment implements Action
{
    use EnsuresIdempotency;

    public function __construct(private readonly Actor $actor) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof AddCommentInput) {
            throw new InvalidArgumentException(self::class.' requires an '.AddCommentInput::class.'.');
        }

        if (! $this->actor instanceof UserActor) {
            return ActionResult::failure([
                'actor' => 'A comment must be authored by a user.',
            ]);
        }

        $author = $this->actor->user();
        $ticket = Ticket::query()->findOrFail($input->ticketId);

        if ($author->cannot('create', [Comment::class, $ticket])) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to comment on this ticket.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($author, $ticket, $input): ActionResult {
            $comment = DB::transaction(fn (): Comment => Comment::create([
                'ticket_id' => $ticket->getKey(),
                'author_id' => $author->getKey(),
                'content' => $input->content,
            ]));

            CommentAdded::dispatch($comment);

            return ActionResult::success($comment);
        });
    }
}
