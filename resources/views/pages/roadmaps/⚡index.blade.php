<?php

use App\Actions\Roadmap\CreateRoadmap;
use App\Actions\Roadmap\CreateRoadmapInput;
use App\Enums\RoadmapStatus;
use App\Models\Client;
use App\Models\Roadmap;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Roadmaps')] class extends Component {
    use Toast;

    public bool $showCreate = false;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public ?string $description = null;

    #[Validate('nullable|integer|exists:clients,id')]
    public ?int $clientId = null;

    public bool $isPublic = false;

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Roadmap>
     */
    #[Computed]
    public function roadmaps()
    {
        return Roadmap::query()
            ->withCount('items')
            ->with('client')
            ->orderByRaw("status = ? desc", [RoadmapStatus::Active->value])
            ->latest()
            ->get();
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

    public function openCreate(): void
    {
        $this->reset('title', 'description', 'clientId', 'isPublic');
        $this->resetValidation();
        $this->showCreate = true;
    }

    public function create(CreateRoadmap $action): void
    {
        $this->validate();

        $result = $action->execute(new CreateRoadmapInput(
            title: $this->title,
            description: $this->description,
            clientId: $this->clientId,
            isPublic: $this->isPublic,
        ));

        if (! $result->success) {
            $this->showCreate = true;
            $this->error(implode(' ', $result->errors));

            return;
        }

        $this->showCreate = false;
        unset($this->roadmaps);

        $this->redirectRoute('roadmaps.show', $result->data, navigate: true);
    }
}; ?>

<section class="w-full">
    <x-header :title="__('Roadmaps')" :subtitle="__('Plan and share what is coming next')" separator>
        <x-slot:actions>
            <x-button
                label="{{ __('New roadmap') }}"
                icon="o-plus"
                wire:click="openCreate"
                class="btn-primary"
                data-test="new-roadmap-button"
            />
        </x-slot:actions>
    </x-header>

    @if ($this->roadmaps->isEmpty())
        <x-card class="mt-6">
            <div class="py-10 text-center text-base-content/60">
                {{ __('No roadmaps yet. Create your first one to start planning.') }}
            </div>
        </x-card>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->roadmaps as $roadmap)
                <a
                    href="{{ route('roadmaps.show', $roadmap) }}"
                    wire:navigate
                    wire:key="roadmap-{{ $roadmap->id }}"
                    class="block"
                    data-test="roadmap-card-{{ $roadmap->id }}"
                >
                    <x-card class="h-full transition hover:shadow-md">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="font-medium">{{ $roadmap->title }}</h2>
                            <x-badge :value="$roadmap->status->label()" class="badge-soft" />
                        </div>

                        @if ($roadmap->description)
                            <p class="mt-2 line-clamp-2 text-sm text-base-content/60">{{ $roadmap->description }}</p>
                        @endif

                        <div class="mt-4 flex items-center gap-3 text-sm text-base-content/60">
                            <span class="flex items-center gap-1">
                                <x-icon name="o-list-bullet" class="h-4 w-4" />
                                {{ trans_choice(':count item|:count items', $roadmap->items_count, ['count' => $roadmap->items_count]) }}
                            </span>
                            @if ($roadmap->client)
                                <span class="flex items-center gap-1">
                                    <x-icon name="o-building-office-2" class="h-4 w-4" />
                                    {{ $roadmap->client->name }}
                                </span>
                            @endif
                            @if ($roadmap->is_public)
                                <x-badge :value="__('Public')" class="badge-soft badge-success badge-sm" />
                            @endif
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif

    <x-modal wire:model="showCreate" :title="__('New roadmap')" box-class="max-w-lg" class="backdrop-blur">
        <form wire:submit="create" class="space-y-4">
            <x-input
                wire:model="title"
                label="{{ __('Title') }}"
                required
                data-test="roadmap-title"
            />

            <x-textarea
                wire:model="description"
                label="{{ __('Description') }}"
                rows="3"
                data-test="roadmap-description"
            />

            <x-select
                wire:model="clientId"
                label="{{ __('Client') }}"
                :options="$this->clientOptions"
                placeholder="{{ __('No client') }}"
                placeholder-value=""
                data-test="roadmap-client"
            />

            <x-toggle
                wire:model="isPublic"
                label="{{ __('Publicly visible') }}"
                hint="{{ __('Controls whether the roadmap itself is shared.') }}"
                data-test="roadmap-public"
            />

            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showCreate', false)" />
                <x-button
                    type="submit"
                    label="{{ __('Create roadmap') }}"
                    class="btn-primary"
                    spinner="create"
                    data-test="create-roadmap-button"
                />
            </x-slot:actions>
        </form>
    </x-modal>
</section>
