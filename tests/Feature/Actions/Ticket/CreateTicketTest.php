<?php

use App\Actions\Ticket\CreateTicket;
use App\Actions\Ticket\CreateTicketInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

test('it creates a ticket with a generated key and the actor as creator', function () {
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    $result = app(CreateTicket::class)->execute(new CreateTicketInput(
        title: 'Ship the kanban board',
        priority: TicketPriority::High,
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(Ticket::class)
        ->and($result->data->key)->toBe('TK-1')
        ->and($result->data->creator_id)->toBe($user->getKey())
        ->and($result->data->status)->toBe(TicketStatus::Open)
        ->and($result->data->priority)->toBe(TicketPriority::High);

    $this->assertDatabaseHas('tickets', [
        'title' => 'Ship the kanban board',
        'creator_id' => $user->getKey(),
    ]);
});

test('it syncs the supplied projects to the pivot', function () {
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    $projects = Project::factory()->count(2)->create();

    $result = app(CreateTicket::class)->execute(new CreateTicketInput(
        title: 'Linked ticket',
        projectIds: $projects->modelKeys(),
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->projects)->toHaveCount(2);

    foreach ($projects as $project) {
        $this->assertDatabaseHas('ticket_project', [
            'ticket_id' => $result->data->getKey(),
            'project_id' => $project->getKey(),
        ]);
    }
});

test('it fails when no user actor is present', function () {
    app()->instance(Actor::class, new SystemActor);

    $result = app(CreateTicket::class)->execute(new CreateTicketInput(title: 'System ticket'));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    $this->assertDatabaseCount('tickets', 0);
});

test('a repeated idempotency key short-circuits to a single create', function () {
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    $input = new CreateTicketInput(title: 'Once only', idempotencyKey: 'create-ticket-1');

    $first = app(CreateTicket::class)->execute($input);
    $second = app(CreateTicket::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey());

    $this->assertDatabaseCount('tickets', 1);
});
