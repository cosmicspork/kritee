<?php

use App\Actions\Roadmap\CreateRoadmapItem;
use App\Actions\Roadmap\CreateRoadmapItemInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Enums\RoadmapItemStatus;
use App\Events\RoadmapItemCreated;
use App\Models\Roadmap;
use App\Models\RoadmapItem;
use Illuminate\Support\Facades\Event;

test('it creates an item and appends it after existing items', function () {
    actAsUser();
    $roadmap = Roadmap::factory()->create();
    RoadmapItem::factory()->for($roadmap)->create(['sort_order' => 0]);
    RoadmapItem::factory()->for($roadmap)->create(['sort_order' => 1]);

    $result = app(CreateRoadmapItem::class)->execute(new CreateRoadmapItemInput(
        roadmapId: $roadmap->getKey(),
        title: 'Ship it',
        status: RoadmapItemStatus::InProgress,
        startsAt: '2026-06-01',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(RoadmapItem::class)
        ->and($result->data->sort_order)->toBe(2)
        ->and($result->data->status)->toBe(RoadmapItemStatus::InProgress)
        ->and($result->data->starts_at->toDateString())->toBe('2026-06-01');
});

test('it dispatches RoadmapItemCreated on success', function () {
    Event::fake();
    actAsUser();
    $roadmap = Roadmap::factory()->create();

    $result = app(CreateRoadmapItem::class)->execute(new CreateRoadmapItemInput(
        roadmapId: $roadmap->getKey(),
        title: 'Task',
    ));

    Event::assertDispatched(RoadmapItemCreated::class, fn (RoadmapItemCreated $event): bool => $event->item->is($result->data));
});

test('a non-user actor cannot create an item', function () {
    app()->instance(Actor::class, new SystemActor);
    $roadmap = Roadmap::factory()->create();

    $result = app(CreateRoadmapItem::class)->execute(new CreateRoadmapItemInput(
        roadmapId: $roadmap->getKey(),
        title: 'Task',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor')
        ->and(RoadmapItem::count())->toBe(0);
});

test('a repeated idempotency key creates the item once', function () {
    actAsUser();
    $roadmap = Roadmap::factory()->create();

    $input = new CreateRoadmapItemInput(
        roadmapId: $roadmap->getKey(),
        title: 'Task',
        idempotencyKey: 'item-create-1',
    );

    $first = app(CreateRoadmapItem::class)->execute($input);
    $second = app(CreateRoadmapItem::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey())
        ->and(RoadmapItem::count())->toBe(1);
});
