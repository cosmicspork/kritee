<?php

use App\Actions\TimeEntry\StartTimer;
use App\Actions\TimeEntry\StartTimerInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Events\TimeEntryRecorded;
use App\Models\Client;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

test('it opens an entry owned by the acting user with no end time', function () {
    $user = actAsUser();

    $result = app(StartTimer::class)->execute(new StartTimerInput(description: 'Investigating a bug'));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(TimeEntry::class)
        ->and($result->data->user_id)->toBe($user->getKey())
        ->and($result->data->started_at)->not->toBeNull()
        ->and($result->data->ended_at)->toBeNull()
        ->and($result->data->duration_minutes)->toBe(0)
        ->and($result->data->billed_rate)->toBeNull();

    expect(TimeEntry::count())->toBe(1);
});

test('it resolves the client from the ticket when none is given', function () {
    actAsUser();
    $client = Client::factory()->create();
    $ticket = Ticket::factory()->create(['client_id' => $client->getKey()]);

    $result = app(StartTimer::class)->execute(new StartTimerInput(ticketId: $ticket->getKey()));

    expect($result->success)->toBeTrue()
        ->and($result->data->client_id)->toBe($client->getKey());
});

test('it resolves the client from the project when the ticket carries none', function () {
    actAsUser();
    $client = Client::factory()->create();
    $project = Project::factory()->for($client)->create();

    $result = app(StartTimer::class)->execute(new StartTimerInput(projectId: $project->getKey()));

    expect($result->success)->toBeTrue()
        ->and($result->data->client_id)->toBe($client->getKey());
});

test('an explicit client id wins over the relational context', function () {
    actAsUser();
    $ticketClient = Client::factory()->create();
    $explicitClient = Client::factory()->create();
    $ticket = Ticket::factory()->create(['client_id' => $ticketClient->getKey()]);

    $result = app(StartTimer::class)->execute(new StartTimerInput(
        ticketId: $ticket->getKey(),
        clientId: $explicitClient->getKey(),
    ));

    expect($result->data->client_id)->toBe($explicitClient->getKey());
});

test('it dispatches TimeEntryRecorded on success', function () {
    Event::fake();
    actAsUser();

    $result = app(StartTimer::class)->execute(new StartTimerInput);

    expect($result->success)->toBeTrue();
    Event::assertDispatched(
        TimeEntryRecorded::class,
        fn (TimeEntryRecorded $event): bool => $event->timeEntry->is($result->data),
    );
});

test('a non-user actor cannot start a timer', function () {
    app()->instance(Actor::class, new SystemActor);

    $result = app(StartTimer::class)->execute(new StartTimerInput);

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    expect(TimeEntry::count())->toBe(0);
});

test('a missing ticket fails validation', function () {
    actAsUser();

    expect(fn () => StartTimerInput::validateAndCreate(['ticket_id' => 999999]))
        ->toThrow(ValidationException::class);
});

test('a repeated idempotency key opens a single entry', function () {
    actAsUser();

    $input = new StartTimerInput(idempotencyKey: 'start-once');

    $first = app(StartTimer::class)->execute($input);
    $second = app(StartTimer::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey())
        ->and(TimeEntry::count())->toBe(1);
});
