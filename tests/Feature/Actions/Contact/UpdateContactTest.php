<?php

use App\Actions\Contact\UpdateContact;
use App\Actions\Contact\UpdateContactInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Events\ContactUpdated;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

test('it updates only the supplied fields', function () {
    bindContactActor();
    $contact = Contact::factory()->create([
        'name' => 'Old Name',
        'title' => 'Engineer',
    ]);

    $result = app(UpdateContact::class)->execute(new UpdateContactInput(
        contactId: $contact->getKey(),
        name: 'New Name',
    ));

    expect($result->success)->toBeTrue();

    $fresh = $contact->fresh();
    expect($fresh->name)->toBe('New Name')
        ->and($fresh->title)->toBe('Engineer');
});

test('promoting a contact to primary unsets the client\'s other primaries', function () {
    bindContactActor();
    $client = Client::factory()->create();
    $existing = Contact::factory()->for($client)->create(['is_primary' => true]);
    $contact = Contact::factory()->for($client)->create(['is_primary' => false]);

    $result = app(UpdateContact::class)->execute(new UpdateContactInput(
        contactId: $contact->getKey(),
        isPrimary: true,
    ));

    expect($result->success)->toBeTrue()
        ->and($contact->fresh()->is_primary)->toBeTrue()
        ->and($existing->fresh()->is_primary)->toBeFalse();
});

test('it dispatches ContactUpdated on success', function () {
    Event::fake();
    bindContactActor();
    $contact = Contact::factory()->create();

    app(UpdateContact::class)->execute(new UpdateContactInput(
        contactId: $contact->getKey(),
        name: 'Changed',
    ));

    Event::assertDispatched(ContactUpdated::class, fn (ContactUpdated $event): bool => $event->contact->is($contact));
});

test('a system actor may not update a contact', function () {
    $contact = Contact::factory()->create(['name' => 'Untouched']);
    app()->instance(Actor::class, new SystemActor);

    $result = app(UpdateContact::class)->execute(new UpdateContactInput(
        contactId: $contact->getKey(),
        name: 'Hacked',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor')
        ->and($contact->fresh()->name)->toBe('Untouched');
});

test('updating a missing contact fails validation', function () {
    bindContactActor();

    expect(fn () => UpdateContactInput::validateAndCreate([
        'contact_id' => 999999,
        'name' => 'Ghost',
    ]))->toThrow(ValidationException::class);
});
