<?php

use App\Actions\Document\UpdateDocument;
use App\Actions\Document\UpdateDocumentInput;
use App\Models\Client;
use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts::app'), Title('Edit document')] class extends Component {
    use Toast;

    #[Locked]
    public int $documentId;

    #[Validate('required|string|max:255')]
    public string $title = '';

    public ?string $content = null;

    public ?int $clientId = null;

    /**
     * Hydrate the form from the document, authorizing before exposing any data.
     */
    public function mount(Document $document): void
    {
        $this->authorize('update', $document);

        $this->documentId = $document->getKey();
        $this->title = $document->title;
        $this->content = $document->content;
        $this->clientId = $document->client_id;
    }

    public function document(): Document
    {
        return Document::with('attachments')->findOrFail($this->documentId);
    }

    /**
     * @return array<int, array{id: int|string, name: string}>
     */
    public function clientOptions(): array
    {
        $options = Client::orderBy('name')->get(['id', 'name'])
            ->map(fn (Client $client): array => ['id' => $client->id, 'name' => $client->name])
            ->all();

        return array_merge([['id' => '', 'name' => __('— None —')]], $options);
    }

    /**
     * Persist edits through the action layer.
     */
    public function save(UpdateDocument $action): void
    {
        $this->validate();

        $result = $action->execute(UpdateDocumentInput::validateAndCreate([
            'document_id' => $this->documentId,
            'title' => $this->title,
            'content' => $this->content ?: null,
            'client_id' => $this->clientId ?: null,
        ]));

        if (! $result->success) {
            $this->mapErrors($result->errors);

            return;
        }

        $this->success(__('Document updated.'), redirectTo: route('documents.show', $this->documentId));
    }

    /**
     * @param  array<int|string, mixed>  $errors
     */
    private function mapErrors(array $errors): void
    {
        foreach ($errors as $field => $message) {
            $this->addError(is_string($field) ? $field : 'document', is_string($message) ? $message : __('Invalid value.'));
        }

        $first = collect($errors)->flatten()->first();
        $this->error(is_string($first) ? $first : __('Could not update the document.'));
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">{{ __('Edit document') }}</h1>
        <x-button :label="__('Back')" icon="o-arrow-left" :link="route('documents.show', $this->documentId)" class="btn-ghost" />
    </div>

    <x-card>
        <form wire:submit="save" class="flex flex-col gap-4">
            <x-input
                wire:model="title"
                :label="__('Title')"
                required
                data-test="document-title"
            />

            <x-textarea
                wire:model="content"
                :label="__('Content')"
                :placeholder="__('Write the document body…')"
                rows="8"
                data-test="document-content"
            />

            <x-select
                wire:model="clientId"
                :label="__('Client')"
                :options="$this->clientOptions()"
                data-test="document-client"
            />

            @if ($this->document()->attachments->isNotEmpty())
                <div>
                    <h3 class="mb-2 text-sm font-medium text-base-content/70">{{ __('Attachments') }}</h3>
                    <ul class="space-y-1">
                        @foreach ($this->document()->attachments as $attachment)
                            <li wire:key="attachment-{{ $attachment->id }}" class="flex items-center gap-2 text-sm">
                                <x-icon name="o-paper-clip" class="h-4 w-4 text-base-content/50" />
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->path) }}" target="_blank" class="link link-hover">
                                    {{ $attachment->filename }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <x-button :label="__('Cancel')" :link="route('documents.show', $this->documentId)" class="btn-ghost" />
                <x-button type="submit" :label="__('Save changes')" class="btn-primary" spinner="save" data-test="save-document-button" />
            </div>
        </form>
    </x-card>
</div>
