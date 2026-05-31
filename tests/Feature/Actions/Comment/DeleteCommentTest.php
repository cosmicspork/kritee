<?php

use App\Actions\Comment\DeleteComment;
use App\Actions\Comment\DeleteCommentInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\CommentDeleted;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function bindUserActor(User $user): User
{
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('an author deletes their own comment', function (): void {
    Event::fake();

    $author = bindUserActor(User::factory()->create());
    $comment = Comment::factory()->for($author, 'author')->create();

    $result = app(DeleteComment::class)->execute(new DeleteCommentInput(
        commentId: $comment->getKey(),
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toMatchArray([
            'comment_id' => $comment->getKey(),
            'ticket_id' => $comment->ticket_id,
        ]);

    $this->assertDatabaseMissing('comments', ['id' => $comment->getKey()]);
});

test('an admin deletes another user\'s comment', function (): void {
    Event::fake();

    bindUserActor(User::factory()->admin()->create());
    $comment = Comment::factory()->create();

    $result = app(DeleteComment::class)->execute(new DeleteCommentInput(
        commentId: $comment->getKey(),
    ));

    expect($result->success)->toBeTrue();
    $this->assertDatabaseMissing('comments', ['id' => $comment->getKey()]);
});

test('a non-author non-admin cannot delete a comment', function (): void {
    Event::fake();

    bindUserActor(User::factory()->create());
    $comment = Comment::factory()->create();

    $result = app(DeleteComment::class)->execute(new DeleteCommentInput(
        commentId: $comment->getKey(),
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseHas('comments', ['id' => $comment->getKey()]);
    Event::assertNotDispatched(CommentDeleted::class);
});

test('it dispatches CommentDeleted on success', function (): void {
    Event::fake();

    $author = bindUserActor(User::factory()->create());
    $comment = Comment::factory()->for($author, 'author')->create();
    $ticketId = $comment->ticket_id;

    app(DeleteComment::class)->execute(new DeleteCommentInput(
        commentId: $comment->getKey(),
    ));

    Event::assertDispatched(
        CommentDeleted::class,
        fn (CommentDeleted $event): bool => $event->commentId === $comment->getKey()
            && $event->ticketId === $ticketId,
    );
});
