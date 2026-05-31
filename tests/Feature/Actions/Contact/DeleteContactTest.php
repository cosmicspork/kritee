<?php

use App\Actions\Contact\DeleteContact;
use App\Actions\Contact\DeleteContactInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\ContactDeleted;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

test('an admin can delete a contact', function () {
    bindContactActor(User::factory()->admin()->create());
    $contact = Contact::factory()->create();
    $id = $contact->getKey();

    $result = app(DeleteContact::class)->execute(new DeleteContactInput(contactId: $id));

    expect($result->success)->toBeTrue();
    $this->assertDatabaseMissing('contacts', ['id' => $id]);
});

test('it dispatches ContactDeleted carrying the contact and client ids', function () {
    Event::fake();
    bindContactActor(User::factory()->admin()->create());
    $contact = Contact::factory()->create();
    $id = (int) $contact->getKey();
    $clientId = (int) $contact->client_id;

    app(DeleteContact::class)->execute(new DeleteContactInput(contactId: $id));

    Event::assertDispatched(
        ContactDeleted::class,
        fn (ContactDeleted $event): bool => $event->contactId === $id && $event->clientId === $clientId,
    );
});

test('a non-admin member may not delete a contact', function () {
    $member = User::factory()->create();
    app()->instance(Actor::class, new UserActor($member));
    $contact = Contact::factory()->create();

    $result = app(DeleteContact::class)->execute(new DeleteContactInput(contactId: $contact->getKey()));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseHas('contacts', ['id' => $contact->getKey()]);
});

test('deleting a missing contact fails validation', function () {
    bindContactActor(User::factory()->admin()->create());

    expect(fn () => DeleteContactInput::validateAndCreate([
        'contact_id' => 999999,
    ]))->toThrow(ValidationException::class);
});
