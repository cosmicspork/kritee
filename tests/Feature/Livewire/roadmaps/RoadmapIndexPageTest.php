<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Roadmap\CreateRoadmap;
use App\Enums\RoadmapStatus;
use App\Enums\UserRole;
use App\Models\Roadmap;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->user);
    actAsUser($this->user);

    Route::livewire('roadmaps', 'pages::roadmaps.index')->name('roadmaps.index');
    Route::livewire('roadmaps/{roadmap}', 'pages::roadmaps.show')->name('roadmaps.show');
});

test('the index lists existing roadmaps', function () {
    Roadmap::factory()->create(['title' => 'Platform Roadmap']);
    Roadmap::factory()->archived()->create(['title' => 'Legacy Roadmap']);

    Livewire::test('pages::roadmaps.index')
        ->assertOk()
        ->assertSee('Platform Roadmap')
        ->assertSee('Legacy Roadmap');
});

test('opening the create modal resets the form', function () {
    Livewire::test('pages::roadmaps.index')
        ->set('title', 'Stale')
        ->call('openCreate')
        ->assertSet('showCreate', true)
        ->assertSet('title', '');
});

test('create invokes the CreateRoadmap action and redirects on success', function () {
    $roadmap = Roadmap::factory()->create();

    $spy = Mockery::mock(CreateRoadmap::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success($roadmap));
    app()->instance(CreateRoadmap::class, $spy);

    Livewire::test('pages::roadmaps.index')
        ->set('title', 'Q3 Plan')
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect(route('roadmaps.show', $roadmap));
});

test('create surfaces an action failure without redirecting', function () {
    $spy = Mockery::mock(CreateRoadmap::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::failure(['authorization' => 'Not allowed.']));
    app()->instance(CreateRoadmap::class, $spy);

    Livewire::test('pages::roadmaps.index')
        ->set('title', 'Q3 Plan')
        ->call('create')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSet('showCreate', true);
});

test('a blank title fails validation before touching the action', function () {
    $spy = Mockery::mock(CreateRoadmap::class);
    $spy->shouldNotReceive('execute');
    app()->instance(CreateRoadmap::class, $spy);

    Livewire::test('pages::roadmaps.index')
        ->set('title', '')
        ->call('create')
        ->assertHasErrors(['title' => 'required']);
});

test('creating through the real action persists an active roadmap', function () {
    Livewire::test('pages::roadmaps.index')
        ->set('title', 'Real Roadmap')
        ->set('isPublic', true)
        ->call('create');

    $roadmap = Roadmap::firstWhere('title', 'Real Roadmap');

    expect($roadmap)->not->toBeNull()
        ->and($roadmap->status)->toBe(RoadmapStatus::Active)
        ->and($roadmap->is_public)->toBeTrue();
});
