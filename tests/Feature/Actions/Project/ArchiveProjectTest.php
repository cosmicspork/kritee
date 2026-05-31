<?php

use App\Actions\Project\ArchiveProject;
use App\Actions\Project\ArchiveProjectInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Events\ProjectArchived;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function bindArchiveProjectActor(UserRole $role = UserRole::Admin): User
{
    $user = User::factory()->create(['role' => $role]);
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('an admin archives a project', function () {
    bindArchiveProjectActor();
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    $result = app(ArchiveProject::class)->execute(new ArchiveProjectInput(
        projectId: $project->getKey(),
    ));

    expect($result->success)->toBeTrue()
        ->and($project->refresh()->status)->toBe(ProjectStatus::Archived);
});

test('a non-admin user is not allowed to archive a project', function () {
    bindArchiveProjectActor(UserRole::Member);
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    $result = app(ArchiveProject::class)->execute(new ArchiveProjectInput(
        projectId: $project->getKey(),
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization')
        ->and($project->refresh()->status)->toBe(ProjectStatus::Active);
});

test('a non-user actor cannot archive a project', function () {
    app()->instance(Actor::class, new SystemActor);
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    $result = app(ArchiveProject::class)->execute(new ArchiveProjectInput(
        projectId: $project->getKey(),
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');
});

test('it dispatches ProjectArchived on success', function () {
    Event::fake([ProjectArchived::class]);
    bindArchiveProjectActor();
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    app(ArchiveProject::class)->execute(new ArchiveProjectInput(
        projectId: $project->getKey(),
    ));

    Event::assertDispatched(
        ProjectArchived::class,
        fn (ProjectArchived $event): bool => $event->project->is($project),
    );
});
