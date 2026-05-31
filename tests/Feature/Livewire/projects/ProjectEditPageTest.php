<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Project\ArchiveProject;
use App\Actions\Project\UpdateProject;
use App\Actions\Project\UpdateProjectInput;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->user);
    actAsUser($this->user);
});

test('the edit page hydrates from the bound project', function () {
    $client = Client::factory()->create(['name' => 'Acme']);
    $project = Project::factory()->create([
        'name' => 'Acme Portal',
        'client_id' => $client->id,
        'status' => ProjectStatus::OnHold,
    ]);

    Livewire::test('pages::projects.edit', ['project' => $project])
        ->assertOk()
        ->assertSet('name', 'Acme Portal')
        ->assertSet('clientId', $client->id)
        ->assertSet('status', ProjectStatus::OnHold->value);
});

test('saving invokes UpdateProject with the project id and changed fields', function () {
    $project = Project::factory()->create(['name' => 'Old Name']);

    $spy = Mockery::mock(UpdateProject::class);
    $spy->shouldReceive('execute')
        ->once()
        ->withArgs(function (UpdateProjectInput $input) use ($project): bool {
            return $input->projectId === $project->id
                && $input->name === 'New Name';
        })
        ->andReturn(ActionResult::success($project));
    app()->instance(UpdateProject::class, $spy);

    Livewire::test('pages::projects.edit', ['project' => $project])
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();
});

test('saving through the real action persists the changes', function () {
    $project = Project::factory()->create(['name' => 'Before']);

    Livewire::test('pages::projects.edit', ['project' => $project])
        ->set('name', 'After')
        ->call('save')
        ->assertHasNoErrors();

    expect($project->fresh()->name)->toBe('After');
});

test('an action failure surfaces errors and stays on the page', function () {
    $project = Project::factory()->create();

    $spy = Mockery::mock(UpdateProject::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::failure(['name' => 'The name is invalid.']));
    app()->instance(UpdateProject::class, $spy);

    Livewire::test('pages::projects.edit', ['project' => $project])
        ->set('name', 'Whatever')
        ->call('save')
        ->assertHasErrors('name')
        ->assertNoRedirect();
});

test('archive invokes ArchiveProject and redirects to the index on success', function () {
    $project = Project::factory()->create();

    $spy = Mockery::mock(ArchiveProject::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success($project));
    app()->instance(ArchiveProject::class, $spy);

    Livewire::test('pages::projects.edit', ['project' => $project])
        ->call('archive')
        ->assertHasNoErrors()
        ->assertRedirect(route('projects.index'));
});

test('a non-admin cannot see the archive control', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $this->actingAs($member);
    actAsUser($member);

    $project = Project::factory()->create();

    Livewire::test('pages::projects.edit', ['project' => $project])
        ->assertOk()
        ->assertDontSeeHtml('wire:click="archive"');
});
