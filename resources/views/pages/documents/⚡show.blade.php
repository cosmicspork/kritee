<?php

use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app'), Title('Document')] class extends Component {
    #[Locked]
    public Document $document;

    public function mount(Document $document): void
    {
        $this->authorize('view', $document);

        $this->document = $document->load(['client', 'uploadedBy', 'attachments']);
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <x-header :title="$document->title" separator>
        <x-slot:subtitle>
            <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-base-content/60">
                @if ($document->client)
                    <span class="flex items-center gap-1">
                        <x-icon name="o-building-office-2" class="h-4 w-4" />
                        {{ $document->client->name }}
                    </span>
                @endif
                <span class="flex items-center gap-1">
                    <x-icon name="o-user" class="h-4 w-4" />
                    {{ $document->uploadedBy?->name ?? __('Unknown') }}
                </span>
                <span class="flex items-center gap-1">
                    <x-icon name="o-calendar" class="h-4 w-4" />
                    {{ $document->created_at->format('M j, Y') }}
                </span>
            </div>
        </x-slot:subtitle>

        <x-slot:actions>
            @can('update', $document)
                <x-button :label="__('Edit')" icon="o-pencil-square" :link="route('documents.edit', $document)" class="btn-ghost" />
            @endcan
            <x-button :label="__('All documents')" icon="o-arrow-left" :link="route('documents.index')" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <x-card>
        @if ($document->content)
            <div class="whitespace-pre-wrap text-base-content/90">{{ $document->content }}</div>
        @else
            <p class="text-base-content/50">{{ __('This document has no content.') }}</p>
        @endif
    </x-card>

    @if ($document->attachments->isNotEmpty())
        <x-card :title="__('Attachments')">
            <ul class="space-y-1">
                @foreach ($document->attachments as $attachment)
                    <li wire:key="attachment-{{ $attachment->id }}" class="flex items-center gap-2 text-sm">
                        <x-icon name="o-paper-clip" class="h-4 w-4 text-base-content/50" />
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->path) }}" target="_blank" class="link link-hover">
                            {{ $attachment->filename }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</div>
