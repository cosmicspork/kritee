<?php

use App\Actions\Roadmap\ToggleRoadmapItemPublic;
use App\Actions\Roadmap\ToggleRoadmapItemPublicInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Events\RoadmapItemPublicityChanged;
use App\Models\RoadmapItem;
use Illuminate\Support\Facades\Event;

test('it sets the item public flag', function () {
    actAsUser();
    $item = RoadmapItem::factory()->create(['is_public' => false]);

    $result = app(ToggleRoadmapItemPublic::class)->execute(new ToggleRoadmapItemPublicInput(
        roadmapItemId: $item->getKey(),
        isPublic: true,
    ));

    expect($result->success)->toBeTrue()
        ->and($item->refresh()->is_public)->toBeTrue();
});

test('it can hide a previously public item', function () {
    actAsUser();
    $item = RoadmapItem::factory()->create(['is_public' => true]);

    app(ToggleRoadmapItemPublic::class)->execute(new ToggleRoadmapItemPublicInput(
        roadmapItemId: $item->getKey(),
        isPublic: false,
    ));

    expect($item->refresh()->is_public)->toBeFalse();
});

test('it dispatches RoadmapItemPublicityChanged on success', function () {
    Event::fake();
    actAsUser();
    $item = RoadmapItem::factory()->create(['is_public' => false]);

    app(ToggleRoadmapItemPublic::class)->execute(new ToggleRoadmapItemPublicInput(
        roadmapItemId: $item->getKey(),
        isPublic: true,
    ));

    Event::assertDispatched(
        RoadmapItemPublicityChanged::class,
        fn (RoadmapItemPublicityChanged $event): bool => $event->item->is($item),
    );
});

test('a non-user actor cannot change visibility', function () {
    app()->instance(Actor::class, new SystemActor);
    $item = RoadmapItem::factory()->create(['is_public' => false]);

    $result = app(ToggleRoadmapItemPublic::class)->execute(new ToggleRoadmapItemPublicInput(
        roadmapItemId: $item->getKey(),
        isPublic: true,
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor')
        ->and($item->refresh()->is_public)->toBeFalse();
});
