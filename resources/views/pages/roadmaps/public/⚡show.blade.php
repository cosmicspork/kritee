<?php

use App\Enums\RoadmapStatus;
use App\Models\Roadmap;
use App\Models\RoadmapItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public'), Title('Roadmap')] class extends Component {
    #[Locked]
    public Roadmap $roadmap;

    public function mount(Roadmap $roadmap): void
    {
        abort_unless($roadmap->is_public && $roadmap->status === RoadmapStatus::Active, 404);

        $this->roadmap = $roadmap;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, RoadmapItem>
     */
    #[Computed]
    public function items()
    {
        return $this->roadmap->items()
            ->public()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}; ?>

<section class="w-full">
    <x-header :title="$roadmap->title" :subtitle="$roadmap->description" separator>
        <x-slot:actions>
            <x-button
                label="{{ __('All roadmaps') }}"
                icon="o-arrow-left"
                link="{{ route('roadmap.index') }}"
                class="btn-ghost"
            />
        </x-slot:actions>
    </x-header>

    @if ($this->items->isEmpty())
        <x-card class="mt-6">
            <div class="py-10 text-center text-base-content/60">
                {{ __('No public items on this roadmap yet.') }}
            </div>
        </x-card>
    @else
        <ul class="mt-6 space-y-3" data-test="roadmap-items">
            @foreach ($this->items as $item)
                <li
                    wire:key="item-{{ $item->id }}"
                    class="rounded-box border border-base-300 bg-base-100 p-4"
                    data-test="roadmap-item-{{ $item->id }}"
                >
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
                </li>
            @endforeach
        </ul>
    @endif
</section>
