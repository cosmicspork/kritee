<?php

use App\Enums\RoadmapItemStatus;
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

    /**
     * Items grouped for display: what's moving first, then what's next,
     * then what has shipped.
     *
     * @return array<int, array{status: RoadmapItemStatus, items: \Illuminate\Support\Collection<int, RoadmapItem>}>
     */
    #[Computed]
    public function groups(): array
    {
        $byStatus = $this->items->groupBy(fn (RoadmapItem $item): string => $item->status->value);

        $groups = [];

        foreach ([RoadmapItemStatus::InProgress, RoadmapItemStatus::Planned, RoadmapItemStatus::Completed] as $status) {
            $items = $byStatus->get($status->value, collect());

            if ($items->isNotEmpty()) {
                $groups[] = ['status' => $status, 'items' => $items];
            }
        }

        return $groups;
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
        {{-- The ledger margin motif: status labels hang in the margin beside
             the rule, echoing the dashboard's account-book layout. --}}
        <div class="mt-8 grid grid-cols-1 gap-y-8 sm:grid-cols-[7rem_1fr]" data-test="roadmap-items">
            @foreach ($this->groups as $group)
                <div
                    class="pt-1 text-xs font-semibold uppercase tracking-wider text-base-content/60 sm:border-e-2 sm:border-(--madder) sm:pe-3 sm:text-end"
                    wire:key="group-label-{{ $group['status']->value }}"
                >
                    {{ $group['status']->label() }}
                </div>
                <ul class="space-y-3 sm:ps-5" wire:key="group-{{ $group['status']->value }}">
                    @foreach ($group['items'] as $item)
                        <li
                            wire:key="item-{{ $item->id }}"
                            @class([
                                'rounded-box border border-base-300 bg-base-100 p-4',
                                'border-s-4 border-s-primary' => $group['status'] === \App\Enums\RoadmapItemStatus::InProgress,
                                'opacity-70' => $group['status'] === \App\Enums\RoadmapItemStatus::Completed,
                            ])
                            data-test="roadmap-item-{{ $item->id }}"
                        >
                            <div class="flex items-center gap-2">
                                @if ($group['status'] === \App\Enums\RoadmapItemStatus::Completed)
                                    <x-icon name="o-check" class="h-4 w-4 text-success" />
                                @endif
                                <span class="font-medium">{{ $item->title }}</span>
                            </div>

                            @if ($item->description)
                                <p class="mt-1 text-sm text-base-content/60">{{ $item->description }}</p>
                            @endif

                            @if ($item->starts_at || $item->ends_at)
                                <p class="mt-2 font-mono text-xs text-base-content/50">
                                    {{ $item->starts_at?->toFormattedDateString() ?? '—' }}
                                    &rarr;
                                    {{ $item->ends_at?->toFormattedDateString() ?? '—' }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    @endif
</section>
