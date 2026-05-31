<?php

use App\Enums\ClientStatus;
use App\Events\ClientArchived;
use App\Events\ClientCreated;
use App\Events\ClientUpdated;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    actAsUser($this->user);
});

test('the clients index lists active clients and hides archived ones by default', function () {
    $active = Client::factory()->create(['name' => 'Acme Industries']);
    $archived = Client::factory()->archived()->create(['name' => 'Defunct Co']);

    Livewire::test('pages::clients.index')
        ->assertOk()
        ->assertSee('Acme Industries')
        ->assertDontSee('Defunct Co')
        ->set('showArchived', true)
        ->assertSee('Defunct Co');
});

test('creating a client goes through the CreateClient action', function () {
    Event::fake([ClientCreated::class]);

    Livewire::test('pages::clients.index')
        ->call('create')
        ->set('name', 'Globex')
        ->set('email', 'hello@globex.test')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showFormModal', false);

    $this->assertDatabaseHas('clients', [
        'name' => 'Globex',
        'email' => 'hello@globex.test',
        'status' => ClientStatus::Active->value,
    ]);

    Event::assertDispatched(ClientCreated::class);
});

test('a blank name surfaces a validation error and creates nothing', function () {
    Livewire::test('pages::clients.index')
        ->call('create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => ['required']])
        ->assertSet('showFormModal', true);

    expect(Client::count())->toBe(0);
});

test('editing a client goes through the UpdateClient action', function () {
    Event::fake([ClientUpdated::class]);
    $client = Client::factory()->create(['name' => 'Initech']);

    Livewire::test('pages::clients.index')
        ->call('edit', $client->id)
        ->assertSet('name', 'Initech')
        ->set('name', 'Initech LLC')
        ->call('save')
        ->assertHasNoErrors();

    expect($client->refresh()->name)->toBe('Initech LLC');

    Event::assertDispatched(ClientUpdated::class);
});

test('archiving a client goes through the ArchiveClient action', function () {
    Event::fake([ClientArchived::class]);
    $client = Client::factory()->create();

    Livewire::test('pages::clients.index')
        ->call('archive', $client->id);

    expect($client->refresh()->status)->toBe(ClientStatus::Archived);

    Event::assertDispatched(ClientArchived::class);
});

test('a guest is redirected away from the clients index', function () {
    auth()->logout();

    $this->get(route('clients.index'))->assertRedirect(route('login'));
})->skip(fn () => ! Route::has('clients.index'), 'Route wired in a later step.');
