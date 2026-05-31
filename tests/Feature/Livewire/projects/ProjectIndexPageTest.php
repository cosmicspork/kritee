<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Project\ArchiveProject;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->user);
    actAsUser($this->user);
});

test('the index lists active projects and hides archived ones by default', function () {
    $active = Project::factory()->create(['name' => 'Active Build']);
    $archived = Project::factory()->archived()->create(['name' => 'Old Build']);

    Livewire::test('pages::projects.index')
        ->assertOk()
        ->assertSee('Active Build')
        ->assertDontSee('Old Build');
});

test('archived projects appear when explicitly requested', function () {
    Project::factory()->archived()->create(['name' => 'Old Build']);

    Livewire::test('pages::projects.index')
        ->set('includeArchived', true)
        ->assertSee('Old Build');
});

test('searching by name filters the list', function () {
    Project::factory()->create(['name' => 'Marketing Site']);
    Project::factory()->create(['name' => 'Mobile App']);

    Livewire::test('pages::projects.index')
        ->set('search', 'Marketing')
        ->assertSee('Marketing Site')
        ->assertDontSee('Mobile App');
});

test('archive invokes the ArchiveProject action and reflects success', function () {
    $project = Project::factory()->create(['name' => 'To Archive']);

    $spy = Mockery::mock(ArchiveProject::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success($project));
    app()->instance(ArchiveProject::class, $spy);

    Livewire::test('pages::projects.index')
        ->call('archive', $project->id)
        ->assertHasNoErrors();
});

test('archive surfaces an action failure without throwing', function () {
    $project = Project::factory()->create();

    $spy = Mockery::mock(ArchiveProject::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::failure(['authorization' => 'Not allowed.']));
    app()->instance(ArchiveProject::class, $spy);

    Livewire::test('pages::projects.index')
        ->call('archive', $project->id)
        ->assertHasNoErrors();
});

test('archiving a project through the real action moves it to the archived state', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    Livewire::test('pages::projects.index')
        ->call('archive', $project->id);

    expect($project->fresh()->status)->toBe(ProjectStatus::Archived);
});
