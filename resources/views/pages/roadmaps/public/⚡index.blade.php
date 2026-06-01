<?php

use App\Models\Roadmap;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public'), Title('Roadmap')] class extends Component {
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Roadmap>
     */
    #[Computed]
    public function roadmaps()
    {
        return Roadmap::query()
            ->public()
            ->active()
            ->withCount(['items' => fn (Builder $query) => $query->where('is_public', true)])
            ->with('client')
            ->latest()
            ->get();
    }
}; ?>

<section class="w-full">
    <x-header :title="__('Roadmap')" :subtitle="__('What we are working on next')" separator />

    @if ($this->roadmaps->isEmpty())
        <x-card class="mt-6">
            <div class="py-10 text-center text-base-content/60">
                {{ __('Nothing to share just yet. Check back soon.') }}
            </div>
        </x-card>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->roadmaps as $roadmap)
                <a
                    href="{{ route('roadmap.show', $roadmap) }}"
                    wire:navigate
                    wire:key="roadmap-{{ $roadmap->id }}"
                    class="block"
                    data-test="roadmap-card-{{ $roadmap->id }}"
                >
                    <x-card class="h-full transition hover:shadow-md">
                        <h2 class="font-medium">{{ $roadmap->title }}</h2>

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
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</section>
