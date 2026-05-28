<?php

use App\Actions\TimeEntry\UpdateTimeEntry;
use App\Actions\TimeEntry\UpdateTimeEntryInput;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('it edits only the supplied fields', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create([
        'description' => 'Original',
        'duration_minutes' => 30,
        'is_billable' => true,
    ]);

    $result = app(UpdateTimeEntry::class)->execute(new UpdateTimeEntryInput(
        timeEntryId: $entry->getKey(),
        description: 'Revised',
        durationMinutes: 75,
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->description)->toBe('Revised')
        ->and($result->data->duration_minutes)->toBe(75)
        ->and($result->data->is_billable)->toBeTrue();
});

test('it re-resolves the client when the project changes', function () {
    $user = actAsUser();
    $originalClient = Client::factory()->create();
    $entry = TimeEntry::factory()->for($user)->create(['client_id' => $originalClient->getKey()]);

    $newClient = Client::factory()->create();
    $project = Project::factory()->for($newClient)->create();

    $result = app(UpdateTimeEntry::class)->execute(new UpdateTimeEntryInput(
        timeEntryId: $entry->getKey(),
        projectId: $project->getKey(),
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->project_id)->toBe($project->getKey())
        ->and($result->data->client_id)->toBe($newClient->getKey());
});

test('an explicit null clears a nullable column', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create(['description' => 'Has text']);

    $result = app(UpdateTimeEntry::class)->execute(new UpdateTimeEntryInput(
        timeEntryId: $entry->getKey(),
        description: null,
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->description)->toBeNull();
});

test('a user cannot edit another user\'s entry', function () {
    $owner = User::factory()->create();
    $entry = TimeEntry::factory()->for($owner)->create();

    actAsUser();

    $result = app(UpdateTimeEntry::class)->execute(new UpdateTimeEntryInput(
        timeEntryId: $entry->getKey(),
        description: 'Tampering',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');
});

test('a billed entry cannot be edited', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create(['is_billed' => true]);

    $result = app(UpdateTimeEntry::class)->execute(new UpdateTimeEntryInput(
        timeEntryId: $entry->getKey(),
        description: 'Late change',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');
});

test('an unknown ticket fails validation', function () {
    actAsUser();
    $entry = TimeEntry::factory()->create();

    expect(fn () => UpdateTimeEntryInput::validateAndCreate([
        'time_entry_id' => $entry->getKey(),
        'ticket_id' => 999999,
    ]))->toThrow(ValidationException::class);
});

test('a repeated idempotency key applies the edit once', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create(['duration_minutes' => 10]);

    $input = new UpdateTimeEntryInput(
        timeEntryId: $entry->getKey(),
        durationMinutes: 99,
        idempotencyKey: 'update-once',
    );

    $first = app(UpdateTimeEntry::class)->execute($input);
    $second = app(UpdateTimeEntry::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->duration_minutes)->toBe(99);
});
