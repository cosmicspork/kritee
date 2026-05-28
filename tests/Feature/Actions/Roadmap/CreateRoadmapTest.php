<?php

use App\Actions\Roadmap\CreateRoadmap;
use App\Actions\Roadmap\CreateRoadmapInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Enums\RoadmapStatus;
use App\Events\RoadmapCreated;
use App\Models\Client;
use App\Models\Roadmap;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

test('it creates an active roadmap', function () {
    actAsUser();
    $client = Client::factory()->create();

    $result = app(CreateRoadmap::class)->execute(new CreateRoadmapInput(
        title: 'Q3 Delivery',
        description: 'Quarterly plan',
        clientId: $client->getKey(),
        isPublic: true,
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(Roadmap::class)
        ->and($result->data->status)->toBe(RoadmapStatus::Active)
        ->and($result->data->is_public)->toBeTrue()
        ->and($result->data->client_id)->toBe($client->getKey());

    $this->assertDatabaseHas('roadmaps', [
        'title' => 'Q3 Delivery',
        'client_id' => $client->getKey(),
        'is_public' => true,
    ]);
});

test('it dispatches RoadmapCreated on success', function () {
    Event::fake();
    actAsUser();

    $result = app(CreateRoadmap::class)->execute(new CreateRoadmapInput(title: 'Plan'));

    expect($result->success)->toBeTrue();
    Event::assertDispatched(RoadmapCreated::class, fn (RoadmapCreated $event): bool => $event->roadmap->is($result->data));
});

test('a non-user actor cannot create a roadmap', function () {
    app()->instance(Actor::class, new SystemActor);

    $result = app(CreateRoadmap::class)->execute(new CreateRoadmapInput(title: 'Plan'));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    expect(Roadmap::count())->toBe(0);
});

test('a blank title fails validation', function () {
    actAsUser();

    expect(fn () => CreateRoadmapInput::validateAndCreate(['title' => '']))
        ->toThrow(ValidationException::class);
});

test('a repeated idempotency key returns the original roadmap without creating a second', function () {
    actAsUser();

    $input = new CreateRoadmapInput(title: 'Once', idempotencyKey: 'roadmap-create-1');

    $first = app(CreateRoadmap::class)->execute($input);
    $second = app(CreateRoadmap::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey())
        ->and(Roadmap::count())->toBe(1);
});
