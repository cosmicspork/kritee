<?php

use App\Actions\TimeEntry\StopTimer;
use App\Actions\TimeEntry\StopTimerInput;
use App\Events\TimeEntryRecorded;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

test('it closes an open entry and derives the duration', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => CarbonImmutable::parse('2026-05-28 09:00:00'),
        'ended_at' => null,
        'duration_minutes' => 0,
    ]);

    $result = app(StopTimer::class)->execute(new StopTimerInput(
        timeEntryId: $entry->getKey(),
        endedAt: '2026-05-28 10:30:00',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->ended_at->toDateTimeString())->toBe('2026-05-28 10:30:00')
        ->and($result->data->duration_minutes)->toBe(90);
});

test('it does not re-dispatch TimeEntryRecorded when stopping', function () {
    Event::fake();
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => CarbonImmutable::now()->subHour(),
        'ended_at' => null,
    ]);

    app(StopTimer::class)->execute(new StopTimerInput(timeEntryId: $entry->getKey()));

    Event::assertNotDispatched(TimeEntryRecorded::class);
});

test('an already-stopped timer cannot be stopped again', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => CarbonImmutable::now()->subHour(),
        'ended_at' => CarbonImmutable::now(),
    ]);

    $result = app(StopTimer::class)->execute(new StopTimerInput(timeEntryId: $entry->getKey()));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('ended_at');
});

test('a user cannot stop another user\'s timer', function () {
    $owner = User::factory()->create();
    $entry = TimeEntry::factory()->for($owner)->create(['ended_at' => null]);

    actAsUser();

    $result = app(StopTimer::class)->execute(new StopTimerInput(timeEntryId: $entry->getKey()));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');
});

test('a stop time before the start time fails', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => CarbonImmutable::parse('2026-05-28 12:00:00'),
        'ended_at' => null,
    ]);

    $result = app(StopTimer::class)->execute(new StopTimerInput(
        timeEntryId: $entry->getKey(),
        endedAt: '2026-05-28 11:00:00',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('ended_at');
});

test('stopping an unknown entry fails validation', function () {
    actAsUser();

    expect(fn () => StopTimerInput::validateAndCreate(['time_entry_id' => 999999]))
        ->toThrow(ValidationException::class);
});
