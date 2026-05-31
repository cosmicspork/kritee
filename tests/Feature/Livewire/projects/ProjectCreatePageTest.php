<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Project\CreateProject;
use App\Actions\Project\CreateProjectInput;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    actAsUser($this->user);
});

test('the create page renders with the default active status', function () {
    Livewire::test('pages::projects.create')
        ->assertOk()
        ->assertSet('status', ProjectStatus::Active->value);
});

test('saving invokes CreateProject with the form values and redirects on success', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->make();

    $spy = Mockery::mock(CreateProject::class);
    $spy->shouldReceive('execute')
        ->once()
        ->withArgs(function (CreateProjectInput $input) use ($client): bool {
            return $input->name === 'Website Redesign'
                && $input->clientId === $client->id
                && $input->status === ProjectStatus::Active;
        })
        ->andReturn(ActionResult::success($project));
    app()->instance(CreateProject::class, $spy);

    Livewire::test('pages::projects.create')
        ->set('name', 'Website Redesign')
        ->set('clientId', $client->id)
        ->set('status', ProjectStatus::Active->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('projects.index'));
});

test('saving through the real action persists the project', function () {
    $client = Client::factory()->create();

    Livewire::test('pages::projects.create')
        ->set('name', 'Internal Tooling')
        ->set('clientId', $client->id)
        ->set('budget', '5000.00')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('projects', [
        'name' => 'Internal Tooling',
        'client_id' => $client->id,
        'budget' => '5000.00',
    ]);
});

test('an action failure surfaces field errors and keeps the user on the page', function () {
    $spy = Mockery::mock(CreateProject::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::failure(['name' => 'The name is required.']));
    app()->instance(CreateProject::class, $spy);

    Livewire::test('pages::projects.create')
        ->set('name', 'Anything')
        ->call('save')
        ->assertHasErrors('name')
        ->assertNoRedirect();
});

test('the create page denies access when the gate forbids it', function () {
    Gate::policy(Project::class, DenyProjectsPolicy::class);

    expect(fn () => Livewire::test('pages::projects.create'))
        ->toThrow(HttpException::class);
});

class DenyProjectsPolicy
{
    public function create(User $user): bool
    {
        return false;
    }
}
