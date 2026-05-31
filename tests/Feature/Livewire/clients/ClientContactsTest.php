<?php

use App\Events\ContactCreated;
use App\Events\ContactDeleted;
use App\Events\ContactUpdated;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->client = Client::factory()->create(['name' => 'Acme Industries']);
});

function actAsClientMember(): User
{
    $user = User::factory()->create();
    test()->actingAs($user);
    actAsUser($user);

    return $user;
}

test('the show page lists a client\'s contacts', function () {
    actAsClientMember();
    $contact = Contact::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'Jane Doe',
    ]);
    Contact::factory()->create(['name' => 'Other Client Person']);

    Livewire::test('pages::clients.show', ['client' => $this->client])
        ->assertOk()
        ->assertSee('Jane Doe')
        ->assertDontSee('Other Client Person');
});

test('adding a contact goes through the CreateContact action', function () {
    actAsClientMember();
    Event::fake([ContactCreated::class]);

    Livewire::test('pages::clients.show', ['client' => $this->client])
        ->call('create')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@acme.test')
        ->set('isPrimary', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showFormModal', false);

    $this->assertDatabaseHas('contacts', [
        'client_id' => $this->client->id,
        'name' => 'Jane Doe',
        'email' => 'jane@acme.test',
        'is_primary' => true,
    ]);

    Event::assertDispatched(ContactCreated::class);
});

test('a blank contact name surfaces a validation error', function () {
    actAsClientMember();

    Livewire::test('pages::clients.show', ['client' => $this->client])
        ->call('create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => ['required']]);

    expect(Contact::count())->toBe(0);
});

test('editing a contact goes through the UpdateContact action', function () {
    actAsClientMember();
    Event::fake([ContactUpdated::class]);
    $contact = Contact::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'Jane Doe',
    ]);

    Livewire::test('pages::clients.show', ['client' => $this->client])
        ->call('edit', $contact->id)
        ->assertSet('name', 'Jane Doe')
        ->set('name', 'Jane Smith')
        ->call('save')
        ->assertHasNoErrors();

    expect($contact->refresh()->name)->toBe('Jane Smith');

    Event::assertDispatched(ContactUpdated::class);
});

test('an admin deletes a contact through the DeleteContact action', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    actAsUser($admin);

    Event::fake([ContactDeleted::class]);
    $contact = Contact::factory()->create(['client_id' => $this->client->id]);

    Livewire::test('pages::clients.show', ['client' => $this->client])
        ->call('delete', $contact->id);

    $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);

    Event::assertDispatched(ContactDeleted::class);
});

test('a member cannot delete a contact and the action surfaces an authorization failure', function () {
    actAsClientMember();
    $contact = Contact::factory()->create(['client_id' => $this->client->id]);

    Livewire::test('pages::clients.show', ['client' => $this->client])
        ->call('delete', $contact->id);

    $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
});
