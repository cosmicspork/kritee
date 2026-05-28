<?php

use App\Actions\Ticket\UpdateTicket;
use App\Actions\Ticket\UpdateTicketInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\TicketPriority;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

test('it updates only the supplied attributes', function () {
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    $ticket = Ticket::factory()->create([
        'creator_id' => $user->getKey(),
        'title' => 'Original',
        'description' => 'Original description',
        'priority' => TicketPriority::Low,
    ]);

    $result = app(UpdateTicket::class)->execute(new UpdateTicketInput(
        ticketId: $ticket->getKey(),
        title: 'Renamed',
        priority: TicketPriority::Urgent,
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->title)->toBe('Renamed')
        ->and($result->data->priority)->toBe(TicketPriority::Urgent)
        ->and($result->data->description)->toBe('Original description');
});

test('it replaces project associations when projectIds is supplied', function () {
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    $ticket = Ticket::factory()->create(['creator_id' => $user->getKey()]);
    $original = Project::factory()->create();
    $ticket->projects()->attach($original);

    $replacement = Project::factory()->create();

    $result = app(UpdateTicket::class)->execute(new UpdateTicketInput(
        ticketId: $ticket->getKey(),
        projectIds: [$replacement->getKey()],
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->projects->modelKeys())->toBe([$replacement->getKey()]);
});

test('it denies a user who is neither admin, creator, nor assignee', function () {
    $creator = User::factory()->create();
    $stranger = User::factory()->create();
    app()->instance(Actor::class, new UserActor($stranger));

    $ticket = Ticket::factory()->create([
        'creator_id' => $creator->getKey(),
        'title' => 'Untouchable',
    ]);

    $result = app(UpdateTicket::class)->execute(new UpdateTicketInput(
        ticketId: $ticket->getKey(),
        title: 'Hijacked',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->getKey(),
        'title' => 'Untouchable',
    ]);
});
