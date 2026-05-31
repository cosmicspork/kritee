<?php

use App\Actions\Project\ArchiveProject;
use App\Actions\Project\ArchiveProjectInput;
use App\Actions\Project\UpdateProject;
use App\Actions\Project\UpdateProjectInput;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts::app.sidebar'), Title('Edit project')] class extends Component {
    use Toast;

    #[Locked]
    public int $projectId;

    public string $name = '';

    public ?int $clientId = null;

    public string $description = '';

    public string $status = '';

    public ?string $budget = null;

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public function mount(Project $project): void
    {
        abort_unless(Auth::user()->can('update', $project), 403);

        $this->projectId = $project->id;
        $this->name = $project->name;
        $this->clientId = $project->client_id;
        $this->description = $project->description ?? '';
        $this->status = $project->status->value;
        $this->budget = $project->budget;
        $this->startsAt = $project->starts_at?->toDateString();
        $this->endsAt = $project->ends_at?->toDateString();
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

    #[Computed]
    public function isArchived(): bool
    {
        return $this->status === ProjectStatus::Archived->value;
    }

    public function canArchive(): bool
    {
        return Auth::user()->can('archive', Project::query()->findOrFail($this->projectId));
    }

    /**
     * Persist edits through {@see UpdateProject}; the action owns validation,
     * authorization, and the unit of work.
     */
    public function save(): void
    {
        $result = app(UpdateProject::class)->execute(new UpdateProjectInput(
            projectId: $this->projectId,
            name: $this->name,
            clientId: $this->clientId,
            description: $this->description !== '' ? $this->description : null,
            status: ProjectStatus::from($this->status),
            budget: $this->budget !== '' ? $this->budget : null,
            startsAt: $this->startsAt !== '' ? $this->startsAt : null,
            endsAt: $this->endsAt !== '' ? $this->endsAt : null,
        ));

        if (! $result->success) {
            foreach ($result->errors as $field => $message) {
                $this->addError(is_string($field) ? $field : 'name', (string) $message);
            }

            $this->error(__('Could not save the project.'));

            return;
        }

        $this->success(__('Project saved.'));
    }

    /**
     * Archive through {@see ArchiveProject}; authorization is enforced inside
     * the action.
     */
    public function archive(): void
    {
        $result = app(ArchiveProject::class)->execute(new ArchiveProjectInput(projectId: $this->projectId));

        if (! $result->success) {
            $this->error(reset($result->errors) ?: __('Unable to archive this project.'));

            return;
        }

        $this->status = ProjectStatus::Archived->value;

        $this->success(__('Project archived.'));

        $this->redirect(route('projects.index'), navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-6">
    <x-header :title="$name" :subtitle="__('Edit project')" separator>
        <x-slot:actions>
            <x-badge :value="ProjectStatus::from($status)->label()" class="badge-soft" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-input
            wire:model="name"
            :label="__('Name')"
            required
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
            <x-button type="submit" :label="__('Save')" class="btn-primary" data-test="save-project-button" />
        </x-slot:actions>
    </x-form>

    @if (! $this->isArchived && $this->canArchive())
        <x-card :title="__('Danger zone')" class="border border-error/30">
            <p class="mb-4 text-sm text-base-content/70">
                {{ __('Archiving hides the project from active lists. This does not delete its data.') }}
            </p>

            <x-button
                :label="__('Archive project')"
                wire:click="archive"
                wire:confirm="{{ __('Archive this project?') }}"
                class="btn-error btn-outline"
                data-test="archive-project-button"
            />
        </x-card>
    @endif
</section>
