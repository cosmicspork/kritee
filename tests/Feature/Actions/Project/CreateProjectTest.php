<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Project\CreateProject;
use App\Actions\Project\CreateProjectInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\ProjectStatus;
use App\Events\ProjectCreated;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

function bindCreateProjectActor(?User $user = null): User
{
    $user ??= User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('it creates a project for the acting user', function () {
    bindCreateProjectActor();
    $client = Client::factory()->create();

    $result = app(CreateProject::class)->execute(new CreateProjectInput(
        name: 'Website Redesign',
        clientId: $client->getKey(),
        description: 'Rework the marketing site.',
        budget: '5000.00',
    ));

    expect($result)->toBeInstanceOf(ActionResult::class)
        ->and($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(Project::class)
        ->and($result->data->slug)->toBe('website-redesign')
        ->and($result->data->status)->toBe(ProjectStatus::Active);

    $this->assertDatabaseHas('projects', [
        'name' => 'Website Redesign',
        'client_id' => $client->getKey(),
        'slug' => 'website-redesign',
        'budget' => '5000.00',
    ]);
});

test('it creates an internal project with no client', function () {
    bindCreateProjectActor();

    $result = app(CreateProject::class)->execute(new CreateProjectInput(
        name: 'Internal Tooling',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->data->client_id)->toBeNull();
});

test('a non-user actor cannot create a project', function () {
    app()->instance(Actor::class, new SystemActor);

    $result = app(CreateProject::class)->execute(new CreateProjectInput(
        name: 'Ghost Project',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    $this->assertDatabaseCount('projects', 0);
});

test('it rejects a budget below zero at validation', function () {
    bindCreateProjectActor();

    expect(fn () => CreateProjectInput::validateAndCreate([
        'name' => 'Bad Budget',
        'budget' => -10,
    ]))->toThrow(ValidationException::class);
});

test('it dispatches ProjectCreated on success', function () {
    Event::fake([ProjectCreated::class]);
    bindCreateProjectActor();

    $result = app(CreateProject::class)->execute(new CreateProjectInput(
        name: 'Eventful Project',
    ));

    Event::assertDispatched(
        ProjectCreated::class,
        fn (ProjectCreated $event): bool => $event->project->is($result->data),
    );
});

test('a repeated idempotency key creates the project only once', function () {
    bindCreateProjectActor();

    $input = new CreateProjectInput(
        name: 'Once Only',
        idempotencyKey: 'create-project-1',
    );

    $first = app(CreateProject::class)->execute($input);
    $second = app(CreateProject::class)->execute($input);

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey());

    $this->assertDatabaseCount('projects', 1);
});
