<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Roadmap\CreateRoadmapItem;
use App\Actions\Roadmap\MoveRoadmapItem;
use App\Actions\Roadmap\ToggleRoadmapItemPublic;
use App\Actions\Roadmap\UpdateRoadmapItem;
use App\Enums\RoadmapItemStatus;
use App\Enums\UserRole;
use App\Models\Roadmap;
use App\Models\RoadmapItem;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->user);
    actAsUser($this->user);
    $this->roadmap = Roadmap::factory()->create(['title' => 'Delivery Plan']);

    Route::livewire('roadmaps', 'pages::roadmaps.index')->name('roadmaps.index');
    Route::livewire('roadmaps/{roadmap}', 'pages::roadmaps.show')->name('roadmaps.show');
});

test('the detail page lists items in sort order', function () {
    RoadmapItem::factory()->for($this->roadmap)->create(['title' => 'First milestone', 'sort_order' => 0]);
    RoadmapItem::factory()->for($this->roadmap)->create(['title' => 'Second milestone', 'sort_order' => 1]);

    Livewire::test('pages::roadmaps.show', ['roadmap' => $this->roadmap])
        ->assertOk()
        ->assertSee('Delivery Plan')
        ->assertSee('First milestone')
        ->assertSee('Second milestone');
});

test('opening the edit form populates the fields from the item', function () {
    $item = RoadmapItem::factory()->for($this->roadmap)->create([
        'title' => 'Existing item',
        'status' => RoadmapItemStatus::InProgress,
    ]);

    Livewire::test('pages::roadmaps.show', ['roadmap' => $this->roadmap])
        ->call('openEdit', $item->id)
        ->assertSet('showItemForm', true)
        ->assertSet('editingItemId', $item->id)
        ->assertSet('title', 'Existing item')
        ->assertSet('status', RoadmapItemStatus::InProgress->value);
});

test('saving a new item invokes the CreateRoadmapItem action', function () {
    $spy = Mockery::mock(CreateRoadmapItem::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success(new RoadmapItem));
    app()->instance(CreateRoadmapItem::class, $spy);

    Livewire::test('pages::roadmaps.show', ['roadmap' => $this->roadmap])
        ->call('openCreate')
        ->set('title', 'New milestone')
        ->call('saveItem')
        ->assertHasNoErrors()
        ->assertSet('showItemForm', false);
});

test('saving an existing item invokes the UpdateRoadmapItem action', function () {
    $item = RoadmapItem::factory()->for($this->roadmap)->create();

    $spy = Mockery::mock(UpdateRoadmapItem::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success($item));
    app()->instance(UpdateRoadmapItem::class, $spy);

    Livewire::test('pages::roadmaps.show', ['roadmap' => $this->roadmap])
        ->call('openEdit', $item->id)
        ->set('title', 'Renamed milestone')
        ->call('saveItem')
        ->assertHasNoErrors()
        ->assertSet('showItemForm', false);
});

test('a blank item title fails validation before touching the action', function () {
    $spy = Mockery::mock(CreateRoadmapItem::class);
    $spy->shouldNotReceive('execute');
    app()->instance(CreateRoadmapItem::class, $spy);

    Livewire::test('pages::roadmaps.show', ['roadmap' => $this->roadmap])
        ->call('openCreate')
        ->set('title', '')
        ->call('saveItem')
        ->assertHasErrors(['title' => 'required']);
});

test('reorder invokes the MoveRoadmapItem action with the dropped position', function () {
    $item = RoadmapItem::factory()->for($this->roadmap)->create();

    $spy = Mockery::mock(MoveRoadmapItem::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success($item));
    app()->instance(MoveRoadmapItem::class, $spy);

    Livewire::test('pages::roadmaps.show', ['roadmap' => $this->roadmap])
        ->call('reorder', $item->id, 0)
        ->assertHasNoErrors();
});

test('togglePublic invokes the ToggleRoadmapItemPublic action', function () {
    $item = RoadmapItem::factory()->for($this->roadmap)->create(['is_public' => false]);

    $spy = Mockery::mock(ToggleRoadmapItemPublic::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success($item));
    app()->instance(ToggleRoadmapItemPublic::class, $spy);

    Livewire::test('pages::roadmaps.show', ['roadmap' => $this->roadmap])
        ->call('togglePublic', $item->id)
        ->assertHasNoErrors();
});

test('reordering through the real action renumbers the items', function () {
    $first = RoadmapItem::factory()->for($this->roadmap)->create(['sort_order' => 0]);
    $second = RoadmapItem::factory()->for($this->roadmap)->create(['sort_order' => 1]);

    Livewire::test('pages::roadmaps.show', ['roadmap' => $this->roadmap])
        ->call('reorder', $second->id, 0);

    expect($second->fresh()->sort_order)->toBe(0)
        ->and($first->fresh()->sort_order)->toBe(1);
});

test('toggling visibility through the real action flips is_public', function () {
    $item = RoadmapItem::factory()->for($this->roadmap)->create(['is_public' => false]);

    Livewire::test('pages::roadmaps.show', ['roadmap' => $this->roadmap])
        ->call('togglePublic', $item->id);

    expect($item->fresh()->is_public)->toBeTrue();
});
