<?php

use App\Actions\Roadmap\UpdateRoadmapItem;
use App\Actions\Roadmap\UpdateRoadmapItemInput;
use App\Enums\RoadmapItemStatus;
use App\Events\RoadmapItemUpdated;
use App\Models\RoadmapItem;
use Illuminate\Support\Facades\Event;

test('it updates only the provided fields', function () {
    actAsUser();
    $item = RoadmapItem::factory()->create([
        'title' => 'Old',
        'description' => 'Keep me',
        'status' => RoadmapItemStatus::Planned,
    ]);

    $result = app(UpdateRoadmapItem::class)->execute(new UpdateRoadmapItemInput(
        roadmapItemId: $item->getKey(),
        title: 'New',
        status: RoadmapItemStatus::Completed,
    ));

    expect($result->success)->toBeTrue();

    $item->refresh();

    expect($item->title)->toBe('New')
        ->and($item->description)->toBe('Keep me')
        ->and($item->status)->toBe(RoadmapItemStatus::Completed);
});

test('it dispatches RoadmapItemUpdated on success', function () {
    Event::fake();
    actAsUser();
    $item = RoadmapItem::factory()->create();

    app(UpdateRoadmapItem::class)->execute(new UpdateRoadmapItemInput(
        roadmapItemId: $item->getKey(),
        title: 'Renamed',
    ));

    Event::assertDispatched(RoadmapItemUpdated::class, fn (RoadmapItemUpdated $event): bool => $event->item->is($item));
});

test('updating a missing item fails', function () {
    actAsUser();

    $result = app(UpdateRoadmapItem::class)->execute(new UpdateRoadmapItemInput(
        roadmapItemId: 9999,
        title: 'Ghost',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('roadmap_item_id');
});
