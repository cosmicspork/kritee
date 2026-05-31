<?php

use App\Actions\Comment\AddComment;
use App\Actions\Comment\AddCommentInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Events\CommentAdded;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('a user adds a comment authored by themselves', function (): void {
    Event::fake();

    $user = actAsUser(User::factory()->create());
    $ticket = Ticket::factory()->create();

    $result = app(AddComment::class)->execute(new AddCommentInput(
        ticketId: $ticket->getKey(),
        content: 'First reply.',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(Comment::class)
        ->and($result->data->author_id)->toBe($user->getKey())
        ->and($result->data->ticket_id)->toBe($ticket->getKey())
        ->and($result->data->content)->toBe('First reply.');

    $this->assertDatabaseHas('comments', [
        'ticket_id' => $ticket->getKey(),
        'author_id' => $user->getKey(),
        'content' => 'First reply.',
    ]);
});

test('it dispatches CommentAdded on success', function (): void {
    Event::fake();

    actAsUser(User::factory()->create());
    $ticket = Ticket::factory()->create();

    $result = app(AddComment::class)->execute(new AddCommentInput(
        ticketId: $ticket->getKey(),
        content: 'Notify me.',
    ));

    Event::assertDispatched(
        CommentAdded::class,
        fn (CommentAdded $event): bool => $event->comment->is($result->data),
    );
});

test('a non-user actor cannot add a comment', function (): void {
    Event::fake();

    app()->instance(Actor::class, new SystemActor);
    $ticket = Ticket::factory()->create();

    $result = app(AddComment::class)->execute(new AddCommentInput(
        ticketId: $ticket->getKey(),
        content: 'From the void.',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    Event::assertNotDispatched(CommentAdded::class);
    $this->assertDatabaseCount('comments', 0);
});

test('a repeated idempotency key adds the comment only once', function (): void {
    Event::fake();

    actAsUser(User::factory()->create());
    $ticket = Ticket::factory()->create();

    $input = new AddCommentInput(
        ticketId: $ticket->getKey(),
        content: 'Only once.',
        idempotencyKey: 'add-comment-1',
    );

    $first = app(AddComment::class)->execute($input);
    $second = app(AddComment::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey());

    $this->assertDatabaseCount('comments', 1);
    Event::assertDispatchedTimes(CommentAdded::class, 1);
});
