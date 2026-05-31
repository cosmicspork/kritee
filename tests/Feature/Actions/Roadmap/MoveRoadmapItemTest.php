<?php

use App\Actions\Roadmap\MoveRoadmapItem;
use App\Actions\Roadmap\MoveRoadmapItemInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Events\RoadmapItemMoved;
use App\Models\Roadmap;
use App\Models\RoadmapItem;
use Illuminate\Support\Facades\Event;

test('it moves an item to a new position and renumbers its siblings', function () {
    actAsUser();
    $roadmap = Roadmap::factory()->create();

    $first = RoadmapItem::factory()->for($roadmap)->create(['sort_order' => 0]);
    $second = RoadmapItem::factory()->for($roadmap)->create(['sort_order' => 1]);
    $third = RoadmapItem::factory()->for($roadmap)->create(['sort_order' => 2]);

    $result = app(MoveRoadmapItem::class)->execute(new MoveRoadmapItemInput(
        roadmapItemId: $third->getKey(),
        sortOrder: 0,
    ));

    expect($result->success)->toBeTrue()
        ->and($third->refresh()->sort_order)->toBe(0)
        ->and($first->refresh()->sort_order)->toBe(1)
        ->and($second->refresh()->sort_order)->toBe(2);
});

test('reordering does not touch items on another roadmap', function () {
    actAsUser();
    $roadmap = Roadmap::factory()->create();
    $other = Roadmap::factory()->create();

    $a = RoadmapItem::factory()->for($roadmap)->create(['sort_order' => 0]);
    $b = RoadmapItem::factory()->for($roadmap)->create(['sort_order' => 1]);
    $foreign = RoadmapItem::factory()->for($other)->create(['sort_order' => 5]);

    app(MoveRoadmapItem::class)->execute(new MoveRoadmapItemInput(
        roadmapItemId: $a->getKey(),
        sortOrder: 1,
    ));

    expect($b->refresh()->sort_order)->toBe(0)
        ->and($a->refresh()->sort_order)->toBe(1)
        ->and($foreign->refresh()->sort_order)->toBe(5);
});

test('it dispatches RoadmapItemMoved on success', function () {
    Event::fake();
    actAsUser();
    $item = RoadmapItem::factory()->create(['sort_order' => 0]);

    app(MoveRoadmapItem::class)->execute(new MoveRoadmapItemInput(
        roadmapItemId: $item->getKey(),
        sortOrder: 0,
    ));

    Event::assertDispatched(RoadmapItemMoved::class, fn (RoadmapItemMoved $event): bool => $event->item->is($item));
});

test('a non-user actor cannot reorder items', function () {
    app()->instance(Actor::class, new SystemActor);
    $item = RoadmapItem::factory()->create(['sort_order' => 3]);

    $result = app(MoveRoadmapItem::class)->execute(new MoveRoadmapItemInput(
        roadmapItemId: $item->getKey(),
        sortOrder: 0,
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor')
        ->and($item->refresh()->sort_order)->toBe(3);
});
