<?php

use App\Actions\Roadmap\ArchiveRoadmap;
use App\Actions\Roadmap\ArchiveRoadmapInput;
use App\Enums\RoadmapStatus;
use App\Enums\UserRole;
use App\Events\RoadmapArchived;
use App\Models\Roadmap;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('an admin archives a roadmap', function () {
    actAsUser(User::factory()->create(['role' => UserRole::Admin]));
    $roadmap = Roadmap::factory()->create();

    $result = app(ArchiveRoadmap::class)->execute(new ArchiveRoadmapInput(roadmapId: $roadmap->getKey()));

    expect($result->success)->toBeTrue()
        ->and($roadmap->refresh()->status)->toBe(RoadmapStatus::Archived);
});

test('it dispatches RoadmapArchived on success', function () {
    Event::fake();
    actAsUser(User::factory()->create(['role' => UserRole::Admin]));
    $roadmap = Roadmap::factory()->create();

    app(ArchiveRoadmap::class)->execute(new ArchiveRoadmapInput(roadmapId: $roadmap->getKey()));

    Event::assertDispatched(RoadmapArchived::class, fn (RoadmapArchived $event): bool => $event->roadmap->is($roadmap));
});

test('a member is not authorized to archive a roadmap', function () {
    actAsUser(User::factory()->create(['role' => UserRole::Member]));
    $roadmap = Roadmap::factory()->create();

    $result = app(ArchiveRoadmap::class)->execute(new ArchiveRoadmapInput(roadmapId: $roadmap->getKey()));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization')
        ->and($roadmap->refresh()->status)->toBe(RoadmapStatus::Active);
});
