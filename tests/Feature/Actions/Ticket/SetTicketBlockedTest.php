<?php

use App\Actions\Ticket\SetTicketBlocked;
use App\Actions\Ticket\SetTicketBlockedInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Models\Ticket;
use App\Models\User;

test('it sets the blocked flag', function () {
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    $ticket = Ticket::factory()->create([
        'creator_id' => $user->getKey(),
        'is_blocked' => false,
    ]);

    $result = app(SetTicketBlocked::class)->execute(new SetTicketBlockedInput(
        ticketId: $ticket->getKey(),
        isBlocked: true,
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->is_blocked)->toBeTrue();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->getKey(),
        'is_blocked' => true,
    ]);
});

test('it clears the blocked flag', function () {
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    $ticket = Ticket::factory()->create([
        'creator_id' => $user->getKey(),
        'is_blocked' => true,
    ]);

    $result = app(SetTicketBlocked::class)->execute(new SetTicketBlockedInput(
        ticketId: $ticket->getKey(),
        isBlocked: false,
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->is_blocked)->toBeFalse();
});

test('it denies a user without update rights', function () {
    $creator = User::factory()->create();
    $stranger = User::factory()->create();
    app()->instance(Actor::class, new UserActor($stranger));

    $ticket = Ticket::factory()->create([
        'creator_id' => $creator->getKey(),
        'is_blocked' => false,
    ]);

    $result = app(SetTicketBlocked::class)->execute(new SetTicketBlockedInput(
        ticketId: $ticket->getKey(),
        isBlocked: true,
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    expect($ticket->fresh()->is_blocked)->toBeFalse();
});
