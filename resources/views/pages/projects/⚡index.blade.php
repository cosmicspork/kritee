<?php

use App\Actions\Project\ArchiveProject;
use App\Actions\Project\ArchiveProjectInput;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Layout('layouts::app.sidebar'), Title('Projects')] class extends Component {
    use Toast, WithPagination;

    public string $search = '';

    public string $status = '';

    public bool $includeArchived = false;

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
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Project>
     */
    #[Computed]
    public function projects()
    {
        return Project::query()
            ->with('client')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when(
                ! $this->includeArchived && $this->status === '',
                fn ($query) => $query->where('status', '!=', ProjectStatus::Archived),
            )
            ->latest()
            ->paginate(15);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedIncludeArchived(): void
    {
        $this->resetPage();
    }

    /**
     * Archive a project through the action layer; authorization and the unit of
     * work live inside {@see ArchiveProject}.
     */
    public function archive(int $project): void
    {
        $result = app(ArchiveProject::class)->execute(new ArchiveProjectInput(projectId: $project));

        if (! $result->success) {
            $errors = $result->errors;

            $this->error(reset($errors) ?: __('Unable to archive this project.'));

            return;
        }

        unset($this->projects);

        $this->success(__('Project archived.'));
    }

    public function canCreate(): bool
    {
        return Auth::user()->can('create', Project::class);
    }
}; ?>

<section class="w-full space-y-6">
    <x-header :title="__('Projects')" :subtitle="__('Client and internal work')" separator>
        <x-slot:actions>
            @if ($this->canCreate())
                <x-button
                    :label="__('New project')"
                    icon="o-plus"
                    :link="route('projects.create')"
                    class="btn-primary"
                    data-test="create-project-button"
                />
            @endif
        </x-slot:actions>
    </x-header>

    <div class="grid gap-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
        <x-input
            wire:model.live.debounce.300ms="search"
            :label="__('Search')"
            icon="o-magnifying-glass"
            :placeholder="__('Search by name')"
            clearable
            data-test="projects-search"
        />

        <x-select
            wire:model.live="status"
            :label="__('Status')"
            :options="$this->statusOptions"
            :placeholder="__('All statuses')"
            data-test="projects-status-filter"
        />

        <x-checkbox
            wire:model.live="includeArchived"
            :label="__('Include archived')"
            data-test="projects-include-archived"
        />
    </div>

    @if ($this->projects->isEmpty())
        <x-card>
            <p class="py-8 text-center text-base-content/60">{{ __('No projects found.') }}</p>
        </x-card>
    @else
        <table class="table" data-test="projects-table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Client') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Budget') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->projects as $project)
                    <tr wire:key="project-{{ $project->id }}">
                        <td>
                            <a
                                href="{{ route('projects.edit', $project) }}"
                                class="link link-hover font-medium"
                                wire:navigate
                                data-test="project-link-{{ $project->id }}"
                            >
                                {{ $project->name }}
                            </a>
                        </td>
                        <td class="text-base-content/70">
                            {{ $project->client?->name ?? __('Internal') }}
                        </td>
                        <td><x-badge :value="$project->status->label()" class="badge-soft" /></td>
                        <td class="text-base-content/70">
                            {{ $project->budget !== null ? number_format((float) $project->budget, 2) : '—' }}
                        </td>
                        <td class="flex justify-end gap-2">
                            <x-button
                                :label="__('Edit')"
                                :link="route('projects.edit', $project)"
                                class="btn-ghost btn-sm"
                                data-test="edit-project-{{ $project->id }}"
                            />

                            @if ($project->status !== \App\Enums\ProjectStatus::Archived)
                                <x-button
                                    :label="__('Archive')"
                                    wire:click="archive({{ $project->id }})"
                                    wire:confirm="{{ __('Archive this project?') }}"
                                    class="btn-ghost btn-sm text-error"
                                    data-test="archive-project-{{ $project->id }}"
                                />
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div>{{ $this->projects->links() }}</div>
    @endif
</section>
