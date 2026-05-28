<?php

use App\Actions\TimeEntry\RecordManualTimeEntry;
use App\Actions\TimeEntry\RecordManualTimeEntryInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Events\TimeEntryRecorded;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

test('it logs a completed entry using the supplied duration', function () {
    $user = actAsUser();

    $result = app(RecordManualTimeEntry::class)->execute(new RecordManualTimeEntryInput(
        durationMinutes: 45,
        description: 'Phone call',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(TimeEntry::class)
        ->and($result->data->user_id)->toBe($user->getKey())
        ->and($result->data->duration_minutes)->toBe(45)
        ->and($result->data->started_at)->toBeNull()
        ->and($result->data->ended_at)->toBeNull()
        ->and($result->data->billed_rate)->toBeNull();
});

test('it keeps the supplied timestamps without deriving the duration from them', function () {
    actAsUser();

    $result = app(RecordManualTimeEntry::class)->execute(new RecordManualTimeEntryInput(
        durationMinutes: 30,
        startedAt: '2026-05-28 09:00:00',
        endedAt: '2026-05-28 12:00:00',
    ));

    expect($result->data->duration_minutes)->toBe(30)
        ->and($result->data->started_at->toDateTimeString())->toBe('2026-05-28 09:00:00')
        ->and($result->data->ended_at->toDateTimeString())->toBe('2026-05-28 12:00:00');
});

test('it resolves the client from the project', function () {
    actAsUser();
    $client = Client::factory()->create();
    $project = Project::factory()->for($client)->create();

    $result = app(RecordManualTimeEntry::class)->execute(new RecordManualTimeEntryInput(
        durationMinutes: 60,
        projectId: $project->getKey(),
    ));

    expect($result->data->client_id)->toBe($client->getKey());
});

test('it dispatches TimeEntryRecorded on success', function () {
    Event::fake();
    actAsUser();

    $result = app(RecordManualTimeEntry::class)->execute(new RecordManualTimeEntryInput(durationMinutes: 15));

    expect($result->success)->toBeTrue();
    Event::assertDispatched(
        TimeEntryRecorded::class,
        fn (TimeEntryRecorded $event): bool => $event->timeEntry->is($result->data),
    );
});

test('a non-user actor cannot record time', function () {
    app()->instance(Actor::class, new SystemActor);

    $result = app(RecordManualTimeEntry::class)->execute(new RecordManualTimeEntryInput(durationMinutes: 15));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    expect(TimeEntry::count())->toBe(0);
});

test('a non-positive duration fails validation', function () {
    actAsUser();

    expect(fn () => RecordManualTimeEntryInput::validateAndCreate(['duration_minutes' => 0]))
        ->toThrow(ValidationException::class);
});

test('a repeated idempotency key records a single entry', function () {
    actAsUser();

    $input = new RecordManualTimeEntryInput(durationMinutes: 20, idempotencyKey: 'record-once');

    $first = app(RecordManualTimeEntry::class)->execute($input);
    $second = app(RecordManualTimeEntry::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey())
        ->and(TimeEntry::count())->toBe(1);
});
