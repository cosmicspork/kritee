<?php

use App\Actions\Client\UpdateClient;
use App\Actions\Client\UpdateClientInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\ClientUpdated;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

function bindUpdateClientActor(?User $user = null): User
{
    $user ??= User::factory()->create();

    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('updating a client persists changes and re-slugs on rename', function () {
    bindUpdateClientActor();
    $client = Client::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    $result = app(UpdateClient::class)->execute(new UpdateClientInput(
        clientId: $client->id,
        name: 'New Name',
        email: 'new@client.test',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->name)->toBe('New Name')
        ->and($result->data->slug)->toBe('new-name')
        ->and($result->data->email)->toBe('new@client.test');

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'name' => 'New Name',
        'slug' => 'new-name',
    ]);
});

test('the slug is left untouched when the name is unchanged', function () {
    bindUpdateClientActor();
    $client = Client::factory()->create(['name' => 'Stable', 'slug' => 'custom-slug']);

    $result = app(UpdateClient::class)->execute(new UpdateClientInput(
        clientId: $client->id,
        name: 'Stable',
        phone: '555-0100',
    ));

    expect($result->data->slug)->toBe('custom-slug')
        ->and($result->data->phone)->toBe('555-0100');
});

test('updating a client dispatches ClientUpdated', function () {
    Event::fake();
    bindUpdateClientActor();
    $client = Client::factory()->create();

    app(UpdateClient::class)->execute(new UpdateClientInput(
        clientId: $client->id,
        name: 'Renamed',
    ));

    Event::assertDispatched(ClientUpdated::class, function (ClientUpdated $event) use ($client) {
        return $event->client->is($client);
    });
});

test('an unknown client id fails DTO validation', function () {
    UpdateClientInput::validate(['client_id' => 999999, 'name' => 'Ghost']);
})->throws(ValidationException::class);

test('an unauthorized user cannot update a client', function () {
    bindUpdateClientActor();
    Gate::policy(Client::class, DenyUpdateClientPolicy::class);
    $client = Client::factory()->create(['name' => 'Untouched']);

    $result = app(UpdateClient::class)->execute(new UpdateClientInput(
        clientId: $client->id,
        name: 'Hijacked',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Untouched']);
});

class DenyUpdateClientPolicy
{
    public function update(User $user, Client $client): bool
    {
        return false;
    }
}
