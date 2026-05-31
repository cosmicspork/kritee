<?php

use App\Actions\Project\CreateProject;
use App\Actions\Project\CreateProjectInput;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts::app.sidebar'), Title('New project')] class extends Component {
    use Toast;

    public string $name = '';

    public ?int $clientId = null;

    public string $description = '';

    public string $status = '';

    public ?string $budget = null;

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    // Gate the page during instantiation rather than in mount(): an HttpException
    // raised here propagates to the caller, whereas one raised in mount() is
    // caught and rendered as a 403 response. CreateProject enforces the real
    // authorization; this only keeps the form out of an unauthorized user's view.
    public function __construct()
    {
        abort_unless(Auth::user()?->can('create', Project::class), 403);
    }

    public function mount(): void
    {
        $this->status = ProjectStatus::Active->value;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    #[Computed]
    public function clientOptions(): array
    {
        return Client::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Client $client): array => ['id' => $client->id, 'name' => $client->name])
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return array_map(
            fn (ProjectStatus $status): array => ['id' => $status->value, 'name' => $status->label()],
            ProjectStatus::cases(),
        );
    }

    /**
     * Persist the new project through {@see CreateProject}; the action owns
     * validation, authorization, and the unit of work.
     */
    public function save(): void
    {
        $result = app(CreateProject::class)->execute(new CreateProjectInput(
            name: $this->name,
            clientId: $this->clientId,
            description: $this->description !== '' ? $this->description : null,
            status: $this->status !== '' ? ProjectStatus::from($this->status) : null,
            budget: $this->budget !== '' ? $this->budget : null,
            startsAt: $this->startsAt !== '' ? $this->startsAt : null,
            endsAt: $this->endsAt !== '' ? $this->endsAt : null,
        ));

        if (! $result->success) {
            foreach ($result->errors as $field => $message) {
                $this->addError(is_string($field) ? $field : 'name', (string) $message);
            }

            $this->error(__('Could not create the project.'));

            return;
        }

        $this->success(__('Project created.'));

        $this->redirect(route('projects.index'), navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-6">
    <x-header :title="__('New project')" :subtitle="__('Create a client or internal project')" separator />

    <x-form wire:submit="save">
        <x-input
            wire:model="name"
            :label="__('Name')"
            required
            autofocus
            data-test="project-name"
        />

        <x-select
            wire:model="clientId"
            :label="__('Client')"
            :options="$this->clientOptions"
            :placeholder="__('Internal (no client)')"
            data-test="project-client"
        />

        <x-select
            wire:model="status"
            :label="__('Status')"
            :options="$this->statusOptions"
            data-test="project-status"
        />

        <x-textarea
            wire:model="description"
            :label="__('Description')"
            rows="3"
            data-test="project-description"
        />

        <x-input
            wire:model="budget"
            :label="__('Budget')"
            type="number"
            step="0.01"
            min="0"
            prefix="$"
            data-test="project-budget"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input
                wire:model="startsAt"
                :label="__('Starts at')"
                type="date"
                data-test="project-starts-at"
            />

            <x-input
                wire:model="endsAt"
                :label="__('Ends at')"
                type="date"
                data-test="project-ends-at"
            />
        </div>

        <x-slot:actions>
            <x-button :label="__('Cancel')" :link="route('projects.index')" class="btn-ghost" />
            <x-button type="submit" :label="__('Create project')" class="btn-primary" data-test="save-project-button" />
        </x-slot:actions>
    </x-form>
</section>
