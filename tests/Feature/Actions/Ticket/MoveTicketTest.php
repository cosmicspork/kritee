<?php

use App\Actions\Ticket\MoveTicket;
use App\Actions\Ticket\MoveTicketInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\TicketStatus;
use App\Events\TicketMoved;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('it changes the status column and inserts at the requested position', function () {
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    $existing = Ticket::factory()->count(2)->sequence(
        ['sort_order' => 0],
        ['sort_order' => 1],
    )->create([
        'creator_id' => $user->getKey(),
        'status' => TicketStatus::InProgress,
    ]);

    $moved = Ticket::factory()->create([
        'creator_id' => $user->getKey(),
        'status' => TicketStatus::Open,
        'sort_order' => 0,
    ]);

    $result = app(MoveTicket::class)->execute(new MoveTicketInput(
        ticketId: $moved->getKey(),
        status: TicketStatus::InProgress,
        sortOrder: 1,
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->status)->toBe(TicketStatus::InProgress)
        ->and($result->data->sort_order)->toBe(1);

    expect($existing[0]->fresh()->sort_order)->toBe(0)
        ->and($existing[1]->fresh()->sort_order)->toBe(2);
});

test('it dispatches the TicketMoved event with the status transition', function () {
    Event::fake([TicketMoved::class]);

    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    $ticket = Ticket::factory()->create([
        'creator_id' => $user->getKey(),
        'status' => TicketStatus::Open,
    ]);

    app(MoveTicket::class)->execute(new MoveTicketInput(
        ticketId: $ticket->getKey(),
        status: TicketStatus::Done,
    ));

    Event::assertDispatched(TicketMoved::class, function (TicketMoved $event) use ($ticket): bool {
        return $event->ticket->is($ticket)
            && $event->from === TicketStatus::Open
            && $event->to === TicketStatus::Done;
    });
});

test('it denies a user without update rights and fires no event', function () {
    Event::fake([TicketMoved::class]);

    $creator = User::factory()->create();
    $stranger = User::factory()->create();
    app()->instance(Actor::class, new UserActor($stranger));

    $ticket = Ticket::factory()->create([
        'creator_id' => $creator->getKey(),
        'status' => TicketStatus::Open,
    ]);

    $result = app(MoveTicket::class)->execute(new MoveTicketInput(
        ticketId: $ticket->getKey(),
        status: TicketStatus::Done,
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
    Event::assertNotDispatched(TicketMoved::class);
});
