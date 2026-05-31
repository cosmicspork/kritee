<?php

use App\Actions\Roadmap\CreateRoadmapItem;
use App\Actions\Roadmap\CreateRoadmapItemInput;
use App\Actions\Roadmap\MoveRoadmapItem;
use App\Actions\Roadmap\MoveRoadmapItemInput;
use App\Actions\Roadmap\ToggleRoadmapItemPublic;
use App\Actions\Roadmap\ToggleRoadmapItemPublicInput;
use App\Actions\Roadmap\UpdateRoadmapItem;
use App\Actions\Roadmap\UpdateRoadmapItemInput;
use App\Enums\RoadmapItemStatus;
use App\Models\Roadmap;
use App\Models\RoadmapItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Roadmap')] class extends Component {
    use Toast;

    public Roadmap $roadmap;

    public bool $showItemForm = false;

    public ?int $editingItemId = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public ?string $description = null;

    #[Validate('required|in:planned,in_progress,completed')]
    public string $status = RoadmapItemStatus::Planned->value;

    #[Validate('nullable|date')]
    public ?string $startsAt = null;

    #[Validate('nullable|date')]
    public ?string $endsAt = null;

    public function mount(Roadmap $roadmap): void
    {
        $this->roadmap = $roadmap;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, RoadmapItem>
     */
    #[Computed]
    public function items()
    {
        return $this->roadmap->items()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return array_map(
            fn (RoadmapItemStatus $status): array => ['id' => $status->value, 'name' => $status->label()],
            RoadmapItemStatus::cases(),
        );
    }

    public function openCreate(): void
    {
        $this->reset('title', 'description', 'startsAt', 'endsAt', 'editingItemId');
        $this->status = RoadmapItemStatus::Planned->value;
        $this->resetValidation();
        $this->showItemForm = true;
    }

    public function openEdit(int $itemId): void
    {
        $item = $this->roadmap->items()->findOrFail($itemId);

        $this->editingItemId = $item->id;
        $this->title = $item->title;
        $this->description = $item->description;
        $this->status = $item->status->value;
        $this->startsAt = $item->starts_at?->toDateString();
        $this->endsAt = $item->ends_at?->toDateString();
        $this->resetValidation();
        $this->showItemForm = true;
    }

    public function saveItem(CreateRoadmapItem $create, UpdateRoadmapItem $update): void
    {
        $this->validate();

        $result = $this->editingItemId === null
            ? $create->execute(new CreateRoadmapItemInput(
                roadmapId: $this->roadmap->id,
                title: $this->title,
                description: $this->description,
                status: RoadmapItemStatus::from($this->status),
                startsAt: $this->startsAt,
                endsAt: $this->endsAt,
            ))
            : $update->execute(new UpdateRoadmapItemInput(
                roadmapItemId: $this->editingItemId,
                title: $this->title,
                description: $this->description,
                status: RoadmapItemStatus::from($this->status),
                startsAt: $this->startsAt,
                endsAt: $this->endsAt,
            ));

        if (! $result->success) {
            $this->error(implode(' ', $result->errors));

            return;
        }

        $this->showItemForm = false;
        unset($this->items);

        $this->success($this->editingItemId === null ? __('Item added.') : __('Item updated.'));
    }

    public function reorder(int $id, int $position, MoveRoadmapItem $action): void
    {
        $result = $action->execute(new MoveRoadmapItemInput(
            roadmapItemId: $id,
            sortOrder: $position,
        ));

        if (! $result->success) {
            $this->error(implode(' ', $result->errors));
        }

        unset($this->items);
    }

    public function togglePublic(int $itemId, ToggleRoadmapItemPublic $action): void
    {
        $item = $this->roadmap->items()->findOrFail($itemId);

        $result = $action->execute(new ToggleRoadmapItemPublicInput(
            roadmapItemId: $item->id,
            isPublic: ! $item->is_public,
        ));

        if (! $result->success) {
            $this->error(implode(' ', $result->errors));
        }

        unset($this->items);
    }
}; ?>

<section class="w-full">
    <x-header :title="$roadmap->title" :subtitle="$roadmap->description" separator>
        <x-slot:middle class="!justify-start">
            <x-badge :value="$roadmap->status->label()" class="badge-soft" />
            @if ($roadmap->is_public)
                <x-badge :value="__('Public')" class="badge-soft badge-success" />
            @endif
        </x-slot:middle>

        <x-slot:actions>
            <x-button
                label="{{ __('Back') }}"
                icon="o-arrow-left"
                link="{{ route('roadmaps.index') }}"
                class="btn-ghost"
            />
            <x-button
                label="{{ __('Add item') }}"
                icon="o-plus"
                wire:click="openCreate"
                class="btn-primary"
                data-test="add-item-button"
            />
        </x-slot:actions>
    </x-header>

    @if ($this->items->isEmpty())
        <x-card class="mt-6">
            <div class="py-10 text-center text-base-content/60">
                {{ __('No items yet. Add the first milestone to this roadmap.') }}
            </div>
        </x-card>
    @else
        <ul wire:sort="reorder" class="mt-6 space-y-3" data-test="roadmap-items">
            @foreach ($this->items as $item)
                <li
                    wire:key="item-{{ $item->id }}"
                    wire:sort:item="{{ $item->id }}"
                    class="flex items-start gap-3 rounded-box border border-base-300 bg-base-100 p-4"
                    data-test="roadmap-item-{{ $item->id }}"
                >
                    <button
                        type="button"
                        wire:sort:handle
                        class="mt-1 cursor-grab text-base-content/40 hover:text-base-content"
                        aria-label="{{ __('Drag to reorder') }}"
                    >
                        <x-icon name="o-bars-2" class="h-5 w-5" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $item->title }}</span>
                            <x-badge :value="$item->status->label()" class="badge-soft badge-sm" />
                        </div>

                        @if ($item->description)
                            <p class="mt-1 text-sm text-base-content/60">{{ $item->description }}</p>
                        @endif

                        @if ($item->starts_at || $item->ends_at)
                            <p class="mt-2 text-xs text-base-content/50">
                                {{ $item->starts_at?->toFormattedDateString() ?? '—' }}
                                &rarr;
                                {{ $item->ends_at?->toFormattedDateString() ?? '—' }}
                            </p>
                        @endif
                    </div>

                    <div wire:sort:ignore class="flex items-center gap-2">
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-base-content/70">
                            <input
                                type="checkbox"
                                class="toggle toggle-sm toggle-success"
                                @checked($item->is_public)
                                wire:click="togglePublic({{ $item->id }})"
                                data-test="toggle-public-{{ $item->id }}"
                            />
                            {{ __('Public') }}
                        </label>

                        <x-button
                            icon="o-pencil-square"
                            wire:click="openEdit({{ $item->id }})"
                            class="btn-ghost btn-sm"
                            aria-label="{{ __('Edit item') }}"
                            data-test="edit-item-{{ $item->id }}"
                        />
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <x-modal wire:model="showItemForm" :title="$editingItemId ? __('Edit item') : __('Add item')" box-class="max-w-lg" class="backdrop-blur">
        <form wire:submit="saveItem" class="space-y-4">
            <x-input
                wire:model="title"
                label="{{ __('Title') }}"
                required
                data-test="item-title"
            />

            <x-textarea
                wire:model="description"
                label="{{ __('Description') }}"
                rows="3"
                data-test="item-description"
            />

            <x-select
                wire:model="status"
                label="{{ __('Status') }}"
                :options="$this->statusOptions"
                data-test="item-status"
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input
                    wire:model="startsAt"
                    label="{{ __('Starts') }}"
                    type="date"
                    data-test="item-starts-at"
                />
                <x-input
                    wire:model="endsAt"
                    label="{{ __('Ends') }}"
                    type="date"
                    data-test="item-ends-at"
                />
            </div>

            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showItemForm', false)" />
                <x-button
                    type="submit"
                    label="{{ $editingItemId ? __('Save item') : __('Add item') }}"
                    class="btn-primary"
                    spinner="saveItem"
                    data-test="save-item-button"
                />
            </x-slot:actions>
        </form>
    </x-modal>
</section>
