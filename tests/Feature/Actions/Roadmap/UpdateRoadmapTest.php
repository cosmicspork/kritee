<?php

use App\Actions\Roadmap\UpdateRoadmap;
use App\Actions\Roadmap\UpdateRoadmapInput;
use App\Events\RoadmapUpdated;
use App\Models\Client;
use App\Models\Roadmap;
use Illuminate\Support\Facades\Event;

test('it updates only the provided fields', function () {
    actAsUser();
    $client = Client::factory()->create();
    $roadmap = Roadmap::factory()->create(['title' => 'Old', 'description' => 'Keep me']);

    $result = app(UpdateRoadmap::class)->execute(new UpdateRoadmapInput(
        roadmapId: $roadmap->getKey(),
        title: 'New',
        clientId: $client->getKey(),
    ));

    expect($result->success)->toBeTrue();

    $roadmap->refresh();

    expect($roadmap->title)->toBe('New')
        ->and($roadmap->description)->toBe('Keep me')
        ->and($roadmap->client_id)->toBe($client->getKey());
});

test('it dispatches RoadmapUpdated on success', function () {
    Event::fake();
    actAsUser();
    $roadmap = Roadmap::factory()->create();

    app(UpdateRoadmap::class)->execute(new UpdateRoadmapInput(
        roadmapId: $roadmap->getKey(),
        title: 'Renamed',
    ));

    Event::assertDispatched(RoadmapUpdated::class, fn (RoadmapUpdated $event): bool => $event->roadmap->is($roadmap));
});

test('updating a missing roadmap fails', function () {
    actAsUser();

    $result = app(UpdateRoadmap::class)->execute(new UpdateRoadmapInput(
        roadmapId: 9999,
        title: 'Ghost',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('roadmap_id');
});
