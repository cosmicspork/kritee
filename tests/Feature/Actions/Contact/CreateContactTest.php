<?php

use App\Actions\Contact\CreateContact;
use App\Actions\Contact\CreateContactInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Events\ContactCreated;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

function bindContactActor(?User $user = null): User
{
    $user ??= User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('it creates a contact for the client', function () {
    bindContactActor();
    $client = Client::factory()->create();

    $result = app(CreateContact::class)->execute(new CreateContactInput(
        clientId: $client->getKey(),
        name: 'Ada Lovelace',
        email: 'ada@example.com',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(Contact::class)
        ->and($result->data->name)->toBe('Ada Lovelace');

    $this->assertDatabaseHas('contacts', [
        'client_id' => $client->getKey(),
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);
});

test('setting a new primary contact unsets the client\'s other primaries', function () {
    bindContactActor();
    $client = Client::factory()->create();
    $existing = Contact::factory()->for($client)->create(['is_primary' => true]);

    $result = app(CreateContact::class)->execute(new CreateContactInput(
        clientId: $client->getKey(),
        name: 'New Primary',
        isPrimary: true,
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->is_primary)->toBeTrue()
        ->and($existing->fresh()->is_primary)->toBeFalse();
});

test('a primary contact for one client leaves another client untouched', function () {
    bindContactActor();
    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();
    $otherPrimary = Contact::factory()->for($clientB)->create(['is_primary' => true]);

    app(CreateContact::class)->execute(new CreateContactInput(
        clientId: $clientA->getKey(),
        name: 'A Primary',
        isPrimary: true,
    ));

    expect($otherPrimary->fresh()->is_primary)->toBeTrue();
});

test('it dispatches ContactCreated on success', function () {
    Event::fake();
    bindContactActor();
    $client = Client::factory()->create();

    app(CreateContact::class)->execute(new CreateContactInput(
        clientId: $client->getKey(),
        name: 'Grace Hopper',
    ));

    Event::assertDispatched(ContactCreated::class, fn (ContactCreated $event): bool => $event->contact->name === 'Grace Hopper');
});

test('a system actor may not create a contact', function () {
    app()->instance(Actor::class, new SystemActor);
    $client = Client::factory()->create();

    $result = app(CreateContact::class)->execute(new CreateContactInput(
        clientId: $client->getKey(),
        name: 'Denied',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    $this->assertDatabaseMissing('contacts', ['name' => 'Denied']);
});

test('validation rejects a missing client', function () {
    bindContactActor();

    expect(fn () => CreateContactInput::validateAndCreate([
        'client_id' => 999999,
        'name' => 'Orphan',
    ]))->toThrow(ValidationException::class);
});

test('a repeated idempotency key short-circuits the second create', function () {
    Event::fake();
    bindContactActor();
    $client = Client::factory()->create();

    $input = new CreateContactInput(
        clientId: $client->getKey(),
        name: 'Once Only',
        idempotencyKey: 'contact-create-1',
    );

    $first = app(CreateContact::class)->execute($input);
    $second = app(CreateContact::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and(Contact::query()->where('name', 'Once Only')->count())->toBe(1);

    Event::assertDispatchedTimes(ContactCreated::class, 1);
});
