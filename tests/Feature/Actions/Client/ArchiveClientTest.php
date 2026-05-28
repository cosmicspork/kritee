<?php

use App\Actions\Client\ArchiveClient;
use App\Actions\Client\ArchiveClientInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\ClientStatus;
use App\Events\ClientArchived;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

function bindArchiveClientActor(?User $user = null): User
{
    $user ??= User::factory()->create();

    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('archiving an active client sets its status', function () {
    bindArchiveClientActor();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    $result = app(ArchiveClient::class)->execute(new ArchiveClientInput(clientId: $client->id));

    expect($result->success)->toBeTrue()
        ->and($result->data->status)->toBe(ClientStatus::Archived);

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'status' => ClientStatus::Archived->value,
    ]);
});

test('archiving dispatches ClientArchived', function () {
    Event::fake();
    bindArchiveClientActor();
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    app(ArchiveClient::class)->execute(new ArchiveClientInput(clientId: $client->id));

    Event::assertDispatched(ClientArchived::class, function (ClientArchived $event) use ($client) {
        return $event->client->is($client);
    });
});

test('archiving an already-archived client is a no-op event-wise', function () {
    Event::fake();
    bindArchiveClientActor();
    $client = Client::factory()->archived()->create();

    $result = app(ArchiveClient::class)->execute(new ArchiveClientInput(clientId: $client->id));

    expect($result->success)->toBeTrue()
        ->and($result->data->status)->toBe(ClientStatus::Archived);

    Event::assertNotDispatched(ClientArchived::class);
});

test('an unauthorized user cannot archive a client', function () {
    bindArchiveClientActor();
    Gate::policy(Client::class, DenyArchiveClientPolicy::class);
    $client = Client::factory()->create(['status' => ClientStatus::Active]);

    $result = app(ArchiveClient::class)->execute(new ArchiveClientInput(clientId: $client->id));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'status' => ClientStatus::Active->value,
    ]);
});

class DenyArchiveClientPolicy
{
    public function archive(User $user, Client $client): bool
    {
        return false;
    }
}
