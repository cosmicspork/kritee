<?php

use App\Enums\RoadmapItemStatus;
use App\Models\Roadmap;
use App\Models\RoadmapItem;

test('the index lists public, active roadmaps to a guest', function () {
    Roadmap::factory()->public()->create(['title' => 'Shared roadmap']);
    Roadmap::factory()->create(['title' => 'Private roadmap']);
    Roadmap::factory()->public()->archived()->create(['title' => 'Retired roadmap']);

    $this->get(route('roadmap.index'))
        ->assertOk()
        ->assertSee('Shared roadmap')
        ->assertDontSee('Private roadmap')
        ->assertDontSee('Retired roadmap');
});

test('the detail page shows public items but hides private ones', function () {
    $roadmap = Roadmap::factory()->public()->create(['title' => 'Shared roadmap']);
    RoadmapItem::factory()->for($roadmap)->create(['title' => 'Visible milestone', 'is_public' => true]);
    RoadmapItem::factory()->for($roadmap)->create(['title' => 'Hidden milestone', 'is_public' => false]);

    $this->get(route('roadmap.show', $roadmap))
        ->assertOk()
        ->assertSee('Shared roadmap')
        ->assertSee('Visible milestone')
        ->assertDontSee('Hidden milestone');
});

test('the detail page groups items by status, in-progress first', function () {
    $roadmap = Roadmap::factory()->public()->create();
    RoadmapItem::factory()->for($roadmap)->create(['title' => 'Next up', 'is_public' => true, 'status' => RoadmapItemStatus::Planned]);
    RoadmapItem::factory()->for($roadmap)->create(['title' => 'Underway', 'is_public' => true, 'status' => RoadmapItemStatus::InProgress]);
    RoadmapItem::factory()->for($roadmap)->create(['title' => 'Delivered', 'is_public' => true, 'status' => RoadmapItemStatus::Completed]);

    $this->get(route('roadmap.show', $roadmap))
        ->assertOk()
        ->assertSeeInOrder(['In progress', 'Underway', 'Planned', 'Next up', 'Completed', 'Delivered']);
});

test('a private roadmap is not reachable publicly', function () {
    $roadmap = Roadmap::factory()->create();

    $this->get(route('roadmap.show', $roadmap))->assertNotFound();
});

test('an archived public roadmap is not reachable publicly', function () {
    $roadmap = Roadmap::factory()->public()->archived()->create();

    $this->get(route('roadmap.show', $roadmap))->assertNotFound();
});
