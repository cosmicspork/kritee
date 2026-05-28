<?php

use App\Actions\Client\CreateClient;
use App\Actions\Client\CreateClientInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\ClientStatus;
use App\Events\ClientCreated;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function bindCreateClientActor(?User $user = null): User
{
    $user ??= User::factory()->create();

    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('a member creates an active client with a generated slug', function () {
    bindCreateClientActor();

    $result = app(CreateClient::class)->execute(new CreateClientInput(
        name: 'Acme Industries',
        email: 'hello@acme.test',
    ));

    expect($result->success)->toBeTrue();

    $client = $result->data;

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->slug)->toBe('acme-industries')
        ->and($client->status)->toBe(ClientStatus::Active)
        ->and($client->email)->toBe('hello@acme.test');

    $this->assertDatabaseHas('clients', [
        'name' => 'Acme Industries',
        'slug' => 'acme-industries',
        'status' => ClientStatus::Active->value,
    ]);
});

test('the generated slug avoids collisions', function () {
    bindCreateClientActor();
    Client::factory()->create(['slug' => 'acme']);

    $result = app(CreateClient::class)->execute(new CreateClientInput(name: 'Acme'));

    expect($result->data->slug)->toBe('acme-2');
});

test('creating a client dispatches ClientCreated', function () {
    Event::fake();
    bindCreateClientActor();

    $result = app(CreateClient::class)->execute(new CreateClientInput(name: 'Globex'));

    Event::assertDispatched(ClientCreated::class, function (ClientCreated $event) use ($result) {
        return $event->client->is($result->data);
    });
});

test('a blank name fails DTO validation', function () {
    CreateClientInput::validate(['name' => '']);
})->throws(ValidationException::class);

test('an unauthorized user cannot create a client', function () {
    bindCreateClientActor();
    Gate::policy(Client::class, DenyClientPolicy::class);

    $result = app(CreateClient::class)->execute(new CreateClientInput(name: 'Initech'));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseMissing('clients', ['name' => 'Initech']);
});

test('a repeated idempotency key short-circuits and creates one client', function () {
    bindCreateClientActor();
    $key = (string) Str::uuid();

    $first = app(CreateClient::class)->execute(new CreateClientInput(name: 'Soylent', idempotencyKey: $key));
    $second = app(CreateClient::class)->execute(new CreateClientInput(name: 'Soylent', idempotencyKey: $key));

    expect($second)->toEqual($first)
        ->and(Client::query()->where('name', 'Soylent')->count())->toBe(1);
});

class DenyClientPolicy
{
    public function create(User $user): bool
    {
        return false;
    }
}
