<?php

use App\Actions\Project\UpdateProject;
use App\Actions\Project\UpdateProjectInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\ProjectStatus;
use App\Events\ProjectUpdated;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

function bindUpdateProjectActor(?User $user = null): User
{
    $user ??= User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('it applies only the supplied fields and leaves the rest untouched', function () {
    bindUpdateProjectActor();
    $project = Project::factory()->create([
        'name' => 'Original',
        'description' => 'Keep me',
        'status' => ProjectStatus::Active,
    ]);

    $result = app(UpdateProject::class)->execute(new UpdateProjectInput(
        projectId: $project->getKey(),
        status: ProjectStatus::OnHold,
    ));

    expect($result->success)->toBeTrue();

    $project->refresh();
    expect($project->status)->toBe(ProjectStatus::OnHold)
        ->and($project->name)->toBe('Original')
        ->and($project->description)->toBe('Keep me');
});

test('it regenerates the slug when the name changes', function () {
    bindUpdateProjectActor();
    $project = Project::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    app(UpdateProject::class)->execute(new UpdateProjectInput(
        projectId: $project->getKey(),
        name: 'Brand New Name',
    ));

    expect($project->refresh()->slug)->toBe('brand-new-name');
});

test('it keeps the existing slug when the name is unchanged', function () {
    bindUpdateProjectActor();
    $project = Project::factory()->create(['name' => 'Stable', 'slug' => 'stable']);

    app(UpdateProject::class)->execute(new UpdateProjectInput(
        projectId: $project->getKey(),
        name: 'Stable',
        description: 'New description',
    ));

    expect($project->refresh()->slug)->toBe('stable');
});

test('a non-user actor cannot update a project', function () {
    app()->instance(Actor::class, new SystemActor);
    $project = Project::factory()->create(['name' => 'Untouched']);

    $result = app(UpdateProject::class)->execute(new UpdateProjectInput(
        projectId: $project->getKey(),
        name: 'Changed',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    expect($project->refresh()->name)->toBe('Untouched');
});

test('it rejects an update for a project that does not exist', function () {
    bindUpdateProjectActor();

    expect(fn () => UpdateProjectInput::validateAndCreate([
        'project_id' => 999999,
        'name' => 'Nope',
    ]))->toThrow(ValidationException::class);
});

test('it dispatches ProjectUpdated on success', function () {
    Event::fake([ProjectUpdated::class]);
    bindUpdateProjectActor();
    $project = Project::factory()->create();

    app(UpdateProject::class)->execute(new UpdateProjectInput(
        projectId: $project->getKey(),
        name: 'Updated',
    ));

    Event::assertDispatched(
        ProjectUpdated::class,
        fn (ProjectUpdated $event): bool => $event->project->is($project),
    );
});
