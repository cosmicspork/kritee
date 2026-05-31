<?php

use App\Actions\TimeEntry\DeleteTimeEntry;
use App\Actions\TimeEntry\DeleteTimeEntryInput;
use App\Enums\UserRole;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('it deletes the owner\'s entry', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create();

    $result = app(DeleteTimeEntry::class)->execute(new DeleteTimeEntryInput(timeEntryId: $entry->getKey()));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBe(['id' => $entry->getKey()]);

    expect(TimeEntry::count())->toBe(0);
});

test('an admin may delete anyone\'s entry', function () {
    $owner = User::factory()->create();
    $entry = TimeEntry::factory()->for($owner)->create();

    actAsUser(User::factory()->create(['role' => UserRole::Admin]));

    $result = app(DeleteTimeEntry::class)->execute(new DeleteTimeEntryInput(timeEntryId: $entry->getKey()));

    expect($result->success)->toBeTrue();
    expect(TimeEntry::count())->toBe(0);
});

test('a user cannot delete another user\'s entry', function () {
    $owner = User::factory()->create();
    $entry = TimeEntry::factory()->for($owner)->create();

    actAsUser();

    $result = app(DeleteTimeEntry::class)->execute(new DeleteTimeEntryInput(timeEntryId: $entry->getKey()));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    expect(TimeEntry::count())->toBe(1);
});

test('a billed entry cannot be deleted', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create(['is_billed' => true]);

    $result = app(DeleteTimeEntry::class)->execute(new DeleteTimeEntryInput(timeEntryId: $entry->getKey()));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    expect(TimeEntry::count())->toBe(1);
});

test('deleting an unknown entry fails validation', function () {
    actAsUser();

    expect(fn () => DeleteTimeEntryInput::validateAndCreate(['time_entry_id' => 999999]))
        ->toThrow(ValidationException::class);
});

test('a repeated idempotency key deletes once', function () {
    $user = actAsUser();
    $entry = TimeEntry::factory()->for($user)->create();

    $input = new DeleteTimeEntryInput(timeEntryId: $entry->getKey(), idempotencyKey: 'delete-once');

    $first = app(DeleteTimeEntry::class)->execute($input);
    $second = app(DeleteTimeEntry::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data)->toBe($first->data);
});
