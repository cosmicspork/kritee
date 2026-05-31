<?php

namespace App\Actions\Comment;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\CommentDeleted;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeleteComment implements Action
{
    use EnsuresIdempotency;

    public function __construct(private readonly Actor $actor) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof DeleteCommentInput) {
            throw new InvalidArgumentException(self::class.' requires a '.DeleteCommentInput::class.'.');
        }

        if (! $this->actor instanceof UserActor) {
            return ActionResult::failure([
                'actor' => 'A comment may only be deleted by a user.',
            ]);
        }

        $user = $this->actor->user();
        $comment = Comment::query()->findOrFail($input->commentId);

        if ($user->cannot('delete', $comment)) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to delete this comment.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($comment): ActionResult {
            $commentId = (int) $comment->getKey();
            $ticketId = (int) $comment->ticket_id;

            DB::transaction(fn () => $comment->delete());

            CommentDeleted::dispatch($commentId, $ticketId);

            return ActionResult::success(['comment_id' => $commentId, 'ticket_id' => $ticketId]);
        });
    }
}
